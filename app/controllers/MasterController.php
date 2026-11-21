<?php
// app/controllers/MasterController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/ImporterService.php';
require_once __DIR__ . '/AuthController.php';

class MasterController {
    private PDO $db;

    public function __construct() {
        $auth = new AuthController();
        $auth->requireAuth();
        $this->db = Database::getConnection();
    }

    // --- PRODUK & TOPPING (FR-2.1, FR-2.4, FR-2.7) ---
    public function products() {
        $sql = "
            SELECT p.product_id as id, p.product_name as nama_produk, p.category_id, p.price as harga,
                   p.hpp_manual, p.is_active as status_aktif, pc.category_name as nama_kategori,
                   COALESCE((
                       SELECT SUM(r.quantity * rm.price_per_unit)
                       FROM recipes r
                       JOIN raw_materials rm ON r.material_id = rm.material_id
                       WHERE r.product_id = p.product_id
                   ), p.hpp_manual, 0) as hpp_calculated,
                   (SELECT COUNT(*) FROM recipes WHERE product_id = p.product_id) as recipe_count
            FROM products p
            LEFT JOIN product_categories pc ON p.category_id = pc.category_id
            ORDER BY p.category_id ASC, p.product_id ASC
        ";
        $products = $this->db->query($sql)->fetchAll();
        $categories = $this->db->query("SELECT category_id as id, category_name as nama_kategori FROM product_categories ORDER BY category_id ASC")->fetchAll();

        // Cek peringatan HPP 0 (FR-2.7)
        $zeroHppWarnings = [];
        foreach ($products as $p) {
            if ($p['hpp_calculated'] <= 0) {
                $zeroHppWarnings[] = $p['nama_produk'];
            }
        }

        $pageTitle = 'Master Menu & Topping - ' . APP_NAME;
        $currentRoute = 'products';
        require_once __DIR__ . '/../views/master/products.php';
    }

    public function storeProduct() {
        $name = trim($_POST['nama_produk'] ?? '');
        $catId = (int)($_POST['category_id'] ?? 1);
        $harga = (float)($_POST['harga'] ?? 0);
        $hppManual = !empty($_POST['hpp_manual']) ? (float)$_POST['hpp_manual'] : null;

        if (empty($name)) {
            set_flash('error', 'Nama produk wajib diisi.');
            redirect('/?page=products');
        }

        $stmt = $this->db->prepare("INSERT INTO products (product_name, category_id, price, hpp_manual, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$name, $catId, $harga, $hppManual]);

        set_flash('success', "Produk '$name' berhasil ditambahkan.");
        redirect('/?page=products');
    }

    public function updateProduct() {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['nama_produk'] ?? '');
        $catId = (int)($_POST['category_id'] ?? 1);
        $harga = (float)($_POST['harga'] ?? 0);
        $hppManual = !empty($_POST['hpp_manual']) ? (float)$_POST['hpp_manual'] : null;
        $aktif = isset($_POST['status_aktif']) ? 1 : 0;

        if ($id <= 0 || empty($name)) {
            set_flash('error', 'Data produk tidak valid.');
            redirect('/?page=products');
        }

        $stmt = $this->db->prepare("UPDATE products SET product_name = ?, category_id = ?, price = ?, hpp_manual = ?, is_active = ? WHERE product_id = ?");
        $stmt->execute([$name, $catId, $harga, $hppManual, $aktif, $id]);

        set_flash('success', "Produk '$name' berhasil diperbarui.");
        redirect('/?page=products');
    }

    // --- BAHAN BAKU & STOK (FR-2.2) ---
    public function materials() {
        $sql = "
            SELECT material_id as id, material_name as nama_bahan, unit as satuan,
                   current_stock as stok, min_stock as stok_minimum, price_per_unit as harga_per_unit
            FROM raw_materials
            ORDER BY material_id ASC
        ";
        $materials = $this->db->query($sql)->fetchAll();
        $pageTitle = 'Master Bahan Baku & Stok - ' . APP_NAME;
        $currentRoute = 'materials';
        require_once __DIR__ . '/../views/master/materials.php';
    }

    public function storeMaterial() {
        $nama = trim($_POST['nama_bahan'] ?? '');
        $satuan = trim($_POST['satuan'] ?? '');
        $stok = (float)($_POST['stok'] ?? 0);
        $stokMin = (float)($_POST['stok_minimum'] ?? 0);
        $harga = (float)($_POST['harga_per_unit'] ?? 0);

        if (empty($nama) || empty($satuan)) {
            set_flash('error', 'Nama bahan dan satuan wajib diisi.');
            redirect('/?page=materials');
        }

        $stmt = $this->db->prepare("INSERT INTO raw_materials (material_name, unit, current_stock, min_stock, price_per_unit) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $satuan, $stok, $stokMin, $harga]);

        set_flash('success', "Bahan baku '$nama' berhasil ditambahkan.");
        redirect('/?page=materials');
    }

    public function updateMaterial() {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_bahan'] ?? '');
        $satuan = trim($_POST['satuan'] ?? '');
        $stok = (float)($_POST['stok'] ?? 0);
        $stokMin = (float)($_POST['stok_minimum'] ?? 0);
        $harga = (float)($_POST['harga_per_unit'] ?? 0);

        if ($id <= 0 || empty($nama)) {
            set_flash('error', 'Data bahan baku tidak valid.');
            redirect('/?page=materials');
        }

        $stmt = $this->db->prepare("UPDATE raw_materials SET material_name = ?, unit = ?, current_stock = ?, min_stock = ?, price_per_unit = ? WHERE material_id = ?");
        $stmt->execute([$nama, $satuan, $stok, $stokMin, $harga, $id]);

        set_flash('success', "Bahan baku '$nama' berhasil diperbarui.");
        redirect('/?page=materials');
    }

    // --- RESEP & HPP (FR-2.3, FR-2.4) ---
    public function recipes() {
        $productId = (int)($_GET['product_id'] ?? 1);

        $products = $this->db->query("SELECT product_id as id, product_name as nama_produk, price as harga FROM products WHERE is_active = 1 ORDER BY product_id ASC")->fetchAll();
        $materials = $this->db->query("SELECT material_id as id, material_name as nama_bahan, unit as satuan, price_per_unit as harga_per_unit FROM raw_materials ORDER BY material_name ASC")->fetchAll();

        // Ambil detail resep produk terpilih
        $stmtR = $this->db->prepare("
            SELECT r.recipe_id, r.product_id, r.material_id, r.material_id as raw_material_id,
                   r.quantity as takaran, rm.material_name as nama_bahan, rm.unit as satuan,
                   rm.price_per_unit as harga_per_unit,
                   (r.quantity * rm.price_per_unit) as subtotal_hpp
            FROM recipes r
            JOIN raw_materials rm ON r.material_id = rm.material_id
            WHERE r.product_id = ?
        ");
        $stmtR->execute([$productId]);
        $recipeItems = $stmtR->fetchAll();

        $selectedProduct = null;
        foreach ($products as $p) {
            if ($p['id'] == $productId) {
                $selectedProduct = $p;
                break;
            }
        }

        $totalHpp = 0;
        foreach ($recipeItems as $it) {
            $totalHpp += $it['subtotal_hpp'];
        }

        $pageTitle = 'Master Resep & Komposisi HPP - ' . APP_NAME;
        $currentRoute = 'recipes';
        require_once __DIR__ . '/../views/master/recipes.php';
    }

    public function storeRecipeItem() {
        $prodId = (int)($_POST['product_id'] ?? 0);
        $matId = (int)($_POST['raw_material_id'] ?? 0);
        $takaran = (float)($_POST['takaran'] ?? 0);

        if ($prodId <= 0 || $matId <= 0 || $takaran <= 0) {
            set_flash('error', 'Bahan dan takaran harus valid.');
            redirect("/?page=recipes&product_id=$prodId");
        }

        $stmtC = $this->db->prepare("SELECT recipe_id FROM recipes WHERE product_id = ? AND material_id = ?");
        $stmtC->execute([$prodId, $matId]);
        if ($stmtC->fetch()) {
            $stmtUp = $this->db->prepare("UPDATE recipes SET quantity = quantity + ? WHERE product_id = ? AND material_id = ?");
            $stmtUp->execute([$takaran, $prodId, $matId]);
        } else {
            $stmtIns = $this->db->prepare("INSERT INTO recipes (product_id, material_id, quantity) VALUES (?, ?, ?)");
            $stmtIns->execute([$prodId, $matId, $takaran]);
        }

        set_flash('success', 'Bahan baku berhasil ditambahkan ke dalam resep.');
        redirect("/?page=recipes&product_id=$prodId");
    }

    public function deleteRecipeItem() {
        $prodId = (int)($_GET['product_id'] ?? 0);
        $matId = (int)($_GET['raw_material_id'] ?? 0);

        $stmt = $this->db->prepare("DELETE FROM recipes WHERE product_id = ? AND material_id = ?");
        $stmt->execute([$prodId, $matId]);

        set_flash('success', 'Bahan baku berhasil dihapus dari resep.');
        redirect("/?page=recipes&product_id=$prodId");
    }

    // --- RIWAYAT PENJUALAN (FR-2.8) ---
    public function transactions() {
        $startDate = $_GET['start_date'] ?? '2026-02-10';
        $endDate = $_GET['end_date'] ?? '2026-07-09';
        $limit = (int)($_GET['limit'] ?? 100);

        $fullStart = $startDate . ' 00:00:00';
        $fullEnd   = $endDate . ' 23:59:59';

        $sql = "
            SELECT t.transaction_id as id, t.transaction_code as invoice_no, t.transaction_date as tanggal,
                   t.total as total_amount, t.payment_method as tipe_bayar,
                   COUNT(td.detail_id) as item_count,
                   GROUP_CONCAT(CONCAT(td.product_name, ' (', td.quantity, ')') SEPARATOR ', ') as menu_list
            FROM transactions t
            LEFT JOIN transaction_details td ON t.transaction_id = td.transaction_id
            WHERE t.transaction_date >= ? AND t.transaction_date <= ?
            GROUP BY t.transaction_id
            ORDER BY t.transaction_date DESC, t.transaction_id DESC
            LIMIT ?
        ";

        if (Database::getActiveDriver() === 'sqlite') {
            $sql = "
                SELECT t.transaction_id as id, t.transaction_code as invoice_no, t.transaction_date as tanggal,
                       t.total as total_amount, t.payment_method as tipe_bayar,
                       COUNT(td.detail_id) as item_count,
                       group_concat(td.product_name || ' (' || td.quantity || ')') as menu_list
                FROM transactions t
                LEFT JOIN transaction_details td ON t.transaction_id = td.transaction_id
                WHERE t.transaction_date >= ? AND t.transaction_date <= ?
                GROUP BY t.transaction_id
                ORDER BY t.transaction_date DESC, t.transaction_id DESC
                LIMIT ?
            ";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fullStart, $fullEnd, $limit]);
        $transactions = $stmt->fetchAll();

        // Hitung total ringkasan
        $stmtSummary = $this->db->prepare("
            SELECT COUNT(DISTINCT t.transaction_id) as total_trx,
                   COALESCE(SUM(td.quantity), 0) as total_cups,
                   COALESCE(SUM(t.total), 0) as total_omzet
            FROM transactions t
            LEFT JOIN transaction_details td ON t.transaction_id = td.transaction_id
            WHERE t.transaction_date >= ? AND t.transaction_date <= ?
        ");
        $stmtSummary->execute([$fullStart, $fullEnd]);
        $summary = $stmtSummary->fetch();

        $pageTitle = 'Riwayat Penjualan Kasir - ' . APP_NAME;
        $currentRoute = 'transactions';
        require_once __DIR__ . '/../views/master/transactions.php';
    }

    // --- IMPOR DATA PENJUALAN (FR-2.5, FR-2.6) ---
    public function import() {
        $pageTitle = 'Impor Data Transaksi Penjualan - ' . APP_NAME;
        $currentRoute = 'import';
        require_once __DIR__ . '/../views/master/import.php';
    }

    public function handleImport() {
        if (!isset($_FILES['file_transaksi']) || $_FILES['file_transaksi']['error'] !== UPLOAD_ERR_OK) {
            set_flash('error', 'Silakan pilih berkas data transaksi yang valid.');
            redirect('/?page=import');
        }

        $fileTmp = $_FILES['file_transaksi']['tmp_name'];
        $fileName = $_FILES['file_transaksi']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext === 'sql') {
            // Impor langsung file dump SQL kasir
            require_once __DIR__ . '/../../database/import_cashier_dump.php';
            try {
                importCashierDump($fileTmp);
                set_flash('success', 'Dump SQL kasir berhasil diimpor penuh ke sistem SPK.');
                redirect('/?page=transactions');
            } catch (Exception $e) {
                set_flash('error', 'Gagal impor SQL: ' . $e->getMessage());
                redirect('/?page=import');
            }
        } else if ($ext === 'csv') {
            try {
                $importer = new ImporterService($this->db);
                $res = $importer->importCsv($fileTmp);

                if (!empty($res['errors'])) {
                    $_SESSION['import_errors'] = $res['errors'];
                }
                set_flash('success', $res['summary']);
                redirect('/?page=import');
            } catch (Exception $e) {
                set_flash('error', 'Gagal memproses berkas impor: ' . $e->getMessage());
                redirect('/?page=import');
            }
        } else {
            set_flash('error', 'Hanya berkas format .sql atau .csv yang didukung.');
            redirect('/?page=import');
        }
    }
}

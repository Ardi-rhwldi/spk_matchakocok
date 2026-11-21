<?php
// app/controllers/BundleController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/MatrixCalculator.php';
require_once __DIR__ . '/../services/BundleGenerator.php';
require_once __DIR__ . '/AuthController.php';

class BundleController {
    private PDO $db;
    private MatrixCalculator $calculator;

    public function __construct() {
        $auth = new AuthController();
        $auth->requireAuth();
        $this->db = Database::getConnection();
        $this->calculator = new MatrixCalculator($this->db);
    }

    public function index() {
        $sql = "
            SELECT b.*,
                   GROUP_CONCAT(CONCAT(p.product_name, ' (', bi.qty, ')') SEPARATOR ' + ') as item_names
            FROM bundles b
            LEFT JOIN bundle_items bi ON b.id = bi.bundle_id
            LEFT JOIN products p ON bi.product_id = p.product_id
            GROUP BY b.id
            ORDER BY b.id ASC
        ";

        if (Database::getActiveDriver() === 'sqlite') {
            $sql = "
                SELECT b.*,
                       group_concat(p.product_name || ' (' || bi.qty || ')') as item_names
                FROM bundles b
                LEFT JOIN bundle_items bi ON b.id = bi.bundle_id
                LEFT JOIN products p ON bi.product_id = p.product_id
                GROUP BY b.id
                ORDER BY b.id ASC
            ";
        }

        $bundles = $this->db->query($sql)->fetchAll();

        // Hitung frekuensi bersama (C2) dari transaksi riil
        foreach ($bundles as &$b) {
            $stmtItems = $this->db->prepare("SELECT product_id FROM bundle_items WHERE bundle_id = ?");
            $stmtItems->execute([$b['id']]);
            $pIds = $stmtItems->fetchAll(PDO::FETCH_COLUMN);
            $b['freq'] = $this->calculator->calculateCoPurchaseFrequency($pIds, '2026-02-10 00:00:00', '2026-07-09 23:59:59');
        }
        unset($b);

        $pageTitle = 'Manajemen Alternatif Paket Promosi - ' . APP_NAME;
        $currentRoute = 'bundles';
        require_once __DIR__ . '/../views/bundles/index.php';
    }

    public function create() {
        $products = $this->db->query("SELECT product_id as id, product_name as nama_produk, price as harga FROM products WHERE is_active = 1 ORDER BY product_id ASC")->fetchAll();
        $bundle = null;
        $pageTitle = 'Buat Paket Bundling Manual - ' . APP_NAME;
        $currentRoute = 'bundles';
        require_once __DIR__ . '/../views/bundles/form.php';
    }

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $this->db->prepare("SELECT * FROM bundles WHERE id = ?");
        $stmt->execute([$id]);
        $bundle = $stmt->fetch();

        if (!$bundle) {
            set_flash('error', 'Paket tidak ditemukan.');
            redirect('/?page=bundles');
        }

        $products = $this->db->query("SELECT product_id as id, product_name as nama_produk, price as harga FROM products WHERE is_active = 1 ORDER BY product_id ASC")->fetchAll();
        $stmtItems = $this->db->prepare("SELECT product_id, qty FROM bundle_items WHERE bundle_id = ?");
        $stmtItems->execute([$id]);
        $existingItems = $stmtItems->fetchAll(PDO::FETCH_KEY_PAIR);

        $pageTitle = 'Edit Paket Bundling - ' . APP_NAME;
        $currentRoute = 'bundles';
        require_once __DIR__ . '/../views/bundles/form.php';
    }

    public function store() {
        $kode = trim($_POST['kode_paket'] ?? '');
        $nama = trim($_POST['nama_paket'] ?? '');
        $diskonTipe = in_array($_POST['diskon_tipe'] ?? '', ['nominal', 'persen']) ? $_POST['diskon_tipe'] : 'nominal';
        $diskonNilai = (float)($_POST['diskon_nilai'] ?? 0);
        $selectedProducts = $_POST['products'] ?? [];

        $chosenItems = [];
        $chosenIds = [];
        foreach ($selectedProducts as $pId => $qty) {
            $q = (int)$qty;
            if ($q > 0) {
                $chosenItems[(int)$pId] = $q;
                $chosenIds[] = (int)$pId;
            }
        }

        if (empty($kode) || empty($nama)) {
            set_flash('error', 'Kode dan nama paket wajib diisi.');
            redirect('/?page=bundles_create');
        }

        if (count($chosenItems) < 2 || count($chosenItems) > 3) {
            set_flash('error', 'Sesuai PRD Bagian 7.1, paket bundling harus terdiri dari 2 sampai 3 menu.');
            redirect('/?page=bundles_create');
        }

        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM bundles WHERE kode_paket = ?");
        $stmtC->execute([$kode]);
        if ($stmtC->fetchColumn() > 0) {
            set_flash('error', "Kode paket '$kode' sudah ada.");
            redirect('/?page=bundles_create');
        }

        sort($chosenIds);
        if ($this->checkDuplicateBundleSignature($chosenIds)) {
            set_flash('error', 'Sudah ada paket dengan kombinasi menu penyusun yang persis sama (FR-3.6).');
            redirect('/?page=bundles_create');
        }

        $calcData = $this->calculateBundlePricing($chosenItems, $diskonTipe, $diskonNilai);

        $this->db->beginTransaction();
        $stmtIns = $this->db->prepare("
            INSERT INTO bundles (kode_paket, nama_paket, diskon_tipe, diskon_nilai, harga_normal, harga_promosi, hpp_total, margin_awal, ikut_analisis)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmtIns->execute([
            $kode, $nama, $diskonTipe, $diskonNilai,
            $calcData['harga_normal'], $calcData['harga_promosi'],
            $calcData['hpp_total'], $calcData['margin_awal']
        ]);
        $newBundleId = (int)$this->db->lastInsertId();

        $stmtItem = $this->db->prepare("INSERT INTO bundle_items (bundle_id, product_id, qty) VALUES (?, ?, ?)");
        foreach ($chosenItems as $pId => $qty) {
            $stmtItem->execute([$newBundleId, $pId, $qty]);
        }
        $this->db->commit();

        set_flash('success', "Paket '$nama' berhasil dibuat dan didaftarkan sebagai kandidat analisis.");
        redirect('/?page=bundles');
    }

    public function update() {
        $id = (int)($_POST['id'] ?? 0);
        $kode = trim($_POST['kode_paket'] ?? '');
        $nama = trim($_POST['nama_paket'] ?? '');
        $diskonTipe = in_array($_POST['diskon_tipe'] ?? '', ['nominal', 'persen']) ? $_POST['diskon_tipe'] : 'nominal';
        $diskonNilai = (float)($_POST['diskon_nilai'] ?? 0);
        $selectedProducts = $_POST['products'] ?? [];

        $chosenItems = [];
        $chosenIds = [];
        foreach ($selectedProducts as $pId => $qty) {
            $q = (int)$qty;
            if ($q > 0) {
                $chosenItems[(int)$pId] = $q;
                $chosenIds[] = (int)$pId;
            }
        }

        if ($id <= 0 || empty($nama) || count($chosenItems) < 2) {
            set_flash('error', 'Data paket tidak valid.');
            redirect('/?page=bundles');
        }

        sort($chosenIds);
        if ($this->checkDuplicateBundleSignature($chosenIds, $id)) {
            set_flash('error', 'Kombinasi menu ini sudah dipakai oleh paket lain.');
            redirect('/?page=bundles_edit&id=' . $id);
        }

        $calcData = $this->calculateBundlePricing($chosenItems, $diskonTipe, $diskonNilai);

        $this->db->beginTransaction();
        $stmtUp = $this->db->prepare("
            UPDATE bundles 
            SET kode_paket = ?, nama_paket = ?, diskon_tipe = ?, diskon_nilai = ?,
                harga_normal = ?, harga_promosi = ?, hpp_total = ?, margin_awal = ?
            WHERE id = ?
        ");
        $stmtUp->execute([
            $kode, $nama, $diskonTipe, $diskonNilai,
            $calcData['harga_normal'], $calcData['harga_promosi'],
            $calcData['hpp_total'], $calcData['margin_awal'],
            $id
        ]);

        $this->db->prepare("DELETE FROM bundle_items WHERE bundle_id = ?")->execute([$id]);
        $stmtItem = $this->db->prepare("INSERT INTO bundle_items (bundle_id, product_id, qty) VALUES (?, ?, ?)");
        foreach ($chosenItems as $pId => $qty) {
            $stmtItem->execute([$id, $pId, $qty]);
        }
        $this->db->commit();

        set_flash('success', "Paket '$nama' berhasil diperbarui.");
        redirect('/?page=bundles');
    }

    public function toggleActive() {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $this->db->prepare("UPDATE bundles SET ikut_analisis = 1 - ikut_analisis WHERE id = ?");
        $stmt->execute([$id]);

        set_flash('success', "Status keikutsertaan paket dalam analisis berhasil diubah.");
        redirect('/?page=bundles');
    }

    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        $this->db->prepare("DELETE FROM bundles WHERE id = ?")->execute([$id]);

        set_flash('success', "Paket kandidat berhasil dihapus.");
        redirect('/?page=bundles');
    }

    public function showGenerator() {
        $generator = new BundleGenerator($this->db);
        $candidates = $generator->generateCombinations();

        $pageTitle = 'Generator Kandidat Paket Otomatis - ' . APP_NAME;
        $currentRoute = 'bundles';
        require_once __DIR__ . '/../views/bundles/generate.php';
    }

    public function applyGeneratedCandidates() {
        $selectedKeys = $_POST['selected_candidates'] ?? [];
        if (empty($selectedKeys)) {
            set_flash('warning', 'Tidak ada kandidat paket yang dipilih.');
            redirect('/?page=bundles_generate');
        }

        $generator = new BundleGenerator($this->db);
        $allCandidates = $generator->generateCombinations();

        $candidatesByCode = [];
        foreach ($allCandidates as $c) {
            $candidatesByCode[$c['code']] = $c;
        }

        $insertedCount = 0;
        $this->db->beginTransaction();

        $stmtMaxCode = $this->db->query("SELECT MAX(id) FROM bundles")->fetchColumn();
        $nextNum = ($stmtMaxCode ?: 0) + 1;

        $stmtBundle = $this->db->prepare("
            INSERT INTO bundles (kode_paket, nama_paket, diskon_tipe, diskon_nilai, harga_normal, harga_promosi, hpp_total, margin_awal, ikut_analisis)
            VALUES (?, ?, 'nominal', ?, ?, ?, ?, ?, 1)
        ");
        $stmtItem = $this->db->prepare("INSERT INTO bundle_items (bundle_id, product_id, qty) VALUES (?, ?, 1)");

        foreach ($selectedKeys as $code) {
            if (!isset($candidatesByCode[$code])) continue;
            $cand = $candidatesByCode[$code];

            if ($cand['is_existing']) continue;

            $kodePaket = sprintf('P%02d', $nextNum++);
            $stmtBundle->execute([
                $kodePaket,
                $cand['nama_paket'],
                $cand['diskon'],
                $cand['harga_normal'],
                $cand['harga_promosi'],
                $cand['hpp_total'],
                $cand['margin_awal']
            ]);
            $bundleId = (int)$this->db->lastInsertId();

            foreach ($cand['product_ids'] as $pId) {
                $stmtItem->execute([$bundleId, $pId]);
            }
            $insertedCount++;
        }

        $this->db->commit();
        set_flash('success', "Berhasil menambahkan $insertedCount kandidat paket baru ke daftar analisis.");
        redirect('/?page=bundles');
    }

    private function checkDuplicateBundleSignature(array $sortedProdIds, int $excludeBundleId = 0): bool {
        $sig = implode('-', $sortedProdIds);
        $sql = "
            SELECT bundle_id, GROUP_CONCAT(product_id ORDER BY product_id ASC SEPARATOR '-') as signature
            FROM bundle_items
            WHERE bundle_id != ?
            GROUP BY bundle_id
        ";
        if (Database::getActiveDriver() === 'sqlite') {
            $sql = "
                SELECT bundle_id, group_concat(product_id) as signature
                FROM (
                    SELECT bundle_id, product_id 
                    FROM bundle_items 
                    WHERE bundle_id != ?
                    ORDER BY bundle_id, product_id ASC
                )
                GROUP BY bundle_id
            ";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$excludeBundleId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $r) {
            $existingSig = str_replace(',', '-', $r['signature']);
            if ($existingSig === $sig) {
                return true;
            }
        }
        return false;
    }

    private function calculateBundlePricing(array $chosenItems, string $diskonTipe, float $diskonNilai): array {
        $hargaNormal = 0.0;
        $itemsForHpp = [];

        foreach ($chosenItems as $pId => $qty) {
            $stmtP = $this->db->prepare("SELECT price as harga, hpp_manual FROM products WHERE product_id = ?");
            $stmtP->execute([$pId]);
            $p = $stmtP->fetch();

            $hargaNormal += ((float)$p['harga'] * $qty);
            $itemsForHpp[] = [
                'product_id' => $pId,
                'qty' => $qty,
                'hpp_manual' => $p['hpp_manual']
            ];
        }

        $hppTotal = $this->calculator->calculateBundleHpp($itemsForHpp);

        $diskonRupiah = 0.0;
        if ($diskonTipe === 'persen') {
            $diskonRupiah = ($hargaNormal * $diskonNilai) / 100;
        } else {
            $diskonRupiah = $diskonNilai;
        }

        $hargaPromosi = max(0, $hargaNormal - $diskonRupiah);
        $marginAwal = $hargaPromosi - $hppTotal;

        return [
            'harga_normal' => $hargaNormal,
            'diskon_rupiah' => $diskonRupiah,
            'harga_promosi' => $hargaPromosi,
            'hpp_total' => $hppTotal,
            'margin_awal' => $marginAwal,
        ];
    }
}

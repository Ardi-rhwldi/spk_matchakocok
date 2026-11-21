<?php
// app/controllers/DashboardController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class DashboardController {
    private PDO $db;

    public function __construct() {
        $auth = new AuthController();
        $auth->requireAuth();
        $this->db = Database::getConnection();
    }

    public function index() {
        // 1. Rekomendasi Paket Terbaru (FR-7.1)
        $latestRun = $this->db->query("
            SELECT ar.*, u.nama as user_nama
            FROM analysis_runs ar
            JOIN users u ON ar.user_id = u.id
            ORDER BY ar.id DESC
            LIMIT 1
        ")->fetch();

        $topBundles = [];
        if ($latestRun) {
            $stmtTop = $this->db->prepare("
                SELECT res.*, b.kode_paket, b.nama_paket, b.harga_promosi, b.margin_awal
                FROM analysis_results res
                JOIN bundles b ON res.bundle_id = b.id
                WHERE res.run_id = ?
                ORDER BY res.peringkat ASC
                LIMIT 3
            ");
            $stmtTop->execute([$latestRun['id']]);
            $topBundles = $stmtTop->fetchAll();
        }

        // 2. Ringkasan Kriteria dan Bobot (FR-7.2)
        $criteria = $this->db->query("SELECT * FROM criteria ORDER BY urutan ASC")->fetchAll();

        // 3. Ringkasan Statistik Operasional
        $totalProducts = $this->db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
        $totalBundles = $this->db->query("SELECT COUNT(*) FROM bundles WHERE ikut_analisis = 1")->fetchColumn();
        $totalTrx = $this->db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
        $totalOmzet = $this->db->query("SELECT COALESCE(SUM(total), 0) FROM transactions")->fetchColumn();

        // 4. Peringatan Stok Bahan yang Menipis (FR-7.4)
        $lowStocks = $this->db->query("
            SELECT material_id as id, material_name as nama_bahan, unit as satuan,
                   current_stock as stok, min_stock as stok_minimum
            FROM raw_materials
            WHERE current_stock <= min_stock
            ORDER BY (current_stock / NULLIF(min_stock, 0)) ASC
        ")->fetchAll();

        // 5. Data Grafik Omzet Bulanan (FR-7.3)
        $sqlMonthly = "
            SELECT DATE_FORMAT(transaction_date, '%Y-%m') as bulan,
                   COALESCE(SUM(total), 0) as omzet,
                   COUNT(transaction_id) as trx_count
            FROM transactions
            GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
            ORDER BY bulan ASC
        ";
        if (Database::getActiveDriver() === 'sqlite') {
            $sqlMonthly = "
                SELECT strftime('%Y-%m', transaction_date) as bulan,
                       COALESCE(SUM(total), 0) as omzet,
                       COUNT(transaction_id) as trx_count
                FROM transactions
                GROUP BY strftime('%Y-%m', transaction_date)
                ORDER BY bulan ASC
            ";
        }
        $monthlyRows = $this->db->query($sqlMonthly)->fetchAll();

        $monthLabels = [];
        $omzetData = [];
        foreach ($monthlyRows as $m) {
            $monthLabels[] = date('M Y', strtotime($m['bulan'] . '-01'));
            $omzetData[] = (float)$m['omzet'];
        }

        // 6. Data Produk Terlaris (Top Selling Drinks)
        $topProducts = $this->db->query("
            SELECT p.product_name as nama_produk, COALESCE(SUM(td.quantity), 0) as total_qty
            FROM products p
            LEFT JOIN transaction_details td ON p.product_id = td.product_id
            WHERE p.category_id = 1
            GROUP BY p.product_id
            ORDER BY total_qty DESC
            LIMIT 5
        ")->fetchAll();

        $pageTitle = 'Dashboard SPK Bundling Matcha Kocok (Modul 7) - ' . APP_NAME;
        $currentRoute = 'dashboard';
        require_once __DIR__ . '/../views/dashboard/index.php';
    }
}

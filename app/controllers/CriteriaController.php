<?php
// app/controllers/CriteriaController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class CriteriaController {
    private PDO $db;

    public function __construct() {
        $auth = new AuthController();
        $auth->requireAuth();
        $this->db = Database::getConnection();
    }

    public function index() {
        $criteria = $this->db->query("SELECT * FROM criteria ORDER BY urutan ASC")->fetchAll();
        $history = $this->db->query("
            SELECT ch.*, u.nama as user_nama
            FROM criteria_history ch
            JOIN users u ON ch.user_id = u.id
            ORDER BY ch.created_at DESC
            LIMIT 10
        ")->fetchAll();

        $totalWeight = 0;
        foreach ($criteria as $c) {
            $totalWeight += (float)$c['bobot'];
        }

        // Ambil default periode analisis dari session atau konfigurasi
        $startDate = $_SESSION['analysis_period_start'] ?? '2026-02-10';
        $endDate   = $_SESSION['analysis_period_end'] ?? '2026-07-09';

        $isPemilik = ($_SESSION['user']['peran'] ?? '') === 'pemilik';

        $pageTitle = 'Pengaturan Kriteria & Bobot (Modul 4) - ' . APP_NAME;
        $currentRoute = 'criteria';
        require_once __DIR__ . '/../views/criteria/index.php';
    }

    public function updateWeights() {
        // Hak akses: hanya Pemilik atau persetujuan pemilik (Bagian 5)
        if (($_SESSION['user']['peran'] ?? '') !== 'pemilik') {
            set_flash('error', 'Hanya Pemilik usaha yang berhak mengubah bobot kriteria final (Bagian 5 PRD).');
            redirect('/?page=criteria');
        }

        $weights = $_POST['weights'] ?? []; // [crit_id => weight]
        $catatan = trim($_POST['catatan'] ?? 'Pembaruan bobot kriteria oleh Pemilik Usaha');

        $totalWeight = 0.0;
        foreach ($weights as $cId => $w) {
            $totalWeight += (float)$w;
        }

        // Validasi total bobot sama dengan 1,00 (FR-4.2)
        if (abs($totalWeight - 1.0) > 0.0001) {
            set_flash('error', "Validasi gagal: Total bobot harus tepat 1,00 (Total saat ini: " . number_format($totalWeight, 4) . ").");
            redirect('/?page=criteria');
        }

        $this->db->beginTransaction();

        // 1. Simpan snapshot riwayat sebelum perubahan (FR-4.5)
        $currentSnapshot = $this->db->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll();
        $stmtHist = $this->db->prepare("INSERT INTO criteria_history (user_id, catatan, snapshot_json) VALUES (?, ?, ?)");
        $stmtHist->execute([
            $_SESSION['user']['id'],
            $catatan,
            json_encode($currentSnapshot, JSON_PRETTY_PRINT)
        ]);

        // 2. Perbarui bobot kriteria
        $stmtUp = $this->db->prepare("UPDATE criteria SET bobot = ? WHERE id = ?");
        foreach ($weights as $cId => $w) {
            $stmtUp->execute([(float)$w, (int)$cId]);
        }

        $this->db->commit();
        set_flash('success', 'Bobot 7 kriteria berhasil diperbarui dan riwayat perubahan telah dicatat.');
        redirect('/?page=criteria');
    }

    public function updatePeriod() {
        $start = $_POST['start_date'] ?? '2026-02-10';
        $end   = $_POST['end_date'] ?? '2026-07-09';

        if (strtotime($start) > strtotime($end)) {
            set_flash('error', 'Tanggal awal tidak boleh melebihi tanggal akhir.');
            redirect('/?page=criteria');
        }

        $_SESSION['analysis_period_start'] = $start;
        $_SESSION['analysis_period_end'] = $end;

        set_flash('success', "Periode data penjualan untuk analisis ELECTRE berhasil diatur: $start s.d $end.");
        redirect('/?page=criteria');
    }
}


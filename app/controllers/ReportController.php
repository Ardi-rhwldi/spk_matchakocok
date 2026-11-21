<?php
// app/controllers/ReportController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class ReportController {
    private PDO $db;

    public function __construct() {
        $auth = new AuthController();
        $auth->requireAuth();
        $this->db = Database::getConnection();
    }

    public function index() {
        $runId = (int)($_GET['run_id'] ?? 0);

        if ($runId <= 0) {
            // Ambil analisis terbaru
            $latestRun = $this->db->query("SELECT id FROM analysis_runs ORDER BY id DESC LIMIT 1")->fetch();
            $runId = (int)($latestRun['id'] ?? 0);
        }

        if ($runId <= 0) {
            set_flash('warning', 'Belum ada analisis ELECTRE yang dijalankan. Silakan jalankan analisis terlebih dahulu pada Modul 5.');
            redirect('/?page=electre');
        }

        // Ambil data header analisis
        $stmtRun = $this->db->prepare("
            SELECT ar.*, u.nama as user_nama
            FROM analysis_runs ar
            JOIN users u ON ar.user_id = u.id
            WHERE ar.id = ?
        ");
        $stmtRun->execute([$runId]);
        $run = $stmtRun->fetch();

        // Ambil daftar riwayat semua analisis untuk dropdown switcher
        $allRuns = $this->db->query("SELECT id, nama_analisis, tanggal_analisis FROM analysis_runs ORDER BY id DESC")->fetchAll();

        // Ambil hasil perankingan
        $stmtRes = $this->db->prepare("
            SELECT ar.*, b.kode_paket, b.nama_paket, b.harga_normal, b.harga_promosi, b.hpp_total, b.margin_awal
            FROM analysis_results ar
            JOIN bundles b ON ar.bundle_id = b.id
            WHERE ar.run_id = ?
            ORDER BY ar.peringkat ASC
        ");
        $stmtRes->execute([$runId]);
        $results = $stmtRes->fetchAll();

        // Alternatif Peringkat 1 (Best Choice / Rekomendasi Utama)
        $topRecommendation = $results[0] ?? null;

        // Ambil skor kriteria awal & final
        $stmtScores = $this->db->prepare("
            SELECT s.*, c.kode, c.nama as nama_kriteria, c.tipe
            FROM analysis_scores s
            JOIN criteria c ON s.criterion_id = c.id
            WHERE s.run_id = ?
        ");
        $stmtScores->execute([$runId]);
        $scoreRows = $stmtScores->fetchAll();
        $scoresByBundle = [];
        foreach ($scoreRows as $row) {
            $scoresByBundle[$row['bundle_id']][$row['kode']] = $row;
        }

        // Ambil kriteria
        $criteria = $this->db->query("SELECT * FROM criteria ORDER BY urutan ASC")->fetchAll();

        $pageTitle = 'Laporan Hasil Keputusan & Rekomendasi (Modul 6) - ' . APP_NAME;
        $currentRoute = 'reports';
        require_once __DIR__ . '/../views/reports/index.php';
    }

    /**
     * Ekspor Laporan ke format Excel / CSV (FR-6.4)
     */
    public function exportExcel() {
        $runId = (int)($_GET['run_id'] ?? 0);
        $stmtRun = $this->db->prepare("SELECT * FROM analysis_runs WHERE id = ?");
        $stmtRun->execute([$runId]);
        $run = $stmtRun->fetch();

        if (!$run) {
            set_flash('error', 'Analisis tidak ditemukan.');
            redirect('/?page=reports');
        }

        $stmtRes = $this->db->prepare("
            SELECT ar.peringkat, b.kode_paket, b.nama_paket, b.harga_normal, b.harga_promosi, b.hpp_total, b.margin_awal,
                   ar.outranking_count, ar.dominated_count, ar.net_score, ar.status_dominasi, ar.alasan_rekomendasi
            FROM analysis_results ar
            JOIN bundles b ON ar.bundle_id = b.id
            WHERE ar.run_id = ?
            ORDER BY ar.peringkat ASC
        ");
        $stmtRes->execute([$runId]);
        $results = $stmtRes->fetchAll();

        // Buat file CSV / TSV Excel
        $filename = "Laporan_SPK_ELECTRE_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $run['nama_analisis']) . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // BOM untuk UTF-8 di Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['LAPORAN HASIL KEPUTUSAN PEMILIHAN PAKET BUNDLING PROMOSI (METODE ELECTRE)']);
        fputcsv($output, ['Nama Usaha: Matcha Kocok Cidahu, Kab. Sukabumi']);
        fputcsv($output, ['Sesi Analisis: ' . $run['nama_analisis']]);
        fputcsv($output, ['Tanggal Analisis: ' . $run['tanggal_analisis']]);
        fputcsv($output, ['Periode Data Kasir: ' . $run['periode_awal'] . ' s.d ' . $run['periode_akhir']]);
        fputcsv($output, ['Ambang Batas c: ' . $run['threshold_c'], 'Ambang Batas d: ' . $run['threshold_d']]);
        fputcsv($output, []); // Baris kosong

        fputcsv($output, ['Peringkat', 'Kode Paket', 'Nama Paket Bundling', 'Harga Normal (Rp)', 'Harga Promosi (Rp)', 'HPP Paket (Rp)', 'Margin Awal (Rp)', 'Mendominasi', 'Didominasi', 'Skor Bersih (Net Score)', 'Status Dominasi', 'Alasan Rekomendasi']);

        foreach ($results as $r) {
            fputcsv($output, [
                $r['peringkat'],
                $r['kode_paket'],
                $r['nama_paket'],
                $r['harga_normal'],
                $r['harga_promosi'],
                $r['hpp_total'],
                $r['margin_awal'],
                $r['outranking_count'],
                $r['dominated_count'],
                $r['net_score'],
                $r['status_dominasi'],
                $r['alasan_rekomendasi']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Halaman Simulasi Proyeksi Laba bila paket terjual N unit per bulan (FR-6.5)
     */
    public function simulation() {
        $runId = (int)($_GET['run_id'] ?? 0);
        if ($runId <= 0) {
            $latestRun = $this->db->query("SELECT id FROM analysis_runs ORDER BY id DESC LIMIT 1")->fetch();
            $runId = (int)($latestRun['id'] ?? 0);
        }

        $unitsPerMonth = (int)($_GET['units'] ?? 100);
        if ($unitsPerMonth <= 0) $unitsPerMonth = 100;

        // Ambil ranking hasil analisis
        $stmtRes = $this->db->prepare("
            SELECT ar.*, b.kode_paket, b.nama_paket, b.harga_normal, b.harga_promosi, b.hpp_total, b.margin_awal
            FROM analysis_results ar
            JOIN bundles b ON ar.bundle_id = b.id
            WHERE ar.run_id = ?
            ORDER BY ar.peringkat ASC
        ");
        $stmtRes->execute([$runId]);
        $results = $stmtRes->fetchAll();

        // Hitung proyeksi laba untuk setiap paket
        $simulations = [];
        foreach ($results as $r) {
            $omzet = (float)$r['harga_promosi'] * $unitsPerMonth;
            $hppTotal = (float)$r['hpp_total'] * $unitsPerMonth;
            $labaBersih = (float)$r['margin_awal'] * $unitsPerMonth;
            $marginPct = ($omzet > 0) ? ($labaBersih / $omzet) * 100 : 0;

            $simulations[] = array_merge($r, [
                'target_units' => $unitsPerMonth,
                'proyeksi_omzet' => $omzet,
                'proyeksi_hpp' => $hppTotal,
                'proyeksi_laba' => $labaBersih,
                'proyeksi_margin_pct' => $marginPct
            ]);
        }

        $pageTitle = 'Simulasi Proyeksi Laba Bundling (FR-6.5) - ' . APP_NAME;
        $currentRoute = 'simulation';
        require_once __DIR__ . '/../views/reports/simulation.php';
    }
}


<?php
// app/controllers/ElectreController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/MatrixCalculator.php';
require_once __DIR__ . '/../services/ElectreService.php';
require_once __DIR__ . '/AuthController.php';

class ElectreController {
    private PDO $db;
    private MatrixCalculator $calculator;
    private ElectreService $electre;

    public function __construct() {
        $auth = new AuthController();
        $auth->requireAuth();
        $this->db = Database::getConnection();
        $this->calculator = new MatrixCalculator($this->db);
        $this->electre = new ElectreService();
    }

    /**
     * Halaman Utama Modul 5: Preview Matriks Keputusan Otomatis (X)
     * Dapat mengoreksi nilai manual dengan catatan alasan (FR-5.1, FR-5.2)
     */
    public function index() {
        $startDate = $_SESSION['analysis_period_start'] ?? '2026-02-10';
        $endDate   = $_SESSION['analysis_period_end'] ?? '2026-07-09';

        // Susun matriks keputusan dari data (FR-5.1)
        $matrixData = $this->calculator->buildDecisionMatrix($startDate, $endDate);

        $alternatives = $matrixData['alternatives'];
        $criteria = $matrixData['criteria'];
        $matrixX = $matrixData['matrix_x'];

        // Cek prasyarat (FR-5.6)
        $errors = [];
        if (count($alternatives) < 2) {
            $errors[] = "Jumlah paket kandidat yang diikutkan dalam analisis kurang dari 2 (saat ini: " . count($alternatives) . "). Silakan aktifkan minimal 2 paket di Modul 3.";
        }

        $totalW = 0.0;
        foreach ($criteria as $c) {
            $totalW += (float)$c['weight'];
        }
        if (abs($totalW - 1.0) > 0.0001) {
            $errors[] = "Total bobot kriteria tidak sama dengan 1,00 (saat ini: " . number_format($totalW, 4) . "). Silakan sesuaikan di Modul 4.";
        }

        $pageTitle = 'Perhitungan ELECTRE (Modul 5) - ' . APP_NAME;
        $currentRoute = 'electre';
        require_once __DIR__ . '/../views/electre/index.php';
    }

    /**
     * Jalankan proses perhitungan ELECTRE lengkap dan simpan ke database (FR-5.3, FR-5.4, FR-5.5)
     */
    public function process() {
        $startDate = $_POST['start_date'] ?? ($_SESSION['analysis_period_start'] ?? '2026-02-10');
        $endDate   = $_POST['end_date'] ?? ($_SESSION['analysis_period_end'] ?? '2026-07-09');
        $namaAnalisis = trim($_POST['nama_analisis'] ?? 'Analisis Pemilihan Paket Bundling ' . date('d/m/Y H:i'));
        $catatanAnalisis = trim($_POST['catatan_analisis'] ?? '');

        // Susun matriks awal dari data
        $matrixData = $this->calculator->buildDecisionMatrix($startDate, $endDate);
        $alternatives = $matrixData['alternatives'];
        $criteria = $matrixData['criteria'];
        $matrixX = $matrixData['matrix_x'];

        // Terapkan koreksi manual jika ada (FR-5.2)
        $manualInputs = $_POST['matrix_manual'] ?? []; // [bundle_id => [crit_code => val]]
        $koreksiCatatan = $_POST['catatan_koreksi'] ?? []; // [bundle_id => catatan]

        $scoreRecords = []; // Untuk disimpan ke analysis_scores
        foreach ($alternatives as $bId => $alt) {
            foreach ($criteria as $cCode => $crit) {
                $valAwal = (float)$matrixX[$bId][$cCode];
                $valFinal = $valAwal;
                $valManual = null;

                if (isset($manualInputs[$bId][$cCode]) && is_numeric($manualInputs[$bId][$cCode])) {
                    $valManual = (float)$manualInputs[$bId][$cCode];
                    $valFinal = $valManual;
                    $matrixX[$bId][$cCode] = $valFinal;
                }

                $scoreRecords[] = [
                    'bundle_id' => $bId,
                    'criterion_id' => $crit['id'],
                    'nilai_awal' => $valAwal,
                    'nilai_manual' => $valManual,
                    'nilai_final' => $valFinal,
                    'catatan' => $koreksiCatatan[$bId] ?? null
                ];
            }
        }

        // Jalankan kalkulasi ELECTRE (FR-5.3)
        try {
            $result = $this->electre->calculate($alternatives, $criteria, $matrixX);
        } catch (Exception $e) {
            set_flash('error', $e->getMessage());
            redirect('/?page=electre');
        }

        // Simpan hasil ke database dengan transaksi (FR-5.5, Non-fungsional Keandalan)
        $this->db->beginTransaction();
        try {
            // 1. Simpan Header analysis_runs
            $stmtRun = $this->db->prepare("
                INSERT INTO analysis_runs (nama_analisis, tanggal_analisis, periode_awal, periode_akhir, threshold_c, threshold_d, user_id, catatan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $now = date('Y-m-d H:i:s');
            $stmtRun->execute([
                $namaAnalisis,
                $now,
                $startDate,
                $endDate,
                $result['threshold_c'],
                $result['threshold_d'],
                $_SESSION['user']['id'],
                $catatanAnalisis
            ]);
            $runId = (int)$this->db->lastInsertId();

            // 2. Simpan Skor Alternatif (analysis_scores)
            $stmtScore = $this->db->prepare("
                INSERT INTO analysis_scores (run_id, bundle_id, criterion_id, nilai_awal, nilai_manual, nilai_final, catatan_koreksi)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($scoreRecords as $sc) {
                $stmtScore->execute([
                    $runId,
                    $sc['bundle_id'],
                    $sc['criterion_id'],
                    $sc['nilai_awal'],
                    $sc['nilai_manual'],
                    $sc['nilai_final'],
                    $sc['catatan']
                ]);
            }

            // 3. Simpan Matriks Tahapan (analysis_matrices)
            $matricesToSave = [
                'X' => $result['matrix_x'],
                'R' => $result['matrix_r'],
                'V' => $result['matrix_v'],
                'C' => $result['matrix_c'],
                'D' => $result['matrix_d'],
                'F' => $result['matrix_f'],
                'G' => $result['matrix_g'],
                'E' => $result['matrix_e'],
            ];

            $stmtMat = $this->db->prepare("INSERT INTO analysis_matrices (run_id, matrix_type, matrix_json) VALUES (?, ?, ?)");
            foreach ($matricesToSave as $type => $mat) {
                $stmtMat->execute([$runId, $type, json_encode($mat)]);
            }

            // 4. Simpan Hasil Akhir & Peringkat (analysis_results)
            $stmtRes = $this->db->prepare("
                INSERT INTO analysis_results (run_id, bundle_id, outranking_count, dominated_count, net_score, peringkat, status_dominasi, alasan_rekomendasi)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($result['scores'] as $sc) {
                $stmtRes->execute([
                    $runId,
                    $sc['bundle_id'],
                    $sc['outranking_count'],
                    $sc['dominated_count'],
                    $sc['net_score'],
                    $sc['rank'],
                    $sc['status_dominasi'],
                    $sc['alasan']
                ]);
            }

            $this->db->commit();

            // Arahkan ke halaman tampilan tahapan perhitungan lengkap
            $_SESSION['last_run_id'] = $runId;
            set_flash('success', "Perhitungan metode ELECTRE berhasil diselesaikan dan disimpan dengan ID Analisis #$runId.");
            redirect('/?page=electre_steps&run_id=' . $runId);

        } catch (Exception $e) {
            $this->db->rollBack();
            set_flash('error', 'Gagal menyimpan hasil perhitungan: ' . $e->getMessage());
            redirect('/?page=electre');
        }
    }

    /**
     * Tampilan Berurutan Semua Matriks Tahapan ELECTRE (FR-5.4)
     */
    public function steps() {
        $runId = (int)($_GET['run_id'] ?? ($_SESSION['last_run_id'] ?? 0));

        $stmtRun = $this->db->prepare("
            SELECT ar.*, u.nama as user_nama
            FROM analysis_runs ar
            JOIN users u ON ar.user_id = u.id
            WHERE ar.id = ?
        ");
        $stmtRun->execute([$runId]);
        $run = $stmtRun->fetch();

        if (!$run) {
            set_flash('error', 'Riwayat analisis tidak ditemukan.');
            redirect('/?page=electre');
        }

        // Ambil Matriks dari analysis_matrices
        $stmtMat = $this->db->prepare("SELECT matrix_type, matrix_json FROM analysis_matrices WHERE run_id = ?");
        $stmtMat->execute([$runId]);
        $matrices = [];
        while ($row = $stmtMat->fetch()) {
            $matrices[$row['matrix_type']] = json_decode($row['matrix_json'], true);
        }

        // Ambil Kriteria
        $criteria = $this->db->query("SELECT * FROM criteria ORDER BY urutan ASC")->fetchAll();
        $critMap = [];
        foreach ($criteria as $c) {
            $critMap[$c['kode']] = $c;
        }

        // Ambil Alternatif & Hasil Ranking
        $stmtRes = $this->db->prepare("
            SELECT ar.*, b.kode_paket, b.nama_paket, b.harga_promosi, b.margin_awal
            FROM analysis_results ar
            JOIN bundles b ON ar.bundle_id = b.id
            WHERE ar.run_id = ?
            ORDER BY ar.peringkat ASC
        ");
        $stmtRes->execute([$runId]);
        $results = $stmtRes->fetchAll();

        // Map Alternatif
        $altMap = [];
        foreach ($results as $r) {
            $altMap[$r['bundle_id']] = [
                'code' => $r['kode_paket'],
                'name' => $r['nama_paket'],
                'harga_promosi' => $r['harga_promosi'],
            ];
        }

        $pageTitle = 'Tahapan Perhitungan ELECTRE (Modul 5) - ' . APP_NAME;
        $currentRoute = 'electre';
        require_once __DIR__ . '/../views/electre/steps.php';
    }
}


<?php
// tests/seed_first_run.php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/MatrixCalculator.php';
require_once __DIR__ . '/../app/services/ElectreService.php';

$db = Database::getConnection();

// Cek apakah sudah ada run
$cnt = $db->query("SELECT COUNT(*) FROM analysis_runs")->fetchColumn();
if ($cnt == 0) {
    echo "Creating initial ELECTRE run...\n";
    $calc = new MatrixCalculator($db);
    $data = $calc->buildDecisionMatrix('2026-02-10', '2026-07-09');

    $electre = new ElectreService();
    $result = $electre->calculate($data['alternatives'], $data['criteria'], $data['matrix_x']);

    $db->beginTransaction();
    $stmtRun = $db->prepare("
        INSERT INTO analysis_runs (nama_analisis, tanggal_analisis, periode_awal, periode_akhir, threshold_c, threshold_d, user_id, catatan)
        VALUES (?, '2026-07-10 10:00:00', '2026-02-10', '2026-07-09', ?, ?, 1, 'Analisis Awal Pemilihan Paket Bundling Sesuai PRD')
    ");
    $stmtRun->execute([
        'Analisis Bundling Semester 1 2026',
        $result['threshold_c'],
        $result['threshold_d']
    ]);
    $runId = (int)$db->lastInsertId();

    // Scores
    $stmtScore = $db->prepare("
        INSERT INTO analysis_scores (run_id, bundle_id, criterion_id, nilai_awal, nilai_manual, nilai_final, catatan_koreksi)
        VALUES (?, ?, ?, ?, NULL, ?, NULL)
    ");
    foreach ($data['alternatives'] as $bId => $alt) {
        foreach ($data['criteria'] as $cCode => $crit) {
            $val = (float)$data['matrix_x'][$bId][$cCode];
            $stmtScore->execute([$runId, $bId, $crit['id'], $val, $val]);
        }
    }

    // Matrices
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
    $stmtMat = $db->prepare("INSERT INTO analysis_matrices (run_id, matrix_type, matrix_json) VALUES (?, ?, ?)");
    foreach ($matricesToSave as $type => $mat) {
        $stmtMat->execute([$runId, $type, json_encode($mat)]);
    }

    // Results
    $stmtRes = $db->prepare("
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

    $db->commit();
    echo "Initial ELECTRE run created successfully with Run ID: $runId\n";
} else {
    echo "Analysis run already exists.\n";
}


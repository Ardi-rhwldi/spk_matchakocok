<?php
// tests/test_electre.php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/MatrixCalculator.php';
require_once __DIR__ . '/../app/services/ElectreService.php';

echo "--- Testing Matrix Calculator ---\n";
$calc = new MatrixCalculator();
$matrixData = $calc->buildDecisionMatrix('2026-02-10', '2026-07-09');

echo "Total Alternatif: " . count($matrixData['alternatives']) . "\n";
echo "Total Kriteria: " . count($matrixData['criteria']) . "\n";
echo "\nMatriks Keputusan X:\n";
printf("%-10s %-10s %-10s %-10s %-10s %-10s %-10s %-10s\n", "Kode", "C1", "C2", "C3", "C4", "C5", "C6", "C7");
foreach ($matrixData['matrix_x'] as $bId => $vals) {
    printf("%-10s %-10.2f %-10.2f %-10.2f %-10.2f %-10.2f %-10.2f %-10.2f\n", 
        $matrixData['alternatives'][$bId]['code'],
        $vals['C1'], $vals['C2'], $vals['C3'], $vals['C4'], $vals['C5'], $vals['C6'], $vals['C7']
    );
}

echo "\n--- Testing ELECTRE Calculation ---\n";
$electre = new ElectreService();
$result = $electre->calculate($matrixData['alternatives'], $matrixData['criteria'], $matrixData['matrix_x']);

echo "Ambang Batas c̄: " . number_format($result['threshold_c'], 4) . "\n";
echo "Ambang Batas d̄: " . number_format($result['threshold_d'], 4) . "\n\n";

echo "Hasil Peringkat:\n";
printf("%-5s %-10s %-30s %-10s %-10s %-10s %-12s\n", "Rank", "Kode", "Nama Paket", "Net Score", "Outrank", "Dominated", "C1 (Margin)");
foreach ($result['scores'] as $sc) {
    printf("%-5d %-10s %-30s %-10d %-10d %-10d %-12.2f\n",
        $sc['rank'], $sc['code'], $sc['name'], $sc['net_score'], $sc['outranking_count'], $sc['dominated_count'], $sc['c1_margin']
    );
}

echo "\nTest Passed Successfully!\n";


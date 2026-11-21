<?php
// database/migrate_mysql.php

$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$dbName = 'spk_matchakocok';

echo "=== Memulai Migrasi Database MySQL di XAMPP ===\n";

try {
    // 1. Hubungkan ke MySQL
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    // Buat database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `$dbName`;");
    echo "[OK] Database `$dbName` aktif.\n";

    // 2. Jalankan schema.sql
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        die("Error: File $schemaFile tidak ditemukan.\n");
    }

    // Nonaktifkan foreign key checks sementara untuk instalasi skema
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    $schemaSql = file_get_contents($schemaFile);
    $queries = array_filter(array_map('trim', explode(';', $schemaSql)));
    foreach ($queries as $q) {
        if (!empty($q)) {
            $pdo->exec($q);
        }
    }
    echo "[OK] Skema tabel berhasil dibuat/diverifikasi di MySQL.\n";

    // 3. Impor data riil dari matcha_kocok_cashier.sql
    $cashierFile = __DIR__ . '/matcha_kocok_cashier.sql';
    if (file_exists($cashierFile)) {
        echo "Mengimpor data kasir dari matcha_kocok_cashier.sql ...\n";
        $content = file_get_contents($cashierFile);

        $tables = [
            'product_categories',
            'products',
            'raw_materials',
            'recipes',
            'transactions',
            'transaction_details'
        ];

        // Kosongkan data tabel kasir sebelum mengisi agar tidak duplikat
        foreach (array_reverse($tables) as $t) {
            $pdo->exec("DELETE FROM `$t`;");
        }

        foreach ($tables as $t) {
            $pattern = '/INSERT INTO `' . $t . '`\s*\((.*?)\)\s*VALUES\s*(.*?);/s';
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $cols = $m[1];
                    $vals = $m[2];
                    $pdo->exec("INSERT INTO `$t` ($cols) VALUES $vals;");
                }
                echo "  -> Data `$t` selesai diimpor.\n";
            }
        }

        // Set HPP manual untuk topping
        $pdo->exec("UPDATE products SET hpp_manual = 1000 WHERE product_id IN (6, 7, 8);");
    }

    // 4. Masukkan Akun Pengguna SPK jika belum ada
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $passPemilik = password_hash('pemilik123', PASSWORD_BCRYPT);
        $passAdmin   = password_hash('admin123', PASSWORD_BCRYPT);

        $stmtUser = $pdo->prepare("INSERT INTO users (username, password_hash, nama, peran, aktif) VALUES (?, ?, ?, ?, 1)");
        $stmtUser->execute(['pemilik', $passPemilik, 'Ardi Rihwaldi (Pemilik)', 'pemilik']);
        $stmtUser->execute(['admin', $passAdmin, 'Admin Matcha Cidahu', 'admin']);
        echo "[OK] Akun pengguna SPK dibuat: pemilik (pemilik123) dan admin (admin123).\n";
    }

    // 5. Masukkan 7 Kriteria ELECTRE jika belum ada
    $critCount = $pdo->query("SELECT COUNT(*) FROM criteria")->fetchColumn();
    if ($critCount == 0) {
        $pdo->exec("INSERT INTO criteria (id, kode, nama, tipe, bobot, keterangan, urutan) VALUES 
            (1, 'C1', 'Margin Keuntungan Paket', 'benefit', 0.2500, 'Harga promosi dikurangi HPP paket (HPP dari resep dikali harga bahan)', 1),
            (2, 'C2', 'Frekuensi Dibeli Bersamaan', 'benefit', 0.2000, 'Jumlah transaksi yang memuat semua menu penyusun paket pada periode analisis', 2),
            (3, 'C3', 'Volume Penjualan Menu', 'benefit', 0.1500, 'Total qty terjual seluruh menu penyusun pada periode analisis', 3),
            (4, 'C4', 'Ketersediaan Stok Bahan Baku', 'benefit', 0.1000, 'Jumlah paket yang dapat dibuat dari stok = min(stok bahan / kebutuhan bahan)', 4),
            (5, 'C5', 'Harga Bundling Promosi', 'cost', 0.1000, 'Harga promosi paket yang ditawarkan ke konsumen', 5),
            (6, 'C6', 'Tren Penjualan Menu', 'benefit', 0.1000, 'Persentase perubahan qty penjualan paruh akhir dibanding paruh awal', 6),
            (7, 'C7', 'Kompleksitas Produksi', 'cost', 0.1000, 'Jumlah jenis bahan baku unik pada gabungan resep paket', 7)
        ");
        echo "[OK] 7 Kriteria ELECTRE dimasukkan.\n";
    }

    // 6. Masukkan 10 Kandidat Paket Promosi jika belum ada
    $bundleCount = $pdo->query("SELECT COUNT(*) FROM bundles")->fetchColumn();
    if ($bundleCount == 0) {
        $bundles = [
            ['P01', 'Paket Original + Latte', 22000, 2000, 20000, 11220, 8780, [1, 2]],
            ['P02', 'Paket Original + Caramel', 23000, 2000, 21000, 12270, 8730, [1, 4]],
            ['P03', 'Paket Latte + Caramel', 25000, 2000, 23000, 12870, 10130, [2, 4]],
            ['P04', 'Paket Original + Sea Salt', 25000, 2000, 23000, 12720, 10280, [1, 5]],
            ['P05', 'Paket Original + Blueberry', 23000, 2000, 21000, 12420, 8580, [1, 3]],
            ['P06', 'Paket Latte + Sea Salt', 27000, 2000, 25000, 13320, 11680, [2, 5]],
            ['P07', 'Paket Latte + Blueberry', 25000, 2000, 23000, 13020, 9980, [2, 3]],
            ['P08', 'Paket Blueberry + Caramel', 26000, 2000, 24000, 14070, 9930, [3, 4]],
            ['P09', 'Paket Caramel + Sea Salt', 28000, 2000, 26000, 14370, 11630, [4, 5]],
            ['P10', 'Paket Blueberry + Sea Salt', 28000, 2000, 26000, 14520, 11480, [3, 5]],
        ];

        $stmtBundle = $pdo->prepare("INSERT INTO bundles (id, kode_paket, nama_paket, diskon_tipe, diskon_nilai, harga_normal, harga_promosi, hpp_total, margin_awal, ikut_analisis) VALUES (?, ?, ?, 'nominal', ?, ?, ?, ?, ?, 1)");
        $stmtBItem  = $pdo->prepare("INSERT INTO bundle_items (bundle_id, product_id, qty) VALUES (?, ?, 1)");

        $bId = 1;
        foreach ($bundles as $b) {
            $stmtBundle->execute([$bId, $b[0], $b[1], $b[3], $b[2], $b[4], $b[5], $b[6]]);
            foreach ($b[7] as $pId) {
                $stmtBItem->execute([$bId, $pId]);
            }
            $bId++;
        }
        echo "[OK] 10 Kandidat Paket Promosi dimasukkan.\n";
    }

    // Aktifkan kembali foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 7. Hitung & Simpan Hasil Analisis Awal jika belum ada
    $runCount = $pdo->query("SELECT COUNT(*) FROM analysis_runs")->fetchColumn();
    if ($runCount == 0) {
        require_once __DIR__ . '/../app/services/MatrixCalculator.php';
        require_once __DIR__ . '/../app/services/ElectreService.php';

        $calc = new MatrixCalculator($pdo);
        $data = $calc->buildDecisionMatrix('2026-02-10', '2026-07-09');

        $electre = new ElectreService();
        $result = $electre->calculate($data['alternatives'], $data['criteria'], $data['matrix_x']);

        $stmtRun = $pdo->prepare("
            INSERT INTO analysis_runs (nama_analisis, tanggal_analisis, periode_awal, periode_akhir, threshold_c, threshold_d, user_id, catatan)
            VALUES (?, '2026-07-10 10:00:00', '2026-02-10', '2026-07-09', ?, ?, 1, 'Analisis Awal Pemilihan Paket Bundling Sesuai PRD')
        ");
        $stmtRun->execute([
            'Analisis Bundling Semester 1 2026',
            $result['threshold_c'],
            $result['threshold_d']
        ]);
        $runId = (int)$pdo->lastInsertId();

        $stmtScore = $pdo->prepare("
            INSERT INTO analysis_scores (run_id, bundle_id, criterion_id, nilai_awal, nilai_manual, nilai_final, catatan_koreksi)
            VALUES (?, ?, ?, ?, NULL, ?, NULL)
        ");
        foreach ($data['alternatives'] as $bId => $alt) {
            foreach ($data['criteria'] as $cCode => $crit) {
                $val = (float)$data['matrix_x'][$bId][$cCode];
                $stmtScore->execute([$runId, $bId, $crit['id'], $val, $val]);
            }
        }

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
        $stmtMat = $pdo->prepare("INSERT INTO analysis_matrices (run_id, matrix_type, matrix_json) VALUES (?, ?, ?)");
        foreach ($matricesToSave as $type => $mat) {
            $stmtMat->execute([$runId, $type, json_encode($mat)]);
        }

        $stmtRes = $pdo->prepare("
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
        echo "[OK] Hasil analisis awal ELECTRE berhasil dihitung dan disimpan di MySQL (Run ID: $runId).\n";
    }

    echo "\n=== Verifikasi Data di MySQL `$dbName` ===\n";
    $tablesCheck = [
        'users',
        'product_categories',
        'products',
        'raw_materials',
        'recipes',
        'transactions',
        'transaction_details',
        'bundles',
        'bundle_items',
        'criteria',
        'analysis_runs',
        'analysis_results'
    ];

    foreach ($tablesCheck as $tc) {
        $c = $pdo->query("SELECT COUNT(*) FROM `$tc`")->fetchColumn();
        printf("%-25s: %d baris\n", $tc, $c);
    }

    echo "\nMigrasi ke MySQL lokal berhasil 100%!\n";

} catch (Exception $e) {
    echo "ERROR MIGRASI: " . $e->getMessage() . "\n";
}


<?php
// database/setup.php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

function runSetup($isCli = false) {
    $db = Database::getConnection();
    $driver = Database::getActiveDriver();
    $messages = [];

    // Jika database sudah memiliki data transaksi kasir, tidak perlu diulang
    try {
        $checkTrx = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
        if ($checkTrx >= 700) {
            $messages[] = "Database sudah siap dan memuat <strong>$checkTrx transaksi kasir riil</strong>.";
            return $messages;
        }
    } catch (Exception $e) {}

    // Jalankan setup penuh dengan dump kasir
    require_once __DIR__ . '/setup_with_dump.php';
    $messages[] = "Database kasir riil berhasil dimuat ke dalam SPK (709 transaksi, 11 bahan baku, 8 produk, 32 resep, 7 kriteria, 10 kandidat paket).";
    return $messages;
}

if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'setup.php') {
    echo "=== Menjalankan Setup & Seeder SPK Matcha Kocok ===\n";
    $results = runSetup(true);
    foreach ($results as $res) {
        echo strip_tags($res) . "\n";
    }
    echo "=== Selesai! ===\n";
}

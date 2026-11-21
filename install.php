<?php
// install.php - Web Installer & Database Initializer
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/database/setup.php';

$messages = [];
$installed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    try {
        $messages = runSetup(false);
        $installed = true;
    } catch (Exception $e) {
        $messages[] = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi & Pengaturan Basis Data - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
    <style>
        body {
            background-color: #f4f6f3;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .install-box {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 600px;
            padding: 35px;
        }
    </style>
</head>
<body>

<div class="install-box">
    <div class="text-center mb-4">
        <div style="font-size: 48px;">🍵</div>
        <h4 class="fw-bold text-success mb-1"><?= APP_NAME ?></h4>
        <p class="text-muted small"><?= APP_OBJECT ?> &bull; <?= APP_INSTITUTION ?></p>
        <hr>
    </div>

    <?php if ($installed): ?>
        <div class="alert alert-success">
            <h6 class="fw-bold mb-2"><i class="bi bi-check-circle-fill me-1"></i> Database Siap Digunakan!</h6>
            <ul class="mb-0 small ps-3">
                <?php foreach ($messages as $msg): ?>
                    <li><?= $msg ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="p-3 bg-light rounded border mb-4 small">
            <strong>Kredensial Login Default:</strong><br>
            - Pemilik: <code>pemilik</code> / Kata Sandi: <code>pemilik123</code><br>
            - Admin: <code>admin</code> / Kata Sandi: <code>admin123</code>
        </div>

        <div class="d-grid gap-2">
            <a href="<?= BASE_URL ?>/?page=login" class="btn btn-matcha py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Buka Halaman Login Aplikasi
            </a>
        </div>
    <?php else: ?>
        <p class="text-muted small mb-4">
            Halaman ini akan menyiapkan tabel-tabel database MySQL / MariaDB (atau SQLite sebagai fallback cerdas) beserta <strong>709 data transaksi operasional riil (10 Feb - 9 Jul 2026), 11 bahan baku, 5 menu matcha, 3 topping, 32 resep HPP, dan 7 kriteria ELECTRE</strong> sesuai PRD.
        </p>

        <form method="POST">
            <button type="submit" class="btn btn-matcha w-100 py-3 fs-6">
                <i class="bi bi-database-check me-2"></i> Inisialisasi Database & Pasang Data Sekarang
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>


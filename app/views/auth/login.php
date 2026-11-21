<?php
// app/views/auth/login.php
if (!defined('APP_NAME')) exit;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <style>
    <?php
    $cssFile = __DIR__ . '/../../../assets/css/custom.css';
    if (file_exists($cssFile)) echo file_get_contents($cssFile);
    ?>
    /* Login page overrides */
    body {
        background: #f7f7f6;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 24px;
    }
    </style>
</head>
<body>

<div class="login-card">
    <!-- Header -->
    <div class="login-header">
        <div class="login-logo">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 7H19V13C19 14.6569 17.6569 16 16 16H8C6.34315 16 5 14.6569 5 13V7Z" fill="#6b8f6d"/>
                <path d="M4 18C4 18.5523 4.44772 19 5 19H19C19.5523 19 20 18.5523 20 18V17H4V18Z" fill="#c8ddc9"/>
                <path d="M19 9H21C21.5523 9 22 9.44772 22 10V12C22 12.5523 21.5523 13 21 13H19V9Z" stroke="#6b8f6d" stroke-width="1.5"/>
                <path d="M8 4C8 4 8.5 5.5 7.5 6.2" stroke="#6b8f6d" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M12 3.5C12 3.5 12.5 5 11.5 5.7" stroke="#6b8f6d" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M16 4C16 4 16.5 5.5 15.5 6.2" stroke="#6b8f6d" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
        <h4 class="fw-semibold mb-1" style="font-size:1rem;color:var(--text-primary);"><?= APP_NAME ?></h4>
        <p class="text-muted" style="font-size:0.8rem;margin:0;"><?= APP_OBJECT ?></p>
        <span class="badge badge-matcha" style="margin-top:8px;display:inline-block;">Metode ELECTRE</span>
    </div>

    <!-- Body -->
    <div class="login-body">
        <?php if ($flashError = get_flash('error')): ?>
            <div class="alert alert-danger" role="alert" style="font-size:0.82rem;padding:10px 14px;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?= htmlspecialchars($flashError) ?>
            </div>
        <?php endif; ?>
        <?php if ($flashSuccess = get_flash('success')): ?>
            <div class="alert alert-success" role="alert" style="font-size:0.82rem;padding:10px 14px;">
                <i class="bi bi-check-circle me-1"></i>
                <?= htmlspecialchars($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/?action=login" method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="pemilik / admin" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Kata Sandi</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-matcha w-100" style="justify-content:center;padding:9px 14px;">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
            </button>
        </form>

        <div style="margin-top:16px;padding:12px;background:#fafaf9;border:1px solid var(--border);border-radius:var(--radius);font-size:0.78rem;color:var(--text-muted);">
            <div style="font-weight:600;color:var(--text-secondary);margin-bottom:6px;">
                <i class="bi bi-info-circle me-1"></i> Akun Bawaan Sistem:
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                <span>Pemilik: <code>pemilik</code></span>
                <span>Sandi: <code>pemilik123</code></span>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span>Admin: <code>admin</code></span>
                <span>Sandi: <code>admin123</code></span>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>

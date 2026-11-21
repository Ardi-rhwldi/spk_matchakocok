<?php
// app/views/layouts/navbar.php
if (!defined('APP_NAME')) exit;
$user = $_SESSION['user'] ?? ['nama' => 'Pengguna', 'peran' => 'admin'];
$bcLabel = $GLOBALS['_sidebar_bc_label'] ?? 'Dashboard';
?>
<main class="app-main">
    <!-- Top Navbar -->
    <header class="app-navbar">
        <!-- Left: Breadcrumb -->
        <div class="navbar-breadcrumb">
            <span class="bc-workspace">Ruang kerja</span>
            <span class="bc-sep">›</span>
            <span class="bc-page"><?= htmlspecialchars($bcLabel) ?></span>
        </div>

        <!-- Right: actions + user -->
        <div class="navbar-actions">
            <!-- Bell -->
            <button class="navbar-icon-btn" title="Notifikasi">
                <i class="bi bi-bell" style="font-size:15px;"></i>
            </button>

            <!-- User dropdown -->
            <div class="dropdown">
                <button class="navbar-user dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="navbar-avatar">
                        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($user['nama']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>/?page=profile">
                            <i class="bi bi-key"></i> Ganti Kata Sandi
                        </a>
                    </li>
                    <li><div class="dropdown-divider"></div></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/?action=logout">
                            <i class="bi bi-box-arrow-right"></i> Keluar (Logout)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <div class="page-container">
        <?php if ($flashSuccess = get_flash('success')): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <i class="bi bi-check-circle"></i>
                <span><?= htmlspecialchars($flashSuccess) ?></span>
                <button type="button" class="btn-close" onclick="this.closest('.alert').remove()">×</button>
            </div>
        <?php endif; ?>
        <?php if ($flashError = get_flash('error')): ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <span><?= htmlspecialchars($flashError) ?></span>
                <button type="button" class="btn-close" onclick="this.closest('.alert').remove()">×</button>
            </div>
        <?php endif; ?>
        <?php if ($flashWarning = get_flash('warning')): ?>
            <div class="alert alert-warning alert-dismissible" role="alert">
                <i class="bi bi-info-circle"></i>
                <span><?= htmlspecialchars($flashWarning) ?></span>
                <button type="button" class="btn-close" onclick="this.closest('.alert').remove()">×</button>
            </div>
        <?php endif; ?>

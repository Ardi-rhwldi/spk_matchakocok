<?php
// app/views/layouts/sidebar.php
if (!defined('APP_NAME')) exit;
$currentRoute = $currentRoute ?? 'dashboard';
$userRole = $_SESSION['user']['peran'] ?? 'admin';
$isPemilik = ($userRole === 'pemilik');

// Determine page label for breadcrumb
$pageLabels = [
    'dashboard'   => 'Dashboard',
    'products'    => 'Data Master',
    'materials'   => 'Data Master',
    'recipes'     => 'Data Master',
    'transactions'=> 'Data Master',
    'import'      => 'Data Master',
    'bundles'     => 'Alternatif Paket',
    'criteria'    => 'Kriteria & Bobot',
    'electre'     => 'Perhitungan ELECTRE',
    'reports'     => 'Laporan/Rekapitulasi',
    'simulation'  => 'Laporan/Rekapitulasi',
    'users'       => 'Manajemen Pengguna',
    'profile'     => 'Profil Akun',
];
$GLOBALS['_sidebar_bc_label'] = $pageLabels[$currentRoute] ?? ucfirst($currentRoute);
?>
<aside class="app-sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 7H19V13C19 14.6569 17.6569 16 16 16H8C6.34315 16 5 14.6569 5 13V7Z" fill="#6b8f6d"/>
                <path d="M4 18C4 18.5523 4.44772 19 5 19H19C19.5523 19 20 18.5523 20 18V17H4V18Z" fill="#c8ddc9"/>
                <path d="M19 9H21C21.5523 9 22 9.44772 22 10V12C22 12.5523 21.5523 13 21 13H19V9Z" stroke="#6b8f6d" stroke-width="1.5"/>
                <path d="M8 4C8 4 8.5 5.5 7.5 6.2" stroke="#6b8f6d" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M12 3.5C12 3.5 12.5 5 11.5 5.7" stroke="#6b8f6d" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M16 4C16 4 16.5 5.5 15.5 6.2" stroke="#6b8f6d" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
        <div>
            <div class="brand-title">Matcha Kocok</div>
            <div class="brand-subtitle">SPK Bundling ELECTRE</div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="sidebar-nav">
        <ul class="sidebar-menu">
            <!-- Utama -->
            <li class="menu-section">Utama</li>
            <li class="<?= $currentRoute === 'dashboard' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=dashboard">
                    <i class="bi bi-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Data Master -->
            <li class="menu-section">Data Master</li>
            <li class="<?= $currentRoute === 'products' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=products">
                    <i class="bi bi-cup-hot"></i>
                    <span>Menu &amp; Topping</span>
                </a>
            </li>
            <li class="<?= $currentRoute === 'materials' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=materials">
                    <i class="bi bi-box-seam"></i>
                    <span>Bahan Baku &amp; Stok</span>
                </a>
            </li>
            <li class="<?= $currentRoute === 'recipes' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=recipes">
                    <i class="bi bi-journal-text"></i>
                    <span>Resep &amp; HPP Menu</span>
                </a>
            </li>
            <li class="<?= $currentRoute === 'transactions' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=transactions">
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat Penjualan</span>
                </a>
            </li>
            <li class="<?= $currentRoute === 'import' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=import">
                    <i class="bi bi-upload"></i>
                    <span>Impor Data Penjualan</span>
                </a>
            </li>

            <!-- SPK & Keputusan -->
            <li class="menu-section">SPK &amp; Keputusan</li>
            <li class="<?= $currentRoute === 'bundles' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=bundles">
                    <i class="bi bi-collection"></i>
                    <span>Alternatif Paket</span>
                </a>
            </li>
            <li class="<?= $currentRoute === 'criteria' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=criteria">
                    <i class="bi bi-sliders"></i>
                    <span>Kriteria &amp; Bobot</span>
                </a>
            </li>
            <li class="<?= $currentRoute === 'electre' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=electre">
                    <i class="bi bi-calculator"></i>
                    <span>Perhitungan ELECTRE</span>
                </a>
            </li>

            <!-- Laporan -->
            <li class="menu-section">Laporan</li>
            <li class="<?= $currentRoute === 'reports' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=reports">
                    <i class="bi bi-bar-chart"></i>
                    <span>Hasil &amp; Laporan</span>
                </a>
            </li>
            <li class="<?= $currentRoute === 'simulation' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=simulation">
                    <i class="bi bi-graph-up"></i>
                    <span>Simulasi Proyeksi Laba</span>
                </a>
            </li>

            <?php if ($isPemilik): ?>
            <!-- Sistem -->
            <li class="menu-section">Sistem</li>
            <li class="<?= $currentRoute === 'users' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/?page=users">
                    <i class="bi bi-people"></i>
                    <span>Manajemen Pengguna</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Sidebar footer: logged in user -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <div style="width:28px;height:28px;border-radius:50%;background:var(--sage-subtle);border:1px solid var(--sage-light);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;color:var(--sage-dark);flex-shrink:0;">
                <?= strtoupper(substr($_SESSION['user']['nama'] ?? 'U', 0, 1)) ?>
            </div>
            <div style="overflow:hidden;">
                <div style="font-size:0.78rem;font-weight:500;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Pengguna') ?>
                </div>
                <div style="font-size:0.68rem;color:var(--text-muted);"><?= ucfirst($_SESSION['user']['peran'] ?? 'Admin') ?></div>
            </div>
        </div>
    </div>
</aside>

<?php
// app/views/bundles/index.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';

$activeCount = 0;
foreach ($bundles as $b) {
    if ($b['ikut_analisis']) $activeCount++;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-tags-fill text-success me-2"></i> Manajemen Alternatif Paket Promosi (Modul 3)</h4>
        <p class="text-muted small mb-0">Kelola paket bundling minuman matcha, harga normal, diskon, HPP, serta keikutsertaan analisis</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/?page=bundles_generate" class="btn btn-matcha-outline">
            <i class="bi bi-magic me-1"></i> Generator Otomatis (FR-3.2)
        </a>
        <a href="<?= BASE_URL ?>/?page=bundles_create" class="btn btn-matcha">
            <i class="bi bi-plus-circle me-1"></i> Buat Paket Manual (FR-3.1)
        </a>
    </div>
</div>

<div class="alert alert-matcha d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <div>
            <strong>Rekomendasi PRD Bagian 7.1:</strong> Dari kombinasi menu yang ada, disarankan memilih <strong>8 sampai 12 alternatif</strong> aktif untuk dianalisis agar proses komparasi ELECTRE seimbang.
        </div>
    </div>
    <span class="badge bg-success fs-6 py-2 px-3">
        <?= $activeCount ?> Paket Terpilih untuk Analisis
    </span>
</div>

<div class="card-matcha">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">Ikut Analisis</th>
                        <th>Kode</th>
                        <th>Nama Paket Bundling</th>
                        <th>Menu Penyusun</th>
                        <th>Harga Normal</th>
                        <th>Diskon</th>
                        <th>Harga Promosi (C5)</th>
                        <th>HPP Paket</th>
                        <th>Margin Awal (C1)</th>
                        <th>Dibeli Bersamaan (C2)</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bundles)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">Belum ada paket bundling yang didaftarkan.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($bundles as $b): ?>
                        <tr class="<?= !$b['ikut_analisis'] ? 'opacity-50 table-light' : '' ?>">
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/?action=toggle_bundle&id=<?= $b['id'] ?>" class="btn btn-sm <?= $b['ikut_analisis'] ? 'btn-success' : 'btn-outline-secondary' ?>" title="Klik untuk mengaktifkan / menonaktifkan">
                                    <i class="bi <?= $b['ikut_analisis'] ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
                                </a>
                            </td>
                            <td><span class="badge bg-dark-subtle text-dark border"><?= htmlspecialchars($b['kode_paket']) ?></span></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($b['nama_paket']) ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($b['item_names']) ?></small></td>
                            <td class="text-muted"><?= format_rupiah($b['harga_normal']) ?></td>
                            <td class="text-danger">
                                -<?= format_rupiah($b['harga_normal'] - $b['harga_promosi']) ?>
                                <?php if ($b['diskon_tipe'] === 'persen'): ?>
                                    <small>(<?= number_format($b['diskon_nilai'], 0) ?>%)</small>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-success"><?= format_rupiah($b['harga_promosi']) ?></td>
                            <td class="text-muted"><?= format_rupiah($b['hpp_total']) ?></td>
                            <td class="fw-bold text-primary"><?= format_rupiah($b['margin_awal']) ?></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="bi bi-cart4 me-1"></i> <?= $b['freq'] ?> Trx
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/?page=bundles_edit&id=<?= $b['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/?action=delete_bundle&id=<?= $b['id'] ?>" class="btn btn-outline-danger" title="Hapus" onclick="return confirm('Hapus paket bundling ini?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


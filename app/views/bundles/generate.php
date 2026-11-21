<?php
// app/views/bundles/generate.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-magic text-success me-2"></i> Generator Kandidat Paket Otomatis (FR-3.2)</h4>
        <p class="text-muted small mb-0">Sistem otomatis membentuk kombinasi 2 menu (10 kombinasi) dan 3 menu (10 kombinasi) dari produk aktif beserta frekuensi beli bersama</p>
    </div>
    <a href="<?= BASE_URL ?>/?page=bundles" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Paket
    </a>
</div>

<form action="<?= BASE_URL ?>/?action=apply_generated_bundles" method="POST">
    <div class="card-matcha mb-4">
        <div class="card-header-matcha">
            <h5><i class="bi bi-grid-3x3-gap-fill me-2"></i> Seluruh Kombinasi Paket Bundling Yang Dapat Dihasilkan</h5>
            <button type="submit" class="btn btn-matcha">
                <i class="bi bi-check-all me-1"></i> Tambahkan Kandidat Terpilih ke SPK
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">Pilih</th>
                            <th>Kode</th>
                            <th>Kombinasi Paket</th>
                            <th>Jenis Kombinasi</th>
                            <th>Harga Normal</th>
                            <th>Diskon Default</th>
                            <th>Harga Promosi</th>
                            <th>HPP Terhitung</th>
                            <th>Margin Awal</th>
                            <th>Dibeli Bersamaan (C2)</th>
                            <th>Status di Sistem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidates as $c): ?>
                        <tr class="<?= $c['is_existing'] ? 'table-light text-muted' : '' ?>">
                            <td class="text-center">
                                <?php if ($c['is_existing']): ?>
                                    <i class="bi bi-check2-all text-success fs-5" title="Sudah terdaftar di sistem"></i>
                                <?php else: ?>
                                    <input type="checkbox" name="selected_candidates[]" value="<?= $c['code'] ?>" class="form-check-input">
                                <?php endif; ?>
                            </td>
                            <td><code><?= $c['code'] ?></code></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($c['nama_paket']) ?></td>
                            <td>
                                <span class="badge <?= $c['combo_size'] == 2 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-info-subtle text-info border border-info-subtle' ?>">
                                    <?= $c['combo_size'] ?> Menu Minuman
                                </span>
                            </td>
                            <td><?= format_rupiah($c['harga_normal']) ?></td>
                            <td class="text-danger">-<?= format_rupiah($c['diskon']) ?></td>
                            <td class="fw-bold text-success"><?= format_rupiah($c['harga_promosi']) ?></td>
                            <td class="text-muted"><?= format_rupiah($c['hpp_total']) ?></td>
                            <td class="fw-bold text-primary"><?= format_rupiah($c['margin_awal']) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-cart4 text-success me-1"></i> <?= $c['frekuensi_bersama'] ?> Transaksi
                                </span>
                            </td>
                            <td>
                                <?php if ($c['is_existing']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Sudah Ada di SPK</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Belum Terdaftar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


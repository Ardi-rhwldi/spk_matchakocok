<?php
// app/views/master/products.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cup-hot-fill text-success me-2"></i> Master Menu Minuman & Topping</h4>
        <p class="text-muted small mb-0">Kelola daftar produk, harga jual, dan HPP untuk perhitungan bundling</p>
    </div>
    <button class="btn btn-matcha" data-bs-toggle="modal" data-bs-target="#modalAddProduct">
        <i class="bi bi-plus-circle me-1"></i> Tambah Menu / Topping
    </button>
</div>

<?php if (!empty($zeroHppWarnings)): ?>
    <div class="alert alert-warning py-3" role="alert">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <strong>Peringatan Kualitas Data (FR-2.7):</strong>
        </div>
        <p class="mb-0 small">
            Menu/Topping berikut belum memiliki resep atau HPP masih bernilai Rp 0: 
            <strong><?= implode(', ', array_map('htmlspecialchars', $zeroHppWarnings)) ?></strong>.
            Sesuai PRD Bagian 6 Catatan #1, HPP manual harus diisi sebelum produk dipakai dalam kandidat paket promosi agar margin tidak bias.
        </p>
    </div>
<?php endif; ?>

<div class="card-matcha">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Menu / Topping</th>
                        <th>Kategori</th>
                        <th>Harga Jual Normal</th>
                        <th>HPP Terhitung</th>
                        <th>Margin Kotor</th>
                        <th>Status Resep</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <?php 
                        $marginRp = $p['harga'] - $p['hpp_calculated'];
                        $marginPct = ($p['harga'] > 0) ? ($marginRp / $p['harga']) * 100 : 0;
                    ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($p['nama_produk']) ?></td>
                        <td>
                            <span class="badge <?= $p['category_id'] == 1 ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' ?> border">
                                <?= htmlspecialchars($p['nama_kategori']) ?>
                            </span>
                        </td>
                        <td class="fw-semibold"><?= format_rupiah($p['harga']) ?></td>
                        <td>
                            <?php if ($p['hpp_calculated'] > 0): ?>
                                <span class="text-success fw-bold"><?= format_rupiah($p['hpp_calculated']) ?></span>
                                <?php if ($p['recipe_count'] == 0 && !empty($p['hpp_manual'])): ?>
                                    <small class="text-muted">(Manual)</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger">Rp 0 (Perlu Diisi)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= format_rupiah($marginRp) ?>
                            <small class="text-muted">(<?= number_format($marginPct, 1) ?>%)</small>
                        </td>
                        <td>
                            <?php if ($p['recipe_count'] > 0): ?>
                                <a href="<?= BASE_URL ?>/?page=recipes&product_id=<?= $p['id'] ?>" class="badge bg-light text-primary border">
                                    <i class="bi bi-list-check me-1"></i> <?= $p['recipe_count'] ?> Bahan
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">Tanpa Resep</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $p['status_aktif'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditProduct<?= $p['id'] ?>">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            <?php if ($p['category_id'] == 1): ?>
                                <a href="<?= BASE_URL ?>/?page=recipes&product_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-gear"></i> Resep
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Modal Edit Produk -->
                    <div class="modal fade" id="modalEditProduct<?= $p['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= BASE_URL ?>/?action=update_product" method="POST">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Menu: <?= htmlspecialchars($p['nama_produk']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Nama Menu / Topping</label>
                                            <input type="text" name="nama_produk" class="form-control" value="<?= htmlspecialchars($p['nama_produk']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Kategori</label>
                                            <select name="category_id" class="form-select">
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['id'] ?>" <?= $p['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cat['nama_kategori']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Harga Jual Normal (Rp)</label>
                                            <input type="number" step="500" name="harga" class="form-control" value="<?= $p['harga'] ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">HPP Manual (Rp) <span class="text-muted fw-normal">(Khusus topping / tanpa resep)</span></label>
                                            <input type="number" step="100" name="hpp_manual" class="form-control" value="<?= $p['hpp_manual'] ?? '' ?>" placeholder="Kosongkan jika dihitung otomatis dari resep">
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="status_aktif" id="statusAktif<?= $p['id'] ?>" <?= $p['status_aktif'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="statusAktif<?= $p['id'] ?>">Status Menu Aktif</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-matcha">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Produk -->
<div class="modal fade" id="modalAddProduct" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/?action=store_product" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Menu / Topping Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Menu / Topping</label>
                        <input type="text" name="nama_produk" class="form-control" required placeholder="Contoh: Roasted Hojicha">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kategori</label>
                        <select name="category_id" class="form-select">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Harga Jual Normal (Rp)</label>
                        <input type="number" step="500" name="harga" class="form-control" required placeholder="Contoh: 14000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">HPP Manual (Rp) <span class="text-muted fw-normal">(Opsional)</span></label>
                        <input type="number" step="100" name="hpp_manual" class="form-control" placeholder="Wajib untuk topping">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-matcha">Tambah Menu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


<?php
// app/views/master/recipes.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-receipt text-success me-2"></i> Master Resep & Komposisi HPP</h4>
        <p class="text-muted small mb-0">Kelola komposisi bahan baku per porsi menu untuk menentukan HPP otomatis (FR-2.3, FR-2.4)</p>
    </div>
</div>

<div class="row">
    <!-- Kolom Kiri: Pilihan Menu -->
    <div class="col-md-4">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5><i class="bi bi-list-ul me-2"></i> Pilih Menu Minuman</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($products as $p): ?>
                <a href="<?= BASE_URL ?>/?page=recipes&product_id=<?= $p['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $p['id'] == $productId ? 'active bg-success text-white' : '' ?>">
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($p['nama_produk']) ?></div>
                        <small class="<?= $p['id'] == $productId ? 'text-white-50' : 'text-muted' ?>">Harga Jual: <?= format_rupiah($p['harga']) ?></small>
                    </div>
                    <i class="bi bi-chevron-right"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Rincian Resep & HPP -->
    <div class="col-md-8">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5><i class="bi bi-cup-straw me-2"></i> Resep: <?= htmlspecialchars($selectedProduct['nama_produk'] ?? '') ?></h5>
                <button class="btn btn-sm btn-matcha" data-bs-toggle="modal" data-bs-target="#modalAddRecipe">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Bahan ke Resep
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Bahan Baku</th>
                                <th>Takaran / Porsi</th>
                                <th>Harga Satuan Bahan</th>
                                <th>Subtotal HPP</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recipeItems)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                    Menu ini belum memiliki komposisi resep bahan.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recipeItems as $r): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($r['nama_bahan']) ?></td>
                                    <td><?= number_format($r['takaran'], 3, ',', '.') ?> <?= htmlspecialchars($r['satuan']) ?></td>
                                    <td class="text-muted"><?= format_rupiah($r['harga_per_unit']) ?> / <?= htmlspecialchars($r['satuan']) ?></td>
                                    <td class="fw-bold text-success"><?= format_rupiah($r['subtotal_hpp']) ?></td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>/?action=delete_recipe_item&product_id=<?= $productId ?>&raw_material_id=<?= $r['raw_material_id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Hapus bahan ini dari resep?');">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="3" class="text-end">Total HPP per Cup:</th>
                                <th class="text-success fs-5"><?= format_rupiah($totalHpp) ?></th>
                                <th></th>
                            </tr>
                            <tr class="table-light">
                                <th colspan="3" class="text-end">Margin Kotor per Cup:</th>
                                <th class="text-primary fs-6">
                                    <?php 
                                        $jual = $selectedProduct['harga'] ?? 0;
                                        $marginCup = $jual - $totalHpp;
                                        $marginPct = ($jual > 0) ? ($marginCup / $jual) * 100 : 0;
                                    ?>
                                    <?= format_rupiah($marginCup) ?> (<?= number_format($marginPct, 1) ?>%)
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Bahan ke Resep -->
<div class="modal fade" id="modalAddRecipe" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/?action=store_recipe_item" method="POST">
                <input type="hidden" name="product_id" value="<?= $productId ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Bahan ke Resep</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Bahan Baku</label>
                        <select name="raw_material_id" class="form-select" required>
                            <option value="">-- Pilih Bahan Baku --</option>
                            <?php foreach ($materials as $m): ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= htmlspecialchars($m['nama_bahan']) ?> (Satuan: <?= htmlspecialchars($m['satuan']) ?> - <?= format_rupiah($m['harga_per_unit']) ?>/<?= htmlspecialchars($m['satuan']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Takaran per Cup / Porsi</label>
                        <input type="number" step="0.001" name="takaran" class="form-control" required placeholder="Contoh: 10 untuk 10 gram / 80 untuk 80 ml">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-matcha">Simpan ke Resep</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


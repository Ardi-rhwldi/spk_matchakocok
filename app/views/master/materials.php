<?php
// app/views/master/materials.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-box-seam-fill text-success me-2"></i> Master Bahan Baku & Ketersediaan Stok</h4>
        <p class="text-muted small mb-0">Kelola stok bahan baku, stok minimum peringatan, dan harga per unit untuk HPP resep</p>
    </div>
    <button class="btn btn-matcha" data-bs-toggle="modal" data-bs-target="#modalAddMaterial">
        <i class="bi bi-plus-circle me-1"></i> Tambah Bahan Baku
    </button>
</div>

<div class="card-matcha">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Bahan Baku</th>
                        <th>Satuan</th>
                        <th>Sisa Stok Saat Ini</th>
                        <th>Stok Minimum</th>
                        <th>Harga per Unit</th>
                        <th>Status Ketersediaan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materials as $m): ?>
                    <?php 
                        $isLow = $m['stok'] <= $m['stok_minimum'];
                    ?>
                    <tr class="<?= $isLow ? 'table-warning' : '' ?>">
                        <td>#<?= $m['id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($m['nama_bahan']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($m['satuan']) ?></span></td>
                        <td class="fw-bold <?= $isLow ? 'text-danger' : 'text-dark' ?>">
                            <?= number_format($m['stok'], 2, ',', '.') ?> <?= htmlspecialchars($m['satuan']) ?>
                        </td>
                        <td class="text-muted">
                            <?= number_format($m['stok_minimum'], 2, ',', '.') ?> <?= htmlspecialchars($m['satuan']) ?>
                        </td>
                        <td class="fw-semibold"><?= format_rupiah($m['harga_per_unit']) ?> / <?= htmlspecialchars($m['satuan']) ?></td>
                        <td>
                            <?php if ($isLow): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Menipis
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success">Aman</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditMaterial<?= $m['id'] ?>">
                                <i class="bi bi-pencil-square"></i> Update Stok/Harga
                            </button>
                        </td>
                    </tr>

                    <!-- Modal Edit Bahan -->
                    <div class="modal fade" id="modalEditMaterial<?= $m['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= BASE_URL ?>/?action=update_material" method="POST">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Update Bahan: <?= htmlspecialchars($m['nama_bahan']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Nama Bahan Baku</label>
                                            <input type="text" name="nama_bahan" class="form-control" value="<?= htmlspecialchars($m['nama_bahan']) ?>" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label small fw-bold">Satuan</label>
                                                <input type="text" name="satuan" class="form-control" value="<?= htmlspecialchars($m['satuan']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label small fw-bold">Harga per Unit (Rp)</label>
                                                <input type="number" step="0.01" name="harga_per_unit" class="form-control" value="<?= $m['harga_per_unit'] ?>" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label small fw-bold">Sisa Stok Fisik</label>
                                                <input type="number" step="0.01" name="stok" class="form-control" value="<?= $m['stok'] ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label small fw-bold">Batas Stok Minimum</label>
                                                <input type="number" step="0.01" name="stok_minimum" class="form-control" value="<?= $m['stok_minimum'] ?>" required>
                                            </div>
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

<!-- Modal Tambah Bahan -->
<div class="modal fade" id="modalAddMaterial" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/?action=store_material" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Bahan Baku Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Bahan Baku</label>
                        <input type="text" name="nama_bahan" class="form-control" required placeholder="Contoh: Bubuk Matcha Gold">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Satuan</label>
                            <input type="text" name="satuan" class="form-control" required placeholder="gram / ml / pcs / kg">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Harga per Unit (Rp)</label>
                            <input type="number" step="0.01" name="harga_per_unit" class="form-control" required placeholder="Contoh: 250">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Stok Awal</label>
                            <input type="number" step="0.01" name="stok" class="form-control" required placeholder="Contoh: 1000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Stok Minimum</label>
                            <input type="number" step="0.01" name="stok_minimum" class="form-control" required placeholder="Contoh: 200">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-matcha">Tambah Bahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


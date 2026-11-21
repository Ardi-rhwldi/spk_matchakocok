<?php
// app/views/bundles/form.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';

$isEdit = !empty($bundle);
$actionUrl = $isEdit ? BASE_URL . '/?action=update_bundle' : BASE_URL . '/?action=store_bundle';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-tags text-success me-2"></i> <?= $isEdit ? 'Edit Paket Bundling' : 'Tambah Paket Bundling Manual' ?>
        </h4>
        <p class="text-muted small mb-0">Tentukan menu penyusun (2 sampai 3 varian menu) dan tetapkan diskon promosi</p>
    </div>
    <a href="<?= BASE_URL ?>/?page=bundles" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<form action="<?= $actionUrl ?>" method="POST" id="bundleForm">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $bundle['id'] ?>">
    <?php endif; ?>

    <div class="row">
        <!-- Rincian Identitas & Diskon Paket -->
        <div class="col-md-5">
            <div class="card-matcha">
                <div class="card-header-matcha">
                    <h5><i class="bi bi-info-circle me-2"></i> Identitas & Aturan Diskon</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Paket</label>
                        <input type="text" name="kode_paket" class="form-control" value="<?= htmlspecialchars($bundle['kode_paket'] ?? '') ?>" required placeholder="Contoh: P11">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Paket Promosi</label>
                        <input type="text" name="nama_paket" class="form-control" value="<?= htmlspecialchars($bundle['nama_paket'] ?? '') ?>" required placeholder="Contoh: Paket Double Matcha Mantap">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Jenis Diskon</label>
                            <select name="diskon_tipe" id="diskonTipe" class="form-select">
                                <option value="nominal" <?= ($bundle['diskon_tipe'] ?? '') === 'nominal' ? 'selected' : '' ?>>Nominal Rupiah (Rp)</option>
                                <option value="persen" <?= ($bundle['diskon_tipe'] ?? '') === 'persen' ? 'selected' : '' ?>>Persentase (%)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Nilai Diskon</label>
                            <input type="number" step="100" name="diskon_nilai" id="diskonNilai" class="form-control" value="<?= $bundle['diskon_nilai'] ?? '2000' ?>" required>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border mt-3">
                        <div class="fw-bold small text-dark mb-2">Simulasi Harga & Margin:</div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Total Harga Normal:</span>
                            <span id="previewNormal" class="fw-bold">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Diskon Diterapkan:</span>
                            <span id="previewDiskon" class="text-danger fw-bold">- Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between small border-top pt-1 mb-1">
                            <span><strong>Harga Promosi (C5):</strong></span>
                            <span id="previewPromosi" class="text-success fw-bold fs-6">Rp 0</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-matcha w-100 mt-4 py-2">
                        <i class="bi bi-check-circle me-1"></i> <?= $isEdit ? 'Simpan Perubahan Paket' : 'Daftarkan Paket' ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Pemilihan Menu Penyusun -->
        <div class="col-md-7">
            <div class="card-matcha">
                <div class="card-header-matcha">
                    <h5><i class="bi bi-cup-hot me-2"></i> Pilih 2 s.d 3 Menu Penyusun</h5>
                    <span class="badge bg-secondary" id="selectedCountBadge">0 Menu Dipilih</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">Pilih</th>
                                    <th>Menu Minuman / Topping</th>
                                    <th>Harga Normal</th>
                                    <th style="width: 100px;">Jumlah (Qty)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                <?php 
                                    $isSelected = isset($existingItems[$p['id']]);
                                    $qtyVal = $existingItems[$p['id']] ?? 1;
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input item-check" data-id="<?= $p['id'] ?>" data-price="<?= $p['harga'] ?>" <?= $isSelected ? 'checked' : '' ?>>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($p['nama_produk']) ?></span>
                                    </td>
                                    <td><?= format_rupiah($p['harga']) ?></td>
                                    <td>
                                        <input type="number" name="products[<?= $p['id'] ?>]" id="qty_<?= $p['id'] ?>" class="form-control form-control-sm item-qty text-center" value="<?= $isSelected ? $qtyVal : 0 ?>" min="0" max="5">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkBoxes = document.querySelectorAll('.item-check');
    const diskonTipe = document.getElementById('diskonTipe');
    const diskonNilai = document.getElementById('diskonNilai');

    function updatePreview() {
        let totalNormal = 0;
        let selectedCount = 0;

        checkBoxes.forEach(cb => {
            const pId = cb.getAttribute('data-id');
            const price = parseFloat(cb.getAttribute('data-price')) || 0;
            const qtyInput = document.getElementById('qty_' + pId);

            if (cb.checked) {
                selectedCount++;
                if (parseInt(qtyInput.value) <= 0) {
                    qtyInput.value = 1;
                }
                totalNormal += price * parseInt(qtyInput.value);
            } else {
                qtyInput.value = 0;
            }
        });

        document.getElementById('selectedCountBadge').textContent = selectedCount + ' Menu Dipilih';

        const dVal = parseFloat(diskonNilai.value) || 0;
        let pot = 0;
        if (diskonTipe.value === 'persen') {
            pot = (totalNormal * dVal) / 100;
        } else {
            pot = dVal;
        }
        const promosi = Math.max(0, totalNormal - pot);

        document.getElementById('previewNormal').textContent = 'Rp ' + Math.round(totalNormal).toLocaleString('id-ID');
        document.getElementById('previewDiskon').textContent = '- Rp ' + Math.round(pot).toLocaleString('id-ID');
        document.getElementById('previewPromosi').textContent = 'Rp ' + Math.round(promosi).toLocaleString('id-ID');
    }

    checkBoxes.forEach(cb => {
        cb.addEventListener('change', updatePreview);
    });

    document.querySelectorAll('.item-qty').forEach(input => {
        input.addEventListener('input', function() {
            const pId = this.id.replace('qty_', '');
            const cb = document.querySelector(`.item-check[data-id="${pId}"]`);
            if (parseInt(this.value) > 0) {
                cb.checked = true;
            } else {
                cb.checked = false;
            }
            updatePreview();
        });
    });

    diskonTipe.addEventListener('change', updatePreview);
    diskonNilai.addEventListener('input', updatePreview);

    updatePreview();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


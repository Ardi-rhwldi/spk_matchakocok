<?php
// app/views/criteria/index.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-sliders text-success me-2"></i> Pengaturan Kriteria, Bobot & Periode (Modul 4)</h4>
        <p class="text-muted small mb-0">Kelola 7 kriteria keputusan ELECTRE, validasi bobot total 1,00, dan atur periode data analisis</p>
    </div>
</div>

<div class="row">
    <!-- Pengaturan Periode Analisis (FR-4.3) -->
    <div class="col-md-12 mb-4">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5><i class="bi bi-calendar-range me-2"></i> Pengaturan Periode Data Analisis (FR-4.3)</h5>
            </div>
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/?action=update_period" method="POST" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Tanggal Awal Penjualan</label>
                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Tanggal Akhir Penjualan</label>
                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-matcha w-100 py-2">
                            <i class="bi bi-calendar-check me-1"></i> Simpan Periode Analisis
                        </button>
                    </div>
                </form>
                <div class="mt-2 text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Data rujukan default PRD: <strong>10 Februari 2026 s.d 9 Juli 2026</strong> (79 hari operasional aktif, mencakup 709 transaksi).
                </div>
            </div>
        </div>
    </div>

    <!-- Kelola 7 Kriteria & Bobot (FR-4.1, FR-4.2) -->
    <div class="col-md-8">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5><i class="bi bi-list-check me-2"></i> 7 Kriteria Keputusan & Bobot Prioritas</h5>
                <span class="badge <?= abs($totalWeight - 1.0) < 0.0001 ? 'bg-success' : 'bg-danger' ?> px-3 py-2">
                    Total Bobot: <?= number_format($totalWeight, 4) ?> / 1.0000
                </span>
            </div>
            <div class="card-body p-0">
                <form action="<?= BASE_URL ?>/?action=update_weights" method="POST" id="weightsForm">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Kriteria</th>
                                    <th>Tipe Sifat</th>
                                    <th style="width: 140px;">Bobot ($w_j$)</th>
                                    <th>Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($criteria as $c): ?>
                                <tr>
                                    <td><strong class="text-success"><?= $c['kode'] ?></strong></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($c['nama']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($c['keterangan']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($c['tipe'] === 'benefit'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-arrow-up-right me-1"></i> Benefit (Makin besar makin baik)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                <i class="bi bi-arrow-down-right me-1"></i> Cost (Makin kecil makin baik)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="1" 
                                               name="weights[<?= $c['id'] ?>]" 
                                               value="<?= number_format($c['bobot'], 4, '.', '') ?>" 
                                               class="form-control form-control-sm weight-input text-end fw-bold"
                                               <?= !$isPemilik ? 'readonly' : '' ?> required>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        <span class="weight-pct"><?= number_format($c['bobot'] * 100, 1) ?></span>%
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="3" class="text-end">Total Akumulasi Bobot:</th>
                                    <th class="text-end">
                                        <span id="liveTotalWeight" class="fs-6 fw-bold <?= abs($totalWeight - 1.0) < 0.0001 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($totalWeight, 4) ?>
                                        </span>
                                    </th>
                                    <th class="text-end"><span id="liveTotalPct">100</span>%</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="p-4 border-top">
                        <?php if ($isPemilik): ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Catatan Alasan Perubahan Bobot (FR-4.5)</label>
                                <input type="text" name="catatan" class="form-control" placeholder="Contoh: Menyesuaikan prioritas margin keuntungan jelang akhir semester" required>
                            </div>
                            <button type="submit" id="btnSaveWeights" class="btn btn-matcha">
                                <i class="bi bi-save me-1"></i> Simpan Pembaharuan Bobot Kriteria
                            </button>
                        <?php else: ?>
                            <div class="alert alert-secondary py-2 small mb-0">
                                <i class="bi bi-lock-fill me-1"></i> Sesuai Bagian 5 PRD, Anda login sebagai <strong>Admin</strong>. Pengubahan bobot kriteria final hanya dapat dilakukan oleh <strong>Pemilik Usaha</strong>.
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Definisi & Sumber Data Tiap Kriteria (FR-4.4) -->
    <div class="col-md-4">
        <div class="card-matcha mb-4">
            <div class="card-header-matcha">
                <h5><i class="bi bi-book me-2"></i> Definisi & Sumber Data (FR-4.4)</h5>
            </div>
            <div class="card-body p-3 small text-muted">
                <div class="mb-3 pb-2 border-bottom">
                    <strong class="text-dark">C1 - Margin Keuntungan (0.25):</strong>
                    <div>Selisih harga promosi dengan total HPP resep menu. (Sumber: Resep & Harga Bahan).</div>
                </div>
                <div class="mb-3 pb-2 border-bottom">
                    <strong class="text-dark">C2 - Frekuensi Dibeli Bersamaan (0.20):</strong>
                    <div>Banyaknya transaksi kasir yang memuat seluruh menu penyusun paket. (Sumber: Data Kasir).</div>
                </div>
                <div class="mb-3 pb-2 border-bottom">
                    <strong class="text-dark">C3 - Volume Penjualan (0.15):</strong>
                    <div>Total cup terjual dari seluruh menu penyusun pada periode analisis. (Sumber: Data Kasir).</div>
                </div>
                <div class="mb-3 pb-2 border-bottom">
                    <strong class="text-dark">C4 - Ketersediaan Stok (0.10):</strong>
                    <div>Kapasitas paket yang dapat dibuat dari sisa stok bahan baku = $\min(\text{stok} / \text{kebutuhan})$. (Sumber: Stok Bahan).</div>
                </div>
                <div class="mb-3 pb-2 border-bottom">
                    <strong class="text-dark">C5 - Harga Bundling (0.10, Cost):</strong>
                    <div>Harga promosi paket yang ditawarkan. Makin terjangkau, makin menarik bagi konsumen.</div>
                </div>
                <div class="mb-3 pb-2 border-bottom">
                    <strong class="text-dark">C6 - Tren Penjualan (0.10):</strong>
                    <div>Persentase perubahan kuantitas penjualan paruh akhir dibanding paruh awal. (Sumber: Kasir).</div>
                </div>
                <div>
                    <strong class="text-dark">C7 - Kompleksitas Produksi (0.10, Cost):</strong>
                    <div>Jumlah bahan baku unik dalam gabungan resep paket. Makin sedikit variasi bahan, operasional makin cepat.</div>
                </div>
            </div>
        </div>

        <!-- Riwayat Perubahan Bobot (FR-4.5) -->
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5><i class="bi bi-clock-history me-2"></i> Riwayat Perubahan Bobot</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <?php if (empty($history)): ?>
                        <li class="list-group-item text-muted text-center py-3">Belum ada riwayat perubahan bobot.</li>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between mb-1">
                                <strong class="text-dark"><?= htmlspecialchars($h['user_nama']) ?></strong>
                                <span class="text-muted"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></span>
                            </div>
                            <div class="text-muted"><?= htmlspecialchars($h['catatan']) ?></div>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.weight-input');
    const liveTotal = document.getElementById('liveTotalWeight');
    const livePct = document.getElementById('liveTotalPct');
    const btnSave = document.getElementById('btnSaveWeights');

    function checkWeights() {
        let sum = 0;
        inputs.forEach(input => {
            const row = input.closest('tr');
            const val = parseFloat(input.value) || 0;
            sum += val;
            const pctSpan = row.querySelector('.weight-pct');
            if (pctSpan) {
                pctSpan.textContent = (val * 100).toFixed(1);
            }
        });

        liveTotal.textContent = sum.toFixed(4);
        livePct.textContent = (sum * 100).toFixed(1);

        if (Math.abs(sum - 1.0) < 0.0001) {
            liveTotal.className = 'fs-6 fw-bold text-success';
            if (btnSave) btnSave.disabled = false;
        } else {
            liveTotal.className = 'fs-6 fw-bold text-danger';
            if (btnSave) btnSave.disabled = true;
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', checkWeights);
    });

    checkWeights();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


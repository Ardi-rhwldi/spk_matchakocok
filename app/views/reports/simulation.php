<?php
// app/views/reports/simulation.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-success me-2"></i> Simulasi Proyeksi Laba Bundling (FR-6.5)</h4>
        <p class="text-muted small mb-0">Hitung estimasi omzet, total HPP, dan laba bersih per bulan jika setiap paket promosi terjual $N$ unit</p>
    </div>
    <a href="<?= BASE_URL ?>/?page=reports" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Laporan
    </a>
</div>

<!-- Input Parameter Target Unit per Bulan -->
<div class="card-matcha mb-4">
    <div class="card-body p-4">
        <form method="GET" action="<?= BASE_URL ?>/" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="simulation">
            <input type="hidden" name="run_id" value="<?= $runId ?>">
            <div class="col-md-6">
                <label class="form-label fw-bold">Target Penjualan Paket per Bulan ($N$ Unit)</label>
                <div class="input-group">
                    <input type="number" name="units" class="form-control form-control-lg" value="<?= $unitsPerMonth ?>" min="1" step="10" required>
                    <span class="input-group-text bg-light">Paket / Bulan</span>
                </div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-matcha btn-lg w-100">
                    <i class="bi bi-calculator me-1"></i> Hitung Simulasi
                </button>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/?page=simulation&run_id=<?= $runId ?>&units=50" class="btn btn-sm btn-outline-secondary w-100">50 Unit</a>
                    <a href="<?= BASE_URL ?>/?page=simulation&run_id=<?= $runId ?>&units=100" class="btn btn-sm btn-outline-secondary w-100">100 Unit</a>
                    <a href="<?= BASE_URL ?>/?page=simulation&run_id=<?= $runId ?>&units=200" class="btn btn-sm btn-outline-secondary w-100">200 Unit</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Hasil Simulasi Proyeksi -->
<div class="card-matcha">
    <div class="card-header-matcha">
        <h5><i class="bi bi-table me-2"></i> Hasil Proyeksi Finansial Bulanan untuk Target <?= number_format($unitsPerMonth, 0, ',', '.') ?> Paket</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th class="text-center">Peringkat SPK</th>
                        <th>Kode</th>
                        <th>Nama Paket Bundling</th>
                        <th>Harga Promosi (C5)</th>
                        <th>Margin / Unit</th>
                        <th>Estimasi Omzet / Bulan</th>
                        <th>Estimasi Total HPP</th>
                        <th>Estimasi Laba Bersih / Bulan</th>
                        <th>Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($simulations as $s): ?>
                    <tr class="<?= $s['peringkat'] === 1 ? 'table-warning' : '' ?>">
                        <td class="text-center">
                            <?php if ($s['peringkat'] === 1): ?>
                                <span class="badge badge-rank-1 fs-6">Rank 1</span>
                            <?php elseif ($s['peringkat'] === 2): ?>
                                <span class="badge badge-rank-2 fs-6">Rank 2</span>
                            <?php elseif ($s['peringkat'] === 3): ?>
                                <span class="badge badge-rank-3 fs-6">Rank 3</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border">#<?= $s['peringkat'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= $s['kode_paket'] ?></code></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($s['nama_paket']) ?></td>
                        <td><?= format_rupiah($s['harga_promosi']) ?></td>
                        <td class="text-primary fw-semibold"><?= format_rupiah($s['margin_awal']) ?></td>
                        <td class="fw-semibold"><?= format_rupiah($s['proyeksi_omzet']) ?></td>
                        <td class="text-muted"><?= format_rupiah($s['proyeksi_hpp']) ?></td>
                        <td class="fw-bold text-success fs-6"><?= format_rupiah($s['proyeksi_laba']) ?></td>
                        <td><span class="badge bg-success-subtle text-success"><?= number_format($s['proyeksi_margin_pct'], 1) ?>%</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


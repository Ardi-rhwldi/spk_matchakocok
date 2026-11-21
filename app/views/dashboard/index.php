<?php
// app/views/dashboard/index.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Sistem Pendukung Keputusan Pemilihan Paket Promosi Bundling &bull; Metode ELECTRE</p>
    </div>
    <a href="<?= BASE_URL ?>/?page=electre" class="btn btn-matcha">
        <i class="bi bi-play-fill me-1"></i> Jalankan Analisis
    </a>
</div>

<!-- Peringatan Stok (FR-7.4) -->
<?php if (!empty($lowStocks)): ?>
    <div class="alert alert-warning mb-4" role="alert" style="align-items:flex-start;flex-direction:column;">
        <div class="d-flex align-items-center gap-2 mb-2" style="width:100%;">
            <i class="bi bi-exclamation-octagon-fill" style="color:#9a7628;font-size:15px;"></i>
            <strong style="font-size:0.84rem;">Peringatan Stok Bahan Baku Menipis</strong>
        </div>
        <div class="row" style="width:100%;margin:0;gap:0;">
            <?php foreach ($lowStocks as $ls): ?>
                <div class="col-md-4" style="padding:4px 8px 4px 0;">
                    <div style="background:#fff;border:1px solid #f5e0a0;border-radius:var(--radius);padding:8px 12px;font-size:0.8rem;display:flex;justify-content:space-between;">
                        <span><?= htmlspecialchars($ls['nama_bahan']) ?></span>
                        <span style="color:#9a7628;font-weight:600;">
                            <?= number_format($ls['stok'], 1, ',', '.') ?> <?= htmlspecialchars($ls['satuan']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="row" style="margin-bottom:16px;">
    <div class="col-md-3" style="margin-bottom:12px;">
        <div class="stat-card">
            <div class="stat-icon stat-green"><i class="bi bi-cup-hot"></i></div>
            <div>
                <div class="stat-value"><?= $totalProducts ?></div>
                <div class="stat-label">Menu &amp; Topping Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-md-3" style="margin-bottom:12px;">
        <div class="stat-card">
            <div class="stat-icon stat-blue"><i class="bi bi-collection"></i></div>
            <div>
                <div class="stat-value"><?= $totalBundles ?></div>
                <div class="stat-label">Kandidat Paket SPK</div>
            </div>
        </div>
    </div>
    <div class="col-md-3" style="margin-bottom:12px;">
        <div class="stat-card">
            <div class="stat-icon stat-gold"><i class="bi bi-receipt"></i></div>
            <div>
                <div class="stat-value"><?= number_format($totalTrx, 0, ',', '.') ?></div>
                <div class="stat-label">Transaksi Diimpor</div>
            </div>
        </div>
    </div>
    <div class="col-md-3" style="margin-bottom:12px;">
        <div class="stat-card">
            <div class="stat-icon stat-coral"><i class="bi bi-cash"></i></div>
            <div>
                <div class="stat-value"><?= format_rupiah($totalOmzet) ?></div>
                <div class="stat-label">Total Omzet</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Rekomendasi Paket (FR-7.1) -->
    <div class="col-md-7" style="margin-bottom:16px;">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-award me-2" style="color:var(--sage-primary);"></i>Rekomendasi Paket Terbaru</h5>
                <?php if ($latestRun): ?>
                    <span class="badge badge-matcha">Analisis #<?= $latestRun['id'] ?> &bull; <?= date('d M Y', strtotime($latestRun['tanggal_analisis'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($topBundles)): ?>
                    <div style="text-align:center;padding:32px 0;color:var(--text-muted);">
                        <i class="bi bi-bar-chart" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                        <div style="font-size:0.84rem;">Belum ada riwayat analisis ELECTRE.</div>
                        <a href="<?= BASE_URL ?>/?page=electre" class="btn btn-sm btn-matcha" style="margin-top:12px;">Jalankan Analisis</a>
                    </div>
                <?php else: ?>
                    <!-- Rank #1 -->
                    <div style="background:var(--sage-subtle);border:1px solid var(--sage-light);border-radius:var(--radius-lg);padding:14px 16px;margin-bottom:12px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                            <span class="badge badge-rank-1">#1 Terbaik</span>
                            <span style="font-weight:700;font-size:0.95rem;color:var(--sage-dark);"><?= format_rupiah($topBundles[0]['harga_promosi']) ?></span>
                        </div>
                        <div style="font-size:0.9rem;font-weight:600;color:var(--text-primary);margin-bottom:4px;">
                            <?= htmlspecialchars($topBundles[0]['nama_paket']) ?>
                            <span style="font-weight:400;color:var(--text-muted);font-size:0.8rem;">(<?= $topBundles[0]['kode_paket'] ?>)</span>
                        </div>
                        <div style="font-size:0.78rem;color:var(--text-secondary);">
                            <?= htmlspecialchars($topBundles[0]['status_dominasi']) ?> &bull;
                            Skor Bersih: <strong><?= ($topBundles[0]['net_score'] > 0 ? '+' : '') . $topBundles[0]['net_score'] ?></strong>
                        </div>
                        <?php if (!empty($topBundles[0]['alasan_rekomendasi'])): ?>
                        <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--sage-light);font-size:0.78rem;color:var(--text-muted);">
                            <?= htmlspecialchars($topBundles[0]['alasan_rekomendasi']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Rank #2, #3 -->
                    <?php if (count($topBundles) > 1): ?>
                    <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Alternatif Lainnya</div>
                    <?php for ($i = 1; $i < count($topBundles); $i++): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:6px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span class="badge <?= $i === 1 ? 'badge-rank-2' : 'badge-rank-3' ?>">#<?= $i + 1 ?></span>
                            <div>
                                <div style="font-size:0.84rem;font-weight:500;"><?= htmlspecialchars($topBundles[$i]['nama_paket']) ?></div>
                                <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($topBundles[$i]['status_dominasi']) ?></div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:0.84rem;font-weight:600;color:var(--sage-dark);"><?= format_rupiah($topBundles[$i]['harga_promosi']) ?></div>
                            <div style="font-size:0.75rem;color:var(--text-muted);">Margin: <?= format_rupiah($topBundles[$i]['margin_awal']) ?></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                    <?php endif; ?>

                    <div style="text-align:right;margin-top:10px;">
                        <a href="<?= BASE_URL ?>/?page=reports&run_id=<?= $latestRun['id'] ?>" class="btn btn-sm btn-matcha-outline">
                            Lihat Laporan Lengkap <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bobot Kriteria (FR-7.2) -->
    <div class="col-md-5" style="margin-bottom:16px;">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-sliders me-2" style="color:var(--sage-primary);"></i>Bobot Kriteria</h5>
                <a href="<?= BASE_URL ?>/?page=criteria" class="btn btn-sm btn-outline-secondary">Edit</a>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Kriteria</th>
                                <th>Sifat</th>
                                <th style="text-align:right;">Bobot</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($criteria as $c): ?>
                            <tr>
                                <td><strong><?= $c['kode'] ?></strong></td>
                                <td style="font-size:0.82rem;"><?= htmlspecialchars($c['nama']) ?></td>
                                <td>
                                    <span class="badge <?= $c['tipe'] === 'benefit' ? 'badge-matcha' : 'bg-warning-subtle' ?>">
                                        <?= ucfirst($c['tipe']) ?>
                                    </span>
                                </td>
                                <td style="text-align:right;font-weight:600;"><?= number_format($c['bobot'] * 100, 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Omzet & Produk (FR-7.3) -->
<div class="row">
    <div class="col-md-7" style="margin-bottom:16px;">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-graph-up me-2" style="color:var(--sage-primary);"></i>Tren Omzet Bulanan</h5>
            </div>
            <div class="card-body">
                <canvas id="chartOmzet" height="130"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-5" style="margin-bottom:16px;">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-cup-straw me-2" style="color:var(--sage-primary);"></i>Menu Terlaris</h5>
            </div>
            <div class="card-body">
                <canvas id="chartTopProducts" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart defaults for clean look
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#9a9a98';

    // Omzet Chart
    const ctxOmzet = document.getElementById('chartOmzet');
    if (ctxOmzet) {
        new Chart(ctxOmzet.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($monthLabels) ?>,
                datasets: [{
                    label: 'Omzet (Rp)',
                    data: <?= json_encode($omzetData) ?>,
                    borderColor: '#6b8f6d',
                    backgroundColor: 'rgba(107, 143, 109, 0.08)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 1.5,
                    pointBackgroundColor: '#6b8f6d',
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '#f0f0ef' },
                        ticks: { callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'M' }
                    }
                }
            }
        });
    }

    // Top Products Bar Chart
    const ctxProd = document.getElementById('chartTopProducts');
    if (ctxProd) {
        new Chart(ctxProd.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($topProducts, 'nama_produk')) ?>,
                datasets: [{
                    label: 'Cup Terjual',
                    data: <?= json_encode(array_map('intval', array_column($topProducts, 'total_qty'))) ?>,
                    backgroundColor: ['#6b8f6d','#8aab8c','#a8c4a9','#c5dcc6','#dfeede'],
                    borderRadius: 4,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, border: { display: false }, grid: { color: '#f0f0ef' } },
                    y: { grid: { display: false }, border: { display: false } }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

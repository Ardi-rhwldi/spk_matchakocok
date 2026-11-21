<?php
// app/views/reports/index.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-trophy-fill text-success me-2"></i> Laporan Hasil Keputusan & Rekomendasi (Modul 6)</h4>
        <p class="text-muted small mb-0">Rekomendasi paket promosi bundling terbaik berdasarkan pemeringkatan metode ELECTRE</p>
    </div>
    <div class="d-flex gap-2">
        <!-- Switcher Riwayat Analisis -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-clock-history me-1"></i> Pilih Sesi Analisis
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <?php foreach ($allRuns as $ar): ?>
                    <li>
                        <a class="dropdown-item <?= $ar['id'] == $run['id'] ? 'active bg-success' : '' ?>" href="<?= BASE_URL ?>/?page=reports&run_id=<?= $ar['id'] ?>">
                            #<?= $ar['id'] ?> - <?= htmlspecialchars($ar['nama_analisis']) ?>
                            <small class="d-block <?= $ar['id'] == $run['id'] ? 'text-white-50' : 'text-muted' ?>"><?= date('d/m/Y H:i', strtotime($ar['tanggal_analisis'])) ?></small>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <a href="<?= BASE_URL ?>/?page=electre_steps&run_id=<?= $run['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-diagram-3 me-1"></i> Tahapan Hitung Ringkas (FR-6.2)
        </a>
        <a href="<?= BASE_URL ?>/?action=export_excel&run_id=<?= $run['id'] ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Ekspor Excel (CSV)
        </a>
        <button onclick="window.print()" class="btn btn-matcha">
            <i class="bi bi-printer-fill me-1"></i> Cetak Laporan (PDF)
        </button>
    </div>
</div>

<!-- Header Khusus Cetak Dokumen Resmi -->
<div class="print-header d-none">
    <div class="text-center pb-3">
        <h3 class="fw-bold mb-1 text-uppercase">MATCHA KOCOK CIDAHU</h3>
        <p class="mb-1">Sistem Pendukung Keputusan Pemilihan Paket Promosi Bundling Menu</p>
        <p class="text-muted small mb-0">Kecamatan Cidahu, Kabupaten Sukabumi, Jawa Barat &bull; STIKOM El Rahma Bogor</p>
    </div>
    <hr style="border-top: 2px solid #000;">
</div>

<!-- Rekomendasi Utama (Alternatif Terpilih - FR-6.3) -->
<?php if ($topRecommendation): ?>
<div class="card-matcha border-success mb-4" style="background: linear-gradient(135deg, #ffffff 0%, #f4faf2 100%);">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="font-size: 48px;">🏆</div>
                <div>
                    <span class="badge badge-rank-1 fs-6 px-3 py-1 mb-1">REKOMENDASI UTAMA TERPILIH (RANK #1)</span>
                    <h3 class="fw-bold mb-1" style="color: var(--matcha-dark);">
                        <?= htmlspecialchars($topRecommendation['nama_paket']) ?> (<?= $topRecommendation['kode_paket'] ?>)
                    </h3>
                    <div class="text-muted small">
                        Status Dominasi: <strong class="text-success"><?= htmlspecialchars($topRecommendation['status_dominasi']) ?></strong> &bull; 
                        Skor Bersih: <span class="badge bg-success fs-6"><?= ($topRecommendation['net_score'] > 0 ? '+' : '') . $topRecommendation['net_score'] ?></span>
                        (Mendominasi <?= $topRecommendation['outranking_count'] ?> paket lain, Didominasi <?= $topRecommendation['dominated_count'] ?> paket)
                    </div>
                </div>
            </div>
            <div class="text-end">
                <div class="small text-muted">Harga Promosi Bundling:</div>
                <div class="fs-4 fw-bold text-success"><?= format_rupiah($topRecommendation['harga_promosi']) ?></div>
                <div class="small text-primary">Margin Keuntungan: <strong><?= format_rupiah($topRecommendation['margin_awal']) ?></strong></div>
            </div>
        </div>

        <div class="mt-3 pt-3 border-top">
            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Alasan Rekomendasi Sistem (FR-6.3):</h6>
            <p class="mb-0 text-muted">
                <?= htmlspecialchars($topRecommendation['alasan_rekomendasi']) ?>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabel Peringkat Lengkap (FR-6.1) -->
<div class="card-matcha mb-4">
    <div class="card-header-matcha">
        <h5><i class="bi bi-list-ol me-2"></i> Tabel Peringkat Seluruh Alternatif Paket (FR-6.1)</h5>
        <span class="badge bg-light text-dark border">
            Total <?= count($results) ?> Alternatif Dianalisis
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">Peringkat</th>
                        <th>Kode</th>
                        <th>Nama Paket Bundling</th>
                        <th>Harga Normal</th>
                        <th>Harga Promosi (C5)</th>
                        <th>HPP Paket</th>
                        <th>Margin Paket (C1)</th>
                        <th class="text-center">Mendominasi</th>
                        <th class="text-center">Didominasi</th>
                        <th class="text-center">Skor Bersih</th>
                        <th>Status Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr class="<?= $r['peringkat'] === 1 ? 'table-warning' : '' ?>">
                        <td class="text-center">
                            <?php if ($r['peringkat'] === 1): ?>
                                <span class="badge badge-rank-1 fs-6">Rank 1</span>
                            <?php elseif ($r['peringkat'] === 2): ?>
                                <span class="badge badge-rank-2 fs-6">Rank 2</span>
                            <?php elseif ($r['peringkat'] === 3): ?>
                                <span class="badge badge-rank-3 fs-6">Rank 3</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border">#<?= $r['peringkat'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= $r['kode_paket'] ?></code></td>
                        <td class="fw-bold text-dark">
                            <?= htmlspecialchars($r['nama_paket']) ?>
                            <div class="small text-muted fw-normal"><?= htmlspecialchars($r['alasan_rekomendasi']) ?></div>
                        </td>
                        <td class="text-muted"><?= format_rupiah($r['harga_normal']) ?></td>
                        <td class="fw-bold text-success"><?= format_rupiah($r['harga_promosi']) ?></td>
                        <td class="text-muted"><?= format_rupiah($r['hpp_total']) ?></td>
                        <td class="fw-bold text-primary"><?= format_rupiah($r['margin_awal']) ?></td>
                        <td class="text-center text-success fw-bold">+<?= $r['outranking_count'] ?></td>
                        <td class="text-center text-danger fw-bold">-<?= $r['dominated_count'] ?></td>
                        <td class="text-center fw-bold fs-6 <?= $r['net_score'] > 0 ? 'text-success' : ($r['net_score'] < 0 ? 'text-danger' : 'text-secondary') ?>">
                            <?= ($r['net_score'] > 0 ? '+' : '') . $r['net_score'] ?>
                        </td>
                        <td>
                            <span class="badge <?= $r['peringkat'] <= 3 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' ?>">
                                <?= htmlspecialchars($r['status_dominasi']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Lembar Tanda Tangan Khusus Cetak -->
<div class="d-none print-header mt-5 pt-4">
    <div class="row">
        <div class="col-6 text-center">
            <p class="mb-5">Mengetahui,<br><strong>Admin Pengelola Data</strong></p>
            <p class="mt-5 mb-0"><u>( .................................................. )</u></p>
        </div>
        <div class="col-6 text-center">
            <p class="mb-5">Cidahu, <?= date('d F Y') ?><br><strong>Pemilik Matcha Kocok Cidahu</strong></p>
            <p class="mt-5 mb-0"><u>( <?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Ardi Rihwaldi') ?> )</u></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


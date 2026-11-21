<?php
// app/views/electre/index.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calculator-fill text-success me-2"></i> Perhitungan Metode ELECTRE (Modul 5)</h4>
        <p class="text-muted small mb-0">Susun matriks keputusan dari data riil, lakukan verifikasi, dan jalankan algoritma outranking ELECTRE</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/?page=criteria" class="btn btn-outline-secondary">
            <i class="bi bi-sliders me-1"></i> Sesuaikan Bobot Kriteria
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger py-3" role="alert">
        <h6 class="fw-bold mb-2"><i class="bi bi-x-circle-fill me-1"></i> Prasyarat Perhitungan Tidak Terpenuhi (FR-5.6):</h6>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/?action=run_electre" method="POST">
    <input type="hidden" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
    <input type="hidden" name="end_date" value="<?= htmlspecialchars($endDate) ?>">

    <!-- Konfigurasi Analisis -->
    <div class="card-matcha mb-4">
        <div class="card-header-matcha">
            <h5><i class="bi bi-gear-fill me-2"></i> Parameter Sesi Analisis</h5>
            <span class="badge bg-success-subtle text-success border border-success-subtle">
                Periode: <?= date('d M Y', strtotime($startDate)) ?> s.d <?= date('d M Y', strtotime($endDate)) ?>
            </span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Judul / Nama Sesi Analisis</label>
                    <input type="text" name="nama_analisis" class="form-control" value="Analisis Rekomendasi Bundling Promosi (<?= date('d M Y') ?>)" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Catatan Pengambil Keputusan (Opsional)</label>
                    <input type="text" name="catatan_analisis" class="form-control" placeholder="Contoh: Penilaian kandidat paket untuk promo semester genap 2026">
                </div>
            </div>
        </div>
    </div>

    <!-- Matriks Keputusan Otomatis (X) -->
    <div class="card-matcha mb-4">
        <div class="card-header-matcha">
            <div>
                <h5><i class="bi bi-table me-2"></i> 1. Matriks Keputusan ($X$) Otomatis dari Data Kasir & Resep (FR-5.1)</h5>
                <small class="text-muted">Nilai dihitung otomatis dan dapat dikoreksi secara manual jika diperlukan (FR-5.2)</small>
            </div>
            <span class="badge bg-light text-dark border">
                <?= count($alternatives) ?> Alternatif &times; <?= count($criteria) ?> Kriteria
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Paket Kandidat</th>
                            <?php foreach ($criteria as $cCode => $crit): ?>
                                <th class="text-center">
                                    <div><?= $crit['name'] ?></div>
                                    <div class="small fw-normal text-muted">
                                        <?= $cCode ?> (<?= ucfirst($crit['type']) ?>, <?= number_format($crit['weight'], 2) ?>)
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alternatives as $bId => $alt): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= $alt['code'] ?></span></td>
                            <td class="fw-bold text-dark">
                                <?= htmlspecialchars($alt['name']) ?>
                                <div class="small text-muted fw-normal">Harga Promosi: <?= format_rupiah($alt['harga_promosi']) ?></div>
                            </td>
                            <?php foreach ($criteria as $cCode => $crit): ?>
                                <?php $val = $matrixX[$bId][$cCode] ?? 0; ?>
                                <td style="width: 130px;">
                                    <input type="number" step="0.0001" 
                                           name="matrix_manual[<?= $bId ?>][<?= $cCode ?>]" 
                                           value="<?= number_format($val, 4, '.', '') ?>" 
                                           class="form-control form-control-sm text-end"
                                           title="Nilai default dari formula: <?= $val ?>">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                <i class="bi bi-info-circle me-1"></i> Tampilan nilai dibulatkan ke 4 desimal sesuai PRD Bagian 8, sementara perhitungan internal tetap menggunakan presisi penuh (floating-point).
            </div>
            <button type="submit" class="btn btn-matcha px-4 py-2" <?= !empty($errors) ? 'disabled' : '' ?>>
                <i class="bi bi-play-fill fs-5 me-1"></i> Mulai Hitung ELECTRE Lengkap
            </button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


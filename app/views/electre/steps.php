<?php
// app/views/electre/steps.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';

$altIds = array_keys($altMap);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-diagram-3-fill text-success me-2"></i> Tahapan Perhitungan Metode ELECTRE (FR-5.4)</h4>
        <p class="text-muted small mb-0">Rincian matematis lengkap per matriks untuk verifikasi akurasi hasil (ID Analisis: #<?= $run['id'] ?>)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/?page=reports&run_id=<?= $run['id'] ?>" class="btn btn-matcha">
            <i class="bi bi-file-earmark-text-fill me-1"></i> Lihat Laporan Akhir Keputusan
        </a>
        <a href="<?= BASE_URL ?>/?page=electre" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-repeat me-1"></i> Hitung Ulang
        </a>
    </div>
</div>

<!-- Informasi Sesi Analisis -->
<div class="card-matcha mb-4">
    <div class="card-body p-3">
        <div class="row align-items-center">
            <div class="col-md-3">
                <small class="text-muted d-block">Judul Analisis:</small>
                <strong><?= htmlspecialchars($run['nama_analisis']) ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Tanggal Analisis:</small>
                <strong><?= date('d/m/Y H:i', strtotime($run['tanggal_analisis'])) ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Ambang Batas Concordance (c̄):</small>
                <strong class="text-primary fs-6"><?= number_format($run['threshold_c'], 4) ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Ambang Batas Discordance (d̄):</small>
                <strong class="text-danger fs-6"><?= number_format($run['threshold_d'], 4) ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Tab Navigasi Tahapan Matriks -->
<ul class="nav nav-pills mb-4 gap-2" id="electreTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active rounded-pill btn-sm" data-bs-toggle="pill" data-bs-target="#tabX">1. Matriks Keputusan ($X$)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill btn-sm" data-bs-toggle="pill" data-bs-target="#tabR">2. Normalisasi ($R$)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill btn-sm" data-bs-toggle="pill" data-bs-target="#tabV">3. Pembobotan ($V$)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill btn-sm" data-bs-toggle="pill" data-bs-target="#tabC">4. Concordance ($C$)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill btn-sm" data-bs-toggle="pill" data-bs-target="#tabD">5. Discordance ($D$)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill btn-sm" data-bs-toggle="pill" data-bs-target="#tabFG">6. Matriks Dominan ($F$ & $G$)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill btn-sm" data-bs-toggle="pill" data-bs-target="#tabE">7. Matriks Agregat ($E$)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill btn-sm bg-success text-white" data-bs-toggle="pill" data-bs-target="#tabRank">8. Hasil Peringkat</button>
    </li>
</ul>

<div class="tab-content">
    <!-- TAB 1: MATRIKS X -->
    <div class="tab-pane fade show active" id="tabX">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5>1. Matriks Keputusan ($X$)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-custom table-matrix">
                        <thead>
                            <tr>
                                <th>Alternatif</th>
                                <?php foreach ($critMap as $cCode => $crit): ?>
                                    <th><?= $cCode ?><br><small class="text-muted fw-normal">(<?= ucfirst($crit['tipe']) ?>)</small></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($altMap as $bId => $alt): ?>
                            <tr>
                                <td class="fw-bold text-start ps-3"><?= $alt['code'] ?> - <?= htmlspecialchars($alt['name']) ?></td>
                                <?php foreach ($critMap as $cCode => $crit): ?>
                                    <td><?= number_format($matrices['X'][$bId][$cCode] ?? 0, 4) ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: MATRIKS R -->
    <div class="tab-pane fade" id="tabR">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5>2. Matriks Normalisasi ($R$): $r_{ij} = \frac{x_{ij}}{\sqrt{\sum_k x_{kj}^2}}$</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-custom table-matrix">
                        <thead>
                            <tr>
                                <th>Alternatif</th>
                                <?php foreach ($critMap as $cCode => $crit): ?>
                                    <th><?= $cCode ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($altMap as $bId => $alt): ?>
                            <tr>
                                <td class="fw-bold text-start ps-3"><?= $alt['code'] ?> - <?= htmlspecialchars($alt['name']) ?></td>
                                <?php foreach ($critMap as $cCode => $crit): ?>
                                    <td><?= number_format($matrices['R'][$bId][$cCode] ?? 0, 4) ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: MATRIKS V -->
    <div class="tab-pane fade" id="tabV">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5>3. Matriks Normalisasi Terbobot ($V$): $v_{ij} = w_j \times r_{ij}$</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-custom table-matrix">
                        <thead>
                            <tr>
                                <th>Alternatif</th>
                                <?php foreach ($critMap as $cCode => $crit): ?>
                                    <th><?= $cCode ?><br><small class="text-muted fw-normal">(w=<?= number_format($crit['bobot'], 2) ?>)</small></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($altMap as $bId => $alt): ?>
                            <tr>
                                <td class="fw-bold text-start ps-3"><?= $alt['code'] ?> - <?= htmlspecialchars($alt['name']) ?></td>
                                <?php foreach ($critMap as $cCode => $crit): ?>
                                    <td class="fw-semibold"><?= number_format($matrices['V'][$bId][$cCode] ?? 0, 4) ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 4: MATRIKS C -->
    <div class="tab-pane fade" id="tabC">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5>4. Matriks Indeks Concordance ($C$): $c_{kl} = \sum_{j \in C_{kl}} w_j$</h5>
                <span class="badge bg-primary fs-6">Ambang Batas c̄ = <?= number_format($run['threshold_c'], 4) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-custom table-matrix">
                        <thead>
                            <tr>
                                <th>k \ l</th>
                                <?php foreach ($altMap as $bId => $alt): ?>
                                    <th><?= $alt['code'] ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($altMap as $k => $altK): ?>
                            <tr>
                                <td class="fw-bold text-start ps-3"><?= $altK['code'] ?></td>
                                <?php foreach ($altMap as $l => $altL): ?>
                                    <?php 
                                        $val = $matrices['C'][$k][$l] ?? null;
                                        $isDiag = ($k === $l);
                                        $aboveThresh = (!$isDiag && $val !== null && $val >= $run['threshold_c']);
                                    ?>
                                    <td class="<?= $isDiag ? 'highlight-diag' : ($aboveThresh ? 'cell-one' : '') ?>">
                                        <?= $isDiag ? '-' : number_format((float)$val, 4) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 5: MATRIKS D -->
    <div class="tab-pane fade" id="tabD">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5>5. Matriks Indeks Discordance ($D$): $d_{kl} = \frac{\max_{j \in D_{kl}} |v_{kj} - v_{lj}|}{\max_j |v_{kj} - v_{lj}|}$</h5>
                <span class="badge bg-danger fs-6">Ambang Batas d̄ = <?= number_format($run['threshold_d'], 4) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-custom table-matrix">
                        <thead>
                            <tr>
                                <th>k \ l</th>
                                <?php foreach ($altMap as $bId => $alt): ?>
                                    <th><?= $alt['code'] ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($altMap as $k => $altK): ?>
                            <tr>
                                <td class="fw-bold text-start ps-3"><?= $altK['code'] ?></td>
                                <?php foreach ($altMap as $l => $altL): ?>
                                    <?php 
                                        $val = $matrices['D'][$k][$l] ?? null;
                                        $isDiag = ($k === $l);
                                        $belowThresh = (!$isDiag && $val !== null && $val <= $run['threshold_d']);
                                    ?>
                                    <td class="<?= $isDiag ? 'highlight-diag' : ($belowThresh ? 'cell-one' : '') ?>">
                                        <?= $isDiag ? '-' : number_format((float)$val, 4) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 6: MATRIKS DOMINAN F & G -->
    <div class="tab-pane fade" id="tabFG">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card-matcha">
                    <div class="card-header-matcha">
                        <h5>Matriks Dominan Concordance ($F$): $c_{kl} \ge \bar{c}$</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table-custom table-matrix">
                                <thead>
                                    <tr>
                                        <th>k \ l</th>
                                        <?php foreach ($altMap as $bId => $alt): ?>
                                            <th><?= $alt['code'] ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($altMap as $k => $altK): ?>
                                    <tr>
                                        <td class="fw-bold text-start ps-3"><?= $altK['code'] ?></td>
                                        <?php foreach ($altMap as $l => $altL): ?>
                                            <?php 
                                                $val = $matrices['F'][$k][$l] ?? '-';
                                                $isOne = ($val === 1 || $val === '1');
                                            ?>
                                            <td class="<?= $k === $l ? 'highlight-diag' : ($isOne ? 'cell-one' : 'cell-zero') ?>">
                                                <?= $val ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card-matcha">
                    <div class="card-header-matcha">
                        <h5>Matriks Dominan Discordance ($G$): $d_{kl} \le \bar{d}$</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table-custom table-matrix">
                                <thead>
                                    <tr>
                                        <th>k \ l</th>
                                        <?php foreach ($altMap as $bId => $alt): ?>
                                            <th><?= $alt['code'] ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($altMap as $k => $altK): ?>
                                    <tr>
                                        <td class="fw-bold text-start ps-3"><?= $altK['code'] ?></td>
                                        <?php foreach ($altMap as $l => $altL): ?>
                                            <?php 
                                                $val = $matrices['G'][$k][$l] ?? '-';
                                                $isOne = ($val === 1 || $val === '1');
                                            ?>
                                            <td class="<?= $k === $l ? 'highlight-diag' : ($isOne ? 'cell-one' : 'cell-zero') ?>">
                                                <?= $val ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 7: MATRIKS AGREGAT DOMINAN E -->
    <div class="tab-pane fade" id="tabE">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5>7. Matriks Agregat Dominan ($E$): $E_{kl} = F_{kl} \times G_{kl}$</h5>
                <span class="badge bg-success fs-6">Nilai 1 = Alternatif $k$ Mendominasi Alternatif $l$</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-custom table-matrix">
                        <thead>
                            <tr>
                                <th>Alternatif $k$ \ $l$</th>
                                <?php foreach ($altMap as $bId => $alt): ?>
                                    <th><?= $alt['code'] ?></th>
                                <?php endforeach; ?>
                                <th class="table-light">Jumlah Baris (Outrank)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($altMap as $k => $altK): ?>
                            <tr>
                                <td class="fw-bold text-start ps-3"><?= $altK['code'] ?></td>
                                <?php 
                                    $rowSum = 0;
                                    foreach ($altMap as $l => $altL): 
                                        $val = $matrices['E'][$k][$l] ?? '-';
                                        $isOne = ($val === 1 || $val === '1');
                                        if ($isOne) $rowSum++;
                                ?>
                                    <td class="<?= $k === $l ? 'highlight-diag' : ($isOne ? 'cell-one' : 'cell-zero') ?>">
                                        <?= $val ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="fw-bold table-light text-center text-success"><?= $rowSum ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 8: HASIL PERINGKAT & SKOR BERSIH -->
    <div class="tab-pane fade" id="tabRank">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5>8. Seleksi dan Peringkat Akhir Berdasarkan Skor Bersih (Net Score)</h5>
                <a href="<?= BASE_URL ?>/?page=reports&run_id=<?= $run['id'] ?>" class="btn btn-sm btn-matcha">
                    <i class="bi bi-file-earmark-text-fill me-1"></i> Buka Laporan Keputusan Lengkap
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th class="text-center">Peringkat</th>
                                <th>Kode</th>
                                <th>Nama Paket Bundling</th>
                                <th>Harga Promosi (C5)</th>
                                <th>Margin Awal (C1)</th>
                                <th class="text-center">Mendominasi (Outranking)</th>
                                <th class="text-center">Didominasi (Dominated)</th>
                                <th class="text-center">Skor Bersih (Net Score)</th>
                                <th>Status Dominasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $res): ?>
                            <tr class="<?= $res['peringkat'] === 1 ? 'table-warning' : '' ?>">
                                <td class="text-center">
                                    <?php if ($res['peringkat'] === 1): ?>
                                        <span class="badge badge-rank-1 fs-6 px-3">Rank #1</span>
                                    <?php elseif ($res['peringkat'] === 2): ?>
                                        <span class="badge badge-rank-2 fs-6 px-3">Rank #2</span>
                                    <?php elseif ($res['peringkat'] === 3): ?>
                                        <span class="badge badge-rank-3 fs-6 px-3">Rank #3</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">#<?= $res['peringkat'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= $res['kode_paket'] ?></code></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($res['nama_paket']) ?></td>
                                <td><?= format_rupiah($res['harga_promosi']) ?></td>
                                <td class="text-primary fw-semibold"><?= format_rupiah($res['margin_awal']) ?></td>
                                <td class="text-center fw-bold text-success">+<?= $res['outranking_count'] ?></td>
                                <td class="text-center fw-bold text-danger">-<?= $res['dominated_count'] ?></td>
                                <td class="text-center fs-6 fw-bold <?= $res['net_score'] > 0 ? 'text-success' : ($res['net_score'] < 0 ? 'text-danger' : 'text-secondary') ?>">
                                    <?= ($res['net_score'] > 0 ? '+' : '') . $res['net_score'] ?>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <?= htmlspecialchars($res['status_dominasi']) ?>
                                    </span>
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


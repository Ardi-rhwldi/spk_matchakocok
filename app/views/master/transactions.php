<?php
// app/views/master/transactions.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cart-check-fill text-success me-2"></i> Riwayat Transaksi Kasir</h4>
        <p class="text-muted small mb-0">Data operasional penjualan yang diimpor dari aplikasi kasir sebagai sumber analisis SPK</p>
    </div>
    <a href="<?= BASE_URL ?>/?page=import" class="btn btn-matcha">
        <i class="bi bi-file-earmark-arrow-up-fill me-1"></i> Impor Transaksi Baru
    </a>
</div>

<!-- Filter Periode (FR-2.8) -->
<div class="card-matcha mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= BASE_URL ?>/" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="transactions">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Tanggal Awal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Batasan Tampilan Baris</label>
                <select name="limit" class="form-select form-select-sm">
                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 Baris</option>
                    <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 Baris</option>
                    <option value="250" <?= $limit == 250 ? 'selected' : '' ?>>250 Baris</option>
                    <option value="1000" <?= $limit == 1000 ? 'selected' : '' ?>>1000 Baris</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-matcha w-100">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Ringkasan Periode -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon stat-green"><i class="bi bi-receipt"></i></div>
            <div>
                <div class="stat-value"><?= number_format($summary['total_trx'] ?? 0, 0, ',', '.') ?></div>
                <div class="stat-label">Total Transaksi</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon stat-blue"><i class="bi bi-cup-straw"></i></div>
            <div>
                <div class="stat-value"><?= number_format($summary['total_cups'] ?? 0, 0, ',', '.') ?></div>
                <div class="stat-label">Total Cup Terjual</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon stat-gold"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="stat-value"><?= format_rupiah($summary['total_omzet'] ?? 0) ?></div>
                <div class="stat-label">Total Omzet Penjualan</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Transaksi -->
<div class="card-matcha">
    <div class="card-header-matcha">
        <h5><i class="bi bi-table me-2"></i> Daftar Transaksi (Menampilkan maks <?= $limit ?> data terbaru)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Tanggal</th>
                        <th>Komposisi Item Penjualan (Keranjang)</th>
                        <th>Jumlah Item</th>
                        <th>Total Bayar</th>
                        <th>Metode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Tidak ada transaksi ditemukan pada rentang tanggal ini.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($t['invoice_no']) ?></code></td>
                            <td><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                            <td>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($t['menu_list'] ?? '-') ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $t['item_count'] > 1 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' ?>">
                                    <?= $t['item_count'] ?> Item
                                </span>
                            </td>
                            <td class="fw-bold"><?= format_rupiah($t['total_amount']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['tipe_bayar']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


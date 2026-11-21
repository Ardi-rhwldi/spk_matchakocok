<?php
// app/views/master/import.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';

$importErrors = $_SESSION['import_errors'] ?? [];
unset($_SESSION['import_errors']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-arrow-up-fill text-success me-2"></i> Impor Data Transaksi Penjualan</h4>
        <p class="text-muted small mb-0">Impor data transaksi kasir dari file CSV dengan validasi duplikasi dan tanggal otomatis (FR-2.5, FR-2.6)</p>
    </div>
    <a href="<?= BASE_URL ?>/?page=transactions" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Riwayat
    </a>
</div>

<?php if (!empty($importErrors)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Beberapa Baris Dilewati saat Impor:</h6>
        <ul class="mb-0 small ps-3">
            <?php foreach (array_slice($importErrors, 0, 10) as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
            <?php if (count($importErrors) > 10): ?>
                <li><em>...dan <?= count($importErrors) - 10 ?> baris lainnya.</em></li>
            <?php endif; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5><i class="bi bi-cloud-arrow-up-fill me-2"></i> Unggah Berkas Transaksi</h5>
            </div>
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/?action=process_import" method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Pilih Berkas CSV Transaksi</label>
                        <input type="file" name="file_transaksi" class="form-control" accept=".csv" required>
                        <div class="form-text small">Berkas harus berekstensi .csv dengan pemisah koma (,). Ukuran maksimal 10 MB.</div>
                    </div>

                    <div class="alert alert-info py-2 small mb-4">
                        <i class="bi bi-shield-lock-fill me-1"></i> <strong>Jaminan Privasi (Bagian 6 & 10):</strong> Data sensitif kasir seperti hash kata sandi pengguna kasir, kontak, dan nomor rekening toko tidak akan diimpor ke sistem SPK.
                    </div>

                    <button type="submit" class="btn btn-matcha py-2 px-4">
                        <i class="bi bi-upload me-1"></i> Proses & Impor Data Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5><i class="bi bi-info-circle me-2"></i> Panduan Format Kolom CSV</h5>
            </div>
            <div class="card-body p-4">
                <p class="small text-muted mb-3">Sistem otomatis mengenali dan memetakan nama kolom pada baris pertama berkas CSV Anda:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Kolom</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>invoice_no</code></td>
                                <td>Nomor unik transaksi (misal: INV-202600001)</td>
                            </tr>
                            <tr>
                                <td><code>tanggal</code></td>
                                <td>Format YYYY-MM-DD atau DD/MM/YYYY</td>
                            </tr>
                            <tr>
                                <td><code>product_id</code> / <code>produk</code></td>
                                <td>ID atau Nama Produk sesuai master menu</td>
                            </tr>
                            <tr>
                                <td><code>qty</code></td>
                                <td>Jumlah cup yang dipesan (angka bulat)</td>
                            </tr>
                            <tr>
                                <td><code>harga_satuan</code></td>
                                <td>Harga satuan menu saat transaksi</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 p-3 bg-light rounded border">
                    <h6 class="fw-bold small mb-1"><i class="bi bi-check2-circle text-success me-1"></i> Validasi Otomatis (FR-2.6):</h6>
                    <ul class="mb-0 small text-muted ps-3">
                        <li>Baris dengan invoice_no duplikat akan digabungkan ke invoice yang sama.</li>
                        <li>Format tanggal tidak valid akan ditolak dan dilaporkan.</li>
                        <li>Nama produk yang tidak terdaftar di master akan dilewati.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


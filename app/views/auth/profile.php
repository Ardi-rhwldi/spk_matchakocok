<?php
// app/views/auth/profile.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card-matcha">
            <div class="card-header-matcha">
                <h5><i class="bi bi-key-fill me-2"></i> Pengaturan Akun & Ganti Kata Sandi</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-4 p-3 bg-light rounded border">
                    <div class="row">
                        <div class="col-4 text-muted small">Username:</div>
                        <div class="col-8 fw-bold"><?= htmlspecialchars($_SESSION['user']['username']) ?></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-4 text-muted small">Nama:</div>
                        <div class="col-8 fw-bold"><?= htmlspecialchars($_SESSION['user']['nama']) ?></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-4 text-muted small">Peran:</div>
                        <div class="col-8"><span class="badge bg-success-subtle text-success"><?= ucfirst($_SESSION['user']['peran']) ?></span></div>
                    </div>
                </div>

                <form action="<?= BASE_URL ?>/?action=update_password" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kata Sandi Saat Ini</label>
                        <input type="password" name="old_password" class="form-control" required placeholder="Masukkan kata sandi lama">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kata Sandi Baru</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6" placeholder="Ulangi kata sandi baru">
                    </div>
                    <button type="submit" class="btn btn-matcha">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


<?php
// app/views/users/index.php
if (!defined('APP_NAME')) exit;
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people-fill text-success me-2"></i> Manajemen Pengguna (Modul 1)</h4>
        <p class="text-muted small mb-0">Kelola akun pemilik dan admin sistem SPK Matcha Kocok</p>
    </div>
    <button class="btn btn-matcha" data-bs-toggle="modal" data-bs-target="#modalAddUser">
        <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna Baru
    </button>
</div>

<div class="card-matcha">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Peran (Role)</th>
                        <th>Status</th>
                        <th>Tanggal Dibuat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?= $u['id'] ?></td>
                        <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                        <td class="fw-semibold"><?= htmlspecialchars($u['nama']) ?></td>
                        <td>
                            <?php if ($u['peran'] === 'pemilik'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Pemilik (Full Access)</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border">Admin (Modul 1-4)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['aktif']): ?>
                                <span class="badge bg-success-subtle text-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditUser<?= $u['id'] ?>">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                        </td>
                    </tr>

                    <!-- Modal Edit User -->
                    <div class="modal fade" id="modalEditUser<?= $u['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="<?= BASE_URL ?>/?action=update_user" method="POST">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Pengguna: <?= htmlspecialchars($u['username']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Nama Lengkap</label>
                                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($u['nama']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Peran (Role)</label>
                                            <select name="peran" class="form-select">
                                                <option value="admin" <?= $u['peran'] === 'admin' ? 'selected' : '' ?>>Admin (Pengelola Data Master)</option>
                                                <option value="pemilik" <?= $u['peran'] === 'pemilik' ? 'selected' : '' ?>>Pemilik (Pengambil Keputusan & Analisis)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Reset Kata Sandi (Kosongkan jika tidak diubah)</label>
                                            <input type="password" name="password" class="form-control" placeholder="Kata sandi baru">
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="aktif" id="aktifSwitch<?= $u['id'] ?>" <?= $u['aktif'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="aktifSwitch<?= $u['id'] ?>">Akun Aktif</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-matcha">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalAddUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/?action=store_user" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <input type="text" name="username" class="form-control" required placeholder="Contoh: kasir1 / staf">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kata Sandi Awal</label>
                        <input type="password" name="password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Peran (Role)</label>
                        <select name="peran" class="form-select">
                            <option value="admin">Admin (Pengelola Data Master)</option>
                            <option value="pemilik">Pemilik (Pengambil Keputusan & Analisis)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-matcha">Tambah Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


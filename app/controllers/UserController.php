<?php
// app/controllers/UserController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class UserController {
    private PDO $db;

    public function __construct() {
        $auth = new AuthController();
        $auth->requireAuth();
        if (($_SESSION['user']['peran'] ?? '') !== 'pemilik') {
            set_flash('error', 'Hanya Pemilik yang berhak mengelola pengguna.');
            redirect('/?page=dashboard');
        }
        $this->db = Database::getConnection();
    }

    public function index() {
        $users = $this->db->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();
        $pageTitle = 'Manajemen Pengguna - ' . APP_NAME;
        $currentRoute = 'users';
        require_once __DIR__ . '/../views/users/index.php';
    }

    public function store() {
        $username = trim($_POST['username'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $password = $_POST['password'] ?? '';
        $peran = in_array($_POST['peran'] ?? '', ['pemilik', 'admin']) ? $_POST['peran'] : 'admin';

        if (empty($username) || empty($nama) || empty($password)) {
            set_flash('error', 'Semua kolom pengguna wajib diisi.');
            redirect('/?page=users');
        }

        // Cek duplikasi username
        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmtC->execute([$username]);
        if ($stmtC->fetchColumn() > 0) {
            set_flash('error', "Username '$username' sudah digunakan.");
            redirect('/?page=users');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, nama, peran, aktif) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$username, $hash, $nama, $peran]);

        set_flash('success', "Pengguna '$nama' berhasil ditambahkan.");
        redirect('/?page=users');
    }

    public function update() {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $peran = in_array($_POST['peran'] ?? '', ['pemilik', 'admin']) ? $_POST['peran'] : 'admin';
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || empty($nama)) {
            set_flash('error', 'Data pengguna tidak valid.');
            redirect('/?page=users');
        }

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("UPDATE users SET nama = ?, peran = ?, aktif = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$nama, $peran, $aktif, $hash, $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE users SET nama = ?, peran = ?, aktif = ? WHERE id = ?");
            $stmt->execute([$nama, $peran, $aktif, $id]);
        }

        set_flash('success', "Data pengguna berhasil diperbarui.");
        redirect('/?page=users');
    }
}


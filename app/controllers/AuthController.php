<?php
// app/controllers/AuthController.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class AuthController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function showLogin() {
        if (!empty($_SESSION['user'])) {
            redirect('/?page=dashboard');
        }
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function handleLogin() {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            set_flash('error', 'Username dan password wajib diisi.');
            redirect('/?page=login');
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND aktif = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'nama' => $user['nama'],
                'peran' => $user['peran']
            ];
            $_SESSION['last_activity'] = time();
            set_flash('success', "Selamat datang kembali, " . htmlspecialchars($user['nama']) . "!");
            redirect('/?page=dashboard');
        } else {
            set_flash('error', 'Username atau kata sandi tidak cocok, atau akun dinonaktifkan.');
            redirect('/?page=login');
        }
    }

    public function handleLogout() {
        unset($_SESSION['user']);
        session_destroy();
        redirect('/?page=login');
    }

    public function showProfile() {
        $this->requireAuth();
        $user = $_SESSION['user'];
        $pageTitle = 'Ganti Kata Sandi - ' . APP_NAME;
        $currentRoute = 'profile';
        require_once __DIR__ . '/../views/auth/profile.php';
    }

    public function updatePassword() {
        $this->requireAuth();
        $oldPass = $_POST['old_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        $userId = $_SESSION['user']['id'];

        if (empty($oldPass) || empty($newPass) || empty($confirmPass)) {
            set_flash('error', 'Semua kolom kata sandi wajib diisi.');
            redirect('/?page=profile');
        }

        if ($newPass !== $confirmPass) {
            set_flash('error', 'Konfirmasi kata sandi baru tidak cocok.');
            redirect('/?page=profile');
        }

        if (strlen($newPass) < 6) {
            set_flash('error', 'Kata sandi baru minimal 6 karakter.');
            redirect('/?page=profile');
        }

        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentHash = $stmt->fetchColumn();

        if (!password_verify($oldPass, $currentHash)) {
            set_flash('error', 'Kata sandi saat ini salah.');
            redirect('/?page=profile');
        }

        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $stmtUp = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmtUp->execute([$newHash, $userId]);

        set_flash('success', 'Kata sandi Anda berhasil diperbarui.');
        redirect('/?page=profile');
    }

    public function requireAuth() {
        if (empty($_SESSION['user'])) {
            set_flash('error', 'Silakan login terlebih dahulu untuk mengakses sistem.');
            redirect('/?page=login');
        }
    }
}


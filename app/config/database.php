<?php
// app/config/database.php
require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $pdo = null;
    private static string $activeDriver = 'mysql';

    public static function getConnection(): PDO {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Coba koneksi MySQL terlebih dahulu
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            self::$activeDriver = 'mysql';
            return self::$pdo;
        } catch (PDOException $e) {
            // Jika database belum dibuat di MySQL, coba sambung ke MySQL tanpa dbname untuk auto-create
            try {
                $dsnWithoutDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $pdoInit = new PDO($dsnWithoutDb, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Sambung ulang
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                self::$activeDriver = 'mysql';
                return self::$pdo;
            } catch (PDOException $ex) {
                // Jika MySQL mati (misal diuji di CLI tanpa XAMPP service aktif), gunakan SQLite sebagai fallback cerdas
                $dir = dirname(SQLITE_FILE);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $dsn = "sqlite:" . SQLITE_FILE;
                self::$pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                // Enable foreign keys for SQLite
                self::$pdo->exec("PRAGMA foreign_keys = ON;");
                self::$activeDriver = 'sqlite';
                return self::$pdo;
            }
        }
    }

    public static function getActiveDriver(): string {
        return self::$activeDriver;
    }
}


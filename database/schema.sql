-- Skema Database SPK Bundling Matcha Kocok Cidahu
-- Menggunakan struktur tabel asli aplikasi kasir + tabel khusus SPK

CREATE TABLE IF NOT EXISTS `product_categories` (
  `category_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `min_stock` int(11) DEFAULT 5,
  `product_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `hpp_manual` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `product_categories`(`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `raw_materials` (
  `material_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `material_name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `current_stock` decimal(10,2) DEFAULT 0.00,
  `min_stock` decimal(10,2) DEFAULT 0.00,
  `price_per_unit` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recipes` (
  `recipe_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
  FOREIGN KEY (`material_id`) REFERENCES `raw_materials`(`material_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transactions` (
  `transaction_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `transaction_code` varchar(50) NOT NULL UNIQUE,
  `user_id` int(11) NOT NULL,
  `payment_method` enum('cash','qris','transfer') NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `change_amount` decimal(10,2) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transaction_details` (
  `detail_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `production_cost` decimal(10,2) DEFAULT 0.00,
  FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`transaction_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Khusus SPK
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `peran` varchar(20) NOT NULL DEFAULT 'admin',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bundles` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `kode_paket` varchar(20) NOT NULL UNIQUE,
  `nama_paket` varchar(100) NOT NULL,
  `diskon_tipe` varchar(20) NOT NULL DEFAULT 'nominal',
  `diskon_nilai` decimal(12,2) NOT NULL DEFAULT 0.00,
  `harga_normal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `harga_promosi` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hpp_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `margin_awal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ikut_analisis` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bundle_items` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `bundle_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  FOREIGN KEY (`bundle_id`) REFERENCES `bundles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `criteria` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `kode` varchar(10) NOT NULL UNIQUE,
  `nama` varchar(100) NOT NULL,
  `tipe` varchar(20) NOT NULL,
  `bobot` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `keterangan` text DEFAULT NULL,
  `urutan` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `criteria_history` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `catatan` text DEFAULT NULL,
  `snapshot_json` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `analysis_runs` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nama_analisis` varchar(150) NOT NULL,
  `tanggal_analisis` datetime DEFAULT CURRENT_TIMESTAMP,
  `periode_awal` date NOT NULL,
  `periode_akhir` date NOT NULL,
  `threshold_c` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `threshold_d` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `user_id` int(11) NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `analysis_scores` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `run_id` int(11) NOT NULL,
  `bundle_id` int(11) NOT NULL,
  `criterion_id` int(11) NOT NULL,
  `nilai_awal` decimal(16,4) NOT NULL DEFAULT 0.0000,
  `nilai_manual` decimal(16,4) DEFAULT NULL,
  `nilai_final` decimal(16,4) NOT NULL DEFAULT 0.0000,
  `catatan_koreksi` text DEFAULT NULL,
  FOREIGN KEY (`run_id`) REFERENCES `analysis_runs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`bundle_id`) REFERENCES `bundles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`criterion_id`) REFERENCES `criteria`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `analysis_matrices` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `run_id` int(11) NOT NULL,
  `matrix_type` varchar(20) NOT NULL,
  `matrix_json` longtext NOT NULL,
  FOREIGN KEY (`run_id`) REFERENCES `analysis_runs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `analysis_results` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `run_id` int(11) NOT NULL,
  `bundle_id` int(11) NOT NULL,
  `outranking_count` int(11) NOT NULL DEFAULT 0,
  `dominated_count` int(11) NOT NULL DEFAULT 0,
  `net_score` int(11) NOT NULL DEFAULT 0,
  `peringkat` int(11) NOT NULL DEFAULT 0,
  `status_dominasi` varchar(50) DEFAULT NULL,
  `alasan_rekomendasi` text DEFAULT NULL,
  FOREIGN KEY (`run_id`) REFERENCES `analysis_runs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`bundle_id`) REFERENCES `bundles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

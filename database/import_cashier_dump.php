<?php
// database/import_cashier_dump.php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

function importCashierDump($sqlFile) {
    if (!file_exists($sqlFile)) {
        die("File $sqlFile tidak ditemukan!\n");
    }

    echo "Membaca file SQL kasir: $sqlFile ...\n";
    $content = file_get_contents($sqlFile);
    $db = Database::getConnection();
    $driver = Database::getActiveDriver();
    echo "Target Database Driver: " . strtoupper($driver) . "\n";

    // Bersihkan tabel yang akan diimpor dari kasir
    $db->exec("DELETE FROM transaction_details;");
    $db->exec("DELETE FROM transactions;");
    $db->exec("DELETE FROM recipes;");
    $db->exec("DELETE FROM raw_materials;");
    $db->exec("DELETE FROM products;");
    $db->exec("DELETE FROM product_categories;");

    // Ekstrak INSERT statements untuk tabel-tabel terkait:
    // product_categories, products, raw_materials, recipes, transactions, transaction_details
    $targetTables = [
        'product_categories',
        'products',
        'raw_materials',
        'recipes',
        'transactions',
        'transaction_details'
    ];

    foreach ($targetTables as $table) {
        echo "Mengimpor data tabel: $table ...\n";
        
        // Cari blok INSERT INTO `$table` (...) VALUES ... ;
        // Regex untuk menangkap seluruh statement insert tabel ini
        $pattern = '/INSERT INTO `' . $table . '`\s*\((.*?)\)\s*VALUES\s*(.*?);/s';
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $columnsRaw = $match[1];
                $valuesRaw = $match[2];

                // Adaptasi kolom & data ke skema aplikasi SPK kita
                adaptAndInsert($db, $table, $columnsRaw, $valuesRaw, $driver);
            }
        }
    }

    // Pastikan kolom hpp_manual ada di products jika perlu
    if ($driver === 'sqlite') {
        try {
            $db->exec("ALTER TABLE products ADD COLUMN hpp_manual REAL DEFAULT NULL;");
        } catch (Exception $e) {}
    } else {
        try {
            $db->exec("ALTER TABLE products ADD COLUMN hpp_manual DECIMAL(12,2) DEFAULT NULL;");
        } catch (Exception $e) {}
    }

    // Isi default hpp_manual untuk topping sesuai Catatan PRD Bagian 6 Catatan #1
    $db->exec("UPDATE products SET hpp_manual = 1000 WHERE product_name LIKE '%Topping%' OR nama_produk LIKE '%Topping%';");

    echo "Impor dump kasir selesai!\n";
}

function adaptAndInsert(PDO $db, string $table, string $colsStr, string $valuesStr, string $driver) {
    // Normalisasi nama kolom dan tabel agar sesuai
    // Dump kasir menggunakan:
    // products: product_id, product_name, category_id, price, min_stock, product_image, is_active, created_at, updated_at
    // product_categories: category_id, category_name, created_at
    // raw_materials: material_id, material_name, unit, current_stock, min_stock, price_per_unit, created_at, updated_at
    // recipes: recipe_id, product_id, material_id, quantity, created_at, updated_at
    // transactions: transaction_id, transaction_code, user_id, payment_method, subtotal, total, payment_amount, change_amount, transaction_date
    // transaction_details: detail_id, transaction_id, product_id, product_name, quantity, price, subtotal, production_cost

    // Kita bisa mengeksekusi langsung query INSERT INTO `$table` ...
    // Karena kita akan memastikan tabel SPK mendukung nama kolom ini atau kompatibel!
    $sql = "INSERT INTO `$table` ($colsStr) VALUES $valuesStr;";
    
    // SQLite compatibility replacements:
    if ($driver === 'sqlite') {
        // Ganti backticks jika perlu, tapi SQLite mendukung backticks.
    }

    try {
        $db->exec($sql);
    } catch (PDOException $e) {
        echo "Error insert $table: " . $e->getMessage() . "\n";
    }
}

// Jalankan import
importCashierDump(__DIR__ . '/matcha_kocok_cashier.sql');


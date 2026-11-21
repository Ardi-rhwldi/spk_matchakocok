<?php
// database/setup_with_dump.php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

$db = Database::getConnection();
$driver = Database::getActiveDriver();
echo "Using driver: " . strtoupper($driver) . "\n";

// 1. Buat Tabel-tabel Master sesuai format aplikasi kasir & Tabel SPK
if ($driver === 'sqlite') {
    // Drop all to re-create clean schema matching cashier dump
    $db->exec("
        DROP TABLE IF EXISTS analysis_results;
        DROP TABLE IF EXISTS analysis_matrices;
        DROP TABLE IF EXISTS analysis_scores;
        DROP TABLE IF EXISTS analysis_runs;
        DROP TABLE IF EXISTS criteria_history;
        DROP TABLE IF EXISTS criteria;
        DROP TABLE IF EXISTS bundle_items;
        DROP TABLE IF EXISTS bundles;
        DROP TABLE IF EXISTS transaction_details;
        DROP TABLE IF EXISTS transactions;
        DROP TABLE IF EXISTS recipes;
        DROP TABLE IF EXISTS raw_materials;
        DROP TABLE IF EXISTS products;
        DROP TABLE IF EXISTS product_categories;
        DROP TABLE IF EXISTS users;

        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            nama TEXT NOT NULL,
            peran TEXT NOT NULL DEFAULT 'admin',
            aktif INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE product_categories (
            category_id INTEGER PRIMARY KEY,
            category_name TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE products (
            product_id INTEGER PRIMARY KEY,
            product_name TEXT NOT NULL,
            category_id INTEGER DEFAULT 1,
            price REAL NOT NULL,
            min_stock INTEGER DEFAULT 5,
            product_image TEXT DEFAULT NULL,
            is_active INTEGER DEFAULT 1,
            hpp_manual REAL DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE raw_materials (
            material_id INTEGER PRIMARY KEY,
            material_name TEXT NOT NULL,
            unit TEXT NOT NULL,
            current_stock REAL DEFAULT 0,
            min_stock REAL DEFAULT 0,
            price_per_unit REAL NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE recipes (
            recipe_id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            material_id INTEGER NOT NULL,
            quantity REAL NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE transactions (
            transaction_id INTEGER PRIMARY KEY,
            transaction_code TEXT NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            payment_method TEXT NOT NULL,
            subtotal REAL NOT NULL,
            total REAL NOT NULL,
            payment_amount REAL DEFAULT NULL,
            change_amount REAL DEFAULT NULL,
            transaction_date DATETIME NOT NULL
        );

        CREATE TABLE transaction_details (
            detail_id INTEGER PRIMARY KEY,
            transaction_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            price REAL NOT NULL,
            subtotal REAL NOT NULL,
            production_cost REAL DEFAULT 0
        );

        CREATE TABLE bundles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kode_paket TEXT NOT NULL UNIQUE,
            nama_paket TEXT NOT NULL,
            diskon_tipe TEXT NOT NULL DEFAULT 'nominal',
            diskon_nilai REAL NOT NULL DEFAULT 0,
            harga_normal REAL NOT NULL DEFAULT 0,
            harga_promosi REAL NOT NULL DEFAULT 0,
            hpp_total REAL NOT NULL DEFAULT 0,
            margin_awal REAL NOT NULL DEFAULT 0,
            ikut_analisis INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE bundle_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bundle_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            qty INTEGER NOT NULL DEFAULT 1
        );

        CREATE TABLE criteria (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kode TEXT NOT NULL UNIQUE,
            nama TEXT NOT NULL,
            tipe TEXT NOT NULL,
            bobot REAL NOT NULL DEFAULT 0,
            keterangan TEXT DEFAULT NULL,
            urutan INTEGER NOT NULL DEFAULT 1
        );

        CREATE TABLE criteria_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            catatan TEXT DEFAULT NULL,
            snapshot_json TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE analysis_runs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama_analisis TEXT NOT NULL,
            tanggal_analisis DATETIME DEFAULT CURRENT_TIMESTAMP,
            periode_awal DATE NOT NULL,
            periode_akhir DATE NOT NULL,
            threshold_c REAL NOT NULL DEFAULT 0,
            threshold_d REAL NOT NULL DEFAULT 0,
            user_id INTEGER NOT NULL,
            catatan TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE analysis_scores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            run_id INTEGER NOT NULL,
            bundle_id INTEGER NOT NULL,
            criterion_id INTEGER NOT NULL,
            nilai_awal REAL NOT NULL DEFAULT 0,
            nilai_manual REAL DEFAULT NULL,
            nilai_final REAL NOT NULL DEFAULT 0,
            catatan_koreksi TEXT DEFAULT NULL
        );

        CREATE TABLE analysis_matrices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            run_id INTEGER NOT NULL,
            matrix_type TEXT NOT NULL,
            matrix_json TEXT NOT NULL
        );

        CREATE TABLE analysis_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            run_id INTEGER NOT NULL,
            bundle_id INTEGER NOT NULL,
            outranking_count INTEGER NOT NULL DEFAULT 0,
            dominated_count INTEGER NOT NULL DEFAULT 0,
            net_score INTEGER NOT NULL DEFAULT 0,
            peringkat INTEGER NOT NULL DEFAULT 0,
            status_dominasi TEXT DEFAULT NULL,
            alasan_rekomendasi TEXT DEFAULT NULL
        );
    ");
}

echo "Tables created. Importing cashier dump...\n";

// 2. Baca file SQL kasir dan eksekusi INSERT untuk tabel-tabel terkait
$sqlFile = __DIR__ . '/matcha_kocok_cashier.sql';
$content = file_get_contents($sqlFile);

$tables = [
    'product_categories',
    'products',
    'raw_materials',
    'recipes',
    'transactions',
    'transaction_details'
];

$db->beginTransaction();
foreach ($tables as $t) {
    echo "Processing $t ... ";
    $pattern = '/INSERT INTO `' . $t . '`\s*\((.*?)\)\s*VALUES\s*(.*?);/s';
    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $cols = $m[1];
            $vals = $m[2];
            $db->exec("INSERT INTO `$t` ($cols) VALUES $vals;");
        }
        echo "Done.\n";
    } else {
        echo "No insert statements found.\n";
    }
}

// 3. Masukkan Akun SPK (Pemilik & Admin)
$passPemilik = password_hash('pemilik123', PASSWORD_BCRYPT);
$passAdmin   = password_hash('admin123', PASSWORD_BCRYPT);

$stmtUser = $db->prepare("INSERT INTO users (username, password_hash, nama, peran, aktif) VALUES (?, ?, ?, ?, 1)");
$stmtUser->execute(['pemilik', $passPemilik, 'Ardi Rihwaldi (Pemilik)', 'pemilik']);
$stmtUser->execute(['admin', $passAdmin, 'Admin Matcha Cidahu', 'admin']);

// 4. Masukkan 7 Kriteria ELECTRE
$db->exec("INSERT INTO criteria (id, kode, nama, tipe, bobot, keterangan, urutan) VALUES 
    (1, 'C1', 'Margin Keuntungan Paket', 'benefit', 0.2500, 'Harga promosi dikurangi HPP paket (HPP dari resep dikali harga bahan)', 1),
    (2, 'C2', 'Frekuensi Dibeli Bersamaan', 'benefit', 0.2000, 'Jumlah transaksi yang memuat semua menu penyusun paket pada periode analisis', 2),
    (3, 'C3', 'Volume Penjualan Menu', 'benefit', 0.1500, 'Total qty terjual seluruh menu penyusun pada periode analisis', 3),
    (4, 'C4', 'Ketersediaan Stok Bahan Baku', 'benefit', 0.1000, 'Jumlah paket yang dapat dibuat dari stok = min(stok bahan / kebutuhan bahan)', 4),
    (5, 'C5', 'Harga Bundling Promosi', 'cost', 0.1000, 'Harga promosi paket yang ditawarkan ke konsumen', 5),
    (6, 'C6', 'Tren Penjualan Menu', 'benefit', 0.1000, 'Persentase perubahan qty penjualan paruh akhir dibanding paruh awal', 6),
    (7, 'C7', 'Kompleksitas Produksi', 'cost', 0.1000, 'Jumlah jenis bahan baku unik pada gabungan resep paket', 7)
");

// 5. Masukkan 10 Kandidat Paket Promosi (Lampiran A PRD)
// Mapping produk dari dump:
// 1 = Original Matcha (10.000)
// 2 = Matcha Latte (12.000)
// 3 = Blueberry Matcha (13.000)
// 4 = Caramel Matcha (13.000)
// 5 = Sea Salt Matcha (15.000)

$bundles = [
    // [kode, nama, normal, diskon, promo, hpp, margin, [item_product_ids]]
    ['P01', 'Paket Original + Latte', 22000, 2000, 20000, 11220, 8780, [1, 2]],
    ['P02', 'Paket Original + Caramel', 23000, 2000, 21000, 12270, 8730, [1, 4]],
    ['P03', 'Paket Latte + Caramel', 25000, 2000, 23000, 12870, 10130, [2, 4]],
    ['P04', 'Paket Original + Sea Salt', 25000, 2000, 23000, 12720, 10280, [1, 5]],
    ['P05', 'Paket Original + Blueberry', 23000, 2000, 21000, 12420, 8580, [1, 3]],
    ['P06', 'Paket Latte + Sea Salt', 27000, 2000, 25000, 13320, 11680, [2, 5]],
    ['P07', 'Paket Latte + Blueberry', 25000, 2000, 23000, 13020, 9980, [2, 3]],
    ['P08', 'Paket Blueberry + Caramel', 26000, 2000, 24000, 14070, 9930, [3, 4]],
    ['P09', 'Paket Caramel + Sea Salt', 28000, 2000, 26000, 14370, 11630, [4, 5]],
    ['P10', 'Paket Blueberry + Sea Salt', 28000, 2000, 26000, 14520, 11480, [3, 5]],
];

$stmtBundle = $db->prepare("INSERT INTO bundles (id, kode_paket, nama_paket, diskon_tipe, diskon_nilai, harga_normal, harga_promosi, hpp_total, margin_awal, ikut_analisis) VALUES (?, ?, ?, 'nominal', ?, ?, ?, ?, ?, 1)");
$stmtBItem  = $db->prepare("INSERT INTO bundle_items (bundle_id, product_id, qty) VALUES (?, ?, 1)");

$bId = 1;
foreach ($bundles as $b) {
    $stmtBundle->execute([$bId, $b[0], $b[1], $b[3], $b[2], $b[4], $b[5], $b[6]]);
    foreach ($b[7] as $pId) {
        $stmtBItem->execute([$bId, $pId]);
    }
    $bId++;
}

// 6. Set HPP manual untuk topping sesuai Catatan PRD Bagian 6 Catatan #1
$db->exec("UPDATE products SET hpp_manual = 1000 WHERE product_id IN (6, 7, 8);");

$db->commit();

echo "\nVerification of imported real data:\n";
echo "Total Products: " . $db->query("SELECT COUNT(*) FROM products")->fetchColumn() . "\n";
echo "Total Raw Materials: " . $db->query("SELECT COUNT(*) FROM raw_materials")->fetchColumn() . "\n";
echo "Total Recipes: " . $db->query("SELECT COUNT(*) FROM recipes")->fetchColumn() . "\n";
echo "Total Transactions: " . $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn() . "\n";
echo "Total Transaction Details: " . $db->query("SELECT COUNT(*) FROM transaction_details")->fetchColumn() . "\n";
echo "Total Bundles: " . $db->query("SELECT COUNT(*) FROM bundles")->fetchColumn() . "\n";
echo "\nImport completed successfully!\n";


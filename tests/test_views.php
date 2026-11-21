<?php
// tests/test_views.php
$_SESSION['user'] = [
    'id' => 1,
    'username' => 'pemilik',
    'nama' => 'Ardi Rihwaldi (Pemilik)',
    'peran' => 'pemilik'
];
$_SESSION['last_activity'] = time();

$pages = [
    'dashboard',
    'products',
    'materials',
    'recipes',
    'transactions',
    'import',
    'bundles',
    'bundles_create',
    'bundles_generate',
    'criteria',
    'electre',
    'electre_steps',
    'reports',
    'simulation',
    'users',
    'profile'
];

echo "=== Testing Page Rendering ===\n";
foreach ($pages as $p) {
    ob_start();
    $_GET['page'] = $p;
    $_GET['run_id'] = 1;
    $_GET['product_id'] = 1;
    try {
        include __DIR__ . '/../index.php';
        $out = ob_get_clean();
        echo "[OK] Page: $p (" . strlen($out) . " bytes rendered)\n";
    } catch (Throwable $e) {
        ob_end_clean();
        echo "[FAIL] Page: $p -> " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
echo "=== Page Rendering Test Finished ===\n";


<?php
// index.php - Front Controller SPK Bundling Matcha Kocok

// 1. Penanganan Berkas Statis (CSS, JS, Gambar, Font) untuk PHP Built-in Server
$reqUri = $_SERVER['REQUEST_URI'] ?? '';
$reqPath = urldecode(parse_url($reqUri, PHP_URL_PATH));

if (preg_match('/\.(?:css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|map)$/i', $reqPath)) {
    // Normalisasi: buang prefix /spk_matchakocok/ jika dipanggil di subpath
    $cleanedPath = preg_replace('#^/spk_matchakocok/#i', '/', $reqPath);
    $targetFile = __DIR__ . $cleanedPath;

    if (file_exists($targetFile) && is_file($targetFile)) {
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $mimes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'map'   => 'application/json'
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile($targetFile);
        exit;
    }
}

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

// Pastikan database dan seeder siap
require_once __DIR__ . '/database/setup.php';
try {
    runSetup(false);
} catch (Exception $e) {
    // Abaikan jika sudah siap
}

// Autoload Controllers
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/controllers/MasterController.php';
require_once __DIR__ . '/app/controllers/BundleController.php';
require_once __DIR__ . '/app/controllers/CriteriaController.php';
require_once __DIR__ . '/app/controllers/ElectreController.php';
require_once __DIR__ . '/app/controllers/ReportController.php';
require_once __DIR__ . '/app/controllers/UserController.php';

$action = $_GET['action'] ?? null;
$page = $_GET['page'] ?? null;

// Handle Actions (POST / state-changing GET)
if ($action) {
    switch ($action) {
        case 'login':
            (new AuthController())->handleLogin();
            break;
        case 'logout':
            (new AuthController())->handleLogout();
            break;
        case 'update_password':
            (new AuthController())->updatePassword();
            break;
        case 'store_product':
            (new MasterController())->storeProduct();
            break;
        case 'update_product':
            (new MasterController())->updateProduct();
            break;
        case 'store_material':
            (new MasterController())->storeMaterial();
            break;
        case 'update_material':
            (new MasterController())->updateMaterial();
            break;
        case 'store_recipe_item':
            (new MasterController())->storeRecipeItem();
            break;
        case 'delete_recipe_item':
            (new MasterController())->deleteRecipeItem();
            break;
        case 'process_import':
            (new MasterController())->handleImport();
            break;
        case 'store_bundle':
            (new BundleController())->store();
            break;
        case 'update_bundle':
            (new BundleController())->update();
            break;
        case 'toggle_bundle':
            (new BundleController())->toggleActive();
            break;
        case 'delete_bundle':
            (new BundleController())->delete();
            break;
        case 'apply_generated_bundles':
            (new BundleController())->applyGeneratedCandidates();
            break;
        case 'update_weights':
            (new CriteriaController())->updateWeights();
            break;
        case 'update_period':
            (new CriteriaController())->updatePeriod();
            break;
        case 'run_electre':
            (new ElectreController())->process();
            break;
        case 'export_excel':
            (new ReportController())->exportExcel();
            break;
        case 'store_user':
            (new UserController())->store();
            break;
        case 'update_user':
            (new UserController())->update();
            break;
        default:
            redirect('/?page=dashboard');
            break;
    }
    exit;
}

// Handle Views (GET)
if (empty($_SESSION['user']) && $page !== 'login') {
    (new AuthController())->showLogin();
    exit;
}

switch ($page) {
    case 'login':
        (new AuthController())->showLogin();
        break;
    case 'profile':
        (new AuthController())->showProfile();
        break;
    case 'dashboard':
        (new DashboardController())->index();
        break;
    case 'products':
        (new MasterController())->products();
        break;
    case 'materials':
        (new MasterController())->materials();
        break;
    case 'recipes':
        (new MasterController())->recipes();
        break;
    case 'transactions':
        (new MasterController())->transactions();
        break;
    case 'import':
        (new MasterController())->import();
        break;
    case 'bundles':
        (new BundleController())->index();
        break;
    case 'bundles_create':
        (new BundleController())->create();
        break;
    case 'bundles_edit':
        (new BundleController())->edit();
        break;
    case 'bundles_generate':
        (new BundleController())->showGenerator();
        break;
    case 'criteria':
        (new CriteriaController())->index();
        break;
    case 'electre':
        (new ElectreController())->index();
        break;
    case 'electre_steps':
        (new ElectreController())->steps();
        break;
    case 'reports':
        (new ReportController())->index();
        break;
    case 'simulation':
        (new ReportController())->simulation();
        break;
    case 'users':
        (new UserController())->index();
        break;
    default:
        (new DashboardController())->index();
        break;
}

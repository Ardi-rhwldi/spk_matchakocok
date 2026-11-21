<?php
$files = [
    '/assets/vendor/bootstrap/bootstrap.min.css',
    '/assets/vendor/bootstrap/bootstrap.bundle.min.js',
    '/assets/vendor/bootstrap-icons/bootstrap-icons.min.css',
    '/assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2',
    '/assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff',
    '/assets/vendor/chartjs/chart.umd.min.js',
    '/assets/css/custom.css'
];

foreach ($files as $f) {
    $full = __DIR__ . '/..' . $f;
    echo $f . ": " . (file_exists($full) ? "EXISTS (" . filesize($full) . " bytes)" : "NOT FOUND") . "\n";
}


<?php
// app/views/layouts/header.php
if (!defined('APP_NAME')) exit;
$currentUser = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>

    <!-- Bootstrap 5 CSS (Local first) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.min.css">
    <!-- Bootstrap Icons (Local first) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Chart.js -->
    <script src="<?= BASE_URL ?>/assets/vendor/chartjs/chart.umd.min.js"></script>

    <!-- Matcha Design System v2 (Inlined for guaranteed delivery) -->
    <style>
    <?php
    $cssFile = __DIR__ . '/../../../assets/css/custom.css';
    if (file_exists($cssFile)) echo file_get_contents($cssFile);
    ?>
    </style>
</head>
<body>
<div class="app-wrapper">

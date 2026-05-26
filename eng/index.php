<?php
ob_start();
session_start();

define('APP_ACCESS', true);

$allowedViews = [
    'home' => __DIR__ . '/home.php'
];

$view = $_GET['view'] ?? 'home';

if ($view === '') {
    $view = 'home';
}

if (!array_key_exists($view, $allowedViews)) {
    http_response_code(404);
    $view = 'home';
}

$pageTitle = 'WronAir | Home';
$pageStyle = '../style/style.css';

require __DIR__ . '/includes/header.php';
require $allowedViews[$view];
require __DIR__ . '/includes/footer.php';

ob_end_flush();
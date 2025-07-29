<?php
// debug.php - Fichier de test pour Render
header('Content-Type: application/json');

echo json_encode([
    'status' => 'OK',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_info' => [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'N/A',
        'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'N/A'
    ],
    'env_vars' => [
        'DB_HOST' => $_ENV['DB_HOST'] ?? 'NOT_SET',
        'DB_PORT' => $_ENV['DB_PORT'] ?? 'NOT_SET',
        'APP_ENV' => $_ENV['APP_ENV'] ?? 'NOT_SET'
    ],
    'files_exist' => [
        'bootstrap.php' => file_exists(__DIR__ . '/../app/config/bootstrap.php'),
        'routes' => file_exists(__DIR__ . '/../routes/route.web.php'),
        'services.yml' => file_exists(__DIR__ . '/../app/config/services.yml')
    ]
], JSON_PRETTY_PRINT);
?>
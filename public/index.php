<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ⭐ LOGS DE DEBUG DÉTAILLÉS
error_log("=== 🔍 DEBUG ROUTING WOYOFAL ===");
error_log("🌐 REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NON DÉFINI'));
error_log("🔧 REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NON DÉFINI'));
error_log("📝 SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NON DÉFINI'));
error_log("📍 PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NON DÉFINI'));
error_log("🔍 QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'NON DÉFINI'));
error_log("🏠 DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NON DÉFINI'));
error_log("📊 HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NON DÉFINI'));
error_log("📅 Date: " . date('Y-m-d H:i:s'));

// ⭐ Vérifier que le bootstrap est accessible
if (!file_exists(__DIR__ . '/../app/config/bootstrap.php')) {
    error_log("❌ ERREUR: bootstrap.php introuvable!");
    http_response_code(500);
    echo json_encode(['error' => 'Configuration manquante']);
    exit;
}

require_once __DIR__ . '/../app/config/bootstrap.php';

use Mapathe\Router;

// ⭐ Vérifier que les routes sont chargées
if (!isset($routes) || empty($routes)) {
    error_log("❌ ERREUR: Routes non définies!");
    http_response_code(500);
    echo json_encode(['error' => 'Routes non configurées']);
    exit;
}

error_log("✅ Routes chargées: " . count($routes) . " routes disponibles");
error_log("📋 Routes: " . json_encode(array_keys($routes)));

Router::setRoute($routes);
Router::resolve();

error_log("=== 🏁 FIN DEBUG ROUTING ===");
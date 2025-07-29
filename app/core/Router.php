<?php

namespace Mapathe;

use Mapathe\Enums\KeyRoute;

class Router
{
    private static array $routes = [];
    
    public static function setRoute(array $routes): void
    {
        self::$routes = $routes;
    }
    
    public static function resolve(): void
{
    error_log("🚀 Router::resolve() - DÉBUT");
    
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    // ⭐ Nettoyer et normaliser l'URI
    $uri = '/' . trim($uri, '/');
    if ($uri === '/') $uri = '/';
    
    error_log("📍 URI originale: " . ($_SERVER['REQUEST_URI'] ?? 'non définie'));
    error_log("📍 URI nettoyée: '{$uri}'");
    error_log("📍 Méthode HTTP: '{$method}'");
    error_log("📍 Routes disponibles: " . json_encode(array_keys(self::$routes)));
    
    // ⭐ 1. Chercher une route exacte
    if (isset(self::$routes[$uri])) {
        error_log("✅ Route exacte trouvée: {$uri}");
        self::executeRoute(self::$routes[$uri]);
        return;
    }
    
    // ⭐ 2. Chercher une route avec paramètres
    foreach (self::$routes as $route => $config) {
        if (self::matchRoute($route, $uri)) {
            error_log("✅ Route avec paramètres trouvée: {$route} match {$uri}");
            self::executeRoute($config, self::extractParams($route, $uri));
            return;
        }
    }
    
    // ⭐ 3. Route 404
    error_log("❌ Aucune route trouvée pour: {$uri}");
    
    if (isset(self::$routes['/404'])) {
        error_log("🔄 Redirection vers route 404");
        self::executeRoute(self::$routes['/404']);
    } elseif (isset(self::$routes['/'])) {
        error_log("🔄 Redirection vers route par défaut");
        self::executeRoute(self::$routes['/']);
    } else {
        error_log("💥 Aucune route de fallback!");
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Route non trouvée',
            'uri' => $uri,
            'method' => $method,
            'available_routes' => array_keys(self::$routes)
        ]);
    }
    
    error_log("🏁 Router::resolve() - FIN");
}
    
    private static function matchRoute(string $route, string $uri): bool
    {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
        $routePattern = '#^' . $routePattern . '$#';
        return preg_match($routePattern, $uri);
    }
    
    private static function extractParams(string $route, string $uri): array
    {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
        $routePattern = '#^' . $routePattern . '$#';
        
        preg_match($routePattern, $uri, $matches);
        array_shift($matches);
        
        return $matches;
    }
    
   private static function executeRoute(array $config, array $params = []): void
{
    $controllerClass = $config[KeyRoute::CONTROLLER->value];
    $method = $config[KeyRoute::METHOD->value];
    $httpMethod = $config[KeyRoute::HTTP_METHOD->value] ?? 'GET';

    if (!class_exists($controllerClass)) {
        http_response_code(500);
        echo json_encode(['error' => 'Controller not found']);
        return;
    }

    $controller = new $controllerClass();

    if (!method_exists($controller, $method)) {
        http_response_code(500);
        echo json_encode(['error' => 'Method not found']);
        return;
    }

    // Cas spécial POST JSON : injecter les données du body
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($params)) {
        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'Format JSON invalide']);
            return;
        }

        if (!isset($input['numero_compteur'], $input['montant'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Paramètres requis: numero_compteur et montant']);
            return;
        }

        $params = [
            trim($input['numero_compteur']),
            floatval($input['montant']),
        ];
    }

    try {
        call_user_func_array([$controller, $method], $params);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

}
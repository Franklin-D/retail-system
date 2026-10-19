<?php
/**
 * Front controller: sesión, CORS, despacho a routes/*.routes.php
 */
require_once __DIR__ . '/../config/env.php';
loadEnv(__DIR__ . '/../../.env');

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/cors.middleware.php';
require_once __DIR__ . '/../middleware/auth.middleware.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

applyCors();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#/api(/.*)?$#', $path, $coincidencias)) {
    $apiPath = $coincidencias[1] ?? '/';
} else {
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $apiPath = $path;
    if ($scriptDir !== '' && str_starts_with($path, $scriptDir)) {
        $apiPath = substr($path, strlen($scriptDir)) ?: '/';
    }
}

$segments = array_values(array_filter(explode('/', trim($apiPath, '/')), fn($p) => $p !== ''));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$resource = $segments[0] ?? '';
$rest = array_slice($segments, 1);

$publicas = (
    ($resource === 'auth' && ($rest[0] ?? '') === 'login')
    || $resource === 'ping'
    || $resource === ''
);

if (!$publicas) {
    requireAuth();
}

try {
    switch ($resource) {
        case '':
        case 'ping':
            Response::success([
                'database' => env('DB_NAME', 'planet_color'),
                'time'     => date('Y-m-d H:i:s'),
            ], 'API activa');
            break;
        case 'auth':
            require __DIR__ . '/../routes/auth.routes.php';
            break;
        case 'products':
        case 'productos':
            require __DIR__ . '/../routes/products.routes.php';
            break;
        case 'categories':
            require __DIR__ . '/../routes/categories.routes.php';
            break;
        case 'suppliers':
            require __DIR__ . '/../routes/suppliers.routes.php';
            break;
        case 'customers':
            require __DIR__ . '/../routes/customers.routes.php';
            break;
        case 'sales':
            require __DIR__ . '/../routes/sales.routes.php';
            break;
        case 'purchases':
            require __DIR__ . '/../routes/purchases.routes.php';
            break;
        case 'invoices':
            require __DIR__ . '/../routes/invoices.routes.php';
            break;
        case 'income':
            require __DIR__ . '/../routes/income.routes.php';
            break;
        case 'expenses':
            require __DIR__ . '/../routes/expenses.routes.php';
            break;
        case 'reports':
            require __DIR__ . '/../routes/reports.routes.php';
            break;
        default:
            Response::error('Ruta no encontrada', 404);
    }
} catch (Throwable $e) {
    Response::error('Error interno del servidor', 500);
}

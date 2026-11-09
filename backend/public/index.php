<?php
/**
 * Front controller: sesión, CORS y despacho a src/Routes/*.php
 */

use Retail\Config\Env;
use Retail\Middleware\AuthMiddleware;
use Retail\Middleware\CorsMiddleware;
use Retail\Utils\Response;

require_once __DIR__ . '/../autoload.php';

Env::load(__DIR__ . '/../../.env');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

CorsMiddleware::apply();

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
    AuthMiddleware::requireAuth();
}

try {
    switch ($resource) {
        case '':
        case 'ping':
            Response::success([
                'database' => Env::get('DB_NAME', 'planet_color'),
                'time'     => date('Y-m-d H:i:s'),
            ], 'API activa');
            break;
        case 'auth':
            require __DIR__ . '/../src/Routes/AuthRoutes.php';
            break;
        case 'products':
        case 'productos':
            require __DIR__ . '/../src/Routes/ProductRoutes.php';
            break;
        case 'categories':
            require __DIR__ . '/../src/Routes/CategoryRoutes.php';
            break;
        case 'suppliers':
            require __DIR__ . '/../src/Routes/SupplierRoutes.php';
            break;
        case 'customers':
            require __DIR__ . '/../src/Routes/CustomerRoutes.php';
            break;
        case 'sales':
            require __DIR__ . '/../src/Routes/SalesRoutes.php';
            break;
        case 'purchases':
            require __DIR__ . '/../src/Routes/PurchaseRoutes.php';
            break;
        case 'invoices':
            require __DIR__ . '/../src/Routes/InvoiceRoutes.php';
            break;
        case 'income':
            require __DIR__ . '/../src/Routes/IncomeRoutes.php';
            break;
        case 'expenses':
            require __DIR__ . '/../src/Routes/ExpenseRoutes.php';
            break;
        case 'reports':
            require __DIR__ . '/../src/Routes/ReportRoutes.php';
            break;
        default:
            Response::error('Ruta no encontrada', 404);
    }
} catch (Throwable $e) {
    Response::error('Error interno del servidor', 500);
}

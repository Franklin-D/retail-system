<?php
/**
 * // backend/public/index.php — Front Controller
 * -----------------------------------------------------------------------
 * Único punto de entrada HTTP del backend. Responsabilidades previstas:
 *
 *  1. Cargar configuración (.env) y arrancar la sesión PHP.
 *  2. Aplicar middleware transversal (CORS, parseo de JSON body).
 *  3. Despachar la petición a routes/*.routes.php según el prefijo de URL.
 *  4. Capturar errores no controlados y devolver JSON con código 500
 *     en vez de dejar que PHP imprima HTML de error.
 *
 * Implementación real: ETAPA 5 (API REST), reutilizando el middleware de
 * auth de la ETAPA 6 y las rutas de cada módulo (ETAPAS 7-10).
 *
 * Todas las rutas de la API cuelgan de /api/... y este archivo es el
 * "index" al que el servidor (php -S o Apache/Nginx con rewrite) redirige
 * cualquier petición que no sea un archivo estático existente.
 * -----------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/env.php';
loadEnv(__DIR__ . '/../../.env');

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/sistema-retail/backend/public';
$path = str_replace($basePath, '', $requestUri);
$segments = array_values(array_filter(explode('/', trim($path, '/'))));

$resource = $segments[0] ?? '';
$id = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;
$action = $segments[1] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($resource) {
    case 'ping':
        Response::success(['database' => env('DB_NAME', 'planet_color'), 'time' => date('Y-m-d H:i:s')], 'API activa');
        break;

    case 'auth':
        $authController = new AuthController();
        if ($method === 'POST' && $action === 'login') {
            $authController->login();
        } elseif ($method === 'GET' && $action === 'me') {
            $authController->me();
        } else {
            Response::error('Ruta o método no permitido en /auth', 405);
        }
        break;

    case 'productos':
        $productoController = new ProductoController();

        if ($method === 'GET' && $id === null) {
            $productoController->index();
        } elseif ($method === 'GET' && $id !== null) {
            $productoController->show($id);
        } elseif ($method === 'POST') {
            $productoController->store();
        } elseif ($method === 'PUT' && $id !== null) {
            $productoController->update($id);
        } elseif ($method === 'DELETE' && $id !== null) {
            $productoController->destroy($id);
        } else {
            Response::error('Método o parámetro no permitido para /productos', 405);
        }
        break;

    default:
        Response::error('Ruta no encontrada', 404);
        break;
}
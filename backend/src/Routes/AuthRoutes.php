<?php

namespace Retail\Routes;

use Retail\Controllers\AuthController;
use Retail\Utils\Response;

$authController = new AuthController();
$accion = $rest[0] ?? '';

if ($method === 'POST' && $accion === 'login') {
    $authController->login();
} elseif ($method === 'POST' && $accion === 'logout') {
    $authController->logout();
} elseif ($method === 'GET' && $accion === 'me') {
    $authController->me();
} else {
    Response::error('Ruta o método no permitido en /auth', 405);
}

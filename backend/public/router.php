<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$archivo = __DIR__ . $uri;
if ($uri !== '/' && is_file($archivo)) {
    return false;
}
require __DIR__ . '/index.php';

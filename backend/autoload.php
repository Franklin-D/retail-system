<?php
/**
 * Autoload PSR-4 de respaldo.
 * Usa vendor/autoload.php si Composer está instalado; si no, registra
 * un autoloader mínimo para el prefijo Retail\.
 */

$vendors = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
];
foreach ($vendors as $vendor) {
    if (is_file($vendor)) {
        require $vendor;
        return;
    }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Retail\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

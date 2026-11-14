<?php

require __DIR__ . '/../src/Utils/ProductSearch.php';

use Retail\Utils\ProductSearch;

$products = [
    ['id' => 1, 'nombre' => 'Pepsi Cola', 'codigo_barra' => '7750001'],
    ['id' => 2, 'nombre' => 'Powerade', 'codigo_barra' => '7750002'],
    ['id' => 3, 'nombre' => 'Arroz Extra', 'codigo_barra' => '7750003'],
];

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$assert(ProductSearch::fuzzy($products, 'pepsii')[0]['id'] === 1, 'Debe corregir un error tipográfico en Pepsi.');
$assert(ProductSearch::fuzzy($products, 'pwerade')[0]['id'] === 2, 'Debe encontrar Powerade con un carácter omitido.');
$assert(ProductSearch::fuzzy($products, 'pe') === [], 'Las consultas muy cortas no deben generar coincidencias aproximadas.');
$assert(ProductSearch::fuzzy($products, 'inexistente') === [], 'Una consulta sin coincidencias debe devolver una lista vacía.');

echo "ProductSearch: 4 pruebas aprobadas\n";

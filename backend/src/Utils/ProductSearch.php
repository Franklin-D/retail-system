<?php

namespace Retail\Utils;

/** Búsqueda aproximada por nombre; los códigos de barras requieren coincidencia exacta. */
class ProductSearch
{
    public static function fuzzy(array $products, string $query): array
    {
        $query = self::normalize($query);
        if (strlen($query) < 3) {
            return [];
        }

        $matches = [];
        foreach ($products as $product) {
            $name = self::normalize((string) ($product['nombre'] ?? ''));
            $best = 1.0;
            $nameLength = max(strlen($query), strlen($name));
            if ($nameLength > 0) {
                $best = levenshtein($query, $name) / $nameLength;
            }
            foreach (preg_split('/\s+/', $name) ?: [] as $word) {
                $length = max(strlen($query), strlen($word));
                if ($length === 0) {
                    continue;
                }
                $distance = levenshtein($query, $word);
                $score = $distance / $length;
                if ($score < $best) {
                    $best = $score;
                }
            }

            // Tolerancia conservadora para evitar que una consulta corta devuelva ruido.
            $maxDistance = strlen($query) >= 8 ? 2 : 1;
            if ($best <= $maxDistance / strlen($query)) {
                $matches[] = ['score' => $best, 'product' => $product];
            }
        }

        usort($matches, static fn(array $a, array $b): int => $a['score'] <=> $b['score']);
        return array_column($matches, 'product');
    }

    private static function normalize(string $value): string
    {
        $value = function_exists('mb_strtolower')
            ? mb_strtolower(trim($value), 'UTF-8')
            : strtolower(trim($value));
        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($ascii !== false) {
                $value = $ascii;
            }
        }
        return preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
    }
}

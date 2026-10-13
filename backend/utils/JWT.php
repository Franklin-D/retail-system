<?php
// backend/utils/JWT.php

require_once __DIR__ . '/../config/env.php';

class JWT {
    public static function generate(array $payload): string {
        $secret = env('JWT_SECRET', 'planet_color_secret_key_2026');
        $expiration = (int) env('JWT_EXPIRATION', 28800);

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiration;
        $payloadEncoded = json_encode($payload);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payloadEncoded);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function verify(string $token): ?array {
        $secret = env('JWT_SECRET', 'planet_color_secret_key_2026');
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }

        list($header, $payload, $signature) = $parts;

        $validSignature = self::base64UrlEncode(
            hash_hmac('sha256', $header . "." . $payload, $secret, true)
        );

        if (!hash_equals($validSignature, $signature)) {
            return null;
        }

        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);

        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return null;
        }

        return $decodedPayload;
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
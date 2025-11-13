<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../vendor/autoload.php';

class Token {
    private static $secret_key = "ECOPOINTS_SECRET_KEY";
    private static $encrypt = ['HS256'];

    public static function crearToken($data) {
        $time = time();
        $payload = [
            'iat' => $time,
            'exp' => $time + (60 * 60 * 4),
            'data' => $data
        ];

        return JWT::encode($payload, self::$secret_key, self::$encrypt[0]);
    }

    public static function verificarToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(self::$secret_key, self::$encrypt[0]));
            return $decoded->data;
        } catch (Exception $e) {
            return false;
        }
    }
}

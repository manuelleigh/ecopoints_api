<?php
require_once __DIR__ . '/Token.php';
require_once __DIR__ . '/../core/funciones.php';

function validarBearer() {
    global $conn;

    $getAuthorizationHeader = function() {
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'REDIRECT_HTTP_AUTHORIZATION') !== false && !empty($value)) {
                return $value;
            }
        }
        
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            return $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }
        
        return null;
    };

    $authorization = $getAuthorizationHeader();

    if (empty($authorization)) {
        respuestaJSON(["error" => "Token no proporcionado"], 401);
    }

    if (!preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
        respuestaJSON(["error" => "Formato de token incorrecto"], 401);
    }

    $token = $matches[1];

    $datos = Token::verificarToken($token);
    if (!$datos) {
        respuestaJSON(["error" => "Token inválido o expirado"], 401);
    }

    $stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE id = :id");
    $stmt->execute(["id" => $datos->id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        respuestaJSON(["error" => "Usuario no encontrado"], 401);
    }


    return $datos;
}
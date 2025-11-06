<?php
// ============================
// Cabeceras y configuración CORS
// ============================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// ============================
// Definición del mapa de rutas
// ============================
$routes = [
    "POST" => [
        "registrarUsuario" => "endpoints/registrarUsuario.php",
        "generarQR"        => "endpoints/generarQR.php",
        "validarQR"        => "endpoints/validarQR.php",
        "canjearPuntos"    => "endpoints/canjearPuntos.php",
        "logeoUsuario"     => "endpoints/logeoUsuario.php"
    ],
    "GET" => [
        "obtenerPuntos"    => "endpoints/obtenerPuntos.php",
        "listarConvenios"  => "endpoints/listarConvenios.php"
    ]
];

// ============================
// Obtener endpoint solicitado
// ============================
$uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = explode('/', trim($uri_path, '/'));
$endpoint = end($request_uri);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if (isset($routes[$method][$endpoint])) {
        $path = __DIR__ . "/" . $routes[$method][$endpoint];

        if (file_exists($path)) {
            require_once $path;
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Archivo no encontrado: $path"]);
        }
    } else {
        http_response_code(404);
        echo json_encode([
            "error" => "Ruta no encontrada",
            "ruta_solicitada" => $endpoint,
            "metodo" => $method
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error interno del servidor",
        "mensaje" => $e->getMessage()
    ]);
}
?>

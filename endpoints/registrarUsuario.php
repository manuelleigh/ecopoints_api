<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['nombre'], $data['email'], $data['password'])) {
    respuestaJSON(["error" => "Datos incompletos"], 400);
}

$hash = password_hash($data['password'], PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password_hash) VALUES (:nombre, :email, :hash)");
$stmt->execute(["nombre" => $data['nombre'], "email" => $data['email'], "hash" => $hash]);

respuestaJSON(["mensaje" => "Usuario registrado con éxito"]);
?>

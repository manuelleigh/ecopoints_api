<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

if (!isset($_GET['usuario_id'])) {
    respuestaJSON(["error" => "Falta usuario_id"], 400);
}

$stmt = $conn->prepare("SELECT puntos FROM usuarios WHERE id = :id");
$stmt->execute(["id" => $_GET['usuario_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    respuestaJSON(["error" => "Usuario no encontrado"], 404);
}

respuestaJSON(["puntos" => $user['puntos']]);
?>

<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['dispositivo_id'])) {
    respuestaJSON(["error" => "Falta dispositivo_id"], 400);
} if (!dispositivoValido($data['dispositivo_id'])) {
    respuestaJSON(["error" => "Dispositivo no autorizado"], 403);
}

$codigo = generarCodigoUnico(12);
$stmt = $conn->prepare("
    INSERT INTO codigos_qr (codigo, dispositivo_id, botellas_recicladas, valor_puntos)
    VALUES (:codigo, :dispositivo_id, :botellas, :valor)
");
$stmt->execute([
    "codigo" => $codigo,
    "dispositivo_id" => $data['dispositivo_id'],
    "botellas" => $data['botellas_recicladas'] ?? 1,
    "valor" => 5
]);


respuestaJSON(["codigo_qr" => $codigo]);
?>

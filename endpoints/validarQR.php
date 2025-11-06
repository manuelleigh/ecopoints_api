<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['codigo_qr']) || !isset($data['usuario_id'])) {
    respuestaJSON(["error" => "Datos incompletos"], 400);
}

try {
    // Buscar el QR
    $qr = $conn->prepare("SELECT * FROM codigos_qr WHERE codigo = :codigo");
    $qr->execute(["codigo" => $data['codigo_qr']]);
    $result = $qr->fetch(PDO::FETCH_ASSOC);

    $puntos = $result['valor_puntos'] * $result['botellas_recicladas'];

    if (!$result) {
        respuestaJSON(["error" => "Código QR inválido"], 404);
    }

    if ($result['estado'] !== 'PENDIENTE') {
        respuestaJSON(["error" => "El código ya fue canjeado o expiró"], 409);
    }

    // Iniciar transacción
    $conn->beginTransaction();

    // Asignar puntos al usuario
    $updateUser = $conn->prepare("
        UPDATE usuarios 
        SET puntos = puntos + :puntos 
        WHERE id = :id
    ");
    $updateUser->execute([
        "puntos" => $puntos,
        "id" => $data['usuario_id']
    ]);

    // Cambiar el estado del QR
    $updateQR = $conn->prepare("
        UPDATE codigos_qr 
        SET estado = 'CANJEADO' 
        WHERE id = :id
    ");
    $updateQR->execute(["id" => $result['id']]);

    // Registrar la transacción
    $insertTrans = $conn->prepare("
        INSERT INTO transacciones (usuario_id, codigo_qr_id, puntos)
        VALUES (:uid, :qid, :puntos)
    ");
    $insertTrans->execute([
        "uid" => $data['usuario_id'],
        "qid" => $result['id'],
        "puntos" => $puntos
    ]);

    // Confirmar transacción
    $conn->commit();

    respuestaJSON([
        "mensaje" => "Canje exitoso",
        "puntos_obtenidos" => $puntos
    ]);

} catch (Exception $e) {
    // Revertir cambios en caso de error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    respuestaJSON(["error" => "Error interno: " . $e->getMessage()], 500);
}
?>

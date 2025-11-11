<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

$camposRequeridos = ['usuario_id', 'convenio_id'];
foreach ($camposRequeridos as $campo) {
    if (!isset($data[$campo])) {
        respuestaJSON(["error" => "Campo requerido faltante: $campo"], 400);
    }
}

try {
    $conn->beginTransaction();

    // Obtener usuario
    $stmtUsuario = $conn->prepare("SELECT puntos FROM usuarios WHERE id = :id");
    $stmtUsuario->execute(['id' => $data['usuario_id']]);
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $conn->rollBack();
        respuestaJSON(["error" => "Usuario no encontrado"], 404);
    }

    // Obtener convenio
    $stmtConvenio = $conn->prepare("
        SELECT id, puntos_requeridos, tipo_entrega, stock 
        FROM convenios WHERE id = :id AND activo = TRUE
    ");
    $stmtConvenio->execute(['id' => $data['convenio_id']]);
    $convenio = $stmtConvenio->fetch(PDO::FETCH_ASSOC);

    if (!$convenio) {
        $conn->rollBack();
        respuestaJSON(["error" => "Convenio no disponible o inactivo"], 404);
    }

    if ($usuario['puntos'] < $convenio['puntos_requeridos']) {
        $conn->rollBack();
        respuestaJSON(["error" => "Puntos insuficientes para este canje"], 400);
    }

    if ($convenio['stock'] <= 0) {
        $conn->rollBack();
        respuestaJSON(["error" => "No hay stock disponible para este convenio"], 400);
    }

    // Buscar código disponible
    $stmtCodigo = $conn->prepare("
        SELECT id, codigo FROM codigos_convenio
        WHERE convenio_id = :convenio_id AND usado = FALSE
        LIMIT 1 FOR UPDATE
    ");
    $stmtCodigo->execute(['convenio_id' => $data['convenio_id']]);
    $codigoDisponible = $stmtCodigo->fetch(PDO::FETCH_ASSOC);

    if (!$codigoDisponible) {
        $conn->rollBack();
        respuestaJSON(["error" => "No hay códigos disponibles para este convenio"], 400);
    }

    // Marcar código como usado
    $stmtUsarCodigo = $conn->prepare("
        UPDATE codigos_convenio SET usado = TRUE, fecha_asignacion = NOW()
        WHERE id = :id
    ");
    $stmtUsarCodigo->execute(['id' => $codigoDisponible['id']]);

    // Registrar canje
    $stmtCanje = $conn->prepare("
        INSERT INTO canjes (usuario_id, convenio_id, puntos_usados, codigo_convenio_id)
        VALUES (:usuario_id, :convenio_id, :puntos_usados, :codigo_convenio_id)
    ");
    $stmtCanje->execute([
        'usuario_id' => $data['usuario_id'],
        'convenio_id' => $data['convenio_id'],
        'puntos_usados' => $convenio['puntos_requeridos'],
        'codigo_convenio_id' => $codigoDisponible['id']
    ]);
    
    $canjeId = $conn->lastInsertId();

    // Registrar transacción tipo 'redeem'
    $stmtTransaccion = $conn->prepare("
        INSERT INTO transacciones (usuario_id, canje_id, puntos, tipo)
        VALUES (:usuario_id, :canje_id, :puntos, 'redeem')
    ");
    $stmtTransaccion->execute([
        'usuario_id' => $data['usuario_id'],
        'canje_id' => $canjeId,
        'puntos' => $convenio['puntos_requeridos']
    ]);

    // Actualizar puntos usuario
    $stmtUpdateUser = $conn->prepare("
        UPDATE usuarios SET puntos = puntos - :puntos WHERE id = :id
    ");
    $stmtUpdateUser->execute([
        'puntos' => $convenio['puntos_requeridos'],
        'id' => $data['usuario_id']
    ]);

    // Reducir stock del convenio
    $stmtStock = $conn->prepare("
        UPDATE convenios SET stock = stock - 1 WHERE id = :id
    ");
    $stmtStock->execute(['id' => $data['convenio_id']]);

    $conn->commit();

    respuestaJSON([
        "mensaje" => "Canje realizado con éxito",
        "codigo_entrega" => $codigoDisponible['codigo'],
        "puntos_restantes" => $usuario['puntos'] - $convenio['puntos_requeridos']
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    respuestaJSON(["error" => "Error en el proceso de canje: " . $e->getMessage()], 500);
}
?>

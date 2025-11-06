<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['usuario_id'], $data['convenio_id'])) {
    respuestaJSON(["error" => "Datos incompletos"], 400);
}

$conn->beginTransaction();

try {
    $userStmt = $conn->prepare("SELECT puntos FROM usuarios WHERE id = :id");
    $userStmt->execute(["id" => $data['usuario_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    $convStmt = $conn->prepare("SELECT * FROM convenios WHERE id = :id AND activo = 1");
    $convStmt->execute(["id" => $data['convenio_id']]);
    $convenio = $convStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$convenio) {
        throw new Exception("Usuario o convenio no encontrado");
    }

    if ($user['puntos'] < $convenio['puntos_requeridos']) {
        throw new Exception("No tienes puntos suficientes");
    }

    if ($convenio['stock'] <= 0) {
        throw new Exception("Este beneficio ya no tiene stock disponible");
    }

    // Generar código único
    $codigo = "ECO-" . strtoupper(substr(md5(uniqid()), 0, 8));

    // Descontar puntos y stock
    $conn->prepare("UPDATE usuarios SET puntos = puntos - :p WHERE id = :id")
         ->execute(["p" => $convenio['puntos_requeridos'], "id" => $data['usuario_id']]);
    $conn->prepare("UPDATE convenios SET stock = stock - 1 WHERE id = :id")
         ->execute(["id" => $data['convenio_id']]);

    // Determinar tipo de entrega
    $codigo_entrega = ($convenio['tipo_entrega'] === 'URL')
        ? rtrim($convenio['base_url'], '/') . '/' . $codigo
        : $codigo;

    // Registrar canje
    $conn->prepare("INSERT INTO canjes (usuario_id, convenio_id, puntos_usados, codigo_entrega)
                    VALUES (:uid, :cid, :puntos, :codigo)")
         ->execute([
             "uid" => $data['usuario_id'],
             "cid" => $data['convenio_id'],
             "puntos" => $convenio['puntos_requeridos'],
             "codigo" => $codigo_entrega
         ]);

    $conn->commit();

    respuestaJSON([
        "mensaje" => "Canje exitoso",
        "beneficio" => $convenio['titulo'],
        "codigo_entrega" => $codigo_entrega,
        "tipo" => $convenio['tipo_entrega']
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    respuestaJSON(["error" => $e->getMessage()], 400);
}
?>

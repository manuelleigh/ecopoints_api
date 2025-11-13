<?php
/**
 * @OA\Post(
 *     path="/api/validarQR",
 *     summary="Validar código QR de reciclaje",
 *     description="Valida un código QR generado por el sistema y asigna los puntos correspondientes al usuario autenticado. Requiere autenticación Bearer Token.",
 *     tags={"Códigos QR"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Código QR a validar",
 *         @OA\JsonContent(
 *             required={"codigo_qr"},
 *             @OA\Property(
 *                 property="codigo_qr",
 *                 type="string",
 *                 description="Código QR generado por el sistema para reciclaje",
 *                 example="QR123ABC456DEF"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="QR validado exitosamente y puntos asignados",
 *         @OA\JsonContent(
 *             @OA\Property(property="mensaje", type="string", example="Canje exitoso"),
 *             @OA\Property(property="usuario", type="string", example="Juan Pérez"),
 *             @OA\Property(property="puntos_obtenidos", type="integer", example=25)
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Datos incompletos",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Datos incompletos")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="No autorizado - Token inválido o expirado",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Token inválido o expirado")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Código QR no encontrado",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Código QR inválido")
 *         )
 *     ),
 *     @OA\Response(
 *         response=409,
 *         description="Código QR ya utilizado",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="El código ya fue canjeado o expiró")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error interno: Mensaje de error específico")
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');
require_once(__DIR__ . '/../auth/validarBearer.php');

$usuario = validarBearer();
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['codigo_qr'])) {
    respuestaJSON(["error" => "Datos incompletos"], 400);
}

try {
    // Buscar el QR
    $qr = $conn->prepare("SELECT * FROM codigos_qr WHERE codigo = :codigo");
    $qr->execute(["codigo" => $data['codigo_qr']]);
    $result = $qr->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        respuestaJSON(["error" => "Código QR inválido"], 404);
    }

    if ($result['estado'] !== 'PENDIENTE') {
        respuestaJSON(["error" => "El código ya fue canjeado o expiró"], 409);
    }

    // Calcular puntos
    $puntos = $result['valor_puntos'] * $result['botellas_recicladas'];

    // Iniciar transacción
    $conn->beginTransaction();

    // Actualizar puntos del usuario autenticado
    $updateUser = $conn->prepare("
        UPDATE usuarios 
        SET puntos = puntos + :puntos 
        WHERE id = :id
    ");
    $updateUser->execute([
        "puntos" => $puntos,
        "id" => $usuario->id
    ]);

    // Cambiar estado del QR
    $updateQR = $conn->prepare("
        UPDATE codigos_qr 
        SET estado = 'CANJEADO' 
        WHERE id = :id
    ");
    $updateQR->execute(["id" => $result['id']]);

    // Registrar transacción
    $insertTrans = $conn->prepare("
        INSERT INTO transacciones (usuario_id, codigo_qr_id, puntos, tipo)
        VALUES (:uid, :qid, :puntos, 'scan')
    ");
    $insertTrans->execute([
        "uid" => $usuario->id,
        "qid" => $result['id'],
        "puntos" => $puntos
    ]);

    $conn->commit();

    respuestaJSON([
        "mensaje" => "Canje exitoso",
        "usuario" => $usuario->nombre,
        "puntos_obtenidos" => $puntos
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    respuestaJSON(["error" => "Error interno: " . $e->getMessage()], 500);
}

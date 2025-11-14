<?php
/**
 * @OA\Post(
 *     path="/api/canjearPuntos",
 *     summary="Canjear puntos por convenio",
 *     description="Permite al usuario canjear sus puntos por un convenio disponible. Requiere autenticación Bearer Token.",
 *     tags={"Canjes"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="ID del convenio a canjear",
 *         @OA\JsonContent(
 *             required={"convenio_id"},
 *             @OA\Property(
 *                 property="convenio_id",
 *                 type="integer",
 *                 description="ID del convenio que se desea canjear",
 *                 example=1
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Canje realizado exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="mensaje", type="string", example="Canje realizado con éxito"),
 *             @OA\Property(property="codigo_entrega", type="string", example="DESC2024ABC123"),
 *             @OA\Property(property="puntos_restantes", type="integer", example=850)
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Solicitud incorrecta",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Campo requerido faltante: convenio_id")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Puntos insuficientes para este canje")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="No hay stock disponible para este convenio")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="No hay códigos disponibles para este convenio")
 *                 )
 *             }
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
 *         description="Recurso no encontrado",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Usuario no encontrado")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Convenio no disponible o inactivo")
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error en el proceso de canje: Mensaje de error específico")
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');
require_once(__DIR__ . '/../auth/validarBearer.php');

$usuarioToken = validarBearer();
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['convenio_id'])) {
    respuestaJSON(["error" => "Campo requerido faltante: convenio_id"], 400);
}

try {
    $conn->beginTransaction();

    $stmtUsuario = $conn->prepare("
        SELECT puntos 
        FROM usuarios 
        WHERE id = :id
    ");
    $stmtUsuario->execute(['id' => $usuarioToken->id]);
    $usuarioDB = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioDB) {
        $conn->rollBack();
        respuestaJSON(["error" => "Usuario no encontrado"], 404);
    }

    $stmtConvenio = $conn->prepare("
        SELECT id, puntos_requeridos, tipo_entrega, stock 
        FROM convenios 
        WHERE id = :id AND activo = TRUE
    ");
    $stmtConvenio->execute(['id' => $data['convenio_id']]);
    $convenio = $stmtConvenio->fetch(PDO::FETCH_ASSOC);

    if (!$convenio) {
        $conn->rollBack();
        respuestaJSON(["error" => "Convenio no disponible o inactivo"], 404);
    }

    if ($usuarioDB['puntos'] < $convenio['puntos_requeridos']) {
        $conn->rollBack();
        respuestaJSON(["error" => "Puntos insuficientes para este canje"], 400);
    }

    if ($convenio['stock'] <= 0) {
        $conn->rollBack();
        respuestaJSON(["error" => "No hay stock disponible para este convenio"], 400);
    }

    $stmtCodigo = $conn->prepare("
        SELECT id, codigo 
        FROM codigos_convenio
        WHERE convenio_id = :convenio_id AND usado = FALSE
        LIMIT 1 FOR UPDATE
    ");
    $stmtCodigo->execute(['convenio_id' => $data['convenio_id']]);
    $codigoDisponible = $stmtCodigo->fetch(PDO::FETCH_ASSOC);

    if (!$codigoDisponible) {
        $conn->rollBack();
        respuestaJSON(["error" => "No hay códigos disponibles para este convenio"], 400);
    }

    $stmtUsarCodigo = $conn->prepare("
        UPDATE codigos_convenio 
        SET usado = TRUE, fecha_asignacion = NOW()
        WHERE id = :id
    ");
    $stmtUsarCodigo->execute(['id' => $codigoDisponible['id']]);

    $stmtCanje = $conn->prepare("
        INSERT INTO canjes (usuario_id, convenio_id, puntos_usados, codigo_convenio_id)
        VALUES (:usuario_id, :convenio_id, :puntos_usados, :codigo_convenio_id)
    ");
    $stmtCanje->execute([
        'usuario_id' => $usuarioToken->id,
        'convenio_id' => $data['convenio_id'],
        'puntos_usados' => $convenio['puntos_requeridos'],
        'codigo_convenio_id' => $codigoDisponible['id']
    ]);
    $canjeId = $conn->lastInsertId();

    $stmtTransaccion = $conn->prepare("
        INSERT INTO transacciones (usuario_id, canje_id, puntos, tipo)
        VALUES (:usuario_id, :canje_id, :puntos, 'redeem')
    ");
    $stmtTransaccion->execute([
        'usuario_id' => $usuarioToken->id,
        'canje_id' => $canjeId,
        'puntos' => $convenio['puntos_requeridos']
    ]);

    $stmtUpdateUser = $conn->prepare("
        UPDATE usuarios 
        SET puntos = puntos - :puntos 
        WHERE id = :id
    ");
    $stmtUpdateUser->execute([
        'puntos' => $convenio['puntos_requeridos'],
        'id' => $usuarioToken->id
    ]);

    $stmtStock = $conn->prepare("
        UPDATE convenios 
        SET stock = stock - 1 
        WHERE id = :id
    ");
    $stmtStock->execute(['id' => $data['convenio_id']]);

    $conn->commit();

    respuestaJSON([
        "mensaje" => "Canje realizado con éxito",
        "codigo_entrega" => $codigoDisponible['codigo'],
        "puntos_restantes" => $usuarioDB['puntos'] - $convenio['puntos_requeridos']
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    respuestaJSON([
        "error" => "Error en el proceso de canje: " . $e->getMessage()
    ], 500);
}
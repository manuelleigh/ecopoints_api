<?php
/**
 * @OA\Post(
 *     path="/api/actualizarContrasena",
 *     summary="Actualizar contraseña con token temporal",
 *     description="Permite actualizar la contraseña del usuario utilizando un token temporal obtenido tras verificar el código de reset",
 *     tags={"Autenticación"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Token temporal y nueva contraseña",
 *         @OA\JsonContent(
 *             required={"token_temporal", "nueva_contrasena"},
 *             @OA\Property(
 *                 property="token_temporal",
 *                 type="string",
 *                 description="Token temporal obtenido al verificar el código de reset",
 *                 example="a1b2c3d4e5f6789012345678901234567890123456789012345678901234567890"
 *             ),
 *             @OA\Property(
 *                 property="nueva_contrasena",
 *                 type="string",
 *                 format="password",
 *                 description="Nueva contraseña (mínimo 8 caracteres)",
 *                 example="nuevaContraseñaSegura123"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Contraseña actualizada exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="mensaje", type="string", example="Contraseña actualizada correctamente.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Datos inválidos o token incorrecto",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Token y nueva contraseña requeridos.")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="La contraseña debe tener al menos 8 caracteres.")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Token inválido o ya utilizado.")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="El token ha expirado.")
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error al actualizar contraseña: Mensaje de error específico")
 *         )
 *     )
 * )
 */

require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$input = json_decode(file_get_contents('php://input'), true);
$token_temporal = isset($input['token_temporal']) ? trim($input['token_temporal']) : '';
$nueva_contrasena = isset($input['nueva_contrasena']) ? trim($input['nueva_contrasena']) : '';

if (empty($token_temporal) || empty($nueva_contrasena)) {
    respuestaJSON(['error' => 'Token y nueva contraseña requeridos.'], 400);
}

// Validar fortaleza de contraseña
if (strlen($nueva_contrasena) < 8) {
    respuestaJSON(['error' => 'La contraseña debe tener al menos 8 caracteres.'], 400);
}

try {
    // Verificar token temporal
    $stmt = $conn->prepare("
        SELECT rp.id, rp.usuario_id, rp.expiracion_token 
        FROM reset_passwords rp 
        WHERE rp.token_temporal = :token AND rp.utilizado = 0
    ");
    $stmt->bindParam(':token', $token_temporal);
    $stmt->execute();
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        respuestaJSON(['error' => 'Token inválido o ya utilizado.'], 400);
    }

    // Verificar expiración del token
    if (strtotime($reset['expiracion_token']) < time()) {
        respuestaJSON(['error' => 'El token ha expirado.'], 400);
    }

    // Iniciar transacción
    $conn->beginTransaction();

    // Actualizar contraseña
    $nuevo_hash = password_hash($nueva_contrasena, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("UPDATE usuarios SET password_hash = :hash WHERE id = :id");
    $stmt->bindParam(':hash', $nuevo_hash);
    $stmt->bindParam(':id', $reset['usuario_id']);
    $stmt->execute();

    // Marcar código como utilizado
    $stmt = $conn->prepare("UPDATE reset_passwords SET utilizado = 1 WHERE id = :id");
    $stmt->bindParam(':id', $reset['id']);
    $stmt->execute();

    $conn->commit();

    respuestaJSON(['mensaje' => 'Contraseña actualizada correctamente.']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    respuestaJSON(['error' => 'Error al actualizar contraseña: ' . $e->getMessage()], 500);
}
?>
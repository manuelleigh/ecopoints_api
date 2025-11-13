<?php
/**
 * @OA\Post(
 *     path="/api/verificarCodigo",
 *     summary="Verificar código de reset de contraseña",
 *     description="Verifica el código de 6 dígitos enviado por email y genera un token temporal para permitir el cambio de contraseña. El token temporal expira en 10 minutos.",
 *     tags={"Autenticación"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Email y código de verificación",
 *         @OA\JsonContent(
 *             required={"email", "codigo"},
 *             @OA\Property(
 *                 property="email",
 *                 type="string",
 *                 format="email",
 *                 description="Email del usuario que solicitó el reset",
 *                 example="usuario@ejemplo.com"
 *             ),
 *             @OA\Property(
 *                 property="codigo",
 *                 type="string",
 *                 description="Código de 6 dígitos recibido por email",
 *                 example="123456"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Código verificado exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="mensaje", type="string", example="Código verificado correctamente."),
 *             @OA\Property(property="token_temporal", type="string", example="a1b2c3d4e5f6789012345678901234567890123456789012345678901234567890"),
 *             @OA\Property(property="usuario_id", type="integer", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Solicitud incorrecta o código inválido",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Email y código requeridos.")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Código inválido o ya utilizado.")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="El código ha expirado.")
 *                 )
 *             }
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

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';
$codigo = isset($input['codigo']) ? trim($input['codigo']) : '';

if (empty($email) || empty($codigo)) {
    respuestaJSON(['error' => 'Email y código requeridos.'], 400);
}

try {
    // Verificar código
    $stmt = $conn->prepare("
        SELECT rp.id, rp.usuario_id, rp.expiracion 
        FROM reset_passwords rp 
        INNER JOIN usuarios u ON rp.usuario_id = u.id 
        WHERE u.email = :email AND rp.codigo = :codigo AND rp.utilizado = 0
    ");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':codigo', $codigo);
    $stmt->execute();
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        respuestaJSON(['error' => 'Código inválido o ya utilizado.'], 400);
    }

    // Verificar expiración
    if (strtotime($reset['expiracion']) < time()) {
        respuestaJSON(['error' => 'El código ha expirado.'], 400);
    }

    // Generar token temporal para permitir el cambio
    $token_temporal = bin2hex(random_bytes(32));
    $expiracion_token = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $conn->prepare("
        UPDATE reset_passwords 
        SET token_temporal = :token, expiracion_token = :expiracion 
        WHERE id = :id
    ");
    $stmt->bindParam(':token', $token_temporal);
    $stmt->bindParam(':expiracion', $expiracion_token);
    $stmt->bindParam(':id', $reset['id']);
    $stmt->execute();

    respuestaJSON([
        'mensaje' => 'Código verificado correctamente.',
        'token_temporal' => $token_temporal,
        'usuario_id' => $reset['usuario_id']
    ]);

} catch (Exception $e) {
    respuestaJSON(['error' => 'Error interno: ' . $e->getMessage()], 500);
}
?>
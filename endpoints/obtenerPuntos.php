<?php
/**
 * @OA\Get(
 *     path="/api/obtenerPuntos",
 *     summary="Obtener puntos del usuario",
 *     description="Obtiene la cantidad de puntos actuales del usuario autenticado. Requiere autenticación Bearer Token.",
 *     tags={"Usuario"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Puntos obtenidos exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="puntos", type="integer", example=1500)
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
 *         description="Usuario no encontrado",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Usuario no encontrado")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error interno del servidor")
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');
require_once(__DIR__ . '/../auth/validarBearer.php');

try {
    $usuario = validarBearer();

    $stmt = $conn->prepare("SELECT puntos, nombre, email FROM usuarios WHERE id = :id");
    $stmt->execute(["id" => $usuario->id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        respuestaJSON(["error" => "Usuario no encontrado"], 404);
    }

    respuestaJSON([
        "puntos" => (int)$user['puntos']
    ]);

} catch (Exception $e) {
    respuestaJSON(["error" => "Error interno del servidor"], 500);
}
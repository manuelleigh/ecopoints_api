<?php
/**
 * @OA\Get(
 *     path="/api/obtenerHistorial",
 *     summary="Obtener historial de transacciones del usuario",
 *     description="Obtiene el historial completo de transacciones (scan de QR y canjes) del usuario autenticado. Requiere autenticación Bearer Token.",
 *     tags={"Historial"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Historial obtenido exitosamente",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="type", type="string", enum={"scan", "redeem"}, example="scan"),
 *                 @OA\Property(property="description", type="string", example="Reciclado de botella"),
 *                 @OA\Property(property="location", type="string", example="Ecopoints Piura"),
 *                 @OA\Property(property="points", type="integer", example=5),
 *                 @OA\Property(property="date", type="string", format="date-time", example="2024-01-15 10:30:00"),
 *                 @OA\Property(property="extra", type="object",
 *                     oneOf={
 *                         @OA\Schema(
 *                             @OA\Property(property="codigo_qr", type="string", example="QR123ABC456DEF")
 *                         ),
 *                         @OA\Schema(
 *                             @OA\Property(property="convenio", type="string", example="20% de descuento en productos ecológicos")
 *                         )
 *                     }
 *                 )
 *             )
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
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error al obtener el historial: Mensaje de error específico")
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');
require_once(__DIR__ . '/../auth/validarBearer.php');

$usuario = validarBearer();
$usuario_id = $usuario->id;

try {
    $stmt = $conn->prepare("
        SELECT 
            t.id,
            t.tipo,
            t.puntos,
            t.fecha,
            cq.codigo AS codigo_qr,
            c.titulo AS convenio
        FROM transacciones t
        LEFT JOIN codigos_qr cq ON t.codigo_qr_id = cq.id
        LEFT JOIN canjes ca ON t.canje_id = ca.id
        LEFT JOIN convenios c ON ca.convenio_id = c.id
        WHERE t.usuario_id = :usuario
        ORDER BY t.fecha DESC
    ");

    $stmt->bindParam(':usuario', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();

    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reformatear la respuesta según el tipo de transacción
    $resultado = [];
    foreach ($historial as $fila) {
        if ($fila['tipo'] === 'scan') {
            $descripcion = 'Reciclado de botella';
            $ubicacion = 'Ecopoints Piura';
        } else {
            $descripcion = $fila['convenio'] 
                ? $fila['convenio'] 
                : 'Canje';
            $ubicacion = 'Centro de canje Ecopoints';
        }

        $resultado[] = [
            'id' => (int)$fila['id'],
            'type' => $fila['tipo'],
            'description' => $descripcion,
            'location' => $ubicacion,
            'points' => (int)$fila['puntos'],
            'date' => $fila['fecha'],
            'extra' => $fila['tipo'] === 'scan' 
                ? ['codigo_qr' => $fila['codigo_qr']] 
                : ['convenio' => $fila['convenio']]
        ];
    }

    respuestaJSON($resultado);

} catch (PDOException $e) {
    respuestaJSON(['error' => 'Error al obtener el historial: ' . $e->getMessage()], 500);
}
?>

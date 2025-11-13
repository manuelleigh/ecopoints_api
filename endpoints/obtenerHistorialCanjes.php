<?php
/**
 * @OA\Get(
 *     path="/api/obtenerHistorialCanjes",
 *     summary="Obtener historial de canjes del usuario",
 *     description="Obtiene el historial específico de canjes realizados por el usuario autenticado. Incluye información detallada de cada convenio canjeado. Requiere autenticación Bearer Token.",
 *     tags={"Historial"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Historial de canjes obtenido exitosamente",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="convenio", type="string", example="20% de descuento en productos ecológicos"),
 *                 @OA\Property(property="empresa", type="string", example="EcoStore"),
 *                 @OA\Property(property="codigo", type="string", nullable=true, example="DESC2024ABC123"),
 *                 @OA\Property(property="puntos_usados", type="integer", example=500),
 *                 @OA\Property(property="estado", type="string", enum={"PENDIENTE", "ENTREGADO", "CANCELADO"}, example="ENTREGADO"),
 *                 @OA\Property(property="fecha", type="string", format="date-time", example="2024-01-15 10:30:00"),
 *                 @OA\Property(property="imagen_url", type="string", nullable=true, example="https://ecopoints.hvd.lat/data/img/convenio_1.jpg"),
 *                 @OA\Property(property="logo_url", type="string", nullable=true, example="https://ecopoints.hvd.lat/data/logos/ecostore.png")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Usuario no válido",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Usuario no válido.")
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
 *             @OA\Property(property="error", type="string", example="Error al obtener historial de canjes: Mensaje de error específico")
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');
require_once(__DIR__ . '/../auth/validarBearer.php');

$usuario = validarBearer();
$usuario_id = $usuario->id;

if ($usuario_id <= 0) {
    respuestaJSON(['error' => 'Usuario no válido.'], 400);
}

try {
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            cv.titulo AS convenio,
            e.nombre AS empresa,
            c.puntos_usados,
            c.estado,
            c.fecha,
            cv.imagen_url,
            e.logo_url,
            cc.codigo AS codigo_canje
        FROM canjes c
        INNER JOIN convenios cv ON c.convenio_id = cv.id
        INNER JOIN empresas e ON cv.empresa_id = e.id
        LEFT JOIN codigos_convenio cc ON c.codigo_convenio_id = cc.id
        WHERE c.usuario_id = :usuario_id
        ORDER BY c.fecha DESC
    ");
    
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();

    $base_url = "https://ecopoints.hvd.lat/data/";

    $historial = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['imagen_url'] = !empty($row['imagen_url']) 
            ? $base_url . "img/" . $row['imagen_url'] 
            : null;
            
        $row['logo_url'] = !empty($row['logo_url']) 
            ? $base_url . "logos/" . $row['logo_url'] 
            : null;

        $historial[] = [
            'id' => $row['id'],
            'convenio' => $row['convenio'],
            'empresa' => $row['empresa'],
            'codigo' => $row['codigo_canje'] ?? null,
            'puntos_usados' => (int)$row['puntos_usados'],
            'estado' => $row['estado'],
            'fecha' => $row['fecha'],
            'imagen_url' => $row['imagen_url'],
            'logo_url' => $row['logo_url']
        ];
    }

    respuestaJSON($historial);

} catch (Exception $e) {
    respuestaJSON(['error' => 'Error al obtener historial de canjes: ' . $e->getMessage()], 500);
}
?>

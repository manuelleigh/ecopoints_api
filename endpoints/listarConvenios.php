<?php
/**
 * @OA\Get(
 *     path="/api/listarConvenios",
 *     summary="Listar convenios disponibles",
 *     description="Obtiene la lista de todos los convenios activos disponibles para canje. Requiere autenticación Bearer Token.",
 *     tags={"Convenios"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de convenios obtenida exitosamente",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="titulo", type="string", example="20% de descuento en productos ecológicos"),
 *                 @OA\Property(property="descripcion", type="string", example="Descuento especial en toda la tienda online"),
 *                 @OA\Property(property="puntos_requeridos", type="integer", example=500),
 *                 @OA\Property(property="stock", type="integer", example=25),
 *                 @OA\Property(property="empresa", type="string", example="EcoStore"),
 *                 @OA\Property(property="imagen_url", type="string", nullable=true, example="https://ecopoints.hvd.lat/data/img/convenio_1.jpg"),
 *                 @OA\Property(property="logo_url", type="string", nullable=true, example="https://ecopoints.hvd.lat/data/logos/ecostore.png"),
 *                 @OA\Property(property="descuento", type="integer", example=20)
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
 *             @OA\Property(property="error", type="string", example="Error al obtener convenios: Mensaje de error específico")
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');
require_once(__DIR__ . '/../auth/validarBearer.php');

$usuario = validarBearer();

$base_url = "https://ecopoints.hvd.lat/data/";

try {
    $stmt = $conn->query("
        SELECT c.id, c.titulo, c.descripcion, c.puntos_requeridos, c.stock, 
               e.nombre AS empresa, c.imagen_url, e.logo_url
        FROM convenios c
        JOIN empresas e ON c.empresa_id = e.id
        WHERE c.activo = 1 AND e.activo = 1
    ");

    $convenios = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['imagen_url'])) {
            $row['imagen_url'] = $base_url . "img/" . $row['imagen_url'];
        } else {
            $row['imagen_url'] = null;
        }
        if (!empty($row['logo_url'])) {
            $row['logo_url'] = $base_url . "logos/" . $row['logo_url'];
        } else {
            $row['logo_url'] = null;
        }
        $row['descuento'] = 20;

        $convenios[] = $row;
    }

    respuestaJSON($convenios);

} catch (Exception $e) {
    respuestaJSON(['error' => 'Error al obtener convenios: ' . $e->getMessage()], 500);
}
?>

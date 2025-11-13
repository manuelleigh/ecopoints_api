<?php
/**
 * @OA\Post(
 *     path="/api/generarQR",
 *     summary="Generar código QR para reciclaje",
 *     description="Genera un código QR único para dispositivos autorizados que representa un reciclaje de botellas",
 *     tags={"Códigos QR"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos para generar el código QR",
 *         @OA\JsonContent(
 *             required={"dispositivo_id"},
 *             @OA\Property(
 *                 property="dispositivo_id",
 *                 type="string",
 *                 description="ID único del dispositivo autorizado para generar QR",
 *                 example="disp_123456789abc"
 *             ),
 *             @OA\Property(
 *                 property="botellas_recicladas",
 *                 type="integer",
 *                 description="Número de botellas recicladas (opcional, default: 1)",
 *                 example=5,
 *                 minimum=1
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Código QR generado exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="codigo_qr", type="string", example="QR123ABC456DEF")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Solicitud incorrecta",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Falta dispositivo_id")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Dispositivo no autorizado",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Dispositivo no autorizado")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error al generar código QR")
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['dispositivo_id'])) {
    respuestaJSON(["error" => "Falta dispositivo_id"], 400);
} if (!dispositivoValido($data['dispositivo_id'])) {
    respuestaJSON(["error" => "Dispositivo no autorizado"], 403);
}

$codigo = generarCodigoUnico(12);
$stmt = $conn->prepare("
    INSERT INTO codigos_qr (codigo, dispositivo_id, botellas_recicladas, valor_puntos)
    VALUES (:codigo, :dispositivo_id, :botellas, :valor)
");
$stmt->execute([
    "codigo" => $codigo,
    "dispositivo_id" => $data['dispositivo_id'],
    "botellas" => $data['botellas_recicladas'] ?? 1,
    "valor" => 5
]);


respuestaJSON(["codigo_qr" => $codigo]);
?>

<?php
require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * @OA\Info(
 *     title="EcoPoints API",
 *     version="1.0.0",
 *     description="API para el sistema de puntos ecológicos EcoPoints",
 *     @OA\Contact(
 *         email="soporte@ecopoints.hvd.lat",
 *         name="Soporte EcoPoints"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="https://ecopoints.hvd.lat",
 *     description="Servidor de producción"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost/ecopoints",
 *     description="Servidor de desarrollo"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Usar el token JWT obtenido en el login"
 * )
 * 
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints para registro, login y gestión de contraseñas"
 * )
 * @OA\Tag(
 *     name="Usuario",
 *     description="Endpoints relacionados con el usuario autenticado"
 * )
 * @OA\Tag(
 *     name="Códigos QR",
 *     description="Endpoints para generar y validar códigos QR de reciclaje"
 * )
 * @OA\Tag(
 *     name="Convenios",
 *     description="Endpoints para listar y canjear convenios"
 * )
 * @OA\Tag(
 *     name="Convenios - Admin",
 *     description="Endpoints administrativos para gestionar convenios"
 * )
 * @OA\Tag(
 *     name="Empresas - Admin",
 *     description="Endpoints administrativos para gestionar empresas"
 * )
 * @OA\Tag(
 *     name="Historial",
 *     description="Endpoints para consultar historial de transacciones y canjes"
 * )
 * @OA\Tag(
 *     name="Canjes",
 *     description="Endpoints para realizar canjes de puntos"
 * )
 */

header('Content-Type: application/json');

try {
    // 🔥 CORREGIDO: Escanear la carpeta endpoints
    $openapi = \OpenApi\Generator::scan([
        __DIR__ . '/../endpoints',
        __DIR__ . '/../auth'
    ]);

    if (count($openapi->paths) === 0) {
        throw new Exception('No se encontraron endpoints documentados. Verifica que:
        1. Los archivos estén en /endpoints/
        2. Las anotaciones Swagger estén inmediatamente después de <?php
        3. No haya espacios o código antes de las anotaciones');
    }

    echo $openapi->toJson();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'paths_scanned' => [
            realpath(__DIR__ . '/../endpoints'),
            realpath(__DIR__ . '/../auth')
        ]
    ]);
}
?>
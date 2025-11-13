<?php
/**
 * @OA\Post(
 *     path="/api/registrarConvenio",
 *     summary="Registrar nuevo convenio",
 *     description="Crea un nuevo convenio con imagen en base64, códigos de canje y toda la información necesaria. Endpoint para administradores.",
 *     tags={"Convenios - Admin"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos del convenio a registrar",
 *         @OA\JsonContent(
 *             required={"empresa_id", "titulo", "descripcion", "puntos_requeridos", "tipo_entrega", "imagen"},
 *             @OA\Property(property="empresa_id", type="integer", description="ID de la empresa", example=1),
 *             @OA\Property(property="titulo", type="string", description="Título del convenio", example="20% de descuento en productos ecológicos"),
 *             @OA\Property(property="descripcion", type="string", description="Descripción detallada del convenio", example="Descuento especial en toda la tienda online por tiempo limitado"),
 *             @OA\Property(property="puntos_requeridos", type="integer", description="Puntos necesarios para canjear", example=500, minimum=1),
 *             @OA\Property(property="tipo_entrega", type="string", enum={"CODIGO", "URL"}, description="Tipo de entrega del beneficio", example="CODIGO"),
 *             @OA\Property(property="base_url", type="string", nullable=true, description="URL base para tipo URL (requerido si tipo_entrega es URL)", example="https://tienda.com/cupones/"),
 *             @OA\Property(property="stock", type="integer", description="Stock disponible (opcional)", example=100, minimum=0),
 *             @OA\Property(property="imagen", type="string", format="base64", description="Imagen del convenio en base64 (PNG, JPG, JPEG, WEBP)", example="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=="),
 *             @OA\Property(property="codigos", type="array", description="Lista de códigos para el convenio (opcional)", 
 *                 @OA\Items(type="string", example="DESC2024ABC123")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Convenio registrado exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="mensaje", type="string", example="Convenio registrado con éxito"),
 *             @OA\Property(property="convenio_id", type="integer", example=15),
 *             @OA\Property(property="imagen_guardada", type="string", example="convenio_6789abc12345.png"),
 *             @OA\Property(property="codigos_registrados", type="integer", example=5)
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Solicitud incorrecta",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Campo requerido faltante: titulo")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="El campo tipo_entrega debe ser 'CODIGO' o 'URL'")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="El campo puntos_requeridos debe ser un número positivo")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Debe proporcionar una imagen válida en formato Base64")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Formato de imagen no permitido. Usa PNG, JPG o WEBP.")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Debe proporcionar una base_url válida para tipo_entrega 'URL'")
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Empresa no encontrada",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="La empresa especificada no existe o está inactiva")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="No se pudo guardar la imagen del logo")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Error en la base de datos: Mensaje de error específico")
 *                 )
 *             }
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

// =========================
// VALIDACIONES INICIALES
// =========================
$camposRequeridos = ['empresa_id', 'titulo', 'descripcion', 'puntos_requeridos', 'tipo_entrega'];
foreach ($camposRequeridos as $campo) {
    if (empty($data[$campo])) {
        respuestaJSON(["error" => "Campo requerido faltante: $campo"], 400);
    }
}

$tiposValidos = ['CODIGO', 'URL'];
if (!in_array(strtoupper($data['tipo_entrega']), $tiposValidos)) {
    respuestaJSON(["error" => "El campo tipo_entrega debe ser 'CODIGO' o 'URL'"], 400);
}

if (!is_numeric($data['puntos_requeridos']) || intval($data['puntos_requeridos']) <= 0) {
    respuestaJSON(["error" => "El campo puntos_requeridos debe ser un número positivo"], 400);
}

if (empty($data['imagen']) || !preg_match('/^data:image\/(\w+);base64,/', $data['imagen'], $tipo)) {
    respuestaJSON(["error" => "Debe proporcionar una imagen válida en formato Base64"], 400);
}

// ======================
// PROCESAR LA IMAGEN
// ======================
$extension = strtolower($tipo[1]);
if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp'])) {
    respuestaJSON(["error" => "Formato de imagen no permitido. Usa PNG, JPG o WEBP."], 400);
}

$imagenBase64 = substr($data['imagen'], strpos($data['imagen'], ',') + 1);
$imagenDecodificada = base64_decode($imagenBase64);

if ($imagenDecodificada === false) {
    respuestaJSON(["error" => "Error al decodificar la imagen Base64"], 400);
}

$directorio = __DIR__ . '/../data/img/';
if (!is_dir($directorio)) {
    mkdir($directorio, 0775, true);
}

$nombreImagen = uniqid('convenio_', true) . '.' . $extension;
$rutaImagen = $directorio . $nombreImagen;

if (file_put_contents($rutaImagen, $imagenDecodificada) === false) {
    respuestaJSON(["error" => "No se pudo guardar la imagen del logo"], 500);
}

// ===========================
// GUARDAR DATOS EN LA BD
// ===========================
try {
    $conn->beginTransaction();

    // Verificar empresa
    $stmtCheckEmpresa = $conn->prepare("SELECT id FROM empresas WHERE id = :id AND activo = TRUE");
    $stmtCheckEmpresa->execute(['id' => intval($data['empresa_id'])]);
    $empresa = $stmtCheckEmpresa->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        $conn->rollBack();
        respuestaJSON(["error" => "La empresa especificada no existe o está inactiva"], 404);
    }

    $base_url = null;
    if (strtoupper($data['tipo_entrega']) === 'URL') {
        if (empty($data['base_url']) || !filter_var($data['base_url'], FILTER_VALIDATE_URL)) {
            $conn->rollBack();
            respuestaJSON(["error" => "Debe proporcionar una base_url válida para tipo_entrega 'URL'"], 400);
        }
        $base_url = $data['base_url'];
    }

    $stock = isset($data['stock']) ? intval($data['stock']) : 0;

    // Insertar convenio
    $stmtConvenio = $conn->prepare("
        INSERT INTO convenios (
            empresa_id, titulo, descripcion, puntos_requeridos,
            tipo_entrega, base_url, stock, activo, imagen_url
        ) VALUES (
            :empresa_id, :titulo, :descripcion, :puntos_requeridos,
            :tipo_entrega, :base_url, :stock, TRUE, :imagen_url
        )
    ");
    $stmtConvenio->execute([
        'empresa_id' => intval($data['empresa_id']),
        'titulo' => trim($data['titulo']),
        'descripcion' => trim($data['descripcion']),
        'puntos_requeridos' => intval($data['puntos_requeridos']),
        'tipo_entrega' => strtoupper($data['tipo_entrega']),
        'base_url' => $base_url,
        'stock' => $stock,
        'imagen_url' => $nombreImagen
    ]);

    $convenio_id = $conn->lastInsertId();

    // Insertar códigos si existen
    if (!empty($data['codigos']) && is_array($data['codigos'])) {
        $stmtCodigo = $conn->prepare("
            INSERT INTO codigos_convenio (convenio_id, codigo, usado)
            VALUES (:convenio_id, :codigo, FALSE)
        ");
        foreach ($data['codigos'] as $codigo) {
            $stmtCodigo->execute([
                'convenio_id' => $convenio_id,
                'codigo' => trim($codigo)
            ]);
        }

        // Actualizar stock según cantidad de códigos
        $stmtStock = $conn->prepare("UPDATE convenios SET stock = :stock WHERE id = :id");
        $stmtStock->execute([
            'stock' => count($data['codigos']),
            'id' => $convenio_id
        ]);
    }

    $conn->commit();

    respuestaJSON([
        "mensaje" => "Convenio registrado con éxito",
        "convenio_id" => $convenio_id,
        "imagen_guardada" => $nombreImagen,
        "codigos_registrados" => isset($data['codigos']) ? count($data['codigos']) : 0
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    respuestaJSON(["error" => "Error en la base de datos: " . $e->getMessage()], 500);
}
?>

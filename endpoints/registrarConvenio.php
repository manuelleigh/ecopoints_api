<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

// =========================
// 🔹 VALIDACIONES INICIALES
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
// 🔹 PROCESAR LA IMAGEN
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
// 🔹 GUARDAR DATOS EN LA BD
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

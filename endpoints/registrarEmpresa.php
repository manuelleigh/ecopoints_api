<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

// =========================
// VALIDACIONES INICIALES
// =========================
$camposRequeridos = ['nombre'];
foreach ($camposRequeridos as $campo) {
    if (empty($data[$campo])) {
        respuestaJSON(["error" => "Campo requerido faltante: $campo"], 400);
    }
}

$nombre = trim($data['nombre']);
$descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : null;
$web_url = isset($data['web_url']) ? trim($data['web_url']) : null;
$logo_base64 = $data['logo'] ?? null;
$logo_url = null;

// ========================
// VALIDAR DUPLICADOS
// ========================
try {
    $stmt = $conn->prepare("SELECT id FROM empresas WHERE nombre = :nombre");
    $stmt->execute(['nombre' => $nombre]);
    if ($stmt->fetch()) {
        respuestaJSON(["error" => "Ya existe una empresa registrada con ese nombre"], 409);
    }
} catch (PDOException $e) {
    respuestaJSON(["error" => "Error al verificar duplicados: " . $e->getMessage()], 500);
}

// =========================
// VALIDAR LOGO (OPCIONAL)
// =========================
if (!empty($logo_base64)) {
    if (!preg_match('/^data:image\/(\w+);base64,/', $logo_base64, $tipo)) {
        respuestaJSON(["error" => "Formato de imagen no válido para el logo"], 400);
    }

    $extension = strtolower($tipo[1]);
    if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp'])) {
        respuestaJSON(["error" => "Formato de logo no permitido. Usa PNG, JPG o WEBP."], 400);
    }

    $imagenBase64 = substr($logo_base64, strpos($logo_base64, ',') + 1);
    $imagenDecodificada = base64_decode($imagenBase64);

    if ($imagenDecodificada === false) {
        respuestaJSON(["error" => "Error al decodificar la imagen del logo"], 400);
    }

    // Crear carpeta si no existe
    $directorio = __DIR__ . '/../data/logos/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0775, true);
    }

    // Guardar logo
    $nombreLogo = uniqid('logo_', true) . '.' . $extension;
    $rutaLogo = $directorio . $nombreLogo;

    if (file_put_contents($rutaLogo, $imagenDecodificada) === false) {
        respuestaJSON(["error" => "No se pudo guardar la imagen del logo"], 500);
    }

    $logo_url = $nombreLogo;
}

// ======================
// GUARDAR EN LA BD
// ======================
try {
    $stmtInsert = $conn->prepare("
        INSERT INTO empresas (nombre, descripcion, logo_url, web_url, activo)
        VALUES (:nombre, :descripcion, :logo_url, :web_url, TRUE)
    ");

    $stmtInsert->execute([
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'logo_url' => $logo_url,
        'web_url' => $web_url
    ]);

    $empresa_id = $conn->lastInsertId();

    respuestaJSON([
        "mensaje" => "Empresa registrada con éxito",
        "empresa_id" => $empresa_id,
        "logo_guardado" => $logo_url
    ]);
} catch (PDOException $e) {
    respuestaJSON(["error" => "Error al registrar la empresa: " . $e->getMessage()], 500);
}
?>

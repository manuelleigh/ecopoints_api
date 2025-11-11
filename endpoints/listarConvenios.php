<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

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

<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$stmt = $conn->query("
    SELECT c.id, c.titulo, c.descripcion, c.puntos_requeridos, c.stock, 
           e.nombre AS empresa, e.logo_url
    FROM convenios c
    JOIN empresas e ON c.empresa_id = e.id
    WHERE c.activo = 1 AND e.activo = 1
");
$convenios = $stmt->fetchAll(PDO::FETCH_ASSOC);

respuestaJSON($convenios);
?>

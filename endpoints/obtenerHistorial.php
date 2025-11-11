<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;

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

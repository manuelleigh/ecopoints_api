<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;

try {
    $stmt = $conn->prepare("
        SELECT id, puntos, fecha 
        FROM transacciones 
        WHERE usuario_id = :usuario 
        ORDER BY fecha DESC
    ");

    $stmt->bindParam(':usuario', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();

    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reformatear los datos al nuevo esquema
    $resultado = [];
    foreach ($historial as $fila) {
        $resultado[] = [
            'id' => $fila['id'],
            'type' => 'scan',
            'description' => 'Reciclado de Botella',
            'location' => 'Ecopoints Piura',
            'points' => (int)$fila['puntos'],
            'date' => $fila['fecha']
        ];
    }

    respuestaJSON($resultado);
} catch (PDOException $e) {
    respuestaJSON(['error' => 'Error al obtener el historial: ' . $e->getMessage()]);
}
?>

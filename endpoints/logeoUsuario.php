<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

// Verificar si los datos requeridos están presentes
if (empty($data['email']) || empty($data['password'])) {
    respuestaJSON(["error" => "Datos incompletos"], 400);
}

// Buscar al usuario por su email
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email");
$stmt->bindParam(':email', $data['email']);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar credenciales
if (!$usuario || !password_verify($data['password'], $usuario['password_hash'])) 
{
    respuestaJSON(["error" => "Credenciales inválidas"], 401);
}

// Si todo está correcto
respuestaJSON([
    "id" => $usuario['id'],
    "mensaje" => "Inicio de sesión exitoso"
]);
?>

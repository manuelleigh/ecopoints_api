<?php
/**
 * @OA\Post(
 *     path="/api/logeoUsuario",
 *     summary="Iniciar sesión de usuario",
 *     description="Autentica un usuario con email y contraseña, devuelve información del usuario y token JWT para autenticación en endpoints protegidos",
 *     tags={"Autenticación"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Credenciales de acceso",
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(
 *                 property="email",
 *                 type="string",
 *                 format="email",
 *                 description="Email del usuario registrado",
 *                 example="usuario@ejemplo.com"
 *             ),
 *             @OA\Property(
 *                 property="password",
 *                 type="string",
 *                 format="password",
 *                 description="Contraseña del usuario",
 *                 example="miContraseñaSegura123"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Inicio de sesión exitoso",
 *         @OA\JsonContent(
 *             @OA\Property(property="usuario", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="nombre", type="string", example="Juan Pérez"),
 *                 @OA\Property(property="correo", type="string", example="juan@ejemplo.com")
 *             ),
 *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NjMwMDczNDksImV4cCI6MTc2MzAyMTc0OSwiZGF0YSI6eyJpZCI6IjEiLCJub21icmUiOiJKdWFuIFBlcsOpeiIsImVtYWlsIjoianVhbkBlamVtcGxvLmNvbSJ9fQ.example_token_here"),
 *             @OA\Property(property="mensaje", type="string", example="Inicio de sesión exitoso")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Datos incompletos",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Datos incompletos")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Credenciales inválidas",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Credenciales inválidas")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error en el proceso de autenticación")
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');
require_once(__DIR__ . '/../auth/Token.php');

$data = json_decode(file_get_contents("php://input"), true);

// Validar datos
if (empty($data['email']) || empty($data['password'])) {
    respuestaJSON(["error" => "Datos incompletos"], 400);
}

// Buscar usuario
$stmt = $conn->prepare("SELECT id, nombre, email, password_hash FROM usuarios WHERE email = :email");
$stmt->bindParam(':email', $data['email']);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($data['password'], $usuario['password_hash'])) {
    respuestaJSON(["error" => "Credenciales inválidas"], 401);
}

$token = Token::crearToken([
    "id" => $usuario['id'],
    "nombre" => $usuario['nombre'],
    "email" => $usuario['email']
]);

respuestaJSON([
    "usuario" => [
        "id" => $usuario['id'],
        "nombre" => $usuario['nombre'],
        "correo" => $usuario['email']
    ],
    "token" => $token,
    "mensaje" => "Inicio de sesión exitoso"
]);
?>

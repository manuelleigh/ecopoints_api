<?php
/**
 * @OA\Post(
 *     path="/api/registrarUsuario",
 *     summary="Registrar nuevo usuario",
 *     description="Crea una nueva cuenta de usuario en el sistema EcoPoints",
 *     tags={"Autenticación"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos del usuario a registrar",
 *         @OA\JsonContent(
 *             required={"nombre", "email", "password"},
 *             @OA\Property(
 *                 property="nombre",
 *                 type="string",
 *                 description="Nombre completo del usuario",
 *                 example="Juan Pérez"
 *             ),
 *             @OA\Property(
 *                 property="email",
 *                 type="string",
 *                 format="email",
 *                 description="Email del usuario",
 *                 example="juan@ejemplo.com"
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
 *         description="Usuario registrado exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="mensaje", type="string", example="Usuario registrado con éxito")
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
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error al registrar el usuario")
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../core/funciones.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['nombre'], $data['email'], $data['password'])) {
    respuestaJSON(["error" => "Datos incompletos"], 400);
}

$hash = password_hash($data['password'], PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password_hash) VALUES (:nombre, :email, :hash)");
$stmt->execute(["nombre" => $data['nombre'], "email" => $data['email'], "hash" => $hash]);

respuestaJSON(["mensaje" => "Usuario registrado con éxito"]);
?>

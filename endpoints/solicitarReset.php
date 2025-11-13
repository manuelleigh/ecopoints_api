<?php
/**
 * @OA\Post(
 *     path="/api/solicitarReset",
 *     summary="Solicitar código de verificación para reset de contraseña",
 *     description="Envía un código de verificación de 6 dígitos al email del usuario para permitir el restablecimiento de contraseña. El código expira en 15 minutos.",
 *     tags={"Autenticación"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Email del usuario para enviar código de verificación",
 *         @OA\JsonContent(
 *             required={"email"},
 *             @OA\Property(
 *                 property="email",
 *                 type="string",
 *                 format="email",
 *                 description="Email del usuario registrado",
 *                 example="usuario@ejemplo.com"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Código enviado exitosamente o email no existe (por seguridad)",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(
 *                     @OA\Property(property="mensaje", type="string", example="Código de verificación enviado a tu email.")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="mensaje", type="string", example="Si el email existe, recibirás un código de verificación.")
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Email inválido",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Email inválido.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Error interno con el email del usuario.")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Error al enviar el email. Intenta nuevamente.")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="error", type="string", example="Error interno: Mensaje de error específico")
 *                 )
 *             }
 *         )
 *     )
 * )
 */
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../config/smtp.php');
require_once(__DIR__ . '/../core/funciones.php');
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';

error_log("Email recibido en input: " . ($email ?: "VACÍO"));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respuestaJSON(['error' => 'Email inválido.'], 400);
}

try {
    $stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE email = :email AND estado = 'ACTIVO'");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Usuario encontrado: " . ($usuario ? $usuario['email'] : "NO ENCONTRADO"));

    if (!$usuario) {
        respuestaJSON(['mensaje' => 'Si el email existe, recibirás un código de verificación.'], 200);
    }

    if (empty($usuario['email'])) {
        error_log("ERROR: Email vacío en base de datos para usuario ID: " . $usuario['id']);
        respuestaJSON(['error' => 'Error interno con el email del usuario.'], 500);
    }

    $codigo = sprintf("%06d", random_int(1, 999999));
    $expiracion = date('Y-m-d H:i:s', time() + (15 * 60));

    $stmt = $conn->prepare("
        INSERT INTO reset_passwords (usuario_id, codigo, expiracion, utilizado) 
        VALUES (:usuario_id, :codigo, :expiracion, 0)
        ON DUPLICATE KEY UPDATE 
            codigo = VALUES(codigo), 
            expiracion = VALUES(expiracion), 
            utilizado = 0,
            token_temporal = NULL
    ");
    $stmt->execute([
        ':usuario_id' => $usuario['id'],
        ':codigo' => $codigo,
        ':expiracion' => $expiracion
    ]);

    error_log("Solicitud reset - Email: {$usuario['email']}, Código: $codigo");

    $enviado = enviarEmailCodigo($usuario['email'], $usuario['nombre'], $codigo);

    if ($enviado) {
        respuestaJSON(['mensaje' => 'Código de verificación enviado a tu email.'], 200);
    } else {
        $stmt = $conn->prepare("DELETE FROM reset_passwords WHERE usuario_id = :usuario_id AND codigo = :codigo");
        $stmt->execute([':usuario_id' => $usuario['id'], ':codigo' => $codigo]);
        
        respuestaJSON(['error' => 'Error al enviar el email. Intenta nuevamente.'], 500);
    }

} catch (Exception $e) {
    error_log("Exception en solicitar_reset: " . $e->getMessage());
    respuestaJSON(['error' => 'Error interno: ' . $e->getMessage()], 500);
}

function enviarEmailCodigo($email, $nombre, $codigo) {
    if (empty($email)) {
        error_log("ERROR: Email vacío pasado a enviarEmailCodigo");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        error_log("Iniciando PHPMailer para: $email");
        
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 30;
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $nombre);
        
        $mail->isHTML(true);
        $mail->Subject = 'Código de Verificación - EcoPoints';
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #2E7D32; color: white; padding: 20px; text-align: center; }
                    .code { font-size: 32px; font-weight: bold; color: #2E7D32; text-align: center; margin: 20px 0; }
                    .footer { margin-top: 20px; padding: 20px; background: #f5f5f5; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>EcoPoints</h1>
                    </div>
                    <h2>Restablecimiento de Contraseña</h2>
                    <p>Hola <strong>$nombre</strong>,</p>
                    <p>Has solicitado restablecer tu contraseña. Usa el siguiente código de verificación:</p>
                    <div class='code'>$codigo</div>
                    <p>Este código expirará en <strong>15 minutos</strong>.</p>
                    <p>Si no solicitaste este cambio, puedes ignorar este mensaje de forma segura.</p>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " EcoPoints. Todos los derechos reservados.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Código de verificación EcoPoints: $codigo\n\nHola $nombre,\n\nHas solicitado restablecer tu contraseña. Usa el código anterior para continuar.\n\nEste código expirará en 15 minutos.\n\nSi no solicitaste este cambio, ignora este mensaje.";

        error_log("Intentando enviar email a: $email");
        $result = $mail->send();
        error_log("Resultado del envío: " . ($result ? "EXITOSO" : "FALLIDO"));
        
        return $result;
        
    } catch (Exception $e) {
        error_log("EXCEPCIÓN PHPMailer: " . $e->getMessage());
        return false;
    }
}
?>
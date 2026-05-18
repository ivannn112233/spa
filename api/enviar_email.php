<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';

$response = ['success' => false, 'message' => ''];

// Configuración SMTP (usa Gmail o cualquier servicio)
function enviarEmailSMTP($destinatario, $asunto, $mensaje_html) {
    // Usar PHPMailer si está instalado, o usar mail() de PHP
    
    // Opción 1: mail() de PHP (simple)
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    
    return @mail($destinatario, $asunto, $mensaje_html, $headers);
}

// Enviar código de verificación
if ($_GET['action'] == 'enviar_codigo') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $response['message'] = 'Email requerido';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Generar código de 6 dígitos
    $codigo = sprintf("%06d", mt_rand(1, 999999));
    $expiracion = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Guardar código en BD
    $stmt = $db->prepare("UPDATE usuarios SET token_activacion = ?, token_expiracion = ? WHERE email = ?");
    $stmt->execute([$codigo, $expiracion, $email]);
    
    // Crear mensaje HTML bonito
    $mensaje = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Código de verificación</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 500px; margin: 0 auto; padding: 20px; }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
            .code { font-size: 32px; letter-spacing: 5px; background: #f0f0f0; padding: 20px; text-align: center; font-weight: bold; margin: 20px 0; }
            .footer { font-size: 12px; color: #999; text-align: center; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🐾 " . SITE_NAME . "</h2>
            </div>
            <h3>Verificación de cuenta</h3>
            <p>Hola,</p>
            <p>Gracias por registrarte en " . SITE_NAME . ". Para activar tu cuenta, ingresa el siguiente código:</p>
            <div class='code'>$codigo</div>
            <p>Este código expirará en <strong>10 minutos</strong>.</p>
            <p>Si no solicitaste este registro, ignora este mensaje.</p>
            <div class='footer'>
                <p>" . SITE_NAME . " - Cuidado profesional para tu mascota</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    if (enviarEmailSMTP($email, "Código de verificación - " . SITE_NAME, $mensaje)) {
        $response['success'] = true;
        $response['message'] = 'Código enviado a tu correo';
        $response['codigo'] = $codigo; // Solo para pruebas, quitar en producción
    } else {
        $response['message'] = 'Error al enviar el correo. Verifica la configuración SMTP.';
    }
    
    echo json_encode($response);
    exit;
}
?>
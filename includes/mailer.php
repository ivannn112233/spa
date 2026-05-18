<?php
class Mailer {
    
    // Enviar correo usando la función mail() de PHP
    public static function send($to, $subject, $htmlMessage, $textMessage = '') {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
        
        return @mail($to, $subject, $htmlMessage, $headers);
    }
    
    // Enviar código de verificación
    public static function sendVerificationCode($to, $code) {
        $subject = "Código de verificación - " . SITE_NAME;
        
        $htmlMessage = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Código de verificación</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; }
                .container { max-width: 500px; margin: 0 auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px; }
                .code { font-size: 36px; letter-spacing: 8px; background: #f0f0f0; padding: 20px; text-align: center; font-weight: bold; border-radius: 10px; margin: 20px 0; font-family: monospace; }
                .footer { font-size: 12px; color: #999; text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
                .info { color: #e74c3c; font-size: 12px; text-align: center; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🐾 " . SITE_NAME . "</h2>
                </div>
                <h3>¡Hola!</h3>
                <p>Gracias por registrarte en " . SITE_NAME . ". Para activar tu cuenta, ingresa el siguiente código:</p>
                <div class='code'>$code</div>
                <p>Este código expirará en <strong>10 minutos</strong>.</p>
                <p>Si no solicitaste este registro, ignora este mensaje.</p>
                <div class='footer'>
                    <p>" . SITE_NAME . " - El mejor cuidado para tu mascota</p>
                    <p>© " . date('Y') . " Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $textMessage = "Tu código de verificación es: $code\n\nVálido por 10 minutos.\n\n" . SITE_NAME;
        
        return self::send($to, $subject, $htmlMessage, $textMessage);
    }
    
    // Enviar recordatorio de cita
    public static function sendAppointmentReminder($to, $nombre, $fecha, $servicio) {
        $subject = "Recordatorio de cita - " . SITE_NAME;
        
        $htmlMessage = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Recordatorio de cita</title>
        </head>
        <body>
            <div style='max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #2c3e50;'>📅 Recordatorio de Cita</h2>
                <p>Hola <strong>$nombre</strong>,</p>
                <p>Te recordamos que tienes una cita programada:</p>
                <ul>
                    <li><strong>Fecha:</strong> $fecha</li>
                    <li><strong>Servicio:</strong> $servicio</li>
                </ul>
                <p>¡Te esperamos!</p>
                <hr>
                <small>" . SITE_NAME . "</small>
            </div>
        </body>
        </html>
        ";
        
        return self::send($to, $subject, $htmlMessage);
    }
    
    // Enviar confirmación de cita
    public static function sendAppointmentConfirmation($to, $nombre, $fecha, $servicio, $groomer) {
        $subject = "Cita confirmada - " . SITE_NAME;
        
        $htmlMessage = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Cita confirmada</title>
        </head>
        <body>
            <div style='max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #27ae60;'>✅ Cita Confirmada</h2>
                <p>Hola <strong>$nombre</strong>,</p>
                <p>Tu cita ha sido confirmada exitosamente:</p>
                <ul>
                    <li><strong>📅 Fecha:</strong> $fecha</li>
                    <li><strong>✂️ Servicio:</strong> $servicio</li>
                    <li><strong>👨‍🦱 Groomer:</strong> $groomer</li>
                </ul>
                <p>¡Te esperamos!</p>
                <hr>
                <small>" . SITE_NAME . "</small>
            </div>
        </body>
        </html>
        ";
        
        return self::send($to, $subject, $htmlMessage);
    }
}
?>
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';
require_once '../includes/mailer.php';

$response = ['success' => false, 'message' => ''];

// Enviar código de verificación
if ($_GET['action'] == 'enviar_codigo') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $response['message'] = 'Email requerido';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verificar si el email existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $response['message'] = 'Email no registrado';
        echo json_encode($response);
        exit;
    }
    
    // Generar código aleatorio de 6 dígitos
    $codigo = sprintf("%06d", mt_rand(1, 999999));
    $expiracion = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    $stmt = $db->prepare("UPDATE usuarios SET token_activacion = ?, token_expiracion = ? WHERE email = ?");
    $stmt->execute([$codigo, $expiracion, $email]);
    
    if (Mailer::sendVerificationCode($email, $codigo)) {
        $response['success'] = true;
        $response['message'] = 'Código enviado a tu correo';
        // Solo para pruebas, quitar en producción
        $response['codigo'] = $codigo;
    } else {
        $response['message'] = 'Error al enviar el correo';
    }
    
    echo json_encode($response);
    exit;
}

// Verificar código
if ($_GET['action'] == 'verificar_codigo') {
    $email = $_POST['email'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    
    if (empty($email) || empty($codigo)) {
        $response['message'] = 'Email y código requeridos';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT id FROM usuarios 
        WHERE email = ? AND token_activacion = ? AND token_expiracion > NOW()
    ");
    $stmt->execute([$email, $codigo]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        $stmt = $db->prepare("UPDATE usuarios SET estado = 'activo', token_activacion = NULL, token_expiracion = NULL WHERE id = ?");
        $stmt->execute([$usuario['id']]);
        
        $response['success'] = true;
        $response['message'] = 'Cuenta verificada correctamente';
    } else {
        $response['message'] = 'Código inválido o expirado';
    }
    
    echo json_encode($response);
    exit;
}

// Reenviar código
if ($_GET['action'] == 'reenviar_codigo') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $response['message'] = 'Email requerido';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND estado = 'pendiente'");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $response['message'] = 'Usuario no encontrado o ya activado';
        echo json_encode($response);
        exit;
    }
    
    // Generar nuevo código
    $codigo = sprintf("%06d", mt_rand(1, 999999));
    $expiracion = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    $stmt = $db->prepare("UPDATE usuarios SET token_activacion = ?, token_expiracion = ? WHERE email = ?");
    $stmt->execute([$codigo, $expiracion, $email]);
    
    if (Mailer::sendVerificationCode($email, $codigo)) {
        $response['success'] = true;
        $response['message'] = 'Código reenviado a tu correo';
        $response['codigo'] = $codigo;
    } else {
        $response['message'] = 'Error al reenviar el código';
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode($response);
?>
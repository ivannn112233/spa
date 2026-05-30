<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';

$response = ['success' => false, 'message' => ''];
$action = $_GET['action'] ?? '';

// Enviar notificaciones pendientes (para CRON)
if($action == 'enviar_pendientes'){
    $db = Database::getInstance()->getConnection();
    
    // Obtener notificaciones pendientes
    $stmt = $db->prepare("
        SELECT n.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido, 
               c.email, c.telefono, c.canal_notificacion
        FROM notificaciones_programadas n
        JOIN clientes c ON n.cliente_id = c.id
        WHERE n.estado = 'pendiente' AND n.fecha_programada <= NOW()
        LIMIT 50
    ");
    $stmt->execute();
    $notificaciones = $stmt->fetchAll();
    
    $enviadas = 0;
    $fallidas = 0;
    
    foreach($notificaciones as $notif){
        $enviado = false;
        
        if($notif['canal'] == 'email'){
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
            $enviado = @mail($notif['destino'], "Notificación - " . SITE_NAME, nl2br($notif['mensaje']), $headers);
        } elseif($notif['canal'] == 'whatsapp'){
            $numero = $notif['destino'];
            $enviado = true; // Simular envío
            error_log("WhatsApp a {$numero}: {$notif['mensaje']}");
        }
        
        if($enviado){
            $stmt = $db->prepare("UPDATE notificaciones_programadas SET estado = 'enviada', fecha_enviada = NOW() WHERE id = ?");
            $stmt->execute([$notif['id']]);
            $enviadas++;
        } else {
            $stmt = $db->prepare("UPDATE notificaciones_programadas SET intentos = intentos + 1 WHERE id = ?");
            $stmt->execute([$notif['id']]);
            $fallidas++;
        }
    }
    
    $response['success'] = true;
    $response['message'] = "Procesadas: " . count($notificaciones) . " | Enviadas: $enviadas | Fallidas: $fallidas";
    echo json_encode($response);
    exit;
}

// Enviar notificación de cita confirmada
if($action == 'cita_confirmada'){
    $cita_id = $_POST['cita_id'] ?? 0;
    
    if(!$cita_id){
        $response['message'] = 'Cita ID requerido';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT c.*, cl.nombre, cl.apellido, cl.email, cl.telefono, cl.canal_notificacion,
               m.nombre as mascota, s.nombre as servicio
        FROM citas c
        JOIN clientes cl ON c.cliente_id = cl.id
        JOIN mascotas m ON c.mascota_id = m.id
        JOIN servicios s ON c.servicio_id = s.id
        WHERE c.id = ?
    ");
    $stmt->execute([$cita_id]);
    $cita = $stmt->fetch();
    
    if($cita){
        $mensaje = "¡Hola {$cita['nombre']}! Tu cita ha sido CONFIRMADA.\n\n";
        $mensaje .= "📅 Fecha: " . date('d/m/Y H:i', strtotime($cita['fecha_hora_inicio'])) . "\n";
        $mensaje .= "🐾 Mascota: {$cita['mascota']}\n";
        $mensaje .= "✂️ Servicio: {$cita['servicio']}\n\n";
        $mensaje .= "¡Te esperamos!";
        
        // Guardar en notificaciones
        $stmt = $db->prepare("
            INSERT INTO notificaciones_programadas 
            (cita_id, cliente_id, tipo_evento, canal, destino, mensaje, fecha_programada, estado)
            VALUES (?, ?, 'cita_confirmada', ?, ?, ?, NOW(), 'pendiente')
        ");
        $stmt->execute([$cita_id, $cita['cliente_id'], $cita['canal_notificacion'], $cita['email'], $mensaje]);
        
        $response['success'] = true;
        $response['message'] = 'Notificación programada';
    } else {
        $response['message'] = 'Cita no encontrada';
    }
    
    echo json_encode($response);
    exit;
}

// Enviar recordatorio 24 horas
if($action == 'recordatorio_24h'){
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT c.*, cl.nombre, cl.apellido, cl.email, cl.telefono, cl.canal_notificacion,
               m.nombre as mascota, s.nombre as servicio
        FROM citas c
        JOIN clientes cl ON c.cliente_id = cl.id
        JOIN mascotas m ON c.mascota_id = m.id
        JOIN servicios s ON c.servicio_id = s.id
        WHERE DATE(c.fecha_hora_inicio) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        AND c.estado IN ('confirmada', 'agendada')
    ");
    $stmt->execute();
    $citas = $stmt->fetchAll();
    
    foreach($citas as $cita){
        $mensaje = "📅 RECORDATORIO: Mañana tienes una cita en Pet Spa\n\n";
        $mensaje .= "📅 Fecha: " . date('d/m/Y H:i', strtotime($cita['fecha_hora_inicio'])) . "\n";
        $mensaje .= "🐾 Mascota: {$cita['mascota']}\n";
        $mensaje .= "✂️ Servicio: {$cita['servicio']}\n\n";
        $mensaje .= "¡Te esperamos!";
        
        $stmt2 = $db->prepare("
            INSERT INTO notificaciones_programadas 
            (cita_id, cliente_id, tipo_evento, canal, destino, mensaje, fecha_programada, estado)
            VALUES (?, ?, 'recordatorio_24h', ?, ?, ?, NOW(), 'pendiente')
        ");
        $stmt2->execute([$cita['id'], $cita['cliente_id'], $cita['canal_notificacion'], $cita['email'], $mensaje]);
    }
    
    $response['success'] = true;
    $response['message'] = count($citas) . ' recordatorios programados';
    echo json_encode($response);
    exit;
}

// Mascota lista para recoger
if($action == 'mascota_lista'){
    $cita_id = $_POST['cita_id'] ?? 0;
    
    if(!$cita_id){
        $response['message'] = 'Cita ID requerido';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT c.*, cl.nombre, cl.apellido, cl.email, cl.telefono, cl.canal_notificacion,
               m.nombre as mascota
        FROM citas c
        JOIN clientes cl ON c.cliente_id = cl.id
        JOIN mascotas m ON c.mascota_id = m.id
        WHERE c.id = ?
    ");
    $stmt->execute([$cita_id]);
    $cita = $stmt->fetch();
    
    if($cita){
        $mensaje = "🐾 ¡Hola {$cita['nombre']}! Tu mascota {$cita['mascota']} ya está LISTA para recoger.\n\n";
        $mensaje .= "Puedes pasar por ella en nuestro local. ¡Gracias por confiar en Pet Spa!";
        
        $stmt2 = $db->prepare("
            INSERT INTO notificaciones_programadas 
            (cita_id, cliente_id, tipo_evento, canal, destino, mensaje, fecha_programada, estado)
            VALUES (?, ?, 'mascota_lista', ?, ?, ?, NOW(), 'pendiente')
        ");
        $stmt2->execute([$cita_id, $cita['cliente_id'], $cita['canal_notificacion'], $cita['email'], $mensaje]);
        
        $response['success'] = true;
        $response['message'] = 'Notificación de mascota lista programada';
    } else {
        $response['message'] = 'Cita no encontrada';
    }
    
    echo json_encode($response);
    exit;
}

// Obtener notificaciones de un cliente
if($action == 'mis_notificaciones'){
    $cliente_id = $_POST['cliente_id'] ?? $_GET['cliente_id'] ?? 0;
    
    if(!$cliente_id && isset($_SESSION['user_id'])){
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cliente = $stmt->fetch();
        $cliente_id = $cliente['id'] ?? 0;
    }
    
    if($cliente_id){
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM notificaciones_programadas 
            WHERE cliente_id = ? 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$cliente_id]);
        $notificaciones = $stmt->fetchAll();
        
        $response['success'] = true;
        $response['notificaciones'] = $notificaciones;
    } else {
        $response['message'] = 'Cliente no identificado';
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode($response);
?>
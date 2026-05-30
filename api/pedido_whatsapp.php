<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';

$response = ['success' => false, 'message' => ''];
$action = $_GET['action'] ?? '';

// Generar mensaje para pedido
if($action == 'generar_mensaje'){
    $pedido_id = $_POST['pedido_id'] ?? 0;
    
    if(!$pedido_id){
        $response['message'] = 'Pedido ID requerido';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT p.*, c.nombre, c.apellido, c.telefono, c.email
        FROM pedidos p
        JOIN clientes c ON p.cliente_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();
    
    if(!$pedido){
        $response['message'] = 'Pedido no encontrado';
        echo json_encode($response);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT dp.*, pr.nombre as producto_nombre, pr.sku
        FROM detalle_pedido dp
        JOIN productos pr ON dp.producto_id = pr.id
        WHERE dp.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $items = $stmt->fetchAll();
    
    $mensaje = "🛍️ *NUEVO PEDIDO - " . SITE_NAME . "*\n\n";
    $mensaje .= "🧑 *Cliente:* " . $pedido['nombre'] . ' ' . $pedido['apellido'] . "\n";
    $mensaje .= "📞 *Teléfono:* " . ($pedido['telefono'] ?? 'No registrado') . "\n";
    $mensaje .= "📧 *Email:* " . $pedido['email'] . "\n\n";
    $mensaje .= "📦 *PRODUCTOS:*\n";
    $mensaje .= "─────────────────\n";
    
    foreach($items as $item){
        $mensaje .= "• " . $item['producto_nombre'];
        if($item['sku']) $mensaje .= " (" . $item['sku'] . ")";
        $mensaje .= "\n  Cantidad: " . $item['cantidad'] . " x Bs. " . number_format($item['precio_unitario'], 2);
        $mensaje .= " = Bs. " . number_format($item['subtotal'], 2) . "\n";
    }
    
    $mensaje .= "─────────────────\n";
    $mensaje .= "💰 *Subtotal:* Bs. " . number_format($pedido['subtotal'], 2) . "\n";
    if($pedido['descuento'] > 0){
        $mensaje .= "🎉 *Descuento:* -Bs. " . number_format($pedido['descuento'], 2) . "\n";
    }
    $mensaje .= "💵 *TOTAL:* Bs. " . number_format($pedido['total'], 2) . "\n\n";
    $mensaje .= "📅 *Fecha:* " . date('d/m/Y H:i', strtotime($pedido['created_at'])) . "\n";
    $mensaje .= "🆔 *Pedido N°:* " . str_pad($pedido['id'], 6, '0', STR_PAD_LEFT) . "\n\n";
    $mensaje .= "✅ *Para confirmar el pedido, responde a este mensaje.*\n";
    $mensaje .= "─────────────────\n";
    $mensaje .= SITE_NAME . " - El mejor cuidado para tu mascota";
    
    $response['success'] = true;
    $response['mensaje'] = $mensaje;
    $response['whatsapp_link'] = "https://wa.me/" . WHATSAPP_BUSINESS_NUMBER . "?text=" . urlencode($mensaje);
    
    echo json_encode($response);
    exit;
}

// Enviar pedido directamente
if($action == 'enviar_pedido'){
    $pedido_id = $_POST['pedido_id'] ?? 0;
    $numero_cliente = $_POST['numero'] ?? '';
    
    if(!$pedido_id){
        $response['message'] = 'Pedido ID requerido';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT p.*, c.nombre, c.apellido, c.telefono
        FROM pedidos p
        JOIN clientes c ON p.cliente_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();
    
    if(!$pedido){
        $response['message'] = 'Pedido no encontrado';
        echo json_encode($response);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT dp.*, pr.nombre as producto_nombre
        FROM detalle_pedido dp
        JOIN productos pr ON dp.producto_id = pr.id
        WHERE dp.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $items = $stmt->fetchAll();
    
    $mensaje = "🛍️ *NUEVO PEDIDO - " . SITE_NAME . "*\n\n";
    $mensaje .= "🧑 Cliente: " . $pedido['nombre'] . ' ' . $pedido['apellido'] . "\n";
    $mensaje .= "📞 Teléfono: " . ($pedido['telefono'] ?? '-') . "\n\n";
    $mensaje .= "📦 *PRODUCTOS:*\n";
    foreach($items as $item){
        $mensaje .= "• " . $item['producto_nombre'] . " x" . $item['cantidad'] . " - Bs. " . number_format($item['subtotal'], 2) . "\n";
    }
    $mensaje .= "\n💰 *Total: Bs. " . number_format($pedido['total'], 2) . "*\n";
    $mensaje .= "\n📅 Fecha: " . date('d/m/Y H:i') . "\n";
    
    $numero_destino = !empty($numero_cliente) ? $numero_cliente : WHATSAPP_BUSINESS_NUMBER;
    $whatsapp_link = "https://wa.me/" . $numero_destino . "?text=" . urlencode($mensaje);
    
    $response['success'] = true;
    $response['mensaje'] = $mensaje;
    $response['whatsapp_link'] = $whatsapp_link;
    
    echo json_encode($response);
    exit;
}

// Obtener pedido por ID
if($action == 'get_pedido'){
    $pedido_id = $_GET['pedido_id'] ?? 0;
    
    if(!$pedido_id){
        $response['message'] = 'Pedido ID requerido';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT p.*, c.nombre, c.apellido, c.telefono, c.email
        FROM pedidos p
        JOIN clientes c ON p.cliente_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();
    
    if($pedido){
        $stmt = $db->prepare("
            SELECT dp.*, pr.nombre as producto_nombre
            FROM detalle_pedido dp
            JOIN productos pr ON dp.producto_id = pr.id
            WHERE dp.pedido_id = ?
        ");
        $stmt->execute([$pedido_id]);
        $items = $stmt->fetchAll();
        
        $response['success'] = true;
        $response['pedido'] = $pedido;
        $response['items'] = $items;
    } else {
        $response['message'] = 'Pedido no encontrado';
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode($response);
?>
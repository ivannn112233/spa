<?php
require_once '../../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();
$pedido_id = $_GET['pedido_id'] ?? 0;

$stmt = $db->prepare("
    SELECT p.*, c.nombre, c.apellido, c.telefono 
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    WHERE p.id = ? AND c.usuario_id = ?
");
$stmt->execute([$pedido_id, $_SESSION['user_id']]);
$pedido = $stmt->fetch();

if(!$pedido){
    header("Location: index.php");
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

// Generar mensaje para WhatsApp
$mensaje = "🛍️ *NUEVO PEDIDO - " . SITE_NAME . "*\n\n";
$mensaje .= "🧑 Cliente: " . $pedido['nombre'] . ' ' . $pedido['apellido'] . "\n";
$mensaje .= "📞 Teléfono: " . $pedido['telefono'] . "\n\n";
$mensaje .= "📦 *PRODUCTOS:*\n";
foreach($items as $item){
    $mensaje .= "• " . $item['producto_nombre'] . " x" . $item['cantidad'] . " - Bs. " . number_format($item['subtotal'], 2) . "\n";
}
$mensaje .= "\n💰 *Total: Bs. " . number_format($pedido['total'], 2) . "*\n";
$mensaje .= "\n📅 Fecha: " . date('d/m/Y H:i') . "\n";
$mensaje .= "\n✅ Confirmar pedido respondiendo a este mensaje.";

$whatsapp_link = "https://wa.me/" . WHATSAPP_BUSINESS_NUMBER . "?text=" . urlencode($mensaje);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido Enviado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow text-center">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-check-circle"></i> Pedido Recibido</h4>
                </div>
                <div class="card-body">
                    <div class="display-1">🎉</div>
                    <p class="mt-3">¡Gracias por tu compra! Tu pedido ha sido registrado.</p>
                    <p><strong>N° Pedido:</strong> <?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    <p><strong>Total:</strong> Bs. <?php echo number_format($pedido['total'], 2); ?></p>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fab fa-whatsapp"></i> Envía el siguiente mensaje para confirmar tu pedido:
                    </div>
                    
                    <div class="border rounded p-2 bg-light text-start small mb-3" style="font-family: monospace;">
                        <?php echo nl2br(htmlspecialchars($mensaje)); ?>
                    </div>
                    
                    <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="btn btn-success btn-lg w-100 mb-2">
                        <i class="fab fa-whatsapp"></i> Enviar por WhatsApp
                    </a>
                    
                    <a href="../index.php" class="btn btn-secondary w-100">
                        <i class="fas fa-home"></i> Volver a Mi Cuenta
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
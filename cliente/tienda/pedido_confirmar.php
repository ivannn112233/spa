<?php
require_once '../../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
foreach($carrito as $item){
    $total += $item['precio'] * $item['cantidad'];
}

if(empty($carrito)){
    header("Location: index.php");
    exit;
}

// Obtener datos del cliente
$stmt = $db->prepare("SELECT * FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $metodo_contacto = $_POST['metodo_contacto'];
    
    // Guardar pedido
    $stmt = $db->prepare("INSERT INTO pedidos (cliente_id, subtotal, descuento, total, metodo_contacto, estado, created_at) VALUES (?, ?, 0, ?, ?, 'pendiente', NOW())");
    $stmt->execute([$cliente['id'], $total, $total, $metodo_contacto]);
    $pedido_id = $db->lastInsertId();
    
    // Guardar detalles
    foreach($carrito as $item){
        $stmt = $db->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$pedido_id, $item['id'], $item['cantidad'], $item['precio'], $item['precio'] * $item['cantidad']]);
    }
    
    // Limpiar carrito
    $_SESSION['carrito'] = [];
    
    // Redirigir a enviar pedido
    header("Location: pedido_enviar.php?pedido_id=" . $pedido_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Confirmar Pedido</h4>
                </div>
                <div class="card-body">
                    <h6>Resumen del pedido</h6>
                    <table class="table table-sm">
                        <?php foreach($carrito as $item): ?>
                        <tr><td><?php echo $item['nombre']; ?> x<?php echo $item['cantidad']; ?></td><td class="text-end">Bs. <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></td></tr>
                        <?php endforeach; ?>
                        <tr class="table-light"><th>Total</th><th class="text-end">Bs. <?php echo number_format($total, 2); ?></th></tr>
                    </table>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label>Método de contacto</label>
                            <select name="metodo_contacto" class="form-control" required>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="telegram">Telegram</option>
                            </select>
                            <small class="text-muted">Recibirás un mensaje con los detalles de tu pedido</small>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Confirmar Pedido</button>
                        <a href="carrito.php" class="btn btn-secondary w-100 mt-2">Volver al carrito</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
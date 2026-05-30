<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$mensaje = '';
$carrito = $_SESSION['carrito_pv'] ?? [];

// Obtener datos del usuario para avatar
$usuario_nombre = 'Recepción';
$usuario_apellido = '';
$iniciales = 'R';

try {
    $stmt = $db->prepare("SELECT nombre, apellido FROM clientes WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch();
    if($usuario && is_array($usuario)){
        $usuario_nombre = $usuario['nombre'] ?? 'Recepción';
        $usuario_apellido = $usuario['apellido'] ?? '';
        $iniciales = strtoupper(substr($usuario_nombre, 0, 1) . ($usuario_apellido ? substr($usuario_apellido, 0, 1) : ''));
    }
} catch(PDOException $e) {
    $iniciales = 'R';
}

// Agregar producto al carrito
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar'])){
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'] ?? 1;
    
    $stmt = $db->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();
    
    if($producto){
        if(isset($carrito[$producto_id])){
            $carrito[$producto_id]['cantidad'] += $cantidad;
        } else {
            $carrito[$producto_id] = [
                'id' => $producto['id'],
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio_base'],
                'cantidad' => $cantidad
            ];
        }
        $_SESSION['carrito_pv'] = $carrito;
        $mensaje = '<div class="alert alert-success">Producto agregado</div>';
    }
}

// Eliminar del carrito
if(isset($_GET['eliminar'])){
    $id = $_GET['eliminar'];
    unset($carrito[$id]);
    $_SESSION['carrito_pv'] = $carrito;
    $mensaje = '<div class="alert alert-warning">Producto eliminado</div>';
}

// Vaciar carrito
if(isset($_GET['vaciar'])){
    $_SESSION['carrito_pv'] = [];
    $carrito = [];
    $mensaje = '<div class="alert alert-info">Carrito vaciado</div>';
}

// Finalizar venta
if(isset($_POST['finalizar'])){
    $cliente_id = $_POST['cliente_id'] ?? null;
    $metodo_pago = $_POST['metodo_pago'];
    $subtotal = array_sum(array_map(function($item){ return $item['precio'] * $item['cantidad']; }, $carrito));
    $impuesto = $subtotal * (IMPUESTO_PORCENTAJE / 100);
    $total = $subtotal + $impuesto;
    
    $db->beginTransaction();
    
    try {
        // Crear pedido
        $stmt = $db->prepare("INSERT INTO pedidos (cliente_id, subtotal, impuesto, total, estado, created_at) VALUES (?, ?, ?, ?, 'pagado', NOW())");
        $stmt->execute([$cliente_id, $subtotal, $impuesto, $total]);
        $pedido_id = $db->lastInsertId();
        
        // Detalles del pedido
        foreach($carrito as $item){
            $stmt = $db->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$pedido_id, $item['id'], $item['cantidad'], $item['precio'], $item['precio'] * $item['cantidad']]);
            
            // Descontar stock
            $stmt = $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['cantidad'], $item['id']]);
        }
        
        // Crear factura
        $stmt = $db->prepare("INSERT INTO facturas (cliente_id, pedido_id, subtotal, impuesto, total, metodo_pago, estado, fecha_emision) VALUES (?, ?, ?, ?, ?, ?, 'pagada', NOW())");
        $stmt->execute([$cliente_id, $pedido_id, $subtotal, $impuesto, $total, $metodo_pago]);
        
        $db->commit();
        
        // Limpiar carrito
        $_SESSION['carrito_pv'] = [];
        $carrito = [];
        
        $mensaje = '<div class="alert alert-success">Venta completada. Total: Bs. ' . number_format($total, 2) . '</div>';
    } catch(Exception $e){
        $db->rollBack();
        $mensaje = '<div class="alert alert-danger">Error al procesar la venta</div>';
    }
}

// Obtener productos
$productos = $db->query("SELECT * FROM productos WHERE activo = 1 AND stock > 0 ORDER BY nombre")->fetchAll();

// Obtener clientes
$clientes = $db->query("SELECT id, nombre, apellido, ci, telefono FROM clientes ORDER BY nombre LIMIT 20")->fetchAll();

// Calcular totales
$subtotal = array_sum(array_map(function($item){ return $item['precio'] * $item['cantidad']; }, $carrito));
$impuesto = $subtotal * (IMPUESTO_PORCENTAJE / 100);
$total = $subtotal + $impuesto;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Punto de Venta - Recepción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%);
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            transition: all 0.3s;
            border-radius: 8px;
            margin: 2px 8px;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(52, 152, 219, 0.3);
            padding-left: 28px;
        }
        
        .sidebar .nav-link.active {
            background: #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
            margin: 0 auto 10px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        
        .btn-logout {
            background: #e74c3c;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .producto-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .producto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: #3498db;
        }
        
        .cart-item {
            transition: all 0.2s;
        }
        
        .cart-item:hover {
            background-color: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <nav class="col-md-2 sidebar">
            <div class="text-center py-4">
                <div class="user-avatar">
                    <?php echo $iniciales; ?>
                </div>
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($usuario_nombre . ' ' . $usuario_apellido); ?></h5>
                <small class="text-muted"><i class="fas fa-phone-alt"></i> Recepción</small>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a class="nav-link" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
                <li><a class="nav-link" href="calendario.php"><i class="fas fa-calendar-week"></i> Calendario</a></li>
                <li><a class="nav-link" href="solicitudes.php"><i class="fas fa-inbox"></i> Solicitudes</a></li>
                <li><a class="nav-link" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><a class="nav-link" href="promociones_aplicar.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link active" href="punto_venta.php"><i class="fas fa-shopping-cart"></i> Punto de Venta</a></li>
                <li><hr class="bg-secondary mx-3 my-2"></li>
                <li><a class="nav-link text-warning" href="../cambiar_password.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="col-md-10 main-content">
            <!-- Header con botón de salir -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><i class="fas fa-shopping-cart text-primary"></i> Punto de Venta</h1>
                    <small class="text-muted">Venta rápida de productos</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <?php echo $mensaje; ?>
            
            <div class="row">
                <!-- Productos -->
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-box"></i> Productos Disponibles</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($productos)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-box-open fa-3x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No hay productos disponibles</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach($productos as $p): ?>
                                    <div class="col-md-3 col-sm-4 col-6 mb-3">
                                        <div class="card producto-card h-100" onclick="agregarProducto(<?php echo $p['id']; ?>, '<?php echo addslashes($p['nombre']); ?>', <?php echo $p['precio_base']; ?>)">
                                            <div class="card-body p-2 text-center">
                                                <i class="fas fa-box fa-2x text-primary mb-2"></i>
                                                <h6 class="mt-1 mb-0 small"><?php echo htmlspecialchars(substr($p['nombre'], 0, 20)); ?></h6>
                                                <small class="text-muted">Bs. <?php echo number_format($p['precio_base'], 2); ?></small>
                                                <br><small class="badge bg-<?php echo $p['stock'] <= 5 ? 'danger' : 'success'; ?>">Stock: <?php echo $p['stock']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Carrito -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Carrito de Compras</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($carrito)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-cart-empty fa-3x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Carrito vacío</p>
                                    <small class="text-muted">Agrega productos haciendo clic en ellos</small>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Producto</th>
                                                <th class="text-center">Cant</th>
                                                <th class="text-end">Precio</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($carrito as $item): ?>
                                            <tr class="cart-item">
                                                <td><small><?php echo htmlspecialchars(substr($item['nombre'], 0, 20)); ?></small></td>
                                                <td class="text-center"><?php echo $item['cantidad']; ?></td>
                                                <td class="text-end">Bs. <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></td>
                                                <td class="text-center">
                                                    <a href="punto_venta.php?eliminar=<?php echo $item['id']; ?>" class="text-danger" title="Eliminar">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-1">
                                    <strong>Subtotal:</strong>
                                    <span>Bs. <?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <strong>Impuesto (<?php echo IMPUESTO_PORCENTAJE; ?>%):</strong>
                                    <span>Bs. <?php echo number_format($impuesto, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                    <strong class="fs-5">Total:</strong>
                                    <strong class="fs-5 text-success">Bs. <?php echo number_format($total, 2); ?></strong>
                                </div>
                                <hr>
                                <form method="POST" onsubmit="return confirm('¿Finalizar la venta?')">
                                    <div class="mb-2">
                                        <label class="form-label small">Cliente</label>
                                        <select name="cliente_id" class="form-select form-select-sm">
                                            <option value="">Cliente mostrador (sin registro)</option>
                                            <?php foreach($clientes as $c): ?>
                                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre'] . ' ' . $c['apellido'] . ' - ' . $c['ci']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Método de Pago</label>
                                        <select name="metodo_pago" class="form-select form-select-sm" required>
                                            <option value="efectivo">💵 Efectivo</option>
                                            <option value="qr">📱 QR</option>
                                            <option value="transferencia">🏦 Transferencia</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="finalizar" class="btn btn-success w-100 mb-2">
                                        <i class="fas fa-check"></i> Cobrar Bs. <?php echo number_format($total, 2); ?>
                                    </button>
                                    <a href="punto_venta.php?vaciar=1" class="btn btn-danger btn-sm w-100" onclick="return confirm('¿Vaciar carrito?')">
                                        <i class="fas fa-trash-alt"></i> Vaciar Carrito
                                    </a>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="text-center text-muted mt-4 pt-3 border-top">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos los derechos reservados</small>
            </footer>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function agregarProducto(id, nombre, precio) {
    let cantidad = prompt("Cantidad de " + nombre + ":", 1);
    if(cantidad && parseInt(cantidad) > 0){
        let form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        let inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'producto_id';
        inputId.value = id;
        
        let inputCant = document.createElement('input');
        inputCant.type = 'hidden';
        inputCant.name = 'cantidad';
        inputCant.value = parseInt(cantidad);
        
        let inputAgregar = document.createElement('input');
        inputAgregar.type = 'hidden';
        inputAgregar.name = 'agregar';
        inputAgregar.value = '1';
        
        form.appendChild(inputId);
        form.appendChild(inputCant);
        form.appendChild(inputAgregar);
        document.body.appendChild(form);
        form.submit();
    } else if(cantidad !== null){
        alert('Por favor ingresa una cantidad válida');
    }
}
</script>
</body>
</html>
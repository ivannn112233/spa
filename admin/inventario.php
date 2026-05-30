<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$mensaje = '';

// Procesar ajuste de stock
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajustar_stock'])){
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];
    $tipo = $_POST['tipo']; // 'ingreso' o 'salida'
    
    if($tipo == 'ingreso'){
        $stmt = $db->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
        $stmt->execute([$cantidad, $producto_id]);
        $mensaje = '<div class="alert alert-success">Stock actualizado (+' . $cantidad . ')</div>';
        AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'STOCK_INGRESO', "Ingreso de $cantidad unidades al producto ID: $producto_id", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    } else {
        $stmt = $db->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE id = ?");
        $stmt->execute([$cantidad, $producto_id]);
        $mensaje = '<div class="alert alert-warning">Stock actualizado (-' . $cantidad . ')</div>';
        AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'STOCK_SALIDA', "Salida de $cantidad unidades del producto ID: $producto_id", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    }
}

// Agregar nuevo producto
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nuevo_producto'])){
    $nombre = $_POST['nombre'];
    $sku = $_POST['sku'];
    $precio = $_POST['precio_base'];
    $stock = $_POST['stock'];
    $stock_minimo = $_POST['stock_minimo'];
    $categoria_id = $_POST['categoria_id'];
    
    $stmt = $db->prepare("INSERT INTO productos (nombre, sku, precio_base, stock, stock_minimo, categoria_id, activo) VALUES (?,?,?,?,?,?,1)");
    if($stmt->execute([$nombre, $sku, $precio, $stock, $stock_minimo, $categoria_id])){
        $mensaje = '<div class="alert alert-success">Producto agregado correctamente</div>';
        AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'PRODUCTO_CREADO', "Nuevo producto: $nombre (SKU: $sku)", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    }
}

// Eliminar/Desactivar producto
if(isset($_GET['delete'])){
    $stmt = $db->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $mensaje = '<div class="alert alert-danger">Producto desactivado</div>';
}

// Obtener productos
$productos = $db->query("
    SELECT p.*, c.nombre as categoria_nombre 
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE p.activo = 1
    ORDER BY p.stock ASC
")->fetchAll();

// Productos con stock bajo para alertas
$alertas = array_filter($productos, function($p) { return $p['stock'] <= $p['stock_minimo']; });

// Categorías
$categorias = $db->query("SELECT * FROM categorias_productos")->fetchAll();

// Estadísticas
$total_productos = count($productos);
$total_stock = array_sum(array_column($productos, 'stock'));
$valor_inventario = array_sum(array_map(function($p){ return $p['stock'] * $p['precio_base']; }, $productos));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .stock-bajo { background-color: #fff3cd; }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Admin</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a class="nav-link" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link active" href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
                <li><a class="nav-link" href="disponibilidad.php"><i class="fas fa-clock"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                <li><a class="nav-link" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-boxes"></i> Inventario</h2>
            </div>
            
            <?php echo $mensaje; ?>
            
            <!-- Alertas de stock bajo -->
            <?php if(!empty($alertas)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>¡Atención!</strong> Los siguientes productos tienen stock bajo:
                <?php foreach($alertas as $a): ?>
                <span class="badge bg-danger"><?php echo $a['nombre']; ?> (Stock: <?php echo $a['stock']; ?>)</span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h3><?php echo $total_productos; ?></h3>
                            <p>Productos Activos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h3><?php echo $total_stock; ?></h3>
                            <p>Unidades en Stock</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h3>Bs. <?php echo number_format($valor_inventario, 2); ?></h3>
                            <p>Valor del Inventario</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-plus"></i> Agregar Producto</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="nuevo_producto" value="1">
                                <div class="mb-2"><input type="text" name="nombre" class="form-control" placeholder="Nombre" required></div>
                                <div class="mb-2"><input type="text" name="sku" class="form-control" placeholder="SKU" required></div>
                                <div class="mb-2"><input type="number" step="0.01" name="precio_base" class="form-control" placeholder="Precio" required></div>
                                <div class="mb-2"><input type="number" name="stock" class="form-control" placeholder="Stock inicial" value="0"></div>
                                <div class="mb-2"><input type="number" name="stock_minimo" class="form-control" placeholder="Stock mínimo" value="5"></div>
                                <div class="mb-2">
                                    <select name="categoria_id" class="form-control">
                                        <option value="">Sin categoría</option>
                                        <?php foreach($categorias as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Agregar Producto</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Productos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr><th>SKU</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Mínimo</th><th>Estado</th><th>Acciones</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($productos as $p): 
                                            $stockClass = $p['stock'] <= $p['stock_minimo'] ? 'stock-bajo' : '';
                                        ?>
                                        <tr class="<?php echo $stockClass; ?>">
                                            <td><?php echo $p['sku']; ?></td>
                                            <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                                            <td><?php echo $p['categoria_nombre'] ?? '-'; ?></td>
                                            <td>Bs. <?php echo number_format($p['precio_base'], 2); ?></td>
                                            <td><strong><?php echo $p['stock']; ?></strong></td>
                                            <td><?php echo $p['stock_minimo']; ?></td>
                                            <td><?php echo $p['stock'] <= $p['stock_minimo'] ? '<span class="badge bg-danger">¡Bajo stock!</span>' : '<span class="badge bg-success">OK</span>'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#ajustarModal" data-id="<?php echo $p['id']; ?>" data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>" data-stock="<?php echo $p['stock']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="inventario.php?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Desactivar este producto?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                             </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Ajustar Stock -->
<div class="modal fade" id="ajustarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Ajustar Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p id="productoInfo"></p>
                    <input type="hidden" name="producto_id" id="producto_id">
                    <div class="mb-3">
                        <label>Cantidad</label>
                        <input type="number" name="cantidad" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Tipo de movimiento</label>
                        <select name="tipo" class="form-control">
                            <option value="ingreso">📥 Ingreso (sumar stock)</option>
                            <option value="salida">📤 Salida (restar stock)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="ajustar_stock" class="btn btn-primary">Aplicar Cambio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('ajustarModal').addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var nombre = button.getAttribute('data-nombre');
    var stock = button.getAttribute('data-stock');
    document.getElementById('producto_id').value = id;
    document.getElementById('productoInfo').innerHTML = '<strong>' + nombre + '</strong><br>Stock actual: ' + stock;
});
</script>
</body>
</html>
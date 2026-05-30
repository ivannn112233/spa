<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$mensaje = '';

// Crear tabla de categorías si no existe
$db->exec("CREATE TABLE IF NOT EXISTS categorias_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    padre_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insertar categorías por defecto si no hay
$check = $db->query("SELECT COUNT(*) FROM categorias_productos")->fetchColumn();
if($check == 0){
    $db->exec("INSERT INTO categorias_productos (nombre) VALUES ('Alimentos'), ('Shampoos'), ('Accesorios'), ('Juguetes'), ('Medicamentos'), ('Higiene')");
}

// Procesar formulario
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['guardar_producto'])){
        $nombre = $_POST['nombre'];
        $precio = $_POST['precio_base'];
        $stock = $_POST['stock'];
        $stock_minimo = $_POST['stock_minimo'];
        $sku = strtoupper($_POST['sku']);
        $categoria_id = $_POST['categoria_id'] ?: null;
        $descripcion = $_POST['descripcion'];
        
        if(isset($_POST['edit_id']) && $_POST['edit_id']){
            $stmt = $db->prepare("UPDATE productos SET nombre=?, precio_base=?, stock=?, stock_minimo=?, sku=?, categoria_id=?, descripcion=? WHERE id=?");
            $stmt->execute([$nombre, $precio, $stock, $stock_minimo, $sku, $categoria_id, $descripcion, $_POST['edit_id']]);
            $mensaje = '<div class="alert alert-success">Producto actualizado correctamente</div>';
            AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'PRODUCTO_EDITADO', "Editó producto: $nombre (SKU: $sku)", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        } else {
            // Verificar SKU único
            $stmt = $db->prepare("SELECT id FROM productos WHERE sku = ?");
            $stmt->execute([$sku]);
            if($stmt->fetch()){
                $mensaje = '<div class="alert alert-danger">El SKU ya existe. Debe ser único.</div>';
            } else {
                $stmt = $db->prepare("INSERT INTO productos (nombre, precio_base, stock, stock_minimo, sku, categoria_id, descripcion, activo) VALUES (?,?,?,?,?,?,?,1)");
                $stmt->execute([$nombre, $precio, $stock, $stock_minimo, $sku, $categoria_id, $descripcion]);
                $mensaje = '<div class="alert alert-success">Producto agregado correctamente</div>';
                AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'PRODUCTO_CREADO', "Creó producto: $nombre (SKU: $sku)", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            }
        }
    }
    
    // Guardar categoría
    if(isset($_POST['guardar_categoria'])){
        $nombre_categoria = $_POST['categoria_nombre'];
        $stmt = $db->prepare("INSERT INTO categorias_productos (nombre) VALUES (?)");
        $stmt->execute([$nombre_categoria]);
        $mensaje = '<div class="alert alert-success">Categoría agregada</div>';
    }
}

// Eliminar/Desactivar producto
if(isset($_GET['delete'])){
    $stmt = $db->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $mensaje = '<div class="alert alert-warning">Producto desactivado</div>';
    AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'PRODUCTO_DESACTIVADO', "Desactivó producto ID: {$_GET['delete']}", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
}

// Activar producto
if(isset($_GET['activate'])){
    $stmt = $db->prepare("UPDATE productos SET activo = 1 WHERE id = ?");
    $stmt->execute([$_GET['activate']]);
    $mensaje = '<div class="alert alert-success">Producto activado</div>';
}

// Obtener productos (incluyendo inactivos para gestión)
$productos = $db->query("
    SELECT p.*, c.nombre as categoria_nombre 
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    ORDER BY p.activo DESC, p.nombre ASC
")->fetchAll();

// Obtener categorías
$categorias = $db->query("SELECT * FROM categorias_productos ORDER BY nombre")->fetchAll();

// Obtener producto para editar
$edit = null;
if(isset($_GET['edit'])){
    $stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

// Estadísticas
$total_productos = $db->query("SELECT COUNT(*) FROM productos WHERE activo = 1")->fetchColumn();
$stock_bajo = $db->query("SELECT COUNT(*) FROM productos WHERE stock <= stock_minimo AND activo = 1")->fetchColumn();
$valor_inventario = $db->query("SELECT COALESCE(SUM(stock * precio_base), 0) FROM productos WHERE activo = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .stock-bajo { background-color: #fff3cd !important; }
        .producto-inactivo { opacity: 0.6; background-color: #f8f9fa; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; padding: 15px; color: white; text-align: center; }
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
                <li><a class="nav-link active" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
                <li><a class="nav-link" href="disponibilidad.php"><i class="fas fa-clock"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                <li><a class="nav-link" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-box"></i> Productos</h2>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoriaModal"><i class="fas fa-folder-plus"></i> Nueva Categoría</button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#productoModal" onclick="resetForm()"><i class="fas fa-plus"></i> Nuevo Producto</button>
                </div>
            </div>
            
            <?php echo $mensaje; ?>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-4"><div class="stat-card"><h3><?php echo $total_productos; ?></h3><p>Productos Activos</p></div></div>
                <div class="col-md-4"><div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"><h3><?php echo $stock_bajo; ?></h3><p>Stock Bajo</p></div></div>
                <div class="col-md-4"><div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);"><h3>Bs. <?php echo number_format($valor_inventario, 2); ?></h3><p>Valor Inventario</p></div></div>
            </div>
            
            <!-- Tabla de productos -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Productos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaProductos">
                            <thead class="table-dark">
                                <tr>
                                    <th>SKU</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Mínimo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($productos as $p): 
                                    $stockClass = ($p['stock'] <= $p['stock_minimo'] && $p['activo'] == 1) ? 'stock-bajo' : '';
                                    $inactivoClass = ($p['activo'] == 0) ? 'producto-inactivo' : '';
                                ?>
                                <tr class="<?php echo $stockClass . ' ' . $inactivoClass; ?>">
                                    <td><strong><?php echo $p['sku']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                                    <td><?php echo $p['categoria_nombre'] ?? '-'; ?></td>
                                    <td>Bs. <?php echo number_format($p['precio_base'], 2); ?></td>
                                    <td>
                                        <?php if($p['stock'] <= $p['stock_minimo'] && $p['activo'] == 1): ?>
                                            <span class="badge bg-danger"><?php echo $p['stock']; ?></span>
                                        <?php else: ?>
                                            <?php echo $p['stock']; ?>
                                        <?php endif; ?>
                                     </div>
                                    </td>
                                    <td><?php echo $p['stock_minimo']; ?></td>
                                    <td>
                                        <?php if($p['activo'] == 1): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                     </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="productos.php?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if($p['activo'] == 1): ?>
                                                <a href="productos.php?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Desactivar este producto?')" title="Desactivar">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="productos.php?activate=<?php echo $p['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('¿Activar este producto?')" title="Activar">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                     </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Producto -->
<div class="modal fade" id="productoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="productoModalTitle">Nuevo Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="productoForm">
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Nombre *</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>SKU *</label>
                            <input type="text" name="sku" id="sku" class="form-control" required placeholder="Ej: SH-001">
                            <small class="text-muted">Código único del producto</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Categoría</label>
                            <select name="categoria_id" id="categoria_id" class="form-control">
                                <option value="">Sin categoría</option>
                                <?php foreach($categorias as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Precio Base (Bs) *</label>
                            <input type="number" step="0.01" name="precio_base" id="precio_base" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Stock *</label>
                            <input type="number" name="stock" id="stock" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Stock Mínimo *</label>
                            <input type="number" name="stock_minimo" id="stock_minimo" class="form-control" value="5" required>
                            <small class="text-muted">Alerta cuando stock sea menor o igual</small>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Descripción</label>
                            <textarea name="descripcion" id="descripcion" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar_producto" class="btn btn-primary">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Categoría -->
<div class="modal fade" id="categoriaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre de la Categoría</label>
                        <input type="text" name="categoria_nombre" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar_categoria" class="btn btn-success">Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function resetForm() {
    document.getElementById('productoModalTitle').innerText = 'Nuevo Producto';
    document.getElementById('edit_id').value = '';
    document.getElementById('nombre').value = '';
    document.getElementById('sku').value = '';
    document.getElementById('categoria_id').value = '';
    document.getElementById('precio_base').value = '';
    document.getElementById('stock').value = '';
    document.getElementById('stock_minimo').value = '5';
    document.getElementById('descripcion').value = '';
}

<?php if($edit): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('productoModalTitle').innerText = 'Editar Producto';
    document.getElementById('edit_id').value = '<?php echo $edit['id']; ?>';
    document.getElementById('nombre').value = '<?php echo htmlspecialchars($edit['nombre']); ?>';
    document.getElementById('sku').value = '<?php echo $edit['sku']; ?>';
    document.getElementById('categoria_id').value = '<?php echo $edit['categoria_id']; ?>';
    document.getElementById('precio_base').value = '<?php echo $edit['precio_base']; ?>';
    document.getElementById('stock').value = '<?php echo $edit['stock']; ?>';
    document.getElementById('stock_minimo').value = '<?php echo $edit['stock_minimo']; ?>';
    document.getElementById('descripcion').value = '<?php echo htmlspecialchars($edit['descripcion']); ?>';
    
    new bootstrap.Modal(document.getElementById('productoModal')).show();
});
<?php endif; ?>

// Generar SKU automático
document.getElementById('nombre')?.addEventListener('blur', function() {
    const skuInput = document.getElementById('sku');
    if(!skuInput.value){
        const nombre = this.value;
        const palabras = nombre.split(' ');
        let sku = '';
        for(let i = 0; i < Math.min(3, palabras.length); i++){
            sku += palabras[i].substring(0, 2).toUpperCase();
        }
        sku += '-' + Math.floor(Math.random() * 1000);
        skuInput.value = sku;
    }
});
</script>
</body>
</html>
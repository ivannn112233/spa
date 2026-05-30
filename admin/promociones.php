<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();

// Crear tabla de promociones si no existe
$db->exec("CREATE TABLE IF NOT EXISTS promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('porcentaje','monto_fijo','2x1','servicio_gratis') DEFAULT 'porcentaje',
    valor DECIMAL(10,2) NOT NULL,
    codigo VARCHAR(50) UNIQUE,
    servicio_id INT NULL,
    producto_id INT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    uso_maximo INT DEFAULT 1,
    usos_actuales INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$mensaje = '';
$error = '';

// Guardar promoción
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])){
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $valor = $_POST['valor'];
    $codigo = strtoupper($_POST['codigo']);
    $servicio_id = $_POST['servicio_id'] ?: null;
    $producto_id = $_POST['producto_id'] ?: null;
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $uso_maximo = $_POST['uso_maximo'];
    
    if(isset($_POST['edit_id']) && $_POST['edit_id']){
        $stmt = $db->prepare("UPDATE promociones SET nombre=?, tipo=?, valor=?, codigo=?, servicio_id=?, producto_id=?, fecha_inicio=?, fecha_fin=?, uso_maximo=? WHERE id=?");
        $stmt->execute([$nombre, $tipo, $valor, $codigo, $servicio_id, $producto_id, $fecha_inicio, $fecha_fin, $uso_maximo, $_POST['edit_id']]);
        $mensaje = "Promoción actualizada";
    } else {
        $stmt = $db->prepare("INSERT INTO promociones (nombre, tipo, valor, codigo, servicio_id, producto_id, fecha_inicio, fecha_fin, uso_maximo) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$nombre, $tipo, $valor, $codigo, $servicio_id, $producto_id, $fecha_inicio, $fecha_fin, $uso_maximo]);
        $mensaje = "Promoción creada";
    }
}

// Eliminar promoción
if(isset($_GET['delete'])){
    $stmt = $db->prepare("DELETE FROM promociones WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $mensaje = "Promoción eliminada";
}

// Obtener promociones
$promociones = $db->query("SELECT p.*, s.nombre as servicio_nombre, pr.nombre as producto_nombre 
    FROM promociones p
    LEFT JOIN servicios s ON p.servicio_id = s.id
    LEFT JOIN productos pr ON p.producto_id = pr.id
    WHERE p.activo = 1
    ORDER BY p.fecha_fin ASC
")->fetchAll();

$servicios = $db->query("SELECT id, nombre FROM servicios WHERE activo=1")->fetchAll();
$productos = $db->query("SELECT id, nombre FROM productos WHERE activo=1")->fetchAll();

$edit = null;
if(isset($_GET['edit'])){
    $stmt = $db->prepare("SELECT * FROM promociones WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Promociones - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .promo-card { border-left: 4px solid #e74c3c; transition: transform 0.3s; }
        .promo-card:hover { transform: translateY(-3px); }
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
                <li><a class="nav-link" href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
                <li><a class="nav-link" href="disponibilidad.php"><i class="fas fa-clock"></i> Disponibilidad</a></li>
                <li><a class="nav-link active" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                <li><a class="nav-link" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-tags"></i> Promociones y Descuentos</h2>
            </div>
            
            <?php if($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-5">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo $edit ? 'Editar' : 'Nueva'; ?> Promoción</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php if($edit): ?>
                                    <input type="hidden" name="edit_id" value="<?php echo $edit['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label>Nombre de la Promoción</label>
                                    <input type="text" name="nombre" class="form-control" value="<?php echo $edit['nombre'] ?? ''; ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Tipo</label>
                                        <select name="tipo" class="form-control" id="tipoPromo">
                                            <option value="porcentaje" <?php echo ($edit['tipo']??'')=='porcentaje'?'selected':''; ?>>% Porcentaje</option>
                                            <option value="monto_fijo" <?php echo ($edit['tipo']??'')=='monto_fijo'?'selected':''; ?>>Bs. Monto fijo</option>
                                            <option value="2x1" <?php echo ($edit['tipo']??'')=='2x1'?'selected':''; ?>>2x1</option>
                                            <option value="servicio_gratis" <?php echo ($edit['tipo']??'')=='servicio_gratis'?'selected':''; ?>>Servicio gratis</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Valor</label>
                                        <input type="number" step="0.01" name="valor" class="form-control" value="<?php echo $edit['valor'] ?? ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label>Código promocional</label>
                                    <div class="input-group">
                                        <input type="text" name="codigo" class="form-control" placeholder="Ej: BIENVENIDO10" value="<?php echo $edit['codigo'] ?? ''; ?>">
                                        <button type="button" class="btn btn-secondary" onclick="generarCodigo()">Generar</button>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Aplicar a servicio</label>
                                        <select name="servicio_id" class="form-control">
                                            <option value="">Todos los servicios</option>
                                            <?php foreach($servicios as $s): ?>
                                            <option value="<?php echo $s['id']; ?>" <?php echo ($edit['servicio_id']??'')==$s['id']?'selected':''; ?>><?php echo $s['nombre']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Aplicar a producto</label>
                                        <select name="producto_id" class="form-control">
                                            <option value="">Todos los productos</option>
                                            <?php foreach($productos as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" <?php echo ($edit['producto_id']??'')==$p['id']?'selected':''; ?>><?php echo $p['nombre']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Fecha Inicio</label>
                                        <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $edit['fecha_inicio'] ?? date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Fecha Fin</label>
                                        <input type="date" name="fecha_fin" class="form-control" value="<?php echo $edit['fecha_fin'] ?? date('Y-m-d', strtotime('+30 days')); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label>Uso máximo</label>
                                    <input type="number" name="uso_maximo" class="form-control" value="<?php echo $edit['uso_maximo'] ?? 1; ?>" min="1">
                                    <small class="text-muted">1 = Una sola vez por cliente</small>
                                </div>
                                
                                <button type="submit" name="guardar" class="btn btn-primary w-100">Guardar Promoción</button>
                                <?php if($edit): ?>
                                    <a href="promociones.php" class="btn btn-secondary w-100 mt-2">Cancelar</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">Promociones Activas</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($promociones)): ?>
                                <p class="text-muted">No hay promociones activas</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach($promociones as $p):
                                        $icono = $p['tipo'] == 'porcentaje' ? '%' : ($p['tipo'] == 'monto_fijo' ? 'Bs.' : ($p['tipo'] == '2x1' ? '2x1' : '🎁'));
                                        $color = $p['fecha_fin'] < date('Y-m-d') ? 'secondary' : 'success';
                                    ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card promo-card border-<?php echo $color; ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="card-title"><?php echo $p['nombre']; ?></h6>
                                                    <span class="badge bg-<?php echo $color; ?>"><?php echo $icono; ?> <?php echo $p['tipo']=='porcentaje' ? $p['valor'].'%' : ($p['tipo']=='monto_fijo' ? 'Bs. '.$p['valor'] : $p['tipo']); ?></span>
                                                </div>
                                                <p class="small text-muted mb-1">
                                                    <i class="fas fa-tag"></i> Código: <strong><?php echo $p['codigo']; ?></strong>
                                                </p>
                                                <p class="small mb-1">
                                                    <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($p['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($p['fecha_fin'])); ?>
                                                </p>
                                                <p class="small">
                                                    <i class="fas fa-users"></i> Usos: <?php echo $p['usos_actuales']; ?>/<?php echo $p['uso_maximo']; ?>
                                                </p>
                                                <div class="btn-group w-100">
                                                    <a href="promociones.php?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                                    <a href="promociones.php?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta promoción?')">Eliminar</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function generarCodigo() {
    const letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numeros = '0123456789';
    let codigo = '';
    for(let i = 0; i < 3; i++) codigo += letras[Math.floor(Math.random() * letras.length)];
    for(let i = 0; i < 3; i++) codigo += numeros[Math.floor(Math.random() * numeros.length)];
    document.querySelector('input[name="codigo"]').value = codigo;
}
</script>
</body>
</html>
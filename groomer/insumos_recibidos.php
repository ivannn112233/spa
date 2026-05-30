<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_GROOMER);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id, nombre, apellido FROM groomers WHERE usuario_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$groomer = $stmt->fetch();
$groomerId = $groomer['id'];

$mensaje = '';
$cita_id = $_GET['cita_id'] ?? 0;

// Obtener productos disponibles (insumos)
$productos = $db->query("SELECT id, nombre, sku, stock FROM productos WHERE activo = 1 AND stock > 0 ORDER BY nombre")->fetchAll();

// Registrar solicitud de insumos
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];
    $cita_id = $_POST['cita_id'] ?: null;
    
    $stmt = $db->prepare("INSERT INTO consumo_insumos (groomer_id, producto_id, cantidad, cita_id, created_at) VALUES (?, ?, ?, ?, NOW())");
    if($stmt->execute([$groomerId, $producto_id, $cantidad, $cita_id])){
        // Descontar stock
        $stmt = $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$cantidad, $producto_id]);
        $mensaje = '<div class="alert alert-success">Insumo registrado correctamente</div>';
        AuditLog::log($_SESSION['user_id'], ROLE_GROOMER, 'INSUMO_RECIBIDO', "Registró insumo: cantidad $cantidad del producto ID: $producto_id", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    } else {
        $mensaje = '<div class="alert alert-danger">Error al registrar insumo</div>';
    }
}

// Obtener insumos del groomer hoy
$stmt = $db->prepare("
    SELECT i.*, p.nombre as producto_nombre, p.sku 
    FROM consumo_insumos i
    JOIN productos p ON i.producto_id = p.id
    WHERE i.groomer_id = :groomer_id 
    ORDER BY i.created_at DESC
    LIMIT 20
");
$stmt->execute([':groomer_id' => $groomerId]);
$insumos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Insumos - Groomer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; }
        .main-content { margin-left: 250px; }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Groomer</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="ficha_grooming.php"><i class="fas fa-clipboard-list"></i> Fichas</a></li>
                <li><a class="nav-link" href="checklist.php"><i class="fas fa-check-square"></i> Checklist</a></li>
                <li><a class="nav-link active" href="insumos_recibidos.php"><i class="fas fa-boxes"></i> Insumos</a></li>
                <li><a class="nav-link" href="galeria_fotos.php"><i class="fas fa-camera"></i> Galería</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-boxes"></i> Registro de Insumos</h2>
            </div>
            
            <?php echo $mensaje; ?>
            
            <div class="row">
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5>Solicitar Insumo</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label>Insumo</label>
                                    <select name="producto_id" class="form-control" required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach($productos as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?> (Stock: <?php echo $p['stock']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Cantidad</label>
                                    <input type="number" name="cantidad" class="form-control" required min="1">
                                </div>
                                <div class="mb-3">
                                    <label>Cita asociada (opcional)</label>
                                    <input type="number" name="cita_id" class="form-control" placeholder="ID de la cita">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Registrar Insumo</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5>Historial de Insumos</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($insumos)): ?>
                                <p class="text-muted">No hay insumos registrados</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="table-dark">
                                            <tr><th>Fecha</th><th>Producto</th><th>Cantidad</th><th>Cita</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($insumos as $i): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($i['created_at'])); ?></td>
                                                <td><?php echo $i['producto_nombre']; ?> (<?php echo $i['sku']; ?>)</a></td>
                                                <td><?php echo $i['cantidad']; ?></td>
                                                <td><?php echo $i['cita_id'] ? '<a href="ficha_grooming.php?cita_id='.$i['cita_id'].'">Ver cita</a>' : '-'; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
</body>
</html>
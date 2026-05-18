<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

// Procesar formulario
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio_base'];
    $duracion = $_POST['duracion_base_minutos'];
    $descripcion = $_POST['descripcion'];
    
    if(isset($_POST['edit_id']) && $_POST['edit_id']){
        $stmt = $db->prepare("UPDATE servicios SET nombre=?, precio_base=?, duracion_base_minutos=?, descripcion=? WHERE id=?");
        $stmt->execute([$nombre, $precio, $duracion, $descripcion, $_POST['edit_id']]);
    }else{
        $stmt = $db->prepare("INSERT INTO servicios (nombre, precio_base, duracion_base_minutos, descripcion, activo) VALUES (?,?,?,?,1)");
        $stmt->execute([$nombre, $precio, $duracion, $descripcion]);
    }
    header("Location: servicios.php?msg=Guardado");
    exit;
}

// Eliminar
if(isset($_GET['delete'])){
    $stmt = $db->prepare("UPDATE servicios SET activo=0 WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: servicios.php?msg=Eliminado");
    exit;
}

$servicios = $db->query("SELECT * FROM servicios ORDER BY id DESC")->fetchAll();
$edit = null;
if(isset($_GET['edit'])) $edit = $db->prepare("SELECT * FROM servicios WHERE id=?")->execute([$_GET['edit']])->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>.sidebar { min-height: 100vh; background: #2c3e50; }
    .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
    .sidebar .nav-link i { width: 25px; margin-right: 10px; }</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-md-block sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Admin</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a class="nav-link" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link active" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom"><h2>Servicios</h2></div>
            <?php if(isset($_GET['msg'])) echo '<div class="alert alert-success">'.$_GET['msg'].'</div>'; ?>
            <div class="row">
                <div class="col-md-5">
                    <div class="card"><div class="card-header"><?php echo $edit?'Editar':'Nuevo'; ?> Servicio</div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if($edit): ?>
                            <input type="hidden" name="edit_id" value="<?php echo $edit['id']; ?>">
                            <?php endif; ?>
                            <div class="mb-3"><label>Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo $edit?htmlspecialchars($edit['nombre']):''; ?>" required></div>
                            <div class="mb-3"><label>Precio Base (Bs)</label><input type="number" step="0.01" name="precio_base" class="form-control" value="<?php echo $edit?$edit['precio_base']:''; ?>" required></div>
                            <div class="mb-3"><label>Duración (minutos)</label><input type="number" name="duracion_base_minutos" class="form-control" value="<?php echo $edit?$edit['duracion_base_minutos']:60; ?>" required></div>
                            <div class="mb-3"><label>Descripción</label><textarea name="descripcion" class="form-control"><?php echo $edit?htmlspecialchars($edit['descripcion']):''; ?></textarea></div>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                            <?php if($edit): ?>
                            <a href="servicios.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </form>
                    </div></div>
                </div>
                <div class="col-md-7">
                    <div class="card"><div class="card-header">Lista de Servicios</div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead><tr><th>Nombre</th><th>Precio</th><th>Duración</th><th>Acciones</th></tr></thead>
                            <tbody><?php foreach($servicios as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['nombre']); ?></td>
                                <td>Bs. <?php echo $s['precio_base']; ?></td>
                                <td><?php echo $s['duracion_base_minutos']; ?> min</td>
                                <td>
                                    <a href="servicios.php?edit=<?php echo $s['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <a href="servicios.php?delete=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr><?php endforeach; ?></tbody>
                        </table>
                    </div></div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
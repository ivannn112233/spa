<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $stmt = $db->prepare("INSERT INTO roles (nombre, descripcion) VALUES (?,?)");
    $stmt->execute([$nombre, $descripcion]);
    header("Location: roles.php?msg=Rol creado");
    exit;
}

$roles = $db->query("SELECT * FROM roles")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Roles - Admin</title>
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
                <li><a class="nav-link active" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom"><h2>Roles</h2></div>
            <?php if(isset($_GET['msg'])) echo '<div class="alert alert-success">'.$_GET['msg'].'</div>'; ?>
            <div class="row">
                <div class="col-md-5">
                    <div class="card"><div class="card-header">Agregar Rol</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3"><label>Nombre</label><input type="text" name="nombre" class="form-control" required></div>
                            <div class="mb-3"><label>Descripción</label><textarea name="descripcion" class="form-control"></textarea></div>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </form>
                    </div></div>
                </div>
                <div class="col-md-7">
                    <div class="card"><div class="card-header">Lista de Roles</div>
                    <div class="card-body">
                        <table class="table"><thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th></tr></thead>
                        <tbody><?php foreach($roles as $r): ?>
                        <tr><td><?php echo $r['id']; ?></td><td><?php echo ucfirst($r['nombre']); ?></td><td><?php echo htmlspecialchars($r['descripcion']); ?></td></tr>
                        <?php endforeach; ?></tbody></table>
                    </div></div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
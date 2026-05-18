<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT * FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();

$mensaje = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $canal = $_POST['canal_notificacion'];
    
    $stmt = $db->prepare("UPDATE clientes SET nombre=?, apellido=?, telefono=?, direccion=?, canal_notificacion=? WHERE usuario_id=?");
    $stmt->execute([$nombre, $apellido, $telefono, $direccion, $canal, $_SESSION['user_id']]);
    
    $_SESSION['user_name'] = $nombre . ' ' . $apellido;
    $mensaje = '<div class="alert alert-success">Perfil actualizado</div>';
    
    // Recargar datos
    $stmt->execute([$_SESSION['user_id']]);
    $cliente = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>.sidebar { min-height: 100vh; background: #2c3e50; position: fixed; } .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; } .sidebar .nav-link i { width: 25px; } .main-content { margin-left: 16.666%; } @media (max-width: 768px) { .sidebar { position: static; } .main-content { margin-left: 0; } }</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Cliente</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a class="nav-link" href="mascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="historial.php"><i class="fas fa-history"></i> Historial</a></li>
                <li><a class="nav-link active" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link text-warning" href="../cambiar_password.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-user"></i> Mi Perfil</h2>
            </div>
            
            <?php echo $mensaje; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label>Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required></div>
                            <div class="col-md-6 mb-3"><label>Apellido</label><input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($cliente['apellido']); ?>" required></div>
                            <div class="col-md-6 mb-3"><label>Email</label><input type="email" class="form-control" value="<?php echo $_SESSION['user_email']; ?>" disabled></div>
                            <div class="col-md-6 mb-3"><label>Teléfono</label><input type="text" name="telefono" class="form-control" value="<?php echo $cliente['telefono']; ?>"></div>
                            <div class="col-md-12 mb-3"><label>Dirección</label><textarea name="direccion" class="form-control"><?php echo htmlspecialchars($cliente['direccion']); ?></textarea></div>
                            <div class="col-md-6 mb-3">
                                <label>Canal de notificación</label>
                                <select name="canal_notificacion" class="form-control">
                                    <option value="email" <?php echo $cliente['canal_notificacion']=='email'?'selected':''; ?>>Email</option>
                                    <option value="whatsapp" <?php echo $cliente['canal_notificacion']=='whatsapp'?'selected':''; ?>>WhatsApp</option>
                                    <option value="telegram" <?php echo $cliente['canal_notificacion']=='telegram'?'selected':''; ?>>Telegram</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        <a href="index.php" class="btn btn-secondary">Volver</a>
                        <a href="../cambiar_password.php" class="btn btn-warning float-end">Cambiar Contraseña</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

$message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Actualizar configuración general (ejemplo simple)
    $site_name = $_POST['site_name'];
    $session_timeout = $_POST['session_timeout'];
    
    // Guardar en archivo o base de datos (ejemplo: guardar en tabla settings)
    $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?");
    $stmt->execute(['site_name', $site_name, $site_name]);
    $stmt->execute(['session_timeout', $session_timeout, $session_timeout]);
    
    $message = '<div class="alert alert-success">Configuración actualizada</div>';
}

// Obtener configuración actual
$config = [];
$stmt = $db->query("SELECT clave, valor FROM configuracion");
while($row = $stmt->fetch()){
    $config[$row['clave']] = $row['valor'];
}

// Crear tabla de configuración si no existe
$db->exec("CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE,
    valor TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración - Admin</title>
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
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link active" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom"><h2>Configuración del Sistema</h2></div>
            <?php echo $message; ?>
            <div class="card">
                <div class="card-header">Configuración General</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Nombre del Sitio</label>
                            <input type="text" name="site_name" class="form-control" value="<?php echo $config['site_name']??SITE_NAME; ?>">
                        </div>
                        <div class="mb-3">
                            <label>Tiempo de Sesión (segundos)</label>
                            <input type="number" name="session_timeout" class="form-control" value="<?php echo $config['session_timeout']??SESSION_TIMEOUT; ?>">
                            <small class="text-muted">Tiempo de inactividad antes de cerrar sesión</small>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label>Información del Sistema</label>
                            <div class="alert alert-info">
                                <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
                                <strong>MySQL Version:</strong> <?php echo $db->getAttribute(PDO::ATTR_SERVER_VERSION); ?><br>
                                <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                    </form>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header">Seguridad</div>
                <div class="card-body">
                    <a href="../2fa-setup.php" class="btn btn-warning"><i class="fas fa-shield-alt"></i> Configurar 2FA</a>
                    <a href="../logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
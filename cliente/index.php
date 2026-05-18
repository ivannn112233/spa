<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

// Obtener datos del cliente
$stmt = $db->prepare("SELECT * FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();

// Mascotas
$stmt = $db->prepare("
    SELECT m.* FROM mascotas m
    JOIN mascota_dueno md ON m.id = md.mascota_id
    WHERE md.cliente_id = ?
");
$stmt->execute([$cliente['id']]);
$mascotas = $stmt->fetchAll();

// Próximas citas
$stmt = $db->prepare("
    SELECT c.*, s.nombre as servicio, gr.nombre as groomer
    FROM citas c
    JOIN servicios s ON c.servicio_id = s.id
    JOIN groomers gr ON c.groomer_id = gr.id
    WHERE c.cliente_id = ? AND c.fecha_hora_inicio > NOW()
    ORDER BY c.fecha_hora_inicio ASC LIMIT 5
");
$stmt->execute([$cliente['id']]);
$citas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Cuenta - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; top: 0; left: 0; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 16.666%; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; padding: 20px; color: white; }
        @media (max-width: 768px) { .sidebar { position: static; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Cliente</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link active" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a class="nav-link" href="mascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="historial.php"><i class="fas fa-history"></i> Historial</a></li>
                <li><a class="nav-link" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link text-warning" href="../cambiar_password.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2>¡Hola, <?php echo htmlspecialchars($cliente['nombre']); ?>!</h2>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="stat-card">
                        <h3><?php echo count($mascotas); ?></h3>
                        <p>Mascotas Registradas</p>
                        <a href="mascotas.php" class="btn btn-sm btn-light mt-2">Gestionar</a>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?php echo count($citas); ?></h3>
                        <p>Próximas Citas</p>
                        <a href="cita_agendar.php" class="btn btn-sm btn-light mt-2">Agendar</a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">Próximas Citas</div>
                <div class="card-body">
                    <?php if(empty($citas)): ?>
                        <p class="text-muted">No tienes citas programadas</p>
                        <a href="cita_agendar.php" class="btn btn-primary">Agendar Cita</a>
                    <?php else: ?>
                        <?php foreach($citas as $c): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <strong><?php echo date('d/m/Y H:i', strtotime($c['fecha_hora_inicio'])); ?></strong><br>
                            <?php echo $c['servicio']; ?> - Groomer: <?php echo $c['groomer']; ?>
                            <span class="badge bg-warning float-end"><?php echo $c['estado']; ?></span>
                        </div>
                        <?php endforeach; ?>
                        <a href="citas.php" class="btn btn-sm btn-outline-primary mt-2">Ver todas</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
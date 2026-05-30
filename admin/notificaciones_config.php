<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$mensaje = '';

// Crear tabla de configuración de notificaciones si no existe
$db->exec("CREATE TABLE IF NOT EXISTS notificaciones_programadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT UNSIGNED NULL,
    cliente_id INT UNSIGNED NOT NULL,
    tipo_evento VARCHAR(50) NOT NULL,
    canal VARCHAR(20) NOT NULL,
    destino VARCHAR(120) NOT NULL,
    mensaje TEXT,
    fecha_programada DATETIME NOT NULL,
    fecha_enviada DATETIME NULL,
    estado ENUM('pendiente','enviada','fallida') DEFAULT 'pendiente',
    intentos TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Enviar notificaciones manualmente
if(isset($_GET['enviar_notificacion'])){
    $id = $_GET['enviar_notificacion'];
    $stmt = $db->prepare("SELECT * FROM notificaciones_programadas WHERE id = ?");
    $stmt->execute([$id]);
    $notif = $stmt->fetch();
    
    if($notif){
        $enviado = false;
        
        if($notif['canal'] == 'email'){
            // Usar mailer para enviar email
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
            $enviado = @mail($notif['destino'], "Notificación - " . SITE_NAME, nl2br($notif['mensaje']), $headers);
        } elseif($notif['canal'] == 'whatsapp'){
            // Simular envío de WhatsApp (en producción conectar con API real)
            $numero = $notif['destino'];
            $whatsappLink = "https://wa.me/{$numero}?text=" . urlencode($notif['mensaje']);
            // Registramos como enviado (simulado)
            $enviado = true;
            // Podrías guardar el link en un log
            error_log("WhatsApp a {$numero}: {$notif['mensaje']}");
        }
        
        if($enviado){
            $stmt = $db->prepare("UPDATE notificaciones_programadas SET estado = 'enviada', fecha_enviada = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = '<div class="alert alert-success">Notificación enviada correctamente</div>';
        } else {
            $stmt = $db->prepare("UPDATE notificaciones_programadas SET intentos = intentos + 1 WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = '<div class="alert alert-danger">Error al enviar la notificación</div>';
        }
    }
}

// Obtener notificaciones
$notificaciones = $db->query("
    SELECT n.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.telefono, c.email
    FROM notificaciones_programadas n
    JOIN clientes c ON n.cliente_id = c.id
    ORDER BY n.fecha_programada DESC
    LIMIT 50
")->fetchAll();

// Estadísticas
$pendientes = $db->query("SELECT COUNT(*) FROM notificaciones_programadas WHERE estado = 'pendiente' AND fecha_programada <= NOW()")->fetchColumn();
$enviadas_hoy = $db->query("SELECT COUNT(*) FROM notificaciones_programadas WHERE DATE(fecha_enviada) = CURDATE()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificaciones - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
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
                <li><a class="nav-link" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                <li><a class="nav-link" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link active" href="notificaciones_config.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-bell"></i> Notificaciones</h2>
            </div>
            
            <?php echo $mensaje; ?>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h3><?php echo $pendientes; ?></h3>
                            <p>Pendientes de envío</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h3><?php echo $enviadas_hoy; ?></h3>
                            <p>Enviadas hoy</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h3><i class="fas fa-calendar"></i></h3>
                            <p>Configuración en config.php</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de notificaciones -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Historial de Notificaciones</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($notificaciones)): ?>
                        <p class="text-muted">No hay notificaciones registradas</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha Programada</th>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Canal</th>
                                    <th>Destino</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($notificaciones as $n): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($n['fecha_programada'])); ?></td>
                                    <td><?php echo htmlspecialchars($n['cliente_nombre'] . ' ' . $n['cliente_apellido']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $n['tipo_evento']; ?></span></td>
                                    <td>
                                        <span class="badge bg-<?php echo $n['canal']=='email'?'info':($n['canal']=='whatsapp'?'success':'secondary'); ?>">
                                            <?php echo $n['canal']; ?>
                                        </span>
                                     </div>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($n['destino']); ?></small></td>
                                    <td>
                                        <?php if($n['estado'] == 'pendiente'): ?>
                                            <span class="badge bg-warning">Pendiente</span>
                                        <?php elseif($n['estado'] == 'enviada'): ?>
                                            <span class="badge bg-success">Enviada <?php echo $n['fecha_enviada'] ? date('H:i', strtotime($n['fecha_enviada'])) : ''; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Fallida</span>
                                        <?php endif; ?>
                                     </div>
                                    </td>
                                    <td>
                                        <?php if($n['estado'] == 'pendiente'): ?>
                                            <a href="notificaciones_config.php?enviar_notificacion=<?php echo $n['id']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('¿Enviar esta notificación ahora?')">
                                                <i class="fas fa-paper-plane"></i> Enviar
                                            </a>
                                        <?php endif; ?>
                                     </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Script para enviar notificaciones pendientes automáticamente -->
            <div class="card mt-4 bg-light">
                <div class="card-body">
                    <h6><i class="fas fa-code"></i> Configuración para envío automático</h6>
                    <p class="small text-muted">
                        Para enviar notificaciones automáticamente, configura un CRON (o tarea programada en Windows) que ejecute:<br>
                        <code>curl <?php echo SITE_URL; ?>api/notificaciones.php?action=enviar_pendientes</code>
                    </p>
                    <hr>
                    <h6><i class="fas fa-envelope"></i> Probar envío de email</h6>
                    <form method="POST" action="test_email.php" target="_blank" class="row g-2">
                        <div class="col-auto">
                            <input type="email" name="email" class="form-control" placeholder="Email de prueba" value="<?php echo SMTP_FROM; ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-secondary">Probar email</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
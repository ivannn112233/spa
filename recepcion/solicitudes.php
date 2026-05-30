<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$mensaje = '';

// Obtener datos del usuario para avatar
$usuario_nombre = 'Recepción';
$usuario_apellido = '';
$iniciales = 'R';

try {
    $stmt = $db->prepare("SELECT nombre, apellido FROM clientes WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch();
    if($usuario && is_array($usuario)){
        $usuario_nombre = $usuario['nombre'] ?? 'Recepción';
        $usuario_apellido = $usuario['apellido'] ?? '';
        $iniciales = strtoupper(substr($usuario_nombre, 0, 1) . ($usuario_apellido ? substr($usuario_apellido, 0, 1) : ''));
    }
} catch(PDOException $e) {
    $iniciales = 'R';
}

// Aprobar solicitud de cita
if(isset($_GET['aprobar'])){
    $cita_id = (int)$_GET['aprobar'];
    $stmt = $db->prepare("UPDATE citas SET estado = 'confirmada' WHERE id = ?");
    if($stmt->execute([$cita_id])){
        $mensaje = '<div class="alert alert-success">Cita confirmada</div>';
        
        // Obtener datos para notificar al cliente
        $stmt2 = $db->prepare("
            SELECT c.*, u.email, cl.telefono, cl.canal_notificacion, cl.nombre, cl.apellido
            FROM citas c
            JOIN clientes cl ON c.cliente_id = cl.id
            JOIN usuarios u ON cl.usuario_id = u.id
            WHERE c.id = ?
        ");
        $stmt2->execute([$cita_id]);
        $cita = $stmt2->fetch();
        
        // Enviar notificación
        if($cita && is_array($cita)){
            $fecha = date('d/m/Y H:i', strtotime($cita['fecha_hora_inicio'] ?? 'now'));
            $mensaje_notif = "Tu cita ha sido confirmada para el " . $fecha;
            if(isset($cita['canal_notificacion']) && $cita['canal_notificacion'] == 'email' && !empty($cita['email'])){
                @mail($cita['email'], "Cita confirmada - " . SITE_NAME, $mensaje_notif);
            }
        }
    }
}

// Rechazar solicitud
if(isset($_GET['rechazar'])){
    $cita_id = (int)$_GET['rechazar'];
    $stmt = $db->prepare("UPDATE citas SET estado = 'cancelada', motivo_cancelacion = 'Solicitud rechazada por recepción' WHERE id = ?");
    if($stmt->execute([$cita_id])){
        $mensaje = '<div class="alert alert-warning">Solicitud rechazada</div>';
    }
}

// Obtener solicitudes pendientes (citas agendadas por clientes)
$solicitudes = [];
try {
    $result = $db->query("
        SELECT c.*, m.nombre as mascota, 
               cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
               cl.telefono, cl.canal_notificacion,
               u.email,
               s.nombre as servicio, 
               gr.nombre as groomer, gr.apellido as groomer_apellido
        FROM citas c
        JOIN mascotas m ON m.id = c.mascota_id
        JOIN clientes cl ON cl.id = c.cliente_id
        JOIN usuarios u ON cl.usuario_id = u.id
        JOIN servicios s ON s.id = c.servicio_id
        JOIN groomers gr ON gr.id = c.groomer_id
        WHERE c.estado = 'agendada' AND c.creado_por IS NOT NULL
        ORDER BY c.created_at DESC
    ");
    if($result){
        $solicitudes = $result->fetchAll();
    }
} catch(PDOException $e) {
    $solicitudes = [];
}

$total_pendientes = is_array($solicitudes) ? count($solicitudes) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitudes - Recepción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%);
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            transition: all 0.3s;
            border-radius: 8px;
            margin: 2px 8px;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(52, 152, 219, 0.3);
            padding-left: 28px;
        }
        
        .sidebar .nav-link.active {
            background: #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
            margin: 0 auto 10px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        
        .btn-logout {
            background: #e74c3c;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .solicitud-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .solicitud-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <nav class="col-md-2 sidebar">
            <div class="text-center py-4">
                <div class="user-avatar">
                    <?php echo $iniciales; ?>
                </div>
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($usuario_nombre . ' ' . $usuario_apellido); ?></h5>
                <small class="text-muted"><i class="fas fa-phone-alt"></i> Recepción</small>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a class="nav-link" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
                <li><a class="nav-link" href="calendario.php"><i class="fas fa-calendar-week"></i> Calendario</a></li>
                <li><a class="nav-link active" href="solicitudes.php"><i class="fas fa-inbox"></i> Solicitudes</a></li>
                <li><a class="nav-link" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><a class="nav-link" href="punto_venta.php"><i class="fas fa-shopping-cart"></i> Punto de Venta</a></li>
                <li><hr class="bg-secondary mx-3 my-2"></li>
                <li><a class="nav-link text-warning" href="../cambiar_password.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="col-md-10 main-content">
            <!-- Header con botón de salir -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><i class="fas fa-inbox text-primary"></i> Solicitudes de Cita</h1>
                    <small class="text-muted">Revisa y confirma las solicitudes de los clientes</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <?php echo $mensaje; ?>
            
            <?php if(empty($solicitudes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5>No hay solicitudes pendientes</h5>
                    <p class="text-muted">Todas las solicitudes han sido procesadas</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($solicitudes as $s): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card solicitud-card border-warning">
                            <div class="card-header bg-warning text-white d-flex justify-content-between">
                                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($s['fecha_hora_inicio'] ?? 'now')); ?></span>
                                <span class="badge bg-dark">Solicitado: <?php echo date('d/m/Y', strtotime($s['created_at'] ?? 'now')); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <p><strong><i class="fas fa-user"></i> Cliente:</strong><br><?php echo htmlspecialchars(($s['cliente_nombre'] ?? '') . ' ' . ($s['cliente_apellido'] ?? '')); ?></p>
                                        <p><strong><i class="fas fa-paw"></i> Mascota:</strong><br><?php echo htmlspecialchars($s['mascota'] ?? ''); ?></p>
                                        <p><strong><i class="fas fa-cut"></i> Servicio:</strong><br><?php echo htmlspecialchars($s['servicio'] ?? ''); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <p><strong><i class="fas fa-user-md"></i> Groomer:</strong><br><?php echo htmlspecialchars(($s['groomer'] ?? '') . ' ' . ($s['groomer_apellido'] ?? '')); ?></p>
                                        <p><strong><i class="fas fa-phone"></i> Contacto:</strong><br><?php echo htmlspecialchars($s['telefono'] ?? 'No registrado'); ?></p>
                                        <p><strong><i class="fas fa-envelope"></i> Email:</strong><br><?php echo htmlspecialchars($s['email'] ?? ''); ?></p>
                                    </div>
                                </div>
                                <?php if(!empty($s['notas'])): ?>
                                <div class="alert alert-info mt-2 small">
                                    <i class="fas fa-sticky-note"></i> <?php echo nl2br(htmlspecialchars($s['notas'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer d-flex gap-2">
                                <a href="solicitudes.php?aprobar=<?php echo $s['id']; ?>" class="btn btn-success flex-grow-1" onclick="return confirm('¿Confirmar esta cita?')">
                                    <i class="fas fa-check"></i> Aprobar
                                </a>
                                <a href="solicitudes.php?rechazar=<?php echo $s['id']; ?>" class="btn btn-danger flex-grow-1" onclick="return confirm('¿Rechazar esta solicitud?')">
                                    <i class="fas fa-times"></i> Rechazar
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <footer class="text-center text-muted mt-4 pt-3 border-top">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos los derechos reservados</small>
            </footer>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
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
    SELECT m.*, m.tamano, m.especie, m.raza 
    FROM mascotas m
    JOIN mascota_dueno md ON m.id = md.mascota_id
    WHERE md.cliente_id = ?
");
$stmt->execute([$cliente['id']]);
$mascotas = $stmt->fetchAll();

// Próximas citas
$stmt = $db->prepare("
    SELECT c.*, s.nombre as servicio, gr.nombre as groomer, gr.apellido as groomer_apellido
    FROM citas c
    JOIN servicios s ON c.servicio_id = s.id
    JOIN groomers gr ON c.groomer_id = gr.id
    WHERE c.cliente_id = ? AND c.fecha_hora_inicio > NOW() AND c.estado IN ('agendada', 'confirmada')
    ORDER BY c.fecha_hora_inicio ASC LIMIT 5
");
$stmt->execute([$cliente['id']]);
$citas = $stmt->fetchAll();

// Promociones activas
$promociones = $db->query("
    SELECT * FROM promociones 
    WHERE activo = 1 AND fecha_inicio <= CURDATE() AND fecha_fin >= CURDATE()
    ORDER BY fecha_fin ASC LIMIT 3
")->fetchAll();

// Notificaciones recientes
$stmt = $db->prepare("
    SELECT * FROM notificaciones 
    WHERE cliente_id = ? 
    ORDER BY created_at DESC LIMIT 5
");
$stmt->execute([$cliente['id']]);
$notificaciones = $stmt->fetchAll();

// Obtener iniciales para avatar
$nombreCompleto = $cliente['nombre'] . ' ' . $cliente['apellido'];
$iniciales = strtoupper(substr($cliente['nombre'], 0, 1) . substr($cliente['apellido'], 0, 1));

// Contar notificaciones no leídas
$notificaciones_no_leidas = count(array_filter($notificaciones, function($n) {
    return !isset($n['leida']) || !$n['leida'];
}));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Cuenta - <?php echo SITE_NAME; ?></title>
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
        
        .stat-card {
            border-radius: 15px;
            padding: 20px;
            color: white;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            overflow: hidden;
            position: relative;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.85rem;
        }
        
        .stat-card i {
            position: absolute;
            right: 20px;
            bottom: 20px;
            font-size: 3rem;
            opacity: 0.2;
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
        
        .notificacion-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            .stat-card h3 {
                font-size: 1.5rem;
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
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($nombreCompleto); ?></h5>
                <small class="text-muted"><i class="fas fa-user"></i> Cliente</small>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link active" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a class="nav-link" href="mascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="historial.php"><i class="fas fa-history"></i> Historial</a></li>
                <li><a class="nav-link" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link" href="notificaciones.php">
                    <i class="fas fa-bell"></i> Notificaciones
                    <?php if($notificaciones_no_leidas > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $notificaciones_no_leidas; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a class="nav-link" href="tienda/"><i class="fas fa-store"></i> Tienda</a></li>
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
                    <h1 class="h3 mb-0"><i class="fas fa-home text-primary"></i> Panel de Cliente</h1>
                    <small class="text-muted">Bienvenido, <?php echo htmlspecialchars($cliente['nombre']); ?></small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h3><?php echo count($mascotas); ?></h3><p>Mascotas Registradas</p></div>
                            <i class="fas fa-paw fa-2x"></i>
                        </div>
                        <a href="mascotas.php" class="btn btn-sm btn-light mt-2">Gestionar</a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h3><?php echo count($citas); ?></h3><p>Próximas Citas</p></div>
                            <i class="fas fa-calendar-check fa-2x"></i>
                        </div>
                        <a href="cita_agendar.php" class="btn btn-sm btn-light mt-2">Agendar</a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h3><i class="fas fa-store"></i></h3><p>Tienda de Productos</p></div>
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                        <a href="tienda/" class="btn btn-sm btn-light mt-2">Comprar</a>
                    </div>
                </div>
            </div>
            
            <!-- Promociones -->
            <?php if(!empty($promociones)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-tags"></i> Promociones Activas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($promociones as $p): ?>
                        <div class="col-md-4 mb-2">
                            <div class="border rounded p-2 text-center bg-light">
                                <strong><?php echo htmlspecialchars($p['nombre']); ?></strong><br>
                                <span class="badge bg-success">
                                    <?php if($p['tipo'] == 'porcentaje') echo $p['valor'] . '% OFF'; 
                                    elseif($p['tipo'] == 'monto_fijo') echo 'Bs. ' . $p['valor'] . ' OFF';
                                    elseif($p['tipo'] == '2x1') echo '2x1';
                                    else echo 'Gratis'; ?>
                                </span>
                                <br><small>Código: <strong><?php echo $p['codigo']; ?></strong></small>
                                <br><small>Válido hasta: <?php echo date('d/m/Y', strtotime($p['fecha_fin'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Próximas Citas -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Próximas Citas</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($citas)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">No tienes citas programadas</p>
                                    <a href="cita_agendar.php" class="btn btn-primary btn-sm">Agendar Cita</a>
                                </div>
                            <?php else: ?>
                                <?php foreach($citas as $c): ?>
                                <div class="border-bottom pb-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><i class="fas fa-calendar-day"></i> <?php echo date('d/m/Y H:i', strtotime($c['fecha_hora_inicio'])); ?></strong>
                                            <br><small><?php echo htmlspecialchars($c['servicio']); ?></small>
                                            <br><small class="text-muted"><i class="fas fa-user-md"></i> Groomer: <?php echo htmlspecialchars($c['groomer'] . ' ' . $c['groomer_apellido']); ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $c['estado'] == 'confirmada' ? 'success' : 'warning'; ?>"><?php echo ucfirst($c['estado']); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <div class="mt-3 text-end">
                                    <a href="citas.php" class="btn btn-sm btn-outline-primary">Ver todas <i class="fas fa-arrow-right"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Últimas Notificaciones -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-bell"></i> Últimas Notificaciones</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($notificaciones)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-bell-slash fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">No hay notificaciones recientes</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($notificaciones as $n): ?>
                                <div class="border-bottom pb-2 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <div class="small">
                                            <i class="fas fa-bell text-<?php echo !isset($n['leida']) || !$n['leida'] ? 'warning' : 'secondary'; ?>"></i>
                                            <?php echo nl2br(htmlspecialchars(substr($n['mensaje'], 0, 80))); ?>
                                            <?php if(strlen($n['mensaje']) > 80): ?>...<?php endif; ?>
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></small>
                                </div>
                                <?php endforeach; ?>
                                <div class="mt-3 text-end">
                                    <a href="notificaciones.php" class="btn btn-sm btn-outline-info">Ver todas <i class="fas fa-arrow-right"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
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
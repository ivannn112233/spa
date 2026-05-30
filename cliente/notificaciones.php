<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

// Obtener datos del cliente
$stmt = $db->prepare("SELECT id, nombre, apellido FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();
$cliente_id = $cliente['id'];

// Obtener iniciales para avatar
$iniciales = strtoupper(substr($cliente['nombre'], 0, 1) . substr($cliente['apellido'], 0, 1));

// Obtener notificaciones
$notificaciones = $db->prepare("
    SELECT * FROM notificaciones 
    WHERE cliente_id = ? 
    ORDER BY created_at DESC
");
$notificaciones->execute([$cliente_id]);
$notificaciones = $notificaciones->fetchAll();

// Contar no leídas
$no_leidas = 0;
foreach($notificaciones as $n){
    if(!isset($n['leida']) || !$n['leida']){
        $no_leidas++;
    }
}

// Marcar como leídas
if(isset($_GET['marcar_leidas'])){
    $db->prepare("UPDATE notificaciones SET leida = 1 WHERE cliente_id = ?")->execute([$cliente_id]);
    header("Location: notificaciones.php");
    exit;
}

// Marcar una como leída
if(isset($_GET['marcar_leida'])){
    $id = $_GET['marcar_leida'];
    $db->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND cliente_id = ?")->execute([$id, $cliente_id]);
    header("Location: notificaciones.php");
    exit;
}

// Eliminar notificación
if(isset($_GET['eliminar'])){
    $id = $_GET['eliminar'];
    $db->prepare("DELETE FROM notificaciones WHERE id = ? AND cliente_id = ?")->execute([$id, $cliente_id]);
    header("Location: notificaciones.php?msg=Notificación eliminada");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificaciones - <?php echo SITE_NAME; ?></title>
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
        
        .notificacion-item {
            border-left: 4px solid #3498db;
            margin-bottom: 12px;
            transition: all 0.3s;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .notificacion-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .notificacion-item.no-leida {
            border-left-color: #e74c3c;
            background: #fff5f5;
        }
        
        .notificacion-item.no-leida:hover {
            background: #ffe8e8;
        }
        
        .badge-nueva {
            background: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .stat-card {
            border-radius: 12px;
            padding: 15px;
            color: white;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
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
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></h5>
                <small class="text-muted"><i class="fas fa-user"></i> Cliente</small>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a class="nav-link" href="mascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="historial.php"><i class="fas fa-history"></i> Historial</a></li>
                <li><a class="nav-link" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link active" href="notificaciones.php"><i class="fas fa-bell"></i> Notificaciones
                    <?php if($no_leidas > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $no_leidas; ?></span>
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
                    <h1 class="h3 mb-0"><i class="fas fa-bell text-primary"></i> Notificaciones</h1>
                    <small class="text-muted">Mantente informado sobre tus citas y promociones</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-md-6 mb-2">
                    <div class="stat-card bg-primary">
                        <h3 class="mb-0"><?php echo count($notificaciones); ?></h3>
                        <small>Total Notificaciones</small>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="stat-card bg-danger">
                        <h3 class="mb-0"><?php echo $no_leidas; ?></h3>
                        <small>No leídas</small>
                    </div>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <?php if(!empty($notificaciones)): ?>
            <div class="d-flex justify-content-end mb-3 gap-2">
                <a href="notificaciones.php?marcar_leidas=1" class="btn btn-sm btn-secondary" onclick="return confirm('¿Marcar todas las notificaciones como leídas?')">
                    <i class="fas fa-check-double"></i> Marcar todas como leídas
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Lista de notificaciones -->
            <?php if(empty($notificaciones)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                    <h5>No tienes notificaciones</h5>
                    <p class="text-muted">Te notificaremos cuando haya novedades</p>
                    <a href="index.php" class="btn btn-primary">Ir al dashboard</a>
                </div>
            <?php else: ?>
                <?php foreach($notificaciones as $n): 
                    $es_nueva = !isset($n['leida']) || !$n['leida'];
                    $icono = '';
                    switch($n['tipo_evento']){
                        case 'cita_confirmada':
                            $icono = 'fa-calendar-check';
                            $color = 'success';
                            break;
                        case 'recordatorio_24h':
                        case 'recordatorio_2h':
                            $icono = 'fa-clock';
                            $color = 'warning';
                            break;
                        case 'mascota_lista':
                            $icono = 'fa-paw';
                            $color = 'info';
                            break;
                        case 'promocion':
                            $icono = 'fa-tag';
                            $color = 'primary';
                            break;
                        default:
                            $icono = 'fa-bell';
                            $color = 'secondary';
                    }
                ?>
                <div class="notificacion-item p-3 shadow-sm <?php echo $es_nueva ? 'no-leida' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex gap-3">
                            <div class="text-<?php echo $color; ?> fs-4">
                                <i class="fas <?php echo $icono; ?>"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <strong class="text-<?php echo $color; ?>"><?php echo ucfirst(str_replace('_', ' ', $n['tipo_evento'])); ?></strong>
                                    <?php if($es_nueva): ?>
                                        <span class="badge-nueva">Nueva</span>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($n['mensaje'])); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?>
                                    <?php if($n['canal']): ?>
                                        | <i class="fas fa-<?php echo $n['canal']=='email'?'envelope':'comment'; ?>"></i> Enviado por <?php echo ucfirst($n['canal']); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <div class="btn-group">
                            <?php if($es_nueva): ?>
                            <a href="notificaciones.php?marcar_leida=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Marcar como leída">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>
                            <a href="notificaciones.php?eliminar=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar esta notificación?')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
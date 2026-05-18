<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_GROOMER);

$db = Database::getInstance()->getConnection();

// Obtener ID del groomer
$stmt = $db->prepare("SELECT id, nombre, apellido, especialidad, telefono FROM groomers WHERE usuario_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$groomer = $stmt->fetch();

if(!$groomer){
    die("Error: No se encontró el perfil de groomer");
}

$groomerId = $groomer['id'];

// Estadísticas
$stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE groomer_id = :groomer_id AND DATE(fecha_hora_inicio) = CURDATE()");
$stmt->execute([':groomer_id' => $groomerId]);
$citas_hoy = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE groomer_id = :groomer_id AND estado IN ('agendada', 'confirmada') AND fecha_hora_inicio > NOW()");
$stmt->execute([':groomer_id' => $groomerId]);
$citas_pendientes = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE groomer_id = :groomer_id AND estado = 'en_progreso'");
$stmt->execute([':groomer_id' => $groomerId]);
$citas_progreso = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE groomer_id = :groomer_id AND estado = 'completada' AND MONTH(fecha_hora_inicio) = MONTH(CURDATE())");
$stmt->execute([':groomer_id' => $groomerId]);
$citas_completadas = $stmt->fetch()['total'];

// Citas de hoy
$stmt = $db->prepare("
    SELECT c.*, m.nombre as mascota_nombre, m.especie, m.raza,
           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido, cl.telefono as cliente_telefono,
           s.nombre as servicio_nombre, s.duracion_base_minutos,
           f.id as ficha_id
    FROM citas c
    JOIN mascotas m ON m.id = c.mascota_id
    JOIN clientes cl ON cl.id = c.cliente_id
    JOIN servicios s ON s.id = c.servicio_id
    LEFT JOIN fichas_grooming f ON f.cita_id = c.id
    WHERE c.groomer_id = :groomer_id AND DATE(c.fecha_hora_inicio) = CURDATE()
    ORDER BY c.fecha_hora_inicio ASC
");
$stmt->execute([':groomer_id' => $groomerId]);
$citas_hoy_detalle = $stmt->fetchAll();

// Próximas citas
$stmt = $db->prepare("
    SELECT c.*, m.nombre as mascota_nombre,
           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
           s.nombre as servicio_nombre
    FROM citas c
    JOIN mascotas m ON m.id = c.mascota_id
    JOIN clientes cl ON cl.id = c.cliente_id
    JOIN servicios s ON s.id = c.servicio_id
    WHERE c.groomer_id = :groomer_id 
    AND c.fecha_hora_inicio > NOW()
    AND c.estado IN ('agendada', 'confirmada')
    ORDER BY c.fecha_hora_inicio ASC
    LIMIT 5
");
$stmt->execute([':groomer_id' => $groomerId]);
$proximas_citas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Groomer Panel - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            background: #34495e;
            padding-left: 25px;
        }
        .sidebar .nav-link.active {
            background: #3498db;
        }
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
        }
        .main-content {
            margin-left: 16.666%;
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h2 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            margin: 0 auto 10px;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-md-block sidebar p-0">
            <div class="text-center py-4 border-bottom border-secondary">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($groomer['nombre'], 0, 1) . substr($groomer['apellido'], 0, 1)); ?>
                </div>
                <h6 class="text-white mb-0"><?php echo htmlspecialchars($groomer['nombre'] . ' ' . $groomer['apellido']); ?></h6>
                <small class="text-muted">Groomer</small>
            </div>
            <ul class="nav flex-column mt-2">
                <li>
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="citas.php">
                        <i class="fas fa-calendar-alt"></i> Mis Citas
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="ficha_grooming.php">
                        <i class="fas fa-clipboard-list"></i> Fichas
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="checklist.php">
                        <i class="fas fa-check-square"></i> Checklist
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="perfil.php">
                        <i class="fas fa-user-circle"></i> Mi Perfil
                    </a>
                </li>
                <li class="mt-3">
                    <hr class="bg-secondary mx-3 my-1">
                </li>
                <li>
                    <a class="nav-link text-warning" href="../cambiar_password.php">
                        <i class="fas fa-key"></i> Cambiar Contraseña
                    </a>
                </li>
                <li>
                    <a class="nav-link text-danger" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="col-md-10 ms-sm-auto main-content px-md-4">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-cut"></i> Panel de Groomer</h1>
                <span><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-primary">
                        <div class="d-flex justify-content-between">
                            <div><h2><?php echo $citas_hoy; ?></h2><p>Citas Hoy</p></div>
                            <i class="fas fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-warning">
                        <div class="d-flex justify-content-between">
                            <div><h2><?php echo $citas_pendientes; ?></h2><p>Pendientes</p></div>
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-info">
                        <div class="d-flex justify-content-between">
                            <div><h2><?php echo $citas_progreso; ?></h2><p>En Progreso</p></div>
                            <i class="fas fa-spinner fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-success">
                        <div class="d-flex justify-content-between">
                            <div><h2><?php echo $citas_completadas; ?></h2><p>Completadas (Mes)</p></div>
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Citas de Hoy -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-day"></i> Mis Citas de Hoy</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($citas_hoy_detalle)): ?>
                        <div class="alert alert-info">No tienes citas programadas para hoy.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr><th>Hora</th><th>Cliente</th><th>Mascota</th><th>Servicio</th><th>Estado</th><th>Ficha</th><th>Acciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($citas_hoy_detalle as $cita): 
                                        $badge = 'secondary';
                                        if($cita['estado'] == 'agendada') $badge = 'warning';
                                        elseif($cita['estado'] == 'confirmada') $badge = 'info';
                                        elseif($cita['estado'] == 'en_progreso') $badge = 'primary';
                                        elseif($cita['estado'] == 'completada') $badge = 'success';
                                    ?>
                                    <tr>
                                        <td><?php echo date('H:i', strtotime($cita['fecha_hora_inicio'])); ?> - <?php echo date('H:i', strtotime($cita['fecha_hora_fin'])); ?></td>
                                        <td><?php echo htmlspecialchars($cita['cliente_nombre'] . ' ' . $cita['cliente_apellido']); ?><br><small>📞 <?php echo $cita['cliente_telefono'] ?? '-'; ?></small></td>
                                        <td><strong><?php echo htmlspecialchars($cita['mascota_nombre']); ?></strong><br><small><?php echo $cita['especie']; ?> <?php echo $cita['raza']; ?></small></td>
                                        <td><?php echo htmlspecialchars($cita['servicio_nombre']); ?><br><small><?php echo $cita['duracion_base_minutos']; ?> min</small></td>
                                        <td><span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($cita['estado']); ?></span></td>
                                        <td><?php echo $cita['ficha_id'] ? '<span class="badge bg-success">✓ Completada</span>' : '<span class="badge bg-secondary">Pendiente</span>'; ?></td>
                                        <td>
                                            <a href="ficha_grooming.php?cita_id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-primary">Ficha</a>
                                            <?php if($cita['estado'] != 'completada' && $cita['estado'] != 'cancelada'): ?>
                                            <a href="citas.php?cambiar_estado=<?php echo $cita['id']; ?>&estado=en_progreso" class="btn btn-sm btn-success" onclick="return confirm('¿Iniciar esta cita?')">Iniciar</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Próximas Citas -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-week"></i> Próximas Citas</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($proximas_citas)): ?>
                        <p class="text-muted">No hay citas próximas</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Fecha</th><th>Hora</th><th>Cliente</th><th>Mascota</th><th>Servicio</th></tr></thead>
                                <tbody>
                                    <?php foreach($proximas_citas as $cita): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($cita['fecha_hora_inicio'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($cita['fecha_hora_inicio'])); ?></td>
                                        <td><?php echo htmlspecialchars($cita['cliente_nombre'] . ' ' . $cita['cliente_apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['mascota_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['servicio_nombre']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 text-end">
                            <a href="citas.php" class="btn btn-sm btn-outline-success">Ver todas →</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
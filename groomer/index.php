<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_GROOMER);

$db = Database::getInstance()->getConnection();

// Obtener ID del groomer
$stmt = $db->prepare("SELECT id, nombre, apellido, especialidad FROM groomers WHERE usuario_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$groomer = $stmt->fetch();
$groomerId = $groomer['id'];

// Verificar si la tabla consumo_insumos existe
$tablaExiste = false;
try {
    $stmt = $db->query("SHOW TABLES LIKE 'consumo_insumos'");
    $tablaExiste = $stmt->rowCount() > 0;
} catch(PDOException $e) {
    $tablaExiste = false;
}

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

// Insumos asignados hoy (solo si la tabla existe)
$insumos_hoy = [];
if($tablaExiste){
    try {
        $stmt = $db->prepare("
            SELECT i.*, p.nombre as producto_nombre, p.sku 
            FROM consumo_insumos i
            JOIN productos p ON i.producto_id = p.id
            WHERE i.groomer_id = :groomer_id AND DATE(i.created_at) = CURDATE()
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([':groomer_id' => $groomerId]);
        $insumos_hoy = $stmt->fetchAll();
    } catch(PDOException $e) {
        $insumos_hoy = [];
    }
}

// Citas de hoy con detalles
$stmt = $db->prepare("
    SELECT c.*, m.nombre as mascota_nombre, m.especie, m.raza, m.tamano,
           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido, cl.telefono as cliente_telefono,
           s.nombre as servicio_nombre, s.duracion_base_minutos,
           f.id as ficha_id, f.estado_inicial, f.estado_final
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

// Obtener iniciales para avatar
$iniciales = strtoupper(substr($groomer['nombre'], 0, 1) . substr($groomer['apellido'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Groomer Panel - <?php echo SITE_NAME; ?></title>
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
        
        .stat-card h2 {
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
        
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
            .stat-card h2 {
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
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($groomer['nombre'] . ' ' . $groomer['apellido']); ?></h5>
                <small class="text-muted"><i class="fas fa-cut"></i> Groomer</small>
                <?php if($groomer['especialidad']): ?>
                    <div class="mt-1"><span class="badge bg-info"><?php echo htmlspecialchars($groomer['especialidad']); ?></span></div>
                <?php endif; ?>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link active" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="ficha_grooming.php"><i class="fas fa-clipboard-list"></i> Fichas</a></li>
                <li><a class="nav-link" href="checklist.php"><i class="fas fa-check-square"></i> Checklist</a></li>
                <li><a class="nav-link" href="insumos_recibidos.php"><i class="fas fa-boxes"></i> Insumos</a></li>
                <li><a class="nav-link" href="galeria_fotos.php"><i class="fas fa-camera"></i> Galería</a></li>
                <li><a class="nav-link" href="perfil.php"><i class="fas fa-user-circle"></i> Mi Perfil</a></li>
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
                    <h1 class="h3 mb-0"><i class="fas fa-cut text-primary"></i> Panel de Groomer</h1>
                    <small class="text-muted">Bienvenido, <?php echo htmlspecialchars($groomer['nombre']); ?></small>
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
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h2><?php echo $citas_hoy; ?></h2><p>Citas Hoy</p></div>
                            <i class="fas fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-warning">
                        <div class="d-flex justify-content-between">
                            <div><h2><?php echo $citas_pendientes; ?></h2><p>Pendientes</p></div>
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-info">
                        <div class="d-flex justify-content-between">
                            <div><h2><?php echo $citas_progreso; ?></h2><p>En Progreso</p></div>
                            <i class="fas fa-spinner fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-success">
                        <div class="d-flex justify-content-between">
                            <div><h2><?php echo $citas_completadas; ?></h2><p>Completadas (Mes)</p></div>
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Insumos asignados hoy -->
            <?php if($tablaExiste && !empty($insumos_hoy)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-boxes"></i> Insumos Asignados Hoy</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr><th>Producto</th><th>Cantidad</th><th>Hora</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($insumos_hoy as $i): ?>
                                <tr>
                                    <td><?php echo $i['producto_nombre']; ?> (<?php echo $i['sku']; ?>)</a></td>
                                    <td><span class="badge bg-success"><?php echo $i['cantidad']; ?> unidades</span></td>
                                    <td><?php echo date('H:i', strtotime($i['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Citas de hoy -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-day"></i> Mis Citas de Hoy</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($citas_hoy_detalle)): ?>
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-smile-wink fa-2x mb-2"></i>
                            <p class="mb-0">No tienes citas programadas para hoy. ¡Disfruta tu día!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Hora</th>
                                        <th>Cliente</th>
                                        <th>Mascota</th>
                                        <th>Servicio</th>
                                        <th>Estado</th>
                                        <th>Ficha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($citas_hoy_detalle as $cita): 
                                        $badgeClass = 'secondary';
                                        $estadoTexto = ucfirst($cita['estado']);
                                        if($cita['estado'] == 'agendada') {
                                            $badgeClass = 'warning';
                                        } elseif($cita['estado'] == 'confirmada') {
                                            $badgeClass = 'info';
                                        } elseif($cita['estado'] == 'en_progreso') {
                                            $badgeClass = 'primary';
                                            $estadoTexto = 'En Progreso';
                                        } elseif($cita['estado'] == 'completada') {
                                            $badgeClass = 'success';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('H:i', strtotime($cita['fecha_hora_inicio'])); ?></strong>
                                            <br><small class="text-muted"><?php echo date('H:i', strtotime($cita['fecha_hora_fin'])); ?></small>
                                         </div>
                                        <td>
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($cita['cliente_nombre'] . ' ' . $cita['cliente_apellido']); ?>
                                            <br><small class="text-muted"><i class="fas fa-phone"></i> <?php echo $cita['cliente_telefono'] ?? 'Sin teléfono'; ?></small>
                                         </div>
                                        <td>
                                            <strong><i class="fas fa-paw"></i> <?php echo htmlspecialchars($cita['mascota_nombre']); ?></strong>
                                            <br><small class="text-muted"><?php echo ucfirst($cita['especie']); ?> <?php echo $cita['raza'] ? '- ' . $cita['raza'] : ''; ?></small>
                                            <?php if(isset($cita['tamano'])): ?>
                                                <br><small class="text-muted">Tamaño: <strong><?php echo ucfirst($cita['tamano']); ?></strong></small>
                                            <?php endif; ?>
                                         </div>
                                        <td>
                                            <?php echo htmlspecialchars($cita['servicio_nombre']); ?>
                                            <br><small class="text-muted"><?php echo $cita['duracion_base_minutos']; ?> min</small>
                                         </div>
                                        <td>
                                            <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $estadoTexto; ?></span>
                                         </div>
                                        <td>
                                            <?php if($cita['ficha_id']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Completada</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-clock"></i> Pendiente</span>
                                            <?php endif; ?>
                                         </div>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="ficha_grooming.php?cita_id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-primary" title="Ver ficha">
                                                    <i class="fas fa-clipboard-list"></i> Ficha
                                                </a>
                                                <?php if($cita['estado'] != 'completada' && $cita['estado'] != 'cancelada'): ?>
                                                <a href="citas.php?cambiar_estado=<?php echo $cita['id']; ?>&estado=en_progreso" class="btn btn-sm btn-success" onclick="return confirm('¿Iniciar esta cita?')" title="Iniciar cita">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if($cita['estado'] == 'en_progreso' && $cita['ficha_id']): ?>
                                                <a href="cierre_servicio.php?cita_id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-success" title="Finalizar servicio">
                                                    <i class="fas fa-check-circle"></i> Cerrar
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                         </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tbody>
                            </tr>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Próximas Citas -->
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-week"></i> Próximas Citas</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($proximas_citas)): ?>
                        <p class="text-muted text-center py-3">No hay citas próximas</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr><th>Fecha</th><th>Hora</th><th>Cliente</th><th>Mascota</th><th>Servicio</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($proximas_citas as $cita): ?>
                                    <tr>
                                        <td><strong><?php echo date('d/m/Y', strtotime($cita['fecha_hora_inicio'])); ?></strong></td>
                                        <td><?php echo date('H:i', strtotime($cita['fecha_hora_inicio'])); ?></td>
                                        <td><?php echo htmlspecialchars($cita['cliente_nombre'] . ' ' . $cita['cliente_apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['mascota_nombre']); ?></a></td>
                                        <td><?php echo htmlspecialchars($cita['servicio_nombre']); ?></a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 text-end">
                            <a href="citas.php" class="btn btn-sm btn-outline-success">Ver todas las citas →</a>
                        </div>
                    <?php endif; ?>
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
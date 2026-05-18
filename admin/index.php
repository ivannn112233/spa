<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();

// Estadísticas
$stats = [];

// Total usuarios
$stmt = $db->query("SELECT COUNT(*) FROM usuarios");
$stats['total_usuarios'] = $stmt->fetchColumn();

// Usuarios por rol
$stmt = $db->query("
    SELECT r.nombre, COUNT(u.id) as total 
    FROM roles r 
    LEFT JOIN usuarios u ON u.rol_id = r.id 
    GROUP BY r.id
");
$stats['roles'] = $stmt->fetchAll();

// Citas hoy
$stmt = $db->query("SELECT COUNT(*) FROM citas WHERE DATE(fecha_hora_inicio) = CURDATE()");
$stats['citas_hoy'] = $stmt->fetchColumn();

// Ingresos del mes
$stmt = $db->query("
    SELECT COALESCE(SUM(total), 0) as total FROM facturas 
    WHERE MONTH(fecha_emision) = MONTH(CURDATE()) 
    AND YEAR(fecha_emision) = YEAR(CURDATE())
    AND estado = 'pagada'
");
$stats['ingresos_mes'] = $stmt->fetch()['total'];

// Mascotas registradas
$stmt = $db->query("SELECT COUNT(*) FROM mascotas");
$stats['total_mascotas'] = $stmt->fetchColumn();

// Citas pendientes
$stmt = $db->query("SELECT COUNT(*) FROM citas WHERE estado IN ('agendada', 'confirmada') AND fecha_hora_inicio > NOW()");
$stats['citas_pendientes'] = $stmt->fetchColumn();

// Clientes registrados
$stmt = $db->query("SELECT COUNT(*) FROM clientes");
$stats['total_clientes'] = $stmt->fetchColumn();

// Últimos logs de auditoría
$stmt = $db->query("
    SELECT a.*, u.email as usuario_email 
    FROM audit_log a 
    LEFT JOIN usuarios u ON a.usuario_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
$ultimos_logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
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
            margin-left: 250px;
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
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-card p {
            margin: 0;
            opacity: 0.9;
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
                width: 100%;
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
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4 border-bottom border-secondary">
                <div class="user-avatar">
                    A
                </div>
                <h5 class="text-white mb-0"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></h5>
                <small class="text-muted">Administrador</small>
            </div>
            <ul class="nav flex-column mt-3">
                <li>
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="roles.php">
                        <i class="fas fa-tags"></i> Roles
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="servicios.php">
                        <i class="fas fa-cut"></i> Servicios
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="productos.php">
                        <i class="fas fa-box"></i> Productos
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="auditoria.php">
                        <i class="fas fa-history"></i> Auditoría
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="configuracion.php">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                </li>
                <li><hr class="bg-secondary my-2 mx-3"></li>
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
        <main class="col-md-10 main-content px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-tachometer-alt"></i> Panel de Administración
                </h1>
                <div class="btn-toolbar">
                    <span class="text-muted">
                        <i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?>
                    </span>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><?php echo $stats['total_usuarios']; ?></h2>
                                <p>Usuarios Totales</p>
                            </div>
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><?php echo $stats['total_clientes']; ?></h2>
                                <p>Clientes</p>
                            </div>
                            <i class="fas fa-user-friends fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><?php echo $stats['total_mascotas']; ?></h2>
                                <p>Mascotas</p>
                            </div>
                            <i class="fas fa-paw fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><?php echo $stats['citas_pendientes']; ?></h2>
                                <p>Citas Pendientes</p>
                            </div>
                            <i class="fas fa-calendar-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card bg-danger">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><?php echo $stats['citas_hoy']; ?></h2>
                                <p>Citas Hoy</p>
                            </div>
                            <i class="fas fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card bg-secondary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2>Bs. <?php echo number_format($stats['ingresos_mes'], 2); ?></h2>
                                <p>Ingresos del Mes</p>
                            </div>
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card bg-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><?php echo count($stats['roles']); ?></h2>
                                <p>Roles del Sistema</p>
                            </div>
                            <i class="fas fa-tags fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Usuarios por Rol -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Usuarios por Rol</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($stats['roles'] as $rol): ?>
                        <div class="col-md-3 text-center mb-3">
                            <div class="border rounded p-3">
                                <h3><?php echo $rol['total']; ?></h3>
                                <p class="mb-0"><?php echo ucfirst($rol['nombre']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Últimos Registros de Auditoría -->
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Últimos Registros de Auditoría</h5>
                    <a href="auditoria.php" class="btn btn-sm btn-light">Ver todos →</a>
                </div>
                <div class="card-body">
                    <?php if(empty($ultimos_logs)): ?>
                        <p class="text-muted">No hay registros de auditoría</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Usuario</th>
                                        <th>Rol</th>
                                        <th>Acción</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ultimos_logs as $log): 
                                        $badgeColor = '';
                                        if(strpos($log['accion'], 'LOGIN_EXITOSO') !== false) $badgeColor = 'success';
                                        elseif(strpos($log['accion'], 'LOGIN_FALLIDO') !== false) $badgeColor = 'danger';
                                        elseif(strpos($log['accion'], 'CREAR') !== false) $badgeColor = 'primary';
                                        elseif(strpos($log['accion'], 'EDITAR') !== false) $badgeColor = 'warning';
                                        elseif(strpos($log['accion'], 'ELIMINAR') !== false) $badgeColor = 'danger';
                                        else $badgeColor = 'secondary';
                                    ?>
                                    <tr>
                                        <td><small><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small></td>
                                        <td>
                                            <?php if($log['usuario_email']): ?>
                                                <?php echo htmlspecialchars($log['usuario_email']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Sistema</span>
                                            <?php endif; ?>
                                         </a></td>
                                        <td><span class="badge bg-secondary"><?php echo ucfirst($log['rol'] ?? 'N/A'); ?></span></td>
                                        <td><span class="badge bg-<?php echo $badgeColor; ?>"><?php echo $log['accion']; ?></span></td>
                                        <td><code><small><?php echo $log['ip_address']; ?></small></code></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
<?php
require_once '../config/config.php';

// Verificar autenticación
if (!Auth::checkAuth()) {
    header('Location: ../login.php');
    exit;
}

// Verificar rol de ADMIN
if (!Auth::hasRole(ROLE_ADMIN)) {
    $role = $_SESSION['user_role'];
    if ($role == ROLE_GROOMER) header('Location: ../groomer/index.php');
    elseif ($role == ROLE_RECEPCION) header('Location: ../recepcion/index.php');
    elseif ($role == ROLE_CLIENTE) header('Location: ../cliente/index.php');
    else header('Location: ../login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// ============================================
// ESTADÍSTICAS COMPLETAS
// ============================================

// Total usuarios
$stmt = $db->query("SELECT COUNT(*) FROM usuarios");
$stats['total_usuarios'] = $stmt->fetchColumn();

// Usuarios activos
$stmt = $db->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'activo'");
$stats['usuarios_activos'] = $stmt->fetchColumn();

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

// Citas pendientes
$stmt = $db->query("SELECT COUNT(*) FROM citas WHERE estado IN ('agendada', 'confirmada') AND fecha_hora_inicio > NOW()");
$stats['citas_pendientes'] = $stmt->fetchColumn();

// Citas completadas este mes
$stmt = $db->query("
    SELECT COUNT(*) FROM citas 
    WHERE estado = 'completada' 
    AND MONTH(fecha_hora_inicio) = MONTH(CURDATE())
    AND YEAR(fecha_hora_inicio) = YEAR(CURDATE())
");
$stats['citas_completadas_mes'] = $stmt->fetchColumn();

// Ingresos del mes
$stmt = $db->query("
    SELECT COALESCE(SUM(total), 0) as total FROM facturas 
    WHERE MONTH(fecha_emision) = MONTH(CURDATE()) 
    AND YEAR(fecha_emision) = YEAR(CURDATE())
    AND estado = 'pagada'
");
$stats['ingresos_mes'] = $stmt->fetch()['total'];

// Ingresos hoy
$stmt = $db->query("
    SELECT COALESCE(SUM(total), 0) as total FROM facturas 
    WHERE DATE(fecha_emision) = CURDATE() 
    AND estado = 'pagada'
");
$stats['ingresos_hoy'] = $stmt->fetch()['total'];

// Mascotas registradas
$stmt = $db->query("SELECT COUNT(*) FROM mascotas");
$stats['total_mascotas'] = $stmt->fetchColumn();

// Clientes registrados
$stmt = $db->query("SELECT COUNT(*) FROM clientes");
$stats['total_clientes'] = $stmt->fetchColumn();

// Groomers activos
$stmt = $db->query("SELECT COUNT(*) FROM groomers WHERE estado_activo = 1");
$stats['total_groomers'] = $stmt->fetchColumn();

// Servicios activos
$stmt = $db->query("SELECT COUNT(*) FROM servicios WHERE activo = 1");
$stats['total_servicios'] = $stmt->fetchColumn();

// Productos activos
$stmt = $db->query("SELECT COUNT(*) FROM productos WHERE activo = 1");
$stats['total_productos'] = $stmt->fetchColumn();

// Productos con stock bajo
$stmt = $db->query("SELECT COUNT(*) FROM productos WHERE stock <= stock_minimo AND activo = 1");
$stats['stock_bajo'] = $stmt->fetchColumn();

// Valor total del inventario
$stmt = $db->query("SELECT COALESCE(SUM(stock * precio_base), 0) FROM productos WHERE activo = 1");
$stats['valor_inventario'] = $stmt->fetchColumn();

// Últimos logs de auditoría
$stmt = $db->query("
    SELECT a.*, u.email as usuario_email 
    FROM audit_log a 
    LEFT JOIN usuarios u ON a.usuario_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
$ultimos_logs = $stmt->fetchAll();

// Próximas citas (para el panel)
$stmt = $db->query("
    SELECT c.*, m.nombre as mascota, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
           s.nombre as servicio, gr.nombre as groomer
    FROM citas c
    JOIN mascotas m ON m.id = c.mascota_id
    JOIN clientes cl ON cl.id = c.cliente_id
    JOIN servicios s ON s.id = c.servicio_id
    JOIN groomers gr ON gr.id = c.groomer_id
    WHERE c.fecha_hora_inicio > NOW() AND c.estado IN ('agendada', 'confirmada')
    ORDER BY c.fecha_hora_inicio ASC
    LIMIT 5
");
$proximas_citas = $stmt->fetchAll();

// Obtener datos del admin para avatar
$user_name = $_SESSION['user_name'] ?? 'Admin';
$iniciales = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
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
        .sidebar .nav-link:hover { background: rgba(52, 152, 219, 0.3); padding-left: 28px; }
        .sidebar .nav-link.active { background: #3498db; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; text-align: center; }
        .main-content { margin-left: 260px; padding: 20px; transition: all 0.3s; }
        .stat-card {
            border-radius: 15px;
            padding: 20px;
            color: white;
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            position: relative;
            border: none;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .stat-card h2 { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .stat-card p { margin: 0; opacity: 0.9; font-size: 0.85rem; }
        .stat-card i { position: absolute; right: 20px; bottom: 20px; font-size: 3rem; opacity: 0.2; }
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
        .btn-logout { background: #e74c3c; border: none; border-radius: 8px; padding: 8px 16px; }
        .btn-logout:hover { background: #c0392b; transform: translateY(-2px); }
        .alert-stock {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            min-width: 280px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: slideIn 0.5s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @media (max-width: 768px) {
            .sidebar { position: static; width: 100%; min-height: auto; }
            .main-content { margin-left: 0; }
            .stat-card h2 { font-size: 1.5rem; }
            .alert-stock { position: static; margin-top: 20px; }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <nav class="col-md-2 sidebar">
            <div class="text-center py-4">
                <div class="user-avatar"><?php echo $iniciales; ?></div>
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($user_name); ?></h5>
                <small class="text-muted"><i class="fas fa-shield-alt"></i> Administrador</small>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link active" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a class="nav-link" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
                <li><a class="nav-link" href="disponibilidad.php"><i class="fas fa-clock"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                <li><a class="nav-link" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><hr class="bg-secondary mx-3 my-2"></li>
                <li><a class="nav-link text-warning" href="../cambiar_password.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="col-md-10 main-content">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><i class="fas fa-tachometer-alt text-primary"></i> Panel de Administración</h1>
                    <small class="text-muted">Bienvenido, <?php echo htmlspecialchars($user_name); ?></small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
                </div>
            </div>
            
            <!-- Alerta de stock bajo -->
            <?php if($stats['stock_bajo'] > 0): ?>
            <div class="alert alert-warning alert-stock shadow">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>¡Atención!</strong> <?php echo $stats['stock_bajo']; ?> producto(s) con stock bajo.
                <a href="inventario.php" class="alert-link">Revisar inventario</a>
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Fila 1 - Stats Principales -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-primary">
                        <div class="d-flex justify-content-between"><div><h2><?php echo $stats['total_usuarios']; ?></h2><p>Usuarios Totales</p></div><i class="fas fa-users fa-2x"></i></div>
                        <small class="mt-2 d-block"><?php echo $stats['usuarios_activos']; ?> activos</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-success">
                        <div class="d-flex justify-content-between"><div><h2><?php echo $stats['total_clientes']; ?></h2><p>Clientes</p></div><i class="fas fa-user-friends fa-2x"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-info">
                        <div class="d-flex justify-content-between"><div><h2><?php echo $stats['total_mascotas']; ?></h2><p>Mascotas</p></div><i class="fas fa-paw fa-2x"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-warning">
                        <div class="d-flex justify-content-between"><div><h2><?php echo $stats['total_groomers']; ?></h2><p>Groomers</p></div><i class="fas fa-user-md fa-2x"></i></div>
                    </div>
                </div>
            </div>
            
            <!-- Fila 2 - Citas y Ventas -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-danger">
                        <div class="d-flex justify-content-between"><div><h2><?php echo $stats['citas_hoy']; ?></h2><p>Citas Hoy</p></div><i class="fas fa-calendar-day fa-2x"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-secondary">
                        <div class="d-flex justify-content-between"><div><h2><?php echo $stats['citas_pendientes']; ?></h2><p>Citas Pendientes</p></div><i class="fas fa-clock fa-2x"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <div class="d-flex justify-content-between"><div><h2>Bs. <?php echo number_format($stats['ingresos_hoy'], 2); ?></h2><p>Ventas Hoy</p></div><i class="fas fa-dollar-sign fa-2x"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="d-flex justify-content-between"><div><h2>Bs. <?php echo number_format($stats['ingresos_mes'], 2); ?></h2><p>Ingresos del Mes</p></div><i class="fas fa-chart-line fa-2x"></i></div>
                    </div>
                </div>
            </div>
            
            <!-- Fila 3 - Inventario y Servicios -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-dark">
                        <div class="d-flex justify-content-between"><div><h2><?php echo $stats['total_servicios']; ?></h2><p>Servicios Activos</p></div><i class="fas fa-cut fa-2x"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="d-flex justify-content-between"><div><h2><?php echo $stats['total_productos']; ?></h2><p>Productos</p></div><i class="fas fa-box fa-2x"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="d-flex justify-content-between"><div><h2>Bs. <?php echo number_format($stats['valor_inventario'], 2); ?></h2><p>Valor Inventario</p></div><i class="fas fa-chart-simple fa-2x"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-success">
                        <div class="d-flex justify-content-between"><div><h2><?php echo $stats['citas_completadas_mes']; ?></h2><p>Citas Completadas (Mes)</p></div><i class="fas fa-check-circle fa-2x"></i></div>
                    </div>
                </div>
            </div>
            
            <!-- Usuarios por Rol -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-chart-pie"></i> Usuarios por Rol</h5></div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($stats['roles'] as $rol): ?>
                        <div class="col-md-3 text-center mb-3">
                            <div class="border rounded p-3 bg-light">
                                <h3 class="text-<?php 
                                    echo $rol['nombre']=='admin'?'danger':
                                        ($rol['nombre']=='recepcion'?'warning':
                                        ($rol['nombre']=='groomer'?'info':'success')); 
                                ?>"><?php echo $rol['total']; ?></h3>
                                <p class="mb-0 small text-muted"><?php echo ucfirst($rol['nombre']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Próximas Citas y Auditoría -->
            <div class="row">
                <!-- Próximas Citas -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calendar-week"></i> Próximas Citas</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($proximas_citas)): ?>
                                <p class="text-muted text-center py-3">No hay citas próximas</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-light">
                                            <tr><th>Fecha/Hora</th><th>Cliente</th><th>Mascota</th><th>Servicio</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($proximas_citas as $c): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($c['fecha_hora_inicio'])); ?></a></td>
                                                <td><?php echo htmlspecialchars($c['cliente_nombre'] . ' ' . $c['cliente_apellido']); ?></a></td>
                                                <td><?php echo htmlspecialchars($c['mascota']); ?></a></td>
                                                <td><?php echo htmlspecialchars($c['servicio']); ?></a></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="citas.php" class="btn btn-sm btn-outline-primary">Ver todas →</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Últimos Registros de Auditoría -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Últimos Registros de Auditoría</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($ultimos_logs)): ?>
                                <p class="text-muted text-center py-3">No hay registros de auditoría</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-light">
                                            <tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>IP</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($ultimos_logs as $log): 
                                                $badgeColor = 'secondary';
                                                if(strpos($log['accion'], 'LOGIN_EXITOSO') !== false) $badgeColor = 'success';
                                                elseif(strpos($log['accion'], 'LOGIN_FALLIDO') !== false) $badgeColor = 'danger';
                                                elseif(strpos($log['accion'], 'CREAR') !== false) $badgeColor = 'primary';
                                                elseif(strpos($log['accion'], 'EDITAR') !== false) $badgeColor = 'warning';
                                                elseif(strpos($log['accion'], 'ELIMINAR') !== false) $badgeColor = 'danger';
                                            ?>
                                            <tr>
                                                <td><small><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></small></td>
                                                <td><small><?php echo htmlspecialchars($log['usuario_email'] ?? 'Sistema'); ?></small></td>
                                                <td><span class="badge bg-<?php echo $badgeColor; ?>"><?php echo $log['accion']; ?></span></td>
                                                <td><code><small><?php echo $log['ip_address']; ?></small></code></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="auditoria.php" class="btn btn-sm btn-outline-secondary">Ver todos →</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="text-center text-muted mt-2 pt-3 border-top">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos los derechos reservados</small>
            </footer>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
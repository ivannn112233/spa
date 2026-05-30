<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();

// Filtros
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_accion = $_GET['accion'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? date('Y-m-d');
$filtro_rol = $_GET['rol'] ?? '';
$filtro_ip = $_GET['ip'] ?? '';

// Obtener lista de acciones únicas para el filtro
$acciones = $db->query("SELECT DISTINCT accion FROM audit_log ORDER BY accion")->fetchAll();

// Construir consulta con prepared statements (más seguro)
$sql = "SELECT a.*, u.email as usuario_email, u.id as usuario_id 
        FROM audit_log a 
        LEFT JOIN usuarios u ON a.usuario_id = u.id 
        WHERE 1=1";
$params = [];

if($filtro_usuario){
    $sql .= " AND a.usuario_id = ?";
    $params[] = $filtro_usuario;
}
if($filtro_accion){
    $sql .= " AND a.accion = ?";
    $params[] = $filtro_accion;
}
if($filtro_fecha){
    $sql .= " AND DATE(a.created_at) = ?";
    $params[] = $filtro_fecha;
}
if($filtro_rol){
    $sql .= " AND a.rol = ?";
    $params[] = $filtro_rol;
}
if($filtro_ip){
    $sql .= " AND a.ip_address LIKE ?";
    $params[] = "%$filtro_ip%";
}

$sql .= " ORDER BY a.created_at DESC LIMIT 500";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Estadísticas
$stats = [];
$stmt = $db->query("SELECT COUNT(*) FROM audit_log"); 
$stats['total'] = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM audit_log WHERE DATE(created_at) = CURDATE()"); 
$stats['hoy'] = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"); 
$stats['semana'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT accion, COUNT(*) as total FROM audit_log GROUP BY accion ORDER BY total DESC LIMIT 5"); 
$stats['top_acciones'] = $stmt->fetchAll();

// Usuarios para filtro
$usuarios = $db->query("SELECT id, email FROM usuarios ORDER BY email")->fetchAll();

// Roles para filtro
$roles = $db->query("SELECT DISTINCT rol FROM audit_log WHERE rol IS NOT NULL ORDER BY rol")->fetchAll();

// Exportar a CSV
if(isset($_GET['exportar']) && $_GET['exportar'] == 'csv'){
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="auditoria_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Fecha', 'Usuario', 'Rol', 'Acción', 'Detalle', 'IP', 'User Agent']);
    
    foreach($logs as $log){
        fputcsv($output, [
            $log['created_at'],
            $log['usuario_email'] ?? 'Sistema',
            $log['rol'] ?? 'N/A',
            $log['accion'],
            $log['detalle'],
            $log['ip_address'],
            $log['user_agent']
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .log-table { font-size: 0.85rem; }
        .badge-action { font-size: 0.7rem; padding: 5px 8px; }
        .stat-card { border-radius: 10px; padding: 15px; color: white; text-align: center; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4 border-bottom border-secondary">
                <h5 class="text-white">🐾 Pet Spa</h5>
                <small class="text-muted">Administrador</small>
            </div>
            <ul class="nav flex-column mt-3">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a class="nav-link" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
                <li><a class="nav-link" href="disponibilidad.php"><i class="fas fa-clock"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                <li><a class="nav-link active" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-history"></i> Auditoría del Sistema</h2>
                <div>
                    <a href="?exportar=csv&<?php echo http_build_query($_GET); ?>" class="btn btn-success"><i class="fas fa-file-excel"></i> Exportar CSV</a>
                    <button onclick="window.location.reload()" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Actualizar</button>
                </div>
            </div>
            
            <!-- Tarjetas de estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card bg-primary"><h3><?php echo number_format($stats['total']); ?></h3><p>Total Registros</p></div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-success"><h3><?php echo number_format($stats['hoy']); ?></h3><p>Registros Hoy</p></div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-info"><h3><?php echo number_format($stats['semana']); ?></h3><p>Última Semana</p></div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark text-white">
                        <div class="card-header bg-secondary">Top Acciones</div>
                        <div class="card-body p-2">
                            <?php foreach($stats['top_acciones'] as $a): ?>
                            <div class="d-flex justify-content-between small"><span><?php echo $a['accion']; ?></span><span class="badge bg-primary"><?php echo $a['total']; ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white"><i class="fas fa-filter"></i> Filtros</div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label>Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo $filtro_fecha; ?>">
                        </div>
                        <div class="col-md-2">
                            <label>Usuario</label>
                            <select name="usuario" class="form-control">
                                <option value="">Todos</option>
                                <?php foreach($usuarios as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $filtro_usuario==$u['id']?'selected':''; ?>><?php echo htmlspecialchars($u['email']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Acción</label>
                            <select name="accion" class="form-control">
                                <option value="">Todas</option>
                                <?php foreach($acciones as $a): ?>
                                <option value="<?php echo htmlspecialchars($a['accion']); ?>" <?php echo $filtro_accion==$a['accion']?'selected':''; ?>><?php echo $a['accion']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Rol</label>
                            <select name="rol" class="form-control">
                                <option value="">Todos</option>
                                <?php foreach($roles as $r): ?>
                                <option value="<?php echo $r['rol']; ?>" <?php echo $filtro_rol==$r['rol']?'selected':''; ?>><?php echo ucfirst($r['rol']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>IP</label>
                            <input type="text" name="ip" class="form-control" placeholder="192.168.1.1" value="<?php echo htmlspecialchars($filtro_ip); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
                            <a href="auditoria.php" class="btn btn-secondary w-100 ms-2"><i class="fas fa-eraser"></i> Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabla de logs -->
            <div class="card">
                <div class="card-header bg-primary text-white"><i class="fas fa-list"></i> Registros de Auditoría</div>
                <div class="card-body">
                    <?php if(empty($logs)): ?>
                        <div class="alert alert-info">No hay registros para mostrar</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover log-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Usuario</th>
                                        <th>Rol</th>
                                        <th>Acción</th>
                                        <th>Detalle</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($logs as $log): 
                                        $badgeColor = 'secondary';
                                        if(strpos($log['accion'], 'LOGIN_EXITOSO') !== false) $badgeColor = 'success';
                                        elseif(strpos($log['accion'], 'LOGIN_FALLIDO') !== false) $badgeColor = 'danger';
                                        elseif(strpos($log['accion'], 'CREAR') !== false || strpos($log['accion'], 'CREACION') !== false) $badgeColor = 'primary';
                                        elseif(strpos($log['accion'], 'EDITAR') !== false || strpos($log['accion'], 'ACTUALIZAR') !== false) $badgeColor = 'warning';
                                        elseif(strpos($log['accion'], 'ELIMINAR') !== false || strpos($log['accion'], 'DESACTIVAR') !== false) $badgeColor = 'danger';
                                        elseif(strpos($log['accion'], 'CANCELAR') !== false) $badgeColor = 'danger';
                                        elseif(strpos($log['accion'], 'PAGO') !== false) $badgeColor = 'info';
                                    ?>
                                    <tr>
                                        <td><small><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small></td>
                                        <td>
                                            <?php if($log['usuario_id']): ?>
                                                <a href="auditoria.php?usuario=<?php echo $log['usuario_id']; ?>">
                                                    <?php echo htmlspecialchars($log['usuario_email'] ?? 'ID:'.$log['usuario_id']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Sistema</span>
                                            <?php endif; ?>
                                         </a></td>
                                        <td><span class="badge bg-secondary"><?php echo ucfirst($log['rol'] ?? 'N/A'); ?></span></td>
                                        <td><span class="badge bg-<?php echo $badgeColor; ?> badge-action"><?php echo $log['accion']; ?></span></td>
                                        <td><small><?php echo htmlspecialchars(substr($log['detalle'] ?? '', 0, 150)); ?></small></td>
                                        <td><code><small><?php echo $log['ip_address']; ?></small></code></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 text-muted">
                            <small><i class="fas fa-info-circle"></i> Mostrando <?php echo count($logs); ?> registros</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Botón para limpiar logs -->
            <div class="card mt-4 bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-database"></i> <strong>Mantenimiento</strong><br><small class="text-muted">Eliminar registros antiguos (más de 3 meses)</small></div>
                        <button class="btn btn-danger btn-sm" onclick="limpiarLogs()" id="btnLimpiar"><i class="fas fa-trash-alt"></i> Limpiar Logs Antiguos</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function limpiarLogs() {
    if(confirm('¿Eliminar registros de auditoría con más de 3 meses? Esta acción no se puede deshacer.')) {
        const btn = document.getElementById('btnLimpiar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        
        fetch('ajax_limpiar_logs.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=limpiar'
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Se eliminaron ' + data.eliminados + ' registros antiguos');
                location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt"></i> Limpiar Logs Antiguos';
            }
        })
        .catch(error => {
            alert('Error al conectar con el servidor');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash-alt"></i> Limpiar Logs Antiguos';
        });
    }
}
</script>
</body>
</html>
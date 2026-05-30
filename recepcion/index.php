<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();

// Estadísticas
$stmt = $db->query("SELECT COUNT(*) FROM citas WHERE DATE(fecha_hora_inicio) = CURDATE()");
$citas_hoy = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM citas WHERE fecha_hora_inicio > NOW() AND estado IN ('agendada', 'confirmada')");
$citas_pendientes = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM clientes");
$total_clientes = $stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(total), 0) FROM facturas WHERE DATE(fecha_emision) = CURDATE() AND estado = 'pagada'");
$ventas_hoy = $stmt->fetchColumn();

// Solicitudes pendientes (citas agendadas por clientes)
$stmt = $db->query("SELECT COUNT(*) FROM citas WHERE estado = 'agendada' AND creado_por IS NOT NULL");
$solicitudes_pendientes = $stmt->fetchColumn();

// Citas de hoy
$stmt = $db->prepare("
    SELECT c.*, m.nombre as mascota, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
           s.nombre as servicio, gr.nombre as groomer
    FROM citas c
    JOIN mascotas m ON m.id = c.mascota_id
    JOIN clientes cl ON cl.id = c.cliente_id
    JOIN servicios s ON s.id = c.servicio_id
    JOIN groomers gr ON gr.id = c.groomer_id
    WHERE DATE(c.fecha_hora_inicio) = CURDATE()
    ORDER BY c.fecha_hora_inicio ASC
");
$stmt->execute();
$citas_hoy_detalle = $stmt->fetchAll();

// Últimas solicitudes
$solicitudes = $db->query("
    SELECT c.*, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido, s.nombre as servicio
    FROM citas c
    JOIN clientes cl ON cl.id = c.cliente_id
    JOIN servicios s ON s.id = c.servicio_id
    WHERE c.estado = 'agendada' AND c.creado_por IS NOT NULL
    ORDER BY c.created_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recepción - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .stat-card { border-radius: 10px; padding: 20px; color: white; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .solicitud-item { border-left: 4px solid #f39c12; transition: all 0.3s; }
        .solicitud-item:hover { background: #fff8e7; }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4 border-bottom border-secondary">
                <h5 class="text-white">🐾 Pet Spa</h5>
                <small class="text-muted">Recepción</small>
            </div>
            <ul class="nav flex-column mt-3">
                <li><a class="nav-link active" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a class="nav-link" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
                <li><a class="nav-link" href="calendario.php"><i class="fas fa-calendar-week"></i> Calendario</a></li>
                <li><a class="nav-link" href="solicitudes.php"><i class="fas fa-inbox"></i> Solicitudes 
                    <?php if($solicitudes_pendientes > 0): ?>
                        <span class="badge bg-danger"><?php echo $solicitudes_pendientes; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a class="nav-link" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><a class="nav-link" href="punto_venta.php"><i class="fas fa-shopping-cart"></i> Punto de Venta</a></li>
                <li><a class="nav-link text-warning" href="../cambiar_password.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-tachometer-alt"></i> Panel de Recepción</h2>
                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i'); ?></span>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-primary"><h2><?php echo $citas_hoy; ?></h2><p>Citas Hoy</p></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-warning"><h2><?php echo $citas_pendientes; ?></h2><p>Citas Pendientes</p></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-success"><h2><?php echo $total_clientes; ?></h2><p>Clientes</p></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card bg-info"><h2>Bs. <?php echo number_format($ventas_hoy, 2); ?></h2><p>Ventas Hoy</p></div>
                </div>
            </div>
            
            <div class="row">
                <!-- Citas de Hoy -->
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="fas fa-calendar-day"></i> Citas de Hoy</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($citas_hoy_detalle)): ?>
                                <p class="text-muted">No hay citas programadas para hoy</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr><th>Hora</th><th>Cliente</th><th>Mascota</th><th>Servicio</th><th>Groomer</th><th>Estado</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($citas_hoy_detalle as $cita): ?>
                                            <tr>
                                                <td><?php echo date('H:i', strtotime($cita['fecha_hora_inicio'])); ?></td>
                                                <td><?php echo htmlspecialchars($cita['cliente_nombre'] . ' ' . $cita['cliente_apellido']); ?></a></td>
                                                <td><?php echo htmlspecialchars($cita['mascota']); ?></a></td>
                                                <td><?php echo htmlspecialchars($cita['servicio']); ?></a></td>
                                                <td><?php echo htmlspecialchars($cita['groomer']); ?></a></td>
                                                <td><span class="badge bg-warning"><?php echo $cita['estado']; ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Solicitudes Pendientes -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header bg-warning text-white d-flex justify-content-between">
                            <h5><i class="fas fa-inbox"></i> Solicitudes Pendientes</h5>
                            <?php if($solicitudes_pendientes > 0): ?>
                                <a href="solicitudes.php" class="btn btn-sm btn-light">Ver todas (<?php echo $solicitudes_pendientes; ?>)</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if(empty($solicitudes)): ?>
                                <p class="text-muted">No hay solicitudes pendientes</p>
                            <?php else: ?>
                                <?php foreach($solicitudes as $s): ?>
                                <div class="solicitud-item p-2 mb-2 rounded">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($s['cliente_nombre'] . ' ' . $s['cliente_apellido']); ?></strong>
                                            <br><small><?php echo htmlspecialchars($s['servicio']); ?></small>
                                        </div>
                                        <div>
                                            <span class="badge bg-warning"><?php echo date('d/m/Y H:i', strtotime($s['fecha_hora_inicio'])); ?></span>
                                            <br>
                                            <a href="solicitudes.php?aprobar=<?php echo $s['id']; ?>" class="btn btn-sm btn-success mt-1" onclick="return confirm('¿Aprobar esta cita?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="solicitudes.php?rechazar=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger mt-1" onclick="return confirm('¿Rechazar esta cita?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
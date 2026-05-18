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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recepción - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; top: 0; left: 0; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .stat-card { border-radius: 10px; padding: 20px; color: white; }
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
                <li><a class="nav-link" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><hr class="bg-secondary my-2"></li>
                <li><a class="nav-link text-warning" href="../cambiar_password.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-tachometer-alt"></i> Panel de Recepción</h2>
                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i'); ?></span>
            </div>
            
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
                                    <tr><th>Hora</th><th>Cliente</th><th>Mascota</th><th>Servicio</th><th>Groomer</th><th>Estado</th><th>Acciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($citas_hoy_detalle as $cita): ?>
                                    <tr>
                                        <td><?php echo date('H:i', strtotime($cita['fecha_hora_inicio'])); ?></td>
                                        <td><?php echo htmlspecialchars($cita['cliente_nombre'] . ' ' . $cita['cliente_apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['mascota']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['servicio']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['groomer']); ?></td>
                                        <td><span class="badge bg-warning"><?php echo $cita['estado']; ?></span></td>
                                        <td><a href="citas.php?edit=<?php echo $cita['id']; ?>" class="btn btn-sm btn-info">Editar</a></td>
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
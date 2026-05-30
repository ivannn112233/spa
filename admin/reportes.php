<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$reporte = $_GET['reporte'] ?? 'ventas';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Reporte de Ventas
if($reporte == 'ventas'){
    $stmt = $db->prepare("
        SELECT 
            DATE(fecha_emision) as fecha,
            COUNT(*) as total_facturas,
            SUM(subtotal) as subtotal,
            SUM(impuesto) as impuesto,
            SUM(total) as total,
            SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END) as efectivo,
            SUM(CASE WHEN metodo_pago = 'qr' THEN total ELSE 0 END) as qr,
            SUM(CASE WHEN metodo_pago = 'transferencia' THEN total ELSE 0 END) as transferencia
        FROM facturas 
        WHERE DATE(fecha_emision) BETWEEN ? AND ? AND estado = 'pagada'
        GROUP BY DATE(fecha_emision)
        ORDER BY fecha DESC
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $datos = $stmt->fetchAll();
    
    // Totales
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_facturas,
            SUM(subtotal) as subtotal,
            SUM(impuesto) as impuesto,
            SUM(total) as total
        FROM facturas 
        WHERE DATE(fecha_emision) BETWEEN ? AND ? AND estado = 'pagada'
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $totales = $stmt->fetch();
}

// Reporte de Ocupación
if($reporte == 'ocupacion'){
    $stmt = $db->prepare("
        SELECT 
            DATE(fecha_hora_inicio) as fecha,
            COUNT(*) as total_citas,
            SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
            SUM(CASE WHEN estado = 'no_asistio' THEN 1 ELSE 0 END) as no_asistieron,
            COUNT(DISTINCT groomer_id) as groomers_activos
        FROM citas 
        WHERE DATE(fecha_hora_inicio) BETWEEN ? AND ?
        GROUP BY DATE(fecha_hora_inicio)
        ORDER BY fecha DESC
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $datos = $stmt->fetchAll();
}

// Reporte de Servicios más vendidos
if($reporte == 'servicios_top'){
    $stmt = $db->prepare("
        SELECT 
            s.nombre,
            COUNT(c.id) as total_citas,
            SUM(c.precio_acordado) as total_ingresos,
            AVG(c.precio_acordado) as promedio
        FROM citas c
        JOIN servicios s ON c.servicio_id = s.id
        WHERE DATE(c.fecha_hora_inicio) BETWEEN ? AND ? AND c.estado = 'completada'
        GROUP BY s.id
        ORDER BY total_citas DESC
        LIMIT 10
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $datos = $stmt->fetchAll();
}

// Reporte de Groomers
if($reporte == 'groomers'){
    $stmt = $db->prepare("
        SELECT 
            g.nombre,
            g.apellido,
            COUNT(c.id) as total_citas,
            SUM(CASE WHEN c.estado = 'completada' THEN 1 ELSE 0 END) as completadas,
            AVG(CASE WHEN c.estado = 'completada' THEN 1 ELSE 0 END) * 100 as eficiencia
        FROM groomers g
        LEFT JOIN citas c ON g.id = c.groomer_id AND DATE(c.fecha_hora_inicio) BETWEEN ? AND ?
        GROUP BY g.id
        ORDER BY completadas DESC
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $datos = $stmt->fetchAll();
}

// Reporte de Clientes Frecuentes
if($reporte == 'clientes_top'){
    $stmt = $db->prepare("
        SELECT 
            cl.nombre,
            cl.apellido,
            cl.telefono,
            COUNT(c.id) as total_citas,
            SUM(c.precio_acordado) as total_gastado
        FROM clientes cl
        JOIN citas c ON cl.id = c.cliente_id
        WHERE DATE(c.fecha_hora_inicio) BETWEEN ? AND ? AND c.estado = 'completada'
        GROUP BY cl.id
        ORDER BY total_citas DESC
        LIMIT 20
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $datos = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; padding: 20px; color: white; }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Admin</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a class="nav-link" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
                <li><a class="nav-link" href="disponibilidad.php"><i class="fas fa-clock"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link active" href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                <li><a class="nav-link" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-chart-line"></i> Reportes del Sistema</h2>
            </div>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label>Tipo de Reporte</label>
                            <select name="reporte" class="form-control" onchange="this.form.submit()">
                                <option value="ventas" <?php echo $reporte=='ventas'?'selected':''; ?>>📊 Ventas</option>
                                <option value="ocupacion" <?php echo $reporte=='ocupacion'?'selected':''; ?>>📅 Ocupación</option>
                                <option value="servicios_top" <?php echo $reporte=='servicios_top'?'selected':''; ?>>✂️ Servicios Top</option>
                                <option value="groomers" <?php echo $reporte=='groomers'?'selected':''; ?>>👨‍🦱 Rendimiento Groomers</option>
                                <option value="clientes_top" <?php echo $reporte=='clientes_top'?'selected':''; ?>>🏆 Clientes Frecuentes</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                        </div>
                        <div class="col-md-3">
                            <label>Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Generar Reporte</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Reporte de Ventas -->
            <?php if($reporte == 'ventas'): ?>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3>Bs. <?php echo number_format($totales['total'] ?? 0, 2); ?></h3>
                        <p>Total Ventas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?php echo $totales['total_facturas'] ?? 0; ?></h3>
                        <p>Facturas Emitidas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3>Bs. <?php echo number_format($totales['impuesto'] ?? 0, 2); ?></h3>
                        <p>Impuesto Recaudado</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3>Bs. <?php echo number_format(($totales['total'] ?? 0) / max(1, $totales['total_facturas'] ?? 1), 2); ?></h3>
                        <p>Ticket Promedio</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Ventas por Día</h5>
                </div>
                <div class="card-body">
                    <canvas id="ventasChart" height="100"></canvas>
                    <div class="table-responsive mt-4">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr><th>Fecha</th><th>Facturas</th><th>Subtotal</th><th>Impuesto</th><th>Total</th><th>Efectivo</th><th>QR</th><th>Transferencia</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($datos as $d): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($d['fecha'])); ?></td>
                                    <td><?php echo $d['total_facturas']; ?></td>
                                    <td>Bs. <?php echo number_format($d['subtotal'], 2); ?></td>
                                    <td>Bs. <?php echo number_format($d['impuesto'], 2); ?></td>
                                    <td><strong>Bs. <?php echo number_format($d['total'], 2); ?></strong></td>
                                    <td>Bs. <?php echo number_format($d['efectivo'], 2); ?></td>
                                    <td>Bs. <?php echo number_format($d['qr'], 2); ?></td>
                                    <td>Bs. <?php echo number_format($d['transferencia'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <script>
            new Chart(document.getElementById('ventasChart'), {
                type: 'line',
                data: {
                    labels: [<?php echo implode(',', array_map(function($d){ return '"'.date('d/m', strtotime($d['fecha'])).'"'; }, $datos)); ?>],
                    datasets: [{
                        label: 'Ventas (Bs)',
                        data: [<?php echo implode(',', array_map(function($d){ return $d['total']; }, $datos)); ?>],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52,152,219,0.1)',
                        fill: true
                    }]
                }
            });
            </script>
            <?php endif; ?>
            
            <!-- Reporte de Ocupación -->
            <?php if($reporte == 'ocupacion'): ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5>Ocupación y Citas</h5>
                </div>
                <div class="card-body">
                    <canvas id="ocupacionChart" height="100"></canvas>
                    <div class="table-responsive mt-4">
                        <table class="table table-striped">
                            <thead class="table-dark"><tr><th>Fecha</th><th>Total Citas</th><th>Completadas</th><th>Canceladas</th><th>No Asistieron</th><th>Groomers Activos</th><th>Ocupación</th></tr></thead>
                            <tbody>
                                <?php foreach($datos as $d): 
                                    $ocupacion = $d['total_citas'] > 0 ? round(($d['completadas'] / $d['total_citas']) * 100) : 0;
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($d['fecha'])); ?></td>
                                    <td><?php echo $d['total_citas']; ?></td>
                                    <td><?php echo $d['completadas']; ?></td>
                                    <td><?php echo $d['canceladas']; ?></td>
                                    <td><?php echo $d['no_asistieron']; ?></td>
                                    <td><?php echo $d['groomers_activos']; ?></td>
                                    <td><div class="progress"><div class="progress-bar bg-success" style="width: <?php echo $ocupacion; ?>%"><?php echo $ocupacion; ?>%</div></div></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <script>
            new Chart(document.getElementById('ocupacionChart'), {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function($d){ return '"'.date('d/m', strtotime($d['fecha'])).'"'; }, $datos)); ?>],
                    datasets: [
                        { label: 'Completadas', data: [<?php echo implode(',', array_map(function($d){ return $d['completadas']; }, $datos)); ?>], backgroundColor: '#27ae60' },
                        { label: 'Canceladas', data: [<?php echo implode(',', array_map(function($d){ return $d['canceladas']; }, $datos)); ?>], backgroundColor: '#e74c3c' }
                    ]
                }
            });
            </script>
            <?php endif; ?>
            
            <!-- Reporte de Servicios Top -->
            <?php if($reporte == 'servicios_top'): ?>
            <div class="card">
                <div class="card-header bg-warning">
                    <h5>Servicios Más Solicitados</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="serviciosChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <thead class="table-dark"><tr><th>Servicio</th><th>Citas</th><th>Ingresos</th><th>Promedio</th></tr></thead>
                                <tbody>
                                    <?php foreach($datos as $d): ?>
                                    <tr>
                                        <td><?php echo $d['nombre']; ?></td>
                                        <td><?php echo $d['total_citas']; ?></td>
                                        <td>Bs. <?php echo number_format($d['total_ingresos'], 2); ?></td>
                                        <td>Bs. <?php echo number_format($d['promedio'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            new Chart(document.getElementById('serviciosChart'), {
                type: 'pie',
                data: {
                    labels: [<?php echo implode(',', array_map(function($d){ return '"'.$d['nombre'].'"'; }, $datos)); ?>],
                    datasets: [{ data: [<?php echo implode(',', array_map(function($d){ return $d['total_citas']; }, $datos)); ?>], backgroundColor: ['#3498db','#e74c3c','#27ae60','#f39c12','#9b59b6'] }]
                }
            });
            </script>
            <?php endif; ?>
            
            <!-- Reporte de Groomers -->
            <?php if($reporte == 'groomers'): ?>
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5>Rendimiento de Groomers</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead class="table-dark"><tr><th>Groomer</th><th>Total Citas</th><th>Completadas</th><th>Eficiencia</th></tr></thead>
                        <tbody>
                            <?php foreach($datos as $d): ?>
                            <tr>
                                <td><?php echo $d['nombre'] . ' ' . $d['apellido']; ?></td>
                                <td><?php echo $d['total_citas']; ?></td>
                                <td><?php echo $d['completadas']; ?></td>
                                <td><div class="progress"><div class="progress-bar bg-success" style="width: <?php echo round($d['eficiencia']); ?>%"><?php echo round($d['eficiencia']); ?>%</div></div></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Reporte de Clientes Frecuentes -->
            <?php if($reporte == 'clientes_top'): ?>
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5>Clientes Frecuentes</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead class="table-dark"><tr><th>Cliente</th><th>Teléfono</th><th>Citas</th><th>Total Gastado</th></tr></thead>
                        <tbody>
                            <?php foreach($datos as $d): ?>
                            <tr>
                                <td><?php echo $d['nombre'] . ' ' . $d['apellido']; ?></td>
                                <td><?php echo $d['telefono'] ?? '-'; ?></td>
                                <td><?php echo $d['total_citas']; ?></td>
                                <td>Bs. <?php echo number_format($d['total_gastado'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
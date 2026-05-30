<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Ocupación diaria
$stmt = $db->prepare("
    SELECT 
        DATE(fecha_hora_inicio) as fecha,
        COUNT(*) as total_citas,
        SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
        SUM(CASE WHEN estado = 'no_asistio' THEN 1 ELSE 0 END) as no_asistieron,
        COUNT(DISTINCT groomer_id) as groomers_activos,
        (SELECT COUNT(*) FROM disponibilidad_groomer WHERE dia_semana = WEEKDAY(DATE(fecha_hora_inicio)) + 1) as capacidad_maxima
    FROM citas 
    WHERE DATE(fecha_hora_inicio) BETWEEN ? AND ?
    GROUP BY DATE(fecha_hora_inicio)
    ORDER BY fecha ASC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$ocupacion = $stmt->fetchAll();

// Ocupación por groomer
$stmt = $db->prepare("
    SELECT 
        g.nombre,
        g.apellido,
        COUNT(c.id) as total_citas,
        SUM(CASE WHEN c.estado = 'completada' THEN 1 ELSE 0 END) as completadas,
        ROUND(AVG(CASE WHEN c.estado = 'completada' THEN 1 ELSE 0 END) * 100, 2) as eficiencia
    FROM groomers g
    LEFT JOIN citas c ON g.id = c.groomer_id AND DATE(c.fecha_hora_inicio) BETWEEN ? AND ?
    GROUP BY g.id
    ORDER BY completadas DESC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$groomers = $stmt->fetchAll();

// Horas pico
$stmt = $db->prepare("
    SELECT 
        HOUR(fecha_hora_inicio) as hora,
        COUNT(*) as total_citas
    FROM citas 
    WHERE DATE(fecha_hora_inicio) BETWEEN ? AND ? AND estado = 'completada'
    GROUP BY HOUR(fecha_hora_inicio)
    ORDER BY hora ASC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$horas_pico = $stmt->fetchAll();

$total_citas = array_sum(array_column($ocupacion, 'total_citas'));
$total_completadas = array_sum(array_column($ocupacion, 'completadas'));
$total_canceladas = array_sum(array_column($ocupacion, 'canceladas'));
$tasa_ocupacion = $total_citas > 0 ? round(($total_completadas / $total_citas) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ocupación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container-fluid py-4">
    <div class="no-print mb-4">
        <div class="d-flex justify-content-between">
            <h2><i class="fas fa-calendar-alt"></i> Reporte de Ocupación</h2>
            <div><button onclick="window.print()" class="btn btn-secondary">Imprimir</button><a href="../admin/reportes.php" class="btn btn-primary ms-2">Volver</a></div>
        </div>
        <form method="GET" class="row g-3 mt-3"><div class="col-auto"><input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>"></div><div class="col-auto"><span>a</span></div><div class="col-auto"><input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>"></div><div class="col-auto"><button type="submit" class="btn btn-primary">Filtrar</button></div></form>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h3><?php echo $total_citas; ?></h3><p>Total Citas</p></div></div></div>
        <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h3><?php echo $total_completadas; ?></h3><p>Completadas</p></div></div></div>
        <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><h3><?php echo $total_canceladas; ?></h3><p>Canceladas</p></div></div></div>
        <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h3><?php echo $tasa_ocupacion; ?>%</h3><p>Tasa Ocupación</p></div></div></div>
    </div>
    
    <div class="row">
        <div class="col-md-8"><div class="card"><div class="card-header">Ocupación Diaria</div><div class="card-body"><canvas id="ocupacionChart" height="250"></canvas><div class="table-responsive mt-3"><table class="table table-sm"><?php foreach($ocupacion as $o): ?><tr><td><?php echo date('d/m/Y', strtotime($o['fecha'])); ?></td><td><?php echo $o['total_citas']; ?> citas</td><td><?php echo $o['completadas']; ?> completadas</td><td><?php echo $o['canceladas']; ?> canceladas</td><td><div class="progress"><div class="progress-bar bg-success" style="width: <?php echo $o['total_citas']>0?($o['completadas']/$o['total_citas'])*100:0; ?>%"></div></div></td></tr><?php endforeach; ?></table></div></div></div></div>
        <div class="col-md-4"><div class="card mb-3"><div class="card-header">Rendimiento por Groomer</div><div class="card-body"><?php foreach($groomers as $g): ?><div class="d-flex justify-content-between"><span><?php echo $g['nombre'] . ' ' . $g['apellido']; ?></span><span><?php echo $g['completadas']; ?>/<?php echo $g['total_citas']; ?></span></div><div class="progress mb-2"><div class="progress-bar bg-success" style="width: <?php echo $g['eficiencia']; ?>%"><?php echo $g['eficiencia']; ?>%</div></div><?php endforeach; ?></div></div><div class="card"><div class="card-header">Horas Pico</div><div class="card-body"><canvas id="horasChart" height="200"></canvas></div></div></div>
    </div>
</div>
<script>
new Chart(document.getElementById('ocupacionChart'), { type: 'bar', data: { labels: [<?php echo implode(',', array_map(function($o){ return '"'.date('d/m', strtotime($o['fecha'])).'"'; }, $ocupacion)); ?>], datasets: [{ label: 'Completadas', data: [<?php echo implode(',', array_map(function($o){ return $o['completadas']; }, $ocupacion)); ?>], backgroundColor: '#27ae60' }, { label: 'Canceladas', data: [<?php echo implode(',', array_map(function($o){ return $o['canceladas']; }, $ocupacion)); ?>], backgroundColor: '#e74c3c' }] } });
new Chart(document.getElementById('horasChart'), { type: 'line', data: { labels: [<?php echo implode(',', array_map(function($h){ return $h['hora'].':00'; }, $horas_pico)); ?>], datasets: [{ label: 'Citas', data: [<?php echo implode(',', array_map(function($h){ return $h['total_citas']; }, $horas_pico)); ?>], borderColor: '#3498db', fill: false }] } });
</script>
</body>
</html>
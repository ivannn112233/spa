<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Encuestas de satisfacción
$stmt = $db->prepare("
    SELECT e.*, c.nombre, c.apellido, s.nombre as servicio
    FROM encuestas_satisfaccion e
    JOIN clientes c ON e.cliente_id = c.id
    JOIN citas ct ON e.cita_id = ct.id
    JOIN servicios s ON ct.servicio_id = s.id
    WHERE DATE(e.created_at) BETWEEN ? AND ?
    ORDER BY e.created_at DESC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$encuestas = $stmt->fetchAll();

// Estadísticas de calificaciones
$calificaciones = [1=>0,2=>0,3=>0,4=>0,5=>0];
foreach($encuestas as $e){
    $calificaciones[$e['calificacion']]++;
}
$total_encuestas = count($encuestas);
$promedio = $total_encuestas > 0 ? round(array_sum(array_map(fn($e) => $e['calificacion'], $encuestas)) / $total_encuestas, 2) : 0;
$nps = $total_encuestas > 0 ? round((($calificaciones[5] + $calificaciones[4]) - $calificaciones[1]) / $total_encuestas * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Satisfacción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between mb-4">
        <h2><i class="fas fa-star"></i> Reporte de Satisfacción</h2>
        <div><button onclick="window.print()" class="btn btn-secondary">Imprimir</button><a href="../admin/reportes.php" class="btn btn-primary ms-2">Volver</a></div>
    </div>
    
    <form method="GET" class="row g-3 mb-4"><div class="col-auto"><input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>"></div><div class="col-auto"><span>a</span></div><div class="col-auto"><input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>"></div><div class="col-auto"><button type="submit" class="btn btn-primary">Filtrar</button></div></form>
    
    <div class="row mb-4">
        <div class="col-md-4"><div class="card bg-primary text-white"><div class="card-body"><h3><?php echo $total_encuestas; ?></h3><p>Encuestas Recibidas</p></div></div></div>
        <div class="col-md-4"><div class="card bg-success text-white"><div class="card-body"><h3><?php echo $promedio; ?> / 5</h3><p>Calificación Promedio</p></div></div></div>
        <div class="col-md-4"><div class="card <?php echo $nps >= 50 ? 'bg-success' : ($nps >= 0 ? 'bg-warning' : 'bg-danger'); ?> text-white"><div class="card-body"><h3><?php echo $nps; ?>%</h3><p>NPS (Net Promoter Score)</p></div></div></div>
    </div>
    
    <div class="row">
        <div class="col-md-6"><div class="card"><div class="card-header">Distribución de Calificaciones</div><div class="card-body"><canvas id="calificacionesChart" height="250"></canvas><div class="mt-3"><?php for($i=5;$i>=1;$i--): ?><div class="d-flex justify-content-between"><span><?php echo $i; ?> estrellas</span><span><?php echo $calificaciones[$i]; ?> respuestas</span></div><div class="progress mb-2"><div class="progress-bar bg-<?php echo $i>=4?'success':($i>=3?'warning':'danger'); ?>" style="width: <?php echo $total_encuestas>0?($calificaciones[$i]/$total_encuestas)*100:0; ?>%"></div></div><?php endfor; ?></div></div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-header">Comentarios de Clientes</div><div class="card-body" style="max-height:500px; overflow-y:auto;"><?php foreach($encuestas as $e): ?><div class="border-bottom mb-3 pb-2"><div class="d-flex justify-content-between"><strong><?php echo $e['nombre'] . ' ' . $e['apellido']; ?></strong><span class="text-warning"><?php echo str_repeat('★', $e['calificacion']) . str_repeat('☆', 5-$e['calificacion']); ?></span></div><small class="text-muted">Servicio: <?php echo $e['servicio']; ?> | <?php echo date('d/m/Y', strtotime($e['created_at'])); ?></small><p class="mt-1"><?php echo nl2br(htmlspecialchars($e['comentario'] ?: 'Sin comentario')); ?></p></div><?php endforeach; ?></div></div></div>
    </div>
</div>
<script>
new Chart(document.getElementById('calificacionesChart'), { type: 'bar', data: { labels: ['1 estrella', '2 estrellas', '3 estrellas', '4 estrellas', '5 estrellas'], datasets: [{ label: 'Cantidad', data: [<?php echo $calificaciones[1]; ?>, <?php echo $calificaciones[2]; ?>, <?php echo $calificaciones[3]; ?>, <?php echo $calificaciones[4]; ?>, <?php echo $calificaciones[5]; ?>], backgroundColor: ['#e74c3c', '#e67e22', '#f39c12', '#3498db', '#27ae60'] }] } });
</script>
</body>
</html>
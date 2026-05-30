<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$formato = $_GET['formato'] ?? 'html'; // html, pdf, excel

// Datos de ventas
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
$ventas_diarias = $stmt->fetchAll();

// Totales generales
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_facturas,
        SUM(subtotal) as subtotal,
        SUM(impuesto) as impuesto,
        SUM(total) as total,
        SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END) as efectivo,
        SUM(CASE WHEN metodo_pago = 'qr' THEN total ELSE 0 END) as qr,
        SUM(CASE WHEN metodo_pago = 'transferencia' THEN total ELSE 0 END) as transferencia,
        AVG(total) as ticket_promedio
    FROM facturas 
    WHERE DATE(fecha_emision) BETWEEN ? AND ? AND estado = 'pagada'
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$totales = $stmt->fetch();

// Ventas por servicio
$stmt = $db->prepare("
    SELECT 
        s.nombre as servicio,
        COUNT(c.id) as cantidad,
        SUM(c.precio_acordado) as total
    FROM citas c
    JOIN servicios s ON c.servicio_id = s.id
    WHERE DATE(c.fecha_hora_inicio) BETWEEN ? AND ? AND c.estado = 'completada'
    GROUP BY s.id
    ORDER BY total DESC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$ventas_servicios = $stmt->fetchAll();

// Ventas por producto (tienda)
$stmt = $db->prepare("
    SELECT 
        p.nombre as producto,
        SUM(dp.cantidad) as cantidad,
        SUM(dp.subtotal) as total
    FROM detalle_pedido dp
    JOIN pedidos ped ON dp.pedido_id = ped.id
    JOIN productos p ON dp.producto_id = p.id
    WHERE DATE(ped.created_at) BETWEEN ? AND ? AND ped.estado = 'pagado'
    GROUP BY p.id
    ORDER BY total DESC
    LIMIT 10
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$ventas_productos = $stmt->fetchAll();

if($formato == 'excel'){
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_ventas_' . date('Y-m-d') . '.xls"');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            .no-print { display: none; }
            .card { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="no-print mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-chart-line"></i> Reporte de Ventas</h2>
            <div>
                <button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i> Imprimir</button>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['formato' => 'excel'])); ?>" class="btn btn-success">Exportar Excel</a>
                <a href="../admin/reportes.php" class="btn btn-primary">Volver</a>
            </div>
        </div>
        
        <form method="GET" class="row g-3 mt-3">
            <div class="col-auto"><input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>"></div>
            <div class="col-auto"><span>a</span></div>
            <div class="col-auto"><input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>"></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary">Filtrar</button></div>
        </form>
    </div>
    
    <!-- Tarjetas resumen -->
    <div class="row mb-4">
        <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h3>Bs. <?php echo number_format($totales['total'], 2); ?></h3><p>Total Ventas</p></div></div></div>
        <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h3><?php echo $totales['total_facturas']; ?></h3><p>Facturas</p></div></div></div>
        <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h3>Bs. <?php echo number_format($totales['ticket_promedio'], 2); ?></h3><p>Ticket Promedio</p></div></div></div>
        <div class="col-md-3"><div class="card bg-warning text-white"><div class="card-body"><h3>Bs. <?php echo number_format($totales['impuesto'], 2); ?></h3><p>Impuesto Recaudado</p></div></div></div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card"><div class="card-header">Ventas por Día</div><div class="card-body"><canvas id="ventasChart" height="250"></canvas><div class="table-responsive mt-3"><table class="table table-sm"><?php foreach($ventas_diarias as $v): ?><tr><td><?php echo date('d/m/Y', strtotime($v['fecha'])); ?></td><td class="text-end">Bs. <?php echo number_format($v['total'], 2); ?></td></tr><?php endforeach; ?></table></div></div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-header">Ventas por Servicio</div><div class="card-body"><canvas id="serviciosChart" height="250"></canvas><?php foreach($ventas_servicios as $v): ?><div class="d-flex justify-content-between"><span><?php echo $v['servicio']; ?></span><span>Bs. <?php echo number_format($v['total'], 2); ?> (<?php echo $v['cantidad']; ?> citas)</span></div><div class="progress mb-2"><div class="progress-bar" style="width: <?php echo ($v['total']/$totales['total'])*100; ?>%"></div></div><?php endforeach; ?></div></div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card"><div class="card-header">Productos Más Vendidos</div><div class="card-body"><table class="table"><?php foreach($ventas_productos as $p): ?><tr><td><?php echo $p['producto']; ?></td><td><?php echo $p['cantidad']; ?> unid.</td><td class="text-end">Bs. <?php echo number_format($p['total'], 2); ?></td></tr><?php endforeach; ?></table></div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-header">Métodos de Pago</div><div class="card-body"><canvas id="pagosChart" height="200"></canvas><div class="row mt-3"><div class="col-4 text-center"><strong>Efectivo</strong><br>Bs. <?php echo number_format($totales['efectivo'], 2); ?></div><div class="col-4 text-center"><strong>QR</strong><br>Bs. <?php echo number_format($totales['qr'], 2); ?></div><div class="col-4 text-center"><strong>Transferencia</strong><br>Bs. <?php echo number_format($totales['transferencia'], 2); ?></div></div></div></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
new Chart(document.getElementById('ventasChart'), { type: 'line', data: { labels: [<?php echo implode(',', array_map(function($v){ return '"'.date('d/m', strtotime($v['fecha'])).'"'; }, $ventas_diarias)); ?>], datasets: [{ label: 'Ventas (Bs)', data: [<?php echo implode(',', array_map(function($v){ return $v['total']; }, $ventas_diarias)); ?>], borderColor: '#3498db', fill: false }] } });
new Chart(document.getElementById('serviciosChart'), { type: 'pie', data: { labels: [<?php echo implode(',', array_map(function($v){ return '"'.$v['servicio'].'"'; }, $ventas_servicios)); ?>], datasets: [{ data: [<?php echo implode(',', array_map(function($v){ return $v['total']; }, $ventas_servicios)); ?>] }] } });
new Chart(document.getElementById('pagosChart'), { type: 'doughnut', data: { labels: ['Efectivo', 'QR', 'Transferencia'], datasets: [{ data: [<?php echo $totales['efectivo']; ?>, <?php echo $totales['qr']; ?>, <?php echo $totales['transferencia']; ?>], backgroundColor: ['#27ae60', '#3498db', '#e74c3c'] }] } });
</script>
</body>
</html>
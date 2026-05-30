<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();

// Stock actual
$productos = $db->query("
    SELECT p.*, c.nombre as categoria,
           CASE WHEN p.stock <= p.stock_minimo THEN 'Bajo' WHEN p.stock <= p.stock_minimo * 2 THEN 'Medio' ELSE 'Alto' END as nivel_stock
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE p.activo = 1
    ORDER BY p.stock ASC
")->fetchAll();

// Consumo por producto
$stmt = $db->prepare("
    SELECT p.nombre, SUM(dp.cantidad) as total_consumido
    FROM detalle_pedido dp
    JOIN productos p ON dp.producto_id = p.id
    WHERE DATE(dp.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY p.id
    ORDER BY total_consumido DESC
    LIMIT 10
");
$stmt->execute();
$consumo = $stmt->fetchAll();

// Consumo por servicio
$stmt = $db->prepare("
    SELECT s.nombre, COUNT(c.id) as cantidad
    FROM citas c
    JOIN servicios s ON c.servicio_id = s.id
    WHERE DATE(c.fecha_hora_inicio) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    AND c.estado = 'completada'
    GROUP BY s.id
    ORDER BY cantidad DESC
");
$stmt->execute();
$servicios_populares = $stmt->fetchAll();

$total_productos = count($productos);
$stock_bajo = count(array_filter($productos, fn($p) => $p['nivel_stock'] == 'Bajo'));
$valor_inventario = array_sum(array_map(fn($p) => $p['stock'] * $p['precio_base'], $productos));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between mb-4">
        <h2><i class="fas fa-boxes"></i> Reporte de Inventario</h2>
        <div><button onclick="window.print()" class="btn btn-secondary">Imprimir</button><a href="../admin/reportes.php" class="btn btn-primary ms-2">Volver</a></div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h3><?php echo $total_productos; ?></h3><p>Productos Activos</p></div></div></div>
        <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><h3><?php echo $stock_bajo; ?></h3><p>Stock Bajo</p></div></div></div>
        <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h3>Bs. <?php echo number_format($valor_inventario, 2); ?></h3><p>Valor Inventario</p></div></div></div>
        <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h3><?php echo array_sum(array_column($productos, 'stock')); ?></h3><p>Unidades Totales</p></div></div></div>
    </div>
    
    <div class="row">
        <div class="col-md-7"><div class="card"><div class="card-header">Stock por Producto</div><div class="card-body"><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Producto</th><th>Categoría</th><th>Stock</th><th>Mínimo</th><th>Estado</th><th>Valor</th></tr></thead><tbody><?php foreach($productos as $p): ?><tr class="<?php echo $p['nivel_stock']=='Bajo'?'table-danger':($p['nivel_stock']=='Medio'?'table-warning':''); ?>"><td><?php echo $p['nombre']; ?></td><td><?php echo $p['categoria']??'-'; ?></td><td><?php echo $p['stock']; ?></td><td><?php echo $p['stock_minimo']; ?></td><td><span class="badge bg-<?php echo $p['nivel_stock']=='Bajo'?'danger':($p['nivel_stock']=='Medio'?'warning':'success'); ?>"><?php echo $p['nivel_stock']; ?></span></td><td>Bs. <?php echo number_format($p['stock'] * $p['precio_base'], 2); ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
        <div class="col-md-5"><div class="card mb-3"><div class="card-header">Productos Más Consumidos (30 días)</div><div class="card-body"><?php foreach($consumo as $c): ?><div class="d-flex justify-content-between"><span><?php echo $c['nombre']; ?></span><span><?php echo $c['total_consumido']; ?> unid.</span></div><div class="progress mb-2"><div class="progress-bar" style="width: <?php echo ($c['total_consumido']/max(1,$consumo[0]['total_consumido']))*100; ?>%"></div></div><?php endforeach; ?></div></div><div class="card"><div class="card-header">Servicios Más Solicitados</div><div class="card-body"><?php foreach($servicios_populares as $s): ?><div class="d-flex justify-content-between"><span><?php echo $s['nombre']; ?></span><span><?php echo $s['cantidad']; ?> citas</span></div><div class="progress mb-2"><div class="progress-bar bg-info" style="width: <?php echo ($s['cantidad']/max(1,$servicios_populares[0]['cantidad']))*100; ?>%"></div></div><?php endforeach; ?></div></div></div>
    </div>
</div>
</body>
</html>
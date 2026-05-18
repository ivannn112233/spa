<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$mensaje = '';
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Registrar pago
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_pago'])){
    $cita_id = $_POST['cita_id'];
    $monto = $_POST['monto'];
    $metodo = $_POST['metodo_pago'];
    
    $db->beginTransaction();
    
    // Obtener datos de la cita
    $stmt = $db->prepare("SELECT cliente_id, precio_acordado FROM citas WHERE id = ?");
    $stmt->execute([$cita_id]);
    $cita = $stmt->fetch();
    
    // Crear factura
    $stmt = $db->prepare("INSERT INTO facturas (cliente_id, cita_id, subtotal, impuesto, total, metodo_pago, estado, fecha_emision) VALUES (?, ?, ?, ?, ?, ?, 'pagada', NOW())");
    $impuesto = $monto * 0.13;
    $stmt->execute([$cita['cliente_id'], $cita_id, $monto, $impuesto, $monto + $impuesto, $metodo]);
    
    // Registrar pago
    $factura_id = $db->lastInsertId();
    $stmt = $db->prepare("INSERT INTO pagos (factura_id, monto, metodo, estado) VALUES (?, ?, ?, 'completado')");
    $stmt->execute([$factura_id, $monto, $metodo]);
    
    // Actualizar estado de cita
    $stmt = $db->prepare("UPDATE citas SET estado = 'completada' WHERE id = ?");
    $stmt->execute([$cita_id]);
    
    $db->commit();
    $mensaje = '<div class="alert alert-success">Pago registrado correctamente</div>';
}

// Citas pendientes de pago
$stmt = $db->prepare("
    SELECT c.*, m.nombre as mascota, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
           s.nombre as servicio, s.precio_base
    FROM citas c
    JOIN mascotas m ON m.id = c.mascota_id
    JOIN clientes cl ON cl.id = c.cliente_id
    JOIN servicios s ON s.id = c.servicio_id
    WHERE c.estado = 'completada' 
    AND NOT EXISTS (SELECT 1 FROM facturas WHERE cita_id = c.id)
    ORDER BY c.fecha_hora_inicio DESC
");
$stmt->execute();
$pendientes = $stmt->fetchAll();

// Facturas del día
$stmt = $db->prepare("
    SELECT f.*, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido
    FROM facturas f
    JOIN clientes cl ON cl.id = f.cliente_id
    WHERE DATE(f.fecha_emision) = ?
    ORDER BY f.fecha_emision DESC
");
$stmt->execute([$fecha]);
$facturas = $stmt->fetchAll();

// Totales del día
$stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad FROM facturas WHERE DATE(fecha_emision) = ? AND estado = 'pagada'");
$stmt->execute([$fecha]);
$totales = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja - Recepción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>.sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; } .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; } .sidebar .nav-link i { width: 25px; } .main-content { margin-left: 250px; } @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Recepción</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a class="nav-link" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
                <li><a class="nav-link" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link active" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-cash-register"></i> Caja</h2>
                <form method="GET" class="d-flex">
                    <input type="date" name="fecha" class="form-control w-auto me-2" value="<?php echo $fecha; ?>">
                    <button type="submit" class="btn btn-primary">Ver</button>
                </form>
            </div>
            
            <?php echo $mensaje; ?>
            
            <!-- Resumen del día -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h3>Bs. <?php echo number_format($totales['total'], 2); ?></h3>
                            <p>Total Vendido</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h3><?php echo $totales['cantidad']; ?></h3>
                            <p>Facturas Emitidas</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Servicios pendientes de pago -->
            <div class="card mb-4">
                <div class="card-header bg-warning">Servicios Completados - Pendientes de Pago</div>
                <div class="card-body">
                    <?php if(empty($pendientes)): ?>
                        <p class="text-muted">No hay servicios pendientes de pago</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Fecha</th><th>Cliente</th><th>Mascota</th><th>Servicio</th><th>Monto</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <?php foreach($pendientes as $p): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($p['fecha_hora_inicio'])); ?></td>
                                        <td><?php echo htmlspecialchars($p['cliente_nombre'] . ' ' . $p['cliente_apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($p['mascota']); ?></td>
                                        <td><?php echo htmlspecialchars($p['servicio']); ?></td>
                                        <td>Bs. <?php echo number_format($p['precio_base'], 2); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#pagoModal" data-id="<?php echo $p['id']; ?>" data-monto="<?php echo $p['precio_base']; ?>" data-cliente="<?php echo htmlspecialchars($p['cliente_nombre'] . ' ' . $p['cliente_apellido']); ?>">
                                                <i class="fas fa-money-bill"></i> Cobrar
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Facturas del día -->
            <div class="card">
                <div class="card-header bg-primary text-white">Facturas del Día</div>
                <div class="card-body">
                    <?php if(empty($facturas)): ?>
                        <p class="text-muted">No hay facturas registradas hoy</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>N° Factura</th><th>Cliente</th><th>Subtotal</th><th>Impuesto</th><th>Total</th><th>Método</th><th>Hora</th></tr></thead>
                                <tbody>
                                    <?php foreach($facturas as $f): ?>
                                    <tr>
                                        <td><?php echo $f['numero']; ?></td>
                                        <td><?php echo htmlspecialchars($f['cliente_nombre'] . ' ' . $f['cliente_apellido']); ?></td>
                                        <td>Bs. <?php echo number_format($f['subtotal'], 2); ?></td>
                                        <td>Bs. <?php echo number_format($f['impuesto'], 2); ?></td>
                                        <td><strong>Bs. <?php echo number_format($f['total'], 2); ?></strong></td>
                                        <td><?php echo $f['metodo_pago']; ?></td>
                                        <td><?php echo date('H:i', strtotime($f['fecha_emision'])); ?></td>
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

<!-- Modal Cobro -->
<div class="modal fade" id="pagoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Registrar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p id="clienteInfo"></p>
                    <input type="hidden" name="cita_id" id="cita_id">
                    <div class="mb-3"><label>Monto</label><input type="number" name="monto" id="monto" class="form-control" step="0.01" required></div>
                    <div class="mb-3"><label>Método de Pago</label>
                        <select name="metodo_pago" class="form-control">
                            <option value="efectivo">Efectivo</option>
                            <option value="qr">QR</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="registrar_pago" class="btn btn-success">Registrar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('pagoModal').addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var monto = button.getAttribute('data-monto');
    var cliente = button.getAttribute('data-cliente');
    document.getElementById('cita_id').value = id;
    document.getElementById('monto').value = monto;
    document.getElementById('clienteInfo').innerHTML = '<strong>Cliente:</strong> ' + cliente;
});
</script>
</body>
</html>
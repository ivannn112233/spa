<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$mensaje = '';
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$descuento_aplicado = 0;
$codigo_promo = '';

// Obtener datos del usuario para avatar
$usuario_nombre = 'Recepción';
$usuario_apellido = '';
$iniciales = 'R';

try {
    $stmt = $db->prepare("SELECT nombre, apellido FROM clientes WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch();
    if($usuario && is_array($usuario)){
        $usuario_nombre = $usuario['nombre'] ?? 'Recepción';
        $usuario_apellido = $usuario['apellido'] ?? '';
        $iniciales = strtoupper(substr($usuario_nombre, 0, 1) . ($usuario_apellido ? substr($usuario_apellido, 0, 1) : ''));
    }
} catch(PDOException $e) {
    $iniciales = 'R';
}

// Aplicar promoción
if(isset($_POST['aplicar_promo'])){
    $codigo_promo = strtoupper($_POST['codigo_promo']);
    $monto_original = $_POST['monto_original'];
    
    $stmt = $db->prepare("SELECT * FROM promociones WHERE codigo = ? AND activo = 1 AND fecha_inicio <= CURDATE() AND fecha_fin >= CURDATE()");
    $stmt->execute([$codigo_promo]);
    $promo = $stmt->fetch();
    
    if($promo){
        if($promo['tipo'] == 'porcentaje'){
            $descuento_aplicado = $monto_original * ($promo['valor'] / 100);
        } elseif($promo['tipo'] == 'monto_fijo'){
            $descuento_aplicado = $promo['valor'];
        } elseif($promo['tipo'] == '2x1'){
            $descuento_aplicado = $monto_original / 2;
        }
        $mensaje = '<div class="alert alert-success">Promoción aplicada: ' . $promo['nombre'] . ' (Descuento: Bs. ' . number_format($descuento_aplicado, 2) . ')</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Código promocional inválido o expirado</div>';
    }
}

// Registrar pago
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_pago'])){
    $cita_id = $_POST['cita_id'];
    $monto = $_POST['monto'];
    $metodo = $_POST['metodo_pago'];
    $descuento = $_POST['descuento_aplicado'] ?? 0;
    
    $db->beginTransaction();
    
    $stmt = $db->prepare("SELECT cliente_id, precio_acordado FROM citas WHERE id = ?");
    $stmt->execute([$cita_id]);
    $cita = $stmt->fetch();
    
    $total_con_descuento = max(0, $monto - $descuento);
    
    $stmt = $db->prepare("INSERT INTO facturas (cliente_id, cita_id, subtotal, impuesto, total, metodo_pago, estado, fecha_emision) VALUES (?, ?, ?, ?, ?, ?, 'pagada', NOW())");
    $impuesto = $total_con_descuento * (IMPUESTO_PORCENTAJE / 100);
    $stmt->execute([$cita['cliente_id'], $cita_id, $total_con_descuento, $impuesto, $total_con_descuento + $impuesto, $metodo]);
    
    $factura_id = $db->lastInsertId();
    $stmt = $db->prepare("INSERT INTO pagos (factura_id, monto, metodo, estado) VALUES (?, ?, ?, 'completado')");
    $stmt->execute([$factura_id, $total_con_descuento, $metodo]);
    
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

$stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad FROM facturas WHERE DATE(fecha_emision) = ? AND estado = 'pagada'");
$stmt->execute([$fecha]);
$totales = $stmt->fetch();

// Promociones activas
$promociones = $db->query("SELECT * FROM promociones WHERE activo = 1 AND fecha_inicio <= CURDATE() AND fecha_fin >= CURDATE() LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja - Recepción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
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
        
        .sidebar .nav-link:hover {
            background: rgba(52, 152, 219, 0.3);
            padding-left: 28px;
        }
        
        .sidebar .nav-link.active {
            background: #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s;
        }
        
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
        
        .btn-logout {
            background: #e74c3c;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            color: white;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .promo-badge {
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
            margin: 2px;
        }
        
        .promo-badge:hover {
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <nav class="col-md-2 sidebar">
            <div class="text-center py-4">
                <div class="user-avatar">
                    <?php echo $iniciales; ?>
                </div>
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($usuario_nombre . ' ' . $usuario_apellido); ?></h5>
                <small class="text-muted"><i class="fas fa-phone-alt"></i> Recepción</small>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a class="nav-link" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
                <li><a class="nav-link" href="calendario.php"><i class="fas fa-calendar-week"></i> Calendario</a></li>
                <li><a class="nav-link" href="solicitudes.php"><i class="fas fa-inbox"></i> Solicitudes</a></li>
                <li><a class="nav-link" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link active" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><a class="nav-link" href="punto_venta.php"><i class="fas fa-shopping-cart"></i> Punto de Venta</a></li>
                <li><a class="nav-link" href="promociones_aplicar.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><hr class="bg-secondary mx-3 my-2"></li>
                <li><a class="nav-link text-warning" href="../cambiar_password.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="col-md-10 main-content">
            <!-- Header con botón de salir -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><i class="fas fa-cash-register text-primary"></i> Caja</h1>
                    <small class="text-muted">Gestión de cobros y facturación</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <?php echo $mensaje; ?>
            
            <!-- Resumen del día -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="stat-card bg-success">
                        <h2 class="mb-0">Bs. <?php echo number_format($totales['total'], 2); ?></h2>
                        <p class="mb-0">Total Vendido</p>
                        <small><?php echo date('d/m/Y', strtotime($fecha)); ?></small>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stat-card bg-info">
                        <h2 class="mb-0"><?php echo $totales['cantidad']; ?></h2>
                        <p class="mb-0">Facturas Emitidas</p>
                        <small><?php echo date('d/m/Y', strtotime($fecha)); ?></small>
                    </div>
                </div>
            </div>
            
            <!-- Promociones activas -->
            <?php if(!empty($promociones)): ?>
            <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-tags fs-4 me-2"></i>
                <div>
                    <strong>Promociones activas:</strong>
                    <?php foreach($promociones as $p): ?>
                    <span class="badge bg-warning promo-badge" onclick="aplicarPromocion('<?php echo $p['codigo']; ?>')">
                        <?php echo $p['nombre']; ?> (<?php echo $p['codigo']; ?>)
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Servicios pendientes de pago -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Servicios Completados - Pendientes de Pago</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($pendientes)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                            <p class="text-muted mb-0">No hay servicios pendientes de pago</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Mascota</th>
                                        <th>Servicio</th>
                                        <th>Monto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($pendientes as $p): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($p['fecha_hora_inicio'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($p['cliente_nombre'] . ' ' . $p['cliente_apellido']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($p['mascota']); ?></td>
                                        <td><?php echo htmlspecialchars($p['servicio']); ?></td>
                                        <td><span class="badge bg-success">Bs. <?php echo number_format($p['precio_base'], 2); ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#pagoModal" 
                                                    data-id="<?php echo $p['id']; ?>" data-monto="<?php echo $p['precio_base']; ?>" 
                                                    data-cliente="<?php echo htmlspecialchars($p['cliente_nombre'] . ' ' . $p['cliente_apellido']); ?>">
                                                <i class="fas fa-money-bill"></i> Cobrar
                                            </button>
                                         </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Facturas del día -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Facturas del Día</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($facturas)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-3x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No hay facturas registradas hoy</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>N° Factura</th>
                                        <th>Cliente</th>
                                        <th>Subtotal</th>
                                        <th>Impuesto</th>
                                        <th>Total</th>
                                        <th>Método</th>
                                        <th>Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($facturas as $f): ?>
                                    <tr>
                                        <td><strong><?php echo $f['numero']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($f['cliente_nombre'] . ' ' . $f['cliente_apellido']); ?></td>
                                        <td>Bs. <?php echo number_format($f['subtotal'], 2); ?></td>
                                        <td>Bs. <?php echo number_format($f['impuesto'], 2); ?></td>
                                        <td><span class="badge bg-success">Bs. <?php echo number_format($f['total'], 2); ?></span></td>
                                        <td><span class="badge bg-info"><?php echo ucfirst($f['metodo_pago']); ?></span></td>
                                        <td><?php echo date('H:i', strtotime($f['fecha_emision'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="text-center text-muted mt-4 pt-3 border-top">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos los derechos reservados</small>
            </footer>
        </main>
    </div>
</div>

<!-- Modal Cobro con Promoción -->
<div class="modal fade" id="pagoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-credit-card"></i> Registrar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="pagoForm">
                <div class="modal-body">
                    <p id="clienteInfo" class="fw-bold"></p>
                    <input type="hidden" name="cita_id" id="cita_id">
                    <div class="mb-3">
                        <label class="form-label">Monto original</label>
                        <input type="number" name="monto" id="monto" class="form-control" step="0.01" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código promocional</label>
                        <div class="input-group">
                            <input type="text" name="codigo_promo" id="codigo_promo" class="form-control" placeholder="Ej: DESCUENTO10">
                            <button type="button" class="btn btn-warning" onclick="aplicarPromoEnModal()">Aplicar</button>
                        </div>
                    </div>
                    <div id="promoInfo" class="small text-success mb-2"></div>
                    <input type="hidden" name="descuento_aplicado" id="descuento_aplicado" value="0">
                    <div class="mb-3">
                        <label class="form-label">Método de Pago</label>
                        <select name="metodo_pago" class="form-select" required>
                            <option value="efectivo">💵 Efectivo</option>
                            <option value="qr">📱 QR</option>
                            <option value="transferencia">🏦 Transferencia</option>
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
let montoOriginal = 0;

function aplicarPromocion(codigo) {
    document.getElementById('codigo_promo').value = codigo;
    aplicarPromoEnModal();
}

function aplicarPromoEnModal() {
    const codigo = document.getElementById('codigo_promo').value;
    const monto = montoOriginal;
    
    if(codigo && monto > 0){
        fetch('ajax_aplicar_promo.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'codigo=' + encodeURIComponent(codigo) + '&monto=' + monto
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                document.getElementById('promoInfo').innerHTML = '<i class="fas fa-check-circle"></i> Descuento aplicado: Bs. ' + parseFloat(data.descuento).toFixed(2);
                document.getElementById('descuento_aplicado').value = data.descuento;
            } else {
                document.getElementById('promoInfo').innerHTML = '<i class="fas fa-times-circle text-danger"></i> ' + data.message;
                document.getElementById('descuento_aplicado').value = 0;
            }
        })
        .catch(error => {
            document.getElementById('promoInfo').innerHTML = '<i class="fas fa-times-circle text-danger"></i> Error al aplicar promoción';
        });
    }
}

document.getElementById('pagoModal').addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var monto = button.getAttribute('data-monto');
    var cliente = button.getAttribute('data-cliente');
    
    document.getElementById('cita_id').value = id;
    document.getElementById('monto').value = monto;
    document.getElementById('clienteInfo').innerHTML = '<i class="fas fa-user"></i> Cliente: ' + cliente;
    montoOriginal = parseFloat(monto);
    document.getElementById('promoInfo').innerHTML = '';
    document.getElementById('descuento_aplicado').value = 0;
    document.getElementById('codigo_promo').value = '';
});
</script>
</body>
</html>
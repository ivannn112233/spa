<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$mensaje = '';
$descuento = 0;
$codigo_aplicado = '';
$monto_original = 0;
$monto_final = 0;

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

// Obtener promociones activas
$promociones = $db->query("
    SELECT * FROM promociones 
    WHERE activo = 1 
    AND fecha_inicio <= CURDATE() 
    AND fecha_fin >= CURDATE()
    AND usos_actuales < uso_maximo
    ORDER BY fecha_fin ASC
")->fetchAll();

// Aplicar promoción
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $codigo = strtoupper($_POST['codigo'] ?? '');
    $monto_original = floatval($_POST['monto_original'] ?? 0);
    
    if($codigo){
        $stmt = $db->prepare("SELECT * FROM promociones WHERE codigo = ? AND activo = 1 AND fecha_inicio <= CURDATE() AND fecha_fin >= CURDATE() AND usos_actuales < uso_maximo");
        $stmt->execute([$codigo]);
        $promo = $stmt->fetch();
        
        if($promo){
            $descuento = 0;
            if($promo['tipo'] == 'porcentaje'){
                $descuento = $monto_original * ($promo['valor'] / 100);
            } elseif($promo['tipo'] == 'monto_fijo'){
                $descuento = $promo['valor'];
            } elseif($promo['tipo'] == '2x1'){
                $descuento = $monto_original / 2;
            }
            
            $monto_final = max(0, $monto_original - $descuento);
            $codigo_aplicado = $codigo;
            $mensaje = '<div class="alert alert-success">Promoción aplicada: ' . $promo['nombre'] . ' (Descuento: Bs. ' . number_format($descuento, 2) . ')</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Código promocional inválido o expirado</div>';
            $monto_final = $monto_original;
        }
    } else {
        $monto_final = $monto_original;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aplicar Promociones - Recepción</title>
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
        
        .promo-card {
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid #e74c3c;
            border-radius: 10px;
            margin-bottom: 12px;
        }
        
        .promo-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .result-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
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
                <li><a class="nav-link" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><a class="nav-link active" href="promociones_aplicar.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="punto_venta.php"><i class="fas fa-shopping-cart"></i> Punto de Venta</a></li>
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
                    <h1 class="h3 mb-0"><i class="fas fa-tags text-primary"></i> Aplicar Promociones</h1>
                    <small class="text-muted">Aplica descuentos y promociones a las ventas</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <?php echo $mensaje; ?>
            
            <div class="row">
                <!-- Formulario -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calculator"></i> Calcular Descuento</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="promoForm">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-dollar-sign"></i> Monto original (Bs)</label>
                                    <input type="number" step="0.01" name="monto_original" class="form-control" id="monto_original" value="<?php echo $monto_original ?: ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-tag"></i> Código promocional</label>
                                    <div class="input-group">
                                        <input type="text" name="codigo" class="form-control" id="codigo" placeholder="Ingresa el código" value="<?php echo htmlspecialchars($codigo_aplicado); ?>">
                                        <button type="submit" class="btn btn-primary">Aplicar</button>
                                    </div>
                                </div>
                            </form>
                            
                            <?php if($monto_final > 0): ?>
                            <div class="result-card mt-3">
                                <div class="text-center">
                                    <i class="fas fa-receipt fa-2x mb-2"></i>
                                    <h5>Resumen de la venta</h5>
                                    <hr class="bg-white">
                                    <div class="d-flex justify-content-between">
                                        <span>Monto original:</span>
                                        <strong>Bs. <?php echo number_format($monto_original, 2); ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between text-warning">
                                        <span>Descuento:</span>
                                        <strong>- Bs. <?php echo number_format($descuento, 2); ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2 pt-2 border-top border-white">
                                        <span>Total a pagar:</span>
                                        <strong class="fs-4">Bs. <?php echo number_format($monto_final, 2); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-success w-100 mt-3" onclick="registrarPago(<?php echo $monto_final; ?>)">
                                <i class="fas fa-cash-register"></i> Cobrar Bs. <?php echo number_format($monto_final, 2); ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Promociones activas -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-gift"></i> Promociones Activas</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($promociones)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-tag fa-3x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No hay promociones activas disponibles</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($promociones as $p): ?>
                                <div class="card promo-card" onclick="aplicarPromocion('<?php echo $p['codigo']; ?>')">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><i class="fas fa-star text-warning"></i> <?php echo htmlspecialchars($p['nombre']); ?></strong>
                                                <br><small class="text-muted">Código: <span class="font-monospace"><?php echo $p['codigo']; ?></span></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary fs-6">
                                                    <?php 
                                                        if($p['tipo'] == 'porcentaje') echo $p['valor'] . '% OFF';
                                                        elseif($p['tipo'] == 'monto_fijo') echo 'Bs. ' . $p['valor'] . ' OFF';
                                                        elseif($p['tipo'] == '2x1') echo '2x1';
                                                        else echo '🎁 Servicio gratis';
                                                    ?>
                                                </span>
                                                <br><small class="text-muted">📅 Válido hasta: <?php echo date('d/m/Y', strtotime($p['fecha_fin'])); ?></small>
                                                <br><small class="text-muted">🔢 Usos: <?php echo $p['usos_actuales']; ?>/<?php echo $p['uso_maximo']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Info -->
            <div class="card shadow-sm bg-light">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle text-primary"></i> Cómo usar las promociones</h6>
                    <ol class="small mb-0">
                        <li>Ingresa el monto original del servicio o producto</li>
                        <li>Ingresa el código promocional o haz clic en una promoción activa</li>
                        <li>El sistema calculará automáticamente el descuento aplicable</li>
                        <li>Confirma el cobro con el monto final mostrado</li>
                    </ol>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="text-center text-muted mt-4 pt-3 border-top">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos los derechos reservados</small>
            </footer>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function aplicarPromocion(codigo) {
    document.getElementById('codigo').value = codigo;
    document.getElementById('promoForm').submit();
}

function registrarPago(monto) {
    if(confirm('¿Registrar pago por Bs. ' + monto.toFixed(2) + '?')){
        alert('Pago registrado correctamente');
        // Aquí puedes redirigir o limpiar el formulario
        // window.location.href = 'caja.php';
    }
}

// Validar que el monto sea positivo
document.getElementById('promoForm')?.addEventListener('submit', function(e) {
    const monto = document.getElementById('monto_original').value;
    if(parseFloat(monto) <= 0){
        e.preventDefault();
        alert('Por favor ingresa un monto válido mayor a cero');
    }
});
</script>
</body>
</html>
<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

// Obtener datos del cliente
$stmt = $db->prepare("SELECT id, nombre, apellido FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();
$cliente_id = $cliente['id'];

// Obtener iniciales para avatar
$iniciales = strtoupper(substr($cliente['nombre'], 0, 1) . substr($cliente['apellido'], 0, 1));

// Obtener mascotas del cliente
$stmt = $db->prepare("
    SELECT m.* FROM mascotas m
    JOIN mascota_dueno md ON m.id = md.mascota_id
    WHERE md.cliente_id = ?
    ORDER BY m.nombre
");
$stmt->execute([$cliente_id]);
$mascotas = $stmt->fetchAll();

// Obtener servicios
$servicios = $db->query("SELECT id, nombre, precio_base, duracion_base_minutos FROM servicios WHERE activo=1 ORDER BY nombre")->fetchAll();

// Obtener groomers
$groomers = $db->query("SELECT id, nombre, apellido FROM groomers WHERE estado_activo=1 ORDER BY nombre")->fetchAll();

$error = '';
$success = '';

// Función para calcular duración según tamaño
function calcularDuracion($duracionBase, $tamano) {
    $factores = ['pequeno' => 1.00, 'mediano' => 1.10, 'grande' => 1.15, 'gigante' => 1.30];
    $factor = $factores[strtolower($tamano)] ?? 1.10;
    return round($duracionBase * $factor);
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $mascota_id = $_POST['mascota_id'];
    $servicio_id = $_POST['servicio_id'];
    $groomer_id = $_POST['groomer_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    
    // Obtener tamaño de la mascota
    $stmt = $db->prepare("SELECT tamano FROM mascotas WHERE id = ?");
    $stmt->execute([$mascota_id]);
    $tamano = $stmt->fetchColumn();
    if(!$tamano) $tamano = 'mediano';
    
    // Obtener duración base del servicio
    $stmt = $db->prepare("SELECT duracion_base_minutos FROM servicios WHERE id = ?");
    $stmt->execute([$servicio_id]);
    $duracionBase = $stmt->fetchColumn();
    
    $duracion = calcularDuracion($duracionBase, $tamano);
    
    $fecha_hora = $fecha . ' ' . $hora . ':00';
    $fecha_fin = date('Y-m-d H:i:s', strtotime($fecha_hora . ' + ' . $duracion . ' minutes'));
    
    // Verificar disponibilidad
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM citas 
        WHERE groomer_id = ? AND fecha_hora_inicio < ? AND fecha_hora_fin > ?
        AND estado NOT IN ('cancelada', 'no_asistio')
    ");
    $stmt->execute([$groomer_id, $fecha_fin, $fecha_hora]);
    $conflicto = $stmt->fetchColumn();
    
    if($conflicto > 0){
        $error = "El groomer no está disponible en ese horario";
    } else {
        $stmt = $db->prepare("
            INSERT INTO citas (mascota_id, groomer_id, servicio_id, cliente_id, fecha_hora_inicio, fecha_hora_fin, estado, creado_por)
            VALUES (?,?,?,?,?,?,'agendada',?)
        ");
        if($stmt->execute([$mascota_id, $groomer_id, $servicio_id, $cliente_id, $fecha_hora, $fecha_fin, $_SESSION['user_id']])){
            $success = "Solicitud de cita enviada. Espera confirmación de recepción.";
            // Enviar notificación al cliente (simulada)
            AuditLog::log($_SESSION['user_id'], ROLE_CLIENTE, 'CITA_SOLICITADA', "Solicitó cita para mascota ID: $mascota_id", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        } else {
            $error = "Error al agendar cita";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita - <?php echo SITE_NAME; ?></title>
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
        
        .info-duracion {
            font-size: 12px;
            color: #27ae60;
            margin-top: 8px;
            padding: 8px;
            background: #e8f5e9;
            border-radius: 8px;
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
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></h5>
                <small class="text-muted"><i class="fas fa-user"></i> Cliente</small>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a class="nav-link" href="mascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="historial.php"><i class="fas fa-history"></i> Historial</a></li>
                <li><a class="nav-link" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link" href="notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                <li><a class="nav-link" href="tienda/"><i class="fas fa-store"></i> Tienda</a></li>
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
                    <h1 class="h3 mb-0"><i class="fas fa-calendar-plus text-primary"></i> Agendar Cita</h1>
                    <small class="text-muted">Solicita una nueva cita para tu mascota</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?> <a href="citas.php">Ver mis citas</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php else: ?>
                <?php if(empty($mascotas)): ?>
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-paw fa-4x text-muted mb-3"></i>
                            <h5>No tienes mascotas registradas</h5>
                            <p class="text-muted">Para agendar una cita, primero debes registrar una mascota</p>
                            <a href="mascota_agregar.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Registrar Mascota
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-calendar-plus"></i> Formulario de Solicitud</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="agendarForm">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-paw"></i> Mascota *</label>
                                    <select name="mascota_id" id="mascota_id" class="form-select" required>
                                        <option value="">Seleccionar mascota</option>
                                        <?php foreach($mascotas as $m): ?>
                                        <option value="<?php echo $m['id']; ?>" data-tamano="<?php echo $m['tamano']; ?>">
                                            <?php echo htmlspecialchars($m['nombre']); ?> 
                                            (<?php echo ucfirst($m['especie']); ?> 
                                            <?php if($m['tamano']): ?>- <?php echo ucfirst($m['tamano']); ?><?php endif; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="infoDuracion" class="info-duracion"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-cut"></i> Servicio *</label>
                                    <select name="servicio_id" id="servicio_id" class="form-select" required>
                                        <option value="">Seleccionar servicio</option>
                                        <?php foreach($servicios as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" data-duracion="<?php echo $s['duracion_base_minutos']; ?>">
                                            <?php echo htmlspecialchars($s['nombre']); ?> - Bs. <?php echo number_format($s['precio_base'], 2); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-user-md"></i> Groomer *</label>
                                    <select name="groomer_id" class="form-select" required>
                                        <option value="">Seleccionar groomer</option>
                                        <?php foreach($groomers as $g): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['nombre'] . ' ' . $g['apellido']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-calendar-day"></i> Fecha *</label>
                                        <input type="date" name="fecha" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-clock"></i> Hora *</label>
                                        <input type="time" name="hora" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-2">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Duración ajustada por tamaño:</strong> La duración se ajusta según el tamaño de tu mascota:
                                    <ul class="mb-0 mt-1">
                                        <li>🐕 Pequeño: Duración base</li>
                                        <li>🐕‍🦺 Mediano: Duración base + 10%</li>
                                        <li>🐕‍🦺 Grande: Duración base + 15%</li>
                                        <li>🐕‍🦺 Gigante: Duración base + 30%</li>
                                    </ul>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-paper-plane"></i> Solicitar Cita
                                </button>
                                <a href="citas.php" class="btn btn-secondary w-100 mt-2">
                                    <i class="fas fa-arrow-left"></i> Cancelar
                                </a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Footer -->
            <footer class="text-center text-muted mt-4 pt-3 border-top">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos los derechos reservados</small>
            </footer>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const factores = {
    'pequeno': 1.00,
    'mediano': 1.10,
    'grande': 1.15,
    'gigante': 1.30
};

const mascotaSelect = document.getElementById('mascota_id');
const servicioSelect = document.getElementById('servicio_id');
const infoDiv = document.getElementById('infoDuracion');

if(mascotaSelect){
    mascotaSelect.addEventListener('change', calcularDuracion);
}
if(servicioSelect){
    servicioSelect.addEventListener('change', calcularDuracion);
}

function calcularDuracion() {
    if(mascotaSelect && servicioSelect && mascotaSelect.value && servicioSelect.value){
        const tamano = mascotaSelect.options[mascotaSelect.selectedIndex].getAttribute('data-tamano') || 'mediano';
        const duracionBase = parseInt(servicioSelect.options[servicioSelect.selectedIndex].getAttribute('data-duracion')) || 60;
        const factor = factores[tamano.toLowerCase()] || 1.10;
        const duracionFinal = Math.round(duracionBase * factor);
        const extra = ((factor - 1) * 100).toFixed(0);
        
        if(infoDiv){
            infoDiv.innerHTML = `<i class="fas fa-clock"></i> Duración estimada: <strong>${duracionFinal} minutos</strong> 
                                 (Base: ${duracionBase} min + ${extra}% por tamaño ${tamano})`;
            infoDiv.style.display = 'block';
        }
    } else if(infoDiv){
        infoDiv.innerHTML = '';
        infoDiv.style.display = 'none';
    }
}
</script>
</body>
</html>
<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

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

// Obtener clientes
$clientes = $db->query("SELECT id, nombre, apellido, telefono FROM clientes ORDER BY nombre")->fetchAll();

// Obtener servicios
$servicios = $db->query("SELECT id, nombre, precio_base, duracion_base_minutos FROM servicios WHERE activo=1")->fetchAll();

// Obtener groomers
$groomers = $db->query("SELECT id, nombre, apellido FROM groomers WHERE estado_activo=1")->fetchAll();

// Función para calcular duración según tamaño de mascota
function calcularDuracionConTamaño($duracionBase, $tamanoMascota) {
    $factores = [
        'pequeno' => 1.00,
        'mediano' => 1.10,
        'grande' => 1.15,
        'gigante' => 1.30
    ];
    $factor = $factores[strtolower($tamanoMascota)] ?? 1.00;
    return round($duracionBase * $factor);
}

// Procesar formulario
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $cliente_id = $_POST['cliente_id'] ?: null;
    $mascota_id = $_POST['mascota_id'];
    $servicio_id = $_POST['servicio_id'];
    $groomer_id = $_POST['groomer_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $notas = $_POST['notas'] ?? '';
    
    if(!$mascota_id || !$servicio_id || !$groomer_id || !$fecha || !$hora){
        $error = "Complete todos los campos obligatorios";
    } else {
        // Obtener duración base del servicio
        $stmt = $db->prepare("SELECT duracion_base_minutos FROM servicios WHERE id = ?");
        $stmt->execute([$servicio_id]);
        $duracionBase = $stmt->fetchColumn();
        
        // Obtener tamaño de la mascota
        $stmt = $db->prepare("SELECT tamano FROM mascotas WHERE id = ?");
        $stmt->execute([$mascota_id]);
        $tamano = $stmt->fetchColumn();
        if(!$tamano) $tamano = 'mediano';
        
        // Calcular duración ajustada por tamaño
        $duracion = calcularDuracionConTamaño($duracionBase, $tamano);
        
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
                INSERT INTO citas (mascota_id, groomer_id, servicio_id, cliente_id, fecha_hora_inicio, fecha_hora_fin, estado, notas, creado_por)
                VALUES (?, ?, ?, ?, ?, ?, 'confirmada', ?, ?)
            ");
            $creado_por = $_SESSION['user_id'];
            
            if($stmt->execute([$mascota_id, $groomer_id, $servicio_id, $cliente_id, $fecha_hora, $fecha_fin, $notas, $creado_por])){
                $success = "Cita agendada correctamente";
            } else {
                $error = "Error al agendar cita";
            }
        }
    }
}

// Variable para almacenar mascotas (inicialmente vacío)
$mascotas = [];
$cliente_seleccionado = $_POST['cliente_id'] ?? $_GET['cliente'] ?? 0;

// Si hay un cliente seleccionado, cargar sus mascotas
if($cliente_seleccionado){
    $stmt = $db->prepare("
        SELECT m.id, m.nombre, m.tamano 
        FROM mascotas m 
        JOIN mascota_dueno md ON m.id = md.mascota_id 
        WHERE md.cliente_id = ?
        ORDER BY m.nombre
    ");
    $stmt->execute([$cliente_seleccionado]);
    $mascotas = $stmt->fetchAll();
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
            margin-top: 5px;
            padding: 5px;
            background: #e8f5e9;
            border-radius: 5px;
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
                <li><a class="nav-link active" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
                <li><a class="nav-link" href="calendario.php"><i class="fas fa-calendar-week"></i> Calendario</a></li>
                <li><a class="nav-link" href="solicitudes.php"><i class="fas fa-inbox"></i> Solicitudes</a></li>
                <li><a class="nav-link" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
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
                    <h1 class="h3 mb-0"><i class="fas fa-plus-circle text-primary"></i> Agendar Nueva Cita</h1>
                    <small class="text-muted">Registra una nueva cita en el sistema</small>
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
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?> <a href="citas.php">Ver citas</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus"></i> Formulario de Registro</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="agendarForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user"></i> Cliente *</label>
                                <select name="cliente_id" id="cliente_id" class="form-select" required onchange="cargarMascotas()">
                                    <option value="">Seleccionar cliente</option>
                                    <?php foreach($clientes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($cliente_seleccionado == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['nombre'] . ' ' . $c['apellido'] . ' - ' . $c['telefono']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-paw"></i> Mascota *</label>
                                <select name="mascota_id" id="mascota_id" class="form-select" required>
                                    <option value="">Primero selecciona un cliente</option>
                                </select>
                                <div id="infoDuracion" class="info-duracion"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-cut"></i> Servicio *</label>
                                <select name="servicio_id" id="servicio_id" class="form-select" required>
                                    <option value="">Seleccionar servicio</option>
                                    <?php foreach($servicios as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" data-duracion="<?php echo $s['duracion_base_minutos']; ?>">
                                        <?php echo htmlspecialchars($s['nombre']) . ' - Bs. ' . number_format($s['precio_base'], 2) . ' (' . $s['duracion_base_minutos'] . ' min base)'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user-md"></i> Groomer *</label>
                                <select name="groomer_id" class="form-select" required>
                                    <option value="">Seleccionar groomer</option>
                                    <?php foreach($groomers as $g): ?>
                                    <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['nombre'] . ' ' . $g['apellido']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-calendar-day"></i> Fecha *</label>
                                <input type="date" name="fecha" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-clock"></i> Hora *</label>
                                <input type="time" name="hora" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><i class="fas fa-sticky-note"></i> Notas</label>
                                <textarea name="notas" class="form-control" rows="2" placeholder="Información adicional..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Agendar Cita</button>
                        <a href="citas.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancelar</a>
                    </form>
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> 
                <strong>Duración ajustada por tamaño:</strong> La duración del servicio se ajusta automáticamente según el tamaño de la mascota:
                <ul class="mb-0 mt-1">
                    <li>🐕 Pequeño: Duración base</li>
                    <li>🐕‍🦺 Mediano: Duración base + 10%</li>
                    <li>🐕‍🦺 Grande: Duración base + 15%</li>
                    <li>🐕‍🦺 Gigante: Duración base + 30%</li>
                </ul>
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
const factores = {
    'pequeno': 1.00,
    'mediano': 1.10,
    'grande': 1.15,
    'gigante': 1.30
};

const mascotaSelect = document.getElementById('mascota_id');
const servicioSelect = document.getElementById('servicio_id');
const infoDiv = document.getElementById('infoDuracion');

if(servicioSelect){
    servicioSelect.addEventListener('change', calcularDuracion);
}

function cargarMascotas() {
    const clienteId = document.getElementById('cliente_id').value;
    const mascotaSelect = document.getElementById('mascota_id');
    
    if(!clienteId){
        mascotaSelect.innerHTML = '<option value="">Primero selecciona un cliente</option>';
        if(infoDiv) infoDiv.innerHTML = '';
        return;
    }
    
    // Mostrar loading
    mascotaSelect.innerHTML = '<option value="">Cargando mascotas...</option>';
    
    fetch('ajax_mascotas_por_cliente.php?cliente_id=' + clienteId)
        .then(response => response.json())
        .then(data => {
            if(data.success && data.mascotas.length > 0){
                let options = '<option value="">Seleccionar mascota</option>';
                data.mascotas.forEach(m => {
                    options += `<option value="${m.id}" data-tamano="${m.tamano}">${m.nombre}${m.tamano ? ' - ' + m.tamano : ''}</option>`;
                });
                mascotaSelect.innerHTML = options;
                
                // Si hay una sola mascota, seleccionarla automáticamente
                if(data.mascotas.length === 1){
                    mascotaSelect.value = data.mascotas[0].id;
                    calcularDuracion();
                }
            } else {
                mascotaSelect.innerHTML = '<option value="">Este cliente no tiene mascotas registradas</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mascotaSelect.innerHTML = '<option value="">Error al cargar mascotas</option>';
        });
}

function calcularDuracion() {
    if(mascotaSelect && servicioSelect && mascotaSelect.value && servicioSelect.value){
        const selectedMascota = mascotaSelect.options[mascotaSelect.selectedIndex];
        const selectedServicio = servicioSelect.options[servicioSelect.selectedIndex];
        
        const tamano = selectedMascota.getAttribute('data-tamano') || 'mediano';
        const duracionBase = parseInt(selectedServicio.getAttribute('data-duracion')) || 60;
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

// Si hay un cliente seleccionado por GET (desde clientes.php), cargar sus mascotas
if(document.getElementById('cliente_id').value){
    cargarMascotas();
}
</script>
</body>
</html>
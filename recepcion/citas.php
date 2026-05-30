<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$mensaje = '';

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

// Reprogramar cita
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reprogramar'])){
    $cita_id = $_POST['cita_id'];
    $nueva_fecha = $_POST['nueva_fecha'];
    $nueva_hora = $_POST['nueva_hora'];
    $nuevo_groomer = $_POST['nuevo_groomer'];
    $motivo = $_POST['motivo'] ?? '';
    
    $nueva_fecha_hora = $nueva_fecha . ' ' . $nueva_hora . ':00';
    
    $stmt = $db->prepare("SELECT s.duracion_base_minutos FROM citas c JOIN servicios s ON c.servicio_id = s.id WHERE c.id = ?");
    $stmt->execute([$cita_id]);
    $duracion = $stmt->fetchColumn();
    
    $nueva_fecha_fin = date('Y-m-d H:i:s', strtotime($nueva_fecha_hora . ' + ' . $duracion . ' minutes'));
    
    $stmt = $db->prepare("
        UPDATE citas 
        SET fecha_hora_inicio = ?, fecha_hora_fin = ?, groomer_id = ?, 
            estado = 'confirmada', reprogramado_fecha = NOW(), reprogramado_por = ?,
            motivo_cancelacion = ?
        WHERE id = ?
    ");
    $stmt->execute([$nueva_fecha_hora, $nueva_fecha_fin, $nuevo_groomer, $_SESSION['user_id'], $motivo, $cita_id]);
    
    $mensaje = '<div class="alert alert-success">Cita reprogramada correctamente</div>';
}

// Cambiar estado de cita
if(isset($_GET['cambiar_estado'])){
    $stmt = $db->prepare("UPDATE citas SET estado = ? WHERE id = ?");
    $stmt->execute([$_GET['cambiar_estado'], $_GET['id']]);
    $mensaje = '<div class="alert alert-success">Estado actualizado</div>';
}

// Cancelar cita
if(isset($_GET['cancelar'])){
    $stmt = $db->prepare("UPDATE citas SET estado = 'cancelada', motivo_cancelacion = ? WHERE id = ?");
    $stmt->execute([$_GET['motivo'] ?? 'Cancelado por recepción', $_GET['cancelar']]);
    $mensaje = '<div class="alert alert-warning">Cita cancelada</div>';
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_fecha = $_GET['fecha'] ?? date('Y-m-d');

$sql = "SELECT c.*, m.nombre as mascota, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
        s.nombre as servicio, gr.nombre as groomer, gr.apellido as groomer_apellido
        FROM citas c
        JOIN mascotas m ON m.id = c.mascota_id
        JOIN clientes cl ON cl.id = c.cliente_id
        JOIN servicios s ON s.id = c.servicio_id
        JOIN groomers gr ON gr.id = c.groomer_id
        WHERE 1=1";

if($filtro_estado != 'todos') $sql .= " AND c.estado = '$filtro_estado'";
if($filtro_fecha) $sql .= " AND DATE(c.fecha_hora_inicio) = '$filtro_fecha'";

$sql .= " ORDER BY c.fecha_hora_inicio DESC";
$citas = $db->query($sql)->fetchAll();

$groomers = $db->query("SELECT id, nombre, apellido FROM groomers WHERE estado_activo=1")->fetchAll();
$servicios = $db->query("SELECT id, nombre FROM servicios WHERE activo=1")->fetchAll();

// Estadísticas
$total_citas = count($citas);
$completadas = count(array_filter($citas, fn($c) => $c['estado'] == 'completada'));
$pendientes = count(array_filter($citas, fn($c) => $c['estado'] == 'agendada' || $c['estado'] == 'confirmada'));
$canceladas = count(array_filter($citas, fn($c) => $c['estado'] == 'cancelada'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Citas - Recepción</title>
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
            padding: 15px;
            color: white;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .cita-row {
            transition: all 0.2s;
        }
        
        .cita-row:hover {
            background-color: #f8f9fa;
            transform: translateX(3px);
        }
        
        .draggable {
            cursor: grab;
        }
        
        .draggable:active {
            cursor: grabbing;
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
                <li><a class="nav-link active" href="citas.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a class="nav-link" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
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
                    <h1 class="h3 mb-0"><i class="fas fa-calendar-alt text-primary"></i> Gestión de Citas</h1>
                    <small class="text-muted">Administra todas las citas del sistema</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <?php echo $mensaje; ?>
            
            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-md-3 mb-2">
                    <div class="stat-card bg-primary">
                        <h3 class="mb-0"><?php echo $total_citas; ?></h3>
                        <small>Total Citas</small>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="stat-card bg-warning">
                        <h3 class="mb-0"><?php echo $pendientes; ?></h3>
                        <small>Pendientes</small>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="stat-card bg-success">
                        <h3 class="mb-0"><?php echo $completadas; ?></h3>
                        <small>Completadas</small>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="stat-card bg-danger">
                        <h3 class="mb-0"><?php echo $canceladas; ?></h3>
                        <small>Canceladas</small>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label"><i class="fas fa-calendar-day"></i> Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo $filtro_fecha; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><i class="fas fa-filter"></i> Estado</label>
                            <select name="estado" class="form-select">
                                <option value="todos" <?php echo $filtro_estado=='todos'?'selected':''; ?>>Todos</option>
                                <option value="agendada" <?php echo $filtro_estado=='agendada'?'selected':''; ?>>Agendada</option>
                                <option value="confirmada" <?php echo $filtro_estado=='confirmada'?'selected':''; ?>>Confirmada</option>
                                <option value="en_progreso" <?php echo $filtro_estado=='en_progreso'?'selected':''; ?>>En Progreso</option>
                                <option value="completada" <?php echo $filtro_estado=='completada'?'selected':''; ?>>Completada</option>
                                <option value="cancelada" <?php echo $filtro_estado=='cancelada'?'selected':''; ?>>Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                            <a href="citas.php" class="btn btn-secondary ms-2"><i class="fas fa-eraser"></i> Limpiar</a>
                        </div>
                        <div class="col-md-3 d-flex align-items-end justify-content-end">
                            <a href="cita_agendar.php" class="btn btn-success"><i class="fas fa-plus"></i> Nueva Cita</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabla de citas -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Citas</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($citas)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h5>No hay citas para mostrar</h5>
                            <p class="text-muted">No se encontraron citas con los filtros seleccionados</p>
                            <a href="cita_agendar.php" class="btn btn-primary">Agendar nueva cita</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Cliente</th>
                                        <th>Mascota</th>
                                        <th>Servicio</th>
                                        <th>Groomer</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($citas as $c): 
                                        $badgeClass = 'secondary';
                                        $estadoTexto = ucfirst($c['estado']);
                                        if($c['estado'] == 'agendada') {
                                            $badgeClass = 'warning';
                                        } elseif($c['estado'] == 'confirmada') {
                                            $badgeClass = 'info';
                                        } elseif($c['estado'] == 'en_progreso') {
                                            $badgeClass = 'primary';
                                            $estadoTexto = 'En Progreso';
                                        } elseif($c['estado'] == 'completada') {
                                            $badgeClass = 'success';
                                        } elseif($c['estado'] == 'cancelada') {
                                            $badgeClass = 'danger';
                                        }
                                    ?>
                                    <tr class="cita-row draggable" draggable="true" data-id="<?php echo $c['id']; ?>" data-nombre="<?php echo htmlspecialchars($c['cliente_nombre'] . ' ' . $c['cliente_apellido']); ?>">
                                        <td>
                                            <strong><?php echo date('d/m/Y', strtotime($c['fecha_hora_inicio'])); ?></strong>
                                            <br><small class="text-muted"><?php echo date('H:i', strtotime($c['fecha_hora_inicio'])); ?></small>
                                         </div>
                                        <td>
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($c['cliente_nombre'] . ' ' . $c['cliente_apellido']); ?>
                                         </div>
                                        <td>
                                            <i class="fas fa-paw"></i> <?php echo htmlspecialchars($c['mascota']); ?>
                                         </div>
                                        <td>
                                            <?php echo htmlspecialchars($c['servicio']); ?>
                                         </div>
                                        <td>
                                            <i class="fas fa-user-md"></i> <?php echo htmlspecialchars($c['groomer'] . ' ' . ($c['groomer_apellido'] ?? '')); ?>
                                         </div>
                                        <td>
                                            <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $estadoTexto; ?></span>
                                         </div>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-info" onclick="verCita(<?php echo $c['id']; ?>)" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" onclick="reprogramarCita(<?php echo $c['id']; ?>, '<?php echo date('Y-m-d', strtotime($c['fecha_hora_inicio'])); ?>', '<?php echo date('H:i', strtotime($c['fecha_hora_inicio'])); ?>', <?php echo $c['groomer_id']; ?>)" title="Reprogramar">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </button>
                                                <?php if($c['estado'] == 'agendada'): ?>
                                                <a href="citas.php?cambiar_estado=confirmada&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('¿Confirmar esta cita?')" title="Confirmar">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if($c['estado'] != 'cancelada' && $c['estado'] != 'completada'): ?>
                                                <a href="citas.php?cancelar=<?php echo $c['id']; ?>&motivo=Cancelado por recepción" class="btn btn-sm btn-danger" onclick="return confirm('¿Cancelar esta cita?')" title="Cancelar">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                         </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 text-muted">
                            <small><i class="fas fa-info-circle"></i> Mostrando <?php echo count($citas); ?> citas</small>
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

<!-- Modal Reprogramar -->
<div class="modal fade" id="reprogramarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-alt"></i> Reprogramar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="cita_id" id="cita_id">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-calendar-day"></i> Nueva Fecha</label>
                        <input type="date" name="nueva_fecha" class="form-control" id="nueva_fecha" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-clock"></i> Nueva Hora</label>
                        <input type="time" name="nueva_hora" class="form-control" id="nueva_hora" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-user-md"></i> Nuevo Groomer</label>
                        <select name="nuevo_groomer" class="form-select" id="nuevo_groomer" required>
                            <?php foreach($groomers as $g): ?>
                            <option value="<?php echo $g['id']; ?>"><?php echo $g['nombre'] . ' ' . $g['apellido']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-sticky-note"></i> Motivo de reprogramación</label>
                        <textarea name="motivo" class="form-control" rows="2" placeholder="Opcional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="reprogramar" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function verCita(id){
    window.location.href = 'cliente_ver.php?id=' + id;
}

function reprogramarCita(id, fecha, hora, groomerId){
    document.getElementById('cita_id').value = id;
    document.getElementById('nueva_fecha').value = fecha;
    document.getElementById('nueva_hora').value = hora;
    document.getElementById('nuevo_groomer').value = groomerId;
    new bootstrap.Modal(document.getElementById('reprogramarModal')).show();
}

// Drag & drop para reprogramar (visual)
const draggables = document.querySelectorAll('.draggable');
draggables.forEach(drag => {
    drag.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('text/plain', drag.getAttribute('data-id'));
        drag.style.opacity = '0.5';
    });
    drag.addEventListener('dragend', () => {
        drag.style.opacity = '1';
    });
});
</script>
</body>
</html>
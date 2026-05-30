<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$vista = $_GET['vista'] ?? 'dia'; // dia, semana

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

// Obtener groomers
$groomers = $db->query("SELECT id, nombre, apellido FROM groomers WHERE estado_activo = 1")->fetchAll();

// Obtener citas del día
$stmt = $db->prepare("
    SELECT c.*, m.nombre as mascota, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
           s.nombre as servicio, s.duracion_base_minutos
    FROM citas c
    JOIN mascotas m ON m.id = c.mascota_id
    JOIN clientes cl ON cl.id = c.cliente_id
    JOIN servicios s ON s.id = c.servicio_id
    WHERE DATE(c.fecha_hora_inicio) = ? AND c.estado NOT IN ('cancelada', 'no_asistio')
    ORDER BY c.fecha_hora_inicio ASC
");
$stmt->execute([$fecha]);
$citas = $stmt->fetchAll();

// Agrupar citas por groomer
$citas_por_groomer = [];
foreach($citas as $cita){
    $citas_por_groomer[$cita['groomer_id']][] = $cita;
}

// Horario laboral
$horario_inicio = '09:00';
$horario_fin = '18:00';
$intervalo = 30; // minutos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calendario - Recepción</title>
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
        
        .calendario {
            overflow-x: auto;
        }
        
        .hora-columna {
            width: 80px;
            background: #f8f9fa;
            position: sticky;
            left: 0;
            z-index: 1;
        }
        
        .groomer-columna {
            min-width: 220px;
            vertical-align: top;
        }
        
        .cita-item {
            background: #3498db;
            color: white;
            border-radius: 8px;
            padding: 8px;
            margin: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .cita-item:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .cita-item.agendada { background: #f39c12; }
        .cita-item.confirmada { background: #27ae60; }
        .cita-item.en_progreso { background: #3498db; }
        .cita-item.completada { background: #95a5a6; }
        
        .hora-fila {
            height: 60px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .hora-label {
            font-size: 12px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .slot-vacio {
            height: 52px;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .slot-vacio:hover {
            background: #e8f4fd !important;
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
            .groomer-columna {
                min-width: 180px;
            }
            .cita-item {
                font-size: 10px;
                padding: 4px;
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
                <li><a class="nav-link active" href="calendario.php"><i class="fas fa-calendar-week"></i> Calendario</a></li>
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
                    <h1 class="h3 mb-0"><i class="fas fa-calendar-week text-primary"></i> Calendario de Citas</h1>
                    <small class="text-muted">Visualiza y gestiona las citas programadas</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <!-- Controles -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div class="btn-group">
                    <a href="calendario.php?vista=dia&fecha=<?php echo $fecha; ?>" class="btn btn-outline-primary <?php echo $vista=='dia'?'active':''; ?>">
                        <i class="fas fa-calendar-day"></i> Día
                    </a>
                    <a href="calendario.php?vista=semana&fecha=<?php echo $fecha; ?>" class="btn btn-outline-primary <?php echo $vista=='semana'?'active':''; ?>">
                        <i class="fas fa-calendar-week"></i> Semana
                    </a>
                </div>
                <div class="btn-group">
                    <a href="calendario.php?vista=<?php echo $vista; ?>&fecha=<?php echo date('Y-m-d', strtotime($fecha . ' -1 day')); ?>" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <button class="btn btn-secondary" onclick="irHoy()">
                        <i class="fas fa-calendar-day"></i> Hoy
                    </button>
                    <a href="calendario.php?vista=<?php echo $vista; ?>&fecha=<?php echo date('Y-m-d', strtotime($fecha . ' +1 day')); ?>" class="btn btn-secondary">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Calendario -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar"></i> 
                        <?php echo date('d/m/Y', strtotime($fecha)); ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="calendario">
                        <table class="table table-bordered mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="hora-columna text-center">Hora</th>
                                    <?php foreach($groomers as $g): ?>
                                    <th class="groomer-columna text-center">
                                        <i class="fas fa-user-md"></i> <?php echo $g['nombre'] . ' ' . $g['apellido']; ?>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $hora_actual = strtotime($horario_inicio);
                                $fin = strtotime($horario_fin);
                                while($hora_actual < $fin):
                                    $hora_label = date('H:i', $hora_actual);
                                ?>
                                <tr class="hora-fila">
                                    <td class="hora-columna text-center align-middle hora-label">
                                        <?php echo $hora_label; ?>
                                    </td>
                                    <?php foreach($groomers as $g): 
                                        $cita_en_hora = null;
                                        if(isset($citas_por_groomer[$g['id']])){
                                            foreach($citas_por_groomer[$g['id']] as $c){
                                                $cita_inicio = date('H:i', strtotime($c['fecha_hora_inicio']));
                                                if($cita_inicio == $hora_label){
                                                    $cita_en_hora = $c;
                                                    break;
                                                }
                                            }
                                        }
                                    ?>
                                    <td class="align-middle" style="background: <?php echo $cita_en_hora ? '#e8f4fd' : '#fff'; ?>">
                                        <?php if($cita_en_hora): ?>
                                        <div class="cita-item <?php echo $cita_en_hora['estado']; ?>" 
                                             onclick="verCita(<?php echo $cita_en_hora['id']; ?>)"
                                             data-id="<?php echo $cita_en_hora['id']; ?>"
                                             draggable="true"
                                             ondragstart="dragStart(event)"
                                             ondragend="dragEnd(event)">
                                            <strong><i class="fas fa-paw"></i> <?php echo htmlspecialchars(substr($cita_en_hora['mascota'], 0, 15)); ?></strong><br>
                                            <small><i class="fas fa-cut"></i> <?php echo htmlspecialchars($cita_en_hora['servicio']); ?></small>
                                            <br><small><i class="fas fa-user"></i> <?php echo htmlspecialchars($cita_en_hora['cliente_nombre']); ?></small>
                                        </div>
                                        <?php else: ?>
                                        <div class="slot-vacio" 
                                             style="height: 52px; cursor: pointer;"
                                             onclick="agendarCita('<?php echo $hora_label; ?>', <?php echo $g['id']; ?>)">
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                 </tr>
                                <?php 
                                    $hora_actual = strtotime("+{$intervalo} minutes", $hora_actual);
                                endwhile; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="text-center text-muted mt-4 pt-3 border-top">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos los derechos reservados</small>
            </footer>
        </main>
    </div>
</div>

<!-- Modal Reprogramar Cita -->
<div class="modal fade" id="reprogramarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-alt"></i> Reprogramar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="citas.php">
                <div class="modal-body">
                    <input type="hidden" name="cita_id" id="reprogramar_cita_id">
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
let dragCitaId = null;

function verCita(id) {
    window.location.href = 'citas.php?edit=' + id;
}

function agendarCita(hora, groomerId) {
    window.location.href = 'cita_agendar.php?hora=' + hora + '&groomer=' + groomerId + '&fecha=<?php echo $fecha; ?>';
}

function irHoy() {
    window.location.href = 'calendario.php?vista=<?php echo $vista; ?>&fecha=<?php echo date('Y-m-d'); ?>';
}

function dragStart(event) {
    dragCitaId = event.target.closest('.cita-item')?.getAttribute('data-id');
    if(dragCitaId){
        event.dataTransfer.setData('text/plain', dragCitaId);
        event.target.style.opacity = '0.5';
    }
}

function dragEnd(event) {
    event.target.style.opacity = '1';
    dragCitaId = null;
}

// Permitir soltar en slots vacíos
document.querySelectorAll('.slot-vacio').forEach(slot => {
    slot.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '#d4edda';
    });
    
    slot.addEventListener('dragleave', function(e) {
        this.style.backgroundColor = '';
    });
    
    slot.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.backgroundColor = '';
        if(dragCitaId){
            const row = this.closest('tr');
            const hora = row?.querySelector('.hora-label')?.innerText;
            const groomerId = this.closest('td').parentElement?.querySelector('th')?.innerText;
            
            if(hora && groomerId){
                document.getElementById('reprogramar_cita_id').value = dragCitaId;
                document.getElementById('nueva_fecha').value = '<?php echo $fecha; ?>';
                document.getElementById('nueva_hora').value = hora;
                
                new bootstrap.Modal(document.getElementById('reprogramarModal')).show();
            }
        }
    });
});
</script>
</body>
</html>
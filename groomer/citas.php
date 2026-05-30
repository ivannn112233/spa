<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_GROOMER);

$db = Database::getInstance()->getConnection();

// Obtener ID del groomer
$stmt = $db->prepare("SELECT g.id, g.nombre, g.apellido, u.email 
                      FROM groomers g 
                      JOIN usuarios u ON g.usuario_id = u.id 
                      WHERE u.id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$groomer = $stmt->fetch();
$groomerId = $groomer['id'];

// Cambiar estado de cita
if(isset($_GET['cambiar_estado'])){
    $cita_id = $_GET['cambiar_estado'];
    $estado = $_GET['estado'];
    
    $stmt = $db->prepare("UPDATE citas SET estado = :estado WHERE id = :id AND groomer_id = :groomer_id");
    $stmt->execute([':estado' => $estado, ':id' => $cita_id, ':groomer_id' => $groomerId]);
    
    AuditLog::log($_SESSION['user_id'], ROLE_GROOMER, 'CAMBIO_ESTADO_CITA', "Cambió cita ID: $cita_id a estado: $estado", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    
    header("Location: citas.php?msg=Estado actualizado");
    exit;
}

// Cerrar cita (completar)
if(isset($_GET['cerrar_cita'])){
    $cita_id = $_GET['cerrar_cita'];
    
    // Verificar que el checklist esté completo
    $stmt = $db->prepare("
        SELECT COUNT(*) as total, SUM(completado) as completados 
        FROM ficha_checklist fc
        JOIN fichas_grooming f ON fc.ficha_id = f.id
        WHERE f.cita_id = :cita_id
    ");
    $stmt->execute([':cita_id' => $cita_id]);
    $checklist = $stmt->fetch();
    
    if($checklist['total'] > 0 && $checklist['total'] == $checklist['completados']){
        $stmt = $db->prepare("UPDATE citas SET estado = 'completada' WHERE id = :id");
        $stmt->execute([':id' => $cita_id]);
        
        // Registrar en auditoría
        AuditLog::log($_SESSION['user_id'], ROLE_GROOMER, 'CITA_COMPLETADA', "Completó cita ID: $cita_id", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        
        header("Location: citas.php?msg=Cita completada exitosamente");
    } else {
        header("Location: citas.php?error=Debes completar todo el checklist antes de cerrar la cita");
    }
    exit;
}

// Reprogramar cita
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reprogramar'])){
    $cita_id = $_POST['cita_id'];
    $nueva_fecha = $_POST['nueva_fecha'];
    $nueva_hora = $_POST['nueva_hora'];
    $motivo = $_POST['motivo'] ?? '';
    
    $nueva_fecha_hora = $nueva_fecha . ' ' . $nueva_hora . ':00';
    
    // Obtener duración del servicio
    $stmt = $db->prepare("
        SELECT s.duracion_base_minutos FROM citas c 
        JOIN servicios s ON c.servicio_id = s.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$cita_id]);
    $duracion = $stmt->fetchColumn();
    
    $nueva_fecha_fin = date('Y-m-d H:i:s', strtotime($nueva_fecha_hora . ' + ' . $duracion . ' minutes'));
    
    $stmt = $db->prepare("
        UPDATE citas 
        SET fecha_hora_inicio = ?, fecha_hora_fin = ?, 
            estado = 'confirmada', reprogramado_fecha = NOW(), reprogramado_por = ?,
            motivo_cancelacion = ?
        WHERE id = ? AND groomer_id = ?
    ");
    $stmt->execute([$nueva_fecha_hora, $nueva_fecha_fin, $_SESSION['user_id'], $motivo, $cita_id, $groomerId]);
    
    AuditLog::log($_SESSION['user_id'], ROLE_GROOMER, 'CITA_REPROGRAMADA', "Reprogramó cita ID: $cita_id", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    
    header("Location: citas.php?msg=Cita reprogramada correctamente");
    exit;
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_fecha = $_GET['fecha'] ?? date('Y-m-d');

$sql = "
    SELECT c.*, m.nombre as mascota_nombre, m.especie, m.raza, m.tamano,
           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido, cl.telefono,
           s.nombre as servicio_nombre, s.duracion_base_minutos,
           f.id as ficha_id, f.estado_inicial, f.estado_final
    FROM citas c
    JOIN mascotas m ON m.id = c.mascota_id
    JOIN clientes cl ON cl.id = c.cliente_id
    JOIN servicios s ON s.id = c.servicio_id
    LEFT JOIN fichas_grooming f ON f.cita_id = c.id
    WHERE c.groomer_id = :groomer_id
";

if($filtro_estado != 'todos') $sql .= " AND c.estado = :estado";
if($filtro_fecha) $sql .= " AND DATE(c.fecha_hora_inicio) = :fecha";

$sql .= " ORDER BY c.fecha_hora_inicio DESC";

$stmt = $db->prepare($sql);
$params = [':groomer_id' => $groomerId];
if($filtro_estado != 'todos') $params[':estado'] = $filtro_estado;
if($filtro_fecha) $params[':fecha'] = $filtro_fecha;
$stmt->execute($params);
$citas = $stmt->fetchAll();

$mensaje = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Obtener iniciales para avatar
$iniciales = strtoupper(substr($groomer['nombre'], 0, 1) . substr($groomer['apellido'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Citas - Groomer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .status-completada { background-color: #d4edda; }
        .status-cancelada { background-color: #f8d7da; }
        .status-en-progreso { background-color: #fff3cd; }
        .user-avatar { width: 40px; height: 40px; background: #3498db; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin: 0 auto 10px; }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4">
                <div class="user-avatar"><?php echo $iniciales; ?></div>
                <h6 class="text-white mb-0"><?php echo htmlspecialchars($groomer['nombre'] . ' ' . $groomer['apellido']); ?></h6>
                <small class="text-muted">Groomer</small>
            </div>
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link active" href="citas.php"><i class="fas fa-calendar-alt"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="ficha_grooming.php"><i class="fas fa-clipboard-list"></i> Fichas</a></li>
                <li><a class="nav-link" href="checklist.php"><i class="fas fa-check-square"></i> Checklist</a></li>
                <li><a class="nav-link" href="insumos_recibidos.php"><i class="fas fa-boxes"></i> Insumos</a></li>
                <li><a class="nav-link" href="galeria_fotos.php"><i class="fas fa-camera"></i> Galería</a></li>
                <li><hr class="bg-secondary my-2 mx-3"></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-calendar-alt"></i> Mis Citas</h2>
                <span class="text-muted"><i class="fas fa-calendar"></i> <?php echo date('d/m/Y'); ?></span>
            </div>
            
            <?php if($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filtros -->
            <div class="card mb-4">
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
                            <div class="btn-group">
                                <button class="btn btn-outline-primary" onclick="cambiarVista('semana')"><i class="fas fa-calendar-week"></i> Semana</button>
                                <button class="btn btn-outline-primary" onclick="cambiarVista('mes')"><i class="fas fa-calendar-alt"></i> Mes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Resumen rápido -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h4><?php echo count(array_filter($citas, fn($c) => $c['estado'] == 'agendada' || $c['estado'] == 'confirmada')); ?></h4>
                            <small>Pendientes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h4><?php echo count(array_filter($citas, fn($c) => $c['estado'] == 'en_progreso')); ?></h4>
                            <small>En Progreso</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h4><?php echo count(array_filter($citas, fn($c) => $c['estado'] == 'completada')); ?></h4>
                            <small>Completadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h4><?php echo count(array_filter($citas, fn($c) => $c['estado'] == 'cancelada')); ?></h4>
                            <small>Canceladas</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de citas -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-list"></i> Lista de Citas
                </div>
                <div class="card-body">
                    <?php if(empty($citas)): ?>
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-calendar-times fa-2x mb-2"></i>
                            <p class="mb-0">No hay citas para mostrar con los filtros seleccionados</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Cliente</th>
                                        <th>Mascota</th>
                                        <th>Servicio</th>
                                        <th>Ficha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($citas as $cita): 
                                        $badgeClass = 'secondary';
                                        $estadoTexto = ucfirst($cita['estado']);
                                        $rowClass = '';
                                        if($cita['estado'] == 'agendada') {
                                            $badgeClass = 'warning';
                                        } elseif($cita['estado'] == 'confirmada') {
                                            $badgeClass = 'info';
                                        } elseif($cita['estado'] == 'en_progreso') {
                                            $badgeClass = 'primary';
                                            $estadoTexto = 'En Progreso';
                                            $rowClass = 'status-en-progreso';
                                        } elseif($cita['estado'] == 'completada') {
                                            $badgeClass = 'success';
                                            $rowClass = 'status-completada';
                                        } elseif($cita['estado'] == 'cancelada') {
                                            $badgeClass = 'danger';
                                            $rowClass = 'status-cancelada';
                                        }
                                    ?>
                                    <tr class="<?php echo $rowClass; ?>">
                                        <td>
                                            <strong><?php echo date('d/m/Y', strtotime($cita['fecha_hora_inicio'])); ?></strong>
                                            <br><small class="text-muted"><?php echo date('H:i', strtotime($cita['fecha_hora_inicio'])); ?> - <?php echo date('H:i', strtotime($cita['fecha_hora_fin'])); ?></small>
                                         </div>
                                        <td>
                                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($cita['cliente_nombre'] . ' ' . $cita['cliente_apellido']); ?>
                                            <br><small class="text-muted"><i class="fas fa-phone"></i> <?php echo $cita['telefono'] ?? 'Sin teléfono'; ?></small>
                                         </div>
                                        <td>
                                            <strong><i class="fas fa-paw"></i> <?php echo htmlspecialchars($cita['mascota_nombre']); ?></strong>
                                            <br><small class="text-muted"><?php echo ucfirst($cita['especie']); ?> <?php echo $cita['raza'] ? '- ' . $cita['raza'] : ''; ?></small>
                                            <?php if(isset($cita['tamano'])): ?>
                                                <br><span class="badge bg-secondary"><?php echo ucfirst($cita['tamano']); ?></span>
                                            <?php endif; ?>
                                         </div>
                                        <td>
                                            <?php echo htmlspecialchars($cita['servicio_nombre']); ?>
                                            <br><small class="text-muted"><?php echo $cita['duracion_base_minutos']; ?> min</small>
                                         </div>
                                        <td>
                                            <?php if($cita['ficha_id']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Creada</span>
                                                <?php if($cita['estado_final']): ?>
                                                    <br><small class="text-muted">Finalizada</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-clock"></i> Pendiente</span>
                                            <?php endif; ?>
                                         </div>
                                        <td>
                                            <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $estadoTexto; ?></span>
                                         </div>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="ficha_grooming.php?cita_id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-primary" title="Ver ficha">
                                                    <i class="fas fa-clipboard-list"></i>
                                                </a>
                                                <?php if($cita['estado'] == 'agendada' || $cita['estado'] == 'confirmada'): ?>
                                                <a href="citas.php?cambiar_estado=<?php echo $cita['id']; ?>&estado=en_progreso" class="btn btn-sm btn-success" onclick="return confirm('¿Iniciar esta cita?')" title="Iniciar cita">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if($cita['estado'] == 'en_progreso' && $cita['ficha_id']): ?>
                                                <a href="cierre_servicio.php?cita_id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-success" title="Finalizar servicio">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if($cita['estado'] != 'completada' && $cita['estado'] != 'cancelada'): ?>
                                                <button class="btn btn-sm btn-warning" onclick="reprogramarCita(<?php echo $cita['id']; ?>, '<?php echo date('Y-m-d', strtotime($cita['fecha_hora_inicio'])); ?>', '<?php echo date('H:i', strtotime($cita['fecha_hora_inicio'])); ?>')" title="Reprogramar">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                         </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 text-muted">
                            <small><i class="fas fa-info-circle"></i> Total: <?php echo count($citas); ?> citas encontradas</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Reprogramar Cita -->
<div class="modal fade" id="reprogramarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-calendar-alt"></i> Reprogramar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="cita_id" id="reprogramar_cita_id">
                    <div class="mb-3">
                        <label class="form-label">Nueva Fecha</label>
                        <input type="date" name="nueva_fecha" id="nueva_fecha" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nueva Hora</label>
                        <input type="time" name="nueva_hora" id="nueva_hora" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo de reprogramación</label>
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
function reprogramarCita(id, fecha, hora) {
    document.getElementById('reprogramar_cita_id').value = id;
    document.getElementById('nueva_fecha').value = fecha;
    document.getElementById('nueva_hora').value = hora;
    new bootstrap.Modal(document.getElementById('reprogramarModal')).show();
}

function cambiarVista(vista) {
    const fecha = document.querySelector('input[name="fecha"]').value;
    window.location.href = `calendario.php?vista=${vista}&fecha=${fecha}`;
}
</script>
</body>
</html>
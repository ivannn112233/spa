<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_GROOMER);

$db = Database::getInstance()->getConnection();

// Obtener ID del groomer
$stmt = $db->prepare("SELECT id FROM groomers WHERE usuario_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$groomerId = $stmt->fetch()['id'];

// Cambiar estado de cita
if(isset($_GET['cambiar_estado'])){
    $cita_id = $_GET['cambiar_estado'];
    $estado = $_GET['estado'];
    
    $stmt = $db->prepare("UPDATE citas SET estado = :estado WHERE id = :id AND groomer_id = :groomer_id");
    $stmt->execute([':estado' => $estado, ':id' => $cita_id, ':groomer_id' => $groomerId]);
    
    header("Location: citas.php?msg=Estado actualizado");
    exit;
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_fecha = $_GET['fecha'] ?? date('Y-m-d');

$sql = "
    SELECT c.*, m.nombre as mascota_nombre, m.especie, m.raza,
           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido, cl.telefono,
           s.nombre as servicio_nombre, s.duracion_base_minutos
    FROM citas c
    JOIN mascotas m ON m.id = c.mascota_id
    JOIN clientes cl ON cl.id = c.cliente_id
    JOIN servicios s ON s.id = c.servicio_id
    WHERE c.groomer_id = :groomer_id
";

if($filtro_estado != 'todos'){
    $sql .= " AND c.estado = :estado";
}
if($filtro_fecha){
    $sql .= " AND DATE(c.fecha_hora_inicio) = :fecha";
}

$sql .= " ORDER BY c.fecha_hora_inicio DESC";

$stmt = $db->prepare($sql);
$params = [':groomer_id' => $groomerId];
if($filtro_estado != 'todos') $params[':estado'] = $filtro_estado;
if($filtro_fecha) $params[':fecha'] = $filtro_fecha;
$stmt->execute($params);
$citas = $stmt->fetchAll();

$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Citas - Groomer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-md-block sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Groomer</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link active" href="citas.php"><i class="fas fa-calendar-alt"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="ficha_grooming.php"><i class="fas fa-clipboard-list"></i> Fichas</a></li>
                <li><a class="nav-link" href="checklist.php"><i class="fas fa-check-square"></i> Checklist</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-calendar-alt"></i> Mis Citas</h2>
            </div>
            
            <?php if($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label>Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo $filtro_fecha; ?>">
                        </div>
                        <div class="col-md-4">
                            <label>Estado</label>
                            <select name="estado" class="form-control">
                                <option value="todos" <?php echo $filtro_estado=='todos'?'selected':''; ?>>Todos</option>
                                <option value="agendada" <?php echo $filtro_estado=='agendada'?'selected':''; ?>>Agendada</option>
                                <option value="confirmada" <?php echo $filtro_estado=='confirmada'?'selected':''; ?>>Confirmada</option>
                                <option value="en_progreso" <?php echo $filtro_estado=='en_progreso'?'selected':''; ?>>En Progreso</option>
                                <option value="completada" <?php echo $filtro_estado=='completada'?'selected':''; ?>>Completada</option>
                                <option value="cancelada" <?php echo $filtro_estado=='cancelada'?'selected':''; ?>>Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="citas.php" class="btn btn-secondary ms-2">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de citas -->
            <div class="card">
                <div class="card-body">
                    <?php if(empty($citas)): ?>
                        <div class="alert alert-info">No hay citas para mostrar</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Cliente</th>
                                        <th>Mascota</th>
                                        <th>Servicio</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($citas as $cita): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($cita['fecha_hora_inicio'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($cita['cliente_nombre'] . ' ' . $cita['cliente_apellido']); ?>
                                            <br><small class="text-muted">📞 <?php echo $cita['telefono'] ?? 'Sin teléfono'; ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($cita['mascota_nombre']); ?></strong>
                                            <br><small><?php echo $cita['especie']; ?> <?php echo $cita['raza']; ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($cita['servicio_nombre']); ?></td>
                                        <td>
                                            <?php
                                            $badge = 'secondary';
                                            if($cita['estado'] == 'agendada') $badge = 'warning';
                                            elseif($cita['estado'] == 'confirmada') $badge = 'info';
                                            elseif($cita['estado'] == 'en_progreso') $badge = 'primary';
                                            elseif($cita['estado'] == 'completada') $badge = 'success';
                                            elseif($cita['estado'] == 'cancelada') $badge = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($cita['estado']); ?></span>
                                        </td>
                                        <td>
                                            <a href="ficha_grooming.php?cita_id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-clipboard-list"></i> Ficha
                                            </a>
                                            <?php if($cita['estado'] == 'agendada' || $cita['estado'] == 'confirmada'): ?>
                                            <button class="btn btn-sm btn-success" onclick="cambiarEstado(<?php echo $cita['id']; ?>, 'en_progreso')">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if($cita['estado'] == 'en_progreso'): ?>
                                            <button class="btn btn-sm btn-success" onclick="cambiarEstado(<?php echo $cita['id']; ?>, 'completada')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
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

<script>
function cambiarEstado(citaId, estado) {
    if(confirm('¿Confirmas esta acción?')){
        window.location.href = 'citas.php?cambiar_estado=' + citaId + '&estado=' + estado;
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
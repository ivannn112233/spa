<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$mensaje = '';

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
        s.nombre as servicio, gr.nombre as groomer
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

// Obtener groomers y servicios para filtros
$groomers = $db->query("SELECT id, nombre, apellido FROM groomers WHERE estado_activo=1")->fetchAll();
$servicios = $db->query("SELECT id, nombre FROM servicios WHERE activo=1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Citas</title>
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
                <li><a class="nav-link active" href="citas.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a class="nav-link" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
                <li><a class="nav-link" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-calendar-alt"></i> Gestión de Citas</h2>
            </div>
            
            <?php echo $mensaje; ?>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3"><label>Fecha</label><input type="date" name="fecha" class="form-control" value="<?php echo $filtro_fecha; ?>"></div>
                        <div class="col-md-3"><label>Estado</label>
                            <select name="estado" class="form-control">
                                <option value="todos" <?php echo $filtro_estado=='todos'?'selected':''; ?>>Todos</option>
                                <option value="agendada" <?php echo $filtro_estado=='agendada'?'selected':''; ?>>Agendada</option>
                                <option value="confirmada" <?php echo $filtro_estado=='confirmada'?'selected':''; ?>>Confirmada</option>
                                <option value="en_progreso" <?php echo $filtro_estado=='en_progreso'?'selected':''; ?>>En Progreso</option>
                                <option value="completada" <?php echo $filtro_estado=='completada'?'selected':''; ?>>Completada</option>
                                <option value="cancelada" <?php echo $filtro_estado=='cancelada'?'selected':''; ?>>Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="citas.php" class="btn btn-secondary ms-2">Limpiar</a></div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <?php if(empty($citas)): ?>
                        <p class="text-muted">No hay citas para mostrar</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr><th>Fecha/Hora</th><th>Cliente</th><th>Mascota</th><th>Servicio</th><th>Groomer</th><th>Estado</th><th>Acciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($citas as $c): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($c['fecha_hora_inicio'])); ?></td>
                                        <td><?php echo htmlspecialchars($c['cliente_nombre'] . ' ' . $c['cliente_apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($c['mascota']); ?></td>
                                        <td><?php echo htmlspecialchars($c['servicio']); ?></td>
                                        <td><?php echo htmlspecialchars($c['groomer']); ?></td>
                                        <td><span class="badge bg-<?php echo $c['estado']=='completada'?'success':($c['estado']=='cancelada'?'danger':'warning'); ?>"><?php echo $c['estado']; ?></span></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-info" onclick="verCita(<?php echo $c['id']; ?>)"><i class="fas fa-eye"></i></button>
                                                <?php if($c['estado'] == 'agendada'): ?>
                                                <a href="citas.php?cambiar_estado=confirmada&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
                                                <?php endif; ?>
                                                <?php if($c['estado'] != 'cancelada' && $c['estado'] != 'completada'): ?>
                                                <a href="citas.php?cancelar=<?php echo $c['id']; ?>&motivo=Cancelado por recepción" class="btn btn-sm btn-danger" onclick="return confirm('¿Cancelar esta cita?')"><i class="fas fa-times"></i></a>
                                                <?php endif; ?>
                                            </div>
                                         </div>
                                        </td>
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
function verCita(id){ alert('Ver detalles de cita ' + id); }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
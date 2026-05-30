<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$mensaje = '';

// Crear tablas si no existen
$db->exec("CREATE TABLE IF NOT EXISTS disponibilidad_groomer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groomer_id INT UNSIGNED NOT NULL,
    dia_semana TINYINT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    intervalo_descanso JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (groomer_id) REFERENCES groomers(id) ON DELETE CASCADE
)");

$db->exec("CREATE TABLE IF NOT EXISTS bloqueos_calendario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groomer_id INT UNSIGNED NULL,
    tipo ENUM('feriado','vacaciones','mantenimiento','ausencia') NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    motivo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Guardar disponibilidad
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_disponibilidad'])){
    $groomer_id = $_POST['groomer_id'];
    $dia_semana = $_POST['dia_semana'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $descanso_inicio = $_POST['descanso_inicio'] ?? null;
    $descanso_fin = $_POST['descanso_fin'] ?? null;
    
    $intervalo_descanso = null;
    if($descanso_inicio && $descanso_fin){
        $intervalo_descanso = json_encode(['inicio' => $descanso_inicio, 'fin' => $descanso_fin]);
    }
    
    // Eliminar disponibilidad existente
    $stmt = $db->prepare("DELETE FROM disponibilidad_groomer WHERE groomer_id = ? AND dia_semana = ?");
    $stmt->execute([$groomer_id, $dia_semana]);
    
    // Insertar nueva
    $stmt = $db->prepare("INSERT INTO disponibilidad_groomer (groomer_id, dia_semana, hora_inicio, hora_fin, intervalo_descanso) VALUES (?,?,?,?,?)");
    $stmt->execute([$groomer_id, $dia_semana, $hora_inicio, $hora_fin, $intervalo_descanso]);
    $mensaje = '<div class="alert alert-success">Disponibilidad guardada</div>';
}

// Guardar bloqueo
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_bloqueo'])){
    $groomer_id = $_POST['groomer_id'] ?: null;
    $tipo = $_POST['tipo'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $motivo = $_POST['motivo'];
    
    $stmt = $db->prepare("INSERT INTO bloqueos_calendario (groomer_id, tipo, fecha_inicio, fecha_fin, motivo) VALUES (?,?,?,?,?)");
    $stmt->execute([$groomer_id, $tipo, $fecha_inicio, $fecha_fin, $motivo]);
    $mensaje = '<div class="alert alert-success">Bloqueo registrado</div>';
}

// Eliminar bloqueo
if(isset($_GET['delete_bloqueo'])){
    $stmt = $db->prepare("DELETE FROM bloqueos_calendario WHERE id = ?");
    $stmt->execute([$_GET['delete_bloqueo']]);
    $mensaje = '<div class="alert alert-success">Bloqueo eliminado</div>';
}

$groomers = $db->query("SELECT id, nombre, apellido FROM groomers WHERE estado_activo = 1")->fetchAll();

// Obtener disponibilidad actual
$disponibilidad = [];
foreach($groomers as $g){
    $stmt = $db->prepare("SELECT * FROM disponibilidad_groomer WHERE groomer_id = ?");
    $stmt->execute([$g['id']]);
    $disp = $stmt->fetchAll();
    foreach($disp as $d){
        $disponibilidad[$g['id']][$d['dia_semana']] = $d;
    }
}

// Obtener bloqueos
$bloqueos = $db->query("SELECT b.*, g.nombre as groomer_nombre, g.apellido as groomer_apellido FROM bloqueos_calendario b LEFT JOIN groomers g ON b.groomer_id = g.id ORDER BY b.fecha_inicio DESC")->fetchAll();

$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Disponibilidad - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .horario-card { background: #f8f9fa; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
        .horario-card h6 { margin-bottom: 15px; color: #2c3e50; border-bottom: 2px solid #3498db; display: inline-block; }
        .horario-tabla td { padding: 8px; }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Admin</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a class="nav-link" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
                <li><a class="nav-link active" href="disponibilidad.php"><i class="fas fa-clock"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                <li><a class="nav-link" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-clock"></i> Disponibilidad y Horarios</h2>
            </div>
            
            <?php echo $mensaje; ?>
            
            <div class="row">
                <!-- Configurar Horarios -->
                <div class="col-md-5">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Configurar Horario por Groomer</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="guardar_disponibilidad" value="1">
                                <div class="mb-3">
                                    <label>Groomer</label>
                                    <select name="groomer_id" class="form-control" required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach($groomers as $g): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo $g['nombre'] . ' ' . $g['apellido']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Día</label>
                                    <select name="dia_semana" class="form-control" required>
                                        <?php foreach($dias as $i => $dia): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $dia; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label>Hora Inicio</label><input type="time" name="hora_inicio" class="form-control" value="09:00" required></div>
                                    <div class="col-md-6 mb-3"><label>Hora Fin</label><input type="time" name="hora_fin" class="form-control" value="18:00" required></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label>Descanso Inicio</label><input type="time" name="descanso_inicio" class="form-control" placeholder="Opcional"></div>
                                    <div class="col-md-6 mb-3"><label>Descanso Fin</label><input type="time" name="descanso_fin" class="form-control" placeholder="Opcional"></div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Guardar Horario</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Bloquear fechas -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0">Bloquear Fechas</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="guardar_bloqueo" value="1">
                                <div class="mb-3">
                                    <label>Groomer (opcional)</label>
                                    <select name="groomer_id" class="form-control">
                                        <option value="">Todos los groomers</option>
                                        <?php foreach($groomers as $g): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo $g['nombre'] . ' ' . $g['apellido']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Tipo de Bloqueo</label>
                                    <select name="tipo" class="form-control" required>
                                        <option value="feriado">Feriado</option>
                                        <option value="vacaciones">Vacaciones</option>
                                        <option value="mantenimiento">Mantenimiento</option>
                                        <option value="ausencia">Ausencia</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label>Fecha Inicio</label><input type="date" name="fecha_inicio" class="form-control" required></div>
                                    <div class="col-md-6 mb-3"><label>Fecha Fin</label><input type="date" name="fecha_fin" class="form-control" required></div>
                                </div>
                                <div class="mb-3"><label>Motivo</label><textarea name="motivo" class="form-control" rows="2"></textarea></div>
                                <button type="submit" class="btn btn-warning w-100">Registrar Bloqueo</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Listado de horarios configurados -->
                <div class="col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">Horarios Configurados</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach($groomers as $g): ?>
                            <div class="horario-card">
                                <h6><i class="fas fa-user-md"></i> <?php echo $g['nombre'] . ' ' . $g['apellido']; ?></h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered horario-tabla">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Día</th>
                                                <th>Horario</th>
                                                <th>Descanso</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php for($i = 0; $i < 7; $i++): ?>
                                            <tr>
                                                <td><strong><?php echo $dias[$i]; ?></strong></td>
                                                <?php if(isset($disponibilidad[$g['id']][$i])): 
                                                    $d = $disponibilidad[$g['id']][$i];
                                                    $descanso = '';
                                                    if($d['intervalo_descanso']){
                                                        $desc = json_decode($d['intervalo_descanso'], true);
                                                        $descanso = substr($desc['inicio'], 0, 5) . ' - ' . substr($desc['fin'], 0, 5);
                                                    } else {
                                                        $descanso = 'Sin descanso';
                                                    }
                                                ?>
                                                    <td><?php echo substr($d['hora_inicio'], 0, 5); ?> - <?php echo substr($d['hora_fin'], 0, 5); ?></td>
                                                    <td><?php echo $descanso; ?></td>
                                                <?php else: ?>
                                                    <td colspan="2" class="text-muted text-center">No configurado</td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Bloqueos activos -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Bloqueos Activos</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($bloqueos)): ?>
                                <p class="text-muted">No hay bloqueos registrados</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-dark">
                                            <tr><th>Fechas</th><th>Groomer</th><th>Tipo</th><th>Motivo</th><th></th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($bloqueos as $b): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($b['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($b['fecha_fin'])); ?></td>
                                                <td><?php echo $b['groomer_nombre'] ? $b['groomer_nombre'] . ' ' . $b['groomer_apellido'] : 'Todos'; ?></td>
                                                <td><span class="badge bg-warning"><?php echo $b['tipo']; ?></span></td>
                                                <td><?php echo $b['motivo'] ?: '-'; ?></td>
                                                <td><a href="disponibilidad.php?delete_bloqueo=<?php echo $b['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este bloqueo?')"><i class="fas fa-trash"></i></a></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// Obtener datos necesarios
$clientes = $db->query("SELECT id, nombre, apellido, telefono FROM clientes ORDER BY nombre")->fetchAll();
$mascotas = $db->query("SELECT m.id, m.nombre, c.nombre as dueno FROM mascotas m JOIN mascota_dueno md ON m.id=md.mascota_id JOIN clientes c ON c.id=md.cliente_id")->fetchAll();
$servicios = $db->query("SELECT id, nombre, precio_base, duracion_base_minutos FROM servicios WHERE activo=1")->fetchAll();
$groomers = $db->query("SELECT id, nombre, apellido FROM groomers WHERE estado_activo=1")->fetchAll();

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
        $fecha_hora = $fecha . ' ' . $hora . ':00';
        $stmt = $db->prepare("SELECT duracion_base_minutos FROM servicios WHERE id = ?");
        $stmt->execute([$servicio_id]);
        $duracion = $stmt->fetchColumn();
        $fecha_fin = date('Y-m-d H:i:s', strtotime($fecha_hora . ' + ' . $duracion . ' minutes'));
        
        $stmt = $db->prepare("
            INSERT INTO citas (mascota_id, groomer_id, servicio_id, cliente_id, fecha_hora_inicio, fecha_hora_fin, estado, notas, creado_por)
            VALUES (?, ?, ?, ?, ?, ?, 'agendada', ?, ?)
        ");
        $creado_por = $_SESSION['user_id'];
        
        if($stmt->execute([$mascota_id, $groomer_id, $servicio_id, $cliente_id, $fecha_hora, $fecha_fin, $notas, $creado_por])){
            $success = "Cita agendada correctamente";
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
    <title>Agendar Cita</title>
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
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a class="nav-link active" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
                <li><a class="nav-link" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom"><h2><i class="fas fa-plus-circle"></i> Agendar Nueva Cita</h2></div>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?> <a href="citas.php">Ver citas</a></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Cliente</label>
                                <select name="cliente_id" class="form-control" id="cliente_id">
                                    <option value="">Seleccionar cliente</option>
                                    <?php foreach($clientes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre'] . ' ' . $c['apellido'] . ' - ' . $c['telefono']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Mascota *</label>
                                <select name="mascota_id" class="form-control" required>
                                    <option value="">Seleccionar mascota</option>
                                    <?php foreach($mascotas as $m): ?>
                                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nombre'] . ' (' . $m['dueno'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Servicio *</label>
                                <select name="servicio_id" class="form-control" required>
                                    <option value="">Seleccionar servicio</option>
                                    <?php foreach($servicios as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['nombre']) . ' - Bs. ' . $s['precio_base'] . ' (' . $s['duracion_base_minutos'] . ' min)'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Groomer *</label>
                                <select name="groomer_id" class="form-control" required>
                                    <option value="">Seleccionar groomer</option>
                                    <?php foreach($groomers as $g): ?>
                                    <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['nombre'] . ' ' . $g['apellido']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Fecha *</label>
                                <input type="date" name="fecha" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Hora *</label>
                                <input type="time" name="hora" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Notas</label>
                                <textarea name="notas" class="form-control" rows="2" placeholder="Información adicional..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Agendar Cita</button>
                        <a href="citas.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
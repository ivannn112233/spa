<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente_id = $stmt->fetch()['id'];

// Obtener mascotas del cliente
$stmt = $db->prepare("
    SELECT m.* FROM mascotas m
    JOIN mascota_dueno md ON m.id = md.mascota_id
    WHERE md.cliente_id = ?
");
$stmt->execute([$cliente_id]);
$mascotas = $stmt->fetchAll();

// Obtener servicios
$servicios = $db->query("SELECT * FROM servicios WHERE activo=1")->fetchAll();

// Obtener groomers
$groomers = $db->query("SELECT id, nombre, apellido FROM groomers WHERE estado_activo=1")->fetchAll();

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $mascota_id = $_POST['mascota_id'];
    $servicio_id = $_POST['servicio_id'];
    $groomer_id = $_POST['groomer_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    
    $fecha_hora = $fecha . ' ' . $hora . ':00';
    
    // Obtener duración del servicio
    $stmt = $db->prepare("SELECT duracion_base_minutos FROM servicios WHERE id=?");
    $stmt->execute([$servicio_id]);
    $duracion = $stmt->fetch()['duracion_base_minutos'];
    
    $fecha_fin = date('Y-m-d H:i:s', strtotime($fecha_hora . ' + ' . $duracion . ' minutes'));
    
    $stmt = $db->prepare("
        INSERT INTO citas (mascota_id, groomer_id, servicio_id, cliente_id, fecha_hora_inicio, fecha_hora_fin, estado, creado_por)
        VALUES (?,?,?,?,?,?,'agendada',?)
    ");
    if($stmt->execute([$mascota_id, $groomer_id, $servicio_id, $cliente_id, $fecha_hora, $fecha_fin, $_SESSION['user_id']])){
        $success = "Cita agendada correctamente";
    } else {
        $error = "Error al agendar cita";
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
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white"><h4>Agendar Cita</h4></div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?> <a href="citas.php">Ver mis citas</a></div>
                    <?php else: ?>
                        <?php if(empty($mascotas)): ?>
                            <div class="alert alert-warning">Primero debes <a href="mascota_agregar.php">registrar una mascota</a></div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3"><label>Mascota</label>
                                    <select name="mascota_id" class="form-control" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach($mascotas as $m): ?>
                                        <option value="<?php echo $m['id']; ?>"><?php echo $m['nombre']; ?> (<?php echo $m['especie']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3"><label>Servicio</label>
                                    <select name="servicio_id" class="form-control" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach($servicios as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo $s['nombre']; ?> - Bs. <?php echo $s['precio_base']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3"><label>Groomer</label>
                                    <select name="groomer_id" class="form-control" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach($groomers as $g): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo $g['nombre'] . ' ' . $g['apellido']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3"><label>Fecha</label><input type="date" name="fecha" class="form-control" required min="<?php echo date('Y-m-d'); ?>"></div>
                                <div class="mb-3"><label>Hora</label><input type="time" name="hora" class="form-control" required></div>
                                <button type="submit" class="btn btn-primary w-100">Agendar</button>
                                <a href="citas.php" class="btn btn-secondary w-100 mt-2">Cancelar</a>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
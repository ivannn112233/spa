<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente_id = $stmt->fetch()['id'];

$cita_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Verificar que la cita pertenece al cliente
$stmt = $db->prepare("
    SELECT c.*, m.nombre as mascota, s.nombre as servicio 
    FROM citas c
    JOIN mascotas m ON c.mascota_id = m.id
    JOIN servicios s ON c.servicio_id = s.id
    WHERE c.id = ? AND c.cliente_id = ? AND c.estado IN ('agendada', 'confirmada')
");
$stmt->execute([$cita_id, $cliente_id]);
$cita = $stmt->fetch();

if(!$cita){
    header("Location: citas.php?msg=Cita no encontrada o no se puede cancelar");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $motivo = $_POST['motivo'];
    
    $stmt = $db->prepare("UPDATE citas SET estado = 'cancelada', motivo_cancelacion = ? WHERE id = ?");
    $stmt->execute([$motivo, $cita_id]);
    
    $success = "Cita cancelada correctamente";
    header("refresh:2;url=citas.php");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cancelar Cita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-times-circle"></i> Cancelar Cita</h4>
                </div>
                <div class="card-body">
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?> Redirigiendo...</div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>¿Estás seguro de cancelar esta cita?</strong>
                        </div>
                        
                        <div class="border rounded p-3 mb-3">
                            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($cita['fecha_hora_inicio'])); ?></p>
                            <p><strong>Mascota:</strong> <?php echo $cita['mascota']; ?></p>
                            <p><strong>Servicio:</strong> <?php echo $cita['servicio']; ?></p>
                        </div>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label>Motivo de cancelación</label>
                                <select name="motivo" class="form-control" required>
                                    <option value="">Seleccionar motivo</option>
                                    <option value="Cambio de planes">Cambio de planes</option>
                                    <option value="Mascota enferma">Mascota enferma</option>
                                    <option value="Emergencia">Emergencia</option>
                                    <option value="Problemas de agenda">Problemas de agenda</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">Confirmar Cancelación</button>
                            <a href="citas.php" class="btn btn-secondary w-100 mt-2">Volver</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
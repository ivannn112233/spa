<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente_id = $stmt->fetch()['id'];

// Cancelar cita
if(isset($_GET['cancelar'])){
    $db->prepare("UPDATE citas SET estado='cancelada', motivo_cancelacion='Cancelado por cliente' WHERE id=? AND cliente_id=?")->execute([$_GET['cancelar'], $cliente_id]);
    header("Location: citas.php?msg=Cita cancelada");
    exit;
}

$stmt = $db->prepare("
    SELECT c.*, s.nombre as servicio, gr.nombre as groomer, m.nombre as mascota
    FROM citas c
    JOIN servicios s ON c.servicio_id = s.id
    JOIN groomers gr ON c.groomer_id = gr.id
    JOIN mascotas m ON c.mascota_id = m.id
    WHERE c.cliente_id = ?
    ORDER BY c.fecha_hora_inicio DESC
");
$stmt->execute([$cliente_id]);
$citas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>.sidebar { min-height: 100vh; background: #2c3e50; position: fixed; } .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; } .sidebar .nav-link i { width: 25px; } .main-content { margin-left: 16.666%; } @media (max-width: 768px) { .sidebar { position: static; } .main-content { margin-left: 0; } }</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Cliente</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a class="nav-link" href="mascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link active" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="historial.php"><i class="fas fa-history"></i> Historial</a></li>
                <li><a class="nav-link" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-calendar"></i> Mis Citas</h2>
                <a href="cita_agendar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Agendar Cita</a>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?php echo $_GET['msg']; ?></div>
            <?php endif; ?>
            
            <?php if(empty($citas)): ?>
                <div class="alert alert-info">No tienes citas registradas</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark"><tr><th>Fecha</th><th>Mascota</th><th>Servicio</th><th>Groomer</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach($citas as $c): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($c['fecha_hora_inicio'])); ?></td>
                                <td><?php echo $c['mascota']; ?></td>
                                <td><?php echo $c['servicio']; ?></td>
                                <td><?php echo $c['groomer']; ?></td>
                                <td><span class="badge bg-<?php echo $c['estado']=='cancelada'?'danger':($c['estado']=='completada'?'success':'warning'); ?>"><?php echo $c['estado']; ?></span></td>
                                <td><?php if($c['estado']=='agendada' || $c['estado']=='confirmada'): ?>
                                    <a href="citas.php?cancelar=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Cancelar cita?')">Cancelar</a>
                                <?php endif; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
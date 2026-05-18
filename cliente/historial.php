<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente_id = $stmt->fetch()['id'];

$stmt = $db->prepare("
    SELECT c.*, s.nombre as servicio, m.nombre as mascota, 
           CASE WHEN f.id IS NOT NULL THEN 'Completada' ELSE 'Sin ficha' END as ficha_estado
    FROM citas c
    JOIN servicios s ON c.servicio_id = s.id
    JOIN mascotas m ON c.mascota_id = m.id
    LEFT JOIN fichas_grooming f ON f.cita_id = c.id
    WHERE c.cliente_id = ? AND c.estado IN ('completada', 'cancelada')
    ORDER BY c.fecha_hora_inicio DESC
");
$stmt->execute([$cliente_id]);
$historial = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial</title>
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
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
                <li><a class="nav-link active" href="historial.php"><i class="fas fa-history"></i> Historial</a></li>
                <li><a class="nav-link" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-history"></i> Historial de Citas</h2>
            </div>
            
            <?php if(empty($historial)): ?>
                <div class="alert alert-info">No hay historial de citas</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr><th>Fecha</th><th>Mascota</th><th>Servicio</th><th>Estado</th><th>Ficha</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($historial as $h): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($h['fecha_hora_inicio'])); ?></td>
                                <td><?php echo $h['mascota']; ?></td>
                                <td><?php echo $h['servicio']; ?></td>
                                <td><span class="badge bg-<?php echo $h['estado']=='completada'?'success':'danger'; ?>"><?php echo $h['estado']; ?></span></td>
                                <td><?php echo $h['ficha_estado']; ?></td>
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
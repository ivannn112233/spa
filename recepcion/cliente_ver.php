<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT c.*, u.email, u.estado FROM clientes c JOIN usuarios u ON c.usuario_id = u.id WHERE c.id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();
if(!$cliente) die("Cliente no encontrado");

// Mascotas del cliente
$stmt = $db->prepare("
    SELECT m.* FROM mascotas m
    JOIN mascota_dueno md ON m.id = md.mascota_id
    WHERE md.cliente_id = ?
");
$stmt->execute([$id]);
$mascotas = $stmt->fetchAll();

// Citas del cliente
$stmt = $db->prepare("
    SELECT c.*, s.nombre as servicio, gr.nombre as groomer
    FROM citas c
    JOIN servicios s ON c.servicio_id = s.id
    JOIN groomers gr ON c.groomer_id = gr.id
    WHERE c.cliente_id = ?
    ORDER BY c.fecha_hora_inicio DESC
    LIMIT 10
");
$stmt->execute([$id]);
$citas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>.sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; } .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; } .main-content { margin-left: 250px; } @media (max-width: 768px) { .sidebar { position: static; } .main-content { margin-left: 0; } }</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Recepción</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a class="nav-link" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
                <li><a class="nav-link active" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-user"></i> <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></h2>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">Datos Personales</div>
                        <div class="card-body">
                            <p><strong>Email:</strong> <?php echo $cliente['email']; ?></p>
                            <p><strong>Teléfono:</strong> <?php echo $cliente['telefono'] ?: '-'; ?></p>
                            <p><strong>CI:</strong> <?php echo $cliente['ci'] ?: '-'; ?></p>
                            <p><strong>Dirección:</strong> <?php echo $cliente['direccion'] ?: '-'; ?></p>
                            <p><strong>Notificaciones:</strong> <?php echo $cliente['canal_notificacion']; ?></p>
                            <p><strong>Estado:</strong> <span class="badge bg-<?php echo $cliente['estado']=='activo'?'success':'danger'; ?>"><?php echo $cliente['estado']; ?></span></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">Mascotas</div>
                        <div class="card-body">
                            <?php if(empty($mascotas)): ?>
                                <p class="text-muted">No tiene mascotas registradas</p>
                            <?php else: ?>
                                <?php foreach($mascotas as $m): ?>
                                <div class="border-bottom mb-2 pb-2">
                                    <strong><?php echo htmlspecialchars($m['nombre']); ?></strong>
                                    <span class="badge bg-info"><?php echo $m['especie']; ?></span>
                                    <p class="small mb-0">Raza: <?php echo $m['raza'] ?: '-'; ?> | Peso: <?php echo $m['peso_kg'] ?: '-'; ?> kg</p>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-warning">Historial de Citas</div>
                <div class="card-body">
                    <?php if(empty($citas)): ?>
                        <p class="text-muted">No hay citas registradas</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Fecha</th><th>Servicio</th><th>Groomer</th><th>Estado</th></tr></thead>
                                <tbody>
                                    <?php foreach($citas as $c): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($c['fecha_hora_inicio'])); ?></td>
                                        <td><?php echo $c['servicio']; ?></td>
                                        <td><?php echo $c['groomer']; ?></td>
                                        <td><span class="badge bg-<?php echo $c['estado']=='completada'?'success':($c['estado']=='cancelada'?'danger':'warning'); ?>"><?php echo $c['estado']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <a href="cita_agendar.php" class="btn btn-primary mt-2">Agendar nueva cita</a>
                    <a href="clientes.php" class="btn btn-secondary mt-2">Volver</a>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
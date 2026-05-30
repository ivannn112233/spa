<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_GROOMER);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id FROM groomers WHERE usuario_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$groomer = $stmt->fetch();
$groomerId = $groomer['id'];

$cita_id = $_GET['cita_id'] ?? 0;
$mensaje = '';
$error = '';

// Verificar cita
$stmt = $db->prepare("
    SELECT c.*, f.id as ficha_id, f.inventario_consumido
    FROM citas c
    LEFT JOIN fichas_grooming f ON f.cita_id = c.id
    WHERE c.id = :cita_id AND c.groomer_id = :groomer_id
");
$stmt->execute([':cita_id' => $cita_id, ':groomer_id' => $groomerId]);
$cita = $stmt->fetch();

if(!$cita){
    header("Location: citas.php?msg=Cita no encontrada");
    exit;
}

if(!$cita['ficha_id']){
    header("Location: ficha_grooming.php?cita_id=$cita_id&msg=Primero debes crear la ficha de grooming");
    exit;
}

// Verificar checklist completo
$stmt = $db->prepare("
    SELECT COUNT(*) as total, SUM(completado) as completados 
    FROM ficha_checklist 
    WHERE ficha_id = :ficha_id
");
$stmt->execute([':ficha_id' => $cita['ficha_id']]);
$checklist = $stmt->fetch();

$checklist_completo = ($checklist['total'] > 0 && $checklist['total'] == $checklist['completados']);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(!$checklist_completo){
        $error = "Debes completar todo el checklist antes de finalizar el servicio";
    } else {
        // Descontar inventario si no se ha hecho
        if(!$cita['inventario_consumido']){
            $stmt = $db->prepare("
                SELECT s.consumo_insumos 
                FROM citas c
                JOIN servicios s ON c.servicio_id = s.id
                WHERE c.id = :cita_id
            ");
            $stmt->execute([':cita_id' => $cita_id]);
            $consumo = $stmt->fetchColumn();
            
            if($consumo){
                $insumos = json_decode($consumo, true);
                foreach($insumos as $insumo){
                    $stmt = $db->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                    $stmt->execute([$insumo['cantidad'], $insumo['producto_id']]);
                }
            }
            
            $stmt = $db->prepare("UPDATE fichas_grooming SET inventario_consumido = 1, fecha_cierre = NOW() WHERE id = :ficha_id");
            $stmt->execute([':ficha_id' => $cita['ficha_id']]);
        }
        
        // Completar cita
        $stmt = $db->prepare("UPDATE citas SET estado = 'completada' WHERE id = :cita_id");
        $stmt->execute([':cita_id' => $cita_id]);
        
        // Registrar en auditoría
        AuditLog::log($_SESSION['user_id'], ROLE_GROOMER, 'SERVICIO_COMPLETADO', "Completó servicio para cita ID: $cita_id", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        
        // Notificar al cliente (simulado)
        $stmt = $db->prepare("
            SELECT cl.email, cl.telefono, cl.canal_notificacion, cl.nombre
            FROM clientes cl
            JOIN citas c ON cl.id = c.cliente_id
            WHERE c.id = :cita_id
        ");
        $stmt->execute([':cita_id' => $cita_id]);
        $cliente = $stmt->fetch();
        
        if($cliente){
            $mensaje_notificacion = "¡Hola {$cliente['nombre']}! Tu mascota ya está lista para recoger. Te esperamos en Pet Spa.";
            if($cliente['canal_notificacion'] == 'email'){
                @mail($cliente['email'], "Mascota lista - " . SITE_NAME, $mensaje_notificacion);
            }
        }
        
        $mensaje = "¡Servicio completado exitosamente! Se ha notificado al cliente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Servicio - Groomer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; }
        .main-content { margin-left: 250px; }
        .check-status { font-size: 1.2rem; }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Groomer</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Mis Citas</a></li>
                <li><a class="nav-link active" href="ficha_grooming.php"><i class="fas fa-clipboard-list"></i> Fichas</a></li>
                <li><a class="nav-link" href="checklist.php"><i class="fas fa-check-square"></i> Checklist</a></li>
                <li><a class="nav-link" href="insumos_recibidos.php"><i class="fas fa-boxes"></i> Insumos</a></li>
                <li><a class="nav-link" href="galeria_fotos.php"><i class="fas fa-camera"></i> Galería</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-check-circle"></i> Finalizar Servicio</h2>
            </div>
            
            <?php if($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <div class="text-center mt-3">
                    <a href="citas.php" class="btn btn-primary">Volver a Mis Citas</a>
                </div>
            <?php elseif($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <div class="text-center mt-3">
                    <a href="checklist.php?cita_id=<?php echo $cita_id; ?>" class="btn btn-warning">Completar Checklist</a>
                    <a href="ficha_grooming.php?cita_id=<?php echo $cita_id; ?>" class="btn btn-secondary">Volver a Ficha</a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5>Verificación antes de finalizar</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-clipboard-list"></i> Checklist de grooming</h6>
                                <div class="check-status">
                                    <?php if($checklist_completo): ?>
                                        <span class="text-success"><i class="fas fa-check-circle"></i> Completado (<?php echo $checklist['completados']; ?>/<?php echo $checklist['total']; ?>)</span>
                                    <?php else: ?>
                                        <span class="text-danger"><i class="fas fa-times-circle"></i> Incompleto (<?php echo $checklist['completados']; ?>/<?php echo $checklist['total']; ?>)</span>
                                        <br><small>Debes marcar todas las tareas del checklist</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-boxes"></i> Inventario</h6>
                                <div class="check-status">
                                    <?php if($cita['inventario_consumido']): ?>
                                        <span class="text-success"><i class="fas fa-check-circle"></i> Inventario registrado</span>
                                    <?php else: ?>
                                        <span class="text-warning"><i class="fas fa-clock"></i> Se registrará automáticamente al finalizar</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Confirmación:</strong> Al finalizar el servicio:
                            <ul class="mb-0 mt-2">
                                <li>Se descontará el inventario automáticamente</li>
                                <li>Se notificará al cliente que puede recoger a su mascota</li>
                                <li>La cita quedará marcada como completada</li>
                                <li>No se podrá modificar la ficha después</li>
                            </ul>
                        </div>
                        
                        <form method="POST">
                            <div class="text-center">
                                <button type="submit" class="btn btn-success btn-lg" <?php echo !$checklist_completo ? 'disabled' : ''; ?>>
                                    <i class="fas fa-check-circle"></i> Confirmar y Finalizar Servicio
                                </button>
                                <a href="ficha_grooming.php?cita_id=<?php echo $cita_id; ?>" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
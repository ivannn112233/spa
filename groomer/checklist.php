<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_GROOMER);

$db = Database::getInstance()->getConnection();

// Obtener ID del groomer
$stmt = $db->prepare("SELECT id FROM groomers WHERE usuario_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$groomerId = $stmt->fetch()['id'];

$cita_id = $_GET['cita_id'] ?? 0;
$mensaje = '';

// Verificar cita
$stmt = $db->prepare("
    SELECT c.*, f.id as ficha_id 
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

// Obtener items del checklist
$items = $db->query("SELECT * FROM checklist_items_plantilla ORDER BY orden")->fetchAll();

// Obtener estado actual del checklist
$stmt = $db->prepare("
    SELECT fc.*, cip.nombre 
    FROM ficha_checklist fc
    JOIN checklist_items_plantilla cip ON fc.item_id = cip.id
    WHERE fc.ficha_id = :ficha_id
");
$stmt->execute([':ficha_id' => $cita['ficha_id']]);
$checklist_actual = [];
while($row = $stmt->fetch()){
    $checklist_actual[$row['item_id']] = $row;
}

// Procesar checklist
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    foreach($items as $item){
        $completado = isset($_POST['item_' . $item['id']]) ? 1 : 0;
        $observacion = $_POST['obs_' . $item['id']] ?? '';
        
        $stmt = $db->prepare("SELECT id FROM ficha_checklist WHERE ficha_id = :ficha_id AND item_id = :item_id");
        $stmt->execute([':ficha_id' => $cita['ficha_id'], ':item_id' => $item['id']]);
        
        if($stmt->fetch()){
            $stmt = $db->prepare("UPDATE ficha_checklist SET completado = :completado, observacion = :observacion WHERE ficha_id = :ficha_id AND item_id = :item_id");
        } else {
            $stmt = $db->prepare("INSERT INTO ficha_checklist (ficha_id, item_id, completado, observacion) VALUES (:ficha_id, :item_id, :completado, :observacion)");
        }
        $stmt->execute([
            ':ficha_id' => $cita['ficha_id'],
            ':item_id' => $item['id'],
            ':completado' => $completado,
            ':observacion' => $observacion
        ]);
    }
    
    $mensaje = "Checklist guardado correctamente";
    
    $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(completado) as completados FROM ficha_checklist WHERE ficha_id = :ficha_id");
    $stmt->execute([':ficha_id' => $cita['ficha_id']]);
    $stats = $stmt->fetch();
    
    if($stats['total'] == $stats['completados'] && $stats['total'] > 0){
        $mensaje .= " ¡Excelente! Has completado todos los items del checklist.";
    }
    
    $stmt = $db->prepare("
        SELECT fc.*, cip.nombre 
        FROM ficha_checklist fc
        JOIN checklist_items_plantilla cip ON fc.item_id = cip.id
        WHERE fc.ficha_id = :ficha_id
    ");
    $stmt->execute([':ficha_id' => $cita['ficha_id']]);
    $checklist_actual = [];
    while($row = $stmt->fetch()){
        $checklist_actual[$row['item_id']] = $row;
    }
}

// Calcular progreso
$total_items = count($items);
$completados = count(array_filter($checklist_actual, function($item) { return $item['completado'] == 1; }));
$porcentaje = $total_items > 0 ? round(($completados / $total_items) * 100) : 0;
$checklist_completo = ($porcentaje == 100);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Checklist de Grooming - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; }
        .main-content { margin-left: 250px; }
        .checklist-item { border-left: 4px solid #3498db; margin-bottom: 15px; transition: all 0.3s; background: #fff; border-radius: 8px; padding: 12px; }
        .checklist-item.completed { border-left-color: #27ae60; background: #f0fff0; }
        .checklist-item input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .progress-bar-custom { height: 10px; border-radius: 5px; transition: width 0.5s; }
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
                <li><a class="nav-link" href="ficha_grooming.php?cita_id=<?php echo $cita_id; ?>"><i class="fas fa-clipboard-list"></i> Fichas</a></li>
                <li><a class="nav-link active" href="checklist.php?cita_id=<?php echo $cita_id; ?>"><i class="fas fa-check-square"></i> Checklist</a></li>
                <li><a class="nav-link" href="insumos_recibidos.php?cita_id=<?php echo $cita_id; ?>"><i class="fas fa-boxes"></i> Insumos</a></li>
                <li><a class="nav-link" href="galeria_fotos.php?cita_id=<?php echo $cita_id; ?>"><i class="fas fa-camera"></i> Galería</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-check-square"></i> Checklist de Grooming</h2>
            </div>
            
            <?php if($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <!-- Progreso -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5>Progreso del Checklist</h5>
                    <div class="progress mb-2">
                        <div class="progress-bar progress-bar-custom bg-success" style="width: <?php echo $porcentaje; ?>%">
                            <?php echo $porcentaje; ?>%
                        </div>
                    </div>
                    <p class="text-muted"><?php echo $completados; ?> de <?php echo $total_items; ?> tareas completadas</p>
                    
                    <?php if($checklist_completo): ?>
                        <div class="alert alert-success mt-2">
                            <i class="fas fa-check-circle"></i> ¡Checklist completo! Puedes finalizar el servicio.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-2">
                            <i class="fas fa-exclamation-triangle"></i> Debes completar todas las tareas para poder finalizar el servicio.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Checklist -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Lista de Verificación</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php foreach($items as $item): 
                            $completado = isset($checklist_actual[$item['id']]) ? $checklist_actual[$item['id']]['completado'] : 0;
                            $observacion = isset($checklist_actual[$item['id']]) ? $checklist_actual[$item['id']]['observacion'] : '';
                        ?>
                        <div class="checklist-item <?php echo $completado ? 'completed' : ''; ?>">
                            <div class="row align-items-start">
                                <div class="col-auto">
                                    <input type="checkbox" name="item_<?php echo $item['id']; ?>" id="item_<?php echo $item['id']; ?>" 
                                           value="1" <?php echo $completado ? 'checked' : ''; ?> 
                                           onchange="marcarCompletado(this, <?php echo $item['id']; ?>)">
                                </div>
                                <div class="col">
                                    <label for="item_<?php echo $item['id']; ?>" class="fw-bold" style="cursor: pointer;">
                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                    </label>
                                    <?php if($item['requiere_observacion']): ?>
                                        <div class="mt-2">
                                            <textarea name="obs_<?php echo $item['id']; ?>" class="form-control form-control-sm" 
                                                      rows="2" placeholder="Observaciones..."><?php echo htmlspecialchars($observacion); ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Checklist
                            </button>
                            <a href="ficha_grooming.php?cita_id=<?php echo $cita_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Ficha
                            </a>
                            <?php if($checklist_completo): ?>
                                <a href="cierre_servicio.php?cita_id=<?php echo $cita_id; ?>" class="btn btn-success float-end">
                                    <i class="fas fa-check-circle"></i> Finalizar Servicio
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary float-end" disabled title="Completa todas las tareas primero">
                                    <i class="fas fa-lock"></i> Finalizar Servicio (Checklist incompleto)
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function marcarCompletado(checkbox, itemId) {
    const itemDiv = checkbox.closest('.checklist-item');
    if(checkbox.checked) {
        itemDiv.classList.add('completed');
    } else {
        itemDiv.classList.remove('completed');
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
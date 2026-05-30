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
$error = '';

// Verificar que la cita pertenece al groomer
$stmt = $db->prepare("
    SELECT c.*, m.nombre as mascota_nombre, m.especie, m.raza, m.sexo, m.peso_kg, m.alergias, m.restricciones_medicas, m.tamano,
           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido, cl.telefono,
           s.nombre as servicio_nombre, s.precio_base
    FROM citas c
    JOIN mascotas m ON m.id = c.mascota_id
    JOIN clientes cl ON cl.id = c.cliente_id
    JOIN servicios s ON s.id = c.servicio_id
    WHERE c.id = :cita_id AND c.groomer_id = :groomer_id
");
$stmt->execute([':cita_id' => $cita_id, ':groomer_id' => $groomerId]);
$cita = $stmt->fetch();

if(!$cita){
    header("Location: citas.php?msg=Cita no encontrada");
    exit;
}

// Verificar si ya existe ficha
$stmt = $db->prepare("SELECT * FROM fichas_grooming WHERE cita_id = :cita_id");
$stmt->execute([':cita_id' => $cita_id]);
$ficha = $stmt->fetch();

// Procesar formulario
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $peso = $_POST['peso'] ?? null;
    $temperatura = $_POST['temperatura'] ?? null;
    $estado_inicial = $_POST['estado_inicial'] ?? '';
    $estado_final = $_POST['estado_final'] ?? '';
    $notas = $_POST['notas'] ?? '';
    $recomendaciones = $_POST['recomendaciones'] ?? '';
    $consumo_inventario = isset($_POST['consumo_inventario']) ? 1 : 0;
    
    if($ficha){
        $stmt = $db->prepare("
            UPDATE fichas_grooming 
            SET peso_al_momento = :peso, temperatura_ingreso = :temp, 
                estado_inicial = :estado_ini, estado_final = :estado_fin,
                notas_internas = :notas, recomendaciones = :recomendaciones,
                inventario_consumido = :consumo
            WHERE cita_id = :cita_id
        ");
        $stmt->execute([
            ':peso' => $peso, ':temp' => $temperatura, ':estado_ini' => $estado_inicial,
            ':estado_fin' => $estado_final, ':notas' => $notas, ':recomendaciones' => $recomendaciones,
            ':consumo' => $consumo_inventario, ':cita_id' => $cita_id
        ]);
        $mensaje = "Ficha actualizada correctamente";
    } else {
        $stmt = $db->prepare("
            INSERT INTO fichas_grooming (cita_id, peso_al_momento, temperatura_ingreso, 
                estado_inicial, estado_final, notas_internas, recomendaciones, inventario_consumido)
            VALUES (:cita_id, :peso, :temp, :estado_ini, :estado_fin, :notas, :recomendaciones, :consumo)
        ");
        $stmt->execute([
            ':cita_id' => $cita_id, ':peso' => $peso, ':temp' => $temperatura,
            ':estado_ini' => $estado_inicial, ':estado_fin' => $estado_final,
            ':notas' => $notas, ':recomendaciones' => $recomendaciones, ':consumo' => $consumo_inventario
        ]);
        $mensaje = "Ficha creada correctamente";
        
        if($cita['estado'] == 'agendada' || $cita['estado'] == 'confirmada'){
            $stmt = $db->prepare("UPDATE citas SET estado = 'en_progreso' WHERE id = :id");
            $stmt->execute([':id' => $cita_id]);
        }
        
        $stmt = $db->prepare("SELECT * FROM fichas_grooming WHERE cita_id = :cita_id");
        $stmt->execute([':cita_id' => $cita_id]);
        $ficha = $stmt->fetch();
    }
    
    header("Location: ficha_grooming.php?cita_id=$cita_id&msg=" . urlencode($mensaje));
    exit;
}

if(isset($_GET['msg'])) $mensaje = $_GET['msg'];

// Obtener fotos existentes
$fotos = [];
if($ficha){
    $stmt = $db->prepare("SELECT * FROM fotos_mascota WHERE ficha_id = :ficha_id ORDER BY created_at DESC");
    $stmt->execute([':ficha_id' => $ficha['id']]);
    $fotos = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha de Grooming - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; }
        .main-content { margin-left: 250px; }
        .info-card { background: #f8f9fa; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
        .foto-thumb { width: 100px; height: 100px; object-fit: cover; border-radius: 10px; margin: 5px; cursor: pointer; }
        @media (max-width: 768px) { .sidebar { position: static; } .main-content { margin-left: 0; } }
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
                <li><a class="nav-link active" href="ficha_grooming.php?cita_id=<?php echo $cita_id; ?>"><i class="fas fa-clipboard-list"></i> Fichas</a></li>
                <li><a class="nav-link" href="checklist.php?cita_id=<?php echo $cita_id; ?>"><i class="fas fa-check-square"></i> Checklist</a></li>
                <li><a class="nav-link" href="insumos_recibidos.php?cita_id=<?php echo $cita_id; ?>"><i class="fas fa-boxes"></i> Insumos</a></li>
                <li><a class="nav-link" href="galeria_fotos.php?cita_id=<?php echo $cita_id; ?>"><i class="fas fa-camera"></i> Galería</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-clipboard-list"></i> Ficha de Grooming</h2>
            </div>
            
            <?php if($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-card">
                        <h5><i class="fas fa-user"></i> Cliente</h5>
                        <p><strong><?php echo htmlspecialchars($cita['cliente_nombre'] . ' ' . $cita['cliente_apellido']); ?></strong><br>📞 <?php echo $cita['telefono'] ?? '-'; ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <h5><i class="fas fa-paw"></i> Mascota</h5>
                        <p><strong><?php echo htmlspecialchars($cita['mascota_nombre']); ?></strong><br>
                        <?php echo ucfirst($cita['especie']); ?> | <?php echo $cita['raza'] ?: '-'; ?> | <?php echo $cita['sexo'] ?: '-'; ?><br>
                        Tamaño: <strong><?php echo ucfirst($cita['tamano'] ?? 'Mediano'); ?></strong></p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Registro de Trabajo</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label><i class="fas fa-weight-scale"></i> Peso actual (kg)</label>
                                <input type="number" step="0.01" name="peso" class="form-control" value="<?php echo $ficha['peso_al_momento'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label><i class="fas fa-thermometer-half"></i> Temperatura (°C)</label>
                                <input type="number" step="0.1" name="temperatura" class="form-control" value="<?php echo $ficha['temperatura_ingreso'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label><i class="fas fa-clipboard"></i> Estado inicial de la mascota</label>
                            <textarea name="estado_inicial" class="form-control" rows="2" placeholder="Ej: Pelaje enredado, nervioso, etc."><?php echo htmlspecialchars($ficha['estado_inicial'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label><i class="fas fa-clipboard-check"></i> Estado final de la mascota</label>
                            <textarea name="estado_final" class="form-control" rows="2" placeholder="Ej: Muy limpio, tranquilo, etc."><?php echo htmlspecialchars($ficha['estado_final'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label><i class="fas fa-sticky-note"></i> Notas internas</label>
                            <textarea name="notas" class="form-control" rows="2" placeholder="Observaciones para el groomer o recepción"><?php echo htmlspecialchars($ficha['notas_internas'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label><i class="fas fa-lightbulb"></i> Recomendaciones para el dueño</label>
                            <textarea name="recomendaciones" class="form-control" rows="2" placeholder="Ej: Cepillar cada 2 días, próximo corte en 1 mes"><?php echo htmlspecialchars($ficha['recomendaciones'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="consumo_inventario" class="form-check-input" id="consumo" <?php echo ($ficha['inventario_consumido'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="consumo">
                                <i class="fas fa-boxes"></i> Marcar consumo de inventario (shampoo, productos usados)
                            </label>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>¿Necesitas completar el checklist?</strong>
                            <a href="checklist.php?cita_id=<?php echo $cita_id; ?>" class="alert-link">Ir al checklist de grooming →</a>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-camera"></i> 
                            <strong>Subir fotos:</strong>
                            <a href="galeria_fotos.php?cita_id=<?php echo $cita_id; ?>" class="alert-link">Ir a la galería de fotos →</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Ficha</button>
                        <a href="citas.php" class="btn btn-secondary">Volver a Citas</a>
                        
                        <?php if($ficha && ($cita['estado'] != 'completada')): ?>
                            <a href="cierre_servicio.php?cita_id=<?php echo $cita_id; ?>" class="btn btn-success float-end">
                                <i class="fas fa-check-circle"></i> Finalizar Servicio
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <?php if(!empty($fotos)): ?>
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-images"></i> Fotos de la sesión</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($fotos as $foto): ?>
                        <div class="col-md-2 mb-2">
                            <img src="<?php echo $foto['url']; ?>" class="foto-thumb" alt="Foto <?php echo $foto['tipo']; ?>" onclick="window.open(this.src)">
                            <div class="small text-center"><?php echo $foto['tipo']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
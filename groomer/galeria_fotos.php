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

// Verificar cita
if($cita_id){
    $stmt = $db->prepare("SELECT * FROM citas WHERE id = :cita_id AND groomer_id = :groomer_id");
    $stmt->execute([':cita_id' => $cita_id, ':groomer_id' => $groomerId]);
    if(!$stmt->fetch()){
        $cita_id = 0;
    }
}

// Obtener ficha
$ficha_id = null;
if($cita_id){
    $stmt = $db->prepare("SELECT id FROM fichas_grooming WHERE cita_id = :cita_id");
    $stmt->execute([':cita_id' => $cita_id]);
    $ficha = $stmt->fetch();
    if($ficha) $ficha_id = $ficha['id'];
}

// Subir foto
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])){
    if(!$ficha_id){
        $mensaje = '<div class="alert alert-danger">Primero debes crear la ficha de grooming</div>';
    } else {
        $tipo = $_POST['tipo'];
        $uploadDir = '../uploads/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $nombreArchivo = time() . '_' . basename($_FILES['foto']['name']);
        $rutaDestino = $uploadDir . $nombreArchivo;
        
        if(move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)){
            $url = '/pet-spa/uploads/' . $nombreArchivo;
            $stmt = $db->prepare("INSERT INTO fotos_mascota (ficha_id, tipo, url) VALUES (?, ?, ?)");
            $stmt->execute([$ficha_id, $tipo, $url]);
            $mensaje = '<div class="alert alert-success">Foto subida correctamente</div>';
            AuditLog::log($_SESSION['user_id'], ROLE_GROOMER, 'FOTO_SUBIDA', "Subió foto tipo: $tipo para cita ID: $cita_id", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        } else {
            $mensaje = '<div class="alert alert-danger">Error al subir la foto</div>';
        }
    }
}

// Eliminar foto
if(isset($_GET['delete_foto'])){
    $foto_id = $_GET['delete_foto'];
    $stmt = $db->prepare("DELETE FROM fotos_mascota WHERE id = :id");
    $stmt->execute([':id' => $foto_id]);
    $mensaje = '<div class="alert alert-warning">Foto eliminada</div>';
}

// Obtener fotos
$fotos = [];
if($ficha_id){
    $stmt = $db->prepare("SELECT * FROM fotos_mascota WHERE ficha_id = :ficha_id ORDER BY created_at DESC");
    $stmt->execute([':ficha_id' => $ficha_id]);
    $fotos = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Galería de Fotos - Groomer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; }
        .main-content { margin-left: 250px; }
        .foto-card { position: relative; overflow: hidden; border-radius: 10px; transition: transform 0.3s; cursor: pointer; }
        .foto-card:hover { transform: scale(1.05); }
        .foto-card img { width: 100%; height: 200px; object-fit: cover; }
        .foto-tipo { position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.6); color: white; padding: 2px 8px; border-radius: 5px; font-size: 12px; }
        .foto-eliminar { position: absolute; top: 10px; right: 10px; background: rgba(255,0,0,0.8); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity 0.3s; }
        .foto-card:hover .foto-eliminar { opacity: 1; }
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
                <li><a class="nav-link" href="ficha_grooming.php"><i class="fas fa-clipboard-list"></i> Fichas</a></li>
                <li><a class="nav-link" href="checklist.php"><i class="fas fa-check-square"></i> Checklist</a></li>
                <li><a class="nav-link" href="insumos_recibidos.php"><i class="fas fa-boxes"></i> Insumos</a></li>
                <li><a class="nav-link active" href="galeria_fotos.php"><i class="fas fa-camera"></i> Galería</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-camera"></i> Galería de Fotos</h2>
            </div>
            
            <?php echo $mensaje; ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5>Subir Nueva Foto</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label>Cita ID</label>
                                    <input type="number" name="cita_id" class="form-control" value="<?php echo $cita_id; ?>" placeholder="ID de la cita">
                                    <small class="text-muted">Opcional, para vincular a una cita específica</small>
                                </div>
                                <div class="mb-3">
                                    <label>Tipo de foto</label>
                                    <select name="tipo" class="form-control" required>
                                        <option value="antes">Antes del servicio</option>
                                        <option value="despues">Después del servicio</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Seleccionar imagen</label>
                                    <input type="file" name="foto" class="form-control" accept="image/*" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Subir Foto</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5>Galería de Fotos</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($fotos)): ?>
                                <p class="text-muted text-center">No hay fotos subidas</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach($fotos as $f): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="foto-card" onclick="window.open('<?php echo $f['url']; ?>')">
                                            <img src="<?php echo $f['url']; ?>" alt="Foto">
                                            <div class="foto-tipo"><?php echo ucfirst($f['tipo']); ?></div>
                                            <div class="foto-eliminar" onclick="event.stopPropagation(); if(confirm('¿Eliminar esta foto?')) window.location.href='galeria_fotos.php?delete_foto=<?php echo $f['id']; ?>'">
                                                <i class="fas fa-trash"></i>
                                            </div>
                                        </div>
                                        <div class="text-center small text-muted"><?php echo date('d/m/Y H:i', strtotime($f['created_at'])); ?></div>
                                    </div>
                                    <?php endforeach; ?>
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
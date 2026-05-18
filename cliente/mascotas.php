<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente_id = $stmt->fetch()['id'];

// Eliminar mascota
if(isset($_GET['delete'])){
    $db->prepare("DELETE FROM mascota_dueno WHERE mascota_id=? AND cliente_id=?")->execute([$_GET['delete'], $cliente_id]);
    header("Location: mascotas.php?msg=Eliminada");
    exit;
}

$stmt = $db->prepare("
    SELECT m.* FROM mascotas m
    JOIN mascota_dueno md ON m.id = md.mascota_id
    WHERE md.cliente_id = ?
");
$stmt->execute([$cliente_id]);
$mascotas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Mascotas</title>
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
                <li><a class="nav-link active" href="mascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="historial.php"><i class="fas fa-history"></i> Historial</a></li>
                <li><a class="nav-link" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-paw"></i> Mis Mascotas</h2>
                <a href="mascota_agregar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Agregar Mascota</a>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success">Mascota eliminada</div>
            <?php endif; ?>
            
            <?php if(empty($mascotas)): ?>
                <div class="alert alert-info">No tienes mascotas registradas. <a href="mascota_agregar.php">Agregar una</a></div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($mascotas as $m): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($m['nombre']); ?></h5>
                                <p class="card-text">
                                    <strong>Especie:</strong> <?php echo $m['especie']; ?><br>
                                    <strong>Raza:</strong> <?php echo $m['raza'] ?: 'No especificada'; ?><br>
                                    <strong>Sexo:</strong> <?php echo $m['sexo'] ?: 'No especificado'; ?><br>
                                    <?php if($m['peso_kg']): ?>
                                    <strong>Peso:</strong> <?php echo $m['peso_kg']; ?> kg<br>
                                    <?php endif; ?>
                                </p>
                                <a href="mascota_agregar.php?edit=<?php echo $m['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                <a href="mascotas.php?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
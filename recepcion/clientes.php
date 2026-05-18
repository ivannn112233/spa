<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();

$buscar = $_GET['buscar'] ?? '';
$sql = "SELECT c.*, u.email, u.estado FROM clientes c JOIN usuarios u ON c.usuario_id = u.id";
if($buscar){
    $sql .= " WHERE c.nombre LIKE '%$buscar%' OR c.apellido LIKE '%$buscar%' OR c.telefono LIKE '%$buscar%' OR u.email LIKE '%$buscar%'";
}
$sql .= " ORDER BY c.created_at DESC";
$clientes = $db->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>.sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; } .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; } .sidebar .nav-link i { width: 25px; } .main-content { margin-left: 250px; } @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }</style>
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
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-users"></i> Clientes</h2>
                <a href="cita_agendar.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Cita</a>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row">
                        <div class="col-md-10"><input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, apellido, teléfono o email" value="<?php echo htmlspecialchars($buscar); ?>"></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Buscar</button></div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <?php if(empty($clientes)): ?>
                        <p class="text-muted">No hay clientes registrados</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($clientes as $c): ?>
                                    <tr>
                                        <td><?php echo $c['id']; ?></td>
                                        <td><?php echo htmlspecialchars($c['nombre'] . ' ' . $c['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($c['email']); ?></a></td>
                                        <td><?php echo $c['telefono'] ?: '-'; ?></td>
                                        <td><span class="badge bg-<?php echo $c['estado']=='activo'?'success':'danger'; ?>"><?php echo $c['estado']; ?></span></td>
                                        <td><a href="cliente_ver.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Ver</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
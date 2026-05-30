<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

$mensaje = '';

// Procesar acciones
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['guardar_rol'])){
        $nombre = strtolower($_POST['nombre']);
        $descripcion = $_POST['descripcion'];
        
        // Verificar si ya existe
        $stmt = $db->prepare("SELECT id FROM roles WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if($stmt->fetch()){
            $mensaje = '<div class="alert alert-danger">El rol ya existe</div>';
        } else {
            $stmt = $db->prepare("INSERT INTO roles (nombre, descripcion) VALUES (?,?)");
            $stmt->execute([$nombre, $descripcion]);
            $mensaje = '<div class="alert alert-success">Rol creado correctamente</div>';
            AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'ROL_CREADO', "Creó rol: $nombre", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        }
    }
    
    if(isset($_POST['editar_rol'])){
        $id = $_POST['rol_id'];
        $nombre = strtolower($_POST['nombre']);
        $descripcion = $_POST['descripcion'];
        
        $stmt = $db->prepare("UPDATE roles SET nombre = ?, descripcion = ? WHERE id = ?");
        $stmt->execute([$nombre, $descripcion, $id]);
        $mensaje = '<div class="alert alert-success">Rol actualizado correctamente</div>';
        AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'ROL_EDITADO', "Editó rol ID: $id", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    }
}

// Eliminar rol (solo si no tiene usuarios asociados)
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    
    // Verificar si hay usuarios con este rol
    $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();
    
    if($count > 0){
        $mensaje = '<div class="alert alert-danger">No se puede eliminar el rol porque tiene ' . $count . ' usuarios asociados</div>';
    } else {
        $stmt = $db->prepare("DELETE FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $mensaje = '<div class="alert alert-success">Rol eliminado correctamente</div>';
        AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'ROL_ELIMINADO', "Eliminó rol ID: $id", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    }
}

// Obtener roles
$roles = $db->query("SELECT * FROM roles ORDER BY id")->fetchAll();

// Obtener cantidad de usuarios por rol
$stmt = $db->query("
    SELECT r.id, r.nombre, COUNT(u.id) as total_usuarios 
    FROM roles r 
    LEFT JOIN usuarios u ON u.rol_id = r.id 
    GROUP BY r.id
");
$stats_roles = $stmt->fetchAll();
$stats = [];
foreach($stats_roles as $s){
    $stats[$s['id']] = $s['total_usuarios'];
}

// Obtener rol para editar
$edit = null;
if(isset($_GET['edit'])){
    $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Roles - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .rol-card { transition: transform 0.2s; border-left: 4px solid #3498db; }
        .rol-card:hover { transform: translateY(-3px); }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Admin</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a class="nav-link active" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
                <li><a class="nav-link" href="disponibilidad.php"><i class="fas fa-clock"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                <li><a class="nav-link" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-tags"></i> Gestión de Roles</h2>
            </div>
            
            <?php echo $mensaje; ?>
            <?php if(isset($_GET['msg'])) echo '<div class="alert alert-success">'.htmlspecialchars($_GET['msg']).'</div>'; ?>
            
            <div class="row">
                <!-- Formulario -->
                <div class="col-md-5">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo $edit ? 'Editar Rol' : 'Nuevo Rol'; ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php if($edit): ?>
                                    <input type="hidden" name="rol_id" value="<?php echo $edit['id']; ?>">
                                    <input type="hidden" name="editar_rol" value="1">
                                <?php else: ?>
                                    <input type="hidden" name="guardar_rol" value="1">
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label>Nombre del Rol *</label>
                                    <input type="text" name="nombre" class="form-control" value="<?php echo $edit ? htmlspecialchars($edit['nombre']) : ''; ?>" required>
                                    <small class="text-muted">Ej: veterinario, cajero, etc. (se guardará en minúsculas)</small>
                                </div>
                                <div class="mb-3">
                                    <label>Descripción</label>
                                    <textarea name="descripcion" class="form-control" rows="3"><?php echo $edit ? htmlspecialchars($edit['descripcion']) : ''; ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save"></i> <?php echo $edit ? 'Actualizar Rol' : 'Guardar Rol'; ?>
                                </button>
                                <?php if($edit): ?>
                                    <a href="roles.php" class="btn btn-secondary w-100 mt-2">Cancelar edición</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-3 bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-info-circle"></i> Información</h6>
                            <small>Los roles predefinidos del sistema son: <strong>admin, recepcion, groomer, cliente</strong>. No se recomienda eliminarlos.</small>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de roles -->
                <div class="col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Roles del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($roles)): ?>
                                <p class="text-muted">No hay roles registrados</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Descripción</th>
                                                <th>Usuarios</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($roles as $r): 
                                                $usuarios_count = $stats[$r['id']] ?? 0;
                                                $es_predefinido = in_array($r['nombre'], ['admin', 'recepcion', 'groomer', 'cliente']);
                                            ?>
                                            <tr class="rol-card">
                                                <td><?php echo $r['id']; ?></td>
                                                <td>
                                                    <strong><?php echo ucfirst($r['nombre']); ?></strong>
                                                    <?php if($es_predefinido): ?>
                                                        <span class="badge bg-secondary">Sistema</span>
                                                    <?php endif; ?>
                                                 </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($r['descripcion']); ?></td>
                                                <td><span class="badge bg-info"><?php echo $usuarios_count; ?> usuarios</span></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="roles.php?edit=<?php echo $r['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if(!$es_predefinido && $usuarios_count == 0): ?>
                                                            <a href="roles.php?delete=<?php echo $r['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este rol? Se eliminará permanentemente.')" title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php elseif($usuarios_count > 0): ?>
                                                            <button class="btn btn-sm btn-secondary" disabled title="No se puede eliminar, tiene usuarios asociados">
                                                                <i class="fas fa-lock"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                 </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
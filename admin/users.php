<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

$usuarios = $db->query("
    SELECT u.*, r.nombre as rol_nombre,
           c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.telefono as cliente_telefono, c.ci,
           g.nombre as groomer_nombre, g.apellido as groomer_apellido, g.telefono as groomer_telefono, g.especialidad
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id 
    LEFT JOIN clientes c ON c.usuario_id = u.id
    LEFT JOIN groomers g ON g.usuario_id = u.id
    ORDER BY u.created_at DESC
")->fetchAll();

$mensaje = '';
if (isset($_GET['msg'])) $mensaje = '<div class="alert alert-success">'.htmlspecialchars($_GET['msg']).'</div>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link.active { background: #3498db; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .user-card { transition: transform 0.2s; }
        .user-card:hover { transform: translateY(-2px); }
        .modal-header { background: #2c3e50; color: white; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-md-block sidebar p-0">
            <div class="text-center py-4 border-bottom border-secondary">
                <h5 class="text-white">🐾 Pet Spa</h5>
                <small class="text-muted">Administrador</small>
            </div>
            <ul class="nav flex-column mt-3">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link active" href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a class="nav-link" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
                <a href="users_create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Usuario</a>
            </div>
            
            <?php echo $mensaje; ?>
            
            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5><?php echo count($usuarios); ?></h5>
                            <small>Total Usuarios</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5><?php echo count(array_filter($usuarios, fn($u) => $u['estado']=='activo')); ?></h5>
                            <small>Usuarios Activos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5><?php echo count(array_filter($usuarios, fn($u) => $u['rol_nombre']=='cliente')); ?></h5>
                            <small>Clientes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5><?php echo count(array_filter($usuarios, fn($u) => $u['rol_nombre']=='groomer')); ?></h5>
                            <small>Groomers</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de usuarios mejorada -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-list"></i> Lista de Usuarios
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Nombre Completo</th>
                                    <th>Teléfono</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($usuarios as $u): 
                                    $nombreCompleto = '';
                                    $telefono = '';
                                    $extraInfo = '';
                                    
                                    if($u['rol_nombre'] == 'cliente'){
                                        $nombreCompleto = ($u['cliente_nombre'] ? $u['cliente_nombre'].' '.$u['cliente_apellido'] : '-');
                                        $telefono = $u['cliente_telefono'] ?? '-';
                                        $extraInfo = $u['ci'] ? "CI: {$u['ci']}" : '';
                                    } elseif($u['rol_nombre'] == 'groomer'){
                                        $nombreCompleto = ($u['groomer_nombre'] ? $u['groomer_nombre'].' '.$u['groomer_apellido'] : '-');
                                        $telefono = $u['groomer_telefono'] ?? '-';
                                        $extraInfo = $u['especialidad'] ? "Esp: {$u['especialidad']}" : '';
                                    } else {
                                        $nombreCompleto = $u['rol_nombre'];
                                        $telefono = '-';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($u['email']); ?>
                                        <?php if($extraInfo): ?>
                                            <br><small class="text-muted"><?php echo $extraInfo; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($nombreCompleto); ?></td>
                                    <td><?php echo htmlspecialchars($telefono); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $u['rol_nombre']=='admin'?'danger':
                                                ($u['rol_nombre']=='recepcion'?'warning':
                                                ($u['rol_nombre']=='groomer'?'info':'success'));
                                        ?>">
                                            <?php echo ucfirst($u['rol_nombre']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $u['estado']=='activo'?'success':($u['estado']=='pendiente'?'warning':'danger'); ?>">
                                            <?php 
                                                echo $u['estado']=='activo'?'Activo':
                                                    ($u['estado']=='pendiente'?'Pendiente':
                                                    ($u['estado']=='bloqueado'?'Bloqueado':'Inactivo'));
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca'; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-info" onclick="verDetalle(<?php echo htmlspecialchars(json_encode($u)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="users_edit.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if($u['estado'] == 'activo'): ?>
                                                <a href="users.php?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Desactivar este usuario?')">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="users.php?activate=<?php echo $u['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('¿Activar este usuario?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if($u['estado'] == 'bloqueado'): ?>
                                                <a href="users.php?unlock=<?php echo $u['id']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('¿Desbloquear este usuario?')">
                                                    <i class="fas fa-unlock"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal de Detalle de Usuario -->
<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-circle"></i> Detalle del Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleContent">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
// Activar usuario
if(isset($_GET['activate'])){
    $stmt = $db->prepare("UPDATE usuarios SET estado='activo', intentos_fallidos=0, bloqueado_hasta=NULL WHERE id=?");
    $stmt->execute([$_GET['activate']]);
    header("Location: users.php?msg=Usuario activado");
    exit;
}

// Desactivar usuario
if(isset($_GET['delete'])){
    $stmt = $db->prepare("UPDATE usuarios SET estado='inactivo' WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: users.php?msg=Usuario desactivado");
    exit;
}

// Desbloquear usuario
if(isset($_GET['unlock'])){
    $stmt = $db->prepare("UPDATE usuarios SET estado='activo', intentos_fallidos=0, bloqueado_hasta=NULL WHERE id=?");
    $stmt->execute([$_GET['unlock']]);
    header("Location: users.php?msg=Usuario desbloqueado");
    exit;
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function verDetalle(usuario) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-envelope"></i> Información de Cuenta
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><th>ID:</th><td>${usuario.id}</td></tr>
                            <tr><th>Email:</th><td>${usuario.email}</td></tr>
                            <tr><th>Rol:</th><td><span class="badge bg-info">${usuario.rol_nombre}</span></td></tr>
                            <tr><th>Estado:</th><td><span class="badge bg-${usuario.estado=='activo'?'success':'danger'}">${usuario.estado}</span></td></tr>
                            <tr><th>2FA:</th><td>${usuario.two_factor_enabled ? '<span class="badge bg-success">Activado</span>' : '<span class="badge bg-secondary">Desactivado</span>'}</td></tr>
                            <tr><th>Intentos fallidos:</th><td>${usuario.intentos_fallidos}</td></tr>
                            <tr><th>Último acceso:</th><td>${usuario.ultimo_acceso ? new Date(usuario.ultimo_acceso).toLocaleString() : 'Nunca'}</td></tr>
                            <tr><th>Creado:</th><td>${new Date(usuario.created_at).toLocaleString()}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-user"></i> Información Personal
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
    `;
    
    if(usuario.rol_nombre == 'cliente') {
        html += `
            <tr><th>Nombre:</th><td>${usuario.cliente_nombre || '-'} ${usuario.cliente_apellido || ''}</td></tr>
            <tr><th>Teléfono:</th><td>${usuario.cliente_telefono || '-'}</td></tr>
            <tr><th>Carnet Identidad:</th><td>${usuario.ci || '-'}</td></tr>
        `;
    } else if(usuario.rol_nombre == 'groomer') {
        html += `
            <tr><th>Nombre:</th><td>${usuario.groomer_nombre || '-'} ${usuario.groomer_apellido || ''}</td></tr>
            <tr><th>Teléfono:</th><td>${usuario.groomer_telefono || '-'}</td></tr>
            <tr><th>Especialidad:</th><td>${usuario.especialidad || '-'}</td></tr>
        `;
    } else {
        html += `<tr><td colspan="2" class="text-muted">No hay información adicional para este rol</td></tr>`;
    }
    
    html += `
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('detalleContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('detalleModal')).show();
}
</script>
</body>
</html>
<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

// Procesar acciones vía POST para mayor seguridad
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['activate'])){
        $stmt = $db->prepare("UPDATE usuarios SET estado='activo', intentos_fallidos=0, bloqueado_hasta=NULL WHERE id=?");
        $stmt->execute([$_POST['activate']]);
        header("Location: users.php?msg=Usuario activado");
        exit;
    }
    if(isset($_POST['delete'])){
        $stmt = $db->prepare("UPDATE usuarios SET estado='inactivo' WHERE id=?");
        $stmt->execute([$_POST['delete']]);
        header("Location: users.php?msg=Usuario desactivado");
        exit;
    }
    if(isset($_POST['unlock'])){
        $stmt = $db->prepare("UPDATE usuarios SET estado='activo', intentos_fallidos=0, bloqueado_hasta=NULL WHERE id=?");
        $stmt->execute([$_POST['unlock']]);
        header("Location: users.php?msg=Usuario desbloqueado");
        exit;
    }
    if(isset($_POST['reset_password'])){
        $id = $_POST['reset_password'];
        $new_password = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%'), 0, 10);
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE usuarios SET password_hash=?, password_cambiada=0 WHERE id=?");
        $stmt->execute([$hash, $id]);
        
        // Obtener email del usuario
        $stmt = $db->prepare("SELECT email FROM usuarios WHERE id=?");
        $stmt->execute([$id]);
        $email = $stmt->fetchColumn();
        
        header("Location: users.php?msg=Contraseña restablecida&new_pass=" . urlencode($new_password) . "&email=" . urlencode($email));
        exit;
    }
}

$usuarios = $db->query("
    SELECT u.*, r.nombre as rol_nombre,
           c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.telefono as cliente_telefono, c.ci, c.direccion,
           g.nombre as groomer_nombre, g.apellido as groomer_apellido, g.telefono as groomer_telefono, g.especialidad, g.turno
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id 
    LEFT JOIN clientes c ON c.usuario_id = u.id
    LEFT JOIN groomers g ON g.usuario_id = u.id
    ORDER BY u.created_at DESC
")->fetchAll();

$mensaje = '';
if (isset($_GET['msg'])) {
    $mensaje = '<div class="alert alert-success">'.htmlspecialchars($_GET['msg']).'</div>';
    if(isset($_GET['new_pass'])){
        $mensaje .= '<div class="alert alert-info">Nueva contraseña para <strong>'.htmlspecialchars($_GET['email']).'</strong>: <code>'.htmlspecialchars($_GET['new_pass']).'</code><br><small>Guarda esta contraseña y entrégala al usuario.</small></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link:hover { background: #34495e; }
        .sidebar .nav-link.active { background: #3498db; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .user-card { transition: transform 0.2s; }
        .user-card:hover { transform: translateY(-2px); }
        .table-usuarios { font-size: 0.85rem; }
        .badge-role { padding: 5px 10px; border-radius: 20px; }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
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
            <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
                <a href="users_create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Usuario</a>
            </div>
            
            <?php echo $mensaje; ?>
            
            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h5><?php echo count($usuarios); ?></h5><small>Total Usuarios</small></div></div></div>
                <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h5><?php echo count(array_filter($usuarios, fn($u) => $u['estado']=='activo')); ?></h5><small>Usuarios Activos</small></div></div></div>
                <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h5><?php echo count(array_filter($usuarios, fn($u) => $u['rol_nombre']=='cliente')); ?></h5><small>Clientes</small></div></div></div>
                <div class="col-md-3"><div class="card bg-warning text-white"><div class="card-body"><h5><?php echo count(array_filter($usuarios, fn($u) => $u['rol_nombre']=='groomer')); ?></h5><small>Groomers</small></div></div></div>
            </div>
            
            <!-- Barra de búsqueda -->
            <div class="card mb-4">
                <div class="card-body">
                    <input type="text" id="buscarUsuario" class="form-control" placeholder="🔍 Buscar por email, nombre o teléfono...">
                </div>
            </div>
            
            <!-- Tabla de usuarios -->
            <div class="card">
                <div class="card-header bg-dark text-white"><i class="fas fa-list"></i> Lista de Usuarios</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-usuarios" id="tablaUsuarios">
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
                                        $nombreCompleto = ucfirst($u['rol_nombre']);
                                        $telefono = '-';
                                    }
                                    
                                    $estadoBadge = '';
                                    if($u['estado'] == 'activo') $estadoBadge = 'success';
                                    elseif($u['estado'] == 'pendiente') $estadoBadge = 'warning';
                                    elseif($u['estado'] == 'bloqueado') $estadoBadge = 'danger';
                                    else $estadoBadge = 'secondary';
                                    
                                    $rolBadge = '';
                                    if($u['rol_nombre'] == 'admin') $rolBadge = 'danger';
                                    elseif($u['rol_nombre'] == 'recepcion') $rolBadge = 'warning';
                                    elseif($u['rol_nombre'] == 'groomer') $rolBadge = 'info';
                                    else $rolBadge = 'success';
                                ?>
                                <tr class="fila-usuario">
                                    <td><?php echo $u['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($u['email']); ?>
                                        <?php if($extraInfo): ?><br><small class="text-muted"><?php echo $extraInfo; ?></small><?php endif; ?>
                                     </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($nombreCompleto); ?></td>
                                    <td><?php echo htmlspecialchars($telefono); ?></td>
                                    <td><span class="badge bg-<?php echo $rolBadge; ?> badge-role"><?php echo ucfirst($u['rol_nombre']); ?></span></td>
                                    <td><span class="badge bg-<?php echo $estadoBadge; ?>"><?php echo ucfirst($u['estado']); ?></span></td>
                                    <td><?php echo $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca'; ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-info" onclick="verDetalle(<?php echo htmlspecialchars(json_encode($u)); ?>)"><i class="fas fa-eye"></i></button>
                                            <a href="users_edit.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-secondary" onclick="resetPassword(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['email']); ?>')"><i class="fas fa-key"></i></button>
                                            <?php if($u['estado'] == 'activo'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Desactivar este usuario?')">
                                                    <input type="hidden" name="delete" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-ban"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Activar este usuario?')">
                                                    <input type="hidden" name="activate" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if($u['estado'] == 'bloqueado'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Desbloquear este usuario?')">
                                                    <input type="hidden" name="unlock" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-unlock"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
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

<!-- Modal Detalle Usuario -->
<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-circle"></i> Detalle del Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Restablecer Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>¿Estás seguro de restablecer la contraseña para <strong id="resetEmail"></strong>?</p>
                    <p>Se generará una nueva contraseña temporal que se mostrará al finalizar.</p>
                    <input type="hidden" name="reset_password" id="resetUserId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Restablecer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function verDetalle(usuario) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white"><i class="fas fa-envelope"></i> Información de Cuenta</div>
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
                    <div class="card-header bg-success text-white"><i class="fas fa-user"></i> Información Personal</div>
                    <div class="card-body">
                        <table class="table table-sm">
    `;
    
    if(usuario.rol_nombre == 'cliente') {
        html += `
            <tr><th>Nombre:</th><td>${usuario.cliente_nombre || '-'} ${usuario.cliente_apellido || ''}</td></tr>
            <tr><th>Teléfono:</th><td>${usuario.cliente_telefono || '-'}</td></tr>
            <tr><th>Carnet Identidad:</th><td>${usuario.ci || '-'}</td></tr>
            <tr><th>Dirección:</th><td>${usuario.direccion || '-'}</td></tr>
        `;
    } else if(usuario.rol_nombre == 'groomer') {
        html += `
            <tr><th>Nombre:</th><td>${usuario.groomer_nombre || '-'} ${usuario.groomer_apellido || ''}</td></tr>
            <tr><th>Teléfono:</th><td>${usuario.groomer_telefono || '-'}</td></tr>
            <tr><th>Especialidad:</th><td>${usuario.especialidad || '-'}</td></tr>
            <tr><th>Turno:</th><td>${usuario.turno || '-'}</td></tr>
        `;
    } else {
        html += `<tr><td colspan="2" class="text-muted">No hay información adicional para este rol</td></tr>`;
    }
    
    html += `</table></div></div></div></div>`;
    document.getElementById('detalleContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('detalleModal')).show();
}

function resetPassword(userId, email) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetEmail').innerText = email;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

// Búsqueda en tiempo real
document.getElementById('buscarUsuario').addEventListener('keyup', function() {
    const busqueda = this.value.toLowerCase();
    const filas = document.querySelectorAll('.fila-usuario');
    filas.forEach(fila => {
        const texto = fila.innerText.toLowerCase();
        fila.style.display = texto.includes(busqueda) ? '' : 'none';
    });
});
</script>
</body>
</html>
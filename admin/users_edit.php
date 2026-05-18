<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

$id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Obtener datos del usuario con información adicional según su rol
$stmt = $db->prepare("
    SELECT u.*, r.nombre as rol_nombre,
           c.id as cliente_id, c.nombre as cliente_nombre, c.apellido as cliente_apellido, 
           c.telefono as cliente_telefono, c.ci, c.direccion, c.canal_notificacion,
           g.id as groomer_id, g.nombre as groomer_nombre, g.apellido as groomer_apellido, 
           g.telefono as groomer_telefono, g.especialidad, g.turno, g.capacidad_simultanea
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id 
    LEFT JOIN clientes c ON c.usuario_id = u.id
    LEFT JOIN groomers g ON g.usuario_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch();
if(!$user) die("Usuario no encontrado");

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email = $_POST['email'];
    $estado = $_POST['estado'];
    $rol_id = $_POST['rol_id'];
    
    // Actualizar usuario principal
    $stmt = $db->prepare("UPDATE usuarios SET email=?, estado=?, rol_id=? WHERE id=?");
    $stmt->execute([$email, $estado, $rol_id, $id]);
    
    // Cambiar contraseña si se proporcionó
    if(!empty($_POST['password'])){
        $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $db->prepare("UPDATE usuarios SET password_hash=? WHERE id=?")->execute([$pass, $id]);
    }
    
    // Actualizar datos según rol
    if($user['rol_nombre'] == 'cliente' || $rol_id == 4){
        $nombre = $_POST['nombre'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $ci = $_POST['ci'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $canal = $_POST['canal_notificacion'] ?? 'email';
        
        if($user['cliente_id']){
            $stmt = $db->prepare("UPDATE clientes SET nombre=?, apellido=?, telefono=?, ci=?, direccion=?, canal_notificacion=? WHERE usuario_id=?");
            $stmt->execute([$nombre, $apellido, $telefono, $ci, $direccion, $canal, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO clientes (usuario_id, nombre, apellido, telefono, ci, direccion, canal_notificacion) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$id, $nombre, $apellido, $telefono, $ci, $direccion, $canal]);
        }
    }
    
    if($user['rol_nombre'] == 'groomer' || $rol_id == 3){
        $nombre = $_POST['groomer_nombre'] ?? '';
        $apellido = $_POST['groomer_apellido'] ?? '';
        $telefono = $_POST['groomer_telefono'] ?? '';
        $especialidad = $_POST['especialidad'] ?? '';
        $turno = $_POST['turno'] ?? 'completo';
        $capacidad = $_POST['capacidad_simultanea'] ?? 1;
        
        if($user['groomer_id']){
            $stmt = $db->prepare("UPDATE groomers SET nombre=?, apellido=?, telefono=?, especialidad=?, turno=?, capacidad_simultanea=? WHERE usuario_id=?");
            $stmt->execute([$nombre, $apellido, $telefono, $especialidad, $turno, $capacidad, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO groomers (usuario_id, nombre, apellido, telefono, especialidad, turno, capacidad_simultanea, estado_activo) VALUES (?,?,?,?,?,?,?,1)");
            $stmt->execute([$id, $nombre, $apellido, $telefono, $especialidad, $turno, $capacidad]);
        }
    }
    
    header("Location: users.php?msg=Usuario actualizado correctamente");
    exit;
}

// Obtener roles para el selector
$roles = $db->query("SELECT * FROM roles ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .form-section h5 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-warning">
                    <h4 class="mb-0">
                        <i class="fas fa-edit"></i> Editar Usuario: 
                        <small><?php echo htmlspecialchars($user['email']); ?></small>
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <!-- Información de cuenta -->
                        <div class="form-section">
                            <h5><i class="fas fa-envelope"></i> Información de Cuenta</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Email *</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Rol</label>
                                    <select name="rol_id" class="form-control">
                                        <?php foreach($roles as $r): ?>
                                        <option value="<?php echo $r['id']; ?>" <?php echo $user['rol_id']==$r['id']?'selected':''; ?>>
                                            <?php echo ucfirst($r['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control">
                                        <option value="activo" <?php echo $user['estado']=='activo'?'selected':''; ?>>Activo</option>
                                        <option value="inactivo" <?php echo $user['estado']=='inactivo'?'selected':''; ?>>Inactivo</option>
                                        <option value="bloqueado" <?php echo $user['estado']=='bloqueado'?'selected':''; ?>>Bloqueado</option>
                                        <option value="pendiente" <?php echo $user['estado']=='pendiente'?'selected':''; ?>>Pendiente</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Nueva Contraseña</label>
                                    <input type="password" name="password" class="form-control" placeholder="Dejar vacío para no cambiar">
                                    <small class="text-muted">Mínimo 8 caracteres con mayúsculas, minúsculas, números y símbolos</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Datos según rol -->
                        <?php if($user['rol_nombre'] == 'cliente'): ?>
                        <!-- Datos de Cliente -->
                        <div class="form-section">
                            <h5><i class="fas fa-user"></i> Datos del Cliente</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Nombre</label>
                                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($user['cliente_nombre'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Apellido</label>
                                    <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($user['cliente_apellido'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Teléfono</label>
                                    <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($user['cliente_telefono'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Carnet de Identidad</label>
                                    <input type="text" name="ci" class="form-control" value="<?php echo htmlspecialchars($user['ci'] ?? ''); ?>">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label>Dirección</label>
                                    <textarea name="direccion" class="form-control" rows="2"><?php echo htmlspecialchars($user['direccion'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Canal de Notificación</label>
                                    <select name="canal_notificacion" class="form-control">
                                        <option value="email" <?php echo ($user['canal_notificacion']??'')=='email'?'selected':''; ?>>Email</option>
                                        <option value="whatsapp" <?php echo ($user['canal_notificacion']??'')=='whatsapp'?'selected':''; ?>>WhatsApp</option>
                                        <option value="telegram" <?php echo ($user['canal_notificacion']??'')=='telegram'?'selected':''; ?>>Telegram</option>
                                        <option value="sms" <?php echo ($user['canal_notificacion']??'')=='sms'?'selected':''; ?>>SMS</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php elseif($user['rol_nombre'] == 'groomer'): ?>
                        <!-- Datos de Groomer -->
                        <div class="form-section">
                            <h5><i class="fas fa-cut"></i> Datos del Groomer</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Nombre</label>
                                    <input type="text" name="groomer_nombre" class="form-control" value="<?php echo htmlspecialchars($user['groomer_nombre'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Apellido</label>
                                    <input type="text" name="groomer_apellido" class="form-control" value="<?php echo htmlspecialchars($user['groomer_apellido'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Teléfono</label>
                                    <input type="text" name="groomer_telefono" class="form-control" value="<?php echo htmlspecialchars($user['groomer_telefono'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Especialidad</label>
                                    <input type="text" name="especialidad" class="form-control" value="<?php echo htmlspecialchars($user['especialidad'] ?? ''); ?>" placeholder="Ej: Corte fino, Baño, etc">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Turno</label>
                                    <select name="turno" class="form-control">
                                        <option value="manana" <?php echo ($user['turno']??'')=='manana'?'selected':''; ?>>Mañana</option>
                                        <option value="tarde" <?php echo ($user['turno']??'')=='tarde'?'selected':''; ?>>Tarde</option>
                                        <option value="completo" <?php echo ($user['turno']??'')=='completo'?'selected':''; ?>>Completo</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Capacidad Simultánea</label>
                                    <input type="number" name="capacidad_simultanea" class="form-control" value="<?php echo $user['capacidad_simultanea'] ?? 1; ?>" min="1" max="5">
                                </div>
                            </div>
                        </div>
                        <?php elseif($user['rol_nombre'] == 'recepcion'): ?>
                        <div class="form-section">
                            <h5><i class="fas fa-phone"></i> Información</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> El personal de recepción solo tiene acceso a la gestión de citas y clientes.
                            </div>
                        </div>
                        <?php elseif($user['rol_nombre'] == 'admin'): ?>
                        <div class="form-section">
                            <h5><i class="fas fa-shield-alt"></i> Información</h5>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> El administrador tiene acceso total al sistema.
                            </div>
                            <div class="mb-3">
                                <label>Configurar 2FA</label>
                                <a href="../2fa-setup.php" class="btn btn-sm btn-primary">Configurar Autenticación de Dos Factores</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Información adicional -->
            <div class="card mt-3">
                <div class="card-body">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Creado: <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                        <?php if($user['ultimo_acceso']): ?>
                            | Último acceso: <?php echo date('d/m/Y H:i', strtotime($user['ultimo_acceso'])); ?>
                        <?php endif; ?>
                        <?php if($user['intentos_fallidos'] > 0): ?>
                            | Intentos fallidos: <span class="text-danger"><?php echo $user['intentos_fallidos']; ?></span>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
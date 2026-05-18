<?php
require_once '../config/config.php';
require_once '../includes/mailer.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

$error = '';
$success = '';
$password_generada = '';
$email_enviado = false;
$ultimo_usuario = null;

$roles = $db->query("SELECT * FROM roles WHERE nombre != 'admin' ORDER BY id")->fetchAll();

// Función para generar contraseña aleatoria segura
function generarPassword($longitud = 10) {
    $mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $minusculas = 'abcdefghijkmnpqrstuvwxyz';
    $numeros = '23456789';
    $simbolos = '!@#$%';
    
    $password = '';
    $password .= $mayusculas[random_int(0, strlen($mayusculas)-1)];
    $password .= $minusculas[random_int(0, strlen($minusculas)-1)];
    $password .= $numeros[random_int(0, strlen($numeros)-1)];
    $password .= $simbolos[random_int(0, strlen($simbolos)-1)];
    
    $todos = $mayusculas . $minusculas . $numeros . $simbolos;
    for($i = strlen($password); $i < $longitud; $i++) {
        $password .= $todos[random_int(0, strlen($todos)-1)];
    }
    return str_shuffle($password);
}

// Obtener especialidades para groomer
$especialidades = [
    'Corte fino', 'Baño y secado', 'Corte de razas', 
    'Grooming creativo', 'Deslanado', 'Corte de uñas', 
    'Limpieza dental', 'Spa completo'
];

// Turnos disponibles
$turnos = [
    'manana' => 'Mañana (08:00 - 14:00)',
    'tarde' => 'Tarde (14:00 - 20:00)',
    'completo' => 'Completo (08:00 - 20:00)'
];

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email = $_POST['email'];
    $rol_id = $_POST['rol_id'];
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $enviar_email = isset($_POST['enviar_email']) ? true : false;
    
    // Campos adicionales según rol
    $ci = $_POST['ci'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $canal_notificacion = $_POST['canal_notificacion'] ?? 'email';
    $especialidad = $_POST['especialidad'] ?? '';
    $turno = $_POST['turno'] ?? 'completo';
    $capacidad = $_POST['capacidad_simultanea'] ?? 1;
    
    // Verificar si el email ya existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if($stmt->fetch()){
        $error = "El email ya está registrado";
    } else {
        // Generar contraseña temporal
        $password_temporal = generarPassword(10);
        $password_hash = password_hash($password_temporal, PASSWORD_BCRYPT);
        
        // Crear usuario
        $stmt = $db->prepare("INSERT INTO usuarios (rol_id, email, password_hash, estado, created_at) VALUES (?,?,?,'activo', NOW())");
        if($stmt->execute([$rol_id, $email, $password_hash])){
            $user_id = $db->lastInsertId();
            
            // Crear perfil según rol con todos los campos
            if($rol_id == 4){ // Cliente
                $stmt = $db->prepare("
                    INSERT INTO clientes (usuario_id, nombre, apellido, telefono, ci, direccion, canal_notificacion) 
                    VALUES (?,?,?,?,?,?,?)
                ");
                $stmt->execute([$user_id, $nombre, $apellido, $telefono, $ci, $direccion, $canal_notificacion]);
                
            } elseif($rol_id == 3){ // Groomer
                $stmt = $db->prepare("
                    INSERT INTO groomers (usuario_id, nombre, apellido, telefono, especialidad, turno, capacidad_simultanea, estado_activo) 
                    VALUES (?,?,?,?,?,?,?,1)
                ");
                $stmt->execute([$user_id, $nombre, $apellido, $telefono, $especialidad, $turno, $capacidad]);
                
            } elseif($rol_id == 2){ // Recepción
                $stmt = $db->prepare("
                    INSERT INTO recepcionistas (usuario_id, nombre, apellido, telefono, turno) 
                    VALUES (?,?,?,?,?)
                ");
                $stmt->execute([$user_id, $nombre, $apellido, $telefono, $turno]);
            }
            
            // Guardar datos para mostrar
            $password_generada = $password_temporal;
            $ultimo_usuario = [
                'email' => $email,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'rol' => $rol_id,
                'password' => $password_temporal,
                'telefono' => $telefono,
                'ci' => $ci,
                'direccion' => $direccion,
                'especialidad' => $especialidad,
                'turno' => $turno
            ];
            
            // Enviar email con la contraseña
            if($enviar_email){
                $email_enviado = enviarEmailBienvenida($email, $nombre, $apellido, $password_temporal, $rol_id);
            }
            
            // Guardar en auditoría
            AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'USUARIO_CREADO', "Creó usuario: {$email} (rol_id: {$rol_id})", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            
            $success = true;
        } else {
            $error = "Error al crear usuario";
        }
    }
}

// Función para enviar email de bienvenida
function enviarEmailBienvenida($email, $nombre, $apellido, $password, $rol_id) {
    $rol_nombre = '';
    if($rol_id == 2) $rol_nombre = 'Recepción';
    elseif($rol_id == 3) $rol_nombre = 'Groomer';
    elseif($rol_id == 4) $rol_nombre = 'Cliente';
    
    $subject = "Tu cuenta ha sido creada - " . SITE_NAME;
    
    $htmlMessage = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Bienvenido a " . SITE_NAME . "</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px; }
            .password-box { font-size: 24px; letter-spacing: 2px; background: #f0f0f0; padding: 15px; text-align: center; font-weight: bold; border-radius: 10px; margin: 20px 0; font-family: monospace; }
            .footer { font-size: 12px; color: #999; text-align: center; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🐾 " . SITE_NAME . "</h2>
            </div>
            <h3>¡Bienvenido/a $nombre $apellido!</h3>
            <p>Se ha creado una cuenta para ti en nuestro sistema con el rol de <strong>$rol_nombre</strong>.</p>
            <p><strong>Tus credenciales de acceso:</strong></p>
            <ul>
                <li>📧 <strong>Email:</strong> $email</li>
                <li>🔑 <strong>Contraseña temporal:</strong> <span style='font-size: 18px; font-family: monospace; background: #f0f0f0; padding: 5px 10px;'>$password</span></li>
            </ul>
            <p style='color: #e74c3c;'><strong>⚠️ Importante:</strong> Esta es una contraseña temporal. Te recomendamos cambiarla en tu primer inicio de sesión.</p>
            <p>
                <a href='" . SITE_URL . "login.php' style='display: inline-block; background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                    Iniciar Sesión
                </a>
            </p>
            <div class='footer'>
                <p>" . SITE_NAME . " - El mejor cuidado para tu mascota</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    
    return @mail($email, $subject, $htmlMessage, $headers);
}

function getRolName($rol_id, $db) {
    $stmt = $db->prepare("SELECT nombre FROM roles WHERE id = ?");
    $stmt->execute([$rol_id]);
    $r = $stmt->fetch();
    return $r ? ucfirst($r['nombre']) : '';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-box {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 24px;
            text-align: center;
            letter-spacing: 2px;
            margin: 15px 0;
        }
        .copy-btn {
            cursor: pointer;
            transition: all 0.3s;
        }
        .copy-btn:hover {
            transform: scale(1.05);
        }
        .campos-rol {
            display: none;
        }
        .campos-rol.active {
            display: block;
        }
        .info-card {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h4>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($success) && $success): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> ¡Usuario creado exitosamente!</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($ultimo_usuario['email']); ?></p>
                                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($ultimo_usuario['nombre'] . ' ' . $ultimo_usuario['apellido']); ?></p>
                                    <p><strong>Rol:</strong> <?php echo getRolName($ultimo_usuario['rol'], $db); ?></p>
                                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($ultimo_usuario['telefono']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <?php if($ultimo_usuario['ci']): ?>
                                    <p><strong>CI:</strong> <?php echo htmlspecialchars($ultimo_usuario['ci']); ?></p>
                                    <?php endif; ?>
                                    <?php if($ultimo_usuario['especialidad']): ?>
                                    <p><strong>Especialidad:</strong> <?php echo htmlspecialchars($ultimo_usuario['especialidad']); ?></p>
                                    <?php endif; ?>
                                    <?php if($ultimo_usuario['turno']): ?>
                                    <p><strong>Turno:</strong> <?php echo htmlspecialchars($ultimo_usuario['turno']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <label><strong>🔑 Contraseña temporal generada:</strong></label>
                                <div class="password-box">
                                    <?php echo $password_generada; ?>
                                    <button class="btn btn-sm btn-light copy-btn" onclick="copiarPassword('<?php echo $password_generada; ?>')">
                                        <i class="fas fa-copy"></i> Copiar
                                    </button>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Guarda esta contraseña para entregarla al usuario.
                                </small>
                            </div>
                            
                            <?php if($email_enviado): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-envelope"></i> Se ha enviado un email con las credenciales a: <?php echo htmlspecialchars($ultimo_usuario['email']); ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    No se pudo enviar el email. Por favor, entrega la contraseña manualmente al usuario.
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <a href="users_create.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Crear otro usuario
                                </a>
                                <a href="users.php" class="btn btn-secondary">
                                    <i class="fas fa-list"></i> Ver lista de usuarios
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Se generará una contraseña temporal de 10 caracteres. 
                        Puedes optar por enviarla por email o entregarla manualmente.
                    </div>
                    
                    <form method="POST" id="formUsuario">
                        <!-- Datos básicos -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Rol *</label>
                                <select name="rol_id" id="rol_id" class="form-control" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo ucfirst($r['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <hr>
                        <h6>Datos personales</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Apellido *</label>
                                <input type="text" name="apellido" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Teléfono</label>
                                <input type="text" name="telefono" class="form-control">
                            </div>
                        </div>
                        
                        <!-- Campos para CLIENTE (rol_id = 4) -->
                        <div id="campos_cliente" class="campos-rol">
                            <div class="info-card">
                                <i class="fas fa-user"></i> <strong>Datos del Cliente</strong>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Carnet de Identidad</label>
                                    <input type="text" name="ci" class="form-control" placeholder="CI o NIT">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Canal de Notificación</label>
                                    <select name="canal_notificacion" class="form-control">
                                        <option value="email">Email</option>
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="telegram">Telegram</option>
                                        <option value="sms">SMS</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label>Dirección</label>
                                    <textarea name="direccion" class="form-control" rows="2" placeholder="Dirección completa"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campos para GROOMER (rol_id = 3) -->
                        <div id="campos_groomer" class="campos-rol">
                            <div class="info-card">
                                <i class="fas fa-cut"></i> <strong>Datos del Groomer</strong>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Especialidad</label>
                                    <select name="especialidad" class="form-control">
                                        <option value="">Seleccionar especialidad</option>
                                        <?php foreach($especialidades as $e): ?>
                                        <option value="<?php echo $e; ?>"><?php echo $e; ?></option>
                                        <?php endforeach; ?>
                                        <option value="otra">Otra (especificar)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Turno</label>
                                    <select name="turno" class="form-control">
                                        <?php foreach($turnos as $key => $t): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $t; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Capacidad Simultánea</label>
                                    <input type="number" name="capacidad_simultanea" class="form-control" value="1" min="1" max="5">
                                    <small class="text-muted">Cuántas mascotas puede atender a la vez</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Especialidad adicional</label>
                                    <input type="text" name="especialidad_extra" class="form-control" placeholder="Ej: Perros grandes, gatos, etc">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campos para RECEPCION (rol_id = 2) -->
                        <div id="campos_recepcion" class="campos-rol">
                            <div class="info-card">
                                <i class="fas fa-phone-alt"></i> <strong>Datos del Recepcionista</strong>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Turno</label>
                                    <select name="turno" class="form-control">
                                        <option value="manana">Mañana (08:00 - 14:00)</option>
                                        <option value="tarde">Tarde (14:00 - 20:00)</option>
                                        <option value="completo">Completo (08:00 - 20:00)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Caja asignada</label>
                                    <input type="text" name="caja_asignada" class="form-control" placeholder="Ej: Caja 1">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Opción de enviar email -->
                        <hr>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="enviar_email" class="form-check-input" id="enviar_email" checked>
                            <label class="form-check-label" for="enviar_email">
                                <i class="fas fa-envelope"></i> Enviar credenciales por email
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus"></i> Crear Usuario
                        </button>
                        <a href="users.php" class="btn btn-secondary w-100 mt-2">Cancelar</a>
                    </form>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar campos según rol seleccionado
document.getElementById('rol_id').addEventListener('change', function() {
    // Ocultar todos
    document.getElementById('campos_cliente').classList.remove('active');
    document.getElementById('campos_groomer').classList.remove('active');
    document.getElementById('campos_recepcion').classList.remove('active');
    
    var rol = this.value;
    if(rol == '4') { // Cliente
        document.getElementById('campos_cliente').classList.add('active');
    } else if(rol == '3') { // Groomer
        document.getElementById('campos_groomer').classList.add('active');
    } else if(rol == '2') { // Recepción
        document.getElementById('campos_recepcion').classList.add('active');
    }
});

function copiarPassword(password) {
    navigator.clipboard.writeText(password).then(function() {
        alert('Contraseña copiada al portapapeles');
    }, function() {
        alert('Error al copiar la contraseña');
    });
}

// Inicializar por si hay selección previa
document.addEventListener('DOMContentLoaded', function() {
    if(document.getElementById('rol_id').value) {
        var event = new Event('change');
        document.getElementById('rol_id').dispatchEvent(event);
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
require_once 'config/config.php';

if(!Auth::checkAuth()){
    redirect('login.php');
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    
    // Verificar contraseña actual
    $stmt = $db->prepare("SELECT password_hash FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $usuario = $stmt->fetch();
    
    if(!password_verify($password_actual, $usuario['password_hash'])){
        $error = "Contraseña actual incorrecta";
    } elseif(strlen($password_nueva) < 8){
        $error = "La nueva contraseña debe tener al menos 8 caracteres";
    } elseif($password_nueva != $password_confirmar){
        $error = "Las contraseñas nuevas no coinciden";
    } else {
        // Verificar fuerza de contraseña
        $strength = Security::checkPasswordStrength($password_nueva);
        if($strength['strength'] == 'weak'){
            $error = "Contraseña débil: " . implode(', ', $strength['feedback']);
        } else {
            // Actualizar contraseña
            $new_hash = password_hash($password_nueva, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE usuarios SET password_hash = :password WHERE id = :id");
            $stmt->execute([':password' => $new_hash, ':id' => $user_id]);
            
            // Registrar en auditoría
            AuditLog::log($user_id, $_SESSION['user_role'], 'PASSWORD_CAMBIADA', "Usuario cambió su contraseña", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            
            $success = "Contraseña actualizada correctamente";
            
            // Opcional: cerrar sesión después de cambiar contraseña
            // $auth = new Auth();
            // $auth->logout();
            // redirect('login.php?msg=Contraseña actualizada, inicia sesión nuevamente');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .password-box {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
        .strength-bar {
            height: 5px;
            border-radius: 5px;
            transition: all 0.3s;
            margin-top: 5px;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0"><i class="fas fa-key"></i> Cambiar Contraseña</h4>
                    <small><?php echo $_SESSION['user_email']; ?></small>
                </div>
                <div class="card-body p-4">
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <div class="text-center mt-3">
                            <a href="dashboard.php" class="btn btn-primary">Volver al Dashboard</a>
                        </div>
                    <?php else: ?>
                    
                    <form method="POST" id="passwordForm">
                        <div class="mb-3">
                            <label class="form-label">Contraseña actual</label>
                            <div class="password-box">
                                <input type="password" name="password_actual" id="password_actual" class="form-control" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('password_actual')"></i>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nueva contraseña</label>
                            <div class="password-box">
                                <input type="password" name="password_nueva" id="password_nueva" class="form-control" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('password_nueva')"></i>
                            </div>
                            <div id="strength-bar" class="strength-bar" style="width: 0%;"></div>
                            <small id="strength-text" class="text-muted"></small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirmar nueva contraseña</label>
                            <div class="password-box">
                                <input type="password" name="password_confirmar" id="password_confirmar" class="form-control" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('password_confirmar')"></i>
                            </div>
                            <small id="match-text" class="text-muted"></small>
                        </div>
                        
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle"></i> 
                            Requisitos: Mínimo 8 caracteres, mayúsculas, minúsculas, números y símbolos.
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Cambiar Contraseña
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary w-100 mt-2">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    </form>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar contraseña
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling;
    if(field.type === 'password'){
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Medidor de fuerza de contraseña
document.getElementById('password_nueva').addEventListener('input', function(){
    const password = this.value;
    let score = 0;
    let feedback = '';
    
    if(password.length >= 8) score++;
    if(password.match(/[a-z]/)) score++;
    if(password.match(/[A-Z]/)) score++;
    if(password.match(/[0-9]/)) score++;
    if(password.match(/[^a-zA-Z0-9]/)) score++;
    
    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');
    
    if(password.length === 0){
        bar.style.width = '0%';
        bar.style.backgroundColor = '';
        text.textContent = '';
        return;
    }
    
    if(score <= 2){
        bar.style.width = '33%';
        bar.style.backgroundColor = '#dc3545';
        text.textContent = 'Débil - ' + getFeedback(score);
        text.style.color = '#dc3545';
    } else if(score <= 4){
        bar.style.width = '66%';
        bar.style.backgroundColor = '#ffc107';
        text.textContent = 'Media - Añade más variedad';
        text.style.color = '#ffc107';
    } else {
        bar.style.width = '100%';
        bar.style.backgroundColor = '#28a745';
        text.textContent = 'Fuerte - ¡Excelente!';
        text.style.color = '#28a745';
    }
});

function getFeedback(score){
    if(score < 2) return 'Agrega números y símbolos';
    return 'Añade mayúsculas y símbolos';
}

// Verificar coincidencia de contraseñas
document.getElementById('password_confirmar').addEventListener('input', function(){
    const password = document.getElementById('password_nueva').value;
    const confirm = this.value;
    const matchText = document.getElementById('match-text');
    
    if(confirm.length === 0){
        matchText.textContent = '';
        return;
    }
    
    if(password === confirm){
        matchText.textContent = '✓ Las contraseñas coinciden';
        matchText.style.color = '#28a745';
    } else {
        matchText.textContent = '✗ Las contraseñas no coinciden';
        matchText.style.color = '#dc3545';
    }
});

// Validar antes de enviar
document.getElementById('passwordForm').addEventListener('submit', function(e){
    const password = document.getElementById('password_nueva').value;
    const confirm = document.getElementById('password_confirmar').value;
    
    if(password !== confirm){
        e.preventDefault();
        alert('Las contraseñas nuevas no coinciden');
        return false;
    }
    
    if(password.length < 8){
        e.preventDefault();
        alert('La contraseña debe tener al menos 8 caracteres');
        return false;
    }
});
</script>
</body>
</html>
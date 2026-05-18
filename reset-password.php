<?php
require_once 'config/config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Verificar token
if (empty($token)) {
    $error = 'Token de recuperación no válido';
} else {
    $db = Database::getInstance()->getConnection();
    
    // Buscar token válido
    $stmt = $db->prepare("
        SELECT id, email, token_recuperacion, token_rec_expiracion 
        FROM usuarios 
        WHERE token_recuperacion = :token 
        AND token_rec_expiracion > NOW()
    ");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = 'El enlace de recuperación ha expirado o es inválido. Solicita uno nuevo.';
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Por favor completa todos los campos';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        // Validar fortaleza
        $strength = Security::checkPasswordStrength($password);
        if ($strength['strength'] === 'weak') {
            $error = 'Contraseña muy débil: ' . implode(', ', $strength['feedback']);
        } else {
            // Actualizar contraseña
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            
            $stmt = $db->prepare("
                UPDATE usuarios 
                SET password_hash = :password,
                    token_recuperacion = NULL,
                    token_rec_expiracion = NULL,
                    intentos_fallidos = 0,
                    estado = 'activo'
                WHERE id = :id
            ");
            $stmt->execute([
                ':password' => $passwordHash,
                ':id' => $user['id']
            ]);
            
            AuditLog::log($user['id'], null, 'PASSWORD_RESTABLECIDA', "Contraseña restablecida exitosamente", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            
            $success = 'Tu contraseña ha sido actualizada exitosamente. Ahora puedes iniciar sesión.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="assets/js/password-meter.js" defer></script>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0">🔑 Restablecer Contraseña</h4>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <div class="text-center mt-3">
                                <a href="forgot-password.php" class="btn btn-primary">Solicitar nuevo enlace</a>
                                <br>
                                <a href="login.php" class="btn btn-link mt-2">Volver al inicio</a>
                            </div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-primary">Iniciar Sesión</a>
                            </div>
                        <?php elseif ($user): ?>
                            <p class="text-muted mb-4">
                                Ingresa tu nueva contraseña para la cuenta: <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                            </p>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nueva contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div id="password-strength" class="mt-2">
                                        <div class="progress" style="height: 5px;">
                                            <div id="strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small id="strength-text" class="text-muted"></small>
                                    </div>
                                    <small class="text-muted">Mínimo 8 caracteres con mayúsculas, minúsculas, números y símbolos</small>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Actualizar contraseña</button>
                            </form>
                            
                            <hr>
                            <div class="text-center">
                                <a href="login.php">← Volver al inicio de sesión</a>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
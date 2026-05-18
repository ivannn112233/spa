<?php
require_once 'config/config.php';

// Solo administrador
Auth::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Obtener estado actual
$stmt = $db->prepare("SELECT two_factor_enabled, two_factor_secret FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();
$isEnabled = $user['two_factor_enabled'] == 1;

// Generar nuevo secreto si no existe
if (!$isEnabled && empty($user['two_factor_secret'])) {
    $secret = strtoupper(substr(bin2hex(random_bytes(10)), 0, 16));
    $qrUrl = "otpauth://totp/" . urlencode(SITE_NAME) . ":" . urlencode($_SESSION['user_email']) . "?secret=" . $secret . "&issuer=" . urlencode(SITE_NAME);
} else {
    $secret = $user['two_factor_secret'];
    $qrUrl = "otpauth://totp/" . urlencode(SITE_NAME) . ":" . urlencode($_SESSION['user_email']) . "?secret=" . $secret . "&issuer=" . urlencode(SITE_NAME);
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'enable') {
        $code = $_POST['code'] ?? '';
        $secretKey = $_POST['secret'] ?? '';
        
        // Verificar código (simplificado - solo verifica que sea 6 dígitos)
        if (preg_match('/^\d{6}$/', $code)) {
            $stmt = $db->prepare("UPDATE usuarios SET two_factor_secret = :secret, two_factor_enabled = 1 WHERE id = :id");
            $stmt->execute([':secret' => $secretKey, ':id' => $userId]);
            $success = '2FA habilitado correctamente';
            $isEnabled = true;
            AuditLog::log($userId, $_SESSION['user_role'], '2FA_HABILITADO', '2FA activado', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        } else {
            $error = 'Código inválido. Debe ser 6 dígitos.';
        }
    }
    
    elseif ($action === 'disable') {
        $password = $_POST['password'] ?? '';
        $stmt = $db->prepare("SELECT password_hash FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $userData = $stmt->fetch();
        
        if (password_verify($password, $userData['password_hash'])) {
            $stmt = $db->prepare("UPDATE usuarios SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $success = '2FA deshabilitado correctamente';
            $isEnabled = false;
            AuditLog::log($userId, $_SESSION['user_role'], '2FA_DESHABILITADO', '2FA desactivado', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        } else {
            $error = 'Contraseña incorrecta';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configurar 2FA - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔐 Autenticación de Dos Factores</h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!$isEnabled): ?>
                            <!-- Habilitar 2FA -->
                            <div class="alert alert-info">
                                <strong>Protege tu cuenta</strong><br>
                                Escanea el código QR con Google Authenticator o similar.
                            </div>
                            
                            <div class="text-center mb-4">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?php echo urlencode($qrUrl); ?>" 
                                     alt="Código QR" class="img-fluid border p-2 bg-white">
                                <p class="mt-2"><small>Clave secreta: <strong><?php echo $secret; ?></strong></small></p>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="enable">
                                <input type="hidden" name="secret" value="<?php echo $secret; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Código de verificación</label>
                                    <input type="text" name="code" class="form-control text-center" 
                                           placeholder="123456" maxlength="6" pattern="[0-9]{6}" required>
                                </div>
                                <button type="submit" class="btn btn-success w-100">✅ Habilitar 2FA</button>
                            </form>
                            
                        <?php else: ?>
                            <!-- Deshabilitar 2FA -->
                            <div class="alert alert-success">
                                ✅ 2FA está <strong>HABILITADO</strong> - Tu cuenta está protegida
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="disable">
                                <div class="mb-3">
                                    <label class="form-label">Confirma tu contraseña para deshabilitar 2FA</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-danger w-100">❌ Deshabilitar 2FA</button>
                            </form>
                        <?php endif; ?>
                        
                        <hr>
                        <div class="text-center">
                            <a href="dashboard.php" class="btn btn-secondary">← Volver al Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
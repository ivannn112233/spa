<?php
require_once 'config/config.php';

// Si ya está logueado, redirigir
if (Auth::checkAuth()) {
    redirect('dashboard.php');
}

$email = $_GET['email'] ?? $_SESSION['temp_email'] ?? '';
$error = '';
$success = '';

// Si viene de registro, guardar email temporal
if(isset($_GET['email'])){
    $_SESSION['temp_email'] = $_GET['email'];
    $email = $_GET['email'];
}

// Procesar verificación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    
    if(empty($email) || empty($codigo)){
        $error = 'Email y código son requeridos';
    } else {
        // Llamar a la API para verificar
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, SITE_URL . 'api/verificar_email.php?action=verificar_codigo');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['email' => $email, 'codigo' => $codigo]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if($httpCode == 200 && $response){
            $data = json_decode($response, true);
            if($data['success']){
                $success = $data['message'];
                unset($_SESSION['temp_email']);
                setFlashMessage('success', $data['message']);
                echo '<script>setTimeout(function(){ window.location.href = "login.php"; }, 2000);</script>';
            } else {
                $error = $data['message'];
            }
        } else {
            $error = 'Error al conectar con el servidor';
        }
    }
}

// Reenviar código
if (isset($_GET['reenviar'])) {
    $email = $_GET['email'] ?? $_SESSION['temp_email'] ?? '';
    
    if($email){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, SITE_URL . 'api/verificar_email.php?action=reenviar_codigo');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['email' => $email]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        if($data['success']){
            setFlashMessage('success', 'Código reenviado a tu correo');
        } else {
            setFlashMessage('danger', $data['message']);
        }
        header("Location: activate.php?email=" . urlencode($email));
        exit;
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Cuenta - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .code-input {
            font-size: 2rem;
            text-align: center;
            letter-spacing: 10px;
            font-family: monospace;
        }
        .verification-icon {
            font-size: 4rem;
            color: #3498db;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-5">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0">🐾 Verificar Cuenta</h4>
                        <small>Pet Spa Grooming</small>
                    </div>
                    <div class="card-body p-4 text-center">
                        
                        <?php if($flash): ?>
                            <div class="alert alert-<?php echo $flash['type']; ?>">
                                <?php echo htmlspecialchars($flash['message']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle fa-2x"></i>
                                <p class="mt-2"><?php echo htmlspecialchars($success); ?></p>
                                <p>Redirigiendo al inicio de sesión...</p>
                            </div>
                        <?php else: ?>
                            
                            <div class="verification-icon mb-3">
                                <i class="fas fa-envelope-open-text"></i>
                            </div>
                            
                            <h5>Verifica tu dirección de correo</h5>
                            <p class="text-muted">
                                Hemos enviado un código de verificación a:<br>
                                <strong><?php echo htmlspecialchars($email ?: 'tu correo electrónico'); ?></strong>
                            </p>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                Revisa tu bandeja de entrada y SPAM. El código expira en 10 minutos.
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Código de verificación</label>
                                    <input type="text" 
                                           name="codigo" 
                                           class="form-control code-input" 
                                           placeholder="000000"
                                           maxlength="6"
                                           pattern="[0-9]{6}"
                                           required
                                           autofocus>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-check"></i> Verificar Cuenta
                                </button>
                            </form>
                            
                            <hr>
                            
                            <div class="text-center">
                                <p class="mb-2">
                                    ¿No recibiste el código?
                                    <a href="activate.php?reenviar=1&email=<?php echo urlencode($email); ?>">
                                        Reenviar código
                                    </a>
                                </p>
                                <p class="mb-0">
                                    <a href="register.php">
                                        <i class="fas fa-arrow-left"></i> Volver al registro
                                    </a>
                                </p>
                            </div>
                            
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('input[name="codigo"]')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    </script>
</body>
</html>
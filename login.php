<?php
require_once 'config/config.php';

// Si ya está logueado, redirigir según su rol
if (Auth::checkAuth()) {
    switch ($_SESSION['user_role']) {
        case ROLE_ADMIN: redirect('admin/index.php'); break;
        case ROLE_RECEPCION: redirect('recepcion/index.php'); break;
        case ROLE_GROOMER: redirect('groomer/index.php'); break;
        case ROLE_CLIENTE: redirect('cliente/index.php'); break;
        default: redirect('dashboard.php');
    }
    exit;
}

$error = '';
$requires2fa = false;
$email_recordado = $_COOKIE['recordar_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $twoFactorCode = $_POST['2fa_code'] ?? '';
    
    $auth = new Auth();
    
    if (!empty($twoFactorCode) && isset($_SESSION['2fa_pending'])) {
        if ($auth->verify2FA($twoFactorCode)) {
            switch ($_SESSION['user_role']) {
                case ROLE_ADMIN: redirect('admin/index.php'); break;
                case ROLE_RECEPCION: redirect('recepcion/index.php'); break;
                case ROLE_GROOMER: redirect('groomer/index.php'); break;
                case ROLE_CLIENTE: redirect('cliente/index.php'); break;
                default: redirect('dashboard.php');
            }
        } else {
            $error = 'Código 2FA incorrecto';
        }
    } else {
        if($remember){
            setcookie('recordar_email', $email, time() + (86400 * 30), '/');
        } else {
            setcookie('recordar_email', '', time() - 3600, '/');
        }
        
        $result = $auth->login($email, $password, $remember);
        
        if ($result['success']) {
            if (isset($result['requires_2fa']) && $result['requires_2fa']) {
                $requires2fa = true;
            } else {
                switch ($_SESSION['user_role']) {
                    case ROLE_ADMIN: redirect('admin/index.php'); break;
                    case ROLE_RECEPCION: redirect('recepcion/index.php'); break;
                    case ROLE_GROOMER: redirect('groomer/index.php'); break;
                    case ROLE_CLIENTE: redirect('cliente/index.php'); break;
                    default: redirect('dashboard.php');
                }
            }
        } else {
            $error = $result['error'];
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Pet Spa Grooming</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        
        .card {
            border-radius: 20px;
            overflow: hidden;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            animation: fadeInUp 0.6s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            padding: 35px 30px;
            border-bottom: none;
        }
        
        .card-header h4 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .card-header small {
            opacity: 0.9;
            font-size: 0.85rem;
        }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group-custom i.input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            z-index: 10;
            font-size: 1.1rem;
        }
        
        .input-group-custom input {
            padding-left: 45px;
            height: 52px;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .input-group-custom input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #95a5a6;
            z-index: 10;
            transition: color 0.3s;
        }
        
        .toggle-password:hover {
            color: #3498db;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border: none;
            height: 52px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-volver {
            position: fixed;
            bottom: 25px;
            left: 25px;
            z-index: 1000;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50px;
            padding: 12px 24px;
            transition: all 0.3s ease;
            color: white;
            font-weight: 500;
            text-decoration: none;
        }
        
        .btn-volver:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-3px);
            color: white;
        }
        
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
        }
        
        .alert-custom i {
            margin-right: 10px;
        }
        
        .forgot-link {
            color: #3498db;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s;
        }
        
        .forgot-link:hover {
            color: #2c3e50;
            text-decoration: underline;
        }
        
        .register-link {
            color: #3498db;
            font-weight: 600;
            text-decoration: none;
        }
        
        .register-link:hover {
            text-decoration: underline;
        }
        
        /* Animación de carga */
        .btn-login .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .btn-login.loading .spinner {
            display: inline-block;
        }
        
        .btn-login.loading .btn-text {
            display: none;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .card-header {
                padding: 25px 20px;
            }
            .card-body {
                padding: 25px !important;
            }
            .btn-volver {
                bottom: 15px;
                left: 15px;
                padding: 8px 16px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header text-white text-center">
                        <div class="mb-2" style="font-size: 3rem;">🐾</div>
                        <h4 class="mb-0">Pet Spa Grooming</h4>
                        <small>Bienvenido de vuelta</small>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($flash): ?>
                            <div class="alert alert-custom alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                                <i class="fas fa-<?php echo $flash['type']=='success'?'check-circle':'exclamation-triangle'; ?>"></i>
                                <?php echo htmlspecialchars($flash['message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-custom alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($requires2fa): ?>
                            <div class="alert alert-custom alert-info">
                                <i class="fas fa-shield-alt"></i>
                                <strong>Verificación de dos factores</strong><br>
                                Ingresa el código de autenticación de Google Authenticator.
                            </div>
                            <form method="POST" id="loginForm">
                                <div class="input-group-custom">
                                    <i class="fas fa-key input-icon"></i>
                                    <input type="text" class="form-control" id="2fa_code" name="2fa_code" 
                                           placeholder="Código de verificación" maxlength="6" required autofocus>
                                </div>
                                <button type="submit" class="btn btn-login w-100 text-white" id="btnLogin2FA">
                                    <span class="spinner"></span>
                                    <span class="btn-text">Verificar</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" id="loginForm">
                                <div class="input-group-custom">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? $email_recordado); ?>" 
                                           placeholder="Correo electrónico" required autofocus>
                                </div>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Contraseña" required>
                                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                        <label class="form-check-label small" for="remember">
                                            <i class="fas fa-user-check"></i> Recordarme
                                        </label>
                                    </div>
                                    <a href="forgot-password.php" class="forgot-link">
                                        <i class="fas fa-question-circle"></i> ¿Olvidaste tu contraseña?
                                    </a>
                                </div>
                                
                                <button type="submit" class="btn btn-login w-100 text-white" id="btnLogin">
                                    <span class="spinner"></span>
                                    <span class="btn-text">Iniciar Sesión</span>
                                </button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="mb-0 small">
                                    ¿No tienes una cuenta? 
                                    <a href="register.php" class="register-link">
                                        <i class="fas fa-user-plus"></i> Regístrate aquí
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Botón para volver al index -->
    <a href="index.php" class="btn btn-volver">
        <i class="fas fa-arrow-left"></i> Volver al Inicio
    </a>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar contraseña
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.querySelector('.toggle-password');
            if(password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Auto-enfocar en 2fa_code si existe
        if(document.getElementById('2fa_code')) {
            document.getElementById('2fa_code').focus();
        }
        
        // Validar email antes de enviar y mostrar loading
        const loginForm = document.getElementById('loginForm');
        if(loginForm) {
            loginForm.addEventListener('submit', function(e) {
                const email = document.getElementById('email');
                const password = document.getElementById('password');
                const btn = document.getElementById('btnLogin');
                
                if(email && password) {
                    if(!email.value || !password.value) {
                        e.preventDefault();
                        alert('Por favor completa todos los campos');
                        return false;
                    }
                    
                    // Mostrar loading
                    if(btn) {
                        btn.classList.add('loading');
                        btn.disabled = true;
                    }
                }
            });
        }
        
        // También para el botón de 2FA
        const btn2FA = document.getElementById('btnLogin2FA');
        if(btn2FA) {
            btn2FA.addEventListener('click', function() {
                this.classList.add('loading');
                this.disabled = true;
            });
        }
        
        // Animación al cargar
        document.querySelector('.card').style.opacity = '0';
        setTimeout(() => {
            document.querySelector('.card').style.opacity = '1';
        }, 100);
    </script>
</body>
</html>
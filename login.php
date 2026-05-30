<?php
require_once 'config/config.php';

// Si ya está logueado, redirigir directamente según su rol
if (Auth::checkAuth()) {
    switch ($_SESSION['user_role']) {
        case ROLE_ADMIN:
            redirect('admin/index.php');
            break;
        case ROLE_RECEPCION:
            redirect('recepcion/index.php');
            break;
        case ROLE_GROOMER:
            redirect('groomer/index.php');
            break;
        case ROLE_CLIENTE:
            redirect('cliente/index.php');
            break;
        default:
            redirect('dashboard.php');
            break;
    }
    exit;
}

$error = '';
$requires2fa = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $twoFactorCode = $_POST['2fa_code'] ?? '';
    
    $auth = new Auth();
    
    if (!empty($twoFactorCode) && isset($_SESSION['2fa_pending'])) {
        if ($auth->verify2FA($twoFactorCode)) {
            // Redirigir según rol después de 2FA
            switch ($_SESSION['user_role']) {
                case ROLE_ADMIN: redirect('admin/index.php'); break;
                case ROLE_RECEPCION: redirect('recepcion/index.php'); break;
                case ROLE_GROOMER: redirect('groomer/index.php'); break;
                case ROLE_CLIENTE: redirect('cliente/index.php'); break;
                default: redirect('dashboard.php'); break;
            }
        } else {
            $error = 'Código 2FA incorrecto';
        }
    } else {
        $result = $auth->login($email, $password, $remember);
        
        if ($result['success']) {
            if (isset($result['requires_2fa']) && $result['requires_2fa']) {
                $requires2fa = true;
            } else {
                // Redirigir según rol
                switch ($_SESSION['user_role']) {
                    case ROLE_ADMIN:
                        redirect('admin/index.php');
                        break;
                    case ROLE_RECEPCION:
                        redirect('recepcion/index.php');
                        break;
                    case ROLE_GROOMER:
                        redirect('groomer/index.php');
                        break;
                    case ROLE_CLIENTE:
                        redirect('cliente/index.php');
                        break;
                    default:
                        redirect('dashboard.php');
                        break;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: fadeInUp 0.6s ease; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .card-header { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); padding: 35px 30px; border-bottom: none; }
        .btn-volver { position: fixed; bottom: 20px; left: 20px; z-index: 1000; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: none; border-radius: 50px; padding: 12px 24px; color: white; text-decoration: none; }
        .btn-volver:hover { background: rgba(255,255,255,0.3); transform: translateY(-3px); color: white; }
        .input-group-custom { position: relative; margin-bottom: 20px; }
        .input-group-custom i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #95a5a6; z-index: 10; }
        .input-group-custom input { padding-left: 45px; height: 52px; border-radius: 12px; border: 1px solid #e0e0e0; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #95a5a6; }
        .btn-login { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); border: none; height: 52px; border-radius: 12px; font-weight: 600; transition: all 0.3s; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(52,152,219,0.4); }
        @media (max-width: 768px) { .btn-volver { bottom: 15px; left: 15px; padding: 8px 16px; font-size: 0.85rem; } }
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
                            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show"><?php echo htmlspecialchars($flash['message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($requires2fa): ?>
                            <div class="alert alert-info"><strong>Verificación de dos factores</strong><br>Ingresa el código de autenticación de Google Authenticator.</div>
                            <form method="POST">
                                <div class="input-group-custom"><i class="fas fa-key"></i><input type="text" class="form-control" id="2fa_code" name="2fa_code" placeholder="Código de verificación" maxlength="6" required autofocus></div>
                                <button type="submit" class="btn btn-login w-100 text-white">Verificar</button>
                            </form>
                        <?php else: ?>
                            <form method="POST">
                                <div class="input-group-custom"><i class="fas fa-envelope"></i><input type="email" class="form-control" id="email" name="email" placeholder="Correo electrónico" required autofocus></div>
                                <div class="input-group-custom"><i class="fas fa-lock"></i><input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required><i class="fas fa-eye toggle-password" onclick="togglePassword()"></i></div>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check"><input type="checkbox" class="form-check-input" id="remember" name="remember"><label class="form-check-label small" for="remember">Recordarme</label></div>
                                    <a href="forgot-password.php" class="small">¿Olvidaste tu contraseña?</a>
                                </div>
                                <button type="submit" class="btn btn-login w-100 text-white">Iniciar Sesión</button>
                            </form>
                            <hr>
                            <div class="text-center"><p class="mb-0 small">¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <a href="index.php" class="btn btn-volver"><i class="fas fa-arrow-left"></i> Volver al Inicio</a>
    <script>
        function togglePassword() { const p = document.getElementById('password'); const i = document.querySelector('.toggle-password'); if(p.type === 'password') { p.type = 'text'; i.classList.remove('fa-eye'); i.classList.add('fa-eye-slash'); } else { p.type = 'password'; i.classList.remove('fa-eye-slash'); i.classList.add('fa-eye'); } }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
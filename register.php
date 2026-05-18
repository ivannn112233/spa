<?php
require_once 'config/config.php';

if (Auth::checkAuth()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$email_registrado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'nombre' => $_POST['nombre'] ?? '',
        'apellido' => $_POST['apellido'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'ci' => $_POST['ci'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'canal_notificacion' => $_POST['canal_notificacion'] ?? 'email'
    ];
    
    if (empty($data['email']) || empty($data['password']) || empty($data['nombre']) || empty($data['apellido'])) {
        $error = 'Todos los campos marcados con * son obligatorios';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $error = 'Las contraseñas no coinciden';
    } else {
        $auth = new Auth();
        $result = $auth->registerCliente($data);
        
        if ($result['success']) {
            $email_registrado = $data['email'];
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Pet Spa Grooming</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 20px;
            overflow: hidden;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeIn 0.6s ease;
        }
        
        .card-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 25px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group-custom i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            z-index: 10;
        }
        
        .input-group-custom input, .input-group-custom select, .input-group-custom textarea {
            padding-left: 45px;
            border-radius: 10px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        .input-group-custom input:focus, .input-group-custom select:focus, .input-group-custom textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #95a5a6;
            z-index: 10;
        }
        
        .toggle-password:hover {
            color: #3498db;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            border: none;
            height: 50px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 8px;
            transition: all 0.3s;
        }
        
        .strength-text {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 20px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #3498db;
            display: inline-block;
        }
        
        .info-badge {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }
        
        .alert-custom {
            border-radius: 10px;
            border-left: 4px solid;
        }
        
        .btn-volver {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border: none;
            transition: all 0.3s;
        }
        
        .btn-volver:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-3px);
        }
        
        .requisitos {
            font-size: 0.75rem;
            margin-top: 5px;
        }
        
        .requisitos i {
            width: 16px;
            margin-right: 5px;
        }
        
        .requisitos .valid { color: #27ae60; }
        .requisitos .invalid { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow">
                    <div class="card-header text-white text-center">
                        <div style="font-size: 2.5rem;">🐾</div>
                        <h3 class="mb-0">Crear una cuenta</h3>
                        <small>Regístrate como cliente de Pet Spa</small>
                    </div>
                    <div class="card-body p-4">
                        <?php if($error): ?>
                            <div class="alert alert-custom alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($email_registrado): ?>
                            <div class="alert alert-custom alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="activate.php?email=<?php echo urlencode($email_registrado); ?>" class="btn btn-register text-white">
                                    <i class="fas fa-envelope"></i> Verificar mi cuenta
                                </a>
                                <br>
                                <a href="login.php" class="btn btn-link mt-2">← Volver al inicio de sesión</a>
                            </div>
                        <?php else: ?>
                            <div class="info-badge">
                                <i class="fas fa-info-circle text-info"></i>
                                Completa el formulario para crear tu cuenta. Recibirás un código de verificación por email.
                            </div>
                            
                            <form method="POST" id="registerForm">
                                <h5 class="section-title">
                                    <i class="fas fa-laptop"></i> Datos de acceso
                                </h5>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" id="email" class="form-control" 
                                           placeholder="Correo electrónico *" required>
                                </div>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="password" id="password" class="form-control" 
                                           placeholder="Contraseña *" required>
                                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
                                </div>
                                
                                <!-- Medidor de fuerza de contraseña -->
                                <div class="password-strength" id="strengthBar"></div>
                                <div class="strength-text" id="strengthText"></div>
                                
                                <div class="requisitos mb-3" id="requisitos">
                                    <div id="req-length"><i class="fas fa-circle"></i> Mínimo 8 caracteres</div>
                                    <div id="req-mayus"><i class="fas fa-circle"></i> Al menos una mayúscula</div>
                                    <div id="req-minus"><i class="fas fa-circle"></i> Al menos una minúscula</div>
                                    <div id="req-num"><i class="fas fa-circle"></i> Al menos un número</div>
                                    <div id="req-simbol"><i class="fas fa-circle"></i> Al menos un símbolo (!@#$%)</div>
                                </div>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                                           placeholder="Confirmar contraseña *" required>
                                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                                </div>
                                <div id="matchMsg" class="small mb-3"></div>
                                
                                <h5 class="section-title">
                                    <i class="fas fa-user"></i> Datos personales
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="input-group-custom">
                                            <i class="fas fa-user"></i>
                                            <input type="text" name="nombre" class="form-control" 
                                                   placeholder="Nombre *" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group-custom">
                                            <i class="fas fa-user"></i>
                                            <input type="text" name="apellido" class="form-control" 
                                                   placeholder="Apellido *" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" name="telefono" class="form-control" 
                                           placeholder="Teléfono *" required>
                                </div>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-id-card"></i>
                                    <input type="text" name="ci" class="form-control" 
                                           placeholder="Carnet de Identidad">
                                </div>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <textarea name="direccion" class="form-control" rows="2" 
                                              placeholder="Dirección"></textarea>
                                </div>
                                
                                <div class="input-group-custom">
                                    <i class="fas fa-bell"></i>
                                    <select name="canal_notificacion" class="form-control">
                                        <option value="email">📧 Email</option>
                                        <option value="whatsapp">📱 WhatsApp</option>
                                        <option value="telegram">✈️ Telegram</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-register w-100 text-white" id="btnRegister">
                                    <i class="fas fa-user-plus"></i> Registrarse
                                </button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="mb-0">
                                    ¿Ya tienes cuenta? 
                                    <a href="login.php" class="fw-bold">Inicia Sesión aquí</a>
                                </p>
                                <a href="index.php" class="text-muted small">
                                    ← Volver al inicio
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Botón para volver al index -->
    <a href="index.php" class="btn btn-volver text-white">
        <i class="fas fa-arrow-left"></i> Volver al Inicio
    </a>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar contraseña
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            if(field.type === 'password') {
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
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        function checkPasswordStrength(password) {
            let score = 0;
            let checks = {
                length: password.length >= 8,
                mayus: /[A-Z]/.test(password),
                minus: /[a-z]/.test(password),
                num: /[0-9]/.test(password),
                simbol: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };
            
            // Actualizar requisitos visuales
            document.getElementById('req-length').innerHTML = checks.length ? '<i class="fas fa-check-circle valid"></i> Mínimo 8 caracteres' : '<i class="fas fa-circle invalid"></i> Mínimo 8 caracteres';
            document.getElementById('req-mayus').innerHTML = checks.mayus ? '<i class="fas fa-check-circle valid"></i> Al menos una mayúscula' : '<i class="fas fa-circle invalid"></i> Al menos una mayúscula';
            document.getElementById('req-minus').innerHTML = checks.minus ? '<i class="fas fa-check-circle valid"></i> Al menos una minúscula' : '<i class="fas fa-circle invalid"></i> Al menos una minúscula';
            document.getElementById('req-num').innerHTML = checks.num ? '<i class="fas fa-check-circle valid"></i> Al menos un número' : '<i class="fas fa-circle invalid"></i> Al menos un número';
            document.getElementById('req-simbol').innerHTML = checks.simbol ? '<i class="fas fa-check-circle valid"></i> Al menos un símbolo' : '<i class="fas fa-circle invalid"></i> Al menos un símbolo';
            
            // Calcular score
            if(checks.length) score++;
            if(checks.mayus) score++;
            if(checks.minus) score++;
            if(checks.num) score++;
            if(checks.simbol) score++;
            
            // Actualizar barra
            if(password.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = '';
                strengthText.textContent = '';
                return;
            }
            
            if(score <= 2) {
                strengthBar.style.width = '33%';
                strengthBar.style.backgroundColor = '#e74c3c';
                strengthText.innerHTML = '<span style="color: #e74c3c;">🔴 Contraseña débil</span>';
            } else if(score <= 4) {
                strengthBar.style.width = '66%';
                strengthBar.style.backgroundColor = '#f39c12';
                strengthText.innerHTML = '<span style="color: #f39c12;">🟡 Contraseña media</span>';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#27ae60';
                strengthText.innerHTML = '<span style="color: #27ae60;">🟢 Contraseña fuerte</span>';
            }
        }
        
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
        
        // Verificar coincidencia de contraseñas
        const confirmInput = document.getElementById('confirm_password');
        const matchMsg = document.getElementById('matchMsg');
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if(confirm.length === 0) {
                matchMsg.innerHTML = '';
                return;
            }
            
            if(password === confirm) {
                matchMsg.innerHTML = '<i class="fas fa-check-circle text-success"></i> <span class="text-success">Las contraseñas coinciden</span>';
            } else {
                matchMsg.innerHTML = '<i class="fas fa-times-circle text-danger"></i> <span class="text-danger">Las contraseñas no coinciden</span>';
            }
        }
        
        confirmInput.addEventListener('input', checkPasswordMatch);
        
        // Validar antes de enviar
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            // Verificar fuerza de contraseña
            let score = 0;
            if(password.length >= 8) score++;
            if(/[A-Z]/.test(password)) score++;
            if(/[a-z]/.test(password)) score++;
            if(/[0-9]/.test(password)) score++;
            if(/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
            
            if(score < 3) {
                e.preventDefault();
                alert('La contraseña es demasiado débil. Usa al menos 8 caracteres con mayúsculas, minúsculas, números y símbolos.');
                return false;
            }
            
            if(password !== confirm) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            // Deshabilitar botón para evitar doble envío
            const btn = document.getElementById('btnRegister');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
        });
    </script>
</body>
</html>
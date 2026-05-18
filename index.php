<?php
/**
 * Punto de entrada principal del sistema Pet Spa Grooming
 * Redirige según estado de autenticación y rol del usuario
 */

require_once 'config/config.php';

// Verificar si el usuario está autenticado
if (Auth::checkAuth()) {
    // Verificar si la sesión es válida (tiempo de inactividad)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        // Sesión expirada
        $auth = new Auth();
        $auth->logout();
        setFlashMessage('warning', 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.');
        redirect('login.php');
    }
    
    // Actualizar último actividad
    $_SESSION['last_activity'] = time();
    
    // Redirigir según el rol del usuario
    switch ($_SESSION['user_role']) {
        case ROLE_ADMIN:
            redirect('dashboard.php');
            break;
        case ROLE_RECEPCION:
            redirect('dashboard.php');
            break;
        case ROLE_GROOMER:
            redirect('dashboard.php');
            break;
        case ROLE_CLIENTE:
            redirect('dashboard.php');
            break;
        default:
            // Rol no reconocido, cerrar sesión
            $auth = new Auth();
            $auth->logout();
            redirect('login.php');
            break;
    }
} else {
    // Verificar si hay cookie de "recordarme"
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $db = Database::getInstance()->getConnection();
        
        // Buscar sesión activa con este token
        $stmt = $db->prepare("
            SELECT u.*, r.nombre as rol_nombre 
            FROM sesiones_usuario s
            JOIN usuarios u ON s.usuario_id = u.id
            JOIN roles r ON u.rol_id = r.id
            WHERE s.refresh_token = :token 
            AND s.fecha_expiracion > NOW()
            AND u.estado = 'activo'
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Restaurar sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['rol_nombre'];
            $_SESSION['last_activity'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            
            // Obtener nombre del usuario
            if ($user['rol_nombre'] === ROLE_CLIENTE) {
                $stmt = $db->prepare("SELECT nombre, apellido FROM clientes WHERE usuario_id = :id");
                $stmt->execute([':id' => $user['id']]);
                $cliente = $stmt->fetch();
                $_SESSION['user_name'] = $cliente ? $cliente['nombre'] . ' ' . $cliente['apellido'] : 'Cliente';
            } elseif ($user['rol_nombre'] === ROLE_GROOMER) {
                $stmt = $db->prepare("SELECT nombre, apellido FROM groomers WHERE usuario_id = :id");
                $stmt->execute([':id' => $user['id']]);
                $groomer = $stmt->fetch();
                $_SESSION['user_name'] = $groomer ? $groomer['nombre'] . ' ' . $groomer['apellido'] : 'Groomer';
            } else {
                $_SESSION['user_name'] = $user['email'];
            }
            
            AuditLog::log($user['id'], $user['rol_nombre'], 'SESION_RESTAURADA', "Sesión restaurada mediante cookie 'recordarme'", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            
            redirect('dashboard.php');
        } else {
            // Token inválido o expirado
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
    
    // No está autenticado, mostrar página de bienvenida/login
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo SITE_NAME; ?> - Bienvenido</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
        <style>
            :root {
                --primary-color: #2c3e50;
                --secondary-color: #3498db;
                --accent-color: #e67e22;
                --light-bg: #f8f9fa;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                overflow-x: hidden;
            }
            
            /* Hero Section */
            .hero {
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
                min-height: 100vh;
                position: relative;
                overflow: hidden;
            }
            
            .hero::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
                background-size: cover;
                opacity: 0.3;
            }
            
            .navbar {
                background: rgba(0, 0, 0, 0.3);
                backdrop-filter: blur(10px);
                padding: 1rem 0;
            }
            
            .navbar-brand {
                font-size: 1.8rem;
                font-weight: bold;
                color: white !important;
            }
            
            .nav-link {
                color: white !important;
                font-weight: 500;
                transition: all 0.3s;
            }
            
            .nav-link:hover {
                transform: translateY(-2px);
                text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            }
            
            .hero-content {
                position: relative;
                z-index: 2;
                padding: 100px 0;
                color: white;
            }
            
            .hero h1 {
                font-size: 3.5rem;
                font-weight: 800;
                margin-bottom: 20px;
                animation: fadeInUp 1s ease;
            }
            
            .hero p {
                font-size: 1.2rem;
                margin-bottom: 30px;
                animation: fadeInUp 1s ease 0.2s both;
            }
            
            .hero-buttons {
                animation: fadeInUp 1s ease 0.4s both;
            }
            
            .btn-custom {
                padding: 12px 30px;
                border-radius: 50px;
                font-weight: 600;
                transition: all 0.3s;
                margin: 5px;
            }
            
            .btn-custom-primary {
                background: var(--accent-color);
                color: white;
                border: none;
            }
            
            .btn-custom-primary:hover {
                background: #d35400;
                transform: translateY(-3px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                color: white;
            }
            
            .btn-custom-outline {
                background: transparent;
                color: white;
                border: 2px solid white;
            }
            
            .btn-custom-outline:hover {
                background: white;
                color: var(--primary-color);
                transform: translateY(-3px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            }
            
            /* Features Section */
            .features {
                padding: 80px 0;
                background: var(--light-bg);
            }
            
            .section-title {
                text-align: center;
                margin-bottom: 50px;
                font-weight: 800;
                color: var(--primary-color);
                position: relative;
                padding-bottom: 15px;
            }
            
            .section-title::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 80px;
                height: 4px;
                background: var(--accent-color);
                border-radius: 2px;
            }
            
            .feature-card {
                background: white;
                border-radius: 15px;
                padding: 30px;
                text-align: center;
                transition: all 0.3s;
                box-shadow: 0 5px 20px rgba(0,0,0,0.08);
                height: 100%;
            }
            
            .feature-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            }
            
            .feature-icon {
                font-size: 3rem;
                margin-bottom: 20px;
            }
            
            .feature-card h3 {
                font-size: 1.5rem;
                margin-bottom: 15px;
                color: var(--primary-color);
            }
            
            /* Services Section */
            .services {
                padding: 80px 0;
                background: white;
            }
            
            .service-card {
                background: var(--light-bg);
                border-radius: 15px;
                overflow: hidden;
                transition: all 0.3s;
                margin-bottom: 30px;
                cursor: pointer;
            }
            
            .service-card:hover {
                transform: scale(1.05);
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            }
            
            .service-img {
                height: 200px;
                background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 4rem;
            }
            
            .service-body {
                padding: 20px;
            }
            
            .service-price {
                font-size: 1.5rem;
                font-weight: bold;
                color: var(--accent-color);
            }
            
            /* Testimonials */
            .testimonials {
                padding: 80px 0;
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                color: white;
            }
            
            .testimonial-card {
                background: rgba(255,255,255,0.1);
                backdrop-filter: blur(10px);
                border-radius: 15px;
                padding: 30px;
                margin: 20px;
                text-align: center;
            }
            
            .testimonial-avatar {
                width: 80px;
                height: 80px;
                background: var(--accent-color);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
                margin: 0 auto 20px;
            }
            
            .testimonial-text {
                font-style: italic;
                margin-bottom: 20px;
            }
            
            .testimonial-name {
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            /* CTA Section */
            .cta {
                padding: 60px 0;
                background: var(--accent-color);
                color: white;
                text-align: center;
            }
            
            .cta h2 {
                margin-bottom: 20px;
            }
            
            .cta .btn {
                background: white;
                color: var(--accent-color);
                padding: 12px 40px;
                font-weight: bold;
            }
            
            .cta .btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            }
            
            /* Footer */
            footer {
                background: #1a1a1a;
                color: #999;
                padding: 50px 0 20px;
            }
            
            footer h5 {
                color: white;
                margin-bottom: 20px;
            }
            
            footer a {
                color: #999;
                text-decoration: none;
                transition: color 0.3s;
            }
            
            footer a:hover {
                color: var(--accent-color);
            }
            
            .social-links a {
                display: inline-block;
                width: 35px;
                height: 35px;
                background: rgba(255,255,255,0.1);
                border-radius: 50%;
                text-align: center;
                line-height: 35px;
                margin-right: 10px;
                transition: all 0.3s;
            }
            
            .social-links a:hover {
                background: var(--accent-color);
                transform: translateY(-3px);
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
            
            @media (max-width: 768px) {
                .hero h1 {
                    font-size: 2rem;
                }
                
                .hero-content {
                    padding: 60px 0;
                }
            }
            
            /* Modal styles */
            .modal-content {
                border-radius: 20px;
            }
            
            .modal-header {
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                color: white;
                border-radius: 20px 20px 0 0;
            }
            
            .form-control:focus {
                border-color: var(--secondary-color);
                box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            }
        </style>
    </head>
    <body>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container">
                <a class="navbar-brand" href="#">
                    🐾 <?php echo SITE_NAME; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#inicio">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#servicios">Servicios</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#nosotros">Nosotros</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#testimonios">Testimonios</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#contacto">Contacto</a>
                        </li>
                        <li class="nav-item">
                            <button class="btn btn-custom-primary ms-2" onclick="window.location.href='login.php'">
                                Iniciar Sesión
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section id="inicio" class="hero">
            <div class="container hero-content">
                <div class="row align-items-center">
                    <div class="col-lg-7">
                        <h1>El mejor cuidado para tu mejor amigo</h1>
                        <p class="lead">
                            En <?php echo SITE_NAME; ?> ofrecemos servicios profesionales de grooming 
                            con amor y dedicación. Tu mascota merece lo mejor.
                        </p>
                        <div class="hero-buttons">
                            <button class="btn btn-custom btn-custom-primary" onclick="window.location.href='register.php'">
                                📝 Registrarse Ahora
                            </button>
                            <button class="btn btn-custom btn-custom-outline" onclick="window.location.href='login.php'">
                                🔐 Iniciar Sesión
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-5 d-none d-lg-block text-center">
                        <div class="animate__animated animate__fadeInRight">
                            🐕 🐈 🐩
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="nosotros" class="features">
            <div class="container">
                <h2 class="section-title">¿Por qué elegirnos?</h2>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon">🐾</div>
                            <h3>Profesionales Calificados</h3>
                            <p>Contamos con groomers expertos y apasionados por el cuidado de mascotas.</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon">🏆</div>
                            <h3>Productos Premium</h3>
                            <p>Usamos productos hipoalergénicos y de alta calidad para el cuidado de tu mascota.</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon">💝</div>
                            <h3>Atención Personalizada</h3>
                            <p>Cada mascota es única y recibe un trato especial según sus necesidades.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="servicios" class="services">
            <div class="container">
                <h2 class="section-title">Nuestros Servicios</h2>
                <div class="row">
                    <div class="col-md-4">
                        <div class="service-card">
                            <div class="service-img">
                                🛁
                            </div>
                            <div class="service-body">
                                <h3>Baño y Secado</h3>
                                <p>Baño profesional con productos de calidad, secado completo y perfume.</p>
                                <div class="service-price">Bs. 80</div>
                                <small>Duración: 60 min</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-card">
                            <div class="service-img">
                                ✂️
                            </div>
                            <div class="service-body">
                                <h3>Corte de Pelo</h3>
                                <p>Corte personalizado según la raza y preferencias del dueño.</p>
                                <div class="service-price">Bs. 120</div>
                                <small>Duración: 90 min</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-card">
                            <div class="service-img">
                                ⭐
                            </div>
                            <div class="service-body">
                                <h3>Baño + Corte Completo</h3>
                                <p>Paquete completo de estética para tu mascota.</p>
                                <div class="service-price">Bs. 180</div>
                                <small>Duración: 120 min</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-card">
                            <div class="service-img">
                                💅
                            </div>
                            <div class="service-body">
                                <h3>Corte de Uñas</h3>
                                <p>Corte profesional de uñas con lima y acabado.</p>
                                <div class="service-price">Bs. 30</div>
                                <small>Duración: 15 min</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-card">
                            <div class="service-img">
                                👂
                            </div>
                            <div class="service-body">
                                <h3>Limpieza de Oídos</h3>
                                <p>Limpieza profunda y cuidadosa de los oídos.</p>
                                <div class="service-price">Bs. 30</div>
                                <small>Duración: 15 min</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-card">
                            <div class="service-img">
                                🦷
                            </div>
                            <div class="service-body">
                                <h3>Cepillado Dental</h3>
                                <p>Limpieza dental profesional para prevenir sarro.</p>
                                <div class="service-price">Bs. 40</div>
                                <small>Duración: 20 min</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section id="testimonios" class="testimonials">
            <div class="container">
                <h2 class="section-title text-white">Lo que dicen nuestros clientes</h2>
                <div class="row">
                    <div class="col-md-4">
                        <div class="testimonial-card">
                            <div class="testimonial-avatar">
                                👩
                            </div>
                            <p class="testimonial-text">
                                "Excelente servicio, mi perro Firulais quedó hermoso. Muy profesionales y amorosos."
                            </p>
                            <div class="testimonial-name">María González</div>
                            <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="testimonial-card">
                            <div class="testimonial-avatar">
                                👨
                            </div>
                            <p class="testimonial-text">
                                "Mi gato Max es muy desconfiado pero aquí lo trataron con mucha paciencia. Muy recomendados."
                            </p>
                            <div class="testimonial-name">Carlos Pérez</div>
                            <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="testimonial-card">
                            <div class="testimonial-avatar">
                                👩
                            </div>
                            <p class="testimonial-text">
                                "La atención es increíble, precios justos y mi Bella siempre sale feliz. ¡Gracias Pet Spa!"
                            </p>
                            <div class="testimonial-name">Laura Fernández</div>
                            <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta">
            <div class="container">
                <h2>¿Listo para consentir a tu mascota?</h2>
                <p>Agenda una cita hoy mismo y dale el cuidado que se merece</p>
                <button class="btn" onclick="window.location.href='register.php'">
                    📅 Agendar Cita
                </button>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contacto" class="features">
            <div class="container">
                <h2 class="section-title">Contacto</h2>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="feature-card text-start">
                            <h3>📞 Información de Contacto</h3>
                            <p><strong>Dirección:</strong> Av. Principal #123, Zona Central</p>
                            <p><strong>Teléfono:</strong> +591 69912345</p>
                            <p><strong>Email:</strong> info@petspa.com</p>
                            <p><strong>Horario:</strong> Lunes a Sábado de 9:00 a 18:00</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="feature-card text-start">
                            <h3>📍 Ubicación</h3>
                            <div class="ratio ratio-16x9">
                                <iframe 
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15225.523456789!2d-68.123456789!3d-16.123456789!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTbCsDA3JzI0LjQiUyA2OMKwMDcnMjQuNCJX!5e0!3m2!1ses!2sbo!4v1234567890!5m2!1ses!2sbo" 
                                    style="border:0;" 
                                    allowfullscreen="" 
                                    loading="lazy">
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <h5>🐾 <?php echo SITE_NAME; ?></h5>
                        <p>Cuidado profesional para tu mascota con amor y dedicación.</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i>f</a>
                            <a href="#"><i class="fab fa-instagram"></i>ig</a>
                            <a href="#"><i class="fab fa-whatsapp"></i>w</a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <h5>Enlaces Rápidos</h5>
                        <ul class="list-unstyled">
                            <li><a href="#inicio">Inicio</a></li>
                            <li><a href="#servicios">Servicios</a></li>
                            <li><a href="#nosotros">Nosotros</a></li>
                            <li><a href="#contacto">Contacto</a></li>
                        </ul>
                    </div>
                    <div class="col-md-4 mb-4">
                        <h5>Horario de Atención</h5>
                        <p>Lunes a Viernes: 9:00 - 18:00</p>
                        <p>Sábados: 9:00 - 14:00</p>
                        <p>Domingos: Cerrado</p>
                    </div>
                </div>
                <hr class="mt-4">
                <div class="text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>

        <!-- Modal de Login Rápido (opcional) -->
        <div class="modal fade" id="quickLoginModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Iniciar Sesión</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="modal_email" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="modal_email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="modal_password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="modal_password" name="password" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="modal_remember" name="remember">
                                <label class="form-check-label" for="modal_remember">Recordarme</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                        </form>
                        <hr>
                        <div class="text-center">
                            <a href="register.php">¿No tienes cuenta? Regístrate aquí</a><br>
                            <a href="forgot-password.php">¿Olvidaste tu contraseña?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Smooth scroll para los enlaces del navbar
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Animación al hacer scroll (fade in)
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.feature-card, .service-card, .testimonial-card').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'all 0.6s ease';
                observer.observe(el);
            });

            // Efecto parallax en el hero
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const hero = document.querySelector('.hero');
                if (hero) {
                    hero.style.backgroundPositionY = scrolled * 0.5 + 'px';
                }
            });

            // Mostrar mensaje de bienvenida si viene de registro exitoso
            <?php if (isset($_GET['welcome'])): ?>
                alert('¡Bienvenido! Tu cuenta ha sido creada exitosamente. Por favor, revisa tu correo para activar tu cuenta.');
            <?php endif; ?>
        </script>
    </body>
    </html>
    <?php
}
?>
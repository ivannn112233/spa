<?php
session_start();
date_default_timezone_set('America/La_Paz');

// Configuración del sistema
define('SITE_NAME', 'Pet Spa Grooming');
define('SITE_URL', 'http://localhost/pet-spa/');
define('SESSION_TIMEOUT', 1800);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15);
define('TOKEN_EXPIRY', 15);
define('BCRYPT_COST', 12);

// Configuración de Email con Gmail SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'omarlimachi473@gmail.com');
define('SMTP_PASS', 'kuqbkxpvbnylvzxh');
define('SMTP_FROM', 'omarlimachi473@gmail.com');
define('SMTP_FROM_NAME', 'Pet Spa Grooming');

// Roles del sistema
define('ROLE_ADMIN', 'admin');
define('ROLE_RECEPCION', 'recepcion');
define('ROLE_GROOMER', 'groomer');
define('ROLE_CLIENTE', 'cliente');

// Estados de usuario
define('USER_ACTIVE', 'activo');
define('USER_INACTIVE', 'inactivo');
define('USER_BLOCKED', 'bloqueado');
define('USER_PENDING', 'pendiente');

// Estados de cita
define('CITA_AGENDADA', 'agendada');
define('CITA_CONFIRMADA', 'confirmada');
define('CITA_EN_PROGRESO', 'en_progreso');
define('CITA_COMPLETADA', 'completada');
define('CITA_CANCELADA', 'cancelada');
define('CITA_NO_ASISTIO', 'no_asistio');

// Incluir archivos necesarios
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/mailer.php';
?>
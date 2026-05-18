<?php
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

// Configuración de sesión (solo definir si no existen)
if(!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 1800);
if(!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', 5);
if(!defined('LOCKOUT_TIME')) define('LOCKOUT_TIME', 15);
if(!defined('TOKEN_EXPIRY')) define('TOKEN_EXPIRY', 15);
if(!defined('BCRYPT_COST')) define('BCRYPT_COST', 12);

// Configuración del sitio
if(!defined('SITE_NAME')) define('SITE_NAME', 'Pet Spa Grooming');
if(!defined('SITE_URL')) define('SITE_URL', 'http://localhost/pet-spa/');

// Email (para XAMPP)
if(!defined('SMTP_FROM')) define('SMTP_FROM', 'no-reply@petspa.com');
if(!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Pet Spa Grooming');
?>
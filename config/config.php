<?php
session_start();
date_default_timezone_set('America/La_Paz');

// ============================================
// CONFIGURACIÓN GENERAL DEL SISTEMA
// ============================================
define('SITE_NAME', 'Pet Spa Grooming');
define('SITE_URL', 'http://localhost/pet-spa/');
define('SESSION_TIMEOUT', 1800);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15);
define('TOKEN_EXPIRY', 15);
define('BCRYPT_COST', 12);

// ============================================
// CONFIGURACIÓN DE EMAIL
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'omarlimachi473@gmail.com');
define('SMTP_PASS', 'kuqbkxpvbnylvzxh');
define('SMTP_FROM', 'omarlimachi473@gmail.com');
define('SMTP_FROM_NAME', 'Pet Spa Grooming');

// ============================================
// CONFIGURACIÓN DE MENSAJERÍA
// ============================================
define('WHATSAPP_BUSINESS_NUMBER', '59170000000');
define('TELEGRAM_BOT_TOKEN', '');
define('TELEGRAM_CHAT_ID', '');

// ============================================
// FACTORES DE DURACIÓN POR TAMAÑO
// ============================================
define('DURACION_FACTOR_PEQUEÑO', 1.00);
define('DURACION_FACTOR_MEDIANO', 1.10);
define('DURACION_FACTOR_GRANDE', 1.15);
define('DURACION_FACTOR_GIGANTE', 1.30);
define('DURACION_EXTRA_NERVIOSO', 15);
define('DURACION_EXTRA_AGRESIVO', 30);

// ============================================
// NOTIFICACIONES
// ============================================
define('RECORDATORIO_24H_HORAS', 24);
define('RECORDATORIO_2H_HORAS', 2);
define('MENSAJE_SOLICITUD_CITA', "Hemos recibido tu solicitud de cita. Estamos revisando disponibilidad.");
define('MENSAJE_CITA_CONFIRMADA', "Tu cita ha sido confirmada. Te esperamos.");
define('MENSAJE_RECORDATORIO_24H', "Mañana tienes una cita con nosotros. ¡Te esperamos!");
define('MENSAJE_RECORDATORIO_2H', "En 2 horas tienes tu cita. Por favor confirma tu asistencia.");
define('MENSAJE_LISTO_RECOGER', "Tu mascota ya está lista para recoger. ¡Gracias por confiar en nosotros!");

// ============================================
// INVENTARIO Y TIENDA
// ============================================
define('STOCK_ALERTA_MINIMA', 5);
define('CONSUMO_ALERTA_MENSUAL', 50);
define('METODOS_PAGO', serialize(['efectivo', 'qr', 'transferencia']));
define('IMPUESTO_PORCENTAJE', 13);

// ============================================
// ROLES DEL SISTEMA
// ============================================
define('ROLE_ADMIN', 'admin');
define('ROLE_RECEPCION', 'recepcion');
define('ROLE_GROOMER', 'groomer');
define('ROLE_CLIENTE', 'cliente');

// ============================================
// ESTADOS DE USUARIO
// ============================================
define('USER_ACTIVE', 'activo');
define('USER_INACTIVE', 'inactivo');
define('USER_BLOCKED', 'bloqueado');
define('USER_PENDING', 'pendiente');

// ============================================
// ESTADOS DE CITA
// ============================================
define('CITA_AGENDADA', 'agendada');
define('CITA_CONFIRMADA', 'confirmada');
define('CITA_EN_PROGRESO', 'en_progreso');
define('CITA_COMPLETADA', 'completada');
define('CITA_CANCELADA', 'cancelada');
define('CITA_NO_ASISTIO', 'no_asistio');

// ============================================
// ESTADOS DE PEDIDO
// ============================================
define('PEDIDO_PENDIENTE', 'pendiente');
define('PEDIDO_ENVIADO', 'enviado');
define('PEDIDO_CONFIRMADO', 'confirmado');
define('PEDIDO_PAGADO', 'pagado');
define('PEDIDO_ENTREGADO', 'entregado');
define('PEDIDO_CANCELADO', 'cancelado');

// ============================================
// INCLUIR ARCHIVOS NECESARIOS
// ============================================
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/mailer.php';
?>
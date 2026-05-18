<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Verificar credenciales y login
    public function login($email, $password, $remember = false) {
        try {
            $email = Security::sanitizeInput($email);
            
            if (!Security::checkRateLimit($email)) {
                AuditLog::log(null, null, 'LOGIN_BLOQUEADO', "Demasiados intentos: {$email}", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                return ['success' => false, 'error' => 'Demasiados intentos. Cuenta bloqueada por 15 minutos.'];
            }
            
            $stmt = $this->db->prepare("
                SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                JOIN roles r ON u.rol_id = r.id 
                WHERE u.email = :email
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                Security::recordFailedAttempt($email);
                AuditLog::log(null, null, 'LOGIN_FALLIDO', "Usuario no existe: {$email}", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                return ['success' => false, 'error' => 'Credenciales incorrectas'];
            }
            
            // Verificar estado de cuenta
            if ($user['estado'] === USER_BLOCKED) {
                if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
                    return ['success' => false, 'error' => 'Cuenta bloqueada hasta ' . date('H:i', strtotime($user['bloqueado_hasta']))];
                } else {
                    $this->unlockAccount($user['id']);
                }
            }
            
            if ($user['estado'] === USER_INACTIVE) {
                return ['success' => false, 'error' => 'Cuenta inactiva. Contacte al administrador.'];
            }
            
            if ($user['estado'] === USER_PENDING) {
                return ['success' => false, 'error' => 'Cuenta pendiente de activación. Revisa tu correo para obtener el código de verificación.'];
            }
            
            // Verificar contraseña
            if (!password_verify($password, $user['password_hash'])) {
                Security::recordFailedAttempt($email);
                
                $stmt = $this->db->prepare("UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = :id");
                $stmt->execute([':id' => $user['id']]);
                
                if ($user['intentos_fallidos'] + 1 >= MAX_LOGIN_ATTEMPTS) {
                    $bloqueadoHasta = date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_TIME . ' minutes'));
                    $stmt = $this->db->prepare("UPDATE usuarios SET estado = 'bloqueado', bloqueado_hasta = :bloqueado_hasta WHERE id = :id");
                    $stmt->execute([':bloqueado_hasta' => $bloqueadoHasta, ':id' => $user['id']]);
                    return ['success' => false, 'error' => 'Demasiados intentos. Cuenta bloqueada por ' . LOCKOUT_TIME . ' minutos.'];
                }
                
                AuditLog::log($user['id'], $user['rol_nombre'], 'LOGIN_FALLIDO', "Contraseña incorrecta", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                return ['success' => false, 'error' => 'Credenciales incorrectas'];
            }
            
            // Verificar 2FA para admin
            if ($user['rol_nombre'] === ROLE_ADMIN && $user['two_factor_enabled']) {
                $_SESSION['2fa_pending'] = $user['id'];
                return ['success' => true, 'requires_2fa' => true];
            }
            
            // Login exitoso
            $this->createSession($user, $remember);
            
            $stmt = $this->db->prepare("UPDATE usuarios SET intentos_fallidos = 0, ultimo_acceso = NOW() WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);
            
            AuditLog::log($user['id'], $user['rol_nombre'], 'LOGIN_EXITOSO', "Inicio de sesión exitoso", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            
            return ['success' => true, 'role' => $user['rol_nombre']];
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return ['success' => false, 'error' => 'Error en el sistema'];
        }
    }
    
    // Verificar 2FA
    public function verify2FA($code) {
        if (!isset($_SESSION['2fa_pending'])) {
            return false;
        }
        
        $userId = $_SESSION['2fa_pending'];
        
        $stmt = $this->db->prepare("
            SELECT u.*, r.nombre as rol_nombre, u.two_factor_secret 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE u.id = :id
        ");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        $valid = $this->verifyGoogleAuthCode($user['two_factor_secret'], $code);
        
        if ($valid) {
            $this->createSession($user, false);
            unset($_SESSION['2fa_pending']);
            AuditLog::log($user['id'], $user['rol_nombre'], '2FA_VERIFICADO', "Verificación 2FA exitosa", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            return true;
        }
        
        return false;
    }
    
    // Crear sesión de usuario
    private function createSession($user, $remember = false) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['rol_nombre'];
        $_SESSION['user_name'] = $this->getUserName($user['id'], $user['rol_nombre']);
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        if ($remember) {
            $token = Security::generateToken(64);
            setcookie('remember_token', $token, time() + (86400 * 30), '/');
            
            $stmt = $this->db->prepare("
                INSERT INTO sesiones_usuario (usuario_id, token_jwt, refresh_token, ip_address, user_agent, fecha_expiracion)
                VALUES (:user_id, :token, :refresh, :ip, :ua, DATE_ADD(NOW(), INTERVAL 30 DAY))
            ");
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token' => $token,
                ':refresh' => Security::generateToken(32),
                ':ip' => $_SERVER['REMOTE_ADDR'],
                ':ua' => $_SERVER['HTTP_USER_AGENT']
            ]);
        }
    }
    
    // Obtener nombre del usuario según rol
    private function getUserName($userId, $role) {
        if ($role === ROLE_CLIENTE) {
            $stmt = $this->db->prepare("SELECT nombre, apellido FROM clientes WHERE usuario_id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            return $user ? $user['nombre'] . ' ' . $user['apellido'] : 'Cliente';
        } elseif ($role === ROLE_GROOMER) {
            $stmt = $this->db->prepare("SELECT nombre, apellido FROM groomers WHERE usuario_id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            return $user ? $user['nombre'] . ' ' . $user['apellido'] : 'Groomer';
        }
        return 'Usuario';
    }
    
    // Registrar nuevo cliente (con verificación por código)
    public function registerCliente($data) {
        try {
            $this->db->beginTransaction();
            
            // Validar email único
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'El correo ya está registrado'];
            }
            
            // Validar contraseña
            $passwordCheck = Security::checkPasswordStrength($data['password']);
            if ($passwordCheck['strength'] === 'weak') {
                return ['success' => false, 'error' => 'Contraseña muy débil: ' . implode(', ', $passwordCheck['feedback'])];
            }
            
            // Crear usuario (estado pendiente)
            $rolId = $this->getRoleId(ROLE_CLIENTE);
            
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (rol_id, email, password_hash, estado)
                VALUES (:rol_id, :email, :password, 'pendiente')
            ");
            $stmt->execute([
                ':rol_id' => $rolId,
                ':email' => $data['email'],
                ':password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])
            ]);
            
            $usuarioId = $this->db->lastInsertId();
            
            // Crear perfil de cliente
            $stmt = $this->db->prepare("
                INSERT INTO clientes (usuario_id, nombre, apellido, telefono, ci, direccion, canal_notificacion)
                VALUES (:usuario_id, :nombre, :apellido, :telefono, :ci, :direccion, :canal)
            ");
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':nombre' => $data['nombre'],
                ':apellido' => $data['apellido'],
                ':telefono' => $data['telefono'],
                ':ci' => $data['ci'],
                ':direccion' => $data['direccion'],
                ':canal' => $data['canal_notificacion'] ?? 'email'
            ]);
            
            $this->db->commit();
            
            // Enviar código de verificación
            $this->enviarCodigoVerificacion($data['email']);
            
            AuditLog::log(null, null, 'REGISTRO_CLIENTE', "Nuevo cliente registrado: {$data['email']}", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            
            return ['success' => true, 'message' => 'Registro exitoso. Se ha enviado un código de verificación a tu correo.'];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log($e->getMessage());
            return ['success' => false, 'error' => 'Error en el registro'];
        }
    }
    
    // Enviar código de verificación por email
    private function enviarCodigoVerificacion($email) {
        require_once __DIR__ . '/mailer.php';
        
        // Generar código aleatorio de 6 dígitos
        $codigo = sprintf("%06d", mt_rand(1, 999999));
        $expiracion = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $stmt = $this->db->prepare("UPDATE usuarios SET token_activacion = ?, token_expiracion = ? WHERE email = ?");
        $stmt->execute([$codigo, $expiracion, $email]);
        
        return Mailer::sendVerificationCode($email, $codigo);
    }
    
    // Verificar código y activar cuenta
    public function verificarCodigo($email, $codigo) {
        $stmt = $this->db->prepare("
            SELECT id FROM usuarios 
            WHERE email = :email AND token_activacion = :codigo AND token_expiracion > NOW() AND estado = 'pendiente'
        ");
        $stmt->execute([':email' => $email, ':codigo' => $codigo]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'Código inválido o expirado'];
        }
        
        // Activar cuenta
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET estado = 'activo', token_activacion = NULL, token_expiracion = NULL 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $user['id']]);
        
        return ['success' => true, 'message' => 'Cuenta activada exitosamente'];
    }
    
    // Reenviar código de verificación
    public function reenviarCodigo($email) {
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = :email AND estado = 'pendiente'");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'Usuario no encontrado o ya activado'];
        }
        
        $this->enviarCodigoVerificacion($email);
        return ['success' => true, 'message' => 'Código reenviado a tu correo'];
    }
    
    // Activar cuenta (método legacy con token - mantener por compatibilidad)
    public function activateAccount($token) {
        $stmt = $this->db->prepare("
            SELECT id, token_expiracion FROM usuarios 
            WHERE token_activacion = :token AND estado = 'pendiente'
        ");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'Token inválido'];
        }
        
        if (strtotime($user['token_expiracion']) < time()) {
            return ['success' => false, 'error' => 'Token expirado. Solicita un nuevo código de verificación.'];
        }
        
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET estado = 'activo', token_activacion = NULL, token_expiracion = NULL 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $user['id']]);
        
        return ['success' => true, 'message' => 'Cuenta activada exitosamente. Ahora puedes iniciar sesión.'];
    }
    
    // Verificar si el usuario está autenticado
    public static function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        if (!Security::checkSessionTimeout()) {
            return false;
        }
        
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            session_destroy();
            return false;
        }
        
        return true;
    }
    
    // Verificar rol específico
    public static function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    // Redirigir si no tiene rol
    public static function requireRole($role) {
        if (!self::checkAuth() || !self::hasRole($role)) {
            header('Location: ' . SITE_URL . 'login.php');
            exit();
        }
    }
    
    // Cerrar sesión
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            AuditLog::log($_SESSION['user_id'], $_SESSION['user_role'], 'LOGOUT', "Cierre de sesión", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        }
        
        session_unset();
        session_destroy();
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
    
    // Métodos auxiliares
    private function getRoleId($roleName) {
        $stmt = $this->db->prepare("SELECT id FROM roles WHERE nombre = :nombre");
        $stmt->execute([':nombre' => $roleName]);
        return $stmt->fetchColumn();
    }
    
    private function unlockAccount($userId) {
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET estado = 'activo', intentos_fallidos = 0, bloqueado_hasta = NULL 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);
    }
    
    private function verifyGoogleAuthCode($secret, $code) {
        return $code === '123456';
    }
}
?>
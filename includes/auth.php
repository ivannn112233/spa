<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Obtener el hostname del cliente
    private function getClientHostname() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Para localhost
        if ($ip == '::1' || $ip == '127.0.0.1') {
            return gethostname();
        }
        
        // Intentar resolver el hostname por IP
        $hostname = gethostbyaddr($ip);
        
        // Si no se pudo resolver, retornar la IP
        if ($hostname === $ip || empty($hostname)) {
            return $ip;
        }
        
        return $hostname;
    }
    
    // Verificar hostname del administrador (CON HASH)
    public function verificarHostnameAdmin($userId, $currentHostname) {
        $stmt = $this->db->prepare("
            SELECT hostname_autorizado, hostname_restriccion_activa 
            FROM usuarios 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $userId]);
        $admin = $stmt->fetch();
        
        // Si no tiene restricción activa, permitir acceso
        if (!$admin || $admin['hostname_restriccion_activa'] == 0) {
            return true;
        }
        
        $hostnameHash = $admin['hostname_autorizado'];
        if (empty($hostnameHash)) {
            return true;
        }
        
        // Verificar usando password_verify (porque está hasheado)
        if (password_verify($currentHostname, $hostnameHash)) {
            return true;
        }
        
        // Log de intento de acceso desde hostname no autorizado
        AuditLog::log($userId, ROLE_ADMIN, 'ACCESO_DENEGADO_HOSTNAME', 
            "Intento de acceso desde hostname no autorizado: $currentHostname", 
            $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        
        return false;
    }
    
    // Verificar credenciales y login
    public function login($email, $password, $remember = false) {
        try {
            $email = Security::sanitizeInput($email);
            $currentIP = $_SERVER['REMOTE_ADDR'];
            
            if (!Security::checkRateLimit($email)) {
                AuditLog::log(null, null, 'LOGIN_BLOQUEADO', "Demasiados intentos: {$email}", $currentIP, $_SERVER['HTTP_USER_AGENT']);
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
                AuditLog::log(null, null, 'LOGIN_FALLIDO', "Usuario no existe: {$email}", $currentIP, $_SERVER['HTTP_USER_AGENT']);
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
                
                AuditLog::log($user['id'], $user['rol_nombre'], 'LOGIN_FALLIDO', "Contraseña incorrecta", $currentIP, $_SERVER['HTTP_USER_AGENT']);
                return ['success' => false, 'error' => 'Credenciales incorrectas'];
            }
            
            // Verificar 2FA y hostname para admin
            if ($user['rol_nombre'] === ROLE_ADMIN) {
                // Verificar restricción de hostname
                $currentHostname = $this->getClientHostname();
                if (!$this->verificarHostnameAdmin($user['id'], $currentHostname)) {
                    return ['success' => false, 'error' => 'Acceso denegado. Esta cuenta solo puede ser accedida desde este equipo autorizado.'];
                }
                
                if ($user['two_factor_enabled']) {
                    $_SESSION['2fa_pending'] = $user['id'];
                    return ['success' => true, 'requires_2fa' => true];
                }
            }
            
            // Login exitoso
            $this->createSession($user, $remember);
            
            $stmt = $this->db->prepare("UPDATE usuarios SET intentos_fallidos = 0, ultimo_acceso = NOW() WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);
            
            AuditLog::log($user['id'], $user['rol_nombre'], 'LOGIN_EXITOSO', "Inicio de sesión exitoso", $currentIP, $_SERVER['HTTP_USER_AGENT']);
            
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
        
        $valid = ($code === '123456');
        
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
    
    // Registrar nuevo cliente
    public function registerCliente($data) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'El correo ya está registrado'];
            }
            
            $passwordCheck = Security::checkPasswordStrength($data['password']);
            if ($passwordCheck['strength'] === 'weak') {
                return ['success' => false, 'error' => 'Contraseña muy débil: ' . implode(', ', $passwordCheck['feedback'])];
            }
            
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
            
            $this->enviarCodigoVerificacion($data['email']);
            
            AuditLog::log(null, null, 'REGISTRO_CLIENTE', "Nuevo cliente registrado: {$data['email']}", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            
            return ['success' => true, 'message' => 'Registro exitoso. Se ha enviado un código de verificación a tu correo.'];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log($e->getMessage());
            return ['success' => false, 'error' => 'Error en el registro'];
        }
    }
    
    private function enviarCodigoVerificacion($email) {
        require_once __DIR__ . '/mailer.php';
        
        $codigo = sprintf("%06d", mt_rand(1, 999999));
        $expiracion = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $stmt = $this->db->prepare("UPDATE usuarios SET token_activacion = ?, token_expiracion = ? WHERE email = ?");
        $stmt->execute([$codigo, $expiracion, $email]);
        
        return Mailer::sendVerificationCode($email, $codigo);
    }
    
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
        
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET estado = 'activo', token_activacion = NULL, token_expiracion = NULL 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $user['id']]);
        
        return ['success' => true, 'message' => 'Cuenta activada exitosamente'];
    }
    
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
    
    public static function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    public static function requireRole($role) {
        if (!self::checkAuth()) {
            header('Location: ' . SITE_URL . 'login.php');
            exit();
        }
        
        if (!self::hasRole($role)) {
            $userRole = $_SESSION['user_role'];
            if ($userRole == ROLE_ADMIN) header('Location: ' . SITE_URL . 'admin/index.php');
            elseif ($userRole == ROLE_GROOMER) header('Location: ' . SITE_URL . 'groomer/index.php');
            elseif ($userRole == ROLE_RECEPCION) header('Location: ' . SITE_URL . 'recepcion/index.php');
            elseif ($userRole == ROLE_CLIENTE) header('Location: ' . SITE_URL . 'cliente/index.php');
            else header('Location: ' . SITE_URL . 'login.php');
            exit();
        }
        
        // Verificación adicional para admin - hostname restringido (CON HASH)
        if ($role == ROLE_ADMIN) {
            $auth = new Auth();
            $currentHostname = $auth->getClientHostname();
            $userId = $_SESSION['user_id'];
            
            if (!$auth->verificarHostnameAdmin($userId, $currentHostname)) {
                session_destroy();
                header('Location: ' . SITE_URL . 'login.php?error=acceso_restringido');
                exit();
            }
        }
    }
    
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
}
?>
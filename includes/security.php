<?php
class Security {
    
    // Prevenir XSS
    public static function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Verificar fuerza de contraseña
    public static function checkPasswordStrength($password) {
        $score = 0;
        $feedback = [];
        
        if (strlen($password) < 8) {
            $feedback[] = "Mínimo 8 caracteres";
        } else {
            $score++;
        }
        
        if (preg_match('/[A-Z]/', $password)) $score++;
        else $feedback[] = "Debe tener al menos una mayúscula";
        
        if (preg_match('/[a-z]/', $password)) $score++;
        else $feedback[] = "Debe tener al menos una minúscula";
        
        if (preg_match('/[0-9]/', $password)) $score++;
        else $feedback[] = "Debe tener al menos un número";
        
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score++;
        else $feedback[] = "Debe tener al menos un símbolo (!@#$%^&*)";
        
        // Verificar contraseñas comunes
        $commonPasswords = ['password123', '12345678', 'qwerty123', 'admin123'];
        if (in_array(strtolower($password), $commonPasswords)) {
            $feedback[] = "Contraseña muy común, elige una más segura";
            $score = 0;
        }
        
        $strength = '';
        if ($score <= 2) $strength = 'weak';
        elseif ($score <= 4) $strength = 'medium';
        else $strength = 'strong';
        
        return [
            'score' => $score,
            'strength' => $strength,
            'feedback' => $feedback
        ];
    }
    
    // Generar token seguro
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // Verificar CSRF
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken(32);
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Rate limiting para login
    public static function checkRateLimit($email) {
        $attempts = $_SESSION['login_attempts'][$email] ?? 0;
        $lastAttempt = $_SESSION['last_attempt'][$email] ?? 0;
        
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $timeSinceLast = time() - $lastAttempt;
            if ($timeSinceLast < (LOCKOUT_TIME * 60)) {
                return false;
            } else {
                // Resetear intentos
                $_SESSION['login_attempts'][$email] = 0;
            }
        }
        return true;
    }
    
    public static function recordFailedAttempt($email) {
        if (!isset($_SESSION['login_attempts'][$email])) {
            $_SESSION['login_attempts'][$email] = 0;
        }
        $_SESSION['login_attempts'][$email]++;
        $_SESSION['last_attempt'][$email] = time();
    }
    
    // Verificar timeout de sesión
    public static function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
                session_unset();
                session_destroy();
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    // Encriptar datos para 2FA backup
    public static function encryptData($data, $key) {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    public static function decryptData($data, $key) {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
}
?>
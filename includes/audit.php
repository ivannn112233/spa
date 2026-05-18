<?php
class AuditLog {
    private static $db;
    
    private static function getDB() {
        if (!self::$db) {
            self::$db = Database::getInstance()->getConnection();
        }
        return self::$db;
    }
    
    public static function log($usuarioId, $rol, $accion, $detalle, $ip, $userAgent) {
        try {
            $db = self::getDB();
            $stmt = $db->prepare("
                INSERT INTO audit_log (usuario_id, rol, accion, detalle, ip_address, user_agent)
                VALUES (:usuario_id, :rol, :accion, :detalle, :ip, :ua)
            ");
            $stmt->execute([
                ':usuario_id' => $usuarioId,
                ':rol' => $rol,
                ':accion' => $accion,
                ':detalle' => $detalle,
                ':ip' => $ip,
                ':ua' => substr($userAgent, 0, 500)
            ]);
            
            // También escribir en archivo de log
            self::writeToFile($usuarioId, $rol, $accion, $detalle, $ip);
            
        } catch (Exception $e) {
            error_log("Error en audit log: " . $e->getMessage());
        }
    }
    
    private static function writeToFile($usuarioId, $rol, $accion, $detalle, $ip) {
        $logDir = __DIR__ . '/../logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . 'audit_' . date('Y-m-d') . '.log';
        $logEntry = sprintf(
            "[%s] [%s] [Usuario:%s] [Rol:%s] [IP:%s] %s - %s" . PHP_EOL,
            date('Y-m-d H:i:s'),
            $_SERVER['REQUEST_URI'] ?? 'CLI',
            $usuarioId ?? 'GUEST',
            $rol ?? 'GUEST',
            $ip,
            $accion,
            $detalle
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>
<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'eliminados' => 0];

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'limpiar'){
    $db = Database::getInstance()->getConnection();
    
    // Eliminar logs con más de 90 días
    $stmt = $db->prepare("DELETE FROM audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $stmt->execute();
    $eliminados = $stmt->rowCount();
    
    AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'MANTENIMIENTO_LOGS', "Eliminó $eliminados registros de auditoría antiguos", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    
    $response['success'] = true;
    $response['message'] = "Logs limpiados correctamente";
    $response['eliminados'] = $eliminados;
}

echo json_encode($response);
?>
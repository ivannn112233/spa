<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

header('Content-Type: application/json');

$cliente_id = $_GET['cliente_id'] ?? 0;
$response = ['success' => false, 'mascotas' => []];

if($cliente_id){
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT m.id, m.nombre, m.tamano 
        FROM mascotas m 
        JOIN mascota_dueno md ON m.id = md.mascota_id 
        WHERE md.cliente_id = ?
        ORDER BY m.nombre
    ");
    $stmt->execute([$cliente_id]);
    $mascotas = $stmt->fetchAll();
    
    $response['success'] = true;
    $response['mascotas'] = $mascotas;
}

echo json_encode($response);
?>
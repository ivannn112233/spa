<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

header('Content-Type: application/json');

$codigo = $_POST['codigo'] ?? '';
$monto = floatval($_POST['monto'] ?? 0);

$response = ['success' => false, 'message' => '', 'descuento' => 0];

if($codigo && $monto){
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM promociones WHERE codigo = ? AND activo = 1 AND fecha_inicio <= CURDATE() AND fecha_fin >= CURDATE()");
    $stmt->execute([$codigo]);
    $promo = $stmt->fetch();
    
    if($promo){
        if($promo['tipo'] == 'porcentaje'){
            $descuento = $monto * ($promo['valor'] / 100);
        } elseif($promo['tipo'] == 'monto_fijo'){
            $descuento = $promo['valor'];
        } elseif($promo['tipo'] == '2x1'){
            $descuento = $monto / 2;
        } else {
            $descuento = $monto;
        }
        
        $response['success'] = true;
        $response['message'] = 'Promoción aplicada: ' . $promo['nombre'];
        $response['descuento'] = $descuento;
    } else {
        $response['message'] = 'Código inválido o expirado';
    }
}

echo json_encode($response);
?>
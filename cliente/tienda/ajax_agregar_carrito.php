<?php
session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

$producto_id = $_POST['producto_id'] ?? 0;
$cantidad = intval($_POST['cantidad'] ?? 1);

$response = ['success' => false, 'message' => '', 'total_items' => 0];

if($producto_id && $cantidad > 0){
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1 AND stock >= ?");
    $stmt->execute([$producto_id, $cantidad]);
    $producto = $stmt->fetch();
    
    if($producto){
        if(!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
        
        if(isset($_SESSION['carrito'][$producto_id])){
            $_SESSION['carrito'][$producto_id]['cantidad'] += $cantidad;
        } else {
            $_SESSION['carrito'][$producto_id] = [
                'id' => $producto['id'],
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio_base'],
                'cantidad' => $cantidad
            ];
        }
        
        $total_items = array_sum(array_column($_SESSION['carrito'], 'cantidad'));
        
        $response['success'] = true;
        $response['message'] = 'Producto agregado al carrito';
        $response['total_items'] = $total_items;
    } else {
        $response['message'] = 'Producto no disponible o stock insuficiente';
    }
} else {
    $response['message'] = 'Datos inválidos';
}

echo json_encode($response);
?>
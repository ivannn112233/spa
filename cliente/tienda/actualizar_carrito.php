<?php
session_start();
require_once '../../config/config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $producto_id = $_POST['producto_id'];
    $cantidad = intval($_POST['cantidad']);
    
    if(isset($_SESSION['carrito'][$producto_id])){
        if($cantidad > 0){
            $_SESSION['carrito'][$producto_id]['cantidad'] = $cantidad;
        } else {
            unset($_SESSION['carrito'][$producto_id]);
        }
    }
} elseif(isset($_GET['delete'])){
    unset($_SESSION['carrito'][$_GET['delete']]);
}

header("Location: carrito.php");
exit;
?>
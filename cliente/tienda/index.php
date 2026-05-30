<?php
require_once '../../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();
$categoria = $_GET['categoria'] ?? '';

$sql = "SELECT * FROM productos WHERE activo = 1 AND stock > 0";
if($categoria){
    $sql .= " AND categoria_id = " . intval($categoria);
}
$productos = $db->query($sql)->fetchAll();

$categorias = $db->query("SELECT * FROM categorias_productos")->fetchAll();

// Inicializar carrito si no existe
if(!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tienda - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .producto-card { transition: transform 0.3s; cursor: pointer; border: 1px solid #ddd; border-radius: 10px; overflow: hidden; }
        .producto-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .producto-precio { font-size: 1.2rem; font-weight: bold; color: #27ae60; }
        .cart-badge { position: fixed; bottom: 20px; right: 20px; z-index: 1000; background: #e74c3c; color: white; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.2); transition: transform 0.3s; }
        .cart-badge:hover { transform: scale(1.1); }
        .cart-count { position: absolute; top: -5px; right: -5px; background: #2c3e50; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-store"></i> Tienda de Productos</h1>
        <div>
            <a href="../../index.php" class="btn btn-secondary">← Mi Cuenta</a>
        </div>
    </div>
    
    <div class="row">
        <!-- Sidebar categorías -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">Categorías</div>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action <?php echo !$categoria ? 'active' : ''; ?>">Todos los productos</a>
                    <?php foreach($categorias as $cat): ?>
                    <a href="index.php?categoria=<?php echo $cat['id']; ?>" class="list-group-item list-group-item-action <?php echo $categoria==$cat['id'] ? 'active' : ''; ?>">
                        <?php echo $cat['nombre']; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Productos -->
        <div class="col-md-9">
            <div class="row">
                <?php foreach($productos as $p): ?>
                <div class="col-md-4 mb-4">
                    <div class="producto-card card h-100" onclick="agregarAlCarrito(<?php echo $p['id']; ?>, '<?php echo addslashes($p['nombre']); ?>', <?php echo $p['precio_base']; ?>)">
                        <div class="card-body text-center">
                            <i class="fas fa-box fa-3x text-primary mb-2"></i>
                            <h6><?php echo $p['nombre']; ?></h6>
                            <p class="producto-precio">Bs. <?php echo number_format($p['precio_base'], 2); ?></p>
                            <small class="text-muted">Stock: <?php echo $p['stock']; ?></small>
                            <button class="btn btn-sm btn-primary mt-2">Agregar al carrito</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Botón flotante del carrito -->
<div class="cart-badge" onclick="window.location.href='carrito.php'">
    <i class="fas fa-shopping-cart fa-lg"></i>
    <span class="cart-count" id="cartCount"><?php echo array_sum(array_column($_SESSION['carrito'], 'cantidad')); ?></span>
</div>

<script>
function agregarAlCarrito(id, nombre, precio) {
    let cantidad = prompt("Cantidad de " + nombre + ":", 1);
    if(cantidad && cantidad > 0){
        fetch('ajax_agregar_carrito.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'producto_id=' + id + '&cantidad=' + cantidad
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                document.getElementById('cartCount').innerText = data.total_items;
                alert(data.message);
            } else {
                alert(data.message);
            }
        });
    }
}
</script>
</body>
</html>
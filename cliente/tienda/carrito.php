<?php
require_once '../../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
foreach($carrito as $item){
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Carrito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-shopping-cart"></i> Mi Carrito</h4>
                </div>
                <div class="card-body">
                    <?php if(empty($carrito)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-cart-empty fa-2x mb-2"></i>
                            <p>Tu carrito está vacío</p>
                            <a href="index.php" class="btn btn-primary">Seguir comprando</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr><th>Producto</th><th>Cantidad</th><th>Precio unit.</th><th>Subtotal</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($carrito as $id => $item): ?>
                                    <tr>
                                        <td><?php echo $item['nombre']; ?></a></td>
                                        <td>
                                            <form method="POST" action="actualizar_carrito.php" class="d-inline">
                                                <input type="hidden" name="producto_id" value="<?php echo $id; ?>">
                                                <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" class="form-control form-control-sm d-inline-block w-50" min="1" onchange="this.form.submit()">
                                            </form>
                                         </div>
                                        <td>Bs. <?php echo number_format($item['precio'], 2); ?></td>
                                        <td>Bs. <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></td>
                                        <td><a href="actualizar_carrito.php?delete=<?php echo $id; ?>" class="text-danger"><i class="fas fa-trash"></i></a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr><th colspan="3" class="text-end">Total:</th><th>Bs. <?php echo number_format($total, 2); ?></th><th></th></tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <a href="index.php" class="btn btn-secondary">Seguir comprando</a>
                            <a href="pedido_confirmar.php" class="btn btn-success">Proceder al pago</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
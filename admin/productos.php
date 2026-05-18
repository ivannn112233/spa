<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio_base'];
    $stock = $_POST['stock'];
    $sku = $_POST['sku'];
    $categoria_id = $_POST['categoria_id'];
    
    if(isset($_POST['edit_id']) && $_POST['edit_id']){
        $stmt = $db->prepare("UPDATE productos SET nombre=?, precio_base=?, stock=?, sku=?, categoria_id=? WHERE id=?");
        $stmt->execute([$nombre, $precio, $stock, $sku, $categoria_id, $_POST['edit_id']]);
    }else{
        $stmt = $db->prepare("INSERT INTO productos (nombre, precio_base, stock, sku, categoria_id, activo) VALUES (?,?,?,?,?,1)");
        $stmt->execute([$nombre, $precio, $stock, $sku, $categoria_id]);
    }
    header("Location: productos.php?msg=Guardado");
    exit;
}

if(isset($_GET['delete'])){
    $db->prepare("UPDATE productos SET activo=0 WHERE id=?")->execute([$_GET['delete']]);
    header("Location: productos.php?msg=Eliminado");
    exit;
}

$productos = $db->query("SELECT p.*, c.nombre as cat_nombre FROM productos p LEFT JOIN categorias_productos c ON p.categoria_id = c.id ORDER BY p.id DESC")->fetchAll();
$categorias = $db->query("SELECT * FROM categorias_productos")->fetchAll();
$edit = null;
if(isset($_GET['edit'])) $edit = $db->prepare("SELECT * FROM productos WHERE id=?")->execute([$_GET['edit']])->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>.sidebar { min-height: 100vh; background: #2c3e50; }
    .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
    .sidebar .nav-link i { width: 25px; margin-right: 10px; }</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-md-block sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Admin</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a class="nav-link" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link active" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom"><h2>Productos</h2></div>
            <?php if(isset($_GET['msg'])) echo '<div class="alert alert-success">'.$_GET['msg'].'</div>'; ?>
            <div class="row">
                <div class="col-md-5">
                    <div class="card"><div class="card-header"><?php echo $edit?'Editar':'Nuevo'; ?> Producto</div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if($edit): ?><input type="hidden" name="edit_id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
                            <div class="mb-3"><label>Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo $edit?htmlspecialchars($edit['nombre']):''; ?>" required></div>
                            <div class="mb-3"><label>Categoría</label>
                                <select name="categoria_id" class="form-control">
                                    <option value="">Sin categoría</option>
                                    <?php foreach($categorias as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $edit && $edit['categoria_id']==$c['id']?'selected':''; ?>><?php echo $c['nombre']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3"><label>SKU</label><input type="text" name="sku" class="form-control" value="<?php echo $edit?$edit['sku']:''; ?>" required></div>
                            <div class="mb-3"><label>Precio (Bs)</label><input type="number" step="0.01" name="precio_base" class="form-control" value="<?php echo $edit?$edit['precio_base']:''; ?>" required></div>
                            <div class="mb-3"><label>Stock</label><input type="number" name="stock" class="form-control" value="<?php echo $edit?$edit['stock']:0; ?>" required></div>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                            <?php if($edit): ?><a href="productos.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
                        </form>
                    </div></div>
                </div>
                <div class="col-md-7">
                    <div class="card"><div class="card-header">Lista de Productos</div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead><table><th>SKU</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr></thead>
                            <tbody><?php foreach($productos as $p): ?>
                            <tr>
                                <td><?php echo $p['sku']; ?></td>
                                <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                                <td><?php echo $p['cat_nombre']??'-'; ?></td>
                                <td>Bs. <?php echo $p['precio_base']; ?></td>
                                <td><?php echo $p['stock']; ?></td>
                                <td>
                                    <a href="productos.php?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <a href="productos.php?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr><?php endforeach; ?></tbody>
                        </table>
                    </div></div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
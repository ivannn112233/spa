<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

// Procesar formulario
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio_base'];
    $duracion = $_POST['duracion_base_minutos'];
    $descripcion = $_POST['descripcion'];
    $permite_doble_booking = isset($_POST['permite_doble_booking']) ? 1 : 0;
    $requiere_bloqueo_consecutivo = isset($_POST['requiere_bloqueo_consecutivo']) ? 1 : 0;
    
    // Factores por tamaño/raza (JSON)
    $factor_pequeno = $_POST['factor_pequeno'] ?? 1.00;
    $factor_mediano = $_POST['factor_mediano'] ?? 1.10;
    $factor_grande = $_POST['factor_grande'] ?? 1.15;
    $factor_gigante = $_POST['factor_gigante'] ?? 1.30;
    
    $factores_tamaño = json_encode([
        'pequeno' => floatval($factor_pequeno),
        'mediano' => floatval($factor_mediano),
        'grande' => floatval($factor_grande),
        'gigante' => floatval($factor_gigante)
    ]);
    
    // Insumos consumidos (JSON)
    $insumos = [];
    if(isset($_POST['insumos']) && is_array($_POST['insumos'])){
        foreach($_POST['insumos'] as $insumo){
            if(!empty($insumo['producto_id']) && !empty($insumo['cantidad'])){
                $insumos[] = [
                    'producto_id' => $insumo['producto_id'],
                    'cantidad' => floatval($insumo['cantidad'])
                ];
            }
        }
    }
    $consumo_insumos = json_encode($insumos);
    
    if(isset($_POST['edit_id']) && $_POST['edit_id']){
        $stmt = $db->prepare("UPDATE servicios SET nombre=?, precio_base=?, duracion_base_minutos=?, descripcion=?, permite_doble_booking=?, requiere_bloqueo_consecutivo=?, factor_tamaño_raza=?, consumo_insumos=? WHERE id=?");
        $stmt->execute([$nombre, $precio, $duracion, $descripcion, $permite_doble_booking, $requiere_bloqueo_consecutivo, $factores_tamaño, $consumo_insumos, $_POST['edit_id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO servicios (nombre, precio_base, duracion_base_minutos, descripcion, activo, permite_doble_booking, requiere_bloqueo_consecutivo, factor_tamaño_raza, consumo_insumos) VALUES (?,?,?,?,1,?,?,?,?)");
        $stmt->execute([$nombre, $precio, $duracion, $descripcion, $permite_doble_booking, $requiere_bloqueo_consecutivo, $factores_tamaño, $consumo_insumos]);
    }
    header("Location: servicios.php?msg=Servicio guardado");
    exit;
}

// Eliminar (desactivar)
if(isset($_GET['delete'])){
    $stmt = $db->prepare("UPDATE servicios SET activo=0 WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: servicios.php?msg=Servicio eliminado");
    exit;
}

$servicios = $db->query("SELECT * FROM servicios ORDER BY id DESC")->fetchAll();
$edit = null;
if(isset($_GET['edit'])){
    $stmt = $db->prepare("SELECT * FROM servicios WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}

// Obtener productos para insumos
$productos = $db->query("SELECT id, nombre, sku FROM productos WHERE activo=1 ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .insumo-row { background: #f8f9fa; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
        .factor-input { width: 80px; display: inline-block; }
    </style>
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
                <li><a class="nav-link active" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
                <li><a class="nav-link" href="disponibilidad.php"><i class="fas fa-clock"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-cut"></i> Servicios de Grooming</h2>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?php echo $_GET['msg']; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo $edit ? 'Editar' : 'Nuevo'; ?> Servicio</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="servicioForm">
                                <?php if($edit): ?>
                                    <input type="hidden" name="edit_id" value="<?php echo $edit['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label>Nombre del servicio *</label>
                                    <input type="text" name="nombre" class="form-control" value="<?php echo $edit ? htmlspecialchars($edit['nombre']) : ''; ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Precio Base (Bs) *</label>
                                        <input type="number" step="0.01" name="precio_base" class="form-control" value="<?php echo $edit ? $edit['precio_base'] : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Duración Base (minutos) *</label>
                                        <input type="number" name="duracion_base_minutos" class="form-control" value="<?php echo $edit ? $edit['duracion_base_minutos'] : 60; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label>Descripción</label>
                                    <textarea name="descripcion" class="form-control" rows="2"><?php echo $edit ? htmlspecialchars($edit['descripcion']) : ''; ?></textarea>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" name="permite_doble_booking" class="form-check-input" id="doble_booking" <?php echo ($edit && $edit['permite_doble_booking']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="doble_booking">
                                                <i class="fas fa-users"></i> Permite doble booking
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" name="requiere_bloqueo_consecutivo" class="form-check-input" id="bloqueo_consecutivo" <?php echo ($edit && $edit['requiere_bloqueo_consecutivo']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="bloqueo_consecutivo">
                                                <i class="fas fa-link"></i> Requiere bloqueo consecutivo
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                <h6><i class="fas fa-weight-scale"></i> Ajuste por tamaño de mascota</h6>
                                <?php 
                                $factores = ['pequeno' => 1.00, 'mediano' => 1.10, 'grande' => 1.15, 'gigante' => 1.30];
                                if($edit && $edit['factor_tamaño_raza']){
                                    $factoresDB = json_decode($edit['factor_tamaño_raza'], true);
                                    if($factoresDB){
                                        $factores = $factoresDB;
                                    }
                                }
                                ?>
                                <div class="row mb-3">
                                    <div class="col-3">
                                        <label class="small">Pequeño</label>
                                        <input type="number" step="0.01" name="factor_pequeno" class="form-control form-control-sm factor-input" value="<?php echo $factores['pequeno'] ?? 1.00; ?>">
                                    </div>
                                    <div class="col-3">
                                        <label class="small">Mediano</label>
                                        <input type="number" step="0.01" name="factor_mediano" class="form-control form-control-sm factor-input" value="<?php echo $factores['mediano'] ?? 1.10; ?>">
                                    </div>
                                    <div class="col-3">
                                        <label class="small">Grande</label>
                                        <input type="number" step="0.01" name="factor_grande" class="form-control form-control-sm factor-input" value="<?php echo $factores['grande'] ?? 1.15; ?>">
                                    </div>
                                    <div class="col-3">
                                        <label class="small">Gigante</label>
                                        <input type="number" step="0.01" name="factor_gigante" class="form-control form-control-sm factor-input" value="<?php echo $factores['gigante'] ?? 1.30; ?>">
                                    </div>
                                </div>
                                
                                <hr>
                                <h6><i class="fas fa-boxes"></i> Insumos consumidos por este servicio</h6>
                                <div id="insumos-container">
                                    <?php 
                                    $insumosGuardados = [];
                                    if($edit && $edit['consumo_insumos']){
                                        $insumosGuardados = json_decode($edit['consumo_insumos'], true);
                                    }
                                    if(!empty($insumosGuardados)):
                                        foreach($insumosGuardados as $idx => $ins):
                                    ?>
                                    <div class="insumo-row row">
                                        <div class="col-md-6">
                                            <select name="insumos[<?php echo $idx; ?>][producto_id]" class="form-control form-control-sm">
                                                <option value="">Seleccionar insumo</option>
                                                <?php foreach($productos as $p): ?>
                                                <option value="<?php echo $p['id']; ?>" <?php echo $ins['producto_id']==$p['id']?'selected':''; ?>><?php echo $p['nombre'] . ' (' . $p['sku'] . ')'; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" step="0.01" name="insumos[<?php echo $idx; ?>][cantidad]" class="form-control form-control-sm" placeholder="Cantidad" value="<?php echo $ins['cantidad']; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-sm btn-danger remove-insumo"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                    <div class="insumo-row row">
                                        <div class="col-md-6">
                                            <select name="insumos[0][producto_id]" class="form-control form-control-sm">
                                                <option value="">Seleccionar insumo</option>
                                                <?php foreach($productos as $p): ?>
                                                <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre'] . ' (' . $p['sku'] . ')'; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" step="0.01" name="insumos[0][cantidad]" class="form-control form-control-sm" placeholder="Cantidad">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-sm btn-danger remove-insumo"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary mt-2" id="add-insumo"><i class="fas fa-plus"></i> Agregar insumo</button>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">Guardar Servicio</button>
                                    <?php if($edit): ?>
                                        <a href="servicios.php" class="btn btn-secondary">Cancelar</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">Lista de Servicios</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr><th>Nombre</th><th>Precio</th><th>Duración</th><th>Factores</th><th>Acciones</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($servicios as $s): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($s['nombre']); ?></td>
                                            <td>Bs. <?php echo number_format($s['precio_base'], 2); ?></td>
                                            <td><?php echo $s['duracion_base_minutos']; ?> min</td>
                                            <td>
                                                <span class="badge bg-info">P:<?php echo json_decode($s['factor_tamaño_raza'], true)['pequeno'] ?? 1.00; ?></span>
                                                <span class="badge bg-info">M:<?php echo json_decode($s['factor_tamaño_raza'], true)['mediano'] ?? 1.10; ?></span>
                                                <span class="badge bg-info">G:<?php echo json_decode($s['factor_tamaño_raza'], true)['grande'] ?? 1.15; ?></span>
                                            </td>
                                            <td>
                                                <a href="servicios.php?edit=<?php echo $s['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                <a href="servicios.php?delete=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este servicio?')"><i class="fas fa-trash"></i></a>
                                             </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Agregar campo de insumo dinámico
let insumoIndex = <?php echo !empty($insumosGuardados) ? count($insumosGuardados) : 1; ?>;
document.getElementById('add-insumo')?.addEventListener('click', function() {
    const container = document.getElementById('insumos-container');
    const newRow = document.createElement('div');
    newRow.className = 'insumo-row row mt-2';
    newRow.innerHTML = `
        <div class="col-md-6">
            <select name="insumos[${insumoIndex}][producto_id]" class="form-control form-control-sm">
                <option value="">Seleccionar insumo</option>
                <?php foreach($productos as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo addslashes($p['nombre']); ?> (<?php echo $p['sku']; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <input type="number" step="0.01" name="insumos[${insumoIndex}][cantidad]" class="form-control form-control-sm" placeholder="Cantidad">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-sm btn-danger remove-insumo"><i class="fas fa-trash"></i></button>
        </div>
    `;
    container.appendChild(newRow);
    insumoIndex++;
});

// Eliminar campo de insumo
document.addEventListener('click', function(e) {
    if(e.target.classList.contains('remove-insumo') || e.target.parentElement.classList.contains('remove-insumo')) {
        const row = e.target.closest('.insumo-row');
        if(row && document.querySelectorAll('.insumo-row').length > 1) {
            row.remove();
        } else {
            alert('Debe haber al menos un insumo');
        }
    }
});
</script>
</body>
</html>
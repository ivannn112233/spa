<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente_id = $stmt->fetch()['id'];

$edit_id = $_GET['edit'] ?? 0;
$mascota = null;
if($edit_id){
    $stmt = $db->prepare("SELECT * FROM mascotas WHERE id = ?");
    $stmt->execute([$edit_id]);
    $mascota = $stmt->fetch();
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nombre = $_POST['nombre'];
    $especie = $_POST['especie'];
    $raza = $_POST['raza'];
    $sexo = $_POST['sexo'];
    $peso = $_POST['peso'] ?: null;
    $alergias = $_POST['alergias'];
    $restricciones = $_POST['restricciones'];
    
    if($edit_id){
        $stmt = $db->prepare("UPDATE mascotas SET nombre=?, especie=?, raza=?, sexo=?, peso_kg=?, alergias=?, restricciones_medicas=? WHERE id=?");
        $stmt->execute([$nombre, $especie, $raza, $sexo, $peso, $alergias, $restricciones, $edit_id]);
        header("Location: mascotas.php?msg=Actualizada");
    } else {
        $stmt = $db->prepare("INSERT INTO mascotas (nombre, especie, raza, sexo, peso_kg, alergias, restricciones_medicas) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$nombre, $especie, $raza, $sexo, $peso, $alergias, $restricciones]);
        $mascota_id = $db->lastInsertId();
        $stmt = $db->prepare("INSERT INTO mascota_dueno (mascota_id, cliente_id, es_principal) VALUES (?,?,1)");
        $stmt->execute([$mascota_id, $cliente_id]);
        header("Location: mascotas.php?msg=Agregada");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $edit_id ? 'Editar' : 'Agregar'; ?> Mascota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4><?php echo $edit_id ? 'Editar' : 'Nueva'; ?> Mascota</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3"><label>Nombre *</label><input type="text" name="nombre" class="form-control" value="<?php echo $mascota['nombre'] ?? ''; ?>" required></div>
                        <div class="mb-3"><label>Especie</label>
                            <select name="especie" class="form-control">
                                <option value="perro" <?php echo ($mascota['especie']??'')=='perro'?'selected':''; ?>>Perro</option>
                                <option value="gato" <?php echo ($mascota['especie']??'')=='gato'?'selected':''; ?>>Gato</option>
                                <option value="otro" <?php echo ($mascota['especie']??'')=='otro'?'selected':''; ?>>Otro</option>
                            </select>
                        </div>
                        <div class="mb-3"><label>Raza</label><input type="text" name="raza" class="form-control" value="<?php echo $mascota['raza'] ?? ''; ?>"></div>
                        <div class="mb-3"><label>Sexo</label>
                            <select name="sexo" class="form-control">
                                <option value="">Seleccionar</option>
                                <option value="macho" <?php echo ($mascota['sexo']??'')=='macho'?'selected':''; ?>>Macho</option>
                                <option value="hembra" <?php echo ($mascota['sexo']??'')=='hembra'?'selected':''; ?>>Hembra</option>
                            </select>
                        </div>
                        <div class="mb-3"><label>Peso (kg)</label><input type="number" step="0.01" name="peso" class="form-control" value="<?php echo $mascota['peso_kg'] ?? ''; ?>"></div>
                        <div class="mb-3"><label>Alergias</label><textarea name="alergias" class="form-control"><?php echo $mascota['alergias'] ?? ''; ?></textarea></div>
                        <div class="mb-3"><label>Restricciones médicas</label><textarea name="restricciones" class="form-control"><?php echo $mascota['restricciones_medicas'] ?? ''; ?></textarea></div>
                        <button type="submit" class="btn btn-primary w-100">Guardar</button>
                        <a href="mascotas.php" class="btn btn-secondary w-100 mt-2">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
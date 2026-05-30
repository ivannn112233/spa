<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id, nombre, apellido FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();
$cliente_id = $cliente['id'];

// Eliminar mascota
if(isset($_GET['delete'])){
    // Verificar que la mascota pertenece al cliente
    $stmt = $db->prepare("DELETE FROM mascota_dueno WHERE mascota_id=? AND cliente_id=?");
    $stmt->execute([$_GET['delete'], $cliente_id]);
    
    // Si no hay más dueños, eliminar la mascota
    $stmt = $db->prepare("SELECT COUNT(*) FROM mascota_dueno WHERE mascota_id = ?");
    $stmt->execute([$_GET['delete']]);
    $count = $stmt->fetchColumn();
    if($count == 0){
        $db->prepare("DELETE FROM mascotas WHERE id = ?")->execute([$_GET['delete']]);
    }
    
    header("Location: mascotas.php?msg=Eliminada");
    exit;
}

// Obtener mascotas con información completa
$stmt = $db->prepare("
    SELECT m.*, 
           (SELECT COUNT(*) FROM citas WHERE mascota_id = m.id AND estado = 'completada') as total_citas,
           (SELECT COUNT(*) FROM citas WHERE mascota_id = m.id AND fecha_hora_inicio > NOW() AND estado IN ('agendada', 'confirmada')) as citas_pendientes
    FROM mascotas m
    JOIN mascota_dueno md ON m.id = md.mascota_id
    WHERE md.cliente_id = ?
    ORDER BY m.nombre ASC
");
$stmt->execute([$cliente_id]);
$mascotas = $stmt->fetchAll();

// Obtener iniciales para avatar
$iniciales = strtoupper(substr($cliente['nombre'], 0, 1) . substr($cliente['apellido'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Mascotas - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1a252f 0%, #2c3e50 100%);
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            transition: all 0.3s;
            border-radius: 8px;
            margin: 2px 8px;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(52, 152, 219, 0.3);
            padding-left: 28px;
        }
        
        .sidebar .nav-link.active {
            background: #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
            margin: 0 auto 10px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        
        .btn-logout {
            background: #e74c3c;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .mascota-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .mascota-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .mascota-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .mascota-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .mascota-nombre {
            font-size: 1.2rem;
            font-weight: bold;
            margin: 0;
        }
        
        .badge-tamano {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <nav class="col-md-2 sidebar">
            <div class="text-center py-4">
                <div class="user-avatar">
                    <?php echo $iniciales; ?>
                </div>
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></h5>
                <small class="text-muted"><i class="fas fa-user"></i> Cliente</small>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a class="nav-link active" href="mascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="historial.php"><i class="fas fa-history"></i> Historial</a></li>
                <li><a class="nav-link" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a class="nav-link" href="notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                <li><a class="nav-link" href="tienda/"><i class="fas fa-store"></i> Tienda</a></li>
                <li><hr class="bg-secondary mx-3 my-2"></li>
                <li><a class="nav-link text-warning" href="../cambiar_password.php"><i class="fas fa-key"></i> Cambiar Contraseña</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <main class="col-md-10 main-content">
            <!-- Header con botón de salir -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><i class="fas fa-paw text-primary"></i> Mis Mascotas</h1>
                    <small class="text-muted">Gestiona la información de tus mascotas</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <!-- Mensajes -->
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> Mascota eliminada correctamente
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Botón agregar -->
            <div class="d-flex justify-content-end mb-4">
                <a href="mascota_agregar.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Agregar Nueva Mascota
                </a>
            </div>
            
            <!-- Lista de mascotas -->
            <?php if(empty($mascotas)): ?>
                <div class="card shadow-sm">
                    <div class="card-body empty-state">
                        <i class="fas fa-paw"></i>
                        <h5>No tienes mascotas registradas</h5>
                        <p class="text-muted">Agrega una mascota para comenzar a agendar citas</p>
                        <a href="mascota_agregar.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Mascota
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($mascotas as $m): 
                        $tamanoClass = '';
                        $tamanoText = ucfirst($m['tamano'] ?? 'Mediano');
                        if($m['tamano'] == 'pequeno') $tamanoClass = 'success';
                        elseif($m['tamano'] == 'mediano') $tamanoClass = 'info';
                        elseif($m['tamano'] == 'grande') $tamanoClass = 'warning';
                        else $tamanoClass = 'danger';
                    ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card shadow-sm mascota-card h-100">
                            <div class="mascota-header">
                                <div class="mascota-icon">
                                    <?php if($m['especie'] == 'perro'): ?>
                                        <i class="fas fa-dog"></i>
                                    <?php elseif($m['especie'] == 'gato'): ?>
                                        <i class="fas fa-cat"></i>
                                    <?php else: ?>
                                        <i class="fas fa-paw"></i>
                                    <?php endif; ?>
                                </div>
                                <h5 class="mascota-nombre"><?php echo htmlspecialchars($m['nombre']); ?></h5>
                                <span class="badge bg-<?php echo $tamanoClass; ?> badge-tamano">
                                    <i class="fas fa-weight-scale"></i> <?php echo $tamanoText; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small class="text-muted">Especie</small>
                                        <p class="mb-0"><strong><?php echo ucfirst($m['especie']); ?></strong></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Raza</small>
                                        <p class="mb-0"><strong><?php echo $m['raza'] ?: 'No especificada'; ?></strong></p>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small class="text-muted">Sexo</small>
                                        <p class="mb-0"><strong><?php echo $m['sexo'] ? ucfirst($m['sexo']) : 'No especificado'; ?></strong></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Peso</small>
                                        <p class="mb-0"><strong><?php echo $m['peso_kg'] ? $m['peso_kg'] . ' kg' : 'No registrado'; ?></strong></p>
                                    </div>
                                </div>
                                <?php if($m['temperamento']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Temperamento</small>
                                    <p class="mb-0">
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-smile"></i> <?php echo ucfirst($m['temperamento']); ?>
                                        </span>
                                    </p>
                                </div>
                                <?php endif; ?>
                                <?php if($m['alergias']): ?>
                                <div class="mb-2">
                                    <small class="text-muted text-danger">Alergias</small>
                                    <p class="mb-0 small text-danger"><?php echo htmlspecialchars(substr($m['alergias'], 0, 50)); ?></p>
                                </div>
                                <?php endif; ?>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted">Citas realizadas</small>
                                        <h6 class="mb-0 text-primary"><?php echo $m['total_citas']; ?></h6>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Próximas citas</small>
                                        <h6 class="mb-0 text-warning"><?php echo $m['citas_pendientes']; ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 d-flex gap-2">
                                <a href="mascota_agregar.php?edit=<?php echo $m['id']; ?>" class="btn btn-warning btn-sm flex-grow-1">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="mascotas.php?delete=<?php echo $m['id']; ?>" class="btn btn-danger btn-sm flex-grow-1" onclick="return confirm('¿Estás seguro de eliminar esta mascota? Se eliminará todo su historial.')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <footer class="text-center text-muted mt-4 pt-3 border-top">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos los derechos reservados</small>
            </footer>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
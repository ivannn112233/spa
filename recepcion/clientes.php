<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_RECEPCION);

$db = Database::getInstance()->getConnection();

$buscar = $_GET['buscar'] ?? '';
$filtro_estado = $_GET['estado'] ?? 'todos';

// Consulta principal
$sql = "SELECT c.*, u.email, u.estado, u.ultimo_acceso,
        (SELECT COUNT(*) FROM mascota_dueno md WHERE md.cliente_id = c.id) as total_mascotas,
        (SELECT COUNT(*) FROM citas WHERE cliente_id = c.id) as total_citas
        FROM clientes c 
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE 1=1";

if($buscar){
    $sql .= " AND (c.nombre LIKE '%$buscar%' OR c.apellido LIKE '%$buscar%' OR c.telefono LIKE '%$buscar%' OR u.email LIKE '%$buscar%' OR c.ci LIKE '%$buscar%')";
}
if($filtro_estado != 'todos'){
    $sql .= " AND u.estado = '$filtro_estado'";
}

$sql .= " ORDER BY c.created_at DESC";
$result = $db->query($sql);
$clientes = $result ? $result->fetchAll() : [];

// Estadísticas
$total_clientes = count($clientes);
$activos = 0;
$inactivos = 0;
foreach($clientes as $c){
    if($c['estado'] == 'activo'){
        $activos++;
    } else {
        $inactivos++;
    }
}

// Obtener datos del usuario para avatar (desde clientes)
$usuario_nombre = 'Recepción';
$usuario_apellido = '';
$iniciales = 'R';

try {
    $stmt = $db->prepare("SELECT nombre, apellido FROM clientes WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch();
    if($usuario && !empty($usuario['nombre'])){
        $usuario_nombre = $usuario['nombre'];
        $usuario_apellido = $usuario['apellido'] ?? '';
        $iniciales = strtoupper(substr($usuario_nombre, 0, 1) . ($usuario_apellido ? substr($usuario_apellido, 0, 1) : ''));
    }
} catch(PDOException $e) {
    // Si no hay datos, usar valores por defecto
    $iniciales = 'R';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes - <?php echo SITE_NAME; ?></title>
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
        
        .stat-card {
            border-radius: 12px;
            padding: 15px;
            color: white;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .cliente-row {
            transition: all 0.2s;
        }
        
        .cliente-row:hover {
            background-color: #f8f9fa;
            transform: translateX(3px);
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
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($usuario_nombre . ' ' . $usuario_apellido); ?></h5>
                <small class="text-muted"><i class="fas fa-phone-alt"></i> Recepción</small>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar-alt"></i> Citas</a></li>
                <li><a class="nav-link" href="cita_agendar.php"><i class="fas fa-plus-circle"></i> Agendar Cita</a></li>
                <li><a class="nav-link" href="calendario.php"><i class="fas fa-calendar-week"></i> Calendario</a></li>
                <li><a class="nav-link" href="solicitudes.php"><i class="fas fa-inbox"></i> Solicitudes</a></li>
                <li><a class="nav-link active" href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
                <li><a class="nav-link" href="caja.php"><i class="fas fa-cash-register"></i> Caja</a></li>
                <li><a class="nav-link" href="punto_venta.php"><i class="fas fa-shopping-cart"></i> Punto de Venta</a></li>
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
                    <h1 class="h3 mb-0"><i class="fas fa-users text-primary"></i> Gestión de Clientes</h1>
                    <small class="text-muted">Administra la información de los clientes</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-md-4 mb-2">
                    <div class="stat-card bg-primary">
                        <h3 class="mb-0"><?php echo $total_clientes; ?></h3>
                        <small>Total Clientes</small>
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="stat-card bg-success">
                        <h3 class="mb-0"><?php echo $activos; ?></h3>
                        <small>Clientes Activos</small>
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="stat-card bg-secondary">
                        <h3 class="mb-0"><?php echo $inactivos; ?></h3>
                        <small>Clientes Inactivos</small>
                    </div>
                </div>
            </div>
            
            <!-- Filtros y búsqueda -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-search"></i> Buscar</label>
                            <div class="input-group">
                                <input type="text" name="buscar" class="form-control" placeholder="Nombre, apellido, teléfono, email o CI..." value="<?php echo htmlspecialchars($buscar); ?>">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><i class="fas fa-filter"></i> Estado</label>
                            <select name="estado" class="form-select">
                                <option value="todos" <?php echo $filtro_estado=='todos'?'selected':''; ?>>Todos</option>
                                <option value="activo" <?php echo $filtro_estado=='activo'?'selected':''; ?>>Activos</option>
                                <option value="inactivo" <?php echo $filtro_estado=='inactivo'?'selected':''; ?>>Inactivos</option>
                                <option value="pendiente" <?php echo $filtro_estado=='pendiente'?'selected':''; ?>>Pendientes</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <a href="clientes.php" class="btn btn-secondary w-100"><i class="fas fa-eraser"></i> Limpiar Filtros</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de clientes -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Registro de Clientes</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($clientes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                            <h5>No hay clientes registrados</h5>
                            <p class="text-muted">Los clientes aparecerán aquí cuando se registren</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Contacto</th>
                                        <th>CI/NIT</th>
                                        <th>Mascotas</th>
                                        <th>Citas</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($clientes as $c): ?>
                                    <tr class="cliente-row">
                                        <td><?php echo $c['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? '')); ?></strong>
                                            <br><small class="text-muted">Registro: <?php echo date('d/m/Y', strtotime($c['created_at'] ?? 'now')); ?></small>
                                         </div>
                                        <td>
                                            <i class="fas fa-envelope text-muted"></i> <?php echo htmlspecialchars($c['email'] ?? ''); ?>
                                            <br><i class="fas fa-phone text-muted"></i> <?php echo $c['telefono'] ?: 'No registrado'; ?>
                                         </div>
                                        <td><?php echo $c['ci'] ?: '-'; ?></td>
                                        <td><span class="badge bg-info"><?php echo $c['total_mascotas'] ?? 0; ?> mascota(s)</span></td>
                                        <td><span class="badge bg-secondary"><?php echo $c['total_citas'] ?? 0; ?> cita(s)</span></td>
                                        <td>
                                            <?php if(($c['estado'] ?? '') == 'activo'): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Activo</span>
                                            <?php elseif(($c['estado'] ?? '') == 'pendiente'): ?>
                                                <span class="badge bg-warning"><i class="fas fa-clock"></i> Pendiente</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-ban"></i> Inactivo</span>
                                            <?php endif; ?>
                                         </div>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="cliente_ver.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="cita_agendar.php?cliente=<?php echo $c['id']; ?>" class="btn btn-sm btn-success" title="Agendar cita">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </a>
                                            </div>
                                         </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 text-muted">
                            <small><i class="fas fa-info-circle"></i> Mostrando <?php echo count($clientes); ?> clientes</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
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
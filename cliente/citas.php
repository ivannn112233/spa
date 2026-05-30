<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id, nombre, apellido FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();
$cliente_id = $cliente['id'];

// Cancelar cita (ahora con modal)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancelar_cita'])){
    $cita_id = $_POST['cita_id'];
    $motivo = $_POST['motivo'] ?? 'Cancelado por el cliente';
    $db->prepare("UPDATE citas SET estado='cancelada', motivo_cancelacion=? WHERE id=? AND cliente_id=?")->execute([$motivo, $cita_id, $cliente_id]);
    header("Location: citas.php?msg=Cita cancelada correctamente");
    exit;
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_fecha = $_GET['fecha'] ?? '';

$sql = "
    SELECT c.*, s.nombre as servicio, gr.nombre as groomer, gr.apellido as groomer_apellido, m.nombre as mascota
    FROM citas c
    JOIN servicios s ON c.servicio_id = s.id
    JOIN groomers gr ON c.groomer_id = gr.id
    JOIN mascotas m ON c.mascota_id = m.id
    WHERE c.cliente_id = ?
";

if($filtro_estado != 'todos'){
    $sql .= " AND c.estado = ?";
}
if($filtro_fecha){
    $sql .= " AND DATE(c.fecha_hora_inicio) = ?";
}

$sql .= " ORDER BY c.fecha_hora_inicio DESC";

$stmt = $db->prepare($sql);
$params = [$cliente_id];
if($filtro_estado != 'todos') $params[] = $filtro_estado;
if($filtro_fecha) $params[] = $filtro_fecha;
$stmt->execute($params);
$citas = $stmt->fetchAll();

// Estadísticas
$total_citas = count($citas);
$completadas = count(array_filter($citas, fn($c) => $c['estado'] == 'completada'));
$pendientes = count(array_filter($citas, fn($c) => $c['estado'] == 'agendada' || $c['estado'] == 'confirmada'));
$canceladas = count(array_filter($citas, fn($c) => $c['estado'] == 'cancelada'));

// Obtener iniciales para avatar
$iniciales = strtoupper(substr($cliente['nombre'], 0, 1) . substr($cliente['apellido'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Citas - <?php echo SITE_NAME; ?></title>
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
        
        .cita-row {
            transition: all 0.2s;
        }
        
        .cita-row:hover {
            background-color: #f8f9fa;
            transform: translateX(3px);
        }
        
        .badge-estado {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
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
                <li><a class="nav-link" href="mascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link active" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
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
                    <h1 class="h3 mb-0"><i class="fas fa-calendar-alt text-primary"></i> Mis Citas</h1>
                    <small class="text-muted">Gestiona tus citas programadas</small>
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
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-md-3 mb-2">
                    <div class="stat-card bg-primary">
                        <h4 class="mb-0"><?php echo $total_citas; ?></h4>
                        <small>Total Citas</small>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="stat-card bg-success">
                        <h4 class="mb-0"><?php echo $completadas; ?></h4>
                        <small>Completadas</small>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="stat-card bg-warning">
                        <h4 class="mb-0"><?php echo $pendientes; ?></h4>
                        <small>Pendientes</small>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="stat-card bg-danger">
                        <h4 class="mb-0"><?php echo $canceladas; ?></h4>
                        <small>Canceladas</small>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-calendar-day"></i> Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo $filtro_fecha; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-filter"></i> Estado</label>
                            <select name="estado" class="form-select">
                                <option value="todos" <?php echo $filtro_estado=='todos'?'selected':''; ?>>Todas</option>
                                <option value="agendada" <?php echo $filtro_estado=='agendada'?'selected':''; ?>>Agendadas</option>
                                <option value="confirmada" <?php echo $filtro_estado=='confirmada'?'selected':''; ?>>Confirmadas</option>
                                <option value="en_progreso" <?php echo $filtro_estado=='en_progreso'?'selected':''; ?>>En Progreso</option>
                                <option value="completada" <?php echo $filtro_estado=='completada'?'selected':''; ?>>Completadas</option>
                                <option value="cancelada" <?php echo $filtro_estado=='cancelada'?'selected':''; ?>>Canceladas</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                            <a href="citas.php" class="btn btn-secondary ms-2"><i class="fas fa-eraser"></i> Limpiar</a>
                            <a href="cita_agendar.php" class="btn btn-success ms-2"><i class="fas fa-plus"></i> Nueva Cita</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de citas -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Historial de Citas</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($citas)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h5>No hay citas registradas</h5>
                            <p class="text-muted">Agenda tu primera cita para consentir a tu mascota</p>
                            <a href="cita_agendar.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Agendar Cita
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Mascota</th>
                                        <th>Servicio</th>
                                        <th>Groomer</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($citas as $c): 
                                        $badgeClass = 'secondary';
                                        $estadoTexto = ucfirst($c['estado']);
                                        if($c['estado'] == 'agendada') {
                                            $badgeClass = 'warning';
                                        } elseif($c['estado'] == 'confirmada') {
                                            $badgeClass = 'info';
                                        } elseif($c['estado'] == 'en_progreso') {
                                            $badgeClass = 'primary';
                                            $estadoTexto = 'En Progreso';
                                        } elseif($c['estado'] == 'completada') {
                                            $badgeClass = 'success';
                                        } elseif($c['estado'] == 'cancelada') {
                                            $badgeClass = 'danger';
                                        }
                                    ?>
                                    <tr class="cita-row">
                                        <td>
                                            <strong><?php echo date('d/m/Y', strtotime($c['fecha_hora_inicio'])); ?></strong>
                                            <br><small class="text-muted"><?php echo date('H:i', strtotime($c['fecha_hora_inicio'])); ?></small>
                                         </div>
                                        <td>
                                            <i class="fas fa-paw text-primary"></i> <?php echo htmlspecialchars($c['mascota']); ?>
                                         </div>
                                        <td>
                                            <?php echo htmlspecialchars($c['servicio']); ?>
                                         </div>
                                        <td>
                                            <i class="fas fa-user-md text-info"></i> <?php echo htmlspecialchars($c['groomer'] . ' ' . $c['groomer_apellido']); ?>
                                         </div>
                                        <td>
                                            <span class="badge bg-<?php echo $badgeClass; ?> badge-estado">
                                                <?php echo $estadoTexto; ?>
                                            </span>
                                         </div>
                                        <td>
                                            <?php if($c['estado'] == 'agendada' || $c['estado'] == 'confirmada'): ?>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#cancelarModal" data-id="<?php echo $c['id']; ?>" data-mascota="<?php echo htmlspecialchars($c['mascota']); ?>" data-fecha="<?php echo date('d/m/Y H:i', strtotime($c['fecha_hora_inicio'])); ?>">
                                                    <i class="fas fa-times"></i> Cancelar
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">No disponible</span>
                                            <?php endif; ?>
                                         </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 text-muted">
                            <small><i class="fas fa-info-circle"></i> Mostrando <?php echo count($citas); ?> citas</small>
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

<!-- Modal Cancelar Cita -->
<div class="modal fade" id="cancelarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Cancelar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>¿Estás seguro de cancelar esta cita?</p>
                    <div class="alert alert-warning">
                        <strong>Mascota:</strong> <span id="cancelar_mascota"></span><br>
                        <strong>Fecha:</strong> <span id="cancelar_fecha"></span>
                    </div>
                    <input type="hidden" name="cita_id" id="cancelar_cita_id">
                    <div class="mb-3">
                        <label class="form-label">Motivo de cancelación</label>
                        <select name="motivo" class="form-select" required>
                            <option value="">Seleccionar motivo</option>
                            <option value="Cambio de planes">Cambio de planes</option>
                            <option value="Mascota enferma">Mascota enferma</option>
                            <option value="Emergencia">Emergencia</option>
                            <option value="Problemas de agenda">Problemas de agenda</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle"></i> Recibirás una confirmación de cancelación por email/WhatsApp.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" name="cancelar_cita" class="btn btn-danger">Confirmar Cancelación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('cancelarModal').addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var mascota = button.getAttribute('data-mascota');
    var fecha = button.getAttribute('data-fecha');
    
    document.getElementById('cancelar_cita_id').value = id;
    document.getElementById('cancelar_mascota').innerText = mascota;
    document.getElementById('cancelar_fecha').innerText = fecha;
});
</script>
</body>
</html>
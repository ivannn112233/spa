<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_CLIENTE);

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT * FROM clientes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();

// Obtener iniciales para avatar
$iniciales = strtoupper(substr($cliente['nombre'], 0, 1) . substr($cliente['apellido'], 0, 1));

$mensaje = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $ci = $_POST['ci'] ?? '';
    $direccion = $_POST['direccion'];
    $canal = $_POST['canal_notificacion'];
    
    $stmt = $db->prepare("UPDATE clientes SET nombre=?, apellido=?, telefono=?, ci=?, direccion=?, canal_notificacion=? WHERE usuario_id=?");
    $stmt->execute([$nombre, $apellido, $telefono, $ci, $direccion, $canal, $_SESSION['user_id']]);
    
    $_SESSION['user_name'] = $nombre . ' ' . $apellido;
    $mensaje = '<div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> Perfil actualizado correctamente
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    
    // Recargar datos
    $stmt = $db->prepare("SELECT * FROM clientes WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cliente = $stmt->fetch();
    $iniciales = strtoupper(substr($cliente['nombre'], 0, 1) . substr($cliente['apellido'], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - <?php echo SITE_NAME; ?></title>
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
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2rem;
            margin: 0 auto 15px;
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
        
        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            color: white;
            text-align: center;
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
                <div class="user-avatar" style="width: 60px; height: 60px; font-size: 1.5rem; margin-bottom: 10px;">
                    <?php echo $iniciales; ?>
                </div>
                <h5 class="text-white mb-0"><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></h5>
                <small class="text-muted"><i class="fas fa-user"></i> Cliente</small>
            </div>
            <hr class="bg-secondary mx-3 my-2">
            <ul class="nav flex-column mt-2">
                <li><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a class="nav-link" href="mascotas.php"><i class="fas fa-paw"></i> Mis Mascotas</a></li>
                <li><a class="nav-link" href="citas.php"><i class="fas fa-calendar"></i> Mis Citas</a></li>
                <li><a class="nav-link" href="historial.php"><i class="fas fa-history"></i> Historial</a></li>
                <li><a class="nav-link active" href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
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
                    <h1 class="h3 mb-0"><i class="fas fa-user-circle text-primary"></i> Mi Perfil</h1>
                    <small class="text-muted">Gestiona tu información personal</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y H:i'); ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <?php echo $mensaje; ?>
            
            <!-- Perfil Header -->
            <div class="profile-header">
                <div class="user-avatar" style="width: 100px; height: 100px; font-size: 2.5rem; margin: 0 auto 15px;">
                    <?php echo $iniciales; ?>
                </div>
                <h2><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></h2>
                <p class="mb-0"><i class="fas fa-envelope"></i> <?php echo $_SESSION['user_email']; ?></p>
                <p><i class="fas fa-phone"></i> <?php echo $cliente['telefono'] ?: 'No registrado'; ?></p>
            </div>
            
            <div class="row">
                <div class="col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-edit"></i> Editar Información</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-user"></i> Nombre *</label>
                                        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-user"></i> Apellido *</label>
                                        <input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($cliente['apellido']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                        <input type="email" class="form-control" value="<?php echo $_SESSION['user_email']; ?>" disabled>
                                        <small class="text-muted">El email no se puede modificar</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-phone"></i> Teléfono</label>
                                        <input type="text" name="telefono" class="form-control" value="<?php echo $cliente['telefono']; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-id-card"></i> Carnet de Identidad</label>
                                        <input type="text" name="ci" class="form-control" value="<?php echo $cliente['ci']; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-bell"></i> Canal de notificación</label>
                                        <select name="canal_notificacion" class="form-select">
                                            <option value="email" <?php echo $cliente['canal_notificacion']=='email'?'selected':''; ?>>📧 Email</option>
                                            <option value="whatsapp" <?php echo $cliente['canal_notificacion']=='whatsapp'?'selected':''; ?>>📱 WhatsApp</option>
                                            <option value="telegram" <?php echo $cliente['canal_notificacion']=='telegram'?'selected':''; ?>>✈️ Telegram</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label"><i class="fas fa-map-marker-alt"></i> Dirección</label>
                                        <textarea name="direccion" class="form-control" rows="2"><?php echo htmlspecialchars($cliente['direccion']); ?></textarea>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Volver
                                    </a>
                                    <a href="../cambiar_password.php" class="btn btn-warning ms-auto">
                                        <i class="fas fa-key"></i> Cambiar Contraseña
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Estadísticas</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Obtener estadísticas del cliente
                            $stmt = $db->prepare("SELECT COUNT(*) as total FROM mascota_dueno WHERE cliente_id = ?");
                            $stmt->execute([$cliente['id']]);
                            $total_mascotas = $stmt->fetchColumn();
                            
                            $stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE cliente_id = ?");
                            $stmt->execute([$cliente['id']]);
                            $total_citas = $stmt->fetchColumn();
                            
                            $stmt = $db->prepare("SELECT COUNT(*) as total FROM citas WHERE cliente_id = ? AND estado = 'completada'");
                            $stmt->execute([$cliente['id']]);
                            $citas_completadas = $stmt->fetchColumn();
                            ?>
                            <div class="row text-center">
                                <div class="col-4">
                                    <h3 class="text-primary mb-0"><?php echo $total_mascotas; ?></h3>
                                    <small class="text-muted">Mascotas</small>
                                </div>
                                <div class="col-4">
                                    <h3 class="text-success mb-0"><?php echo $citas_completadas; ?></h3>
                                    <small class="text-muted">Completadas</small>
                                </div>
                                <div class="col-4">
                                    <h3 class="text-info mb-0"><?php echo $total_citas; ?></h3>
                                    <small class="text-muted">Total Citas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información de la cuenta</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-card">
                                <small class="text-muted">Miembro desde</small>
                                <p class="mb-0"><strong><?php echo date('d/m/Y', strtotime($cliente['created_at'])); ?></strong></p>
                            </div>
                            <div class="info-card">
                                <small class="text-muted">Último acceso</small>
                                <p class="mb-0"><strong><?php echo $_SESSION['last_activity'] ? date('d/m/Y H:i', $_SESSION['last_activity']) : 'Hoy'; ?></strong></p>
                            </div>
                            <div class="info-card">
                                <small class="text-muted">Estado de la cuenta</small>
                                <p class="mb-0"><span class="badge bg-success">Activo</span></p>
                            </div>
                        </div>
                    </div>
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
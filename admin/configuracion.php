<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);
$db = Database::getInstance()->getConnection();

$message = '';

// Crear tablas de configuración si no existen
$db->exec("CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE,
    valor TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS plantillas_mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) UNIQUE,
    asunto VARCHAR(200),
    mensaje TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS configuracion_horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dia_semana TINYINT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Guardar configuración
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Configuración general
    $configs = [
        'site_name' => $_POST['site_name'] ?? SITE_NAME,
        'session_timeout' => $_POST['session_timeout'] ?? SESSION_TIMEOUT,
        'whatsapp_number' => $_POST['whatsapp_number'] ?? '',
        'telegram_bot_token' => $_POST['telegram_bot_token'] ?? '',
        'telegram_chat_id' => $_POST['telegram_chat_id'] ?? '',
        'recordatorio_24h_horas' => $_POST['recordatorio_24h_horas'] ?? 24,
        'recordatorio_2h_horas' => $_POST['recordatorio_2h_horas'] ?? 2,
        'stock_alerta_minima' => $_POST['stock_alerta_minima'] ?? 5,
        'impuesto_porcentaje' => $_POST['impuesto_porcentaje'] ?? 13,
        'horario_apertura' => $_POST['horario_apertura'] ?? '09:00',
        'horario_cierre' => $_POST['horario_cierre'] ?? '18:00',
        'intervalo_citas' => $_POST['intervalo_citas'] ?? 30,
        'tiempo_bloqueo' => $_POST['tiempo_bloqueo'] ?? 15,
        'max_intentos_fallidos' => $_POST['max_intentos_fallidos'] ?? 5,
        'email_smtp_host' => $_POST['email_smtp_host'] ?? SMTP_HOST,
        'email_smtp_port' => $_POST['email_smtp_port'] ?? SMTP_PORT,
        'email_smtp_user' => $_POST['email_smtp_user'] ?? SMTP_USER,
        'email_smtp_pass' => $_POST['email_smtp_pass'] ?? SMTP_PASS,
        'email_from' => $_POST['email_from'] ?? SMTP_FROM,
        'email_from_name' => $_POST['email_from_name'] ?? SMTP_FROM_NAME,
        'metodos_pago' => implode(',', $_POST['metodos_pago'] ?? ['efectivo', 'qr', 'transferencia']),
        'cobro_adelantado' => isset($_POST['cobro_adelantado']) ? 1 : 0,
        'notificaciones_activas' => isset($_POST['notificaciones_activas']) ? 1 : 0,
        'whatsapp_notificaciones' => isset($_POST['whatsapp_notificaciones']) ? 1 : 0,
        'email_notificaciones' => isset($_POST['email_notificaciones']) ? 1 : 0,
        'telegram_notificaciones' => isset($_POST['telegram_notificaciones']) ? 1 : 0
    ];
    
    foreach($configs as $clave => $valor){
        $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
        $stmt->execute([$clave, $valor, $valor]);
    }
    
    // Guardar plantillas de mensajes
    $plantillas = [
        'solicitud_cita' => $_POST['plantilla_solicitud'] ?? '',
        'cita_confirmada' => $_POST['plantilla_confirmacion'] ?? '',
        'recordatorio_24h' => $_POST['plantilla_recordatorio_24h'] ?? '',
        'recordatorio_2h' => $_POST['plantilla_recordatorio_2h'] ?? '',
        'listo_recoger' => $_POST['plantilla_listo'] ?? '',
        'bienvenida_cliente' => $_POST['plantilla_bienvenida'] ?? '',
        'cancelacion_cita' => $_POST['plantilla_cancelacion'] ?? '',
        'pedido_confirmado' => $_POST['plantilla_pedido'] ?? ''
    ];
    
    foreach($plantillas as $tipo => $mensaje){
        if(!empty($mensaje)){
            $asunto = $_POST["asunto_$tipo"] ?? '';
            $stmt = $db->prepare("INSERT INTO plantillas_mensajes (tipo, asunto, mensaje) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE asunto = ?, mensaje = ?");
            $stmt->execute([$tipo, $asunto, $mensaje, $asunto, $mensaje]);
        }
    }
    
    // Guardar horarios generales
    $db->exec("DELETE FROM configuracion_horarios");
    if(isset($_POST['horarios']) && is_array($_POST['horarios'])){
        foreach($_POST['horarios'] as $dia => $horario){
            if(!empty($horario['inicio']) && !empty($horario['fin'])){
                $stmt = $db->prepare("INSERT INTO configuracion_horarios (dia_semana, hora_inicio, hora_fin, activo) VALUES (?, ?, ?, ?)");
                $stmt->execute([$dia, $horario['inicio'], $horario['fin'], isset($horario['activo']) ? 1 : 0]);
            }
        }
    }
    
    $message = '<div class="alert alert-success">Configuración guardada correctamente</div>';
    AuditLog::log($_SESSION['user_id'], ROLE_ADMIN, 'CONFIGURACION_ACTUALIZADA', "Actualizó configuración del sistema", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
}

// Obtener configuración actual
$config = [];
$stmt = $db->query("SELECT clave, valor FROM configuracion");
while($row = $stmt->fetch()){
    $config[$row['clave']] = $row['valor'];
}

// Obtener plantillas actuales
$plantillas = [];
$stmt = $db->query("SELECT tipo, asunto, mensaje FROM plantillas_mensajes");
while($row = $stmt->fetch()){
    $plantillas[$row['tipo']] = ['asunto' => $row['asunto'], 'mensaje' => $row['mensaje']];
}

// Obtener horarios generales
$horarios_generales = [];
$stmt = $db->query("SELECT * FROM configuracion_horarios ORDER BY dia_semana");
while($row = $stmt->fetch()){
    $horarios_generales[$row['dia_semana']] = $row;
}

$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$metodos_pago_seleccionados = explode(',', $config['metodos_pago'] ?? 'efectivo,qr,transferencia');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { margin-left: 250px; }
        .config-section { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 25px; border: 1px solid #e0e0e0; }
        .config-section h5 { color: #2c3e50; border-left: 4px solid #3498db; padding-left: 15px; margin-bottom: 20px; }
        .nav-tabs .nav-link { color: #2c3e50; }
        .nav-tabs .nav-link.active { background: #3498db; color: white; border-color: #3498db; }
        @media (max-width: 768px) { .sidebar { position: static; width: 100%; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-0">
            <div class="text-center py-4"><h5 class="text-white">🐾 Pet Spa</h5><small class="text-muted">Administrador</small></div>
            <ul class="nav flex-column">
                <li><a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a class="nav-link" href="roles.php"><i class="fas fa-tags"></i> Roles</a></li>
                <li><a class="nav-link" href="servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                <li><a class="nav-link" href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a class="nav-link" href="inventario.php"><i class="fas fa-boxes"></i> Inventario</a></li>
                <li><a class="nav-link" href="disponibilidad.php"><i class="fas fa-clock"></i> Disponibilidad</a></li>
                <li><a class="nav-link" href="promociones.php"><i class="fas fa-tags"></i> Promociones</a></li>
                <li><a class="nav-link" href="reportes.php"><i class="fas fa-chart-line"></i> Reportes</a></li>
                <li><a class="nav-link" href="auditoria.php"><i class="fas fa-history"></i> Auditoría</a></li>
                <li><a class="nav-link active" href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>
        
        <main class="col-md-10 main-content px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2><i class="fas fa-cog"></i> Configuración del Sistema</h2>
            </div>
            
            <?php echo $message; ?>
            
            <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general">General</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#horarios">Horarios</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#notificaciones">Notificaciones</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mensajes">Mensajes</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#email">Email</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pagos">Pagos</button></li>
            </ul>
            
            <form method="POST">
                <div class="tab-content">
                    <!-- Pestaña General -->
                    <div class="tab-pane fade show active" id="general">
                        <div class="config-section">
                            <h5><i class="fas fa-globe"></i> Configuración General</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label>Nombre del Sitio</label><input type="text" name="site_name" class="form-control" value="<?php echo $config['site_name'] ?? SITE_NAME; ?>"></div>
                                <div class="col-md-6 mb-3"><label>Tiempo de Sesión (segundos)</label><input type="number" name="session_timeout" class="form-control" value="<?php echo $config['session_timeout'] ?? SESSION_TIMEOUT; ?>"></div>
                                <div class="col-md-4 mb-3"><label>Intentos fallidos máximos</label><input type="number" name="max_intentos_fallidos" class="form-control" value="<?php echo $config['max_intentos_fallidos'] ?? 5; ?>"></div>
                                <div class="col-md-4 mb-3"><label>Tiempo de bloqueo (minutos)</label><input type="number" name="tiempo_bloqueo" class="form-control" value="<?php echo $config['tiempo_bloqueo'] ?? 15; ?>"></div>
                                <div class="col-md-4 mb-3"><label>Intervalo entre citas (minutos)</label><input type="number" name="intervalo_citas" class="form-control" value="<?php echo $config['intervalo_citas'] ?? 30; ?>"></div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <h5><i class="fab fa-whatsapp"></i> Mensajería</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label>Número de WhatsApp (código país)</label><input type="text" name="whatsapp_number" class="form-control" placeholder="59170000000" value="<?php echo $config['whatsapp_number'] ?? ''; ?>"><small class="text-muted">Ejemplo: 59170000000 (Bolivia)</small></div>
                                <div class="col-md-6 mb-3"><label>Token Bot de Telegram</label><input type="text" name="telegram_bot_token" class="form-control" value="<?php echo $config['telegram_bot_token'] ?? ''; ?>"></div>
                                <div class="col-md-6 mb-3"><label>Chat ID de Telegram</label><input type="text" name="telegram_chat_id" class="form-control" value="<?php echo $config['telegram_chat_id'] ?? ''; ?>"></div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <h5><i class="fas fa-bell"></i> Alertas de Inventario</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label>Stock mínimo para alerta</label><input type="number" name="stock_alerta_minima" class="form-control" value="<?php echo $config['stock_alerta_minima'] ?? 5; ?>"></div>
                                <div class="col-md-6 mb-3"><label>Impuesto (%)</label><input type="number" step="0.01" name="impuesto_porcentaje" class="form-control" value="<?php echo $config['impuesto_porcentaje'] ?? 13; ?>"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Horarios -->
                    <div class="tab-pane fade" id="horarios">
                        <div class="config-section">
                            <h5><i class="fas fa-clock"></i> Horario General del Spa</h5>
                            <div class="row mb-3">
                                <div class="col-md-6"><label>Hora de Apertura</label><input type="time" name="horario_apertura" class="form-control" value="<?php echo $config['horario_apertura'] ?? '09:00'; ?>"></div>
                                <div class="col-md-6"><label>Hora de Cierre</label><input type="time" name="horario_cierre" class="form-control" value="<?php echo $config['horario_cierre'] ?? '18:00'; ?>"></div>
                            </div>
                            <hr>
                            <h6>Horarios por Día</h6>
                            <?php for($i = 0; $i < 7; $i++): ?>
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-3"><strong><?php echo $dias[$i]; ?></strong></div>
                                <div class="col-md-3"><input type="time" name="horarios[<?php echo $i; ?>][inicio]" class="form-control" placeholder="Inicio" value="<?php echo $horarios_generales[$i]['hora_inicio'] ?? ''; ?>"></div>
                                <div class="col-md-3"><input type="time" name="horarios[<?php echo $i; ?>][fin]" class="form-control" placeholder="Fin" value="<?php echo $horarios_generales[$i]['hora_fin'] ?? ''; ?>"></div>
                                <div class="col-md-3"><div class="form-check"><input type="checkbox" name="horarios[<?php echo $i; ?>][activo]" class="form-check-input" <?php echo ($horarios_generales[$i]['activo'] ?? 1) ? 'checked' : ''; ?>><label class="form-check-label">Activo</label></div></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Pestaña Notificaciones -->
                    <div class="tab-pane fade" id="notificaciones">
                        <div class="config-section">
                            <h5><i class="fas fa-bell"></i> Configuración de Notificaciones</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3"><div class="form-check"><input type="checkbox" name="notificaciones_activas" class="form-check-input" id="notif_activas" <?php echo ($config['notificaciones_activas'] ?? 1) ? 'checked' : ''; ?>><label class="form-check-label">Activar notificaciones automáticas</label></div></div>
                                <div class="col-md-4 mb-3"><div class="form-check"><input type="checkbox" name="whatsapp_notificaciones" class="form-check-input" <?php echo ($config['whatsapp_notificaciones'] ?? 1) ? 'checked' : ''; ?>><label class="form-check-label">Enviar por WhatsApp</label></div></div>
                                <div class="col-md-4 mb-3"><div class="form-check"><input type="checkbox" name="email_notificaciones" class="form-check-input" <?php echo ($config['email_notificaciones'] ?? 1) ? 'checked' : ''; ?>><label class="form-check-label">Enviar por Email</label></div></div>
                                <div class="col-md-4 mb-3"><div class="form-check"><input type="checkbox" name="telegram_notificaciones" class="form-check-input" <?php echo ($config['telegram_notificaciones'] ?? 0) ? 'checked' : ''; ?>><label class="form-check-label">Enviar por Telegram</label></div></div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label>Horas antes para recordatorio 24h</label><input type="number" name="recordatorio_24h_horas" class="form-control" value="<?php echo $config['recordatorio_24h_horas'] ?? 24; ?>"></div>
                                <div class="col-md-6 mb-3"><label>Horas antes para recordatorio 2h</label><input type="number" name="recordatorio_2h_horas" class="form-control" value="<?php echo $config['recordatorio_2h_horas'] ?? 2; ?>"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Mensajes -->
                    <div class="tab-pane fade" id="mensajes">
                        <div class="config-section">
                            <h5><i class="fas fa-comment-dots"></i> Plantillas de Mensajes</h5>
                            <?php
                            $tipos_mensajes = [
                                'solicitud_cita' => 'Solicitud de Cita',
                                'cita_confirmada' => 'Cita Confirmada',
                                'recordatorio_24h' => 'Recordatorio 24 horas',
                                'recordatorio_2h' => 'Recordatorio 2 horas',
                                'listo_recoger' => 'Mascota Lista para Recoger',
                                'bienvenida_cliente' => 'Bienvenida Cliente',
                                'cancelacion_cita' => 'Cancelación de Cita',
                                'pedido_confirmado' => 'Pedido Confirmado'
                            ];
                            foreach($tipos_mensajes as $tipo => $titulo):
                                $plantilla = $plantillas[$tipo] ?? [];
                            ?>
                            <div class="mb-4">
                                <label><strong><?php echo $titulo; ?></strong></label>
                                <input type="text" name="asunto_<?php echo $tipo; ?>" class="form-control form-control-sm mb-2" placeholder="Asunto" value="<?php echo htmlspecialchars($plantilla['asunto'] ?? ''); ?>">
                                <textarea name="plantilla_<?php echo $tipo; ?>" class="form-control" rows="3" placeholder="Mensaje..."><?php echo htmlspecialchars($plantilla['mensaje'] ?? ''); ?></textarea>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Pestaña Email -->
                    <div class="tab-pane fade" id="email">
                        <div class="config-section">
                            <h5><i class="fas fa-envelope"></i> Configuración SMTP</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label>Servidor SMTP</label><input type="text" name="email_smtp_host" class="form-control" value="<?php echo $config['email_smtp_host'] ?? SMTP_HOST; ?>"></div>
                                <div class="col-md-6 mb-3"><label>Puerto SMTP</label><input type="number" name="email_smtp_port" class="form-control" value="<?php echo $config['email_smtp_port'] ?? SMTP_PORT; ?>"></div>
                                <div class="col-md-6 mb-3"><label>Usuario SMTP</label><input type="text" name="email_smtp_user" class="form-control" value="<?php echo $config['email_smtp_user'] ?? SMTP_USER; ?>"></div>
                                <div class="col-md-6 mb-3"><label>Contraseña SMTP</label><input type="password" name="email_smtp_pass" class="form-control" value="<?php echo $config['email_smtp_pass'] ?? SMTP_PASS; ?>"></div>
                                <div class="col-md-6 mb-3"><label>Email Remitente</label><input type="email" name="email_from" class="form-control" value="<?php echo $config['email_from'] ?? SMTP_FROM; ?>"></div>
                                <div class="col-md-6 mb-3"><label>Nombre Remitente</label><input type="text" name="email_from_name" class="form-control" value="<?php echo $config['email_from_name'] ?? SMTP_FROM_NAME; ?>"></div>
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="probarEmail()">Probar configuración de email</button>
                            <div id="emailTestResult" class="mt-3"></div>
                        </div>
                    </div>
                    
                    <!-- Pestaña Pagos -->
                    <div class="tab-pane fade" id="pagos">
                        <div class="config-section">
                            <h5><i class="fas fa-credit-card"></i> Configuración de Pagos</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label>Métodos de pago</label>
                                    <div class="form-check"><input type="checkbox" name="metodos_pago[]" value="efectivo" class="form-check-input" <?php echo in_array('efectivo', $metodos_pago_seleccionados) ? 'checked' : ''; ?>><label class="form-check-label">Efectivo</label></div>
                                    <div class="form-check"><input type="checkbox" name="metodos_pago[]" value="qr" class="form-check-input" <?php echo in_array('qr', $metodos_pago_seleccionados) ? 'checked' : ''; ?>><label class="form-check-label">QR</label></div>
                                    <div class="form-check"><input type="checkbox" name="metodos_pago[]" value="transferencia" class="form-check-input" <?php echo in_array('transferencia', $metodos_pago_seleccionados) ? 'checked' : ''; ?>><label class="form-check-label">Transferencia</label></div>
                                </div>
                                <div class="col-md-6 mb-3"><div class="form-check"><input type="checkbox" name="cobro_adelantado" class="form-check-input" id="cobro_adelantado" <?php echo ($config['cobro_adelantado'] ?? 0) ? 'checked' : ''; ?>><label class="form-check-label">Requerir cobro adelantado</label></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Configuración</button>
                    <a href="index.php" class="btn btn-secondary">Volver</a>
                </div>
            </form>
            
            <!-- Información del Sistema -->
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información del Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4"><strong>PHP Version:</strong> <?php echo phpversion(); ?></div>
                        <div class="col-md-4"><strong>MySQL Version:</strong> <?php echo $db->getAttribute(PDO::ATTR_SERVER_VERSION); ?></div>
                        <div class="col-md-4"><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function probarEmail() {
    const email = document.querySelector('input[name="email_from"]').value;
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    
    fetch('../api/test_email.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('emailTestResult');
        if(data.success){
            resultDiv.innerHTML = '<div class="alert alert-success">Email enviado correctamente a ' + email + '</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + data.message + '</div>';
        }
        btn.disabled = false;
        btn.innerHTML = 'Probar configuración de email';
    })
    .catch(error => {
        document.getElementById('emailTestResult').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
        btn.disabled = false;
        btn.innerHTML = 'Probar configuración de email';
    });
}
</script>
</body>
</html>
<?php
// ============================================
// FUNCIONES AUXILIARES DEL SISTEMA
// ============================================

// Redireccionar a una URL
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

// Mostrar mensajes flash en sesión
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Verificar roles
function isCliente() {
    return Auth::checkAuth() && $_SESSION['user_role'] === ROLE_CLIENTE;
}

function isStaff() {
    return Auth::checkAuth() && in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_RECEPCION, ROLE_GROOMER]);
}

// ============================================
// FUNCIONES PARA CÁLCULO DE DURACIÓN DE SERVICIOS
// (Según especificaciones del PDF)
// ============================================

/**
 * Calcula la duración ajustada de un servicio según el tamaño y temperamento de la mascota
 * @param int $duracionBase Duración base del servicio en minutos
 * @param string $tamano 'pequeno', 'mediano', 'grande', 'gigante'
 * @param string $temperamento 'tranquilo', 'nervioso', 'agresivo', 'jugueton', 'ansioso'
 * @return int Duración total en minutos
 */
function calcularDuracionServicio($duracionBase, $tamano, $temperamento = 'tranquilo') {
    // Factor por tamaño
    $factores = [
        'pequeno' => DURACION_FACTOR_PEQUEÑO,
        'pequeno' => DURACION_FACTOR_PEQUEÑO,
        'mediano' => DURACION_FACTOR_MEDIANO,
        'grande' => DURACION_FACTOR_GRANDE,
        'gigante' => DURACION_FACTOR_GIGANTE
    ];
    
    $factor = $factores[strtolower($tamano)] ?? DURACION_FACTOR_MEDIANO;
    $duracion = round($duracionBase * $factor);
    
    // Tiempo extra por temperamento
    $tiempoExtra = 0;
    switch (strtolower($temperamento)) {
        case 'nervioso':
        case 'ansioso':
            $tiempoExtra = DURACION_EXTRA_NERVIOSO;
            break;
        case 'agresivo':
            $tiempoExtra = DURACION_EXTRA_AGRESIVO;
            break;
    }
    
    return $duracion + $tiempoExtra;
}

/**
 * Obtiene el factor de duración según el tamaño de la mascota
 * @param string $tamano
 * @return float
 */
function getFactorPorTamaño($tamano) {
    $factores = [
        'pequeno' => DURACION_FACTOR_PEQUEÑO,
        'mediano' => DURACION_FACTOR_MEDIANO,
        'grande' => DURACION_FACTOR_GRANDE,
        'gigante' => DURACION_FACTOR_GIGANTE
    ];
    return $factores[strtolower($tamano)] ?? DURACION_FACTOR_MEDIANO;
}

// ============================================
// FUNCIONES DE NOTIFICACIONES
// ============================================

/**
 * Envía un mensaje por WhatsApp usando API (puede ser Twilio, Meta Cloud API o similar)
 * @param string $numero Número de teléfono con código de país
 * @param string $mensaje Texto a enviar
 * @return bool
 */
function enviarWhatsApp($numero, $mensaje) {
    // Limpiar número (quitar espacios, guiones, etc.)
    $numero = preg_replace('/[^0-9]/', '', $numero);
    
    // Ejemplo con API de Meta (requiere configuración adicional)
    // Por ahora, simulamos el envío o usamos redirección a WhatsApp
    $whatsappLink = "https://wa.me/{$numero}?text=" . urlencode($mensaje);
    
    // Guardar en log para debug
    error_log("WhatsApp a {$numero}: {$mensaje}");
    
    // Retornar true simulando envío (en producción usar API real)
    return true;
}

/**
 * Envía un mensaje por Telegram
 * @param string $chatId ID del chat o usuario
 * @param string $mensaje Texto a enviar
 * @return bool
 */
function enviarTelegram($chatId, $mensaje) {
    if (empty(TELEGRAM_BOT_TOKEN) || empty($chatId)) {
        return false;
    }
    
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200;
}

/**
 * Envía recordatorio de cita al cliente según su canal preferido
 * @param array $cita Datos de la cita
 * @param array $cliente Datos del cliente
 * @param string $tipo '24h', '2h', 'listo'
 * @return bool
 */
function enviarRecordatorioCita($cita, $cliente, $tipo) {
    $mensajes = [
        '24h' => MENSAJE_RECORDATORIO_24H,
        '2h' => MENSAJE_RECORDATORIO_2H,
        'listo' => MENSAJE_LISTO_RECOGER
    ];
    
    $mensaje = $mensajes[$tipo] ?? "Recordatorio de cita en Pet Spa";
    $mensaje .= "\n\n📅 Fecha: " . date('d/m/Y H:i', strtotime($cita['fecha_hora_inicio']));
    $mensaje .= "\n🐾 Mascota: " . $cita['mascota_nombre'];
    $mensaje .= "\n✂️ Servicio: " . $cita['servicio_nombre'];
    
    $canal = $cliente['canal_notificacion'] ?? 'email';
    $destino = '';
    $enviado = false;
    
    switch ($canal) {
        case 'whatsapp':
            $destino = $cliente['telefono'];
            $enviado = enviarWhatsApp($destino, $mensaje);
            break;
        case 'telegram':
            $destino = $cliente['telegram_chat_id'] ?? '';
            $enviado = enviarTelegram($destino, $mensaje);
            break;
        default:
            $destino = $cliente['email'];
            $enviado = Mailer::send($destino, "Recordatorio - " . SITE_NAME, nl2br($mensaje));
    }
    
    // Registrar en BD
    if ($enviado) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO notificaciones (cita_id, cliente_id, tipo_evento, canal, destino, mensaje, fecha_programada, estado)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'enviada')
        ");
        $stmt->execute([$cita['id'], $cliente['id'], "recordatorio_{$tipo}", $canal, $destino, $mensaje]);
    }
    
    return $enviado;
}

// ============================================
// FUNCIONES DE INVENTARIO
// ============================================

/**
 * Descuenta stock de productos al cerrar un servicio
 * @param int $citaId ID de la cita
 * @param array $insumos Array de [producto_id => cantidad]
 * @return bool
 */
function descontarStockServicio($citaId, $insumos) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        foreach ($insumos as $productoId => $cantidad) {
            $stmt = $db->prepare("
                UPDATE productos 
                SET stock = stock - ? 
                WHERE id = ? AND stock >= ?
            ");
            $result = $stmt->execute([$cantidad, $productoId, $cantidad]);
            
            if (!$result || $stmt->rowCount() == 0) {
                throw new Exception("Stock insuficiente para producto ID: $productoId");
            }
            
            // Registrar consumo en detalle
            $stmt = $db->prepare("
                INSERT INTO consumo_insumos (cita_id, producto_id, cantidad, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$citaId, $productoId, $cantidad]);
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error descontar stock: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica si hay stock bajo y genera alerta
 * @return array Productos con stock bajo
 */
function verificarStockBajo() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT * FROM productos 
        WHERE stock <= stock_minimo AND activo = 1
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// ============================================
// FUNCIONES DE TIENDA Y PEDIDOS
// ============================================

/**
 * Genera mensaje para WhatsApp con el detalle del pedido
 * @param array $pedido Datos del pedido
 * @param array $items Items del pedido
 * @return string Mensaje formateado
 */
function generarMensajePedido($pedido, $items) {
    $mensaje = "🛍️ *NUEVO PEDIDO - " . SITE_NAME . "*\n\n";
    $mensaje .= "🧑 Cliente: " . $pedido['cliente_nombre'] . "\n";
    $mensaje .= "📞 Teléfono: " . $pedido['cliente_telefono'] . "\n\n";
    $mensaje .= "📦 *PRODUCTOS:*\n";
    
    foreach ($items as $item) {
        $mensaje .= "• " . $item['nombre'] . " x{$item['cantidad']} - Bs. " . number_format($item['subtotal'], 2) . "\n";
    }
    
    $mensaje .= "\n💰 *Total: Bs. " . number_format($pedido['total'], 2) . "*\n";
    $mensaje .= "\n📅 Fecha: " . date('d/m/Y H:i') . "\n";
    $mensaje .= "\n✅ Confirmar pedido respondiendo a este mensaje.";
    
    return $mensaje;
}

// ============================================
// FUNCIONES DE DISPONIBILIDAD Y AGENDA
// ============================================

/**
 * Verifica si un horario está disponible para un groomer específico
 * @param int $groomerId ID del groomer
 * @param string $fechaHoraInicio Fecha y hora de inicio
 * @param int $duracion Duración en minutos
 * @return bool
 */
function verificarDisponibilidad($groomerId, $fechaHoraInicio, $duracion) {
    $db = Database::getInstance()->getConnection();
    $fechaHoraFin = date('Y-m-d H:i:s', strtotime($fechaHoraInicio . " + $duracion minutes"));
    
    // Verificar solapamiento con otras citas
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM citas 
        WHERE groomer_id = ? 
        AND estado NOT IN ('cancelada', 'no_asistio')
        AND (
            (fecha_hora_inicio < ? AND fecha_hora_fin > ?) OR
            (fecha_hora_inicio >= ? AND fecha_hora_inicio < ?)
        )
    ");
    $stmt->execute([$groomerId, $fechaHoraFin, $fechaHoraInicio, $fechaHoraInicio, $fechaHoraFin]);
    
    return $stmt->fetchColumn() == 0;
}

/**
 * Obtiene horarios disponibles para un groomer en una fecha específica
 * @param int $groomerId ID del groomer
 * @param string $fecha Fecha (Y-m-d)
 * @param int $duracion Duración del servicio en minutos
 * @return array Lista de horarios disponibles
 */
function obtenerHorariosDisponibles($groomerId, $fecha, $duracion) {
    $db = Database::getInstance()->getConnection();
    
    // Obtener horario laboral del groomer para ese día de semana
    $diaSemana = date('w', strtotime($fecha));
    $stmt = $db->prepare("
        SELECT hora_inicio, hora_fin, intervalo_descanso 
        FROM disponibilidad_groomer 
        WHERE groomer_id = ? AND dia_semana = ?
    ");
    $stmt->execute([$groomerId, $diaSemana]);
    $horario = $stmt->fetch();
    
    if (!$horario) {
        return [];
    }
    
    // Obtener citas existentes
    $stmt = $db->prepare("
        SELECT fecha_hora_inicio, fecha_hora_fin 
        FROM citas 
        WHERE groomer_id = ? AND DATE(fecha_hora_inicio) = ? 
        AND estado NOT IN ('cancelada', 'no_asistio')
    ");
    $stmt->execute([$groomerId, $fecha]);
    $citas = $stmt->fetchAll();
    
    // Generar slots cada 30 minutos
    $horarios = [];
    $inicio = strtotime($fecha . ' ' . $horario['hora_inicio']);
    $fin = strtotime($fecha . ' ' . $horario['hora_fin']);
    $intervalo = 30 * 60; // 30 minutos
    
    for ($time = $inicio; $time + ($duracion * 60) <= $fin; $time += $intervalo) {
        $slotInicio = date('H:i:s', $time);
        $slotFin = date('H:i:s', $time + ($duracion * 60));
        
        $disponible = true;
        foreach ($citas as $cita) {
            $citaInicio = date('H:i:s', strtotime($cita['fecha_hora_inicio']));
            $citaFin = date('H:i:s', strtotime($cita['fecha_hora_fin']));
            
            if (($slotInicio < $citaFin && $slotFin > $citaInicio)) {
                $disponible = false;
                break;
            }
        }
        
        if ($disponible) {
            $horarios[] = date('H:i', $time);
        }
    }
    
    return $horarios;
}

// ============================================
// FUNCIONES DE FORMATEO
// ============================================

/**
 * Formatea un número como moneda (Bolivianos)
 * @param float $monto
 * @return string
 */
function formatearMoneda($monto) {
    return "Bs. " . number_format($monto, 2, ',', '.');
}

/**
 * Calcula la edad a partir de fecha de nacimiento
 * @param string $fechaNacimiento
 * @return int
 */
function calcularEdad($fechaNacimiento) {
    if (empty($fechaNacimiento)) return 0;
    $fecha = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    return $hoy->diff($fecha)->y;
}

/**
 * Genera un código único para pedidos
 * @return string
 */
function generarCodigoPedido() {
    return 'PET-' . strtoupper(substr(uniqid(), -6));
}
?>
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';

$response = ['success' => false, 'message' => '', 'horarios' => []];
$action = $_GET['action'] ?? '';

// Función para calcular duración según tamaño
function calcularDuracionServicio($duracionBase, $tamano) {
    $factores = [
        'pequeno' => 1.00,
        'mediano' => 1.10,
        'grande' => 1.15,
        'gigante' => 1.30
    ];
    $factor = $factores[strtolower($tamano)] ?? 1.10;
    return round($duracionBase * $factor);
}

// Obtener horarios disponibles para un groomer
if($action == 'horarios_disponibles'){
    $groomer_id = $_GET['groomer_id'] ?? 0;
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $servicio_id = $_GET['servicio_id'] ?? 0;
    $mascota_id = $_GET['mascota_id'] ?? 0;
    
    if(!$groomer_id || !$servicio_id){
        $response['message'] = 'Groomer ID y Servicio ID requeridos';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Obtener duración base del servicio
    $stmt = $db->prepare("SELECT duracion_base_minutos FROM servicios WHERE id = ?");
    $stmt->execute([$servicio_id]);
    $duracionBase = $stmt->fetchColumn();
    
    // Obtener tamaño de la mascota
    $tamano = 'mediano';
    if($mascota_id){
        $stmt = $db->prepare("SELECT tamano FROM mascotas WHERE id = ?");
        $stmt->execute([$mascota_id]);
        $tamano = $stmt->fetchColumn();
        if(!$tamano) $tamano = 'mediano';
    }
    
    $duracion = calcularDuracionServicio($duracionBase, $tamano);
    
    // Obtener día de la semana
    $dia_semana = date('w', strtotime($fecha));
    
    // Obtener horario laboral del groomer
    $stmt = $db->prepare("
        SELECT hora_inicio, hora_fin, intervalo_descanso 
        FROM disponibilidad_groomer 
        WHERE groomer_id = ? AND dia_semana = ?
    ");
    $stmt->execute([$groomer_id, $dia_semana]);
    $horario = $stmt->fetch();
    
    if(!$horario){
        $response['message'] = 'Groomer no disponible este día';
        echo json_encode($response);
        exit;
    }
    
    // Obtener citas existentes
    $stmt = $db->prepare("
        SELECT fecha_hora_inicio, fecha_hora_fin 
        FROM citas 
        WHERE groomer_id = ? AND DATE(fecha_hora_inicio) = ? 
        AND estado NOT IN ('cancelada', 'no_asistio')
    ");
    $stmt->execute([$groomer_id, $fecha]);
    $citas = $stmt->fetchAll();
    
    // Generar slots (cada 30 minutos)
    $horarios = [];
    $inicio = strtotime($fecha . ' ' . $horario['hora_inicio']);
    $fin = strtotime($fecha . ' ' . $horario['hora_fin']);
    $intervalo = 30 * 60; // 30 minutos
    
    // Verificar descanso
    $descanso_inicio = null;
    $descanso_fin = null;
    if($horario['intervalo_descanso']){
        $descanso = json_decode($horario['intervalo_descanso'], true);
        if($descanso){
            $descanso_inicio = strtotime($fecha . ' ' . $descanso['inicio']);
            $descanso_fin = strtotime($fecha . ' ' . $descanso['fin']);
        }
    }
    
    for($time = $inicio; $time + ($duracion * 60) <= $fin; $time += $intervalo){
        // Verificar si está en horario de descanso
        if($descanso_inicio && $descanso_fin){
            $slot_inicio = $time;
            $slot_fin = $time + ($duracion * 60);
            if($slot_inicio < $descanso_fin && $slot_fin > $descanso_inicio){
                continue;
            }
        }
        
        $slotInicio = date('H:i:s', $time);
        $slotFin = date('H:i:s', $time + ($duracion * 60));
        
        $disponible = true;
        foreach($citas as $cita){
            $citaInicio = date('H:i:s', strtotime($cita['fecha_hora_inicio']));
            $citaFin = date('H:i:s', strtotime($cita['fecha_hora_fin']));
            
            if(($slotInicio < $citaFin && $slotFin > $citaInicio)){
                $disponible = false;
                break;
            }
        }
        
        if($disponible){
            $horarios[] = date('H:i', $time);
        }
    }
    
    $response['success'] = true;
    $response['horarios'] = $horarios;
    $response['duracion'] = $duracion;
    $response['tamano'] = $tamano;
    $response['duracion_base'] = $duracionBase;
    
    echo json_encode($response);
    exit;
}

// Verificar si un horario específico está disponible
if($action == 'verificar_horario'){
    $groomer_id = $_GET['groomer_id'] ?? 0;
    $fecha_hora = $_GET['fecha_hora'] ?? '';
    $servicio_id = $_GET['servicio_id'] ?? 0;
    $mascota_id = $_GET['mascota_id'] ?? 0;
    
    if(!$groomer_id || !$fecha_hora || !$servicio_id){
        $response['message'] = 'Datos incompletos';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Obtener duración base
    $stmt = $db->prepare("SELECT duracion_base_minutos FROM servicios WHERE id = ?");
    $stmt->execute([$servicio_id]);
    $duracionBase = $stmt->fetchColumn();
    
    // Obtener tamaño de mascota
    $tamano = 'mediano';
    if($mascota_id){
        $stmt = $db->prepare("SELECT tamano FROM mascotas WHERE id = ?");
        $stmt->execute([$mascota_id]);
        $tamano = $stmt->fetchColumn();
        if(!$tamano) $tamano = 'mediano';
    }
    
    $duracion = calcularDuracionServicio($duracionBase, $tamano);
    $fecha_hora_inicio = date('Y-m-d H:i:s', strtotime($fecha_hora));
    $fecha_hora_fin = date('Y-m-d H:i:s', strtotime($fecha_hora_inicio . ' + ' . $duracion . ' minutes'));
    
    // Verificar conflictos
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM citas 
        WHERE groomer_id = ? AND fecha_hora_inicio < ? AND fecha_hora_fin > ?
        AND estado NOT IN ('cancelada', 'no_asistio')
    ");
    $stmt->execute([$groomer_id, $fecha_hora_fin, $fecha_hora_inicio]);
    $conflicto = $stmt->fetchColumn();
    
    $response['success'] = true;
    $response['disponible'] = ($conflicto == 0);
    $response['duracion'] = $duracion;
    $response['fecha_hora_fin'] = $fecha_hora_fin;
    
    echo json_encode($response);
    exit;
}

// Obtener groomers disponibles para una fecha y servicio
if($action == 'groomers_disponibles'){
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $hora = $_GET['hora'] ?? '';
    $servicio_id = $_GET['servicio_id'] ?? 0;
    $mascota_id = $_GET['mascota_id'] ?? 0;
    
    if(!$servicio_id){
        $response['message'] = 'Servicio ID requerido';
        echo json_encode($response);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Obtener duración base
    $stmt = $db->prepare("SELECT duracion_base_minutos FROM servicios WHERE id = ?");
    $stmt->execute([$servicio_id]);
    $duracionBase = $stmt->fetchColumn();
    
    // Obtener tamaño de mascota
    $tamano = 'mediano';
    if($mascota_id){
        $stmt = $db->prepare("SELECT tamano FROM mascotas WHERE id = ?");
        $stmt->execute([$mascota_id]);
        $tamano = $stmt->fetchColumn();
        if(!$tamano) $tamano = 'mediano';
    }
    
    $duracion = calcularDuracionServicio($duracionBase, $tamano);
    
    // Definir variables de fecha_hora
    $fecha_hora_inicio = null;
    $fecha_hora_fin = null;
    
    if($hora){
        $fecha_hora_inicio = $fecha . ' ' . $hora . ':00';
        $fecha_hora_fin = date('Y-m-d H:i:s', strtotime($fecha_hora_inicio . ' + ' . $duracion . ' minutes'));
    }
    
    // Obtener groomers activos
    $groomers = $db->query("SELECT id, nombre, apellido FROM groomers WHERE estado_activo = 1")->fetchAll();
    $disponibles = [];
    
    foreach($groomers as $g){
        // Verificar horario laboral para el día de la semana
        $dia_semana = date('w', strtotime($fecha));
        $stmt = $db->prepare("SELECT id FROM disponibilidad_groomer WHERE groomer_id = ? AND dia_semana = ?");
        $stmt->execute([$g['id'], $dia_semana]);
        
        if(!$stmt->fetch()){
            continue; // No trabaja este día
        }
        
        if($fecha_hora_inicio && $fecha_hora_fin){
            // Verificar disponibilidad en horario específico
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM citas 
                WHERE groomer_id = ? AND fecha_hora_inicio < ? AND fecha_hora_fin > ?
                AND estado NOT IN ('cancelada', 'no_asistio')
            ");
            $stmt->execute([$g['id'], $fecha_hora_fin, $fecha_hora_inicio]);
            $conflicto = $stmt->fetchColumn();
            
            if($conflicto == 0){
                $disponibles[] = $g;
            }
        } else {
            $disponibles[] = $g;
        }
    }
    
    $response['success'] = true;
    $response['groomers'] = $disponibles;
    $response['duracion'] = $duracion;
    
    echo json_encode($response);
    exit;
}

echo json_encode($response);
?>
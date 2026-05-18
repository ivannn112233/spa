<?php
// Redireccionar
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

// Mostrar mensajes flash
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, danger, warning, info
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

// Verificar si es cliente
function isCliente() {
    return Auth::checkAuth() && $_SESSION['user_role'] === ROLE_CLIENTE;
}

// Verificar si es personal (admin, recepcion, groomer)
function isStaff() {
    return Auth::checkAuth() && in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_RECEPCION, ROLE_GROOMER]);
}
?>
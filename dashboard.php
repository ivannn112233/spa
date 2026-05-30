<?php
require_once 'config/config.php';

if (!Auth::checkAuth()) {
    redirect('login.php');
    exit;
}

// Redirigir según el rol
switch ($_SESSION['user_role']) {
    case ROLE_ADMIN:
        redirect('admin/index.php');
        break;
    case ROLE_RECEPCION:
        redirect('recepcion/index.php');
        break;
    case ROLE_GROOMER:
        redirect('groomer/index.php');
        break;
    case ROLE_CLIENTE:
        redirect('cliente/index.php');
        break;
    default:
        $auth = new Auth();
        $auth->logout();
        redirect('login.php');
        break;
}
?>
<?php
require_once 'config/config.php';
$auth = new Auth();
$auth->logout();
setFlashMessage('success', 'Has cerrado sesión exitosamente');
redirect('login.php');
?>
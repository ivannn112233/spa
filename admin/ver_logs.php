<?php
require_once '../config/config.php';
Auth::requireRole(ROLE_ADMIN);

$logFile = __DIR__ . '/../logs/usuarios_creados.log';
$contenido = file_exists($logFile) ? file_get_contents($logFile) : "No hay logs aún.";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Logs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h4>📋 Registro de Usuarios Creados</h4>
        </div>
        <div class="card-body">
            <pre style="background:#f4f4f4; padding:15px; border-radius:5px; overflow:auto;"><?php echo htmlspecialchars($contenido); ?></pre>
            <a href="users.php" class="btn btn-primary mt-3">← Volver</a>
        </div>
    </div>
</div>
</body>
</html>
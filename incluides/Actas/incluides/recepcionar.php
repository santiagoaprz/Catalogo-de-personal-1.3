<?php
// modulos/actas/recepcionar.php
require_once __DIR__.'/../../auth_middleware.php';
requireAuth();
requireRole(['SISTEMAS', 'ADMIN', 'CAPTURISTA']);

require_once __DIR__.'/includes/actas_db.php';

$oficio_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $oficio_id) {
    if (recepcionarOficio($oficio_id, $_SESSION['user']['id'])) {
        registrarEvento($_SESSION['user']['id'], 'OFICIO_RECEPCIONADO', "Oficio ID $oficio_id recepcionado");
        $success = "Oficio recepcionado correctamente";
    } else {
        $error = "Error al recepcionar el oficio";
    }
}

$oficio = $oficio_id ? obtenerOficio($oficio_id) : null;
if (!$oficio) {
    header('Location: consultar.php?error=oficionoencontrado');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recepcionar Oficio</title>
    <style>
        .oficio-info { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; }
        .oficio-field { margin-bottom: 10px; }
        .oficio-label { font-weight: bold; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <?php include '../../header.php'; ?>
    
    <div class="container">
        <h2>Recepcionar Oficio</h2>
        
        <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="oficio-info">
            <div class="oficio-field">
                <span class="oficio-label">Folio:</span>
                <?= htmlspecialchars($oficio['folio']) ?>
            </div>
            <div class="oficio-field">
                <span class="oficio-label">Tipo:</span>
                <?= htmlspecialchars($oficio['tipo']) ?>
            </div>
            <div class="oficio-field">
                <span class="oficio-label">Remitente:</span>
                <?= htmlspecialchars($oficio['remitente_nombre']) ?>
            </div>
            <div class="oficio-field">
                <span class="oficio-label">Asunto:</span>
                <?= htmlspecialchars($oficio['asunto']) ?>
            </div>
            <div class="oficio-field">
                <span class="oficio-label">Contenido:</span>
                <p><?= nl2br(htmlspecialchars($oficio['contenido'])) ?></p>
            </div>
        </div>
        
        <?php if ($oficio['estado'] === 'Pendiente'): ?>
        <form method="post">
            <p>Al recepcionar, se registrará la fecha y hora actual y su nombre como receptor.</p>
            <button type="submit">Recepcionar Oficio</button>
        </form>
        <?php else: ?>
        <div class="info">
            Este oficio ya fue recepcionado el <?= $oficio['recepcion_fecha'] ?> 
            por <?= htmlspecialchars($oficio['recepcion_usuario_id'] ? obtenerNombreUsuario($oficio['recepcion_usuario_id']) : 'Desconocido') ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include '../../footer.php'; ?>
</body>
</html>

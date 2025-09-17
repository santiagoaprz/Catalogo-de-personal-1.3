<?php
// modulos/actas/responder.php
require_once __DIR__.'/../../auth_middleware.php';
requireAuth();
requireRole(['SISTEMAS', 'ADMIN', 'CAPTURISTA']);

require_once __DIR__.'/includes/actas_db.php';

$oficio_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $oficio_id) {
    $datos = [
        'respuesta' => filter_input(INPUT_POST, 'respuesta', FILTER_SANITIZE_STRING),
        'respondente_id' => $_POST['respondente_id'] == $_SESSION['user']['id'] ? 
            $_SESSION['user']['id'] : 
            filter_input(INPUT_POST, 'respondente_id', FILTER_VALIDATE_INT),
        'telefono' => filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING),
        'oficio_id' => $oficio_id
    ];
    
    if (responderOficio($datos['oficio_id'], $datos['respuesta'], $datos['respondente_id'], $datos['telefono'])) {
        registrarEvento($_SESSION['user']['id'], 'OFICIO_RESPONDIDO', "Oficio ID $oficio_id respondido");
        $success = "Respuesta registrada correctamente";
    } else {
        $error = "Error al registrar la respuesta";
    }
}

$oficio = $oficio_id ? obtenerOficio($oficio_id) : null;
if (!$oficio || $oficio['estado'] !== 'Recepcionado') {
    header('Location: consultar.php?error=oficionovalido');
    exit;
}

// Obtener posibles respondentes (mismo departamento que el destinatario)
$respondentes = ejecutarConsulta(
    "SELECT u.id, u.nombre_completo 
    FROM usuarios u
    JOIN usuarios d ON u.departamento = d.departamento
    WHERE d.id = ? AND u.activo = 1
    ORDER BY u.nombre_completo",
    [$oficio['destinatario_id']],
    "i"
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Responder Oficio</title>
    <style>
        .responder-container { max-width: 800px; margin: 0 auto; }
        .respuesta-form textarea { width: 100%; min-height: 200px; }
        .documentos-relacionados { margin-top: 20px; }
    </style>
</head>
<body>
    <?php include '../../header.php'; ?>
    
    <div class="container responder-container">
        <h2>Responder Oficio <?= htmlspecialchars($oficio['folio']) ?></h2>
        
        <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="oficio-info">
            <p><strong>Remitente:</strong> <?= htmlspecialchars($oficio['remitente_nombre']) ?></p>
            <p><strong>Asunto:</strong> <?= htmlspecialchars($oficio['asunto']) ?></p>
            <p><strong>Contenido:</strong> <?= nl2br(htmlspecialchars($oficio['contenido'])) ?></p>
        </div>
        
        <form method="post" class="respuesta-form">
            <div class="form-group">
                <label>¿Quién responde?</label>
                <select name="respondente_id" required>
                    <option value="<?= $_SESSION['user']['id'] ?>">Yo mismo (<?= htmlspecialchars($_SESSION['user']['nombre_completo']) ?>)</option>
                    <?php while ($resp = mysqli_fetch_assoc($respondentes)): ?>
                    <?php if ($resp['id'] != $_SESSION['user']['id']): ?>
                    <option value="<?= $resp['id'] ?>"><?= htmlspecialchars($resp['nombre_completo']) ?></option>
                    <?php endif; ?>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Teléfono de Oficina:</label>
                <input type="text" name="telefono" required>
            </div>
            
            <div class="form-group">
                <label>Respuesta:</label>
                <textarea name="respuesta" required></textarea>
            </div>
            
            <button type="submit">Registrar Respuesta</button>
        </form>
        
        <div class="documentos-relacionados">
            <h3>Documentos Relacionados</h3>
            <!-- Aquí iría la lista de PDFs relacionados -->
            <p>Funcionalidad para visualizar PDFs pendiente de implementar.</p>
        </div>
    </div>
    
    <?php include '../../footer.php'; ?>
</body>
</html>
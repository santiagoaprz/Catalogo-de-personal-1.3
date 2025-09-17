<?php
// modulos/actas/crear.php
require_once __DIR__.'/../../auth_middleware.php';
requireAuth();
requireRole(['SISTEMAS', 'ADMIN', 'CAPTURISTA']);

require_once __DIR__.'/includes/actas_db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar datos
    $datos = [
        'folio' => 'OF-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
        'tipo' => filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING),
        'remitente_id' => $_SESSION['user']['id'],
        'destinatario_id' => filter_input(INPUT_POST, 'destinatario_id', FILTER_VALIDATE_INT),
        'asunto' => filter_input(INPUT_POST, 'asunto', FILTER_SANITIZE_STRING),
        'contenido' => filter_input(INPUT_POST, 'contenido', FILTER_SANITIZE_STRING),
        'telefono' => filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING)
    ];
    
    // Validación adicional
    if (!in_array($datos['tipo'], ['Oficio', 'Circular', 'Nota Informativa', 'Copia de Conocimiento'])) {
        $error = 'Tipo de documento no válido';
    } else {
        $oficio_id = crearOficio($datos);
        if ($oficio_id) {
            registrarEvento($_SESSION['user']['id'], 'OFICIO_CREADO', "Oficio {$datos['folio']} creado");
            $success = "Oficio creado correctamente con folio: {$datos['folio']}";
        } else {
            $error = "Error al crear el oficio";
        }
    }
}

// Obtener destinatarios
$destinatarios = ejecutarConsulta(
    "SELECT id, nombre_completo, departamento FROM usuarios WHERE activo = 1 AND id != ? ORDER BY nombre_completo",
    [$_SESSION['user']['id']],
    "i"
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Oficio</title>
    <style>
        .form-acta { max-width: 800px; margin: 20px auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; }
        textarea { min-height: 150px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <?php include '../../header.php'; ?>
    
    <div class="container">
        <h2>Crear Nuevo Oficio</h2>
        
        <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="post" class="form-acta">
            <div class="form-group">
                <label>Tipo de Documento:</label>
                <select name="tipo" required>
                    <option value="">Seleccione...</option>
                    <option value="Oficio">Oficio</option>
                    <option value="Circular">Circular</option>
                    <option value="Nota Informativa">Nota Informativa</option>
                    <option value="Copia de Conocimiento">Copia de Conocimiento</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Destinatario:</label>
                <select name="destinatario_id" required>
                    <option value="">Seleccione...</option>
                    <?php while ($dest = mysqli_fetch_assoc($destinatarios)): ?>
                    <option value="<?= $dest['id'] ?>">
                        <?= htmlspecialchars($dest['nombre_completo']) ?> - <?= htmlspecialchars($dest['departamento']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Teléfono de Oficina:</label>
                <input type="text" name="telefono" required>
            </div>
            
            <div class="form-group">
                <label>Asunto:</label>
                <input type="text" name="asunto" required>
            </div>
            
            <div class="form-group">
                <label>Contenido:</label>
                <textarea name="contenido" required></textarea>
            </div>
            
            <button type="submit">Generar Oficio</button>
        </form>
    </div>
    
    <?php include '../../footer.php'; ?>
</body>
</html>
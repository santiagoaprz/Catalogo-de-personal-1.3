<?php
require 'session_config.php';
require 'auth_middleware.php';
requireAuth();
require 'database.php';

// Verificar si se recibió un ID válido
$documento_id = $_GET['id'] ?? null;

if (!$documento_id || !is_numeric($documento_id)) {
    die("ID de documento no válido");
}

// Consulta segura con prepared statement
$query = "SELECT * FROM documentos WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $documento_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$documento = mysqli_fetch_assoc($result);

if (!$documento) {
    die("Documento no encontrado");
}

// Consulta para el historial
$historial_query = "SELECT * FROM historial_departamentos WHERE documento_id = ? ORDER BY fecha_cambio DESC";
$historial_stmt = mysqli_prepare($conn, $historial_query);
mysqli_stmt_bind_param($historial_stmt, "i", $documento_id);
mysqli_stmt_execute($historial_stmt);
$historial_result = mysqli_stmt_get_result($historial_stmt);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seguimiento Documental</title>
    <style>
        /* Estilos consistentes con el sistema */
        .document-info, .history-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .history-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h2>Seguimiento Documental</h2>
        
        <div class="document-info">
            <h3>Documento: <?= htmlspecialchars($documento['numero_oficio']) ?></h3>
            <p><strong>Asunto:</strong> <?= htmlspecialchars($documento['asunto']) ?></p>
            <p><strong>Estado actual:</strong> <?= htmlspecialchars($documento['estatus']) ?></p>
            <p><strong>Etapa:</strong> <?= htmlspecialchars($documento['etapa']) ?></p>
        </div>
        
        <div class="history-section">
            <h3>Historial de Movimientos</h3>
            
            <?php if (mysqli_num_rows($historial_result) > 0): ?>
                <?php while ($historial = mysqli_fetch_assoc($historial_result)): ?>
                <div class="history-item">
                    <p><strong>Fecha:</strong> <?= htmlspecialchars($historial['fecha_cambio']) ?></p>
                    <p><strong>Cambio:</strong> De <?= htmlspecialchars($historial['departamento_anterior']) ?> 
                    a <?= htmlspecialchars($historial['departamento_nuevo']) ?></p>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No hay historial registrado para este documento.</p>
            <?php endif; ?>
        </div>
        
        <!-- Formulario para cambiar etapa/estatus -->
        <?php if (in_array($_SESSION['user']['rol'], ['SISTEMAS', 'ADMIN'])): ?>
        <div class="update-section">
            <h3>Actualizar Estado</h3>
            <form action="actualizar_etapa.php" method="post">
                <input type="hidden" name="documento_id" value="<?= $documento_id ?>">
                
                <div class="form-group">
                    <label for="nuevo_estatus">Nuevo Estatus:</label>
                    <select name="nuevo_estatus" id="nuevo_estatus" required>
                        <option value="SEGUIMIENTO" <?= $documento['estatus'] === 'SEGUIMIENTO' ? 'selected' : '' ?>>Seguimiento</option>
                        <option value="ATENDIDO" <?= $documento['estatus'] === 'ATENDIDO' ? 'selected' : '' ?>>Atendido</option>
                        <option value="TURNADO" <?= $documento['estatus'] === 'TURNADO' ? 'selected' : '' ?>>Turnado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="nuevo_departamento">Departamento Destino:</label>
                    <input type="text" name="nuevo_departamento" id="nuevo_departamento" 
                           value="<?= htmlspecialchars($documento['depto_remitente'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn">Actualizar Estado</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// modulos/actas/detalle.php
require_once __DIR__.'/../../auth_middleware.php';
requireAuth();

require_once __DIR__.'/includes/actas_db.php';

$oficio_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$oficio = $oficio_id ? obtenerOficio($oficio_id) : null;

if (!$oficio) {
    header('Location: consultar.php?error=oficionoencontrado');
    exit;
}

// Verificar permisos de visualización
$rol = $_SESSION['user']['rol'];
$usuario_id = $_SESSION['user']['id'];
if ($rol === 'CAPTURISTA' && 
    $oficio['remitente_id'] != $usuario_id && 
    $oficio['destinatario_id'] != $usuario_id) {
    header('Location: consultar.php?error=permisos');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Oficio <?= htmlspecialchars($oficio['folio']) ?></title>
    <style>
        .detalle-container { max-width: 800px; margin: 0 auto; }
        .seccion { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; }
        .seccion h3 { margin-top: 0; }
        .campo { margin-bottom: 10px; }
        .campo label { font-weight: bold; display: inline-block; width: 150px; }
        .acciones { margin-top: 20px; }
        .documentos-relacionados { margin-top: 30px; }
    </style>
</head>
<body>
    <?php include '../../header.php'; ?>
    
    <div class="container detalle-container">
        <h2>Oficio: <?= htmlspecialchars($oficio['folio']) ?></h2>
        
        <div class="seccion">
            <h3>Información Básica</h3>
            <div class="campo">
                <label>Tipo:</label>
                <?= htmlspecialchars($oficio['tipo']) ?>
            </div>
            <div class="campo">
                <label>Estado:</label>
                <?= htmlspecialchars($oficio['estado']) ?>
            </div>
            <div class="campo">
                <label>Fecha Creación:</label>
                <?= $oficio['fecha_creacion'] ?>
            </div>
        </div>
        
        <div class="seccion">
            <h3>Remitente</h3>
            <div class="campo">
                <label>Nombre:</label>
                <?= htmlspecialchars($oficio['remitente_nombre']) ?>
            </div>
            <div class="campo">
                <label>Teléfono:</label>
                <?= htmlspecialchars($oficio['telefono_remitente']) ?>
            </div>
        </div>
        
        <div class="seccion">
            <h3>Destinatario</h3>
            <div class="campo">
                <label>Nombre:</label>
                <?= htmlspecialchars($oficio['destinatario_nombre']) ?>
            </div>
        </div>
        
        <div class="seccion">
            <h3>Contenido</h3>
            <div class="campo">
                <label>Asunto:</label>
                <?= htmlspecialchars($oficio['asunto']) ?>
            </div>
            <div class="campo">
                <label>Mensaje:</label>
                <p><?= nl2br(htmlspecialchars($oficio['contenido'])) ?></p>
            </div>
        </div>
        
        <?php if ($oficio['estado'] === 'Recepcionado' || $oficio['estado'] === 'Respondido'): ?>
        <div class="seccion">
            <h3>Recepción</h3>
            <div class="campo">
                <label>Fecha/Hora:</label>
                <?= $oficio['recepcion_fecha'] ?>
            </div>
            <div class="campo">
                <label>Receptor:</label>
                <?= htmlspecialchars(obtenerNombreUsuario($oficio['recepcion_usuario_id'])) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($oficio['estado'] === 'Respondido' || $oficio['estado'] === 'Finalizado'): ?>
        <div class="seccion">
            <h3>Respuesta</h3>
            <div class="campo">
                <label>Respondente:</label>
                <?= htmlspecialchars($oficio['respondente_nombre']) ?>
            </div>
            <div class="campo">
                <label>Teléfono:</label>
                <?= htmlspecialchars($oficio['telefono_respondente']) ?>
            </div>
            <div class="campo">
                <label>Fecha Respuesta:</label>
                <?= $oficio['fecha_respuesta'] ?>
            </div>
            <div class="campo">
                <label>Contenido:</label>
                <p><?= nl2br(htmlspecialchars($oficio['respuesta'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($oficio['estado'] === 'Finalizado'): ?>
        <div class="seccion">
            <h3>Acuse</h3>
            <div class="campo">
                <label>Fecha Finalización:</label>
                <?= $oficio['acuse_fecha'] ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="acciones">
            <?php if ($rol !== 'CAPTURISTA'): ?>
                <?php if ($oficio['estado'] === 'Pendiente'): ?>
                <a href="recepcionar.php?id=<?= $oficio['id'] ?>" class="btn">Recepcionar</a>
                <?php elseif ($oficio['estado'] === 'Recepcionado'): ?>
                <a href="responder.php?id=<?= $oficio['id'] ?>" class="btn">Responder</a>
                <?php elseif ($oficio['estado'] === 'Respondido'): ?>
                <a href="acuse.php?id=<?= $oficio['id'] ?>" class="btn">Generar Acuse</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="consultar.php" class="btn">Volver</a>
        </div>
        
        <div class="documentos-relacionados">
            <h3>Documentos Relacionados</h3>
            <!-- Lista de PDFs asociados -->
            <p>Funcionalidad para visualizar PDFs pendiente de implementar.</p>
        </div>
    </div>
    
    <?php include '../../footer.php'; ?>
</body>
</html>
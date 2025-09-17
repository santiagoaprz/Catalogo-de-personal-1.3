<?php
// modulos/actas/acuse.php
require_once __DIR__.'/../../auth_middleware.php';
requireAuth();
requireRole(['SISTEMAS', 'ADMIN', 'CAPTURISTA']);

require_once __DIR__.'/includes/actas_db.php';
require_once __DIR__.'/includes/actas_pdf.php';

$oficio_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$oficio = $oficio_id ? obtenerOficio($oficio_id) : null;

if (!$oficio || $oficio['estado'] !== 'Respondido') {
    header('Location: consultar.php?error=oficionovalido');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (generarAcuse($oficio_id)) {
        registrarEvento($_SESSION['user']['id'], 'ACUSE_GENERADO', "Acuse para oficio {$oficio['folio']}");
        
        // Generar PDF del acuse
        $pdf_path = generarPDFAcuse($oficio);
        
        header("Location: detalle.php?id=$oficio_id&success=acuse");
        exit;
    } else {
        $error = "Error al generar el acuse";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generar Acuse</title>
    <style>
        .acuse-preview { border: 1px solid #ddd; padding: 20px; margin: 20px 0; }
        .acuse-header { text-align: center; margin-bottom: 20px; }
        .acuse-content { margin: 30px 0; }
        .acuse-footer { margin-top: 50px; text-align: right; }
    </style>
</head>
<body>
    <?php include '../../header.php'; ?>
    
    <div class="container">
        <h2>Generar Acuse para Oficio <?= htmlspecialchars($oficio['folio']) ?></h2>
        
        <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="acuse-preview">
            <div class="acuse-header">
                <h3>ACUSE DE RECEPCIÓN Y RESPUESTA</h3>
                <p>Folio: <?= htmlspecialchars($oficio['folio']) ?></p>
            </div>
            
            <div class="acuse-content">
                <p>Se informa que el oficio con folio <strong><?= htmlspecialchars($oficio['folio']) ?></strong>, 
                recibido el día <strong><?= $oficio['recepcion_fecha'] ?></strong>, ha sido atendido y 
                respondido satisfactoriamente el día <strong><?= date('Y-m-d') ?></strong>.</p>
                
                <p><strong>Asunto:</strong> <?= htmlspecialchars($oficio['asunto']) ?></p>
                
                <p><strong>Respuesta proporcionada por:</strong> 
                <?= htmlspecialchars($oficio['respondente_nombre']) ?> (Tel: <?= htmlspecialchars($oficio['telephone_respondente']) ?>)</p>
            </div>
            
            <div class="acuse-footer">
                <p>Atentamente,</p>
                <p><strong><?= htmlspecialchars($_SESSION['user']['nombre_completo']) ?></strong></p>
                <p>Departamento de <?= htmlspecialchars($_SESSION['user']['departamento']) ?></p>
            </div>
        </div>
        
        <form method="post">
            <p>Al confirmar, se marcará este oficio como finalizado y se generará el documento oficial.</p>
            <button type="submit">Confirmar y Generar Acuse</button>
            <a href="detalle.php?id=<?= $oficio['id'] ?>" class="btn">Cancelar</a>
        </form>
    </div>
    
    <?php include '../../footer.php'; ?>
</body>
</html>
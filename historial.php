<?php
// Evitar que la conexión se cierre prematuramente
register_shutdown_function(function() {
    global $conn;
    if (isset($conn)) {
        $conn = null;
    }
});

require 'session_config.php';
require 'auth_middleware.php';
requireAuth();
require 'database.php';
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Consulta optimizada para historial.php
$query = "SELECT 
    d.id, 
    d.numero_oficio,
    COALESCE(d.numero_oficio_usuario, d.numero_oficio) AS numero_oficio_mostrar,
    d.remitente,
    COALESCE(d.email_institucional, cp.email_institucional, 'No especificado') AS email_institucional,
    d.asunto, 
    d.tipo,
    d.jud_destino,
    d.estatus,
    DATE_FORMAT(d.fecha_entrega, '%d/%m/%Y') AS fecha_entrega_format,
    d.pdf_url,
    u.username AS usuario_registro
FROM documentos d
LEFT JOIN catalogo_personal cp ON (
    cp.email_institucional = d.email_institucional OR 
    cp.numero_empleado = d.numero_empleado
)
LEFT JOIN usuarios u ON d.usuario_registra = u.id
WHERE d.id IS NOT NULL
ORDER BY d.id DESC, d.fecha_creacion DESC"; 

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error en la consulta: " . mysqli_error($conn));
}

$total_registros = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Historial Completo de Documentos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }
        h1 {
            color: #5D2E36;
            border-bottom: 2px solid #722F37;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #722F37;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #a0bed4;
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        tr:nth-child(even) {
            background-color: #f8f8f8;
        }
        tr:hover {
            background-color: #f0f0f0;
        }
        .pdf-link {
            color: #5D2E36;
            font-weight: bold;
            text-decoration: none;
        }
        .pdf-link:hover {
            text-decoration: underline;
        }
        .estatus-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
            color: white;
        }
        .estatus-seguimiento {
            background-color: #f39c12;
        }
        .estatus-atendido {
            background-color: #2ecc71;
        }
        .estatus-turnado {
            background-color: #3498db;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
            background-color: #f9f9f9;
            border: 1px dashed #ccc;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Historial Completo de Documentos</h1>
    
    <div class="info-box">
        Total de documentos registrados: <?= $total_registros ?>
    </div>

    <?php if ($total_registros === 0): ?>
        <div class="no-data">
            <p>No se encontraron documentos registrados en el sistema.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>N° Oficio</th>
                    <th>Remitente</th>
                    <th>Email</th>
                    <th>N° Empleado</th>
                    <th>JUD Destino</th>
                    <th>Asunto</th>
                    <th>Tipo</th>
                    <th>Estatus</th>
                    <th>Fecha Creación</th>
                    <th>Registrado por</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
    <?php while ($row = mysqli_fetch_assoc($result)): 
        // Preprocesar datos
        $numero_empleado = $row['numero_empleado'] ?? 'S/N';
        $fecha_creacion = isset($row['fecha_creacion']) ? 
            date('d/m/Y H:i', strtotime($row['fecha_creacion'])) : 'Sin fecha';
        $pdf_url = isset($row['pdf_url']) ? '/SISTEMA_OFICIOS/pdfs/' . basename($row['pdf_url']) : '';
    ?>
    <tr>
        <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
        <td><?= htmlspecialchars($row['numero_oficio_mostrar'] ?? '') ?></td>
        <td><?= htmlspecialchars($row['remitente'] ?? '') ?></td>
        <td><?= htmlspecialchars($row['email_institucional'] ?? '') ?></td>
        <td><?= htmlspecialchars($numero_empleado) ?></td>
        <td><?= htmlspecialchars($row['jud_destino'] ?? '') ?></td>
        <td>
            <?php 
            $asunto = $row['asunto'] ?? '';
            echo htmlspecialchars(mb_substr($asunto, 0, 50, 'UTF-8'));
            echo mb_strlen($asunto, 'UTF-8') > 50 ? '...' : ''; 
            ?>
        </td>
        <td><?= htmlspecialchars($row['tipo'] ?? '') ?></td>
        <td>
            <span class="estatus-badge estatus-<?= strtolower($row['estatus'] ?? '') ?>">
                <?= htmlspecialchars($row['estatus'] ?? '') ?>
            </span>
        </td>
        <td><?= htmlspecialchars($fecha_creacion) ?></td>
        <td><?= htmlspecialchars($row['usuario_registro'] ?? '') ?></td>
        <td>
            <?php if (!empty($pdf_url)): ?>
                <a href="<?= htmlspecialchars($pdf_url) ?>" target="_blank" class="pdf-link">Ver PDF</a>
            <?php else: ?>
                <span style="color: #999;">No disponible</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
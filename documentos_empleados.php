<?php
require 'session_config.php';
require 'auth_middleware.php';
requireAuth();
require 'database.php';
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$email_institucional = $_GET['email_institucional'] ?? '';
if (empty($email_institucional)) {
    header('Location: catalogo.php');
    exit;
}

// Obtener información del empleado
$empleado_query = "SELECT * FROM catalogo_personal WHERE email_institucional = ?";
$stmt_emp = mysqli_prepare($conn, $empleado_query);
mysqli_stmt_bind_param($stmt_emp, 's', $email_institucional);
mysqli_stmt_execute($stmt_emp);
$empleado = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_emp));

// Obtener documentos del empleado
$documentos_query = "SELECT * FROM documentos WHERE email_institucional = ? ORDER BY fecha_entrega DESC";
$stmt_doc = mysqli_prepare($conn, $documentos_query);
mysqli_stmt_bind_param($stmt_doc, 's', $email_institucional);
mysqli_stmt_execute($stmt_doc);
$documentos = mysqli_stmt_get_result($stmt_doc);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Estilos similares a catalogo.php -->
</head>
<body>
    <h1>Documentos de <?= htmlspecialchars($empleado['nombre']) ?></h1>
    <h2>N° Empleado: <?= htmlspecialchars($empleado['email_institucional']) ?></h2>
    
    <table>
        <thead>
            <tr>
                <th>N° Oficio</th>
                <th>Fecha</th>
                <th>Asunto</th>
                <th>Tipo</th>
                <th>Estatus</th>
                <th>PDF</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($doc = mysqli_fetch_assoc($documentos)): ?>
            <tr>
                <td><?= htmlspecialchars($doc['numero_oficio']) ?></td>
                <td><?= htmlspecialchars($doc['fecha_entrega']) ?></td>
                <td><?= htmlspecialchars($doc['asunto']) ?></td>
                <td><?= htmlspecialchars($doc['tipo']) ?></td>
                <td><?= htmlspecialchars($doc['estatus']) ?></td>
                <td><a href="<?= htmlspecialchars($doc['pdf_url']) ?>" target="_blank">Ver</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <a href="catalogo.php" class="btn">Volver al Catálogo</a>
</body>
</html>
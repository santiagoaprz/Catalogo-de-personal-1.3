<?php
require 'session_config.php';
require 'auth_middleware.php';
requireAuth();
requireRole(['ADMIN', 'AUDITOR']); // Solo roles con permiso
require 'database.php';

$query = "SELECT 
    a.*,
    u.username
FROM auditoria_oficios a
JOIN usuarios u ON a.usuario_id = u.id
ORDER BY a.fecha_registro DESC
LIMIT 200";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Auditoría de Oficios</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #5D2E36; color: white; }
    </style>
</head>
<body>
    <h1>Registros de Auditoría</h1>
    <table>
        <thead>
            <tr>
                <th>Fecha/Hora</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Oficio Sistema</th>
                <th>Oficio Usuario</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= htmlspecialchars($row['fecha_registro']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['accion']) ?></td>
                <td><?= htmlspecialchars($row['numero_oficio']) ?></td>
                <td><?= htmlspecialchars($row['numero_oficio_usuario']) ?></td>
                <td><?= htmlspecialchars($row['ip_address']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
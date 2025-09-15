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
// Configuración para GROUP_CONCAT
mysqli_query($conn, "SET SESSION group_concat_max_len = 1000000;");

// CONSULTA MEJORADA - Relaciones correctas y optimizada
$query = "SELECT 
    cp.id,
    cp.email_institucional,
    cp.nombre,
    cp.puesto,
    cp.departamento_jud AS departamento_actual,
    cp.telefono,
    cp.extension,
    cp.numero_empleado,
    COUNT(d.id) AS total_documentos,
    (
        SELECT GROUP_CONCAT(
            DISTINCT CONCAT(
                DATE_FORMAT(hd.fecha_cambio, '%d/%m/%Y'), 
                ': ', 
                IFNULL(hd.departamento_anterior, 'Nuevo ingreso'), 
                ' → ', 
                hd.departamento_nuevo
            )
            ORDER BY hd.fecha_cambio DESC
            SEPARATOR ' | '
        )
        FROM historial_departamentos hd
        WHERE hd.personal_id = cp.id
        GROUP BY hd.personal_id
    ) AS historial_deptos,
    MAX(d.fecha_creacion) AS ultimo_documento
FROM catalogo_personal cp
LEFT JOIN documentos d ON d.personal_id = cp.id
GROUP BY cp.id, cp.email_institucional, cp.nombre, cp.puesto, 
         cp.departamento_jud, cp.telefono, cp.extension, cp.numero_empleado
ORDER BY cp.nombre ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error en la consulta: " . mysqli_error($conn));
}

// Registro de diagnóstico
$debug_info = [
    'fecha' => date('Y-m-d H:i:s'),
    'registros' => mysqli_num_rows($result),
    'consulta' => $query,
    'error' => mysqli_error($conn)
];
file_put_contents('catalogo_debug.log', print_r($debug_info, true), FILE_APPEND);
?>

<!-- [El resto del HTML permanece igual] -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Personal</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #a0bed4;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.9em;
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #a0bed4;
            color: white;
            position: sticky;
            top: 0;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .btn {
            display: inline-block;
            background-color: #a0bed4;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #a0bed4;
        }
        .historial-cell {
            max-width: 300px;
            white-space: normal;
            font-size: 0.85em;
            line-height: 1.4;
        }
        .depto-actual {
            font-weight: bold;
            color: #5D2E36;
        }
        .document-count {
            background-color: #5D2E36;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        .historial-item {
            margin-bottom: 5px;
            padding: 3px;
            background-color: #f0f0f0;
            border-radius: 3px;
        }
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            .historial-cell {
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Catálogo de Personal</h1>
        <h2>Relación de Personal con Historial de Departamentos</h2>
        
        <a href="nuevo_personal.php" class="btn">➕ Agregar Nuevo Personal</a>
        
        <table>
            <thead>
                <tr>
                    <th>Correo institucional</th>
                    <th>Nombre</th>
                    <th>Puesto</th>
                    <th>Depto. Actual</th>
                    <th>Teléfono</th>
                    <th>Extensión</th>
                    <th>Documentos</th>
                    <th>Historial de Departamentos</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['email_institucional']) ?></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['puesto']) ?></td>
                    <td class="depto-actual"><?= htmlspecialchars($row['departamento_actual']) ?></td>
                    <td><?= htmlspecialchars($row['telefono']) ?></td>
                    <td><?= htmlspecialchars($row['extension']) ?></td>
                    <td><span class="document-count"><?= $row['total_documentos'] ?></span></td>
                    <td class="historial-cell">
                        <?php if (!empty($row['historial_deptos'])): ?>
                            <?php 
                            $historial_items = explode(' | ', $row['historial_deptos']);
                            foreach ($historial_items as $item): 
                            ?>
                                <div class="historial-item"><?= htmlspecialchars($item) ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            Sin historial registrado
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
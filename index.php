<?php
// Evitar que la conexión se cierre prematuramente
register_shutdown_function(function() {
    global $conn;
    if (isset($conn)) {
        $conn = null; // Limpiar sin cerrar explícitamente
    }
});

require 'session_config.php';
require 'auth_middleware.php';
requireAuth();
require 'database.php';
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

function generarNumeroAutomatico() {
    global $conn;
    $query = "SELECT MAX(CAST(SUBSTRING(numero_empleado, 5) AS UNSIGNED)) as max_num FROM catalogo_personal";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $next_num = ($row['max_num'] ?? 0) + 1;
    return 'EMP-' . str_pad($next_num, 5, '0', STR_PAD_LEFT);
}


// Obtener el próximo número de oficio
$numero_oficio = "OF-00001"; // Valor por defecto
$secuencia_query = "SELECT ultimo_numero FROM secuencia_oficios LIMIT 1";
$secuencia_result = mysqli_query($conn, $secuencia_query);
if ($secuencia_row = mysqli_fetch_assoc($secuencia_result)) {
    $proximo_numero = $secuencia_row['ultimo_numero'] + 1;
    $numero_oficio = "OF-" . str_pad($proximo_numero, 5, '0', STR_PAD_LEFT);
    $numero_oficio_usuario = mysqli_real_escape_string($conn, $_POST['numero_oficio_usuario'] ?? ''); 
}


// Verificar autenticación
if (!isset($_SESSION['user'])) {
    header('Location: login_form.php');
    exit;
}

$query = "SELECT 
    d.id,
    d.numero_oficio AS numero_automatico,
    d.numero_oficio_usuario AS numero_manual,
    COALESCE(d.numero_oficio_usuario, d.numero_oficio) AS numero_oficio_mostrar,
    DATE_FORMAT(d.fecha_creacion, '%d/%m/%Y %H:%i') AS fecha_creacion_format,
    DATE_FORMAT(d.fecha_entrega, '%d/%m/%Y') AS fecha_entrega_format,
    IFNULL(cp.nombre, d.remitente) AS remitente,
    cp.numero_empleado,  -- Se usa siempre el número del catálogo
    COALESCE(d.email_institucional, 'No especificado') AS email_institucional,
    d.jud_destino,
    d.asunto,
    d.tipo,
    d.estatus,
    CONCAT('/SISTEMA_OFICIOS/pdfs/', SUBSTRING_INDEX(d.pdf_url, '/', -1)) AS pdf_url_completo,
    u.username AS registrado_por
FROM documentos d
LEFT JOIN usuarios u ON d.usuario_registra = u.id
LEFT JOIN catalogo_personal cp ON TRIM(LOWER(cp.email_institucional)) = TRIM(LOWER(d.email_institucional))
WHERE d.remitente IS NOT NULL
ORDER BY d.fecha_creacion DESC, d.id DESC
LIMIT 200";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error en la consulta: " . mysqli_error($conn));
}

// Obtener lista de departamentos JUD
$jud_query = "SELECT nombre FROM jud_departamentos WHERE activo = TRUE ORDER BY nombre";
$jud_result = mysqli_query($conn, $jud_query);
$departamentos_jud = [];
while ($row = mysqli_fetch_assoc($jud_result)) {
    $departamentos_jud[] = $row['nombre'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Oficios - Alcaldía</title>
    <style>
        :root {
            --primary-color: #a0bed4;
            --secondary-color: #2ecc71;
            --danger-color: #e74c3c;
            --dark-color: #a0bed4;
            --light-color: #f9f9f9;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
            padding-top: 180px;
        }
        
        .header-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background-color: var(--dark-color);
            color: white;
            padding: 15px 0;
        }
        
        .header-content {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header img {
            height: 200px;
            width: auto;
        }
        
        .header-text h1 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .header-text p {
            margin: 5px 0 0;
            font-size: 1em;
            font-weight: 500;
        }
        
        .menu {
            background-color: var(--dark-color);
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 0;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .welcome-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .welcome-msg {
            font-size: 1.2em;
            color: var(--dark-color);
        }
        
        .role-badge {
            background-color: var(--danger-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .card-title {
            color: var(--dark-color);
            margin-top: 0;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 1em;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--secondary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #27ae60;
        }
        
        .table-container {
            width: 100%;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }
        
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
            word-wrap: break-word;
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        td:nth-child(1), th:nth-child(1) { width: 7%; }  /* N° Oficio */
        td:nth-child(2), th:nth-child(2) { width: 7%; }  /* Fecha Creación */
        td:nth-child(3), th:nth-child(3) { width: 7%; }  /* Fecha Entrega */
        td:nth-child(4), th:nth-child(4) { width: 10%; } /* Remitente */
        td:nth-child(5), th:nth-child(5) { width: 15%; } /* Correo Institucional */
        td:nth-child(6), th:nth-child(6) { width: 12%; } /* JUD Destino */
        td:nth-child(7), th:nth-child(7) { width: 15%; } /* Asunto */
        td:nth-child(8), th:nth-child(8) { width: 6%; }  /* Tipo */
        td:nth-child(9), th:nth-child(9) { width: 8%; }  /* Estatus */
        td:nth-child(10), th:nth-child(10) { width: 6%; } /* PDF */
        td:nth-child(11), th:nth-child(11) { width: 15%; } /* Acciones/Usuario */
        
        .action-link {
            color: var(--primary-color);
            text-decoration: none;
            margin-right: 8px;
            font-weight: 600;
            white-space: nowrap;
            display: inline-block;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .delete-link {
            color: var(--danger-color);
        }
        
        .estatus-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 600;
            color: white;
            text-align: center;
            min-width: 80px;
        }
        
        .text-muted {
            color: #6c757d;
            font-style: italic;
        }
        
        @media (max-width: 1200px) {
            td:nth-child(5), th:nth-child(5) { width: 15%; }
        }
        
        @media (max-width: 992px) {
            td:nth-child(2), th:nth-child(2) { width: 10%; }
            td:nth-child(4), th:nth-child(4) { width: 12%; }
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 220px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .menu {
                flex-direction: column;
                gap: 10px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            table {
                font-size: 0.9em;
            }
            
            th, td {
                padding: 8px 6px;
            }

            fecha {
                white-space: nowrap;
                font-size: 0.9em;
            }
        
            .usuario-registro {
                font-size: 0.85em;
                color: #666;
            }
        }
    </style>
</head>
<body>
    <!-- Encabezado fijo -->
    <div class="header-container">
    <header class="header">
        <div class="header-content">
            <div class="header-text">
                <h1>JUD SISTEMAS</h1>
                <p>4.7 C1-MÓDULO DE INFORMES</p>
            </div>
        </div>
    </header>
    
    <nav class="menu">
        <?php generarMenu(); ?>
    </nav>
</div>
    
    <!-- Contenido principal -->
    <main class="container">
        <div class="welcome-container">
            <div class="welcome-msg">
                Bienvenido, <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong>
                <span class="role-badge"><?= $_SESSION['user']['rol'] ?></span>
            </div>
            <a href="logout.php" class="btn" style="background-color: var(--danger-color);">Cerrar Sesión</a>
        </div>

        <?php if (in_array($_SESSION['user']['rol'], ['SISTEMAS', 'ADMIN', 'CAPTURISTA'])): ?>
        <section class="card">
            <h2 class="card-title">➕ Nuevo Oficio</h2>
            <form action="guardar.php" method="post" enctype="multipart/form-data" id="oficioForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="fecha_creacion">Fecha de creación del oficio</label>
                        <input type="date" id="fecha_creacion" name="fecha_creacion" required value="<?= date('Y-m-d') ?>">
                    </div>    
                
                    <div class="form-group">
                        <label for="fecha_entrega">Fecha del sello de Entrega</label>
                        <input type="date" id="fecha_entrega" name="fecha_entrega" required value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_oficio_usuario">Oficio:</label>
                        <input type="text" class="form-control" id="numero_oficio_usuario" name="numero_oficio_usuario" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="remitente">Remitente</label>
                        <input type="text" id="remitente" name="remitente" required>
                    </div>

                    <div class="form-group">
    <label for="email_institucional">Correo institucional</label>
    <input type="email" id="email_institucional" name="email_institucional" required
        placeholder="usuario@tlalpan.cdmx.gob.mx"
        onblur="validarCorreo(this)">
    <small id="emailHelp" class="text-muted">Debe ser un correo institucional válido</small>
</div>

<input type="hidden" name="numero_empleado" id="numero_empleado" value="<?= generarNumeroAutomatico() ?>">
                    
                    <div class="form-group">
                        <label for="cargo_remitente">Cargo</label>
                        <input type="text" id="cargo_remitente" name="cargo_remitente" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="depto_remitente">Depto del Remitente</label>
                        <select id="depto_remitente" name="depto_remitente" required>
                            <option value="">Seleccione un Depto</option>
                            <?php foreach ($departamentos_jud as $jud): ?>
                                <option value="<?= htmlspecialchars($jud) ?>"><?= htmlspecialchars($jud) ?></option>
                            <?php endforeach; ?>
                            <option value="OTRO">Otro (especificar)</option>
                        </select>
                        <input type="text" id="depto_remitente_otro" name="depto_remitente_otro" 
                                style="margin-top: 5px; display: none;" placeholder="Especifique el Depto">
                    </div>
                    
                    <div class="form-group">
                        <label for="dire_fisica">Dirección Física</label>
                        <input type="text" id="dire_fisica" name="dire_fisica" required>
                    </div>

                    <div class="form-group">
                        <label for="jud_destino">Depto Destinatario</label>
                        <select id="jud_destino" name="jud_destino" required>
                            <option value="">Seleccione un Depto</option>
                            <?php foreach ($departamentos_jud as $jud): ?>
                                <option value="<?= htmlspecialchars($jud) ?>"><?= htmlspecialchars($jud) ?></option>
                            <?php endforeach; ?>
                            <option value="OTRO">Otro (especificar)</option>
                        </select>
                        <input type="text" id="jud_destino_otro" name="jud_destino_otro" 
                                style="margin-top: 5px; display: none;" placeholder="Especifique el Depto">
                    </div>
                
                    <div class="form-group">
                        <label for="trabajador_destino">Nombre del Trabajador Destino</label>
                        <input type="text" id="trabajador_destino" name="trabajador_destino" 
                                placeholder="Nombre completo del destinatario">
                    </div>
                    
                    <div class="form-group">
                        <label for="asunto">Asunto</label>
                        <textarea id="asunto" name="asunto" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo" required>
                            <option value="OFICIO">Oficio</option>
                            <option value="TURNO">Turno</option>
                            <option value="CIRCULAR">Circular</option>
                            <option value="NOTA_INFORMATIVA">Nota Informativa</option>
                            <option value="CONOCIMIENTO">Conocimiento</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estatus">Estatus</label>
                        <select id="estatus" name="estatus" required>
                            <option value="SEGUIMIENTO">Seguimiento</option>
                            <option value="ATENDIDO">Atendido</option>
                            <option value="TURNADO">Turnado</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" required>
                    </div>

                    <div class="form-group">
                        <label for="extension">Extensión</label>
                        <input type="text" id="extension" name="extension" pattern="[0-9]{1,5}" title="Solo números, máximo 5 dígitos">
                    </div>
                    
                    <div class="form-group">
                        <label for="pdf_file">Subir PDF</label>
                        <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">Guardar Oficio</button>
            </form>
        </section>
        <?php endif; ?>

        <section class="card">
            <h2 class="card-title">📋 Oficios Registrados</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Num de oficio</th>   
                            <th>Remitente</th>
                            <th>Correo Institucional</th>
                            <th>Depto Destino</th>
                            <th>Asunto</th>
                            <th>Tipo</th>
                            <th>Estatus</th>
                            <th>PDF</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td><?= htmlspecialchars($row['numero_oficio_mostrar'] ?? 'N/A') ?></td>
        <td><?= htmlspecialchars($row['remitente']) ?></td>
        <td><?= htmlspecialchars($row['email_institucional']) ?></td>
        <td><?= htmlspecialchars($row['jud_destino']) ?></td>
        <td><?= htmlspecialchars($row['asunto']) ?></td>
        <td><?= htmlspecialchars($row['tipo']) ?></td>
                            <td>
                                <span class="estatus-badge" style="background-color: <?= 
                                    $row['estatus'] === 'ATENDIDO' ? '#2ecc71' : 
                                    ($row['estatus'] === 'TURNADO' ? '#3498db' : '#f39c12') 
                                ?>;">
                                    <?= htmlspecialchars($row['estatus']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($row['pdf_url_completo'])): ?>
                                    <a href="<?= htmlspecialchars($row['pdf_url_completo']) ?>" 
                                        target="_blank" 
                                        class="action-link"
                                        title="Ver documento PDF">
                                        📄 Ver
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No disponible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($_SESSION['user']['rol'] === 'SISTEMAS'): ?>
                                    <a href="editar.php?id=<?= $row['id'] ?>" 
                                        class="action-link"
                                        title="Editar oficio">
                                        ✏️ Editar
                                    </a>
                                    <a href="eliminar.php?id=<?= $row['id'] ?>" 
                                        class="action-link delete-link"
                                        title="Eliminar oficio"
                                        onclick="return confirm('¿Estás seguro de eliminar este oficio?')">
                                        🗑️ Eliminar
                                    </a>
                                <?php else: ?>
                                    <a href="detalle.php?id=<?= $row['id'] ?>" 
                                        class="action-link"
                                        title="Ver detalles">
                                        🔍 Ver
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
    // Mostrar campo "Otro" cuando se seleccione esa opción
    document.getElementById('depto_remitente').addEventListener('change', function () {
        const otroField = document.getElementById('depto_remitente_otro');
        otroField.style.display = this.value === 'OTRO' ? 'block' : 'none';
        if (this.value !== 'OTRO') otroField.value = '';
    });

    document.getElementById('jud_destino').addEventListener('change', function () {
        const otroField = document.getElementById('jud_destino_otro');
        otroField.style.display = this.value === 'OTRO' ? 'block' : 'none';
        if (this.value !== 'OTRO') otroField.value = '';
    });

    // Validación de campos requeridos al enviar el formulario
    document.getElementById('oficioForm')?.addEventListener('submit', function (e) {
        const form = this;
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                alert(`El campo "${field.labels[0].textContent}" es obligatorio.`);
                field.focus();
                isValid = false;
                e.preventDefault(); // Detener el envío
                return;
            }
        });

        // Validar archivo PDF
        const pdfFile = document.getElementById('pdf_file');
        if (pdfFile && pdfFile.files.length > 0) {
            const file = pdfFile.files[0];
            if (file.type !== 'application/pdf') {
                alert('Solo se permiten archivos PDF');
                isValid = false;
                e.preventDefault(); // Detener el envío
            }
        }
    });

    // Validación en tiempo real del correo
    function validarCorreo(input) {
        const email = input.value.trim();
        const emailHelp = document.getElementById('emailHelp');

        if (!email.match(/@tlalpan\.cdmx\.gob\.mx$/i)) {
            emailHelp.style.color = 'red';
            emailHelp.textContent = 'Correo no válido. Debe ser @tlalpan.cdmx.gob.mx';
            input.setCustomValidity('Correo institucional inválido');
            return false;
        } else {
            emailHelp.style.color = 'green';
            emailHelp.textContent = 'Correo institucional válido';
            input.setCustomValidity('');

            // Verificar si el correo ya existe
            fetch(`verificar_empleado.php?email=${encodeURIComponent(email)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.existe) {
                        document.getElementById('numero_empleado').value = data.numero_empleado;
                    }
                })
                .catch(error => {
                    console.error('Error al verificar empleado:', error);
                });

            return true;
        }
    }

    // Validación del correo al cambiar el input
    document.addEventListener('DOMContentLoaded', function () {
        const emailInput = document.getElementById('email_institucional');
        if (emailInput) {
            emailInput.addEventListener('input', function () {
                validarCorreo(this);
            });
        }
    });
</script>
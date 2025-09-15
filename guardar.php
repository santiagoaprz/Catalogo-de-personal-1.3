<?php
// Activar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'session_config.php';
require 'funciones.php';
require 'database.php';

if (!$conn) {
    die(json_encode(['error' => "Error de conexión: " . mysqli_connect_error()]));
}

// Iniciar transacción
mysqli_begin_transaction($conn);

try {
    // Validar campos requeridos
    $required_fields = [
        'fecha_creacion', 'fecha_entrega', 'numero_oficio_usuario',
        'remitente', 'cargo_remitente', 'depto_remitente',
        'asunto', 'tipo', 'estatus', 'telefono', 'jud_destino'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es requerido");
        }
    }

    // Validar que el correo tenga el dominio correcto
    if (!preg_match('/@tlalpan\.cdmx\.gob\.mx$/i', $_POST['email_institucional'])) {
        throw new Exception("El correo debe ser del dominio @tlalpan.cdmx.gob.mx");
    }

    // 1. Procesar PDF
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/SISTEMA_OFICIOS/pdfs/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Verificar permisos de escritura
    if (!is_writable($upload_dir)) {
        throw new Exception("El directorio de uploads no tiene permisos de escritura");
    }

    $pdf_nombre = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $pdf_destino = $upload_dir . $pdf_nombre;

    if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $pdf_destino)) {
        throw new Exception("Error al subir el PDF");
    }

    // 2. Obtener próximo número de oficio
    $secuencia = mysqli_query($conn, "SELECT ultimo_numero FROM secuencia_oficios LIMIT 1 FOR UPDATE");
    $row = mysqli_fetch_assoc($secuencia);
    $proximo_numero = $row['ultimo_numero'] + 1;
    $numero_oficio = "OF-" . str_pad($proximo_numero, 5, '0', STR_PAD_LEFT);

    // 3. Buscar si el correo ya existe en el catálogo
    $email = mysqli_real_escape_string($conn, strtolower(trim($_POST['email_institucional'])));
    $query_check = "SELECT id, numero_empleado FROM catalogo_personal WHERE email_institucional = '$email' LIMIT 1";
    $result_check = mysqli_query($conn, $query_check);
    $personal_existente = mysqli_fetch_assoc($result_check);

    // Verificar si el correo ya existe en el catálogo
    if ($personal_existente) {
        // Correo ya registrado - usar la función unificada para actualizar
        $personal_id = actualizarCatalogoPersonal($conn, [
            'email_institucional' => $email,
            'remitente' => $_POST['remitente'],
            'cargo_remitente' => $_POST['cargo_remitente'],
            'depto_remitente' => $_POST['depto_remitente'],
            'telefono' => $_POST['telefono'],
            'extension' => $_POST['extension'] ?? ''
        ]);

        // Usar número de empleado existente
        $numero_empleado = $personal_existente['numero_empleado'];
    } else {
        // Validar formato del número de empleado para nuevos registros
        if (!preg_match('/^EMP-\d{5}$/', $_POST['numero_empleado'])) {
            throw new Exception("El número de empleado debe tener formato EMP-00000");
        }

        // Insertar nuevo registro en el catálogo
        $personal_id = actualizarCatalogoPersonal($conn, [
            'email_institucional' => $email,
            'remitente' => $_POST['remitente'],
            'cargo_remitente' => $_POST['cargo_remitente'],
            'depto_remitente' => $_POST['depto_remitente'],
            'telefono' => $_POST['telefono'],
            'extension' => $_POST['extension'] ?? '',
            'numero_empleado' => $_POST['numero_empleado']
        ]);
        
        $numero_empleado = $_POST['numero_empleado'];
    }

    // 4. Insertar documento
    $query_documento = "INSERT INTO documentos (
        fecha_creacion, fecha_entrega, numero_oficio, numero_oficio_usuario,
        remitente, cargo_remitente, depto_remitente, telefono, extension,
        asunto, tipo, estatus, pdf_url, jud_destino,
        email_institucional, dire_fisica, usuario_registra, personal_id
    ) VALUES (
        NOW(), '" . mysqli_real_escape_string($conn, $_POST['fecha_entrega']) . "',
        '$numero_oficio', '" . mysqli_real_escape_string($conn, $_POST['numero_oficio_usuario']) . "',
        '" . mysqli_real_escape_string($conn, $_POST['remitente']) . "', 
        '" . mysqli_real_escape_string($conn, $_POST['cargo_remitente']) . "',
        '" . mysqli_real_escape_string($conn, $_POST['depto_remitente']) . "',
        '" . mysqli_real_escape_string($conn, $_POST['telefono']) . "',
        '" . mysqli_real_escape_string($conn, $_POST['extension'] ?? '')."',
        '" . mysqli_real_escape_string($conn, $_POST['asunto']) . "',
        '" . mysqli_real_escape_string($conn, $_POST['tipo']) . "',
        '" . mysqli_real_escape_string($conn, $_POST['estatus']) . "',
        '" . mysqli_real_escape_string($conn, $pdf_destino) . "',
        '" . mysqli_real_escape_string($conn, $_POST['jud_destino']) . "',
        '$email',
        '" . mysqli_real_escape_string($conn, $_POST['dire_fisica'] ?? '')."',
        " . (int)$_SESSION['user']['id'] . ",
        $personal_id
    )";

    if (!mysqli_query($conn, $query_documento)) {
        $error = mysqli_error($conn);
        error_log("Error en consulta: $query_documento");
        throw new Exception("Error al insertar documento: $error");
    }

    $documento_id = mysqli_insert_id($conn);
    logAction("Documento insertado correctamente con ID: $documento_id", $_POST);
    
    // 5. Registrar historial departamental
    $depto_anterior_query = "SELECT departamento_jud FROM catalogo_personal WHERE id = $personal_id";
    $depto_anterior_result = mysqli_query($conn, $depto_anterior_query);
    $depto_anterior_row = mysqli_fetch_assoc($depto_anterior_result);
    $depto_anterior = $depto_anterior_row['departamento_jud'];
    
    $query_historial = "INSERT INTO historial_departamentos (
        personal_id, numero_empleado, departamento_anterior, departamento_nuevo, 
        fecha_cambio, usuario_registra, documento_id, numero_oficio_usuario, email_institucional
    ) VALUES (
        $personal_id,
        " . (!empty($numero_empleado) ? "'$numero_empleado'" : "NULL") . ",
        " . (!empty($depto_anterior) ? "'$depto_anterior'" : "NULL") . ",
        '" . mysqli_real_escape_string($conn, $_POST['depto_remitente']) . "',
        NOW(),
        " . (int)$_SESSION['user']['id'] . ",
        $documento_id,
        '" . mysqli_real_escape_string($conn, $_POST['numero_oficio_usuario']) . "',
        '$email'
    )";
    
    if (!mysqli_query($conn, $query_historial)) {
        throw new Exception("Error al registrar historial departamental: " . mysqli_error($conn));
    }
    
    // Actualizar secuencia
    mysqli_query($conn, "UPDATE secuencia_oficios SET ultimo_numero = $proximo_numero");
    
    // Confirmar transacción
    mysqli_commit($conn);
    
    $_SESSION['success'] = "Documento guardado correctamente!";
    header("Location: index.php");
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);

    // Eliminar PDF si existe
    if (isset($pdf_destino) && file_exists($pdf_destino)) {
        unlink($pdf_destino);
    }

    // Registrar error detallado
    error_log("Error en guardar.php: " . $e->getMessage());
    error_log("Datos: " . print_r($_POST, true));

    $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
    header("Location: index.php");
    exit;
}

function logAction($message, $data = []) {
    $log = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    if (!empty($data)) {
        $log .= "Datos: " . print_r($data, true) . PHP_EOL;
    }
    file_put_contents(__DIR__.'/action.log', $log, FILE_APPEND);
}



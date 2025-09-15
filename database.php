<?php
// database.php - Versión corregida y optimizada

// Verificar si la conexión ya está establecida
if (!isset($conn)) {
    // Configuración de conexión
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "alcaldia_control";

    // Establecer conexión
    $conn = mysqli_connect($servername, $username, $password, $dbname);

    if (!$conn) {
        error_log("[" . date('Y-m-d H:i:s') . "] Error de conexión MySQL: " . mysqli_connect_error());
        die("Error crítico: No se pudo conectar a la base de datos. Por favor, intente más tarde.");
    }

    // Configurar charset
    mysqli_set_charset($conn, "utf8mb4");
}

// Protección contra redeclaración de funciones
if (!function_exists('obtenerNombreUsuario')) {
    function obtenerNombreUsuario($id) {
        global $conn;
        $query = "SELECT username, nombre_completo FROM usuarios WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            return !empty($user['nombre_completo']) ? $user['nombre_completo'] : $user['username'];
        }
        return 'Desconocido';
    }
}

if (!function_exists('subirPDF')) {
    function subirPDF($file_input, $prefix) {
        $upload_dir = 'pdfs/';
        
        if (!file_exists($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            throw new Exception("No se pudo crear el directorio para archivos.");
        }
        
        if (!isset($_FILES[$file_input])) {
            throw new Exception("No se recibió ningún archivo.");
        }
        
        $pdf_name = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $pdf_path = $upload_dir . $pdf_name;
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES[$file_input]['tmp_name']);
        finfo_close($finfo);
        
        if ($mime_type != 'application/pdf') {
            throw new Exception("El archivo debe ser un PDF válido. Tipo recibido: $mime_type");
        }
        
        if (!move_uploaded_file($_FILES[$file_input]['tmp_name'], $pdf_path)) {
            throw new Exception("Error al guardar el archivo. Código: " . $_FILES[$file_input]['error']);
        }
        
        return $pdf_path;
    }
}

if (!function_exists('ejecutarConsulta')) {
    function ejecutarConsulta($sql, $params = [], $types = "") {
        global $conn;
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("Error en consulta: $sql - " . mysqli_error($conn));
            return false;
        }
        
        if (!empty($params)) {
            $types = $types ?: str_repeat("s", count($params));
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Error al ejecutar: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
}

if (!function_exists('registrarEvento')) {
    function registrarEvento($usuario_id, $accion, $detalles = "") {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return ejecutarConsulta(
            "INSERT INTO logs_sistema (usuario_id, accion, detalles, ip) VALUES (?, ?, ?, ?)",
            [$usuario_id, $accion, $detalles, $ip],
            "isss"
        );
    }
}



// Cierre de conexión mejorado
register_shutdown_function(function() {
    global $conn;
    if (isset($conn) && $conn instanceof mysqli && mysqli_ping($conn)) {
        mysqli_close($conn);
    }
});

//funcion de validacion 
if (!function_exists('validarNumeroEmpleado')) {
    function validarNumeroEmpleado($conn, $email_institucional) {
        $email = trim($email_institucional);
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        $query = "SELECT 1 FROM catalogo_personal WHERE email_institucional = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        return (mysqli_stmt_num_rows($stmt) > 0);
    }
}
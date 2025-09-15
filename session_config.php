<?php
// session_config.php - Versión corregida y optimizada

// Verificar si la sesión no está activa antes de configurar
if (session_status() === PHP_SESSION_NONE) {
    // Configuración de seguridad
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // Solo activar si tienes HTTPS
    ini_set('session.use_strict_mode', 1);
    
    // Configuración de tiempo de sesión
    ini_set('session.gc_maxlifetime', 1800); // 30 minutos
    session_set_cookie_params([
        'lifetime' => 1800,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => isset($_SERVER['HTTPS']), // Solo HTTPS si está disponible
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Iniciar sesión
    session_start();

    // Regeneración periódica del ID de sesión
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
} else {
    // Si la sesión ya está activa, solo asegurar regeneración si es necesario
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}
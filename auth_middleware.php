<?php
// auth_middleware.php

// Verificar si las funciones ya fueron declaradas
if (!function_exists('requireAuth')) {
    require 'session_config.php';

    function requireAuth() {
        if (!isset($_SESSION['user']['logged_in']) || $_SESSION['user']['logged_in'] !== true) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: login_form.php');
            exit;
        }
        
        // Verificar inactividad (30 minutos)
        if (time() - $_SESSION['user']['last_activity'] > 1800) {
            session_unset();
            session_destroy();
            header('Location: login_form.php?timeout=1');
            exit;
        }
        
        // Actualizar tiempo de actividad
        $_SESSION['user']['last_activity'] = time();
    }
}

if (!function_exists('mostrarDashboard')) {
    function mostrarDashboard() {
        $rol = $_SESSION['user']['rol'];
        $username = htmlspecialchars($_SESSION['user']['username']);
        
        switch ($rol) {
            case 'SISTEMAS':
                echo "<h2>Panel de Control (Sistemas)</h2>
                      <p>Bienvenido, $username. Tienes acceso completo al sistema.</p>
                      <div class='system-stats'>...</div>";
                break;
                
            case 'ADMIN':
                echo "<h2>Panel Administrativo</h2>
                      <p>Bienvenido, $username. Gestión documental completa.</p>";
                break;
                
            case 'CAPTURISTA':
                echo "<h2>Bienvenida, $username</h2>
                      <p>Panel de digitalización de documentos.</p>";
                break;
                
            default:
                echo "<h2>Bienvenido, $username</h2>
                      <p>No tienes un panel específico configurado.</p>";
                break;
        }
    }
}

if (!function_exists('requireRole')) {
    function requireRole($roles) {
        requireAuth();
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array($_SESSION['user']['rol'], $roles)) {
            header('HTTP/1.0 403 Forbidden');
            die("Acceso denegado. No tienes los permisos necesarios.");
        }
    }
}

if (!function_exists('verificarPermisos')) {
    function verificarPermisos($pagina) {
        $rol = $_SESSION['user']['rol'] ?? '';
        
        $permisos = [
            'SISTEMAS' => ['index', 'guardar', 'historial', 'catalogo', 'actualizar_usuario', 'etapas', 'configuracion', 'subir_pdf'],
            'ADMIN' => ['index', 'guardar', 'historial', 'etapas', 'catalogo','subir_pdf' ],
            'CAPTURISTA' => ['index', 'guardar', 'historial', 'subir_pdf']
        ];
        
        // Extraer el nombre de la página sin extensión
        $paginaActual = basename($pagina, '.php');
        
        if (!in_array($paginaActual, $permisos[$rol])) {
            header('HTTP/1.0 403 Forbidden');
            die("Acceso denegado. No tienes permisos para esta sección.");
        }
    }
}

if (!function_exists('generarMenu')) {
    function generarMenu() {
        $rol = $_SESSION['user']['rol'] ?? '';
        
        $menus = [
            'SISTEMAS' => [
                ['url' => 'index.php', 'texto' => 'Inicio', 'icono' => '🏠'],
                ['url' => 'catalogo.php', 'texto' => 'Catálogo', 'icono' => '📁'],
                ['url' => 'historial.php', 'texto' => 'Historial', 'icono' => '📋'],
                ['url' => 'actualizar_usuario.php', 'texto' => 'Usuarios', 'icono' => '👥'],
                ['url' => 'etapas.php', 'texto' => 'Gestión Documental', 'icono' => '📂'],
                ['url' => 'configuracion.php', 'texto' => 'Configuración', 'icono' => '⚙️'],
                ['url' => 'logout.php', 'texto' => 'Salir', 'icono' => '🚪']
            ],
            'ADMIN' => [
                ['url' => 'index.php', 'texto' => 'Inicio', 'icono' => '🏠'],
                ['url' => 'catalogo.php', 'texto' => 'Catálogo', 'icono' => '📁'],
                ['url' => 'guardar.php', 'texto' => 'Nuevo Documento', 'icono' => '➕'],
                ['url' => 'historial.php', 'texto' => 'Historial', 'icono' => '📋'],
                ['url' => 'etapas.php', 'texto' => 'Seguimiento', 'icono' => '🔍'],
                ['url' => 'logout.php', 'texto' => 'Salir', 'icono' => '🚪']
            ],
            'CAPTURISTA' => [
                ['url' => 'index.php', 'texto' => 'Crear nuevo oficio', 'icono' => '🔍'],
                ['url' => 'historial.php', 'texto' => 'Historial', 'icono' => '📋'],
                ['url' => 'guardar.php', 'texto' => 'Digitalizar', 'icono' => '📤'],
                ['url' => 'logout.php', 'texto' => 'Salir', 'icono' => '🚪']
            ]
        ];
        
        // Verificar si el rol existe en el menú
        if (!isset($menus[$rol])) {
            echo "<a href='logout.php' class='nav-link'>🚪 Salir</a>";
            return;
        }
        
        // Generar los elementos del menú
        foreach ($menus[$rol] as $item) {
            $icono = $item['icono'] ?? '';
            echo "<a href='{$item['url']}' class='nav-link' title='{$item['texto']}'>$icono {$item['texto']}</a>";
        }
    }
}
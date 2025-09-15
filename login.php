<?php
// login.php
require 'database.php';
require 'session_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // No escapamos para el hash
    
    $query = "SELECT id, username, password, rol FROM usuarios WHERE username = ? AND activo = 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            // Autenticación exitosa - Configuración mejorada de sesión
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'rol' => $user['rol'],
                'logged_in' => true,
                'last_activity' => time()  // Para el control de inactividad
            ];


              // Redirigir siempre a index.php después del login
    header('Location: index.php');
    exit;
}
            
            // Regenerar ID de sesión para mayor seguridad
            session_regenerate_id(true);
            
            // Primero verificar si hay una URL de redirección guardada
            if (isset($_SESSION['redirect_url']) && !empty($_SESSION['redirect_url'])) {
                $redirect_url = $_SESSION['redirect_url'];
                unset($_SESSION['redirect_url']);
                header('Location: ' . $redirect_url);
                exit;
            }
            
            // Si no hay URL de redirección, usar la lógica basada en rol
            switch ($user['rol']) {
                case 'SISTEMAS':
                    $redirect = 'sistemas.php';
                    break;
                case 'ADMIN':
                    $redirect = 'admin.php';
                    break;
                case 'CAPTURISTA':
                    $redirect = 'capturista.php';
                    break;
                default:
                    $redirect = 'index.php';
            }
            
            // Redirección final
            header('Location: ' . $redirect);
            exit;
        }
    }
    
    // Si llegamos aquí, la autenticación falló
    $_SESSION['error'] = "Usuario o contraseña incorrectos";
    header('Location: login_form.php');
    exit;
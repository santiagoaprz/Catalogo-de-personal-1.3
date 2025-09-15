<?php
require 'database.php';
require 'session_config.php';
require 'auth_middleware.php';
requireRole(['SISTEMAS']);

$usuarios = [
    [
        'username' => 'judds',
        'password' => 'Judds@2024', // Cambia esto
        'nombre' => 'Administrador JUDDS',
        'rol' => 'ADMIN'
    ],
    [
        'username' => 'capturista',
        'password' => 'Digital.2024', // Cambia esto
        'nombre' => 'Capturista Principal',
        'rol' => 'CAPTURISTA'
    ]
];

foreach ($usuarios as $user) {
    $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);
    $query = "INSERT INTO usuarios (username, password, nombre_completo, rol, activo)
              VALUES (?, ?, ?, ?, 1)
              ON DUPLICATE KEY UPDATE 
              password = VALUES(password),
              rol = VALUES(rol)";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssss", 
        $user['username'],
        $hashedPassword,
        $user['nombre'],
        $user['rol']
    );
    
    mysqli_stmt_execute($stmt);
    echo "Usuario {$user['username']} creado/actualizado.<br>";
}

echo "<strong>¡No olvides eliminar este archivo después de usarlo!</strong>";
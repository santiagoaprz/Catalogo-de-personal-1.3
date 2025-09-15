<?php
require 'database.php';
require 'session_config.php';
require 'auth_middleware.php';
requireRole(['SISTEMAS']);
// Solo ejecutar una vez, luego eliminar este archivo
$username = 'admin';
$password = password_hash('Admin123', PASSWORD_BCRYPT);
$nombre = 'Administrador Principal';
$rol = 'SISTEMAS';

$query = "INSERT INTO usuarios (username, password, nombre_completo, rol) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ssss", $username, $password, $nombre, $rol);

if (mysqli_stmt_execute($stmt)) {
    echo "Usuario administrador creado exitosamente. BORRA ESTE ARCHIVO AHORA.";
} else {
    echo "Error al crear usuario: " . mysqli_error($conn);
}
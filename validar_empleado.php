<?php
require 'database.php';

header('Content-Type: application/json');

if (!isset($_GET['email'])) {
    die(json_encode(['error' => 'Email no proporcionado']));
}

$email = trim($_GET['email']);
$query = "SELECT numero_empleado FROM catalogo_personal WHERE email_institucional = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'existe' => true,
        'numero_empleado' => $row['numero_empleado']
    ]);
} else {
    echo json_encode([
        'existe' => false
    ]);
}
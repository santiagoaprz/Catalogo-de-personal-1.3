<?php
// actualizar_registros.php (ejecutar solo una vez)
require 'database.php';
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
$result = mysqli_query($conn, "SELECT numero_empleado FROM documentos WHERE email_institucional IS NULL");
while ($row = mysqli_fetch_assoc($result)) {
    $temp_email = 'temp_' . $row['numero_empleado'] . '@institucion.mx';
    mysqli_query($conn, "UPDATE documentos SET email_institucional = '$temp_email' WHERE numero_empleado = '{$row['numero_empleado']}'");
}
echo "Registros actualizados";
?>

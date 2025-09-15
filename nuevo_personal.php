<?php
require 'session_config.php';
require 'auth_middleware.php';
requireRole(['ADMIN', 'SISTEMAS']);
require 'database.php';

// Obtener lista de departamentos JUD
$jud_query = "SELECT nombre FROM jud_departamentos WHERE activo = TRUE ORDER BY nombre";
$jud_result = mysqli_query($conn, $jud_query);

// Reemplaza todo el bloque de inserción con esto:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y limpiar datos
    $email_institucional = mysqli_real_escape_string($conn, $_POST['email_institucional']);
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $puesto = mysqli_real_escape_string($conn, $_POST['puesto']);
    $departamento = mysqli_real_escape_string($conn, $_POST['departamento']);
    $dire_fisica = mysqli_real_escape_string($conn, $_POST['dire_fisica']);
    $telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
    $usuario_id = $_SESSION['user']['id'];

    // Insertar en catálogo (CON número de empleado)
    $query = "INSERT INTO catalogo_personal 
    (email_institucional, nombre, puesto, departamento_jud, telefono, extension) 
    VALUES ('$email_institucional', '$nombre', '$puesto', '$departamento', '$telefono', '$extension')";
    
    if (mysqli_stmt_execute($stmt)) {
        $personal_id = mysqli_insert_id($conn);
        
        // Registrar en historial
        $historial_query = "INSERT INTO historial_departamentos 
                           (personal_id, departamento_nuevo, usuario_id) 
                           VALUES (?, ?, ?)";
        $stmt_hist = mysqli_prepare($conn, $historial_query);
        mysqli_stmt_bind_param($stmt_hist, 'isi', $personal_id, $departamento, $usuario_id);
        mysqli_stmt_execute($stmt_hist);
        
        $_SESSION['success'] = 'Personal agregado correctamente';
        header('Location: catalogo.php');
        exit;
    } else {
        $error = 'Error al agregar personal: ' . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Nuevo Personal</title>
    <!-- [Estilos similares a catalogo.php] -->
</head>
<body>
    <h1>Agregar Nuevo Personal</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>


    
    <form method="post">
        <div class="form-group">
            <label for="nombre">Nombre Completo</label>
            <input type="text" id="nombre" name="nombre" required>
        </div>
        
        <label for="email_institucional">email institucional</label>
        <input type="text" id="email_institucional" name="email_institucional" required>

        <div class="form-group">
            <label for="puesto">Puesto</label>
            <input type="text" id="puesto" name="puesto" required>
        </div>
        
        <div class="form-group">
            <label for="departamento">Departamento JUD</label>
            <select id="departamento" name="departamento" required>
                <option value="">Seleccione un departamento</option>
                <?php while ($jud = mysqli_fetch_assoc($jud_result)): ?>
                    <option value="<?= htmlspecialchars($jud['nombre']) ?>">
                        <?= htmlspecialchars($jud['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="dire_fisica">Dirección Física</label>
            <input type="text" id="dire_fisica" name="dire_fisica">
        </div>
        
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="tel" id="telefono" name="telefono">
        </div>
        
        <button type="submit" class="btn">Guardar Personal</button>
        <a href="catalogo.php" class="btn">Cancelar</a>
    </form>
</body>
</html>
<?php
require 'session_config.php';
require 'auth_middleware.php';
requireRole(['ADMIN', 'SISTEMAS']);
require 'database.php';

if (!isset($_GET['id'])) {
    header('Location: catalogo.php');
    exit;
}

$personal_id = (int)$_GET['id'];
$personal_query = "SELECT * FROM catalogo_personal WHERE id = ?";
$stmt = mysqli_prepare($conn, $personal_query);
mysqli_stmt_bind_param($stmt, 'i', $personal_id);
mysqli_stmt_execute($stmt);
$personal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Obtener lista de departamentos JUD
$jud_query = "SELECT nombre FROM jud_departamentos WHERE activo = TRUE ORDER BY nombre";
$jud_result = mysqli_query($conn, $jud_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_departamento = mysqli_real_escape_string($conn, $_POST['nuevo_departamento']);
    $usuario_id = $_SESSION['user']['id'];
    
    // Registrar en historial primero
    $historial_query = "INSERT INTO historial_departamentos 
                       (personal_id, departamento_anterior, departamento_nuevo, usuario_id) 
                       VALUES (?, ?, ?, ?)";
    $stmt_hist = mysqli_prepare($conn, $historial_query);
    mysqli_stmt_bind_param($stmt_hist, 'issi', 
        $personal_id, 
        $personal['departamento_jud'], 
        $nuevo_departamento, 
        $usuario_id);
    
    if (mysqli_stmt_execute($stmt_hist)) {
        // Actualizar departamento actual
        $update_query = "UPDATE catalogo_personal SET departamento_jud = ? WHERE id = ?";
        $stmt_upd = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt_upd, 'si', $nuevo_departamento, $personal_id);
        mysqli_stmt_execute($stmt_upd);
        
        $_SESSION['success'] = 'Departamento actualizado correctamente';
        header('Location: catalogo.php');
        exit;
    } else {
        $error = 'Error al actualizar departamento: ' . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Departamento</title>
    <!-- [Estilos similares] -->
</head>
<body>
    <h1>Cambiar Departamento JUD</h1>
    <h2>Para: <?= htmlspecialchars($personal['email_institucional']) ?> - <?= htmlspecialchars($personal['nombre']) ?></h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label>Departamento Actual:</label>
            <input type="text" value="<?= htmlspecialchars($personal['departamento_jud']) ?>" disabled>
        </div>
        
        <div class="form-group">
            <label for="nuevo_departamento">Nuevo Departamento JUD</label>
            <select id="nuevo_departamento" name="nuevo_departamento" required>
                <option value="">Seleccione un departamento</option>
                <?php while ($jud = mysqli_fetch_assoc($jud_result)): ?>
                    <option value="<?= htmlspecialchars($jud['nombre']) ?>">
                        <?= htmlspecialchars($jud['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <button type="submit" class="btn">Actualizar Departamento</button>
        <a href="catalogo.php" class="btn">Cancelar</a>
    </form>
</body>
</html>
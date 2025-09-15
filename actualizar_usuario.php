<!-- Formulario para actualizar datos -->
<form action="procesar_actualizacion.php" method="post">
    <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
    
    <label>Departamento Actual:
        <input type="text" name="nuevo_departamento" required>
    </label>
    
    <label>Teléfono:
        <input type="tel" name="telefono">
    </label>
    
    <button type="submit">Actualizar</button>
</form>
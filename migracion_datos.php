<?php
require 'database.php';

function migrarDatosCorregida($conn) {
    $conn->begin_transaction();
    
    try {
        echo "Paso 1: Normalizando datos de correos electrónicos...\n";
        $conn->query("UPDATE catalogo_personal SET email_institucional = LOWER(TRIM(email_institucional)) WHERE email_institucional IS NOT NULL");
        $conn->query("UPDATE documentos SET email_institucional = LOWER(TRIM(email_institucional)) WHERE email_institucional IS NOT NULL");
        $conn->query("UPDATE historial_departamentos SET email_institucional = LOWER(TRIM(email_institucional)) WHERE email_institucional IS NOT NULL");
        
        echo "Paso 2: Actualizando catálogo con datos de documentos...\n";
        $update_query = "UPDATE catalogo_personal cp
            JOIN documentos d ON cp.email_institucional = d.email_institucional
            SET 
                cp.nombre = d.remitente,
                cp.puesto = d.cargo_remitente,
                cp.departamento_jud = d.depto_remitente,
                cp.telefono = d.telefono,
                cp.ultima_actualizacion = NOW()
            WHERE d.email_institucional IS NOT NULL";
        
        $conn->query($update_query);
        echo "Registros actualizados en catálogo: " . $conn->affected_rows . "\n";
        
        echo "Paso 3: Insertando personal faltante en el catálogo...\n";
        $insert_query = "INSERT INTO catalogo_personal (
            numero_empleado, nombre, puesto, departamento_jud, 
            telefono, email_institucional, fecha_registro, ultima_actualizacion
        )
        SELECT DISTINCT 
            d.numero_empleado, 
            d.remitente, 
            d.cargo_remitente, 
            d.depto_remitente,
            d.telefono, 
            d.email_institucional,
            NOW(),
            NOW()
        FROM documentos d
        WHERE d.email_institucional IS NOT NULL
        AND d.email_institucional NOT LIKE 'sin-correo@%'
        AND NOT EXISTS (
            SELECT 1 FROM catalogo_personal cp 
            WHERE cp.email_institucional = d.email_institucional
        )";
        
        $conn->query($insert_query);
        echo "Registros insertados en catálogo: " . $conn->affected_rows . "\n";
        
        echo "Paso 4: Actualizando historial de departamentos...\n";
        $update_historial = "UPDATE historial_departamentos hd
            JOIN catalogo_personal cp ON hd.email_institucional = cp.email_institucional
            SET hd.personal_id = cp.id
            WHERE hd.personal_id IS NULL OR hd.personal_id = 0";
        
        $conn->query($update_historial);
        echo "Registros de historial actualizados: " . $conn->affected_rows . "\n";
        
        $conn->commit();
        
        echo "Migración completada con éxito\n";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error en la migración: " . $e->getMessage() . "\n";
        error_log("Error en migración: " . $e->getMessage());
        return false;
    }
    return true;
}

// Ejecutar migración corregida
if (migrarDatosCorregida($conn)) {
    // Verificación post-migración
    echo "\n=== VERIFICACIÓN POST-MIGRACIÓN ===\n";
    
    // Documentos sin correspondencia en catálogo
    $query = "SELECT d.id, d.numero_oficio, d.remitente, d.email_institucional 
              FROM documentos d
              LEFT JOIN catalogo_personal cp ON d.email_institucional = cp.email_institucional
              WHERE cp.id IS NULL AND d.email_institucional NOT LIKE 'sin-correo@%'";
    $result = $conn->query($query);
    
    echo "Documentos sin entrada en catálogo: " . $result->num_rows . "\n";
    if ($result->num_rows > 0) {
        echo "Detalles:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- ID: {$row['id']}, Oficio: {$row['numero_oficio']}, Remitente: {$row['remitente']}, Email: {$row['email_institucional']}\n";
        }
    }
    
    // Historial sin personal_id válido
    $query = "SELECT COUNT(*) as total FROM historial_departamentos hd
              LEFT JOIN catalogo_personal cp ON hd.personal_id = cp.id
              WHERE cp.id IS NULL AND hd.personal_id IS NOT NULL";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    echo "Registros de historial sin personal_id válido: " . $row['total'] . "\n";
}
?>
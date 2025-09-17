<?php
// modulos/actas/includes/actas_db.php
require_once __DIR__.'/../../../database.php';

function crearOficio($datos) {
    global $conn;
    
    $sql = "INSERT INTO oficios (
        folio, tipo, remitente_id, destinatario_id, 
        asunto, contenido, telefono_remitente, 
        estado, fecha_creacion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente', NOW())";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssiisss", 
        $datos['folio'],
        $datos['tipo'],
        $datos['remitente_id'],
        $datos['destinatario_id'],
        $datos['asunto'],
        $datos['contenido'],
        $datos['telefono']
    );
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    }
    return false;
}

function recepcionarOficio($oficio_id, $usuario_id) {
    return ejecutarConsulta(
        "UPDATE oficios SET 
        estado = 'Recepcionado',
        recepcion_fecha = NOW(),
        recepcion_usuario_id = ?
        WHERE id = ?",
        [$usuario_id, $oficio_id],
        "ii"
    );
}

function responderOficio($oficio_id, $respuesta, $respondente_id, $telefono) {
    return ejecutarConsulta(
        "UPDATE oficios SET 
        estado = 'Respondido',
        respuesta = ?,
        respondente_id = ?,
        telefono_respondente = ?,
        fecha_respuesta = NOW()
        WHERE id = ?",
        [$respuesta, $respondente_id, $telefono, $oficio_id],
        "sisi"
    );
}

function generarAcuse($oficio_id) {
    return ejecutarConsulta(
        "UPDATE oficios SET 
        estado = 'Finalizado',
        acuse_fecha = NOW()
        WHERE id = ?",
        [$oficio_id],
        "i"
    );
}

function obtenerOficio($id) {
    $result = ejecutarConsulta(
        "SELECT o.*, 
        u1.nombre_completo as remitente_nombre,
        u2.nombre_completo as destinatario_nombre,
        u3.nombre_completo as respondente_nombre
        FROM oficios o
        LEFT JOIN usuarios u1 ON o.remitente_id = u1.id
        LEFT JOIN usuarios u2 ON o.destinatario_id = u2.id
        LEFT JOIN usuarios u3 ON o.respondente_id = u3.id
        WHERE o.id = ?",
        [$id],
        "i"
    );
    return mysqli_fetch_assoc($result);
}
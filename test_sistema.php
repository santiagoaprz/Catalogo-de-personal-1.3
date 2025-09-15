<?php
require 'database.php';

class SistemaTest {
    private $conn;
    private $test_email;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->test_email = 'test_' . time() . '@tlalpan.cdmx.gob.mx';
    }
    
    public function ejecutarPruebas() {
        $this->testRelaciones();
        $this->testFlujoCompleto();
        $this->testConsultasPrincipales();
    }
    
    private function testRelaciones() {
        echo "=== TEST DE RELACIONES ===\n";
        
        // Test 1: Documentos con correo no institucional
        $query = "SELECT COUNT(*) as total FROM documentos 
                 WHERE email_institucional NOT LIKE '%@tlalpan.cdmx.gob.mx' 
                 AND email_institucional NOT LIKE 'sin-correo@%'";
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        $this->mostrarResultado(
            "Documentos con correo no institucional", 
            $row['total'], 
            0,
            "Todos los documentos deben tener correo institucional válido"
        );
        
        // Test 2: Documentos sin correspondencia en catálogo
        $query = "SELECT COUNT(*) as total FROM documentos d 
                 LEFT JOIN catalogo_personal cp ON d.email_institucional = cp.email_institucional 
                 WHERE cp.id IS NULL AND d.email_institucional NOT LIKE 'sin-correo@%'";
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        $this->mostrarResultado(
            "Documentos sin entrada en catálogo", 
            $row['total'], 
            0,
            "Todos los documentos deben tener una entrada correspondiente en el catálogo"
        );
    }
    
    private function testFlujoCompleto() {
        echo "\n=== TEST DE FLUJO COMPLETO ===\n";
        
        // Datos de prueba
        $test_data = [
            'fecha_entrega' => date('Y-m-d'),
            'numero_oficio_usuario' => 'TEST-' . time(),
            'remitente' => 'Usuario de Prueba',
            'email_institucional' => $this->test_email,
            'cargo_remitente' => 'Cargo de Prueba',
            'depto_remitente' => 'J.U.D. de Desarrollo de Sistemas',
            'telefono' => '5512345678',
            'asunto' => 'Prueba del sistema',
            'tipo' => 'OFICIO',
            'estatus' => 'SEGUIMIENTO',
            'jud_destino' => 'J.U.D. de Soporte Técnico',
            'trabajador_destino' => 'Destinatario de Prueba',
            'dire_fisica' => 'Dirección de prueba'
        ];
        
        // Simular guardado (deberías adaptar esto a tu función real)
        $documento_id = $this->simularGuardado($test_data);
        
        if ($documento_id) {
            $this->verificarRegistros($documento_id, $test_data['email_institucional']);
            echo "Prueba completada. Verifica manualmente los registros con email: " . $test_data['email_institucional'] . "\n";
        } else {
            echo "Error: No se pudo simular el guardado\n";
        }
    }
    
    private function testConsultasPrincipales() {
        echo "\n=== TEST DE CONSULTAS PRINCIPALES ===\n";
        
        // Consulta de index.php
        $query = "SELECT COUNT(*) as total FROM (
            SELECT d.id FROM documentos d
            LEFT JOIN catalogo_personal cp ON d.email_institucional = cp.email_institucional
            WHERE d.remitente IS NOT NULL
            ORDER BY d.fecha_creacion DESC, d.id DESC
            LIMIT 50
        ) as subquery";
        
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        $this->mostrarResultado(
            "Consulta de index.php (50 más recientes)", 
            $row['total'], 
            50,
            "Debe devolver hasta 50 registros"
        );
        
        // Consulta de catalogo.php
        $query = "SELECT COUNT(*) as total FROM (
            SELECT cp.id FROM catalogo_personal cp
            LEFT JOIN documentos d ON cp.email_institucional = d.email_institucional
            LEFT JOIN historial_departamentos hd ON cp.id = hd.personal_id
            GROUP BY cp.id
        ) as subquery";
        
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();
        $this->mostrarResultado(
            "Consulta de catalogo.php", 
            $row['total'], 
            ">0",
            "Debe devolver todos los registros del catálogo"
        );
    }
    
    private function simularGuardado($data) {
        // Esta es una simulación - deberías adaptarla a tu función real de guardado
        try {
            // 1. Insertar en catálogo si no existe
            $check = $this->conn->prepare("SELECT id FROM catalogo_personal WHERE email_institucional = ?");
            $check->bind_param('s', $data['email_institucional']);
            $check->execute();
            $result = $check->get_result();
            
            if ($result->num_rows == 0) {
                $insert = $this->conn->prepare("
                    INSERT INTO catalogo_personal (
                        numero_empleado, nombre, puesto, departamento_jud,
                        telefono, extension, email_institucional, fecha_registro, ultima_actualizacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $numero_empleado = 'TEMP-' . time();
                $insert->bind_param(
                    'sssssss',
                    $numero_empleado,
                    $data['remitente'],
                    $data['cargo_remitente'],
                    $data['depto_remitente'],
                    $data['telefono'],
                    '',
                    $data['email_institucional']
                );
                $insert->execute();
                $personal_id = $this->conn->insert_id;
            } else {
                $row = $result->fetch_assoc();
                $personal_id = $row['id'];
            }
            
            // 2. Insertar documento de prueba
            $insert_doc = $this->conn->prepare("
                INSERT INTO documentos (
                    fecha_creacion, fecha_entrega, numero_oficio, numero_oficio_usuario,
                    remitente, cargo_remitente, depto_remitente, telefono, extension,
                    asunto, tipo, estatus, pdf_url, destinatario, jud_destino,
                    email_institucional, numero_empleado, dire_fisica, usuario_registra, etapa
                ) VALUES (
                    NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'RECIBIDO'
                )
            ");
            
            $pdf_url = 'pdfs/test_' . time() . '.pdf';
            $numero_empleado = 'EMP-TEST';
            
            $insert_doc->bind_param(
                'sssssssssssssssss',
                $data['fecha_entrega'],
                'OF-TEST',
                $data['numero_oficio_usuario'],
                $data['remitente'],
                $data['cargo_remitente'],
                $data['depto_remitente'],
                $data['telefono'],
                '',
                $data['asunto'],
                $data['tipo'],
                $data['estatus'],
                $pdf_url,
                $data['trabajador_destino'],
                $data['jud_destino'],
                $data['email_institucional'],
                $numero_empleado,
                $data['dire_fisica']
            );
            $insert_doc->execute();
            $documento_id = $this->conn->insert_id;
            
            // 3. Insertar historial
            $historial = $this->conn->prepare("
                INSERT INTO historial_departamentos (
                    personal_id, numero_empleado, departamento_anterior,
                    departamento_nuevo, usuario_registra, documento_id,
                    numero_oficio_usuario, email_institucional
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $historial->bind_param(
                'isssisss',
                $personal_id,
                $numero_empleado,
                'SIN DEPARTAMENTO',
                $data['depto_remitente'],
                1,
                $documento_id,
                $data['numero_oficio_usuario'],
                $data['email_institucional']
            );
            $historial->execute();
            
            return $documento_id;
        } catch (Exception $e) {
            echo "Error en simulación: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function verificarRegistros($documento_id, $email) {
        // Verificar documento
        $query = "SELECT COUNT(*) as total FROM documentos WHERE id = ? AND email_institucional = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('is', $documento_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $this->mostrarResultado(
            "Documento creado", 
            $row['total'], 
            1,
            "Debe existir el documento de prueba"
        );
        
        // Verificar catálogo
        $query = "SELECT COUNT(*) as total FROM catalogo_personal WHERE email_institucional = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $this->mostrarResultado(
            "Entrada en catálogo", 
            $row['total'], 
            1,
            "Debe existir entrada en catálogo"
        );
        
        // Verificar historial
        $query = "SELECT COUNT(*) as total FROM historial_departamentos 
                 WHERE documento_id = ? AND email_institucional = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('is', $documento_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $this->mostrarResultado(
            "Entrada en historial", 
            $row['total'], 
            1,
            "Debe existir entrada en historial"
        );
    }
    
    private function mostrarResultado($prueba, $obtenido, $esperado, $descripcion = "") {
        $ok = ($esperado === ">0" && $obtenido > 0) || $obtenido == $esperado;
        echo sprintf(
            "[%s] %s: Obtenido %s (Esperado %s) - %s\n",
            $ok ? "OK" : "FALLO",
            $prueba,
            $obtenido,
            $esperado,
            $descripcion
        );
    }
}

// Ejecutar pruebas
$tester = new SistemaTest($conn);
$tester->ejecutarPruebas();

// Limpiar datos de prueba (opcional)
// $conn->query("DELETE FROM documentos WHERE email_institucional LIKE 'test_%'");
// $conn->query("DELETE FROM catalogo_personal WHERE email_institucional LIKE 'test_%'");
// $conn->query("DELETE FROM historial_departamentos WHERE email_institucional LIKE 'test_%'");
?>
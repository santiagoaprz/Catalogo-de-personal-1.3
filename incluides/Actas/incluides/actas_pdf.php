<?php
// modulos/actas/includes/actas_pdf.php

// Configurar rutas base
$rootDir = __DIR__.'/../../../';

// 1. Intentar cargar via Composer
$composerAutoload = $rootDir.'vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} 
// 2. Intentar cargar manualmente
elseif (file_exists($rootDir.'lib/tcpdf/tcpdf.php')) {
    require_once $rootDir.'lib/tcpdf/tcpdf.php';
    
    // Definir constantes necesarias si no existen
    if (!defined('PDF_PAGE_ORIENTATION')) define('PDF_PAGE_ORIENTATION', 'P');
    if (!defined('PDF_UNIT')) define('PDF_UNIT', 'mm');
    if (!defined('PDF_PAGE_FORMAT')) define('PDF_PAGE_FORMAT', 'A4');
    if (!defined('PDF_FONT_NAME_MAIN')) define('PDF_FONT_NAME_MAIN', 'helvetica');
} 
// 3. Mostrar error claro si no se encuentra
else {
    throw new Exception("TCPDF no está instalado. Por favor instálalo via Composer o descarga manualmente.");
}

function generarPDFAcuse($oficio) {
    try {
        // Crear nuevo PDF (con namespace completo)
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuración básica del documento
        $pdf->SetCreator('Sistema de Gestión Documental');
        $pdf->SetAuthor('Alcaldía');
        $pdf->SetTitle('Acuse de Oficio ' . $oficio['folio']);
        $pdf->SetSubject('Acuse de Recepción');
        
        // Configurar márgenes
        $pdf->SetMargins(15, 25, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Agregar página
        $pdf->AddPage();
        
        // Logo (verifica la ruta)
        $logoPath = __DIR__.'/../../../assets/img/logo_alcaldia.jpg';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 15, 30, 0, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Contenido HTML del PDF (mejorado)
        $html = '
        <style>
            .titulo { text-align:center; font-size:16pt; margin-bottom:20px; }
            .folio { text-align:right; font-size:10pt; }
            .tabla { width:100%; border-collapse:collapse; margin:15px 0; }
            .tabla td { padding:8px; border:1px solid #333; }
            .firma { margin-top:40px; text-align:center; }
        </style>
        
        <h1 class="titulo">ACUSE DE RECEPCIÓN</h1>
        <p class="folio"><strong>Folio:</strong> '.htmlspecialchars($oficio['folio']).'</p>
        <p class="folio"><strong>Fecha:</strong> '.date('d/m/Y').'</p>
        
        <p>Se hace constar que se ha recibido y atendido el siguiente documento:</p>
        
        <table class="tabla">
            <tr>
                <td width="30%"><strong>Remitente:</strong></td>
                <td width="70%">'.htmlspecialchars($oficio['remitente_nombre']).'</td>
            </tr>
            <tr>
                <td><strong>Destinatario:</strong></td>
                <td>'.htmlspecialchars($oficio['destinatario_nombre']).'</td>
            </tr>
            <tr>
                <td><strong>Asunto:</strong></td>
                <td>'.htmlspecialchars($oficio['asunto']).'</td>
            </tr>
            <tr>
                <td><strong>Fecha Recepción:</strong></td>
                <td>'.htmlspecialchars($oficio['recepcion_fecha']).'</td>
            </tr>
            <tr>
                <td><strong>Fecha Respuesta:</strong></td>
                <td>'.htmlspecialchars($oficio['fecha_respuesta']).'</td>
            </tr>
        </table>
        
        <p>El presente documento sirve como acuse de recepción y atención del oficio mencionado.</p>
        
        <div class="firma">
            _________________________<br>
            <strong>'.htmlspecialchars($oficio['respondente_nombre']).'</strong><br>
            '.htmlspecialchars($oficio['telefono_respondente']).'
        </div>';
        
        // Escribir contenido HTML
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Directorio para guardar (verifica permisos)
        $pdfDir = __DIR__.'/../../../pdf/acuses/';
        if (!file_exists($pdfDir)) {
            if (!mkdir($pdfDir, 0755, true)) {
                throw new Exception("No se pudo crear el directorio para PDFs");
            }
        }
        
        // Generar nombre único para el archivo
        $pdfName = 'Acuse_'.$oficio['folio'].'_'.date('YmdHis').'.pdf';
        $pdfPath = $pdfDir.$pdfName;
        
        // Guardar PDF en el servidor
        $pdf->Output($pdfPath, 'F');
        
        // Verificar que se creó el archivo
        if (!file_exists($pdfPath)) {
            throw new Exception("El archivo PDF no se generó correctamente");
        }
        
        return 'pdf/acuses/'.$pdfName;
        
    } catch (Exception $e) {
        error_log("[".date('Y-m-d H:i:s')."] Error en generarPDFAcuse: ".$e->getMessage());
        return false;
    }
}
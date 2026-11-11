<?php

class MYPDF extends TCPDF {
    
    protected $firmantes = [];

    public function setFirmantes(array $firmantes) {
        $this->firmantes = $firmantes;
    }

    public function Header() {
        // Cargar helper de imágenes del evento
        require_once APPPATH . 'helpers/deslinde_helper.php'; 
        $logo = deslinde_imagen_header(); 
        
        if (is_readable($logo)) {
            $this->Image($logo, 20, 12, 35, '', '', '', 'C', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Membrete superior institucional derecho
        $this->SetY(15);
        $this->SetFont('dejavusans', 'B', 10);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 5, 'COMITÉ OLIMPICO ORGANIZADOR', 0, 1, 'R');
        $this->SetFont('dejavusans', '', 8);
        $this->Cell(0, 4, 'XXXVIII Olimpiadas Nacionales de Institutos de Vivienda', 0, 1, 'R');
    }

    public function Body($participante) {
        // Lógica de género y rol
        $es_femenino = (isset($participante['sexo']) && strtoupper(trim($participante['sexo'])) === 'F');
        $es_competidor = isset($participante['es_competidor']) ? (bool)$participante['es_competidor'] : true;

        $el_la      = $es_femenino ? 'la' : 'el';
        $agente_art = $es_femenino ? 'la agente' : 'el agente';
        $interesada = $es_femenino ? 'la interesada' : 'el interesado';
        
        // Definir el rol según el campo es_competidor y el sexo
        if ($es_competidor) {
            $rol_texto = $es_femenino ? 'competidora' : 'competidor';
        } else {
            $rol_texto = 'acompañante';
        }

        // Fecha de emisión dinámica en español
        $fecha_emision = 'Santa Rosa, ' . date('d') . ' de ' . $this->obtenerMesEspañol(date('m')) . ' de ' . date('Y');

        $html = '
        <style>
            .fecha {
                font-size: 16px;
                text-align: right;
                color: #4a5568;
                margin-bottom: 10px;
            }
            .a-quien {
                font-size: 16px;
                font-weight: bold;
                color: #1a365d;
                margin-bottom: 10px;
            }
            .titulo-nota {
                font-size: 20px;
                font-weight: bold;
                text-align: center;
                color: #1a365d;
                text-transform: uppercase;
                margin-bottom: 20px;
                letter-spacing: 0.5px;
            }
            .cuerpo-texto {
                font-size: 16px;
                color: #2d3748;
                line-height: 1.7;
                text-align: justify;
            }
            .destacado-nombre {
                font-weight: bold;
                color: #1a365d;
            }
        </style>

        <div class="fecha">' . $fecha_emision . '</div>
        
        <div class="a-quien">A QUIEN CORRESPONDA:</div>
        
        <div class="titulo-nota">Certificado de Participación y Asistencia</div>

        <div class="cuerpo-texto">
            El que suscribe, en carácter de miembros del Comité Olimpico Organizador, certifican por medio de la presente que ' . $agente_art . ' 
            <span class="destacado-nombre">' . htmlspecialchars($participante['nombre_completo'], ENT_QUOTES, 'UTF-8') . '</span>, 
            D.N.I. N° <strong>' . htmlspecialchars($participante['dni'] ?? '________________', ENT_QUOTES, 'UTF-8') . '</strong>, 
            perteneciente a la delegación de la provincia de <strong>' . htmlspecialchars($participante['delegacion'] ?? 'nuestra institución', ENT_QUOTES, 'UTF-8') . '</strong>, 
            ha participado activamente en calidad de ' . $rol_texto . ' en los contingentes y competencias desarrolladas en el marco de las 
            <strong>“XXXVIII OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026”</strong>, 
            llevadas a cabo desde el día 1° al 6 de Noviembre de 2026 en las ciudades de Santa Rosa y Toay de la provincia de La Pampa.
        </div>
        
        <br>
        <div class="cuerpo-texto">
            Se extiende el presente certificado a solicitud de ' . $interesada . ' y para ser presentado ante las autoridades 
            que correspondan a los fines de justificación de licencias, comisiones de servicio o trámites administrativos que estimen convenientes.
        </div>
        ';

        $this->writeHTML($html, true, false, true, false, '');
    }

    public function Footer() {
        // Ubicarnos en la zona inferior fija para las firmas
        $this->SetY(-52);
        
        if (empty($this->firmantes)) {
            return;
        }

        $total_firmantes = count($this->firmantes);
        
        // Configuración de tamaños y espacios
        $ancho_firma = 75;     // ⬅️ Aumenta este número (ej: de 56 a 75 o 85) para que sea más ancha.
        $ancho_columna = 85;   // Espacio reservado para cada columna
        $ancho_total_bloque = $total_firmantes * $ancho_columna;
        
        // Obtener el ancho útil de la página
        $ancho_pagina = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        
        // Calcular la posición X inicial para centrar el bloque
        $start_x = $this->lMargin + ($ancho_pagina - $ancho_total_bloque) / 2;

        $current_y = $this->GetY();

        // 1. Dibujar imágenes de firmas escaneadas (más grandes)
        foreach ($this->firmantes as $i => $firmante) {
            if (!empty($firmante['imagen']) && is_readable($firmante['imagen'])) {
                $pos_x = $start_x + ($i * $ancho_columna) + (($ancho_columna - $ancho_firma) / 2);
                
                // Modificamos el alto de 14 a 22 para hacerla más alta y imponente
                // Sumamos unos píxeles en Y ($current_y - 4) para desplazarla ligeramente hacia arriba si hace falta
                $this->Image($firmante['imagen'], $pos_x, $current_y - 15, $ancho_firma, 28, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }
        }

        // Reducimos el salto de línea para que los textos suban y queden debajo/superpuestos a la firma
        $this->Ln(6);

        // 2. Nombres de los firmantes
        $this->SetFont('dejavusans', 'B', 12);
        $this->SetTextColor(26, 54, 93);
        
        $this->SetX($start_x);
        foreach ($this->firmantes as $firmante) {
            $this->MultiCell($ancho_columna, 4, htmlspecialchars($firmante['nombre'], ENT_QUOTES, 'UTF-8'), 0, 'C', 0, 0, '', '', true);
        }
        $this->Ln();

        // 4. Cargos
        $this->SetFont('dejavusans', '', 7.5);
        $this->SetTextColor(80, 80, 80);
        
        $this->SetX($start_x);
        foreach ($this->firmantes as $firmante) {
            $this->MultiCell($ancho_columna, 3.5, htmlspecialchars($firmante['cargo'], ENT_QUOTES, 'UTF-8'), 0, 'C', 0, 0, '', '', true);
        }
    }

    private function obtenerMesEspañol($mes) {
        $meses = ['01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril', '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto', '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'];
        return $meses[$mes] ?? '';
    }
}

// =========================================================
// SCRIPT DE CREACIÓN Y CONFIGURACIÓN DEL PDF
// =========================================================

$pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(defined('NOMBRE_SITIO') ? NOMBRE_SITIO : 'Comité Olímpico IPV');
$pdf->SetTitle('Certificado_Asistencia_' . str_replace(' ', '_', $participante['nombre_completo']));
$pdf->SetSubject('Certificado de Asistencia - Olimpiadas 2026');

// Definir los firmantes ordenados de IZQUIERDA a DERECHA: Teso -> Secre -> Vice -> Presi
$firmantes = [
    /*[
        'nombre' => 'Nombre Tesorero/a',
        'cargo'  => 'Tesorero / Vocal',
        'imagen' => APPPATH . 'assets/img/firmas/firma_tesorero.png'
    ],
    [
        'nombre' => 'Nombre Secretario/a',
        'cargo'  => 'Secretario General',
        'imagen' => APPPATH . 'assets/img/firmas/firma_secretario.png'
    ],
    [
        'nombre' => 'Nombre Vicepresidente/a',
        'cargo'  => 'Vicepresidente',
        'imagen' => APPPATH . 'assets/img/firmas/firma_vicepresidente.png'
    ],*/
    [
        'nombre' => 'Ing. Walter Hernan Vargas',
        'cargo'  => 'Presidente Comité Olimpico Organizador',
        'imagen' => 'assets/img/firmas/firma_presidente.jpg'
    ]
];

$pdf->setFirmantes($firmantes);

$pdf->SetMargins(18, 30, 18);
$pdf->SetHeaderMargin(8);
$pdf->SetFooterMargin(5);

$pdf->SetAutoPageBreak(TRUE, 55); 
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setFontSubsetting(true);

$pdf->SetFont('dejavusans', '', 10, '', true);

$pdf->AddPage('L', 'A4');
$pdf->Body($participante);

$pdf->Output('Certificado_Asistencia_' . str_replace(' ', '_', $participante['nombre_completo']) . '.pdf', 'I');
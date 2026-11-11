<?php
/**
 * PDF con TODOS los certificados de asistencia de una delegación:
 * un certificado por hoja en formato apaisado (Landscape), con las 4 firmas institucionales.
 *
 * Datos que recibe la vista:
 *   $participantes     -> array de participantes de la delegación (cada uno con
 *                         nombre_completo, dni, instituto, sexo y es_competidor)
 *   $nombre_provincia  -> delegación (para el título / nombre del archivo)
 */

require_once APPPATH . 'libraries/tcpdf/tcpdf.php';
require_once APPPATH . 'helpers/deslinde_helper.php';

class MYPDF_CERTIFICADOS_MULTI extends TCPDF {

    /** Participantes ya depurados (UTF-8) que se van imprimiendo de a uno. */
    public $participantes = array();

    /** Imagen del encabezado / logo institucional. */
    protected $imagen_header = '';

    /** Arreglo con los 4 firmantes del Comité Organizador. */
    protected $firmantes = [];

    public function __construct($participantes = array(), $firmantes = array()) {
        // Formato Apaisado ('L'), milímetros ('mm'), tamaño 'A4'
        parent::__construct('L', 'mm', 'A4', true, 'UTF-8', false);

        $this->participantes  = $participantes;
        $this->imagen_header  = deslinde_imagen_header();
        $this->firmantes      = $firmantes;

        $this->SetCreator(PDF_CREATOR);
        if (defined('NOMBRE_SITIO')) {
            $this->SetAuthor(NOMBRE_SITIO);
        }
        $this->SetTitle('Certificados_Asistencia_Delegacion');
        $this->SetSubject('Certificados de Asistencia - Olimpiadas 2026');
        $this->SetKeywords('TCPDF, PDF, OLIMPIADAS, VIVIENDAS, CERTIFICADOS');

        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Márgenes laterales y superiores
        $this->SetMargins(18, 25, 18);
        $this->SetHeaderMargin(8);
        $this->SetFooterMargin(5);
        $this->SetAutoPageBreak(TRUE, 52); // Dejar espacio para el footer de firmas

        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        if (@file_exists(APPPATH . 'libraries/tcpdf/lang/es.php')) {
            require_once(APPPATH . 'libraries/tcpdf/lang/es.php');
            $this->setLanguageArray($l);
        }

        $this->setFontSubsetting(true);
        $this->SetFont('dejavusans', '', 11, '', true);
    }

    public function Header() {
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

    /**
     * Construye el HTML del certificado adaptado para cada participante
     */
    protected function generarHtmlCertificado($participante) {
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
            D.N.I. N° <b>' . htmlspecialchars($participante['dni'] ?? '________________', ENT_QUOTES, 'UTF-8') . '</b>, 
            perteneciente a la delegación de la provincia de <b> ' . htmlspecialchars($participante['delegacion'] ?? 'nuestra institución', ENT_QUOTES, 'UTF-8') . '</b>, 
            ha participado activamente en calidad de ' . $rol_texto . ' en los contingentes y competencias desarrolladas en el marco de las 
            <b>“XXXVIII OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026”</b>, 
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

    /**
     * Inserta el certificado en una nueva hoja garantizando que ocupe exactamente una página.
     */
    public function AddPageNewCertificado($idx) {
        if (!isset($this->participantes[$idx])) {
            return FALSE;
        }

        $html = $this->generarHtmlCertificado($this->participantes[$idx]);

        for ($size = 11; $size >= 7; $size -= 0.5) {
            $this->nuevaHojaConLogo();
            $this->SetFont('dejavusans', '', $size, '', true);
            $this->writeHTML($html, true, false, true, false, '');

            if ($this->getPage() === $this->getNumPages()) {
                return TRUE; 
            }

            while ($this->getNumPages() > 0) {
                $this->deletePage($this->getNumPages());
            }
        }

        $this->nuevaHojaConLogo();
        $this->SetFont('dejavusans', '', 7, '', true);
        $this->writeHTML($html, true, false, true, false, '');

        return FALSE;
    }

    protected function nuevaHojaConLogo() {
        // Agrega página en Landscape ('L') tamaño A4
        $this->AddPage('L', 'A4');
        if (is_readable($this->imagen_header)) {
            // Logo institucional superior centrado
            $this->Image($this->imagen_header, 20, 10, 32, '', '', '', 'C', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Membrete superior institucional derecho
        $this->SetY(15);
        $this->SetFont('dejavusans', 'B', 10);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 5, 'COMITÉ OLIMPICO ORGANIZADOR', 0, 1, 'R');
        $this->SetFont('dejavusans', '', 8);
        $this->Cell(0, 4, 'XXXVIII Olimpiadas Nacionales de Institutos de Vivienda', 0, 1, 'R');
    }

    private function obtenerMesEspañol($mes) {
        $meses = ['01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril', '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto', '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'];
        return $meses[$mes] ?? '';
    }
}

// ---------------------------------------------------------
// Normalización de datos en UTF-8
// ---------------------------------------------------------
$certificados_data = array();
if (!empty($participantes)) {
    foreach ($participantes as $p) {
        foreach (array('nombre_completo', 'dni', 'telefono', 'fecha_nacimiento', 'email', 'instituto', 'sexo') as $campo) {
            if (!isset($p[$campo])) {
                $p[$campo] = '';
                continue;
            }
            $valor = (string) $p[$campo];
            if ($valor !== '' && !mb_check_encoding($valor, 'UTF-8')) {
                $convertido = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $valor);
                $p[$campo] = $convertido === FALSE ? $valor : $convertido;
            }
        }
        $certificados_data[] = $p;
    }
}

// Definir los 4 firmantes ordenados de izquierda a derecha: Tesorero -> Secretario -> Vicepresidente -> Presidente
$firmantes = [
   /* [
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

// Instanciar PDF múltiple de certificados
$pdf = new MYPDF_CERTIFICADOS_MULTI($certificados_data, $firmantes);

foreach ($certificados_data as $i => $p) {
    $pdf->AddPageNewCertificado($i);
}

$nombre_provincia = isset($nombre_provincia) ? $nombre_provincia : '';
$titulo = str_replace(' ', '_', trim(preg_replace('/\s+/', ' ', $nombre_provincia)));
if ($titulo === '') {
    $titulo = 'delegacion';
}

$pdf->Output('Certificados_Asistencia_' . $titulo . '_' . date('Y-m-d') . '.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
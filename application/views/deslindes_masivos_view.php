<?php
/**
 * PDF con TODOS los deslindes de responsabilidad de una delegación:
 * un deslinde por hoja, con exactamente el mismo formato de deslinde_resp_view.php.
 *
 * Lo genera Inscripciones::descargar_deslindes_delegacion() cuando el delegado
 * aprieta "Descargar Deslindes" en la parte de arriba de su panel.
 *
 * Datos que recibe la vista:
 *   $participantes     -> array de participantes de la delegación (cada uno con
 *                         nombre_completo, dni, telefono, fecha_nacimiento y
 *                         es_competidor)
 *   $nombre_provincia  -> delegación (para el título / nombre del archivo)
 */

require_once APPPATH . 'libraries/tcpdf/tcpdf.php';
require_once APPPATH . 'helpers/deslinde_helper.php';

class MYPDF_MULTI extends TCPDF {

    /** Participantes ya depurados (UTF-8) que se van imprimiendo de a uno. */
    public $participantes = array();

    /** Puntero del participante que toca en la página actual. */
    private $indice = 0;

    /** Imagen del encabezado (se resuelve en tiempo de ejecución). */
    private $imagen_header = '';

    public function __construct($participantes = array()) {
        parent::__construct('P', 'mm', 'LEGAL', true, 'UTF-8', false);

        $this->participantes   = $participantes;
        $this->imagen_header   = deslinde_imagen_header();

        $this->SetCreator(PDF_CREATOR);
        if (defined('NOMBRE_SITIO')) {
            $this->SetAuthor(NOMBRE_SITIO);
        }
        $this->SetTitle('Deslindes_Responsabilidad');
        $this->SetSubject('Deslindes_Responsabilidad');
        $this->SetKeywords('TCPDF, PDF, OLIMPIADAS, VIVIENDAS, DESLINDE');

        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Mismos márgenes que el deslinde individual
        $this->SetMargins(30, 50, 30);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(APPPATH . 'libraries/tcpdf/lang/es.php')) {
            require_once(APPPATH . 'libraries/tcpdf/lang/es.php');
            $this->setLanguageArray($l);
        }

        $this->setFontSubsetting(true);
        $this->SetFont('dejavusans', '', 14, '', true);
    }

    /**
     * A diferencia del deslinde individual, acá no podemos usar Header(): TCPDF
     * lo invoca también cuando corta una hoja por exceso de texto, y eso dibujaría
     * el logo arriba de la firma. Por eso el logo se pinta recién con cada
     * AddPageNewDeslinde(), garantizando una hoja = un deslinde.
     */
    public function Header() {
    }

    public function Footer() {
    }

    /**
     * Agrega una hoja nueva (con su logo) y escribe el deslinde del participante
     * indicado. Si el contenido no entra en la hoja, en vez de cortar el texto a
     * la mitad se achica la fuente hasta que entre entero.
     */
    public function AddPageNewDeslinde($idx) {
        $this->indice = $idx;
        $this->AddPage('P', 'LEGAL');

        if (is_readable($this->imagen_header)) {
            $this->Image($this->imagen_header, 30, 15, 50, '', '', '', 'C', false, 50, '', false, false, 0, false, false, false);
        }

        $html = deslinde_html($this->participantes[$idx]);

        // 14pt es la fuente con la que el deslinde individual ocupa poco más de
        // media hoja LEGAL, así que entran holgadamente. Igual, si algún texto
        // extra largo la supera, reducimos tamaño antes de partir la hoja.
        for ($size = 14; $size >= 7; $size -= 0.5) {
            $this->SetFont('dejavusans', '', $size, '', true);

            $start_y = $this->y;
            $this->writeHTML($html, true, false, true, false, '');

            if ($this->page === $this->numPages) {
                return TRUE; // entró completo en esta hoja
            }

            // Se cortó: descartamos las páginas extra y reintentamos más chico
            while ($this->numPages > $this->page) {
                $this->DeletePage($this->numPages);
            }
            $this->SetY($start_y, TRUE, TRUE);
            $this->x = $this->lMargin;
        }

        // Último intento con el tamaño mínimo (puede terminar cortándose)
        $this->SetFont('dejavusans', '', 7, '', true);
        $this->writeHTML($html, true, false, true, false, '');

        return FALSE;
    }
}

// ---------------------------------------------------------
// Normalizamos los datos: TCPDF necesita UTF-8 y el helper espera
// ciertos campos; si alguno viene en latin1/cp1252 se pierden Ñ y acentos.
// ---------------------------------------------------------
$deslindes = array();
if (!empty($participantes)) {
    foreach ($participantes as $p) {
        foreach (array('nombre_completo', 'dni', 'telefono', 'fecha_nacimiento', 'email') as $campo) {
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
        $deslindes[] = $p;
    }
}

$pdf = new MYPDF_MULTI($deslindes);

foreach ($deslindes as $i => $p) {
    $pdf->AddPageNewDeslinde($i);
}

$nombre_provincia = isset($nombre_provincia) ? $nombre_provincia : '';
$titulo = str_replace(' ', '_', trim(preg_replace('/\s+/', ' ', $nombre_provincia)));
if ($titulo === '') {
    $titulo = 'delegacion';
}

$pdf->Output('Deslindes_' . $titulo . '_' . date('Y-m-d') . '.pdf', 'D');

//============================================================+
// END OF FILE
//============================================================+

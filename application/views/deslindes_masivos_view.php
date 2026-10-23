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

    /** Imagen del encabezado (se resuelve en tiempo de ejecución). */
    protected $imagen_header = '';

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
     * Escribe el deslinde del participante indicado en su propia hoja.
     *
     * Primero intenta en una hoja nueva con el tamaño de letra normal (14pt,
     * igual que el deslinde individual). Si el texto fuera tan largo que no
     * entra (writeHTML cortaría y abriría una segunda hoja), descarta esa hoja
     * extra y reintenta en una hoja limpia con una fuente más chica, hasta que
     * el deslinde completo ocupe exactamente UNA hoja.
     */
    public function AddPageNewDeslinde($idx) {
        if (!isset($this->participantes[$idx])) {
            return FALSE;
        }

        $html = deslinde_html($this->participantes[$idx]);

        for ($size = 14; $size >= 7; $size -= 0.5) {
            $this->nuevaHojaConLogo();
            $this->SetFont('dejavusans', '', $size, '', true);
            $this->writeHTML($html, true, false, true, false, '');

            if ($this->getPage() === $this->getNumPages()) {
                return TRUE; // entró completo en esta hoja
            }

            // Se cortó: eliminamos TODAS las hojas generadas por este intento
            // (la original + las que haya abierto el auto page break) y
            // probamos de nuevo, más chico, en una hoja limpia.
            while ($this->getNumPages() > 0) {
                $this->deletePage($this->getNumPages());
            }
        }

        // No entró ni siquiera a 7pt (improbable): hoja única, tamaño mínimo.
        $this->nuevaHojaConLogo();
        $this->SetFont('dejavusans', '', 7, '', true);
        $this->writeHTML($html, true, false, true, false, '');

        return FALSE;
    }

    /**
     * Abre una hoja LEGAL limpia y le pinta el logo arriba (los márgenes son
     * los mismos del deslinde individual). El logo se dibuja acá y no en
     * Header() porque TCPDF invoca al header también cuando corta una hoja por
     * exceso de texto, y eso duplicaría el encabezado dentro de un deslinde.
     */
    protected function nuevaHojaConLogo() {
        $this->AddPage('P', 'LEGAL');
        if (is_readable($this->imagen_header)) {
            $this->Image($this->imagen_header, 30, 15, 50, '', '', '', 'C', false, 50, '', false, false, 0, false, false, false);
        }
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

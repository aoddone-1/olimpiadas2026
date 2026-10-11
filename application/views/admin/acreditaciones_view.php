<?php
require_once APPPATH . 'libraries/tcpdf/tcpdf.php';

class MYPDF extends TCPDF {

    // Medidas de cada acreditación (badget) en milímetros
    const ANCHO_ACREDITACION = 85;   // 8.5 cm
    const ALTO_ACREDITACION  = 130;  // 13 cm

    public $participantes = [];
    public $nombre_provincia = '';

    public function Header() {
        // Sin encabezado: las acreditaciones se imprimen una al lado de otra
    }

    /**
     * Dibuja una acreditación (rectángulo de 8.5 x 13 cm) con el nombre
     * completo del participante centrado, en la posición indicada.
     */
    public function dibujar_acreditacion($x, $y, $nombre) {
        $ancho  = self::ANCHO_ACREDITACION;
        $alto   = self::ALTO_ACREDITACION;

        // Rectángulo / borde de la acreditación
        $this->SetLineWidth(0.4);
        $this->SetDrawColor(30, 60, 114);      // azul oscuro
        $this->SetFillColor(255, 255, 255);
        $this->Rect($x, $y, $ancho, $alto, 'DF');

        // Nombre completo del participante, centrado en el recuadro
        $this->SetFont('dejavusans', 'B', 14);
        $this->SetXY($x + 3, $y);              // margen interno de 3 mm
        // ignore_min_height = TRUE para poder usar toda la altura del recuadro
        $this->Cell($ancho - 6, $alto, mb_strtoupper(trim($nombre)), 0, 0, 'C', false, 0, true, 'M', 'M');
    }

    public function Body() {
        $page_w = $this->getPageWidth();       // mm
        $page_h = $this->getPageHeight();      // mm

        $ancho  = self::ANCHO_ACREDITACION;
        $alto   = self::ALTO_ACREDITACION;

        $cols   = max(1, floor(($page_w - 10) / $ancho));   // cuántos entran por fila
        $rows   = max(1, floor(($page_h - 10) / $alto));    // cuántas filas por página

        // Centrado horizontal del bloque de acreditaciones
        $offset_x = ($page_w - ($cols * $ancho)) / 2;
        $offset_y = 5;

        $total = count($this->participantes);
        for ($i = 0; $i < $total; $i++) {
            $pos_fila   = $i % $cols;
            $pos_columna = intdiv($i, $cols) % $rows;

            // Nueva página cuando se llena la grilla
            if ($i > 0 && $pos_fila == 0 && $pos_columna == 0) {
                $this->AddPage('L', 'A4');
            }

            $x = $offset_x + $pos_fila * $ancho;
            $y = $offset_y + $pos_columna * $alto;

            $this->dibujar_acreditacion($x, $y, $this->participantes[$i]['nombre_completo']);
        }
    }

    public function Footer() {
        // Sin pie de página para no interferir con la impresión/recorte
    }
}

// ---------------------------------------------------------
// Crear el documento PDF
// ---------------------------------------------------------
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(NOMBRE_SITIO);
$pdf->SetTitle('Acreditaciones_' . $nombre_provincia);
$pdf->SetSubject('Acreditaciones_' . $nombre_provincia);
$pdf->SetKeywords('TCPDF, PDF, OLIMPIADAS, VIVIENDAS, ACREDITACIONES');

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Sin márgenes ni auto page break: manejamos la grilla manualmente
$pdf->SetMargins(0, 0, 0);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->SetAutoPageBreak(FALSE, 0);

$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

if (@file_exists(dirname(__FILE__) . '/lang/es.php')) {
    require_once(dirname(__FILE__) . '/lang/es.php');
    $pdf->setLanguageArray($l);
}

$pdf->setFontSubsetting(true);
$pdf->SetFont('dejavusans', '', 14, '', true);

// Datos
$pdf->participantes   = isset($participantes) ? $participantes : [];
$pdf->nombre_provincia = isset($nombre_provincia) ? $nombre_provincia : '';

// Página inicial (A4 horizontal para aprovechar el espacio: 3 x 2 acreditaciones por hoja)
$pdf->AddPage('L', 'A4');
$pdf->Body();

// Salida
$pdf->Output('acreditaciones_' . str_replace(' ', '_', $nombre_provincia) . '.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

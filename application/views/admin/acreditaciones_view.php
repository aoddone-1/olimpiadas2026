<?php
require_once APPPATH . 'libraries/tcpdf/tcpdf.php';

/**
 * Genera las acreditaciones (badgets) en PDF, replicando el diseño de la
 * credencial oficial de imprimir_credencial.php + css/credencial.css:
 *   - Header con degradé azul (#1e3c72 -> #2a5298), icono y nombre del sitio
 *   - Barra dorada inferior (#ffc107)
 *   - Nombre completo, DNI, código QR de control de mesa, badge "Control
 *     General de Mesa", delegación y tipo de empleado
 *   - Franja inferior con el texto de uso personal
 *
 * Cada acreditación es un rectángulo de 8.5 x 13 cm; se imprimen 3 x 2 por
 * hoja A4 horizontal.
 */
class MYPDF extends TCPDF {

    // Medidas de cada acreditación (badget) en milímetros
    const ANCHO_ACREDITACION = 85;   // 8.5 cm
    const ALTO_ACREDITACION  = 130;  // 13 cm

    // Colores del diseño de la credencial oficial
    const COLOR_AZUL_OSCURO = [30, 60, 114];    // #1e3c72
    const COLOR_AZUL_CLARO  = [42, 82, 152];    // #2a5298
    const COLOR_DORADO      = [255, 193, 7];    // #ffc107
    const COLOR_GRIS        = [108, 117, 125];  // #6c757d (text-muted)
    const COLOR_GRIS_FRASE  = [134, 142, 150];  // frase del pie
    const COLOR_CUADRO      = [221, 221, 221];  // borde exterior

    public $participantes = [];
    public $nombre_provincia = '';

    /**
     * Convierte el texto a UTF-8 de forma segura para TCPDF.
     * Si la base de datos devuelve latin1/Windows-1252, mb_strtoupper y
     * TCPDF pierden las Ñ y los acentos; esta función normaliza todo a UTF-8.
     */
    private function utf8($str) {
        $str = (string) $str;
        if (!mb_check_encoding($str, 'UTF-8')) {
            // Probar conversiones habituales (latin1 -> utf8, cp1252 -> utf8)
            $utf8 = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
            if (mb_check_encoding($utf8, 'UTF-8')) {
                $str = $utf8;
            } else {
                $str = mb_convert_encoding($str, 'UTF-8', 'Windows-1252');
            }
        }
        return $str;
    }

    /**
     * Mayúsculas seguras: primero pasa a UTF-8 y luego aplica mb_strtoupper.
     */
    private function upper($str) {
        return mb_strtoupper($this->utf8($str), 'UTF-8');
    }

    public function Header() {
        // Sin encabezado: las acreditaciones se imprimen una al lado de otra
    }

    public function Footer() {
        // Sin pie de página para no interferir con la impresión/recorte
    }

    /**
     * Dibuja una acreditación completa (8.5 x 13 cm) en la posición indicada.
     * Las proporciones internas reproducen las de css/credencial.css
     * (credencial de 380 x 560 px).
     */
    public function dibujar_acreditacion($x, $y, $p) {
        $ancho = self::ANCHO_ACREDITACION;
        $alto  = self::ALTO_ACREDITACION;

        // ---------------------------------------------------------------
        // Borde exterior de la acreditación (guía de recorte)
        // ---------------------------------------------------------------
        $this->SetLineWidth(0.3);
        $this->SetDrawColor(self::COLOR_CUADRO[0], self::COLOR_CUADRO[1], self::COLOR_CUADRO[2]);
        $this->SetFillColor(255, 255, 255);
        $this->Rect($x, $y, $ancho, $alto, 'DF');

        // Margen interno de 1 mm: todo el contenido vive dentro de este área
        $cx = $x + 1;
        $cy = $y + 1;
        $cw = $ancho - 2;   // ancho útil (~83 mm)
        $ch = $alto - 2;    // alto útil  (~128 mm)

        // ---------------------------------------------------------------
        // HEADER: imagen de fondo (assets/img/header.jpg) + barra dorada
        // ---------------------------------------------------------------
        $h_header = 26.5;

        $header_img = FCPATH . 'assets/img/header.jpg';
        if (file_exists($header_img)) {
            // JPEG sin canal alfa: no requiere conversión previa
            $this->Image($header_img, $cx, $cy, $cw, $h_header, 'JPG', '', '', false, 300, '', false, false, 0);
        } else {
            // Fallback: degradé azul si no existe la imagen
            $this->LinearGradient($cx, $cy, $cw, $h_header, self::COLOR_AZUL_OSCURO, self::COLOR_AZUL_CLARO, array(0, 0, 1, 1));
        }

        $h_dorado = 2.1;    // barra dorada (5px/560px)
        $this->SetFillColor(self::COLOR_DORADO[0], self::COLOR_DORADO[1], self::COLOR_DORADO[2]);
        $this->Rect($cx, $cy + $h_header, $cw, $h_dorado, 'F');

        // ---------------------------------------------------------------
        // CUERPO: nombre, DNI, divisor, QR, badge, delegación, tipo
        // ---------------------------------------------------------------
        $body_y = $cy + $h_header + $h_dorado + 3;

        // Nombre completo (centrado, negrita, mayúsculas)
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('dejavusans', 'B', 12);
        $this->SetXY($cx + 2, $body_y);
        // upper() normaliza a UTF-8 antes de pasar a mayúsculas: así la Ñ y
        // los acentos se imprimen correctamente (TCPDF usa fuentes Unicode).
        $this->MultiCell($cw - 4, 5.2, $this->upper(trim($p['nombre_completo'])), 0, 'C', false, 1);
        $body_y = $this->GetY() + 1.5;

        // DNI
        $this->SetTextColor(self::COLOR_GRIS[0], self::COLOR_GRIS[1], self::COLOR_GRIS[2]);
        $this->SetFont('dejavusans', 'B', 8);
        $this->SetXY($cx + 2, $body_y);
        $this->Cell($cw - 4, 4, 'DNI: ' . $this->utf8($p['dni']), 0, 0, 'C', false);
        $body_y += 5;

        // Línea divisoria (hr)
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(206, 212, 218);
        $this->Line($cx + 8, $body_y, $cx + $cw - 8, $body_y);
        $body_y += 3;

        // Código QR de control de mesa (igual que imprimir_credencial.php)
        $qr_size = 28;
        if (!empty($p['token_qr'])) {
            $url_acreditacion = base_url('Inscripciones/acreditacion/' . $p['token_qr']);
            // write2DBarcode es el método válido de TCPDF para generar QR.
            // El nivel de corrección ('M') se indica en el tipo: 'QRCODE,M'
            $qr_opt = array(
                'align' => 'C',   // centrado dentro del área asignada
                'mARGIN'=> 0,     // sin margen interno extra
            );
            $this->write2DBarcode($url_acreditacion, 'QRCODE,M', $cx + ($cw - $qr_size) / 2, $body_y, $qr_size, $qr_size, $qr_opt, false);
        } else {
            // Placeholder si el participante aún no tiene token generado
            $this->SetDrawColor(self::COLOR_AZUL_OSCURO[0], self::COLOR_AZUL_OSCURO[1], self::COLOR_AZUL_OSCURO[2]);
            $this->SetLineWidth(0.6);
            $this->Rect($cx + ($cw - $qr_size) / 2, $body_y, $qr_size, $qr_size, 'S');
            $this->SetFont('dejavusans', '', 6);
            $this->SetTextColor(self::COLOR_GRIS[0], self::COLOR_GRIS[1], self::COLOR_GRIS[2]);
            $this->SetXY($cx + ($cw - $qr_size) / 2, $body_y + $qr_size / 2 - 2);
            $this->Cell($qr_size, 4, 'SIN QR', 0, 0, 'C', false);
        }
        $body_y += $qr_size + 3;

        // Badge "Control General de Mesa" (fondo azul primario, texto blanco)
        $badge_txt = 'CONTROL GENERAL DE MESA';
        $this->SetFont('dejavusans', 'B', 6.5);
        $badge_w = $this->getStringWidth($badge_txt) + 8;
        $badge_h = 5;
        $this->SetFillColor(13, 110, 253); // bootstrap bg-primary
        $this->SetTextColor(255, 255, 255);
        $this->RoundedRect($cx + ($cw - $badge_w) / 2, $body_y, $badge_w, $badge_h, 1.5, 1.5, 'DF', 1.5);
        $this->SetXY($cx + ($cw - $badge_w) / 2, $body_y);
        $this->Cell($badge_w, $badge_h, $badge_txt, 0, 0, 'C', false);
        $body_y += $badge_h + 2;

        // Delegación (destacada) y tipo de empleado
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('dejavusans', 'B', 10);
        $this->SetXY($cx + 2, $body_y);
        $this->MultiCell($cw - 4, 4.5, $this->upper(trim($p['delegacion'])), 0, 'C', false, 1);
        $body_y = $this->GetY() + 0.5;

        // Si el participante es delegado: cinta dorada llamativa debajo de la delegación
        if (!empty($p['es_delegado'])) {
            $deleg_txt = $this->upper('★ DELEGADO ★');
            $this->SetFont('dejavusans', 'B', 8.5);
            $rib_w = max($this->getStringWidth($deleg_txt) + 12, 40);
            $rib_h = 6.5;
            $rib_x = $cx + ($cw - $rib_w) / 2;
            $rib_y = $body_y;

            // Sombra sutil para dar relieve
            $this->SetFillColor(180, 150, 40);
            $this->RoundedRect($rib_x + 0.6, $rib_y + 0.6, $rib_w, $rib_h, 1.8, 1.8, 'F', 0);

            // Cinta con degradé dorado (borde rojo exterior, relleno dorado)
            $this->SetDrawColor(200, 30, 30); // rojo
            $this->SetLineWidth(0.9);
            try {
                // Degradé lineal vertical: dorado claro -> dorado oscuro
                $this->LinearGradient($rib_x, $rib_y, $rib_w, $rib_h,
                    array(255, 215, 0), array(212, 160, 15), array(0, 0, 0, 1));
            } catch (Exception $e) {
                // Fallback si el degradé falla: color plano
                $this->SetFillColor(255, 215, 0);
                $this->RoundedRect($rib_x, $rib_y, $rib_w, $rib_h, 1.8, 1.8, 'DF', 0.9);
            }
            // Borde delgado adicional por encima para definir la cinta
            $this->SetLineWidth(0.4);
            $this->SetDrawColor(150, 100, 0);
            $this->RoundedRect($rib_x, $rib_y, $rib_w, $rib_h, 1.8, 1.8, 'S', 0);

            // Texto en negro sobre dorado, centrado
            $this->SetTextColor(20, 20, 20);
            $this->SetXY($rib_x, $rib_y);
            $this->Cell($rib_w, $rib_h, $deleg_txt, 0, 0, 'C', false);

            $body_y += $rib_h + 2;
        }

        if (!empty($p['tipo_empleado'])) {
            $this->SetTextColor(self::COLOR_GRIS[0], self::COLOR_GRIS[1], self::COLOR_GRIS[2]);
            $this->SetFont('dejavusans', 'B', 7.5);
            $this->SetXY($cx + 2, $body_y);
            $this->Cell($cw - 4, 4, $this->utf8($p['tipo_empleado']), 0, 0, 'C', false);
        }

        // ---------------------------------------------------------------
        // PIE: franja gris con la leyenda de uso personal (mt-auto)
        // ---------------------------------------------------------------
        $frase = 'Uso Personal: Esta credencial es intransferible y debe ser presentada cada vez que sea requerida.';
        $this->SetFont('dejavusans', '', 5.8);
        $frase_lines = $this->getNumLines($frase, $cw - 8);
        $h_pie = max(11, $frase_lines * 2.8 + 5);
        $pie_y = $cy + $ch - $h_pie;

        $this->SetFillColor(248, 249, 250); // #f8f9fa
        $this->Rect($cx, $pie_y, $cw, $h_pie, 'F');
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(222, 226, 230);
        $this->Line($cx, $pie_y, $cx + $cw, $pie_y);

        $this->SetTextColor(self::COLOR_GRIS_FRASE[0], self::COLOR_GRIS_FRASE[1], self::COLOR_GRIS_FRASE[2]);
        // MultiCell con resetp=true (último argumento) para que NO mueva el cursor:
        // así la frase queda dentro de la credencial y no se amontona en (0,0)
        // de las páginas siguientes.
        $this->SetXY($cx + 4, $pie_y + 2);
        $this->MultiCell($cw - 8, 2.8, $frase, 0, 'C', false, 0, '', '', true, 0, false, false, 0, true);
    }

    public function Body() {
        $page_w = $this->getPageWidth();       // mm
        $page_h = $this->getPageHeight();      // mm

        $ancho = self::ANCHO_ACREDITACION;
        $alto  = self::ALTO_ACREDITACION;

        $cols = max(1, floor(($page_w - 10) / $ancho));   // cuántos entran por fila
        $rows = max(1, floor(($page_h - 10) / $alto));    // cuántas filas por página

        // Centrado horizontal del bloque de acreditaciones
        $offset_x = ($page_w - ($cols * $ancho)) / 2;
        $offset_y = 5;

        $total = count($this->participantes);
        for ($i = 0; $i < $total; $i++) {
            $pos_fila    = $i % $cols;
            $pos_columna = intdiv($i, $cols) % $rows;

            // Nueva página cuando se llena la grilla
            if ($i > 0 && $pos_fila == 0 && $pos_columna == 0) {
                $this->AddPage('L', 'A4');
            }

            $x = $offset_x + $pos_fila * $ancho;
            $y = $offset_y + $pos_columna * $alto;

            $this->dibujar_acreditacion($x, $y, $this->participantes[$i]);
        }
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
$pdf->SetFont('dejavusans', '', 10, '', true);

// Datos
$pdf->participantes    = isset($participantes) ? $participantes : [];
$pdf->nombre_provincia = isset($nombre_provincia) ? $nombre_provincia : '';

// Página inicial (A4 horizontal para aprovechar el espacio: 3 x 2 acreditaciones por hoja)
$pdf->AddPage('L', 'A4');
$pdf->Body();

// Salida
$pdf->Output('acreditaciones_' . str_replace(' ', '_', $nombre_provincia) . '.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * ============================================================================
 *  REPORTE DEL LOCUTOR — "Guion de la ceremonia de premiación" (PDF / TCPDF)
 * ============================================================================
 *  Lo genera la pestaña PREMIACIÓN (Inscripciones/descargar_reporte_locutor).
 *  DESTINATARIO: el LOCUTOR de la cena, persona AJENA a la organización.
 *  Por eso el documento:
 *    - No usa siglas internas ni códigos (nada de "MARCADOR", "UTE", ids, DNI).
 *    - Explica en una carátula cómo leer cada renglón.
 *    - Imprime los nombres tal como deben LEERSE EN VOZ ALTA:
 *      en jornadas únicas / largada masiva ya vienen en el formato
 *      "PROVINCIA (NOMBRE)" → se leen primero en grande la PROVINCIA y el
 *      nombre va entre paréntesis, solo como referencia.
 *  Variables que inyecta el controlador: $guion, $fecha, $nombre_archivo.
 * ============================================================================
 */

// TCPDF ya viene cargado por la librería 'Pdf' del controlador; por si acaso:
if (!class_exists('TCPDF')) {
    require_once APPPATH . 'libraries/tcpdf/tcpdf.php';
}

class Rep_Locutor_PDF extends TCPDF {

    /** Datos del guion (['fecha','bloques','total_medallas']). */
    public $guion = array();
    public $nombre_archivo = 'reporte_locutor.pdf';

    /** Colores institucionales suaves (buena impresión en blanco y negro). */
    const AZUL   = array(20, 33, 61);
    const DORADO = array(241, 196, 15);
    const GRIS   = array(108, 117, 125);

    public function Header() {
        // Cabezal discreto en todas las hojas (el locutor siempre sabe qué noche tiene en la mano).
        if ($this->getPage() === 1) return; // la carátula ya tiene su propio encabezado
        $this->SetFont('dejavusans', 'B', 8);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(0, 5, 'GUION DE PREMIACION · ' . strtoupper($this->_fecha_larga()), 0, 1, 'R');
        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.2);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
        $this->SetY($this->GetY() + 4);
    }

    public function Footer() {
        $this->SetY(-16);
        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.2);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->SetFont('dejavusans', '', 7);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 8, NOMBRE_SITIO . ' · Documento para el locutor de la ceremonia', 0, 0, 'L');
        $this->Cell(0, 8, 'Pagina ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }

    private function _fecha_larga() {
        $f = isset($this->guion['fecha']) ? $this->guion['fecha'] : null;
        if (!$f) return '';
        $ts = strtotime($f);
        if (!$ts) return $f;
        // strftime deprecated en PHP 8.2+: armar a mano con mapas fijos.
        $dias = array('Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado');
        $meses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio',
                       'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
        return $dias[(int) date('w', $ts)] . ' ' . date('j', $ts) . ' de '
             . $meses[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
    }

    /**
     * Descompone un nombre de competidor para el locutor.
     *  - "CATAMARCA (LOPEZ, MARIA FERNANDA)"  => principal CATAMARCA, ref el nombre.
     *  - "LOPEZ, MARIA (35087017)" (formato viejo con DNI) => no exponer el DNI:
     *    principal = nombre, referencia vacia.
     *  - Cualquier otro texto (equipos) => principal = texto completo.
     */
    private function _separar_nombre($nombre) {
        $nombre = trim(preg_replace('/\s+/u', ' ', (string) $nombre));
        if (!preg_match('/^(.*)\(([^()]*)\)$/u', $nombre, $m)) {
            return array('principal' => $nombre, 'referencia' => '');
        }
        $cuerpo = trim($m[1]);
        $sufijo = trim($m[2]);
        if ($cuerpo === '') return array('principal' => $nombre, 'referencia' => '');
        // Sufijo numérico = DNI residual (formato viejo): NO imprimirlo.
        if (preg_match('/^[\d.\-]+$/', $sufijo)) {
            return array('principal' => $cuerpo, 'referencia' => '');
        }
        return array('principal' => $cuerpo, 'referencia' => $sufijo);
    }

    /** Un bloque = una competencia con su podio (formato texto con viñetas). */
    private function _bloque_entrega($b, $num) {
        // Evitar que un bloque se parta entre dos hojas.
        $alt_estimado = 14 + 10 * max(1, count($b['puestos']));
        if ($this->getY() + $alt_estimado > $this->h - $this->bMargin - 20) {
            $this->AddPage();
        }

        $titulo = strtoupper($b['deporte']) . ' — ' . strtoupper($b['categoria']);
        if ($b['genero'] !== '') $titulo .= ' (' . strtoupper($b['genero']) . ')';
        $es_prueba_ind = (isset($b['anunciar']) && $b['anunciar'] === 'provincia');
        if ($es_prueba_ind) $titulo .= '  ·  PRUEBA INDIVIDUAL: se ANUNCIA LA PROVINCIA';

        // Título de la competencia: texto en negrita, sin fondo de color.
        $this->SetTextColor(20, 33, 61);
        $this->SetFont('dejavusans', 'B', 11);
        $this->Write(6, $num . '.- ' . $titulo);
        $this->Ln(7);

        // Etiqueta atrasada (para el locutor, en lenguaje claro).
        if (!empty($b['atraso'])) {
            $this->SetFont('dejavusans', 'BI', 8);
            $this->SetTextColor(180, 120, 0);
            $this->Write(4.5, '     (Entrega atrasada: premio de otra noche)');
            $this->Ln(5.5);
            $this->SetTextColor(20, 33, 61);
        }

        // Medallistas: bronce -> plata -> oro (orden del anuncio), como viñetas de texto.
        $orden = array(3, 2, 1);
        $etiquetas = array(
            1 => 'ORO',
            2 => 'PLATA',
            3 => 'BRONCE',
        );

        foreach ($orden as $pos) {
            if (!isset($b['puestos'][$pos])) continue;
            $p = $b['puestos'][$pos];
            $sep = $this->_separar_nombre($p['nombre']);

            // Viñeta: "• BRONCE: NOMBRE (ref.)"
            $this->SetFont('dejavusans', '', 10);
            $this->SetTextColor(108, 117, 125);
            $this->Write(5.5, '   • ');

            $this->SetFont('dejavusans', 'B', 10);
            $this->SetTextColor(20, 33, 61);
            $this->Write(5.5, $etiquetas[$pos] . ': ');

            // Nombre principal (lo que se anuncia en voz alta).
            $this->SetFont('dejavusans', 'B', 12);
            $this->SetTextColor(20, 33, 61);
            $linea = $sep['principal'];
            if ($sep['referencia'] !== '') {
                $linea .= '  ';
            }
            $this->Write(5.5, $linea);

            // Referencia en tamaño menor y gris, en la misma línea.
            if ($sep['referencia'] !== '') {
                $this->SetFont('dejavusans', '', 9);
                $this->SetTextColor(108, 117, 125);
                $this->Write(5.5, '(ref.: ' . $sep['referencia'] . ')');
            }

            // Nota de desempate, traducida a lenguaje simple.
            if (!empty($p['desempate'])) {
                $this->Ln(6);
                $this->SetFont('dejavusans', 'I', 8);
                $this->SetTextColor(120, 90, 10);
                $this->Write(4.5, '        Se definio en ' . strtoupper($p['desempate']) . '.');
                $this->SetTextColor(20, 33, 61);
            }

            $this->Ln(6);
        }

        if (!isset($b['puestos'][1]) && !isset($b['puestos'][2]) && !isset($b['puestos'][3])) {
            $this->SetFont('dejavusans', 'I', 9);
            $this->SetTextColor(108, 117, 125);
            $this->Write(5, '   • Sin galardonados definidos: NO anunciar esta entrega.');
            $this->Ln(6);
            $this->SetTextColor(20, 33, 61);
        }

        $this->Ln(3);
    }

    public function Body() {

        if (!empty($this->guion['bloques'])) {
            $this->AddPage();
            $num = 0;
            foreach ($this->guion['bloques'] as $b) {
                $num++;
                $this->_bloque_entrega($b, $num);
            }
        }

        $this->Output($this->nombre_archivo, 'I');
    }
}

// ==========================================
// CONFIGURACIÓN Y EJECUCIÓN DEL PDF
// ==========================================
$guion          = isset($guion) ? $guion : array('fecha' => null, 'bloques' => array(), 'total_medallas' => 0);
$nombre_archivo = isset($nombre_archivo) ? $nombre_archivo : 'reporte_locutor_' . date('Y-m-d') . '.pdf';

$pdf = new Rep_Locutor_PDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->guion = $guion;
$pdf->nombre_archivo = $nombre_archivo;

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(NOMBRE_SITIO);
$pdf->SetTitle('Guion de premiacion para el locutor — ' . (isset($guion['fecha']) ? $guion['fecha'] : ''));
$pdf->SetSubject('Ceremonia de premiacion');
$pdf->SetKeywords('LOCUTOR, PREMIACION, CEREMONIA');

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(15, 22, 15);
$pdf->SetHeaderMargin(8);
$pdf->SetFooterMargin(18);
$pdf->SetAutoPageBreak(TRUE, 26);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setFontSubsetting(true);
$pdf->SetFont('dejavusans', '', 11, '', true);

$pdf->Body();

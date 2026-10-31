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

    // Colores institucionales
    const AZUL_OSCURO = array(20, 33, 61);
    const DORADO = array(241, 196, 15);
    const PLATA = array(192, 192, 192);
    const BRONCE = array(205, 127, 50);
    const GRIS_SUAVE = array(240, 240, 245);
    const GRIS_MEDIO = array(108, 117, 125);

    public function Header() {
        //(80, 50, 50);
        $this->SetFont('dejavusans', 'B', 8);
        $this->SetTextColor(108, 117, 125);
        $this->Image('assets/img/header.jpg', $this->lMargin, $this->GetY(), 100, '', '', '', 'C', false, 50, '', false, false,0, false, false, false);
        
        $this->Cell(0, 80, 'GUION DE PREMIACION · ' . strtoupper($this->_fecha_larga()), 0, 1, 'R');
        
        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.2);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin,$this->GetY());
        $this->SetY($this->GetY() + 4);
        
        
    }

    public function Footer() {
        // Pie de página: separador, nombre del sitio a la izquierda y paginación a la derecha
        $this->SetY(-16);
        $this->SetDrawColor(203, 213, 225);
        $this->SetLineWidth(0.2);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->SetFont('dejavusans', '', 7);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 8, NOMBRE_SITIO, 0, 0, 'L');
        $this->Cell(0, 8, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }

    private function _fecha_larga() {
        $f = isset($this->guion['fecha']) ? $this->guion['fecha'] : null;
        if (!$f) return '';
        $ts = strtotime($f);
        if (!$ts) return $f;
        
        $dias = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');
        $meses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
        
        return $dias[(int) date('w', $ts)] . ' ' . date('j', $ts) . ' de ' 
            . $meses[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
    }

    

    /**
     * Separa nombre principal de referencia
     */
    private function _separar_nombre($nombre) {
        $nombre = trim(preg_replace('/\s+/u', ' ', (string) $nombre));
        
        if (!preg_match('/^(.*)\(([^()]*)\)$/u', $nombre, $m)) {
            return array('principal' => $nombre, 'referencia' => '');
        }
        
        $cuerpo = trim($m[1]);
        $sufijo = trim($m[2]);
        
        if ($cuerpo === '') return array('principal' => $nombre, 'referencia' => '');
        
        // Si es numérico (DNI), no mostrar
        if (preg_match('/^[\d.\-]+$/', $sufijo)) {
            return array('principal' => $cuerpo, 'referencia' => '');
        }
        
        return array('principal' => $cuerpo, 'referencia' => $sufijo);
    }

    /**
     * BLOQUE DE ENTREGA - Formato profesional de guion
     */
    private function _bloque_entrega($b, $num) {
        // Verificar espacio disponible
        $alt_estimado = 50 + 25 * max(1, count($b['puestos']));
        if ($this->getY() + $alt_estimado > $this->h - $this->bMargin - 30) {
            $this->AddPage();
        }
        
        // ========================================
        // ENCABEZADO DEL BLOQUE
        // ========================================
        $this->SetFillColor(self::AZUL_OSCURO[0], self::AZUL_OSCURO[1], self::AZUL_OSCURO[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('dejavusans', 'B', 14);
        
        $titulo = $num . '. ' . strtoupper($b['deporte']) . ' — ' . strtoupper($b['categoria']);
        $this->Cell(0, 10, '  ' . $titulo, 0, 1, 'L', 1);
        
        $this->Ln(2);
        
        // Nota de prueba individual si aplica
        if (isset($b['anunciar']) && $b['anunciar'] === 'provincia') {
            $this->SetFont('dejavusans', 'I', 9);
            $this->SetTextColor(180, 120, 0);
            $this->Cell(0, 5, '     PRUEBA INDIVIDUAL: Se anuncia la provincia ganadora', 0, 1, 'L');
            $this->Ln(2);
        }
        
        // ========================================
        // LISTA DE GANADORES
        // ========================================
        $orden = array_keys($b['puestos']);
        sort($orden);
        
        foreach ($orden as $pos) {
            if (!isset($b['puestos'][$pos])) continue;
            $p = $b['puestos'][$pos];
            
            // Separar nombre
            $sep = $this->_separar_nombre($p['nombre']);
            $nombre_mostrar = strtoupper($sep['principal']);
            $ref_mostrar = $sep['referencia'];
            
            // Color según posición
            if ($pos == 1) {
                $color = self::DORADO;
                $simbolo = '🥇';
                $fondo_color = array(255, 248, 220);
            } elseif ($pos == 2) {
                $color = self::PLATA;
                $simbolo = '🥈';
                $fondo_color = array(245, 245, 245);
            } elseif ($pos == 3) {
                $color = self::BRONCE;
                $simbolo = '🥉';
                $fondo_color = array(255, 240, 230);
            } else {
                $color = self::GRIS_MEDIO;
                $simbolo = $pos . '°';
                $fondo_color = self::GRIS_SUAVE;
            }
            
            // Fondo suave para el ganador
            $this->SetFillColor($fondo_color[0], $fondo_color[1], $fondo_color[2]);
            $y_inicio = $this->GetY();
            
            // Número de posición grande
            $this->SetFont('dejavusans', 'B', 20);
            $this->SetTextColor($color[0], $color[1], $color[2]);
            $this->Cell(20, 12, $pos . '°', 0, 0, 'C');
            
            // Nombre del ganador EN GRANDE
            $this->SetFont('dejavusans', 'B', 16);
            $this->SetTextColor(self::AZUL_OSCURO);
            $this->Cell(0, 12, $nombre_mostrar, 0, 1, 'L');
            
            // Referencia (si existe) en tamaño menor
            if ($ref_mostrar !== '') {
                $this->SetFont('dejavusans', '', 9);
                $this->SetTextColor(self::GRIS_MEDIO);
                $this->Cell(20, 5, '', 0, 0);
                $this->Cell(0, 5, '(ref.: ' . $ref_mostrar . ')', 0, 1, 'L');
            }
            
            // Premio que recibe
            if (!empty($p['premio'])) {
                $this->SetFont('dejavusans', 'B', 10);
                $this->SetTextColor($color[0], $color[1], $color[2]);
                $this->Cell(20, 6, '', 0, 0);
                $this->Cell(0, 6, '→ ' . strtoupper($p['premio']), 0, 1, 'L');
            }
            
            // Nota de desempate
            if (!empty($p['desempate'])) {
                $this->SetFont('dejavusans', 'I', 8);
                $this->SetTextColor(150, 100, 0);
                $this->Cell(20, 5, '', 0, 0);
                $this->MultiCell(0, 5, 'Definido por: ' . strtoupper($p['desempate']), 0, 'L');
            }
            
            // Espacio para NOTAS del locutor
            $this->SetY($this->GetY() + 2);
            $this->SetFont('dejavusans', 'I', 8);
            $this->SetTextColor(180, 180, 180);
            $this->Cell(20, 5, '', 0, 0);
            $this->Cell(0, 5, 'Notas: ___________________________________________', 0, 1, 'L');
            
            // Línea separadora
            $this->Ln(3);
            $this->SetDrawColor(220, 220, 230);
            $this->SetLineWidth(0.2);
            $this->Line($this->lMargin + 5, $this->GetY(), $this->w - $this->rMargin - 5, $this->GetY());
            $this->Ln(5);
        }
        
        // Si no hay ganadores
        if (empty($b['puestos'])) {
            $this->SetFont('dejavusans', 'I', 10);
            $this->SetTextColor(self::GRIS_MEDIO);
            $this->Cell(0, 8, 'SIN GALARDONADOS — No anunciar esta entrega', 0, 1, 'C');
        }
        
        // Espacio grande entre bloques
        $this->Ln(10);
    }

    /**
     * CUERPO DEL DOCUMENTO
     */
    public function Body() {
        
        // Luego los bloques
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
$pdf->SetMargins(80, 100, 50);
$pdf->SetHeaderMargin(8);
$pdf->SetFooterMargin(18);
$pdf->SetAutoPageBreak(TRUE, 26);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setFontSubsetting(true);
$pdf->SetFont('dejavusans', '', 11, '', true);

$pdf->Body();

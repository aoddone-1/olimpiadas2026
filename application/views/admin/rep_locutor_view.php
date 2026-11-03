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
 *    - Un DEPORTE POR HOJA: cada deporte arranca en una página nueva con su
 *      encabezado ("FUTBOL · CATEGORIAS A PREMIAR"), así el locutor puede
 *      separar las hojas por deporte. Las categorías siguen numeradas de forma
 *      continua en todo el documento.
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

    /** Encabezado de sección: un deporte por hoja (ver Body()). */
    private function _encabezado_deporte($deporte) {
        // Banda CLARA con texto oscuro: antes el texto usaba el mismo color
        // que el fondo (azul oscuro sobre azul oscuro) y el nombre del deporte
        // quedaba invisible. Además, las bandas oscuras salen casi negras al
        // imprimir en blanco y negro; una banda clara se lee perfecto.
        // Letras GRANDES: el locutor lee el guion de pie y a distancia; se
        // prioriza legibilidad sobre densidad (suba de tamaños + altura de
        // celda proporcional para conservar la estética).
        $this->SetFont('dejavusans', 'B', 17);
        $this->SetTextColor(20, 33, 61);          // Texto: azul oscuro
        $this->SetFillColor(232, 232, 240);       // Fondo: gris azulado suave
        $this->SetDrawColor(20, 33, 61);          // Borde: azul oscuro
        $this->SetLineWidth(0.5);
        $this->Cell(0, 13, '  ' . strtoupper($deporte) . '  ·  CATEGORIAS A PREMIAR', 1, 1, 'L', 1);
        $this->SetTextColor(108, 117, 125);
        $this->SetFont('dejavusans', '', 11);
        $this->Cell(0, 7, '  Anunciar todas las categorias de este deporte en orden.', 0, 1, 'L');
        $this->Ln(4);
        $this->SetTextColor(20, 33, 61);
    }

    /** Un bloque = una competencia con su podio (formato texto con viñetas). */
    private function _bloque_entrega($b, $num) {
        // Integrantes de los equipos premiados (los manda el modelo en
        // $b['integrantes'] como id_ute => array de nombres). Solo se listan
        // cuando la categoría es de EQUIPO.
        $integrantes_map = (isset($b['integrantes']) && is_array($b['integrantes']))
            ? $b['integrantes'] : array();
        $es_equipo = !empty($b['es_equipo']);

        // Calcular espacio necesario: cada equipo premiado puede sumar 2 o 3
        // renglones extra por la lista de integrantes.
        // alt_estimado sigue a los tamaños de fuente agrandados (ver abajo):
        // header más alto + renglones de premiados con más aire.
        $alt_estimado = 40 + 22 * max(1, count($b['puestos']));
        if ($es_equipo && $integrantes_map) {
            foreach ($b['puestos'] as $pp) {
                if (!empty($pp['id']) && !empty($integrantes_map[(int) $pp['id']])) {
                    $alt_estimado += 16;
                }
            }
        }
        if ($this->getY() + $alt_estimado > $this->h - $this->bMargin - 20) {
            $this->AddPage();
            // Si la página nueva quedó dentro del mismo deporte, repetir el
            // encabezado para que ninguna hoja quede sin identificar el deporte.
            if (!empty($b['deporte'])) {
                $this->_encabezado_deporte($b['deporte']);
            }
        }

        // ========================================
        // TÍTULO COMO HEADER / CUADRO DE SECCIÓN
        // ========================================
        $titulo = strtoupper($b['deporte']) . ' — Cat. ' . strtoupper($b['categoria']);


        // Fondo y borde del header
        $this->SetFillColor(232, 232, 240);      // Gris azulado suave
        $this->SetDrawColor(180, 180, 190);      // Borde gris
        $this->SetFont('dejavusans', 'B', 14);
        $this->SetTextColor(20, 33, 61);

        // Celda con borde completo, fondo y salto de línea al final
        $this->Cell(0, 11, '  ' . $num . '.  ' . $titulo, 1, 1, 'L', 1);

        $this->Ln(3); // Espacio entre el header y los ganadores

        // Prueba individual (si aplica)
        $es_prueba_ind = (isset($b['anunciar']) && $b['anunciar'] === 'provincia');
        if ($es_prueba_ind) {
            $this->SetFont('dejavusans', 'I', 11);
            $this->SetTextColor(180, 120, 0);
            $this->Cell(0, 7, '     · PRUEBA INDIVIDUAL: se anuncia la provincia', 0, 1, 'L');
            $this->Ln(2);
        }



        // ========================================
        // LISTA DE GANADORES (1°, 2°, 3°)
        // ========================================
        $orden = array(1, 2, 3); // 1° primero, luego 2°, luego 3°




        $etiquetas = array(1 => '1°', 2 => '2°', 3 => '3°');

        // Colores distintivos
        $colores = array(
            1 => array(180, 140, 0),   // Oro
            2 => array(130, 130, 130), // Plata
            3 => array(184, 115, 51)   // Bronce
        );

        foreach ($orden as $pos) {
            if (!isset($b['puestos'][$pos])) continue;
            $p = $b['puestos'][$pos];



            // Obtener nombre y referencia
            if (method_exists($this, '_separar_nombre')) {
                $sep = $this->_separar_nombre($p['nombre']);
                $nombre_mostrar = $sep['principal'];
                $ref_mostrar = $sep['referencia'];
            } else {
                $nombre_mostrar = $p['nombre'];
                $ref_mostrar = !empty($p['referencia']) ? $p['referencia'] : '';
            }

            // Número de posición (ancho fijo, alineado a derecha)
            $this->SetFont('dejavusans', 'B', 15);
            $this->SetTextColor($colores[$pos][0], $colores[$pos][1], $colores[$pos][2]);
            $this->Cell(14, 9, $etiquetas[$pos], 0, 0, 'R');

            // Nombre y referencia: lo MÁS grande del documento — es lo que
            // el locutor tiene que leer en voz alta.
            $this->SetFont('dejavusans', 'B', 16);
            $this->SetTextColor(20, 33, 61);

            $texto_completo = $nombre_mostrar;
            if ($ref_mostrar !== '') {
                $texto_completo .= '  (ref.: ' . $ref_mostrar . ')';
            }

            $this->MultiCell(0, 9, $texto_completo, 0, 'L', 0, 1);

            // Equipos: listar los integrantes separados por " - " y entre
            // paréntesis, para que el locutor sepa QUIÉNES suben al escenario.
            if ($es_equipo && !empty($p['id'])) {
                $ints = isset($integrantes_map[(int) $p['id']]) ? $integrantes_map[(int) $p['id']] : array();
                if (is_array($ints) && $ints) {
                    $this->SetFont('dejavusans', '', 11);
                    $this->SetTextColor(93, 102, 112);
                    $this->Cell(14, 6, '', 0, 0, 'R'); // sangría: alineado bajo el nombre
                    $this->MultiCell(0, 6, '(' . implode(' - ', $ints) . ')', 0, 'L', 0, 1);
                    $this->SetTextColor(20, 33, 61);
                }
            }

            // Nota de desempate
            if (!empty($p['desempate'])) {
                $this->SetFont('dejavusans', 'I', 10);
                $this->SetTextColor(120, 90, 10);
                $this->Cell(14, 6, '', 0, 0, 'R'); // Espacio para alinear
                $this->MultiCell(0, 6, 'Definido por ' . strtoupper($p['desempate']), 0, 'L', 0, 1);
                $this->SetTextColor(20, 33, 61);
            }










            $this->Ln(3);
        }

        // Sin galardonados
        if (!isset($b['puestos'][1]) && !isset($b['puestos'][2]) && !isset($b['puestos'][3])) {
            $this->SetFont('dejavusans', 'I', 11);
            $this->SetTextColor(108, 117, 125);
            $this->Cell(14, 7, '', 0, 0, 'R');
            $this->MultiCell(0, 7, 'Sin galardonados definidos: NO anunciar esta entrega.', 0, 'L', 0, 1);
        }

        $this->Ln(8); // Espacio final entre bloques
    }

    public function Body() {

        if (!empty($this->guion['bloques'])) {
            $num = 0;
            $deporte_actual = null;
            foreach ($this->guion['bloques'] as $b) {
                // === UN DEPORTE POR HOJA ===
                // El guion ya viene ordenado por deporte (ver
                // Premiacion_model::obtener_guion_locutor), así que cada vez
                // que cambia el deporte se corta la hoja y se imprime su
                // encabezado. Si un deporte tiene más categorías de las que
                // entran en una hoja, continúa en la siguiente con el
                // encabezado repetido (ver _bloque_entrega).
                $deporte = trim((string) ($b['deporte'] ?? ''));
                if ($deporte !== $deporte_actual) {
                    $this->AddPage();
                    $deporte_actual = $deporte;
                    if ($deporte !== '') {
                        $this->_encabezado_deporte($deporte);
                    }
                }
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
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
     * Limpia el nombre de la categoría para el TÍTULO del bloque:
     * muestra SOLO el nombre, quitando el género (Masculino/Femenino/Mixto),
     * el tipo de largada (Largada A/B, Masiva...) y la modalidad
     * (Individual/Dupla/Equipos...). Es tolerante a separadores '·', '-', '/', ','.
     */
    private function _limpiar_nombre_categoria($nombre) {
        $original = trim(preg_replace('/\s+/u', ' ', (string) $nombre));
        $nombre   = $original;

        // Términos que NO deben aparecer en el título del reporte del locutor.
        $excluir = '(?:masculino|femenino|mixto|mixta|misto|varon|varones|'
                 . 'hombre|hombres|mujer|mujeres|'
                 . 'largada\s*[a-z0-9]?|salida\s*[a-z0-9]?|masiv[oa]|'
                 . 'individual(?:es)?|dupla[s]?|pareja[s]?|dobles?|team)';
        // "equipos" solo se quita en la modalidad "por equipos"; nunca "dama/s",
        // "caballeros" u otros nombres que pueden ser el nombre real de la categoría.
        $prefijo = '(?:por\s+equipos|' . $excluir . ')';
        $regex = '\b' . $excluir . '\b';

        // 1) Cortar el nombre en el primer separador seguido de un término a excluir
        //    (ej: "Libre · Masculino · Largada B" -> "Libre").
        if (preg_match('/^(.*?)[\s·\-\/\,]+(' . $regex . '|por\s+equipos)/iu', $nombre, $m)) {
            $corte = trim($m[1], " \t\n·-/,\x{00B7}");
            // Solo cortar si quedó un nombre razonable (evita destruir nombres simples).
            if ($corte !== '') {
                $nombre = $corte;
            }
        }

        // 2) Quitar términos sueltos que hayan quedado incrustados (con sus separadores).
        $nombre = preg_replace('/[\s·\-\/\,]*\(' . $regex . '\)/iu', '', $nombre);
        $nombre = preg_replace('/[\s·\-\/\,]+\b' . $regex . '/iu', '', $nombre);
        // "Por equipos" solo si viene precedido de separador ("Tenis Por Equipos -> Tenis").
        $nombre = preg_replace('/[\s·\-\/\,]+\bpor\s+equipos\b/iu', '', $nombre);

        // 3) Si el nombre COMIENZA con la modalidad ("Por Equipos Masculino"),
        //    quitar también ese prefijo (y sus separadores sobrantes).
        $nombre = preg_replace('/^' . $prefijo . '[\s·\-\/\,]*/iu', '', $nombre);

        // 4) Limpiar separadores sobrantes al inicio/final.
        $nombre = trim($nombre, " \t\n·-/,\x{00B7}");

        return $nombre !== '' ? $nombre : $original;
    }

    /**
     * BLOQUE DE ENTREGA - Formato profesional de guion.
     * Se imprime DENTRO de la página de su DEPORTE (Body() agrupa por
     * deporte y salta de página solo al cambiar de deporte), así el locutor
     * tiene una hoja limpia por disciplina con todas sus categorías abajo.
     */
    private function _bloque_entrega($b, $num) {
        // ========================================
        // TÍTULO DE LA CATEGORÍA (el deporte va en el encabezado de página)
        // ========================================
        $this->SetFillColor(self::GRIS_SUAVE[0], self::GRIS_SUAVE[1], self::GRIS_SUAVE[2]);
        $this->SetTextColor(self::AZUL_OSCURO[0], self::AZUL_OSCURO[1], self::AZUL_OSCURO[2]);
        $this->SetFont('dejavusans', 'B', 12);

        // Solo el NOMBRE de la categoría: se excluyen género, tipo de largada
        // y modalidad del título (el locutor ya los conoce por el deporte).
        $nombre_cat = self::_limpiar_nombre_categoria($b['categoria']);
        $titulo = $num . '. ' . strtoupper($nombre_cat);
        $this->Cell(0, 8, '  ' . $titulo, 0, 1, 'L', 1);

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
            
            // Integrantes del equipo premiado (deportes de EQUIPO) o espacio
            // para notas del locutor (pruebas individuales).
            $integrantes = null;
            if (!empty($b['es_equipo']) && !empty($p['id'])
                && !empty($b['integrantes'][(int) $p['id']])) {
                $integrantes = $b['integrantes'][(int) $p['id']];
            }
            $this->SetY($this->GetY() + 2);
            if ($integrantes) {
                // En equipos el locutor necesita saber QUIÉNES suben al
                // escenario: se listan separados por " - ".
                $x_txt = $this->lMargin + 20;
                $this->SetFont('dejavusans', '', 8);
                $this->SetTextColor(self::GRIS_MEDIO);
                $this->SetX($x_txt);
                $this->Cell(0, 5, 'Integrantes:', 0, 1, 'L');
                $this->SetFont('dejavusans', 'B', 8);
                $this->SetTextColor(self::AZUL_OSCURO);
                $this->SetX($x_txt);
                $this->MultiCell($this->w - $this->rMargin - $x_txt, 4.5,
                    implode(' - ', $integrantes), 0, 'L');
            } else {
                $this->SetFont('dejavusans', 'I', 8);
                $this->SetTextColor(180, 180, 180);
                $this->Cell(20, 5, '', 0, 0);
                $this->Cell(0, 5, 'Notas: ___________________________________________', 0, 1, 'L');
            }
            
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
    }

    /**
     * Banda azul con el nombre del DEPORTE: cabecera de la página del guion.
     */
    private function _banda_deporte($deporte) {
        $this->SetFillColor(self::AZUL_OSCURO[0], self::AZUL_OSCURO[1], self::AZUL_OSCURO[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('dejavusans', 'B', 16);
        $this->Cell(0, 12, '  ' . strtoupper($deporte), 0, 1, 'L', 1);
        $this->Ln(3);
    }

    /**
     * CUERPO DEL DOCUMENTO — UN DEPORTE POR PÁGINA.
     * Se agrupan las entregas por deporte; cada deporte arranca en una hoja
     * nueva con su banda de título y debajo van TODAS sus categorías.
     * Los cortes de página se hacen SIEMPRE entre deportes (una categoría
     * jamás queda partida): si un deporte necesita más de una hoja, todas
     * sus categorías se "comprimen" hasta cerrarlas en la mínima cantidad
     * de hojas posible, y recién entonces pasa el siguiente deporte.
     */
    public function Body() {

        if (!empty($this->guion['bloques'])) {
            // Agrupar los bloques (ya ordenados deporte->categoría->género)
            // por deporte, conservando el orden de aparición.
            $por_deporte = array();
            foreach ($this->guion['bloques'] as $b) {
                $dep = trim((string) $b['deporte']);
                if (!isset($por_deporte[$dep])) $por_deporte[$dep] = array();
                $por_deporte[$dep][] = $b;
            }

            $num  = 0;
            $primero = true;
            foreach ($por_deporte as $dep => $bloques_dep) {
                $titulo_dep = strtoupper($dep !== '' ? $dep : 'OTROS');

                // Medir el alto real de cada categoría del deporte.
                $altos = array();
                foreach ($bloques_dep as $b) {
                    $altos[] = $this->_alto_bloque($b);
                }

                // Hojas necesarias con espaciado normal (4 mm entre bloques).
                $tope_normal = $this->h - $this->bMargin - $this->getFooterMargin();
                $pags = $this->_paginas_necesarias($altos, 4, $tope_normal);
                // Si no cierra en una sola hoja, comprimir el espacio entre
                // categorías hasta el mínimo legible (2 mm) para agrupar lo
                // máximo de entregas por página.
                $espacio = 4;
                if ($pags > 1) {
                    for ($e = 3; $e >= 2; $e--) {
                        $p2 = $this->_paginas_necesarias($altos, $e, $tope_normal);
                        if ($p2 < $pags) { $pags = $p2; $espacio = $e; }
                        if ($pags === 1) break;
                    }
                }

                // Cada DEPORTE empieza en una página nueva.
                if (!$primero) {
                    $this->AddPage();
                }
                $primero = false;
                $this->_banda_deporte($titulo_dep);

                $ultima = count($bloques_dep);
                foreach ($bloques_dep as $i => $b) {
                    $num++;
                    // Antes de imprimir, verificar que la categoría entre en
                    // lo que queda de hoja; si no, pasarla completa a la
                    // siguiente (con su banda de deporte repetida).
                    if ($this->GetY() + $altos[$i] > $tope_normal) {
                        $this->AddPage();
                        $this->_banda_deporte($titulo_dep);
                    }
                    $this->_bloque_entrega($b, $num);
                    if ($i < $ultima - 1) $this->Ln($espacio);
                }
            }
        } else {
            $this->AddPage();
        }

        $this->Output($this->nombre_archivo, 'I');
    }

    /**
     * Cantidad de hojas que ocupan bloques de $altos mm con $espacio entre
     * ellos, dentro de un área útil de $tope mm de alto por hoja.
     */
    private function _paginas_necesarias($altos, $espacio, $tope) {
        $pags = 1;
        $y = 0;
        foreach ($altos as $a) {
            if ($y > 0) $y += $espacio;
            if ($y + $a > $tope) { $pags++; $y = $a; }
            else { $y += $a; }
        }
        return $pags;
    }

    /**
     * Altura real (mm) que ocupa una entrega: se renderiza en una página
     * descartable y se mide cuánto avanzó el cursor. La hoja de medición se
     * elimina limpiamente (deletePage + reindexado interno), por eso la
     * numeración final del documento queda intacta.
     */
    private function _alto_bloque($b) {
        $this->AddPage();
        $pago = $this->page;       // hoja recién creada para medir
        $y0 = $this->GetY();
        $this->_bloque_entrega($b, 0); // número 0: solo para medir
        $alto = $this->GetY() - $y0;

        // --- eliminar la hoja de medición (equivalente a deletePage pero
        // reindexando también las marcas internas de posición) ---
        $this->deletePage($pago);
        if ($pago < $this->numpages) {
            for ($i = $pago; $i <= $this->numpages; ++$i) {
                $j = $i + 1;
                foreach (array('intmrk','cntmrk','bordermrk','transfmrk',
                              'footerpos','footerlen') as $arr) {
                    if (isset($this->$arr[$j])) {
                        $this->$arr[$i] = $this->$arr[$j];
                        unset($this->$arr[$j]);
                    }
                }
            }
        }
        $this->CurPage = $this->page;
        return $alto;
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

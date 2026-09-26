<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// TCPDF ya viene cargado por la librería 'Pdf' del controlador; por si acaso:
if (!class_exists('TCPDF')) {
    require_once APPPATH . 'libraries/tcpdf/tcpdf.php';
}

class MYPDF extends TCPDF {
    // Propiedades para recibir los datos desde el controlador
    public $datos = [];
    public $formato = 'lista';
    public $dia_filtro = null;
    public $ancho_franja = 1;
    public $nombre_archivo = 'Nombre_Archivo.pdf';
    public $titulo_encabezado = 'FIXTURE DE COMPETENCIA';

    public function Header() {
        // Imagen del encabezado exactamente como la solicitaste
        $this->Image('assets/img/header.jpg', 30, 15, 50, '', '', '', 'C', false, 50, '', false, false, 0, false, false, false);
        
        // Se mantiene el título y la línea divisoria para conservar el diseño original
        $this->SetFont('dejavusans', 'B', 12);
        $this->SetY(10);
        $this->Cell(0, 10, $this->titulo_encabezado, 0, 1, 'C');
        $this->SetY(20);
        $this->SetDrawColor(30, 60, 114);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
        $this->SetY(24);
    }

    public function Body() {
        $html = "";
        $esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

        /** Orden garantizado: Deporte → Categoría → Fecha → Hora. */
        usort($this->datos, function ($a, $b) {
            return [
                strtolower($a['nombre_deporte'] ?? ''),
                strtolower($a['nombre_categoria'] ?? ''),
                (string) ($a['fecha_competencia'] ?? ''),
                (string) ($a['hora_inicio'] ?? ''),
                (int) ($a['numero_fecha'] ?? 0)
            ] <=> [
                strtolower($b['nombre_deporte'] ?? ''),
                strtolower($b['nombre_categoria'] ?? ''),
                (string) ($b['fecha_competencia'] ?? ''),
                (string) ($b['hora_inicio'] ?? ''),
                (int) ($b['numero_fecha'] ?? 0)
            ];
        });

        /** Filtrar por día si corresponde */
        if (!empty($this->dia_filtro)) {
            $this->datos = array_values(array_filter($this->datos, function ($f) {
                return substr((string) ($f['fecha_competencia'] ?? ''), 0, 10) === $this->dia_filtro;
            }));
        }

        $deporte_cat = function ($f) {
            return trim(($f['nombre_deporte'] ?? '') . ' ' . ($f['nombre_categoria'] ?? '') . ' ' . ($f['genero_categoria'] ?? ''));
        };

        $horario = function ($f) {
            $hi = substr((string) ($f['hora_inicio'] ?? ''), 0, 5);
            $hf = substr((string) ($f['hora_fin'] ?? ''), 0, 5);
            return $hi . (($hf !== '' && $hf !== '00:00') ? ' – ' . $hf : '');
        };

        $equipos = function ($f) {
            $e1 = trim((string) ($f['ute_1_nombre'] ?? ''));
            $e2 = trim((string) ($f['ute_2_nombre'] ?? ''));
            if ($e1 !== '' && $e2 !== '') return $e1 . ' <span style="color:#95a5a6;">vs</span> ' . $e2;
            if ($e2 !== '') return $e2;
            if ($e1 !== '') return $e1;
            return '';
        };

        /** Días presentes en los datos */
        $dias = array();
        foreach ($this->datos as $fila) {
            $f = substr((string) ($fila['fecha_competencia'] ?? ''), 0, 10);
            if ($f !== '') $dias[$f] = true;
        }
        $dias = array_keys($dias);
        sort($dias);

        $fase_bonito = function ($fase) {
            $map = array(
                'GRUPO' => 'Fase de Grupos', '16AVOS' => '16avos de Final', 'OCTAVOS' => 'Octavos de Final',
                'CUARTOS' => 'Cuartos de Final', 'SEMIFINAL' => 'Semifinal', 'TERCER_PUESTO' => '3er Puesto',
                'FINAL' => 'Final', 'JORNADA_UNICA' => 'Jornada Única'
            );
            return isset($map[$fase]) ? $map[$fase] : (string) $fase;
        };

        $n_dias = count($dias);
        $rango_txt = $n_dias
            ? 'Desde ' . date('d/m/Y', strtotime($dias[0])) . ' hasta ' . date('d/m/Y', strtotime($dias[$n_dias - 1]))
            : 'Sin fechas cargadas';
        
        $this->titulo_encabezado = 'FIXTURE DE COMPETENCIA — ' . NOMBRE_META;

        // ====== COLORES ======
        $c_primary   = '#1e3a5f';
        $c_secondary = '#3498db';
        $c_light     = '#ecf0f1';
        $c_border    = '#bdc3c7';
        $c_text      = '#2c3e50';
        $c_muted     = '#7f8c8d';
        $c_white     = '#ffffff';
        $c_alt       = '#f8f9fa';

        if ($this->formato === 'lista') {
            $html = '<div style="text-align:center; font-size:16pt; font-weight:bold; color:' . $c_primary . '; padding:10px 0;">'
                . 'Fixture — Listado de partidos</div>'
                . '<div style="text-align:center; font-size:9pt; color:' . $c_muted . '; padding-bottom:15px;">' 
                . $esc($rango_txt) . ' &nbsp;·&nbsp; ' . count($this->datos) . ' partido(s)</div>';
            
            if (!$this->datos) {
                $html .= '<p style="text-align:center; font-size:10pt; color:' . $c_muted . ';">No hay partidos cargados.</p>';
            } else {
                $html .= '<table cellpadding="5" cellspacing="0" border="1" style="border-color:' . $c_border . '; border-collapse:collapse; width:100%; font-size:8pt; color:' . $c_text . ';">'
                    . '<thead><tr style="background-color:' . $c_primary . '; color:' . $c_white . ';">'
                    . '<th style="padding:6px; text-align:center; border:1px solid ' . $c_border . ';">Deporte / Cat.</th>'
                    . '<th style="padding:6px; text-align:center; border:1px solid ' . $c_border . ';">Fecha</th>'
                    . '<th style="padding:6px; text-align:center; border:1px solid ' . $c_border . ';">Horario</th>'
                    . '<th style="padding:6px; text-align:center; border:1px solid ' . $c_border . ';">Competidor 1</th>'
                    . '<th style="padding:6px; text-align:center; border:1px solid ' . $c_border . ';">Competidor 2</th>'
                    . '<th style="padding:6px; text-align:center; border:1px solid ' . $c_border . ';">Lugar</th>'
                    . '</tr></thead><tbody>';
                
                $ult_grupo = null;
                foreach ($this->datos as $i => $f) {
                    $grupo = ($f['nombre_deporte'] ?? '') . '|' . ($f['nombre_categoria'] ?? '');
                    if ($grupo !== $ult_grupo) {
                        $html .= '<tr><td colspan="6" style="background-color:' . $c_secondary . '; color:' . $c_white . '; padding:5px; font-weight:bold; border:1px solid ' . $c_border . ';">' 
                            . $esc($deporte_cat($f)) . '</td></tr>';
                        $ult_grupo = $grupo;
                    }
                    $fecha = trim((string) ($f['fecha_competencia'] ?? ''));
                    if ($fecha !== '') $fecha = date('d/m/Y', strtotime($fecha));
                    $sub = trim($fase_bonito($f['fase'] ?? '')
                        . (!empty($f['numero_fecha']) ? ' · Fecha ' . (int) $f['numero_fecha'] : '')
                        . (!empty($f['nombre_prueba']) ? ' · ' . $f['nombre_prueba'] : ''));
                    
                    $bg = ($i % 2) ? $c_alt : $c_white;
                    $html .= '<tr style="background-color:' . $bg . ';">'
                        . '<td style="padding:4px; border:1px solid ' . $c_border . ';"><b>' . $esc(trim(($f['nombre_deporte'] ?? '') . ' ' . ($f['nombre_categoria'] ?? ''))) . '</b></td>'
                        . '<td style="padding:4px; border:1px solid ' . $c_border . '; text-align:center;">' . $esc($fecha) . '</td>'
                        . '<td style="padding:4px; border:1px solid ' . $c_border . '; text-align:center; font-weight:bold;">' . $esc($horario($f)) . '</td>'
                        . '<td style="padding:4px; border:1px solid ' . $c_border . ';">' . $esc($f['ute_1_nombre'] ?? '') 
                            . ($sub !== '' ? '<br/><span style="font-size:6pt; color:' . $c_muted . ';">' . $esc($sub) . '</span>' : '') . '</td>'
                        . '<td style="padding:4px; border:1px solid ' . $c_border . ';">' . $esc($f['ute_2_nombre'] ?? '') . '</td>'
                        . '<td style="padding:4px; border:1px solid ' . $c_border . '; color:' . $c_muted . '; font-size:7pt;">' . $esc($f['lugar_nombre'] ?? '') . '</td>'
                        . '</tr>';
                }
                $html .= '</tbody></table>';
            }
            $this->writeHTML($html, true, false, true, false, '');

        } else {
            // ====== FORMATO CUADRO - EXACTAMENTE 5 DÍAS ======
            $ancho = max(1, (int) $this->ancho_franja);
            $min_hora = null;
            $max_hora = null;
            foreach ($this->datos as $fila) {
                $hi = (string) ($fila['hora_inicio'] ?? '');
                $hf = (string) ($fila['hora_fin'] ?? '');
                if ($hi === '') continue;
                $h_ini = (int) substr($hi, 0, 2);
                $h_fin = $hf !== '' ? (int) substr($hf, 0, 2) : $h_ini;
                if ((int) substr($hf, 3, 2) > 0 || $h_fin === $h_ini) $h_fin++;
                if ($min_hora === null || $h_ini < $min_hora) $min_hora = $h_ini;
                if ($max_hora === null || $h_fin > $max_hora) $max_hora = $h_fin;
            }
            if ($min_hora === null) { $min_hora = 8; $max_hora = 9; }
            
            $min_hora = intdiv($min_hora, $ancho) * $ancho;
            $max_hora = intdiv($max_hora - $min_hora - 1, $ancho) * $ancho + $ancho + $min_hora;
            $n_frajas = intdiv($max_hora - $min_hora, $ancho);

            // Distribuir partidos
            $celdas = array();
            foreach ($this->datos as $fila) {
                $fecha = substr((string) ($fila['fecha_competencia'] ?? ''), 0, 10);
                if ($fecha === '') continue;
                $h_ini = (int) substr((string) ($fila['hora_inicio'] ?? '0'), 0, 2);
                $idx = intdiv(max(0, $h_ini - $min_hora), $ancho);
                if ($idx >= $n_frajas) $idx = $n_frajas - 1;
                $celdas[$idx][$fecha][] = $fila;
            }

            // ====== FORZAR 5 DÍAS POR PÁGINA ======
            $por_bloque = 5;
            $bloques = array();
            for ($b = 0; $b < $n_dias; $b += $por_bloque) {
                $bloques[] = array_slice($dias, $b, $por_bloque);
            }
            if (!$bloques) $bloques = array(array());
            
            $orientacion = 'L'; // Siempre horizontal para 5 días
            
            $dias_completos = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');
            $dias_cortos    = array('DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB');

            // Anchos: horario 12%, cada día 17.6% (5 días = 88%)
            $w_horario = 12;
            $w_col = (100 - $w_horario) / 5;

            foreach ($bloques as $bi => $bloque) {
                if ($bi > 0) {
                    $this->AddPage($orientacion, 'A4');
                }
                
                // Encabezado
                $html_bloque = '<div style="text-align:center; font-size:16pt; font-weight:bold; color:' . $c_primary . '; padding:5px 0 8px 0;">'
                            . 'Fixture de Competencia — Cuadro General</div>'
                            . '<div style="text-align:center; font-size:9pt; color:' . $c_muted . '; padding-bottom:12px;">' 
                            . $esc($rango_txt) . ' &nbsp;·&nbsp; ' . count($this->datos) . ' partido(s)'
                            . ' &nbsp;·&nbsp; Franjas de ' . $ancho . ' h</div>';

                if ($n_dias > $por_bloque) {
                    $html_bloque .= '<div style="text-align:right; font-size:8pt; color:' . $c_muted . '; padding-bottom:8px;">'
                                . 'Página ' . ($bi + 1) . ' de ' . count($bloques) 
                                . ' &nbsp;·&nbsp; Días ' . ($bi * $por_bloque + 1) . '–' 
                                . ($bi * $por_bloque + count($bloque)) . ' de ' . $n_dias . '</div>';
                }

                if (!$bloque) {
                    $html_bloque .= '<p style="text-align:center; font-size:10pt; color:' . $c_muted . ';">No hay partidos cargados.</p>';
                    $this->writeHTML($html_bloque, true, false, true, false, '');
                    continue;
                }

                // Completar con columnas vacías si hay menos de 5 días
                while (count($bloque) < 5) {
                    $bloque[] = null;
                }

                // Tabla principal
                $html_bloque .= '<table cellpadding="3" cellspacing="0" border="1" style="border-color:' . $c_border . '; border-collapse:collapse; width:100%; font-size:7pt; color:' . $c_text . ';">';
                
                // Encabezado de días
                $html_bloque .= '<thead>'
                            . '<tr style="background-color:' . $c_primary . '; color:' . $c_white . ';">'
                            . '<th style="width:' . $w_horario . '%; padding:8px 4px; text-align:center; border:1px solid ' . $c_border . '; font-size:9pt;">HORARIO</th>';
                
                foreach ($bloque as $f) {
                    if ($f === null) {
                        $html_bloque .= '<th style="width:' . $w_col . '%; padding:6px 4px; text-align:center; border:1px solid ' . $c_border . '; background-color:' . $c_light . ';"></th>';
                    } else {
                        $ts = strtotime($f);
                        $nombre_dia = $dias_cortos[(int) date('w', $ts)];
                        $fecha_corta = date('d/m', $ts);
                        $html_bloque .= '<th style="width:' . $w_col . '%; padding:6px 4px; text-align:center; border:1px solid ' . $c_border . ';">'
                                    . '<div style="font-size:11pt; font-weight:bold; letter-spacing:1px;">' . $nombre_dia . '</div>'
                                    . '<div style="font-size:8pt; opacity:0.9;">' . $fecha_corta . '</div>'
                                    . '</th>';
                    }
                }
                $html_bloque .= '</tr></thead><tbody>';

                // Filas de franjas horarias
                for ($i = 0; $i < $n_frajas; $i++) {
                    $h_ini = $min_hora + $i * $ancho;
                    $h_fin = $h_ini + $ancho;
                    $bg_franja = ($i % 2) ? $c_white : $c_alt;
                    
                    $html_bloque .= '<tr style="background-color:' . $bg_franja . ';">';
                    
                    // Columna horario
                    $html_bloque .= '<td style="background-color:' . $c_light . '; font-weight:bold; text-align:center; padding:6px 4px; border:1px solid ' . $c_border . '; font-size:9pt; color:' . $c_primary . ';">'
                                . sprintf('%02d:00<br/>–<br/>%02d:00', $h_ini % 24, $h_fin % 24)
                                . '</td>';

                    // Columnas de días
                    foreach ($bloque as $f) {
                        if ($f === null) {
                            $html_bloque .= '<td style="background-color:' . $c_light . '; border:1px solid ' . $c_border . ';">&nbsp;</td>';
                            continue;
                        }

                        $partidos = isset($celdas[$i][$f]) ? $celdas[$i][$f] : array();
                        
                        if (empty($partidos)) {
                            $html_bloque .= '<td style="background-color:' . $c_white . '; border:1px solid ' . $c_border . '; color:' . $c_muted . '; text-align:center; font-style:italic; font-size:6pt;">—</td>';
                        } else {
                            $cel = '';
                            foreach ($partidos as $idx_p => $p) {
                                $dc = trim(($p['nombre_deporte'] ?? '') . ' ' . ($p['nombre_categoria'] ?? '') . ' ' . ($p['genero_categoria'] ?? ''));
                                $sub_info = trim($fase_bonito($p['fase'] ?? '') . (!empty($p['numero_fecha']) ? ' · F' . (int) $p['numero_fecha'] : ''));
                                
                                if ($idx_p > 0) {
                                    $cel .= '<hr style="border:none; border-top:1px dashed ' . $c_border . '; margin:4px 0;"/>';
                                }
                                
                                $cel .= '<div style="margin-bottom:2px;">'
                                    . '<div style="font-size:7pt; font-weight:bold; color:' . $c_secondary . ';">' . $esc($dc) . '</div>'
                                    . '<div style="font-size:7pt; margin:2px 0;">' . $equipos($p) . '</div>';
                                
                                if ($sub_info !== '') {
                                    $cel .= '<div style="font-size:6pt; color:' . $c_muted . '; font-style:italic;">' . $esc($sub_info) . '</div>';
                                }
                                
                                if (!empty($p['lugar_nombre'])) {
                                    $cel .= '<div style="font-size:6pt; color:' . $c_muted . ';"> ' . $esc($p['lugar_nombre']) . '</div>';
                                }
                                
                                $cel .= '</div>';
                            }
                            $html_bloque .= '<td style="padding:3px; border:1px solid ' . $c_border . '; vertical-align:top;">' . $cel . '</td>';
                        }
                    }
                    $html_bloque .= '</tr>';
                }
                $html_bloque .= '</tbody></table>';
                
                $this->writeHTML($html_bloque, true, false, true, false, '');
            }
        }
    }

    public function Footer() {
        // Se mantiene el pie de página original con paginación y fecha
        
    }
}

// ==========================================
// CONFIGURACIÓN Y EJECUCIÓN DEL PDF
// ==========================================

// create new PDF document
$pdf = new MYPDF('L', 'mm' ,'LEGAL', true,'UTF-8', false);

// ⚠️ IMPORTANTE: Asigna aquí tus variables externas antes de generar el cuerpo
// Descomenta y ajusta según cómo recibas las variables desde tu controlador:
$pdf->datos = $datos;
$pdf->formato = $formato;
$pdf->dia_filtro = $dia_filtro;
$pdf->ancho_franja = $ancho_franja;
$pdf->nombre_archivo = $nombre_archivo;

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(NOMBRE_SITIO);
$pdf->SetTitle('Fixture ' . NOMBRE_META);
$pdf->SetSubject('Fixture de competencia');
$pdf->SetKeywords('TCPDF, PDF, OLIMPIADAS, VIVIENDAS');

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(30, 50, 30);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/es.php')) {
    require_once(dirname(__FILE__).'/lang/es.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
$pdf->SetFont('dejavusans', '', 14, '', true);

// Add a page
$pdf->AddPage('L','LEGAL');

// Generar el contenido del cuerpo (aquí se ejecuta toda la lógica HTML)
$pdf->Body();

// ---------------------------------------------------------

// Close and output PDF document
$pdf->Output($pdf->nombre_archivo, 'I');

//============================================================+
// END OF FILE
//============================================================+
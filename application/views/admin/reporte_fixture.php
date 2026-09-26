<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * REPORTE PDF DEL FIXTURE (TCPDF)
 * --------------------------------
 * Plantilla que genera el PDF de descarga del fixture. Reemplaza al CSV.
 *
 * Variables que envía el controlador Inscripciones::descargar_pdf_fixture():
 *   $datos          array  Partidos (Fixture_model::obtener_todo_el_fixture()).
 *   $formato        string 'cuadro' (columnas = días, filas = franjas horarias)
 *                          'lista'  (una fila por partido).
 *   $dia_filtro     string|null  Fecha AAAA-MM-DD a imprimir, o NULL para todos los días.
 *   $ancho_franja   int     Ancho en horas de cada franja horaria del cuadro.
 *   $nombre_archivo string  Nombre del PDF a descargar.
 */

// TCPDF ya viene cargado por la librería 'Pdf' del controlador; por si acaso:
if (!class_exists('TCPDF')) {
    require_once APPPATH . 'libraries/tcpdf/tcpdf.php';
}

/* ============================================================
 *  FixturePDF: encabezado con logo + pie de página con paginación
 * ============================================================ */
class FixturePDF extends TCPDF {

    /** @var string Título mostrado en el encabezado de todas las páginas. */
    public $titulo_encabezado = '';

    public function Header() {
        // Logo (ruta relativa desde FCPATH, igual que en deslinde_resp_view.php)
        if (file_exists('assets/img/header.jpg')) {
            $this->Image('assets/img/header.jpg', 10, 8, 45, '', '', '', 'L', false, 300, '', false, false, 0, false, false, false);
        }

        // Título centrado
        $this->SetFont('dejavusans', 'B', 12);
        $this->SetY(10);
        $this->Cell(0, 10, $this->titulo_encabezado, 0, 1, 'C');

        // Línea divisoria bajo el encabezado
        $this->SetY(20);
        $this->SetDrawColor(30, 60, 114);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
        $this->SetY(24);
    }

    public function Footer() {
        $this->SetY(-14);
        $this->SetFont('dejavusans', '', 8);
        $this->SetTextColor(110, 110, 110);
        $this->Cell(0, 8, NOMBRE_META . '  —  Generado el ' . date('d/m/Y H:i'), 0, 0, 'L');
        $this->Cell(0, 8, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasTotalPages(), 0, 0, 'R');
    }
}

/* ============================================================
 *  Utilidades de datos (mismos criterios que usaba el CSV)
 * ============================================================ */

$esc = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

/** Orden garantizado: Deporte → Categoría → Fecha → Hora → Fecha de torneo. */
usort($datos, function ($a, $b) {
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

/** Si llegó un día filtrado desde el panel, solo se imprime ese día. */
if (!empty($dia_filtro)) {
    $datos = array_values(array_filter($datos, function ($f) use ($dia_filtro) {
        return substr((string) ($f['fecha_competencia'] ?? ''), 0, 10) === $dia_filtro;
    }));
}

/** "Deporte Categoría Género" en una sola línea. */
$deporte_cat = function ($f) {
    return trim(($f['nombre_deporte'] ?? '') . ' ' . ($f['nombre_categoria'] ?? '') . ' ' . ($f['genero_categoria'] ?? ''));
};

/** "HH:MM–HH:MM" del partido. */
$horario = function ($f) {
    $hi = substr((string) ($f['hora_inicio'] ?? ''), 0, 5);
    $hf = substr((string) ($f['hora_fin'] ?? ''), 0, 5);
    return $hi . (($hf !== '' && $hf !== '00:00') ? '–' . $hf : '');
};

/** "Local vs Visitante" / "(slot libre)". */
$equipos = function ($f) {
    $e1 = trim((string) ($f['ute_1_nombre'] ?? ''));
    $e2 = trim((string) ($f['ute_2_nombre'] ?? ''));
    if ($e1 !== '' && $e2 !== '') return $e1 . ' vs ' . $e2;
    if ($e2 !== '') return $e2;
    if ($e1 !== '') return $e1;
    return '(slot libre)';
};

/** Días presentes en los datos, en orden cronológico. */
$dias = array();
foreach ($datos as $fila) {
    $f = substr((string) ($fila['fecha_competencia'] ?? ''), 0, 10);
    if ($f !== '') $dias[$f] = true;
}
$dias = array_keys($dias);
sort($dias);

/** Nombre legible de la fase del torneo. */
$fase_bonito = function ($fase) {
    $map = array(
        'GRUPO' => 'Fase de Grupos', '16AVOS' => '16avos. de Final', 'OCTAVOS' => 'Octavos de Final',
        'CUARTOS' => 'Cuartos de Final', 'SEMIFINAL' => 'Semifinal', 'TERCER_PUESTO' => '3er Puesto',
        'FINAL' => 'Final', 'JORNADA_UNICA' => 'Jornada Única'
    );
    return isset($map[$fase]) ? $map[$fase] : (string) $fase;
};

/* ============================================================
 *  Configuración del documento
 * ============================================================ */
$pdf = new FixturePDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(NOMBRE_SITIO);
$pdf->SetTitle('Fixture ' . NOMBRE_META);
$pdf->SetSubject('Fixture de competencia');
$pdf->SetKeywords('TCPDF, PDF, OLIMPIADAS, VIVIENDAS, FIXTURE');
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(12, 26, 12);
$pdf->SetHeaderMargin(8);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 16);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setFontSubsetting(true);
$pdf->SetFont('dejavusans', '', 8, '', true);
$pdf->aliasNbPgTotalPg();

$n_dias = count($dias);
$rango_txt = $n_dias
    ? 'Desde ' . date('d/m/Y', strtotime($dias[0])) . ' hasta ' . date('d/m/Y', strtotime($dias[$n_dias - 1]))
    : 'Sin fechas cargadas';

$pdf->titulo_encabezado = 'FIXTURE DE COMPETENCIA — ' . NOMBRE_META;

$css = '
table { font-size: 7pt; }
th { background-color:#1e3c72; color:#ffffff; font-weight:bold; padding:2px; text-align:center; }
td { border:0.5px solid #999999; padding:2px; vertical-align:top; }
.franja { background-color:#e8edf5; font-weight:bold; text-align:center; }
.hora { }
.sub { color:#555555; font-size:6pt; }
.lugar { color:#2a5298; font-size:6pt; }
.titulo { font-size:13pt; font-weight:bold; color:#1e3c72; text-align:center; }
.info { font-size:8pt; color:#444444; text-align:center; }
.alt { background-color:#f4f6fa; }
.grupo { background-color:#dbe4f0; font-weight:bold; color:#1e3c72; }
';

/* ============================================================
 *  FORMATO LISTADO: una fila por partido
 * ============================================================ */
if ($formato === 'lista') {
    $pdf->AddPage('P', 'A4');

    $html = '<div class="titulo">Fixture — Listado de partidos</div>'
        . '<div class="info">' . $esc($rango_txt) . ' &nbsp;·&nbsp; ' . count($datos) . ' partido(s)</div><br>';

    if (!$datos) {
        $html .= '<p style="text-align:center; font-size:10pt;">No hay partidos cargados en el fixture.</p>';
    } else {
        $html .= '<table width="100%" cellpadding="3" cellspacing="0">'
            . '<thead><tr>'
            . '<th width="14%">Deporte / Cat.</th>'
            . '<th width="10%">Fecha</th>'
            . '<th width="10%">Horario</th>'
            . '<th width="26%">Equipo / Competidor 1</th>'
            . '<th width="26%">Equipo / Competidor 2</th>'
            . '<th width="14%">Lugar</th>'
            . '</tr></thead><tbody>';

        $ult_grupo = null;
        foreach ($datos as $i => $f) {
            $grupo = ($f['nombre_deporte'] ?? '') . '|' . ($f['nombre_categoria'] ?? '');
            if ($grupo !== $ult_grupo) {
                $html .= '<tr><td colspan="6" class="grupo">' . $esc($deporte_cat($f)) . '</td></tr>';
                $ult_grupo = $grupo;
            }

            $fecha = trim((string) ($f['fecha_competencia'] ?? ''));
            if ($fecha !== '') $fecha = date('d/m/Y', strtotime($fecha));

            $sub = trim($fase_bonito($f['fase'] ?? '')
                . (!empty($f['numero_fecha']) ? ' · Fecha ' . (int) $f['numero_fecha'] : '')
                . (!empty($f['nombre_prueba']) ? ' · ' . $f['nombre_prueba'] : ''));

            $html .= '<tr class="' . ($i % 2 ? 'alt' : '') . '">'
                . '<td>' . $esc(trim(($f['nombre_deporte'] ?? '') . ' ' . ($f['nombre_categoria'] ?? ''))) . '</td>'
                . '<td>' . $esc($fecha) . '</td>'
                . '<td>' . $esc($horario($f)) . '</td>'
                . '<td>' . $esc($f['ute_1_nombre'] ?? '') . ($sub !== '' ? '<div class="sub">' . $esc($sub) . '</div>' : '') . '</td>'
                . '<td>' . $esc($f['ute_2_nombre'] ?? '') . '</td>'
                . '<td>' . $esc($f['lugar_nombre'] ?? '') . '</td>'
                . '</tr>';
        }
        $html .= '</tbody></table>';
    }

    $pdf->writeHTML($css . $html, true, false, true, false, '');
}

/* ============================================================
 *  FORMATO CUADRO: columnas = días, filas = franjas horarias
 * ============================================================ */ else {
    // 1) Franjas horarias (bloques de $ancho_franja horas) que cubren todos los partidos
    $ancho = max(1, (int) $ancho_franja);
    $min_hora = null;
    $max_hora = null;
    foreach ($datos as $fila) {
        $hi = (string) ($fila['hora_inicio'] ?? '');
        $hf = (string) ($fila['hora_fin'] ?? '');
        if ($hi === '') continue;
        $h_ini = (int) substr($hi, 0, 2);
        $h_fin = $hf !== '' ? (int) substr($hf, 0, 2) : $h_ini;
        if ((int) substr($hf, 3, 2) > 0 || $h_fin === $h_ini) $h_fin++; // redondea a la siguiente hora
        if ($min_hora === null || $h_ini < $min_hora) $min_hora = $h_ini;
        if ($max_hora === null || $h_fin > $max_hora) $max_hora = $h_fin;
    }
    if ($min_hora === null) { $min_hora = 8; $max_hora = 9; } // sin datos: placeholder

    // Alineo el inicio a un múltiplo del ancho de franja y extiendo el fin
    $min_hora = intdiv($min_hora, $ancho) * $ancho;
    $max_hora = intdiv($max_hora - $min_hora - 1, $ancho) * $ancho + $ancho + $min_hora;
    $n_frajas = intdiv($max_hora - $min_hora, $ancho);

    // 2) Distribuir cada partido en su celda [franja][día]
    $celdas = array();
    foreach ($datos as $fila) {
        $fecha = substr((string) ($fila['fecha_competencia'] ?? ''), 0, 10);
        if ($fecha === '') continue;

        $h_ini = (int) substr((string) ($fila['hora_inicio'] ?? '0'), 0, 2);
        $idx = intdiv(max(0, $h_ini - $min_hora), $ancho);
        if ($idx >= $n_frajas) $idx = $n_frajas - 1;

        $celdas[$idx][$fecha][] = $fila;
    }

    // 3) Con muchos días conviene imprimir el cuadro en A4 horizontal
    $orientacion = $n_dias > 3 ? 'L' : 'P';

    // 4) Agrupar los días en bloques para que cada columna tenga ~45 mm de ancho.
    //    En horizontal el área útil es 297mm - márgenes (12+12) = 273mm.
    $ancho_util = ($orientacion === 'L' ? 297 : 210) - 24;
    $por_bloque = max(1, min(max($n_dias, 1), (int) ($ancho_util / 45)));
    $bloques = array();
    for ($b = 0; $b < $n_dias; $b += $por_bloque) {
        $bloques[] = array_slice($dias, $b, $por_bloque);
    }
    if (!$bloques) $bloques = array(array());

    $dias_abr = array('Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb');

    $html_top = '<div class="titulo">Fixture de Competencia — Cuadro General</div>'
        . '<div class="info">' . $esc($rango_txt) . ' &nbsp;·&nbsp; ' . count($datos) . ' partido(s)'
        . ' &nbsp;·&nbsp; Franjas de ' . $ancho . ' h</div><br>';

    foreach ($bloques as $bi => $bloque) {
        $pdf->AddPage($orientacion, 'A4');

        $html = $html_top;
        if ($n_dias > $por_bloque) {
            $html .= '<div class="info">Bloque ' . ($bi + 1) . ': días ' . ($bi * $por_bloque + 1) . '–'
                . ($bi * $por_bloque + count($bloque)) . ' de ' . $n_dias . '</div>';
        }

        if (!$bloque) {
            $html .= '<p style="text-align:center; font-size:10pt;">No hay partidos cargados en el fixture.</p>';
            $pdf->writeHTML($css . $html, true, false, true, false, '');
            continue;
        }

        $w_horario = 100 / (count($bloque) + 1);
        $w_col = $w_horario;

        $html .= '<table width="100%" cellpadding="2" cellspacing="0"><thead><tr>'
            . '<th width="' . $w_horario . '%">Horario</th>';
        foreach ($bloque as $f) {
            $ts = strtotime($f);
            $html .= '<th width="' . $w_col . '%">' . $dias_abr[(int) date('w', $ts)] . ' ' . date('d/m', $ts) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        for ($i = 0; $i < $n_frajas; $i++) {
            $h_ini = $min_hora + $i * $ancho;
            $h_fin = $h_ini + $ancho;
            $html .= '<tr><td class="franja">' . sprintf('%02d:00–%02d:00', $h_ini % 24, $h_fin % 24) . '</td>';
            foreach ($bloque as $f) {
                $partidos = isset($celdas[$i][$f]) ? $celdas[$i][$f] : array();
                $cel = '';
                foreach ($partidos as $p) {
                    $dc = trim(($p['nombre_deporte'] ?? '') . ' ' . ($p['nombre_categoria'] ?? ''));
                    $cel .= '<div><b>' . $esc($horario($p)) . '</b> · <b>' . $esc($dc) . '</b></div>'
                        . '<div>' . $esc($equipos($p)) . '</div>'
                        . (!empty($p['nombre_prueba']) ? '<div class="sub">' . $esc($p['nombre_prueba']) . '</div>' : '')
                        . (!empty($p['lugar_nombre']) ? '<div class="lugar">@ ' . $esc($p['lugar_nombre']) . '</div>' : '');
                }
                $html .= '<td>' . ($cel !== '' ? $cel : '&nbsp;') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($css . $html, true, false, true, false, '');
    }
}

/* ============================================================
 *  Salida: fuerza la descarga del PDF
 * ============================================================ */
$pdf->Output($nombre_archivo, 'D');

//============================================================+
// END OF FILE
//============================================================+

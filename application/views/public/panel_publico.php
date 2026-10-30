<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/* ==========================================================================
 * PANEL PÚBLICO — rediseñado según la entrega del diseñador
 * (Entrega-Fixture-Olimpiadas: maqueta.html + maqueta.css + theme-original.css).
 *
 * Se conserva TODA la carga de datos del controlador (Publico::index):
 * resumen, delegaciones, deportes con categorías, fixture, resultados y
 * medallero. Los filtros siguen siendo GET con recarga del servidor
 * (delegacion / deporte / categoria) y se agregan dia y q como parámetros
 * opcionales de la vista, tal como indica GUIA-DE-INTEGRACION.md.
 *
 * El CSS vive en assets/css/panel-publico-theme.css (tema oscuro institucional).
 * ========================================================================== */

$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };

/* Normalizadores de texto (búsqueda sin tildes ni mayúsculas). */
$norm = function ($s) {
    $s = trim((string) $s);
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) $s = $t;
    }
    $s = preg_replace('/[^A-Za-z0-9]+/', ' ', $s);
    return trim(strtolower((string) $s));
};
/* Lowercase "bonito" para sedes/equipos: primera letra de cada palabra en mayúscula. */
$capwords = function ($s) {
    $s = trim((string) $s);
    if ($s === '') return $s;
    $pequenas = array('de', 'del', 'la', 'el', 'los', 'las', 'y');
    $partes = preg_split('/(\s+)/u', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    $out = ''; $i = 0;
    foreach ($partes as $p) {
        if (trim($p) === '') { $out .= $p; continue; }
        $low = mb_strtolower($p, 'UTF-8');
        $out .= ($i > 0 && in_array($low, $pequenas, true))
              ? $low
              : mb_convert_case($p, MB_CASE_TITLE, 'UTF-8');
        $i++;
    }
    return $out;
};

$id_dia_get = trim((string) $this->input->get('dia'));
$q          = trim((string) $this->input->get('q'));

/* ---- Fases legibles (mismo criterio que pase_valido.php) ---- */
$fases_txt = array(
    'GRUPO' => 'Fase de grupos', '16AVOS' => '16avos. de final',
    'OCTAVOS' => 'Octavos de final', 'CUARTOS' => 'Cuartos de final',
    'SEMIFINAL' => 'Semifinal', 'TERCER_PUESTO' => 'Por el 3er puesto',
    'FINAL' => 'Final', 'JORNADA_UNICA' => 'Jornada',
);
$metodos_txt = array(
    'PENALES' => 'los penales', 'PRORROGA' => 'la prórroga',
    'PUNTOS_DE_ORO' => 'puntos de oro', 'MUERTE_SUBITA' => 'la muerte súbita',
    'LANZAMIENTO_TIRLIBRE' => 'el lanzamiento tirlibre', 'OTRO' => 'el desempate',
);

/* ============================================================
 * 1) PREPARAR EL FIXTURE: por cada fila se calculan etiqueta de día,
 *    hora hh:mm, sede, estado, chip de fase, si es masiva y el HTML de
 *    la tarjeta (idéntico al de la maqueta: .partido/.meta/.vs/...).
 * ============================================================ */

/* "TRAMPITA DE LAS JORNADAS" (espejo de fixture.php / reporte_fixture.php):
   numero_fecha se usa como divisor de grupos — Jornada 1 → Grupo A, ... */
$letra_grupo = function ($n) {
    static $abecedario = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
                               'N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    if ($n >= 1 && $n <= count($abecedario)) return $abecedario[$n - 1];
    return chr(64 + intdiv($n - 1, 26)) . '-' . $abecedario[($n - 1) % 26];
};
$grupos_idx = array(); // clave "categoria|numero_fecha" → ['hay_grupo','todas_grupo']
foreach ($fixture as $fxx) {
    $num_f = (int) (isset($fxx['numero_fecha']) ? $fxx['numero_fecha'] : 0);
    $clave = (int) (isset($fxx['id_categoria']) ? $fxx['id_categoria'] : 0) . '|' . $num_f;
    if (!isset($grupos_idx[$clave])) {
        $grupos_idx[$clave] = array('hay_grupo' => FALSE, 'todas_grupo' => TRUE);
    }
    if (strtoupper(trim((string) (isset($fxx['fase']) ? $fxx['fase'] : ''))) === 'GRUPO') {
        $grupos_idx[$clave]['hay_grupo'] = TRUE;
    } else {
        $grupos_idx[$clave]['todas_grupo'] = FALSE;
    }
}
$grupo_etiqueta = array(); // clave "categoria|numero_fecha" → 'Grupo A' | null
foreach ($grupos_idx as $clave => $g) {
    $num_f = (int) substr($clave, strpos($clave, '|') + 1);
    $grupo_etiqueta[$clave] = ($g['todas_grupo'] && $g['hay_grupo'] && $num_f > 0)
        ? 'Grupo ' . $letra_grupo($num_f)
        : null;
}

$dias_es = array('Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles',
                 'Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado');

$items = array();      // filas preparadas del fixture
$order_days = array(); // días únicos en orden de aparición

foreach ($fixture as $fx) {
    $dia_raw = (string) $fx['fecha_competencia'];
    $ts = strtotime($dia_raw);
    $day_id    = $ts ? date('d/m', $ts) : $dia_raw;
    $day_title = $ts ? ($dias_es[date('l', $ts)] . ' ' . date('d/m', $ts)) : $dia_raw;
    if (!isset($order_days[$day_id])) $order_days[$day_id] = $day_title;

    /* Estado del marcador */
    $mar = isset($fx['marcador']) ? $fx['marcador'] : null;
    $eq1_raw = (isset($fx['ute_1_nombre']) && trim((string) $fx['ute_1_nombre']) !== '') ? (string) $fx['ute_1_nombre'] : '';
    $eq2_raw = (isset($fx['ute_2_nombre']) && trim((string) $fx['ute_2_nombre']) !== '') ? (string) $fx['ute_2_nombre'] : '';
    $eq1_nombre = $e($eq1_raw);
    $eq2_nombre = $e($eq2_raw);
    if ($eq1_nombre === '') $eq1_nombre = '<span class="pendiente">Por definir</span>';
    if ($eq2_nombre === '') $eq2_nombre = '<span class="pendiente">Por definir</span>';
    $marcador_txt = '';
    $g1 = false; $g2 = false;
    if (is_array($mar) && isset($mar['equipos']) && count($mar['equipos']) >= 2) {
        $a = $mar['equipos'][0]; $b = $mar['equipos'][1];
        $af = (int) (isset($a['a_favor']) ? $a['a_favor'] : 0);
        $ac = (int) (isset($a['en_contra']) ? $a['en_contra'] : 0);
        $bf = (int) (isset($b['a_favor']) ? $b['a_favor'] : 0);
        $bc = (int) (isset($b['en_contra']) ? $b['en_contra'] : 0);
        $marcador_txt = $af . ' – ' . $bc;
        if ($af > $bc) $g1 = true;
        elseif ($bc > $af) $g2 = true;
        if (!empty($mar['hubo_desempate'])) { $g1 = false; $g2 = false; }
    } elseif ((string) (isset($fx['estado']) ? $fx['estado'] : '') === 'FINALIZADO' && !empty($fx['resultado'])) {
        $marcador_txt = 'Final';
    }

    /* Deportes MASIVO_TIEMPO / jornadas JORNADA_UNICA: todos contra todos. */
    $es_masivo = ((string) (isset($fx['fase']) ? $fx['fase'] : '') === 'JORNADA_UNICA')
              || ((string) (isset($fx['modalidad_competencia']) ? $fx['modalidad_competencia'] : '') === 'MASIVO_TIEMPO');

    /* Hora de comienzo hh:mm */
    $hora_raw = isset($fx['hora_inicio']) ? trim((string) $fx['hora_inicio']) : '';
    $hora_ts = $hora_raw !== '' ? strtotime('today ' . $hora_raw) : false;
    $hora_txt = $hora_ts !== false ? date('H:i', $hora_ts) : substr($hora_raw, 0, 5);

    /* Chip de fase: "Grupo A" o "Eliminatoria" */
    $clave_fx = (int) (isset($fx['id_categoria']) ? $fx['id_categoria'] : 0) . '|'
              . (int) (isset($fx['numero_fecha']) ? $fx['numero_fecha'] : 0);
    $grupo_txt = isset($grupo_etiqueta[$clave_fx]) ? $grupo_etiqueta[$clave_fx] : null;
    $fase_raw = strtoupper(trim((string) (isset($fx['fase']) ? $fx['fase'] : '')));
    if ($grupo_txt !== null) {
        $chip_fase = 'Grupo ' . substr($grupo_txt, 6);
        $chip_elim = false;
    } elseif ($fase_raw !== '' && $fase_raw !== 'JORNADA_UNICA') {
        $chip_fase = 'Eliminatoria';
        $chip_elim = true;
    } else {
        $chip_fase = null;
        $chip_elim = false;
    }

    $deporte_txt = trim((string) (isset($fx['nombre_deporte']) ? $fx['nombre_deporte'] : ''));
    $cat_txt     = trim((string) (isset($fx['nombre_categoria']) ? $fx['nombre_categoria'] : ''));
    $prueba_txt  = trim((string) (isset($fx['nombre_prueba']) ? $fx['nombre_prueba'] : ''));
    $venue_txt   = $capwords(isset($fx['lugar_nombre']) ? $fx['lugar_nombre'] : '');
    $estado_txt  = (string) $fx['estado'];

    /* Tarjeta .partido — misma estructura de clases que reconoce la maqueta */
    ob_start(); ?>
      <div class="partido<?= $es_masivo ? ' masivo' : '' ?>">
        <?php if ($chip_fase !== null): ?>
        <div class="fase-header<?= $chip_elim ? ' elim' : '' ?>"><?= $e($chip_fase) ?></div>
        <?php endif; ?>
        <div class="meta">
          <span><span class="hora-inicio"><?= $e($hora_txt) ?></span><?php if ($venue_txt !== ''): ?><span class="venue-name"><?= $e($venue_txt) ?></span><?php endif; ?></span>
          <span class="badge <?= $e($estado_txt) ?>"><?= $e($estado_txt) ?></span>
        </div>
        <?php if ($es_masivo): ?>
          <div class="masivo-aviso"></div>
        <?php else: ?>
        <div class="vs">
          <div class="equipo <?= $g1 ? 'ganador' : '' ?>"><?= $eq1_nombre ?></div>
          <div class="marcador"><?= $marcador_txt !== '' ? $e($marcador_txt) : '<span class="vsx">VS</span>' ?></div>
          <div class="equipo <?= $g2 ? 'ganador' : '' ?>"><?= $eq2_nombre ?></div>
        </div>
        <?php endif; ?>
        <div class="categoria-linea">
          <span class="deporte-chip"><?= $e($deporte_txt) ?></span>
          <?php if ($cat_txt !== ''): ?><span style="opacity:.75"><?= $e($cat_txt) ?></span><?php endif; ?>
          <?php if ($prueba_txt !== ''): ?> · <?= $e($prueba_txt) ?><?php endif; ?>
        </div>
      </div>
    <?php
    $card_html = ob_get_clean();

    /* Texto indexado para la búsqueda (equipo, sede, categoría, deporte...) */
    $search_text = $norm(implode(' ', array(
        strip_tags(str_replace('<', ' <', (string) $eq1_nombre)),
        strip_tags(str_replace('<', ' <', (string) $eq2_nombre)),
        $deporte_txt, $cat_txt, $prueba_txt, $venue_txt, $estado_txt, $day_title
    )));

    $items[] = array(
        'dayId' => $day_id, 'dayTitle' => $order_days[$day_id],
        'sport' => $deporte_txt !== '' ? $deporte_txt : 'Competencia',
        'category' => $cat_txt, 'hour' => $hora_txt, 'venue' => $venue_txt,
        'status' => $estado_txt, 'mass' => $es_masivo,
        'html' => $card_html, 'text' => $search_text,
    );
}

/* ============================================================
 * 2) FILTROS CLIENTE: día seleccionado + búsqueda (sin tildes/mayúsculas).
 *    Los filtros de delegación/deporte/categoría ya vinieron aplicados
 *    desde el servidor (consulta del controlador).
 * ============================================================ */
$q_norm = $norm($q);
$matches = array();
foreach ($items as $it) {
    if ($id_dia_get !== '' && $id_dia_get !== 'all' && $it['dayId'] !== $id_dia_get) continue;
    if ($q_norm !== '' && strpos($it['text'], $q_norm) === false) continue;
    $matches[] = $it;
}
$has_filter_ui = ($id_dia_get !== '' && $id_dia_get !== 'all') || $q !== '';

/* Días presentes en los datos filtrados (para day-nav) */
$days_present = array();
foreach ($items as $it) if (!isset($days_present[$it['dayId']])) $days_present[$it['dayId']] = $it['dayTitle'];

$disc_total = count(array_unique(array_column($matches, 'sport')));
$comp_total = count($matches);
$scope_txt  = ($id_dia_get === 'all') ? 'Todos los días'
            : (($id_dia_get !== '' && isset($days_present[$id_dia_get])) ? $days_present[$id_dia_get]
            : (count($days_present) === 1 ? reset($days_present) : 'Todos los días'));

/* ============================================================
 * 3) RENDER DEL FIXTURE: día → disciplinas desplegables (<details>) →
 *    tarjetas; las masivas se agrupan por horario+sede+estado.
 * ============================================================ */
$render_mass_card = function ($group) {
    $first = $group[0];
    $n = count($group);
    echo '<article class="mass-group">';
    echo '<div class="mass-top">';
    echo '<div class="mass-time"><strong>' . $e($first['hour'] !== '' ? $first['hour'] : '—') . '</strong><span>HORARIO</span></div>';
    echo '<div class="mass-title"><h4>' . $e($first['sport']) . '</h4>';
    echo '<p>' . $n . ' ' . ($n === 1 ? 'categoría' : 'categorías') . '</p>';
    if ($first['venue'] !== '') {
        echo '<p class="venue"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg><span>' . $e($first['venue']) . '</span></p>';
    }
    echo '</div></div>';
    echo '<span class="badge ' . $e($first['status']) . '">' . $e($first['status']) . '</span>';
    echo '<details><summary>Categorías (' . $n . ')</summary><div class="category-rows">';
    foreach ($group as $it) echo $it['html'];
    echo '</div></details></article>';
};

$pin_svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>';
$flag_svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20V5m0 0c5-4 9 4 14 0v9c-5 4-9-4-14 0"/></svg>';
$arrow_svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0d1b2a">
<meta name="description" content="Panel público de las <?= $e(NOMBRE_SITIO) ?>. La Pampa 2026: agenda de competencias, resultados y medallero.">
<title><?= $e($titulo_evento) ?> — Agenda y resultados</title>
<link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/panel-publico-theme.css') ?>">
</head>
<body>
<a class="skip" href="#contenido">Ir al contenido</a>

<header class="masthead">
  <div class="container masthead-inner">
    <img class="official-logo" src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" alt="<?= $e(NOMBRE_SITIO) ?> · La Pampa 2026" width="220" height="130">
    <div class="masthead-copy">
      <p class="eyebrow">LA PAMPA 2026 <span aria-hidden="true">/</span> PANEL PÚBLICO</p>
      <h1>Agenda y resultados</h1>
      <p>Toda la competencia, en un solo lugar.</p>
    </div>
    <div class="event-mark">
      <span class="event-mark-label"><?= $e(EDICION) ?> EDICIÓN</span>
      <span>Nos une el deporte.</span>
      <div class="color-stripes" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    </div>
  </div>
  <div class="masthead-location container">
    <strong>📍 <?= $e($lugar_olimpico) ?></strong>
    <?php if (!empty($delegacion)): ?>
      · Viendo: <span class="chip-delegacion"><?= $e($delegacion) ?></span>
    <?php else: ?>
      · Actualizado <?= $e($ahora) ?>
    <?php endif; ?>
  </div>
</header>

<main id="contenido" class="container">

  <button type="button" class="filters-toggle" id="toggle-filters" aria-expanded="false" aria-controls="filters-card">
    <svg class="panel-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16M7 12h10m-7 5h4"/></svg>
    Filtros
  </button>

  <!-- ============ FILTROS ============ -->
  <section class="filters-card" id="filters-card" aria-labelledby="filters-title">
    <div class="filters-heading">
      <div><h2 id="filters-title">Encontrá tu competencia</h2><p>Elegí una delegación, un deporte o una categoría.</p></div>
      <?php if (!empty($delegacion) || !empty($id_deporte) || !empty($id_categoria) || $id_dia_get !== '' || $q !== ''): ?>
      <a class="reset-button" href="<?= site_url('publico') ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10a8 8 0 1 1 1.8 7.1M4 4v6h6"/></svg>Limpiar filtros
      </a>
      <?php endif; ?>
    </div>
    <form id="filters-form" class="filters-form" method="get" action="<?= site_url('publico') ?>">
      <?php /* Conservar día/búsqueda al aplicar filtros del servidor */ ?>
      <?php if ($id_dia_get !== ''): ?><input type="hidden" name="dia" value="<?= $e($id_dia_get) ?>"><?php endif; ?>
      <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= $e($q) ?>"><?php endif; ?>
      <div class="filter-field">
        <label for="filter-delegacion">Delegación</label>
        <select id="filter-delegacion" name="delegacion" onchange="this.form.submit()">
          <option value="">Todas las delegaciones</option>
          <?php foreach ($delegaciones as $d): ?>
            <option value="<?= $e($d) ?>" <?= ($delegacion === $d) ? 'selected' : '' ?>><?= $e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-field">
        <label for="filtroDeporte">Deporte</label>
        <select id="filtroDeporte" name="deporte">
          <option value="">Todos los deportes</option>
          <?php foreach ($deportes as $dep): ?>
            <option value="<?= (int)$dep['id_deporte'] ?>" <?= ((int)$id_deporte === (int)$dep['id_deporte']) ? 'selected' : '' ?>>
              <?= $e($dep['nombre_deporte']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-field">
        <label for="filtroCategoria">Categoría</label>
        <select id="filtroCategoria" name="categoria" onchange="this.form.submit()">
          <option value="">Todas las categorías</option>
          <?php foreach ($deportes as $dep): ?>
            <?php foreach (isset($dep['categorias']) ? $dep['categorias'] : array() as $cat): ?>
              <?php if (empty($cat['id_categoria'])) continue; ?>
              <option value="<?= (int)$cat['id_categoria'] ?>"
                      data-deporte="<?= (int)$dep['id_deporte'] ?>"
                      <?= ((int)$id_categoria === (int)$cat['id_categoria']) ? 'selected' : '' ?>>
                <?= empty($id_deporte) ? $e($dep['nombre_deporte']) . ' - ' : '' ?><?= $e($cat['nombre_categoria']) ?>
              </option>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </section>

  <!-- ============ RESUMEN ============ -->
  <section class="stats" aria-label="Resumen general del evento">
    <div class="stat"><b><?= (int)$resumen['delegaciones'] ?></b><span>Delegaciones</span></div>
    <div class="stat"><b><?= (int)$resumen['deportes'] ?></b><span>Deportes</span></div>
    <div class="stat"><b><?= (int)$resumen['categorias'] ?></b><span>Categorías</span></div>
    <div class="stat"><b><?= (int)$resumen['finalizados'] ?><small> / <?= (int)$resumen['partidos'] ?></small></b><span>Partidos jugados</span></div>
    <div class="stat"><b><?= (int)$resumen['resultados'] ?></b><span>Resultados</span></div>
    <div class="stat"><b><?= (int)$resumen['premiados'] ?></b><span>Medallas entregadas</span></div>
  </section>

  <div class="workspace">
    <!-- ============ PESTAÑAS ============ -->
    <nav class="primary-tabs" role="tablist" aria-label="Información de la competencia">
      <button role="tab" id="button-fixture" aria-controls="tab-fixture" aria-selected="true" data-tab="tab-fixture" tabindex="0">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 3v4m8-4v4M4 11h16M8 15h2m4 0h2"/></svg>Fixture</button>
      <button role="tab" id="button-resultados" aria-controls="tab-resultados" aria-selected="false" data-tab="tab-resultados" tabindex="-1">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4m0 0c5-5 9 5 14 0v10c-5 5-9-5-14 0"/></svg>Resultados</button>
      <button role="tab" id="button-medallero" aria-controls="tab-medallero" aria-selected="false" data-tab="tab-medallero" tabindex="-1">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 3 4 6 4-6M5 3h3m8 0h3"/><circle cx="12" cy="15" r="7"/><path d="m12 11 1.2 2.5 2.8.4-2 1.9.5 2.7-2.5-1.3-2.5 1.3.5-2.7-2-1.9 2.8-.4Z"/></svg>Medallero</button>
    </nav>

    <!-- ============ FIXTURE ============ -->
    <section class="panel-tab" id="tab-fixture" role="tabpanel" aria-labelledby="button-fixture" tabindex="0">
      <div class="section-heading">
        <div><p class="eyebrow">LA AGENDA</p><h2>Fixture de competencias</h2>
          <p id="fixture-description"><?= !empty($delegacion) ? 'Agenda de ' . $e($delegacion) . '. Abrí una disciplina para ver sus competencias.' : 'Abrí una disciplina para ver horarios, categorías y encuentros.' ?></p></div>
      </div>

      <div class="schedule-tools">
        <?php /* Cada día es un <a> con su URL real (?dia=...): funciona incluso
                  si el JavaScript no carga o falla (degradación progresiva). */ ?>
        <nav id="day-nav" class="day-nav" aria-label="Filtrar por día">
          <?php foreach ($days_present as $did => $dtitle):
              $dp = explode(' ', $dtitle, 2);
              $day_qs = array();
              if (!empty($delegacion)) $day_qs['delegacion'] = $delegacion;
              if (!empty($id_deporte)) $day_qs['deporte'] = (int) $id_deporte;
              if (!empty($id_categoria)) $day_qs['categoria'] = (int) $id_categoria;
              if ($q !== '') $day_qs['q'] = $q;
              $day_qs['dia'] = $did;
              $day_href = site_url('publico') . '?' . http_build_query($day_qs); ?>
            <a type="button" class="day-button" href="<?= $e($day_href) ?>" data-day="<?= $e($did) ?>"
               aria-pressed="<?= ($id_dia_get === $did) ? 'true' : 'false' ?>" aria-label="<?= $e($dtitle) ?>">
              <span><?= $e(substr($dp[0], 0, 3)) ?></span><strong><?= $e(isset($dp[1]) ? $dp[1] : $did) ?></strong>
            </a>
          <?php endforeach; ?>
          <?php if (count($days_present) > 1):
              unset($day_qs['dia']);
              $all_href = site_url('publico') . (count($day_qs) ? '?' . http_build_query($day_qs) : ''); ?>
            <a class="day-button all-days" href="<?= $e($all_href) ?>" data-day="all"
               aria-pressed="<?= ($id_dia_get === 'all' || $id_dia_get === '') ? 'true' : 'false' ?>">Todos<br>los días</a>
          <?php endif; ?>
        </nav>
        <label class="search-box">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
          <input id="search" type="search" placeholder="Buscar equipo o sede" aria-label="Buscar equipo, sede o categoría"
                 value="<?= $e($q) ?>" autocomplete="off">
        </label>
      </div>

      <div class="fixture-meta">
        <p id="fixture-count" aria-live="polite" role="status">
          <?= $e($scope_txt) ?> · <?= $disc_total ?> <?= $disc_total === 1 ? 'disciplina' : 'disciplinas' ?> ·
          <?= $comp_total ?> <?= $comp_total === 1 ? 'competencia' : 'competencias' ?>
        </p>
        <button id="expand-disciplines" type="button">Desplegar disciplinas <span aria-hidden="true">↓</span></button>
      </div>

      <div id="schedule">
        <?php if (empty($matches)): ?>
          <div class="empty-state" id="search-empty-view">
            <div class="empty-icon" aria-hidden="true">⌕</div>
            <h3>No encontramos competencias</h3>
            <p><?= empty($fixture) ? 'Todavía no hay partidos programados' . ($delegacion ? ' para esta delegación' : '') . (!empty($id_categoria) ? ' en esta categoría' : '') . '.' : 'Probá con otra búsqueda o cambiá los filtros.' ?></p>
            <a class="secondary-button" href="<?= site_url('publico') ?>">Ver todas las competencias</a>
          </div>
        <?php else:
          /* Agrupar: día → deporte → items (en el orden ya traído del servidor) */
          $by_day = array();
          foreach ($matches as $it) $by_day[$it['dayId']][$it['sport']][] = $it;
          $open_first = !$has_filter_ui; /* disciplinas cerradas al entrar (salvo buscando/filtrando) */
          foreach ($by_day as $did => $sports): ?>
            <section class="day-group" aria-label="<?= $e($order_days[$did]) ?>">
              <h3 class="day-heading"><?= $e($order_days[$did]) ?></h3>
              <div class="discipline-list">
                <?php foreach ($sports as $sport_name => $sitems):
                    /* horarios y sedes de la disciplina */
                    $horas = array_values(array_unique(array_filter(array_column($sitems, 'hour'))));
                    sort($horas);
                    $time_range = count($horas) > 1 ? $horas[0] . '–' . $horas[count($horas)-1]
                              : (count($horas) === 1 ? $horas[0] : 'Horario a confirmar');
                    $venues = array_values(array_unique(array_filter(array_column($sitems, 'venue'))));
                    $loc = count($venues) === 1 ? $venues[0] : (count($venues) > 1 ? count($venues) . ' sedes' : 'Sede a confirmar');
                    $n = count($sitems);
                    $auto_open = $open_first || $q !== '' || !empty($id_deporte) || !empty($id_categoria);
                ?>
                <details class="discipline-group" data-discipline="<?= $e($sport_name) ?>"<?= $auto_open ? ' open' : '' ?>>
                  <summary class="discipline-summary">
                    <span class="discipline-symbol" aria-hidden="true"><?= $flag_svg ?></span>
                    <span class="discipline-label">
                      <span class="discipline-name"><?= $e($sport_name) ?></span>
                      <span class="discipline-description"><?= $e($time_range) ?> · <?= $e($loc) ?></span>
                    </span>
                    <span class="discipline-count"><?= $n ?> <?= $n === 1 ? 'competencia' : 'competencias' ?></span>
                    <span class="discipline-arrow" aria-hidden="true"><?= $arrow_svg ?></span>
                  </summary>
                  <div class="discipline-body">
                    <?php
                    /* ¿Todo masivo? usar mass-cards */
                    $all_mass = !array_filter(array_column($sitems, 'mass'));
                    echo '<div class="cards' . ($all_mass ? ' mass-cards' : '') . '">';
                    $massGroups = array(); $massOrder = array();
                    foreach ($sitems as $it) {
                        if ($it['mass']) {
                            $mk = $it['hour'] . '|' . $it['venue'] . '|' . $it['status'];
                            if (!isset($massGroups[$mk])) { $massGroups[$mk] = array(); $massOrder[] = $mk; }
                            $massGroups[$mk][] = $it;
                        } else {
                            echo $it['html'];
                        }
                    }
                    foreach ($massOrder as $mk) $render_mass_card($massGroups[$mk]);
                    echo '</div>';
                    ?>
                  </div>
                </details>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- ============ RESULTADOS ============ -->
    <section class="panel-tab" id="tab-resultados" role="tabpanel" aria-labelledby="button-resultados" tabindex="0" hidden>
      <div class="section-heading">
        <div><p class="eyebrow">CADA ENCUENTRO CUENTA</p><h2>Resultados</h2>
          <p>Marcadores y posiciones por competencia.</p></div>
      </div>
      <div id="results-content">
      <?php if (empty($resultados)): ?>
        <div class="empty-state">
          <div class="empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M5 21V4m0 0c5-5 9 5 14 0v10c-5 5-9-5-14 0"/></svg>
          </div>
          <h3>Todavía no hay resultados</h3>
          <p>Aún no se cargaron resultados<?= $delegacion ? ' para esta delegación' : '' ?><?= !empty($id_categoria) ? ' en esta categoría' : '' ?>. Podés recorrer el fixture de competencias.</p>
        </div>
      <?php else: foreach ($resultados as $res):
          /* Encabezado: DEPORTE destacado y CATEGORÍA abajo (nunca el nombre interno del partido). */
          $dep_txt  = trim((string) (isset($res['nombre_deporte']) ? $res['nombre_deporte'] : ''));
          $cat_txt  = trim((string) (isset($res['nombre_categoria']) ? $res['nombre_categoria'] : ''));
          if ($dep_txt === '' && $cat_txt === '') $dep_txt = trim((string) (isset($res['nombre_evento']) ? $res['nombre_evento'] : ''));
          // Fecha/hora: se prefiere cuándo se JUGÓ (fixture), no cuándo se cargó.
          $fecha_ts = strtotime(!empty($res['fixture_fecha']) ? $res['fixture_fecha'] : (string) $res['fecha_resultado']);
          $fecha_txt = $fecha_ts ? date('d/m/Y', $fecha_ts) : trim((string) $res['fecha_resultado']);
          $hora_ts   = !empty($res['fixture_hora']) ? strtotime('today ' . trim((string) $res['fixture_hora'])) : false;
          $hora_txt  = $hora_ts !== false ? date('H:i', $hora_ts) : '';
          $fase_raw  = isset($res['fase']) ? (string) $res['fase'] : '';
          $fase_txt  = $fase_raw !== '' ? (isset($fases_txt[$fase_raw]) ? $fases_txt[$fase_raw] : $fase_raw) : '';
      ?>
        <div class="resultado-card">
          <div class="res-header">
            <?php if ($dep_txt !== ''): ?><span class="deporte-chip"><?= $e($dep_txt) ?></span><?php endif; ?>
            <?php if ($cat_txt !== ''): ?><span class="res-categoria"><?= $e($cat_txt) ?></span><?php endif; ?>
          </div>
          <div class="sub">
            <?= $fase_txt !== '' ? $e($fase_txt) . ' · ' : '' ?><?= $e($fecha_txt) ?><?= $hora_txt !== '' ? ' · 🕐 ' . $e($hora_txt) : '' ?><?= !empty($res['lugar']) ? ' · ' . $e((string) $res['lugar']) : '' ?><?= !empty($res['nombre_prueba']) ? ' · ' . $e((string) $res['nombre_prueba']) : '' ?>
          </div>
          <?php if ((string) $res['tipo_resultado'] === 'MARCADOR' && count($res['detalle']) >= 2):
              /* Enfrentamiento: cada equipo con SU puntaje, no "54:58". */
              $a = $res['detalle'][0]; $b = $res['detalle'][1];
              $eq1_nombre = trim((string) $a['nombre_libre']) !== '' ? $a['nombre_libre'] : 'Equipo 1';
              $eq2_nombre = trim((string) $b['nombre_libre']) !== '' ? $b['nombre_libre'] : 'Equipo 2';
              /* Cada fila guarda el marcador DESDE SU PROPIA perspectiva (criterio
                 idéntico a pase_valido.php). */
              $p1 = (int) $a['marcador_local'];
              $r1 = (int) $a['marcador_visita'];
              $p2 = (int) $b['marcador_local'];
              $r2 = (int) $b['marcador_visita'];
              if ($p2 === $p1 && $r2 === $r1) {
                  /* Datos históricos "espejados": normalizar invirtiendo la 2ª fila. */
                  $equipos_distintos = trim((string) $a['nombre_libre']) !== trim((string) $b['nombre_libre'])
                      || ((isset($a['id_ute']) ? (int) $a['id_ute'] : 0) > 0
                          && (isset($b['id_ute']) ? (int) $b['id_ute'] : 0) > 0
                          && (int) $a['id_ute'] !== (int) $b['id_ute']);
                  if ($equipos_distintos) { $tmp = $p2; $p2 = $r2; $r2 = $tmp; }
              }
              $hubo_desempate = !empty($res['hubo_desempate']) && !empty($res['desempate_metodo']);
              $ganador_desemp = isset($res['nombre_ganador_desempate']) ? trim((string) $res['nombre_ganador_desempate']) : '';
              $gana1 = $p1 > $p2 || ($p1 === $p2 && $hubo_desempate && $ganador_desemp !== ''
                        && strtoupper(trim($ganador_desemp)) === strtoupper(trim((string) $eq1_nombre)));
              $gana2 = $p2 > $p1 || ($p1 === $p2 && $hubo_desempate && $ganador_desemp !== ''
                        && strtoupper(trim($ganador_desemp)) === strtoupper(trim((string) $eq2_nombre)));
          ?>
            <div class="vs-res">
              <div class="lado">
                <span class="nombre <?= $gana1 ? 'ganador' : '' ?>"><?= $e($eq1_nombre) ?></span>
                <span class="tato"><?= $p1 ?></span>
              </div>
              <div class="lado derecho">
                <span class="tato"><?= $p2 ?></span>
                <span class="nombre <?= $gana2 ? 'ganador' : '' ?>"><?= $e($eq2_nombre) ?></span>
              </div>
            </div>
            <?php if ($gana1): ?>
              <div class="ganador-linea">🏆 Ganó <?= $e($eq1_nombre) ?></div>
            <?php elseif ($gana2): ?>
              <div class="ganador-linea">🏆 Ganó <?= $e($eq2_nombre) ?></div>
            <?php elseif ($p1 === $p2 && $hubo_desempate): ?>
              <div class="ganador-linea">🏅 Empate en tiempo regular — <?= $e($ganador_desemp !== '' ? $ganador_desemp : 'Definido') ?>
                ganó en <?= $e(isset($metodos_txt[(string) $res['desempate_metodo']]) ? $metodos_txt[(string) $res['desempate_metodo']] : strtolower((string) $res['desempate_metodo'])) ?></div>
            <?php elseif ($p1 === $p2): ?>
              <div class="empate-linea">Empate</div>
            <?php endif; ?>
          <?php elseif ((string) $res['tipo_resultado'] === 'MARCADOR'): ?>
            <?php $a = $res['detalle'][0]; ?>
            <div class="vs-res">
              <div class="lado">
                <span class="nombre"><?= $e(trim((string) $a['nombre_libre']) !== '' ? $a['nombre_libre'] : 'Equipo') ?></span>
                <span class="tato"><?= (int) $a['marcador_local'] ?> : <?= (int) $a['marcador_visita'] ?></span>
              </div>
            </div>
          <?php else: foreach ($res['detalle'] as $d):
              $pos_n = (int)$d['posicion']; ?>
            <div class="fila-pos">
              <span class="pos <?= $pos_n >= 1 && $pos_n <= 3 ? 'p'.$pos_n : '' ?>"><?= $pos_n ?></span>
              <span><?= $e((string) $d['nombre_libre']) ?></span>
              <?php if (!empty($d['tiempo'])): ?><span class="tiempo"><?= $e($d['tiempo']) ?></span><?php endif; ?>
            </div>
          <?php endforeach; endif; ?>
          <?php if (!empty($res['observaciones'])): ?>
            <div class="empate-linea">📝 <?= $e((string) $res['observaciones']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
      </div>
    </section>

    <!-- ============ MEDALLERO ============ -->
    <section class="panel-tab" id="tab-medallero" role="tabpanel" aria-labelledby="button-medallero" tabindex="0" hidden>
      <div class="section-heading">
        <div><p class="eyebrow">EL ESFUERZO TIENE SU LUGAR</p><h2>Medallero</h2>
          <p>Medallas obtenidas por cada delegación.</p></div>
      </div>
      <div id="medals-content">
      <?php $med = $medallero; if ($delegacion): $med = array_values(array_filter($medallero, function($m) use ($delegacion){ return $m['delegacion'] === $delegacion; })); endif; ?>
      <?php if (empty($med)): ?>
        <div class="empty-state">
          <div class="empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="m8 3 4 6 4-6M5 3h3m8 0h3"/><circle cx="12" cy="15" r="7"/><path d="m12 11 1.2 2.5 2.8.4-2 1.9.5 2.7-2.5-1.3-2.5 1.3.5-2.7-2-1.9 2.8-.4Z"/></svg>
          </div>
          <h3>Las medallas están por llegar</h3>
          <p>Aún no se entregaron medallas<?= $delegacion ? ' que correspondan a esta delegación' : '' ?>. ¡Volvé más tarde!</p>
        </div>
      <?php else: ?>
        <table class="medallero">
          <thead><tr><th>Delegación</th><th>🥇 Oro</th><th>🥈 Plata</th><th>🥉 Bronce</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($med as $idx => $m): ?>
            <tr class="<?= $idx === 0 ? 'top1' : '' ?>">
              <td><span class="rank-num"><?= $idx + 1 ?></span><?= $e($m['delegacion']) ?></td>
              <td class="oro"><?= (int)$m['oro'] ?></td>
              <td class="plata"><?= (int)$m['plata'] ?></td>
              <td class="bronce"><?= (int)$m['bronce'] ?></td>
              <td><b><?= (int)$m['total'] ?></b></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (!$delegacion): ?>
          <p style="color:var(--muted);font-size:12px;margin-top:10px;text-align:center">
            Seleccioná una delegación arriba para ver solo su información.
          </p>
        <?php endif; ?>
      <?php endif; ?>
      </div>
    </section>
  </div>
</main>

<footer class="footer">
  <div class="container">
    <div>
      <img class="mascota" src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" alt="Pampito, la mascota de las olimpiadas">
      <strong>La Pampa 2026</strong>
      <p><?= $e(NOMBRE_SITIO) ?></p>
    </div>
    <span>Panel público <span aria-hidden="true">·</span> Sin datos personales</span>
  </div>
</footer>

<script>
(function () {
  'use strict';
  var BASE_URL = '<?= site_url('publico') ?>';
  var INITIAL = {
    delegacion: <?= json_encode((string) ($delegacion ? $delegacion : '')) ?>,
    deporte: <?= json_encode((string) ($id_deporte ? $id_deporte : '')) ?>,
    categoria: <?= json_encode((string) ($id_categoria ? $id_categoria : '')) ?>,
    dia: <?= json_encode($id_dia_get) ?>,
    q: <?= json_encode($q) ?>
  };

  var delegation = document.getElementById('filter-delegacion');
  var sport = document.getElementById('filtroDeporte');
  var category = document.getElementById('filtroCategoria');
  var search = document.getElementById('search');
  var form = document.getElementById('filters-form');
  var dayNav = document.getElementById('day-nav');
  var expandBtn = document.getElementById('expand-disciplines');
  var toggle = document.getElementById('toggle-filters');
  var card = document.getElementById('filters-card');

  /* Helpers ES5 puros: funcionan en cualquier navegador (incluso WebViews muy
     antiguos). Nada de dataset/closest/NodeList.forEach/Map/toggle(2 argos). */
  function all(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }
  function each(list, fn) { for (var i = 0; i < list.length; i++) fn(list[i], i); }
  function on(el, type, fn) { if (el) el.addEventListener(type, fn, false); }
  function closestEl(el, selector) {
    while (el) {
      if (el.nodeType === 1 && typeof el.matches === 'function' && el.matches(selector)) return el;
      el = el.parentElement || el.parentNode;
      if (el && el.nodeType !== 1) el = null;
    }
    return null;
  }
  function selectedOptionText(sel) {
    var opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
    return opt ? (opt.textContent || '').replace(/\s+/g, ' ').trim() : '';
  }
  function addOrRemoveClass(el, name, add) {
    if (!el) return;
    if (add) { el.classList.add(name); } else { el.classList.remove(name); }
  }

  /* Enlaces "Limpiar filtros" / "Ver todas las competencias": conservan la
     pestaña activa (si no es Fixture) para no perder el contexto. */
  each(all('.reset-button, .secondary-button', document.getElementById('contenido')), function (a) {
    var href = a.getAttribute('href') || '';
    if (href.indexOf('?') >= 0 || href.indexOf('#') >= 0) return;
    a.setAttribute('href', href + (activePanel !== 'tab-fixture' ? '#' + activePanel : ''));
  });

  /* ---------- Cascada Deporte → Categoría ---------- */
  var originalCategoryLabels = {};
  if (category) {
    each(category.options, function (o) {
      if (o.value !== '') originalCategoryLabels[o.value] = (o.textContent || '').replace(/\s+/g, ' ').trim();
    });
  }
  function syncCategories() {
    if (!category || !sport) return;
    var selectedSport = selectedOptionText(sport);
    each(category.options, function (option) {
      if (!option.value) return;
      var allowed = !sport.value || option.getAttribute('data-deporte') === sport.value;
      option.hidden = !allowed;
      option.disabled = !allowed;
      var original = originalCategoryLabels[option.value];
      if (typeof original === 'undefined') return;
      var prefix = selectedSport + ' - ';
      option.textContent = (sport.value && original.indexOf(prefix) === 0)
        ? original.slice(prefix.length) : original;
    });
    var cur = category.selectedIndex >= 0 ? category.options[category.selectedIndex] : null;
    if (cur && cur.disabled) category.value = '';
    each([delegation, sport, category], function (sel) {
      if (sel) addOrRemoveClass(sel, 'has-filter', !!sel.value);
    });
  }
  syncCategories();

  /* ---------- Navegación por día y búsqueda: recarga con GET ---------- */
  function buildUrl(dia, q) {
    var p = [];
    if (delegation && delegation.value) p.push('delegacion=' + encodeURIComponent(delegation.value));
    if (sport && sport.value) p.push('deporte=' + encodeURIComponent(sport.value));
    if (category && category.value) p.push('categoria=' + encodeURIComponent(category.value));
    if (dia) p.push('dia=' + encodeURIComponent(dia));
    if (q) p.push('q=' + encodeURIComponent(q));
    var url = BASE_URL + (p.length ? '?' + p.join('&') : '');
    if (activePanel !== 'tab-fixture') url += '#' + activePanel;
    return url;
  }
  function goTo(dia, q) { location.href = buildUrl(dia, q); }

  /* Delegación y Categoría: submit del formulario (conserva día/búsqueda vía
     hidden inputs). Se atan por JS — sin depender de atributos inline. */
  on(delegation, 'change', function () { if (form) form.submit(); });
  on(category, 'change', function () { if (form) form.submit(); });
  /* Deporte: primero recalcula la cascada y luego envía el formulario. */
  on(sport, 'change', function () { syncCategories(); if (form) form.submit(); });

  /* Días: un botón por día + "Todos los días" (igual que la maqueta).
     Cada botón es un <a> real: funciona incluso si el JS no carga. */
  on(dayNav, 'click', function (event) {
    var link = closestEl(event.target, 'a[data-day]');
    if (!link) return;
    if (event.preventDefault) event.preventDefault();
    var pressed = link.getAttribute('aria-pressed') === 'true';
    goTo(pressed ? 'all' : link.getAttribute('data-day'), search ? search.value.trim() : '');
  });

  /* Búsqueda: Enter o al dejar de escribir (debounce, evita recargar x tecla). */
  function selectedDay() {
    var cur = dayNav ? dayNav.querySelector('a[aria-pressed="true"]:not(.all-days)') : null;
    if (cur) return cur.getAttribute('data-day');
    var allBtn = dayNav ? dayNav.querySelector('a.all-days[aria-pressed="true"]') : null;
    if (allBtn) return 'all';
    return INITIAL.dia;
  }
  var debounce;
  on(search, 'input', function () {
    clearTimeout(debounce);
    debounce = setTimeout(function () { goTo(selectedDay(), search.value.trim()); }, 700);
  });
  on(search, 'keydown', function (event) {
    if (event.key === 'Enter' || event.keyCode === 13) {
      clearTimeout(debounce);
      if (event.preventDefault) event.preventDefault();
      goTo(selectedDay(), search.value.trim());
    }
  });

  /* ---------- Desplegar / contraer todas las disciplinas ---------- */
  var forcedOpen = !!INITIAL.q || !!(sport && sport.value) || !!(category && category.value) ||
                   (INITIAL.dia && INITIAL.dia !== 'all');
  var expandedAll = false;
  function disciplineGroups() { return all('#schedule details.discipline-group'); }
  function syncDisclosureButton() {
    if (!expandBtn) return;
    var groups = disciplineGroups();
    expandBtn.hidden = !groups.length;
    var anyOpen = false;
    each(groups, function (d) { if (d.open) anyOpen = true; });
    expandBtn.innerHTML = anyOpen
      ? 'Contraer disciplinas <span aria-hidden="true">\u2191</span>'
      : 'Desplegar disciplinas <span aria-hidden="true">\u2193</span>';
  }
  on(expandBtn, 'click', function () {
    var groups = disciplineGroups();
    var open = false;
    each(groups, function (d) { if (d.open) open = true; });
    open = !open;
    expandedAll = open;
    each(groups, function (d) { d.open = open; });
    syncDisclosureButton();
  });

  /* Al abrir una disciplina, cerrar las otras del mismo día (igual que la
     maqueta: sin <details name=""> para no romper navegadores antiguos). */
  each(disciplineGroups(), function (details) {
    var summary = details.firstElementChild;
    on(summary, 'click', function () {
      if (forcedOpen || expandedAll) return;
      if (!details.open) {
        var parent = details.parentElement;
        var kids = parent ? parent.children : [];
        for (var i = 0; i < kids.length; i++) {
          var k = kids[i];
          if (k !== details && k.tagName === 'DETAILS' &&
              (' ' + k.className + ' ').indexOf(' discipline-group ') >= 0 && k.open) k.open = false;
        }
      }
    });
    on(details, 'toggle', syncDisclosureButton);
  });
  syncDisclosureButton();

  /* ---------- Pestañas (Fixture / Resultados / Medallero) ---------- */
  var activePanel = ['tab-resultados', 'tab-medallero'].indexOf(location.hash.slice(1)) >= 0
    ? location.hash.slice(1) : 'tab-fixture';
  function setTab(id, focus) {
    activePanel = id;
    each(all('.primary-tabs button'), function (button) {
      var isActive = button.getAttribute('data-tab') === id;
      button.setAttribute('aria-selected', isActive ? 'true' : 'false');
      button.tabIndex = isActive ? 0 : -1;
      if (isActive && focus && button.focus) button.focus();
    });
    each(all('.workspace .panel-tab'), function (panel) {
      panel.hidden = panel.id !== id;
    });
    try { if (history && history.replaceState) history.replaceState(null, '', buildUrl(INITIAL.dia, INITIAL.q)); } catch (err) { /* noop */ }
  }
  var tabButtons = all('.primary-tabs button');
  each(tabButtons, function (button, index) {
    on(button, 'click', function () { setTab(button.getAttribute('data-tab')); });
    on(button, 'keydown', function (event) {
      var key = event.key || String.fromCharCode(event.keyCode);
      var next = -1;
      if (key === 'ArrowRight') next = (index + 1) % tabButtons.length;
      else if (key === 'ArrowLeft') next = (index - 1 + tabButtons.length) % tabButtons.length;
      else if (key === 'Home') next = 0;
      else if (key === 'End') next = tabButtons.length - 1;
      else return;
      if (event.preventDefault) event.preventDefault();
      setTab(tabButtons[next].getAttribute('data-tab'), true);
    });
  });
  setTab(activePanel);

  /* ---------- Filtros colapsables en celular ---------- */
  on(toggle, 'click', function () {
    var open = card && card.classList.contains('open');
    addOrRemoveClass(card, 'open', !open);
    if (toggle) toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
  });
  if (typeof window.matchMedia === 'function' &&
      window.matchMedia('(max-width:680px)').matches &&
      (INITIAL.delegacion || (sport && sport.value) || (category && category.value))) {
    addOrRemoveClass(card, 'open', true);
    if (toggle) toggle.setAttribute('aria-expanded', 'true');
  }
})();
</script>
</body>
</html>

<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ===== URL limpia: lectura de los segmentos extra de /publico/... =====
   Antes el panel usaba query string y se veía así:
     /publico?delegacion=Formosa&deporte=13&categoria=56&dia=mi-ercoles-04-11&q=#tab-fixture
   Ahora cada filtro viaja como segmento "legible":
     /publico/delegacion/formosa/deporte/13/categoria/56/dia/2026-11-04/buscar/futbol
   El controlador (Publico::index) ya pasó estos valores por $data; si no
   vienen, se releen de los segmentos por compatibilidad. */
$__ci = isset($this) ? $this : (isset($_CI) ? $_CI : NULL);
$__seg = function ($clave) use ($__ci) {
    if (!$__ci || !isset($__ci->uri)) return '';
    // OJO: el método real de CI3 es segment_array() (no existe uri_segments()).
    // Llamar al nombre equivocado era la causa del HTTP 500 en esta vista.
    if (!method_exists($__ci->uri, 'segment_array')) return '';
    $parts = $__ci->uri->segment_array();       // ['panel-publico','delegacion','formosa',...]
    $n = count($parts);
    for ($i = 0; $i < $n - 1; $i++) {
        if (strtolower((string) $parts[$i]) === strtolower($clave)) {
            $v = (string) $parts[$i + 1];
            // Un separador real "/" nunca llega crudo: solo aceptamos el valor
            // inmediatamente siguiente al nombre del filtro.
            return ($v === '' || strpos($v, '/') !== FALSE) ? '' : rawurldecode($v);
        }
    }
    return '';
};
$delegacion_uri   = isset($delegacion_url)   ? (string) $delegacion_url   : $__seg('delegacion');
$deporte_uri      = isset($deporte_url)      ? (string) $deporte_url      : $__seg('deporte');
$categoria_uri    = isset($categoria_url)    ? (string) $categoria_url    : $__seg('categoria');
$dia_uri          = isset($dia_url)          ? (string) $dia_url          : $__seg('dia');
$buscar_uri       = isset($buscar_url)       ? (string) $buscar_url       : $__seg('buscar');
$disciplina_uri   = isset($disciplina_url)   ? (string) $disciplina_url   : $__seg('disciplina');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titulo_evento) ?> — Panel Público</title>
<link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
<!-- Diseño del panel publico: maqueta aprobada (Entrega-Fixture-Olimpiadas).
     Hoja exclusiva de esta vista: usa selectores globales (body, .container, svg). -->
<link rel="stylesheet" href="<?= base_url('css/panel-publico-theme.css') ?>?v=20261030b">
<style>
/* Ajustes locales de la integración (solo esta vista). */
.venue-name{display:inline}
.discipline-group[open] .discipline-arrow svg{transform:rotate(180deg)}
@media(max-width:680px){.bottom-note a{margin-left:0}}
/* Filtros client-side (JS del panel): días y búsqueda ocultan, nunca borran. */
.day-group[hidden]{display:none}
.discipline-group[hidden]{display:none}
.mass-group[hidden],.partido[hidden]{display:none}
</style>
<style disabled id="estilos-clasicos-panel">
:root{
  --bg:#0d1b2a; --card:#14263b; --card2:#1b3050; --line:#274066;
  --txt:#eaf1f9; --muted:#8fa8c4; --acento:#84cc16; --ok:#2dd4a7;
  /* Gama del verde institucional (#84CC16, lime-500): claro → oscuro */
  --verde-100:#ecfccb; --verde-200:#d9f99d; --verde-300:#bef264; --verde-400:#a3e635;
  --verde-500:#84cc16; --verde-600:#65a30d; --verde-700:#4d7c0f; --verde-800:#3f6212;
  /* "Oro" (1er puesto) en la gama del verde: #d9f99d sobre texto oscuro. */
  --oro:#d9f99d; --plata:#cfd8e3; --bronce:#d9915b;
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:linear-gradient(160deg,#0d1b2a 0%,#10233a 60%,#0d1b2a 100%);color:var(--txt);
     font-family:'Segoe UI',system-ui,-apple-system,sans-serif;min-height:100vh}
.wrap{max-width:1200px;margin:0 auto;padding:16px}
header{text-align:center;padding:22px 10px 10px}
/* Logo oficial sin letras (logo_olimpiadas_2.png): al no tener texto negro,
   puede ir directo sobre el fondo oscuro — va a la par del título. */
.titulo-row{display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap}
/* Un poco más grande */
.titulo-row .logo-evento{height:clamp(80px,11vw,120px);...}

/* Bastante más grande */
.titulo-row .logo-evento{height:clamp(96px,13vw,140px);...}

/* Bien grande (tipo banner) */
.titulo-row .logo-evento{height:clamp(110px,15vw,160px);...}
header{ text-align:center; padding: 14px 10px 6px; }
header h1{font-size:clamp(20px,3.6vw,32px);letter-spacing:1px;line-height:1.25;text-align:left}
header p.sitio{color:var(--txt);margin-top:6px;font-size:14px;font-weight:600;opacity:.85}
header p.lugar{display:inline-block;margin-top:8px;background:var(--card);border:1px solid var(--line);
               border-radius:20px;padding:4px 14px;color:var(--acento);font-size:13px;font-weight:700}
header p.sub{color:var(--muted);margin-top:6px;font-size:14px}
/* Jornada masiva (todos contra todos): sin "rivales" */
.partido.masivo{background:linear-gradient(150deg,var(--card) 0%,#173a5e 100%)}
.masivo-aviso{margin-top:2px;text-align:center;font-size:13px;color:var(--muted);font-style:italic}
.badge.MASIVA{background:#123c52;color:#7fd8ff;margin-left:6px}
/* Barra de filtros */
.filtros {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: center;
  align-items: flex-end;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 16px 20px;
  margin: 18px 0;
}

.filtro-grupo {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 180px;
}

.filtros label {
  font-size: 13px;
  color: var(--muted);
  font-weight: 500;
}

.filtros select {
  background: var(--card2);
  color: var(--txt);
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 15px;
  width: 100%;
  min-width: 180px;
  cursor: pointer;
  transition: border-color 0.2s;
}

.filtros select:hover {
  border-color: var(--acento);
}

.filtros select:focus {
  outline: none;
  border-color: var(--acento);
  box-shadow: 0 0 0 3px rgba(var(--acento-rgb, 0, 123, 255), 0.1);
}

.filtros a.limpiar {
  color: var(--acento);
  text-decoration: none;
  font-size: 14px;
  padding: 8px 16px;
  border: 1px solid var(--line);
  border-radius: 8px;
  transition: all 0.2s;
  align-self: flex-end;
}

.filtros a.limpiar:hover {
  background: var(--card2);
  border-color: var(--acento);
}

/* Responsive */
@media (max-width: 768px) {
  .filtros {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filtro-grupo {
    min-width: 100%;
  }
  
  .filtros a.limpiar {
    align-self: center;
    width: 100%;
    text-align: center;
  }
}
.chip-delegacion{display:inline-block;background:var(--acento);color:#111;border-radius:20px;
                 padding:4px 14px;font-weight:700;font-size:14px}
/* Cards resumen */
.resumen{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:18px}
.stat{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px;text-align:center}
.stat b{display:block;font-size:28px;color:var(--acento)}
.stat span{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
/* Tabs */
.tabs{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:16px}
.tab-btn{background:var(--card);border:1px solid var(--line);color:var(--txt);padding:10px 22px;
         border-radius:30px;font-size:15px;cursor:pointer;transition:.15s}
.tab-btn:hover{border-color:var(--acento)}
.tab-btn.activo{background:var(--acento);color:#111;font-weight:700;border-color:var(--acento)}
.panel-tab{display:none}
.panel-tab.visible{display:block}
/* Fixture: tarjetas de partido */
.grupo-dia{margin-bottom:22px}
.grupo-dia h3{color:var(--acento);font-size:16px;margin-bottom:10px;border-bottom:1px solid var(--line);padding-bottom:6px}
.partidos{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px}
.partido{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:12px 14px}
.partido .meta{display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:8px}
.badge{font-size:11px;padding:2px 8px;border-radius:10px;font-weight:700}
.badge.PROGRAMADO{background:#234;color:#9ec5fe}
.badge.EN_CURSO{background:var(--verde-800);color:var(--verde-300)}
.badge.FINALIZADO{background:#0f3d2e;color:var(--ok)}
.badge.SUSPENDIDO{background:#421f1f;color:#ffab9e}
/* Franja de fase como HEADER real de la card del fixture: "GRUPO A", "ELIMINATORIA"...
   Banda ancha arriba de la tarjeta (sin hueco arriba) para que llame la atención. */
.fase-header{margin:-12px -14px 10px;padding:6px 14px;font-size:12px;font-weight:800;
             letter-spacing:1.2px;text-transform:uppercase;text-align:center;
             background:linear-gradient(90deg, rgba(132,204,22,.35), rgba(132,204,22,.10));
             color:var(--acento);border-bottom:1px solid rgba(132,204,22,.45);
             border-radius:12px 12px 0 0}
.fase-header.elim{background:linear-gradient(90deg, #4a3b18, rgba(74,59,24,.35));
                  color:#ffd76e;border-bottom-color:#8a6d2f}
.vs{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:14px}
.vs .equipo{flex:1;text-align:center;font-weight:600;line-height:1.25}
.vs .marcador{font-size:20px;font-weight:800;color:var(--acento);white-space:nowrap;padding:0 6px}
.vs .vsx{color:var(--muted);font-size:12px}
.ganador{color:var(--ok)}
.pendiente{color:var(--muted);font-style:italic;font-weight:400 !important}
.categoria-linea{font-size:12px;color:var(--muted);margin-top:8px}
/* Deporte destacado en la card del fixture (llama la atención); la categoría
   va discreta abajo. */
.deporte-chip{display:inline-block;background:rgba(132,204,22,.14);border:1px solid rgba(132,204,22,.45);
              color:var(--acento);font-weight:700;font-size:12px;letter-spacing:.5px;text-transform:uppercase;
              border-radius:8px;padding:2px 10px}
.hora-inicio{color:var(--txt);font-weight:600}
/* Resultados */
.resultado-card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:12px}
.resultado-card h4{font-size:15px;margin-bottom:2px}
.resultado-card .sub{font-size:12px;color:var(--muted);margin-bottom:10px}
.fila-pos{display:flex;align-items:center;gap:10px;padding:6px 8px;border-radius:8px;font-size:14px}
.fila-pos:nth-child(odd){background:var(--card2)}
.pos{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;
     font-weight:800;font-size:13px;background:var(--line);flex-shrink:0}
.pos.p1{background:var(--oro);color:#111}.pos.p2{background:var(--plata);color:#111}.pos.p3{background:var(--bronce);color:#111}
.tiempo{margin-left:auto;font-family:Consolas,monospace;color:var(--ok);font-weight:700}
.marcador-res{margin-left:auto;font-weight:800;color:var(--acento)}
.desempate-tag{font-size:11px;color:#ffab9e;margin-left:8px}
/* Encabezado de la card de resultado: DEPORTE destacado + categoría abajo */
.res-header{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.res-categoria{font-size:13px;color:var(--txt);opacity:.85;font-weight:600}
/* Marcador "por equipo" (La Pampa 54 / Mendoza 58) + línea de quién ganó */
.vs-res{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 4px 2px}
.vs-res .lado{flex:1;display:flex;align-items:center;gap:8px;min-width:0}
.vs-res .lado.derecho{justify-content:flex-end;text-align:right}
.vs-res .nombre{font-weight:600;line-height:1.2;overflow-wrap:anywhere}
.vs-res .tato{font-size:22px;font-weight:800;color:var(--acento);white-space:nowrap}
.ganador-linea{margin-top:8px;font-size:13px;font-weight:700;color:var(--ok)}
.empate-linea{margin-top:8px;font-size:13px;color:var(--muted);font-style:italic}
/* Medallero */
table.medallero{width:100%;border-collapse:collapse;background:var(--card);border-radius:12px;overflow:hidden}
table.medallero th{background:var(--card2);color:var(--muted);font-size:12px;text-transform:uppercase;
                   padding:10px;border-bottom:1px solid var(--line)}
table.medallero td{padding:10px;border-bottom:1px solid var(--line);font-size:15px;text-align:center}
table.medallero td:first-child{text-align:left;font-weight:600}
table.medallero tr:hover td{background:rgba(132,204,22,.06)}
table.medallero td.oro{color:var(--oro);font-weight:800}
table.medallero td.plata{color:var(--plata);font-weight:800}
table.medallero td.bronce{color:var(--bronce);font-weight:800}
.rank-num{display:inline-flex;width:24px;height:24px;border-radius:50%;background:var(--line);
          align-items:center;justify-content:center;font-size:12px;font-weight:700;margin-right:8px}
tr.top1 .rank-num{background:var(--oro);color:#111}
.vacio{text-align:center;color:var(--muted);padding:40px 10px;font-size:15px}
footer{text-align:center;color:var(--muted);font-size:12px;padding:26px 0 16px}
footer .mascota{display:block;margin:0 auto 10px;height:72px;width:auto;object-fit:contain;
                filter:drop-shadow(0 3px 10px rgba(0,0,0,.4))}
@media(max-width:600px){.partidos{grid-template-columns:1fr}}

/* Logo + título en la misma línea */
.titulo-row{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:16px;
  flex-wrap:wrap;
  max-width: 600px;          /* 👈 achica el bloque (ajustá a gusto) */
  margin: 0 auto;            /* 👈 lo centra */
  padding: 0 10px;
}

/* El título se adapta al espacio y se parte en líneas */
.titulo-row h1{
  font-size: clamp(20px, 3.6vw, 32px);
  letter-spacing: 1px;
  line-height: 1.25;
  text-align: left;
  margin: 0;
  white-space: normal;       /* 👈 permite saltos de línea */
  flex: 1 1 400px;           /* 👈 ocupa el espacio disponible, mínimo 400px */
}
.titulo-row{
  display:flex;
  align-items:center;
  justify-content:center;   /* centrados juntos */
  gap:18px;
  flex-wrap:nowrap;         /* 👈 clave: no se rompe la línea */
}
.titulo-row .logo-evento{
  height:clamp(140px, 18vw, 220px);
  width:auto;
  object-fit:contain;
  flex-shrink:0;
  filter:drop-shadow(0 4px 14px rgba(0,0,0,.45));
}
/* Si la pantalla es muy chica, recién ahí se apilan */
@media (max-width: 600px){
  .titulo-row{
    max-width: 100%;
    flex-direction: column;
    text-align: center;
  }
  .titulo-row h1{
    text-align: center;
    flex: 1 1 auto;
  }
}

</style>
</head>
<body>
<a class="skip" href="#contenido">Ir al contenido</a>

<?php
/* ===== Diseño nuevo (Entrega-Fixture-Olimpiadas) =====
   Claves de día y disciplina normalizadas, a la manera de la maqueta:
   sin tildes, sin distinción de mayúsculas. Se usan para armar los enlaces
   ?dia= / #disciplina-... y para que el JS recuerde qué días/disciplinas
   quedaron abiertos al navegar con filtros o búsqueda. */
$norm = function ($txt) {
    $txt = trim((string) $txt);
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt);
        if ($t !== false) $txt = $t;
    }
    $txt = strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '-', $txt));
    return trim($txt, '-');
};
?>
<header class="masthead">
  <div class="container masthead-inner">
    <img class="official-logo" src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" alt="Logo <?= htmlspecialchars($titulo_evento) ?>" width="220" height="130">
    <div class="masthead-copy">
      <p class="eyebrow"><?= htmlspecialchars($lugar_olimpico) ?> <span aria-hidden="true">/</span> PANEL PÚBLICO</p>
      <h1>Agenda y resultados</h1>
      <p>Toda la competencia, en un solo lugar.</p>
    </div>
    <div class="event-mark">
      <span class="event-mark-label"><?= htmlspecialchars(defined('EDICION') ? EDICION . ' EDICIÓN' : 'PANEL PÚBLICO') ?></span>
      <span>Nos une el deporte.</span>
      <div class="color-stripes" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    </div>
  </div>
</header>

<main id="contenido" class="container">

<!-- Filtros -->
<section class="filters-card" aria-labelledby="filters-title">
  <div class="filters-heading">
    <div>
      <h2 id="filters-title">Encontrá tu competencia</h2>
      <p>Elegí una delegación, un deporte o una categoría.</p>
    </div>
    <?php if (!empty($delegacion) || !empty($id_deporte) || !empty($id_categoria)): ?>
    <a class="reset-button" href="<?= site_url('panel-publico') ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10a8 8 0 1 1 1.8 7.1M4 4v6h6"/></svg>Limpiar filtros
    </a>
    <?php endif; ?>
  </div>
  <!-- URL limpia: el formulario NO usa query string (?a=b&c=d). Cada select
       arma su propio enlace "limpio" (/publico/delegacion/<nombre>,
       /publico/deporte/<id>/categoria/<id>, ...) y el JS navega con él.
       Así la barra de direcciones queda legible y compartible. -->
  <form method="get" action="<?= site_url('panel-publico') ?>" id="filters-form" class="filters-form">
    <div class="filter-field">
      <label for="filtroDelegacion">Delegación</label>
      <select name="delegacion" id="filtroDelegacion" data-base="<?= site_url('panel-publico') ?>" <?= $delegacion !== '' ? 'class="has-filter"' : '' ?>>
        <option value="">Todas (vista general)</option>
        <?php foreach ($delegaciones as $d): ?>
          <option value="<?= rawurlencode($d) ?>" data-slug="<?= htmlspecialchars(rawurlencode($d)) ?>" <?= ($delegacion === $d) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-field">
      <label for="filtroDeporte">Deporte</label>
      <select name="deporte" id="filtroDeporte" <?= !empty($id_deporte) ? 'class="has-filter"' : '' ?>>
        <option value="">Todos</option>
        <?php foreach ($deportes as $dep): ?>
          <option value="<?= (int)$dep['id_deporte'] ?>" <?= ((int)$id_deporte === (int)$dep['id_deporte']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($dep['nombre_deporte']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-field">
      <label for="filtroCategoria">Categoría</label>
      <select name="categoria" id="filtroCategoria" <?= !empty($id_categoria) ? 'class="has-filter"' : '' ?>>
        <option value="">Todas</option>
        <?php foreach ($deportes as $dep): ?>
          <?php foreach (isset($dep['categorias']) ? $dep['categorias'] : array() as $cat): ?>
            <?php if (empty($cat['id_categoria'])) continue; ?>
            <option value="<?= (int)$cat['id_categoria'] ?>"
                    data-deporte="<?= (int)$dep['id_deporte'] ?>"
                    <?= ((int)$id_categoria === (int)$cat['id_categoria']) ? 'selected' : '' ?>>
              <?= empty($id_deporte) ?  htmlspecialchars($dep['nombre_deporte'])  : '' ?> - <?= htmlspecialchars($cat['nombre_categoria']) ?>
            </option>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </select>
    </div>
    <noscript><div style="grid-column:1/-1;margin-top:6px"><button type="submit" class="secondary-button">Aplicar filtros</button></div></noscript>
  </form>
</section>

<!-- Resumen -->
<section class="stats" aria-label="Resumen general del evento">
  <div class="stat"><b><?= (int)$resumen['delegaciones'] ?></b><span>Delegaciones</span></div>
  <div class="stat"><b><?= (int)$resumen['deportes'] ?></b><span>Deportes</span></div>
  <div class="stat"><b><?= (int)$resumen['categorias'] ?></b><span>Categorías</span></div>
  <div class="stat"><b><?= (int)$resumen['finalizados'] ?><small>/<?= (int)$resumen['partidos'] ?></small></b><span>Partidos jugados</span></div>
  <div class="stat"><b><?= (int)$resumen['resultados'] ?></b><span>Resultados</span></div>
  <div class="stat"><b><?= (int)$resumen['premiados'] ?></b><span>Medallas entregadas</span></div>
</section>

<div class="workspace">
  <!-- Tabs -->
  <nav class="primary-tabs" role="tablist" aria-label="Información de la competencia">
    <button type="button" role="tab" id="button-fixture" class="tab-btn activo" aria-controls="tab-fixture" aria-selected="true" data-tab="tab-fixture" tabindex="0"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 3v4m8-4v4M4 11h16M8 15h2m4 0h2"/></svg>Fixture</button>
    <button type="button" role="tab" id="button-resultados" class="tab-btn" aria-controls="tab-resultados" aria-selected="false" data-tab="tab-resultados" tabindex="-1"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4m0 0c5-5 9 5 14 0v10c-5 5-9-5-14 0"/></svg>Resultados</button>
    <button type="button" role="tab" id="button-medallero" class="tab-btn" aria-controls="tab-medallero" aria-selected="false" data-tab="tab-medallero" tabindex="-1"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 3 4 6 4-6M5 3h3m8 0h3"/><circle cx="12" cy="15" r="7"/><path d="m12 11 1.2 2.5 2.8.4-2 1.9.5 2.7-2.5-1.3-2.5 1.3.5-2.7-2-1.9 2.8-.4Z"/></svg>Medallero</button>
  </nav>

<?php
/* Etiquetas legibles (mismo criterio que pase_valido.php): fases del fixture y
   métodos de desempate. El público no lee códigos como "SEMIFINAL" o "PENALES". */
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

/* "TRAMPITA DE LAS JORNADAS" (espejo de fixture.php / reporte_fixture.php):
   numero_fecha se usa como divisor de grupos — Jornada 1 → Grupo A,
   Jornada 2 → Grupo B, ... Para saber si una jornada es TODA de fase GRUPO
   primero se indexan las filas por categoría + numero_fecha. */
$letra_grupo = function ($n) {
    static $abecedario = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
                               'N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    if ($n >= 1 && $n <= count($abecedario)) return $abecedario[$n - 1];
    return chr(64 + intdiv($n - 1, 26)) . '-' . $abecedario[($n - 1) % 26];
};
$grupos_idx = array(); // clave "categoria|numero_fecha" → ['hay_grupo'=>bool,'todas_grupo'=>bool]
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
// Solo las jornadas que son TODAS de fase GRUPO tienen letra de grupo:
// Grupo A (fecha 1), Grupo B (fecha 2), ... (si se agota la Z, sigue AA...).
$grupo_etiqueta = array(); // clave "categoria|numero_fecha" → 'Grupo A' | null
foreach ($grupos_idx as $clave => $g) {
    $num_f = (int) substr($clave, strpos($clave, '|') + 1);
    $grupo_etiqueta[$clave] = ($g['todas_grupo'] && $g['hay_grupo'] && $num_f > 0)
        ? 'Grupo ' . $letra_grupo($num_f)
        : null;
}

/* ===== Helpers de presentación (diseño de la maqueta) ===== */
$dias_es = array(0=>'Domingo',1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado');
// Abrevatura de 3 letras SIN tilde para el botonito del día ("Mier" arriba de 04/11).
// date('w') = 0 (domingo) … 6 (sábado); antes se usaba date('l') —"Wednesday"— y,
// como esa clave no existía en el mapa, PHP devolvía '' y el botón salía sin etiqueta.
$dias_curtos = array(0=>'Dom',1=>'Lun',2=>'Mar',3=>'Mier',4=>'Jue',5=>'Vier',6=>'Sáb');

// Nombre propio en minúsculas, excepto siglas como MTB / PÁDEL / PADI.
$lower_name = function ($txt) {
    $t = trim((string) $txt);
    if ($t === '') return '';
    if (preg_match('/^(?!.*[a-záéíóúüñ])[A-ZÁÉÍÓÚÜÑ0-9&\/\x{b4}\s\.\-\(\)]+$/u', $t)) return $t;
    $out = preg_replace_callback('/[\p{L}\p{M}]+/u', function ($m) {
        $w = $m[0];
        if (mb_strlen($w, 'UTF-8') <= 3) return $w; // "de", "3x3"... se respetan
        return mb_strtolower(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($w, 1, null, 'UTF-8');
    }, $t);
    return trim($out);
};

// Horario de la disciplina: rango entre la primera y la última hora del día.
$rango_horas = function ($horas) {
    $hs = array_values(array_unique(array_filter($horas)));
    sort($hs);
    if (!$hs) return 'Horario a confirmar';
    return count($hs) === 1 ? $hs[0] : $hs[0] . '–' . $hs[count($hs) - 1];
};

// Icono genérico de disciplina (banderín, como en la maqueta).
$svg_disciplina = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20V5m0 0c5-4 9 4 14 0v9c-5 4-9-4-14 0"/></svg>';
$svg_pin        = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>';
$svg_search     = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>';
$svg_chevron    = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
$svg_flag       = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4m0 0c5-5 9 5 14 0v10c-5 5-9-5-14 0"/></svg>';
$svg_medal      = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 3 4 6 4-6M5 3h3m8 0h3"/><circle cx="12" cy="15" r="7"/><path d="m12 11 1.2 2.5 2.8.4-2 1.9.5 2.7-2.5-1.3-2.5 1.3.5-2.7-2-1.9 2.8-.4Z"/></svg>';
?>
<!-- ============ FIXTURE ============ -->
<section class="panel" id="tab-fixture" role="tabpanel" aria-labelledby="button-fixture" tabindex="0">
  <div class="section-heading">
    <div>
      <p class="eyebrow">LA AGENDA</p>
      <h2>Fixture de competencias</h2>
      <p id="fixture-description"><?php if (!empty($delegacion)): ?>Agenda de <?= htmlspecialchars($delegacion) ?>. Abrí una disciplina para ver sus competencias.<?php else: ?>Abrí una disciplina para ver horarios, categorías y encuentros.<?php endif; ?></p>
    </div>
  </div>

  <?php
  /* Días disponibles (con los filtros del servidor ya aplicados sobre $fixture),
     en el orden de la consulta. */
  $dias_disp = array();   // fecha → ['full' => "Miércoles 04/11", 'short' => 'Mier']
  foreach ($fixture as $fx0) {
      $f0 = (string) $fx0['fecha_competencia'];
      if ($f0 === '' || isset($dias_disp[$f0])) continue;
      $ts0 = strtotime($f0);
      // date('w') = 0 (domingo) … 6 (sábado). Antes se usaba date('l') —"Wednesday"—
      // y, como no existía esa clave en el mapa, PHP devolvía '' y el botón del
      // 04/11 salía sin etiqueta arriba. Ahora: "Mier" + "04/11".
      $dias_disp[$f0] = $ts0
          ? array('full'  => $dias_es[(int) date('w', $ts0)] . ' ' . date('d/m', $ts0),
                  'short' => $dias_curtos[(int) date('w', $ts0)])
          : array('full' => $f0, 'short' => $f0);
  }
  /* Día elegido: ahora viene por SEGMENTO de URL limpia (/publico/dia/2026-11-04)
     y no por query string. Se acepta también la forma antigua (?dia=) para que
     los enlaces guardados/compartidos antes sigan funcionando. */
  $get_dia = trim((string) $dia_uri);
  if ($get_dia === '' && isset($_GET['dia'])) $get_dia = trim((string) $_GET['dia']);
  $dia_sel  = '';
  if ($get_dia !== '') {
      foreach ($dias_disp as $fecha_x => $info_x) {
          if ($get_dia === $fecha_x || $norm($get_dia) === $norm($info_x['full'])) { $dia_sel = $fecha_x; break; }
      }
      if ($get_dia === 'all') $dia_sel = 'all';
  }
  if ($dia_sel === '' && !empty($dias_disp)) $dia_sel = (string) array_key_first($dias_disp);
  elseif (empty($dias_disp)) $dia_sel = 'all';

  /* Búsqueda: igualmente por segmento (/publico/buscar/futbol) o ?q= antiguo. */
  $q_uri = trim((string) $buscar_uri);
  if ($q_uri === '' && isset($_GET['q'])) $q_uri = trim((string) $_GET['q']);

  /* Disciplina anclada por #disciplina-<clave> (o segmento disciplina/...): entra abierta. */
  $hash_get = trim((string) $disciplina_uri);
  if ($hash_get === '' && isset($_GET['disciplina'])) $hash_get = trim((string) $_GET['disciplina']);
  $disc_plana = $hash_get !== '' ? $norm($hash_get) : '';
  $disc_fecha = $hash_get !== '' && strpos($hash_get, '-') !== FALSE
      ? substr($hash_get, strrpos($hash_get, '-') + 1) : '';
  if ($disc_plana === '' && $disc_fecha !== '') $disc_plana = $norm(substr($hash_get, 0, strrpos($hash_get, '-')));

  /* Base sobre la que el JS arma las URLs limpias al cambiar un filtro. */
  $url_base_filtros = rtrim(str_replace('\\', '/', site_url('panel-publico')), '/');
  /* Clave legible del día seleccionado (para /panel-publico/dia/<clave>). */
  $dia_key_actual = ($dia_sel === 'all') ? 'all' : $norm($dias_disp[$dia_sel]['full']);
  ?>
  <div class="schedule-tools">
    <nav class="day-nav" id="day-nav" aria-label="Filtrar por día" data-base="<?= htmlspecialchars($url_base_filtros) ?>">
      <?php foreach ($dias_disp as $fecha_d => $info_d):
          $clave_d = $norm($info_d['full']); ?>
        <button type="button" class="day-button"
                data-day="<?= htmlspecialchars($clave_d) ?>" data-date="<?= htmlspecialchars((string) $fecha_d) ?>"
                aria-pressed="<?= $dia_sel === (string) $fecha_d ? 'true' : 'false' ?>"
                aria-label="<?= htmlspecialchars($info_d['full']) ?>">
          <span><?= htmlspecialchars($info_d['short']) ?></span><strong><?= htmlspecialchars(date('d/m', strtotime($fecha_d)) ?: '') ?></strong>
        </button>
      <?php endforeach; ?>
      <?php if (!empty($dias_disp)): ?>
        <button type="button" class="day-button all-days" data-day="all" data-date="all" aria-pressed="<?= $dia_sel === 'all' ? 'true' : 'false' ?>">Todos<br>los días</button>
      <?php endif; ?>
    </nav>
    <label class="search-box"><?= $svg_search ?><input id="search" type="search" placeholder="Buscar equipo o sede" aria-label="Buscar equipo, sede o categoría" autocomplete="off" value="<?= htmlspecialchars($q_uri) ?>"></label>
  </div>
  <div class="fixture-meta">
    <p id="fixture-count" aria-live="polite" role="status"></p>
    <button id="expand-disciplines" type="button">Desplegar disciplinas <span aria-hidden="true">↓</span></button>
  </div>

  <?php if (empty($fixture)): ?>
    <div class="empty-state" id="search-empty">
      <div class="empty-icon" aria-hidden="true">⌕</div>
      <h3>No encontramos competencias</h3>
      <p><?php if (!empty($delegacion) || !empty($id_deporte) || !empty($id_categoria)): ?>Con estos filtros todavía no hay partidos programados. Probá limpiar los filtros o cambiar la categoría.<?php else: ?>El fixture del evento todavía está vacío. Volvé más tarde para ver la agenda completa.<?php endif; ?></p>
      <a class="secondary-button" href="<?= site_url('panel-publico') ?>">Ver todas las competencias</a>
    </div>
  <?php else: ?>
    <?php
    /* Agrupación del fixture real: día → disciplina (deporte) → tarjetas.
       Las pruebas masivas (todos contra todos) se agrupan por
       hora + sede + estado dentro de su disciplina, como en la maqueta. */
    $por_dia = array();
    foreach ($fixture as $fx) {
        $fd = (string) $fx['fecha_competencia'];
        $dep_key = trim((string) (isset($fx['nombre_deporte']) ? $fx['nombre_deporte'] : ''));
        if ($dep_key === '') $dep_key = 'Competencia';
        $por_dia[$fd][$dep_key][] = $fx;
    }
    $cualquier_abierta = FALSE;
    $total_visibles = 0; $disciplinas_visibles = 0;
    ?>
    <div id="schedule">
    <?php
    foreach ($por_dia as $fecha_d => $disciplinas):
        // Se renderizan TODOS los días (con el resto oculto), para que el filtro
        // por día funcione del lado del cliente sin recargar la página.
        if (!isset($dias_disp[$fecha_d])) continue;
        $etiqueta_d = $dias_disp[$fecha_d]['full'];
        $clave_d = $norm($etiqueta_d);
        $dia_oculto = ($dia_sel !== 'all' && (string) $fecha_d !== $dia_sel);
    ?>
    <section class="day-group" data-date="<?= htmlspecialchars((string) $fecha_d) ?>" data-day="<?= htmlspecialchars($clave_d) ?>" aria-label="<?= htmlspecialchars($etiqueta_d) ?>"<?= $dia_oculto ? ' hidden' : '' ?>>
      <h3 class="day-heading"><?= htmlspecialchars($etiqueta_d) ?></h3>
      <div class="discipline-list">
        <?php foreach ($disciplinas as $dep_key => $items):
            $clave_disc = $norm($dep_key);
            $horas_d = array(); $sedes_d = array(); $n_items = count($items);
            foreach ($items as $it) {
                $h_it = strtotime('today ' . trim((string) $it['hora_inicio']));
                $h_txt = $h_it !== false ? date('H:i', $h_it) : substr(trim((string) $it['hora_inicio']), 0, 5);
                if ($h_txt !== '') $horas_d[] = $h_txt;
                if (!empty($it['lugar_nombre'])) $sedes_d[strtolower(trim((string) $it['lugar_nombre']))] = trim((string) $it['lugar_nombre']);
            }
            $desc_d = $rango_horas($horas_d);
            $sedes_vals = array_values($sedes_d);
            $desc_d .= ' · ' . (count($sedes_vals) === 1 ? $lower_name($sedes_vals[0]) : (count($sedes_vals) > 1 ? count($sedes_vals) . ' sedes' : 'Sede a confirmar'));
            /* La disciplina anclada por #disciplina-... entra abierta; las demás,
               cerradas (como en la maqueta). El JS recuerda además las que el
               usuario abra y cierre. */
            $abierta = FALSE;
            if ($disc_plana !== '') {
                foreach ($disciplinas as $dk_plan => $_ig) {
                    if ($norm($dk_plan) === $disc_plana) { $abierta = ((string) $fecha_d === $disc_fecha); break; }
                }
            }
        ?>
        <details class="discipline-group<?= $abierta ? ' abierta-inicial' : '' ?>" id="disciplina-<?= htmlspecialchars($clave_d . '-' . $clave_disc) ?>" data-discipline="<?= htmlspecialchars($clave_disc) ?>" data-date="<?= htmlspecialchars((string) $fecha_d) ?>">
          <summary class="discipline-summary">
            <span class="discipline-symbol" aria-hidden="true"><?= $svg_disciplina ?></span>
            <span class="discipline-label">
              <span class="discipline-name"><?= htmlspecialchars($lower_name($dep_key)) ?></span>
              <span class="discipline-description"><?= htmlspecialchars($desc_d) ?></span>
            </span>
            <span class="discipline-count"><?= $n_items . ' ' . ($n_items === 1 ? 'competencia' : 'competencias') ?></span>
            <span class="discipline-arrow" aria-hidden="true"><?= $svg_chevron ?></span>
          </summary>
          <div class="discipline-body">
            <?php
            /* Masivas agrupadas por hora+sede+estado (aparecen en su primera posición). */
            $mass_groups = array(); $rendered_mass = array();
            foreach ($items as $idx_m => $it_m) {
                $es_m = ((string) $it_m['fase'] === 'JORNADA_UNICA')
                     || ((string) (isset($it_m['modalidad_competencia']) ? $it_m['modalidad_competencia'] : '') === 'MASIVO_TIEMPO');
                if (!$es_m) continue;
                $h_m = strtotime('today ' . trim((string) $it_m['hora_inicio']));
                $hora_m = $h_m !== false ? date('H:i', $h_m) : substr(trim((string) $it_m['hora_inicio']), 0, 5);
                $sede_m = !empty($it_m['lugar_nombre']) ? trim((string) $it_m['lugar_nombre']) : '';
                $estado_m = (string) $it_m['estado'];
                $mg_key = $hora_m . '|' . strtolower($sede_m) . '|' . $estado_m;
                if (!isset($mass_groups[$mg_key])) $mass_groups[$mg_key] = array('hour' => $hora_m, 'venue' => $sede_m, 'status' => $estado_m, 'items' => array(), 'first' => $idx_m);
                $mass_groups[$mg_key]['items'][] = $it_m;
            }
            ?>
            <div class="cards<?= $n_items ? '' : '' ?>">
            <?php foreach ($items as $idx => $fx):
                // Estado del marcador
                $mar = $fx['marcador'];
                $eq1_nombre = $fx['ute_1_nombre'] ?: '<span class="pendiente">Por definir</span>';
                $eq2_nombre = $fx['ute_2_nombre'] ?: '<span class="pendiente">Por definir</span>';
                $marcador_txt = '';
                $g1 = false; $g2 = false;
                if ($mar && count($mar['equipos']) >= 2) {
                    $a = $mar['equipos'][0]; $b = $mar['equipos'][1];
                    $marcador_txt = $a['a_favor'] . ' – ' . $a['en_contra'];
                    // Determinar ganador por goles a favor (y desempate si hubo)
                    if ((int)$a['a_favor'] > (int)$a['en_contra']) $g1 = true;
                    elseif ((int)$a['en_contra'] > (int)$a['a_favor']) $g2 = true;
                    if (!empty($mar['hubo_desempate'])) { $g1 = false; $g2 = false; }
                } elseif ($fx['estado'] === 'FINALIZADO' && !empty($fx['resultado'])) {
                    $marcador_txt = 'Final';
                }

                // Deportes MASIVO_TIEMPO / jornadas JORNADA_UNICA: todos contra todos,
                // NO llevan rivales ni marcador "VS". (modalidad_competencia es un dato
                // interno: solo se usa acá, nunca se muestra.)
                $es_masivo = ((string) $fx['fase'] === 'JORNADA_UNICA')
                          || ((string) (isset($fx['modalidad_competencia']) ? $fx['modalidad_competencia'] : '') === 'MASIVO_TIEMPO');

                // Horario: lo importante es la HORA DE COMIENZO en hh:mm (los segundos
                // y el horario de fin no interesan al público).
                $hora_ts = strtotime('today ' . trim((string) $fx['hora_inicio']));
                $hora_txt = $hora_ts !== false ? date('H:i', $hora_ts) : substr(trim((string) $fx['hora_inicio']), 0, 5);

                // Chip de fase en el header de la card: "GRUPO A/B/..." (numero_fecha →
                // letra, misma regla que fixture.php/reporte_fixture.php) o
                // "ELIMINATORIA" para cualquier otra fase (SEMIFINAL, FINAL, OCTAVOS...).
                $clave_fx = (int) (isset($fx['id_categoria']) ? $fx['id_categoria'] : 0) . '|'
                          . (int) (isset($fx['numero_fecha']) ? $fx['numero_fecha'] : 0);
                $grupo_txt = isset($grupo_etiqueta[$clave_fx]) ? $grupo_etiqueta[$clave_fx] : null;
                $fase_raw = strtoupper(trim((string) (isset($fx['fase']) ? $fx['fase'] : '')));
                if ($grupo_txt !== null) {
                    $chip_fase = 'Grupo ' . substr($grupo_txt, 6); // "Grupo A"
                    $chip_elim = false;
                } elseif ($fase_raw !== '' && $fase_raw !== 'JORNADA_UNICA') {
                    $chip_fase = 'Eliminatoria';
                    $chip_elim = true;
                } else {
                    $chip_fase = null;
                    $chip_elim = false;
                }

                if ($es_masivo):
                    $mg_key = $hora_txt . '|' . strtolower(!empty($fx['lugar_nombre']) ? trim((string) $fx['lugar_nombre']) : '') . '|' . (string) $fx['estado'];
                    if (in_array($mg_key, $rendered_mass, TRUE)) continue;
                    $rendered_mass[] = $mg_key;
                    $mg = $mass_groups[$mg_key];
                    $n_cats = count($mg['items']);
                ?>
              <article class="mass-group">
                <div class="mass-top">
                  <div class="mass-time"><strong><?= htmlspecialchars($mg['hour'] !== '' ? $mg['hour'] : '—') ?></strong><span>HORARIO</span></div>
                  <div class="mass-title">
                    <h4><?= htmlspecialchars($lower_name($dep_key)) ?></h4>
                    <p><?= $n_cats . ' ' . ($n_cats === 1 ? 'categoría' : 'categorías') ?></p>
                    <?php if ($mg['venue'] !== ''): ?><p class="venue"><?= $svg_pin ?><span><?= htmlspecialchars($lower_name($mg['venue'])) ?></span></p><?php endif; ?>
                  </div>
                </div>
                <span class="badge <?= htmlspecialchars($mg['status']) ?>"><?= htmlspecialchars($mg['status']) ?></span>
                <details open>
                  <summary>Categorías de la jornada</summary>
                  <div class="category-rows">
                    <?php foreach ($mg['items'] as $it_c):
                        $cat_c = trim((string) (isset($it_c['nombre_categoria']) ? $it_c['nombre_categoria'] : ''));
                        $prueba_c = trim((string) (isset($it_c['nombre_prueba']) ? $it_c['nombre_prueba'] : ''));
                    ?>
                      <div class="partido masivo">
                        <div class="categoria-linea">
                          <span style="opacity:.85"><?= $cat_c !== '' ? htmlspecialchars($cat_c) : 'Categoría' ?></span>
                          <?php if ($prueba_c !== ''): ?> · <?= htmlspecialchars($prueba_c) ?><?php endif; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </details>
              </article>
            <?php else: ?>
              <div class="partido">
                <?php if ($chip_fase !== null): ?>
                <div class="fase-header<?= $chip_elim ? ' elim' : '' ?>"><?= htmlspecialchars($chip_fase) ?></div>
                <?php endif; ?>
                <div class="vs">
                  <div class="equipo <?= $g1 ? 'ganador' : '' ?>"><?= $eq1_nombre ?></div>
                  <div class="marcador"><?= $marcador_txt !== '' ? htmlspecialchars($marcador_txt) : '<span class="vsx">VS</span>' ?></div>
                  <div class="equipo <?= $g2 ? 'ganador' : '' ?>"><?= $eq2_nombre ?></div>
                </div>
                <div class="meta">
                  <span><span class="hora-inicio"><?= htmlspecialchars($hora_txt) ?></span><?php if (!empty($fx['lugar_nombre'])): ?><span class="venue-name"><?= htmlspecialchars($lower_name((string) $fx['lugar_nombre'])) ?></span><?php endif; ?></span>
                  <span class="badge <?= htmlspecialchars($fx['estado']) ?>"><?= htmlspecialchars($fx['estado']) ?></span>
                </div>
                <div class="categoria-linea">
                  <?php if (!empty($fx['nombre_categoria'])): ?><span style="opacity:.75"><?= htmlspecialchars((string) $fx['nombre_categoria']) ?></span><?php endif; ?>
                  <?php if (!empty($fx['nombre_prueba'])): ?> · <?= htmlspecialchars($fx['nombre_prueba']) ?><?php endif; ?>
                </div>
              </div>
            <?php endif; endforeach; ?>
            </div>
          </div>
        </details>
        <?php
            $total_visibles += $n_items; $disciplinas_visibles++;
        endforeach; ?>
      </div>
    </section>
    <?php endforeach; ?>
    </div><!-- /#schedule -->
    <div id="search-empty" class="empty-state" hidden>
      <div class="empty-icon" aria-hidden="true">⌕</div>
      <h3>No encontramos competencias</h3>
      <p>Probá con otra búsqueda o cambiá los filtros.</p>
      <button type="button" class="secondary-button" id="clear-search">Ver todas las competencias</button>
    </div>
  <?php endif; ?>
</section>

<!-- ============ RESULTADOS ============ -->
<section class="panel" id="tab-resultados" role="tabpanel" aria-labelledby="button-resultados" tabindex="0" hidden>
  <div class="section-heading">
    <div>
      <p class="eyebrow">CADA ENCUENTRO CUENTA</p>
      <h2>Resultados</h2>
      <p>Marcadores y posiciones por competencia.</p>
    </div>
  </div>
  <?php if (empty($resultados)): ?>
    <div class="vacio">Todavía no hay resultados cargados<?= $delegacion ? ' para esta delegación' : '' ?><?= !empty($id_categoria) ? ' en esta categoría' : '' ?>.</div>
  <?php else: foreach ($resultados as $res):
      /* Encabezado: DEPORTE destacado y CATEGORÍA abajo (nunca el nombre del
         partido, que es un dato interno tipo "Final - Partido 1"). */
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
        <?php if ($dep_txt !== ''): ?><span class="deporte-chip"><?= htmlspecialchars($dep_txt) ?></span><?php endif; ?>
        <?php if ($cat_txt !== ''): ?><span class="res-categoria"><?= htmlspecialchars($cat_txt) ?></span><?php endif; ?>
      </div>
      <div class="sub">
        <?= $fase_txt !== '' ? htmlspecialchars($fase_txt) . ' · ' : '' ?><?= htmlspecialchars($fecha_txt) ?><?= $hora_txt !== '' ? ' · 🕐 ' . htmlspecialchars($hora_txt) : '' ?><?= !empty($res['lugar']) ? ' · ' . htmlspecialchars((string) $res['lugar']) : '' ?><?= !empty($res['nombre_prueba']) ? ' · ' . htmlspecialchars((string) $res['nombre_prueba']) : '' ?>
      </div>
      <?php if ((string) $res['tipo_resultado'] === 'MARCADOR' && count($res['detalle']) >= 2):
          /* Enfrentamiento: cada equipo con SU puntaje (54 / 58), no "54:58".
             Abajo: quién ganó, o cómo se desempató el empate (idea de pase_valido.php). */
          $a = $res['detalle'][0]; $b = $res['detalle'][1];
          $eq1_nombre = trim((string) $a['nombre_libre']) !== '' ? $a['nombre_libre'] : 'Equipo 1';
          $eq2_nombre = trim((string) $b['nombre_libre']) !== '' ? $b['nombre_libre'] : 'Equipo 2';
          /* Cada fila de `resultado_detalle` guarda el marcador DESDE SU PROPIA
             perspectiva: marcador_local = tantos del equipo de la fila,
             marcador_visita = tantos del rival (criterio idéntico a
             pase_valido.php). ANTES se leía el local de una fila y la VISITA de
             la otra, lo que con datos espejados mostraba un empate falso
             (ej. 105 / 105 en vez de 105 : 45). */
          $p1 = (int) $a['marcador_local'];
          $r1 = (int) $a['marcador_visita'];
          $p2 = (int) $b['marcador_local'];
          $r2 = (int) $b['marcador_visita'];
          if ($p2 === $p1 && $r2 === $r1) {
              /* Datos históricos "espejados": ambas filas guardaron el mismo par
                 (goles_1:goles_2). La segunda fila debía estar invertida, así
                 que se invierte SU par (tantos = rival, rival = tantos), igual
                 que Premiacion_model::_parsear_marcador(). Con nombres/UTE
                 idénticos podría ser un empate real cargado duplicado: solo
                 normalizamos si los equipos son distintos (mismo criterio de
                 Resultado_model::_reparar_marcador_espejado). */
              $equipos_distintos = trim((string) $a['nombre_libre']) !== trim((string) $b['nombre_libre'])
                  || ((isset($a['id_ute']) ? (int) $a['id_ute'] : 0) > 0
                      && (isset($b['id_ute']) ? (int) $b['id_ute'] : 0) > 0
                      && (int) $a['id_ute'] !== (int) $b['id_ute']);
              if ($equipos_distintos) {
                  $tmp = $p2; $p2 = $r2; $r2 = $tmp;
              }
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
            <span class="nombre <?= $gana1 ? 'ganador' : '' ?>"><?= htmlspecialchars($eq1_nombre) ?></span>
            <span class="tato"><?= $p1 ?></span>
          </div>
          <div class="lado derecho">
            <span class="tato"><?= $p2 ?></span>
            <span class="nombre <?= $gana2 ? 'ganador' : '' ?>"><?= htmlspecialchars($eq2_nombre) ?></span>
          </div>
        </div>
        <?php if ($gana1): ?>
          <div class="ganador-linea">🏆 Ganó <?= htmlspecialchars($eq1_nombre) ?></div>
        <?php elseif ($gana2): ?>
          <div class="ganador-linea">🏆 Ganó <?= htmlspecialchars($eq2_nombre) ?></div>
        <?php elseif ($p1 === $p2 && $hubo_desempate): ?>
          <div class="ganador-linea">🏅 Empate en tiempo regular — <?= htmlspecialchars($ganador_desemp !== '' ? $ganador_desemp : 'Definido') ?>
            ganó en <?= htmlspecialchars(isset($metodos_txt[(string) $res['desempate_metodo']]) ? $metodos_txt[(string) $res['desempate_metodo']] : strtolower((string) $res['desempate_metodo'])) ?></div>
        <?php elseif ($p1 === $p2): ?>
          <div class="empate-linea">Empate</div>
        <?php endif; ?>
      <?php elseif ((string) $res['tipo_resultado'] === 'MARCADOR'): ?>
        <?php $a = $res['detalle'][0]; ?>
        <div class="vs-res">
          <div class="lado">
            <span class="nombre"><?= htmlspecialchars(trim((string) $a['nombre_libre']) !== '' ? $a['nombre_libre'] : 'Equipo') ?></span>
            <span class="tato"><?= (int) $a['marcador_local'] ?> : <?= (int) $a['marcador_visita'] ?></span>
          </div>
        </div>
      <?php else: foreach ($res['detalle'] as $d):
          $pos_n = (int)$d['posicion']; ?>
        <div class="fila-pos">
          <span class="pos <?= $pos_n >= 1 && $pos_n <= 3 ? 'p'.$pos_n : '' ?>"><?= $pos_n ?></span>
          <span><?= htmlspecialchars((string) $d['nombre_libre']) ?></span>
          <?php if (!empty($d['tiempo'])): ?><span class="tiempo"><?= htmlspecialchars($d['tiempo']) ?></span><?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
      <?php if (!empty($res['observaciones'])): ?>
        <div class="empate-linea">📝 <?= htmlspecialchars((string) $res['observaciones']) ?></div>
      <?php endif; ?>
    </div>
  <?php endforeach; endif; ?>
</section>

<!-- ============ MEDALLERO ============ -->
<section class="panel" id="tab-medallero" role="tabpanel" aria-labelledby="button-medallero" tabindex="0" hidden>
  <div class="section-heading">
    <div>
      <p class="eyebrow">LO MAS ALTO DEL PODIO</p>
      <h2>Medallero</h2>
      <p>Medallas entregadas por delegación.</p>
    </div>
  </div>
  <?php $med = $medallero; if ($delegacion): $med = array_values(array_filter($medallero, function($m) use ($delegacion){ return $m['delegacion'] === $delegacion; })); endif; ?>
  <?php if (empty($med)): ?>
    <div class="vacio">Aún no se entregaron medallas<?= $delegacion ? ' que correspondan a esta delegación' : '' ?>. ¡Volvé más tarde! 🏅</div>
  <?php else: ?>
    <table class="medallero">
      <thead><tr><th>Delegación</th><th>🥇 Oro</th><th>🥈 Plata</th><th>🥉 Bronce</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($med as $idx => $m): ?>
        <tr class="<?= $idx === 0 ? 'top1' : '' ?>">
          <td><span class="rank-num"><?= $idx + 1 ?></span><?= htmlspecialchars($m['delegacion']) ?></td>
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
</section>

<div class="workspace-bottom">
  <footer>
    <img class="mascota" src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" alt="Pampito, la mascota de las olimpiadas">
    Panel público · <?= htmlspecialchars($titulo_evento) ?> · sin datos personales
  </footer>
</div>
  </div><!-- /.workspace -->
</main>

<script>
// ===== Panel público (maqueta Entrega-Fixture-Olimpiadas): comportamiento =====
(function () {
  'use strict';

  var norm = function (txt) {
    return String(txt).normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  };

  /* ---------- Tabs (ARIA, como en la maqueta) ---------- */
  var tabButtons = Array.prototype.slice.call(document.querySelectorAll('.primary-tabs [role="tab"]'));
  function setTab(id, focus) {
    tabButtons.forEach(function (button) {
      var active = button.dataset.tab === id;
      button.classList.toggle('activo', active);
      button.setAttribute('aria-selected', String(active));
      button.tabIndex = active ? 0 : -1;
      if (active && focus) button.focus();
    });
    Array.prototype.forEach.call(document.querySelectorAll('main .panel'), function (panel) {
      panel.hidden = panel.id !== id;
    });
    if (history.replaceState) history.replaceState(null, '', '#' + id);
  }
  tabButtons.forEach(function (button, index) {
    button.addEventListener('click', function () { setTab(button.dataset.tab); });
    button.addEventListener('keydown', function (event) {
      var next = null;
      if (event.key === 'ArrowRight') next = (index + 1) % tabButtons.length;
      else if (event.key === 'ArrowLeft') next = (index - 1 + tabButtons.length) % tabButtons.length;
      else if (event.key === 'Home') next = 0;
      else if (event.key === 'End') next = tabButtons.length - 1;
      if (next === null) return;
      event.preventDefault();
      setTab(tabButtons[next].dataset.tab, true);
    });
  });
  var initialTab = location.hash.replace('#', '');
  if (['tab-fixture', 'tab-resultados', 'tab-medallero'].indexOf(initialTab) < 0) {
    initialTab = 'tab-fixture';
  }
  setTab(initialTab);

  /* ---------- Fixture: filtro por día y búsqueda (sin recargar) ---------- */
  var schedule = document.getElementById('schedule');
  var dayNav = document.querySelector('.day-nav');
  var searchBox = document.getElementById('search');
  var emptyState = document.getElementById('search-empty');
  var counter = document.getElementById('fixture-count');
  var expandBtn = document.getElementById('expand-disciplines');
  if (!schedule) return;

  var dayGroups = Array.prototype.slice.call(schedule.querySelectorAll('.day-group'));
  var selectedDay = '';
  (function () {
    var pressed = dayNav ? dayNav.querySelector('[aria-pressed="true"]') : null;
    selectedDay = pressed ? pressed.dataset.date : 'all';
  })();

  // Clave normalizada de cada disciplina para el ancla #disciplina-...
  Array.prototype.forEach.call(schedule.querySelectorAll('.discipline-group'), function (d) {
    d.dataset.discipline = norm(d.dataset.discipline || '');
  });

  function cardText(card) {
    return norm(card.textContent);
  }

  function applyFilters(keepOpen) {
    var query = searchBox ? norm(searchBox.value) : '';
    var totalCards = 0, totalDisciplines = 0, visibleDays = 0;

    dayGroups.forEach(function (group) {
      var dayVisible = (selectedDay === 'all') || (group.dataset.date === selectedDay);
      group.hidden = !dayVisible;
      if (!dayVisible) return;
      var groupCards = 0, groupDisciplines = 0;

      Array.prototype.forEach.call(group.querySelectorAll('.discipline-group'), function (details) {
        var cards = Array.prototype.slice.call(details.querySelectorAll('.cards > .partido, .cards > .mass-group'));
        var matched = 0;
        cards.forEach(function (card) {
          var ok = !query || cardText(card).indexOf(query) >= 0;
          card.hidden = !ok;
          if (ok) matched++;
        });
        details.hidden = matched === 0;
        if (matched > 0) {
          groupCards += matched; groupDisciplines++;
          // Con búsqueda activa todo se despliega (como en la maqueta); al limpiar,
          // se cierra salvo la disciplina que el usuario había abierto.
          if (query) details.open = true;
          else if (!keepOpen) details.open = details.classList.contains('user-open') || details.classList.contains('abierta-inicial');
        }
      });

      if (groupCards > 0) { visibleDays++; totalCards += groupCards; totalDisciplines += groupDisciplines; }
      else { group.hidden = true; }
    });

    if (emptyState) emptyState.hidden = totalCards > 0;
    if (counter) {
      var pressed = dayNav ? dayNav.querySelector('[aria-pressed="true"]') : null;
      var scope = pressed ? (pressed.dataset.day === 'all' ? 'Todos los días' : pressed.getAttribute('aria-label')) : 'Sin competencias';
      counter.textContent = scope + ' · ' + totalDisciplines + ' ' + (totalDisciplines === 1 ? 'disciplina' : 'disciplinas')
        + ' · ' + totalCards + ' ' + (totalCards === 1 ? 'competencia' : 'competencias');
    }
    syncExpandButton();
  }

  function syncExpandButton() {
    if (!expandBtn) return;
    var disclosures = Array.prototype.slice.call(schedule.querySelectorAll('.discipline-group:not([hidden])'));
    var allOpen = disclosures.length > 0 && disclosures.every(function (d) { return d.open; });
    expandBtn.innerHTML = allOpen
      ? 'Plegar disciplinas <span aria-hidden="true">↑</span>'
      : 'Desplegar disciplinas <span aria-hidden="true">↓</span>';
  }

  // Recordar qué disciplinas abrió/cerró el usuario (persiste al cambiar de día).
  Array.prototype.forEach.call(schedule.querySelectorAll('.discipline-group'), function (details) {
    details.addEventListener('toggle', function () {
      details.classList.toggle('user-open', details.open);
      syncExpandButton();
    });
  });

  if (dayNav) {
    dayNav.addEventListener('click', function (event) {
      var button = event.target.closest ? event.target.closest('.day-button') : null;
      if (!button) return;
      Array.prototype.forEach.call(dayNav.querySelectorAll('.day-button'), function (b) {
        b.setAttribute('aria-pressed', String(b === button));
      });
      selectedDay = button.dataset.date;
      applyFilters(true);
      updateURL();
    });
  }

  var searchTimer = null;
  if (searchBox) {
    searchBox.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () { applyFilters(true); updateURL(); }, 150);
    });
  }

  var clearBtn = document.getElementById('clear-search');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      if (searchBox) searchBox.value = '';
      if (dayNav) {
        var all = dayNav.querySelector('.all-days');
        if (all) all.click();
      }
    });
  }

  if (expandBtn) {
    expandBtn.addEventListener('click', function () {
      var disclosures = Array.prototype.slice.call(schedule.querySelectorAll('.discipline-group:not([hidden])'));
      var openAll = !disclosures.every(function (d) { return d.open; });
      disclosures.forEach(function (d) { d.open = openAll; d.classList.toggle('user-open', openAll); });
      syncExpandButton();
    });
  }

  /* ---------- URL: mantener ?dia= / ?q= compartibles ---------- */
  function updateURL() {
    if (!window.history || !history.replaceState) return;
    var url = new URL(location.href);
    var pressed = dayNav ? dayNav.querySelector('[aria-pressed="true"]') : null;
    if (!pressed || pressed.dataset.date === 'all') url.searchParams.delete('dia');
    else url.searchParams.set('dia', pressed.dataset.day);
    if (searchBox && searchBox.value.trim() !== '') url.searchParams.set('q', searchBox.value.trim());
    else url.searchParams.delete('q');
    history.replaceState(null, '', url.toString() + (url.hash || ''));
  }

  /* ---------- Filtros server-side → URL limpia (sin query string) ----------
     Delegación / Deporte / Categoría cambian datos del servidor: el JS arma la
     ruta /panel-publico/delegacion/<nombre>/deporte/<id>/categoria/<id>
     /dia/<clave>/buscar/<q> y navega con location.href. La barra queda legible
     y compartible, sin "?a=b&c=d". */
  var selDep = document.getElementById('filtroDeporte');
  var selCat = document.getElementById('filtroCategoria');
  var selDel = document.getElementById('filtroDelegacion');
  var filtersForm = document.getElementById('filters-form');

  var PANEL_BASE = (dayNav ? dayNav.getAttribute('data-base') : '') || '';

  // Estado actual de los filtros "de servidor" (los selects) y de los
  // "de cliente" (día y búsqueda), para poder rearmar la ruta completa.
  function estadoFiltrosRuta(sobrescribir) {
    var s = {
      delegacion: selDel ? selDel.value : '',                       // nombre crudo (se sluguea abajo)
      deporte:    selDep ? selDep.value : '',
      categoria:  selCat ? selCat.value : '',
      dia:        window.__diaKeyActual || 'all',
      buscar:     searchBox ? searchBox.value.trim() : ''
    };
    if (sobrescribir) for (var k in sobrescribir) s[k] = sobrescribir[k];
    return s;
  }

  // "San Miguel de Tucumán" → "san-miguel-de-tucuman" (solo ASCII seguro:
  // CI filtra la URI por permitted_uri_chars = a-z 0-9 ~ % . _ : -).
  function slug(txt) {
    try { txt = String(txt).normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch (e) {}
    return String(txt).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }

  function urlLimpia(s) {
    var seg = [];
    if (s.delegacion) seg.push('delegacion', slug(s.delegacion));
    if (s.deporte)    seg.push('deporte', encodeURIComponent(s.deporte));
    if (s.categoria)  seg.push('categoria', encodeURIComponent(s.categoria));
    if (s.dia && s.dia !== 'all') seg.push('dia', encodeURIComponent(s.dia));
    if (s.buscar)     seg.push('buscar', encodeURIComponent(slug(s.buscar)));
    var url = PANEL_BASE + (seg.length ? '/' + seg.join('/') : '');
    var tab = document.querySelector('.primary-tabs [role="tab"].activo');
    return url + (tab ? '#' + tab.dataset.tab : '');
  }

  function navegarFiltros(s) {
    var url = urlLimpia(s);
    // Si el usuario solo cambió día/búsqueda, no recargar: aplicar en cliente.
    if (url === location.pathname + location.search + location.hash) return;
    location.href = url;
  }

  function submitFilters(overrides) {
    if (!PANEL_BASE) { if (filtersForm) filtersForm.submit(); return; }
    navegarFiltros(estadoFiltrosRuta(overrides));
  }

  if (selDep && selCat) {
    Array.prototype.forEach.call(selCat.options, function (o) {
      if (o.value !== '') o.dataset.baseLabel = o.textContent.trim();
    });
    var aplicarCascada = function (enviar) {
      var dep = selDep.value;
      var visibleSel = false;
      Array.prototype.forEach.call(selCat.options, function (o) {
        if (o.value === '') return; // "Todas" siempre visible
        var ok = (dep === '' || o.dataset.deporte === dep);
        o.hidden = !ok;
        o.disabled = !ok;
        if (o.dataset.baseLabel) {
          var base = o.dataset.baseLabel;
          var paren = base.lastIndexOf(' (');
          o.textContent = (dep === '' || paren < 0) ? base : base.slice(0, paren);
        }
        if (o.selected && ok) visibleSel = true;
      });
      if (selCat.value !== '' && !visibleSel) selCat.value = '';
      if (enviar) submitFilters();
    };
    window.onFiltroDeporte = function () { aplicarCascada(true); };
    aplicarCascada(false);
    selDep.addEventListener('change', function () { aplicarCascada(true); });
    selCat.addEventListener('change', submitFilters);
  }
  if (selDel) selDel.addEventListener('change', submitFilters);

  applyFilters(false);
})();
</script>
</body>
</html>

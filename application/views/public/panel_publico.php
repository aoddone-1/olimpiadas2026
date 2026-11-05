<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ===== Panel público: URL SIEMPRE limpia =====
   La barra de direcciones solo muestra la ruta base (/publico o
   /publico). Ningún filtro viaja por la URL: ni query string (?dia=&q=)
   ni segmentos (/dia/viernes-06-11), y tampoco el hash de las pestañas.
   - Delegación / Deporte / Categoría: se envían por POST desde el formulario.
   - Día y búsqueda: se aplican en el cliente (todo el fixture se renderiza
     oculto y el JS muestra lo que corresponde). */
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
<link rel="stylesheet" href="<?= base_url('css/panel-publico-theme.css') ?>?v=20261104b">
<style>
/* Ajustes locales de la integración (solo esta vista). */
.venue-name{display:inline}
button.reset-button{font:inherit;line-height:inherit}
.discipline-group[open] .discipline-arrow svg{transform:rotate(180deg)}
@media(max-width:680px){.bottom-note a{margin-left:0}}
.day-group[hidden]{display:none}
.discipline-group[hidden]{display:none}
.mass-group[hidden],.partido[hidden]{display:none}

/* Estilos para que los resultados funcionen de forma idéntica al fixture (plegados por defecto) */
.resultado-details {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 12px;
  margin-bottom: 12px;
  overflow: hidden;
}
.resultado-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  cursor: pointer;
  list-style: none;
  background: var(--card2);
  transition: background 0.2s;
}
.resultado-summary::-webkit-details-marker { display: none; }
.resultado-summary:hover { background: rgba(255,255,255,0.04); }
.resultado-details[open] .resultado-summary { border-bottom: 1px solid var(--line); }
.resultado-body { padding: 14px; }
.resultado-details[open] .discipline-arrow svg{transform:rotate(180deg)}

/* ============================================================
   NOMBRES DE DELEGACIÓN (deportes de equipo) en pantallas chicas
   ============================================================ */
.equipo,
.vs .equipo,
.vs-res .nombre,
.p-nombre,
.p-eq,
.p-j-nombre{
  word-break:keep-all;
  overflow-wrap:break-word;
  hyphens:none;
  -webkit-hyphens:none;
}

.eq-nom{display:inline}
.abr-corta{display:none}
@media (max-width:680px){
  .nombre-larga{display:none}
  .abr-corta{display:inline}
  .vs .equipo .abr-corta,.p-eq .abr-corta,.p-nombre .abr-corta,
  .vs-res .nombre .abr-corta{font-size:15px;letter-spacing:.2px}
}

@media (max-width:680px){
  .cards{gap:9px}
  .partido{padding:13px 11px}
  .vs{gap:5px}
  .vs .equipo,.equipo{font-size:13.5px;line-height:1.32}
  .marcador{font-size:19px;padding:0 2px}
  .vs-res{gap:6px}
  .vs-res .lado{gap:5px}
  .vs-res .tato{font-size:19px}
  .p-medalla{padding:.7rem .4rem}
  .p-partido,.p-j-fila{gap:6px}
  .p-fase{min-width:62px}
  
  /* MODIFICACIÓN: Podio uno abajo del otro en pantallas pequeñas */
  .podio-puestos {
    grid-template-columns: 1fr !important;
  }
}
</style>
<style disabled id="estilos-clasicos-panel">
:root{
  --bg:#0d1b2a; --card:#14263b; --card2:#1b3050; --line:#274066;
  --txt:#eaf1f9; --muted:#8fa8c4; --acento:#84cc16; --ok:#2dd4a7;
  --verde-100:#ecfccb; --verde-200:#d9f99d; --verde-300:#bef264; --verde-400:#a3e635;
  --verde-500:#84cc16; --verde-600:#65a30d; --verde-700:#4d7c0f; --verde-800:#3f6212;
  --oro:#d9f99d; --plata:#cfd8e3; --bronce:#d9915b;
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:linear-gradient(160deg,#0d1b2a 0%,#10233a 60%,#0d1b2a 100%);color:var(--txt);
     font-family:'Segoe UI',system-ui,-apple-system,sans-serif;min-height:100vh}
.wrap{max-width:1200px;margin:0 auto;padding:16px}
header{text-align:center;padding:22px 10px 10px}
.titulo-row{display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap}
header h1{font-size:clamp(20px,3.6vw,32px);letter-spacing:1px;line-height:1.25;text-align:left}
header p.sitio{color:var(--txt);margin-top:6px;font-size:14px;font-weight:600;opacity:.85}
header p.lugar{display:inline-block;margin-top:8px;background:var(--card);border:1px solid var(--line);
               border-radius:20px;padding:4px 14px;color:var(--acento);font-size:13px;font-weight:700}
header p.sub{color:var(--muted);margin-top:6px;font-size:14px}
.partido.masivo{background:linear-gradient(150deg,var(--card) 0%,#173a5e 100%)}
.masivo-aviso{margin-top:2px;text-align:center;font-size:13px;color:var(--muted);font-style:italic}
.badge.MASIVA{background:#123c52;color:#7fd8ff;margin-left:6px}
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
.filtros label { font-size: 13px; color: var(--muted); font-weight: 500; }
.filtros select {
  background: var(--card2); color: var(--txt); border: 1px solid var(--line);
  border-radius: 8px; padding: 8px 12px; font-size: 15px; width: 100%; min-width: 180px; cursor: pointer;
}
.filtros a.limpiar {
  color: var(--acento); text-decoration: none; font-size: 14px; padding: 8px 16px;
  border: 1px solid var(--line); border-radius: 8px; align-self: flex-end;
}
.chip-delegacion{display:inline-block;background:var(--acento);color:#111;border-radius:20px;
                 padding:4px 14px;font-weight:700;font-size:14px}
.resumen{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:18px}
.stat{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px;text-align:center}
.stat b{display:block;font-size:28px;color:var(--acento)}
.stat span{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.tabs{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:16px}
.tab-btn{background:var(--card);border:1px solid var(--line);color:var(--txt);padding:10px 22px;
         border-radius:30px;font-size:15px;cursor:pointer;transition:.15s}
.tab-btn:hover{border-color:var(--acento)}
.tab-btn.activo{background:var(--acento);color:#111;font-weight:700;border-color:var(--acento)}
.panel-tab{display:none}
.panel-tab.visible{display:block}
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
.fase-header{margin:-12px -14px 10px;padding:6px 14px;font-size:12px;font-weight:800;
             letter-spacing:1.2px;text-transform:uppercase;text-align:center;
             background:linear-gradient(90deg, rgba(132,204,22,.35), rgba(132,204,22,.10));
             color:var(--acento);border-bottom:1px solid rgba(132,204,22,.45);
             border-radius:12px 12px 0 0}
.vs{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:14px}
.vs .equipo{flex:1;min-width:0;text-align:center;font-weight:600;line-height:1.25;
            word-break:keep-all;overflow-wrap:break-word;hyphens:none}
.vs .marcador{font-size:20px;font-weight:800;color:var(--acento);white-space:nowrap;padding:0 6px}
.vs .vsx{color:var(--muted);font-size:12px}
.ganador{color:var(--ok)}
.pendiente{color:var(--muted);font-style:italic;font-weight:400 !important}
.categoria-linea{font-size:12px;color:var(--muted);margin-top:8px}
.deporte-chip{display:inline-block;background:rgba(132,204,22,.14);border:1px solid rgba(132,204,22,.45);
              color:var(--acento);font-weight:700;font-size:12px;letter-spacing:.5px;text-transform:uppercase;
              border-radius:8px;padding:2px 10px}
.hora-inicio{color:var(--txt);font-weight:600}
.resultado-card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:12px}
.resultado-card h4{font-size:15px;margin-bottom:2px}
.resultado-card .sub{font-size:12px;color:var(--muted);margin-bottom:10px}
.fila-pos{display:flex;align-items:center;gap:10px;padding:6px 8px;border-radius:8px;font-size:14px}
.fila-pos:nth-child(odd){background:var(--card2)}
.pos{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;
     font-weight:800;font-size:13px;background:var(--line);flex-shrink:0}
.tiempo{margin-left:auto;font-family:Consolas,monospace;color:var(--ok);font-weight:700}
.marcador-res{margin-left:auto;font-weight:800;color:var(--acento)}
.res-header{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.res-categoria{font-size:13px;color:var(--txt);opacity:.85;font-weight:600}
.vs-res{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 4px 2px}
.vs-res .lado{flex:1;display:flex;align-items:center;gap:8px;min-width:0}
.vs-res .lado.derecho{justify-content:flex-end;text-align:right}
.vs-res .nombre{font-weight:600;line-height:1.2;word-break:keep-all;overflow-wrap:break-word;hyphens:none}
.vs-res .tato{font-size:22px;font-weight:800;color:var(--acento);white-space:nowrap}
.ganador-linea{margin-top:8px;font-size:13px;font-weight:700;color:var(--ok)}
.empate-linea{margin-top:8px;font-size:13px;color:var(--muted);font-style:italic}
.podio-grupo{margin-bottom:22px}
.podio-dep-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;
  background:linear-gradient(135deg,#16324f 0%,#1d4b77 100%);
  border:1px solid var(--line);border-radius:12px;padding:10px 16px;margin-bottom:12px;
  box-shadow:0 3px 10px rgba(0,0,0,.25)}
.podio-dep-icon{width:2.6rem;height:2.6rem;border-radius:.65rem;display:flex;align-items:center;
  justify-content:center;font-size:1.5rem;background:rgba(255,255,255,.1);
  border:1px solid rgba(255,255,255,.2)}
.podio-dep-nombre{font-size:1.15rem;font-weight:800;letter-spacing:.03em;text-transform:uppercase}
.podio-dep-meta{font-size:.78rem;color:var(--muted)}
.podio-cat{background:var(--card);border:1px solid var(--line);border-radius:12px;
  padding:14px;margin-bottom:12px}
.podio-cat-head{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:4px}
.podio-cat-nombre{font-size:15px;font-weight:800}
.podio-cat-regla{font-size:12px;color:var(--muted)}
.podio-cerrada-tag{margin-left:auto;font-size:11px;font-weight:700;color:var(--ok);
  border:1px solid var(--ok);border-radius:.4rem;padding:.05rem .5rem}
.podio-puestos{display:grid;grid-template-columns:repeat(var(--puestos,3),1fr);gap:.6rem;margin:12px 0 4px}
.p-medalla{border-radius:.8rem;padding:.8rem .6rem;text-align:center;border:2px solid transparent;
  background:var(--card2);display:flex;flex-direction:column;gap:.25rem;align-items:center;min-height:112px;justify-content:center}
.p-disco{font-size:1.7rem;line-height:1}
.p-label{font-size:.68rem;letter-spacing:.06em;text-transform:uppercase;font-weight:700;color:var(--muted)}
.p-nombre{font-weight:800;word-break:keep-all;overflow-wrap:break-word;hyphens:none}
.p-extra{font-size:.72rem;color:var(--muted)}
.p-desemp{font-size:.72rem;color:var(--oro);font-weight:700}
.p-oro{border-color:var(--oro);background:linear-gradient(180deg,rgba(217,249,157,.14),rgba(217,249,157,.05))}
.p-plata{border-color:var(--plata);background:linear-gradient(180deg,rgba(207,216,227,.14),rgba(207,216,227,.05))}
.p-bronce{border-color:var(--bronce);background:linear-gradient(180deg,rgba(217,145,91,.14),rgba(217,145,91,.05))}
.p-vacio{border-style:dashed;border-color:var(--line)}
.p-podio-box{margin:10px 0 4px;padding:10px 10px 6px;border-radius:.9rem;
  background:linear-gradient(180deg,rgba(255,255,255,.05),rgba(255,255,255,.02));
  border:1px solid var(--line)}
.p-podio-label{display:flex;align-items:center;gap:6px;font-size:.72rem;font-weight:800;
  letter-spacing:.06em;text-transform:uppercase;color:var(--acento);margin-bottom:2px}
.p-partidos{margin-top:12px;border-top:1px dashed var(--line);padding-top:8px}
.p-partidos-titulo{font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;
  font-weight:700;color:var(--muted);margin-bottom:6px}
.p-partido{display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:10px;
  background:var(--card2);margin-bottom:6px;font-size:14px}
.p-partido.def{border:1px solid rgba(132,204,22,.45);background:rgba(132,204,22,.08)}
.p-fase{flex-shrink:0;font-size:.68rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;
  color:var(--muted);background:var(--line);border-radius:.4rem;padding:.15rem .5rem;min-width:74px;text-align:center}
.p-eq{flex:1;min-width:0;word-break:keep-all;overflow-wrap:break-word;hyphens:none}
.p-eq.dch{text-align:right}
.p-eq.ganador{font-weight:800}
.p-tato{font-size:18px;font-weight:800;color:var(--acento);white-space:nowrap;padding:0 4px}
.p-metas{font-size:.72rem;color:var(--muted);white-space:nowrap}
.p-jornada{border:1px solid var(--line);border-radius:10px;background:var(--card2);
  margin-top:8px;overflow:hidden}
.p-j-head{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:8px 12px;cursor:pointer;
  font-size:13px;font-weight:700}
.p-j-head:hover{background:rgba(255,255,255,.04)}
.p-j-meta{font-size:.72rem;color:var(--muted);font-weight:600;margin-left:auto;white-space:nowrap}
.p-j-official{font-size:.65rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;
  color:#111;background:var(--oro);border-radius:.4rem;padding:.1rem .45rem}
.p-j-flecha{color:var(--muted);font-size:.8rem;transition:transform .15s ease}
.p-jornada.abierta .p-j-flecha{transform:rotate(90deg)}
.p-j-cuerpo{display:none;padding:2px 10px 10px}
.p-jornada.abierta .p-j-cuerpo{display:block}
.p-j-fila{display:flex;align-items:center;gap:10px;padding:5px 8px;border-radius:8px;font-size:14px}
.p-j-fila:nth-child(odd){background:rgba(255,255,255,.03)}
.p-j-fila.podio{background:rgba(132,204,22,.08);border:1px solid rgba(132,204,22,.25)}
.p-j-nombre{flex:1;min-width:0;word-break:keep-all;overflow-wrap:break-word;hyphens:none}
.p-j-marca{font-family:Consolas,monospace;font-weight:700;color:var(--ok);white-space:nowrap}
.p-j-puntos{font-weight:800;color:var(--acento);white-space:nowrap}
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

.titulo-row{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:18px;
  flex-wrap:nowrap;
}
.titulo-row .logo-evento{
  height:clamp(140px, 18vw, 220px);
  width:auto;
  object-fit:contain;
  flex-shrink:0;
  filter:drop-shadow(0 4px 14px rgba(0,0,0,.45));
}
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
    <button type="submit" class="reset-button" form="filters-form" name="limpiar" value="1">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10a8 8 0 1 1 1.8 7.1M4 4v6h6"/></svg>Limpiar filtros
    </button>
    <?php endif; ?>
  </div>
  <form method="post" action="<?= site_url('publico') ?>" id="filters-form" class="filters-form">
    <div class="filter-field">
      <label for="filtroDelegacion">Delegación</label>
      <select name="delegacion" id="filtroDelegacion" <?= $delegacion !== '' ? 'class="has-filter"' : '' ?>>
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
  </nav>

<?php
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

$letra_grupo = function ($n) {
    static $abecedario = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
                               'N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    if ($n >= 1 && $n <= count($abecedario)) return $abecedario[$n - 1];
    return chr(64 + intdiv($n - 1, 26)) . '-' . $abecedario[($n - 1) % 26];
};
$grupos_idx = array();
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
$grupo_etiqueta = array();
foreach ($grupos_idx as $clave => $g) {
    $num_f = (int) substr($clave, strpos($clave, '|') + 1);
    $grupo_etiqueta[$clave] = ($g['todas_grupo'] && $g['hay_grupo'] && $num_f > 0)
        ? 'Grupo ' . $letra_grupo($num_f)
        : null;
}

$dias_es = array(0=>'Domingo',1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado');
$dias_curtos = array(0=>'Dom',1=>'Lun',2=>'Mar',3=>'Mier',4=>'Jue',5=>'Vier',6=>'Sáb');

$lower_name = function ($txt) {
    $t = trim((string) $txt);
    if ($t === '') return '';
    if (preg_match('/^(?!.*[a-záéíóúüñ])[A-ZÁÉÍÓÚÜÑ0-9&\/\x{b4}\s\.\-\(\)]+$/u', $t)) return $t;
    $out = preg_replace_callback('/[\p{L}\p{M}]+/u', function ($m) {
        $w = $m[0];
        if (mb_strlen($w, 'UTF-8') <= 3) return $w;
        return mb_strtolower(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($w, 1, null, 'UTF-8');
    }, $t);
    return trim($out);
};

$rango_horas = function ($horas) {
    $hs = array_values(array_unique(array_filter($horas)));
    sort($hs);
    if (!$hs) return 'Horario a confirmar';
    return count($hs) === 1 ? $hs[0] : $hs[0] . '–' . $hs[count($hs) - 1];
};

$ABR_DISPATADOS = array('de', 'del', 'la', 'las', 'el', 'los', 'y', 'e');
$abrev_provincia = function ($txt) use ($ABR_DISPATADOS) {
    $t = trim((string) preg_replace('/\s+/u', ' ', (string) $txt));
    if ($t === '') return '';
    $palabras = preg_split('/[\s\-\/]+/u', $t);
    $palabras = is_array($palabras) ? array_values(array_filter($palabras, function ($w) {
        return $w !== '' && $w !== null;
    })) : array();
    if (!$palabras || mb_strlen($t, 'UTF-8') <= 4) return $t;
    if (count($palabras) === 1) {
        return mb_substr($t, 0, 3, 'UTF-8');
    }
    $sigla = '';
    foreach ($palabras as $i => $p) {
        $letra = mb_substr($p, 0, 1, 'UTF-8');
        $es_conector = in_array(mb_strtolower($p, 'UTF-8'), $ABR_DISPATADOS, true);
        $sigla .= ($i > 0 && $es_conector) ? mb_strtolower($letra, 'UTF-8') : mb_strtoupper($letra, 'UTF-8');
    }
    return $sigla;
};
$eq_nombre_html = function ($full) use ($abrev_provincia) {
    $f = trim(preg_replace('/\s+/u', ' ', (string) $full));
    if ($f === '') return '';
    if (mb_strlen($f, 'UTF-8') <= 9) return htmlspecialchars($f);
    $abr = $abrev_provincia($f);
    $attrs = ' class="eq-nom" data-full="' . htmlspecialchars($f, ENT_QUOTES) . '"'
           . ' title="' . htmlspecialchars($f, ENT_QUOTES) . '"';
    $corta = ($abr !== '' && strcasecmp($abr, $f) !== 0)
        ? '<span class="abr-corta">' . htmlspecialchars($abr) . '</span>'
        : '';
    return '<span' . $attrs . '>'
         . '<span class="nombre-larga">' . htmlspecialchars($f) . '</span>'
         . $corta . '</span>';
};
$eq_nombre_txt = function ($full) {
    $f = trim(preg_replace('/\s+/u', ' ', (string) $full));
    return $f === '' ? 'Equipo' : htmlspecialchars($f);
};

$svg_disciplina = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20V5m0 0c5-4 9 4 14 0v9c-5 4-9-4-14 0"/></svg>';
$svg_pin        = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>';
$svg_search     = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>';
$svg_chevron    = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
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
  $dias_disp = array();
  foreach ($fixture as $fx0) {
      $f0 = (string) $fx0['fecha_competencia'];
      if ($f0 === '' || isset($dias_disp[$f0])) continue;
      $ts0 = strtotime($f0);
      $dias_disp[$f0] = $ts0
          ? array('full'  => $dias_es[(int) date('w', $ts0)] . ' ' . date('d/m', $ts0),
                  'short' => $dias_curtos[(int) date('w', $ts0)])
          : array('full' => $f0, 'short' => $f0);
  }
  $dia_sel = !empty($dias_disp) ? (string) array_key_first($dias_disp) : 'all';
  $q_uri = '';
  $disc_plana = '';
  $disc_fecha = '';
  ?>
  <div class="schedule-tools">
    <nav class="day-nav" id="day-nav" aria-label="Filtrar por día">
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
      <a class="secondary-button" href="<?= site_url('publico') ?>">Ver todas las competencias</a>
    </div>
  <?php else: ?>
    <?php
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
              <span class="discipline-name"><?= htmlspecialchars($dep_key) ?></span>
              <span class="discipline-description"><?= htmlspecialchars($desc_d) ?></span>
            </span>
            <span class="discipline-count"><?= $n_items . ' ' . ($n_items === 1 ? 'competencia' : 'competencias') ?></span>
            <span class="discipline-arrow" aria-hidden="true"><?= $svg_chevron ?></span>
          </summary>
          <div class="discipline-body">
            <?php
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
            <div class="cards">
            <?php foreach ($items as $idx => $fx):
                $mar = $fx['marcador'];
                $eq1_nombre = $fx['ute_1_nombre'] ? $eq_nombre_html($fx['ute_1_nombre']) : '<span class="pendiente">Por definir</span>';
                $eq2_nombre = $fx['ute_2_nombre'] ? $eq_nombre_html($fx['ute_2_nombre']) : '<span class="pendiente">Por definir</span>';
                $marcador_txt = '';
                $g1 = false; $g2 = false;
                if ($mar && count($mar['equipos']) >= 2) {
                    $a = $mar['equipos'][0]; $b = $mar['equipos'][1];
                    $marcador_txt = $a['a_favor'] . ' – ' . $a['en_contra'];
                    if ((int)$a['a_favor'] > (int)$a['en_contra']) $g1 = true;
                    elseif ((int)$a['en_contra'] > (int)$a['a_favor']) $g2 = true;
                    if (!empty($mar['hubo_desempate'])) { $g1 = false; $g2 = false; }
                } elseif ($fx['estado'] === 'FINALIZADO' && !empty($fx['resultado'])) {
                    $marcador_txt = 'Final';
                }

                $es_masivo = ((string) $fx['fase'] === 'JORNADA_UNICA')
                          || ((string) (isset($fx['modalidad_competencia']) ? $fx['modalidad_competencia'] : '') === 'MASIVO_TIEMPO');

                $hora_ts = strtotime('today ' . trim((string) $fx['hora_inicio']));
                $hora_txt = $hora_ts !== false ? date('H:i', $hora_ts) : substr(trim((string) $fx['hora_inicio']), 0, 5);

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
                <details>
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
    </div>
    <div id="search-empty" class="empty-state" hidden>
      <div class="empty-icon" aria-hidden="true">⌕</div>
      <h3>No encontramos competencias</h3>
      <p>Probá con otra búsqueda o cambiá los filtros.</p>
      <button type="button" class="secondary-button" id="clear-search">Ver todas las competencias</button>
    </div>
  <?php endif; ?>
</section>

<!-- ============ RESULTADOS (AHORA PLEGADOS POR DEFECTO) ============ -->
<section class="panel" id="tab-resultados" role="tabpanel" aria-labelledby="button-resultados" tabindex="0" hidden>
  <div class="section-heading">
    <div>
      <p class="eyebrow">CADA ENCUENTRO CUENTA</p>
      <h2>Resultados</h2>
      <p>Marcadores y posiciones por competencia. Hacé clic en un deporte para desplegarlo.</p>
    </div>
  </div>
  <?php if (empty($resultados)): ?>
    <div class="vacio">Todavía no hay resultados cargados<?= $delegacion ? ' para esta delegación' : '' ?><?= !empty($id_categoria) ? ' en esta categoría' : '' ?>.</div>
  <?php else:
      $podios = isset($podios) && is_array($podios) ? $podios : array();
      $cats_en_podio = array();
      foreach ($podios as $pd) $cats_en_podio[(int) $pd['id_categoria']] = true;

      $icono_deporte = function ($nombre) {
          $n = strtolower(trim((string) $nombre));
          $mapa = array(
              'futbol' => '⚽', 'fútbol' => '⚽', 'handball' => '🤾', 'handbol' => '🤾',
              'basquet' => '🏀', 'basket' => '🏀', 'básquet' => '🏀', 'basquetbol' => '🏀',
              'voley' => '🏐', 'vóley' => '🏐', 'voleibol' => '🏐','newcom' => '🏐','pool' => '🎱',
              'padel' => '🎾', 'ping pong' => '🏓', 'mesa' => '🏓','tenis' => '🎾', 
              'rugby' => '🏉', 'hockey' => '🏑', 'beisbol' => '⚾', 'softbol' => '⚾',
              'atletismo' => '🏃', 'natacion' => '🏊', 'natación' => '🏊','running' => '🏃',
              'boxeo' => '🥊', 'lucha' => '🤼', 'judo' => '🥋', 'karate' => '🥋','carrera' => '🏃',
              'escrime' => '🤺', 'ciclismo' => '🚴','MTB' => '🚴','mtb' => '🚴', 'patin' => '⛸️', 'truco'=> '🃏'
          );
          foreach ($mapa as $k => $ico) if (strpos($n, $k) !== false) return $ico;
          return '🏅';
      };
      $disco_puesto = function ($puesto, $icono_regla) {
          if ($icono_regla !== '') return $icono_regla;
          return array(1 => '🥇', 2 => '🥈', 3 => '🥉')[$puesto] ?? '🏅';
      };
      $clase_puesto = function ($puesto) {
          return array(1 => 'p-oro', 2 => 'p-plata', 3 => 'p-bronce')[$puesto] ?? '';
      };
      $norm_eq = function ($s) {
          $s = strtolower(trim((string) $s));
          $map = array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n');
          $s = strtr($s, $map);
          $s = preg_replace('/\s+/', ' ', $s);
          return $s;
      };

      $grupos_dep = array();
      foreach ($podios as $pd) {
          $dep_clave = $pd['deporte'] !== '' ? $pd['deporte'] : 'Otros';
          $fecha_dia = !empty($pd['fecha_entrega']) ? substr((string) $pd['fecha_entrega'], 0, 10) : '';
          if ($fecha_dia === '') {
              $dias_int = array();
              foreach (isset($pd['partidos']) && is_array($pd['partidos']) ? $pd['partidos'] : array() as $pt_aux) {
                  if (!empty($pt_aux['fecha'])) $dias_int[] = substr((string) $pt_aux['fecha'], 0, 10);
              }
              foreach (isset($pd['jornadas']) && is_array($pd['jornadas']) ? $pd['jornadas'] : array() as $j_aux) {
                  if (!empty($j_aux['fecha'])) $dias_int[] = substr((string) $j_aux['fecha'], 0, 10);
              }
              if ($dias_int) $fecha_dia = max($dias_int);
          }
          $clave = $dep_clave . '|' . $fecha_dia;
          if (!isset($grupos_dep[$clave])) {
              $grupos_dep[$clave] = array('deporte' => $dep_clave, 'fecha' => $fecha_dia, 'items' => array());
          }
          $grupos_dep[$clave]['items'][] = $pd;
      }

      foreach ($grupos_dep as $grupo_dep):
          $dep_nombre = $grupo_dep['deporte'];
          $items_dep = $grupo_dep['items']; 
          $clave_disc_res = $norm($dep_nombre);
  ?>
    <!-- Cada grupo de deporte de podio ahora usa <details> y <summary> para aparecer plegado por defecto -->
    <details class="resultado-details discipline-group" data-discipline="<?= htmlspecialchars($clave_disc_res) ?>">
      <summary class="resultado-summary discipline-summary">
        <span class="discipline-symbol" aria-hidden="true"><?= $icono_deporte($dep_nombre) ?></span>
        <span class="discipline-label">
          <span class="discipline-name"><?= htmlspecialchars($dep_nombre) ?></span>
          <span class="discipline-description"><?= count($items_dep) ?> categoría<?= count($items_dep) !== 1 ? 's' : '' ?></span>
        </span>
        <span class="discipline-count"><?= count($items_dep) ?> competencias</span>
        <span class="discipline-arrow" aria-hidden="true"><?= $svg_chevron ?></span>
      </summary>
      <div class="resultado-body">
        <?php foreach ($items_dep as $pd):
            $puestos_pd = array_keys($pd['podium']);
            sort($puestos_pd);
            $en_podio = array();
            if (empty($pd['masivo'])) {
                foreach ($pd['podium'] as $pu) {
                    $nnm = $norm_eq($pu['nombre'] ?? '');
                    if ($nnm !== '') $en_podio[$nnm] = true;
                }
            }
            $lista_partidos = array();
            if (!empty($pd['masivo'])) {
                $jorns = array();
                foreach (isset($pd['jornadas']) && is_array($pd['jornadas']) ? $pd['jornadas'] : array() as $j_aux) {
                    $j_aux['abierta'] = false; // Jornadas internas también plegadas dentro del deporte
                    $jorns[] = $j_aux;
                }
                $pd['jornadas'] = $jorns;
            } elseif (!empty($pd['partidos'])) {
                foreach ($pd['partidos'] as $pt_aux) {
                    if (!empty($pt_aux['decisivo'])) {
                        $n1 = $norm_eq($pt_aux['eq1_nombre'] ?? '');
                        $n2 = $norm_eq($pt_aux['eq2_nombre'] ?? '');
                        if ($n1 !== '' && isset($en_podio[$n1]) && $n2 !== '' && isset($en_podio[$n2])) continue;
                    }
                    $lista_partidos[] = $pt_aux;
                }
            } ?>
        <div class="podio-cat">
          <div class="podio-cat-head">
            <span class="podio-cat-nombre"><?= htmlspecialchars($pd['categoria']) ?><?= $pd['genero'] !== '' ? ' (' . htmlspecialchars($pd['genero']) . ')' : '' ?></span>
            <span class="podio-cat-regla">⚙ <?= htmlspecialchars($pd['texto_regla']) ?></span>
            <?php if (!empty($pd['cerrada'])): ?><span class="podio-cerrada-tag">FINALIZADO</span><?php endif; ?>
          </div>
          <div class="p-podio-box">
            <div class="p-podio-label">🏅 Podio — <?= count($puestos_pd) > 3 ? 'los ' . count($puestos_pd) . ' primeros' : 'medallistas' ?></div>
            <div class="podio-puestos" data-p="<?= max(1, min(3, count($puestos_pd))) ?>" style="--puestos:<?= max(1, min(3, count($puestos_pd))) ?>">
              <?php foreach ($puestos_pd as $pos): $pu = $pd['podium'][$pos];
                  $vacio = ($pu['nombre'] === null || $pu['nombre'] === '');
                  $ico = $disco_puesto((int) $pos, (string) $pu['icono']);
              ?>
              <div class="p-medalla <?= $vacio ? 'p-vacio' : $clase_puesto((int) $pos) ?>">
                <span class="p-disco"><?= $ico ?></span>
                <span class="p-label"><?= $pu['etiqueta'] !== '' ? htmlspecialchars($pu['etiqueta']) : $pos . '° puesto' ?></span>
                <span class="p-nombre"><?= $vacio ? '—' : $eq_nombre_html((string) $pu['nombre']) ?></span>
                <?php if (!$vacio && !empty($pu['desempate'])): ?>
                  <span class="p-desemp">🏅 <?= htmlspecialchars((string) $pu['desempate']) ?></span>
                <?php elseif (!$vacio && !empty($pu['extra'])): ?>
                  <span class="p-extra"><?= htmlspecialchars((string) $pu['extra']) ?></span>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php if (!empty($pd['tabla_puntaje'])):
              $tabla_visible = array();
              foreach ($pd['tabla_puntaje'] as $i => $e) {
                  if ($i < 3) {
                      $ntab = $norm_eq($e['nombre'] ?? '');
                      if ($ntab !== '' && isset($en_podio[$ntab])) continue;
                  }
                  $tabla_visible[] = array('i' => $i, 'e' => $e);
              }
          ?>
            <?php if ($tabla_visible): ?>
            <div class="p-partidos">
              <div class="p-partidos-titulo">📋 Tabla de puntos</div>
              <?php foreach ($tabla_visible as $tv): $i = $tv['i']; $e = $tv['e']; ?>
                <div class="p-partido">
                  <span class="p-fase"><?= $i + 1 ?>°</span>
                  <span class="p-eq"><?= $eq_nombre_html((string) $e['nombre']) ?></span>
                  <span class="p-tato"><?= (int) $e['puntos'] ?></span>
                  <span class="p-metas"><?= (int) $e['pj'] ?> PJ · <?= (int) $e['pg'] ?>G <?= (int) $e['pe'] ?>E <?= (int) $e['pp'] ?>P</span>
                </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          <?elseif ($lista_partidos): ?>
            <div class="p-partidos">
              <div class="p-partidos-titulo">📅 Partidos anteriores</div>
              <?php foreach ($lista_partidos as $pt):
                  $fase_txt_local = $pt['fase'] !== null ? (isset($fases_txt[$pt['fase']]) ? $fases_txt[$pt['fase']] : $pt['fase']) : 'Partido';
                  $fecha_p = !empty($pt['fecha']) ? date('d/m', strtotime((string) $pt['fecha'])) : '';
                  $hora_p = !empty($pt['hora']) ? date('H:i', strtotime('today ' . trim((string) $pt['hora']))) : '';
                  $gano_desem_p = !empty($pt['hubo_desempate']) && !empty($pt['ganador_desempate']);
              ?>
                <div class="p-partido<?= !empty($pt['decisivo']) ? ' def' : '' ?>">
                  <span class="p-fase"><?= !empty($pt['decisivo']) ? '⭐ ' : '' ?><?= htmlspecialchars($fase_txt_local) ?></span>
                  <span class="p-eq"><?= $eq_nombre_html((string) $pt['eq1_nombre']) ?></span>
                  <span class="p-tato"><?= (int) $pt['eq1_tantos'] ?> : <?= (int) $pt['eq2_tantos'] ?></span>
                  <span class="p-eq dch"><?= $eq_nombre_html((string) $pt['eq2_nombre']) ?></span>
                  <span class="p-metas"><?= htmlspecialchars(trim($fecha_p . ' ' . $hora_p)) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($pd['jornadas'])): $n_j = 0; ?>
            <div class="p-partidos">
              <div class="p-partidos-titulo">📋 Resultados completos</div>
              <?php foreach ($pd['jornadas'] as $j):
                  $n_j++;
                  $fecha_j = !empty($j['fecha']) ? date('d/m', strtotime((string) $j['fecha'])) : '';
                  $hora_j  = !empty($j['hora']) ? date('H:i', strtotime('today ' . trim((string) $j['hora']))) : '';
                  $meta_j  = trim($fecha_j . ($hora_j !== '' ? ' · ' . $hora_j : '') . ($j['lugar'] !== '' ? ' · ' . $j['lugar'] : ''));
                  $titulo_j = $j['prueba'] !== '' ? $j['prueba'] : ('Prueba ' . $n_j);
              ?>
                <div class="p-jornada">
                  <div class="p-j-head" role="button" tabindex="0" aria-expanded="false">
                    <span class="p-j-flecha" aria-hidden="true">▶</span>
                    <span><?= htmlspecialchars($titulo_j) ?></span>
                    <span class="p-j-meta"><?= htmlspecialchars($meta_j) ?> · <?= count($j['filas']) ?> inscriptos</span>
                  </div>
                  <div class="p-j-cuerpo">
                    <?php foreach ($j['filas'] as $f): ?>
                      <div class="p-j-fila<?= !empty($f['es_podio']) ? ' podio' : '' ?>">
                        <span class="pos <?= (int) $f['posicion'] >= 1 && (int) $f['posicion'] <= 3 ? 'p' . (int) $f['posicion'] : '' ?>"><?= (int) $f['posicion'] ?></span>
                        <span class="p-j-nombre"><?= htmlspecialchars((string) $f['nombre']) ?></span>
                        <?php if (!empty($f['marca'])): ?>
                          <span class="<?= !empty($f['es_puntos']) ? 'p-j-puntos' : 'p-j-marca' ?>"><?= htmlspecialchars((string) $f['marca']) ?><?= !empty($f['es_puntos']) ? ' pts' : '' ?></span>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </details>
    <?php endforeach; ?>

    <?php 
          $otros = array_values(array_filter($resultados, function ($r) use ($cats_en_podio) {
              return !isset($cats_en_podio[(int) (isset($r['id_categoria']) ? $r['id_categoria'] : 0)]);
          }));
          if ($otros): ?>
    <div class="section-heading" style="margin-top:16px">
      <div>
        <p class="eyebrow">TIEMPO Y PUNTAJE</p>
        <h2 style="font-size:1.1rem">Resultados Parciales</h2>
      </div>
    </div>
    <?php foreach ($otros as $res):
      $dep_txt  = trim((string) (isset($res['nombre_deporte']) ? $res['nombre_deporte'] : ''));
      $cat_txt  = trim((string) (isset($res['nombre_categoria']) ? $res['nombre_categoria'] : ''));
      if ($dep_txt === '' && $cat_txt === '') $dep_txt = trim((string) (isset($res['nombre_evento']) ? $res['nombre_evento'] : ''));
      $fecha_ts = strtotime(!empty($res['fixture_fecha']) ? $res['fixture_fecha'] : (string) $res['fecha_resultado']);
      $fecha_txt = $fecha_ts ? date('d/m/Y', $fecha_ts) : trim((string) $res['fecha_resultado']);
      $hora_ts   = !empty($res['fixture_hora']) ? strtotime('today ' . trim((string) $res['fixture_hora'])) : false;
      $hora_txt  = $hora_ts !== false ? date('H:i', $hora_ts) : '';
      $fase_raw  = isset($res['fase']) ? (string) $res['fase'] : '';
      $fase_txt  = $fase_raw !== '' ? (isset($fases_txt[$fase_raw]) ? $fases_txt[$fase_raw] : $fase_raw) : '';
  ?>
    <!-- Resultados parciales individuales también envueltos en details/summary para mantener uniformidad -->
    <details class="resultado-details">
      <summary class="resultado-summary">
        <span class="discipline-symbol" aria-hidden="true"><?= $icono_deporte($dep_txt) ?></span>
        <span class="discipline-label">
          <span class="discipline-name"><?= htmlspecialchars($dep_txt !== '' ? $dep_txt : 'Resultado') ?></span>
          <span class="discipline-description"><?= htmlspecialchars($cat_txt) ?><?= $fase_txt !== '' ? ' · ' . $fase_txt : '' ?></span>
        </span>
        <span class="discipline-arrow" aria-hidden="true"><?= $svg_chevron ?></span>
      </summary>
      <div class="resultado-body">
        <div class="sub" style="margin-top:0">
          <?= htmlspecialchars($fecha_txt) ?><?= $hora_txt !== '' ? ' · 🕐 ' . htmlspecialchars($hora_txt) : '' ?><?= !empty($res['lugar']) ? ' · ' . htmlspecialchars((string) $res['lugar']) : '' ?>
        </div>
        <?php if ((string) $res['tipo_resultado'] === 'MARCADOR' && count($res['detalle']) >= 2):
            $a = $res['detalle'][0]; $b = $res['detalle'][1];
            $eq1_nombre = trim(preg_replace('/\s+/u', ' ', (string) $a['nombre_libre']));
            $eq2_nombre = trim(preg_replace('/\s+/u', ' ', (string) $b['nombre_libre']));
            $eq1_visible = $eq1_nombre !== '' ? $eq1_nombre : 'Equipo 1';
            $eq2_visible = $eq2_nombre !== '' ? $eq2_nombre : 'Equipo 2';
            $p1 = (int) $a['marcador_local'];
            $r1 = (int) $a['marcador_visita'];
            $p2 = (int) $b['marcador_local'];
            $r2 = (int) $b['marcador_visita'];
            if ($p2 === $p1 && $r2 === $r1) {
                $tmp = $p2; $p2 = $r2; $r2 = $tmp;
            }
            $hubo_desempate = !empty($res['hubo_desempate']) && !empty($res['desempate_metodo']);
            $ganador_desemp = isset($res['nombre_ganador_desempate']) ? trim((string) $res['nombre_ganador_desempate']) : '';
            $gana1 = $p1 > $p2 || ($p1 === $p2 && $hubo_desempate && $ganador_desemp !== '' && strtoupper(trim($ganador_desemp)) === strtoupper(trim((string) $eq1_nombre)));
            $gana2 = $p2 > $p1 || ($p1 === $p2 && $hubo_desempate && $ganador_desemp !== '' && strtoupper(trim($ganador_desemp)) === strtoupper(trim((string) $eq2_nombre)));
        ?>
          <div class="vs-res">
            <div class="lado">
              <span class="nombre <?= $gana1 ? 'ganador' : '' ?>"><?= $eq_nombre_html($eq1_visible) ?></span>
              <span class="tato"><?= $p1 ?></span>
            </div>
            <div class="lado derecho">
              <span class="tato"><?= $p2 ?></span>
              <span class="nombre <?= $gana2 ? 'ganador' : '' ?>"><?= $eq_nombre_html($eq2_visible) ?></span>
            </div>
          </div>
          <?php if ($gana1): ?>
            <div class="ganador-linea">🏆 Ganó <?= $eq_nombre_txt($eq1_visible) ?></div>
          <?php elseif ($gana2): ?>
            <div class="ganador-linea">🏆 Ganó <?= $eq_nombre_txt($eq2_visible) ?></div>
          <?php elseif ($p1 === $p2): ?>
            <div class="empate-linea">Empate</div>
          <?php endif; ?>
        <?php else: foreach ($res['detalle'] as $d): $pos_n = (int)$d['posicion']; ?>
          <div class="fila-pos">
            <span class="pos <?= $pos_n >= 1 && $pos_n <= 3 ? 'p'.$pos_n : '' ?>"><?= $pos_n ?></span>
            <span><?= htmlspecialchars((string) $d['nombre_libre']) ?></span>
            <?php if (!empty($d['tiempo'])): ?><span class="tiempo"><?= htmlspecialchars($d['tiempo']) ?></span><?php endif; ?>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </details>
  <?php endforeach; endif; ?>
  <?php endif; ?>
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
  <?php endif; ?>
</section>

</div>
  <div class="workspace-bottom">
  <footer>
    <img class="mascota" src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" alt="Pampito, la mascota de las olimpiadas">
    Panel público · <?= htmlspecialchars($titulo_evento) ?> · sin datos personales
  </footer>
</div>
</main>

<script>
(function () {
  'use strict';

  var norm = function (txt) {
    return String(txt).normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  };

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
    if (history.replaceState && (location.search || location.hash)) {
      try { history.replaceState(null, '', location.pathname); } catch (e) {}
    }
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
  setTab('tab-fixture');

  /* ---------- Resultados: planillas desplegables internas ---------- */
  Array.prototype.forEach.call(document.querySelectorAll('.p-j-head'), function (head) {
    var toggle = function () {
      var jornada = head.parentElement;
      var abierto = jornada.classList.toggle('abierta');
      head.setAttribute('aria-expanded', String(abierto));
    };
    head.addEventListener('click', toggle);
    head.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); toggle(); }
    });
  });

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
  }

  Array.prototype.forEach.call(schedule.querySelectorAll('.discipline-group'), function (details) {
    details.addEventListener('toggle', function () {
      details.classList.toggle('user-open', details.open);
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
    });
  }

  var searchTimer = null;
  if (searchBox) {
    searchBox.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () { applyFilters(true); }, 150);
    });
  }

  var selDep = document.getElementById('filtroDeporte');
  var selCat = document.getElementById('filtroCategoria');
  var selDel = document.getElementById('filtroDelegacion');
  var filtersForm = document.getElementById('filters-form');

  function submitFilters() {
    if (filtersForm) filtersForm.submit();
  }

  if (history.replaceState && (location.search || location.hash)) {
    try { history.replaceState(null, '', location.pathname); } catch (e) {}
  }

  if (selDep && selCat) {
    Array.prototype.forEach.call(selCat.options, function (o) {
      if (o.value !== '') o.dataset.baseLabel = o.textContent.trim();
    });
    var aplicarCascada = function (enviar) {
      var dep = selDep.value;
      var visibleSel = false;
      Array.prototype.forEach.call(selCat.options, function (o) {
        if (o.value === '') return;
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
    selDep.addEventListener('change', function () { aplicarCascada(true); });
    selCat.addEventListener('change', submitFilters);
  }
  if (selDel) selDel.addEventListener('change', submitFilters);

  applyFilters(false);
})();
</script>
</body>
</html>
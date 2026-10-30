<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titulo_evento) ?> — Panel Público</title>
<link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
<style>
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
<div class="wrap">

<header>
  <div class="titulo-row">
    <img class="logo-evento" src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" alt="Logo <?= htmlspecialchars($titulo_evento) ?>">
    
  </div>
  <p class="lugar">📍 <?= htmlspecialchars($lugar_olimpico) ?></p>
  <p class="sub">
    <?php if (!empty($delegacion)): ?>
      Viendo: <span class="chip-delegacion"><?= htmlspecialchars($delegacion) ?></span>
    <?php else: ?>
      Seguimiento en vivo del evento · actualizado <?= htmlspecialchars($ahora) ?>
    <?php endif; ?>
  </p>
</header>

<!-- Filtros -->
<form method="get" action="<?= site_url('publico') ?>" class="filtros">
  <div class="filtro-grupo">
    <label>Delegación:</label>
    <select name="delegacion" onchange="this.form.submit()">
      <option value="">— Todas (vista general) —</option>
      <?php foreach ($delegaciones as $d): ?>
        <option value="<?= htmlspecialchars($d) ?>" <?= ($delegacion === $d) ? 'selected' : '' ?>>
          <?= htmlspecialchars($d) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filtro-grupo">
    <label>Deporte:</label>
    <select name="deporte" id="filtroDeporte" onchange="onFiltroDeporte(this)">
      <option value="">— Todos —</option>
      <?php foreach ($deportes as $dep): ?>
        <option value="<?= (int)$dep['id_deporte'] ?>" <?= ((int)$id_deporte === (int)$dep['id_deporte']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($dep['nombre_deporte']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filtro-grupo">
    <label>Categoría:</label>
    <select name="categoria" id="filtroCategoria">
      <option value="">— Todas —</option>
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
  <?php if (!empty($delegacion) || !empty($id_deporte) || !empty($id_categoria)): ?>
    <a class="limpiar" href="<?= site_url('publico') ?>">✕ Quitar filtros</a>
  <?php endif; ?>
</form>

<!-- Resumen -->
<div class="resumen">
  <div class="stat"><b><?= (int)$resumen['delegaciones'] ?></b><span>Delegaciones</span></div>
  <div class="stat"><b><?= (int)$resumen['deportes'] ?></b><span>Deportes</span></div>
  <div class="stat"><b><?= (int)$resumen['categorias'] ?></b><span>Categorías</span></div>
  <div class="stat"><b><?= (int)$resumen['finalizados'] ?>/<?= (int)$resumen['partidos'] ?></b><span>Partidos jugados</span></div>
  <div class="stat"><b><?= (int)$resumen['resultados'] ?></b><span>Resultados</span></div>
  <div class="stat"><b><?= (int)$resumen['premiados'] ?></b><span>Medallas entregadas</span></div>
</div>

<!-- Tabs -->
<div class="tabs">
  <button class="tab-btn activo" data-tab="tab-fixture">📅 Fixture</button>
  <button class="tab-btn" data-tab="tab-resultados">🏁 Resultados</button>
  <button class="tab-btn" data-tab="tab-medallero">🏅 Medallero</button>
</div>

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
?>
<!-- ============ FIXTURE ============ -->
<div id="tab-fixture" class="panel-tab visible">
  <?php if (empty($fixture)): ?>
    <div class="vacio">No hay partidos programados<?= $delegacion ? ' para esta delegación' : '' ?><?= !empty($id_categoria) ? ' en esta categoría' : '' ?> todavía.</div>
  <?php else: ?>
    <?php
      $dia_actual = null;
      foreach ($fixture as $i => $fx):
        $dia = $fx['fecha_competencia'];
        $nuevo_dia = ($dia !== $dia_actual);
        if ($nuevo_dia):
            if ($dia_actual !== null) echo "</div></div>";
            $dia_actual = $dia;
            $ts = strtotime($dia);
            $dias_es = array('Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado');
            $etiqueta_dia = $ts ? ($dias_es[date('l', $ts)] . ' ' . date('d/m', $ts)) : $dia;
            echo '<div class="grupo-dia"><h3>' . htmlspecialchars($etiqueta_dia) . '</h3><div class="partidos">';
        endif;

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
    ?>
      <div class="partido<?= $es_masivo ? ' masivo' : '' ?>">
        <?php if ($chip_fase !== null): ?>
        <div class="fase-header<?= $chip_elim ? ' elim' : '' ?>"><?= htmlspecialchars($chip_fase) ?></div>
        <?php endif; ?>
        <div class="meta">
          <span><span class="hora-inicio">🕐 <?= htmlspecialchars($hora_txt) ?></span><?php if (!empty($fx['lugar_nombre'])): ?> · <?= htmlspecialchars($fx['lugar_nombre']) ?><?php endif; ?></span>
          <span class="badge <?= htmlspecialchars($fx['estado']) ?>"><?= htmlspecialchars($fx['estado']) ?></span>
        </div>
        <?php if ($es_masivo): ?>
          <div class="masivo-aviso"></div>
        <?php else: ?>
        <div class="vs">
          <div class="equipo <?= $g1 ? 'ganador' : '' ?>"><?= $eq1_nombre ?></div>
          <div class="marcador"><?= $marcador_txt !== '' ? htmlspecialchars($marcador_txt) : '<span class="vsx">VS</span>' ?></div>
          <div class="equipo <?= $g2 ? 'ganador' : '' ?>"><?= $eq2_nombre ?></div>
        </div>
        <?php endif; ?>
        <div class="categoria-linea">
          <span class="deporte-chip"><?= htmlspecialchars((string) $fx['nombre_deporte']) ?></span>
          <?php if (!empty($fx['nombre_categoria'])): ?><span style="opacity:.75"><?= htmlspecialchars((string) $fx['nombre_categoria']) ?></span><?php endif; ?>
          <?php if (!empty($fx['nombre_prueba'])): ?> · <?= htmlspecialchars($fx['nombre_prueba']) ?><?php endif; ?>
        </div>
      </div>
    <?php endforeach; if ($dia_actual !== null) echo "</div></div>"; ?>
  <?php endif; ?>
</div>

<!-- ============ RESULTADOS ============ -->
<div id="tab-resultados" class="panel-tab">
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
</div>

<!-- ============ MEDALLERO ============ -->
<div id="tab-medallero" class="panel-tab">
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
</div>

<footer>
  <img class="mascota" src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" alt="Pampito, la mascota de las olimpiadas">
  Panel público · <?= htmlspecialchars($titulo_evento) ?> · sin datos personales
</footer>
</div>

<script>
// Cascada Deporte → Categoría en la barra de filtros del panel público.
// Cada <option> del combo de categorías lleva data-deporte; al elegir un
// deporte se ocultan las categorías de los demás deportes (y, si la elegida
// ya no aplica, se resetea a "— Todas —"). Sin deporte elegido, se muestran
// TODAS las categorías con el nombre del deporte entre paréntesis.
(function () {
  var selDep = document.getElementById('filtroDeporte');
  var selCat = document.getElementById('filtroCategoria');
  if (!selDep || !selCat) return;

  // Snapshot de los textos originales (con "(Deporte)" cuando no hay filtro
  // de deporte activo) para poder restaurarlos al limpiar el deporte.
  Array.prototype.forEach.call(selCat.options, function (o) {
    if (o.value !== '') o.dataset.baseLabel = o.textContent.trim();
  });

  function aplicarCascada(enviar) {
    var dep = selDep.value;
    var visibleSel = false;
    Array.prototype.forEach.call(selCat.options, function (o) {
      if (o.value === '') return; // "— Todas —" siempre visible
      var ok = (dep === '' || o.dataset.deporte === dep);
      o.hidden = !ok;
      o.disabled = !ok;
      // Con deporte elegido se muestra solo el nombre de la categoría;
      // sin deporte, el deporte entre paréntesis identifica a cada una.
      if (o.dataset.baseLabel) {
        var base = o.dataset.baseLabel;
        var paren = base.lastIndexOf(' (');
        o.textContent = (dep === '' || paren < 0) ? base : base.slice(0, paren);
      }
      if (o.selected && ok) visibleSel = true;
    });
    if (selCat.value !== '' && !visibleSel) selCat.value = '';
    if (enviar) selDep.form.submit();
  }

  window.onFiltroDeporte = function () { aplicarCascada(true); };
  aplicarCascada(false); // estado inicial según los combos del servidor
})();

// Tabs
document.querySelectorAll('.tab-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('activo'));
    document.querySelectorAll('.panel-tab').forEach(p=>p.classList.remove('visible'));
    btn.classList.add('activo');
    document.getElementById(btn.dataset.tab).classList.add('visible');
  });
});
// Recordar tab elegida
var hash = location.hash.replace('#','');
if (['tab-fixture','tab-resultados','tab-medallero'].indexOf(hash) >= 0) {
  document.querySelector('[data-tab="'+hash+'"]').click();
}
document.querySelectorAll('.tab-btn').forEach(function(btn){
  btn.addEventListener('click', function(){ history.replaceState(null,'','#'+btn.dataset.tab); });
});
</script>
</body>
</html>

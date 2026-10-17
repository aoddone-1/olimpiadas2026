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
  --txt:#eaf1f9; --muted:#8fa8c4; --acento:#ffb703; --ok:#2dd4a7;
  --oro:#ffd54d; --plata:#cfd8e3; --bronce:#d9915b;
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
.filtros{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;align-items:center;
         background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px 16px;margin:18px 0}
.filtros label{font-size:13px;color:var(--muted)}
.filtros select{background:var(--card2);color:var(--txt);border:1px solid var(--line);
                border-radius:8px;padding:8px 12px;font-size:15px;min-width:160px}
.filtros a.limpiar{color:var(--acento);text-decoration:none;font-size:14px;padding:8px 10px;
                   border:1px solid var(--line);border-radius:8px}
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
.badge.EN_CURSO{background:#423000;color:var(--acento)}
.badge.FINALIZADO{background:#0f3d2e;color:var(--ok)}
.badge.SUSPENDIDO{background:#421f1f;color:#ffab9e}
.vs{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:14px}
.vs .equipo{flex:1;text-align:center;font-weight:600;line-height:1.25}
.vs .marcador{font-size:20px;font-weight:800;color:var(--acento);white-space:nowrap;padding:0 6px}
.vs .vsx{color:var(--muted);font-size:12px}
.ganador{color:var(--ok)}
.pendiente{color:var(--muted);font-style:italic;font-weight:400 !important}
.categoria-linea{font-size:12px;color:var(--muted);margin-top:8px}
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
/* Medallero */
table.medallero{width:100%;border-collapse:collapse;background:var(--card);border-radius:12px;overflow:hidden}
table.medallero th{background:var(--card2);color:var(--muted);font-size:12px;text-transform:uppercase;
                   padding:10px;border-bottom:1px solid var(--line)}
table.medallero td{padding:10px;border-bottom:1px solid var(--line);font-size:15px;text-align:center}
table.medallero td:first-child{text-align:left;font-weight:600}
table.medallero tr:hover td{background:rgba(255,183,3,.06)}
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
  <label>Delegación:</label>
  <select name="delegacion" onchange="this.form.submit()">
    <option value="">— Todas (vista general) —</option>
    <?php foreach ($delegaciones as $d): ?>
      <option value="<?= htmlspecialchars($d) ?>" <?= ($delegacion === $d) ? 'selected' : '' ?>>
        <?= htmlspecialchars($d) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label>Deporte:</label>
  <select name="deporte" onchange="this.form.submit()">
    <option value="">— Todos —</option>
    <?php foreach ($deportes as $dep): ?>
      <option value="<?= (int)$dep['id_deporte'] ?>" <?= ((int)$id_deporte === (int)$dep['id_deporte']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($dep['nombre_deporte']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <?php if (!empty($delegacion) || !empty($id_deporte)): ?>
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

<!-- ============ FIXTURE ============ -->
<div id="tab-fixture" class="panel-tab visible">
  <?php if (empty($fixture)): ?>
    <div class="vacio">No hay partidos programados<?= $delegacion ? ' para esta delegación' : '' ?> todavía.</div>
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
        // NO llevan rivales ni marcador "VS".
        $es_masivo = ((string) $fx['fase'] === 'JORNADA_UNICA')
                  || ((string) (isset($fx['modalidad_competencia']) ? $fx['modalidad_competencia'] : '') === 'MASIVO_TIEMPO');
    ?>
      <div class="partido<?= $es_masivo ? ' masivo' : '' ?>">
        <div class="meta">
          <span><?= htmlspecialchars((string) $fx['hora_inicio']) ?> – <?= htmlspecialchars((string) $fx['hora_fin']) ?><?php if (!empty($fx['lugar_nombre'])): ?> · <?= htmlspecialchars($fx['lugar_nombre']) ?><?php endif; ?></span>
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
          <?= htmlspecialchars((string) $fx['nombre_deporte']) ?> · <?= htmlspecialchars((string) $fx['nombre_categoria']) ?>
          <?php if (!empty($fx['fase'])): ?>· <?= htmlspecialchars($fx['fase']) ?><?php endif; ?><?php if (!empty($fx['nombre_prueba'])): ?> · <?= htmlspecialchars($fx['nombre_prueba']) ?><?php endif; ?>
        </div>
      </div>
    <?php endforeach; if ($dia_actual !== null) echo "</div></div>"; ?>
  <?php endif; ?>
</div>

<!-- ============ RESULTADOS ============ -->
<div id="tab-resultados" class="panel-tab">
  <?php if (empty($resultados)): ?>
    <div class="vacio">Todavía no hay resultados cargados<?= $delegacion ? ' para esta delegación' : '' ?>.</div>
  <?php else: foreach ($resultados as $res): ?>
    <div class="resultado-card">
      <h4><?= htmlspecialchars((string) $res['nombre_evento']) ?></h4>
      <div class="sub">
        <?= htmlspecialchars((string) (isset($res['nombre_deporte']) ? $res['nombre_deporte'] : '')) ?> · <?= htmlspecialchars((string) (isset($res['nombre_categoria']) ? $res['nombre_categoria'] : '')) ?>
        · <?= htmlspecialchars((string) $res['fecha_resultado']) ?><?= !empty($res['lugar']) ? ' · ' . htmlspecialchars($res['lugar']) : '' ?>
        <?php if (!empty($res['hubo_desempate'])): ?>
          <span class="desempate-tag">⚡ Desempate: <?= htmlspecialchars(ucwords(strtolower((string)$res['desempate_metodo']))) ?></span>
        <?php endif; ?>
      </div>
      <?php if ($res['tipo_resultado'] === 'MARCADOR' && count($res['detalle']) >= 2):
          $a = $res['detalle'][0]; $b = $res['detalle'][1]; ?>
        <div class="fila-pos">
          <span class="pos <?= (int)$a['marcador_local'] > (int)$a['marcador_visita'] ? 'p1' : '' ?>">1er</span>
          <span><?= htmlspecialchars((string) $a['nombre_libre']) ?></span>
          <span class="marcador-res"><?= (int)$a['marcador_local'] ?> : <?= (int)$a['marcador_visita'] ?></span>
        </div>
        <div class="fila-pos">
          <span class="pos <?= (int)$b['marcador_local'] > (int)$b['marcador_visita'] ? 'p1' : '' ?>">2do</span>
          <span><?= htmlspecialchars((string) $b['nombre_libre']) ?></span>
          <span class="marcador-res"><?= (int)$b['marcador_local'] ?> : <?= (int)$b['marcador_visita'] ?></span>
        </div>
      <?php else: foreach ($res['detalle'] as $d):
          $pos_n = (int)$d['posicion']; ?>
        <div class="fila-pos">
          <span class="pos <?= $pos_n >= 1 && $pos_n <= 3 ? 'p'.$pos_n : '' ?>"><?= $pos_n ?></span>
          <span><?= htmlspecialchars((string) $d['nombre_libre']) ?></span>
          <?php if (!empty($d['tiempo'])): ?><span class="tiempo"><?= htmlspecialchars($d['tiempo']) ?></span><?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
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

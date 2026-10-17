<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Público - <?= NOMBRE_META; ?></title>
<!-- Mismos Bootstrap + íconos que el formulario de inscripción -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
<!-- ¡Los mismos estilos del formulario_inscripcion_2! (hero azul, borde dorado, cards...) -->
<link href="<?= base_url('css/style_formularios.css') ?>" rel="stylesheet">
<style>
/* Ajustes propios del panel, respetando la paleta del formulario:
   azul #1e3c72 / #2a5298, dorado #ffc107, fondo #f4f7f6 */
:root{
  --azul:#1e3c72; --azul2:#2a5298; --dorado:#ffc107;
  --card:#ffffff; --card2:#f2f5f9; --line:#dee2e6;
  --txt:#212529; --muted:#6c757d; --acento:var(--azul); --ok:#0a8a5f;
  --oro:#b8860b; --plata:#5c6b7a; --bronce:#a05a2c;
}
section.hero-panel{margin-bottom:30px}
.chip-delegacion{display:inline-block;background:var(--dorado);color:#212529;border-radius:20px;
                 padding:4px 14px;font-weight:700;font-size:14px}
.stat b{display:block;font-size:28px;color:var(--azul)}
.stat span{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.tabs{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:16px}
.tab-btn{background:var(--card);border:1px solid var(--line);color:var(--txt);padding:10px 22px;
         border-radius:30px;font-size:15px;cursor:pointer;transition:.15s;box-shadow:0 4px 6px rgba(0,0,0,0.1)}
.tab-btn:hover{border-color:var(--azul);color:var(--azul)}
.tab-btn.activo{background:var(--azul);color:#fff;font-weight:700;border-color:var(--azul)}
.panel-tab{display:none}
.panel-tab.visible{display:block}
.grupo-dia{margin-bottom:22px}
.grupo-dia h3{color:var(--azul);font-size:16px;margin-bottom:10px;border-left:5px solid var(--dorado);
              padding-left:12px;text-transform:uppercase;font-weight:700}
.partidos{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px}
.partido{background:var(--card);border:none;border-left:5px solid var(--azul);border-radius:15px;
         padding:12px 14px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}
.partido .meta{display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:8px}
.badge{font-size:11px;padding:4px 10px;border-radius:10px;font-weight:700}
.badge.PROGRAMADO{background:#e0ecff;color:#1d4ed8}
.badge.EN_CURSO{background:#fef3c7;color:#92400e}
.badge.FINALIZADO{background:#d1f2e4;color:#0a8a5f}
.badge.SUSPENDIDO{background:#fee2e2;color:#b91c1c}
.vs{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:14px}
.vs .equipo{flex:1;text-align:center;font-weight:600;line-height:1.25}
.vs .marcador{font-size:20px;font-weight:800;color:var(--azul);white-space:nowrap;padding:0 6px}
.vs .vsx{color:var(--muted);font-size:12px}
.ganador{color:var(--ok)}
.pendiente{color:var(--muted);font-style:italic;font-weight:400 !important}
.categoria-linea{font-size:12px;color:var(--muted);margin-top:8px}
.resultado-card{background:var(--card);border:none;border-radius:15px;padding:14px;margin-bottom:12px;
                box-shadow:0 4px 6px rgba(0,0,0,0.1)}
.resultado-card h4{font-size:15px;margin-bottom:2px;color:var(--azul)}
.resultado-card .sub{font-size:12px;color:var(--muted);margin-bottom:10px}
.fila-pos{display:flex;align-items:center;gap:10px;padding:6px 8px;border-radius:8px;font-size:14px}
.fila-pos:nth-child(odd){background:var(--card2)}
.pos{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;
     font-weight:800;font-size:13px;background:#e2e8f0;color:#334155;flex-shrink:0}
.pos.p1{background:var(--oro);color:#fff}.pos.p2{background:var(--plata);color:#fff}.pos.p3{background:var(--bronce);color:#fff}
.tiempo{margin-left:auto;font-family:Consolas,monospace;color:var(--ok);font-weight:700}
.marcador-res{margin-left:auto;font-weight:800;color:var(--azul)}
.desempate-tag{font-size:11px;color:#b91c1c;margin-left:8px}
table.medallero{width:100%;border-collapse:collapse;background:var(--card);border-radius:15px;overflow:hidden;
                box-shadow:0 4px 6px rgba(0,0,0,0.1)}
table.medallero th{background:var(--azul);color:#fff;font-size:12px;text-transform:uppercase;
                   padding:10px;border-bottom:1px solid var(--line)}
table.medallero td{padding:10px;border-bottom:1px solid var(--line);font-size:15px;text-align:center}
table.medallero td:first-child{text-align:left;font-weight:600}
table.medallero tr:hover td{background:rgba(30,60,114,.06)}
table.medallero td.oro{color:var(--oro);font-weight:800}
table.medallero td.plata{color:var(--plata);font-weight:800}
table.medallero td.bronce{color:var(--bronce);font-weight:800}
.rank-num{display:inline-flex;width:24px;height:24px;border-radius:50%;background:#e2e8f0;color:#334155;
          align-items:center;justify-content:center;font-size:12px;font-weight:700;margin-right:8px}
tr.top1 .rank-num{background:var(--dorado);color:#212529}
.vacio{text-align:center;color:var(--muted);padding:40px 10px;font-size:15px}
footer .mascota{display:block;margin:0 auto 10px;height:72px;width:auto;object-fit:contain;
                filter:drop-shadow(0px 4px 8px rgba(0,0,0,0.2))}
footer{text-align:center;color:var(--muted);font-size:12px;padding:26px 0 16px}
/* Jornada masiva (todos contra todos): sin "rivales" */
.partido.masivo{background:linear-gradient(150deg,#ffffff 0%,#eaf3fb 100%)}
.masivo-aviso{margin-top:2px;text-align:center;font-size:13px;color:var(--muted);font-style:italic}
.badge.MASIVA{background:#dbeafe;color:#1d4ed8;margin-left:6px}
@media(max-width:600px){.partidos{grid-template-columns:1fr}}
</style>
</head>
<body>

<!-- HERO idéntico al del formulario de inscripción: gradiente azul + borde dorado -->
<div class="hero-section hero-panel">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-12 col-md-4 text-center text-md-end d-none d-md-block">
                <img src="<?= base_url('assets/img/logo_olimpiadas.png') ?>"
                     alt="Logo Olimpiadas" class="img-fluid logo-hero">
            </div>
            <div class="col-12 col-md-8 text-center text-md-start ps-md-4">
                <div class="d-block d-md-none mb-2">
                    <img src="<?= base_url('assets/img/logo_olimpiadas.png') ?>"
                         alt="Logo Olimpiadas" class="img-fluid logo-hero">
                </div>
                <h1 class="h2 fw-bold text-uppercase text-white mb-2 header-title">
                    PANEL PÚBLICO <u>DE OLIMPISMO</u>
                </h1>
                <small class="d-block text-white-50 help-text">
                    <?= NOMBRE_SITIO; ?> <br/> <?= LUGAR_OLIMPICO; ?>
                </small>
            </div>
        </div>
    </div>
</div>

<div class="container">

<p class="text-center mb-3" style="color:var(--muted);font-size:14px">
  <?php if (!empty($delegacion)): ?>
    Viendo: <span class="chip-delegacion"><?= htmlspecialchars($delegacion) ?></span>
  <?php else: ?>
    Seguimiento en vivo del evento · actualizado <?= htmlspecialchars($ahora) ?>
  <?php endif; ?>
</p>

<!-- Filtros -->
<form method="get" action="<?= site_url('publico') ?>" class="card p-3 mb-4 shadow-sm">
  <div class="row g-2 align-items-center justify-content-center">
  <label class="col-auto fw-bold small text-secondary">Delegación:</label>
  <div class="col-auto" style="min-width:170px">
  <select name="delegacion" class="form-select" onchange="this.form.submit()">
    <option value="">— Todas (vista general) —</option>
    <?php foreach ($delegaciones as $d): ?>
      <option value="<?= htmlspecialchars($d) ?>" <?= ($delegacion === $d) ? 'selected' : '' ?>>
        <?= htmlspecialchars($d) ?>
      </option>
    <?php endforeach; ?>
  </select>
  </div>

  <label class="col-auto fw-bold small text-secondary">Deporte:</label>
  <div class="col-auto" style="min-width:170px">
  <select name="deporte" class="form-select" onchange="this.form.submit()">
    <option value="">— Todos —</option>
    <?php foreach ($deportes as $dep): ?>
      <option value="<?= (int)$dep['id_deporte'] ?>" <?= ((int)$id_deporte === (int)$dep['id_deporte']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($dep['nombre_deporte']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  </div>

  <?php if (!empty($delegacion) || !empty($id_deporte)): ?>
    <div class="col-auto">
      <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('publico') ?>"><i class="bi bi-x-circle"></i> Quitar filtros</a>
    </div>
  <?php endif; ?>
  </div>
</form>

<!-- Resumen -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-2"><div class="card text-center shadow-sm"><div class="card-body py-3">
    <b style="font-size:26px;color:var(--azul)"><?= (int)$resumen['delegaciones'] ?></b>
    <div class="small text-muted text-uppercase" style="letter-spacing:.5px">Delegaciones</div>
  </div></div></div>
  <div class="col-6 col-md-2"><div class="card text-center shadow-sm"><div class="card-body py-3">
    <b style="font-size:26px;color:var(--azul)"><?= (int)$resumen['deportes'] ?></b>
    <div class="small text-muted text-uppercase" style="letter-spacing:.5px">Deportes</div>
  </div></div></div>
  <div class="col-6 col-md-2"><div class="card text-center shadow-sm"><div class="card-body py-3">
    <b style="font-size:26px;color:var(--azul)"><?= (int)$resumen['categorias'] ?></b>
    <div class="small text-muted text-uppercase" style="letter-spacing:.5px">Categorías</div>
  </div></div></div>
  <div class="col-6 col-md-2"><div class="card text-center shadow-sm"><div class="card-body py-3">
    <b style="font-size:26px;color:var(--azul)"><?= (int)$resumen['finalizados'] ?>/<?= (int)$resumen['partidos'] ?></b>
    <div class="small text-muted text-uppercase" style="letter-spacing:.5px">Partidos jugados</div>
  </div></div></div>
  <div class="col-6 col-md-2"><div class="card text-center shadow-sm"><div class="card-body py-3">
    <b style="font-size:26px;color:var(--azul)"><?= (int)$resumen['resultados'] ?></b>
    <div class="small text-muted text-uppercase" style="letter-spacing:.5px">Resultados</div>
  </div></div></div>
  <div class="col-6 col-md-2"><div class="card text-center shadow-sm"><div class="card-body py-3">
    <b style="font-size:26px;color:var(--azul)"><?= (int)$resumen['premiados'] ?></b>
    <div class="small text-muted text-uppercase" style="letter-spacing:.5px">Medallas entregadas</div>
  </div></div></div>
</div>

<!-- Tabs -->
<div class="tabs">
  <button class="tab-btn activo" data-tab="tab-fixture"><i class="bi bi-calendar-event me-1"></i> Fixture</button>
  <button class="tab-btn" data-tab="tab-resultados"><i class="bi bi-flag-fill me-1"></i> Resultados</button>
  <button class="tab-btn" data-tab="tab-medallero"><i class="bi bi-medal me-1"></i> Medallero</button>
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
          <span><span class="badge <?= htmlspecialchars($fx['estado']) ?>"><?= htmlspecialchars($fx['estado']) ?></span><?php if ($es_masivo): ?><span class="badge MASIVA">MASIVA</span><?php endif; ?></span>
        </div>
        <?php if ($es_masivo): ?>
          <div class="masivo-aviso">🏃 Todos contra todos — jornada de participación libre.<br>Los puestos y tiempos se publican en la pestaña <b>Resultados</b>.</div>
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

<footer class="text-center mt-5 pb-4" style="color:var(--muted);font-size:13px">
  <img class="mascota mb-2" src="<?= base_url('assets/img/pampito.png') ?>" alt="Pampito, la mascota de las olimpiadas">
  Panel público · <?= NOMBRE_META; ?> · sin datos personales
</footer>
</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard General - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?= base_url('css/style.css?20261022') ?>" rel="stylesheet">
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>

<div class="container mb-5">

    <?php
        // ---- CALENDARIO OLIMPICO (ajustar aqui si cambian las fechas) ----
        $INI_ACREDITACION = '2026-11-01';   // acreditacion / entrega de kits
        $APERTURA         = '2026-11-02';   // primer dia de competencia
        $FIN_JUEGO        = '2026-11-06';   // ultimo dia de competencia

        // Dia consultado: por defecto HOY; se puede cambiar con el selector (?dia=...)
        $dia_actual = isset($dia_seleccionado) ? $dia_seleccionado : date('Y-m-d');
        $es_dia_acreditacion = ($dia_actual < $APERTURA);
        $nuevos_inscriptos = isset($nuevos_inscriptos_hoy) ? (int) $nuevos_inscriptos_hoy : 0;
        $actividad_dia = isset($actividad_dia) ? $actividad_dia : array();
        $resultados_dia = isset($resultados_dia) ? $resultados_dia : array();
        $premios_noche = isset($premios_noche) ? $premios_noche : array();

        // ---- RESUMEN GENERAL DEL DASHBOARD (se calcula desde la lista ya cargada) ----
        $total_participantes = isset($total_inscriptos) ? (int) $total_inscriptos : count($participantes);
        $kits_entregados = isset($total_kits) ? (int) $total_kits : 0;

        $delegaciones = array();
        $delegaciones_completas = array();
        foreach ($participantes as $p) {
            $del = trim($p['delegacion'] ?? '');
            if ($del !== '') {
                if (!isset($delegaciones[$del])) {
                    $delegaciones[$del] = ['total' => 0, 'con_kit' => 0];
                }
                $delegaciones[$del]['total']++;
                if (!empty($p['kit_entregado'])) {
                    $delegaciones[$del]['con_kit']++;
                }
            }
        }
        // Delegaciones que ya tienen TODOS sus kits entregados (se acreditan por el delegado)
        foreach ($delegaciones as $del => $d) {
            if ($d['total'] > 0 && $d['con_kit'] >= $d['total']) {
                $delegaciones_completas[$del] = $d['total'];
            }
        }
        arsort($delegaciones);
        ksort($delegaciones_completas);
        // Delegaciones que AUN NO tienen todos los kits entregados (con conteo pendiente)
        $delegaciones_pendientes = array();
        foreach ($delegaciones as $del => $d) {
            if ($d['total'] > 0 && $d['con_kit'] < $d['total']) {
                $delegaciones_pendientes[$del] = $d['total'] - $d['con_kit'];
            }
        }
        asort($delegaciones_pendientes);
        $porc_kits = $total_participantes > 0 ? round(($kits_entregados / $total_participantes) * 100) : 0;
    ?>

    <?php
        // ---- SELECTOR DE DIA DEL RESUMEN (agrupa el contenido segun la fecha) ----
        $dias_resumen = array();
        if ($INI_ACREDITACION < $APERTURA) $dias_resumen[] = $INI_ACREDITACION;
        for ($d = $APERTURA; $d <= $FIN_JUEGO; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            $dias_resumen[] = $d;
        }
        if (!in_array($dia_actual, $dias_resumen)) {
            $dias_resumen[] = $dia_actual; // permite ver cualquier otro dia via ?dia=
        }
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'spanish');
        $DIAS_SEMANA = array('Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado');
        $MESES = array(1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre');
        $formato_dia_largo = function ($f) use ($DIAS_SEMANA, $MESES) {
            $ts = strtotime($f);
            return $DIAS_SEMANA[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' de ' . $MESES[(int) date('n', $ts)];
        };
    ?>
    <div class="card shadow-sm border-0 rounded-3 mb-3">
        <div class="card-body p-2 p-md-3 d-flex flex-wrap align-items-center gap-2">
            <span class="fw-bold text-dark small me-1"><i class="bi bi-calendar3 me-1"></i>Resumen del día:</span>
            <?php foreach ($dias_resumen as $d): ?>
                <?php $activo = ($d === $dia_actual); ?>
                <a href="<?= base_url('Inscripciones/dashboard?dia=' . $d) ?>"
                   class="btn btn-sm <?= $activo ? 'btn-dark' : 'btn-light border' ?> rounded-pill px-3">
                    <?= $formato_dia_largo($d) ?>
                </a>
            <?php endforeach; ?>
            <span class="ms-auto small text-muted d-none d-lg-inline">
                <?php if ($dia_actual < $INI_ACREDITACION): ?>
                    <i class="bi bi-info-circle me-1"></i>Día previo a la acreditación: se muestra el estado general de inscripciones
                <?php elseif ($es_dia_acreditacion): ?>
                    <i class="bi bi-person-check me-1"></i>Jornada de acreditación y entrega de kits
                <?php else: ?>
                    <i class="bi bi-flag-fill me-1"></i>Jornada de competencia
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card card-indicador shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-1"><i class="bi bi-people me-1"></i>Inscriptos</h6>
                    <h3 class="fw-bold text-dark m-0"><?= $total_participantes ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-indicador shadow-sm h-100" style="border-left-color: #6f42c1;">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-1"><i class="bi bi-geo-alt me-1"></i>Delegaciones</h6>
                    <h3 class="fw-bold text-dark m-0"><?= count($delegaciones) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-indicador shadow-sm h-100" style="border-left-color: #fd7e14;">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-1"><i class="bi bi-box-seam me-1"></i>Kits entregados</h6>
                    <h3 class="fw-bold text-dark m-0"><?= $kits_entregados ?><small class="fs-6 text-muted"> / <?= $total_participantes ?></small></h3>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $porc_kits ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($es_dia_acreditacion): ?>
            <div class="col-6 col-md-3">
                <div class="card card-indicador shadow-sm h-100" style="border-left-color: #03551f;">
                    <div class="card-body p-3">
                        <h6 class="text-muted small text-uppercase mb-1"><i class="bi bi-calendar-event me-1"></i<?= $dia_actual === date('Y-m-d') ? ' title="Día seleccionado"' : '' ?>>Exportar Datos</h6>
                        <a href="<?= base_url('Inscripciones/descargar_csv_todos_inscriptos') ?>" class="btn btn-success btn-sm w-100 mt-1">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Descargar CSV
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-6 col-md-3">
                <div class="card card-indicador shadow-sm h-100" style="border-left-color: #0d6efd;">
                    <div class="card-body p-3">
                        <h6 class="text-muted small text-uppercase mb-1"><i class="bi bi-whistle me-1"></i>Actividad del día</h6>
                        <h3 class="fw-bold text-dark m-0"><?= (int) ($actividad_dia['total_partidos'] ?? 0) ?><small class="fs-6 text-muted"> Competencias</small></h3>
                        <small class="text-muted d-block mt-1">
                            <?= (int) ($actividad_dia['finalizados'] ?? 0) ?> finalizadas ·
                            <?= (int) ($actividad_dia['programados'] ?? 0) ?> programadas
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0 rounded-3">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-2"><i class="bi bi-diagram-3 me-1"></i>Competidores por delegación<?= $es_dia_acreditacion ? '' : ' <span class="badge bg-secondary-subtle text-secondary ms-1" title="Total histórico (no depende del día)">total</span>' ?></h6>
                    <?php if (!empty($delegaciones)): ?>
                        <?php $i = 0; foreach ($delegaciones as $del => $d): ?>
                            <div class="d-flex justify-content-between align-items-center py-1 <?= $i > 0 ? 'border-top' : '' ?> <?= $i >= 8 ? 'd-none delegacion-extra' : '' ?>">
                                <span class="text-primary fw-semibold small"><?= htmlspecialchars($del, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-light text-dark border"><?= $d['total'] ?></span>
                            </div>
                            <?php $i++; ?>
                        <?php endforeach; ?>
                        <?php if ($i > 8): ?>
                            <a href="#" class="ver-mas-delegaciones small text-decoration-none d-block mt-1 text-danger" data-total="<?= $i ?>" data-visibles="8">Ver más (+<?= $i - 8 ?> delegaciones)</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <small class="text-muted">Sin datos de delegaciones.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <?php if ($es_dia_acreditacion): ?>
            <div class="card shadow-sm h-100 border-0 rounded-3">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-2"><i class="bi bi-box-seam me-1"></i>Delegaciones con kits entregados</h6>
                    <?php if (!empty($delegaciones_completas)): ?>
                        <?php $i = 0; foreach ($delegaciones_completas as $del => $cant): ?>
                            <div class="d-flex justify-content-between align-items-center py-1 <?= $i > 0 ? 'border-top' : '' ?> <?= $i >= 8 ? 'd-none delegacion-extra' : '' ?>">
                                <span class="small text-success fw-semibold"><i class="bi bi-check-circle-fill me-1 text-success"></i><?= htmlspecialchars($del, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-success-subtle text-success border-0"><?= $cant ?> kit<?= $cant == 1 ? '' : 's' ?></span>
                            </div>
                            <?php $i++; ?>
                        <?php endforeach; ?>
                        <?php if ($i > 8): ?>
                            <a href="#" class="ver-mas-delegaciones small text-decoration-none d-block mt-1" data-total="<?= $i ?>" data-visibles="8">Ver más (+<?= $i - 8 ?> delegaciones)</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <small class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Aún no hay delegaciones con todos los kits entregados.</small>
                    <?php endif; ?>
                    <div class="mt-2 pt-2 border-top">
                        <?php $pendientes = count($delegaciones_pendientes); ?>
                        <?php if ($pendientes > 0): ?>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-warning-subtle text-warning-emphasis fw-semibold"><i class="bi bi-clock-history me-1"></i><?= $pendientes ?> delegaci<?= $pendientes == 1 ? 'ón pendiente' : 'ones pendientes' ?></span>
                            </div>
                            <?php $j = 0; foreach ($delegaciones_pendientes as $del => $faltan): ?>
                                <div class="d-flex justify-content-between align-items-center py-1 <?= $j > 0 ? 'border-top' : '' ?> <?= $j >= 4 ? 'd-none pendiente-extra' : '' ?>">
                                    <span class="small text-dark"><i class="bi bi-hourglass-split text-warning me-1"></i><?= htmlspecialchars($del, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border-0"><?= $faltan ?> kit<?= $faltan == 1 ? '' : 's' ?> sin entregar</span>
                                </div>
                                <?php $j++; ?>
                            <?php endforeach; ?>
                            <?php if ($j > 4): ?>
                                <a href="#" class="ver-mas-pendientes small text-decoration-none d-block mt-1 text-danger" data-total="<?= $j ?>" data-visibles="4">Ver más (+<?= $j - 4 ?> delegaciones)</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>¡Todas las delegaciones acreditadas!</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card shadow-sm h-100 border-0 rounded-3">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-2"><i class="bi bi-trophy me-1"></i>Premios de la noche (<?= $formato_dia_largo($dia_actual) ?>)</h6>
                    <?php if (!empty($premios_noche)): ?>
                        <?php $i = 0; foreach ($premios_noche as $pn): ?>
                            <?php
                                $nombre_dep = $pn['categoria']['nombre_deporte'] ?? '';
                                $nombre_cat = $pn['categoria']['nombre_categoria'] ?? '';
                            ?>
                            <div class="d-flex justify-content-between align-items-center py-1 <?= $i > 0 ? 'border-top' : '' ?>">
                                <span class="small fw-semibold text-dark"><i class="bi bi-medal-fill text-warning me-1"></i><?= htmlspecialchars($nombre_dep . ($nombre_cat ? ' › ' . $nombre_cat : ''), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-success-subtle text-success border-0">podio confirmado</span>
                            </div>
                            <?php $i++; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <small class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Aún no hay podios confirmados para esta noche. Se cargan desde Premiación.</small>
                    <?php endif; ?>
                    <div class="mt-2 pt-2 border-top">
                        <a href="<?= base_url('Inscripciones/premiacion') ?>" class="small text-decoration-none"><i class="bi bi-box-arrow-up-right me-1"></i>Ir a Premiación</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$es_dia_acreditacion): ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100 border-0 rounded-3" style="border-left: 5px solid #0d6efd;">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-2"><i class="bi bi-flag-fill me-1"></i>Disciplinas en juego hoy</h6>
                    <?php if (!empty($actividad_dia['disciplinas'])): ?>
                        <?php foreach ($actividad_dia['disciplinas'] as $dis): ?>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 py-2 border-top <?= $dis === reset($actividad_dia['disciplinas']) ? 'border-top-0' : '' ?>">
                                <div>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($dis['deporte'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="small text-muted">
                                        <?php if (!empty($dis['horario'])): ?><i class="bi bi-clock me-1"></i><?= htmlspecialchars($dis['horario'], ENT_QUOTES, 'UTF-8') ?><?php if (!empty($dis['ultima_hora']) && $dis['ultima_hora'] !== $dis['horario']): ?> – <?= htmlspecialchars($dis['ultima_hora'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?><?php endif; ?>
                                        <?php if (!empty($dis['sedes'])): ?><span class="ms-1"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars(implode(', ', array_slice($dis['sedes'], 0, 2)) . (count($dis['sedes']) > 2 ? ' +' . (count($dis['sedes']) - 2) : ''), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border-0"><?= (int) $dis['partidos'] ?> <?= $dis['partidos'] == 1 ? 'presentación' : 'presentaciones' ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!empty($actividad_dia['suspendidos'])): ?>
                            <div class="alert alert-warning py-1 px-2 mt-2 small mb-0"><i class="bi bi-exclamation-triangle me-1"></i><?= (int) $actividad_dia['suspendidos'] ?> presentación(es) suspendida(s) hoy.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <small class="text-muted"><i class="bi bi-calendar-x me-1"></i>No hay competencias cargadas en el fixture para este día. Podés armarlas en <a href="<?= base_url('Inscripciones/fixture') ?>" class="text-decoration-none">Fixture</a>.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100 border-0 rounded-3" style="border-left: 5px solid #198754;">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-2"><i class="bi bi-list-columns-reverse me-1"></i>Resultados cargados hoy</h6>
                    <?php if (!empty($resultados_dia)): ?>
                        <?php $i = 0; foreach (array_slice($resultados_dia, 0, 8) as $res): ?>
                            <div class="py-1 <?= $i > 0 ? 'border-top' : '' ?>">
                                <div class="small fw-semibold text-dark"><?= htmlspecialchars(($res['nombre_deporte'] ?? '') . ($res['nombre_categoria'] ? ' › ' . $res['nombre_categoria'] : ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($res['nombre_evento'], ENT_QUOTES, 'UTF-8') ?> <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($res['tipo_resultado'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            </div>
                            <?php $i++; ?>
                        <?php endforeach; ?>
                        <?php if (count($resultados_dia) > 8): ?>
                            <div class="mt-2 pt-1 border-top small text-muted">+<?= count($resultados_dia) - 8 ?> resultado(s) más</div>
                        <?php endif; ?>
                        <div class="mt-2"><a href="<?= base_url('Inscripciones/resultados') ?>" class="small text-decoration-none"><i class="bi bi-box-arrow-up-right me-1"></i>Ver todos los resultados</a></div>
                    <?php else: ?>
                        <small class="text-muted"><i class="bi bi-inbox me-1"></i>Todavía no se cargaron resultados en esta jornada.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="mb-4 search-container">
        <div class="input-group shadow-sm rounded-3 overflow-hidden">
            <span class="input-group-text bg-white border-0 text-muted ps-3"><i class="bi bi-search"></i></span>
            <input type="text" id="buscador" class="form-control border-0 p-3 shadow-none" placeholder="Buscar competidor por Nombre, DNI o Delegación...">
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-secondary m-0">Detalle de Competidores Registrados</h5>
        <span id="contador-resultados" class="badge bg-dark text-white px-2 py-1.5 rounded" style="display: none;"></span>
    </div>
    
    <div id="lista-participantes">
        <?php if(!empty($participantes)): ?>
            <?php foreach($participantes as $p): ?>
                <?php $search_string = mb_strtolower($p['nombre_completo'] . ' ' . $p['dni'] . ' ' . $p['delegacion'], 'UTF-8'); ?>
                <div class="card card-participante shadow-sm mb-2 fila-participante" data-string="<?= htmlspecialchars($search_string, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold text-dark m-0 text-uppercase"><?= htmlspecialchars($p['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></h6>
                            <small class="text-muted">
                                <strong>DNI:</strong> <?= htmlspecialchars($p['dni'], ENT_QUOTES, 'UTF-8') ?> | 
                                <span class="text-primary fw-semibold"><?= htmlspecialchars($p['delegacion'], ENT_QUOTES, 'UTF-8') ?></span>
                            </small>
                            <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                <i class="bi bi-building me-1"></i>Hotel: <?= !empty($p['hotel_alojamiento']) ? htmlspecialchars($p['hotel_alojamiento'], ENT_QUOTES, 'UTF-8') : '<span class="text-danger fw-semibold">NO ASIGNADO</span>' ?>
                            </div>
                        </div>
                        
                        <div class="text-end d-flex align-items-center gap-2">
                            <a href="<?= base_url('Inscripciones/acreditacion/'.$p['token_qr']) ?>" class="btn btn-sm btn-light border text-primary px-2"><i class="bi bi-qr-code-scan"></i> Control</a>
                            <a href="<?= base_url('Inscripciones/imprimir_credencial/'.$p['token_qr']) ?>" target="_blank" class="btn btn-sm btn-dark px-2"><i class="bi bi-printer-fill"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div id="sin-resultados" class="alert alert-white text-center shadow-sm py-4 border" style="display: none; background: white;">
                <i class="bi bi-exclamation-circle text-muted fs-4 d-block mb-2"></i> No se encontraron participantes.
            </div>
        <?php else: ?>
            <div class="alert alert-white text-center shadow-sm py-4">No hay participantes registrados.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ver más / Ver menos en listas de delegaciones (también las pendientes)
    document.querySelectorAll('.ver-mas-delegaciones, .ver-mas-pendientes').forEach(function(enlace) {
        enlace.addEventListener('click', function(e) {
            e.preventDefault();
            const card = this.closest('.card-body');
            const extras = card.querySelectorAll('.delegacion-extra, .pendiente-extra');
            const expandido = this.classList.contains('expandido');
            extras.forEach(function(fila) {
                fila.classList.toggle('d-none', expandido);
            });
            if (!expandido) {
                this.textContent = 'Ver menos';
                this.classList.add('expandido');
            } else {
                const total = parseInt(this.dataset.total, 10);
                const visibles = parseInt(this.dataset.visibles, 10);
                const sufijo = this.classList.contains('ver-mas-pendientes') ? '' : ' delegaciones';
                this.textContent = 'Ver más (+' + (total - visibles) + sufijo + ')';
                this.classList.remove('expandido');
            }
        });
    });

    const buscador = document.getElementById('buscador');
    const filas = document.querySelectorAll('.fila-participante');
    const sinResultados = document.getElementById('sin-resultados');
    const contadorResultados = document.getElementById('contador-resultados');

    if (buscador) {
        buscador.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            let encontrados = 0;

            filas.forEach(fila => {
                if (fila.getAttribute('data-string').includes(query)) {
                    fila.style.setProperty('display', 'block', 'important');
                    encontrados++;
                } else {
                    fila.style.setProperty('display', 'none', 'important');
                }
            });

            if (query === "") {
                sinResultados.style.display = 'none';
                contadorResultados.style.display = 'none';
            } else {
                contadorResultados.innerText = encontrados + (encontrados === 1 ? ' coincidencia' : ' coincidencias');
                contadorResultados.style.display = 'inline-block';
                sinResultados.style.display = encontrados === 0 ? 'block' : 'none';
            }
        });
    }
});
</script>
</body>
</html>
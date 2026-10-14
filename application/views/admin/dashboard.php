<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard General - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>

<div class="container mb-5">

    <?php
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
        $porc_kits = $total_participantes > 0 ? round(($kits_entregados / $total_participantes) * 100) : 0;
    ?>

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
        <div class="col-6 col-md-3">
            <div class="card card-indicador shadow-sm h-100" style="border-left-color: #03551f;">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-1">Exportar Datos</h6>
                    <a href="<?= base_url('Inscripciones/descargar_csv_todos_inscriptos') ?>" class="btn btn-success btn-sm w-100 mt-1">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Descargar CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0 rounded-3">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-2"><i class="bi bi-diagram-3 me-1"></i>Competidores por delegación</h6>
                    <?php if (!empty($delegaciones)): ?>
                        <?php $i = 0; foreach ($delegaciones as $del => $d): ?>
                            <div class="d-flex justify-content-between align-items-center py-1 <?= $i > 0 ? 'border-top' : '' ?> <?= $i >= 8 ? 'd-none delegacion-extra' : '' ?>">
                                <span class="text-primary fw-semibold small"><?= htmlspecialchars($del, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-light text-dark border"><?= $d['total'] ?></span>
                            </div>
                            <?php $i++; ?>
                        <?php endforeach; ?>
                        <?php if ($i > 8): ?>
                            <a href="#" class="ver-mas-delegaciones small text-decoration-none d-block mt-1" data-total="<?= $i ?>" data-visibles="8">Ver más (+<?= $i - 8 ?> delegaciones)</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <small class="text-muted">Sin datos de delegaciones.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
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
                        <?php $pendientes = count($delegaciones) - count($delegaciones_completas); ?>
                        <?php if ($pendientes > 0): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis fw-semibold"><i class="bi bi-clock-history me-1"></i><?= $pendientes ?> delegaci<?= $pendientes == 1 ? 'ón pendiente' : 'ones pendientes' ?></span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>¡Todas las delegaciones acreditadas!</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    // Ver más / Ver menos en listas de delegaciones
    document.querySelectorAll('.ver-mas-delegaciones').forEach(function(enlace) {
        enlace.addEventListener('click', function(e) {
            e.preventDefault();
            const card = this.closest('.card-body');
            const extras = card.querySelectorAll('.delegacion-extra');
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
                this.textContent = 'Ver más (+' + (total - visibles) + ' delegaciones)';
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
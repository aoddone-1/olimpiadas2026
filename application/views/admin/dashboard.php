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
        $hoteles = array();
        $sin_hotel = 0;
        foreach ($participantes as $p) {
            $del = trim($p['delegacion'] ?? '');
            if ($del !== '') $delegaciones[$del] = ($delegaciones[$del] ?? 0) + 1;

            $hot = trim($p['hotel_alojamiento'] ?? '');
            if ($hot === '') { $sin_hotel++; } else { $hoteles[$hot] = ($hoteles[$hot] ?? 0) + 1; }
        }
        arsort($delegaciones);
        arsort($hoteles);
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
                        <?php $i = 0; foreach ($delegaciones as $del => $cant): ?>
                            <div class="d-flex justify-content-between align-items-center py-1 <?= $i > 0 ? 'border-top' : '' ?>">
                                <span class="text-primary fw-semibold small"><?= htmlspecialchars($del, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-light text-dark border"><?= $cant ?></span>
                            </div>
                            <?php if (++$i >= 8) break; ?>
                        <?php endforeach; ?>
                        <?php if (count($delegaciones) > 8): ?>
                            <small class="text-muted d-block mt-1">...y <?= count($delegaciones) - 8 ?> delegaciones más.</small>
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
                    <h6 class="text-muted small text-uppercase mb-2"><i class="bi bi-building me-1"></i>Alojamiento</h6>
                    <?php if (!empty($hoteles)): ?>
                        <?php $i = 0; foreach ($hoteles as $hot => $cant): ?>
                            <div class="d-flex justify-content-between align-items-center py-1 <?= $i > 0 ? 'border-top' : '' ?>">
                                <span class="small"><?= htmlspecialchars($hot, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-light text-dark border"><?= $cant ?></span>
                            </div>
                            <?php if (++$i >= 6) break; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <small class="text-muted">Sin hoteles asignados.</small>
                    <?php endif; ?>
                    <div class="mt-2 pt-2 border-top">
                        <?php if ($sin_hotel > 0): ?>
                            <span class="badge bg-danger-subtle text-danger fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i><?= $sin_hotel ?> sin hotel asignado</span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Todos con hotel asignado</span>
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
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

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-indicador shadow-sm">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-1">Inscriptos</h6>
                    <h3 class="fw-bold text-dark m-0"><?= $total_inscriptos ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-indicador shadow-sm" style="border-left-color: #198754;">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-1">Kits Entregados</h6>
                    <h3 class="fw-bold text-success m-0"><?= $total_kits ?></h3>
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
                            <?php if($p['kit_entregado'] == 1): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle text-uppercase small px-2 py-1">Kit OK</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle text-uppercase small px-2 py-1">Kit Pendiente</span>
                            <?php endif; ?>

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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Deportiva - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <!-- SweetAlert2: confirmaciones estilizadas (con fallback a native si no carga) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>
<?php
// ============================================
// MODO SOLO LECTURA: si el rol del usuario es 'solo_lectura',
// toda la pantalla de Gestión Deportiva queda de solo lectura (sin
// crear, modificar ni eliminar deportes, categorías ni predios). Con 'superadmin' funciona igual que siempre.
// ============================================
$solo_lectura = ($this->session->userdata('user_rol') === 'solo_lectura');
?>
<?php if ($solo_lectura): ?>
<div class="alert alert-info mb-3 border-0 shadow-sm rounded-pill py-2 small fw-semibold text-center" role="alert">
    <i class="bi bi-eye-fill me-1"></i> Estás en modo SOLO LECTURA: podés ver los deportes, categorías, cronogramas y predios, pero no crearlos, modificarlos ni eliminarlos.
</div>
<?php endif; ?>

<!-- PANEL GESTIÓN DEPORTIVA -->
<div class="card fx-card shadow-sm border-0 mb-4 card-inscriptos">
    <div class="card-header fx-card-header fw-bold text-secondary d-flex flex-column gap-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <!-- Título con ícono suave en lugar de texto plano -->
            <div class="d-flex align-items-center gap-2">
                <span class="fx-header-icon d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-trophy-fill text-warning"></i>
                </span>
                <span class="fx-header-titulo">Gestión Deportiva <u><?= LUGAR_OLIMPICO2 ?></u></span>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4 mb-5">
    <div class="row g-4">
        
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-calendar-event text-success me-2"></i>Deportes</h6>
                    <?php if (!$solo_lectura): ?>
                    <button class="btn btn-xs btn-success rounded-pill px-2 py-1 small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalDeporte">
                        <i class="bi bi-plus-circle me-1"></i> Nuevo Deporte
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-3 scroll-panel">
                    <div class="row g-2">
                        <?php if(!empty($deportes)): ?>
                            <?php foreach($deportes as $d): ?>
                                <div class="col-12">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                            <div class="d-flex flex-column gap-1">
                                                <h6 class="fw-bold text-primary m-0 text-uppercase small">
                                                    <i class="bi bi-trophy-fill me-2 text-warning"></i><?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>
                                                </h6>
                                                <div class="d-flex flex-wrap gap-1 align-items-center" style="font-size:0.7rem;">
                                                    <span class="badge bg-dark border-0" style="font-size:0.65rem;">
                                                        <i class="bi bi-people-fill me-1"></i><?= intval($d['total_inscriptos'] ?? 0) ?> Inscriptos
                                                    </span>
                                                    <?php $tot_fx = intval($d['total_fixtures'] ?? 0); ?>
                                                    <?php if ($tot_fx > 0): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem;" title="Este deporte tiene cronograma cargado">
                                                            <i class="bi bi-calendar-check-fill me-1"></i>Fixture cargado (<?= $tot_fx ?>)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:0.65rem;" title="Este deporte aún no tiene partidos en el fixture">
                                                            <i class="bi bi-calendar-x-fill me-1"></i>Sin fixture
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.65rem;">
                                                        <i class="bi bi-gender-female me-1"></i><?= intval($d['total_mujeres'] ?? 0) ?> Mujeres
                                                    </span>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.65rem;">
                                                        <i class="bi bi-gender-male me-1"></i><?= intval($d['total_hombres'] ?? 0) ?> Hombres
                                                    </span>
                                                    <?php if(!empty($d['fiscales'])): ?>
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle text-truncate" style="font-size:0.65rem; max-width: 320px;" title="<?= htmlspecialchars($d['fiscales'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="bi bi-person-badge me-1"></i>Fiscales: <?= htmlspecialchars($d['fiscales'], ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!$solo_lectura): ?>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="text-muted btn-accion-panel btn-editar" data-bs-toggle="modal" data-bs-target="#modalEditarDeporte" data-id="<?= $d['id_deporte'] ?>" data-nombre="<?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>" data-genero="<?= $d['genero'] ?>" data-fiscales="<?= htmlspecialchars($d['fiscales'] ?? '', ENT_QUOTES, 'UTF-8') ?>" title="Editar Deporte">
                                                    <i class="bi bi-pencil-square fs-6"></i>
                                                </button>
                                                <a href="<?= base_url('Inscripciones/eliminar_deporte/'.$d['id_deporte']) ?>" class="text-muted btn-accion-panel btn-eliminar" data-confirm="¿Estás seguro de que querés eliminar el deporte «<?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>»? Se borrarán sus categorías asociadas." title="Eliminar Deporte">
                                                    <i class="bi bi-trash3-fill fs-6"></i>
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="list-group list-group-flush bg-transparent">
                                            <?php if(!empty($d['categorias'])): ?>
                                                <?php foreach($d['categorias'] as $c): ?>
                                                    <div class="list-group-item bg-transparent px-0 py-1 border-0 d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
                                                        <span class="text-dark"><i class="bi bi-circle-fill text-muted me-1" style="font-size: 0.4rem;"></i> <?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?> 
                                                            <small class="text-muted">(<?= $c['genero_categoria'] ?? 'TODOS' ?>)</small>
                                                        </span>
                                                        <span class="badge bg-light text-dark border-0 p-0 text-muted"><i class="bi bi-person me-1"></i><?= $c['cantidad_inscriptos'] ?> inscriptos</span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <small class="text-muted italic small">Sin categorías asignadas</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-3 text-muted small">No hay fixture cargado.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-tags-fill text-primary me-2"></i>Listado de Categorías</h6>
                    <?php if (!$solo_lectura): ?>
                    <button class="btn btn-xs btn-primary rounded-pill px-2 py-1 small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                        <i class="bi bi-plus-circle me-1"></i>Nueva Categoría
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-3 scroll-panel">
                    <!-- Filtro por deporte -->
                    <div class="mb-2">
                        <select id="filtro_deporte_categorias" class="form-select form-select-sm rounded-pill fw-bold text-muted" style="font-size: 0.78rem;">
                            <option value="">🔽 Filtrar por deporte (todos)</option>
                            <?php if(!empty($deportes)): ?>
                                <?php foreach($deportes as $df): ?>
                                    <option value="<?= $df['id_deporte'] ?>"><?= htmlspecialchars($df['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle" style="font-size: 0.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Categoría / Deporte</th>
                                    <th class="text-center">Cronograma</th>
                                    <?php if (!$solo_lectura): ?>
                                    <th class="text-end">Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="tabla_categorias_body">
                                <?php 
                                $hay_categorias = false;
                                if(!empty($deportes)):
                                    foreach($deportes as $d):
                                        if(!empty($d['categorias'])):
                                            foreach($d['categorias'] as $c): 
                                                $hay_categorias = true;
                                ?>
                                                <tr data-deporte="<?= $d['id_deporte'] ?>">
                                                    <td>
                                                        <span class="fw-bold text-dark d-block"><?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        <small class="text-muted text-uppercase" style="font-size:0.7rem;"><?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <small class="d-block text-nowrap"><i class="bi bi-calendar3 me-1 text-muted"></i><?= !empty($c['dia_competencia']) ? date('d/m', strtotime($c['dia_competencia'])) : '--/--' ?> <i class="bi bi-clock me-1 ms-2 text-muted"></i><?= !empty($c['hora_competencia']) ? date('H:i', strtotime($c['hora_competencia'])) : '--:--' ?> hs</small>
                                                        <small class="d-block text-muted text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($c['nombre_lugar'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-geo-alt-fill me-1"></i><?= !empty($c['nombre_lugar']) ? htmlspecialchars($c['nombre_lugar'], ENT_QUOTES, 'UTF-8') : 'Sin lugar asignado' ?></small>
                                                    </td>
                                                    <?php if (!$solo_lectura): ?>
                                                    <td class="text-end">
                                                        <div class="d-flex justify-content-end gap-2">
                                                            <button type="button" class="text-muted btn-accion-panel btn-editar" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#modalEditarCategoria"
                                                                    data-id="<?= $c['id_categoria'] ?>"
                                                                    data-nombre="<?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-genero="<?= $c['genero_categoria'] ?? 'MIXTO' ?>"
                                                                    data-cupo="<?= $c['cupo_maximo'] ?>"
                                                                    data-lugar="<?= $c['id_lugar'] ?>"
                                                                    data-dia="<?= $c['dia_competencia'] ?>"
                                                                    data-hora="<?= $c['hora_competencia'] ?>"
                                                                    data-sistema="<?= htmlspecialchars($c['sistema_definicion'] ?? 'FASE_FINAL', ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-pv="<?= intval($c['puntosVictoria'] ?? 3) ?>"
                                                                    data-pe="<?= intval($c['puntosEmpate'] ?? 1) ?>"
                                                                    title="Editar Categoría">
                                                                <i class="bi bi-pencil-square fs-6"></i>
                                                            </button>
                                                            <a href="<?= base_url('Inscripciones/eliminar_categoria/'.$c['id_categoria']) ?>" class="text-muted btn-accion-panel btn-eliminar" data-confirm="¿Eliminar la categoría «<?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?>»?" title="Eliminar Categoría">
                                                                <i class="bi bi-trash3 fs-6"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <?php endif; ?>
                                                </tr>
                                <?php 
                                            endforeach;
                                        endif;
                                    endforeach;
                                endif;
                                if(!$hay_categorias): ?>
                                    <tr><td colspan="<?= $solo_lectura ? 2 : 3 ?>" class="text-center text-muted py-3">No hay categorías registradas.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Predios / Sedes</h6>
                    <?php if (!$solo_lectura): ?>
                    <button class="btn btn-xs btn-danger rounded-pill px-2 py-1 small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalLugar">
                        <i class="bi bi-plus-circle me-1"></i> Nueva Sede
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-3 scroll-panel">
                    <ul class="list-group list-group-flush">
                        <?php if(!empty($todos_los_lugares)): ?>
                            <?php foreach($todos_los_lugares as $lug): ?>
                                <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-start" style="border-bottom: 1px dashed #dee2e6 !important;">
                                    <div style="font-size: 0.8rem;">
                                        <span class="fw-bold text-dark d-block"><i class="bi bi-building me-1 text-muted"></i> <?= htmlspecialchars($lug['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <small class="text-muted"><i class="bi bi-signpost-fill me-1"></i> <?= !empty($lug['direccion']) ? htmlspecialchars($lug['direccion'], ENT_QUOTES, 'UTF-8') : 'Sin dirección cargada' ?></small>
                                    </div>
                                    <?php if (!$solo_lectura): ?>
                                    <div class="d-flex gap-2 ms-2">
                                        <button type="button" class="text-muted btn-accion-panel btn-editar"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarLugar"
                                                data-id="<?= $lug['id'] ?>"
                                                data-nombre="<?= htmlspecialchars($lug['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-direccion="<?= htmlspecialchars($lug['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                title="Editar Predio">
                                            <i class="bi bi-pencil-square fs-6"></i>
                                        </button>
                                        <a href="<?= base_url('Inscripciones/eliminar_lugar/'.$lug['id']) ?>" class="text-muted btn-accion-panel btn-eliminar" data-confirm="¿Seguro que querés quitar este predio?" title="Eliminar Predio">
                                            <i class="bi bi-x-circle fs-6"></i>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item bg-transparent text-center text-muted small py-3">No hay predios registrados.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalDeporte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/guardar_deporte') ?>" method="POST">
                <div class="modal-header bg-success text-white rounded-top-4">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-trophy-fill me-2 text-warning"></i>Nuevo Deporte General</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre de la Disciplina</label>
                        <input type="text" name="nombre_deporte" class="form-control" placeholder="Ej: Básquet, Pádel, Hockey" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Género Base</label>
                        <select name="genero" class="form-select" required>
                            <option value="MIXTO" selected>TODOS</option>
                            <option value="MASCULINO">MASCULINO (Toda la disciplina es de hombres)</option>
                            <option value="FEMENINO">FEMENINO (Toda la disciplina es de mujeres)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success px-3 fw-bold">Guardar Deporte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarDeporte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/editar_deporte') ?>" method="POST">
                <input type="hidden" name="id_deporte" id="edit_id_deporte">
                <div class="modal-header bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-pencil-square me-2"></i>Editar Deporte General</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre de la Disciplina</label>
                        <input type="text" name="nombre_deporte" id="edit_nombre_deporte" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Género / Rama</label>
                        <select name="genero" id="edit_genero_deporte" class="form-select" required>
                            <option value="MIXTO">TODOS</option>
                            <option value="MASCULINO">MASCULINO</option>
                            <option value="FEMENINO">FEMENINO</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Fiscales</label>
                        <textarea name="fiscales" id="edit_fiscales_deporte" class="form-control" rows="2" placeholder="Ej: Juan Pérez, María Gómez"></textarea>
                        <div class="form-text small">Texto libre con los fiscales asignados a este deporte. Se muestra en el fixture, la acreditación y los reportes.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-warning px-3 fw-bold">Actualizar Deporte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/guardar_categoria') ?>" method="POST">
                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-plus-circle-fill text-warning me-2"></i>Nueva Categoría y Cronograma</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Seleccionar Deporte</label>
                        <select name="id_deporte" id="select_deporte_categoria" class="form-select" required>
                            <option value="" data-genero="">-- Elegir Deporte --</option>
                            <?php if(!empty($todos_los_deportes)): ?>
                                <?php foreach($todos_los_deportes as $dep): ?>
                                    <option value="<?= $dep['id_deporte'] ?>" data-genero="<?= $dep['genero'] ?>"><?= htmlspecialchars($dep['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?> (<?= $dep['genero'] ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre de la Categoría</label>
                        <input type="text" name="nombre_categoria" class="form-control" placeholder="Ej: Libres, Senior, +40" required>
                    </div>

                    <div class="mb-3" id="contenedor_genero_categoria">
                        <label class="form-label small fw-bold text-muted text-uppercase">Sexo / Rama de la Categoría</label>
                        <select name="genero_categoria" id="genero_categoria" class="form-select" required>
                            <option value="MIXTO">TODOS</option>
                            <option value="MASCULINO">MASCULINO</option>
                            <option value="FEMENINO">FEMENINO</option>
                        </select>
                        <div id="info_hardcoded_genero" class="form-text text-primary fw-semibold d-none">
                            <i class="bi bi-info-circle-fill"></i> Heredado automáticamente del deporte.
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Cupo Máximo</label>
                            <input type="number" name="cupo_maximo" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Lugar / Predio</label>
                            <select name="id_lugar" class="form-select">
                                <option value="">-- Sin Asignar --</option>
                                <?php if(!empty($todos_los_lugares)): ?>
                                    <?php foreach($todos_los_lugares as $lug): ?>
                                        <option value="<?= $lug['id'] ?>"><?= htmlspecialchars($lug['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Día de Competencia</label>
                            <input type="date" name="dia_competencia" class="form-control" value="">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Horario de Inicio</label>
                            <input type="time" name="hora_competencia" class="form-control">
                        </div>
                    </div>

                    <!-- Sistema de definición: es POR CATEGORÍA (no por deporte) -->
                    <div class="mt-3 p-3 rounded-3 bg-light border">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">
                            <i class="bi bi-diagram-3-fill me-1 text-dark"></i>Sistema de definición (esta categoría)
                        </label>
                        <select name="sistema_definicion" id="nuevo_sistema_definicion" class="form-select form-select-sm">
                            <option value="FASE_FINAL">Fase final: Final + Tercer puesto</option>
                            <option value="PUNTAJE">Tabla de puntos (liga): gana / empata / pierde</option>
                        </select>
                        <div class="row g-2 mt-2 d-none" id="nuevo_contenedor_puntos">
                            <div class="col-6">
                                <label class="form-label small text-muted">Puntos por victoria</label>
                                <input type="number" name="puntos_victoria" id="nuevo_pv" class="form-control form-control-sm" value="3" min="0" max="10">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Puntos por empate</label>
                                <input type="number" name="puntos_empate" id="nuevo_pe" class="form-control form-control-sm" value="1" min="0" max="10">
                            </div>
                        </div>
                        <div class="form-text small">En "Tabla de puntos" la premiación arma el podio 1º/2º/3º desde las posiciones de la liga, sin necesidad de Final ni 3er puesto.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3 fw-bold">Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/editar_categoria') ?>" method="POST">
                <input type="hidden" name="id_categoria" id="edit_id_categoria">
                <div class="modal-header bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-pencil-square me-2"></i>Modificar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre de la Categoría</label>
                        <input type="text" name="nombre_categoria" id="edit_nombre_categoria" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Sexo / Rama</label>
                        <select name="genero_categoria" id="edit_genero_categoria" class="form-select" required>
                            <option value="MIXTO">TODOS</option>
                            <option value="MASCULINO">MASCULINO</option>
                            <option value="FEMENINO">FEMENINO</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Cupo Máximo</label>
                            <input type="number" name="cupo_maximo" id="edit_cupo_categoria" class="form-control" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Lugar / Predio</label>
                            <select name="id_lugar" id="edit_lugar_categoria" class="form-select">
                                <option value="">-- Sin Asignar --</option>
                                <?php if(!empty($todos_los_lugares)): ?>
                                    <?php foreach($todos_los_lugares as $lug): ?>
                                        <option value="<?= $lug['id'] ?>"><?= htmlspecialchars($lug['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Día de Competencia</label>
                            <input type="date" name="dia_competencia" id="edit_dia_categoria" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Horario de Inicio</label>
                            <input type="time" name="hora_competencia" id="edit_hora_categoria" class="form-control">
                        </div>
                    </div>

                    <!-- Sistema de definición: es POR CATEGORÍA (no por deporte) -->
                    <div class="mt-3 p-3 rounded-3 bg-light border">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">
                            <i class="bi bi-diagram-3-fill me-1 text-dark"></i>Sistema de definición (esta categoría)
                        </label>
                        <select name="sistema_definicion" id="edit_sistema_definicion" class="form-select form-select-sm">
                            <option value="FASE_FINAL">Fase final: Final + Tercer puesto</option>
                            <option value="PUNTAJE">Tabla de puntos (liga): gana / empata / pierde</option>
                        </select>
                        <div class="row g-2 mt-2" id="edit_contenedor_puntos">
                            <div class="col-6">
                                <label class="form-label small text-muted">Puntos por victoria</label>
                                <input type="number" name="puntos_victoria" id="edit_pv" class="form-control form-control-sm" value="3" min="0" max="10">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Puntos por empate</label>
                                <input type="number" name="puntos_empate" id="edit_pe" class="form-control form-control-sm" value="1" min="0" max="10">
                            </div>
                        </div>
                        <div class="form-text small">En "Tabla de puntos" la premiación arma el podio 1º/2º/3º desde las posiciones de la liga, sin necesidad de Final ni 3er puesto.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-warning px-3 fw-bold">Actualizar Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLugar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/guardar_lugar') ?>" method="POST">
                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-geo-alt-fill text-warning me-2"></i>Registrar Nuevo Predio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre del Lugar</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Club Estudiantes, Polideportivo Municipal" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Dirección (Opcional)</label>
                        <input type="text" name="direccion" class="form-control" placeholder="Ej: Av. San Martín 450">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-dark px-3 fw-bold">Registrar Predio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarLugar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/editar_lugar') ?>" method="POST">
                <input type="hidden" name="id_lugar" id="edit_id_lugar">
                <div class="modal-header bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-pencil-square me-2"></i>Modificar Predio Sede</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre del Lugar</label>
                        <input type="text" name="nombre" id="edit_nombre_lugar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Dirección</label>
                        <input type="text" name="direccion" id="edit_direccion_lugar" class="form-control">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-warning px-3 fw-bold">Actualizar Predio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// MODO SOLO LECTURA (viene del PHP de arriba según el rol del usuario):
// con 'solo_lectura' no se puede crear, modificar ni eliminar nada.
// Con 'superadmin' vale false y todo queda igual que siempre.
const SOLO_LECTURA = <?= $solo_lectura ? 'true' : 'false' ?>;

document.addEventListener('DOMContentLoaded', function () {

    // Seguridad solo_lectura: si somehow quedara algún formulario de escritura
    // visible (p. ej. abriendo el modal directo por URL), se bloquea el envío.
    if (SOLO_LECTURA) {
        document.querySelectorAll('form[action*="Inscripciones/"]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        title: 'Solo lectura',
                        text: 'Tu usuario es de solo lectura: no tenés permisos para guardar cambios.',
                        icon: 'warning',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    alert('Tu usuario es de solo lectura: no tenés permisos para guardar cambios.');
                }
            });
        });
    }
    
    /* ---------- Confirmación estilizada (SweetAlert2 si está cargado) ---------- */
    function confirmarAccion(texto, icono, btnOk, btnCancelar) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                title: '¿Confirmás?',
                text: texto,
                icon: icono || 'question',
                showCancelButton: true,
                confirmButtonText: btnOk || 'Sí, confirmar',
                cancelButtonText: btnCancelar || 'Cancelar',
                reverseButtons: true
            }).then(r => r.isConfirmed);
        }
        return Promise.resolve(window.confirm(texto));
    }

    // Enlaces de eliminación: reemplazan el confirm() nativo por SweetAlert2
    document.querySelectorAll('a[data-confirm]').forEach(function (enlace) {
        enlace.addEventListener('click', function (e) {
            e.preventDefault();
            var href = this.getAttribute('href');
            confirmarAccion(this.getAttribute('data-confirm'), 'warning', 'Sí, eliminar', 'Cancelar')
                .then(ok => { if (ok) window.location.href = href; });
        });
    });

    // --- Lógica del Modal Editar Deporte ---
    var modalEditar = document.getElementById('modalEditarDeporte');
    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function (event) {
            var boton = event.relatedTarget;
            document.getElementById('edit_id_deporte').value = boton.getAttribute('data-id');
            document.getElementById('edit_nombre_deporte').value = boton.getAttribute('data-nombre');
            document.getElementById('edit_genero_deporte').value = boton.getAttribute('data-genero');
            var campoFiscales = document.getElementById('edit_fiscales_deporte');
            if (campoFiscales) {
                campoFiscales.value = boton.getAttribute('data-fiscales') || '';
            }
        });
    }

    // --- Lógica Inteligente para Género de Nueva Categoría ---
    var selectDeporte = document.getElementById('select_deporte_categoria');
    var selectGeneroCat = document.getElementById('genero_categoria');
    var infoHardcoded = document.getElementById('info_hardcoded_genero');

    if (selectDeporte && selectGeneroCat) {
        selectDeporte.addEventListener('change', function() {
            var opcionSeleccionada = this.options[this.selectedIndex];
            var generoDeporte = opcionSeleccionada.getAttribute('data-genero');

            if (generoDeporte === 'MASCULINO' || generoDeporte === 'FEMENINO') {
                selectGeneroCat.value = generoDeporte;
                for (var i = 0; i < selectGeneroCat.options.length; i++) {
                    selectGeneroCat.options[i].disabled = (selectGeneroCat.options[i].value !== generoDeporte);
                }
                infoHardcoded.classList.remove('d-none');
            } else {
                for (var i = 0; i < selectGeneroCat.options.length; i++) {
                    selectGeneroCat.options[i].disabled = false;
                }
                if (generoDeporte === 'MIXTO') {
                    selectGeneroCat.value = 'MIXTO';
                }
                infoHardcoded.classList.add('d-none');
            }
        });
    }

    // --- Toggle de puntos en el modal NUEVA categoría ---
    var selSistemaNuevo = document.getElementById('nuevo_sistema_definicion');
    if (selSistemaNuevo) {
        selSistemaNuevo.addEventListener('change', function () {
            var cont = document.getElementById('nuevo_contenedor_puntos');
            if (cont) {
                cont.classList.toggle('d-none', this.value !== 'PUNTAJE');
            }
        });
    }

    // --- Lógica del Modal Editar Categoría ---
    var modalEditarCat = document.getElementById('modalEditarCategoria');
    if (modalEditarCat) {
        // Mostrar/ocultar los campos de puntos según el sistema elegido
        var togglePuntosEdit = function () {
            var sel = document.getElementById('edit_sistema_definicion');
            var cont = document.getElementById('edit_contenedor_puntos');
            if (sel && cont) {
                cont.classList.toggle('d-none', sel.value !== 'PUNTAJE');
            }
        };
        var selSistemaEdit = document.getElementById('edit_sistema_definicion');
        if (selSistemaEdit) {
            selSistemaEdit.addEventListener('change', togglePuntosEdit);
        }

        modalEditarCat.addEventListener('show.bs.modal', function (event) {
            var boton = event.relatedTarget;
            document.getElementById('edit_id_categoria').value = boton.getAttribute('data-id');
            document.getElementById('edit_nombre_categoria').value = boton.getAttribute('data-nombre');
            document.getElementById('edit_genero_categoria').value = boton.getAttribute('data-genero');
            document.getElementById('edit_cupo_categoria').value = boton.getAttribute('data-cupo');
            document.getElementById('edit_lugar_categoria').value = boton.getAttribute('data-lugar');
            document.getElementById('edit_dia_categoria').value = boton.getAttribute('data-dia');
            document.getElementById('edit_hora_categoria').value = boton.getAttribute('data-hora');

            // Sistema de definición POR CATEGORÍA (defaults si la base aún no migró)
            var sistema = boton.getAttribute('data-sistema') || 'FASE_FINAL';
            document.getElementById('edit_sistema_definicion').value = (sistema === 'PUNTAJE') ? 'PUNTAJE' : 'FASE_FINAL';
            document.getElementById('edit_pv').value = boton.getAttribute('data-pv') || 3;
            document.getElementById('edit_pe').value = boton.getAttribute('data-pe') || 1;
            togglePuntosEdit();
        });
    }

    // --- Lógica del Modal Editar Lugar ---
    var modalEditarLug = document.getElementById('modalEditarLugar');
    if (modalEditarLug) {
        modalEditarLug.addEventListener('show.bs.modal', function (event) {
            var boton = event.relatedTarget;
            document.getElementById('edit_id_lugar').value = boton.getAttribute('data-id');
            document.getElementById('edit_nombre_lugar').value = boton.getAttribute('data-nombre');
            document.getElementById('edit_direccion_lugar').value = boton.getAttribute('data-direccion');
        });
    }

    // --- Filtro por deporte en el Listado de Categorías ---
    var filtroDeportes = document.getElementById('filtro_deporte_categorias');
    if (filtroDeportes) {
        filtroDeportes.addEventListener('change', function () {
            var valor = this.value;
            var filas = document.querySelectorAll('#tabla_categorias_body tr[data-deporte]');
            var visibles = 0;
            filas.forEach(function (fila) {
                if (valor === '' || fila.getAttribute('data-deporte') === valor) {
                    fila.style.display = '';
                    visibles++;
                } else {
                    fila.style.display = 'none';
                }
            });
            // Mensaje cuando el filtro no arroja resultados
            var vacio = document.getElementById('filtro_sin_resultados');
            if (!vacio && visibles === 0) {
                var tbody = document.getElementById('tabla_categorias_body');
                if (tbody) {
                    var tr = document.createElement('tr');
                    tr.id = 'filtro_sin_resultados';
                    tr.innerHTML = '<td colspan="3" class="text-center text-muted py-3">No hay categorías para este deporte.</td>';
                    tbody.appendChild(tr);
                }
            } else if (vacio && visibles > 0) {
                vacio.remove();
            }
        });
    }
});
</script>
</body>
</html>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
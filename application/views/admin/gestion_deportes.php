<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Deportiva - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px 0;
            border-bottom: 5px solid #ffc107;
        }
        .nav-menu { background: #fff; border-bottom: 1px solid #dee2e6; }
        .nav-menu .nav-link { color: #495057; font-weight: 500; padding: 12px 20px; }
        .nav-menu .nav-link.active { color: #1e3c72; border-bottom: 3px solid #1e3c72; border-radius: 0; }
        .btn-accion-panel { opacity: 0.6; transition: all 0.2s ease-in-out; cursor: pointer; text-decoration: none; border: 0; background: none; padding: 0; }
        .btn-accion-panel:hover { opacity: 1; transform: scale(1.1); }
        .btn-editar:hover { color: #ffc107 !important; }
        .btn-eliminar:hover { color: #dc3545 !important; }
        .scroll-panel { max-height: 650px; overflow-y: auto; padding-right: 5px; }
        .scroll-panel::-webkit-scrollbar { width: 6px; }
        .scroll-panel::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .scroll-panel::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    </style>
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>

<div class="container-fluid px-4 mb-5">
    <div class="row g-4">
        
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-calendar-event text-primary me-2"></i>Deportes y Cronograma</h6>
                    <button class="btn btn-xs btn-success rounded-pill px-2 py-1 small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalDeporte">
                        <i class="bi bi-trophy me-1"></i> + Nuevo Deporte
                    </button>
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
                                                <div>
                                                    <?php if($d['genero'] == 'MASCULINO'): ?>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.6rem;"><i class="bi bi-gender-male me-1"></i>MASCULINO</span>
                                                    <?php elseif($d['genero'] == 'FEMENINO'): ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.6rem;"><i class="bi bi-gender-female me-1"></i>FEMENINO</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:0.6rem;"><i class="bi bi-gender-ambiguous me-1"></i>TODOS</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <button type="button" class="text-muted btn-accion-panel btn-editar" data-bs-toggle="modal" data-bs-target="#modalEditarDeporte" data-id="<?= $d['id_deporte'] ?>" data-nombre="<?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>" data-genero="<?= $d['genero'] ?>" title="Editar Deporte">
                                                    <i class="bi bi-pencil-square fs-6"></i>
                                                </button>
                                                <a href="<?= base_url('Inscripciones/eliminar_deporte/'.$d['id_deporte']) ?>" class="text-muted btn-accion-panel btn-eliminar" onclick="return confirm('¿Estás seguro de que querés eliminar el deporte «<?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>»? Se borrarán sus categorías asociadas.');" title="Eliminar Deporte">
                                                    <i class="bi bi-trash3-fill fs-6"></i>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div class="list-group list-group-flush bg-transparent">
                                            <?php if(!empty($d['categorias'])): ?>
                                                <?php foreach($d['categorias'] as $c): ?>
                                                    <div class="list-group-item bg-transparent px-0 py-1 border-0 d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
                                                        <span class="text-dark"><i class="bi bi-circle-fill text-muted me-1" style="font-size: 0.4rem;"></i> <?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?> 
                                                            <small class="text-muted">(<?= $c['genero_categoria'] ?? 'TODOS' ?>)</small>
                                                        </span>
                                                        <span class="badge bg-light text-dark border-0 p-0 text-muted"><i class="bi bi-person me-1"></i><?= $c['cupo_maximo'] ?></span>
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
                    <button class="btn btn-xs btn-primary rounded-pill px-2 py-1 small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                        <i class="bi bi-plus-circle me-1"></i> + Nueva Categoría
                    </button>
                </div>
                <div class="card-body p-3 scroll-panel">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle" style="font-size: 0.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Categoría / Deporte</th>
                                    <th class="text-center">Sexo</th>
                                    <th class="text-center">Cronograma</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hay_categorias = false;
                                if(!empty($deportes)):
                                    foreach($deportes as $d):
                                        if(!empty($d['categorias'])):
                                            foreach($d['categorias'] as $c): 
                                                $hay_categorias = true;
                                ?>
                                                <tr>
                                                    <td>
                                                        <span class="fw-bold text-dark d-block"><?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        <small class="text-muted text-uppercase" style="font-size:0.7rem;"><?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-light text-dark border" style="font-size: 0.65rem;"><?= $c['genero_categoria'] ?? 'TODOS' ?></span>
                                                    </td>
                                                    <td>
                                                        <small class="d-block text-nowrap"><i class="bi bi-calendar3 me-1 text-muted"></i><?= !empty($c['dia_competencia']) ? date('d/m', strtotime($c['dia_competencia'])) : '--/--' ?></small>
                                                        <small class="d-block text-muted text-nowrap"><i class="bi bi-clock me-1"></i><?= !empty($c['hora_competencia']) ? date('H:i', strtotime($c['hora_competencia'])) : '--:--' ?> hs</small>
                                                    </td>
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
                                                                    title="Editar Categoría">
                                                                <i class="bi bi-pencil-square fs-6"></i>
                                                            </button>
                                                            <a href="<?= base_url('Inscripciones/eliminar_categoria/'.$c['id_categoria']) ?>" class="text-muted btn-accion-panel btn-eliminar" onclick="return confirm('¿Eliminar la categoría «<?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?>»?');" title="Eliminar Categoría">
                                                                <i class="bi bi-trash3 fs-6"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                <?php 
                                            endforeach;
                                        endif;
                                    endforeach;
                                endif;
                                if(!$hay_categorias): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3">No hay categorías registradas.</td></tr>
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
                    <button class="btn btn-xs btn-outline-dark rounded-pill px-2 py-1 small" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalLugar">
                        <i class="bi bi-plus me-1"></i> Sede
                    </button>
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
                                        <a href="<?= base_url('Inscripciones/eliminar_lugar/'.$lug['id']) ?>" class="text-muted btn-accion-panel btn-eliminar" onclick="return confirm('¿Seguro que querés quitar este predio?');" title="Eliminar Predio">
                                            <i class="bi bi-x-circle fs-6"></i>
                                        </a>
                                    </div>
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
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Día de Competencia</label>
                            <input type="date" name="dia_competencia" id="edit_dia_categoria" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Horario de Inicio</label>
                            <input type="time" name="hora_competencia" id="edit_hora_categoria" class="form-control">
                        </div>
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
document.addEventListener('DOMContentLoaded', function () {
    
    // --- Lógica del Modal Editar Deporte ---
    var modalEditar = document.getElementById('modalEditarDeporte');
    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function (event) {
            var boton = event.relatedTarget;
            document.getElementById('edit_id_deporte').value = boton.getAttribute('data-id');
            document.getElementById('edit_nombre_deporte').value = boton.getAttribute('data-nombre');
            document.getElementById('edit_genero_deporte').value = boton.getAttribute('data-genero');
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

    // --- Lógica del Modal Editar Categoría ---
    var modalEditarCat = document.getElementById('modalEditarCategoria');
    if (modalEditarCat) {
        modalEditarCat.addEventListener('show.bs.modal', function (event) {
            var boton = event.relatedTarget;
            document.getElementById('edit_id_categoria').value = boton.getAttribute('data-id');
            document.getElementById('edit_nombre_categoria').value = boton.getAttribute('data-nombre');
            document.getElementById('edit_genero_categoria').value = boton.getAttribute('data-genero');
            document.getElementById('edit_cupo_categoria').value = boton.getAttribute('data-cupo');
            document.getElementById('edit_lugar_categoria').value = boton.getAttribute('data-lugar');
            document.getElementById('edit_dia_categoria').value = boton.getAttribute('data-dia');
            document.getElementById('edit_hora_categoria').value = boton.getAttribute('data-hora');
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
});
</script>
</body>
</html>
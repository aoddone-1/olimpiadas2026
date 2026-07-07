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
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>

<div class="container-fluid px-4 mb-5">
    
    <!-- Deportes -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center">
                <i class="bi bi-trophy-fill text-warning me-2"></i> 
                <span>Deportes Disciplinas</span>
                <span class="badge bg-secondary ms-2"><?= count($deportes) ?> deportes</span>
            </div>
            
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDeporte">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Deporte
            </button>
        </div>
        
        <div class="card-body">
            <div class="row g-3">
                <?php if(!empty($deportes)): ?>
                    <?php foreach($deportes as $d): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 border shadow-sm">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold text-primary m-0 text-uppercase small">
                                        <i class="bi bi-trophy-fill me-2 text-warning"></i><?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>
                                    </h6>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-editar" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarDeporte" 
                                                data-id="<?= $d['id_deporte'] ?>" 
                                                data-nombre="<?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>" 
                                                data-genero="<?= $d['genero'] ?>" 
                                                title="Editar Deporte">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="<?= base_url('Inscripciones/eliminar_deporte/'.$d['id_deporte']) ?>" 
                                           class="btn btn-sm btn-outline-danger btn-eliminar" 
                                           onclick="return confirm('¿Estás seguro de que querés eliminar el deporte «<?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>»? Se borrarán sus categorías asociadas.');" 
                                           title="Eliminar Deporte">
                                            <i class="bi bi-trash3-fill"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <?php if($d['genero'] == 'MASCULINO'): ?>
                                            <span class="badge bg-primary-subtle text-primary border"><i class="bi bi-gender-male me-1"></i>MASCULINO</span>
                                        <?php elseif($d['genero'] == 'FEMENINO'): ?>
                                            <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-gender-female me-1"></i>FEMENINO</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-gender-ambiguous me-1"></i>TODOS</span>
                                        <?php endif; ?>
                                    </div>
                                    <hr class="my-2">
                                    <small class="fw-semibold text-muted text-uppercase" style="font-size: 0.7rem;">Categorías:</small>
                                    <ul class="list-group list-group-flush mt-2">
                                        <?php if(!empty($d['categorias'])): ?>
                                            <?php foreach($d['categorias'] as $c): ?>
                                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center small">
                                                    <span class="text-dark">
                                                        <i class="bi bi-circle-fill text-muted me-1" style="font-size: 0.4rem;"></i> 
                                                        <?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?>
                                                        <small class="text-muted">(<?= $c['genero_categoria'] ?? 'TODOS' ?>)</small>
                                                    </span>
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="bi bi-person me-1"></i><?= $c['cupo_maximo'] ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="list-group-item px-0 py-2 text-muted small italic">Sin categorías asignadas</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No hay deportes cargados aún
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Categorías -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center">
                <i class="bi bi-tags-fill text-primary me-2"></i> 
                <span>Listado de Categorías</span>
                <span class="badge bg-secondary ms-2" id="contador-categorias">0 filas</span>
            </div>
            
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                <i class="bi bi-plus-circle me-1"></i> Nueva Categoría
            </button>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tabla-categorias">
                    <thead class="table-light">
                        <tr>
                            <th>Categoría / Deporte</th>
                            <th class="text-center">Sexo</th>
                            <th class="text-center">Cronograma</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-categorias">
                        <?php 
                        $hay_categorias = false;
                        if(!empty($deportes)):
                            foreach($deportes as $d):
                                if(!empty($d['categorias'])):
                                    foreach($d['categorias'] as $c): 
                                        $hay_categorias = true;
                        ?>
                                        <tr id="categoria-fila-<?= $c['id_categoria'] ?>">
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <small class="text-muted text-uppercase" style="font-size:0.7rem;"><?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border" style="font-size: 0.65rem;"><?= $c['genero_categoria'] ?? 'TODOS' ?></span>
                                            </td>
                                            <td class="text-center">
                                                <small class="d-block text-nowrap"><i class="bi bi-calendar3 me-1 text-muted"></i><?= !empty($c['dia_competencia']) ? date('d/m', strtotime($c['dia_competencia'])) : '--/--' ?></small>
                                                <small class="d-block text-muted text-nowrap"><i class="bi bi-clock me-1"></i><?= !empty($c['hora_competencia']) ? date('H:i', strtotime($c['hora_competencia'])) : '--:--' ?> hs</small>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-editar" 
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
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="<?= base_url('Inscripciones/eliminar_categoria/'.$c['id_categoria']) ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('¿Eliminar la categoría «<?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?>»?');" 
                                                   title="Eliminar Categoría">
                                                    <i class="bi bi-trash3"></i>
                                                </a>
                                            </td>
                                        </tr>
                        <?php 
                                    endforeach;
                                endif;
                            endforeach;
                        endif;
                        if(!$hay_categorias): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No hay categorías registradas
                                    </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Predios / Sedes -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center">
                <i class="bi bi-geo-alt-fill text-danger me-2"></i> 
                <span>Predios / Sedes</span>
                <span class="badge bg-secondary ms-2"><?= count($todos_los_lugares) ?> sedes</span>
            </div>
            
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalLugar">
                <i class="bi bi-plus-circle me-1"></i> Nueva Sede
            </button>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tabla-lugares">
                    <thead class="table-light">
                        <tr>
                            <th>Sede / Predio</th>
                            <th>Dirección</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($todos_los_lugares)): ?>
                            <?php foreach($todos_los_lugares as $lug): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark d-block"><i class="bi bi-building me-1 text-muted"></i> <?= htmlspecialchars($lug['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><i class="bi bi-signpost-fill me-1"></i> <?= !empty($lug['direccion']) ? htmlspecialchars($lug['direccion'], ENT_QUOTES, 'UTF-8') : 'Sin dirección cargada' ?></small>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-editar"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarLugar"
                                                data-id="<?= $lug['id'] ?>"
                                                data-nombre="<?= htmlspecialchars($lug['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-direccion="<?= htmlspecialchars($lug['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                title="Editar Predio">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="<?= base_url('Inscripciones/eliminar_lugar/'.$lug['id']) ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('¿Seguro que querés quitar este predio?');" 
                                           title="Eliminar Predio">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No hay predios registrados
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="modalDeporte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/guardar_deporte') ?>" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-trophy-fill me-2 text-warning"></i>Nuevo Deporte</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la Disciplina</label>
                        <input type="text" name="nombre_deporte" class="form-control" placeholder="Ej: Básquet, Pádel, Hockey" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Género Base</label>
                        <select name="genero" class="form-select" required>
                            <option value="MIXTO" selected>TODOS</option>
                            <option value="MASCULINO">MASCULINO</option>
                            <option value="FEMENINO">FEMENINO</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Guardar Deporte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarDeporte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/editar_deporte') ?>" method="POST">
                <input type="hidden" name="id_deporte" id="edit_id_deporte">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Editar Deporte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la Disciplina</label>
                        <input type="text" name="nombre_deporte" id="edit_nombre_deporte" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Género / Rama</label>
                        <select name="genero" id="edit_genero_deporte" class="form-select" required>
                            <option value="MIXTO">TODOS</option>
                            <option value="MASCULINO">MASCULINO</option>
                            <option value="FEMENINO">FEMENINO</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-circle me-1"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/guardar_categoria') ?>" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill me-2"></i>Nueva Categoría</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Seleccionar Deporte</label>
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
                        <label class="form-label fw-semibold">Nombre de la Categoría</label>
                        <input type="text" name="nombre_categoria" class="form-control" placeholder="Ej: Libres, Senior, +40" required>
                    </div>

                    <div class="mb-3" id="contenedor_genero_categoria">
                        <label class="form-label fw-semibold">Sexo / Rama de la Categoría</label>
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
                            <label class="form-label fw-semibold">Cupo Máximo</label>
                            <input type="number" name="cupo_maximo" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Lugar / Predio</label>
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
                            <label class="form-label fw-semibold">Día de Competencia</label>
                            <input type="date" name="dia_competencia" class="form-control" value="">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Horario de Inicio</label>
                            <input type="time" name="hora_competencia" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/editar_categoria') ?>" method="POST">
                <input type="hidden" name="id_categoria" id="edit_id_categoria">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Editar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la Categoría</label>
                        <input type="text" name="nombre_categoria" id="edit_nombre_categoria" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sexo / Rama</label>
                        <select name="genero_categoria" id="edit_genero_categoria" class="form-select" required>
                            <option value="MIXTO">TODOS</option>
                            <option value="MASCULINO">MASCULINO</option>
                            <option value="FEMENINO">FEMENINO</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Cupo Máximo</label>
                            <input type="number" name="cupo_maximo" id="edit_cupo_categoria" class="form-control" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Lugar / Predio</label>
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
                            <label class="form-label fw-semibold">Día de Competencia</label>
                            <input type="date" name="dia_competencia" id="edit_dia_categoria" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Horario de Inicio</label>
                            <input type="time" name="hora_competencia" id="edit_hora_categoria" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-circle me-1"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLugar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/guardar_lugar') ?>" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-geo-alt-fill me-2"></i>Nueva Sede</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del Lugar</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Club Estudiantes, Polideportivo Municipal" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dirección (Opcional)</label>
                        <input type="text" name="direccion" class="form-control" placeholder="Ej: Av. San Martín 450">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Guardar Sede</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarLugar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form autocomplete='off' action="<?= base_url('Inscripciones/editar_lugar') ?>" method="POST">
                <input type="hidden" name="id_lugar" id="edit_id_lugar">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Editar Sede</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del Lugar</label>
                        <input type="text" name="nombre" id="edit_nombre_lugar" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dirección</label>
                        <input type="text" name="direccion" id="edit_direccion_lugar" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-circle me-1"></i> Actualizar</button>
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
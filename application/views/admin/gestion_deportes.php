<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Deportiva - Olimpiadas 2026</title>
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
        .btn-eliminar-deporte { opacity: 0.6; transition: all 0.2s ease-in-out; }
        .btn-eliminar-deporte:hover { opacity: 1; color: #dc3545 !important; transform: scale(1.1); }
    </style>
</head>
<body>

<div class="hero-section text-center">
    <h1 class="h4 fw-bold text-uppercase m-0">Panel General de Monitoreo</h1>
    <p class="lead small m-0 text-white-50">XXXVIII Olimpiadas Nacionales IV - La Pampa 2026</p>
</div>

<div class="nav-menu mb-4 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('Inscripciones/login_staff') ?>"><i class="bi bi-people-fill me-1"></i> Inscriptos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="<?= base_url('Inscripciones/gestion_deportes') ?>"><i class="bi bi-trophy-fill me-1"></i> Gestión Deportiva</a>
            </li>
            <!-- Agregamos también acá el acceso al Sondeo que armamos antes -->
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('Inscripciones/monitoreo_encuesta') ?>"><i class="bi bi-bar-chart-line-fill me-1"></i> Sondeo Inicial</a>
            </li>
        </ul>
        <a href="<?= base_url('Inscripciones/logout_staff') ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">
            <i class="bi bi-box-arrow-right me-1"></i> Salir
        </a>
    </div>
</div>

<div class="container mb-5">

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold text-dark m-0"><i class="bi bi-calendar-event text-primary me-2"></i>Fixture y Cronograma de Deportes</h5>
            <div class="gap-2 d-flex flex-wrap">
                <!-- NUEVO BOTÓN: REGISTRAR DEPORTE -->
                <button class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalDeporte">
                    <i class="bi bi-trophy me-1"></i> + Nuevo Deporte
                </button>
                <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                    <i class="bi bi-plus-circle me-1"></i> Nueva Categoría
                </button>
                <button class="btn btn-sm btn-outline-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalLugar">
                    <i class="bi bi-geo-alt-fill me-1"></i> + Registrar Predio
                </button>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <?php if(!empty($deportes)): ?>
                    <?php foreach($deportes as $d): ?>
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-light rounded-3 h-100 border">
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                    <h6 class="fw-bold text-primary m-0 text-uppercase">
                                        <i class="bi bi-trophy-fill me-2 text-warning"></i><?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>
                                    </h6>
                                    
                                    <!-- BOTÓN PARA ELIMINAR EL DEPORTE -->
                                    <a href="<?= base_url('Inscripciones/eliminar_deporte/'.$d['id_deporte']) ?>" 
                                       class="text-muted btn-eliminar-deporte" 
                                       onclick="return confirm('¿Estás seguro de que querés eliminar el deporte «<?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>»? Se borrarán también sus categorías asociadas.');"
                                       title="Eliminar Deporte">
                                        <i class="bi bi-trash3-fill fs-6"></i>
                                    </a>
                                </div>
                                
                                <div class="list-group list-group-flush bg-transparent">
                                    <?php if(!empty($d['categorias'])): ?>
                                        <?php foreach($d['categorias'] as $c): ?>
                                            <div class="list-group-item bg-transparent px-0 py-2" style="border-bottom: 1px dashed #dee2e6 !important;">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <span class="fw-bold text-dark small d-block"><?= htmlspecialchars($c['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        <small class="text-muted d-block" style="font-size: 0.72rem;">
                                                            <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= !empty($c['nombre_lugar']) ? htmlspecialchars($c['nombre_lugar'], ENT_QUOTES, 'UTF-8') : 'Lugar no asignado' ?>
                                                        </small>
                                                        <small class="text-muted d-block" style="font-size: 0.72rem;">
                                                            <i class="bi bi-calendar3 me-1"></i><?= !empty($c['dia_competencia']) ? date('d/m', strtotime($c['dia_competencia'])) : '--/--' ?>
                                                            | <i class="bi bi-clock me-1"></i><?= !empty($c['hora_competencia']) ? date('H:i', strtotime($c['hora_competencia'])) : '--:--' ?> hs
                                                        </small>
                                                    </div>
                                                    <span class="badge bg-secondary-subtle text-secondary border small rounded-pill">
                                                        <i class="bi bi-person-fill"></i> <?= $c['cupo_maximo'] ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <small class="text-muted small italic d-block py-2">Sin categorías</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-3 text-muted small">No hay fixture cargado aún.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===================================================
     NUEVO MODAL: REGISTRAR DEPORTE
     =================================================== -->
<div class="modal fade" id="modalDeporte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= base_url('Inscripciones/guardar_deporte') ?>" method="POST">
                <div class="modal-header bg-success text-white rounded-top-4">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-trophy-fill me-2 text-warning"></i>Nuevo Deporte General</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre de la Disciplina</label>
                        <input type="text" name="nombre_deporte" class="form-control" placeholder="Ej: Básquet, Pádel, Hockey" required>
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

<!-- MODAL: NUEVA CATEGORÍA -->
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= base_url('Inscripciones/guardar_categoria') ?>" method="POST">
                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-plus-circle-fill text-warning me-2"></i>Nueva Categoría y Cronograma</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Seleccionar Deporte</label>
                        <select name="id_deporte" class="form-select" required>
                            <option value="">-- Elegir Deporte --</option>
                            <?php if(!empty($todos_los_deportes)): ?>
                                <?php foreach($todos_los_deportes as $dep): ?>
                                    <option value="<?= $dep['id_deporte'] ?>"><?= $dep['nombre_deporte'] ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre de la Categoría</label>
                        <input type="text" name="nombre_categoria" class="form-control" placeholder="Ej: Libres Masculino" required>
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
                                        <option value="<?= $lug['id'] ?>"><?= $lug['nombre'] ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Día de Competencia</label>
                            <input type="date" name="dia_competencia" class="form-control" value="2026-11-14">
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

<!-- MODAL: REGISTRAR LUGAR -->
<div class="modal fade" id="modalLugar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= base_url('Inscripciones/guardar_lugar') ?>" method="POST">
                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-geo-alt-fill text-warning me-2"></i>Registrar Nuevo Predio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre del Lugar</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Dirección (Opcional)</label>
                        <input type="text" name="direccion" class="form-control">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
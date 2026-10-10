<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesa de Control - <?= NOMBRE_META; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 25px 0;
            border-bottom: 5px solid #ffc107;
            margin-bottom: 25px;
        }

        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .section-title { color: #1e3c72; border-left: 5px solid #ffc107; padding-left: 15px; margin-bottom: 20px; }
        
        .btn-control { 
            padding: 16px; 
            border-radius: 12px; 
            font-weight: bold; 
            font-size: 1.05rem;
            transition: all 0.2s ease-in-out;
            border: none;
        }
        .btn-control-pendiente {
            background-color: #1e3c72;
            color: white;
        }
        .btn-control-pendiente:hover {
            background-color: #2a5298;
            color: white;
            transform: translateY(-2px);
        }
        .btn-control-deporte {
            background-color: white;
            color: #1e3c72;
            border: 2px solid #1e3c72 !important;
        }
        .btn-control-deporte:hover {
            background-color: #f4f7f6;
            transform: translateY(-2px);
        }
        .btn-control-listo {
            background-color: #d1e7dd;
            color: #0f5132;
            cursor: default;
            pointer-events: none;
        }

        .badge-rol { font-size: 0.8rem; padding: 6px 12px; border-radius: 20px; }

        /* Botones de entrega de kit (delegado) */
        .btn-control-kit {
            background: linear-gradient(135deg, #2e7d32 0%, #43a047 100%);
            color: white;
        }
        .btn-control-kit:hover {
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
            color: white;
            transform: translateY(-2px);
        }
        .btn-control-kit-revertir {
            background-color: #d1e7dd;
            color: #0f5132;
            transition: all 0.25s ease-in-out;
        }
        .btn-control-kit-revertir:hover {
            background-color: #f8d7da !important;
            color: #842029 !important;
        }
        .btn-control-kit-revertir:hover .badge {
            background-color: #dc3545 !important;
        }

        .row-accion {
            border-left: 5px solid #1e3c72 !important;
            background: #fff;
            border-radius: 12px;
        }
        .row-accion-lista {
            border-left: 5px solid #198754 !important;
            background: #fff;
            border-radius: 12px;
        }

        .btn-control-listo-revertir {
            background-color: #d1e7dd;
            color: #0f5132;
            transition: all 0.25s ease-in-out;
        }

        /* El cambio a rojo suave SOLO ocurre cuando se pasa el mouse por arriba */
        .btn-control-listo-revertir:hover {
            background-color: #f8d7da !important;
            color: #842029 !important;
            border: none !important;
        }

        /* Opcional: Cambia el texto del badge a "Quitar" al pasar el mouse */
        .btn-control-listo-revertir:hover .badge {
            background-color: #dc3545 !important;
        }
    </style>
</head>
<body>

<div class="hero-section text-center">
    <h1 class="h4 fw-bold text-uppercase m-0">Panel General de Monitoreo</h1>
    <p class="lead small m-0 text-white-50"><?= NOMBRE_SITIO; ?> <br/> <?= LUGAR_OLIMPICO; ?></p>
</div>

<div class="container mb-5" style="max-width: 600px;">

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm border-start border-4 border-warning">
        <div>
            <span class="fw-semibold text-muted small d-block">
                <i class="bi bi-person-circle text-primary me-1"></i> Staff: <?= $this->session->userdata('user_nombre') ?>
            </span>
            <span class="badge bg-primary text-uppercase px-2 py-1 small mt-1"><?= $this->session->userdata('user_rol') ?></span>
        </div>
        <div>
            <a href="<?= base_url('Inscripciones/login') ?>" class="btn btn-sm btn-outline-primary fw-bold px-3 py-2 rounded-3">
                <i class="bi bi-speedometer2 me-1"></i> Ver Dashboard General
            </a>
        </div>
    </div>

    <h3 class="section-title text-uppercase h5 fw-bold">Participante Identificado</h3>
    <?php
        // Datos normalizados del participante (vienen de la tabla 'participantes')
        $es_delegado   = !empty($participante['es_delegado']);
        $es_competidor = !empty($participante['es_competidor']);
        $kit_entregado = !empty($participante['kit_entregado']);

        if (!isset($stats_delegacion) || !is_array($stats_delegacion)) {
            // Respaldo por si la vista se carga desde otro controlador sin las stats
            $this->load->model('Participante_model');
            $stats_delegacion = $this->Participante_model->contar_delegacion(
                isset($participante['delegacion']) ? $participante['delegacion'] : null
            );
        }

        $fnac = !empty($participante['fecha_nacimiento']) ? strtotime($participante['fecha_nacimiento']) : null;
        $edad = $fnac ? (int) floor((time() - $fnac) / 31557600) : null;
    ?>
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <h4 class="fw-bold text-dark mb-0"><?= $participante['nombre_completo'] ?></h4>
                <span class="badge badge-rol <?= $es_delegado ? 'bg-warning text-dark' : ($es_competidor ? 'bg-primary' : 'bg-secondary') ?> text-uppercase">
                    <?php if ($es_delegado && $es_competidor): ?>
                        <i class="bi bi-person-badge me-1"></i> Competidor · Delegado
                    <?php elseif ($es_delegado): ?>
                        <i class="bi bi-person-badge me-1"></i> Delegado
                    <?php elseif ($es_competidor): ?>
                        <i class="bi bi-trophy me-1"></i> Competidor
                    <?php else: ?>
                        <i class="bi bi-people me-1"></i> Acompañante
                    <?php endif; ?>
                </span>
            </div>

            <div class="row g-2 small text-muted">
                <div class="col-6">
                    <i class="bi bi-card-text me-1 text-primary"></i> <strong>DNI:</strong> <?= $participante['dni'] ?>
                </div>
                <div class="col-6">
                    <i class="bi bi-building me-1 text-primary"></i> <strong>Delegación:</strong> <?= $participante['delegacion'] ?>
                </div>
                <?php if (!empty($participante['sexo'])): ?>
                <div class="col-6">
                    <i class="bi bi-gender-ambiguous me-1 text-primary"></i> <strong>Sexo:</strong> <?= $participante['sexo'] ?>
                </div>
                <?php endif; ?>
                <?php if ($fnac): ?>
                <div class="col-6">
                    <i class="bi bi-cake2 me-1 text-primary"></i> <strong>Edad:</strong> <?= $edad ?> años (<?= date('d/m/Y', $fnac) ?>)
                </div>
                <?php endif; ?>
                <?php if (!empty($participante['telefono'])): ?>
                <div class="col-12">
                    <i class="bi bi-telephone me-1 text-primary"></i> <strong>Teléfono:</strong> <?= $participante['telefono'] ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($participante['email'])): ?>
                <div class="col-12">
                    <i class="bi bi-envelope me-1 text-primary"></i> <strong>Email:</strong> <?= $participante['email'] ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($participante['grupo_sanguineo']) || !empty($participante['obra_social'])): ?>
                <div class="col-12">
                    <?php if (!empty($participante['grupo_sanguineo'])): ?>
                        <i class="bi bi-droplet-half me-1 text-danger"></i> <strong>Sangre:</strong> <?= $participante['grupo_sanguineo'] ?>
                    <?php endif; ?>
                    <?php if (!empty($participante['obra_social'])): ?>
                        &nbsp;·&nbsp; <i class="bi bi-heart-pulse me-1 text-danger"></i> <strong>Obra social:</strong> <?= $participante['obra_social'] ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($participante['hotel_alojamiento'])): ?>
                <div class="col-12">
                    <i class="bi bi-house-door me-1 text-primary"></i> <strong>Alojamiento:</strong> <?= $participante['hotel_alojamiento'] ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($participante['dieta_especial']) && $participante['dieta_especial'] !== 'Sin restricciones'): ?>
                <div class="col-12">
                    <i class="bi bi-cup-hot me-1 text-warning"></i> <strong>Dieta especial:</strong> <?= $participante['dieta_especial'] ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($participante['contacto_emergencia'])): ?>
                <div class="col-12">
                    <i class="bi bi-exclamation-triangle me-1 text-danger"></i> <strong>Contacto de emergencia:</strong> <?= $participante['contacto_emergencia'] ?>
                </div>
                <?php endif; ?>
                <div class="col-12 mt-2 pt-2 border-top">
                    <i class="bi bi-info-circle me-1"></i> Tipo: <span class="text-dark fw-semibold"><?= $participante['tipo_empleado'] ?></span>
                </div>
                <?php if (!empty($participante['fecha_inscripcion'])): ?>
                <div class="col-12">
                    <i class="bi bi-calendar-check me-1"></i> Inscripto: <span class="text-dark"><?= date('d/m/Y H:i', strtotime($participante['fecha_inscripcion'])) ?></span>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <i class="bi bi-box-seam me-1"></i> Kit propio:
                    <?php if ($kit_entregado): ?>
                        <span class="badge bg-success text-uppercase">Entregado</span>
                    <?php else: ?>
                        <span class="badge bg-secondary text-uppercase">Pendiente</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <h3 class="section-title text-uppercase h5 fw-bold">Participaciones Deportivas</h3>
    <?php if (!empty($deportes)): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body p-3">
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($deportes as $dep): ?>
                        <li class="d-flex justify-content-between align-items-center py-2 <?= $dep['asistio'] == 1 ? '' : 'border-bottom' ?>">
                            <span>
                                <i class="bi bi-circle-fill me-2 text-primary" style="font-size: 0.5rem;"></i>
                                <strong><?= $dep['nombre_deporte'] ?></strong>
                                <span class="text-muted">(<?= $dep['nombre_categoria'] ?>)</span>
                                <?php if (!empty($dep['detalle_ute'])): ?>
                                    <span class="badge bg-warning text-dark ms-1"><i class="bi bi-people-fill me-1"></i><?= htmlspecialchars($dep['detalle_ute']) ?></span>
                                <?php endif; ?>
                            </span>
                            <?php if ($dep['asistio'] == 1): ?>
                                <span class="badge bg-success text-uppercase">Asistió</span>
                            <?php else: ?>
                                <span class="badge bg-secondary text-uppercase">Pendiente</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-light text-center small text-muted border py-3 mb-4">
            <i class="bi bi-exclamation-circle me-1"></i> El participante no registra inscripciones deportivas.
        </div>
    <?php endif; ?>

    <?php if (!empty($kit_msg = $this->session->flashdata('kit_msg'))): ?>
        <div class="alert alert-info py-2 small shadow-sm"><i class="bi bi-info-circle me-1"></i> <?= htmlspecialchars($kit_msg) ?></div>
    <?php endif; ?>

    <h3 class="section-title text-uppercase h5 fw-bold">Acciones de Acreditación</h3>
    <div class="d-grid gap-3">

        <?php if (!empty($deportes)): ?>
            <?php foreach ($deportes as $dep): ?>
                <?php if ($dep['asistio'] == 1): ?>
                    <div class="card row-accion-lista shadow-sm">
                        <div class="card-body p-2">
                            <form autocomplete='off' action="<?= base_url('Inscripciones/acreditar_deporte') ?>" method="POST">
                                <input type="hidden" name="id_inscripcion" value="<?= $dep['id_inscripcion'] ?>">
                                <input type="hidden" name="token_qr" value="<?= $participante['token_qr'] ?>">
                                <input type="hidden" name="nuevo_estado" value="0">

                                <button type="submit" class="btn btn-control btn-control-listo-revertir w-100 d-flex justify-content-between align-items-center">
                                    <span class="text-truncate me-2">
                                        <i class="bi bi-check-circle-fill me-2"></i> <?= $dep['nombre_deporte'] ?> (<?= $dep['nombre_categoria'] ?>)
                                    </span>
                                    <span class="badge bg-success text-white text-uppercase">Asistió</span>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card row-accion shadow-sm">
                        <div class="card-body p-2">
                            <form autocomplete='off' action="<?= base_url('Inscripciones/acreditar_deporte') ?>" method="POST">
                                <input type="hidden" name="id_inscripcion" value="<?= $dep['id_inscripcion'] ?>">
                                <input type="hidden" name="token_qr" value="<?= $participante['token_qr'] ?>">
                                <input type="hidden" name="nuevo_estado" value="1">
                                
                                <button type="submit" class="btn btn-control btn-control-deporte w-100 text-start">
                                    <i class="bi bi-trophy-fill me-2 text-warning"></i> Confirmar: <?= $dep['nombre_deporte'] ?>
                                    <div class="small fw-normal text-muted ps-4"><?= $dep['nombre_categoria'] ?></div>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-light text-center small text-muted border py-3">
                <i class="bi bi-exclamation-circle me-1"></i> El participante no registra inscripciones deportivas.
            </div>
        <?php endif; ?>

        <?php if ($es_delegado): ?>
            <!-- NUEVO: El delegado puede retirar los kits de TODA su delegación -->
            <div class="card shadow-sm mt-2" style="border-left: 5px solid #2e7d32 !important; border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="small text-muted mb-2">
                        <i class="bi bi-people-fill me-1 text-success"></i>
                        <strong>Delegación <?= $participante['delegacion'] ?>:</strong>
                        <?= (int) $stats_delegacion['total'] ?> participante<?= $stats_delegacion['total'] == 1 ? '' : 's' ?> ·
                        Kits entregados: <span class="fw-bold text-success"><?= (int) $stats_delegacion['con_kit'] ?></span>
                    </div>

                    <?php $kit_delegacion_listo = ($stats_delegacion['total'] > 0 && $stats_delegacion['con_kit'] >= $stats_delegacion['total']); ?>

                    <?php if ($kit_delegacion_listo): ?>
                        <form autocomplete='off' action="<?= base_url('Inscripciones/acreditar_kit_delegacion') ?>" method="POST"
                              onsubmit="return confirm('¿Seguro que querés REVERTIR la entrega de kits de toda la delegación <?= htmlspecialchars($participante['delegacion']) ?>?');">
                            <input type="hidden" name="token_qr" value="<?= $participante['token_qr'] ?>">
                            <input type="hidden" name="delegacion" value="<?= htmlspecialchars($participante['delegacion']) ?>">
                            <input type="hidden" name="nuevo_estado" value="0">

                            <button type="submit" class="btn btn-control btn-control-kit-revertir w-100 d-flex justify-content-between align-items-center">
                                <span class="text-truncate me-2">
                                    <i class="bi bi-box-seam me-2"></i> Entrega de kit · Delegación completa
                                </span>
                                <span class="badge bg-success text-white text-uppercase">Entregado a todos</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <form autocomplete='off' action="<?= base_url('Inscripciones/acreditar_kit_delegacion') ?>" method="POST"
                              onsubmit="return confirm('¿Confirmás que este delegado retiró los kits de TODA la delegación <?= htmlspecialchars($participante['delegacion']) ?> (<?= (int) $stats_delegacion['total'] ?> participantes)?');">
                            <input type="hidden" name="token_qr" value="<?= $participante['token_qr'] ?>">
                            <input type="hidden" name="delegacion" value="<?= htmlspecialchars($participante['delegacion']) ?>">
                            <input type="hidden" name="nuevo_estado" value="1">

                            <button type="submit" class="btn btn-control btn-control-kit w-100">
                                <i class="bi bi-box-seam-fill me-2"></i> Acreditar entrega de kit (Delegación completa)
                                <div class="small fw-normal ps-4">Marca el kit como entregado para el delegado y todos los integrantes de su delegación.</div>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <div class="text-center mt-5 pt-3 border-top border-2">
        <a href="<?= base_url('Inscripciones/logout') ?>" class="btn btn-sm btn-outline-danger px-3 rounded-pill">
            <i class="bi bi-box-arrow-right me-1"></i> Salir del Sistema Staff
        </a>
    </div>

</div>
</body>
</html>
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
            <a href="<?= base_url('Inscripciones/login_staff') ?>" class="btn btn-sm btn-outline-primary fw-bold px-3 py-2 rounded-3">
                <i class="bi bi-speedometer2 me-1"></i> Ver Dashboard General
            </a>
        </div>
    </div>

    <h3 class="section-title text-uppercase h5 fw-bold">Participante Identificado</h3>
    <div class="card mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold text-dark mb-3"><?= $participante['nombre_completo'] ?></h4>
            
            <div class="row g-2 small text-muted">
                <div class="col-6">
                    <i class="bi bi-card-text me-1 text-primary"></i> <strong>DNI:</strong> <?= $participante['dni'] ?>
                </div>
                <div class="col-6">
                    <i class="bi bi-building me-1 text-primary"></i> <strong>Delegación:</strong> <?= $participante['delegacion'] ?>
                </div>
                <div class="col-12 mt-2 pt-2 border-top">
                    <i class="bi bi-info-circle me-1"></i> Tipo: <span class="text-dark fw-semibold"><?= $participante['tipo_empleado'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <h3 class="section-title text-uppercase h5 fw-bold">Acciones de Acreditación</h3>
    <div class="d-grid gap-3">
        
        <?php if ($participante['kit_entregado'] == 1): ?>
            <div class="card row-accion-lista shadow-sm">
                <div class="card-body p-2">
                    <form autocomplete='off' action="<?= base_url('Inscripciones/acreditar_kit') ?>" method="POST">
                        <input type="hidden" name="id_participante" value="<?= $participante['id_participante'] ?>">
                        <input type="hidden" name="token_qr" value="<?= $participante['token_qr'] ?>">
                        <input type="hidden" name="nuevo_estado" value="0">
                        
                        <button type="submit" class="btn btn-control btn-control-listo-revertir w-100 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-box-seam-fill me-2"></i> ENTREGA DE KIT / INICIAL</span>
                            <span class="badge bg-success text-white text-uppercase">Entregado</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card row-accion shadow-sm">
                <div class="card-body p-2">
                    <form autocomplete='off' action="<?= base_url('Inscripciones/acreditar_kit') ?>" method="POST">
                        <input type="hidden" name="id_participante" value="<?= $participante['id_participante'] ?>">
                        <input type="hidden" name="token_qr" value="<?= $participante['token_qr'] ?>">
                        <input type="hidden" name="nuevo_estado" value="1">
                        
                        <button type="submit" class="btn btn-control btn-control-pendiente w-100 text-start">
                            <i class="bi bi-box-seam me-2 text-warning"></i> Acreditar Entrega de Kit
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

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

    </div>

    <div class="text-center mt-5 pt-3 border-top border-2">
        <a href="<?= base_url('Inscripciones/logout_staff') ?>" class="btn btn-sm btn-outline-danger px-3 rounded-pill">
            <i class="bi bi-box-arrow-right me-1"></i> Salir del Sistema Staff
        </a>
    </div>

</div>
</body>
</html>
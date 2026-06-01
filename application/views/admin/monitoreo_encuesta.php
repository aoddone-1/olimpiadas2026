<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas de Sondeo - Olimpiadas 2026</title>
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
        .card-indicador { border: none; border-radius: 12px; border-left: 5px solid #ffc107; }
        .nav-menu { background: #fff; border-bottom: 1px solid #dee2e6; }
        .nav-menu .nav-link { color: #495057; font-weight: 500; padding: 12px 20px; }
        .nav-menu .nav-link.active { color: #1e3c72; border-bottom: 3px solid #1e3c72; border-radius: 0; }
        .card-grafico { border: none; border-radius: 15px; }
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
                <a class="nav-link" href="<?= base_url('Inscripciones/gestion_deportes') ?>"><i class="bi bi-trophy-fill me-1"></i> Gestión Deportiva</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="<?= base_url('Inscripciones/monitoreo_encuesta') ?>"><i class="bi bi-bar-chart-line-fill me-1"></i> Sondeo Inicial</a>
            </li>
        </ul>
        <a href="<?= base_url('Inscripciones/logout_staff') ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">
            <i class="bi bi-box-arrow-right me-1"></i> Salir
        </a>
    </div>
</div>

<div class="container mb-5">
    
    <div class="row mb-4">
        <div class="col-12 col-md-4">
            <div class="card card-indicador shadow-sm">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-1">Encuestas Respondidas</h6>
                    <h3 class="fw-bold text-dark m-0"><i class="bi bi-clipboard2-check-fill text-warning me-2"></i><?= $total_encuestas ?> interesados</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-md-6">
            <div class="card card-grafico shadow-sm border-0">
                <div class="card-header bg-white border-0 pt-3 fw-bold text-secondary">
                    <i class="bi bi-fire text-danger me-2"></i>Disciplinas con Mayor Interés
                </div>
                <div class="card-body">
                    <?php if(!empty($ranking_deportes)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle m-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Deporte</th>
                                        <th class="text-end">Interesados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ranking_deportes as $rd): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark text-uppercase small"><?= htmlspecialchars($rd['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end"><span class="badge bg-primary-subtle text-primary border rounded-pill px-3"><?= $rd['votos'] ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small m-0 text-center py-3">Nadie respondió deportes aún.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card card-grafico shadow-sm border-0">
                <div class="card-header bg-white border-0 pt-3 fw-bold text-secondary">
                    <i class="bi bi-geo-alt-fill text-primary me-2"></i>Participación por Delegación
                </div>
                <div class="card-body">
                    <?php if(!empty($respuestas_por_delegacion)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle m-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Delegación</th>
                                        <th class="text-end">Formularios</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($respuestas_por_delegacion as $rg): ?>
                                        <tr>
                                            <td class="small fw-semibold text-secondary"><?= htmlspecialchars($rg['delegacion'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end fw-bold text-dark"><?= $rg['cantidad'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small m-0 text-center py-3">No hay datos geográficos registrados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 ">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 pt-3 fw-bold text-secondary">
                    <i class="bi bi-ui-checks-grid text-primary me-2"></i> Matriz de Segmentación para Apertura de Categorías
                </div>
                <div class="card-body">
                   <?php if(!empty($ranking_deportes)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle m-0 text-center">
                                <thead class="table-dark small text-uppercase">
                                    <tr>
                                        <th class="text-start" rowspan="2" style="vertical-align: middle;">Disciplina Deportiva</th>
                                        <th rowspan="2" style="vertical-align: middle; background-color: #1e3c72;">Total Interesados</th>
                                        <th colspan="3" class="bg-primary">Segmentación por Sexo</th>
                                        <th colspan="5" class="bg-success">Segmentación por Edad</th>
                                    </tr>
                                    <tr>
                                        <th class="table-primary text-primary small">Masc</th>
                                        <th class="table-primary text-primary small">Fem</th>
                                        <th class="table-primary text-primary small">Otro</th>
                                        
                                        <th class="table-success text-success small">&lt; 30 años</th>
                                        <th class="table-success text-success small">30 a 39</th>
                                        <th class="table-success text-success small">40 a 49</th>
                                        <th class="table-success text-success small">50 a 59</th>
                                        <th class="table-success text-success small">&gt;= 60 años</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ranking_deportes as $rd): ?>
                                        <tr>
                                            <td class="text-start fw-bold text-dark text-uppercase small" style="background-color: #f8f9fa;">
                                                <?= htmlspecialchars($rd['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            
                                            <td class="fw-bold fs-5 table-light text-dark">
                                                <?= $rd['votos'] ?>
                                            </td>
                                            
                                            <td class="text-secondary font-monospace"><?= $rd['masculino'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['femenino'] ?></td>
                                            <td class="text-muted font-monospace"><?= $rd['otro'] ?></td>
                                            
                                            <td class="text-secondary font-monospace"><?= $rd['menos_30'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['entre_30_39'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['entre_40_49'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['entre_50_59'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['mayores_60'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small m-0 text-center py-4">No hay datos suficientes para procesar la matriz.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bundle.min.js"></script>
</body>
</html>
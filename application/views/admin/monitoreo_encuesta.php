<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas de Sondeo - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>

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
                                    <?php 
                                    // Creamos un array temporal para controlar los deportes que ya dibujamos
                                    $deportes_mostrados = [];
                                    
                                    foreach($ranking_deportes as $rd): 
                                        // Si el deporte ya lo mostramos en una fila anterior, saltamos a la siguiente vuelta
                                        if (in_array($rd['id_deporte'], $deportes_mostrados)) {
                                            continue;
                                        }
                                        
                                        // Si es la primera vez que lo vemos, lo registramos y dibujamos la fila
                                        $deportes_mostrados[] = $rd['id_deporte'];
                                    ?>
                                        <tr>
                                            <td class="fw-semibold text-dark text-uppercase small">
                                                <?= htmlspecialchars($rd['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <!-- Usamos 'votos_totales' que es el alias que viene del nuevo modelo -->
                                            <td class="text-end">
                                                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3">
                                                    <?= $rd['votos_totales'] ?>
                                                </span>
                                            </td>
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
                            <table class="table table-bordered border-dark  align-middle m-0 text-center">
                                <thead class="table-dark small text-uppercase">
                                    <tr>
                                        <th class="text-start" style="vertical-align: middle;" rowspan="2">Disciplina Deportiva</th>
                                        <th style="vertical-align: middle; background-color: #1e3c72;" rowspan="2">Total Interesados</th>
                                        <th style="vertical-align: middle;" class="bg-primary" rowspan="2">Sexo</th>
                                        <th style="vertical-align: middle;" class="bg-primary" rowspan="2">Subtotal</th>
                                        <th colspan="8" class="bg-success">Rangos de Edad por Sexo</th>
                                    </tr>
                                    <tr> 
                                        
                                        <th class="table-success text-success small">&lt; 30 años</th>
                                        <th class="table-success text-success small">30 a 34</th>
                                        <th class="table-success text-success small">35 a 39</th>
                                        <th class="table-success text-success small">40 a 44</th>
                                        <th class="table-success text-success small">45 a 49</th>
                                        <th class="table-success text-success small">50 a 54</th>
                                        <th class="table-success text-success small">55 a 59</th>
                                        <th class="table-success text-success small">&gt;= 60 años</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $deporte_actual = null;
                                    foreach($ranking_deportes as $rd): 
                                        // Verificamos si cambiamos de deporte para armar el bloque agrupado
                                        $es_nuevo_deporte = ($rd['id_deporte'] !== $deporte_actual);
                                        if ($es_nuevo_deporte) {
                                            $deporte_actual = $rd['id_deporte'];
                                            // Contamos cuántas veces aparece este deporte en el array para el rowspan (máximo 3)
                                            $rowspan = count(array_filter($ranking_deportes, function($item) use ($deporte_actual) {
                                                return $item['id_deporte'] === $deporte_actual;
                                            }));
                                        }
                                    ?>
                                        <tr>
                                            <!-- Si es el primer registro del deporte, creamos las celdas principales con el rowspan agrupado -->
                                            <?php if($es_nuevo_deporte): ?>
                                                <td class="text-start fw-bold text-dark text-uppercase small" rowspan="<?= $rowspan; ?>" style="background-color: #f8f9fa; vertical-align: middle;">
                                                    <?= htmlspecialchars($rd['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="fw-bold fs-5 table-light text-dark" rowspan="<?= $rowspan; ?>" style="vertical-align: middle;">
                                                    <?= $rd['votos_totales'] ?>
                                                </td>
                                            <?php endif; ?>
                                            
                                            <!-- Datos dinámicos de la fila actual (Sexo y sus Rangos de edad) -->
                                            <td class="fw-bold small 
                                                <?php 
                                                    if ($rd['sexo'] === 'MASCULINO') {
                                                        echo 'bg-primary-subtle text-primary-emphasis';
                                                    } elseif ($rd['sexo'] === 'FEMENINO') {
                                                        echo 'bg-danger-subtle text-danger-emphasis';
                                                    } else {
                                                        echo 'bg-warning-subtle text-warning-emphasis';
                                                    }
                                                ?>">
                                                <?= $rd['sexo'] ?>
                                            </td>
                                            <td class="text-dark font-monospace fw-bold"><?= $rd['votos_sexo'] ?></td>
                                            
                                            <td class="text-secondary font-monospace"><?= $rd['menos_30'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['entre_30_34'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['entre_35_39'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['entre_40_44'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['entre_45_49'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['entre_50_54'] ?></td>
                                            <td class="text-secondary font-monospace"><?= $rd['entre_55_59'] ?></td>
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
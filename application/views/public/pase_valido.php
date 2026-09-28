<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pase Digital - <?= NOMBRE_META; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 25px;
            border-bottom: 4px solid #ffc107;
        }
        
        .btn-info-custom {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-info-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
            color: white;
        }
        
        .btn-staff {
            background: white;
            color: #1e3c72;
            border: 2px solid #1e3c72;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-staff:hover {
            background: #1e3c72;
            color: white;
            transform: translateY(-2px);
        }
        
        .deporte-item {
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            margin-bottom: 8px;
            border-radius: 8px;
        }
        
        .deporte-item.ute-badge {
            border-left: 4px solid #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #f8f9fa 100%);
            position: relative;
            overflow: hidden;
        }
        
        .ute-indicator {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        .ute-team-name {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #1e3c72;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 8px;
            border: 2px dashed #ff9800;
        }
        
        .info-box {
            background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
            border-radius: 12px;
            padding: 15px;
            border: none;
        }

        /* Próximos partidos (fixture) */
        .turno-item {
            background: #fff;
            border: 1px solid #667eea33;
            border-left: 4px solid #667eea;
            border-radius: 10px;
            padding: 8px 10px;
            margin-top: 8px;
            font-size: 0.8rem;
        }
        .turno-item .badge-fase {
            background: #667eea;
            color: #fff;
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .turno-item.en-curso { border-left-color: #fd7e14; }
        .turno-item.en-curso .badge-fase { background: #fd7e14; }

        /* Resultados ya cargados */
        .resultado-item {
            background: linear-gradient(135deg, #f1f8f3 0%, #ffffff 100%);
            border: 1px solid #28a74533;
            border-left: 4px solid #28a745;
            border-radius: 10px;
            padding: 8px 10px;
            margin-top: 8px;
            font-size: 0.8rem;
        }
        .resultado-item .badge-resultado {
            background: #28a745;
            color: #fff;
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .marcador-box {
            display: inline-block;
            background: #1e3c72;
            color: #fff;
            font-weight: 800;
            border-radius: 8px;
            padding: 2px 12px;
            font-size: 0.95rem;
            letter-spacing: 1px;
        }
        .desempate-box {
            background: linear-gradient(135deg, #fff8e1 0%, #ffe082 100%);
            border: 1px dashed #ff9800;
            border-radius: 8px;
            padding: 4px 8px;
            margin-top: 6px;
            font-size: 0.75rem;
            color: #7a4d00;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 500px;">
        <div class="card-header-custom text-center">
            <div class="text-white mb-2"><i class="bi bi-shield-check fs-1"></i></div>
            <h4 class="fw-bold text-uppercase m-0">Inscripción Activa</h4>
        </div>
        
        <div class="card-body p-4">
            <div class="text-start bg-white p-3 rounded border mb-4 shadow-sm">
                <p class="mb-1 text-muted small" style="letter-spacing: 0.5px;">PARTICIPANTE</p>
                <h5 class="fw-bold mb-2 text-dark"><?= $participante['nombre_completo'] ?></h5>
                <p class="mb-1 text-muted small">DNI: <span class="text-dark fw-semibold"><?= $participante['dni'] ?></span></p>
                <p class="mb-0 text-muted small">DELEGACIÓN: <span class="text-dark fw-semibold"><?= $participante['delegacion'] ?></span></p>
            </div>

            <div class="text-start mb-4">
                <p class="mb-2 text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Disciplinas Inscriptas:</p>
                <ul class="list-group list-group-flush border rounded shadow-sm">
                    <?php if(!empty($deportes)): ?>
                        <?php foreach($deportes as $dep): ?>
                            <?php 
                                $tiene_ute = isset($dep['tiene_ute']) && $dep['tiene_ute'] == '1';
                                $necesita_ute = isset($dep['necesita_ute']) && $dep['necesita_ute'] == '1';
                                $detalle_ute = isset($dep['detalle_ute']) && !empty($dep['detalle_ute']) ? $dep['detalle_ute'] : '';
                                $es_ute = $tiene_ute || ($necesita_ute && !empty($detalle_ute));
                            ?>
                            <li class="list-group-item deporte-item p-3 small <?= $es_ute ? 'ute-badge' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                                    <span class="fw-bold text-primary"><i class="bi bi-circle-fill me-2" style="font-size: 0.5rem;"></i> <?= $dep['nombre_deporte'] ?> (<?= $dep['nombre_categoria'] ?>)</span>
                                    <?php if($es_ute): ?>
                                        <span class="ute-indicator">
                                            <i class="bi bi-people-fill me-1"></i> UTE/Equipo
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if(!empty($detalle_ute)): ?>
                                    <div class="ute-team-name">
                                        <i class="bi bi-trophy-fill me-2"></i><?= htmlspecialchars($detalle_ute) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php
                                    // Helper local: nombre legible de la fase del fixture.
                                    $fases_txt = array(
                                        'GRUPO' => 'Fase de grupos', '16AVOS' => '16avos. de final',
                                        'OCTAVOS' => 'Octavos de final', 'CUARTOS' => 'Cuartos de final',
                                        'SEMIFINAL' => 'Semifinal', 'TERCER_PUESTO' => 'Por el 3er puesto',
                                        'FINAL' => 'Final', 'JORNADA_UNICA' => 'Jornada',
                                    );
                                    $metodos_txt = array(
                                        'PENALES' => 'penales', 'PRORROGA' => 'prórroga',
                                        'PUNTOS_DE_ORO' => 'puntos de oro', 'MUERTE_SUBITA' => 'muerte súbita',
                                        'LANZAMIENTO_TIRLIBRE' => 'lanzamiento tirlibre', 'OTRO' => 'desempate',
                                    );
                                    $proximos = !empty($dep['fixture_proximos']) ? $dep['fixture_proximos'] : array();
                                    $resultados = !empty($dep['resultados']) ? $dep['resultados'] : array();
                                ?>

                                <?php if(!empty($proximos)): ?>
                                    <!-- PRÓXIMAS COMPETENCIAS (fecha y hora del FIXTURE) -->
                                    <div class="mt-2 ps-3 border-start">
                                        <small class="fw-bold text-primary text-uppercase" style="letter-spacing:.5px;font-size:.68rem;">
                                            <i class="bi bi-calendar-event me-1"></i>Próximas competencias
                                        </small>
                                        <?php foreach($proximos as $fx): ?>
                                            <div class="turno-item <?= $fx['estado'] === 'EN_CURSO' ? 'en-curso' : '' ?>">
                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                                    <span class="badge-fase">
                                                        <?= isset($fases_txt[$fx['fase']]) ? $fases_txt[$fx['fase']] : htmlspecialchars($fx['fase']) ?>
                                                        <?= (!$fx['es_masivo'] && $fx['fase'] === 'GRUPO' && !empty($fx['numero_fecha'])) ? ' · Fecha ' . (int)$fx['numero_fecha'] : '' ?>
                                                    </span>
                                                    <?php if($fx['estado'] === 'EN_CURSO'): ?>
                                                        <span class="badge bg-warning text-dark fw-bold"><i class="bi bi-broadcast me-1"></i>En curso</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mt-1">
                                                    <i class="bi bi-calendar3 me-1"></i><strong><?= date('d/m/Y', strtotime($fx['fecha_competencia'])) ?></strong>
                                                    &nbsp;<i class="bi bi-clock me-1"></i><strong><?= date('H:i', strtotime($fx['hora_inicio'])) ?> hs</strong>
                                                    <?php if(!empty($fx['lugar_nombre'])): ?>
                                                        <br><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($fx['lugar_nombre']) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if(!$fx['es_masivo']): ?>
                                                    <div class="mt-1 small text-muted">
                                                        <i class="bi bi-vs2 me-1"></i>
                                                        <?= $fx['id_ute_1'] > 0 ? 'Rival por definir' : htmlspecialchars($fx['nombre_prueba'] ?: 'Partido') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif(!empty($dep['dia_competencia']) || !empty($dep['hora_competencia']) || !empty($dep['nombre_lugar'])): ?>
                                    <!-- Sin fixture: día/hora genéricos de la categoría -->
                                    <div class="mt-2 ps-3 border-start">
                                        <?php if(!empty($dep['dia_competencia'])): ?>
                                            <div class="small text-muted mb-1">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <strong>Día:</strong> <?= date('d/m/Y', strtotime($dep['dia_competencia'])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if(!empty($dep['hora_competencia'])): ?>
                                            <div class="small text-muted mb-1">
                                                <i class="bi bi-clock me-1"></i>
                                                <strong>Hora:</strong> <?= date('H:i', strtotime($dep['hora_competencia'])) ?> hs
                                            </div>
                                        <?php endif; ?>
                                        <?php if(!empty($dep['nombre_lugar'])): ?>
                                            <div class="small text-muted">
                                                <i class="bi bi-geo-alt me-1"></i>
                                                <strong>Sede:</strong> <?= $dep['nombre_lugar'] ?>
                                                <?php if(!empty($dep['direccion_lugar'])): ?>
                                                    <br><span class="text-muted small"><?= $dep['direccion_lugar'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="small text-muted fst-italic mt-1"><i class="bi bi-info-circle me-1"></i>Horario confirmado al publicarse el fixture</div>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-2 ps-3 border-start">
                                        <small class="text-muted fst-italic"><i class="bi bi-info-circle me-1"></i>Horarios y sedes a confirmar</small>
                                    </div>
                                <?php endif; ?>

                                <?php if(!empty($resultados)): ?>
                                    <!-- RESULTADOS YA CARGADOS -->
                                    <div class="mt-2 ps-3 border-start border-success">
                                        <small class="fw-bold text-success text-uppercase" style="letter-spacing:.5px;font-size:.68rem;">
                                            <i class="bi bi-check2-circle me-1"></i>Resultados
                                        </small>
                                        <?php foreach($resultados as $res): ?>
                                            <?php
                                                $det = !empty($res['detalle']) ? $res['detalle'] : array();
                                                $es_marcador = ($res['tipo_resultado'] === 'MARCADOR');
                                            ?>
                                            <div class="resultado-item">
                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                                    <span class="badge-resultado"><i class="bi bi-trophy-fill me-1"></i>Resultado</span>
                                                    <span class="text-muted small"><?= !empty($res['fecha_resultado']) ? date('d/m/Y', strtotime($res['fecha_resultado'])) : '' ?></span>
                                                </div>
                                                <div class="fw-semibold mt-1 small text-dark">
                                                    <i class="bi bi-flag-fill me-1 text-primary"></i><?= htmlspecialchars($res['nombre_evento']) ?>
                                                </div>

                                                <?php if($es_marcador && count($det) >= 2): ?>
                                                    <?php
                                                        $eq1 = $det[0]['nombre_libre'] ?: 'Equipo 1';
                                                        $eq2 = $det[1]['nombre_libre'] ?: 'Equipo 2';
                                                        $g1 = (int) $det[0]['marcador_local'];
                                                        $g2 = (int) $det[0]['marcador_visita'];
                                                        $ganador_res = !empty($res['id_ute_ganador']) ? (int) $res['id_ute_ganador'] : 0;
                                                        $nombre_gan_res = !empty($res['nombre_ganador_desempate']) ? $res['nombre_ganador_desempate'] : '';
                                                        // Resaltar al ganador (por marcador o por desempate)
                                                        $gana1 = $g1 > $g2 || ($g1 === $g2 && $nombre_gan_res !== '' && strtoupper(trim($nombre_gan_res)) === strtoupper(trim($eq1)));
                                                        $gana2 = $g2 > $g1 || ($g1 === $g2 && $nombre_gan_res !== '' && strtoupper(trim($nombre_gan_res)) === strtoupper(trim($eq2)));
                                                    ?>
                                                    <div class="mt-1 d-flex align-items-center flex-wrap gap-2">
                                                        <span class="<?= $gana1 ? 'fw-bold text-success' : 'text-muted' ?>"><?= htmlspecialchars($eq1) ?></span>
                                                        <span class="marcador-box"><?= $g1 ?> - <?= $g2 ?></span>
                                                        <span class="<?= $gana2 ? 'fw-bold text-success' : 'text-muted' ?>"><?= htmlspecialchars($eq2) ?></span>
                                                    </div>
                                                    <?php if(!empty($res['hubo_desempate']) && !empty($res['desempate_metodo'])): ?>
                                                        <div class="desempate-box">
                                                            🏅 Empate en tiempo regular —
                                                            <strong><?= htmlspecialchars($nombre_gan_res ?: 'Definido') ?></strong>
                                                            ganó en <strong><?= isset($metodos_txt[$res['desempate_metodo']]) ? $metodos_txt[$res['desempate_metodo']] : htmlspecialchars($res['desempate_metodo']) ?></strong>
                                                        </div>
                                                    <?php elseif($g1 === $g2): ?>
                                                        <div class="small text-muted fst-italic mt-1">Empate</div>
                                                    <?php endif; ?>
                                                <?php elseif($es_marcador && count($det) == 1): ?>
                                                    <div class="mt-1">
                                                        <span class="marcador-box"><?= (int) $det[0]['marcador_local'] ?> - <?= (int) $det[0]['marcador_visita'] ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- TIEMPO: posiciones -->
                                                    <ul class="list-unstyled mt-1 mb-0">
                                                        <?php foreach(array_slice($det, 0, 5) as $d): ?>
                                                            <li class="small">
                                                                <strong><?= $d['posicion'] !== null ? $d['posicion'] . 'º' : '–' ?></strong>
                                                                <?= htmlspecialchars($d['nombre_libre'] ?: 'Competidor') ?>
                                                                <?php if(!empty($d['tiempo'])): ?><span class="text-muted">· <?= htmlspecialchars($d['tiempo']) ?></span><?php endif; ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>

                                                <?php if(!empty($res['observaciones'])): ?>
                                                    <div class="small text-muted mt-1"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($res['observaciones']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item small text-muted">Ninguna disciplina registrada</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="d-grid gap-2">
                <a href="https://olimpiadasipav2026.netlify.app/" target="_blank" class="btn btn-info-custom py-2 rounded-pill">
                    <i class="bi bi-book-half me-2"></i> Información y Ayuda
                </a>
                
                <a href="<?= base_url('Inscripciones/login') ?>" class="btn btn-staff py-2 rounded-pill">
                    <i class="bi bi-shield-lock-fill me-1"></i> Ingreso Staff / Mesa de Control
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
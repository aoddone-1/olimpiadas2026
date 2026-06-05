<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pase Digital - <?= NOMBRE_META; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 450px; border-radius: 15px;">
        <div class="card-body text-center p-4">
            <div class="text-success mb-2"><i class="bi bi-shield-check fs-1"></i></div>
            <h4 class="fw-bold text-uppercase m-0">Inscripción Activa</h4>
            <p class="text-muted small">Presentá este código al ingresar al predio</p>
            
            <hr>
            
            <div class="text-start bg-white p-3 rounded border mb-4">
                <p class="mb-1 text-muted small" style="letter-spacing: 0.5px;">PARTICIPANTE</p>
                <h5 class="fw-bold mb-2 text-dark"><?= $participante['nombre_completo'] ?></h5>
                <p class="mb-1 text-muted small">DNI: <span class="text-dark fw-semibold"><?= $participante['dni'] ?></span></p>
                <p class="mb-0 text-muted small">DELEGACIÓN: <span class="text-dark fw-semibold"><?= $participante['delegacion'] ?></span></p>
            </div>

            <div class="text-start mb-4">
                <p class="mb-2 text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Disciplinas Inscriptas:</p>
                <ul class="list-group list-group-flush border rounded">
                    <?php if(!empty($deportes)): ?>
                        <?php foreach($deportes as $dep): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center small">
                                <span><i class="bi bi-circle-fill text-primary me-2" style="font-size: 0.5rem;"></i> <?= $dep['nombre_deporte'] ?> (<?= $dep['nombre_categoria'] ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item small text-muted">Ninguna disciplina registrada</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="alert alert-info small text-start mb-4">
                <i class="bi bi-info-circle-fill me-2"></i> El personal de control escaneará este pase en las mesas de acreditación.
            </div>

            <div class="text-center pt-2 border-top">
                <a href="<?= base_url('Inscripciones/login_staff') ?>" class="btn btn-sm btn-outline-secondary w-100 py-2">
                    <i class="bi bi-shield-lock-fill me-1"></i> Ingreso Staff / Mesa de Control
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
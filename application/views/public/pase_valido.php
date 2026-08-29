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
        
        .info-box {
            background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
            border-radius: 12px;
            padding: 15px;
            border: none;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 500px;">
        <div class="card-header-custom text-center">
            <div class="text-white mb-2"><i class="bi bi-shield-check fs-1"></i></div>
            <h4 class="fw-bold text-uppercase m-0">Inscripción Activa</h4>
            <p class="text-white-50 small m-0">Presentá este código al ingresar al predio</p>
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
                            <li class="list-group-item deporte-item d-flex justify-content-between align-items-center small">
                                <span><i class="bi bi-circle-fill text-primary me-2" style="font-size: 0.5rem;"></i> <?= $dep['nombre_deporte'] ?> (<?= $dep['nombre_categoria'] ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item small text-muted">Ninguna disciplina registrada</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="info-box mb-4">
                <i class="bi bi-info-circle-fill me-2"></i> El personal de control escaneará este pase en las mesas de acreditación.
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
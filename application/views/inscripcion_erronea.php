<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error en Inscripción - <?= NOMBRE_META; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .error-card {
            max-width: 600px;
            margin: 80px auto;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header-error {
            background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
            color: white;
            padding: 40px;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        .icon-box {
            font-size: 4rem;
            color: #ffffff;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        .btn-retry {
            background-color: #d32f2f;
            color: white;
            border-radius: 10px;
            padding: 12px 30px;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-retry:hover {
            background-color: #b71c1c;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card error-card">
        <div class="header-error">
            <div class="icon-box">
                <i class="bi bi-exclamation-octagon-fill"></i>
            </div>
            <h1 class="h2 fw-bold">¡UPS! ALGO SALIÓ MAL</h1>
        </div>
        <div class="card-body p-5 text-center">
            <p class="lead text-danger fw-bold">No pudimos procesar tu inscripción.</p>
            
            <div class="alert alert-light border text-start" role="alert">
                <h6 class="alert-heading fw-bold"><i class="bi bi-bug me-2"></i>Detalle del problema:</h6>
                <p class="mb-0 small text-muted"><?= $mensaje ?></p>
            </div>

            <?php if(isset($dni)): ?>
                <p class="text-muted small mt-2">DNI intentado: <strong><?= $dni ?></strong></p>
            <?php endif; ?>
            

            <div class="mt-5">
                <a href="javascript:history.back()" class="btn btn-retry">
                    <i class="bi bi-arrow-left-circle me-2"></i> VOLVER E INTENTAR DE NUEVO
                </a>
            </div>
        </div>
        <div class="card-footer text-center py-3 bg-light" style="border-radius: 0 0 20px 20px;">
            <small class="text-muted"><?= NOMBRE_SITIO; ?> | <?= LUGAR_OLIMPICO; ?></small>
        </div>
    </div>
</div>

</body>
</html>
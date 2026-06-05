<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción Exitosa - <?= NOMBRE_META; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .success-card {
            max-width: 600px;
            margin: 50px auto;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header-success {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        .icon-box {
            font-size: 4rem;
            color: #ffc107;
            margin-bottom: 10px;
        }
        .qr-box img {
            max-width: 180px;
            border: 1px solid #ddd;
            padding: 10px;
            background: white;
            border-radius: 15px;
        }
        .btn-home {
            background-color: #1e3c72;
            color: white;
            border-radius: 10px;
            padding: 12px 30px;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-home:hover {
            background-color: #2a5298;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card success-card">
        <div class="header-success">
            <div class="icon-box">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h1 class="h2 fw-bold">¡INSCRIPCIÓN CONFIRMADA!</h1>
        </div>
        <div class="card-body p-5 text-center">
            <p class="lead">¡Muchas gracias, <strong><?= $nombre ?></strong>!</p>
            <p class="text-muted">Tu inscripción para las <strong><?= NOMBRE_SITIO; ?> - <?= LUGAR_OLIMPICO; ?></strong> ha sido procesada con éxito.</p>
            
            <hr class="my-4">
            
            <div class="qr-box my-4">
                <p class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">Tu Pase Digital de Acreditación</p>
                
                <?php $url_acreditacion = base_url("Inscripciones/acreditacion/" . $token); ?>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($url_acreditacion) ?>" alt="Código QR de Acreditación" class="shadow-sm">
                
                
            </div>
            
            <div class="alert alert-info d-flex align-items-center text-start" role="alert">
                <i class="bi bi-pin-angle-fill me-3 fs-4 text-primary"></i>
                <div>
                    <strong>Importante:</strong> Por favor, hacé una captura de pantalla de este códigoo. Este QR facilitará tu acreditación el día del inicio de las Olimpiadas.
                </div>
            </div>

            <div class="mt-5">
                <a href="<?= base_url('inscripciones/formulario_inscripcion') ?>" class="btn btn-home">
                    <i class="bi bi-house-door-fill me-2"></i> VOLVER AL INICIO
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
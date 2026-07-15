<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credencial Oficial - <?= $participante['nombre_completo'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="<?= base_url('css/credencial.css') ?>" rel="stylesheet">
</head>
<body>

<div class="toolbar no-print">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="<?= base_url('Inscripciones/login') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Dashboard
                </a>
            </div>
            <div class="alert alert-info py-2 px-3 m-0 small d-flex align-items-center" role="alert">
                <i class="bi bi-printer-fill me-2 fs-6"></i>
                Impresión de Credencial Oficial - Verificá que la impresora esté lista.
            </div>
            <div>
                <button onclick="window.print();" class="btn btn-primary fw-bold">
                    <i class="bi bi-printer"></i> IMPRIMIR AHORA
                </button>
            </div>
        </div>
    </div>
</div>

<div class="credencial-wrapper">
    <div class="credencial-fisica d-flex flex-column justify-content-between">
        
        <div class="header-success d-flex align-items-center justify-content-center px-3">
            <img src="<?= base_url('assets/img/icon.png') ?>" alt="Icono Olimpiadas" class="logo-olimpiadas-icono me-3">
            
            <div class="text-start text-white dynamic-text-brand">
                <div class="fw-bold text-uppercase m-0 line-1"><?= NOMBRE_SITIO; ?></div>
                <div class="fw-bold text-warning text-uppercase m-0 line-3"><?= LUGAR_OLIMPICO; ?></div>
            </div>
        </div>

        <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
            <div class="text-center mb-1">
                <h3 class="fw-bold text-dark text-uppercase mb-1" style="font-size: 1.4rem; letter-spacing: 0.3px;">
                    <?= $participante['nombre_completo'] ?>
                </h3>
                <p class="text-muted small m-0"><strong>DNI:</strong> <?= $participante['dni'] ?></p>
            </div>
            
            <hr class="my-2 border-muted">

            <div class="qr-box my-2">
                <?php $url_acreditacion = base_url('Inscripciones/acreditacion/' . $participante['token_qr']); ?>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($url_acreditacion) ?>&ecc=M" alt="QR Control de Mesa" class="shadow-sm img-fluid">
            </div>

            <div class="text-center mt-1">
                <span class="badge bg-primary text-uppercase px-3 py-1 mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">
                    Control General de Mesa
                </span>
                <p class="text-dark fw-bold m-0 text-uppercase" style="font-size: 1.05rem; line-height: 1.2;">
                    <?= $participante['delegacion'] ?>
                </p>
                <p class="text-muted m-0 small fw-semibold">
                    <?= $participante['tipo_empleado'] ?>
                </p>
            </div>
        </div>

        <div class="alert alert-custom text-center p-3 m-0 mt-auto" role="alert">
            <div class="small" style="font-size: 0.72rem; line-height: 1.3;">
                <strong>Uso Personal:</strong> Esta credencial es intransferible y debe ser presentada cada vez que sea requerida.
            </div>
        </div>

    </div>
</div>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            window.print();
        }, 1000); 
    });
</script>

</body>
</html>
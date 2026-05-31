<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Muchas Gracias! - Olimpiadas Nacionales 2026</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px 0;
            border-bottom: 5px solid #ffc107;
            margin-bottom: 30px;
        }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .icon-box {
            width: 80px;
            height: 80px;
            background-color: #e8f5e9;
            color: #2e7d32;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="hero-section text-center">
    <div class="container">
        <h1 class="display-6 fw-bold text-uppercase">Sondeo Completado</h1>
        <p class="lead m-0">XXXVIII Olimpiadas Nacionales IV de Empleados de Institutos de Vivienda</p>
    </div>
</div>

<div class="container text-center mb-5" style="max-width: 600px;">
    <div class="card p-5 shadow">
        <div class="card-body">
            <div class="icon-box shadow-sm">
                <i class="bi bi-check-lg fs-1"></i>
            </div>
            
            <h2 class="fw-bold mb-3 text-dark">¡Muchas gracias por tu respuesta!</h2>
            
            <p class="fs-5 text-muted mb-4">
                Tus respuestas nos ayudan un montón a dimensionar la logística general y a definir con precisión las categorías en **La Pampa 2026**.
            </p>
            
            <div class="mb-4">
                <a href="<?= base_url('Inscripciones') ?>" class="btn btn-primary btn-lg px-4 shadow-sm fw-bold">
                    <i class="bi bi-arrow-left-short fs-5 align-middle"></i> Volver al Inicio
                </a>
            </div>
            
            <hr class="my-4 text-muted opacity-25">
            
            <p class="small text-secondary m-0">
                <i class="bi bi-info-circle me-1"></i> Recordá que este sondeo es meramente organizativo y no representa una inscripción definitiva. ¡Pronto abriremos las listas oficiales!
            </p>
        </div>
    </div>
</div>

</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso Delegados - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('css/login.css') ?>" rel="stylesheet">
    <style>
        .login-card {
            max-width: 450px;
            border-top: 5px solid #28a745;
        }
        .btn-delegado {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-delegado:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
    </style>
</head>
<body>
<div class="container d-flex flex-column align-items-center justify-content-center p-3">
    <div class="card login-card mx-auto">
        <div class="card-body p-4 text-center">
            <h3 class="fw-bold mb-1 text-uppercase" style="color: #28a745; letter-spacing: 0.5px;">Acceso Delegados</h3>
            <p class="text-muted small mb-4">Panel de Gestión de Delegación</p>
            
            <?php if($this->session->flashdata('error')): ?>
                <div class="alert alert-danger small py-2 border-0 rounded-3"><?= $this->session->flashdata('error') ?></div>
            <?php endif; ?>

            <form action="<?= base_url('Inscripciones/procesar_login') ?>" method="POST">
                <div class="mb-3 text-start">
                    <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.75rem;">Usuario</label>
                    <input type="text" name="usuario" class="form-control" required autocomplete="off" placeholder="Ingrese su usuario">
                </div>
                <div class="mb-4 text-start">
                    <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.75rem;">Contraseña</label>
                    <input type="password" name="password" class="form-control" required placeholder="Ingrese su contraseña">
                </div>
                <button type="submit" class="btn btn-delegado w-100 py-2 fw-bold text-uppercase fs-6 shadow-sm">Ingresar como Delegado</button>
            </form>
            
            <div class="mt-3">
                <a href="<?= base_url('Inscripciones/login_staff') ?>" class="small text-muted">¿Sos staff? Ingresá aquí</a>
            </div>
        </div>
    </div>

    <!-- El pie de página que pediste -->
    <div class="credits">
        Sitio desarrollado por la <strong>Delegación de La Pampa</strong>
    </div>
</div>
</body>
</html>

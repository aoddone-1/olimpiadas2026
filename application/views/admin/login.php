<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso Staff - Olimpiadas 2026</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .login-card { max-width: 400px; margin: 100px auto padding: 20px; border-radius: 15px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .btn-primary { background-color: #1e3c72; border: none; }
        .btn-primary:hover { background-color: #2a5298; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card login-card mx-auto">
        <div class="card-body p-4 text-center">
            <h3 class="fw-bold mb-3 text-uppercase" style="color: #1e3c72;">Staff Login</h3>
            <p class="text-muted small">Control de Acreditaciones</p>
            
            <?php if($this->session->flashdata('error')): ?>
                <div class="alert alert-danger small py-2"><?= $this->session->flashdata('error') ?></div>
            <?php endif; ?>

            <form action="<?= base_url('Inscripciones/procesar_login') ?>" method="POST">
                <div class="mb-3 text-start">
                    <label class="form-label small fw-bold">Usuario</label>
                    <input type="text" name="usuario" class="form-control" required autocomplete="off">
                </div>
                <div class="mb-4 text-start">
                    <label class="form-label small fw-bold">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">INGRESAR</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
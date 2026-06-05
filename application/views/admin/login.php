<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso Staff - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); 
            font-family: 'Segoe UI', sans-serif; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .login-card { 
            max-width: 400px; 
            width: 100%;
            padding: 25px; 
            border-radius: 24px; 
            border: none; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.3); 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #ced4da;
            background-color: #f8f9fa;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: #2a5298;
            box-shadow: 0 0 0 0.25rem rgba(42, 82, 152, 0.15);
        }
        .btn-primary { 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); 
            border: none; 
            border-radius: 12px;
            padding: 12px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover { 
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%); 
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(42, 82, 152, 0.4);
        }
        .credits {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.78rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            text-align: center;
            margin-top: 25px;
        }
        .credits strong {
            color: #ffc107;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container d-flex flex-column align-items-center justify-content-center p-3">
    <div class="card login-card mx-auto">
        <div class="card-body p-4 text-center">
            <h3 class="fw-bold mb-1 text-uppercase" style="color: #1e3c72; letter-spacing: 0.5px;">Staff Login</h3>
            <p class="text-muted small mb-4">Control de Acreditaciones</p>
            
            <?php if($this->session->flashdata('error')): ?>
                <div class="alert alert-danger small py-2 border-0 rounded-3"><?= $this->session->flashdata('error') ?></div>
            <?php endif; ?>

            <form action="<?= base_url('Inscripciones/procesar_login') ?>" method="POST">
                <div class="mb-3 text-start">
                    <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.75rem;">Usuario</label>
                    <input type="text" name="usuario" class="form-control" required autocomplete="off">
                </div>
                <div class="mb-4 text-start">
                    <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.75rem;">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold text-uppercase fs-6 shadow-sm">Ingresar</button>
            </form>
        </div>
    </div>

    <!-- El pie de página que pediste -->
    <div class="credits">
        Sitio desarrollado por la <strong>Delegación de La Pampa</strong>
    </div>
</div>
</body>
</html>
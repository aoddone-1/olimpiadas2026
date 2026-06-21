<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sondeo Previo - <?= NOMBRE_META; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="<?= base_url('css/style_formularios.css') ?>" rel="stylesheet">
</head>
<body>

<div class="hero-section">
    <div class="container">
       <div class="row align-items-center g-4">
            
            <div class="col-12 col-md-4 text-center text-md-end d-none d-md-block">
                <img src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" 
                    alt="Logo Olimpiadas" 
                    class="img-fluid logo-hero">
            </div>
            
            <div class="col-12 col-md-8 text-center text-md-start ps-md-4">
                
                <div class="d-block d-md-none">
                    <img src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" 
                        alt="Logo Olimpiadas" 
                        class="img-fluid logo-hero">
                </div>
                
                <h1 class="h2 fw-bold text-uppercase text-white mb-2 header-title">
                    Encuesta <u>Previa a Inscripciones</u>
                </h1>
                
                <small class="d-block text-white-50 help-text">
                    Ayudanos a dimensionar las olimpiadas respondiendo este breve cuestionario.
                </small>
            </div>

        </div>
    </div>
</div>

<div class="container mb-5" style="max-width: 800px;">
    
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4 bg-white">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center g-4">
                
                <div class="col-6 col-md-8 text-center text-md-start">
                                        
                    <h2 class="fw-bold text-dark mb-3">El sondeo previo ya terminó!</h2>
                    
                    <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.6;">
                        ¿No llegaste a completar la encuesta? ¡No te preocupes! Ya pudimos recolectar todos los datos necesarios gracias a la increíble participación de todos ustedes. 
                        Con esta información estamos terminando de armar el cronograma y las categorías perfectas para el evento.
                    </p>
                </div>

                <div class="col-6 col-md-4 text-center">
                    <img src="<?= base_url('assets/img/pampito.png') ?>" 
                         alt="Mascota Olimpiadas" 
                         class="img-fluid" 
                         style="object-fit: contain; filter: drop-shadow(0px 8px 16px rgba(0,0,0,0.1));">
                </div>
                <div class="col-12 col-md-12 text-center">
                    <div class="p-3 bg-light rounded-3 d-flex align-items-start gap-2 border border-warning-subtle">
                        <i class="bi bi-bell-fill text-warning fs-5 mt-0.5"></i>
                        <span class="fw-semibold text-dark small">¡Mantenete atento! Muy pronto abriremos la etapa de inscripciones oficiales por este mismo medio.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php $this->load->view('footer'); ?>

</body>
</html>
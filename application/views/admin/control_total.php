<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Total - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>

<div class="container mb-5 mt-4">
    
    <div class="d-flex justify-content-center mb-4">
        <ul class="nav nav-pills bg-white p-2 rounded-pill shadow-sm" id="controlTabs">
            <li class="nav-item">
                <button class="nav-link active rounded-pill px-4 fw-bold js-tab-btn" data-target="#panel-inscripciones" type="button">
                    <i class="bi bi-person-check-fill me-2"></i>Inscripciones
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-4 fw-bold js-tab-btn" data-target="#panel-equipos" type="button">
                    <i class="bi bi-people-fill me-2"></i>UTEs/Equipos
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-4 fw-bold js-tab-btn" data-target="#panel-fixture" type="button">
                    <i class="bi bi-calendar2-week me-2"></i>Fixture
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="controlTabsContent">
        
        <div class="tab-custom-pane active-pane fade show" id="panel-inscripciones">
            <?php $this->load->view('admin/panel-inscripciones'); ?>
        </div>
        <div class="tab-custom-pane fade" id="panel-equipos">
            <?php $this->load->view('admin/panel-equipos'); ?>
        </div>
        <div class="tab-custom-pane fade" id="panel-fixture">
            <?php $this->load->view('admin/panel-fixture'); ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ----------------------------------------
    // CONTROLADOR DE PESTAÑAS (TABS)
    // ----------------------------------------
    const buttons = document.querySelectorAll('.js-tab-btn');
    const panes = document.querySelectorAll('.tab-custom-pane');

    buttons.forEach(button => {
        button.addEventListener('click', function () {
            buttons.forEach(btn => btn.classList.remove('active'));
            panes.forEach(pane => pane.classList.remove('active-pane', 'show'));

            this.classList.add('active');
            
            const targetId = this.getAttribute('data-target');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('active-pane', 'show');
            }
        });
    });
});
</script>
</body>
</html>

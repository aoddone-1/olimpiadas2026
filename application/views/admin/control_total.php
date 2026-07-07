<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas de Sondeo - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

</head>
<body>

<div class="modal fade" id="modalDeportesVotados" tabindex="-1" aria-labelledby="modalDeportesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold text-dark" id="modalDeportesLabel">
                    <i class="bi bi-trophy-fill text-warning me-2"></i>Deportes Seleccionados
                </h5>
                <button type="button" class="btn-close js-close-modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-muted small mb-1 text-uppercase fw-semibold tracking-wider">Participante</p>
                <h6 id="modalNombreParticipante" class="fw-bold text-secondary mb-4"></h6>
                
                <p class="text-muted small mb-2 text-uppercase fw-semibold tracking-wider">Opciones Votadas</p>
                <div id="modalListaDeportes" class="d-flex flex-column gap-2"></div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary rounded-pill btn-sm px-4 js-close-modal" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('admin/header_admin'); ?>

<div class="container mb-5 mt-4">
    
    <div class="d-flex justify-content-center mb-4">
        <ul class="nav nav-pills bg-white p-2 rounded-pill shadow-sm" id="controlTabs">
            <li class="nav-item">
                <button class="nav-link active rounded-pill px-4 fw-bold js-tab-btn" data-target="#panel-encuestas" type="button">
                    <i class="bi bi-clipboard2-data-fill me-2"></i>Respuestas de Encuestas
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-4 fw-bold js-tab-btn" data-target="#panel-inscripciones" type="button">
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
        
        <div class="tab-custom-pane active-pane fade show" id="panel-encuestas">
            <?php $this->load->view('admin/panel-encuestas'); ?>
        </div>

        <div class="tab-custom-pane fade" id="panel-inscripciones">
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

    // ----------------------------------------
    // CONTROLADOR DEL MODAL BLINDADO A FALLOS
    // ----------------------------------------
    const modalElement = document.getElementById('modalDeportesVotados');

    // Escuchamos los clics en los botones de "Ver Deportes"
    document.addEventListener('click', function(e) {
        const button = e.target.closest('[data-bs-target="#modalDeportesVotados"]');
        if (!button) return;
        
        e.preventDefault();

        const participante = button.getAttribute('data-participante');
        const deportesString = button.getAttribute('data-deportes');
        
        // Separamos por comas las opciones
        const listaDeportes = deportesString.split(', ');

        // Inyectamos datos en los contenedores
        document.getElementById('modalNombreParticipante').innerText = participante;

        const contenedorLista = document.getElementById('modalListaDeportes');
        contenedorLista.innerHTML = '';

        listaDeportes.forEach(deporte => {
            const item = document.createElement('div');
            item.className = 'p-2 bg-light border rounded-3 fw-semibold text-dark text-uppercase small d-flex align-items-center';
            item.innerHTML = `<i class="bi bi-check-circle-fill text-success me-2"></i> ${deporte}`;
            contenedorLista.appendChild(item);
        });

        // MÉTODO INMUNE: Intentamos usar Bootstrap, si falla, usamos CSS puro directo al DOM
        try {
            const bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
        } catch (error) {
            console.log("Bootstrap JS bloqueado. Usando apertura forzada por CSS.");
            modalElement.classList.add('js-force-show');
        }
    });

    // Escuchador manual para cerrar el modal si se usó la apertura forzada
    document.querySelectorAll('.js-close-modal').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            modalElement.classList.remove('js-force-show');
            
            // Intento de cierre por objeto por si acaso
            try {
                const instance = bootstrap.Modal.getInstance(modalElement);
                if(instance) instance.hide();
            } catch(e){}
        });
    });
});
</script>
</body>
</html>
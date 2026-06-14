<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas de Sondeo - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px 0;
            border-bottom: 5px solid #ffc107;
        }
        .card-indicador { border: none; border-radius: 12px; border-left: 5px solid #ffc107; }
        .nav-menu { background: #fff; border-bottom: 1px solid #dee2e6; }
        .nav-menu .nav-link { color: #495057; font-weight: 500; padding: 12px 20px; }
        .nav-menu .nav-link.active { color: #1e3c72; border-bottom: 3px solid #1e3c72; border-radius: 0; }
        .card-grafico { border: none; border-radius: 15px; }
        
        /* Forzamos el comportamiento por si los estilos nativos fallan */
        .tab-custom-pane { display: none; }
        .tab-custom-pane.active-pane { display: block !important; }

        /* Estilos de emergencia para el modal por si la librería JS está muerta */
        .modal.js-force-show {
            display: block !important;
            background: rgba(0, 0, 0, 0.5) !important;
            opacity: 1 !important;
        }
    </style>
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
        </ul>
    </div>

    <div class="tab-content" id="controlTabsContent">
        
        <div class="tab-custom-pane active-pane fade show" id="panel-encuestas">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-check text-warning me-2"></i> Registros de Sondeo Inicial</span>
                    <span class="badge bg-secondary"><?= count($listado_encuestas) ?> filas</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Participante / DNI</th>
                                    <th>Delegación</th>
                                    <th class="text-center">Deportes Votados</th> 
                                    <th>Edad / Sexo</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($listado_encuestas)): foreach($listado_encuestas as $enc): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            <?= !empty($enc['nombre_participante']) ? htmlspecialchars($enc['nombre_participante'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted italic small">Sondeo Anónimo</span>'; ?>
                                        </div>
                                        <small class="text-muted">DNI: <?= htmlspecialchars($enc['dni'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($enc['delegacion'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold js-btn-ver-deportes" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalDeportesVotados"
                                                data-participante="<?= !empty($enc['nombre_participante']) ? htmlspecialchars($enc['nombre_participante'], ENT_QUOTES, 'UTF-8') : 'Sondeo Anónimo (DNI: '.$enc['dni'].')'; ?>"
                                                data-deportes="<?= !empty($enc['deportes_votados']) ? htmlspecialchars($enc['deportes_votados'], ENT_QUOTES, 'UTF-8') : 'Ninguno'; ?>">
                                            <i class="bi bi-eye-fill me-1"></i> Ver Deportes
                                        </button>
                                    </td>

                                    <td><small><?= htmlspecialchars($enc['sexo'], ENT_QUOTES, 'UTF-8') ?></small></td>
                                    <td class="text-center">
                                        <a href="<?= base_url('Inscripciones/eliminar_encuesta/'.$enc['id_respuesta']) ?>" 
                                        class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('¿Seguro querés borrar esta encuesta?');">
                                            <i class="bi bi-trash3-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No hay encuestas en la base de datos.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-custom-pane fade" id="panel-inscripciones">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people-fill text-primary me-2"></i> Padrón de Inscriptos Oficiales</span>
                    <span class="badge bg-secondary"><?= count($listado_inscripciones) ?> filas</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Competidor</th>
                                    <th>Delegación</th>
                                    <th>Deporte / Categoría</th>
                                    <th>Hotel</th>
                                    <th>Estado Kit</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($listado_inscripciones)): foreach($listado_inscripciones as $ins): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($ins['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <small class="text-muted">DNI: <?= htmlspecialchars($ins['dni'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-success border"><?= htmlspecialchars($ins['delegacion'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark text-uppercase small"><?= htmlspecialchars($ins['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($ins['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td>
                                        <?= !empty($ins['hotel_alojamiento']) ? htmlspecialchars($ins['hotel_alojamiento'], ENT_QUOTES, 'UTF-8') : '<span class="text-danger small fw-bold">SIN ASIGNAR</span>'; ?>
                                    </td>
                                    <td>
                                        <?php if($ins['kit_entregado'] == 1): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle small">Kit OK</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('Inscripciones/eliminar_inscripcion/'.$ins['id_inscripcion']) ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('¿Seguro querés dar de baja esta inscripción deportiva?');">
                                            <i class="bi bi-person-x-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No hay inscripciones registradas todavía.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
    document.querySelectorAll('.js-btn-ver-deportes').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault(); // Evitamos cualquier acción nativa extraña

            const participante = this.getAttribute('data-participante');
            const deportesString = this.getAttribute('data-deportes');
            
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
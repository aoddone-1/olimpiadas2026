<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Delegado - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <style>
        .header-delegado {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .card-participante {
            border-left: 4px solid #28a745;
            transition: transform 0.2s;
        }
        .card-participante:hover {
            transform: translateX(5px);
        }
        .badge-deporte {
            background-color: #e9f7ef;
            color: #28a745;
            border: 1px solid #28a745;
        }
    </style>
</head>
<body>

<!-- Header específico para delegados -->
<div class="header-delegado shadow-sm">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="m-0 fw-bold"><i class="bi bi-people-fill me-2"></i>Panel de Delegación</h4>
                <small class="opacity-75"><?= htmlspecialchars($delegacion) ?></small>
            </div>
            <div>
                <a href="<?= base_url('Inscripciones/logout') ?>" class="btn btn-light btn-sm text-success fw-bold">
                    <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">

    <!-- Tarjetas de resumen -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-indicador shadow-sm" style="border-left-color: #28a745;">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-1">Total Inscriptos</h6>
                    <h3 class="fw-bold text-success m-0"><?= $total_inscriptos ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de participantes -->
    <div class="mb-4 search-container">
        <div class="input-group shadow-sm rounded-3 overflow-hidden">
            <span class="input-group-text bg-white border-0 text-muted ps-3"><i class="bi bi-search"></i></span>
            <input type="text" id="buscador" class="form-control border-0 p-3 shadow-none" placeholder="Buscar competidor por Nombre o DNI...">
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-secondary m-0">Competidores de tu Delegación</h5>
        <span id="contador-resultados" class="badge bg-success text-white px-2 py-1.5 rounded" style="display: none;"></span>
    </div>
    
    <div id="lista-participantes">
        <?php if(!empty($participantes)): ?>
            <div class="table-responsive bg-white rounded-3 shadow-sm overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase fw-bold">Nombre</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold">DNI</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold">Deportes</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold text-end pe-4">Estado</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($participantes as $p): ?>
                            <tr class="fila-participante" 
                                data-string="<?= strtoupper(htmlspecialchars($p['nombre_completo'] . ' ' . $p['dni'])) ?>" 
                                style="border-bottom: 1px solid #f0f0f0;">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" 
                                             style="width: 40px; height: 40px; min-width: 40px;">
                                            <i class="bi bi-person-fill text-success"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['nombre_completo']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($p['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['dni']) ?></span>
                                </td>
                                <td>
                                    <?php if(isset($p['es_competidor']) && intval($p['es_competidor']) === 0): ?>
                                        <span class="badge bg-info text-white">
                                            <i class="bi bi-person-check-fill me-1"></i>Acompañante
                                        </span>
                                    <?php elseif(!empty($p['deportes'])): ?>
                                        <?php foreach($p['deportes'] as $dep): ?>
                                            <span class="badge badge-deporte me-1 mb-1">
                                                <i class="bi bi-trophy me-1"></i><?= htmlspecialchars($dep['nombre_deporte']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin deportes</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>Inscripto
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-info"
                                            title="Ver Detalles"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDetalleInscripcion"
                                            data-id="<?= $p['id_participante'] ?>">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info border-0 shadow-sm">
                <i class="bi bi-info-circle-fill me-2"></i>
                No hay participantes inscriptos para tu delegación aún.
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal de Detalle de Inscripción -->
<div class="modal fade" id="modalDetalleInscripcion" tabindex="-1" aria-labelledby="modalDetalleInscripcionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-success text-white py-3">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        <h5 class="modal-title fw-bold" id="modalDetalleInscripcionLabel">
                            <i class="bi bi-person-vcard-fill me-2"></i>Detalles de Inscripción
                        </h5>
                    </div>
                    
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle" style='max-height:70vh; overflow-y:auto;'>
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4 gap-3">

                    <div>
                        <h4 class="text-dark fw-bold mb-0" id="det-nombre"></h4>
                        <span class="badge bg-secondary border mt-1" id="det-rol"></span>
                        <span class="badge bg-danger border mt-1 ms-1" id="det-badge-delegado" style="display:none;">DELEGADO</span>
                    </div>
                    <div class="text-end">
                        <img id="det-qr-small" src="" alt="QR" style="width: 100px; height: 100px; border: 1px solid #fff; padding: 2px; background: #fff; display: none;">
                    </div>
                </div>
                

                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white">
                            <h6 class="text-success fw-bold mb-3 border-bottom pb-2"><i class="bi bi-info-circle me-2"></i>Datos Personales</h6>
                            <p class="mb-2"><strong>DNI:</strong> <span class="text-muted" id="det-dni"></span></p>
                            <p class="mb-2"><strong>Sexo:</strong> <span class="text-muted" id="det-sexo"></span></p>
                            <p class="mb-2"><strong>Fecha Nacimiento:</strong> <span class="text-muted" id="det-fnac"></span></p>
                            <p class="mb-2"><strong>Grupo Sanguíneo:</strong> <span class="badge bg-light text-dark border px-2 fw-bold" id="det-gsanguineo"></span></p>
                            <p class="mb-2"><strong>Obra Social:</strong> <span class="text-muted" id="det-osocial"></span></p>
                            <p class="mb-0"><strong>Tipo de Empleado:</strong> <span class="text-muted" id="det-empleado"></span></p>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white">
                            <h6 class="text-success fw-bold mb-3 border-bottom pb-2"><i class="bi bi-telephone me-2"></i>Contacto y Logística</h6>
                            <p class="mb-2"><strong>Email:</strong> <span class="text-muted" id="det-email"></span></p>
                            <p class="mb-2"><strong>Teléfono:</strong> <span class="text-muted" id="det-telefono"></span></p>
                            <p class="mb-2"><strong>Delegación:</strong> <span class="badge bg-light text-dark border fw-semibold" id="det-delegacion"></span></p>
                            <p class="mb-2"><strong>Contacto Emergencia:</strong> <span class="text-muted" id="det-emergencia"></span></p>
                            <p class="mb-0"><strong>Dieta Especial:</strong> <span class="text-muted fw-semibold" id="det-dieta"></span></p>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                            <h6 class="text-success fw-bold mb-3 border-bottom pb-2"><i class="bi bi-trophy me-2"></i>Disciplinas Deportivas Asignadas</h6>
                            
                            <!-- Mensaje para acompañantes -->
                            <div id="mensaje-acompanante" class="alert alert-info d-none">
                                <i class="bi bi-person-check-fill me-2"></i><strong>Es Acompañante</strong> - No tiene deportes asignados.
                            </div>
                            
                            <div id="tabla-deportes-container">
                                <div class="table-responsive mb-4">
                                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                                        <thead class="table-light text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">
                                            <tr>
                                                <th style="width: 25%;"><i class="bi bi-flag-fill me-1"></i> Deporte</th>
                                                <th style="width: 25%;"><i class="bi bi-tags-fill me-1"></i> Categoría</th>
                                                <th style="width: 20%;" class="text-center"><i class="bi bi-diagram-3-fill me-1"></i> UTE</th>
                                                <th style="width: 30%;"><i class="bi bi-chat-left-text-fill me-1"></i> Observación / Detalle</th>
                                            </tr>
                                        </thead>
                                        <tbody id="det-tabla-deportes-body">
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <h6 class="text-secondary fw-bold mb-3 border-bottom pb-2" style="font-size: 0.9rem;"><i class="bi bi-building me-2"></i>Logística de Estadía y Registro</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Hotel Asignado:</strong><br><span class="fw-bold text-dark fs-6" id="det-hotel"></span></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Estado del Kit:</strong><br><span id="det-kit"></span></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Alta de Registro:</strong><br><span class="text-muted small" id="det-finscripcion"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2">
                <button type="button" class="btn btn-secondary rounded-pill btn-sm px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const buscador = document.getElementById('buscador');
    const filas = document.querySelectorAll('.fila-participante');
    const contadorResultados = document.getElementById('contador-resultados');

    if (buscador) {
        buscador.addEventListener('input', function() {
            const query = this.value.toUpperCase();
            let encontrados = 0;

            filas.forEach(fila => {
                if (fila.getAttribute('data-string').includes(query)) {
                    fila.style.setProperty('display', 'table-row', 'important');
                    encontrados++;
                } else {
                    fila.style.setProperty('display', 'none', 'important');
                }
            });

            if (query === "") {
                contadorResultados.style.display = 'none';
            } else {
                contadorResultados.innerText = encontrados + (encontrados === 1 ? ' coincidencia' : ' coincidencias');
                contadorResultados.style.display = 'inline-block';
            }
        });
    }

    // Manejo del modal de detalles para delegados
    const modalElemento = id => document.getElementById(id);
    const modalDetalle = document.getElementById('modalDetalleInscripcion');
    if (modalDetalle) {
        const instanciaModal = new bootstrap.Modal(modalDetalle);
        
        modalDetalle.addEventListener('show.bs.modal', function(event) {
            const boton = event.relatedTarget;
            const idParticipante = boton.getAttribute('data-id');
            
            console.log('ID Participante:', idParticipante);
            
            if (!idParticipante) {
                console.error('No se encontró el ID del participante');
                return;
            }
            
            // Bloquear el botón mientras carga
            boton.disabled = true;
            const iconoOriginal = boton.innerHTML;
            boton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            
            // Fetch a la API
            fetch(`<?= base_url('Inscripciones/detalle_ajax/') ?>${idParticipante}`)
                .then(response => {
                    console.log('Status:', response.status);
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data);
                    
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    // Cargar datos en el modal
                    modalElemento('det-nombre').innerText = data.nombre_completo || 'Sin Nombre';
                    const esComp = parseInt(data.es_competidor) === 1;
                    modalElemento('det-rol').innerText = esComp ? 'COMPETIDOR' : 'ACOMPAÑANTE';
                    modalElemento('det-rol').className = esComp ? "badge bg-primary border mt-1" : "badge bg-secondary border mt-1";
                    modalElemento('det-badge-delegado').style.display = (parseInt(data.es_delegado) === 1) ? 'inline-block' : 'none';
                    
                    // Datos personales
                    modalElemento('det-dni').innerText = data.dni || '-';
                    modalElemento('det-sexo').innerText = data.sexo || 'No especificado';
                    
                    if (data.fecha_nacimiento) {
                        const parts = data.fecha_nacimiento.split('-');
                        modalElemento('det-fnac').innerText = `${parts[2]}/${parts[1]}/${parts[0]}`;
                    } else {
                        modalElemento('det-fnac').innerText = '-';
                    }
                    
                    modalElemento('det-gsanguineo').innerText = data.grupo_sanguineo || 'No especificado';
                    modalElemento('det-osocial').innerText = data.obra_social || 'No especificada';
                    modalElemento('det-empleado').innerText = data.tipo_empleado || 'No especificado';
                    
                    // Contacto y logística
                    modalElemento('det-email').innerText = data.email || '-';
                    modalElemento('det-telefono').innerText = data.telefono || '-';
                    modalElemento('det-delegacion').innerText = data.delegacion || '-';
                    modalElemento('det-emergencia').innerText = data.contacto_emergencia || '-';
                    modalElemento('det-dieta').innerText = data.dieta_especial || 'Ninguna';
                    
                    // QR y Deslinde - QR pequeño en el header
                    const imgQRSmall = document.getElementById('det-qr-small');
                    
                    if (data.token_qr) {
                        // Construir URL del QR usando el endpoint existente de acreditación
                        const qrUrl = '<?= base_url() ?>Inscripciones/acreditacion/' + data.token_qr;
                        // Usamos un servicio de QR con tamaño chico para el header
                        imgQRSmall.src = 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' + encodeURIComponent(qrUrl);
                        imgQRSmall.style.display = 'inline-block';
                        
                        // Botón de deslinde en el footer
                        const btnDeslinde = document.createElement('a');
                        btnDeslinde.href = '<?= base_url() ?>Inscripciones/descargar_deslinde/' + data.token_qr;
                        btnDeslinde.className = 'btn btn-outline-danger btn-sm rounded-pill px-3';
                        btnDeslinde.target = '_blank';
                        btnDeslinde.innerHTML = '<i class="bi bi-file-earmark-pdf me-1"></i>Descargar Deslinde';
                        
                        // Limpiar footer y agregar botón
                        const modalFooter = document.querySelector('#modalDetalleInscripcion .modal-footer');
                        modalFooter.innerHTML = '';
                        modalFooter.appendChild(btnDeslinde);
                        modalFooter.appendChild(document.createElement('button')).outerHTML = '<button type="button" class="btn btn-secondary rounded-pill btn-sm px-4" data-bs-dismiss="modal">Cerrar</button>';
                    } else {
                        imgQRSmall.style.display = 'none';
                    }
                    
                    // Deportes - Mostrar u ocultar según si es acompañante
                    const mensajeAcompanante = document.getElementById('mensaje-acompanante');
                    const tablaDeportesContainer = document.getElementById('tabla-deportes-container');
                    const tbodyDeportes = modalElemento('det-tabla-deportes-body');
                    tbodyDeportes.innerHTML = '';
                    
                    const esCompetidor = parseInt(data.es_competidor) === 1;
                    
                    if (!esCompetidor) {
                        // Es acompañante: mostrar mensaje y ocultar tabla
                        mensajeAcompanante.classList.remove('d-none');
                        tablaDeportesContainer.style.display = 'none';
                    } else if (data.deportes && data.deportes.length > 0) {
                        // Es competidor con deportes: mostrar tabla
                        mensajeAcompanante.classList.add('d-none');
                        tablaDeportesContainer.style.display = 'block';
                        data.deportes.forEach(dep => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${dep.nombre_deporte || '-'}</td>
                                <td>${dep.nombre_categoria || '-'}</td>
                                <td class="text-center">${dep.ute || '-'}</td>
                                <td>${dep.observacion || dep.detalle || '-'}</td>
                            `;
                            tbodyDeportes.appendChild(row);
                        });
                    } else {
                        // Es competidor sin deportes
                        mensajeAcompanante.classList.add('d-none');
                        tablaDeportesContainer.style.display = 'block';
                        tbodyDeportes.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Sin deportes asignados</td></tr>';
                    }
                    
                    // Hotel y kit
                    modalElemento('det-hotel').innerText = data.hotel_alojamiento || 'Sin asignar';
                    
                    const kitEntregado = parseInt(data.kit_entregado) === 1;
                    modalElemento('det-kit').innerHTML = kitEntregado 
                        ? '<span class="badge bg-success border">Entregado</span>'
                        : '<span class="badge bg-warning text-dark border">Pendiente</span>';
                    
                    if (data.fecha_inscripcion) {
                        const parts = data.fecha_inscripcion.split('-');
                        modalElemento('det-finscripcion').innerText = data.fecha_inscripcion;
                    } else {
                        modalElemento('det-finscripcion').innerText = '-';
                    }
                    
                    // Restaurar botón
                    boton.disabled = false;
                    boton.innerHTML = iconoOriginal;
                    
                    instanciaModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los detalles. Intente nuevamente.');
                    boton.disabled = false;
                    boton.innerHTML = iconoOriginal;
                });
        });
    }
});
</script>

</body>
</html>

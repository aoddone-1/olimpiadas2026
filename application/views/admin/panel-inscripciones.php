 <?php include("modals/control_total_modals.php");?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="d-flex align-items-center">
            <i class="bi bi-people-fill text-primary me-2"></i> 
            <span>Padrón de Inscriptos Oficiales</span>
            <span class="badge bg-secondary ms-2" id="contador-inscripciones"><?= count($listado_inscripciones) ?> filas</span>
        </div>
        
        <div class="position-relative" style="max-width: 300px; width: 100%;">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" 
                   id="inputBuscarInscripcion" 
                   class="form-control form-control-sm rounded-pill ps-5 bg-light" 
                   placeholder="Buscar por Nombre, DNI, Deporte, Hotel...">
        </div>
    </div>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaInscripciones">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Participante / DNI</th>
                        <th>Delegación</th>
                        <th>Rol / Asistencia</th>
                        <th>Deporte / Categoría</th>
                        <th>Hotel Alojamiento</th>
                        <th class="text-center">Estado Kit</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($listado_inscripciones)): foreach($listado_inscripciones as $ins): ?>
                    <tr class="js-fila-inscripcion">
                        <td>
                            <div class="fw-bold text-dark">
                                <?= htmlspecialchars($ins['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if(($ins['es_delegado'] ?? 0) == 1): ?>
                                    <span class="badge bg-danger small ms-1" style="font-size:0.65rem;">DELEGADO</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">DNI: <?= htmlspecialchars($ins['dni'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        
                        <td>
                            <span class="badge bg-light text-primary border">
                                <?= htmlspecialchars($ins['delegacion'] ?? 'Sin Especificar', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>

                        <td>
                            <?php if(($ins['es_competidor'] ?? 1) == 0): ?>
                                <span class="badge bg-secondary-subtle text-secondary border small">Acompañante</span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary border small">Competidor</span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <?php if(!empty($ins['deportes_nombres'])): ?>
                                <div class="fw-semibold text-dark text-uppercase small" style="max-width: 250px; white-space: normal;">
                                    <?= htmlspecialchars($ins['deportes_nombres'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.8rem; max-width: 250px; white-space: normal;">
                                    Cat: <?= htmlspecialchars($ins['categorias_nombres'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small italic">---</span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <div class="small fw-semibold text-dark">
                                <?= !empty($ins['hotel_alojamiento']) ? htmlspecialchars($ins['hotel_alojamiento'], ENT_QUOTES, 'UTF-8') : '<span class="text-danger small fw-bold">SIN ASIGNAR</span>'; ?>
                            </div>
                        </td>
                        
                        <td class="text-center">
                            <?php if(($ins['kit_entregado'] ?? 0) == 1): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle small px-3 rounded-pill">Kit OK</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small px-3 rounded-pill">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-info me-1"
                                        title="Ver Detalles Completos"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDetalleInscripcion"
                                        data-id="<?= $ins['id_participante'] ?>">
                                    <i class="bi bi-search"></i>
                                </button>

                                <a href="<?= base_url('Inscripciones/modificar_inscripcion/'.$ins['id_participante']) ?>" 
                                   class="btn btn-sm btn-outline-warning me-1" 
                                   title="Modificar Inscripción">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-id="<?= $ins['id_participante'] ?>"
                                        data-nombre="<?= htmlspecialchars($ins['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>"
                                        title="Eliminar Registro">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr id="filaNoHayDatosIns"><td colspan="7" class="text-center text-muted py-4">No hay inscripciones oficiales registradas todavía.</td></tr>
                    <?php endif; ?>
                    
                    <tr id="filaNoResultadosIns" style="display: none;">
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-exclamation-circle text-danger me-2"></i>No se encontraron coincidencias para tu búsqueda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if(!empty($listado_inscripciones)): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2 border-top pt-3">
            <div class="text-muted small">
                Mostrando filas del <span id="pagStartIns">0</span> al <span id="pagEndIns">0</span>
            </div>
            <nav aria-label="Navegacion de inscriptos">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="ulPaginacionIns">
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputBuscarIns = document.getElementById('inputBuscarInscripcion');
    const filasIns = Array.from(document.querySelectorAll('.js-fila-inscripcion'));
    const filaNoResultadosIns = document.getElementById('filaNoResultadosIns');
    const contadorIns = document.getElementById('contador-inscripciones');
    
    const filasPorPaginaIns = 10; 
    let paginaActualIns = 1;
    let filasFiltradasIns = [...filasIns];

    function actualizarTablaInscripciones() {
        const totalFilasIns = filasFiltradasIns.length;
        const totalPaginasIns = Math.ceil(totalFilasIns / filasPorPaginaIns) || 1;

        if (paginaActualIns > totalPaginasIns) paginaActualIns = totalPaginasIns;
        if (paginaActualIns < 1) paginaActualIns = 1;

        const inicioIns = (paginaActualIns - 1) * filasPorPaginaIns;
        const finIns = inicioIns + filasPorPaginaIns;

        filasIns.forEach(f => f.style.display = 'none');

        filasFiltradasIns.forEach((fila, index) => {
            if (index >= inicioIns && index < finIns) {
                fila.style.display = '';
            }
        });

        const pStartIns = document.getElementById('pagStartIns');
        const pEndIns = document.getElementById('pagEndIns');
        if(pStartIns && pEndIns) {
            pStartIns.innerText = totalFilasIns === 0 ? 0 : inicioIns + 1;
            pEndIns.innerText = finIns > totalFilasIns ? totalFilasIns : finIns;
        }

        const ulPaginacionIns = document.getElementById('ulPaginacionIns');
        if (ulPaginacionIns) {
            ulPaginacionIns.innerHTML = '';

            if (totalPaginasIns > 1) {
                ulPaginacionIns.innerHTML += `
                    <li class="page-item ${paginaActualIns === 1 ? 'disabled' : ''}">
                        <button class="page-link" data-page-ins="${paginaActualIns - 1}">&laquo;</button>
                    </li>`;

                for (let i = 1; i <= totalPaginasIns; i++) {
                    ulPaginacionIns.innerHTML += `
                        <li class="page-item ${paginaActualIns === i ? 'active' : ''}">
                            <button class="page-link" data-page-ins="${i}">${i}</button>
                        </li>`;
                }

                ulPaginacionIns.innerHTML += `
                    <li class="page-item ${paginaActualIns === totalPaginasIns ? 'disabled' : ''}">
                        <button class="page-link" data-page-ins="${paginaActualIns + 1}">&raquo;</button>
                    </li>`;

                ulPaginacionIns.querySelectorAll('button').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const nuevaPagIns = parseInt(this.getAttribute('data-page-ins'));
                        if (nuevaPagIns >= 1 && nuevaPagIns <= totalPaginasIns) {
                            paginaActualIns = nuevaPagIns;
                            actualizarTablaInscripciones();
                        }
                    });
                });
            }
        }

        if (totalFilasIns === 0 && filasIns.length > 0) {
            filaNoResultadosIns.style.display = '';
        } else {
            filaNoResultadosIns.style.display = 'none';
        }

        if (contadorIns) {
            contadorIns.innerText = `${totalFilasIns} ${totalFilasIns === 1 ? 'fila' : 'filas'}`;
        }
    }

    if (inputBuscarIns) {
        inputBuscarIns.addEventListener('input', function () {
            const termino = this.value.toLowerCase().trim();

            filasFiltradasIns = filasIns.filter(fila => {
                return fila.textContent.toLowerCase().includes(termino);
            });

            paginaActualIns = 1; 
            actualizarTablaInscripciones();
        });
    }

    actualizarTablaInscripciones();


    // ==========================================
    // CARGA OPERATIVA MEDIANTE AJAX (FETCH) - VERSION BLINDADA
    // ==========================================
    const modalElemento = id => document.getElementById(id);
    const modalInstancia = new bootstrap.Modal(document.getElementById('modalDetalleInscripcion'));

    // Buscamos la tabla con una alternativa por si falló el ID
    const tablaElemento = document.getElementById('tablaInscripciones') || document.querySelector('.table');

    if (!tablaElemento) {
        console.error("❌ ERROR OPERATIVO: No se encontró la tabla en el DOM con el ID 'tablaInscripciones'. Revisá el HTML.");
    } else {
        console.log("✅ Selector de tabla detectado correctamente. Escuchando clics...");
        
        // Escuchamos el evento
        tablaElemento.addEventListener('click', function(e) {
            // Log para ver EXACTAMENTE dónde está haciendo clic el administrador
            console.log("Clic detectado en el elemento:", e.target);

            // Buscamos el botón subiendo desde el elemento clickeado (sea el <i> o el <button>)
            const boton = e.target.closest('[data-bs-target="#modalDetalleInscripcion"]');
            
            if (!boton) {
                console.log("⚠️ Clic fuera del botón de la lupita (ignorado).");
                return;
            }

            // Si llegó acá, encontramos el botón con éxito
            const idParticipante = boton.getAttribute('data-id') || boton.dataset.id;
            console.log("🚀 Botón de lupita detectado con éxito! ID Participante:", idParticipante);
            
            if (!idParticipante) {
                console.error("❌ ERROR: El botón clickeado no tiene el atributo 'data-id' cargado.");
                return;
            }

            // Bloqueo preventivo de doble click y spinner visual
            const iconoOriginal = boton.innerHTML;
            boton.disabled = true;
            boton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            const urlPeticion = `<?= base_url('Inscripciones/detalle_ajax/') ?>${idParticipante}`;
            console.log("📡 Enviando FETCH a la URL:", urlPeticion);

            // Petición al controlador
            fetch(urlPeticion)
                .then(response => {
                    console.log("📥 Respuesta cruda del servidor recibida:", response);
                    if (!response.ok) {
                        throw new Error(`Error HTTP de servidor! Estado: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("📦 Datos JSON decodificados con éxito:", data);

                    if(data.error) {
                        alert("Error del sistema: " + data.error);
                        return;
                    }

                    // 1. Mapeamos Cabecera Principal
                    modalElemento('det-nombre').innerText = data.nombre_completo || 'Sin Nombre';
                    const esComp = parseInt(data.es_competidor) === 1;
                    modalElemento('det-rol').innerText = esComp ? 'COMPETIDOR' : 'ACOMPAÑANTE';
                    modalElemento('det-rol').className = esComp ? "badge bg-primary border mt-1" : "badge bg-secondary border mt-1";
                    modalElemento('det-badge-delegado').style.display = (parseInt(data.es_delegado) === 1) ? 'inline-block' : 'none';

                    // 2. Mapeamos Datos Personales
                    modalElemento('det-dni').innerText = data.dni || '-';
                    modalElemento('det-sexo').innerText = data.sexo || 'No especificado';
                    
                    if(data.fecha_nacimiento) {
                        const parts = data.fecha_nacimiento.split('-');
                        modalElemento('det-fnac').innerText = `${parts[2]}/${parts[1]}/${parts[0]}`;
                    } else {
                        modalElemento('det-fnac').innerText = 'No registrada';
                    }
                    
                    modalElemento('det-gsanguineo').innerText = data.grupo_sanguineo || '-';
                    modalElemento('det-osocial').innerText = data.obra_social || 'No posee / No registra';
                    modalElemento('det-empleado').innerText = data.tipo_empleado || 'No especificado';

                    // 3. Mapeamos Datos de Contacto
                    modalElemento('det-email').innerText = data.email || 'No registrado';
                    modalElemento('det-telefono').innerText = data.telefono || 'No registrado';
                    modalElemento('det-delegacion').innerText = data.delegacion || 'Sin Especificar';
                    modalElemento('det-emergencia').innerText = data.contacto_emergencia || 'No registrado';
                    modalElemento('det-dieta').innerText = data.dieta_especial || 'Ninguna / Común';

                    // 4. Mapeamos Deportes y Categorías en formato de 4 columnas con Colspan dinámico
                    const tablaBody = document.getElementById('det-tabla-deportes-body');
                    tablaBody.innerHTML = ''; // Limpiamos registros anteriores

                    if (data.deportes && data.deportes.length > 0) {
                        data.deportes.forEach(d => {
                            let filaHtml = '';

                            // Agrupamos condiciones para saber si maneja lógica de equipo/UTE
                            const tieneUte = parseInt(d.tiene_ute) === 1;
                            const necesitaUte = parseInt(d.necesita_ute) === 1 || d.necesita_ute === 'SI';
                            const tieneDetalle = d.detalle_ute && d.detalle_ute.trim() !== '';

                            if (tieneUte || necesitaUte || tieneDetalle) {
                                // ==========================================
                                // CASO A: ES DEPORTE DE EQUIPO / DUPLA
                                // ==========================================
                                let badgeUte = '';
                                let observacionUte = '';

                                if (tieneUte || tieneDetalle) {
                                    badgeUte = `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">TIENE UTE</span>`;
                                    observacionUte = `<span class="fw-semibold text-dark"><i class="bi bi-people-fill me-1"></i>${d.detalle_ute || 'Configurado'}</span>`;
                                } else if (necesitaUte) {
                                    badgeUte = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2">SOLICITA UTE</span>`;
                                    observacionUte = `<span class="text-muted small italic"><i class="bi bi-search me-1"></i>Busca compañero de otra delegación</span>`;
                                }

                                filaHtml = `
                                    <tr>
                                        <td class="fw-bold text-dark text-uppercase">${d.nombre_deporte}</td>
                                        <td class="text-muted">${d.nombre_categoria || 'Única / General'}</td>
                                        <td class="text-center">${badgeUte}</td>
                                        <td>${observacionUte}</td>
                                    </tr>
                                `;
                            } else {
                                // ==========================================
                                // CASO B: DEPORTE INDIVIDUAL (Hacemos Colspan)
                                // ==========================================
                                filaHtml = `
                                    <tr>
                                        <td class="fw-bold text-dark text-uppercase">${d.nombre_deporte}</td>
                                        <td class="text-muted">${d.nombre_categoria || 'Única / General'}</td>
                                        <td colspan="2" class="text-center text-muted bg-light-subtle small italic">
                                            <i class="bi bi-dash-lg text-secondary"></i> Disciplina Individual / Pareja Propia <i class="bi bi-dash-lg text-secondary"></i>
                                        </td>
                                    </tr>
                                `;
                            }

                            tablaBody.innerHTML += filaHtml;
                        });
                    } else {
                        // Si el registro es un acompañante o no está anotado en nada
                        tablaBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center text-muted italic py-3">
                                    <i class="bi bi-info-circle me-1"></i> Sin disciplinas asignadas (Acompañante / No Competidor)
                                </td>
                            </tr>
                        `;
                    }

                    // 5. Renderizado Visual del Hotel
                    if (!data.hotel_alojamiento || data.hotel_alojamiento.trim() === '') {
                        modalElemento('det-hotel').innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>SIN ASIGNAR</span>`;
                    } else {
                        modalElemento('det-hotel').innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-building-check me-1"></i>${data.hotel_alojamiento}</span>`;
                    }

                    // 6. Renderizado Visual del Kit
                    if (parseInt(data.kit_entregado) === 1) {
                        modalElemento('det-kit').innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">ENTREGADO</span>`;
                    } else {
                        modalElemento('det-kit').innerHTML = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-bold">PENDIENTE</span>`;
                    }

                    // 7. Registro de timestamp
                    modalElemento('det-finscripcion').innerText = data.fecha_inscripcion || '---';

                    // Desplegamos el modal por Bootstrap 5
                    console.log("🎬 Abriendo ventana modal...");
                    modalInstancia.show();
                })
                .catch(error => {
                    console.error("❌ ERROR AJAX DETECTADO:", error);
                    alert("Ocurrió un error al obtener el detalle: " + error.message);
                })
                .finally(() => {
                    // Restablecemos el botón original pase lo que pase
                    boton.disabled = false;
                    boton.innerHTML = iconoOriginal;
                });
        });
    }
    
    // ==========================================
    // ELIMINAR INSCRIPCION CON AJAX (FETCH)
    // ==========================================
    tablaElemento.addEventListener('click', function(e) {
        const botonEliminar = e.target.closest('.btn-outline-danger[data-id]');
        
        if (!botonEliminar) return;
        
        // Verificar que no sea el botón de ver detalle
        if (botonEliminar.hasAttribute('data-bs-target') && botonEliminar.getAttribute('data-bs-target') === '#modalDetalleInscripcion') {
            return;
        }
        
        const idParticipante = botonEliminar.getAttribute('data-id');
        const nombreParticipante = botonEliminar.getAttribute('data-nombre');
        
        if (!idParticipante) {
            console.error("❌ ERROR: El botón de eliminar no tiene data-id");
            return;
        }
        
        // Confirmación personalizada
        if (!confirm(`¿Estás SEGURO que deseas eliminar a ${nombreParticipante}?\n\nEsta acción borrará:\n- Todos sus datos personales\n- Sus inscripciones deportivas\n- No se podrá deshacer`)) {
            return;
        }
        
        // Bloqueo visual del botón
        const iconoOriginal = botonEliminar.innerHTML;
        botonEliminar.disabled = true;
        botonEliminar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        
        const urlPeticion = `<?= base_url('Inscripciones/eliminar_inscripcion_ajax/') ?>${idParticipante}`;
        
        fetch(urlPeticion, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Buscar la fila y eliminarla con animación
                const fila = botonEliminar.closest('.js-fila-inscripcion');
                if (fila) {
                    fila.style.transition = 'all 0.3s ease';
                    fila.style.opacity = '0';
                    fila.style.transform = 'translateX(-100%)';
                    
                    setTimeout(() => {
                        fila.remove();
                        
                        // Recalcular paginación y filtro
                        paginaActualIns = 1;
                        filasFiltradasIns = Array.from(document.querySelectorAll('.js-fila-inscripcion'));
                        actualizarTablaInscripciones();
                        
                        // Mostrar mensaje de éxito
                        alert('✅ ' + data.mensaje);
                    }, 300);
                }
            } else {
                alert('❌ Error: ' + (data.error || 'No se pudo eliminar la inscripción'));
                botonEliminar.disabled = false;
                botonEliminar.innerHTML = iconoOriginal;
            }
        })
        .catch(error => {
            console.error("❌ ERROR EN FETCH:", error);
            alert('❌ Ocurrió un error al eliminar: ' + error.message);
            botonEliminar.disabled = false;
            botonEliminar.innerHTML = iconoOriginal;
        });
    });
    
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
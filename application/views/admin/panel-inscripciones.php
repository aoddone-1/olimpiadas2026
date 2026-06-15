<div class="card shadow-sm border-0">
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
                   placeholder="Buscar competidor, DNI, deporte, hotel...">
        </div>
    </div>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaInscripciones">
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
                    <tr class="js-fila-inscripcion">
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
                        <tr id="filaNoHayDatosIns"><td colspan="6" class="text-center text-muted py-4">No hay inscripciones registradas todavía.</td></tr>
                    <?php endif; ?>
                    
                    <tr id="filaNoResultadosIns" style="display: none;">
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-exclamation-circle text-danger me-2"></i>No se encontraron competidores que coincidan con la búsqueda.
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
    
    // Configuración local del paginador
    const filasPorPaginaIns = 10; // Cambialo si querés ver más o menos por página
    let paginaActualIns = 1;
    let filasFiltradasIns = [...filasIns];

    function actualizarTablaInscripciones() {
        const totalFilasIns = filasFiltradasIns.length;
        const totalPaginasIns = Math.ceil(totalFilasIns / filasPorPaginaIns) || 1;

        if (paginaActualIns > totalPaginasIns) paginaActualIns = totalPaginasIns;
        if (paginaActualIns < 1) paginaActualIns = 1;

        const inicioIns = (paginaActualIns - 1) * filasPorPaginaIns;
        const finIns = inicioIns + filasPorPaginaIns;

        // Ocultamos todas primero
        filasIns.forEach(f => f.style.display = 'none');

        // Mostramos las de la página actual
        filasFiltradasIns.forEach((fila, index) => {
            if (index >= inicioIns && index < finIns) {
                fila.style.display = '';
            }
        });

        // Actualizar textos de rangos (Ins)
        const pStartIns = document.getElementById('pagStartIns');
        const pEndIns = document.getElementById('pagEndIns');
        if(pStartIns && pEndIns) {
            pStartIns.innerText = totalFilasIns === 0 ? 0 : inicioIns + 1;
            pEndIns.innerText = finIns > totalFilasIns ? totalFilasIns : finIns;
        }

        // Renderizar botones de paginación (Ins)
        const ulPaginacionIns = document.getElementById('ulPaginacionIns');
        if (ulPaginacionIns) {
            ulPaginacionIns.innerHTML = '';

            if (totalPaginasIns > 1) {
                // Anterior
                ulPaginacionIns.innerHTML += `
                    <li class="page-item ${paginaActualIns === 1 ? 'disabled' : ''}">
                        <button class="page-link" data-page-ins="${paginaActualIns - 1}">&laquo;</button>
                    </li>`;

                // Números
                for (let i = 1; i <= totalPaginasIns; i++) {
                    ulPaginacionIns.innerHTML += `
                        <li class="page-item ${paginaActualIns === i ? 'active' : ''}">
                            <button class="page-link" data-page-ins="${i}">${i}</button>
                        </li>`;
                }

                // Siguiente
                ulPaginacionIns.innerHTML += `
                    <li class="page-item ${paginaActualIns === totalPaginasIns ? 'disabled' : ''}">
                        <button class="page-link" data-page-ins="${paginaActualIns + 1}">&raquo;</button>
                    </li>`;

                // Eventos de clics en páginas
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

        // Mostrar cartel de alerta si está vacío el filtro
        if (totalFilasIns === 0 && filasIns.length > 0) {
            filaNoResultadosIns.style.display = '';
        } else {
            filaNoResultadosIns.style.display = 'none';
        }

        // Sincronizar badge
        if (contadorIns) {
            contadorIns.innerText = `${totalFilasIns} ${totalFilasIns === 1 ? 'fila' : 'filas'}`;
        }
    }

    // Evento del buscador
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

    // Arrancar la tabla limpia
    actualizarTablaInscripciones();
});
</script>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="d-flex align-items-center">
            <i class="bi bi-list-check text-warning me-2"></i> 
            <span>Registros de Sondeo Inicial</span>
            <span class="badge bg-secondary ms-2" id="contador-encuestas"><?= count($listado_encuestas) ?> filas</span>
        </div>
        
        <div class="position-relative" style="max-width: 300px; width: 100%;">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" 
                   id="inputBuscarEncuesta" 
                   class="form-control form-control-sm rounded-pill ps-5 bg-light" 
                   placeholder="Buscar por Nombre, DNI, Delegación...">
        </div>
    </div>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaEncuestas">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Participante / DNI</th>
                        <th>Delegación</th>
                        <th>Edad / Sexo</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($listado_encuestas)): foreach($listado_encuestas as $enc): ?>
                    <tr class="js-fila-encuesta">
                        <td>
                            <div class="fw-bold text-dark">
                                <?= !empty($enc['nombre_participante']) ? htmlspecialchars($enc['nombre_participante'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted italic small">Sondeo Anónimo</span>'; ?>
                            </div>
                            <small class="text-muted">DNI: <?= htmlspecialchars($enc['dni'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border"><?= htmlspecialchars($enc['delegacion'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark"><?= !empty($enc['edad']) ? $enc['edad'] . ' años' : '---'; ?></div>
                            <small class="text-muted text-capitalize"><?= htmlspecialchars($enc['sexo'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-info"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalDeportesVotados"
                                        data-participante="<?= !empty($enc['nombre_participante']) ? htmlspecialchars($enc['nombre_participante'], ENT_QUOTES, 'UTF-8') : 'Sondeo Anónimo (DNI: '.$enc['dni'].')'; ?>"
                                        data-deportes="<?= !empty($enc['deportes_votados']) ? htmlspecialchars($enc['deportes_votados'], ENT_QUOTES, 'UTF-8') : 'Ninguno'; ?>"
                                        title="Ver Deportes">
                                    <i class="bi bi-eye-fill"></i>
                                </button>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('¿Seguro querés borrar esta encuesta?');"
                                        title="Eliminar Encuesta">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr id="filaNoHayDatos"><td colspan="4" class="text-center text-muted py-4">No hay encuestas en la base de datos.</td></tr>
                    <?php endif; ?>
                    
                    <tr id="filaNoResultados" style="display: none;">
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="bi bi-exclamation-circle text-danger me-2"></i>No se encontraron coincidencias para tu búsqueda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if(!empty($listado_encuestas)): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2 border-top pt-3">
            <div class="text-muted small" id="infoPaginacion">
                Mostrando filas del <span id="pagStart">0</span> al <span id="pagEnd">0</span>
            </div>
            <nav aria-label="Navegacion de tabla">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="ulPaginacion">
                    </ul>
            </nav>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputBuscar = document.getElementById('inputBuscarEncuesta');
    const filas = Array.from(document.querySelectorAll('.js-fila-encuesta'));
    const filaNoResultados = document.getElementById('filaNoResultados');
    const contador = document.getElementById('contador-encuestas');
    
    // Configuración del paginador
    const filasPorPagina = 10; // <<--- CAMBIÁ ESTE NÚMERO PARA MOSTRAR MÁS O MENOS FILAS POR PÁGINA
    let paginaActual = 1;
    let filasFiltradas = [...filas]; // Al inicio, las filas filtradas son todas

    function actualizarTabla() {
        const totalFilas = filasFiltradas.length;
        const totalPaginas = Math.ceil(totalFilas / filasPorPagina) || 1;

        // Ajustar página actual si queda fuera de rango tras una búsqueda
        if (paginaActual > totalPaginas) paginaActual = totalPaginas;
        if (paginaActual < 1) paginaActual = 1;

        const indiceInicio = (paginaActual - 1) * filasPorPagina;
        const indiceFin = indiceInicio + filasPorPagina;

        // Ocultar todas las filas físicas primero
        filas.forEach(f => f.style.display = 'none');

        // Mostrar solo las filas que corresponden a la página actual
        filasFiltradas.forEach((fila, index) => {
            if (index >= indiceInicio && index < indiceFin) {
                fila.style.display = '';
            }
        });

        // Actualizar textos informativos de rangos
        const pStart = document.getElementById('pagStart');
        const pEnd = document.getElementById('pagEnd');
        if(pStart && pEnd) {
            pStart.innerText = totalFilas === 0 ? 0 : indiceInicio + 1;
            pEnd.innerText = indiceFin > totalFilas ? totalFilas : indiceFin;
        }

        // Renderizar los botones numéricos de la paginación
        const ulPaginacion = document.getElementById('ulPaginacion');
        if (ulPaginacion) {
            ulPaginacion.innerHTML = '';

            if (totalPaginas > 1) {
                // Botón Anterior
                ulPaginacion.innerHTML += `
                    <li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
                        <button class="page-link" data-page="${paginaActual - 1}">&laquo;</button>
                    </li>`;

                // Números de páginas
                for (let i = 1; i <= totalPaginas; i++) {
                    ulPaginacion.innerHTML += `
                        <li class="page-item ${paginaActual === i ? 'active' : ''}">
                            <button class="page-link" data-page="${i}">${i}</button>
                        </li>`;
                }

                // Botón Siguiente
                ulPaginacion.innerHTML += `
                    <li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
                        <button class="page-link" data-page="${paginaActual + 1}">&raquo;</button>
                    </li>`;

                // Escuchadores de eventos para los nuevos botones
                ulPaginacion.querySelectorAll('button').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const nuevaPagina = parseInt(this.getAttribute('data-page'));
                        if (nuevaPagina >= 1 && nuevaPagina <= totalPaginas) {
                            paginaActual = nuevaPagina;
                            actualizarTabla();
                        }
                    });
                });
            }
        }

        // Mostrar u ocultar cartel de "Sin resultados"
        if (totalFilas === 0 && filas.length > 0) {
            filaNoResultados.style.display = '';
        } else {
            filaNoResultados.style.display = 'none';
        }

        // Actualizar badge del contador general
        if (contador) {
            contador.innerText = `${totalFilas} ${totalFilas === 1 ? 'fila' : 'filas'}`;
        }
    }

    // Evento de búsqueda
    if (inputBuscar) {
        inputBuscar.addEventListener('input', function () {
            const terminoBusqueda = this.value.toLowerCase().trim();

            // Filtrar el array global en base al término de búsqueda
            filasFiltradas = filas.filter(fila => {
                return fila.textContent.toLowerCase().includes(terminoBusqueda);
            });

            paginaActual = 1; // Volvemos siempre a la página 1 al buscar
            actualizarTabla();
        });
    }

    // Inicializar la tabla al cargar la vista
    actualizarTabla();
});
</script>
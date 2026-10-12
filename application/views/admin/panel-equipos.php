<div class="card shadow-sm border-0 mb-4 card-inscriptos">
    <div class="card-header fx-card-header fw-bold text-secondary d-flex flex-column gap-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <!-- Título con ícono suave en lugar de texto plano -->
            <div class="d-flex align-items-center gap-2">
                <span class="fx-header-icon d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-list-check text-warning me-2"></i>
                </span>
                <span class="fx-header-titulo">Gestion de UTE/Equipos</span>
                <span class="badge bg-secondary ms-2" id="contador-equipos">0 filas</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button class="btn btn-lg btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUTE">
                    <i class="bi bi-plus-circle me-1"></i>
                </button>
                <div class="position-relative" style="max-width: 300px; width: 100%;">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" 
                           id="inputBuscarEquipo" 
                           class="form-control form-control-sm rounded-pill ps-5 bg-light" 
                           placeholder="Buscar por Nombre UTE, Deporte...">
                </div>
            </div>
        </div>
        <!-- Panel de Filtros -->
        <div class="row g-2 align-items-end" id="panelFiltrosEquipos">
            <div class="col-md-3">
                <label for="filtroDeporteEquipo" class="form-label small fw-semibold mb-1">
                    <i class="bi bi-trophy-fill text-warning me-1"></i>Deporte
                </label>
                <select id="filtroDeporteEquipo" class="form-select form-select-sm">
                    <option value="">Todos los Deportes</option>
                    <?php if(!empty($categorias)): 
                        $deportesVistos = [];
                        foreach($categorias as $cat): 
                            if(!in_array($cat['nombre_deporte'], $deportesVistos)):
                                $deportesVistos[] = $cat['nombre_deporte'];
                    ?>
                        <option value="<?= htmlspecialchars($cat['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($cat['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endif; endforeach; endif; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="filtroCategoriaEquipo" class="form-label small fw-semibold mb-1">
                    <i class="bi bi-layers-fill text-warning me-1"></i>Categoría
                </label>
                <select id="filtroCategoriaEquipo" class="form-select form-select-sm">
                    <option value="" data-deporte="">Todas las Categorías</option>
                    <?php if(!empty($categorias)): foreach($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?>" 
                                data-deporte="<?= htmlspecialchars($cat['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($cat['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($cat['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <button id="btnLimpiarFiltrosEquipos" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="bi bi-x-circle-fill me-1"></i>Limpiar Filtros
                </button>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle js-tabla-utes" id="tabla-utes">
                <thead class="small text-uppercase">
                    <tr>
                        <th><i class="bi bi-trophy me-1"></i>Deporte</th>
                        <th><i class="bi bi-layers me-1"></i>Categoría</th>
                        <th><i class="bi bi-people me-1"></i>Nombre UTE</th>
                        <th><i class="bi bi-people-fill me-1"></i>Integrantes</th>
                        <th><i class="bi bi-calendar3 me-1"></i>Fecha Creación</th>
                        <th class="text-center"><i class="bi bi-sliders me-1"></i>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-tabla-utes">
                    <?php if (empty($utes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <span class="fw-semibold">No hay UTEs creadas aún</span>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php $idx_ute = 0; foreach ($utes as $ute): ?>
                        <?php
                            // Avatar del equipo: iniciales + color según género de la categoría
                            $nombre_ute_txt   = trim($ute['nombre_ute'] ?? '');
                            $genero_ute       = strtolower(trim($ute['genero'] ?? ''));
                            $iniciales_ute    = '';
                            foreach (preg_split('/\s+/', $nombre_ute_txt) as $palabra_ute) {
                                if ($palabra_ute !== '' && mb_strlen($iniciales_ute) < 2) {
                                    $iniciales_ute .= mb_strtoupper(mb_substr($palabra_ute, 0, 1));
                                }
                            }
                            if ($iniciales_ute === '') $iniciales_ute = '?';
                            $par_ute = (++$idx_ute) % 2 === 0;
                            // Badge "Equipo" o "UTE" según disciplina
                            $es_equipos_deporte = in_array(strtolower(trim($ute['nombre_deporte'])), ['futbol', 'fútbol', 'basquet', 'basquetbol', 'basket', 'handball', 'voley', 'vóley']);
                        ?>
                        <tr class="js-fila-equipo<?= $par_ute ? ' fila-par' : '' ?>" id="ute-fila-<?= $ute['id_ute'] ?>">
                            <td>
                                <div class="fw-semibold text-dark text-uppercase small"><?= htmlspecialchars($ute['nombre_deporte']) ?></div>
                                <?php if (!empty($ute['genero'])): ?>
                                    <span class="badge rounded-pill border <?= $genero_ute === 'femenino' ? 'bg-danger-subtle text-danger' : ($genero_ute === 'masculino' ? 'bg-primary-subtle text-primary' : 'bg-light text-secondary') ?> js-badge-genero" style="font-size:0.65rem;">
                                        <i class="bi <?= $genero_ute === 'femenino' ? 'bi-gender-female' : ($genero_ute === 'masculino' ? 'bi-gender-male' : 'bi-gender-trans') ?> me-1"></i><?= htmlspecialchars($ute['genero']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1">
                                    <i class="bi bi-tag-fill me-1 text-muted" style="font-size:0.7rem;"></i><?= htmlspecialchars($ute['nombre_categoria']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="min-w-0">
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($ute['nombre_ute'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($ute['nombre_ute'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= $ute['cantidad_integrantes'] > 0 ? 'bg-success-subtle text-success border' : 'bg-danger-subtle text-danger border' ?> px-2 py-1 js-badge-integrantes">
                                    <i class="bi bi-people-fill me-1"></i><?= $ute['cantidad_integrantes'] ?>
                                </span>
                                <small class="text-muted ms-1">integrante<?= $ute['cantidad_integrantes'] == 1 ? '' : 's' ?></small>
                            </td>
                            <td>
                                <span class="text-muted small">
                                    <i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y', strtotime($ute['fecha_creacion'])) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center bg-light rounded-pill px-1 py-1 border">
                                    <button type="button" class="btn btn-sm btn-light text-info-emphasis border-0 rounded-circle js-btn-accion btn-ver-integrantes"
                                            data-id-ute="<?= $ute['id_ute'] ?>"
                                            title="Ver integrantes">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    <span class="vr mx-1 text-muted opacity-25"></span>
                                    <button type="button" class="btn btn-sm btn-light text-warning-emphasis border-0 rounded-circle js-btn-accion btn-agregar-integrante"
                                            data-id-ute="<?= $ute['id_ute'] ?>"
                                            data-id-categoria="<?= $ute['id_categoria'] ?>"
                                            title="Agregar integrante">
                                        <i class="bi bi-person-plus-fill"></i>
                                    </button>
                                    <span class="vr mx-1 text-muted opacity-25"></span>
                                    <button type="button" class="btn btn-sm btn-light text-primary border-0 rounded-circle js-btn-accion btn-editar-ute"
                                            data-id-ute="<?= $ute['id_ute'] ?>"
                                            data-nombre-ute="<?= htmlspecialchars($ute['nombre_ute'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-genero="<?= htmlspecialchars($ute['genero'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-deporte="<?= htmlspecialchars($ute['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-categoria="<?= htmlspecialchars($ute['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?>"
                                            title="Editar UTE (nombre y género)">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <span class="vr mx-1 text-muted opacity-25"></span>
                                    <button type="button" class="btn btn-sm btn-light text-danger border-0 rounded-circle js-btn-accion btn-eliminar-ute"
                                            data-id-ute="<?= $ute['id_ute'] ?>"
                                            data-nombre-ute="<?= htmlspecialchars($ute['nombre_ute'], ENT_QUOTES, 'UTF-8') ?>"
                                            title="Eliminar UTE">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <tr id="filaNoResultadosEquipos" style="display: none;">
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-exclamation-circle text-danger me-2"></i>No se encontraron coincidencias para tu búsqueda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php if(!empty($utes)): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2 border-top pt-3">
            <div class="text-muted small">
                Mostrando filas del <span id="pagStartEquipos">0</span> al <span id="pagEndEquipos">0</span>
            </div>
            <nav aria-label="Navegacion de equipos">
                <ul class="pagination pagination-sm mb-0 justify-content-center" id="ulPaginacionEquipos">
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Crear UTE -->
<div class="modal fade" id="modalCrearUTE" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Crear Nueva UTE/Equipo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-crear-ute">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deporte y Categoría</label>
                        <select class="form-select" id="select-categoria-ute" required>
                            <option value="">Seleccione una categoría...</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id_categoria'] ?>" 
                                    data-deporte="<?= htmlspecialchars($cat['nombre_deporte']) ?>"
                                    data-genero="<?= htmlspecialchars($cat['genero']) ?>">
                                <?= htmlspecialchars($cat['nombre_deporte']) ?> - <?= htmlspecialchars($cat['nombre_categoria']) ?> (<?= htmlspecialchars($cat['genero']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del Equipo/UTE</label>
                        <input type="text" class="form-control" id="nombre-ute-input" 
                               placeholder="Ej: Los Pampas FC" required maxlength="150">
                        <div class="form-text">El nombre debe ser único dentro de la categoría seleccionada.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Género de la UTE</label>
                        <select class="form-select" id="genero-ute-input">
                            <option value="MIXTO">MIXTO</option>
                            <option value="MASCULINO">MASCULINO</option>
                            <option value="FEMENINO">FEMENINO</option>
                        </select>
                        <div class="form-text">Podés ajustarlo ahora o más tarde con el botón "Editar UTE".</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-ute">
                    <i class="bi bi-check-circle me-1"></i> Crear UTE
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar UTE -->
<div class="modal fade" id="modalEditarUTE" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar UTE/Equipo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-editar-ute">
                    <input type="hidden" id="editar-id-ute">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deporte y Categoría</label>
                        <input type="text" class="form-control" id="editar-info-categoria" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del Equipo/UTE</label>
                        <input type="text" class="form-control" id="editar-nombre-ute" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Género de la UTE</label>
                        <select class="form-select" id="editar-genero-ute">
                            <option value="MIXTO">MIXTO</option>
                            <option value="MASCULINO">MASCULINO</option>
                            <option value="FEMENINO">FEMENINO</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-edicion-ute">
                    <i class="bi bi-check-circle me-1"></i> Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Integrantes -->
<div class="modal fade" id="modalVerIntegrantes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-people me-2"></i>Integrantes de <span id="nombre-ute-modal"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <div>
                        <strong id="info-categoria-modal"></strong>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>DNI</th>
                                <th>Delegación</th>
                                <th>Sexo</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="lista-integrantes">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Integrante -->
<div class="modal fade" id="modalAgregarIntegrante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Agregar Integrante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Seleccione un participante disponible para esta categoría:</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Participantes Disponibles</label>
                    <select class="form-select" id="select-participante-disponible" required>
                        <option value="">Cargando participantes...</option>
                    </select>
                    <input type="hidden" id="id-ute-agregar">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn-agregar-participante">
                    <i class="bi bi-check-circle me-1"></i> Agregar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar contador
    actualizarContadorEquipos();
    
    // ==========================================
    // CREAR UTE
    // ==========================================
    document.getElementById('btn-guardar-ute').addEventListener('click', function() {
        const idCategoria = document.getElementById('select-categoria-ute').value;
        const nombreUte = document.getElementById('nombre-ute-input').value.trim();
        const generoUte = document.getElementById('genero-ute-input').value;
        
        if (!idCategoria || !nombreUte) {
            alert('Complete todos los campos');
            return;
        }
        
        const formData = new FormData();
        formData.append('id_categoria', idCategoria);
        formData.append('nombre_ute', nombreUte);
        formData.append('genero', generoUte);
        
        fetch('<?= base_url("Inscripciones/ajax_crear_ute") ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar modal
                const modalCrear = bootstrap.Modal.getInstance(document.getElementById('modalCrearUTE'));
                if (modalCrear) modalCrear.hide();
                
                // Obtener información de la categoría seleccionada
                const selectCategoria = document.getElementById('select-categoria-ute');
                const optionSeleccionada = selectCategoria.options[selectCategoria.selectedIndex];
                const nombreDeporte = optionSeleccionada.getAttribute('data-deporte');
                const nombreCategoria = optionSeleccionada.text.split(' - ')[1].split(' (')[0];
                // Se usa el género elegido para la UTE (no el de la categoría)
                const genero = generoUte;
                
                // Crear nueva fila en la tabla
                const tbody = document.getElementById('cuerpo-tabla-utes');
                const fechaActual = new Date().toLocaleDateString('es-AR');
                
                const generoLower = (genero || '').toLowerCase();
                const claseBadgeGenero = generoLower === 'femenino' ? 'bg-danger-subtle text-danger' : (generoLower === 'masculino' ? 'bg-primary-subtle text-primary' : 'bg-light text-secondary');
                const iconoGenero = generoLower === 'femenino' ? 'bi-gender-female' : (generoLower === 'masculino' ? 'bi-gender-male' : 'bi-gender-trans');

                // Iniciales del nombre del equipo para el avatar
                const inicialesUte = nombreUte.split(/\s+/).filter(w => w !== '').slice(0, 2).map(w => w.charAt(0).toUpperCase()).join('') || '?';
                const escHtml = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

                const totalFilas = document.querySelectorAll('#cuerpo-tabla-utes tr.js-fila-equipo').length;
                const esPar = (totalFilas + 1) % 2 === 0;

                const nuevaFila = document.createElement('tr');
                nuevaFila.id = 'ute-fila-' + data.id_ute;
                nuevaFila.className = 'js-fila-equipo' + (esPar ? ' fila-par' : '');
                nuevaFila.innerHTML = `
                    <td>
                        <div class="fw-semibold text-dark text-uppercase small">${escHtml(nombreDeporte)}</div>
                        <span class="badge rounded-pill border ${claseBadgeGenero} js-badge-genero" style="font-size:0.65rem;">
                            <i class="bi ${iconoGenero} me-1"></i>${escHtml(genero)}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1">
                            <i class="bi bi-tag-fill me-1 text-muted" style="font-size:0.7rem;"></i>${escHtml(nombreCategoria)}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="min-w-0">
                                <div class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="${escHtml(nombreUte)}">${escHtml(nombreUte)}</div>
                                <small class="text-muted"><i class="bi bi-people me-1"></i>UTE</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill bg-danger-subtle text-danger border px-2 py-1 js-badge-integrantes">
                            <i class="bi bi-people-fill me-1"></i>0
                        </span>
                        <small class="text-muted ms-1">integrantes</small>
                    </td>
                    <td>
                        <span class="text-muted small">
                            <i class="bi bi-calendar-event me-1"></i>${fechaActual}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center bg-light rounded-pill px-1 py-1 border">
                            <button type="button" class="btn btn-sm btn-light text-info-emphasis border-0 rounded-circle js-btn-accion btn-ver-integrantes"
                                    data-id-ute="${data.id_ute}"
                                    title="Ver integrantes">
                                <i class="bi bi-search"></i>
                            </button>
                            <span class="vr mx-1 text-muted opacity-25"></span>
                            <button type="button" class="btn btn-sm btn-light text-warning-emphasis border-0 rounded-circle js-btn-accion btn-agregar-integrante"
                                    data-id-ute="${data.id_ute}"
                                    data-id-categoria="${idCategoria}"
                                    title="Agregar integrante">
                                <i class="bi bi-person-plus-fill"></i>
                            </button>
                            <span class="vr mx-1 text-muted opacity-25"></span>
                            <button type="button" class="btn btn-sm btn-light text-primary border-0 rounded-circle js-btn-accion btn-editar-ute"
                                    data-id-ute="${data.id_ute}"
                                    data-nombre-ute="${escHtml(nombreUte)}"
                                    data-genero="${escHtml(genero)}"
                                    data-deporte="${escHtml(nombreDeporte)}"
                                    data-categoria="${escHtml(nombreCategoria)}"
                                    title="Editar UTE (nombre y género)">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <span class="vr mx-1 text-muted opacity-25"></span>
                            <button type="button" class="btn btn-sm btn-light text-danger border-0 rounded-circle js-btn-accion btn-eliminar-ute"
                                    data-id-ute="${data.id_ute}"
                                    data-nombre-ute="${escHtml(nombreUte)}"
                                    title="Eliminar UTE">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        </div>
                    </td>
                `;
                
                // Si hay mensaje de "no hay UTEs", removerlo
                const mensajeVacio = tbody.querySelector('td[colspan="6"]');
                if (mensajeVacio) {
                    mensajeVacio.parentElement.remove();
                }
                
                // Agregar fila con animación
                nuevaFila.style.opacity = '0';
                tbody.appendChild(nuevaFila);
                setTimeout(() => {
                    nuevaFila.style.transition = 'opacity 0.5s';
                    nuevaFila.style.opacity = '1';
                }, 50);
                
                // Actualizar contador
                actualizarContadorEquipos();
                
                // Limpiar formulario
                document.getElementById('form-crear-ute').reset();
                
                // Mantenerse en la pestaña de UTEs (no hacer refresh)
                // La página ya se mantiene en la misma pestaña por defecto
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al crear la UTE');
        });
    });
    
    // ==========================================
    // VER INTEGRANTES
    // ==========================================
    document.querySelectorAll('.btn-ver-integrantes').forEach(btn => {
        btn.addEventListener('click', function() {
            const idUte = this.getAttribute('data-id-ute');
            
            fetch('<?= base_url("Inscripciones/ajax_detalle_ute/") ?>' + idUte)
            .then(response => response.json())
            .then(ute => {
                if (ute && ute.nombre_ute) {
                    document.getElementById('nombre-ute-modal').textContent = ute.nombre_ute;
                    const generoUteDetalle = (ute.genero || ute.genero_categoria || '').toUpperCase();
                    document.getElementById('info-categoria-modal').textContent = 
                        ute.nombre_deporte + ' - ' + ute.nombre_categoria + ' (' + generoUteDetalle + ')';
                    
                    const tbody = document.getElementById('lista-integrantes');
                    tbody.innerHTML = '';
                    
                    if (ute.integrantes && ute.integrantes.length > 0) {
                        ute.integrantes.forEach(int => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="fw-semibold">${int.nombre_completo}</td>
                                <td>${int.dni}</td>
                                <td>${int.delegacion}</td>
                                <td>${int.sexo}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger btn-quitar-integrante" 
                                            data-id-ute="${idUte}" 
                                            data-id-participante="${int.id_participante}"
                                            title="Quitar de la UTE">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Sin integrantes</td></tr>';
                    }
                    
                    new bootstrap.Modal(document.getElementById('modalVerIntegrantes')).show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los integrantes');
            });
        });
    });
    
    // ==========================================
    // QUITAR INTEGRANTE
    // ==========================================
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-quitar-integrante')) {
            const btn = e.target.closest('.btn-quitar-integrante');
            const idUte = btn.getAttribute('data-id-ute');
            const idParticipante = btn.getAttribute('data-id-participante');
            
            if (confirm('¿Está seguro de quitar este participante de la UTE?')) {
                const formData = new FormData();
                formData.append('id_ute', idUte);
                formData.append('id_participante', idParticipante);
                
                fetch('<?= base_url("Inscripciones/ajax_eliminar_participante_ute") ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar modal o fila
                        btn.closest('tr').remove();
                        actualizarContadorEquipos();
                        
                        // Actualizar contador en la tabla principal si estamos viendo el modal
                        const filaUte = document.getElementById('ute-fila-' + idUte);
                        if (filaUte) {
                            const badge = filaUte.querySelector('.js-badge-integrantes');
                            if (badge) {
                                const cantidadActual = parseInt(badge.textContent) || 0;
                                const nuevaCantidad = Math.max(0, cantidadActual - 1);
                                badge.innerHTML = '<i class="bi bi-people-fill me-1"></i>' + nuevaCantidad;
                                badge.classList.remove('bg-success-subtle', 'text-success', 'bg-danger-subtle', 'text-danger');
                                badge.classList.add(nuevaCantidad > 0 ? 'bg-success-subtle' : 'bg-danger-subtle', nuevaCantidad > 0 ? 'text-success' : 'text-danger');
                            }
                        }
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al quitar el participante');
                });
            }
        }
    });
    
    // ==========================================
    // AGREGAR INTEGRANTE
    // ==========================================
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-agregar-integrante')) {
            const btn = e.target.closest('.btn-agregar-integrante');
            const idUte = btn.getAttribute('data-id-ute');
            const idCategoria = btn.getAttribute('data-id-categoria');
            
            document.getElementById('id-ute-agregar').value = idUte;
            
            // Cargar participantes disponibles
            const select = document.getElementById('select-participante-disponible');
            select.innerHTML = '<option value="">Cargando...</option>';
            
            fetch('<?= base_url("Inscripciones/ajax_participantes_disponibles/") ?>' + idCategoria)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(participantes => {
                select.innerHTML = '<option value="">Seleccione un participante...</option>';
                
                if (participantes && participantes.length > 0) {
                    participantes.forEach(p => {
                        const option = document.createElement('option');
                        option.value = p.id_participante;
                        option.textContent = `${p.nombre_completo} (${p.dni}) - ${p.delegacion}`;
                        select.appendChild(option);
                    });
                } else {
                    select.innerHTML = '<option value="" disabled>No hay participantes disponibles</option>';
                }
                
                new bootstrap.Modal(document.getElementById('modalAgregarIntegrante')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                select.innerHTML = '<option value="" disabled>Error al cargar</option>';
            });
        }
    });
    
    document.getElementById('btn-agregar-participante').addEventListener('click', function() {
        const idUte = document.getElementById('id-ute-agregar').value;
        const idParticipante = document.getElementById('select-participante-disponible').value;
        
        if (!idParticipante) {
            alert('Seleccione un participante');
            return;
        }
        
        const formData = new FormData();
        formData.append('id_ute', idUte);
        formData.append('id_participante', idParticipante);
        
        fetch('<?= base_url("Inscripciones/ajax_agregar_participante_ute") ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar modal
                const modalAgregar = bootstrap.Modal.getInstance(document.getElementById('modalAgregarIntegrante'));
                if (modalAgregar) modalAgregar.hide();
                
                // Actualizar el contador de integrantes en la fila correspondiente
                const filaUte = document.getElementById('ute-fila-' + idUte);
                if (filaUte) {
                    const badge = filaUte.querySelector('.js-badge-integrantes');
                    if (badge) {
                        const cantidadActual = parseInt(badge.textContent) || 0;
                        const nuevaCantidad = cantidadActual + 1;
                        badge.innerHTML = '<i class="bi bi-people-fill me-1"></i>' + nuevaCantidad;
                        badge.classList.remove('bg-success-subtle', 'text-success', 'bg-danger-subtle', 'text-danger');
                        badge.classList.add(nuevaCantidad > 0 ? 'bg-success-subtle' : 'bg-danger-subtle', nuevaCantidad > 0 ? 'text-success' : 'text-danger');
                    }
                }
                
                // Limpiar formulario
                document.getElementById('select-participante-disponible').innerHTML = '<option value="">Cargando participantes...</option>';
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al agregar el participante');
        });
    });
    
    // ==========================================
    // EDITAR UTE (nombre y/o género)
    // ==========================================
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-editar-ute');
        if (!btn) return;

        document.getElementById('editar-id-ute').value = btn.getAttribute('data-id-ute');
        document.getElementById('editar-nombre-ute').value = btn.getAttribute('data-nombre-ute') || '';
        document.getElementById('editar-info-categoria').value =
            (btn.getAttribute('data-deporte') || '') + ' - ' + (btn.getAttribute('data-categoria') || '');

        const generoActual = (btn.getAttribute('data-genero') || 'MIXTO').toUpperCase();
        const selectGenero = document.getElementById('editar-genero-ute');
        if (['FEMENINO', 'MASCULINO', 'MIXTO'].includes(generoActual)) {
            selectGenero.value = generoActual;
        } else {
            selectGenero.value = 'MIXTO';
        }

        new bootstrap.Modal(document.getElementById('modalEditarUTE')).show();
    });

    document.getElementById('btn-guardar-edicion-ute').addEventListener('click', function() {
        const idUte = document.getElementById('editar-id-ute').value;
        const nombreNuevo = document.getElementById('editar-nombre-ute').value.trim();
        const generoNuevo = document.getElementById('editar-genero-ute').value;

        if (!idUte || !nombreNuevo) {
            alert('Complete el nombre de la UTE');
            return;
        }

        const formData = new FormData();
        formData.append('id_ute', idUte);
        formData.append('nombre_ute', nombreNuevo);
        formData.append('genero', generoNuevo);

        fetch('<?= base_url("Inscripciones/ajax_actualizar_ute") ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar modal
                const modalEditar = bootstrap.Modal.getInstance(document.getElementById('modalEditarUTE'));
                if (modalEditar) modalEditar.hide();

                // Actualizar la fila en la tabla sin recargar la página
                const fila = document.getElementById('ute-fila-' + idUte);
                if (fila) {
                    const escHtml2 = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    const generoLower = generoNuevo.toLowerCase();
                    const claseBadge = generoLower === 'femenino' ? 'bg-danger-subtle text-danger' : (generoLower === 'masculino' ? 'bg-primary-subtle text-primary' : 'bg-light text-secondary');
                    const icono = generoLower === 'femenino' ? 'bi-gender-female' : (generoLower === 'masculino' ? 'bi-gender-male' : 'bi-gender-trans');
                    const color = generoLower === 'femenino' ? '#e83e8c' : (generoLower === 'masculino' ? '#2a5298' : '#6c757d');

                    // Badge de género (1er td)
                    const badgeGenero = fila.querySelector('.js-badge-genero');
                    if (badgeGenero) {
                        badgeGenero.className = 'badge rounded-pill border ' + claseBadge + ' js-badge-genero';
                        badgeGenero.style.fontSize = '0.65rem';
                        badgeGenero.innerHTML = '<i class="bi ' + icono + ' me-1"></i>' + escHtml2(generoNuevo);
                    }

                    // Nombre (2do td: div.fw-bold) + avatar con iniciales/color
                    const celdaNombre = fila.querySelector('td:nth-child(3)');
                    if (celdaNombre) {
                        
                        const divNombre = celdaNombre.querySelector('.fw-bold');
                        if (divNombre) {
                            divNombre.textContent = nombreNuevo;
                            divNombre.setAttribute('title', nombreNuevo);
                        }
                    }

                    // Actualizar los data-* del botón editar para la próxima apertura
                    const btnEditar = fila.querySelector('.btn-editar-ute');
                    if (btnEditar) {
                        btnEditar.setAttribute('data-nombre-ute', nombreNuevo);
                        btnEditar.setAttribute('data-genero', generoNuevo);
                    }
                }

                // Reaplicar filtros/búsqueda por si el cambio afecta lo visible
                aplicarFiltrosEquipos();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar la UTE');
        });
    });

    // ==========================================
    // ELIMINAR UTE
    // ==========================================
    document.querySelectorAll('.btn-eliminar-ute').forEach(btn => {
        btn.addEventListener('click', function() {
            const idUte = this.getAttribute('data-id-ute');
            const nombreUte = this.getAttribute('data-nombre-ute');
            
            if (confirm(`¿Está SEGURO que desea eliminar la UTE "${nombreUte}"?\n\nEsta acción eliminará también todos sus integrantes.`)) {
                const formData = new FormData();
                formData.append('id_ute', idUte);
                
                fetch('<?= base_url("Inscripciones/ajax_eliminar_ute") ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const fila = document.getElementById('ute-fila-' + idUte);
                        if (fila) {
                            fila.style.transition = 'opacity 0.3s';
                            fila.style.opacity = '0';
                            setTimeout(() => {
                                fila.remove();
                                actualizarContadorEquipos();
                                aplicarFiltrosEquipos();
                            }, 300);
                        }
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar la UTE');
                });
            }
        });
    });
    
    function actualizarContadorEquipos() {
        const cantidadFilas = document.querySelectorAll('#cuerpo-tabla-utes tr[id^="ute-fila-"]').length;
        document.getElementById('contador-equipos').textContent = cantidadFilas + ' filas';
    }
    
    // ==========================================
    // FILTROS Y BUSQUEDA DE EQUIPOS/UTES
    // ==========================================
    const inputBuscarEquipo = document.getElementById('inputBuscarEquipo');
    const filtroDeporteEquipo = document.getElementById('filtroDeporteEquipo');
    const filtroCategoriaEquipo = document.getElementById('filtroCategoriaEquipo');
    const btnLimpiarFiltrosEquipos = document.getElementById('btnLimpiarFiltrosEquipos');
    const filasEquipos = Array.from(document.querySelectorAll('.js-fila-equipo'));
    const filaNoResultadosEquipos = document.getElementById('filaNoResultadosEquipos');
    const contadorEquipos = document.getElementById('contador-equipos');
    
    const filasPorPaginaEquipos = 10;
    let paginaActualEquipos = 1;
    let filasFiltradasEquipos = [...filasEquipos];

    function actualizarTablaEquipos() {
        const totalFilasEquipos = filasFiltradasEquipos.length;
        const totalPaginasEquipos = Math.ceil(totalFilasEquipos / filasPorPaginaEquipos) || 1;

        if (paginaActualEquipos > totalPaginasEquipos) paginaActualEquipos = totalPaginasEquipos;
        if (paginaActualEquipos < 1) paginaActualEquipos = 1;

        const inicioEquipos = (paginaActualEquipos - 1) * filasPorPaginaEquipos;
        const finEquipos = inicioEquipos + filasPorPaginaEquipos;

        filasEquipos.forEach(f => f.style.display = 'none');

        filasFiltradasEquipos.forEach((fila, index) => {
            if (index >= inicioEquipos && index < finEquipos) {
                fila.style.display = '';
            }
        });

        const pStartEquipos = document.getElementById('pagStartEquipos');
        const pEndEquipos = document.getElementById('pagEndEquipos');
        if(pStartEquipos && pEndEquipos) {
            pStartEquipos.innerText = totalFilasEquipos === 0 ? 0 : inicioEquipos + 1;
            pEndEquipos.innerText = finEquipos > totalFilasEquipos ? totalFilasEquipos : finEquipos;
        }

        const ulPaginacionEquipos = document.getElementById('ulPaginacionEquipos');
        if (ulPaginacionEquipos) {
            ulPaginacionEquipos.innerHTML = '';

            if (totalPaginasEquipos > 1) {
                ulPaginacionEquipos.innerHTML += `
                    <li class="page-item ${paginaActualEquipos === 1 ? 'disabled' : ''}">
                        <button class="page-link" data-page-equipos="${paginaActualEquipos - 1}">&laquo;</button>
                    </li>`;

                for (let i = 1; i <= totalPaginasEquipos; i++) {
                    ulPaginacionEquipos.innerHTML += `
                        <li class="page-item ${paginaActualEquipos === i ? 'active' : ''}">
                            <button class="page-link" data-page-equipos="${i}">${i}</button>
                        </li>`;
                }

                ulPaginacionEquipos.innerHTML += `
                    <li class="page-item ${paginaActualEquipos === totalPaginasEquipos ? 'disabled' : ''}">
                        <button class="page-link" data-page-equipos="${paginaActualEquipos + 1}">&raquo;</button>
                    </li>`;

                ulPaginacionEquipos.querySelectorAll('button').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const nuevaPagEquipos = parseInt(this.getAttribute('data-page-equipos'));
                        if (nuevaPagEquipos >= 1 && nuevaPagEquipos <= totalPaginasEquipos) {
                            paginaActualEquipos = nuevaPagEquipos;
                            actualizarTablaEquipos();
                        }
                    });
                });
            }
        }

        if (totalFilasEquipos === 0 && filasEquipos.length > 0) {
            filaNoResultadosEquipos.style.display = '';
        } else {
            filaNoResultadosEquipos.style.display = 'none';
        }

        if (contadorEquipos) {
            contadorEquipos.innerText = `${totalFilasEquipos} ${totalFilasEquipos === 1 ? 'fila' : 'filas'}`;
        }
    }

    function aplicarFiltrosEquipos() {
        const terminoBusqueda = inputBuscarEquipo.value.toLowerCase().trim();
        const deporteSeleccionado = filtroDeporteEquipo.value.toLowerCase();
        const categoriaSeleccionada = filtroCategoriaEquipo.value.toLowerCase();

        filasFiltradasEquipos = filasEquipos.filter(fila => {
            // Obtener datos de la fila
            const textoFila = fila.textContent.toLowerCase();
            
            const badgeDeporte = fila.querySelector('td:nth-child(1) .fw-semibold');
            const deporteFila = badgeDeporte ? badgeDeporte.textContent.toLowerCase() : '';
            
            const celdaCategoria = fila.querySelector('td:nth-child(2)');
            const categoriaFila = celdaCategoria ? celdaCategoria.textContent.toLowerCase() : '';

            // Aplicar filtros
            let coincide = true;
            
            // Filtro por búsqueda de texto
            if (terminoBusqueda && !textoFila.includes(terminoBusqueda)) {
                coincide = false;
            }
            
            // Filtro por deporte
            if (deporteSeleccionado && (deporteFila!=deporteSeleccionado)) {
                coincide = false;
            }
            
            // Filtro por categoría
            if (categoriaSeleccionada && !categoriaFila.includes(categoriaSeleccionada)) {
                coincide = false;
            }

            return coincide;
        });

        paginaActualEquipos = 1;
        actualizarTablaEquipos();
    }

    // ----------------------------------------------------
    // Al elegir un deporte, mostrar solo sus categorías
    // (si no hay deporte seleccionado, se muestran todas)
    // ----------------------------------------------------
    function actualizarCategoriasPorDeporte() {
        if (!filtroCategoriaEquipo) return;

        const deporteSeleccionado = filtroDeporteEquipo ? filtroDeporteEquipo.value : '';
        const categoriaActual = filtroCategoriaEquipo.value;
        let categoriaSigueVisible = false;

        Array.from(filtroCategoriaEquipo.options).forEach(option => {
            const deporteDeLaOpcion = option.getAttribute('data-deporte') || '';

            // La opción "Todas las Categorías" siempre queda disponible
            if (!deporteDeLaOpcion) {
                option.hidden = false;
                return;
            }

            const visible = !deporteSeleccionado || deporteDeLaOpcion === deporteSeleccionado;
            option.hidden = !visible;

            if (visible && option.value === categoriaActual) {
                categoriaSigueVisible = true;
            }
        });

        // Si la categoría ya no corresponde al deporte elegido, se resetea
        if (!categoriaSigueVisible) {
            filtroCategoriaEquipo.value = '';
        }
    }

    // Event listeners para los filtros
    if (inputBuscarEquipo) {
        inputBuscarEquipo.addEventListener('input', aplicarFiltrosEquipos);
    }
    if (filtroDeporteEquipo) {
        filtroDeporteEquipo.addEventListener('change', function() {
            actualizarCategoriasPorDeporte();
            aplicarFiltrosEquipos();
        });
    }
    if (filtroCategoriaEquipo) {
        filtroCategoriaEquipo.addEventListener('change', aplicarFiltrosEquipos);
    }
    if (btnLimpiarFiltrosEquipos) {
        btnLimpiarFiltrosEquipos.addEventListener('click', function() {
            inputBuscarEquipo.value = '';
            filtroDeporteEquipo.value = '';
            filtroCategoriaEquipo.value = '';
            actualizarCategoriasPorDeporte();
            aplicarFiltrosEquipos();
        });
    }

    // Dejar las categorías filtradas segun el estado inicial del select de deporte
    actualizarCategoriasPorDeporte();
    actualizarTablaEquipos();
});
</script>
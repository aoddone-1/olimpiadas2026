<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utes/Equipos - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- SweetAlert2: confirmaciones estilizadas (con fallback a native si no carga) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>
<?php
// ============================================
// MODO SOLO LECTURA: si el rol del usuario es 'solo_lectura',
// toda la pantalla de UTEs/Equipos queda de solo lectura (sin
// crear, modificar ni eliminar). Con 'superadmin' funciona igual que siempre.
// ============================================
$solo_lectura = ($this->session->userdata('user_rol') === 'solo_lectura');
?>
<?php if ($solo_lectura): ?>
<div class="alert alert-info mb-3 border-0 shadow-sm rounded-pill py-2 small fw-semibold text-center" role="alert">
    <i class="bi bi-eye-fill me-1"></i> Estás en modo SOLO LECTURA: podés ver y buscar las UTEs/Equipos y sus integrantes, pero no crearlos, modificarlos ni eliminarlos.
</div>
<?php endif; ?>
<div class="card shadow-sm border-0 mb-4 card-inscriptos">
    <div class="card-header fx-card-header fw-bold text-secondary d-flex flex-column gap-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <!-- Título con ícono suave en lugar de texto plano -->
            <div class="d-flex align-items-center gap-2">
                <span class="fx-header-icon d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-list-check text-warning me-2"></i>
                </span>
                <span class="fx-header-titulo">Gestion de UTE/Equipos <u><?= LUGAR_OLIMPICO2 ?></u></span>
                <span class="badge bg-secondary ms-2" id="contador-equipos">0 filas</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if (!$solo_lectura): ?>
                <button class="btn btn-lg btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUTE">
                    <i class="bi bi-plus-circle me-1"></i>
                </button>
                <?php endif; ?>
                <!-- IMPORTANTE: el ícono va con pointer-events:none. Si no, al hacer
                     click sobre él (o muy cerca del borde) el foco NO cae en el input
                     y el cursor titilante desaparece enseguida, imposibilitando escribir. -->
                <div class="position-relative" style="max-width: 300px; width: 100%;">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="pointer-events:none;"></i>
                    <input type="text" 
                           id="inputBuscarEquipo" 
                           class="form-control form-control-sm rounded-pill ps-5 bg-light" 
                           placeholder="Buscar por Nombre UTE, Deporte..."
                           autocomplete="off">
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <div class="fx-filtros row g-2 align-items-end mb-3" id="panelFiltrosEquipos">
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
                 <button id="btnLimpiarFiltrosEquipos" class="btn fx-btn-round fx-btn-limpiar" title="Quitar los filtros activos">
                    <i class="bi bi-x-circle"></i><span class="ms-2">Limpiar filtros</span>
                </button>
            </div>
        </div>
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
                                    <?php if (!$solo_lectura): ?>
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
                                    <?php endif; ?>
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

<?php if (!$solo_lectura): ?>
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

<?php endif; ?>

<?php if (!$solo_lectura): ?>
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

<?php endif; ?>

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
                                <?php if (!$solo_lectura): ?>
                                <th class="text-end">Acción</th>
                                <?php endif; ?>
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

<?php if (!$solo_lectura): ?>
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
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    /* ---------- Confirmación estilizada (SweetAlert2 si está cargado) ---------- */
    function confirmarAccion(texto, icono, btnOk, btnCancelar) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                title: '¿Confirmás?',
                text: texto,
                icon: icono || 'question',
                showCancelButton: true,
                confirmButtonText: btnOk || 'Sí, confirmar',
                cancelButtonText: btnCancelar || 'Cancelar',
                reverseButtons: true
            }).then(r => r.isConfirmed);
        }
        return Promise.resolve(window.confirm(texto));
    }

    /* ---------- Alertas estilizadas (SweetAlert2 con fallback a native) ---------- */
    function alertar(titulo, texto, icono) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                title: titulo,
                text: texto,
                icon: icono || 'info',
                confirmButtonText: 'Entendido'
            });
        }
        return Promise.resolve(window.alert(titulo + '\n\n' + texto));
    }

    /* ---------- Parseo defensivo de respuestas JSON ----------
       Si el servidor (o el firewall/WAF del hosting) responde con una pagina
       HTML en vez de JSON (403 Forbidden, sesion caida con login, error 500),
       response.json() rompia con "Unexpected token '<'" y el usuario veia un
       error incomprensible. Esta funcion lee el texto primero, detecta esos
       casos y devuelve un JSON de error amigable con el status HTTP real. */
    async function parseJsonResponse(response) {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            // No era JSON: el servidor/firewall devolvio HTML. Registramos en
            // consola el cuerpo COMPLETO (no solo un fragmento) para poder ver
            // quien corto la peticion: ModSecurity suele escribir en la propia
            // pagina "web application firewall blocked the request" o similar,
            // lo cual permite identificar la regla exacta que bloquea.
            console.error('[equipos][AJAX] HTTP ' + response.status
                + ' | URL: ' + response.url
                + ' | Body completo:\n' + text);

            const esPaginaWaf = /firewall|mod_?security|sucuri|blocked the request|abuse detection/i.test(text);
            const err = new Error('La respuesta del servidor no es valida (HTTP ' + response.status + ')');
            err.httpStatus = response.status;
            if (esPaginaWaf) {
                err.message = 'El firewall del hosting bloqueó esta petición ('
                    + response.status + '). El body completo quedó en la consola (F12) para pasarlo al proveedor del hosting.';
            } else if (response.status === 403 || /Forbidden/i.test(text)) {
                err.message = 'El servidor bloqueó la operación (' + response.status + '). Puede ser tu rol de solo lectura, la sesión expirada o el firewall del hosting.';
            } else if (response.status === 401) {
                err.message = 'Tu sesión expiró. Recargá la página e ingresá nuevamente.';
            } else if (/^\s*<(!doctype|html)/i.test(text)) {
                // Pagina de login por sesion caida -> tratar como sesion caída
                if (/login|password|contraseña|contrasena/i.test(text) && !/modalCrearUTE|cuerpo-tabla-utes/i.test(text)) {
                    err.message = 'Tu sesión expiró. Recargá la página e ingresá nuevamente.';
                } else {
                    err.message = 'El servidor devolvió una página de error en vez de datos (HTTP ' + response.status + '). Revisá si tu sesión sigue activa o contactá al administrador.';
                }
            }
            throw err;
        }
    }

    /* ---------- Base del controlador (mismo patrón que fixture/premiacion/resultados) ---------- */
    const BASE_UTE = '<?= base_url("Inscripciones") ?>';

    /* ---------- Parseo defensivo de listas JSON (participantes disponibles) ----------
       El endpoint devuelve un ARRAY de participantes en el éxito, pero un OBJETO
       {success:false,...} cuando la sesión cayó. Esta función normaliza ambos
       casos para no confundir un error con "lista vacía". */
    function parsearListaJson(data) {
        if (Array.isArray(data)) return { ok: true, lista: data };
        if (data && typeof data === 'object') {
            if (data.success === false) return { ok: false, error: data.error || 'Error en el servidor', sesion_caida: !!data.sesion_caida };
            // Objeto sin success:false y sin claves de lista -> lo tomamos como array vacío
            return { ok: true, lista: [] };
        }
        return { ok: true, lista: [] };
    }

    /* ---------- POST AJAX estilo gestión_deportes.php ----------
       En Gestión Deportiva TODAS las modificaciones se hacen con <form
       method="POST"> NATIVOS (action="Inscripciones/guardar_deporte", etc.):
       el navegador envía la petición como application/x-www-form-urlencoded,
       sin fetch(), sin cabeceras extra (ni Accept: application/json, ni
       X-Requested-With) y sin query "cache-buster" en la URL. Ese es justo el
       tipo de petición que los firewalls/WAF de algunos hostings (LiteSpeed +
       ModSecurity) NO bloquean, mientras que el fetch() con headers de API
       les parece sospechoso y cortaba todo con 403 HTML.
       Para replicar ese comportamiento 100% sin perder la experiencia de
       "no recargar la página", cada acción crea un <form> oculto REAL con el
       mismo action/method que usa gestión_deportes y lo envía a un IFRAME
       invisible: la petición saliente es idéntica a la de un submit nativo
       (urlencoded, sin headers custom). La respuesta JSON del servidor se
       lee desde el iframe para seguir actualizando la tabla en vivo; si el
       iframe devolviera HTML (firewall/sesión), se muestra el error amigable. */
    function postAjaxUte(url, formData) {
        return enviarFormularioNativoAiframe(url, formData, 'POST');
    }

    /* ---------- GET AJAX estilo gestión_deportes.php ----------
       Misma idea: submit nativo por iframe (esta vez con método GET, como
       cuando se navega un enlace normal). Así las lecturas (detalle de UTE,
       participantes disponibles) tampoco llevan cabeceras de API. */
    function getAjaxUte(url) {
        return enviarFormularioNativoAiframe(url, null, 'GET');
    }

    function enviarFormularioNativoAiframe(url, formData, metodo) {
        return new Promise(function (resolver, rejector) {
            // Nombre único por petición para poder leer el iframe correcto
            var nombreTarget = 'ute_frame_' + Date.now() + '_' + Math.floor(Math.random() * 100000);

            // 1) Iframe invisible que recibe la respuesta (sin fetch ni headers)
            var iframe = document.createElement('iframe');
            iframe.name = nombreTarget;
            iframe.style.display = 'none';
            document.body.appendChild(iframe);

            // 2) Formulario real, igual a los de gestión_deportes.php
            var form = document.createElement('form');
            form.method = metodo;                 // 'POST' o 'GET', urlencoded nativo
            form.action = url;                    // sin ?_=timestamp (eso era parte del patrón "API" que el WAF cortaba)
            form.target = nombreTarget;
            form.acceptCharset = 'UTF-8';
            form.style.display = 'none';

            if (formData) {
                formData.forEach(function (valor, clave) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = clave;
                    input.value = valor;
                    form.appendChild(input);
                });
            }

            document.body.appendChild(form);

            // 3) Lectura de la respuesta desde el iframe
            var resuelto = false;
            function limpiar() {
                setTimeout(function () {
                    if (form.parentNode) form.parentNode.removeChild(form);
                    if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
                }, 1500);
            }
            function interpretarRespuesta() {
                if (resuelto) return;
                resuelto = true;
                var texto = '';
                try {
                    var doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
                    if (doc && doc.body) {
                        texto = doc.body.innerHTML;
                        // Si el servidor devolvió JSON plano, algunos navegadores
                        // lo muestran dentro de <pre>; sacamos el texto crudo.
                        var pre = doc.querySelector('pre');
                        if (pre && pre.textContent) texto = pre.textContent;
                    }
                } catch (err) {
                    texto = '';
                }
                limpiar();
                try {
                    resolver(JSON.parse(texto));
                } catch (e) {
                    console.error('[equipos][AJAX] La respuesta no es JSON. Body recibido:\n' + texto);
                    var errFinal = new Error('El servidor bloqueó la operación (403). Puede ser tu rol de solo lectura, la sesión expirada o el firewall del hosting.');
                    if (/login|password|contraseña|contrasena/i.test(texto) && /^\s*</.test(texto)) {
                        errFinal.message = 'Tu sesión expiró. Recargá la página e ingresá nuevamente.';
                    } else if (/firewall|mod_?security|sucuri|blocked the request|abuse detection/i.test(texto)) {
                        errFinal.message = 'El firewall del hosting bloqueó esta petición (403). El body completo quedó en la consola (F12) para pasarlo al proveedor del hosting.';
                    }
                    rejector(errFinal);
                }
            }
            // onload cubre todos los navegadores; readystatechange ayuda en IE/Edge viejo
            iframe.addEventListener('load', interpretarRespuesta);
            iframe.addEventListener('readystatechange', function () {
                if (iframe.readyState === 'complete') interpretarRespuesta();
            });
            // Red de seguridad: si el hosting tarda muchísimo, no dejar el botón colgado
            setTimeout(function () {
                if (!resuelto) {
                    resuelto = true;
                    limpiar();
                    rejector(new Error('El servidor tardó demasiado en responder. Recargá la página e intentá de nuevo.'));
                }
            }, 30000);

            // 4) Submit NATIVO del formulario (idéntico a gestión_deportes.php)
            form.submit();
        });
    }

    /* ---------- Limpieza anti-"pantalla gris bloqueada" ----------
       Al encadenar Bootstrap modal + SweetAlert2 pueden quedar rezagados
       el backdrop de Bootstrap (.modal-backdrop), el overlay de Swal o las
       clases modal-open / swal2-shown en <body>, dejando la página "gris"
       e inservible tras cerrar todo. Se limpia luego de cada alerta/confirm.

       REGLA DE ORO: nunca limpiar mientras el usuario está INTERACTUANDO con
       la página (escribiendo, seleccionando texto, usando un <select>, etc.).
       Las versiones anteriores barrían los overlays en CADA mouseup/keydown
       (400ms después), y eso provocaba los 3 bugs reportados:
         - el blur() final le robaba el foco al buscador (el cursor titilante
           aparecía y desaparecía solo, imposibilitando escribir);
         - remover backdrops huérfanos durante una selección cerraba de golpe
           el desplegable nativo de los filtros <select>;
         - el barrido intermitente alteraba el layout/re-pintado y rompía la
           paginación en algunos roles.
       Ahora la limpieza corre SOLO cuando la página está inactiva: tras
       cerrar un modal/alerta explícitamente, o diferida unos segundos después
       del último evento y únicamente si no hay ningún elemento interactivo
       enfocado ni texto seleccionado. */
    var ultimoEventoInteraccion = 0;   // timestamp del último click/tecla
    var timerBarridoInactivo = null;   // timeout del barrido diferido

    function hayInteraccionActiva() {
        var activo = document.activeElement;
        if (activo && ['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON'].indexOf(activo.tagName) !== -1) {
            return true; // el usuario está usando un control: NO tocar nada
        }
        var sel = window.getSelection ? window.getSelection() : null;
        if (sel && !sel.isCollapsed && String(sel).length > 0) {
            return true; // hay texto seleccionado: no re-pintar la página
        }
        return false;
    }

    function limpiarOverlaysResiduales() {
        // 1) Si quedó algún modal de Bootstrap visible, no tocar nada
        //    (el backdrop le pertenece).
        var modalAbierto = document.querySelector('.modal.show');
        if (modalAbierto) return;

        // 2) Backdrops huérfanos de Bootstrap
        document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.remove(); });

        // 3) Overlay/backdrop propio de SweetAlert2 si no hay ningún Swal visible
        if (!document.querySelector('.swal2-container .swal2-popup')) {
            document.querySelectorAll('.swal2-container, .swal2-backdrop-show').forEach(function (o) { o.remove(); });
        }

        // 4) Clases y estilos de bloqueo que quedan en <body>.
        //    Solo quitar las clases; NUNCA hacer removeAttribute('style') en
        //    condiciones de interacción activa (eso era parte del problema).
        document.body.classList.remove('modal-open', 'swal2-shown', 'swal2-toast-shown', 'swal2-height-auto');
        document.documentElement.classList.remove('swal2-height-auto');

        // 5) Devolver el scroll a la normalidad SI no hay interacción activa.
        if (!hayInteraccionActiva()) {
            document.body.removeAttribute('style'); // quita padding-right/overflow del scrollbar-lock
        }
    }

    // Barrido preventivo DIFERIDO: cubre los casos donde un Swal se cierra por
    // fuera de nuestro código (click en el fondo, tecla Esc, etc.). La limpieza
    // solo se ejecuta pasados ~1,8s sin ninguna interacción nueva y siempre que
    // el usuario no esté escribiendo/seleccionando (ver hayInteraccionActiva()).
    ['mouseup', 'keyup', 'touchend'].forEach(function (evt) {
        document.addEventListener(evt, function () {
            ultimoEventoInteraccion = Date.now();
            if (timerBarridoInactivo) clearTimeout(timerBarridoInactivo);
            timerBarridoInactivo = setTimeout(function () {
                timerBarridoInactivo = null;
                // ¿Pasó algo nuevo mientras esperaba? -> reprogramar/no limpiar
                if (Date.now() - ultimoEventoInteraccion < 1500) return;
                // ¿El usuario tiene foco en un campo o texto seleccionado? -> no limpiar
                if (hayInteraccionActiva()) return;
                limpiarOverlaysResiduales();
            }, 1800);
        });
    });

    // Actualizar contador
    actualizarContadorEquipos();
    
    // ==========================================
    // CREAR UTE
    // ==========================================
    // IMPORTANTE (modo solo lectura): el modal "Crear UTE" y su botón NO se
    // renderizan cuando el rol es 'solo_lectura'. getElementById devuelve null
    // y, sin esta guarda, el TypeError cortaba TODO el DOMContentLoaded: por
    // eso en ese rol no andaban ni los filtros de los selects ni la paginación.
    const btnGuardarUte = document.getElementById('btn-guardar-ute');
    if (btnGuardarUte) btnGuardarUte.addEventListener('click', function() {
        const idCategoria = document.getElementById('select-categoria-ute').value;
        const nombreUte = document.getElementById('nombre-ute-input').value.trim();
        const generoUte = document.getElementById('genero-ute-input').value;
        
        if (!idCategoria || !nombreUte) {
            alertar('Faltan datos', 'Complete todos los campos', 'warning');
            return;
        }
        
        const formData = new FormData();
        formData.append('id_categoria', idCategoria);
        formData.append('nombre_ute', nombreUte);
        formData.append('genero', generoUte);
        
        postAjaxUte(BASE_UTE + '/ajax_crear_ute', formData)
        .then(data => {
            // Sesión caída o sin permisos (respuesta JSON del guard del controlador)
            if (data && data.sesion_caida) {
                alertar('Sesión expirada', data.error, 'warning').then(() => location.reload());
                return;
            }
            if (data.success) {
                // Cerrar modal + limpiar backdrop que Bootstrap deja rezagado
                const modalCrear = bootstrap.Modal.getInstance(document.getElementById('modalCrearUTE'));
                if (modalCrear) modalCrear.hide();
                setTimeout(limpiarOverlaysResiduales, 400);
                
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
                            <?php if (!$solo_lectura): ?>
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
                            <?php endif; ?>
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
                alertar('Error', 'Error: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertar('Error', (error && error.message ? error.message : 'Error al crear la UTE'), 'error');
        });
    });
    
    // ==========================================
    // VER INTEGRANTES
    // ==========================================
    document.querySelectorAll('.btn-ver-integrantes').forEach(btn => {
        btn.addEventListener('click', function() {
            const idUte = this.getAttribute('data-id-ute');
            
            // SNAPSHOT del estado real de la UTE al abrir el modal. Si mas
            // tarde el servidor/firewall bloquea (403) una baja o alta de
            // integrante, restauramos esta lista exacta en vez de dejar la UI
            // mostrando algo que NO se guardo en la BD.
            window.__snapshotIntegrantes = document.getElementById('lista-integrantes')
                ? document.getElementById('lista-integrantes').innerHTML : '';

            getAjaxUte(BASE_UTE + '/ajax_detalle_ute/' + idUte)
            .then(ute => {
                // Respuestas de error en JSON (sesión caída, sin permisos, etc.)
                if (ute && ute.success === false) {
                    if (ute.sesion_caida) {
                        alertar('Sesión expirada', ute.error, 'warning').then(() => location.reload());
                    } else {
                        alertar('Error', ute.error, 'error');
                    }
                    return;
                }
                if (ute && ute.nombre_ute) {
                    document.getElementById('nombre-ute-modal').textContent = ute.nombre_ute;
                    const generoUteDetalle = (ute.genero || ute.genero_categoria || '').toUpperCase();
                    document.getElementById('info-categoria-modal').textContent = 
                        ute.nombre_deporte + ' - ' + ute.nombre_categoria + ' (' + generoUteDetalle + ')';
                    
                    const tbody = document.getElementById('lista-integrantes');
                    if (!tbody) return;
                    tbody.innerHTML = '';
                    
                    if (ute.integrantes && ute.integrantes.length > 0) {
                        ute.integrantes.forEach(int => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="fw-semibold">${int.nombre_completo}</td>
                                <td>${int.dni}</td>
                                <td>${int.delegacion}</td>
                                <td>${int.sexo}</td>
                                <?php if (!$solo_lectura): ?>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger btn-quitar-integrante" 
                                            data-id-ute="${idUte}" 
                                            data-id-participante="${int.id_participante}"
                                            title="Quitar de la UTE">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="<?= $solo_lectura ? 4 : 5 ?>" class="text-center text-muted py-3">Sin integrantes</td></tr>';
                    }
                    
                    new bootstrap.Modal(document.getElementById('modalVerIntegrantes')).show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alertar('Error', 'Error al cargar los integrantes', 'error');
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
            
            confirmarAccion('¿Está seguro de quitar este participante de la UTE?', 'warning', 'Sí, quitar', 'Cancelar')
            .then(function(confirmado) {
                if (!confirmado) return;
                const formData = new FormData();
                formData.append('id_ute', idUte);
                formData.append('id_participante', idParticipante);
                
                postAjaxUte(BASE_UTE + '/ajax_eliminar_participante_ute', formData)
                .then(data => {
                    if (data.sesion_caida) {
                        alertar('Sesión expirada', data.error, 'warning').then(() => location.reload());
                        return;
                    }
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
                        alertar('Error', 'Error: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // El servidor NO confirmo la baja: restaurar la lista real
                    // para no mostrar un participante "quitado" que sigue en la BD.
                    const tbody = document.getElementById('lista-integrantes');
                    if (tbody && window.__snapshotIntegrantes !== undefined) {
                        tbody.innerHTML = window.__snapshotIntegrantes;
                    }
                    alertar('Error', (error && error.message ? error.message : 'Error al quitar el participante'), 'error');
                });
            });
        }
    });
    
    // ==========================================
    // AGREGAR INTEGRANTE
    // ==========================================
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-agregar-integrante');
        if (!btn) return;
        // En modo solo lectura este botón no existe, pero por las dudas:
        // si el modal no está renderizado, salir sin errores.
        if (!document.getElementById('modalAgregarIntegrante')) return;

        const idUte = btn.getAttribute('data-id-ute');
        const idCategoria = btn.getAttribute('data-id-categoria');
        
        document.getElementById('id-ute-agregar').value = idUte;
            
            // Cargar participantes disponibles
            const select = document.getElementById('select-participante-disponible');
            select.innerHTML = '<option value="">Cargando...</option>';
            
            getAjaxUte(BASE_UTE + '/ajax_participantes_disponibles/' + idCategoria)
            .then(participantes => {
                const resultado = parsearListaJson(participantes);

                // Sesión caída: avisar y recargar (igual que en las escrituras)
                if (!resultado.ok && resultado.sesion_caida) {
                    select.innerHTML = '<option value="" disabled>Sesión expirada</option>';
                    alertar('Sesión expirada', resultado.error, 'warning').then(() => location.reload());
                    return;
                }
                // Otro error del servidor: mostrarlo explícitamente
                if (!resultado.ok) {
                    select.innerHTML = '<option value="" disabled>Error al cargar</option>';
                    alertar('Error', resultado.error, 'error');
                    return;
                }

                select.innerHTML = '<option value="">Seleccione un participante...</option>';
                
                if (resultado.lista && resultado.lista.length > 0) {
                    resultado.lista.forEach(p => {
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
    });
    
    // El botón "Agregar" vive en el modal Agregar Integrante, que NO se
    // renderiza para el rol solo_lectura -> guarda nula obligatoria.
    const btnAgregarParticipante = document.getElementById('btn-agregar-participante');
    if (btnAgregarParticipante) btnAgregarParticipante.addEventListener('click', function() {
        const idUte = document.getElementById('id-ute-agregar').value;
        const idParticipante = document.getElementById('select-participante-disponible').value;
        
        if (!idParticipante) {
            alertar('Atención', 'Seleccione un participante', 'warning');
            return;
        }
        
        const formData = new FormData();
        formData.append('id_ute', idUte);
        formData.append('id_participante', idParticipante);
        
        postAjaxUte(BASE_UTE + '/ajax_agregar_participante_ute', formData)
        .then(data => {
            if (data.success) {
                // Cerrar modal + limpiar backdrop que Bootstrap deja rezagado
                const modalAgregar = bootstrap.Modal.getInstance(document.getElementById('modalAgregarIntegrante'));
                if (modalAgregar) modalAgregar.hide();
                setTimeout(limpiarOverlaysResiduales, 400);
                
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
                alertar('Error', 'Error: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertar('Error', (error && error.message ? error.message : 'Error al agregar el participante'), 'error');
        });
    });
    
    // ==========================================
    // EDITAR UTE (nombre y/o género)
    // ==========================================
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-editar-ute');
        if (!btn) return;
        // En modo solo lectura el modal Editar UTE no existe -> salir sin errores.
        if (!document.getElementById('modalEditarUTE')) return;

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

    // El botón "Guardar cambios" vive en el modal Editar UTE, que NO se
    // renderiza para el rol solo_lectura -> guarda nula obligatoria.
    const btnGuardarEdicionUte = document.getElementById('btn-guardar-edicion-ute');
    if (btnGuardarEdicionUte) btnGuardarEdicionUte.addEventListener('click', function() {
        const idUte = document.getElementById('editar-id-ute').value;
        const nombreNuevo = document.getElementById('editar-nombre-ute').value.trim();
        const generoNuevo = document.getElementById('editar-genero-ute').value;

        if (!idUte || !nombreNuevo) {
            alertar('Falta el nombre', 'Complete el nombre de la UTE', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('id_ute', idUte);
        formData.append('nombre_ute', nombreNuevo);
        formData.append('genero', generoNuevo);

        postAjaxUte(BASE_UTE + '/ajax_actualizar_ute', formData)
        .then(data => {
            if (data.success) {
                // Cerrar modal + limpiar backdrop que Bootstrap deja rezagado
                const modalEditar = bootstrap.Modal.getInstance(document.getElementById('modalEditarUTE'));
                if (modalEditar) modalEditar.hide();
                setTimeout(limpiarOverlaysResiduales, 400);

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
                alertar('Error', 'Error: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alertar('Error', (error && error.message ? error.message : 'Error al actualizar la UTE'), 'error');
        });
    });

    // ==========================================
    // ELIMINAR UTE
    // ==========================================
    document.querySelectorAll('.btn-eliminar-ute').forEach(btn => {
        btn.addEventListener('click', function() {
            const idUte = this.getAttribute('data-id-ute');
            const nombreUte = this.getAttribute('data-nombre-ute');
            
            confirmarAccion(`¿Está SEGURO que desea eliminar la UTE "${nombreUte}"?\n\nEsta acción eliminará también todos sus integrantes.`, 'warning', 'Sí, eliminar', 'Cancelar')
            .then(function(confirmado) {
                if (!confirmado) return;
                const formData = new FormData();
                formData.append('id_ute', idUte);
                
                postAjaxUte(BASE_UTE + '/ajax_eliminar_ute', formData)
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
                        alertar('Error', 'Error: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alertar('Error', (error && error.message ? error.message : 'Error al eliminar la UTE'), 'error');
                });
            });
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

    // Normalizador de texto para comparar (los nombres vienen de la BD y pueden
    // traer acentos o espacios de más; sin esto el filtro "no anda" porque
    // "FÚTBOL" !== "futbol" al comparar).
    const normalizarTexto = s => String(s || '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

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

                // PAGINACIÓN ROBUSTA: los botones se crean dinámicamente acá, así que
                // enlazarlos con addEventListener dentro de este bucle es frágil (se
                // re-crea el innerHTML en cada render y cualquier error previo en la
                // función impedía registrarlos -> los clicks no hacían nada).
                // Se usa delegación de eventos UNA sola vez sobre el <ul>, que funciona
                // siempre sin importar cuándo se re-rendericen los botones.
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
        const terminoBusqueda = normalizarTexto(inputBuscarEquipo.value);
        const deporteSeleccionado = normalizarTexto(filtroDeporteEquipo.value);
        const categoriaSeleccionada = normalizarTexto(filtroCategoriaEquipo.value);

        filasFiltradasEquipos = filasEquipos.filter(fila => {
            // Obtener datos de la fila
            const textoFila = normalizarTexto(fila.textContent);

            const badgeDeporte = fila.querySelector('td:nth-child(1) .fw-semibold');
            const deporteFila = badgeDeporte ? normalizarTexto(badgeDeporte.textContent) : '';

            const celdaCategoria = fila.querySelector('td:nth-child(2)');
            const categoriaFila = celdaCategoria ? normalizarTexto(celdaCategoria.textContent) : '';

            // Aplicar filtros
            let coincide = true;

            // Filtro por búsqueda de texto
            if (terminoBusqueda && !textoFila.includes(terminoBusqueda)) {
                coincide = false;
            }

            // Filtro por deporte (comparación normalizada: sin acentos ni espacios extra)
            if (deporteSeleccionado && deporteFila !== deporteSeleccionado) {
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

    // ----------------------------------------------------
    // PAGINACIÓN: delegación de eventos (un solo listener).
    // Funciona para todos los roles (superadmin y solo_lectura) porque no
    // depende de re-enlazar los botones cada vez que se re-renderiza el <ul>.
    // ----------------------------------------------------
    const ulPaginacionEquiposEl = document.getElementById('ulPaginacionEquipos');
    if (ulPaginacionEquiposEl) {
        ulPaginacionEquiposEl.addEventListener('click', function(e) {
            const btnPag = e.target.closest('button[data-page-equipos]');
            if (!btnPag) return;
            if (btnPag.closest('.page-item') && btnPag.closest('.page-item').classList.contains('disabled')) return;
            const nuevaPagEquipos = parseInt(btnPag.getAttribute('data-page-equipos'), 10);
            const totalPags = Math.ceil(filasFiltradasEquipos.length / filasPorPaginaEquipos) || 1;
            if (!isNaN(nuevaPagEquipos) && nuevaPagEquipos >= 1 && nuevaPagEquipos <= totalPags) {
                paginaActualEquipos = nuevaPagEquipos;
                actualizarTablaEquipos();
                // Devolver la tabla a la vista al cambiar de página
                const tabla = document.getElementById('tabla-utes');
                if (tabla) tabla.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }

    // Dejar las categorías filtradas segun el estado inicial del select de deporte
    actualizarCategoriasPorDeporte();
    actualizarTablaEquipos();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

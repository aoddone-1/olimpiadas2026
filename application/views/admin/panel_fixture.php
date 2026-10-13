<!-- PANEL FIXTURE (vista parcial: se carga dentro de plantilla_panel.php
     desde Inscripciones::fixture()) -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="h5 fw-bold text-uppercase m-0">
        <i class="bi bi-calendar3 me-2 text-primary"></i>Fixture
    </h2>
    <a href="<?= base_url('Inscripciones/inscriptos') ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
        <i class="bi bi-arrow-left me-1"></i> Volver a Inscriptos
    </a>
</div>
<div class="card fx-card shadow-sm border-0">
    <div class="card-header fx-card-header fw-bold text-secondary d-flex flex-column gap-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <!-- Título con ícono suave en lugar de texto plano -->
            <div class="d-flex align-items-center gap-2">
                <span class="fx-header-icon d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-calendar3"></i>
                </span>
                <span class="fx-header-titulo">Gestión de Fixture <u><?= LUGAR_OLIMPICO2 ?></u></span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <select id="fx_deporte_reporte" class="form-select form-select-sm fx-select-reporte"
                    title="Elegí un deporte para descargar su fixture filtrado (o dejá 'Todos' para el general)">
                    <option value="">🏆 Todos los deportes</option>
                    <?php foreach ($deportes_fixture as $dep): ?>
                        <option value="<?= $dep['id_deporte'] ?>">
                            <?= htmlspecialchars($dep['nombre_deporte']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- Botón PDF: antes rojo sólido y grande (muy chocante); ahora
                     outline sutil que solo se "enciende" al pasar el mouse -->
                <a id="fx_btn_pdf" href="<?= base_url('Inscripciones/descargar_pdf_fixture') ?>" target="_blank"
                    class="btn fx-btn-pdf" title="PDF del fixture en cuadro: columnas = días, filas = rangos horarios">
                    <i class="bi bi-file-earmark-pdf me-1"></i>
                    <span id="fx_btn_pdf_txt">Descargar reporte de fixture</span>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- CAJA DE MENSAJES (fx_mensaje): sin este elemento, la función mensaje()
             hacía msgBox.className sobre null y TIRABA TypeError en plena acción
             AJAX. Como eliminar partido actualiza el listado "optimistamente"
             (filter + render, sin pedir nada al server), era el único CRUD que
             se veía; agregar/modificar llamaban a mensaje() ANTES de recargar
             (cargarTodo) y el error cortaba todo el flujo — por eso solo se
             veían apretando F5. -->
        <div id="fx_mensaje" class="alert d-none mb-3" role="alert"></div>

        <!-- Barra de FILTROS: deporte y categoría filtran el listado -->
        <div class="fx-filtros row g-2 align-items-end mb-3">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small fw-semibold mb-1"><i class="bi bi-funnel-fill text-primary me-1"></i>Filtrar por deporte</label>
                <select id="fx_filtro_deporte" class="form-select">
                    <option value="">— Todos los deportes —</option>
                    <?php foreach ($deportes_fixture as $dep): ?>
                        <option value="<?= $dep['id_deporte'] ?>">
                            <?= htmlspecialchars($dep['nombre_deporte']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5 col-lg-4">
                <label class="form-label small fw-semibold mb-1"><i class="bi bi-layers-fill text-primary me-1"></i>Filtrar por categoría</label>
                <select id="fx_filtro_categoria" class="form-select" disabled>
                    <option value="">— Todas las categorías —</option>
                    <?php foreach ($categorias_fixture as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>"
                                data-id-deporte="<?= $cat['id_deporte'] ?>">
                            <?= htmlspecialchars($cat['nombre_deporte']) ?> — <?= htmlspecialchars($cat['nombre_categoria']) ?>
                            (<?= htmlspecialchars($cat['genero']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-12 col-lg-5">
                <label class="form-label small fw-bold d-none d-lg-block">&nbsp;</label>
                <div class="d-flex flex-wrap gap-2 fx-acciones">
                    <button id="fx_btn_nuevo" class="btn fx-btn-round fx-btn-nuevo" title="Crear un partido manual">
                        <i class="bi bi-plus-circle-fill"></i><span class="ms-2">Nuevo partido</span>
                    </button>
                    <button id="fx_btn_borrar_todo" class="btn fx-btn-round fx-btn-borrar" disabled title="Borra el fixture del deporte/categoría que estés filtrando">
                        <i class="bi bi-trash3-fill"></i><span class="ms-2">Borrar fixture</span>
                    </button>
                    <button id="fx_btn_limpiar" class="btn fx-btn-round fx-btn-limpiar d-none" title="Quitar los filtros activos">
                        <i class="bi bi-x-circle"></i><span class="ms-2">Limpiar filtros</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Chips con los filtros activos -->
        <div id="fx_chips_filtros" class="mb-2"></div>

        <!-- Selector de DÍA de competencia: solo los días del calendario oficial -->
        <div class="mb-3 border-top pt-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small fw-semibold me-1"><i class="bi bi-calendar-day-fill text-danger me-1"></i>Día de competencia:</span>
                <div class="btn-group btn-group-sm" role="group" aria-label="Días de competencia" id="fx_dias_btns"></div>
                <span id="fx_dia_resumen" class="small text-muted ms-1"></span>
            </div>
        </div>

        <!-- Listado del día seleccionado -->
        <div id="fx_lista"></div>
    </div>
</div>

<!-- MODAL: crear/editar partido -->
<div class="modal fade" id="modalPartido" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Partido / Jornada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form_partido">
                    <input type="hidden" name="id_fixture" id="fx_id_fixture">

                    <!-- Selección de categoría DEPORTIVA dentro del modal -->
                    <?php
                    // El modal de partido manual necesita TODAS las categorías
                    // (no solo las sin fixture, como el selector de generar).
                    // Se desdobla la búsqueda: primero se elige el DEPORTE y las
                    // CATEGORÍAS de ese deporte se cargan por AJAX al seleccionar
                    // (endpoint Inscripciones/ajax_categorias_por_deporte/$id).
                    $catsModalOrigen = !empty($categorias_modal_partido) ? $categorias_modal_partido
                        : (isset($categorias_fixture) && is_array($categorias_fixture) ? $categorias_fixture : array());
                    $deportesDelModal = array();
                    foreach ($catsModalOrigen as $cat):
                        $idDep = (int) $cat['id_deporte'];
                        if (!isset($deportesDelModal[$idDep])) {
                            $deportesDelModal[$idDep] = $cat['nombre_deporte'];
                        }
                    endforeach;
                    ?>
                    <div class="row g-2 mb-2">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Deporte *</label>
                            <select id="fx_modal_deporte" class="form-select" required>
                                <option value="">— Elegí un deporte —</option>
                                <?php foreach ($deportesDelModal as $idDep => $nomDep): ?>
                                    <option value="<?= $idDep ?>"><?= htmlspecialchars($nomDep) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Categoría *</label>
                            <select id="fx_modal_categoria" class="form-select" required disabled>
                                <option value="">— Primero elegí un deporte —</option>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="id_categoria" id="fx_cat_hidden">

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Nombre de la prueba *</label>
                        <input type="text" name="nombre_prueba" id="fx_nombre_prueba" class="form-control" placeholder="Ej: Final - Partido 1, Largada 5K...">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Fase</label>
                            <select name="fase" id="fx_fase" class="form-select">
                                <option value="GRUPO">GRUPO</option>
                                <option value="16AVOS">16AVOS</option>
                                <option value="OCTAVOS">OCTAVOS</option>
                                <option value="CUARTOS">CUARTOS</option>
                                <option value="SEMIFINAL">SEMIFINAL</option>
                                <option value="TERCER_PUESTO">TERCER PUESTO</option>
                                <option value="FINAL">FINAL</option>
                                <option value="JORNADA_UNICA">JORNADA ÚNICA</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Fecha/Jornada N°</label>
                            <input type="number" name="numero_fecha" id="fx_numero_fecha" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Equipo 1</label>
                            <select name="id_ute_1" id="fx_ute1" class="form-select"></select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Equipo 2</label>
                            <select name="id_ute_2" id="fx_ute2" class="form-select"></select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Lugar</label>
                            <select name="id_lugar" id="fx_lugar" class="form-select">
                                <option value="">— Sin asignar —</option>
                                <?php foreach ($lugares_db as $lugar): ?>
                                    <option value="<?= $lugar['id'] ?>"><?= htmlspecialchars($lugar['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Estado</label>
                            <select name="estado" id="fx_estado" class="form-select">
                                <option value="PROGRAMADO">PROGRAMADO</option>
                                <option value="EN_CURSO">EN CURSO</option>
                                <option value="FINALIZADO">FINALIZADO</option>
                                <option value="SUSPENDIDO">SUSPENDIDO</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Fecha *</label>
                            <input type="date" name="fecha_competencia" id="fx_fecha" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Hora inicio *</label>
                            <input type="time" name="hora_inicio" id="fx_hora_ini" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Hora fin *</label>
                            <input type="time" name="hora_fin" id="fx_hora_fin" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="fx_btn_guardar" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: detalle de participantes (partido o categoría) -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <div>
                    <h5 class="modal-title mb-0" id="fxx_titulo">Detalle</h5>
                    <div class="small opacity-75" id="fxx_sub"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2" id="fxx_body"></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const BASE = '<?= base_url("Inscripciones") ?>';

    /* ---------- MODAL: resultados de deportes masivos (orden de llegada) ---------- */
    if (!document.getElementById('modalMasivo')) {
        document.body.insertAdjacentHTML('beforeend', `
        <div class="modal fade" id="modalMasivo" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-flag me-2"></i>Resultados — <span id="fxm_titulo"></span></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-2">
                            Los equipos en gris todavía no llegaron. A medida que vayan cruzando la meta,
                            <strong>tocalos en orden</strong>: el primero que elijas es 🥇, el segundo 🥈, etc.
                            Si te equivocaste, tocá un equipo ya asignado para devolverlo a "sin llegar".
                        </p>
                        <div id="fxm_lista" class="list-group"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" id="fxm_reset">Reiniciar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" id="fxm_guardar" class="btn btn-primary">Guardar resultado</button>
                    </div>
                </div>
            </div>
        </div>`);
    }

    const selFiltroDeporte = document.getElementById('fx_filtro_deporte');
    const selFiltroCategoria = document.getElementById('fx_filtro_categoria');
    const btnNuevo = document.getElementById('fx_btn_nuevo');
    const btnBorrarTodo = document.getElementById('fx_btn_borrar_todo');
    const btnLimpiarFiltros = document.getElementById('fx_btn_limpiar');
    const chipsFiltros = document.getElementById('fx_chips_filtros');
    const msgBox = document.getElementById('fx_mensaje');
    const lista = document.getElementById('fx_lista');
    const contenedorDias = document.getElementById('fx_dias_btns');

    let todosFixtures = [];   // fixture completo de TODAS las categorías

    /* ---------- Fechas locales (YYYY-MM-DD) sin depender del huso horario ---------- */
    function fechaISO(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function hoyISO() {
        return fechaISO(new Date());
    }

    /* ---------- Calendario oficial de la competencia: 02/11/2026 al 06/11/2026 ----------
       El filtro de día solo habilita estos días. Si algún partido cargado tiene una
       fecha fuera de este rango, su día se agrega automáticamente al selector para
       que no quede invisible. */
    const INICIO_COMPETENCIA = '2026-11-02';
    const FIN_COMPETENCIA = '2026-11-06';

    function diasCalendario() {
        const dias = [];
        const ini = INICIO_COMPETENCIA.split('-');
        const fin = FIN_COMPETENCIA.split('-');
        let d = new Date(+ini[0], +ini[1] - 1, +ini[2]);
        const f = new Date(+fin[0], +fin[1] - 1, +fin[2]);
        while (d <= f) {
            dias.push(fechaISO(d));
            d.setDate(d.getDate() + 1);
        }
        return dias;
    }

    // Día seleccionado: HOY si cae dentro del calendario oficial; si no, el primer día
    let diaActual = (hoyISO() >= INICIO_COMPETENCIA && hoyISO() <= FIN_COMPETENCIA)
        ? hoyISO() : INICIO_COMPETENCIA;
    let modalPartido = new bootstrap.Modal(document.getElementById('modalPartido'));
    let modalMasivo = null;   // se crea al primer uso (el HTML está más abajo)
    // Modal de detalle de participantes: instancia Bootstrap creada ACÁ
    // (antes faltaba esta línea y el botón "Detalle" tiraba ReferenceError).
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalle'));

    const MEDALLAS = ['🥇', '🥈', '🥉'];

    const BADGES = {
        /* Badges de estado en versión "pastel": el color saturado solo
           aparece en elementos chicos, sin lastimar la vista */
        PROGRAMADO: 'fx-badge-programado', EN_CURSO: 'fx-badge-en-curso',
        FINALIZADO: 'fx-badge-finalizado', SUSPENDIDO: 'fx-badge-suspendido'
    };

    function esc(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function mensaje(texto, tipo) {
        // Guard: si el elemento #fx_mensaje no existe (o el script corre antes
        // de que esté en el DOM), NO hay que romper toda la cadena del AJAX.
        if (!msgBox) { console.warn('[Fixture]', texto); return; }
        msgBox.className = 'alert alert-' + tipo;
        msgBox.textContent = texto;
        clearTimeout(mensaje._t);
        mensaje._t = setTimeout(() => msgBox.classList.add('d-none'), 4000);
    }

    // No-cache en las peticiones AJAX: sin esto, el navegador puede servir la
    // respuesta de ajax_fixture_todo desde el cache (o desde "bfcache" al volver
    // atrás) y el listado no refleja los cambios hasta apretar F5.
    const FETCH_NO_CACHE = { cache: 'no-store' };

    function post(url, datos) {
        const form = new FormData();
        Object.keys(datos).forEach(k => form.append(k, datos[k]));
        // El timestamp en la query es un "cache-buster": algunos proxies y el
        // propio navegador pueden cachear respuestas POST si la URL no cambia;
        // con una URL única por petición eso no ocurre nunca más.
        const sep = url.indexOf('?') === -1 ? '?' : '&';
        return fetch(BASE + '/' + url + sep + '_=' + Date.now(),
                     { method: 'POST', body: form, cache: 'no-store' })
            .then(r => r.text().then(txt => ({ status: r.status, txt: txt })))
            .then(({ status, txt }) => {
                try {
                    return JSON.parse(txt);
                } catch (e) {
                    console.error('Respuesta no-JSON de ' + url + ' (HTTP ' + status + '):', txt.slice(0, 2000));
                    throw new Error('El servidor devolvió una respuesta inesperada (HTTP ' + status + ' en ' + url + '). Revisá la consola.');
                }
            });
    }

    function getJSON(url) {
        // Cache-buster en la URL: sin esto, el navegador/proxy puede devolver la
        // respuesta anterior de ajax_fixture_todo y los cambios (alta/edición)
        // recién se ven apretando F5. Se lee el texto primero: si CodeIgniter
        // devolvió un error fatal (HTML), lo detectamos acá en vez de romper con
        // "Unexpected token <" al parsear JSON.
        const sep = url.indexOf('?') === -1 ? '?' : '&';
        return fetch(BASE + '/' + url + sep + '_=' + Date.now(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' },
                cache: 'no-store'
            })
            .then(r => r.text().then(txt => ({ status: r.status, txt: txt })))
            .then(({ status, txt }) => {
                try {
                    return JSON.parse(txt);
                } catch (e) {
                    console.error('Respuesta no-JSON de ' + url + ' (HTTP ' + status + '):', txt.slice(0, 2000));
                    const m = txt.match(/<title>([^<]*)<\/title>/i);
                    throw new Error('El servidor respondió HTML (HTTP ' + status + ')' +
                        (m ? ': ' + m[1] : '') + '. Revisá los logs de PHP.');
                }
            });
    }

    function fechaArma(f) {
        if (!f) return '—';
        const p = f.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    /* ---------- Estado "en vivo" según fecha/hora del partido ----------
       Compara la fecha_competencia + hora_inicio/hora_fin contra el reloj actual.
       Reglas (solo aplica sobre partidos PROGRAMADOS o EN_CURSO; respeta
       SUSPENDIDO y FINALIZADO con resultado cargado, que son estados "reales"):
         - ahora > hora_fin  (o hora_inicio si no hay fin)  -> FINALIZADO
         - ahora >= hora_inicio y <= hora_fin               -> EN_CURSO
       Devuelve además un badge extra ("auto") para aclarar que el estado es
       calculado por horario y no lo modificó un usuario en la base de datos. */
    function estadoEnVivo(f) {
        const estadoDb = (f.estado || '').toUpperCase();
        if (estadoDb === 'SUSPENDIDO') return { estado: estadoDb, auto: false };
        if (estadoDb === 'FINALIZADO') return { estado: estadoDb, auto: false };

        const fecha = (f.fecha_competencia || '').slice(0, 10);
        if (!fecha) return { estado: estadoDb, auto: false };

        const iniStr = (f.hora_inicio || '').slice(0, 5);
        const finStr = (f.hora_fin || '').slice(0, 5);

        const inicio = new Date(fecha + 'T' + (iniStr || '00:00') + ':00');
        const fin = new Date(fecha + 'T' + (finStr || iniStr || '23:59') + ':00');
        if (isNaN(inicio.getTime())) return { estado: estadoDb, auto: false };
        if (isNaN(fin.getTime()) || fin < inicio) fin.setTime(inicio.getTime() + 2 * 60 * 60 * 1000); // default 2hs

        const ahora = new Date();
        if (ahora >= fin) return { estado: 'FINALIZADO', auto: true };
        if (ahora >= inicio) return { estado: 'EN_CURSO', auto: true };
        return { estado: estadoDb, auto: false };
    }

    function eliminarPartido(idFixture) {
        return post('ajax_eliminar_partido', { id_fixture: idFixture })
            .then(res => {
                if (!res || !res.ok) {
                    mensaje((res && (res.error || res.mensaje)) || 'No se pudo eliminar el partido.', 'danger');
                    return false;
                }
                // Sacar el partido del estado local AL INSTANTE: así el listado
                // se actualiza sin necesidad de recargar la página (F5), incluso
                // si luego la refresca completa falla.
                todosFixtures = todosFixtures.filter(f => String(f.id_fixture) !== String(idFixture));
                render();
                return true;
            });
    }

    /* ---------- Render por DÍA: muestra solo lo que se juega en la fecha elegida,
                    agrupado por Deporte → Categoría → Jornada ---------- */
    function render() {
        const dia = diaActual;

        // Filtros activos (los selectores de arriba ahora son solo filtros de vista)
        const fDep = selFiltroDeporte.value;
        const fCat = selFiltroCategoria.value;
        function pasaFiltro(f) {
            if (fDep && String(f.id_deporte) !== String(fDep)) return false;
            if (fCat && String(f.id_categoria) !== String(fCat)) return false;
            return true;
        }
        const fixturesFiltrados = todosFixtures.filter(pasaFiltro);
        renderChipsFiltros(fixturesFiltrados.length);

        // Filtrar el fixture del día seleccionado (ya con deporte/categoría aplicados)
        const fixturesDelDia = dia
            ? fixturesFiltrados.filter(f => (f.fecha_competencia || '').slice(0, 10) === dia)
            : fixturesFiltrados.slice();

        // Botones de día + resumen: "sábado 26/09/2026 — 12 partidos · 4 deportes"
        renderBotonesDias(fixturesDelDia);

        if (!todosFixtures.length) {
            lista.innerHTML = '<div class="fx-vacio text-center text-muted py-5">' +
                '<span class="fx-vacio-icon d-inline-flex align-items-center justify-content-center mb-3"><i class="bi bi-calendar-x fs-2"></i></span>' +
                '<div class="fw-semibold">Todavía no hay ningún partido cargado.</div>' +
                '<div class="small mt-1">Creá uno manual con el botón <strong>+ Nuevo partido</strong>.</div></div>';
            return;
        }

        if (!fixturesFiltrados.length) {
            lista.innerHTML = '<div class="fx-vacio text-center text-muted py-5">' +
                '<span class="fx-vacio-icon d-inline-flex align-items-center justify-content-center mb-3"><i class="bi bi-funnel fs-2"></i></span>' +
                '<div class="fw-semibold">No hay partidos con los filtros seleccionados.</div>' +
                '<div class="small mt-1">Probá con otro deporte/categoría o limpiá los filtros.</div></div>';
            return;
        }

        if (!fixturesDelDia.length) {
            lista.innerHTML = '<div class="fx-vacio text-center text-muted py-5">' +
                '<span class="fx-vacio-icon d-inline-flex align-items-center justify-content-center mb-3"><i class="bi bi-calendar-day fs-2"></i></span>' +
                '<div class="fw-semibold">No hay competencia programada para ' + (dia ? esc(fechaArma(dia)) : 'la fecha seleccionada') + '.</div>' +
                '<div class="small mt-1">Elegí otro día arriba para ver lo que se juega.</div></div>';
            return;
        }

        // Agrupar en dos niveles: Deporte → Categoría
        // (todas las categorías de un mismo deporte quedan juntas dentro de un
        //  bloque del deporte, para no tener que scrollear un montón)
        const deportes = {};
        fixturesDelDia.forEach(f => {
            const dep = f.nombre_deporte || '?';
            const cat = f.nombre_categoria || '?';
            if (!deportes[dep]) deportes[dep] = {};
            (deportes[dep][cat] = deportes[dep][cat] || []).push(f);
        });

        // ¿Cuántos bloques de categoría hay abiertos? Si son muchos, arrancan
        // cerrados para que la página sea navegable de un vistazo.
        const totalCats = Object.values(deportes).reduce((n, cats) => n + Object.keys(cats).length, 0);
        const colapsarPorDefecto = totalCats > 4;

        let html = '';
        Object.keys(deportes).sort().forEach(deporte => {
            const cats = deportes[deporte];
            const partidosDelDeporte = Object.values(cats).reduce((n, items) => n + items.length, 0);
            const idDepBlock = 'fxdep_' + encodeURIComponent(deporte).replace(/%/g, '');

            html += `<div class="card mb-4 border-primary-subtle shadow-sm">
                <div class="card-header py-2">
                    <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center gap-2 fx-deporte-titulo fw-bold"
                            type="button" data-bs-toggle="collapse" data-bs-target="#${idDepBlock}" aria-expanded="${colapsarPorDefecto ? 'false' : 'true'}">
                        <i class="bi bi-chevron-down fx-chev"></i>
                        <i class="bi bi-trophy-fill"></i>
                        <span>${esc(deporte)}</span>
                    </button>
                    <span class="badge bg-white text-primary ms-2">${Object.keys(cats).length} categoría/s</span>
                    <span class="small opacity-75 ms-1">(${partidosDelDeporte} partido/s)</span>
                </div>
                <div class="collapse${colapsarPorDefecto ? '' : ' show'}" id="${idDepBlock}">
                <div class="card-body py-2">`;

            Object.keys(cats).sort().forEach(categoria => {
                const items = cats[categoria];
                const primera = items[0];
                const tipoTag = primera.modalidad_competencia === 'MASIVO_TIEMPO'
                    ? '<span class="badge bg-warning text-dark ms-2">Masivo / Un día</span>'
                    : (primera.tipo_duracion === 'UNICO_DIA'
                        ? '<span class="badge bg-info text-dark ms-2">Un día</span>'
                        : '<span class="badge bg-primary ms-2">Multidía</span>');
                const idCat = primera.id_categoria;

                html += `<div class="border rounded bg-light-subtle mb-3">
                    <div class="px-2 py-1 border-bottom bg-white rounded-top d-flex flex-wrap justify-content-between align-items-center gap-1">
                        <span>
                            <span class="fw-semibold"><i class="bi bi-people me-1 text-muted"></i>${esc(categoria)}</span>
                            ${tipoTag}
                            <span class="small text-muted ms-2">(${items.length} partido/s)</span>
                        </span>
                    </div>
                    <div class="p-2">`;

                // Subagrupar por jornada
                const jornadas = {};
                items.forEach(f => {
                    (jornadas[f.numero_fecha] = jornadas[f.numero_fecha] || []).push(f);
                });

                Object.keys(jornadas).sort((a, b) => a - b).forEach(num => {
                    html += `<h6 class="fw-bold mt-1 mb-1 small"><i class="bi bi-calendar-week me-1"></i>Jornada ${esc(num)}</h6>`;
                    html += '<div class="list-group mb-2">';
                    jornadas[num].forEach(f => {
                    const esMasivo = f.fase === 'JORNADA_UNICA'
                                  || f.modalidad_competencia === 'MASIVO_TIEMPO';
                    const ev = estadoEnVivo(f);
                    const badge = BADGES[ev.estado] || 'bg-secondary';

                    // ---- Podio de deportes masivos (orden de llegada guardado en f.resultado) ----
                    let ordenGuardado = [];
                    if (esMasivo && f.resultado) {
                        try { ordenGuardado = JSON.parse(f.resultado) || []; } catch (e) { ordenGuardado = []; }
                    }
                    const nombresPorId = {};
                    (f.utes_categoria || []).forEach(u => { nombresPorId[u.id_ute] = u.nombre_ute; });
                    if (f.id_ute_1) nombresPorId[f.id_ute_1] = f.ute_1_nombre;
                    if (f.id_ute_2) nombresPorId[f.id_ute_2] = f.ute_2_nombre;

                    let podioHtml = '';
                    if (esMasivo) {
                        if (ordenGuardado.length) {
                            podioHtml = '<div class="mt-1 small">' + ordenGuardado.slice(0, 5).map((id, i) => {
                                const medalla = MEDALLAS[i] || (i + 1) + 'º';
                                return `<span class="me-2">${medalla} ${esc(nombresPorId[id] || ('UTE #' + id))}</span>`;
                            }).join('') + '</div>';
                        } 
                    }

                    html += `<div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div style="min-width:260px">
                            <span class="badge bg-info text-dark me-1">${esc(f.fase.replace('_',' '))}</span>
                            <strong>${esc(f.nombre_prueba)}</strong>
                            <div class="small text-muted">
                                <i class="bi bi-clock me-1"></i>${fechaArma(f.fecha_competencia)} ${esc((f.hora_inicio||'').slice(0,5))}–${esc((f.hora_fin||'').slice(0,5))}
                                &nbsp;<i class="bi bi-geo-alt me-1"></i>${esc(f.lugar_nombre || 'Sin lugar')}
                            </div>
                            ${esMasivo ? podioHtml : `<button type="button" class="btn btn-link p-0 mt-1 fx-detalle fw-semibold text-dark text-decoration-none" data-id="${f.id_fixture}" title="Ver participantes del cruce">
                                ${(f.ute_1_nombre && f.ute_2_nombre) ? esc(f.ute_1_nombre) + '<br/> <span class="text-muted fw-normal">vs</span> ' + esc(f.ute_2_nombre) : '<span class="fst-italic text-muted fw-normal">Cruce pendiente — ver detalle</span>'}
                            </button>`}
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge ${badge}" ${ev.auto ? 'title="Estado calculado automáticamente según la fecha y hora del partido"' : ''}>${esc(ev.estado.replace('_',' '))}</span>
                            ${ev.auto ? '<i class="bi bi-clock-history text-muted small" title="Estado en vivo (por horario), no guardado en la base"></i>' : ''}
                            
                            ${(!esMasivo && f.id_ute_1 && f.id_ute_2) ? `
                                <span class="badge bg-light text-dark border" title="Equipos que se enfrentan">
                                    <i class="bi bi-person-lines-fill me-1"></i>${esc(f.ute_1_nombre)} vs ${esc(f.ute_2_nombre)}
                                </span>` : ''}
                            <div class="d-inline-flex align-items-center bg-light rounded-pill px-1 py-1 border">
                                <button class="btn btn-sm btn-light text-info-emphasis border-0 rounded-circle fx-detalle" data-id="${f.id_fixture}" title="Ver quiénes participan">
                                    <i class="bi bi-search"></i>
                                </button>
                                <span class="vr mx-1 text-muted opacity-25"></span>
                                <button class="btn btn-sm btn-light text-warning-emphasis border-0 rounded-circle fx-editar" data-id="${f.id_fixture}" title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <span class="vr mx-1 text-muted opacity-25"></span>
                                <button class="btn btn-sm btn-light text-danger border-0 rounded-circle fx-borrar" data-id="${f.id_fixture}" title="Eliminar">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </div>
                            
                            
                        </div>
                    </div>`;
                });
                    html += '</div>';   // cierra list-group de la jornada
                });                    // fin jornadas

                html += '</div></div>'; // cierra p-2 y bloque de categoría
            });                        // fin categorías

            html += '</div></div></div>'; // cierra card-body, collapse y card del deporte
        });
        lista.innerHTML = html;

        // Rotar la flechita del header del deporte al abrir/cerrar
        lista.querySelectorAll('.collapse[id^="fxdep_"]').forEach(col => {
            const chev = col.parentElement.querySelector('.fx-chev');
            col.addEventListener('shown.bs.collapse', () => chev && chev.classList.replace('bi-chevron-right', 'bi-chevron-down'));
            col.addEventListener('hidden.bs.collapse', () => chev && chev.classList.replace('bi-chevron-down', 'bi-chevron-right'));
        });

        // Eventos de los botones
        lista.querySelectorAll('.fx-editar').forEach(b => b.addEventListener('click', () => {
            const f = todosFixtures.find(x => x.id_fixture == b.dataset.id);
            abrirModal(f);
        }));
        lista.querySelectorAll('.fx-borrar').forEach(b => b.addEventListener('click', () => {
            if (!confirm('¿Eliminar este partido?')) return;
            eliminarPartido(b.dataset.id)
                .then(ok => { if (ok) { mensaje('Partido eliminado.', 'success'); cargarTodo(); } })
                .catch(err => mensaje(err.message, 'danger'));
        }));
        lista.querySelectorAll('.fx-detalle').forEach(b => b.addEventListener('click', () => abrirDetallePartido(b.dataset.id)));
        lista.querySelectorAll('.fx-cat-participantes').forEach(b => b.addEventListener('click', () => abrirInscriptosCategoria(b.dataset.cat, b.dataset.nombre)));
    }

    /* ============================================================
     *  MODAL DETALLE DE PARTICIPANTES (partido / categoría)
     * ============================================================ */

    function edadDesde(fechaNac) {
        if (!fechaNac) return '';
        const p = String(fechaNac).slice(0, 10).split('-');
        if (p.length !== 3) return '';
        const hoyRef = new Date();
        let edad = hoyRef.getFullYear() - (+p[0]);
        const mDiff = (hoyRef.getMonth() + 1) - (+p[1]);
        if (mDiff < 0 || (mDiff === 0 && hoyRef.getDate() < (+p[2]))) edad--;
        return edad >= 0 && edad < 120 ? edad : '';
    }

    /** Fila compacta de una persona: solo nombre y delegación (como pidió el admin). */
    function filaPersona(per) {
        return `<div class="d-flex justify-content-between align-items-center gap-2 py-1 border-bottom">
                    <span><i class="bi bi-person-circle me-2 text-muted"></i><strong>${esc(per.nombre_completo || per.nombre || '—')}</strong></span>
                    ${per.delegacion ? `<span class="small text-muted text-end"><i class="bi bi-geo-alt me-1"></i>${esc(per.delegacion)}</span>` : ''}
                </div>`;
    }

    /** Bloque EQUIPO colapsable: al tocarlo se despliega la lista de integrantes. */
    function bloqueEquipo(lado, icono) {
        if (!lado) return '';
        const miembros = lado.integrantes || [];
        const uid = 'fxx_' + Math.random().toString(36).slice(2, 9);
        const sub = lado.delegacion ? esc(lado.delegacion) : (miembros.length + ' integrante/s');
        if (!miembros.length) {
            return `<div class="border rounded p-2 mb-2 bg-white">
                        <span class="fw-semibold">${icono} ${esc(lado.nombre)}</span>
                        <div class="small fst-italic text-muted mt-1">No tiene integrantes registrados todavía.</div>
                    </div>`;
        }
        return `<div class="border rounded mb-2 bg-white">
                    <button type="button" class="btn btn-link text-decoration-none d-flex justify-content-between align-items-center w-100 px-2 py-2 text-dark"
                            data-bs-toggle="collapse" data-bs-target="#${uid}" aria-expanded="false">
                        <span class="fw-semibold">${icono} ${esc(lado.nombre)}
                            <span class="badge bg-secondary ms-2">${miembros.length}</span></span>
                        <span class="small text-muted"><i class="bi bi-chevron-down fxd-chev"></i> ver integrantes</span>
                    </button>
                    <div class="collapse px-3 pb-2" id="${uid}">
                        ${miembros.map(per => filaPersona(per)).join('')}
                    </div>
                </div>`;
    }

    /** Slot INDIVIDUAL o DUPLA (dos personas = pareja de dobles). */
    function bloqueIndividual(lado, icono) {
        if (!lado) return '';
        // La model devuelve el objeto de la persona con tipo=INDIVIDUAL,
        // o un array de personas cuando es una dupla agrupada en un slot.
        const personas = Array.isArray(lado) ? lado : [lado];
        const esDupla = personas.length > 1;
        const titulo = esDupla
            ? `${icono} Dupla: ${personas.map(p => esc(p.nombre_completo || p.nombre)).join(' + ')}`
            : `${icono} ${esc(personas[0].nombre_completo || personas[0].nombre || 'Pendiente')}`;
        return `<div class="border rounded p-2 mb-2 bg-white">
                    <span class="fw-semibold">${titulo}</span>
                    ${esDupla ? '' : `<span class="small text-muted ms-1">${personas[0].dni ? 'DNI ' + esc(personas[0].dni) : ''}</span>`}
                    ${personas.map(per => !esDupla ? '' : filaPersona(per)).join('')}
                </div>`;
    }

    function ladoVacio(txt) {
        return `<div class="border rounded p-2 mb-2 bg-white-50 text-muted fst-italic small">${txt}</div>`;
    }

    /** Renderiza ambos lados del partido según lo que devolvió el server. */
    function renderLados(det) {
        const l1 = det.lado_1, l2 = det.lado_2;
        let html = '';
        const uno = !l1 ? ladoVacio('Lado 1 pendiente (se define por clasificación).')
            : (l1.tipo === 'EQUIPO' ? bloqueEquipo(l1, '🛡️') : bloqueIndividual(l1, '👤'));
        const dos = !l2 ? ladoVacio('Lado 2 pendiente (se define por clasificación).')
            : (l2.tipo === 'EQUIPO' ? bloqueEquipo(l2, '🛡️') : bloqueIndividual(l2, '👤'));
        html += uno + dos;
        return html;
    }

    function abrirDetallePartido(idFixture) {
        const body = document.getElementById('fxx_body');
        document.getElementById('fxx_titulo').textContent = 'Cargando…';
        body.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando detalle…</div>';
        modalDetalle.show();

        getJSON('ajax_fixture_detalle/' + idFixture).then(res => {
            if (!res.ok) {
                body.innerHTML = `<div class="alert alert-danger py-2 small mb-0">${esc(res.error || 'No se pudo cargar el detalle.')}${res.detalle ? '<hr><code class="small">' + esc(res.detalle).slice(0, 400) + '</code>' : ''}</div>`;
                return;
            }
            const fx = res.fixture;
            const evFx = estadoEnVivo(fx);
            document.getElementById('fxx_titulo').innerHTML =
                `<i class="bi bi-trophy-fill me-1"></i>${esc(fx.nombre_deporte)} — ${esc(fx.nombre_categoria)}`;
            document.getElementById('fxx_sub').innerHTML =
                `<span class="badge bg-info text-dark me-1">${esc((fx.fase || '').replace('_', ' '))}</span>` +
                `<i class="bi bi-calendar3 me-1"></i>${fechaArma(fx.fecha_competencia)} ${esc((fx.hora_inicio || '').slice(0, 5))}` +
                ` &nbsp;<i class="bi bi-geo-alt me-1"></i>${esc(fx.lugar_nombre || 'Sin lugar')}` +
                ` &nbsp;<span class="badge ${BADGES[evFx.estado] || 'bg-secondary'}" ${evFx.auto ? 'title="Estado calculado automáticamente según la fecha y hora"' : ''}>${esc((evFx.estado || '').replace('_', ' '))}</span>` +
                (evFx.auto ? ' <i class="bi bi-clock-history text-muted small" title="Estado en vivo (por horario), no guardado en la base"></i>' : '');

            let html = '';
            if (res.es_masivo) {
                html += `<div class="alert alert-warning py-2 small"><i class="bi bi-flag me-1"></i>Deporte masivo: compiten todos juntos en esta jornada/largada.</div>`;
                if ((res.todos || []).length) {
                    html += `<h6 class="fw-bold mt-2"><i class="bi bi-person-lines-fill me-1"></i>Competidores inscriptos (${res.todos.length})</h6>`;
                    html += res.todos.map(item =>
                        item.tipo === 'EQUIPO' ? bloqueEquipo(item, '🛡️') : bloqueIndividual({ nombre_completo: item.nombre, dni: item.dni, delegacion: item.delegacion }, '👤')
                    ).join('');
                } else {
                    html += ladoVacio('Todavía no hay competidores inscriptos en esta categoría.');
                }
                if ((res.podio || []).length) {
                    html += `<h6 class="fw-bold mt-3"><i class="bi bi-bar-chart-steps me-1"></i>Orden de llegada cargado</h6>`;
                    html += '<div class="small">' + res.podio.map(p => {
                        const medalla = MEDALLAS[p.posicion - 1] || (p.posicion + 'º');
                        return `<div class="py-1 border-bottom">${medalla} &nbsp; ${esc(nombreSlotDelPodio(fx, p.id))}</div>`;
                    }).join('') + '</div>';
                }
            } else {
                html += `<h6 class="fw-bold mb-2"><i class="bi bi-people-fill me-1"></i>Quiénes participan</h6>`;
                html += renderLados(res);
                if (!res.lado_1 && !res.lado_2) {
                    html += ladoVacio('Este cruce todavía no tiene participantes definidos (espera clasificados de fases anteriores).');
                }
            }
            body.innerHTML = html;
        }).catch(err => {
            body.innerHTML = `<div class="alert alert-danger py-2 small mb-0">${esc(err.message)}</div>`;
        });
    }

    /** Nombre de un id del podio masivo (>0 UTE, <0 inscripción individual). */
    function nombreSlotDelPodio(fx, idSlot) {
        idSlot = parseInt(idSlot, 10);
        if (idSlot > 0) {
            if (fx.id_ute_1 == idSlot) return fx.ute_1_nombre;
            if (fx.id_ute_2 == idSlot) return fx.ute_2_nombre;
            return 'UTE #' + idSlot;
        }
        return 'Inscripto #' + (-idSlot);
    }

    /** Modal "Participantes" del bloque de categoría: todos los inscriptos. */
    function abrirInscriptosCategoria(idCat, nombreCompleto) {
        const body = document.getElementById('fxx_body');
        document.getElementById('fxx_titulo').textContent = nombreCompleto || 'Participantes';
        document.getElementById('fxx_sub').innerHTML = '<span class="text-muted">Todos los inscriptos en esta categoría</span>';
        body.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando…</div>';
        modalDetalle.show();

        getJSON('ajax_inscriptos_categoria/' + idCat).then(res => {
            if (!res.ok) {
                body.innerHTML = `<div class="alert alert-danger py-2 small mb-0">${esc(res.error || 'No se pudo cargar la lista.')}</div>`;
                return;
            }
            const equipos = res.equipos || [];
            const indiv = res.individuales || [];
            if (!equipos.length && !indiv.length) {
                body.innerHTML = ladoVacio('No hay inscriptos en esta categoría todavía.');
                return;
            }
            let html = '';
            if (equipos.length) {
                html += `<h6 class="fw-bold mb-2"><i class="bi bi-shield-fill me-1"></i>Equipos (${equipos.length})</h6>`;
                html += equipos.map(eq => bloqueEquipo({ nombre: eq.nombre, integrantes: eq.integrantes }, '🛡️')).join('');
            }
            if (indiv.length) {
                html += `<h6 class="fw-bold mt-3 mb-2"><i class="bi bi-person-badge-fill me-1"></i>Competidores individuales (${indiv.length})</h6>`;
                html += '<div class="border rounded bg-white p-2">' + indiv.map(per => filaPersona({
                    nombre_completo: per.nombre, dni: per.dni, sexo: per.sexo,
                    fecha_nacimiento: per.fecha_nacimiento, delegacion: per.delegacion
                })).join('') + '</div>';
            }
            body.innerHTML = html;
        }).catch(err => {
            body.innerHTML = `<div class="alert alert-danger py-2 small mb-0">${esc(err.message)}</div>`;
        });
    }

    /* ---------- Carga GENERAL (sin filtro de categoría) ---------- */
    let cargaTodoEnVuelo = null;   // Promise de la última carga (para no pisarse entre sí)
    function cargarTodo() {
        const p = getJSON('ajax_fixture_todo')
            .then(res => {
                if (!res.ok) {
                    lista.innerHTML = '<div class="alert alert-danger py-2 small">' +
                        esc(res.error || 'Error al cargar el fixture.') +
                        (res.detalle ? '<hr><code class="small">' + esc(res.detalle).slice(0, 500) + '</code>' : '') +
                        '</div>';
                    return;
                }
                todosFixtures = res.fixtures || [];

                // Sincronizar los filtros con los datos reales del fixture cargado
                // (y refrescar el botón "Borrar fixture" según haya o no resultados).
                actualizarOpcionesFiltroCategoria();
                actualizarEstadoBtnBorrar();

                // Fallback: si el server no adjuntó las UTEs de cada categoría
                // (p. ej. BD vieja sin la columna resultado), las pedimos por
                // categoría para que el botón "Cargar resultados" siempre aparezca.
                const catsSinUtes = {};
                todosFixtures.forEach(f => {
                    const esMasivo = f.fase === 'JORNADA_UNICA' || f.modalidad_competencia === 'MASIVO_TIEMPO';
                    if (esMasivo && !(f.utes_categoria && f.utes_categoria.length)) {
                        catsSinUtes[f.id_categoria] = true;
                    }
                });
                const idsCats = Object.keys(catsSinUtes);
                if (!idsCats.length) { render(); return; }

                Promise.all(idsCats.map(idCat =>
                    fetch(BASE + '/ajax_fixture_categoria/' + idCat, { cache: 'no-store' })
                        .then(r => r.json())
                        .then(rr => ({ idCat: idCat, utes: rr.ok ? (rr.utes || []) : [] }))
                        .catch(() => ({ idCat: idCat, utes: [] }))
                )).then(results => {
                    results.forEach(r => {
                        todosFixtures.forEach(f => {
                            if (String(f.id_categoria) === String(r.idCat)) f.utes_categoria = r.utes;
                        });
                    });
                    render();
                });
            })
            .catch(err => {
                console.error(err);
                lista.innerHTML = '<div class="alert alert-danger py-2 small">' +
                    'No se pudo cargar el fixture (' + esc(err.message) + '). ' +
                    'Revisá que tengas sesión iniciada como admin y que existan las tablas <code>fixtures</code> / <code>lugares</code>.</div>';
            });
        cargaTodoEnVuelo = p;
        return p;
    }

    /* ---------- UTEs según la categoría elegida DENTRO DEL MODAL ---------- */
    function cargarUtesDelModal(idCategoria, cb) {
        const sel1 = document.getElementById('fx_ute1');
        const sel2 = document.getElementById('fx_ute2');
        if (!idCategoria) {
            sel1.innerHTML = sel2.innerHTML = '<option value="">— Primero elegí la categoría —</option>';
            sel1.disabled = sel2.disabled = true;
            if (cb) cb();
            return;
        }
        // cache: 'no-store' → los equipos de la categoría se piden SIEMPRE
        // frescos al servidor (si venía una respuesta cacheada, el modal de
        // edición mostraba datos viejos y había que hacer F5).
        fetch(BASE + '/ajax_fixture_categoria/' + idCategoria, { cache: 'no-store' })
            .then(r => r.json())
            .then(res => {
                const utes = (res.ok ? res.utes : []);
                [sel1, sel2].forEach(sel => {
                    sel.disabled = false;
                    sel.innerHTML = '<option value="">— Pendaaiente —</option>';
                    utes.forEach(u => {
                        sel.insertAdjacentHTML('beforeend', `<option value="${u.id_ute}">${esc(u.nombre_ute)}</option>`);
                    });
                });
                if (cb) cb(utes);
            });
    }

    // ---------- Desdoblar Deporte -> Categoría dentro del modal ----------
    // Las categorías de cada deporte se cargan por AJAX al seleccionar el
    // deporte (endpoint Inscripciones/ajax_categorias_por_deporte/$id). Se
    // cachean por deporte para no repetir la consulta al abrir el modal.
    // OJO: el cache es SOLO en memoria (CATS_CACHE); usamos 'no-store' en la
    // fetch para que el navegador no devuelva una respuesta HTTP vieja — con
    // una categoría recién creada, eso hacía que el modal no la mostrara hasta
    // apretar F5.
    const CATS_CACHE = {};       // id_deporte -> array de categorías
    const CATS_PETICION = {};    // id_deporte -> Promise en vuelo / resuelta
    const selDeporteModal = document.getElementById('fx_modal_deporte');
    const selCatModal = document.getElementById('fx_modal_categoria');
    // Guard: si el HTML del modal no está en la página, no continuar este bloque.
    if (!selDeporteModal || !selCatModal) { return; }

    function cargarCategoriasDelDeporte(idDeporte) {
        if (!idDeporte) return Promise.resolve([]);
        const key = String(idDeporte);
        if (CATS_PETICION[key]) return CATS_PETICION[key];
        CATS_PETICION[key] = fetch(BASE + '/ajax_categorias_por_deporte/' + key, { cache: 'no-store' })
            .then(r => r.json())
            .then(res => {
                const cats = (res && res.ok) ? (res.categorias || []) : [];
                CATS_CACHE[key] = cats;
                return cats;
            })
            .catch(() => { CATS_CACHE[key] = []; return []; });
        return CATS_PETICION[key];
    }

    // El navegador valida "required" en ambos selects al enviar el form por
    // Enter; cuando se resetea o se abre el modal de NUEVO partido hay que
    // quitarle el required al select de categoría (se habilita al elegir deporte).
    function setRequiredCategoria(on) {
        if (on) selCatModal.setAttribute('required', 'required');
        else selCatModal.removeAttribute('required');
    }

    function poblarCategoriasDelModal(idDeporte, seleccionarId) {
        selCatModal.innerHTML = '';
        if (!idDeporte) {
            selCatModal.disabled = true;
            setRequiredCategoria(false);
            selCatModal.innerHTML = '<option value="">— Primero elegí un deporte —</option>';
            return;
        }
        // Mientras llega la respuesta, el select muestra "Cargando…" y queda
        // deshabilitado; al volver el AJAX se habilita recién ahí.
        selCatModal.disabled = true;
        setRequiredCategoria(false);
        selCatModal.innerHTML = '<option value="">Cargando categorías…</option>';
        cargarCategoriasDelDeporte(idDeporte).then(cats => {
            // Si el usuario cambió de deporte mientras venía la respuesta, descartar.
            if (String(selDeporteModal.value) !== String(idDeporte)) return;
            selCatModal.innerHTML = '<option value="">— Elegí una categoría —</option>';
            cats.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id_categoria;
                opt.dataset.modalidad = c.modalidad_competencia || '';
                opt.textContent = `${c.nombre_categoria} (${c.genero})`;
                selCatModal.appendChild(opt);
            });
            if (cats.length) {
                selCatModal.disabled = false;
                setRequiredCategoria(true);
                if (seleccionarId !== undefined && seleccionarId !== null && seleccionarId !== '') {
                    selCatModal.value = seleccionarId;
                }
            } else {
                selCatModal.innerHTML = '<option value="">— Este deporte no tiene categorías —</option>';
            }
        });
    }

    // Al elegir el deporte, se filtran las categorías de ese deporte.
    selDeporteModal.addEventListener('change', function () {
        poblarCategoriasDelModal(this.value);
        document.getElementById('fx_cat_hidden').value = '';
        cargarUtesDelModal('');
    });

    // Si es un deporte MASIVO, la fase sugerida es JORNADA_UNICA (solo al crear).
    selCatModal.addEventListener('change', function () {
        const opt = this.selectedOptions[0];
        const esMasivo = opt && opt.dataset.modalidad === 'MASIVO_TIEMPO';
        const faseSel = document.getElementById('fx_fase');
        if (esMasivo && !document.getElementById('fx_id_fixture').value) {
            faseSel.value = 'JORNADA_UNICA';
        }
    });

    // Al cambiar la categoría dentro del modal, recargar los equipos
    document.getElementById('fx_modal_categoria').addEventListener('change', function () {
        document.getElementById('fx_cat_hidden').value = this.value;
        cargarUtesDelModal(this.value);
    });

    /* ---------- Modal ---------- */
    function abrirModal(f) {
        document.getElementById('form_partido').reset();
        document.getElementById('fx_id_fixture').value = '';

        const selCat = document.getElementById('fx_modal_categoria');

        if (f) {
            // Edición: deporte/categoría fijos al partido original
            document.getElementById('fx_id_fixture').value = f.id_fixture;
            selDeporteModal.value = f.id_deporte || '';
            // Las categorías llegan por AJAX; fx_cat_hidden se setea igual acá
            // para que el guardado funcione aunque todavía no haya vuelto la
            // respuesta (la categoría del partido original nunca cambia).
            document.getElementById('fx_cat_hidden').value = f.id_categoria;
            poblarCategoriasDelModal(f.id_deporte, f.id_categoria);
            document.getElementById('fx_nombre_prueba').value = f.nombre_prueba || '';
            document.getElementById('fx_fase').value = f.fase;
            document.getElementById('fx_numero_fecha').value = f.numero_fecha;
            document.getElementById('fx_lugar').value = f.id_lugar || '';
            document.getElementById('fx_estado').value = f.estado;
            document.getElementById('fx_fecha').value = f.fecha_competencia;
            document.getElementById('fx_hora_ini').value = (f.hora_inicio || '').slice(0, 5);
            document.getElementById('fx_hora_fin').value = (f.hora_fin || '').slice(0, 5);
            cargarUtesDelModal(f.id_categoria, () => {
                document.getElementById('fx_ute1').value = f.id_ute_1 || '';
                document.getElementById('fx_ute2').value = f.id_ute_2 || '';
            });
        } else {
            // Nuevo: el deporte y la categoría se eligen ACÁ ADENTRO del modal
            selDeporteModal.value = '';
            poblarCategoriasDelModal('');
            document.getElementById('fx_cat_hidden').value = '';
            cargarUtesDelModal('');
        }
        modalPartido.show();
    }

    /* ---------- Resultados de deportes masivos (JORNADA_UNICA) ---------- */
    let jornadaActual = null;      // fixture que se está cargando
    let ordenLlegada = [];         // ids de UTE en el orden elegido

    

    function renderOrden() {
        const cont = document.getElementById('fxm_lista');
        const utes = {};
        (jornadaActual.utes_categoria || []).forEach(u => { utes[u.id_ute] = u.nombre_ute; });

        // Cursor de próximas llegadas: todo lo que esté ANTES de este índice
        // cuenta como "llegó"; lo demás queda en gris como "pendiente".
        if (!jornadaActual._limite) jornadaActual._limite = 0;
        const limite = jornadaActual._limite;

        let html = '';
        ordenLlegada.forEach((id, i) => {
            const asignado = i < limite;
            const pos = MEDALLAS[i] || (i + 1) + 'º';
            html += `<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center fx-orden ${asignado ? '' : 'text-muted'}" data-pos="${i}">
                        <span>${asignado ? pos : '•'} &nbsp; ${esc(utes[id] || ('UTE #' + id))}</span>
                        <span class="badge ${!asignado ? 'bg-light text-dark border' : (i < 3 ? 'bg-warning text-dark' : 'bg-success')}">${!asignado ? 'Sin llegar — tocá para asignar' : (i === 0 ? 'Ganador/a' : 'Posición ' + (i + 1))}</span>
                     </button>`;
        });
        cont.innerHTML = html;

        cont.querySelectorAll('.fx-orden').forEach(b => b.addEventListener('click', () => {
            // Tocar un equipo lo coloca en la PRIMERA posición libre: si ya está
            // asignado (antes del cursor de próximas llegadas) se saca de ahí y
            // se reacomoda; si no, entra justo después de los últimos cargados.
            const pos = parseInt(b.dataset.pos, 10);
            const id = ordenLlegada[pos];
            const limite = jornadaActual._limite || ordenLlegada.length;
            if (pos < limite) {
                // Ya estaba asignado: tocarlo lo devuelve al final (sin asignar).
                ordenLlegada.splice(pos, 1);
                ordenLlegada.push(id);
            } else {
                // Todavía no llegó: lo ponemos en la primera posición vacante.
                ordenLlegada.splice(pos, 1);
                ordenLlegada.splice(limite, 0, id);
                jornadaActual._limite = limite + 1;
            }
            renderOrden();
        }));
    }

    document.getElementById('fxm_reset').addEventListener('click', () => {
        if (jornadaActual) {
            ordenLlegada = (jornadaActual.utes_categoria || []).map(u => String(u.id_ute));
            jornadaActual._limite = 0;   // nada asignado
            renderOrden();
        }
    });

    document.getElementById('fxm_guardar').addEventListener('click', () => {
        if (!jornadaActual) return;
        // Enviar SOLO los equipos que "llegaron" (antes del cursor), en orden.
        const limite = jornadaActual._limite || 0;
        if (limite === 0) {
            mensaje('Todavía no elegiste ningún equipo: tocá los nombres en el orden de llegada.', 'warning');
            return;
        }
        const datos = { id_fixture: jornadaActual.id_fixture };
        ordenLlegada.slice(0, limite).forEach((id, i) => { datos['ute_ids[' + i + ']'] = id; });

        post('ajax_resultado_masivo', datos).then(res => {
            if (res.ok) {
                modalMasivo.hide();
                mensaje(res.mensaje, 'success');
                cargarTodo();
            } else {
                mensaje(res.error || 'No se pudo guardar el resultado.', 'danger');
            }
        }).catch(err => mensaje(err.message, 'danger'));
    });

    document.getElementById('fx_btn_guardar').addEventListener('click', () => {
        const datos = Object.fromEntries(new FormData(document.getElementById('form_partido')).entries());
        if (!selDeporteModal.value) {
            mensaje('Elegí el deporte dentro del formulario.', 'warning');
            return;
        }
        if (!datos.id_categoria) {
            mensaje('Elegí la categoría del deporte dentro del formulario.', 'warning');
            return;
        }
        if (!datos.nombre_prueba || !datos.fecha_competencia || !datos.hora_inicio || !datos.hora_fin) {
            mensaje('Completá nombre, fecha y horarios.', 'warning');
            return;
        }
        post('ajax_guardar_partido', datos).then(res => {
            if (res.ok) {
                modalPartido.hide();
                mensaje(res.mensaje, 'success');
                cargarTodo();
            } else {
                mensaje(res.error, 'danger');
            }
        });
    });

    /* ---------- Selector de DÍA: botones solo para los días del calendario oficial ---------- */
    function diasDisponibles() {
        // Días del calendario oficial + cualquier día extra que tenga partidos cargados
        const dias = new Set(diasCalendario());
        todosFixtures.forEach(f => {
            const d = (f.fecha_competencia || '').slice(0, 10);
            if (d) dias.add(d);
        });
        return Array.from(dias).sort();
    }

    function renderBotonesDias(fixturesDelDia) {
        const resumenEl = document.getElementById('fx_dia_resumen');
        contenedorDias.innerHTML = '';

        // Botón "Todos" (vista general del fixture cargado)
        const btnTodos = document.createElement('button');
        btnTodos.type = 'button';
        btnTodos.className = 'btn btn-sm ' + (diaActual ? 'btn-outline-primary' : 'btn-primary');
        btnTodos.innerHTML = 'Todos <span class="badge ' + (diaActual ? 'bg-light text-muted border' : 'bg-white text-primary') + ' ms-1">' + todosFixtures.length + '</span>';
        btnTodos.title = 'Ver todo el fixture cargado (sin filtro por día). El CSV de arriba descarga en ese caso TODO el fixture.';
        btnTodos.addEventListener('click', () => {
            diaActual = null;
            render();
        });
        contenedorDias.appendChild(btnTodos);

        diasDisponibles().forEach(d => {
            const p = d.split('-');
            const fecha = new Date(+p[0], +p[1] - 1, +p[2]);
            const nombreCorto = fecha.toLocaleDateString('es-AR', { weekday: 'short' }).replace('.', '');
            const cantidad = d === diaActual
                ? fixturesDelDia.length
                : todosFixtures.filter(f => (f.fecha_competencia || '').slice(0, 10) === d).length;
            const esHoy = d === hoyISO();

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm ' + (d === diaActual ? 'btn-primary' : 'btn-outline-primary');
            btn.innerHTML = esc(nombreCorto.charAt(0).toUpperCase() + nombreCorto.slice(1)) +
                ' ' + p[2] + '/' + p[1] +
                (esHoy ? ' <i class="bi bi-circle-fill" style="font-size:.4rem;" title="Hoy"></i>' : '') +
                (cantidad ? ` <span class="badge ${d === diaActual ? 'bg-white text-primary' : 'bg-light text-muted border'} ms-1">${cantidad}</span>` : '');
            btn.title = fechaArma(d) + (cantidad ? ` — ${cantidad} partido/s` : ' — sin partidos');
            btn.addEventListener('click', () => {
                if (diaActual !== d) {
                    diaActual = d;
                    render();
                }
            });
            contenedorDias.appendChild(btn);
        });

        // Los botones de PDF descargan SIEMPRE todos los días (sin filtro):
        // cuadro = columnas días / filas franjas; listado = una fila por partido.
        const btnPdf = document.getElementById('fx_btn_pdf');
        if (btnPdf) {
            btnPdf.href = BASE + '/descargar_pdf_fixture';
            btnPdf.title = 'PDF con el cuadro del fixture de TODOS los días (columnas = días, filas = rangos horarios)';
        }
        const btnPdfLista = document.getElementById('fx_btn_pdf_lista');
        if (btnPdfLista) {
            btnPdfLista.href = BASE + '/descargar_pdf_fixture?formato=lista';
            btnPdfLista.title = 'PDF con el listado ordenado del fixture de TODOS los días (Deporte → Categoría → Fecha → Hora)';
        }

        // Selector de deporte junto al botón de descarga: si se elige uno, el PDF
        // sale FILTRADO solo con ese deporte (mismo formato que el general).
        const selDeporteReporte = document.getElementById('fx_deporte_reporte');
        function actualizarBtnPdf() {
            if (!btnPdf || !selDeporteReporte) return;
            const idDep = selDeporteReporte.value;
            const nombreDep = idDep
                ? selDeporteReporte.options[selDeporteReporte.selectedIndex].text.trim()
                : '';
            btnPdf.href = BASE + '/descargar_pdf_fixture' + (idDep ? '?deporte=' + encodeURIComponent(idDep) : '');
            const txt = document.getElementById('fx_btn_pdf_txt');
            if (txt) {
                txt.textContent = idDep
                    ? 'Descargar fixture de ' + nombreDep
                    : 'Descargar reporte de fixture';
            }
            btnPdf.title = idDep
                ? 'PDF con el cuadro del fixture SOLO de ' + nombreDep + ' (todos los días)'
                : 'PDF con el cuadro del fixture de TODOS los deportes y TODOS los días';
        }
        if (selDeporteReporte) {
            selDeporteReporte.addEventListener('change', actualizarBtnPdf);
            actualizarBtnPdf();
        }

        // Resumen del día elegido
        let resumen = '';
        if (diaActual) {
            const p = diaActual.split('-');
            const nombreDia = new Date(+p[0], +p[1] - 1, +p[2]).toLocaleDateString('es-AR', { weekday: 'long' });
            const deportes = new Set(fixturesDelDia.map(f => f.nombre_deporte)).size;
            resumen = `<i class="bi bi-calendar-check me-1"></i>${esc(nombreDia)} ${fechaArma(diaActual)} — ` +
                `${fixturesDelDia.length} partido/s · ${deportes} deporte/s`;
            if (todosFixtures.length) {
                resumen += ` <span class="text-muted">(total cargado: ${todosFixtures.length})</span>`;
            }
        }
        resumenEl.innerHTML = resumen;
    }

    /* ============================================================
     *  FILTROS DEPORTES / CATEGORÍAS (solo afectan a la visualización)
     * ============================================================ */
    function nombreDeporteOpcion(idDep) {
        const opt = Array.from(selFiltroDeporte.options).find(o => o.value === String(idDep));
        return opt ? opt.textContent.trim() : ('Deporte #' + idDep);
    }

    function actualizarOpcionesFiltroCategoria() {
        const dep = selFiltroDeporte.value;
        const actual = selFiltroCategoria.value;

        // Las categorías disponibles salen del fixture cargado (con sus nombres reales).
        const mapa = {};
        todosFixtures.forEach(f => {
            if (!f.id_categoria) return;
            if (dep && String(f.id_deporte) !== String(dep)) return;
            mapa[f.id_categoria] = `${esc(f.nombre_deporte)} — ${esc(f.nombre_categoria)} (${esc(f.genero_categoria || '')})`;
        });

        selFiltroCategoria.disabled = !dep && !Object.keys(mapa).length;
        selFiltroCategoria.innerHTML = '<option value="">— Todas las categorías —</option>';
        Object.keys(mapa).sort((a, b) => mapa[a].localeCompare(mapa[b])).forEach(id => {
            selFiltroCategoria.insertAdjacentHTML('beforeend', `<option value="${esc(id)}">${mapa[id]}</option>`);
        });

        // Mantener la selección si sigue disponible dentro del filtro de deporte
        if (actual && mapa[actual]) selFiltroCategoria.value = actual;
        else selFiltroCategoria.value = '';
    }

    function tieneFiltros() {
        return !!(selFiltroDeporte.value || selFiltroCategoria.value);
    }

    function actualizarEstadoBtnBorrar() {
        const dep = selFiltroDeporte.value;
        const cat = selFiltroCategoria.value;
        let habilitado = false;
        if (cat) {
            habilitado = todosFixtures.some(f => String(f.id_categoria) === String(cat));
        } else if (dep) {
            habilitado = todosFixtures.some(f => String(f.id_deporte) === String(dep));
        }
        btnBorrarTodo.disabled = !habilitado;
        btnBorrarTodo.title = habilitado
            ? 'Borra el fixture del deporte/categoría que estés filtrando'
            : 'Elegí un deporte o una categoría en los filtros para poder borrar su fixture';
        btnLimpiarFiltros.classList.toggle('d-none', !tieneFiltros());
    }

    function renderChipsFiltros(cantidad) {
        if (!tieneFiltros()) { chipsFiltros.innerHTML = ''; return; }
        const dep = selFiltroDeporte.value;
        const cat = selFiltroCategoria.value;
        let html = '<span class="fx-chip fx-chip-info"><i class="bi bi-funnel-fill me-1"></i>Filtrando:' +
            (dep ? ` <strong>${esc(nombreDeporteOpcion(dep))}</strong>` : '') +
            (cat ? ` <i class="bi bi-chevron-right mx-1 small"></i><strong>${esc(selFiltroCategoria.options[selFiltroCategoria.selectedIndex].text)}</strong>` : '') +
            ` <span class="badge rounded-pill ms-1">${cantidad}</span></span>`;
        html += '<button type="button" class="btn btn-sm btn-link text-danger text-decoration-none fx-chip-limpiar" id="fx_chip_limpiar"><i class="bi bi-x-circle me-1"></i>quitar filtros</button>';
        chipsFiltros.innerHTML = html;
        const btnChip = document.getElementById('fx_chip_limpiar');
        if (btnChip) btnChip.addEventListener('click', limpiarFiltros);
    }

    function limpiarFiltros() {
        selFiltroDeporte.value = '';
        selFiltroCategoria.value = '';
        actualizarOpcionesFiltroCategoria();
        actualizarEstadoBtnBorrar();
        render();
    }

    selFiltroDeporte.addEventListener('change', () => {
        selFiltroCategoria.value = '';
        actualizarOpcionesFiltroCategoria();
        actualizarEstadoBtnBorrar();
        render();
    });

    selFiltroCategoria.addEventListener('change', () => {
        actualizarEstadoBtnBorrar();
        render();
    });

    btnLimpiarFiltros.addEventListener('click', limpiarFiltros);

    /* ---------- Acciones ---------- */
    btnNuevo.addEventListener('click', () => abrirModal(null));

    btnBorrarTodo.addEventListener('click', () => {
        const cat = selFiltroCategoria.value;
        const dep = selFiltroDeporte.value;
        if (cat) {
            if (!confirm('¿Seguro? Se eliminarán TODOS los partidos de la categoría filtrada.')) return;
            post('ajax_eliminar_fixture', { id_categoria: cat }).then(res => {
                mensaje(res.mensaje, 'success');
                cargarTodo();
            });
        } else if (dep) {
            const ids = Array.from(new Set(
                todosFixtures.filter(f => String(f.id_deporte) === String(dep)).map(f => f.id_categoria)
            ));
            if (!ids.length) { mensaje('No hay fixture para borrar en ese deporte.', 'warning'); return; }
            if (!confirm(`¿Seguro? Se eliminarán TODOS los partidos de ${ids.length} categoría/s de ${nombreDeporteOpcion(dep)}.`)) return;
            Promise.all(ids.map(idCat => post('ajax_eliminar_fixture', { id_categoria: idCat })))
                .then(() => { mensaje('Fixture eliminado del deporte.', 'success'); cargarTodo(); })
                .catch(err => mensaje(err.message, 'danger'));
        }
    });

    /* Al iniciar, mostrar TODO el fixture ya cargado */
    cargarTodo();
})();
</script>

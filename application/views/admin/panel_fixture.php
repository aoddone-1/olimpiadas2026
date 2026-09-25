<!-- PANEL FIXTURE -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column gap-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center">
                <i class="bi bi-calendar3 me-2">
                <span></i>Gestión de Fixture</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <a id="fx_btn_csv" href="<?= base_url('Inscripciones/descargar_csv_fixture') ?>"
                    class="btn btn-lg btn-success" title="Cuadro del fixture: columnas = días, filas = rangos horarios">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                </a>
                <a id="fx_btn_csv_lista" href="<?= base_url('Inscripciones/descargar_csv_fixture?lista=1') ?>"
                    class="btn btn-lg btn-secondary" title="Listado tradicional: una fila por partido (Deporte → Categoría → Fecha → Hora)">
                    <i class="bi bi-list-ul me-1"></i>
                </a>
            </div>
        </div>
        
        
        
    </div>
    <div class="card-body">
        <!-- Barra de acciones: los selectores son solo para generar/borrar, NO para ver -->
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-3">
                <label  class="form-label small fw-semibold mb-1"><i class="bi bi-trophy-fill text-danger me-1"></i>Deporte</label>
                <select id="fx_deporte" class="form-select">
                    <option value="">— Todos los deportes —</option>
                    <?php foreach ($deportes_fixture as $dep): ?>
                        <option value="<?= $dep['id_deporte'] ?>">
                            <?= htmlspecialchars($dep['nombre_deporte']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label  class="form-label small fw-semibold mb-1"><i class="bi bi-layers-fill text-danger me-1"></i>Categoría (solo para generar / borrar fixture — el listado se ve por día abajo)</label>
                <select id="fx_categoria" class="form-select">
                    <option value="">— Seleccioná una categoría —</option>
                    <?php foreach ($categorias_fixture as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>"
                                data-id-deporte="<?= $cat['id_deporte'] ?>"
                                data-deporte="<?= htmlspecialchars($cat['nombre_deporte']) ?>"
                                data-categoria="<?= htmlspecialchars($cat['nombre_categoria']) ?>"
                                data-modalidad="<?= $cat['modalidad_competencia'] ?>"
                                data-duracion="<?= $cat['tipo_duracion'] ?>">
                            <?= htmlspecialchars($cat['nombre_deporte']) ?> — <?= htmlspecialchars($cat['nombre_categoria']) ?>
                            (<?= htmlspecialchars($cat['genero']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold">Acciones</label>
                <div class="d-flex flex-wrap gap-2">
                    <button id="fx_btn_generar" class="btn btn-primary d-inline-flex align-items-center" disabled>
                        <i class="bi bi-magic me-2"></i>Generar fixture
                    </button>
                    <button id="fx_btn_nuevo" class="btn  btn-lg  btn-primary d-inline-flex align-items-center">
                        <i class="bi bi-plus-circle me-1"></i>
                    </button>
                    <button id="fx_btn_borrar_todo" class="btn  btn-lg  btn-danger" disabled title="Borra el fixture de la categoría seleccionada">
                        <i class="bi bi-trash me-1"></i>
                    </button>
                    
                    
                </div>
            </div>
        </div>

        <!-- Info de la categoría seleccionada -->
        <div id="fx_info" class="alert alert-info py-2 small d-none"></div>

        <!-- Toast de mensajes -->
        <div id="fx_mensaje" class="alert d-none"></div>

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
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Deporte / Categoría *</label>
                        <select id="fx_modal_categoria" class="form-select" required>
                            <option value="">— Elegí una categoría —</option>
                            <?php foreach ($categorias_fixture as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>"
                                        data-modalidad="<?= $cat['modalidad_competencia'] ?>">
                                    <?= htmlspecialchars($cat['nombre_deporte']) ?> — <?= htmlspecialchars($cat['nombre_categoria']) ?>
                                    (<?= htmlspecialchars($cat['genero']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="id_categoria" id="fx_cat_hidden">
                    </div>

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

    const selDeporte = document.getElementById('fx_deporte');
    const selCategoria = document.getElementById('fx_categoria');
    const btnGenerar = document.getElementById('fx_btn_generar');
    const btnNuevo = document.getElementById('fx_btn_nuevo');
    const btnBorrarTodo = document.getElementById('fx_btn_borrar_todo');
    const infoBox = document.getElementById('fx_info');
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
        PROGRAMADO: 'bg-secondary', EN_CURSO: 'bg-warning text-dark',
        FINALIZADO: 'bg-success', SUSPENDIDO: 'bg-danger'
    };

    function esc(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function mensaje(texto, tipo) {
        msgBox.className = 'alert alert-' + tipo;
        msgBox.textContent = texto;
        setTimeout(() => msgBox.classList.add('d-none'), 4000);
    }

    function post(url, datos) {
        const form = new FormData();
        Object.keys(datos).forEach(k => form.append(k, datos[k]));
        return fetch(BASE + '/' + url, { method: 'POST', body: form })
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
        // Se lee el texto primero: si CodeIgniter devolvió un error fatal (HTML),
        // lo detectamos acá en vez de romper con "Unexpected token <" al parsear JSON.
        return fetch(BASE + '/' + url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
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

    /* ---------- Render por DÍA: muestra solo lo que se juega en la fecha elegida,
                    agrupado por Deporte → Categoría → Jornada ---------- */
    function render() {
        const dia = diaActual;

        // Filtrar el fixture del día seleccionado
        const fixturesDelDia = dia
            ? todosFixtures.filter(f => (f.fecha_competencia || '').slice(0, 10) === dia)
            : todosFixtures.slice();

        // Botones de día + resumen: "sábado 26/09/2026 — 12 partidos · 4 deportes"
        renderBotonesDias(fixturesDelDia);

        if (!todosFixtures.length) {
            lista.innerHTML = '<div class="text-center text-muted py-4">' +
                '<i class="bi bi-calendar-x fs-1 d-block mb-2"></i>' +
                'Todavía no hay ningún partido cargado. Generá un fixture automático o creá uno manual con "+ Partido manual".</div>';
            return;
        }

        if (!fixturesDelDia.length) {
            lista.innerHTML = '<div class="text-center text-muted py-4">' +
                '<i class="bi bi-calendar-day fs-1 d-block mb-2"></i>' +
                'No hay competencia programada para ' + (dia ? esc(fechaArma(dia)) : 'la fecha seleccionada') + '.' +
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
                <div class="card-header bg-primary bg-gradient py-2">
                    <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center gap-2 text-white fw-bold"
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
                        <button type="button" class="btn btn-sm btn-outline-primary fx-cat-participantes"
                                data-cat="${idCat}" data-nombre="${esc(deporte + ' — ' + categoria)}" title="Ver todos los inscriptos de esta categoría">
                            <i class="bi bi-person-lines-fill me-1"></i>Participantes
                        </button>
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
                    const badge = BADGES[f.estado] || 'bg-secondary';

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
                            <span class="badge ${badge}">${esc(f.estado.replace('_',' '))}</span>
                            
                            ${(!esMasivo && f.id_ute_1 && f.id_ute_2 && f.estado !== 'FINALIZADO') ? `
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success fx-ganador" data-id="${f.id_fixture}" data-ute="${f.id_ute_1}" title="Gana: ${esc(f.ute_1_nombre)}">🏆 ${esc(f.ute_1_nombre).slice(0, 14)}</button>
                                    <button class="btn btn-outline-success fx-ganador" data-id="${f.id_fixture}" data-ute="${f.id_ute_2}" title="Gana: ${esc(f.ute_2_nombre)}">🏆 ${esc(f.ute_2_nombre).slice(0, 14)}</button>
                                </div>` : ''}
                            <button class="btn btn-sm btn-outline-primary fx-detalle" data-id="${f.id_fixture}" title="Ver quiénes participan">
                                <i class="bi bi-search"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary fx-editar" data-id="${f.id_fixture}" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger fx-borrar" data-id="${f.id_fixture}" title="Eliminar">
                                <i class="bi bi-x-lg"></i>
                            </button>
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
        lista.querySelectorAll('.fx-ganador').forEach(b => b.addEventListener('click', () => {
            if (!confirm('¿Confirmás el ganador y que clasifica a la siguiente fase?')) return;
            post('ajax_resultado_partido', { id_fixture: b.dataset.id, id_ganador: b.dataset.ute })
                .then(res => {
                    mensaje(res.ok ? res.mensaje : res.error, res.ok ? 'success' : 'danger');
                    cargarTodo();
                });
        }));
        lista.querySelectorAll('.fx-editar').forEach(b => b.addEventListener('click', () => {
            const f = todosFixtures.find(x => x.id_fixture == b.dataset.id);
            abrirModal(f);
        }));
        lista.querySelectorAll('.fx-borrar').forEach(b => b.addEventListener('click', () => {
            if (!confirm('¿Eliminar este partido?')) return;
            post('ajax_eliminar_partido', { id_fixture: b.dataset.id }).then(res => {
                mensaje(res.mensaje, 'success');
                cargarTodo();
            });
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
            document.getElementById('fxx_titulo').innerHTML =
                `<i class="bi bi-trophy-fill me-1"></i>${esc(fx.nombre_deporte)} — ${esc(fx.nombre_categoria)}`;
            document.getElementById('fxx_sub').innerHTML =
                `<span class="badge bg-info text-dark me-1">${esc((fx.fase || '').replace('_', ' '))}</span>` +
                `<i class="bi bi-calendar3 me-1"></i>${fechaArma(fx.fecha_competencia)} ${esc((fx.hora_inicio || '').slice(0, 5))}` +
                ` &nbsp;<i class="bi bi-geo-alt me-1"></i>${esc(fx.lugar_nombre || 'Sin lugar')}` +
                ` &nbsp;<span class="badge ${BADGES[fx.estado] || 'bg-secondary'}">${esc((fx.estado || '').replace('_', ' '))}</span>`;

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
    function cargarTodo() {
        getJSON('ajax_fixture_todo')
            .then(res => {
                if (!res.ok) {
                    lista.innerHTML = '<div class="alert alert-danger py-2 small">' +
                        esc(res.error || 'Error al cargar el fixture.') +
                        (res.detalle ? '<hr><code class="small">' + esc(res.detalle).slice(0, 500) + '</code>' : '') +
                        '</div>';
                    return;
                }
                todosFixtures = res.fixtures || [];

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
                    fetch(BASE + '/ajax_fixture_categoria/' + idCat)
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
        fetch(BASE + '/ajax_fixture_categoria/' + idCategoria)
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
            // Edición: categoría fija al partido original
            document.getElementById('fx_id_fixture').value = f.id_fixture;
            selCat.value = f.id_categoria;
            document.getElementById('fx_cat_hidden').value = f.id_categoria;
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
            // Nuevo: la categoría se elige ACÁ ADENTRO del modal
            selCat.value = '';
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
        if (!datos.id_categoria) {
            mensaje('Elegí la categoría/deporte dentro del formulario.', 'warning');
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

        // El botón de CSV (cuadro) sigue al día seleccionado: descarga el cuadro
        // solo de ese día (?dia=YYYY-MM-DD). El de listado usa ?lista=1.
        const btnCsv = document.getElementById('fx_btn_csv');
        if (btnCsv && diaActual) {
            btnCsv.href = BASE + '/descargar_csv_fixture?dia=' + diaActual;
            btnCsv.title = 'Cuadro del fixture para el ' + fechaArma(diaActual) +
                ' (columnas = días, filas = rangos horarios)';
        }
        const btnCsvLista = document.getElementById('fx_btn_csv_lista');
        if (btnCsvLista && diaActual) {
            btnCsvLista.href = BASE + '/descargar_csv_fixture?dia=' + diaActual + '&lista=1';
            btnCsvLista.title = 'Listado ordenado del fixture para el ' + fechaArma(diaActual) +
                ' (Deporte → Categoría → Fecha → Hora)';
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

    /* ---------- Filtro Deporte → Categoría ---------- */
    // Al elegir un deporte, la lista de categorías se reduce a las de ese deporte.
    const todasLasCategorias = Array.from(selCategoria.options)
        .filter(o => o.value !== '')
        .map(o => ({
            id: o.value,
            idDeporte: o.dataset.idDeporte,
            html: o.innerHTML,
            dataset: Object.assign({}, o.dataset)
        }));

    function filtrarCategoriasPorDeporte() {
        const dep = selDeporte.value;
        const actual = selCategoria.value;

        selCategoria.innerHTML = '<option value="">— Seleccioná una categoría —</option>';

        todasLasCategorias.forEach(c => {
            if (dep && c.idDeporte !== dep) return;
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.innerHTML = c.html;
            Object.keys(c.dataset).forEach(k => opt.dataset[k] = c.dataset[k]);
            selCategoria.appendChild(opt);
        });

        // Mantener la selección si sigue disponible dentro del nuevo filtro
        selCategoria.value = actual;
        if (!selCategoria.value) {
            infoBox.classList.add('d-none');
            btnGenerar.disabled = true;
            btnBorrarTodo.disabled = true;
        }
    }

    selDeporte.addEventListener('change', filtrarCategoriasPorDeporte);

    /* ---------- Acciones sobre la categoría del selector (solo generar/borrar) ---------- */
    selCategoria.addEventListener('change', () => {
        const opt = selCategoria.selectedOptions[0];
        const activo = !!selCategoria.value;
        btnGenerar.disabled = !activo;
        btnBorrarTodo.disabled = !activo;

        if (activo) {
            infoBox.classList.remove('d-none');
            const tipo = opt.dataset.modalidad === 'MASIVO_TIEMPO'
                ? 'Deporte MASIVO de un solo día → se genera una única jornada (largada).'
                : (opt.dataset.duracion === 'UNICO_DIA'
                    ? 'Deporte de enfrentamiento en jornada única → eliminatoria concentrada en 1 día.'
                    : 'Deporte MULTIDIA de enfrentamiento → se genera bracket de eliminatoria por fechas.');
            infoBox.innerHTML = `<strong>${esc(opt.dataset.deporte)} — ${esc(opt.dataset.categoria)}</strong><br>${tipo}`;
        } else {
            infoBox.classList.add('d-none');
        }
    });

    btnGenerar.addEventListener('click', () => {
        if (!confirm('Se generará el fixture automáticamente con las UTEs existentes (cruces aleatorios). ¿Continuar?')) return;
        post('ajax_generar_fixture', { id_categoria: selCategoria.value }).then(res => {
            mensaje(res.ok ? res.mensaje : res.error, res.ok ? 'success' : 'danger');
            if (res.ok) cargarTodo();
        });
    });

    btnNuevo.addEventListener('click', () => abrirModal(null));

    btnBorrarTodo.addEventListener('click', () => {
        if (!confirm('¿Seguro? Se eliminarán TODOS los partidos de esta categoría.')) return;
        post('ajax_eliminar_fixture', { id_categoria: selCategoria.value }).then(res => {
            mensaje(res.mensaje, 'success');
            cargarTodo();
        });
    });

    /* Al iniciar, mostrar TODO el fixture ya cargado */
    cargarTodo();
})();
</script>

<!-- PANEL RESULTADOS -->
<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="card-title fw-bold mb-3">
            <i class="bi bi-trophy-fill me-2 text-warning"></i>Carga de Resultados
        </h5>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <!-- Filtros al estilo UTEs/Equipos -->
            <div class="d-flex flex-wrap gap-2 align-items-end" id="rs_filtros">
                <div>
                    <label class="form-label small fw-bold mb-1"><i class="bi bi-trophy-fill text-warning me-1"></i>Deporte</label>
                    <select id="rs_filtro_deporte" class="form-select form-select-sm" style="min-width:180px">
                        <option value="">Todos los Deportes</option>
                        <?php
                        $deportesVistos = [];
                        foreach ($categorias_fixture as $cat):
                            if (!in_array($cat['nombre_deporte'], $deportesVistos)):
                                $deportesVistos[] = $cat['nombre_deporte'];
                        ?>
                            <option value="<?= htmlspecialchars($cat['nombre_deporte']) ?>"><?= htmlspecialchars($cat['nombre_deporte']) ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-bold mb-1"><i class="bi bi-layers-fill text-warning me-1"></i>Categoría</label>
                    <select id="rs_filtro_categoria" class="form-select form-select-sm" style="min-width:230px">
                        <option value="" data-deporte="">Todas las Categorías</option>
                        <?php foreach ($categorias_fixture as $cat): ?>
                            <option value="<?= (int) $cat['id_categoria'] ?>"
                                    data-deporte="<?= htmlspecialchars($cat['nombre_deporte']) ?>">
                                <?= htmlspecialchars($cat['nombre_deporte']) ?> — <?= htmlspecialchars($cat['nombre_categoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button id="rs_btn_limpiar" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle-fill me-1"></i>Limpiar Filtros
                </button>
            </div>

            <button id="rs_btn_nuevo" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Cargar resultado
            </button>
        </div>

        <div id="rs_mensaje" class="alert d-none"></div>

        <!-- Listado general de resultados cargados -->
        <div id="rs_lista"></div>
    </div>
</div>

<!-- MODAL: cargar resultado -->
<div class="modal fade" id="modalResultado" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-clipboard2-check me-2"></i>Cargar Resultado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form_resultado">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Deporte / Categoría *</label>
                            <select id="rs_modal_categoria" name="id_categoria" class="form-select" required>
                                <option value="">— Elegí una categoría —</option>
                                <?php foreach ($categorias_fixture as $cat): ?>
                                    <option value="<?= $cat['id_categoria'] ?>"
                                            data-deporte="<?= htmlspecialchars($cat['nombre_deporte']) ?>"
                                            data-modalidad="<?= $cat['modalidad_competencia'] ?>">
                                        <?= htmlspecialchars($cat['nombre_deporte']) ?> — <?= htmlspecialchars($cat['nombre_categoria']) ?>
                                        (<?= htmlspecialchars($cat['genero']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Partido / Jornada *</label>
                            <select id="rs_modal_fixture" name="id_fixture" required class="form-select">
                                <option value="">— Sin vincular —</option>
                            </select>
                            <div class="form-text small" id="rs_modal_modalidad_hint"></div>
                        </div>
                        <!-- El tipo de resultado NO se elige: lo define la modalidad
                             del deporte (ENFRENTAMIENTO = marcador, MASIVO_TIEMPO = posiciones/tiempos) -->
                        <input type="hidden" id="rs_modal_tipo" name="tipo_resultado" value="MARCADOR">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombre del partido / prueba *</label>
                            <input type="text" name="nombre_evento" readonly id="rs_modal_nombre" class="form-control"
                                   placeholder="Ej: Final - Partido 1, 5K Masculino...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fecha</label>
                            <input type="date" name="fecha_resultado" readonly class="form-control">
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- BLOQUE MARCADOR (deportes ENFRENTAMIENTO) -->
                    <div id="rs_bloque_marcador" class="d-none">
                        <h6 class="fw-bold small mb-2"><i class="bi bi-uichecks me-1"></i>Marcador</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Equipo 1 *</label>
                                <input type="text" name="equipo_1" id="rs_eq1" class="form-control" list="rs_lista_utes" placeholder="Nombre del equipo/UTE">
                                <input type="hidden" name="id_ute_1" id="rs_idute1">
                            </div>
                            <div class="col-md-2 text-center">
                                <label class="form-label small fw-bold d-block">Goles/Tantos</label>
                                <input type="number" name="goles_1" id="rs_g1" class="form-control form-control-lg fw-bold text-center" min="0" value="0">
                            </div>
                            <div class="col-md-1 text-center fw-bold h3 mb-2">:</div>
                            <div class="col-md-2 text-center">
                                <label class="form-label small fw-bold d-block">Goles/Tantos</label>
                                <input type="number" name="goles_2" id="rs_g2" class="form-control form-control-lg fw-bold text-center" min="0" value="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Equipo 2 *</label>
                                <input type="text" name="equipo_2" id="rs_eq2" class="form-control" list="rs_lista_utes" placeholder="Nombre del equipo/UTE">
                                <input type="hidden" name="id_ute_2" id="rs_idute2">
                            </div>
                        </div>
                    </div>

                    <datalist id="rs_lista_utes"></datalist>

                    <!-- BLOQUE TIEMPO (deportes MASIVO_TIEMPO) -->
                    <div id="rs_bloque_tiempo" class="d-none">
                        <h6 class="fw-bold small mb-2"><i class="bi bi-stopwatch me-1"></i>Posiciones y tiempos</h6>
                        <div class="alert alert-warning py-2 small d-none" id="rs_sin_participantes">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            No hay participantes inscriptos en esta categoría todavía.
                        </div>
                        <table class="table table-sm align-middle mb-1" id="rs_tabla_tiempos">
                            <thead class="table-light">
                                <tr class="small fw-bold">
                                    <th style="width:70px">Posición</th>
                                    <th>Participante / Equipo</th>
                                    <th style="width:160px">Tiempo (mm:ss)</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <button type="button" id="rs_btn_agregar_fila" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i>Agregar fila
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="rs_btn_guardar" class="btn btn-primary">Guardar resultado</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const BASE = '<?= base_url("Inscripciones") ?>';

    const MEDALLAS = ['🥇', '🥈', '🥉'];
    const BADGE_TIPO = { MARCADOR: 'bg-primary', TIEMPO: 'bg-info text-dark' };

    const lista = document.getElementById('rs_lista');
    const msgBox = document.getElementById('rs_mensaje');
    const filtroDeporte = document.getElementById('rs_filtro_deporte');
    const filtroCategoria = document.getElementById('rs_filtro_categoria');

    let todosResultados = [];
    let modalResultado = new bootstrap.Modal(document.getElementById('modalResultado'));

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
            .then(r => r.json())
            .catch(e => ({ ok: false, error: 'Respuesta inesperada del servidor. Revisá la consola.' }));
    }

    function fechaArma(f) {
        if (!f) return '—';
        const p = f.split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : f;
    }

    /* ---------- Render: agrupa por Deporte → Categoría ---------- */
    function render() {
        const dep = filtroDeporte.value.toLowerCase();
        const cat = filtroCategoria.value;

        const items = todosResultados.filter(r => {
            if (dep && (r.nombre_deporte || '').toLowerCase() !== dep) return false;
            if (cat && String(r.id_categoria) !== String(cat)) return false;
            return true;
        });

        if (!items.length) {
            lista.innerHTML = '<div class="text-center text-muted py-4">' +
                '<i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>' +
                (todosResultados.length
                    ? 'No hay resultados que coincidan con los filtros elegidos.'
                    : 'Todavía no cargaste ningún resultado. Usá el botón "Cargar resultado".') +
                '</div>';
            return;
        }

        // Agrupar por deporte/categoría
        const grupos = {};
        items.forEach(r => {
            const clave = (r.nombre_deporte || '?') + '|||' + (r.nombre_categoria || '?');
            (grupos[clave] = grupos[clave] || []).push(r);
        });

        let html = '';
        Object.keys(grupos).sort().forEach(clave => {
            const partes = clave.split('|||');
            html += `<div class="card mb-3 shadow-sm">
                <div class="card-header bg-white py-2">
                    <span class="fw-bold"><i class="bi bi-trophy me-1"></i>${esc(partes[0])}</span>
                    <span class="text-muted mx-1">›</span><span>${esc(partes[1])}</span>
                    <span class="small text-muted ms-2">(${items.length ? grupos[clave].length : 0} resultado/s)</span>
                </div>
                <div class="list-group list-group-flush">`;

            grupos[clave].forEach(r => {
                let cuerpo = '';
                const det = r.detalle || [];
                if (r.tipo_resultado === 'MARCADOR') {
                    const d = det[0] || {};
                    const e1 = det[0] ? (det[0].nombre_libre || 'Equipo 1') : '?';
                    const e2 = det[1] ? (det[1].nombre_libre || 'Equipo 2') : '?';
                    cuerpo = `<div class="mt-1">
                        <span class="fw-semibold">${esc(e1)}</span>
                        <span class="badge bg-dark mx-2 fs-6">${(d.marcador_local ?? 0)} : ${(d.marcador_visita ?? 0)}</span>
                        <span class="fw-semibold">${esc(e2)}</span>
                    </div>`;
                } else {
                    cuerpo = '<div class="mt-1 small">' + det.map(d => {
                        const pos = parseInt(d.posicion, 10);
                        const medalla = MEDALLAS[pos - 1] || (pos + 'º');
                        return `<span class="me-3">${medalla} <strong>${esc(d.nombre_libre)}</strong>${d.tiempo ? ' · ' + esc(d.tiempo) : ''}</span>`;
                    }).join('') + '</div>';
                }

                html += `<div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div style="min-width:260px">
                        <span class="badge ${BADGE_TIPO[r.tipo_resultado] || 'bg-secondary'} me-1">${esc(r.tipo_resultado)}</span>
                        <strong>${esc(r.nombre_evento)}</strong>
                        ${r.nombre_prueba ? `<span class="small text-muted ms-1"><i class="bi bi-calendar3 me-1"></i>${esc(r.nombre_prueba)}</span>` : ''}
                        <div class="small text-muted">
                            <i class="bi bi-clock me-1"></i>${fechaArma(r.fecha_resultado)}
                            &nbsp;<i class="bi bi-geo-alt me-1"></i>${esc(r.lugar || 'Sin lugar')}
                            ${r.observaciones ? '&nbsp;<i class="bi bi-chat-left-text me-1"></i>' + esc(r.observaciones) : ''}
                        </div>
                        ${cuerpo}
                    </div>
                    <button class="btn btn-sm btn-outline-danger rs-borrar" data-id="${r.id_resultado}" title="Eliminar resultado">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>`;
            });

            html += '</div></div>';
        });
        lista.innerHTML = html;

        lista.querySelectorAll('.rs-borrar').forEach(b => b.addEventListener('click', () => {
            if (!confirm('¿Eliminar este resultado?')) return;
            post('ajax_eliminar_resultado', { id_resultado: b.dataset.id }).then(res => {
                mensaje(res.ok ? res.mensaje : (res.error || 'No se pudo eliminar.'), res.ok ? 'success' : 'danger');
                if (res.ok) cargarTodo();
            });
        }));
    }

    function cargarTodo() {
        fetch(BASE + '/ajax_resultados_todo', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) {
                    lista.innerHTML = '<div class="alert alert-warning py-2 small">' + esc(res.error || 'Error al cargar los resultados.') + '</div>';
                    return;
                }
                todosResultados = res.resultados || [];
                render();
            })
            .catch(() => {
                lista.innerHTML = '<div class="alert alert-danger py-2 small">No se pudieron cargar los resultados. Verificá tu sesión y las tablas <code>resultados</code> / <code>resultado_detalle</code>.</div>';
            });
    }

    /* ---------- Filtros (mismo comportamiento que UTEs/Equipos) ---------- */
    function filtrarOpcionesCategoria() {
        const deporte = filtroDeporte.value;
        const actual = filtroCategoria.value;
        Array.from(filtroCategoria.options).forEach(opt => {
            const dep = opt.getAttribute('data-deporte') || '';
            opt.hidden = !(deporte === '' || dep === '' || dep === deporte);
        });
        // Si la categoría elegida no pertenece al deporte nuevo, resetear
        if (filtroCategoria.selectedOptions[0] && filtroCategoria.selectedOptions[0].hidden) {
            filtroCategoria.value = '';
        }
    }
    filtroDeporte.addEventListener('change', () => { filtrarOpcionesCategoria(); render(); });
    filtroCategoria.addEventListener('change', render);
    document.getElementById('rs_btn_limpiar').addEventListener('click', () => {
        filtroDeporte.value = '';
        filtroCategoria.value = '';
        filtrarOpcionesCategoria();
        render();
    });

    /* ---------- Modal: cargar resultado ---------- */
    const selCatModal = document.getElementById('rs_modal_categoria');
    const selTipo = document.getElementById('rs_modal_tipo');
    const selFixture = document.getElementById('rs_modal_fixture');
    const bloqueMarcador = document.getElementById('rs_bloque_marcador');
    const bloqueTiempo = document.getElementById('rs_bloque_tiempo');
    const tbodyTiempos = document.querySelector('#rs_tabla_tiempos tbody');
    const datalistUtes = document.getElementById('rs_lista_utes');
    const hintModalidad = document.getElementById('rs_modal_modalidad_hint');
    const alertSinParticipantes = document.getElementById('rs_sin_participantes');

    let fixturesDelModal = [];   // partidos/jornadas del fixture de la categoría elegida
    let modalidadActual = '';    // ENFRENTAMIENTO | MASIVO_TIEMPO (viene del deporte)
    let competidoresDisponibles = []; // inscriptos (personales) + UTEs de la categoría
    let participantesPorFixture = {}; // MASIVO_TIEMPO: participantes que compitieron en cada jornada

    function mostrarBloqueSegunTipo() {
        const t = selTipo.value;
        bloqueMarcador.classList.toggle('d-none', t !== 'MARCADOR');
        bloqueTiempo.classList.toggle('d-none', t !== 'TIEMPO');
    }

    // El tipo NO lo elige el usuario: lo define la MODALIDAD_COMPETENCIA del deporte.
    function aplicarModalidad(modalidad) {
        modalidadActual = modalidad || '';
        if (modalidadActual === 'MASIVO_TIEMPO') {
            selTipo.value = 'TIEMPO';
            hintModalidad.innerHTML = '<span class="badge bg-warning text-dark">Masivo / Tiempo</span> ' +
                'Cargá la posición y el tiempo de cada participante inscripto.';
        } else {
            selTipo.value = 'MARCADOR';
            hintModalidad.innerHTML = '<span class="badge bg-primary">Enfrentamiento</span> ' +
                'Cargá los goles/tantos de cada equipo.';
        }
        mostrarBloqueSegunTipo();
    }

    // Al elegir categoría: modo según la modalidad del deporte + fixtures + participantes
    selCatModal.addEventListener('change', function () {
        const opt = this.selectedOptions[0];
        aplicarModalidad(opt ? opt.dataset.modalidad : '');
        limpiarPlanillaTiempos();
        cargarFixturesDelModal(this.value);
        cargarUtesDelModal(this.value);
        if (selTipo.value === 'TIEMPO') cargarCompetidoresDelModal(this.value);
    });

    function cargarFixturesDelModal(idCategoria) {
        fixturesDelModal = [];
        selFixture.innerHTML = '<option value="">— Sin vincular —</option>';
        if (!idCategoria) return;
        fetch(BASE + '/ajax_fixtures_por_categoria/' + idCategoria)
            .then(r => r.json())
            .then(res => {
                fixturesDelModal = res.fixtures || [];
                fixturesDelModal.forEach(f => {
                    const equipos = [f.ute_1_nombre, f.ute_2_nombre].filter(Boolean).join(' vs ');
                    selFixture.insertAdjacentHTML('beforeend',
                        `<option value="${f.id_fixture}">F${esc(f.numero_fecha)} · ${esc(f.nombre_prueba)}` +
                        (equipos ? ' · ' + esc(equipos) : '') + ` (${esc(f.estado)})</option>`);
                });
            });
    }

    /* ---- DINÁMICA: al elegir un partido/jornada del fixture se autocompleta todo ---- */
    selFixture.addEventListener('change', function () {
        const f = fixturesDelModal.find(x => String(x.id_fixture) === String(this.value));
        if (!f) return;

        // Nombre y fecha salen del fixture (se puede editar si hace falta)
        document.getElementById('rs_modal_nombre').value = f.nombre_prueba || '';
        const inpFecha = document.querySelector('#form_resultado input[name="fecha_resultado"]');
        if (inpFecha && f.fecha_competencia) inpFecha.value = f.fecha_competencia;

        if (selTipo.value === 'TIEMPO' || f.fase === 'JORNADA_UNICA') {
            // Deporte masivo: la jornada no tiene marcador, solo posiciones/tiempos.
            // Repintar la planilla con los participantes que compitieron en ESA
            // jornada (fixture + inscripciones_deportivas). Si todavía no se
            // cargaron los competidores de la categoría, esperar y reintentar.
            if (competidoresDisponibles.length) {
                repoblarPlanillaTiempos();
            } else if (selCatModal.value) {
                const intentar = (n) => {
                    if (competidoresDisponibles.length || n <= 0) { repoblarPlanillaTiempos(); return; }
                    setTimeout(() => intentar(n - 1), 250);
                };
                intentar(12);
            }
            return;
        }

        // Marcador: autocompletar los dos equipos con sus ids reales
        const eq1 = document.getElementById('rs_eq1');
        const eq2 = document.getElementById('rs_eq2');
        eq1.value = f.ute_1_nombre || '';
        document.getElementById('rs_idute1').value = f.id_ute_1 || '';
        eq2.value = f.ute_2_nombre || '';
        document.getElementById('rs_idute2').value = f.id_ute_2 || '';
        document.getElementById('rs_g1').value = 0;
        document.getElementById('rs_g2').value = 0;
        setTimeout(() => eq1.focus(), 50); // directo a cargar los goles
    });

    function cargarUtesDelModal(idCategoria) {
        datalistUtes.innerHTML = '';
        if (!idCategoria) return;
        fetch(BASE + '/ajax_fixture_categoria/' + idCategoria)
            .then(r => r.json())
            .then(res => {
                const utes = (res.ok ? res.utes : []) || [];
                const mapa = {};
                utes.forEach(u => {
                    datalistUtes.insertAdjacentHTML('beforeend', `<option value="${esc(u.nombre_ute)}">`);
                    mapa[u.nombre_ute.toLowerCase()] = u.id_ute;
                });
                window._utesPorNombre = mapa;
                // Autocompletar ids cuando el nombre coincide con una UTE
                [['rs_eq1', 'rs_idute1'], ['rs_eq2', 'rs_idute2']].forEach(([inp, hid]) => {
                    const el = document.getElementById(inp);
                    if (el.dataset.listener) return;
                    el.dataset.listener = '1';
                    el.addEventListener('change', () => {
                        const id = (window._utesPorNombre || {})[el.value.trim().toLowerCase()];
                        document.getElementById(hid).value = id || '';
                    });
                });
            });
    }

    /* ---- MASIVO_TIEMPO: buscar los PARTICIPANTES (inscripción personal) de la categoría.
       Además se traen, desde el fixture + inscripciones_deportivas, los participantes
       que compitieron en cada partido/jornada (participantes_por_fixture) para
       autocompletar la planilla al elegir la jornada. ---- */
    function cargarCompetidoresDelModal(idCategoria) {
        competidoresDisponibles = [];
        participantesPorFixture = {};
        limpiarPlanillaTiempos();
        if (!idCategoria) return;
        fetch(BASE + '/ajax_competidores_por_categoria/' + idCategoria)
            .then(r => r.json())
            .then(res => {
                competidoresDisponibles = (res.ok ? res.competidores : []) || [];
                participantesPorFixture = (res.ok && res.participantes_por_fixture) || {};
                alertSinParticipantes.classList.toggle('d-none', competidoresDisponibles.length > 0);
                // Una fila por cada participante inscripto, ya numerada 1°, 2°, 3°...
                tbodyTiempos.innerHTML = '';
                const deJornada = participantesDeFixtureActual();
                if (deJornada.length) {
                    deJornada.forEach((c, i) => agregarFila(i + 1, c));
                } else {
                    competidoresDisponibles.forEach((c, i) => agregarFila(i + 1, c));
                }
                if (!tbodyTiempos.children.length) for (let i = 1; i <= 3; i++) agregarFila(i);
            })
            .catch(() => {
                alertSinParticipantes.classList.remove('d-none');
                for (let i = 1; i <= 3; i++) agregarFila(i);
            });
    }

    /** Participantes que compitieron en la jornada del fixture actualmente elegida. */
    function participantesDeFixtureActual() {
        if (!selFixture.value) return [];
        const lista = participantesPorFixture[selFixture.value]
                   || participantesPorFixture[String(selFixture.value)] || [];
        // Si todavía no cargó la lista general de la categoría, igual se muestran
        // los de la jornada (vienen resueltos desde el fixture + inscripciones).
        if (!competidoresDisponibles.length) return lista;
        // Si ninguno de los de la jornada coincide con la lista general (p.ej.
        // inscripciones dadas de baja), se muestran igual los de la jornada:
        // es preferible eso a una planilla vacía.
        const filtrados = lista.filter(c => competidoresDisponibles.some(d => String(d.id) === String(c.id)));
        return filtrados.length ? filtrados : lista;
    }

    /** Repintar la planilla de tiempos según la jornada del fixture elegida. */
    function repoblarPlanillaTiempos() {
        if (selTipo.value !== 'TIEMPO') return;
        tbodyTiempos.innerHTML = '';
        const deJornada = participantesDeFixtureActual();
        if (deJornada.length) {
            deJornada.forEach((c, i) => agregarFila(i + 1, c));
        } else {
            competidoresDisponibles.forEach((c, i) => agregarFila(i + 1, c));
        }
        if (!tbodyTiempos.children.length) for (let i = 1; i <= 3; i++) agregarFila(i);
    }

    function limpiarPlanillaTiempos() {
        tbodyTiempos.innerHTML = '';
        competidoresDisponibles = [];
        alertSinParticipantes.classList.add('d-none');
    }

    /** Opciones <select> de los competidores que todavía no usó otra fila. */
    function opcionesCompetidor(exceptoId) {
        let html = '<option value="">— Escribí o elegí un nombre libre —</option>';
        let grupoActual = '';
        competidoresDisponibles.forEach(c => {
            if (String(c.id) === String(exceptoId)) return;
            const etiqueta = c.tipo === 'PERSONAL'
                ? `${c.nombre} (${c.dni})`
                : `${c.nombre} [equipo]`;
            if (c.tipo !== grupoActual) {
                if (grupoActual !== '') html += '</optgroup>';
                grupoActual = c.tipo;
                html += `<optgroup label="${c.tipo === 'PERSONAL' ? 'Participantes inscriptos' : 'Equipos / UTEs'}">`;
            }
            html += `<option value="${c.id}">${esc(etiqueta)}</option>`;
        });
        if (grupoActual !== '') html += '</optgroup>';
        return html;
    }

    function agregarFila(pos, competidor) {
        const tr = document.createElement('tr');
        const c = competidor || null;
        // El nombre NO se puede modificar: es fijo (viene de la inscripción).
        // Las filas nuevas sin competidor asignado permiten buscar/elegir uno.
        const nombreFijo = c
            ? `<div class="rs-nombre-fijo fw-semibold small text-truncate" title="${esc(c.nombre || '')}">${esc(c.nombre)}${c.dni ? ' <span class="text-muted">(' + esc(c.dni) + ')</span>' : ''}</div>`
            : `<input type="text" class="form-control form-control-sm rs-buscar mb-1" list="rs_lista_utes" placeholder="Buscar por nombre...">`;
        tr.innerHTML = `
            <td><input type="number" name="comp_posicion[]" class="form-control form-control-sm" min="1" value="${pos}"></td>
            <td>
                ${nombreFijo}
                <select name="comp_ute[]" class="form-select form-select-sm rs-comp ${c ? 'd-none' : ''}"></select>
                <!-- el id real del competidor (>0 UTE / <0 inscripción personal):
                     el select muestra nombres libres, este hidden guarda el id -->
                <input type="hidden" name="comp_id[]" class="rs-comp-id" value="${c ? esc(String(c.id)) : ''}">
                <!-- respaldo del nombre: el backend usa este nombre para la fila -->
                <input type="hidden" name="comp_nombre[]" class="rs-nombre-hidden" value="${c ? esc(c.nombre || '') : ''}">
            </td>
            <td><input type="text" name="comp_tiempo[]" class="form-control form-control-sm" placeholder="Ej: 18:42 o 1:05:30"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger rs-quitar-fila"><i class="bi bi-dash-lg"></i></button></td>`;
        tbodyTiempos.appendChild(tr);

        const selComp = tr.querySelector('.rs-comp');
        const inpBuscar = tr.querySelector('.rs-buscar');
        const inpId = tr.querySelector('.rs-comp-id');
        const inpNomHidden = tr.querySelector('.rs-nombre-hidden');

        // Pinchar el nombre fijo => permite cambiarlo (vuelve a modo edición)
        const fijo = tr.querySelector('.rs-nombre-fijo');
        if (fijo) {
            fijo.style.cursor = 'pointer';
            fijo.title = (fijo.title || '') + ' — clic para cambiar';
            fijo.addEventListener('click', () => {
                fijo.remove();
                selComp.classList.remove('d-none');
                inpId.value = '';
                inpNomHidden.value = '';
                const aux = document.createElement('input');
                aux.type = 'text';
                aux.className = 'form-control form-control-sm rs-buscar mb-1';
                aux.setAttribute('list', 'rs_lista_utes');
                aux.placeholder = 'Buscar por nombre...';
                selComp.parentNode.insertBefore(aux, selComp);
                activarBuscador(aux);
                selComp.dispatchEvent(new Event('change'));
                aux.focus();
            });
        }

        // Al elegir del select => guarda el id + nombre reales y oculta el buscador
        selComp.addEventListener('change', () => {
            const opt = selComp.selectedOptions[0];
            if (opt && opt.value !== '') {
                inpId.value = opt.value;
                inpNomHidden.value = opt.textContent.trim();
                if (inpBuscar) inpBuscar.remove();
                selComp.classList.add('d-none');
                const div = document.createElement('div');
                div.className = 'rs-nombre-fijo fw-semibold small text-truncate';
                div.textContent = opt.textContent.trim();
                div.title = 'Clic para cambiar';
                div.style.cursor = 'pointer';
                selComp.parentNode.insertBefore(div, selComp);
                div.addEventListener('click', () => {
                    div.remove();
                    selComp.classList.remove('d-none');
                    inpId.value = '';
                    inpNomHidden.value = '';
                    const aux = document.createElement('input');
                    aux.type = 'text';
                    aux.className = 'form-control form-control-sm rs-buscar mb-1';
                    aux.setAttribute('list', 'rs_lista_utes');
                    aux.placeholder = 'Buscar por nombre...';
                    selComp.parentNode.insertBefore(aux, selComp);
                    activarBuscador(aux);
                    refrescarOpciones();
                    aux.focus();
                });
            } else {
                inpId.value = '';
                inpNomHidden.value = inpBuscar ? inpBuscar.value.trim() : '';
            }
            refrescarOpciones();
        });

        function refrescarOpciones() {
            const actual = inpId.value || selComp.value;
            selComp.innerHTML = opcionesCompetidor(actual || null);
            if (actual && [...selComp.options].some(o => o.value === actual)) selComp.value = actual;
        }
        if (!c) {
            selComp.innerHTML = opcionesCompetidor(null);
            if (inpBuscar) activarBuscador(inpBuscar);
        }

        function activarBuscador(inp) {
            inp.addEventListener('input', () => {
                inpNomHidden.value = inp.value.trim();
            });
            // Escribir un nombre exacto de un competidor => lo selecciona y fija
            inp.addEventListener('change', () => {
                const txt = inp.value.trim().toLowerCase();
                if (!txt) return;
                const match = competidoresDisponibles.find(cc => cc.nombre.toLowerCase() === txt);
                if (match) {
                    inpId.value = String(match.id);
                    inpNomHidden.value = match.nombre;
                    inp.remove();
                    selComp.classList.add('d-none');
                    const div = document.createElement('div');
                    div.className = 'rs-nombre-fijo fw-semibold small text-truncate';
                    div.textContent = match.nombre + (match.dni ? ' (' + match.dni + ')' : '');
                    div.title = 'Clic para cambiar';
                    div.style.cursor = 'pointer';
                    selComp.parentNode.insertBefore(div, selComp);
                    div.addEventListener('click', () => {
                        div.remove();
                        selComp.classList.remove('d-none');
                        inpId.value = '';
                        inpNomHidden.value = '';
                        const aux = document.createElement('input');
                        aux.type = 'text';
                        aux.className = 'form-control form-control-sm rs-buscar mb-1';
                        aux.setAttribute('list', 'rs_lista_utes');
                        aux.placeholder = 'Buscar por nombre...';
                        selComp.parentNode.insertBefore(aux, selComp);
                        activarBuscador(aux);
                        refrescarOpciones();
                        aux.focus();
                    });
                    refrescarOpciones();
                }
            });
        }

        tr.querySelector('.rs-quitar-fila').addEventListener('click', () => {
            tr.remove();
            renumerarFilas();
            // refrescar las opciones para que vuelva a aparecer el quitado
            refrescarTodasLasOpciones();
        });
    }

    /** Vuelve a pintar los <select> de todas las filas excluyendo los ya asignados. */
    function refrescarTodasLasOpciones() {
        tbodyTiempos.querySelectorAll('tr').forEach(fila => {
            const s = fila.querySelector('.rs-comp');
            if (!s) return;
            const actual = fila.querySelector('.rs-comp-id').value || s.value;
            s.innerHTML = opcionesCompetidor(actual || null);
            if (actual && [...s.options].some(o => o.value === actual)) s.value = actual;
        });
    }

    function renumerarFilas() {
        tbodyTiempos.querySelectorAll('input[name="comp_posicion[]"]').forEach((inp, i) => inp.value = i + 1);
    }
    document.getElementById('rs_btn_agregar_fila').addEventListener('click', () => {
        agregarFila(tbodyTiempos.children.length + 1);
    });

    document.getElementById('rs_btn_nuevo').addEventListener('click', () => {
        document.getElementById('form_resultado').reset();
        selCatModal.value = '';
        selTipo.value = 'MARCADOR';
        selFixture.innerHTML = '<option value="">— Sin vincular —</option>';
        datalistUtes.innerHTML = '';
        tbodyTiempos.innerHTML = '';
        for (let i = 1; i <= 3; i++) agregarFila(i); // 1°, 2°, 3° por defecto
        mostrarBloqueSegunTipo();
        modalResultado.show();
    });

    document.getElementById('rs_btn_guardar').addEventListener('click', () => {
        const fd = new FormData(document.getElementById('form_resultado'));
        const datos = {};
        // Recoger arrays (filas de tiempo) manualmente
        const arrays = {};
        for (const [k, v] of fd.entries()) {
            if (k.endsWith('[]')) {
                (arrays[k] = arrays[k] || []).push(v);
            } else {
                datos[k] = v;
            }
        }
        // IMPORTANTE: se envían los valores REALES del DOM (no el FormData, que
        // captura también los <select hidden> vacíos de las filas con nombre fijo).
        datos['comp_posicion'] = [...tbodyTiempos.querySelectorAll('input[name="comp_posicion[]"]')].map(i => i.value);
        datos['comp_nombre']   = [...tbodyTiempos.querySelectorAll('input[name="comp_nombre[]"]')].map(i => i.value);
        datos['comp_id']       = [...tbodyTiempos.querySelectorAll('input[name="comp_id[]"]')].map(i => i.value);
        datos['comp_tiempo']   = [...tbodyTiempos.querySelectorAll('input[name="comp_tiempo[]"]')].map(i => i.value);

        if (!datos.id_categoria) { mensaje('Elegí el deporte/categoría.', 'warning'); return; }
        if (!datos.nombre_evento) { mensaje('Poné un nombre al partido/prueba.', 'warning'); return; }

        post('ajax_guardar_resultado', datos).then(res => {
            if (res.ok) {
                modalResultado.hide();
                mensaje(res.mensaje, 'success');
                cargarTodo();
            } else {
                mensaje(res.error || 'No se pudo guardar el resultado.', 'danger');
            }
        });
    });

    /* Al iniciar, mostrar todo lo cargado */
    filtrarOpcionesCategoria();
    cargarTodo();
})();
</script>

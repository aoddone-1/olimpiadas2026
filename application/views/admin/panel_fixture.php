<!-- PANEL FIXTURE -->
<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="card-title fw-bold mb-3">
            <i class="bi bi-calendar3 me-2"></i>Gestión de Fixture
        </h5>

        <!-- Barra de acciones: el selector es solo para generar/borrar, NO para ver -->
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Categoría (para generar / borrar fixture)</label>
                <select id="fx_categoria" class="form-select">
                    <option value="">— Seleccioná una categoría —</option>
                    <?php foreach ($categorias_fixture as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>"
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
            <div class="col-md-7">
                <button id="fx_btn_generar" class="btn btn-primary" disabled>
                    <i class="bi bi-magic me-1"></i>Generar fixture automático
                </button>
                <button id="fx_btn_borrar_todo" class="btn btn-outline-danger" disabled>
                    <i class="bi bi-trash me-1"></i>Borrar fixture de la categoría
                </button>
                <button id="fx_btn_nuevo" class="btn btn-success">
                    <i class="bi bi-plus-lg me-1"></i>+ Partido manual
                </button>
            </div>
        </div>

        <!-- Info de la categoría seleccionada -->
        <div id="fx_info" class="alert alert-info py-2 small d-none"></div>

        <!-- Toast de mensajes -->
        <div id="fx_mensaje" class="alert d-none"></div>

        <!-- Listado GENERAL: se muestra todo lo cargado sin necesidad de filtrar -->
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

    const selCategoria = document.getElementById('fx_categoria');
    const btnGenerar = document.getElementById('fx_btn_generar');
    const btnNuevo = document.getElementById('fx_btn_nuevo');
    const btnBorrarTodo = document.getElementById('fx_btn_borrar_todo');
    const infoBox = document.getElementById('fx_info');
    const msgBox = document.getElementById('fx_mensaje');
    const lista = document.getElementById('fx_lista');

    let todosFixtures = [];   // fixture completo de TODAS las categorías
    let modalPartido = new bootstrap.Modal(document.getElementById('modalPartido'));
    let modalMasivo = null;   // se crea al primer uso (el HTML está más abajo)

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
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status + ' en ' + url);
                return r.json();
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

    /* ---------- Render GENERAL: agrupa por Deporte → Categoría → Jornada ---------- */
    function render() {
        if (!todosFixtures.length) {
            lista.innerHTML = '<div class="text-center text-muted py-4">' +
                '<i class="bi bi-calendar-x fs-1 d-block mb-2"></i>' +
                'Todavía no hay ningún partido cargado. Generá un fixture automático o creá uno manual con "+ Partido manual".</div>';
            return;
        }

        // Agrupar: clave "Deporte|||Categoría"
        const grupos = {};
        todosFixtures.forEach(f => {
            const clave = (f.nombre_deporte || '?') + '|||' + (f.nombre_categoria || '?');
            (grupos[clave] = grupos[clave] || []).push(f);
        });

        let html = '';
        Object.keys(grupos).sort().forEach(clave => {
            const partes = clave.split('|||');
            const deporte = partes[0], categoria = partes[1];
            const items = grupos[clave];
            const primera = items[0];
            const tipoTag = primera.modalidad_competencia === 'MASIVO_TIEMPO'
                ? '<span class="badge bg-warning text-dark ms-2">Masivo / Un día</span>'
                : (primera.tipo_duracion === 'UNICO_DIA'
                    ? '<span class="badge bg-info text-dark ms-2">Un día</span>'
                    : '<span class="badge bg-primary ms-2">Multidía</span>');

            html += `<div class="card mb-3 shadow-sm">
                <div class="card-header bg-white py-2">
                    <span class="fw-bold"><i class="bi bi-trophy me-1"></i>${esc(deporte)}</span>
                    <span class="text-muted mx-1">›</span>
                    <span>${esc(categoria)}</span>
                    ${tipoTag}
                    <span class="small text-muted ms-2">(${items.length} partido/s)</span>
                </div>
                <div class="card-body py-2">`;

            // Subagrupar por jornada
            const jornadas = {};
            items.forEach(f => {
                (jornadas[f.numero_fecha] = jornadas[f.numero_fecha] || []).push(f);
            });

            Object.keys(jornadas).sort((a, b) => a - b).forEach(num => {
                html += `<h6 class="fw-bold mt-2 mb-1 small"><i class="bi bi-calendar-week me-1"></i>Jornada ${esc(num)}</h6>`;
                html += '<div class="list-group mb-2">';
                jornadas[num].forEach(f => {
                    const t1 = f.ute_1_nombre ? esc(f.ute_1_nombre) : '<span class="fst-italic text-muted">Pendiente</span>';
                    const t2 = f.ute_2_nombre ? esc(f.ute_2_nombre) : '<span class="text-muted fst-italic">Pendiente</span>';
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
                        } else {
                            podioHtml = '<div class="mt-1 small fst-italic text-muted">Sin resultados cargados todavía.</div>';
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
                            ${esMasivo ? podioHtml : `<div class="mt-1">
                                <span class="fw-semibold">${t1}</span>
                                <span class="text-muted mx-1">vs</span>
                                <span class="fw-semibold">${t2}</span>
                            </div>`}
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge ${badge}">${esc(f.estado.replace('_',' '))}</span>
                            ${esMasivo ? `
                                <button class="btn btn-sm btn-outline-primary fx-masivo" data-id="${f.id_fixture}">
                                    <i class="bi bi-flag me-1"></i>${ordenGuardado.length ? 'Editar resultados' : 'Cargar resultados'}
                                </button>` : ''}
                            ${(!esMasivo && f.id_ute_1 && f.id_ute_2 && f.estado !== 'FINALIZADO') ? `
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success fx-ganador" data-id="${f.id_fixture}" data-ute="${f.id_ute_1}" title="Gana: ${esc(f.ute_1_nombre)}">🏆 ${esc(f.ute_1_nombre).slice(0, 14)}</button>
                                    <button class="btn btn-outline-success fx-ganador" data-id="${f.id_fixture}" data-ute="${f.id_ute_2}" title="Gana: ${esc(f.ute_2_nombre)}">🏆 ${esc(f.ute_2_nombre).slice(0, 14)}</button>
                                </div>` : ''}
                            <button class="btn btn-sm btn-outline-secondary fx-editar" data-id="${f.id_fixture}" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger fx-borrar" data-id="${f.id_fixture}" title="Eliminar">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>`;
                });
                html += '</div>';
            });

            html += '</div></div>';
        });
        lista.innerHTML = html;

        // Eventos de los botones
        lista.querySelectorAll('.fx-ganador').forEach(b => b.addEventListener('click', () => {
            if (!confirm('¿Confirmás el ganador y que clasifica a la siguiente fase?')) return;
            post('ajax_resultado_partido', { id_fixture: b.dataset.id, id_ganador: b.dataset.ute })
                .then(res => {
                    mensaje(res.ok ? res.mensaje : res.error, res.ok ? 'success' : 'danger');
                    cargarTodo();
                });
        }));
        lista.querySelectorAll('.fx-masivo').forEach(b => b.addEventListener('click', () => {
            const f = todosFixtures.find(x => x.id_fixture == b.dataset.id);
            abrirModalMasivo(f);
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
    }

    /* ---------- Carga GENERAL (sin filtro de categoría) ---------- */
    function cargarTodo() {
        getJSON('ajax_fixture_todo')
            .then(res => {
                if (!res.ok) {
                    lista.innerHTML = '<div class=\"alert alert-danger py-2 small\">' +
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
                    sel.innerHTML = '<option value="">— Pendiente —</option>';
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

    function abrirModalMasivo(f) {
        if (!f) return;
        const disponibles = (f.utes_categoria || []).map(u => String(u.id_ute));
        if (!disponibles.length) {
            mensaje('No hay UTEs/equipos inscriptos en esta categoría todavía. Cargalos primero en el panel de equipos.', 'warning');
            return;
        }

        // Orden previo guardado (si ya se había cargado) + resto de los equipos.
        let previo = [];
        try { previo = JSON.parse(f.resultado || '[]') || []; } catch (e) { previo = []; }
        previo = previo.map(String).filter(id => disponibles.includes(id));
        ordenLlegada = previo.concat(disponibles.filter(id => !previo.includes(id)));
        f._limite = previo.length;   // cursor: cuántos ya "llegaron"
        jornadaActual = f;
        renderOrden();

        document.getElementById('fxm_titulo').textContent =
            (f.nombre_deporte || '') + ' — ' + (f.nombre_categoria || '') + ' (' + (f.nombre_prueba || 'Jornada') + ')';

        if (!modalMasivo) modalMasivo = new bootstrap.Modal(document.getElementById('modalMasivo'));
        modalMasivo.show();
    }

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

<!-- PANEL FIXTURE -->
<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="card-title fw-bold mb-3">
            <i class="bi bi-calendar3 me-2"></i>Gestión de Fixture
        </h5>

        <!-- Selector de categoría + acciones -->
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold">Deporte / Categoría</label>
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
            <div class="col-md-6">
                <button id="fx_btn_generar" class="btn btn-primary" disabled>
                    <i class="bi bi-magic me-1"></i>Generar fixture automático
                </button>
                <button id="fx_btn_nuevo" class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-plus-lg me-1"></i>Partido manual
                </button>
                <button id="fx_btn_borrar_todo" class="btn btn-outline-danger" disabled>
                    <i class="bi bi-trash me-1"></i>Borrar fixture
                </button>
            </div>
        </div>

        <!-- Info de la categoría seleccionada -->
        <div id="fx_info" class="alert alert-info py-2 small d-none"></div>

        <!-- Toast de mensajes -->
        <div id="fx_mensaje" class="alert d-none"></div>

        <!-- Listado de partidos -->
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

<script>
(function () {
    const BASE = '<?= base_url("Inscripciones") ?>';
    const selCategoria = document.getElementById('fx_categoria');
    const btnGenerar = document.getElementById('fx_btn_generar');
    const btnNuevo = document.getElementById('fx_btn_nuevo');
    const btnBorrarTodo = document.getElementById('fx_btn_borrar_todo');
    const infoBox = document.getElementById('fx_info');
    const msgBox = document.getElementById('fx_mensaje');
    const lista = document.getElementById('fx_lista');

    let utesActuales = [];
    let modalPartido = new bootstrap.Modal(document.getElementById('modalPartido'));

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
            .then(r => r.json());
    }

    function fechaArma(f) {
        if (!f) return '—';
        const p = f.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    /* ---------- Render del listado ---------- */
    function render(fixtures) {
        if (!fixtures.length) {
            lista.innerHTML = '<div class="text-center text-muted py-4">' +
                '<i class="bi bi-calendar-x fs-1 d-block mb-2"></i>' +
                'Esta categoría todavía no tiene fixture. Usá "Generar fixture automático" o creá un partido manual.</div>';
            return;
        }

        // Agrupar por numero_fecha (jornada)
        const jornadas = {};
        fixtures.forEach(f => {
            (jornadas[f.numero_fecha] = jornadas[f.numero_fecha] || []).push(f);
        });

        let html = '';
        Object.keys(jornadas).sort((a, b) => a - b).forEach(num => {
            html += `<h6 class="fw-bold mt-4 mb-2"><i class="bi bi-calendar-week me-1"></i>Jornada ${esc(num)}</h6>`;
            html += '<div class="list-group">';
            jornadas[num].forEach(f => {
                const t1 = f.ute_1_nombre ? esc(f.ute_1_nombre) : '<span class="fst-italic text-muted">Pendiente</span>';
                const t2 = f.ute_2_nombre ? esc(f.ute_2_nombre) : '<span class="text-muted fst-italic">Pendiente</span>';
                const esMasivo = f.fase === 'JORNADA_UNICA';
                const badge = BADGES[f.estado] || 'bg-secondary';

                html += `<div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div style="min-width:260px">
                        <span class="badge bg-info text-dark me-1">${esc(f.fase.replace('_',' '))}</span>
                        <strong>${esc(f.nombre_prueba)}</strong>
                        <div class="small text-muted">
                            <i class="bi bi-clock me-1"></i>${fechaArma(f.fecha_competencia)} ${esc((f.hora_inicio||'').slice(0,5))}–${esc((f.hora_fin||'').slice(0,5))}
                            &nbsp;<i class="bi bi-geo-alt me-1"></i>${esc(f.lugar_nombre || 'Sin lugar')}
                        </div>
                        ${esMasivo ? '' : `<div class="mt-1">
                            <span class="fw-semibold">${t1}</span>
                            <span class="text-muted mx-1">vs</span>
                            <span class="fw-semibold">${t2}</span>
                        </div>`}
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge ${badge}">${esc(f.estado.replace('_',' '))}</span>
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
        lista.innerHTML = html;

        // Eventos de los botones
        lista.querySelectorAll('.fx-ganador').forEach(b => b.addEventListener('click', () => {
            if (!confirm('¿Confirmás el ganador y que clasifica a la siguiente fase?')) return;
            post('ajax_resultado_partido', { id_fixture: b.dataset.id, id_ganador: b.dataset.ute })
                .then(res => {
                    mensaje(res.ok ? res.mensaje : res.error, res.ok ? 'success' : 'danger');
                    cargarFixture();
                });
        }));
        lista.querySelectorAll('.fx-editar').forEach(b => b.addEventListener('click', () => {
            const f = fixtures.find(x => x.id_fixture == b.dataset.id);
            abrirModal(f);
        }));
        lista.querySelectorAll('.fx-borrar').forEach(b => b.addEventListener('click', () => {
            if (!confirm('¿Eliminar este partido?')) return;
            post('ajax_eliminar_partido', { id_fixture: b.dataset.id }).then(res => {
                mensaje(res.mensaje, 'success');
                cargarFixture();
            });
        }));
    }

    /* ---------- Carga de datos ---------- */
    function cargarFixture() {
        const id = selCategoria.value;
        if (!id) return;
        fetch(BASE + '/ajax_fixture_categoria/' + id)
            .then(r => r.json())
            .then(res => {
                if (!res.ok) { mensaje(res.error, 'danger'); return; }
                utesActuales = res.utes;
                render(res.fixtures);
            });
    }

    function llenarSelectUtes(id1, id2) {
        [document.getElementById('fx_ute1'), document.getElementById('fx_ute2')].forEach(sel => {
            sel.innerHTML = '<option value="">— Pendiente —</option>';
            utesActuales.forEach(u => {
                sel.insertAdjacentHTML('beforeend', `<option value="${u.id_ute}">${esc(u.nombre_ute)}</option>`);
            });
        });
        document.getElementById('fx_ute1').value = id1 || '';
        document.getElementById('fx_ute2').value = id2 || '';
    }

    /* ---------- Modal ---------- */
    function abrirModal(f) {
        document.getElementById('fx_cat_hidden').value = selCategoria.value;
        if (f) {
            document.getElementById('fx_id_fixture').value = f.id_fixture;
            document.getElementById('fx_nombre_prueba').value = f.nombre_prueba || '';
            document.getElementById('fx_fase').value = f.fase;
            document.getElementById('fx_numero_fecha').value = f.numero_fecha;
            document.getElementById('fx_lugar').value = f.id_lugar || '';
            document.getElementById('fx_estado').value = f.estado;
            document.getElementById('fx_fecha').value = f.fecha_competencia;
            document.getElementById('fx_hora_ini').value = (f.hora_inicio || '').slice(0, 5);
            document.getElementById('fx_hora_fin').value = (f.hora_fin || '').slice(0, 5);
            llenarSelectUtes(f.id_ute_1, f.id_ute_2);
        } else {
            document.getElementById('form_partido').reset();
            document.getElementById('fx_id_fixture').value = '';
            document.getElementById('fx_cat_hidden').value = selCategoria.value;
            llenarSelectUtes(null, null);
        }
        modalPartido.show();
    }

    document.getElementById('fx_btn_guardar').addEventListener('click', () => {
        const form = document.getElementById('form_partido');
        const datos = Object.fromEntries(new FormData(form).entries());
        if (!datos.nombre_prueba || !datos.fecha_competencia || !datos.hora_inicio || !datos.hora_fin) {
            mensaje('Completá nombre, fecha y horarios.', 'warning');
            return;
        }
        post('ajax_guardar_partido', datos).then(res => {
            if (res.ok) {
                modalPartido.hide();
                mensaje(res.mensaje, 'success');
                cargarFixture();
            } else {
                mensaje(res.error, 'danger');
            }
        });
    });

    /* ---------- Acciones principales ---------- */
    selCategoria.addEventListener('change', () => {
        const opt = selCategoria.selectedOptions[0];
        const activo = !!selCategoria.value;
        btnGenerar.disabled = !activo;
        btnNuevo.disabled = !activo;
        btnBorrarTodo.disabled = !activo;

        if (activo) {
            infoBox.classList.remove('d-none');
            const tipo = opt.dataset.modalidad === 'MASIVO_TIEMPO'
                ? 'Deporte MASIVO de un solo día → se genera una única jornada (largada).'
                : (opt.dataset.duracion === 'UNICO_DIA'
                    ? 'Deporte de enfrentamiento en jornada única → eliminatoria concentrada en 1 día.'
                    : 'Deporte MULTIDIA de enfrentamiento → se genera bracket de eliminatoria por fechas.');
            infoBox.innerHTML = `<strong>${esc(opt.dataset.deporte)} — ${esc(opt.dataset.categoria)}</strong><br>${tipo}`;
            cargarFixture();
        } else {
            infoBox.classList.add('d-none');
            lista.innerHTML = '';
        }
    });

    btnGenerar.addEventListener('click', () => {
        if (!confirm('Se generará el fixture automáticamente con las UTEs existentes (cruces aleatorios). ¿Continuar?')) return;
        post('ajax_generar_fixture', { id_categoria: selCategoria.value }).then(res => {
            mensaje(res.ok ? res.mensaje : res.error, res.ok ? 'success' : 'danger');
            if (res.ok) cargarFixture();
        });
    });

    btnNuevo.addEventListener('click', () => abrirModal(null));

    btnBorrarTodo.addEventListener('click', () => {
        if (!confirm('¿Seguro? Se eliminarán TODOS los partidos de esta categoría.')) return;
        post('ajax_eliminar_fixture', { id_categoria: selCategoria.value }).then(res => {
            mensaje(res.mensaje, 'success');
            cargarFixture();
        });
    });
})();
</script>

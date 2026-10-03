<!-- PANEL FIXTURE — PARTE 1: FASE DE GRUPOS (Fixture tipo Mundial) -->
<!-- Flujo: elegir categoría → crear grupos A, B, C... → asignar UTEs al grupo. -->
<style>
.fx-grupo-card { border: 2px solid #dee2e6; transition: border-color .15s; }
.fx-grupo-card:hover { border-color: #0d6efd66; }
.fx-badge-letra { width: 2.4rem; height: 2.4rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; }
.fx-ute-chip { background: #f1f3f5; border-radius: .75rem; }
.fx-sin-grupo { min-height: 90px; border: 2px dashed #adb5bd; border-radius: .75rem; }
</style>

<div class="card shadow-sm border-0" id="panel-fixture-root">
    <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column gap-1">
        <div class="d-flex align-items-center">
            <i class="bi bi-diagram-3-fill me-2 text-primary"></i>
            <span>Fixture — Fase de Grupos</span>
        </div>
        <small class="text-muted fw-normal">Elegí una categoría, creá los grupos (A, B, C...) y asigná las UTEs/Equipos.</small>
    </div>

    <div class="card-body">

        <!-- PASO 1: elegir deporte → categoría (selects encadenados) -->
        <div class="row g-2 align-items-end mb-4">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-secondary">1 · Deporte</label>
                <select id="fx-deporte" class="form-select">
                    <option value="">— Elegí un deporte —</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold small text-secondary">2 · Categoría</label>
                <select id="fx-cat" class="form-select" disabled>
                    <option value="">— Elegí primero un deporte —</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button id="fx-btn-nuevo-grupo" class="btn btn-primary flex-fill" disabled>
                    <i class="bi bi-plus-lg me-1"></i>Crear grupo
                </button>
                <button id="fx-btn-sorteo" class="btn btn-outline-secondary flex-fill" disabled title="Reparte las UTEs sin grupo entre los grupos (serpentina)">
                    <i class="bi bi-shuffle me-1"></i>Sorteo
                </button>
            </div>
        </div>

        <div id="fx-alert" class="alert d-none"></div>

        <!-- Resumen de la categoría -->
        <div id="fx-resumen" class="mb-4 d-none">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge text-bg-light border fx-chip" id="fx-chip-grupos"></span>
                <span class="badge text-bg-light border fx-chip" id="fx-chip-utes"></span>
                <span class="badge text-bg-light border fx-chip" id="fx-chip-tamano"></span>
                <span class="badge text-bg-light border fx-chip" id="fx-chip-clasifican"></span>
                <span id="fx-chip-listo"></span>
            </div>
        </div>

        <!-- Grupos + pool sin grupo -->
        <div class="row g-3" id="fx-grupos"></div>

        <div class="fx-sin-grupo p-3 mt-3 d-none" id="fx-pool">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-inboxes-fill me-2 text-warning"></i>
                <strong class="small text-secondary">UTEs SIN GRUPO (todavía no asignadas)</strong>
            </div>
            <div id="fx-pool-utes" class="d-flex flex-wrap gap-2"></div>
        </div>

        <p id="fx-vacio" class="text-muted small mt-4 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Seleccioná una categoría para ver sus grupos. Los partidos de la fase de grupos se generarán en la próxima etapa.
        </p>
    </div>
</div>

<!-- Modal crear grupo -->
<div class="modal fade" id="fxModalGrupo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-bold">Nombre / letra del grupo</label>
                <input id="fx-nombre-grupo" class="form-control text-uppercase" maxlength="10" placeholder="A">
                <div class="form-text">Se sugiere la siguiente letra libre.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="fx-confirmar-grupo">Crear</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    const BASE = '<?= base_url("Inscripciones") ?>';
    const $ = (sel) => document.querySelector(sel);

    let estado = { deportes: [], idDep: null, categorias: [], idCat: null, grupos: [], utes: [], resumen: null, siguiente: 'A' };

    /* ---------- helpers ---------- */
    function post(url, datos) {
        const form = new FormData();
        Object.entries(datos || {}).forEach(([k, v]) => form.append(k, v == null ? '' : v));
        return fetch(BASE + '/' + url, { method: 'POST', body: form })
            .then(r => r.json());
    }
    function get(url) {
        return fetch(BASE + '/' + url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json());
    }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }
    function alerta(msg, tipo) {
        const a = $('#fx-alert');
        a.className = 'alert alert-' + (tipo || 'warning');
        a.textContent = msg;
        a.classList.remove('d-none');
        setTimeout(() => a.classList.add('d-none'), 4000);
    }

    /* ---------- carga inicial: deportes (selector 1) ---------- */
    function cargarDeportes() {
        get('ajax_grupos_deportes').then(res => {
            if (!res.ok) { alerta(res.error || 'No se pudieron cargar los deportes.', 'danger'); return; }
            estado.deportes = res.deportes;
            const sel = $('#fx-deporte');
            sel.innerHTML = '<option value="">— Elegí un deporte —</option>';
            res.deportes.forEach(d => {
                const o = document.createElement('option');
                o.value = d.id_deporte;
                o.textContent = d.nombre_deporte;
                sel.appendChild(o);
            });
        });
    }

    /* ---------- selector 2: categorías del deporte elegido ---------- */
    function limpiarSelectorCategoria(placeholder) {
        estado.categorias = [];
        estado.idCat = null;
        const sel = $('#fx-cat');
        sel.disabled = true;
        sel.innerHTML = '<option value="">' + placeholder + '</option>';
    }

    function cargarCategorias(idDeporte) {
        limpiarSelectorCategoria('— Cargando… —');
        get('ajax_grupos_categorias_por_deporte/' + idDeporte).then(res => {
            if (!res.ok) {
                alerta(res.error || 'No se pudieron cargar las categorías.', 'danger');
                limpiarSelectorCategoria('— Elegí primero un deporte —');
                return;
            }
            // Si el usuario cambió de deporte mientras cargaba, descartar.
            if (estado.idDep !== Number(idDeporte)) return;

            estado.categorias = res.categorias;
            const sel = $('#fx-cat');
            sel.innerHTML = '<option value="">— Elegí una categoría —</option>';
            res.categorias.forEach(c => {
                const o = document.createElement('option');
                o.value = c.id_categoria;
                o.textContent = c.nombre_categoria + ' (' + c.genero + ') — ' + c.cantidad_utes + ' UTEs';
                sel.appendChild(o);
            });
            sel.disabled = res.categorias.length === 0;
            if (res.categorias.length === 0) {
                sel.innerHTML = '<option value="">— Sin categorías para este deporte —</option>';
            }
        }).catch(() => limpiarSelectorCategoria('— Error al cargar —'));
    }

    /* ---------- estado de la categoría elegida ---------- */
    function cargarEstado() {
        if (!estado.idCat) return;
        get('ajax_grupos_estado/' + estado.idCat).then(res => {
            if (!res.ok) { alerta(res.error || 'Error al cargar la categoría.', 'danger'); return; }
            estado.grupos = res.grupos;
            estado.utes = res.utes;
            estado.resumen = res.resumen;
            estado.siguiente = res.siguiente_nombre || '';
            render();
        });
    }

    /* ---------- render ---------- */
    function optionGrupos(selectedId) {
        let html = '<option value="">Sin grupo</option>';
        estado.grupos.forEach(g => {
            html += '<option value="' + g.id_grupo + '"' + (String(g.id_grupo) === String(selectedId) ? ' selected' : '') + '>Grupo ' + esc(g.nombre_grupo) + '</option>';
        });
        return html;
    }

    function chipUte(u, enPool) {
        return '<span class="fx-ute-chip px-2 py-1 d-inline-flex align-items-center gap-2 small">' +
            '<i class="bi bi-people-fill text-secondary"></i>' + esc(u.nombre_ute) +
            ' <span class="text-muted">(' + u.integrantes + ')</span>' +
            (enPool
                ? '<select class="form-select form-select-sm fw-bold" style="width:auto" data-ute="' + u.id_ute + '">' + optionGrupos(null) + '</select>'
                : '<button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1 quitargrupo" data-ute="' + u.id_ute + '" title="Quitar del grupo"><i class="bi bi-x-circle"></i></button>') +
            '</span>';
    }

    function render() {
        const cont = $('#fx-grupos');
        cont.innerHTML = '';
        $('#fx-vacio').classList.toggle('d-none', !!estado.idCat);
        $('#fx-resumen').classList.toggle('d-none', !estado.resumen);
        $('#fx-btn-nuevo-grupo').disabled = !estado.idCat;
        $('#fx-btn-sorteo').disabled = !estado.idCat || estado.grupos.length === 0;

        if (estado.resumen) {
            const r = estado.resumen;
            $('#fx-chip-grupos').innerHTML = '<i class="bi bi-collection me-1"></i>Grupos: <b>' + r.cantidad_grupos + '</b>';
            $('#fx-chip-utes').innerHTML = '<i class="bi bi-people me-1"></i>UTEs: <b>' + r.utes_totales + '</b> (sin grupo: <b>' + r.utes_sin_grupo + '</b>)';
            $('#fx-chip-tamano').innerHTML = 'Tamaño deseado: <b>' + r.categoria.equipos_por_grupo + '</b> por grupo';
            $('#fx-chip-clasifican').innerHTML = 'Clasifican: <b>' + r.categoria.clasificados_por_grupo + '</b> por grupo' +
                (Number(r.categoria.mejores_segundos) > 0 ? ' + <b>' + r.categoria.mejores_segundos + '</b> mejores segundos' : '');
            $('#fx-chip-listo').innerHTML = r.listo_para_fixture
                ? '<span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Listo para generar partidos</span>'
                : '<span class="badge text-bg-warning"><i class="bi bi-exclamation-triangle me-1"></i>Faltan grupos / asignaciones</span>';
        }

        // Tarjetas de grupos
        estado.grupos.forEach(g => {
            const col = document.createElement('div');
            col.className = 'col-md-6 col-lg-4';
            const lleno = Number(g.cantidad_utes) >= Number(estado.resumen ? estado.resumen.categoria.equipos_por_grupo : 4);
            col.innerHTML =
                '<div class="card fx-grupo-card h-100">' +
                '<div class="card-header bg-white d-flex align-items-center justify-content-between">' +
                '<span><span class="badge rounded-circle fx-badge-letra text-bg-primary">' + esc(g.nombre_grupo) + '</span>' +
                '<strong class="ms-2">Grupo ' + esc(g.nombre_grupo) + '</strong>' +
                '<span class="badge text-bg-' + (lleno ? 'success' : 'secondary') + ' ms-2">' + g.cantidad_utes + '</span></span>' +
                '<button type="button" class="btn btn-sm btn-outline-danger borrar-grupo" data-grupo="' + g.id_grupo + '" title="Eliminar grupo"><i class="bi bi-trash"></i></button>' +
                '</div>' +
                '<div class="card-body d-flex flex-wrap gap-2 align-content-start">' +
                (g.utes.length
                    ? g.utes.map(u => chipUte(u, false)).join('')
                    : '<span class="text-muted small fst-italic">Sin equipos todavía</span>') +
                '</div></div>';
            cont.appendChild(col);
        });

        // Pool de UTEs sin grupo
        const sinGrupo = estado.utes.filter(u => !u.id_grupo);
        const pool = $('#fx-pool');
        pool.classList.toggle('d-none', !estado.idCat || sinGrupo.length === 0);
        $('#fx-pool-utes').innerHTML = sinGrupo.map(u => chipUte(u, true)).join('');
    }

    /* ---------- acciones ---------- */
    // Selector 1: Deporte → carga las categorías del deporte elegido.
    $('#fx-deporte').addEventListener('change', function () {
        estado.idDep = this.value ? Number(this.value) : null;
        estado.grupos = []; estado.utes = []; estado.resumen = null;
        render();
        if (estado.idDep) {
            cargarCategorias(estado.idDep);
        } else {
            limpiarSelectorCategoria('— Elegí primero un deporte —');
        }
    });

    // Selector 2: Categoría → carga grupos/UTEs de la categoría.
    $('#fx-cat').addEventListener('change', function () {
        estado.idCat = this.value ? Number(this.value) : null;
        estado.grupos = []; estado.utes = []; estado.resumen = null;
        render();
        if (estado.idCat) cargarEstado();
    });

    // Asignar UTE desde cualquier select (pool o tarjeta)
    document.addEventListener('change', function (e) {
        const sel = e.target.closest('select[data-ute]');
        if (!sel) return;
        post('ajax_ute_asignar_grupo', { id_ute: sel.dataset.ute, id_grupo: sel.value })
            .then(res => {
                if (!res.ok) { alerta(res.error, 'danger'); }
                cargarEstado();
            });
    });

    // Quitar UTE del grupo
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.quitargrupo');
        if (!btn) return;
        post('ajax_ute_asignar_grupo', { id_ute: btn.dataset.ute, id_grupo: '' })
            .then(res => { if (!res.ok) alerta(res.error, 'danger'); cargarEstado(); });
    });

    // Eliminar grupo
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.borrar-grupo');
        if (!btn) return;
        if (!confirm('¿Eliminar este grupo? Sus UTEs quedarán sin grupo.')) return;
        post('ajax_grupo_eliminar', { id_grupo: btn.dataset.grupo })
            .then(res => {
                if (!res.ok) { alerta(res.error, 'danger'); return; }
                cargarEstado();
            });
    });

    // Crear grupo (modal)
    const modalEl = $('#fxModalGrupo');
    const modal = new bootstrap.Modal(modalEl);
    $('#fx-btn-nuevo-grupo').addEventListener('click', function () {
        $('#fx-nombre-grupo').value = estado.siguiente || '';
        modal.show();
        setTimeout(() => $('#fx-nombre-grupo').focus(), 300);
    });
    $('#fx-confirmar-grupo').addEventListener('click', function () {
        post('ajax_grupo_crear', { id_categoria: estado.idCat, nombre_grupo: $('#fx-nombre-grupo').value })
            .then(res => {
                modal.hide();
                if (!res.ok) { alerta(res.error, 'danger'); return; }
                alerta('Grupo ' + res.nombre_grupo + ' creado.', 'success');
                cargarEstado();
            });
    });

    // Sorteo automático
    $('#fx-btn-sorteo').addEventListener('click', function () {
        if (!confirm('¿Repartir automáticamente las UTEs sin grupo entre los grupos creados?')) return;
        post('ajax_grupo_sorteo', { id_categoria: estado.idCat })
            .then(res => {
                if (!res.ok) { alerta(res.error, 'danger'); return; }
                alerta(res.asignadas + ' UTEs asignadas por sorteo.', 'success');
                cargarEstado();
            });
    });

    /* init */
    render();
    cargarDeportes();
})();
</script>

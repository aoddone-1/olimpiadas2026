<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premiación - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>

<div class="container mb-5 mt-4">

<div class="card shadow-sm border-0">
    <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column gap-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center">
                <i class="bi bi-award-fill me-2 text-warning"></i>
                <span>Premiación de la noche</span>
            </div>
            <div class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label class="form-label small fw-semibold mb-1"><i class="bi bi-calendar-event me-1"></i>Noche / ceremonia</label>
                    <input type="date" id="pm_fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <button id="pm_btn_hoy" class="btn btn-outline-secondary btn-sm">Hoy</button>
                <button id="pm_btn_refrescar" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- HERO resumen de la noche -->
        <div class="pm-hero p-3 p-md-4 mb-4 shadow">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="small text-uppercase fw-bold" style="opacity:.7"><i class="bi bi-stars me-1"></i>Ceremonia de premiación</div>
                    <div class="pm-fecha-hoy fw-bold" id="pm_hero_fecha">—</div>
                    <div class="small" id="pm_hero_sub" style="opacity:.8">Cargando…</div>
                </div>
                <div class="d-flex gap-3 flex-wrap">
                    <div class="pm-chip text-center">
                        <div class="pm-resumen-num" id="pm_stat_premiar">0</div>
                        <div class="pm-resumen-lbl">Para premiar</div>
                    </div>
                    <div class="pm-chip text-center">
                        <div class="pm-resumen-num" id="pm_stat_entregadas">0</div>
                        <div class="pm-resumen-lbl">Entregadas</div>
                    </div>
                    <div class="pm-chip text-center">
                        <div class="pm-resumen-num" id="pm_stat_medallas">0</div>
                        <div class="pm-resumen-lbl">Medallas</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="pm_mensaje" class="alert d-none"></div>

        <!-- Pendientes de premiar -->
        <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-box-seam me-1"></i>A entregar esta noche</h6>
        <div id="pm_pendientes" class="mb-4"></div>

        <!-- Ya entregadas -->
        <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-check2-circle me-1"></i>Premiaciones registradas</h6>
        <div id="pm_entregadas"></div>
    </div>
</div>

<!-- MODAL: confirmar premiación -->
<div class="modal fade" id="modalPremiacion" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background:linear-gradient(135deg,#14213d,#3a2f7c); color:#fff;">
                <h5 class="modal-title"><i class="bi bi-award-fill me-2"></i>Confirmar premiación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="pm_modal_info" class="mb-3"></div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">🥇 1º puesto (Oro)</label>
                        <input type="text" id="pm_in_1" class="form-control pm-input-puesto" style="--color-medalla:#f1c40f" placeholder="Nombre del ganador">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">🥈 2º puesto (Plata)</label>
                        <input type="text" id="pm_in_2" class="form-control pm-input-puesto" style="--color-medalla:#b9c2cc" placeholder="Segundo puesto">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">🥉 3º puesto (Bronce)</label>
                        <input type="text" id="pm_in_3" class="form-control pm-input-puesto" style="--color-medalla:#cd8b52" placeholder="Tercer puesto">
                    </div>
                </div>
                <div class="row g-2 mt-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Noche de entrega</label>
                        <input type="date" id="pm_modal_fecha" class="form-control">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">Observaciones (opcional)</label>
                        <input type="text" id="pm_modal_obs" class="form-control" maxlength="255" placeholder="Ej: se entrega en el acto de cierre">
                    </div>
                </div>
                <div class="alert alert-secondary small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Los nombres salen automáticamente de los resultados cargados; podés corregirlos antes de confirmar.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="pm_btn_confirmar" class="btn btn-success fw-bold">
                    <i class="bi bi-award-fill me-1"></i>Confirmar premiación
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const BASE = '<?= base_url("Inscripciones") ?>';
    const HOY = '<?= date('Y-m-d') ?>';
    const MEDALLAS = { 1: '🥇', 2: '🥈', 3: '🥉' };
    const CLASES   = { 1: 'pm-oro', 2: 'pm-plata', 3: 'pm-bronce' };
    const LABELS   = { 1: 'Campeón', 2: 'Subcampeón', 3: '3er puesto' };

    const $pend = document.getElementById('pm_pendientes');
    const $ent  = document.getElementById('pm_entregadas');
    const $msg  = document.getElementById('pm_mensaje');
    const $fecha = document.getElementById('pm_fecha');

    let modalPrem = null;
    let catActual = null;
    let itemsCache = [];

    function esc(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function mensaje(texto, tipo) {
        $msg.className = 'alert alert-' + tipo;
        $msg.textContent = texto;
        setTimeout(() => $msg.classList.add('d-none'), 4000);
    }
    function fechaArma(f) {
        if (!f) return '—';
        const p = f.split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : f;
    }
    function nombreLargo(f) {
        try {
            return new Date(f + 'T12:00:00').toLocaleDateString('es-AR',
                { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        } catch (e) { return f; }
    }
    function post(url, datos) {
        const form = new FormData();
        Object.keys(datos).forEach(k => form.append(k, datos[k]));
        return fetch(BASE + '/' + url, { method: 'POST', body: form })
            .then(r => r.json())
            .catch(() => ({ ok: false, error: 'Respuesta inesperada del servidor.' }));
    }

    /* ---------- Render ---------- */
    function cardPodio(item, entregada) {
        const c = item.categoria;
        const podio = item.podio || {};
        const regs = item.entregados || {};
        const origenBadge = item.origen === 'TIEMPO'
            ? '<span class="badge rounded-pill bg-info-subtle text-info-emphasis border pm-badge-origen">TIEMPO · top 3</span>'
            : '<span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border pm-badge-origen">MARCADOR · podio del torneo</span>';

        let celdas = '';
        [1, 2, 3].forEach(pos => {
            const p = podio[pos];
            const reg = regs[pos];
            const nombre = reg ? reg.nombre : (p ? p.nombre : null);
            if (nombre) {
                celdas += `
                    <div class="pm-medalla ${CLASES[pos]}">
                        <div class="pm-disco">${MEDALLAS[pos]}</div>
                        <div class="pm-puesto-label">${LABELS[pos]}</div>
                        <div class="pm-nombre">${esc(nombre)}</div>
                        ${p && p.extra ? `<div class="pm-extra">${esc(p.extra)}</div>` : ''}
                        ${reg ? '<div class="pm-extra text-success fw-bold"><i class="bi bi-check2-all"></i> entregado</div>' : ''}
                    </div>`;
            } else {
                celdas += `
                    <div class="pm-medalla pm-vacio">
                        <div class="pm-disco">${MEDALLAS[pos]}</div>
                        <div class="pm-puesto-label">${LABELS[pos]}</div>
                        <div class="pm-nombre">por definir</div>
                        <div class="pm-extra small">${item.origen === 'MARCADOR' ? 'faltan final / tercer puesto' : 'sin posición cargada'}</div>
                    </div>`;
            }
        });

        const fechaReg = (regs[1] && regs[1].fecha_entrega) || item.fecha_entrega;
        const acciones = entregada
            ? `<div class="d-flex justify-content-between align-items-center px-3 py-2 bg-white border-top flex-wrap gap-2">
                   <span class="small text-muted"><i class="bi bi-calendar-check me-1"></i>Registrada el ${fechaArma(fechaReg)}</span>
                   <div class="d-flex gap-2">
                       <button class="btn btn-sm btn-outline-primary pm-abrir" data-cat="${c.id_categoria}"><i class="bi bi-pencil me-1"></i>Editar</button>
                       <button class="btn btn-sm btn-outline-danger pm-anular" data-cat="${c.id_categoria}"><i class="bi bi-x-lg me-1"></i>Anular</button>
                   </div>
               </div>`
            : `<div class="d-flex justify-content-between align-items-center px-3 py-2 bg-white border-top flex-wrap gap-2">
                   <span class="small text-muted fst-italic"><i class="bi bi-hourglass-split me-1"></i>${esc(item.motivo || '')}</span>
                   <button class="btn btn-sm btn-success fw-bold pm-abrir" data-cat="${c.id_categoria}">
                       <i class="bi bi-award-fill me-1"></i>Premiar esta noche
                   </button>
               </div>`;

        return `
        <div class="card pm-card shadow-sm mb-3">
            <div class="pm-head">
                <div>
                    <span class="pm-deporte"><i class="bi bi-trophy-fill text-warning me-1"></i>${esc(c.nombre_deporte)}</span>
                    <span class="text-muted mx-1">›</span>
                    <span class="pm-cat">${esc(c.nombre_categoria)} (${esc(c.genero || '')})</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    ${origenBadge}
                    <span class="badge rounded-pill bg-dark-subtle text-dark-emphasis border">
                        <i class="bi bi-moon-stars me-1"></i>${fechaArma(item.fecha_entrega)}
                    </span>
                </div>
            </div>
            <div class="pm-cuerpo">
                ${entregada ? '<span class="pm-stamp">PREMIADO</span>' : ''}
                <div class="pm-podio">${celdas}</div>
            </div>
            ${acciones}
        </div>`;
    }

    function render(data) {
        itemsCache = (data.pendientes || []).concat(data.premiados || []);
        const pend = data.pendientes || [];
        const ent = data.premiados || [];

        document.getElementById('pm_hero_fecha').textContent = nombreLargo(data.fecha);
        document.getElementById('pm_stat_premiar').textContent = pend.length;
        document.getElementById('pm_stat_entregadas').textContent = ent.length;
        let medallas = 0;
        itemsCache.forEach(it => {
            [1, 2, 3].forEach(p => { if (it.podio && it.podio[p]) medallas++; });
        });
        document.getElementById('pm_stat_medallas').textContent = medallas;

        const sub = document.getElementById('pm_hero_sub');
        if (pend.length) {
            sub.textContent = pend.length + ' competencia(s) con podio listo para entregar y '
                + ent.length + ' ya premiada(s).';
        } else if (ent.length) {
            sub.textContent = 'Todo lo programado para esta noche ya fue premiado. ¡Impecable! 🎉';
        } else {
            sub.textContent = 'Todavía no hay competencias cerradas para premiar en esta fecha.';
        }

        $pend.innerHTML = pend.length
            ? pend.map(it => cardPodio(it, false)).join('')
            : '<div class="alert alert-light border d-flex align-items-center gap-2 mb-0">' +
              '<i class="bi bi-inbox fs-4 text-muted"></i><span class="small text-muted">' +
              'No hay podios pendientes para esta noche. Cuando cargues los resultados de una ' +
              'competencia cerrada, aparecerá acá automáticamente.</span></div>';

        $ent.innerHTML = ent.length
            ? ent.map(it => cardPodio(it, true)).join('')
            : '<div class="small text-muted fst-italic">Aún no registraste ninguna premiación.</div>';

        document.querySelectorAll('.pm-abrir').forEach(b =>
            b.addEventListener('click', () => abrirModal(b.dataset.cat)));
        document.querySelectorAll('.pm-anular').forEach(b =>
            b.addEventListener('click', () => anular(b.dataset.cat)));
    }

    function cargar() {
        const f = $fecha.value || HOY;
        fetch(BASE + '/ajax_premiacion_noche?fecha=' + encodeURIComponent(f),
              { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) {
                    $pend.innerHTML = '<div class="alert alert-warning py-2 small">' + esc(res.error || 'Error al cargar.') + '</div>';
                    $ent.innerHTML = '';
                    return;
                }
                render(res);
            })
            .catch(() => {
                $pend.innerHTML = '<div class="alert alert-danger py-2 small">No se pudo cargar la información de premiaciones.</div>';
            });
    }

    /* ---------- Modal confirmar ---------- */
    function abrirModal(idCat) {
        const it = itemsCache.find(x => String(x.categoria.id_categoria) === String(idCat));
        if (!it) return;
        catActual = it;

        // El panel vive en un pane que puede estar oculto: llevar el modal al body.
        const elModal = document.getElementById('modalPremiacion');
        if (elModal.parentElement !== document.body) document.body.appendChild(elModal);
        if (!modalPrem) modalPrem = new bootstrap.Modal(elModal);

        const c = it.categoria;
        document.getElementById('pm_modal_info').innerHTML =
            '<div class="d-flex flex-wrap align-items-center gap-2">' +
            '<span class="badge bg-dark"><i class="bi bi-trophy-fill me-1"></i>' + esc(c.nombre_deporte) + '</span>' +
            '<strong>' + esc(c.nombre_categoria) + '</strong>' +
            '<span class="text-muted small">(' + esc(c.genero || '') + ')</span>' +
            '<span class="badge ' + (it.origen === 'TIEMPO' ? 'bg-info' : 'bg-primary') + ' ms-2">' + esc(it.origen) + '</span>' +
            '</div>';

        const regs = it.entregados || {};
        [1, 2, 3].forEach(p => {
            const inp = document.getElementById('pm_in_' + p);
            const reg = regs[p];
            const dato = (it.podio || {})[p];
            inp.value = reg ? reg.nombre : (dato ? dato.nombre : '');
        });
        document.getElementById('pm_modal_fecha').value = $fecha.value || it.fecha_entrega || HOY;
        document.getElementById('pm_modal_obs').value = (regs[1] && regs[1].observaciones) || '';
        modalPrem.show();
    }

    document.getElementById('pm_btn_confirmar').addEventListener('click', () => {
        if (!catActual) return;
        const puestos = [];
        [1, 2, 3].forEach(p => {
            const v = document.getElementById('pm_in_' + p).value.trim();
            if (!v) return;
            const dato = (catActual.podio || {})[p] || {};
            puestos.push({ puesto: p, nombre: v, id_ute: dato.id || null });
        });
        if (!puestos.length) { mensaje('Completá al menos un puesto.', 'warning'); return; }

        post('ajax_confirmar_premiacion', {
            id_categoria: catActual.categoria.id_categoria,
            fecha_entrega: document.getElementById('pm_modal_fecha').value,
            observaciones: document.getElementById('pm_modal_obs').value,
            puestos: JSON.stringify(puestos)
        }).then(res => {
            if (res.ok) {
                modalPrem.hide();
                mensaje(res.mensaje, 'success');
                cargar();
            } else {
                mensaje(res.error || 'No se pudo registrar la premiación.', 'danger');
            }
        });
    });

    function anular(idCat) {
        if (!confirm('¿Anular la premiación registrada de esta categoría? Volverá a "pendiente".')) return;
        post('ajax_anular_premiacion', { id_categoria: idCat }).then(res => {
            mensaje(res.ok ? res.mensaje : (res.error || 'No se pudo anular.'), res.ok ? 'success' : 'danger');
            if (res.ok) cargar();
        });
    }

    /* ---------- Controles ---------- */
    document.getElementById('pm_btn_refrescar').addEventListener('click', cargar);
    document.getElementById('pm_btn_hoy').addEventListener('click', () => {
        $fecha.value = HOY;
        cargar();
    });
    $fecha.addEventListener('change', cargar);

    cargar();
})();
</script>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bundle.min.js"></script>
</body>
</html>

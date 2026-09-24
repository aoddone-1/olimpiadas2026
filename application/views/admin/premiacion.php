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
    <!-- SweetAlert2: confirmaciones estilizadas (con fallback a native si no carga) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>

<div class="container mb-5 mt-4">
<style>
/* ---------- Cabecera de la noche ---------- */
.pm-hero {
    background: linear-gradient(135deg, #14213d 0%, #1b2a5e 55%, #3a2f7c 100%);
    border-radius: 1rem;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.pm-hero::after {
    content: "🏆";
    position: absolute;
    right: -10px;
    bottom: -28px;
    font-size: 130px;
    opacity: .08;
    transform: rotate(-12deg);
}
.pm-fecha-hoy { font-size: 1.6rem; }
.pm-chip {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 2rem;
    padding: .35rem .9rem;
    font-size: .85rem;
    backdrop-filter: blur(4px);
}

/* ---------- Tarjetas de podio ---------- */
.pm-card { border: 0; border-radius: .9rem; overflow: hidden; transition: box-shadow .15s ease; }
.pm-card:hover { box-shadow: 0 .5rem 1.2rem rgba(20,33,61,.15) !important; }
.pm-card .pm-head { display:flex; justify-content:space-between; align-items:center; gap:.5rem; flex-wrap:wrap;
                    padding:.7rem 1rem; background:#fff; border-bottom:1px solid #eef0f4; }
.pm-deporte { font-weight: 800; color:#14213d; }
.pm-cat { color:#6c757d; font-size:.85rem; }

.pm-podio { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; padding: 1rem; background: #fbfcfe; }
@media (max-width: 700px){ .pm-podio { grid-template-columns: 1fr; } }

.pm-medalla {
    border-radius: .8rem; padding: .8rem .7rem; text-align: center;
    border: 2px solid transparent; background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,.06);
    display: flex; flex-direction: column; gap: .25rem; align-items: center; min-height: 118px; justify-content: center;
}
.pm-medalla .pm-disco { font-size: 1.7rem; line-height: 1; }
.pm-medalla .pm-puesto-label { font-size: .7rem; letter-spacing: .06em; text-transform: uppercase; font-weight: 700; color: #6c757d; }
.pm-medalla .pm-nombre { font-weight: 800; color: #14213d; word-break: break-word; }
.pm-medalla .pm-extra { font-size: .72rem; color: #6c757d; }
.pm-oro    { border-color: #f1c40f; background: linear-gradient(180deg,#fffdf2,#fff8db); }
.pm-plata  { border-color: #b9c2cc; background: linear-gradient(180deg,#fbfcfd,#eef2f6); }
.pm-bronce { border-color: #cd8b52; background: linear-gradient(180deg,#fffaf5,#fbeee2); }
.pm-vacio  { border-style: dashed; border-color: #d6dbe2; background: #fff; }
.pm-vacio .pm-nombre { color:#adb5bd; font-weight: 600; font-style: italic; }

.pm-stamp {
    position: absolute; top: .6rem; right: .8rem; font-size: .72rem; font-weight: 800;
    color: #146c43; border: 2px solid #146c43; border-radius: .4rem; padding: .05rem .45rem;
    transform: rotate(6deg); background: rgba(255,255,255,.85); letter-spacing: .04em;
}
.pm-cuerpo { position: relative; }

.pm-badge-origen { font-size: .68rem; letter-spacing: .05em; }
.pm-resumen-num { font-size: 2rem; font-weight: 800; line-height: 1; }
.pm-resumen-lbl { font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; opacity: .85; }

/* Inputs del modal */
.pm-input-puesto { border-left: 4px solid var(--color-medalla, #ccc); }

/* Asegurar que el tema global no pinte de blanco el texto oscuro */
.badge.text-dark, .badge.text-dark * { color: #000 !important; }
.pm-hero .pm-chip, .pm-hero .pm-chip * { color: #fff; }
</style>

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

<!-- MODAL: confirmar premiación (fuera del .container para que Bootstrap no
     herede contextos con transform/overflow y quede centrado) -->
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
                    ${item.atraso ? '<span class="badge rounded-pill bg-warning text-dark border pm-badge-origen"><i class="bi bi-exclamation-triangle-fill me-1"></i>Atraso</span>' : ''}
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
        // El filtro es por el DÍA en que se compitió: todo lo que aparece acá
        // tiene fecha_entrega == fecha seleccionada (o es un atraso anterior).
        const delDia = (data.pendientes || []).filter(it => !it.atraso);
        const atrasos = (data.pendientes || []).filter(it => it.atraso);
        const pend = delDia;
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
            sub.textContent = pend.length + ' competencia(s) cerrada(s) ese día con podio listo y '
                + ent.length + ' ya premiada(s).';
        } else if (ent.length) {
            sub.textContent = 'Todo lo que se compitió esa noche ya fue premiado. ¡Impecable! 🎉';
        } else {
            sub.textContent = 'No hay competencias cerradas cuyo día de competencia sea esta fecha.';
        }

        $pend.innerHTML = (pend.length
            ? pend.map(it => cardPodio(it, false)).join('')
            : '<div class="alert alert-light border d-flex align-items-center gap-2 mb-0">' +
              '<i class="bi bi-inbox fs-4 text-muted"></i><span class="small text-muted">' +
              'Ninguna competencia cerró ese día. Cuando cargues los resultados de una ' +
              'competencia jugada en esta fecha, aparecerá acá automáticamente.</span></div>')
            + (atrasos.length
                ? '<div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 py-2 small">'
                  + '<span><i class="bi bi-exclamation-triangle-fill me-1"></i>'
                  + '<strong>' + atrasos.length + ' premiación(es) de noches anteriores todavía sin entregar.</strong></span></div>'
                  + atrasos.map(it => cardPodio(it, false)).join('')
                : '');

        $ent.innerHTML = ent.length
            ? ent.map(it => cardPodio(it, true)).join('')
            : '<div class="small text-muted fst-italic">Aún no registraste ninguna premiación para este día.</div>';

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

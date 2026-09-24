<!-- PANEL RESULTADOS -->
<style>
/* Podio "top 3" de deportes con tiempo: fondo claro + SIEMPRE letra negra
   (el tema global pone .badge { color: #fff }, por eso el !important). */
.rs-podio.badge,
.rs-podio.badge strong,
.rs-podio.badge span { color: #000 !important; background-color: #fff !important; }
</style>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column gap-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center">
                 <i class="bi bi-trophy-fill me-2 text-success"></i>
                <span>Carga de Resultados</span>
            </div>
        </div>
    </div>
    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <!-- Filtros al estilo UTEs/Equipos -->
            <div class="d-flex flex-wrap gap-2 align-items-end" id="rs_filtros">
                <div>
                    <label class="form-label small fw-semibold mb-1"><i class="bi bi-trophy-fill text-success me-1"></i>Deporte</label>
                    <select id="rs_filtro_deporte" class="form-select form-select-sm" style="min-width:180px">
                        <option value="">Todos los Deportes</option>
                        <?php
                        $deportesVistos = [];
                        foreach ($categorias_fixture as$cat):
                            if (!in_array($cat['nombre_deporte'],$deportesVistos)):
                                $deportesVistos[] =$cat['nombre_deporte'];
                        ?>
                            <option value="<?= htmlspecialchars($cat['nombre_deporte']) ?>"><?= htmlspecialchars($cat['nombre_deporte']) ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-semibold mb-1"><i class="bi bi-layers-fill text-success me-1"></i>Categoría</label>
                    <select id="rs_filtro_categoria" class="form-select form-select-sm" style="min-width:230px">
                        <option value="" data-deporte="">Todas las Categorías</option>
                        <?php foreach ($categorias_fixture as$cat): ?>
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
                                <?php foreach ($categorias_fixture as$cat): ?>
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
                        <h6 class="fw-bold small mb-1"><i class="bi bi-stopwatch me-1"></i>Tiempos de los participantes</h6>
                        <p class="text-muted small mb-2">La posición se asigna automáticamente según el orden de carga.</p>
                        <div class="alert alert-warning py-2 small d-none" id="rs_sin_participantes">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            No hay participantes inscriptos en esta categoría todavía.
                        </div>
                        <table class="table table-sm align-middle mb-1" id="rs_tabla_tiempos">
                            <thead class="table-light">
                                <tr class="small fw-bold">
                                    <th style="width:50px">#</th>
                                    <th>Participante</th>
                                    <th style="width:170px">Tiempo (mm:ss)</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
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

<!-- MODAL: detalle de un resultado -->
<div class="modal fade" id="modalDetalleResultado" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-card-list me-2"></i>Detalle del Resultado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="rs_detalle_body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
    // El modal de carga vive dentro del pane oculto de la pestaña: lo pasamos al body
    // para que Bootstrap calcule bien el overlay y el scroll.
    document.body.appendChild(document.getElementById('modalResultado'));
    let modalResultado = new bootstrap.Modal(document.getElementById('modalResultado'));
    let modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalleResultado'));

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
        Object.keys(datos).forEach(k => {
            if (Array.isArray(datos[k])) {
                datos[k].forEach(val => form.append(k + '[]', val));
            } else {
                form.append(k, datos[k]);
            }
        });
        return fetch(BASE + '/' + url, { method: 'POST', body: form })
            .then(r => r.json())
            .catch(e => ({ ok: false, error: 'Respuesta inesperada del servidor. Revisá la consola.' }));
    }

    function fechaArma(f) {
        if (!f) return '—';
        const p = f.split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : f;
    }

    /* ---------- Helpers de marcador / posiciones ---------- */
    // Cada fila de resultado_detalle guarda el partido DESDE LA PERSPECTIVA de
    // su propio equipo: marcador_local = tantos del equipo de la fila,
    // marcador_visita = tantos del rival.
    // (Los resultados viejos quedaron guardados "espejados": las dos filas con
    // los mismos valores goles_1/goles_2. Eso hacía que siempre se leyera el
    // marcador local y pareciera un empate. Lo detectamos y corregimos abajo,
    // y además reparamos esos registros en el servidor al listar.)
    function leerFila(d) {
        return {
            id: d.id_ute !== null && d.id_ute !== undefined ? String(d.id_ute) : '',
            nombre: String(d.nombre_libre || ''),
            prop: parseInt(d.marcador_local, 10) || 0,
            rival: parseInt(d.marcador_visita, 10) || 0
        };
    }

    function infoMarcador(det) {
        const a = leerFila(det[0] || {});
        let b = det.length > 1 ? leerFila(det[1]) : { id: '', nombre: '', prop: a.rival, rival: a.prop };
        // ¿Filas espejadas (datos viejos)? Ambas muestran el mismo par de
        // números; el segundo equipo era en realidad el visitante.
        if (det.length > 1 && a.prop === b.prop && a.rival === b.rival) {
            b = { id: b.id, nombre: b.nombre, prop: a.rival, rival: a.prop };
        }
        const gan = a.prop === b.prop ? -1 : (a.prop > b.prop ? 0 : 1);
        return { e1: a.nombre || 'Equipo 1', e2: b.nombre || 'Equipo 2', s1: a.prop, s2: b.prop, gan: gan };
    }

    function nombreDet(d, fallback) {
        return (d && (d.nombre_libre || d.nombre_ute)) || fallback || '?';
    }

    function medallaPos(pos) {
        return MEDALLAS[pos - 1] || null;
    }

    /* ---------- Modal detalle ---------- */
    // El panel vive dentro de un .tab-custom-pane con display:none cuando no
    // está activo: Bootstrap calcularía mal el alto del modal. Lo movemos al
    // <body> la primera vez que se abre (mismo patrón que los otros modales).
    function asegurarModalEnBody() {
        const el = document.getElementById('modalDetalleResultado');
        if (el && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
    }

    function abrirDetalle(idResultado) {
        const r = todosResultados.find(x => String(x.id_resultado) === String(idResultado));
        if (!r) return;
        asegurarModalEnBody();
        const det = r.detalle || [];
        let html = `
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="badge ${BADGE_TIPO[r.tipo_resultado] || 'bg-secondary'}">${esc(r.tipo_resultado)}</span>
                <h5 class="mb-0 fw-bold">${esc(r.nombre_evento)}</h5>
            </div>
            <div class="row g-2 small mb-3">
                <div class="col-sm-6"><i class="bi bi-trophy text-success me-1"></i><strong>Deporte:</strong> ${esc(r.nombre_deporte || '—')}</div>
                <div class="col-sm-6"><i class="bi bi-layers text-success me-1"></i><strong>Categoría:</strong> ${esc(r.nombre_categoria || '—')}</div>
                <div class="col-sm-6"><i class="bi bi-calendar3 text-success me-1"></i><strong>Fecha:</strong> ${fechaArma(r.fecha_competencia || r.fecha_resultado)}</div>
                ${r.nombre_prueba ? `<div class="col-12"><i class="bi bi-journal-text text-success me-1"></i><strong>Prueba:</strong> ${esc(r.nombre_prueba)}</div>` : ''}
            </div>`;

        if (r.tipo_resultado === 'MARCADOR') {
            const m = infoMarcador(det);
            // Si hubo más de dos filas (parciales), mostrarlas como desglose.
            let parciales = '';
            if (det.length > 2) {
                parciales = `
                    <div class="mt-3 small text-muted">
                        <i class="bi bi-list-ul me-1"></i><strong>Parciales cargados:</strong>
                        ${det.map(d => `${esc(nombreDet(d, '?'))} (${(parseInt(d.marcador_local, 10) || 0)} : ${(parseInt(d.marcador_visita, 10) || 0)})`).join(' · ')}
                    </div>`;
            }
            html += `
                <div class="card border-0 bg-light shadow-none mb-3">
                    <div class="card-body py-4">
                        <div class="d-flex justify-content-between align-items-center text-center">
                            <div class="flex-fill ${m.gan === 0 ? 'text-success' : ''}">
                                <div class="fs-5 fw-bold text-truncate">${esc(m.e1)} ${m.gan === 0 ? '<i class="bi bi-trophy-fill"></i>' : ''}</div>
                            </div>
                            <div class="px-3">
                                <span class="badge bg-dark fs-3 px-4 py-2">${m.s1} : ${m.s2}</span>
                            </div>
                            <div class="flex-fill ${m.gan === 1 ? 'text-success' : ''}">
                                <div class="fs-5 fw-bold text-truncate">${m.gan === 1 ? '<i class="bi bi-trophy-fill"></i> ' : ''}${esc(m.e2)}</div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            ${m.gan < 0
                                ? '<span class="badge rounded-pill bg-secondary"><i class="bi bi-dash-lg me-1"></i>Empate</span>'
                                : `<span class="badge rounded-pill bg-success-subtle text-success-emphasis border fs-6">
                                       <i class="bi bi-trophy-fill me-1"></i>Ganador: <strong>${esc(m.gan === 0 ? m.e1 : m.e2)}</strong>
                                   </span>`}
                        </div>
                        ${parciales}
                    </div>
                </div>`;
        } else {
            const ordenados = [...det].sort((a, b) => (parseInt(a.posicion, 10) || 999) - (parseInt(b.posicion, 10) || 999));
            html += `
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small fw-bold">
                            <tr><th style="width:80px">Posición</th><th>Participante</th><th style="width:140px">Tiempo</th></tr>
                        </thead>
                        <tbody>`;
            if (!ordenados.length) {
                html += '<tr><td colspan="3" class="text-center text-muted py-3">Sin datos de detalle.</td></tr>';
            }
            ordenados.forEach(d => {
                const pos = parseInt(d.posicion, 10) || 0;
                const med = medallaPos(pos);
                html += `<tr class="${pos <= 3 ? 'table-warning-subtle' : ''}">
                    <td class="fw-bold">${med ? med + ' ' : ''}${pos}º</td>
                    <td>${esc(nombreDet(d, 'participante sin nombre'))}</td>
                    <td class="font-monospace">${d.tiempo ? esc(d.tiempo) : '—'}</td>
                </tr>`;
            });
            html += '</tbody></table></div>';
        }

        if (r.observaciones) {
            html += `<div class="alert alert-secondary small mt-3 mb-0">
                        <i class="bi bi-chat-left-text me-1"></i><strong>Observaciones:</strong> ${esc(r.observaciones)}
                     </div>`;
        }

        document.getElementById('rs_detalle_body').innerHTML = html;
        modalDetalle.show();
    }

    /* ---------- Render: agrupa por Deporte → Categoría ---------- */
    function render() {
        const dep = filtroDeporte.value.toLowerCase().trim();
        const cat = filtroCategoria.value;

        const items = todosResultados.filter(r => {
            if (dep && (r.nombre_deporte || '').toLowerCase().trim() !== dep) return false;
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
            const cantidadEnGrupo = grupos[clave].length;

            html += `<div class="card mb-3 shadow-sm">
                <div class="card-header bg-white py-2">
                    <span class="fw-bold"><i class="bi bi-trophy me-1"></i>${esc(partes[0])}</span>
                    <span class="text-muted mx-1">›</span><span>${esc(partes[1])}</span>
                    <span class="small text-muted ms-2">(${cantidadEnGrupo} resultado/s)</span>
                </div>
                <div class="list-group list-group-flush rs-grupo">`;

            grupos[clave].forEach(r => {
                let cuerpo = '';
                const det = r.detalle || [];
                if (r.tipo_resultado === 'MARCADOR') {
                    const m = infoMarcador(det);
                    // Resaltar al ganador en la lista rápida + cartelito con el resultado.
                    const cls1 = m.gan === 0 ? 'text-success' : (m.gan === 1 ? 'text-muted' : '');
                    const cls2 = m.gan === 1 ? 'text-success' : (m.gan === 0 ? 'text-muted' : '');
                    const cartel = m.gan < 0
                        ? '<span class="badge rounded-pill bg-secondary ms-2"><i class="bi bi-dash-lg me-1"></i>Empate</span>'
                        : `<span class="badge rounded-pill bg-success-subtle text-success-emphasis border ms-2">
                               <i class="bi bi-trophy-fill me-1"></i>Ganador: ${esc(m.gan === 0 ? m.e1 : m.e2)}
                           </span>`;
                    cuerpo = `<div class="mt-1">
                        <span class="fw-semibold ${cls1}">${m.gan === 0 ? '<i class="bi bi-trophy-fill small me-1"></i>' : ''}${esc(m.e1)}</span>
                        <span class="badge bg-dark mx-2 fs-6">${m.s1} : ${m.s2}</span>
                        <span class="fw-semibold ${cls2}">${m.gan === 1 ? '<i class="bi bi-trophy-fill small me-1"></i>' : ''}${esc(m.e2)}</span>
                        ${cartel}
                    </div>`;
                } else {
                    // Solo los 3 primeros "a ojo"; el resto se ve en el detalle.
                    const ordenados = [...det].sort((a, b) =>
                        (parseInt(a.posicion, 10) || 999) - (parseInt(b.posicion, 10) || 999));
                    const top = ordenados.slice(0, 3);
                    const resto = ordenados.length - top.length;
                    cuerpo = '<div class="d-flex flex-wrap gap-2 mt-1">' + top.map(d => {
                        const pos = parseInt(d.posicion, 10);
                        const medalla = MEDALLAS[pos - 1] || (pos + 'º');
                        return `<span class="badge rounded-pill bg-white border text-dark rs-podio">
                                    ${medalla} <strong>${esc(nombreDet(d, ''))}</strong>${d.tiempo ? ' · <span class="font-monospace">' + esc(d.tiempo) + '</span>' : ''}
                                </span>`;
                    }).join('') +
                    (resto > 0
                        ? `<span class="align-self-center small text-muted fst-italic">+ ${resto} puesto/s más <i class="bi bi-arrow-right-short"></i> Detalle</span>`
                        : '') +
                    '</div>';
                }

                html += `<div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div style="min-width:260px">
                        <span class="badge ${BADGE_TIPO[r.tipo_resultado] || 'bg-secondary'} me-1">${esc(r.tipo_resultado)}</span>
                        <strong>${esc(r.nombre_evento)}</strong>
                        ${r.nombre_prueba ? `<span class="small text-muted ms-1"><i class="bi bi-calendar3 me-1"></i>${esc(r.nombre_prueba)}</span>` : ''}
                        <div class="small text-muted">
                            <i class="bi bi-calendar-event me-1"></i>${fechaArma(r.fecha_competencia || r.fecha_resultado)}
                            ${r.observaciones ? '&nbsp;<i class="bi bi-chat-left-text me-1"></i>' + esc(r.observaciones) : ''}
                        </div>
                        ${cuerpo}
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary rs-detalle" data-id="${r.id_resultado}" title="Ver detalle del resultado">
                            <i class="bi bi-eye-fill me-1"></i>Detalle
                        </button>
                        <button class="btn btn-sm btn-outline-danger rs-borrar" data-id="${r.id_resultado}" title="Eliminar resultado">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>`;
            });

            html += '</div></div>';
        });
        lista.innerHTML = html;

        lista.querySelectorAll('.rs-detalle').forEach(b => b.addEventListener('click', () => abrirDetalle(b.dataset.id)));

        // Estética: en grupos con muchos resultados, mostrar solo los primeros 5 y plegar el resto.
        lista.querySelectorAll('.rs-grupo').forEach(grupo => {
            const items = [...grupo.querySelectorAll('.list-group-item')];
            if (items.length <= 5) return;
            const ocultos = items.slice(5);
            ocultos.forEach(el => el.classList.add('d-none'));
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-link btn-sm w-100 text-decoration-none fw-semibold';
            const actualizarTexto = () => {
                const visibles = items.filter(el => !el.classList.contains('d-none')).length;
                btn.innerHTML = visibles === items.length
                    ? '<i class="bi bi-chevron-up me-1"></i>Mostrar menos'
                    : `<i class="bi bi-chevron-down me-1"></i>Mostrar ${items.length - visibles} resultado/s más`;
            };
            btn.addEventListener('click', () => {
                const colapsado = items[5].classList.contains('d-none');
                ocultos.forEach(el => el.classList.toggle('d-none', !colapsado));
                actualizarTexto();
            });
            actualizarTexto();
            grupo.appendChild(btn);
        });

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

    /* ---------- Filtros ---------- */
    function filtrarOpcionesCategoria() {
        const deporte = filtroDeporte.value;
        Array.from(filtroCategoria.options).forEach(opt => {
            const dep = opt.getAttribute('data-deporte') || '';
            opt.hidden = !(deporte === '' || dep === '' || dep === deporte);
        });
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

    let fixturesDelModal = []; 
    let modalidadActual = ''; 
    let competidoresDisponibles = []; 
    let participantesPorFixture = {}; 

    function mostrarBloqueSegunTipo() {
        const t = selTipo.value;
        bloqueMarcador.classList.toggle('d-none', t !== 'MARCADOR');
        bloqueTiempo.classList.toggle('d-none', t !== 'TIEMPO');
    }

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
                    const fFx = f.fecha_competencia ? fechaArma(f.fecha_competencia) : '';
                    selFixture.insertAdjacentHTML('beforeend',
                        `<option value="${f.id_fixture}">F${esc(f.numero_fecha)} · ${esc(f.nombre_prueba)}` +
                        (equipos ? ' · ' + esc(equipos) : '') +
                        (fFx ? ' · 📅' + fFx : '') + ` (${esc(f.estado)})</option>`);
                });
            });
    }

    selFixture.addEventListener('change', function () {
        const f = fixturesDelModal.find(x => String(x.id_fixture) === String(this.value));
        if (!f) return;

        document.getElementById('rs_modal_nombre').value = f.nombre_prueba || '';
        const inpFecha = document.querySelector('#form_resultado input[name="fecha_resultado"]');
        if (inpFecha && f.fecha_competencia) inpFecha.value = f.fecha_competencia;

        if (selTipo.value === 'TIEMPO' || f.fase === 'JORNADA_UNICA') {
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

        const eq1 = document.getElementById('rs_eq1');
        const eq2 = document.getElementById('rs_eq2');
        eq1.value = f.ute_1_nombre || '';
        document.getElementById('rs_idute1').value = f.id_ute_1 || '';
        eq2.value = f.ute_2_nombre || '';
        document.getElementById('rs_idute2').value = f.id_ute_2 || '';
        document.getElementById('rs_g1').value = 0;
        document.getElementById('rs_g2').value = 0;
        setTimeout(() => eq1.focus(), 50);
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
                tbodyTiempos.innerHTML = '';
                const deJornada = participantesDeFixtureActual();
                if (deJornada.length) {
                    deJornada.forEach((c, i) => agregarFila(i + 1, c));
                } else {
                    competidoresDisponibles.forEach((c, i) => agregarFila(i + 1, c));
                }
                if (!tbodyTiempos.children.length) {
                    alertSinParticipantes.classList.remove('d-none');
                }
            })
            .catch(() => {
                alertSinParticipantes.classList.remove('d-none');
            });
    }

    function participantesDeFixtureActual() {
        if (!selFixture.value) return [];
        const lista = participantesPorFixture[selFixture.value]
                   || participantesPorFixture[String(selFixture.value)] || [];
        if (!competidoresDisponibles.length) return lista;
        const filtrados = lista.filter(c => competidoresDisponibles.some(d => String(d.id) === String(c.id)));
        return filtrados.length ? filtrados : lista;
    }

    function repoblarPlanillaTiempos() {
        if (selTipo.value !== 'TIEMPO') return;
        tbodyTiempos.innerHTML = '';
        const deJornada = participantesDeFixtureActual();
        const lista = deJornada.length ? deJornada : competidoresDisponibles;
        lista.forEach((c, i) => agregarFila(i + 1, c));
        alertSinParticipantes.classList.toggle('d-none', lista.length > 0);
    }

    function limpiarPlanillaTiempos() {
        tbodyTiempos.innerHTML = '';
        competidoresDisponibles = [];
        alertSinParticipantes.classList.add('d-none');
    }

    function agregarFila(pos, competidor) {
        const tr = document.createElement('tr');
        const c = competidor || null;
        const nombreTxt = c
            ? esc(c.nombre)
              + (c.integrantes ? ' <span class="text-muted small fw-normal">(' + esc(c.integrantes) + ')</span>' : '')
              + (!c.integrantes && c.dni ? ' <span class="text-muted small fw-normal">(' + esc(c.dni) + ')</span>' : '')
            : '<span class="text-muted fst-italic small">participante sin nombre</span>';
        tr.innerHTML = `
            <td class="text-muted small fw-bold">${pos}º</td>
            <td class="small fw-semibold text-truncate" style="max-width:260px">${nombreTxt}</td>
            <td>
                <input type="hidden" name="comp_id[]" value="${c ? esc(String(c.id)) : ''}">
                <input type="hidden" name="comp_nombre[]" value="${c ? esc(c.nombre || '') : ''}">
                <input type="text" name="comp_tiempo[]" class="form-control form-control-sm" placeholder="Ej: 18:42">
            </td>`;
        tbodyTiempos.appendChild(tr);
    }

    document.getElementById('rs_btn_nuevo').addEventListener('click', () => {
        document.getElementById('form_resultado').reset();
        selCatModal.value = '';
        selTipo.value = 'MARCADOR';
        selFixture.innerHTML = '<option value="">— Sin vincular —</option>';
        datalistUtes.innerHTML = '';
        tbodyTiempos.innerHTML = '';
        mostrarBloqueSegunTipo();
        modalResultado.show();
    });

    document.getElementById('rs_btn_guardar').addEventListener('click', () => {
        const fd = new FormData(document.getElementById('form_resultado'));
        const datos = {};
        for (const [k, v] of fd.entries()) {
            if (!k.endsWith('[]')) datos[k] = v;
        }

        // Se recopilan los arrays individualmente
        datos['comp_nombre'] = [...tbodyTiempos.querySelectorAll('input[name="comp_nombre[]"]')].map(i => i.value);
        datos['comp_id']     = [...tbodyTiempos.querySelectorAll('input[name="comp_id[]"]')].map(i => i.value);
        datos['comp_tiempo'] = [...tbodyTiempos.querySelectorAll('input[name="comp_tiempo[]"]')].map(i => i.value);
        datos['comp_posicion'] = datos['comp_nombre'].map((_, i) => String(i + 1));

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
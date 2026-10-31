<div class="modal fade" id="modalDetalleInscripcion" tabindex="-1" aria-labelledby="modalDetalleInscripcionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalDetalleInscripcionLabel">
                    <i class="bi bi-person-vcard-fill me-2"></i>Detalles de Inscripción Oficial
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle">
                <div class="d-flex align-items-center border-bottom pb-3 mb-4 gap-3">
                    <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-person text-primary fs-3"></i>
                    </div>
                    <div>
                        <h4 class="text-dark fw-bold mb-0" id="det-nombre"></h4>
                        <span class="badge bg-secondary border mt-1" id="det-rol"></span>
                        <span class="badge bg-danger border mt-1 ms-1" id="det-badge-delegado" style="display:none;">DELEGADO</span>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white">
                            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2"><i class="bi bi-info-circle me-2"></i>Datos Personales</h6>
                            <p class="mb-2"><strong>DNI:</strong> <span class="text-muted" id="det-dni"></span></p>
                            <p class="mb-2"><strong>Sexo:</strong> <span class="text-muted" id="det-sexo"></span></p>
                            <p class="mb-2"><strong>Fecha Nacimiento:</strong> <span class="text-muted" id="det-fnac"></span></p>
                            <p class="mb-2"><strong>Grupo Sanguíneo:</strong> <span class="badge bg-light text-dark border px-2 fw-bold" id="det-gsanguineo"></span></p>
                            <p class="mb-2"><strong>Obra Social:</strong> <span class="text-muted" id="det-osocial"></span></p>
                            <p class="mb-0"><strong>Tipo de Empleado:</strong> <span class="text-muted" id="det-empleado"></span></p>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white">
                            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2"><i class="bi bi-telephone me-2"></i>Contacto y Logística</h6>
                            <p class="mb-2"><strong>Email:</strong> <span class="text-muted" id="det-email"></span></p>
                            <p class="mb-2"><strong>Teléfono:</strong> <span class="text-muted" id="det-telefono"></span></p>
                            <p class="mb-2"><strong>Delegación:</strong> <span class="badge bg-light text-dark border fw-semibold" id="det-delegacion"></span></p>
                            <p class="mb-2"><strong>Contacto Emergencia:</strong> <span class="text-muted" id="det-emergencia"></span></p>
                            <p class="mb-0"><strong>Dieta Especial:</strong> <span class="text-muted fw-semibold" id="det-dieta"></span></p>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                            <h6 class="text-success fw-bold mb-3 border-bottom pb-2"><i class="bi bi-trophy me-2"></i>Disciplinas Deportivas Asignadas</h6>
                            
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">
                                        <tr>
                                            <th style="width: 25%;"><i class="bi bi-flag-fill me-1"></i> Deporte</th>
                                            <th style="width: 25%;"><i class="bi bi-tags-fill me-1"></i> Categoría</th>
                                            <th style="width: 20%;" class="text-center"><i class="bi bi-diagram-3-fill me-1"></i> UTE</th>
                                            <th style="width: 30%;"><i class="bi bi-chat-left-text-fill me-1"></i> Observación / Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody id="det-tabla-deportes-body">
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="text-secondary fw-bold mb-3 border-bottom pb-2" style="font-size: 0.9rem;"><i class="bi bi-building me-2"></i>Logística de Estadía y Registro</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <p class="mb-0"><strong>Hotel Asignado:</strong><br><span class="fw-bold text-dark fs-6" id="det-hotel"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-0"><strong>Alta de Registro:</strong><br><span class="text-muted small" id="det-finscripcion"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2">
                <!-- Descarga del deslinde de responsabilidad del participante.
                     El href se completa con el id cuando llega el JSON de
                     detalle_ajax (ver inscriptos: js btn-descargar-deslinde). -->
                <a id="btn-descargar-deslinde"
                   href="#"
                   target="_blank"
                   rel="noopener"
                   class="btn btn-outline-danger rounded-pill btn-sm px-4 disabled">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Descargar Deslinde
                </a>
                <button type="button" class="btn btn-secondary rounded-pill btn-sm px-4" data-bs-dismiss="modal">Cerrar Detalle</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: IMPRIMIR CREDENCIALES (acreditaciones por delegación) -->
<!-- ============================================================ -->
<div class="modal fade" id="modalImprimirCredenciales" tabindex="-1" aria-labelledby="modalImprimirCredencialesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold" id="modalImprimirCredencialesLabel">
                    <i class="bi bi-person-badge-fill me-2"></i>Imprimir Credenciales
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    Seleccioná la delegación y el modo de impresión para generar el PDF
                    con las acreditaciones (rectángulos de 8.5 × 13 cm listos para recortar):
                </p>
                <ul class="list-group list-group-flush mb-3 small">
                    <li class="list-group-item px-0">
                        <i class="bi bi-printer-fill text-primary me-1"></i>
                        <strong>Todas:</strong> genera el PDF con las credenciales de todos los participantes de la delegación.
                        (Requiere elegir una delegación.)
                    </li>
                    <li class="list-group-item px-0">
                        <i class="bi bi-file-earmark-x-fill text-warning-emphasis me-1"></i>
                        <strong>No impresas:</strong> solo las que tienen <code>credencial_impresa</code> en NULL
                        (nunca imprimidas o con reimpresión habilitada desde Inscriptos).
                        <strong>No hace falta elegir delegación:</strong> si la dejás vacía, se genera un único PDF
                        con las pendientes de TODAS las provincias.
                    </li>
                </ul>
                <label for="selDelegacionCredenciales" class="form-label fw-semibold">
                    <i class="bi bi-geo-alt-fill text-primary me-1"></i>Delegación / Provincia
                    <span id="lblDelegacionOpcional" class="text-muted fw-normal small d-none">(opcional en modo "No impresas")</span>
                </label>
                <select id="selDelegacionCredenciales" class="form-select mb-1">
                    <option value="">— Elegí una delegación —</option>
                    <?php if (!empty($delegaciones)): ?>
                        <?php foreach ($delegaciones as $deleg): ?>
                            <option value="<?= htmlspecialchars($deleg, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($deleg, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <!-- Contador de credenciales pendientes (solo modo "no impresas") -->
                <div id="contadorNoImpresas" class="small text-muted mb-3 d-none">
                    <i class="bi bi-hourglass-split me-1"></i><span id="contadorNoImpresasTexto"></span>
                </div>
                <label class="form-label fw-semibold d-block">
                    <i class="bi bi-funnel-fill text-primary me-1"></i>Modo de impresión
                </label>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold sel-modo-credenciales active" data-modo="todas">
                        <i class="bi bi-collection-fill me-1"></i>Todas
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill fw-semibold sel-modo-credenciales" data-modo="no_impresas">
                        <i class="bi bi-printer-fill me-1"></i>Imprimir no impresas
                    </button>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2">
                <button type="button" class="btn btn-secondary rounded-pill btn-sm px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnDescargarCredenciales" class="btn btn-warning rounded-pill btn-sm px-4 fw-bold" disabled>
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i>Descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selDeleg = document.getElementById('selDelegacionCredenciales');
    const btnDescargar = document.getElementById('btnDescargarCredenciales');
    if (!selDeleg || !btnDescargar) return;

    const lblOpcional = document.getElementById('lblDelegacionOpcional');
    const divContador = document.getElementById('contadorNoImpresas');
    const spanContador = document.getElementById('contadorNoImpresasTexto');

    // Modo de impresión seleccionado en el modal ("todas" | "no_impresas")
    let modoCredenciales = 'todas';

    // Estado del botón "Descargar PDF":
    //  - modo "todas": requiere delegación seleccionada (como siempre).
    //  - modo "no_impresas": la delegación es OPCIONAL; sin delegación se
    //    descargan las pendientes de todas las provincias.
    function actualizarBoton() {
        btnDescargar.disabled = (modoCredenciales === 'todas' && selDeleg.value === '');
        btnDescargar.innerHTML = (modoCredenciales === 'no_impresas')
            ? (selDeleg.value === ''
                ? '<i class="bi bi-printer-fill me-1"></i>Descargar NO impresas (todas las provincias)'
                : '<i class="bi bi-printer-fill me-1"></i>Descargar NO impresas')
            : '<i class="bi bi-file-earmark-pdf-fill me-1"></i>Descargar PDF';
    }

    // Consulta cuántas credenciales hay pendientes para el modo/delegación
    // actuales y muestra el contador en el modal. Reutiliza el endpoint de
    // conteo que ya se usa para marcar la impresión tras descargar.
    let ultimoConteo = 0;
    function actualizarContador() {
        if (modoCredenciales !== 'no_impresas') {
            divContador.classList.add('d-none');
            return;
        }
        fetch('<?= base_url('Inscripciones/contar_participantes_delegacion') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'delegacion=' + encodeURIComponent(selDeleg.value)
                + '&modo=no_impresas'
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data || !data.success) return;
            ultimoConteo = data.ids.length;
            spanContador.textContent = (selDeleg.value === '')
                ? ultimoConteo + ' credenciales pendientes en todas las provincias.'
                : ultimoConteo + ' credenciales pendientes en ' + selDeleg.value + '.';
            divContador.classList.remove('d-none');
            if (ultimoConteo === 0) {
                btnDescargar.disabled = true;
                btnDescargar.innerHTML = '<i class="bi bi-check2-circle me-1"></i>No hay pendientes';
            } else {
                actualizarBoton();
            }
        })
        .catch(function () { /* el contador es informativo: si falla, no bloquea */ });
    }

    const botonesModo = document.querySelectorAll('.sel-modo-credenciales');
    botonesModo.forEach(function (btn) {
        btn.addEventListener('click', function () {
            modoCredenciales = this.getAttribute('data-modo') || 'todas';
            botonesModo.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            lblOpcional.classList.toggle('d-none', modoCredenciales !== 'no_impresas');
            actualizarBoton();
            actualizarContador();
        });
    });

    // Habilitar/deshabilitar el botón según selección
    selDeleg.addEventListener('change', function () {
        actualizarBoton();
        if (modoCredenciales === 'no_impresas') actualizarContador();
    });

    btnDescargar.addEventListener('click', function () {
        const delegacion = selDeleg.value;
        // En modo "todas" la delegación es obligatoria; en "no_impresas" puede
        // ir vacía (= todas las provincias).
        if (modoCredenciales !== 'no_impresas' && !delegacion) return;
        if (modoCredenciales === 'no_impresas' && ultimoConteo === 0) return;
        // IMPORTANTE: se envía como POST en un formulario oculto (no por GET).
        // Razones:
        //  1) CodeIgniter filtra los caracteres de la URI (permitted_uri_chars),
        //     por eso "Córdoba" no puede ir como segmento de URL.
        //  2) En algunos hosts (p. ej. SiteGround con ModSecurity/Anti-Bot System)
        //     una URL con query string que contiene parámetros "sospechosos"
        //     devuelve 403 Forbidden antes de llegar a PHP. Con POST en el body
        //     la URL queda limpia (/index.php/inscripciones/descargar_acreditaciones)
        //     y no se dispara el filtro.
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('Inscripciones/descargar_acreditaciones') ?>';
        form.target = '_blank';
        form.style.display = 'none';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delegacion';
        input.value = delegacion;
        form.appendChild(input);

        // "modo": todas las credenciales o solo las NO impresas
        // (credencial_impresa IS NULL).
        const inputModo = document.createElement('input');
        inputModo.type = 'hidden';
        inputModo.name = 'modo';
        inputModo.value = modoCredenciales;
        form.appendChild(inputModo);

        document.body.appendChild(form);
        form.submit();
        form.remove();

        // FEEDBACK DE IMPRESIÓN: marcamos credencial_impresa = NOW() para los
        // participantes incluidos en el PDF recién generado. Así quedan fuera
        // del próximo "Imprimir no impresas". El conteo se hace DESPUÉS de
        // enviar el formulario (la pestaña nueva ya empezó a descargar el PDF;
        // esta petición fetch corre en paralelo y no lo retrasa).
        const urlConteo = '<?= base_url('Inscripciones/contar_participantes_delegacion') ?>';
        setTimeout(function () {
            fetch(urlConteo, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'delegacion=' + encodeURIComponent(delegacion)
                    + '&modo=' + encodeURIComponent(modoCredenciales)
            })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.success && data.ids.length > 0) {
                    return fetch('<?= base_url('Inscripciones/marcar_credenciales_impresas') ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'ids=' + encodeURIComponent(data.ids.join(','))
                    });
                }
            })
            .catch(function (err) { console.warn('No se pudo registrar la impresión de credenciales:', err); });
        }, 1500);
    });
});
</script>
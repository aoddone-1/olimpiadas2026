 <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscriptos - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('css/style.css?20261022') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- SweetAlert2: confirmaciones estilizadas (con fallback a native si no carga) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php $this->load->view('admin/header_admin'); ?>
<?php include("modals/control_total_modals.php");?>

<?php
// ============================================
// MODO SOLO LECTURA: si el rol del usuario es 'solo_lectura',
// toda la pantalla de inscriptos queda de solo lectura (sin
// crear, modificar ni eliminar). Con 'superadmin' funciona igual que siempre.
// ============================================
$solo_lectura = ($this->session->userdata('user_rol') === 'solo_lectura');
?>
<?php if ($solo_lectura): ?>
<div class="alert alert-info mb-3 border-0 shadow-sm rounded-pill py-2 small fw-semibold text-center" role="alert">
    <i class="bi bi-eye-fill me-1"></i> Estás en modo SOLO LECTURA: podés ver y buscar los inscriptos, pero no crearlos, modificarlos ni eliminarlos.
</div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4 card-inscriptos">

    <div class="card-header fx-card-header fw-bold text-secondary d-flex flex-column gap-3">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fx-header-icon d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-people-fill text-primary me-2"></i> 
                </span>
                <span class="fx-header-titulo">Inscriptos Oficiales <u><?= LUGAR_OLIMPICO2 ?></u></span>
                <span class="badge bg-secondary ms-2" id="contador-inscripciones"><?= count($listado_inscripciones) ?> filas</span>            
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if (!$solo_lectura): ?>
                <button type="button" class="btn btn-lg  btn-warning"
                        data-bs-toggle="modal" data-bs-target="#modalImprimirCredenciales">
                    <i class="bi bi-person-badge-fill me-1"></i>
                </button>
                <?php endif; ?>

                <?php if (!$solo_lectura): ?>
                <a href="<?= base_url('Inscripciones/nueva_inscripcion') ?>" 

                   class="btn btn-lg btn-danger"

                   title="Cargar nueva inscripción">

                    <i class="bi bi-person-add me-1"></i>

                    

                </a>
                <?php endif; ?>

                

                <a href="<?= base_url('Inscripciones/descargar_csv_todos_inscriptos') ?>" 

                   class="btn btn-lg btn-success "

                   title="Descargar lista completa en CSV">

                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>

                </a>

                

                <div class="position-relative" style="max-width: 300px; width: 100%;">

                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>

                    <input type="text" 

                           id="inputBuscarInscripcion" 

                           class="form-control form-control-sm rounded-pill ps-5 bg-light" 

                           placeholder="Buscar por Nombre, DNI, Hotel...">

                </div>

            </div>

        </div>

        

        <!-- Panel de Filtros -->

       

    </div>

    

    <div class="card-body">

        <div class="fx-filtros row g-2 align-items-end mb-3" id="panelFiltros">

            <div class="col-md-3">

                <label for="filtroDelegacion" class="form-label small fw-semibold mb-1">

                    <i class="bi bi-geo-alt-fill text-primary me-1"></i>Provincia

                </label>

                <select id="filtroDelegacion" class="form-select form-select-sm">

                    <option value="">Todas las Provincias</option>

                    <option value="Buenos Aires">Buenos Aires</option>

                    <option value="CABA">CABA</option>

                    <option value="La Pampa">La Pampa</option>

                    <option value="Catamarca">Catamarca</option>

                    <option value="Chaco">Chaco</option>

                    <option value="Chubut">Chubut</option>

                    <option value="Córdoba">Córdoba</option>

                    <option value="Corrientes">Corrientes</option>

                    <option value="Entre Ríos">Entre Ríos</option>

                    <option value="Formosa">Formosa</option>

                    <option value="Jujuy">Jujuy</option>

                    <option value="La Rioja">La Rioja</option>

                    <option value="Mendoza">Mendoza</option>

                    <option value="Misiones">Misiones</option>

                    <option value="Neuquén">Neuquén</option>

                    <option value="Río Negro">Río Negro</option>

                    <option value="Salta">Salta</option>

                    <option value="San Juan">San Juan</option>

                    <option value="San Luis">San Luis</option>

                    <option value="Santa Cruz">Santa Cruz</option>

                    <option value="Santa Fe">Santa Fe</option>

                    <option value="Santiago del Estero">Santiago del Estero</option>

                    <option value="Tierra del Fuego">Tierra del Fuego</option>

                    <option value="Tucumán">Tucumán</option>

                </select>

            </div>

            

            <div class="col-md-2">

                <label for="filtroTipo" class="form-label small fw-semibold mb-1">

                    <i class="bi bi-person-badge-fill text-primary me-1"></i>Tipo de Inscripción

                </label>

                <select id="filtroTipo" class="form-select form-select-sm">

                    <option value="">Todos los Tipos</option>

                    <option value="1">Competidor</option>

                    <option value="0">Acompañante</option>

                </select>

            </div>

            

            <div class="col-md-3">

                <label for="filtroDeporte" class="form-label small fw-semibold mb-1">

                    <i class="bi bi-trophy-fill text-primary me-1"></i>Deporte

                </label>

                <select id="filtroDeporte" class="form-select form-select-sm">

                    <option value="">Todos los Deportes</option>

                    <?php if(!empty($deportes_db)): foreach($deportes_db as $dep): ?>

                        <option value="<?= htmlspecialchars($dep['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>">

                            <?= htmlspecialchars($dep['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>

                        </option>

                    <?php endforeach; endif; ?>

                </select>

            </div>

            

            <div class="col-md-2">

                <label for="filtroSexo" class="form-label small fw-semibold mb-1">

                    <i class="bi bi-gender-ambiguous text-primary me-1"></i>Sexo

                </label>

                <select id="filtroSexo" class="form-select form-select-sm">

                    <option value="">Todos</option>

                    <option value="Masculino">Masculino</option>

                    <option value="Femenino">Femenino</option>

                    <option value="Otro">Otro</option>

                </select>

            </div>



            <div class="col-md-2">
                <button id="btnLimpiarFiltros" class="btn fx-btn-round fx-btn-limpiar" title="Quitar los filtros activos">
                    <i class="bi bi-x-circle"></i><span class="ms-2">Limpiar filtros</span>
                </button>
            </div>

        </div>
        <div class="table-responsive">

            <table class="table table-hover align-middle js-tabla-inscripciones" id="tablaInscripciones">

                <thead class="small text-uppercase">

                    <tr>

                        <th><i class="bi bi-person-lines-fill me-1"></i>Participante / DNI</th>

                        <th><i class="bi bi-geo-alt me-1"></i>Delegación</th>

                        <th><i class="bi bi-person-badge me-1"></i>Rol / Asistencia</th>

                        <th><i class="bi bi-trophy me-1"></i>Deporte / Categoría</th>

                        <th><i class="bi bi-building me-1"></i>Hotel Alojamiento</th>

                        <th class="text-center"><i class="bi bi-box-seam me-1"></i>Kit</th>

                        <th class="text-center"><i class="bi bi-sliders me-1"></i>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if(!empty($listado_inscripciones)): $idx_ins = 0; foreach($listado_inscripciones as $ins): ?>

                    <?php

                        // Datos para la fila (avatar con iniciales y colores según sexo)

                        $sexo_inscrito = trim($ins['sexo'] ?? '');

                        $sx = strtolower($sexo_inscrito);

                        $nombre_ins = trim($ins['nombre_completo'] ?? '');

                        $iniciales_ins = '';

                        foreach(preg_split('/\s+/', $nombre_ins) as $palabra_ins) {

                            if ($palabra_ins !== '' && mb_strlen($iniciales_ins) < 2) {

                                $iniciales_ins .= mb_strtoupper(mb_substr($palabra_ins, 0, 1));

                            }

                        }

                        if ($iniciales_ins === '') $iniciales_ins = '?';

                        $color_avatar = $sx === 'femenino' ? '#e83e8c' : ($sx === 'masculino' ? '#2a5298' : '#6c757d');

                        $par_ins = (++$idx_ins) % 2 === 0;

                    ?>

                    <tr class="js-fila-inscripcion<?= $par_ins ? ' fila-par' : '' ?>">

                        <td class="ps-3">

                            <div class="d-flex align-items-center gap-2">

                                <span class="js-avatar-ins d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0 fw-bold text-white"

                                      style="width:38px;height:38px;font-size:0.8rem;background:linear-gradient(135deg, <?= $color_avatar ?> 0%, <?= $color_avatar ?>cc 100%);box-shadow:0 2px 5px rgba(0,0,0,.18);"><?= htmlspecialchars($iniciales_ins, ENT_QUOTES, 'UTF-8') ?></span>

                                <div class="min-w-0">

                                    <div class="fw-bold text-dark text-truncate" style="max-width: 240px;" title="<?= htmlspecialchars($nombre_ins, ENT_QUOTES, 'UTF-8') ?>">

                                        <?= htmlspecialchars($nombre_ins, ENT_QUOTES, 'UTF-8') ?>

                                        <?php if(($ins['es_delegado'] ?? 0) == 1): ?>

                                            <span class="badge bg-danger bg-gradient text-uppercase ms-1 js-badge-delegado" style="font-size:0.6rem;">&#9733; Delegado</span>

                                        <?php endif; ?>

                                    </div>

                                    <small class="text-muted font-monospace">DNI: <?= htmlspecialchars($ins['dni'], ENT_QUOTES, 'UTF-8') ?></small>

                                    <?php if($sexo_inscrito !== ''): ?>

                                        <span class="badge js-badge-sexo ms-1 border <?php echo $sx === 'femenino' ? 'bg-danger-subtle text-danger' : ($sx === 'masculino' ? 'bg-primary-subtle text-primary' : 'bg-light text-secondary'); ?>" style="font-size:0.65rem;">

                                            <i class="bi <?= $sx === 'femenino' ? 'bi-gender-female' : ($sx === 'masculino' ? 'bi-gender-male' : 'bi-gender-trans') ?> me-1"></i><?= htmlspecialchars($sexo_inscrito, ENT_QUOTES, 'UTF-8') ?>

                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </td>

                        

                        <td>

                            <span class="badge bg-light text-primary border rounded-pill px-2 py-1">

                                <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($ins['delegacion'] ?? 'Sin Especificar', ENT_QUOTES, 'UTF-8') ?>

                            </span>

                        </td>



                        <td>

                            <?php if(($ins['es_competidor'] ?? 1) == 0): ?>

                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill small">

                                    <i class="bi bi-person-video3 me-1"></i>Acompañante</span>

                            <?php else: ?>

                                <span class="badge bg-primary-subtle text-primary border rounded-pill small">

                                    <i class="bi bi-flag-fill me-1"></i>Competidor</span>

                            <?php endif; ?>

                        </td>

                        

                        <td>

                            <?php if(!empty($ins['deportes_nombres'])): ?>

                                <div class="fw-semibold text-dark text-uppercase small" style="max-width: 250px; white-space: normal;">

                                    <?= htmlspecialchars($ins['deportes_nombres'], ENT_QUOTES, 'UTF-8') ?>

                                </div>

                                <div class="text-muted" style="font-size: 0.8rem; max-width: 250px; white-space: normal;">

                                    <i class="bi bi-tag-fill me-1" style="font-size:0.7rem;"></i>Cat: <?= htmlspecialchars($ins['categorias_nombres'], ENT_QUOTES, 'UTF-8') ?>

                                </div>

                            <?php else: ?>

                                <span class="text-muted small fst-italic">— Sin deporte —</span>

                            <?php endif; ?>

                        </td>

                        

                        <td>

                            <?php if(!empty($ins['hotel_alojamiento'])): ?>

                                <div class="small fw-semibold text-success">

                                    <i class="bi bi-buildings me-1"></i><?= htmlspecialchars($ins['hotel_alojamiento'], ENT_QUOTES, 'UTF-8') ?>

                                </div>

                            <?php else: ?>

                                <span class="badge bg-warning-subtle text-warning-emphasis border rounded-pill small fw-bold">

                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>SIN ASIGNAR</span>

                            <?php endif; ?>

                        </td>

                        

                        <!-- NUEVO: columna KIT. Botón rápido uno por uno:
                             clic = acreditar entrega (verde) / clic de nuevo = revertir. -->

                        <td class="text-center js-kit-celda">

                            <?php $kit_entregado_ins = (($ins['kit_entregado'] ?? 0) == 1); ?>

                            <?php if ($solo_lectura): ?>

                                <span class="badge rounded-pill <?= $kit_entregado_ins ? 'bg-success-subtle text-success border' : 'bg-light text-muted border' ?>"
                                      title="<?= $kit_entregado_ins ? 'Kit ENTREGADO' : 'Kit pendiente de entrega' ?>">
                                    <i class="bi <?= $kit_entregado_ins ? 'bi-check-circle-fill' : 'bi-box-seam' ?> me-1"></i><?= $kit_entregado_ins ? 'Entregado' : 'Pendiente' ?>
                                </span>

                            <?php else: ?>

                                <button type="button"
                                        class="btn btn-sm rounded-pill border fw-semibold js-btn-kit <?= $kit_entregado_ins ? 'btn-success' : 'btn-outline-secondary' ?>"
                                        data-id="<?= $ins['id_participante'] ?>"
                                        data-nombre="<?= htmlspecialchars($ins['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-estado="<?= $kit_entregado_ins ? 1 : 0 ?>"
                                        style="font-size:0.75rem; min-width:104px;"
                                        title="<?= $kit_entregado_ins ? 'Kit ya entregado — clic para REVERTIR la acreditación' : 'Clic para ACREDITAR la entrega del kit' ?>">
                                    <i class="bi <?= $kit_entregado_ins ? 'bi-check-circle-fill' : 'bi-box-seam' ?> me-1"></i><?= $kit_entregado_ins ? 'Entregado' : 'Entregar' ?>
                                </button>

                            <?php endif; ?>

                        </td>

                        

                        <td class="text-center">

                            <div class="d-inline-flex align-items-center bg-light rounded-pill px-1 py-1 border">

                                <button type="button" 

                                        class="btn btn-sm btn-light text-info-emphasis border-0 rounded-circle js-btn-accion"

                                        title="Ver Detalles Completos"

                                        data-bs-toggle="modal"

                                        data-bs-target="#modalDetalleInscripcion"

                                        data-id="<?= $ins['id_participante'] ?>">

                                    <i class="bi bi-search"></i>

                                </button>

                                <?php if (!$solo_lectura): ?>

                                <span class="vr mx-1 text-muted opacity-25"></span>

                                <?php
                                // Estado de la credencial (columna credencial_impresa):
                                //  - NULL  -> nunca se imprimió (o se habilitó su reimpresión):
                                //           entra en el PDF "Imprimir NO impresas".
                                //  - fecha -> ya fue impresa: el botón permite "Habilitar
                                //           reimpresión" (vuelve a ponerla en NULL).
                                $cred_impressa = !empty($ins['credencial_impresa']);
                                ?>
                                <button type="button"

                                        class="btn btn-sm btn-light border-0 rounded-circle js-btn-accion <?= $cred_impressa ? 'js-btn-habilitar-reimpresion text-success-emphasis' : 'text-muted' ?>"

                                        <?= $cred_impressa ? 'data-id="' . $ins['id_participante'] . '" data-nombre="' . htmlspecialchars($ins['nombre_completo'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>

                                        title="<?= $cred_impressa
                                            ? 'Credencial impresa el ' . htmlspecialchars($ins['credencial_impresa'], ENT_QUOTES, 'UTF-8') . ' — clic para habilitar reimpresión'
                                            : 'Credencial NO impresa (pendiente para el PDF \"Imprimir no impresas\")' ?>">

                                    <i class="bi <?= $cred_impressa ? 'bi-printer-fill' : 'bi-printer' ?>"></i>

                                </button>

                                <span class="vr mx-1 text-muted opacity-25"></span>

                                <a href="<?= base_url('Inscripciones/modificar_inscripcion/'.$ins['id_participante']) ?>" 

                                   class="btn btn-sm btn-light text-warning-emphasis border-0 rounded-circle js-btn-accion" 

                                   title="Modificar Inscripción">

                                    <i class="bi bi-pencil-fill"></i>

                                </a>

                                <span class="vr mx-1 text-muted opacity-25"></span>

                                <button type="button"

                                        class="btn btn-sm btn-light text-danger border-0 rounded-circle js-btn-accion js-btn-eliminar"

                                        data-id="<?= $ins['id_participante'] ?>"

                                        data-nombre="<?= htmlspecialchars($ins['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>"

                                        title="Eliminar Registro">

                                    <i class="bi bi-trash3-fill"></i>

                                </button>
                                <?php endif; ?>

                            </div>

                        </td>

                    </tr>

                    <?php endforeach; else: ?>

                        <tr id="filaNoHayDatosIns"><td colspan="7" class="text-center text-muted py-5">

                            <div class="fs-1 mb-2 opacity-25"><i class="bi bi-inboxes-fill"></i></div>

                            No hay inscripciones oficiales registradas todavía.

                        </td></tr>

                    <?php endif; ?>

                    

                    <tr id="filaNoResultadosIns" style="display: none;">

                        <td colspan="7" class="text-center text-muted py-5">

                            <div class="fs-1 mb-2 text-danger opacity-50"><i class="bi bi-search-heart"></i></div>

                            No se encontraron coincidencias para tu búsqueda.

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>



        <?php if(!empty($listado_inscripciones)): ?>

        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2 border-top pt-3">

            <div class="text-muted small">

                Mostrando filas del <span id="pagStartIns">0</span> al <span id="pagEndIns">0</span>

            </div>

            <nav aria-label="Navegacion de inscriptos">

                <ul class="pagination pagination-sm mb-0 justify-content-center" id="ulPaginacionIns">

                </ul>

            </nav>

        </div>

        <?php endif; ?>

    </div>

</div>





<script>

document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // HELPERS SWEETALERT2 (con fallback nativo)
    // ==========================================
    
    function confirmarAccion(texto, icono, btnOk, btnCancelar) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                title: '¿Confirmás?',
                text: texto,
                icon: icono || 'question',
                showCancelButton: true,
                confirmButtonText: btnOk || 'Sí, confirmar',
                cancelButtonText: btnCancelar || 'Cancelar',
                reverseButtons: true
            }).then(r => r.isConfirmed);
        }
        return Promise.resolve(window.confirm(texto));
    }

    function alertar(mensaje, icono, titulo) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                title: titulo || 'Atención',
                text: mensaje,
                icon: icono || 'info',
                confirmButtonText: 'OK'
            });
        }
        window.alert(mensaje);
        return Promise.resolve();
    }

    const inputBuscarIns = document.getElementById('inputBuscarInscripcion');

    const filtroDelegacion = document.getElementById('filtroDelegacion');

    const filtroTipo = document.getElementById('filtroTipo');

    const filtroDeporte = document.getElementById('filtroDeporte');

    const filtroSexo = document.getElementById('filtroSexo');

    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');

    const filasIns = Array.from(document.querySelectorAll('.js-fila-inscripcion'));

    const filaNoResultadosIns = document.getElementById('filaNoResultadosIns');

    const contadorIns = document.getElementById('contador-inscripciones');

    

    const filasPorPaginaIns = 10; 

    let paginaActualIns = 1;

    let filasFiltradasIns = [...filasIns];



    function actualizarTablaInscripciones() {

        const totalFilasIns = filasFiltradasIns.length;

        const totalPaginasIns = Math.ceil(totalFilasIns / filasPorPaginaIns) || 1;



        if (paginaActualIns > totalPaginasIns) paginaActualIns = totalPaginasIns;

        if (paginaActualIns < 1) paginaActualIns = 1;



        const inicioIns = (paginaActualIns - 1) * filasPorPaginaIns;

        const finIns = inicioIns + filasPorPaginaIns;



        filasIns.forEach(f => f.style.display = 'none');



        filasFiltradasIns.forEach((fila, index) => {

            if (index >= inicioIns && index < finIns) {

                fila.style.display = '';

            }

        });



        const pStartIns = document.getElementById('pagStartIns');

        const pEndIns = document.getElementById('pagEndIns');

        if(pStartIns && pEndIns) {

            pStartIns.innerText = totalFilasIns === 0 ? 0 : inicioIns + 1;

            pEndIns.innerText = finIns > totalFilasIns ? totalFilasIns : finIns;

        }



        const ulPaginacionIns = document.getElementById('ulPaginacionIns');

        if (ulPaginacionIns) {

            ulPaginacionIns.innerHTML = '';



            if (totalPaginasIns > 1) {

                ulPaginacionIns.innerHTML += `

                    <li class="page-item ${paginaActualIns === 1 ? 'disabled' : ''}">

                        <button class="page-link" data-page-ins="${paginaActualIns - 1}">&laquo;</button>

                    </li>`;



                for (let i = 1; i <= totalPaginasIns; i++) {

                    ulPaginacionIns.innerHTML += `

                        <li class="page-item ${paginaActualIns === i ? 'active' : ''}">

                            <button class="page-link" data-page-ins="${i}">${i}</button>

                        </li>`;

                }



                ulPaginacionIns.innerHTML += `

                    <li class="page-item ${paginaActualIns === totalPaginasIns ? 'disabled' : ''}">

                        <button class="page-link" data-page-ins="${paginaActualIns + 1}">&raquo;</button>

                    </li>`;



                ulPaginacionIns.querySelectorAll('button').forEach(btn => {

                    btn.addEventListener('click', function() {

                        const nuevaPagIns = parseInt(this.getAttribute('data-page-ins'));

                        if (nuevaPagIns >= 1 && nuevaPagIns <= totalPaginasIns) {

                            paginaActualIns = nuevaPagIns;

                            actualizarTablaInscripciones();

                        }

                    });

                });

            }

        }



        if (totalFilasIns === 0 && filasIns.length > 0) {

            filaNoResultadosIns.style.display = '';

        } else {

            filaNoResultadosIns.style.display = 'none';

        }



        if (contadorIns) {

            contadorIns.innerText = `${totalFilasIns} ${totalFilasIns === 1 ? 'fila' : 'filas'}`;

        }

    }



    function aplicarFiltros() {

        const terminoBusqueda = inputBuscarIns.value.toLowerCase().trim();

        const delegacionSeleccionada = filtroDelegacion.value.toLowerCase();

        const tipoSeleccionado = filtroTipo.value;

        const deporteSeleccionado = filtroDeporte.value.toLowerCase();

        const sexoSeleccionado = filtroSexo ? filtroSexo.value.toLowerCase() : '';



        filasFiltradasIns = filasIns.filter(fila => {

            // Obtener datos de la fila

            const textoFila = fila.textContent.toLowerCase();

            const badgeDelegacion = fila.querySelector('td:nth-child(2) .badge');

            const delegacionFila = badgeDelegacion ? badgeDelegacion.textContent.toLowerCase() : '';

            

            const badgeTipo = fila.querySelector('td:nth-child(3) .badge');

            const esCompetidorFila = badgeTipo && badgeTipo.classList.contains('bg-primary-subtle');

            const esAcompanianteFila = badgeTipo && badgeTipo.classList.contains('bg-secondary-subtle');

            

            const deportesFilaElem = fila.querySelector('td:nth-child(4) .fw-semibold');

            const deportesFila = deportesFilaElem ? deportesFilaElem.textContent.toLowerCase() : '';



            // Aplicar filtros

            let coincide = true;

            

            // Filtro por búsqueda de texto

            if (terminoBusqueda && !textoFila.includes(terminoBusqueda)) {

                coincide = false;

            }

            

            // Filtro por delegación

            if (delegacionSeleccionada && !delegacionFila.includes(delegacionSeleccionada)) {

                coincide = false;

            }

            

            // Filtro por tipo (competidor vs acompañante)

            if (tipoSeleccionado === '1' && !esCompetidorFila) {

                coincide = false;

            }

            if (tipoSeleccionado === '0' && !esAcompanianteFila) {

                coincide = false;

            }

            

            // Filtro por deporte

            if (deporteSeleccionado && !deportesFila.includes(deporteSeleccionado)) {

                coincide = false;

            }



            // Filtro por sexo (badge en la columna Participante/DNI)

            if (sexoSeleccionado) {

                const badgeSexo = fila.querySelector('.js-badge-sexo');

                const sexoFila = badgeSexo ? badgeSexo.textContent.trim().toLowerCase() : '';

                if (sexoFila !== sexoSeleccionado) {

                    coincide = false;

                }

            }



            return coincide;

        });



        paginaActualIns = 1;

        actualizarTablaInscripciones();

    }



    // Event listeners para los filtros

    if (inputBuscarIns) {

        inputBuscarIns.addEventListener('input', aplicarFiltros);

    }

    if (filtroDelegacion) {

        filtroDelegacion.addEventListener('change', aplicarFiltros);

    }

    if (filtroTipo) {

        filtroTipo.addEventListener('change', aplicarFiltros);

    }

    if (filtroDeporte) {

        filtroDeporte.addEventListener('change', aplicarFiltros);

    }

    if (filtroSexo) {

        filtroSexo.addEventListener('change', aplicarFiltros);

    }

    if (btnLimpiarFiltros) {

        btnLimpiarFiltros.addEventListener('click', function() {

            inputBuscarIns.value = '';

            filtroDelegacion.value = '';

            filtroTipo.value = '';

            filtroDeporte.value = '';

            if (filtroSexo) filtroSexo.value = '';

            aplicarFiltros();

        });

    }



    actualizarTablaInscripciones();





    // ==========================================

    // CARGA OPERATIVA MEDIANTE AJAX (FETCH) - VERSION BLINDADA

    // ==========================================

    const modalElemento = id => document.getElementById(id);

    const modalInstancia = new bootstrap.Modal(document.getElementById('modalDetalleInscripcion'));



    // Buscamos la tabla con una alternativa por si falló el ID

    const tablaElemento = document.getElementById('tablaInscripciones') || document.querySelector('.table');



    if (!tablaElemento) {

        console.error("❌ ERROR OPERATIVO: No se encontró la tabla en el DOM con el ID 'tablaInscripciones'. Revisá el HTML.");

    } else {

        console.log("✅ Selector de tabla detectado correctamente. Escuchando clics...");

        

        // Escuchamos el evento

        tablaElemento.addEventListener('click', function(e) {

            // Log para ver EXACTAMENTE dónde está haciendo clic el administrador

            console.log("Clic detectado en el elemento:", e.target);



            // Buscamos el botón subiendo desde el elemento clickeado (sea el <i> o el <button>)

            const boton = e.target.closest('[data-bs-target="#modalDetalleInscripcion"]');

            

            if (!boton) {

                console.log("⚠️ Clic fuera del botón de la lupita (ignorado).");

                return;

            }



            // Si llegó acá, encontramos el botón con éxito

            const idParticipante = boton.getAttribute('data-id') || boton.dataset.id;

            console.log("🚀 Botón de lupita detectado con éxito! ID Participante:", idParticipante);

            

            if (!idParticipante) {

                console.error("❌ ERROR: El botón clickeado no tiene el atributo 'data-id' cargado.");

                return;

            }



            // Bloqueo preventivo de doble click y spinner visual

            const iconoOriginal = boton.innerHTML;

            boton.disabled = true;

            boton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';



            const urlPeticion = `<?= base_url('Inscripciones/detalle_ajax/') ?>${idParticipante}`;

            console.log("📡 Enviando FETCH a la URL:", urlPeticion);



            // Petición al controlador

            fetch(urlPeticion)

                .then(response => {

                    console.log("📥 Respuesta cruda del servidor recibida:", response);

                    if (!response.ok) {

                        throw new Error(`Error HTTP de servidor! Estado: ${response.status}`);

                    }

                    return response.json();

                })

                .then(data => {

                    console.log("📦 Datos JSON decodificados con éxito:", data);



                    if(data.error) {

                        alertar("Error del sistema: " + data.error, 'error', 'Error');

                        return;

                    }



                    // 1. Mapeamos Cabecera Principal

                    modalElemento('det-nombre').innerText = data.nombre_completo || 'Sin Nombre';

                    // Botón "Descargar Deslinde" del footer: el endpoint acepta
                    // el id del participante (descargar_deslinde/id/{id}), así que
                    // lo apuntamos acá y lo habilitamos.
                    const btnDeslindeDetalle = modalElemento('btn-descargar-deslinde');

                    if (btnDeslindeDetalle) {

                        if (data.id_participante) {

                            btnDeslindeDetalle.href = `<?= base_url('Inscripciones/descargar_deslinde/id/') ?>${data.id_participante}`;

                            btnDeslindeDetalle.classList.remove('disabled');

                        } else {

                            btnDeslindeDetalle.href = '#';

                            btnDeslindeDetalle.classList.add('disabled');

                        }

                    }


                    const esComp = parseInt(data.es_competidor) === 1;

                    modalElemento('det-rol').innerText = esComp ? 'COMPETIDOR' : 'ACOMPAÑANTE';

                    modalElemento('det-rol').className = esComp ? "badge bg-primary border mt-1" : "badge bg-secondary border mt-1";

                    modalElemento('det-badge-delegado').style.display = (parseInt(data.es_delegado) === 1) ? 'inline-block' : 'none';



                    // 2. Mapeamos Datos Personales

                    modalElemento('det-dni').innerText = data.dni || '-';

                    modalElemento('det-sexo').innerText = data.sexo || 'No especificado';

                    

                    if(data.fecha_nacimiento) {

                        const parts = data.fecha_nacimiento.split('-');

                        modalElemento('det-fnac').innerText = `${parts[2]}/${parts[1]}/${parts[0]}`;

                    } else {

                        modalElemento('det-fnac').innerText = 'No registrada';

                    }

                    

                    modalElemento('det-gsanguineo').innerText = data.grupo_sanguineo || '-';

                    modalElemento('det-osocial').innerText = data.obra_social || 'No posee / No registra';

                    modalElemento('det-empleado').innerText = data.tipo_empleado || 'No especificado';



                    // 3. Mapeamos Datos de Contacto

                    modalElemento('det-email').innerText = data.email || 'No registrado';

                    modalElemento('det-telefono').innerText = data.telefono || 'No registrado';

                    modalElemento('det-delegacion').innerText = data.delegacion || 'Sin Especificar';

                    modalElemento('det-emergencia').innerText = data.contacto_emergencia || 'No registrado';

                    modalElemento('det-dieta').innerText = data.dieta_especial || 'Ninguna / Común';



                    // 4. Mapeamos Deportes y Categorías en formato de 4 columnas con Colspan dinámico

                    const tablaBody = document.getElementById('det-tabla-deportes-body');

                    tablaBody.innerHTML = ''; // Limpiamos registros anteriores



                    if (data.deportes && data.deportes.length > 0) {

                        data.deportes.forEach(d => {

                            let filaHtml = '';



                            // Agrupamos condiciones para saber si maneja lógica de equipo/UTE

                            const tieneUte = parseInt(d.tiene_ute) === 1;

                            const necesitaUte = parseInt(d.necesita_ute) === 1 || d.necesita_ute === 'SI';

                            const tieneDetalle = d.detalle_ute && d.detalle_ute.trim() !== '';



                            if (tieneUte || necesitaUte || tieneDetalle) {

                                // ==========================================

                                // CASO A: ES DEPORTE DE EQUIPO / DUPLA

                                // ==========================================

                                let badgeUte = '';

                                let observacionUte = '';



                                if (tieneUte || tieneDetalle) {

                                    badgeUte = `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">TIENE UTE</span>`;

                                    observacionUte = `<span class="fw-semibold text-dark"><i class="bi bi-people-fill me-1"></i>${d.detalle_ute || 'Configurado'}</span>`;

                                } else if (necesitaUte) {

                                    badgeUte = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2">SOLICITA UTE</span>`;

                                    observacionUte = `<span class="text-muted small italic"><i class="bi bi-search me-1"></i>Busca compañero de otra delegación</span>`;

                                }



                                filaHtml = `

                                    <tr>

                                        <td class="fw-bold text-dark text-uppercase">${d.nombre_deporte}</td>

                                        <td class="text-muted">${d.nombre_categoria || 'Única / General'}</td>

                                        <td class="text-center">${badgeUte}</td>

                                        <td>${observacionUte}</td>

                                    </tr>

                                `;

                            } else {

                                // ==========================================

                                // CASO B: DEPORTE INDIVIDUAL (Hacemos Colspan)

                                // ==========================================

                                filaHtml = `

                                    <tr>

                                        <td class="fw-bold text-dark text-uppercase">${d.nombre_deporte}</td>

                                        <td class="text-muted">${d.nombre_categoria || 'Única / General'}</td>

                                        <td colspan="2" class="text-center text-muted bg-light-subtle small italic">

                                            <i class="bi bi-dash-lg text-secondary"></i> Disciplina Individual / Pareja Propia <i class="bi bi-dash-lg text-secondary"></i>

                                        </td>

                                    </tr>

                                `;

                            }



                            tablaBody.innerHTML += filaHtml;

                        });

                    } else {

                        // Si el registro es un acompañante o no está anotado en nada

                        tablaBody.innerHTML = `

                            <tr>

                                <td colspan="4" class="text-center text-muted italic py-3">

                                    <i class="bi bi-info-circle me-1"></i> Sin disciplinas asignadas (Acompañante / No Competidor)

                                </td>

                            </tr>

                        `;

                    }



                    // 5. Renderizado Visual del Hotel

                    if (!data.hotel_alojamiento || data.hotel_alojamiento.trim() === '') {

                        modalElemento('det-hotel').innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>SIN ASIGNAR</span>`;

                    } else {

                        modalElemento('det-hotel').innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-building-check me-1"></i>${data.hotel_alojamiento}</span>`;

                    }



                    // 6. Registro de timestamp

                    modalElemento('det-finscripcion').innerText = data.fecha_inscripcion || '---';



                    // Desplegamos el modal por Bootstrap 5

                    console.log("🎬 Abriendo ventana modal...");

                    modalInstancia.show();

                })

                .catch(error => {

                    console.error("❌ ERROR AJAX DETECTADO:", error);

                    alertar("Ocurrió un error al obtener el detalle: " + error.message, 'error', 'Error');

                })

                .finally(() => {

                    // Restablecemos el botón original pase lo que pase

                    boton.disabled = false;

                    boton.innerHTML = iconoOriginal;

                });

        });

    }

    

    // ==========================================

    // ELIMINAR INSCRIPCION CON AJAX (FETCH)

    // ==========================================

    tablaElemento.addEventListener('click', function(e) {

        const botonEliminar = e.target.closest('.js-btn-eliminar[data-id]');

        

        if (!botonEliminar) return;

        

        // Verificar que no sea el botón de ver detalle

        if (botonEliminar.hasAttribute('data-bs-target') && botonEliminar.getAttribute('data-bs-target') === '#modalDetalleInscripcion') {

            return;

        }

        

        const idParticipante = botonEliminar.getAttribute('data-id');

        const nombreParticipante = botonEliminar.getAttribute('data-nombre');

        

        if (!idParticipante) {

            console.error("❌ ERROR: El botón de eliminar no tiene data-id");

            return;

        }

        

        // Confirmación con SweetAlert2
        
        confirmarAccion(
            `¿Estás SEGURO que deseas eliminar a ${nombreParticipante}? Esta acción borrará todos sus datos personales y sus inscripciones deportivas. No se podrá deshacer.`,
            'warning',
            'Sí, eliminar',
            'Cancelar'
        ).then(function (confirmado) {

            if (!confirmado) return;

            // Bloqueo visual del botón

            const iconoOriginal = botonEliminar.innerHTML;
            botonEliminar.disabled = true;
            botonEliminar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            const urlPeticion = `<?= base_url('Inscripciones/eliminar_inscripcion_ajax/') ?>${idParticipante}`;

            fetch(urlPeticion, {

                method: 'POST',

                headers: {

                    'Content-Type': 'application/json'

                }

            })

            .then(response => {

                if (!response.ok) {

                    throw new Error(`Error HTTP: ${response.status}`);

                }

                return response.json();

            })

            .then(data => {

                if (data.success) {

                    // Buscar la fila y eliminarla con animación

                    const fila = botonEliminar.closest('.js-fila-inscripcion');

                    if (fila) {

                        fila.style.transition = 'all 0.3s ease';

                        fila.style.opacity = '0';

                        fila.style.transform = 'translateX(-100%)';

                        

                        setTimeout(() => {

                            fila.remove();

                            

                            // Recalcular paginación y filtro

                            paginaActualIns = 1;

                            filasFiltradasIns = Array.from(document.querySelectorAll('.js-fila-inscripcion'));

                            filasIns = filasFiltradasIns;

                            actualizarTablaInscripciones();

                            

                            // Mostrar mensaje de éxito

                            alertar(data.mensaje || 'Inscripción eliminada correctamente', 'success', '¡Listo!');

                        }, 300);

                    }

                } else {

                    alertar('Error: ' + (data.error || 'No se pudo eliminar la inscripción'), 'error', 'Error');

                    botonEliminar.disabled = false;

                    botonEliminar.innerHTML = iconoOriginal;

                }

            })

            .catch(error => {

                console.error("❌ ERROR EN FETCH:", error);

                alertar('Ocurrió un error al eliminar: ' + error.message, 'error', 'Error');

                botonEliminar.disabled = false;

                botonEliminar.innerHTML = iconoOriginal;

            });

        });

    });


    // ==========================================
    // HABILITAR REIMPRESIÓN DE CREDENCIAL (AJAX)
    // Pone credencial_impresa = NULL para que la credencial vuelva a quedar
    // pendiente dentro del PDF "Imprimir NO impresas" del modal de
    // Credenciales. En ese modo la delegación es OPCIONAL: no hace falta
    // volver a elegir la provincia, el botón re-habilita la fila y el
    // próximo PDF global ("todas las provincias") ya la incluye.
    // ==========================================
    tablaElemento.addEventListener('click', function(e) {

        const botonReimpresion = e.target.closest('.js-btn-habilitar-reimpresion[data-id]');

        if (!botonReimpresion) return;

        const idParticipante = botonReimpresion.getAttribute('data-id');
        const nombreParticipante = botonReimpresion.getAttribute('data-nombre');

        confirmarAccion(
            `¿Habilitar la reimpresión de la credencial de ${nombreParticipante}? Volverá a incluirse en el PDF "Imprimir no impresas".`,
            'question',
            'Sí, habilitar',
            'Cancelar'
        ).then(function (confirmado) {

            if (!confirmado) return;

            const iconoOriginal = botonReimpresion.innerHTML;
            botonReimpresion.disabled = true;
            botonReimpresion.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            fetch(`<?= base_url('Inscripciones/habilitar_reimpresion_ajax/') ?>${idParticipante}`, {
                method: 'POST'
            })
            .then(response => {
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Actualizar el botón sin recargar la página: pasa a
                    // estado "NO impresa" (gris, sin data-id).
                    botonReimpresion.classList.remove('js-btn-habilitar-reimpresion', 'text-success-emphasis');
                    botonReimpresion.classList.add('text-muted');
                    botonReimpresion.removeAttribute('data-id');
                    botonReimpresion.removeAttribute('data-nombre');
                    botonReimpresion.title = 'Credencial NO impresa (pendiente para el PDF "Imprimir no impresas")';
                    botonReimpresion.innerHTML = '<i class="bi bi-printer"></i>';
                    alertar(data.mensaje || 'Reimpresión habilitada.', 'success', '¡Listo!');
                } else {
                    alertar('Error: ' + (data.error || 'No se pudo habilitar la reimpresión'), 'error', 'Error');
                    botonReimpresion.disabled = false;
                    botonReimpresion.innerHTML = iconoOriginal;
                }
            })
            .catch(error => {
                console.error("❌ ERROR EN FETCH (habilitar reimpresión):", error);
                alertar('Ocurrió un error al habilitar la reimpresión: ' + error.message, 'error', 'Error');
                botonReimpresion.disabled = false;
                botonReimpresion.innerHTML = iconoOriginal;
            });

        });

    });

    // ==========================================
    // ACREDITAR ENTREGA DE KIT UNO POR UNO (AJAX, DESDE LA TABLA)
    // Un clic en el botón de la columna "Kit" acredita la entrega; un segundo
    // clic sobre el botón verde permite revertirla. Todo sin recargar la
    // página: el botón se actualiza en el lugar con la respuesta del servidor.
    // ==========================================
    tablaElemento.addEventListener('click', function(e) {

        // Capturamos TODOS los clics en botones de kit (no solo los que
        // tienen data-id): así ningún botón puede quedar "sin respuesta".
        const botonKit = e.target.closest('.js-btn-kit');
        if (!botonKit) return;

        const idParticipante = botonKit.getAttribute('data-id');
        if (!idParticipante) {
            console.error('❌ El botón de kit no tiene data-id (fila fuera de lo esperado).');
            alertar('Este botón no tiene participante asociado. Recargá la página (F5) e intentá de nuevo.', 'warning', 'Atención');
            return;
        }
        if (botonKit.disabled) {
            // Está en pleno guardado (spinner): ignoramos clics dobles.
            return;
        }
        const nombreParticipante = botonKit.getAttribute('data-nombre') || 'este inscripto';
        const estadoActual = parseInt(botonKit.getAttribute('data-estado'), 10) === 1 ? 1 : 0;
        const nuevoEstado = estadoActual === 1 ? 0 : 1; // toggle: entregar / revertir

        // Revertir es delicado: pedimos confirmación. Acreditar es directo (rápido).
        const continuar = (nuevoEstado === 0)
            ? confirmarAccion(
                `¿Revertir la acreditación del kit de ${nombreParticipante}? Volverá a quedar como "pendiente de entrega".`,
                'warning',
                'Sí, revertir',
                'Cancelar'
              )
            : Promise.resolve(true);

        // Pintar botón según estado (función aparte para reutilizarla siempre)
        const pintarBotonKit = function(boton, estado) {
            boton.setAttribute('data-estado', String(estado));
            if (estado === 1) {
                boton.classList.remove('btn-outline-secondary');
                boton.classList.add('btn-success');
                boton.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Entregado';
                boton.title = 'Kit ya entregado — clic para REVERTIR la acreditación';
            } else {
                boton.classList.remove('btn-success');
                boton.classList.add('btn-outline-secondary');
                boton.innerHTML = '<i class="bi bi-box-seam me-1"></i>Entregar';
                boton.title = 'Clic para ACREDITAR la entrega del kit';
            }
            // IMPORTANTE: SIEMPRE se habilita acá. Así el botón nunca queda
            // trabado en "disabled" ni siquiera si la respuesta fue errónea.
            boton.disabled = false;
        };

        continuar.then(function(confirmado) {

            if (!confirmado) return;

            const iconoOriginal = botonKit.innerHTML;
            botonKit.disabled = true;
            botonKit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            fetch('<?= base_url('Inscripciones/acreditar_kit_ajax') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' // para que el servidor responda SIEMPRE JSON
                },
                body: 'id_participante=' + encodeURIComponent(idParticipante)
                    + '&nuevo_estado=' + nuevoEstado
            })
            .then(response => {
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                // Leemos el texto primero: si el servidor devolviera HTML en vez
                // de JSON (ej. "Unexpected token '<'"), el catch lo atrapa y el
                // botón SIEMPRE se re-habilita abajo. Nunca queda trabado.
                return response.text().then(txt => {
                    try { return JSON.parse(txt); }
                    catch (e) { throw new Error('El servidor no devolvió un JSON válido.'); }
                });
            })

            .then(data => {
                if (data.success) {
                    // Repintar el botón en el mismo lugar con el nuevo estado
                    // y volver a habilitarlo (queda clicable para revertir).
                    pintarBotonKit(botonKit, nuevoEstado);
                    alertar(data.mensaje || 'Estado del kit actualizado.', 'success', '¡Listo!');

                } else {
                    alertar('Error: ' + (data.error || 'No se pudo registrar la entrega del kit'), 'error', 'Error');
                    // Restauramos el estado REAL que tenía el botón y lo
                    // re-habilitamos SIEMPRE (nunca queda trabado en disabled).
                    pintarBotonKit(botonKit, estadoActual);
                }
            })
            .catch(error => {
                console.error('❌ ERROR EN FETCH (acreditar kit):', error);
                alertar('Ocurrió un error al acreditar el kit: ' + error.message, 'error', 'Error');
                // Recargamos el estado real desde el servidor pintando según
                // data-estado actual: así el botón vuelve a estar HABILITADO
                // aunque algo haya salido mal (nunca queda disabled).
                pintarBotonKit(botonKit, estadoActual);
            });

        });

    });

});

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

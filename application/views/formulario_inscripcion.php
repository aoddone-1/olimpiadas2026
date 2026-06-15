<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción - <?= NOMBRE_META; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="<?= base_url('css/style_formularios.css') ?>" rel="stylesheet">
</head>
<body>

<div class="hero-section">
    <div class="container">
       <div class="row align-items-center g-4">
            
            <div class="col-12 col-md-4 text-center text-md-end d-none d-md-block">
                <img src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" 
                    alt="Logo Olimpiadas" 
                    class="img-fluid logo-hero">
            </div>
            
            <div class="col-12 col-md-8 text-center text-md-start ps-md-4">
                
                <div class="d-block d-md-none">
                    <img src="<?= base_url('assets/img/logo_olimpiadas.png') ?>" 
                        alt="Logo Olimpiadas" 
                        class="img-fluid logo-hero">
                </div>
                
                <h1 class="h2 fw-bold text-uppercase text-white mb-2 header-title">
                    FORMULARIO DE <u>INSCRIPCIÓN</u>
                </h1>
                
                <small class="d-block text-white-50 help-text">
                    <?= NOMBRE_SITIO; ?> <br/> <?= LUGAR_OLIMPICO; ?>
                </small>
            </div>

       </div>
    </div>
</div>

<div class="container mb-5">
    <form autocomplete='off' action="<?= base_url('Inscripciones/guardar') ?>" method="POST" class="needs-validation" novalidate>
        
        <h3 class="section-title">1. Información Personal</h3>
        <div id="cartel-modo" class="alert alert-warning border-warning shadow-sm mb-4" style="display: none;">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-dark"></i>
                <div>
                    <h4 class="alert-heading fw-bold m-0 text-dark">¡DNI ya registrado!</h4>
                    <p class="m-0 text-dark small">Detectamos que ya posees una inscripción activa. Hemos cargado tus datos para que puedas modificarlos o actualizar tus disciplinas.</p>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">DNI</label>
                        <input type="text" name="dni" id="txt-dni" class="form-control" placeholder="Sin puntos" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nombre Completo</label>
                        <input type="text" name="nombre_completo" id="txt-nombre" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Delegación (Provincia)</label>
                        <select name="delegacion" id="cmb-delegacion" class="form-select" required>
                            <option value="">Seleccione su delegación...</option>
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
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" id="txt-email" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Teléfono</label>
                        <input type="text" name="telefono" id="txt-telefono" class="form-control" placeholder="Ej: 2954123456" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Género</label>
                        <select name="sexo" id="cmb-sexo" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fecha Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" id="txt-nacimiento" class="form-control" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required>
                    </div>
                    <div class="col-md-4">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipo Empleado</label>
                        <select name="tipo_empleado" id="cmb-empleado" class="form-select" required>
                            <option value="Planta Permanente">Planta Permanente</option>
                            <option value="Jubilado">Jubilado</option>
                            <option value="Contratado">Contratado</option>
                            <option value="Pasante">Pasante</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipo de Asistente</label>
                        <select name="rol_asistente" id="cmb-rol-asistente" class="form-select" required>
                            <option value="" selected disabled>Seleccione su tipo de asistente...</option>
                            <option value="competidor">Soy Competidor</option>
                            <option value="acompañante">Soy Acompañante</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-center mt-md-4 pt-md-2" id="bloque-delegado">
                        <div class="form-check form-switch p-3 bg-light border rounded w-100">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="es_delegado" id="chk-delegado" value="1">
                            <label class="form-check-label fw-bold text-secondary" for="chk-delegado">
                                <i class="bi bi-person-badge text-primary me-1"></i> Soy Delegado de la delegación
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="section-title">2. Logística y Salud</h3>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Grupo Sanguíneo</label>
                        <select name="grupo_sanguineo" id="cmb-sangre" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="0+">0+</option>
                            <option value="0-">0-</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Obra Social</label>
                        <input type="text" name="obra_social" id="txt-osocial" class="form-control" placeholder="Nombre de la cobertura">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Contacto Emergencia</label>
                        <input type="text" name="contacto_emergencia" id="txt-emergencia" class="form-control" placeholder="Nombre y Tel." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Dieta Especial</label>
                        <select name="dieta_especial" id="cmb-dieta" class="form-select" required>
                            <option value="Sin restrictions" selected>Sin restricciones</option>
                            <option value="Celiaco">Celiaco</option>
                            <option value="Vegetariano">Vegetariano</option>
                            <option value="Vegano">Vegano</option>
                            <option value="Diabético">Diabético</option>
                            <option value="Hipertenso (Sin Sal)">Hipertenso (Sin Sal)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Alojamiento</label>
                        <input type="text" name="hotel_alojamiento" id="txt-hotel" class="form-control" placeholder="Nombre del hotel asignado">
                    </div>
                </div>
            </div>
        </div>

        <div id="seccion-deportiva-completa" style="display: none;">
            <h3 class="section-title">3. Disciplinas Deportivas</h3>

            <div id="msg-espera" class="card p-4 text-center text-muted border mb-3 bg-light shadow-sm">
                <div class="card-body py-3">
                    <i class="bi bi-person-fill-check fs-2 text-primary d-block mb-2"></i>
                    <p class="m-0 fw-semibold">Por favor, seleccioná tu género arriba para desplegar los deportes disponibles.</p>
                </div>
            </div>

            <div id="contenedor-deportes" class="contenedor-deportes-activos"></div>

            <div id="molde-fila-deporte" style="display: none;">
                <div class="card mb-3 row-deporte-molde border">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-trophy"></i> Deporte</label>
                                <select class="form-select select-deporte">
                                    <option value="">Seleccione un deporte...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-tags"></i> Categoría</label>
                                <select class="form-select select-categoria" disabled>
                                    <option value="">Elija el deporte primero</option>
                                </select>
                            </div>
                        </div>

                        <div class="bloque-ute mt-3 pt-3 border-top" style="display: none;">
                            <div class="row align-items-center g-3">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="hidden" class="hd-tiene-ute" value="0">
                                        <input class="form-check-input check-tengo-ute" type="checkbox">
                                        <label class="form-check-label text-success fw-bold">Tengo UTE</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="hidden" class="hd-necesita-ute" value="0">
                                        <input class="form-check-input check-necesito-ute" type="checkbox">
                                        <label class="form-check-label text-danger fw-bold">Necesito UTE</label>
                                    </div>
                                </div>
                                <div class="col-md-6 bloque-detalle-ute" style="display: none;">
                                    <textarea class="form-control txt-detalle-ute" rows="1" placeholder="Detalle de la UTE (Integrantes, equipo, etc.)"></textarea>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-danger btn-remove shadow-sm mt-3" style="display:none;"><i class="bi bi-x"></i> </button>
                    </div>
                </div>
            </div>

            <div class="text-center mb-5" id="bloque-btn-agregar" style="display: none;">
                <button type="button" id="btn-agregar" class="btn btn-outline-primary fw-bold">
                    <i class="bi bi-plus-circle-fill"></i> AGREGAR OTRA DISCIPLINA
                </button>
            </div>
        </div>

        <div class="card bg-white p-4 border-top border-4 border-warning shadow-sm">
    
            <h5 class="text-start fw-bold text-uppercase text-secondary mb-4 border-bottom pb-2">
                <i class="bi bi-file-earmark-text-fill text-warning me-2"></i> 
                Declaración Jurada y Conformidad Legal
            </h5>

            <div class="row text-start g-3 mb-4">
                
                <div class="col-12">
                    <div class="form-check p-3 border rounded bg-light items-deslinde">
                        <input class="form-check-input ms-0 me-3 border-secondary check-item-deslinde" type="checkbox" id="chk-voluntario" required style="width: 1.35rem; height: 1.35rem; cursor:pointer;">
                        <label class="form-check-label small text-dark fw-medium d-block ps-2" for="chk-voluntario" style="cursor:pointer; user-select:none;">
                            1. Declaro que participo de forma <u>voluntaria</u> en las competencias de las “XXXVIII Olimpiadas Nacionales de Empleados de Institutos de Vivienda La Pampa 2026”, manifestando haber leído, comprendido y aceptado sus Reglamentos y las condiciones de la Póliza de Seguro por Accidentes Personales.
                        </label>
                        <div class="invalid-feedback fw-bold mt-1 ps-2">Debe confirmar que conoce y acepta los reglamentos de la competencia.</div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check p-3 border rounded bg-light items-deslinde">
                        <input class="form-check-input ms-0 me-3 border-secondary check-item-deslinde" type="checkbox" id="chk-riesgos" required style="width: 1.35rem; height: 1.35rem; cursor:pointer;">
                        <label class="form-check-label small text-dark fw-medium d-block ps-2" for="chk-riesgos" style="cursor:pointer; user-select:none;">
                            2. Reconozco plenamente que las actividades deportivas implican <u>riesgos físicos</u> y asumo voluntariamente total responsabilidad por cualquier contingencia que pueda suceder practicando las disciplinas en las que me inscribo, tanto a mi persona como a terceros.
                        </label>
                        <div class="invalid-feedback fw-bold mt-1 ps-2">Debe asumir la responsabilidad de los riesgos físicos de la práctica deportiva.</div>
                    </div>
                </div>

                <div class="col-12 bloque-competidor-check">
                    <div class="form-check p-3 border rounded bg-light items-deslinde">
                        <input class="form-check-input ms-0 me-3 border-secondary check-item-deslinde" type="checkbox" id="chk-aptitud" required style="width: 1.35rem; height: 1.35rem; cursor:pointer;">
                        <label class="form-check-label small text-dark fw-medium d-block ps-2" for="chk-aptitud" style="cursor:pointer; user-select:none;">
                            3. Declaro bajo juramento encontrarme en <u>perfectas condiciones psicofísicas para competir</u>, habiendo realizado los entrenamientos previos necesarios y los reconocimientos médicos correspondientes, no poseyendo ningún impedimento físico ni deficiencia de salud.
                        </label>
                        <div class="invalid-feedback fw-bold mt-1 ps-2">Debe certificar su aptitud psicofísica para la competencia.</div>
                    </div>
                </div>

                <div class="col-12 bloque-competidor-check">
                    <div class="form-check p-3 border rounded bg-light items-deslinde">
                        <input class="form-check-input ms-0 me-3 border-secondary check-item-deslinde" type="checkbox" id="chk-indumentaria" required style="width: 1.35rem; height: 1.35rem; cursor:pointer;">
                        <label class="form-check-label small text-dark fw-medium d-block ps-2" for="chk-indumentaria" style="cursor:pointer; user-select:none;">
                            4. Certifico que la categoría solicitada corresponde estrictamente a mi <u>edad y nivel deportivo</u>, y que participaré con la <u>indumentaria adecuada</u> requerida para la práctica segura en los circuitos y canchas asignados.
                        </label>
                        <div class="invalid-feedback fw-bold mt-1 ps-2">Debe confirmar la veracidad de su categoría e indumentaria.</div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check p-3 border rounded bg-light items-deslinde">
                        <input class="form-check-input ms-0 me-3 border-secondary check-item-deslinde" type="checkbox" id="chk-exoneracion" required style="width: 1.35rem; height: 1.35rem; cursor:pointer;">
                        <label class="form-check-label small text-dark fw-medium d-block ps-2" for="chk-exoneracion" style="cursor:pointer; user-select:none;">
                            5. <u>Desligo de toda responsabilidad</u> a los Organizadores, Coordinadores, Comité Olímpico, Municipios, Autoridades Provinciales, patrocinadores y titulares de los predios, ante cualquier accidente, lesión, muerte, robo o daño material que pudiera sufrir, renunciando expresamente a reclamos judiciales o extrajudiciales fuera del seguro contratado.
                        </label>
                        <div class="invalid-feedback fw-bold mt-1 ps-2">Debe aceptar la exoneración de responsabilidad organizativa para inscribirse.</div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check p-3 border rounded bg-light items-deslinde">
                        <input class="form-check-input ms-0 me-3 border-secondary check-item-deslinde" type="checkbox" id="chk-imagen" required style="width: 1.35rem; height: 1.35rem; cursor:pointer;">
                        <label class="form-check-label small text-dark fw-medium d-block ps-2" for="chk-imagen" style="cursor:pointer; user-select:none;">
                            6. Autorizo de forma expresa a la Organización y Sponsors al uso legítimo de <u>fotografías, películas, videos y grabaciones</u> de mi participación en el evento para fines de difusión, sin compensación económica alguna.
                        </label>
                        <div class="invalid-feedback fw-bold mt-1 ps-2">Debe autorizar el uso de registros de imagen institucional.</div>
                    </div>
                </div>

            </div>

            <p id="leyenda-boton" class="text-muted small mb-3">
                Al hacer clic en "Confirmar", declara bajo juramento que todos los datos proveídos son verídicos y se compromete a respetar las normas establecidas.
            </p>
            
            <button type="submit" id="btn-submit-principal" class="btn btn-primary btn-lg shadow-lg px-5 fw-bold">
                <i class="bi bi-check-circle-fill me-2"></i> CONFIRMAR INSCRIPCIÓN
            </button>
        </div>

    </form>
</div>
<?php $this->load->view('footer'); ?>

<script>
$(document).ready(function() {

    // Bootstrap validation trigger manual
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);

    // ==========================================
    // AUXILIAR: REASIGNAR NAMES CORRECTAMENTE
    // ==========================================
    function actualizarNamesDeportes() {
        let esAcompanante = $('#cmb-rol-asistente').val() === 'acompañante';
        
        // CORRECCIÓN PROTECTORA: Limpiamos por completo el molde oculto para que no interfiera en la validación
        $('#molde-fila-deporte').find('.select-deporte, .select-categoria, .hd-tiene-ute, .hd-necesita-ute, .txt-detalle-ute').removeAttr('name').prop('required', false);

        // Indexamos únicamente las filas reales y visibles que están en el contenedor activo
        $('#contenedor-deportes .row-deporte-activo').each(function() {
            let fila = $(this);
            if (esAcompanante) {
                fila.find('.select-deporte, .select-categoria, .hd-tiene-ute, .hd-necesita-ute, .txt-detalle-ute').removeAttr('name').prop('required', false);
            } else {
                fila.find('.select-deporte').attr('name', 'deporte_id[]');
                fila.find('.select-categoria').attr('name', 'categoria_id[]');
                fila.find('.hd-tiene-ute').attr('name', 'tiene_ute[]');
                fila.find('.hd-necesita-ute').attr('name', 'necesita_ute[]');
                fila.find('.txt-detalle-ute').attr('name', 'detalle_ute[]');
            }
        });
    }

    // ==========================================
    // 1. GESTIÓN DE DEPORTES SEGÚN EL GÉNERO
    // ==========================================
    function actualizarDeportesPorGenero(callback_interno) {
        let sexo = $('#cmb-sexo').val();
        let rol = $('#cmb-rol-asistente').val();
        
        if (rol === 'acompañante') return;

        if (!sexo) {
            $('#contenedor-deportes').empty();
            $('#bloque-btn-agregar').fadeOut(300);
            $('#msg-espera').slideDown(300);
            return;
        }

        $.get('<?= base_url("Inscripciones/getDeportesPorGenero/") ?>' + sexo, function(deportes) {
            
            $('#msg-espera').slideUp(300, function() {
                
                if ($('#contenedor-deportes').children().length === 0) {
                    let primeraFila = $('#molde-fila-deporte .row-deporte-molde').clone();
                    primeraFila.removeClass('row-deporte-molde').addClass('row-deporte-activo');
                    
                    let selectDeporte = primeraFila.find('.select-deporte');
                    deportes.forEach(function(d) {
                        selectDeporte.append(`<option value="${d.id_deporte}" data-modalidad="${d.modalidad}">${d.nombre_deporte}</option>`);
                    });
                    
                    $('#contenedor-deportes').append(primeraFila);
                    $('#bloque-btn-agregar').fadeIn(300);
                    controlarVisibilidadRoles(); 
                } else {
                    $('#contenedor-deportes .row-deporte-activo').each(function() {
                        let selectDeporte = $(this).find('.select-deporte');
                        let valorActual = selectDeporte.val();

                        selectDeporte.empty().append('<option value="">Seleccione un deporte...</option>');
                        deportes.forEach(function(d) {
                            selectDeporte.append(`<option value="${d.id_deporte}" data-modalidad="${d.modalidad}">${d.nombre_deporte}</option>`);
                        });

                        if (valorActual && selectDeporte.find(`option[value="${valorActual}"]`).length > 0) {
                            selectDeporte.val(valorActual).trigger('change');
                        } else {
                            selectDeporte.val('').trigger('change');
                        }
                    });
                }

                if (typeof callback_interno === "function") {
                    callback_interno(deportes);
                }
            });

        }, 'json');
    }

    $('#cmb-sexo').on('change', function() {
        actualizarDeportesPorGenero();
    });


    // ==========================================
    // 2. BÚSQUEDA AUTOMÁTICA POR DNI (CORREGIDA PRE-CARGA UTE)
    // ==========================================
    $('#txt-dni').on('input', function() {
        let dni = $(this).val().trim();
        if (dni.length < 6) return;

        $.post('<?= base_url("Inscripciones/buscar_por_dni") ?>', { dni: dni }, function(response) {
            let res = JSON.parse(response);

            if (res.existe) {
                $('#cartel-modo').slideDown(400);

                $('#txt-nombre').val(res.datos.nombre_completo);
                $('#txt-email').val(res.datos.email);
                $('#txt-telefono').val(res.datos.telefono);
                $('#cmb-delegacion').val(res.datos.delegacion);
                $('#cmb-sexo').val(res.datos.sexo);
                $('#txt-nacimiento').val(res.datos.fecha_nacimiento);
                $('#cmb-empleado').val(res.datos.tipo_empleado);
                
                let asignacion_rol = (res.datos.es_competidor == 1) ? 'competidor' : 'acompañante';
                $('#cmb-rol-asistente').val(asignacion_rol);
                
                if (res.datos.es_delegado == 1 && asignacion_rol === 'competidor') {
                    $('#chk-delegado').prop('checked', true);
                } else {
                    $('#chk-delegado').prop('checked', false);
                }

                $('#cmb-sangre').val(res.datos.grupo_sanguineo);
                $('#txt-osocial').val(res.datos.obra_social);
                $('#txt-emergencia').val(res.datos.contacto_emergencia);
                $('#cmb-dieta').val(res.datos.dieta_especial);
                $('#txt-hotel').val(res.datos.hotel_alojamiento);

                $('#btn-submit-principal').html('<i class="bi bi-pencil-square"></i> ACTUALIZAR INSCRIPCIÓN').removeClass('btn-primary').addClass('btn-warning text-dark fw-bold');
                $('#leyenda-boton').html('Al hacer clic en "Actualizar", se guardarán las modificaciones sobre tu registro existente.');

                controlarVisibilidadRoles();
                $('#contenedor-deportes').empty();

                if (res.disciplinas && res.disciplinas.length > 0 && $('#cmb-rol-asistente').val() !== 'acompañante') {
                    
                    actualizarDeportesPorGenero(function(deportesValidos) {
                        $('#contenedor-deportes').empty();
                        
                        res.disciplinas.forEach(function(disc, index) {
                            let nuevoBloque = $('#molde-fila-deporte .row-deporte-molde').clone();
                            nuevoBloque.removeClass('row-deporte-molde').addClass('row-deporte-activo');
                            
                            let selectDeporte = nuevoBloque.find('.select-deporte');
                            let selectCategoria = nuevoBloque.find('.select-categoria');
                            
                            selectDeporte.empty().append('<option value="">Seleccione un deporte...</option>');
                            deportesValidos.forEach(function(d) {
                                selectDeporte.append(`<option value="${d.id_deporte}" data-modalidad="${d.modalidad}">${d.nombre_deporte}</option>`);
                            });
                            
                            selectDeporte.val(disc.id_deporte);
                            
                            if (index > 0) {
                                nuevoBloque.find('.btn-remove').show();
                            } else {
                                nuevoBloque.find('.btn-remove').hide();
                            }
                            
                            $('#contenedor-deportes').append(nuevoBloque);
                            $('#bloque-btn-agregar').fadeIn(300);

                            // Petición asincrónica de categorías
                            $.get('<?= base_url("Inscripciones/getCategorias/") ?>' + disc.id_deporte, function(data) {
                                selectCategoria.empty().append('<option value="">Seleccione categoría...</option>');
                                let categorias = (typeof data === 'string') ? JSON.parse(data) : data;
                                
                                categorias.forEach(function(cat) {
                                    selectCategoria.append(`<option value="${cat.id_categoria}">${cat.nombre_categoria}</option>`);
                                });
                                
                                selectCategoria.prop('disabled', false).val(disc.id_categoria);
                                
                                // === FIJATE ACÁ: Forzamos la carga de la UTE DESPUÉS de renderizar la categoría ===
                                let optionSeleccionada = selectDeporte.find(':selected');
                                let mod = optionSeleccionada.data('modalidad');
                                
                                if (mod === 'EQUIPO' || mod === 'AMBAS') {
                                    nuevoBloque.find('.bloque-ute').show(); // Desplegamos el contenedor general
                                    
                                    if (disc.tiene_ute == 1) {
                                        nuevoBloque.find('.check-tengo-ute').prop('checked', true);
                                        nuevoBloque.find('.hd-tiene-ute').val('1');
                                        nuevoBloque.find('.bloque-detalle-ute').show();
                                        nuevoBloque.find('.txt-detalle-ute').val(disc.detalle_ute).prop('required', true);
                                    }
                                    
                                    if (disc.necesita_ute == 1) {
                                        nuevoBloque.find('.check-necesito-ute').prop('checked', true);
                                        nuevoBloque.find('.hd-necesita-ute').val('1');
                                    }
                                }
                                
                                controlarVisibilidadRoles();
                            }, 'json');
                        });
                    });
                } else {
                    actualizarDeportesPorGenero();
                }

            } else {
                $('#cartel-modo').slideUp(300);
                $('#btn-submit-principal').html('<i class="bi bi-check-circle-fill"></i> CONFIRMAR INSCRIPCIÓN').removeClass('btn-warning text-dark').addClass('btn-primary');
                $('#leyenda-boton').html('Al hacer clic en "Confirmar", declara que los datos son correctos y posee aptitud física para competir.');
            }
        });
    });


    // ==========================================
    // 3. DINÁMICA DE FILAS (DEPORTE -> CATEGORÍA + PANEL UTE)
    // ==========================================
    $(document).on('change', '.select-deporte', function() {
        let id_deporte = $(this).val();
        let fila = $(this).closest('.row-deporte-activo');
        if(fila.length === 0) return;

        let selectCat = fila.find('.select-categoria');
        let bloqueUte = fila.find('.bloque-ute');
        let modalidad = $(this).find(':selected').data('modalidad');

        if (modalidad === 'EQUIPO' || modalidad === 'AMBAS') {
            bloqueUte.slideDown(300);
        } else {
            bloqueUte.slideUp(300, function() {
                bloqueUte.find('input[type="checkbox"]').prop('checked', false);
                bloqueUte.find('.hd-tiene-ute, .hd-necesita-ute').val('0');
                bloqueUte.find('.bloque-detalle-ute').hide();
                bloqueUte.find('.txt-detalle-ute').val('').prop('required', false);
            });
        }

        if (id_deporte) {
            $.get('<?= base_url("Inscripciones/getCategorias/") ?>' + id_deporte, function(data) {
                selectCat.empty().append('<option value="">Seleccione categoría...</option>');
                
                let categorias = (typeof data === 'string') ? JSON.parse(data) : data;
                categorias.forEach(function(cat) {
                    selectCat.append(`<option value="${cat.id_categoria}">${cat.nombre_categoria}</option>`);
                });
                selectCat.prop('disabled', false);
                actualizarNamesDeportes();
            }, 'json');
        } else {
            selectCat.prop('disabled', true).empty().append('<option value="">Elija el deporte primero</option>');
            actualizarNamesDeportes();
        }
    });

    // --- MANEJO COMPORTAMIENTO INTERNO DE CHECKS DE UTE ---
    $(document).on('change', '.check-tengo-ute', function() {
        let fila = $(this).closest('.row-deporte-activo');
        let txtAreaBlock = fila.find('.bloque-detalle-ute');
        
        if ($(this).is(':checked')) {
            fila.find('.check-necesito-ute').prop('checked', false);
            fila.find('.hd-necesita-ute').val('0');
            fila.find('.hd-tiene-ute').val('1');
            txtAreaBlock.slideDown(250);
            fila.find('.txt-detalle-ute').prop('required', true);
        } else {
            fila.find('.hd-tiene-ute').val('0');
            txtAreaBlock.slideUp(250, function() {
                fila.find('.txt-detalle-ute').val('').prop('required', false);
            });
        }
    });

    $(document).on('change', '.check-necesito-ute', function() {
        let fila = $(this).closest('.row-deporte-activo');
        
        if ($(this).is(':checked')) {
            fila.find('.check-tengo-ute').prop('checked', false);
            fila.find('.hd-tiene-ute').val('0');
            fila.find('.hd-necesita-ute').val('1');
            fila.find('.bloque-detalle-ute').slideUp(250, function() {
                fila.find('.txt-detalle-ute').val('').prop('required', false);
            });
        } else {
            fila.find('.hd-necesita-ute').val('0');
        }
    });

    $('#btn-agregar').click(function() {
        let sexo = $('#cmb-sexo').val();
        if (!sexo) return; 

        let nuevoBloque = $('#molde-fila-deporte .row-deporte-molde').clone();
        nuevoBloque.removeClass('row-deporte-molde').addClass('row-deporte-activo');
        
        let selectDeporteDestino = nuevoBloque.find('.select-deporte');
        let selectDeporteOrigen = $('#contenedor-deportes .select-deporte:first');

        selectDeporteDestino.html(selectDeporteOrigen.html());
        selectDeporteDestino.val(''); 

        nuevoBloque.find('.btn-remove').show();
        nuevoBloque.hide();
        
        $('#contenedor-deportes').append(nuevoBloque);
        nuevoBloque.fadeIn(400);
        
        controlarVisibilidadRoles();
    });

    $(document).on('click', '.btn-remove', function() {
        let fila = $(this).closest('.row-deporte-activo');
        fila.find('.select-deporte, .select-categoria, .txt-detalle-ute').prop('required', false).removeAttr('name');
        fila.fadeOut(300, function() {
            $(this).remove();
            actualizarNamesDeportes();
        });
    });


    // ==========================================
    // 4. CONTROL DE ROLES (COMPETIDOR / ACOMPAÑANTE)
    // ==========================================
    function controlarVisibilidadRoles() {
        let rol = $('#cmb-rol-asistente').val();
        
        // CORRECCIÓN CLAVE: El molde base y oculto jamás lleva requeridos activos ni names
        $('#molde-fila-deporte').find('.select-deporte, .select-categoria, .txt-detalle-ute').prop('required', false).removeAttr('name');

        if (rol === 'acompañante') {
            $('#seccion-deportiva-completa').fadeOut(300);
            $('#contenedor-deportes').find('.select-deporte, .select-categoria, .txt-detalle-ute').prop('required', false).val('');
            
            $('#bloque-delegado').fadeOut(300);
            $('#chk-delegado').prop('checked', false).prop('disabled', true);
            $('#leyenda-boton').html('Al hacer clic en "Confirmar", declara que los datos del acompañante son correctos.');
            
            actualizarNamesDeportes();
        } else {
            $('#seccion-deportiva-completa').fadeIn(300);
            $('#bloque-delegado').fadeIn(300);
            $('#chk-delegado').prop('disabled', false);
            $('#leyenda-boton').html('Al hacer clic en "Confirmar", declara que los datos son correctos y posee aptitud física para competir.');
            
            // Asignamos required ÚNICAMENTE a las filas del contenedor real y visible
            $('#contenedor-deportes .row-deporte-activo').each(function() {
                $(this).find('.select-deporte').prop('required', true);
                $(this).find('.select-categoria').prop('required', true);
                
                if ($(this).find('.bloque-detalle-ute').is(':visible') && $(this).find('.check-tengo-ute').is(':checked')) {
                    $(this).find('.txt-detalle-ute').prop('required', true);
                } else {
                    $(this).find('.txt-detalle-ute').prop('required', false);
                }
            });

            actualizarNamesDeportes();

            let sexo = $('#cmb-sexo').val();
            if (sexo) {
                $('#contenedor-deportes, #bloque-btn-agregar').fadeIn(300);
                $('#msg-espera').hide();
            } else {
                $('#msg-espera').fadeIn(300);
                $('#contenedor-deportes, #bloque-btn-agregar').hide();
            }
        }
    }

    $('#cmb-rol-asistente').on('change', controlarVisibilidadRoles);

});
</script>

</body>
</html>
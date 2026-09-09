<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Inscripción - <?= NOMBRE_META; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="<?= base_url('css/style_formularios.css') ?>" rel="stylesheet">
</head>
<body>

<div class="hero-section bg-warning">
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
                    <u>NUEVA INSCRIPCIÓN</u>
                </h1>
                
                <small class="d-block text-white-50 help-text">
                    <?= NOMBRE_SITIO; ?> <br/> <?= LUGAR_OLIMPICO; ?>
                </small>
            </div>

       </div>
    </div>
</div>

<div class="container mb-5">
    <?php if($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= $this->session->flashdata('error'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= $this->session->flashdata('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form autocomplete='off' action="<?= base_url('Inscripciones/guardar_nueva_inscripcion') ?>" method="POST" class="needs-validation" novalidate>
        
        <h3 class="section-title">1. Información Personal</h3>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">DNI</label>
                        <input type="text" name="dni" id="txt-dni" class="form-control" value="" required>
                        <small class="text-muted">Ingrese el DNI del participante</small>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nombre Completo</label>
                        <input type="text" name="nombre_completo" id="txt-nombre" class="form-control" value="" required>
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
                        <label class="form-label fw-bold">Sexo</label>
                        <select name="sexo" id="cmb-sexo" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" id="txt-fnac" class="form-control" value="" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Grupo Sanguíneo</label>
                        <select name="grupo_sanguineo" id="cmb-gsanguineo" class="form-select" required>
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
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Obra Social</label>
                        <input type="text" name="obra_social" id="txt-osocial" class="form-control" value="">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tipo de Empleado</label>
                        <select name="tipo_empleado" id="cmb-empleado" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Planta Permanente">Planta Permanente</option>
                            <option value="Contratado">Contratado</option>
                            <option value="Pensionado">Pensionado</option>
                            <option value="Jubilado">Jubilado</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="section-title">2. Contacto</h3>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" id="txt-email" class="form-control" value="" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Teléfono / Celular</label>
                        <input type="tel" name="telefono" id="txt-telefono" class="form-control" value="" placeholder="Ej: 2954123456" required>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="section-title">3. Logística y Salud</h3>
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
                        <input type="text" name="obra_social" id="txt-osocial" class="form-control" value="" placeholder="Nombre de la cobertura">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Contacto Emergencia</label>
                        <input type="text" name="contacto_emergencia" id="txt-emergencia" class="form-control" value="" placeholder="Nombre y Tel." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Dieta Especial</label>
                        <select name="dieta_especial" id="cmb-dieta" class="form-select" required>
                            <option value="Sin restricciones">Sin restricciones</option>
                            <option value="Celiaco">Celiaco</option>
                            <option value="Vegetariano">Vegetariano</option>
                            <option value="Vegano">Vegano</option>
                            <option value="Diabético">Diabético</option>
                            <option value="Hipertenso (Sin Sal)">Hipertenso (Sin Sal)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Alojamiento</label>
                        <input type="text" name="hotel_alojamiento" id="txt-hotel" class="form-control" value="" placeholder="Nombre del hotel donde se hospeda">
                    </div>
                </div>
            </div>
        </div>

        <h3 class="section-title">4. Rol y Tipo de Participación</h3>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Rol de Asistencia</label>
                        <div class="d-flex gap-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rol_asistente" id="rol-competidor" value="competidor" checked required>
                                <label class="form-check-label fw-semibold" for="rol-competidor">
                                    Competidor
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rol_asistente" id="rol-acompanante" value="acompanante">
                                <label class="form-check-label fw-semibold" for="rol-acompanante">
                                    Acompañante
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6" id="contenedor-delegado">
                        <label class="form-label fw-bold">&nbsp;</label>
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="es_delegado" id="chk-delegado" value="1">
                            <label class="form-check-label fw-semibold text-danger" for="chk-delegado">
                                Marcar como DELEGADO de la delegación
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE DEPORTES (Solo visible si es competidor) -->
        <div id="seccion-deportes">
            <h3 class="section-title">5. Disciplinas Deportivas</h3>
            <div class="card mb-4">
                <div class="card-body">
                    <p class="text-muted small mb-3">Seleccioná los deportes en los que querés competir.</p>
                    
                    <div id="contenedor-deportes-dinamico">
                        <div class="fila-deporte card p-3 mb-3 border bg-light-subtle">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Deporte</label>
                                    <select name="deporte_id[]" class="form-select form-select-sm cmb-deporte" required onchange="cargarCategorias(this, 0)">
                                        <option value="">Seleccione...</option>
                                        <?php foreach($deportes as $d): ?>
                                            <option value="<?= $d['id_deporte'] ?>" data-genero="<?= htmlspecialchars($d['genero'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small">Categoría</label>
                                    <select name="categoria_id[]" class="form-select form-select-sm cmb-categoria" required>
                                        <option value="">Primero seleccione deporte...</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-bold small">¿Tiene UTE?</label>
                                    <select name="tiene_ute[]" class="form-select form-select-sm cmb-tiene-ute" onchange="toggleDetalleUte(this)">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-bold small">¿Necesita UTE?</label>
                                    <select name="necesita_ute[]" class="form-select form-select-sm cmb-necesita-ute" onchange="toggleDetalleUte(this)">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="eliminarFilaDeporte(this)">
                                        <i class="bi bi-trash3-fill"></i> Eliminar
                                    </button>
                                </div>

                                <div class="col-md-12 detalle-ute-container" style="display:none;">
                                    <label class="form-label fw-bold small">Detalle de la UTE / Equipo / Compañeros</label>
                                    <input type="text" name="detalle_ute[]" class="form-control form-control-sm txt-detalle-ute" placeholder="Ej: 'Equipo Fútbol 5 - Juan Pérez, María Gómez...'">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="btn-agregar-deporte" class="btn btn-outline-primary btn-sm mt-2" onclick="agregarFilaDeporte()">
                        <i class="bi bi-plus-circle-fill"></i> Agregar otro deporte
                    </button>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <p id="leyenda-boton" class="text-muted small mb-3">
                Al hacer clic en "Guardar Nueva Inscripción", se registrará todos los datos del participante.
            </p>
            
            <button type="submit" id="btn-submit-principal" class="btn btn-success btn-lg shadow-lg px-5 fw-bold text-dark">
                <i class="bi bi-check-circle-fill me-2"></i> GUARDAR NUEVA INSCRIPCIÓN
            </button>
            
            <a href="<?= base_url('Inscripciones/control_total') ?>" class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-arrow-left me-2"></i> VOLVER AL PANEL
            </a>
        </div>

    </form>
</div>

<script>
$(document).ready(function() {
    
    // Toggle de sección de deportes según rol
    $('input[name="rol_asistente"]').change(function() {
        if ($('#rol-competidor').is(':checked')) {
            $('#seccion-deportes').slideDown();
            $('#contenedor-delegado').fadeIn();
        } else {
            $('#seccion-deportes').slideUp();
            $('#contenedor-delegado').fadeOut();
        }
    });

    // Función para cargar categorías dinámicamente
    window.cargarCategorias = function(selectDeporte, index) {
        const idDeporte = $(selectDeporte).val();
        const selectCategoria = $(selectDeporte).closest('.fila-deporte').find('.cmb-categoria');
        
        if (!idDeporte) {
            selectCategoria.html('<option value="">Primero seleccione deporte...</option>');
            return;
        }

        selectCategoria.html('<option value="">Cargando...</option>');
        
        $.get('<?= base_url('Inscripciones/getCategorias/') ?>' + idDeporte, function(data) {
            selectCategoria.html('<option value="">Seleccione categoría...</option>');
            $.each(data, function(key, categoria) {
                selectCategoria.append('<option value="' + categoria.id_categoria + '">' + categoria.nombre_categoria + '</option>');
            });
        });
    };

    // Toggle del campo de detalle UTE
    window.toggleDetalleUte = function(select) {
        const fila = $(select).closest('.fila-deporte');
        const tieneUte = fila.find('.cmb-tiene-ute').val();
        const necesitaUte = fila.find('.cmb-necesita-ute').val();
        
        if (tieneUte == '1' || necesitaUte == '1') {
            fila.find('.detalle-ute-container').slideDown();
        } else {
            fila.find('.detalle-ute-container').slideUp();
        }
    };

    // Agregar nueva fila de deporte
    window.agregarFilaDeporte = function() {
        const contador = $('#contenedor-deportes-dinamico .fila-deporte').length;
        const nuevaFila = `
            <div class="fila-deporte card p-3 mb-3 border bg-light-subtle">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Deporte</label>
                        <select name="deporte_id[]" class="form-select form-select-sm cmb-deporte" required onchange="cargarCategorias(this, ${contador})">
                            <option value="">Seleccione...</option>
                            <?php foreach($deportes as $d): ?>
                                <option value="<?= $d['id_deporte'] ?>" data-genero="<?= htmlspecialchars($d['genero'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Categoría</label>
                        <select name="categoria_id[]" class="form-select form-select-sm cmb-categoria" required>
                            <option value="">Primero seleccione deporte...</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold small">¿Tiene UTE?</label>
                        <select name="tiene_ute[]" class="form-select form-select-sm cmb-tiene-ute" onchange="toggleDetalleUte(this)">
                            <option value="0">No</option>
                            <option value="1">Sí</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold small">¿Necesita UTE?</label>
                        <select name="necesita_ute[]" class="form-select form-select-sm cmb-necesita-ute" onchange="toggleDetalleUte(this)">
                            <option value="0">No</option>
                            <option value="1">Sí</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="eliminarFilaDeporte(this)">
                            <i class="bi bi-trash3-fill"></i> Eliminar
                        </button>
                    </div>

                    <div class="col-md-12 detalle-ute-container" style="display:none;">
                        <label class="form-label fw-bold small">Detalle de la UTE / Equipo / Compañeros</label>
                        <input type="text" name="detalle_ute[]" class="form-control form-control-sm txt-detalle-ute" placeholder="Ej: 'Equipo Fútbol 5 - Juan Pérez, María Gómez...'">
                    </div>
                </div>
            </div>
        `;
        $('#contenedor-deportes-dinamico').append(nuevaFila);
    };

    // Eliminar fila de deporte
    window.eliminarFilaDeporte = function(btn) {
        const totalFilas = $('#contenedor-deportes-dinamico .fila-deporte').length;
        if (totalFilas > 1) {
            $(btn).closest('.fila-deporte').remove();
        } else {
            alert('Debe tener al menos un deporte registrado.');
        }
    };

    // Inicializar estado de campos UTE
    $('.cmb-tiene-ute, .cmb-necesita-ute').each(function() {
        toggleDetalleUte(this);
    });
});
</script>

<?php $this->load->view('footer'); ?>

</body>
</html>

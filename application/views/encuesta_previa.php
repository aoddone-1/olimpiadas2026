<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sondeo Previo - <?= NOMBRE_META; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
                    Encuesta <u>Previa a Inscripciones</u>
                </h1>
                
                <small class="d-block text-white-50 help-text">
                    Ayudanos a dimensionar las olimpiadas respondiendo este breve cuestionario.
                </small>
            </div>

        </div>
    </div>
</div>

<div class="container mb-5" style="max-width: 800px;">
    
    <?php if($this->session->flashdata('encuesta_ok')): ?>
        <div class="alert alert-success shadow text-center py-4 rounded-3 border-0">
            <i class="bi bi-check-circle-fill fs-1 text-success d-block mb-2"></i>
            <h5>¡Muchas gracias por participar!</h5>
            <p class="m-0 text-muted">Tus respuestas nos ayudan a organizar un mejor evento.</p>
        </div>
    <?php else: ?>

    <form autocomplete='off' action="<?= base_url('encuesta/guardar_respuesta') ?>" method="POST" class="needs-validation" novalidate>
        
        <!-- DATOS DEMOGRÁFICOS -->
        <h3 class="section-title">1. Datos Generales</h3>
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">DNI</label>
                        <input type="number" name="dni" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Delegación (Provincia)</label>
                        <select name="delegacion" class="form-select" required>
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
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" id="txt-nacimiento" class="form-control" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Género</label>
                        <select name="sexo" id="cmb-sexo" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-text text-muted"><i class="bi bi-info-circle"></i> Usamos la edad y el género para calcular qué categorías reales conviene abrir en base a los interesados.</div>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="section-title">2. ¿En qué deportes tenés intención de participar?</h3>
        <p class="text-muted small px-2 mb-3"><i class="bi bi-info-circle"></i> Podés marcar todas las opciones que consideres. <u class="text-danger"><strong>NO es una inscripción definitiva.</strong></u></p>
        
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    
                    <div id="msg-espera" class="col-12 text-center text-muted py-4">
                        <i class="bi bi-person-fill-check fs-2 text-primary d-block mb-2"></i>
                        <p class="m-0 fw-semibold">Por favor, seleccioná tu género arriba para desplegar los deportes disponibles.</p>
                    </div>

                    <?php if(!empty($deportes)): ?>
                        <?php foreach($deportes as $d): ?>
                            <div class="col-6 col-sm-4 sport-container" data-genero="<?= $d['genero'] ?>">
                                <input type="checkbox" name="deportes_interes[]" value="<?= $d['id_deporte'] ?>" id="sport_<?= $d['id_deporte'] ?>" class="sport-checkbox">
                                <label for="sport_<?= $d['id_deporte'] ?>" class="sport-label text-center h-100 d-flex flex-column justify-content-center">
                                    <i class="bi bi-trophy mb-1 text-muted"></i>
                                    <span class="small text-uppercase fw-bold"><?= htmlspecialchars($d['nombre_deporte'], ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center text-muted">No hay deportes cargados.</div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg shadow px-5 py-3 fw-bold">
                <i class="bi bi-send-fill me-2"></i> ENVIAR RESPUESTAS
            </button>
        </div>

    </form>
    <?php endif; ?>
</div>
<?php $this->load->view('footer'); ?>
<script>
(function () {
    'use strict'

    var selectSexo = document.getElementById('cmb-sexo');
    var deportes = document.querySelectorAll('.sport-container');

    // --- LÓGICA DE FILTRADO DINÁMICO ---
    var msgEspera = document.getElementById('msg-espera'); // Capturamos el cartel

    function filtrarDeportes() {
        var valorSeleccionado = selectSexo.value.toUpperCase(); // 'MASCULINO', 'FEMENINO' o 'OTRO'

        if (valorSeleccionado === '') {
            // Si está vacío, MOSTRAMOS el cartel y ocultamos deportes
            if (msgEspera) msgEspera.style.display = 'block';
            
            deportes.forEach(function (contenedor) {
                contenedor.style.display = 'none';
                contenedor.querySelector('.sport-checkbox').checked = false;
            });
        } else {
            // Si ya eligió sexo, OCULTAMOS el cartel de espera
            if (msgEspera) msgEspera.style.display = 'none';

            deportes.forEach(function (contenedor) {
                var generoDeporte = contenedor.getAttribute('data-genero');
                var checkbox = contenedor.querySelector('.sport-checkbox');

                if (valorSeleccionado === 'MASCULINO') {
                    if (generoDeporte === 'MASCULINO' || generoDeporte === 'MIXTO') {
                        contenedor.style.display = 'block';
                    } else {
                        contenedor.style.display = 'none';
                        checkbox.checked = false;
                    }
                } else if (valorSeleccionado === 'FEMENINO') {
                    if (generoDeporte === 'FEMENINO' || generoDeporte === 'MIXTO') {
                        contenedor.style.display = 'block';
                    } else {
                        contenedor.style.display = 'none';
                        checkbox.checked = false;
                    }
                } else {
                    if (generoDeporte === 'MIXTO') {
                        contenedor.style.display = 'block';
                    } else {
                        contenedor.style.display = 'none';
                        checkbox.checked = false;
                    }
                }
            });
        }
    }

    // Ejecutamos al cambiar el select y al cargar la página (por si hay recarga con datos)
    if (selectSexo) {
        selectSexo.addEventListener('change', filtrarDeportes);
        filtrarDeportes(); 
    }


    // --- VALIDACIÓN DE FORMULARIO DE BOOTSTRAP ---
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            
            // Solo validar deportes si el formulario en general es válido hasta acá
            if (form.checkValidity()) {
                // Verificar si marcó al menos un deporte VISIBLE
                var checkboxesVisibles = document.querySelectorAll('.sport-container[style*="display: block"] .sport-checkbox:checked');
                
                if (checkboxesVisibles.length === 0) {
                    alert('Por favor, seleccioná al menos un deporte de tu interés para continuar.');
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
            } else {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated')
        }, false)
    })
})()
</script>
</body>
</html>
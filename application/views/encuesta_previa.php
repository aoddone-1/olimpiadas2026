<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sondeo Previo - Olimpiadas Nacionales 2026</title>
    <meta name="description" content="Participá del sondeo previo para las XXXVIII Olimpiadas Nacionales de Empleados de Institutos de Vivienda - La Pampa 2026.">
    <meta name="author" content="Comisión Organizadora">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= current_url() ?>">
    <meta property="og:title" content="Sondeo Previo - Olimpiadas Nacionales 2026">
    <meta property="og:description" content="Ayudanos a dimensionar la logística y categorías del evento. ¡Tu participación es clave!">
    <meta property="og:image" content="<?= base_url('assets/img/compartir-card.png') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/png">
    <meta property="og:site_name" content="Olimpiadas Vivienda 2026">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px 0;
            border-bottom: 5px solid #ffc107;
            margin-bottom: 30px;
        }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .section-title { color: #1e3c72; border-left: 5px solid #ffc107; padding-left: 15px; margin-bottom: 20px; }
        .btn-primary { background-color: #1e3c72; border: none; border-radius: 8px; }
        .btn-primary:hover { background-color: #2a5298; }
        
        /* Estilo para los checkboxes de deportes tipo tarjetas chicas */
        .sport-checkbox { display: none; }
        .sport-label {
            display: block;
            padding: 12px;
            background: #fff;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .sport-checkbox:checked + .sport-label {
            border-color: #1e3c72;
            background-color: #eef2f7;
            color: #1e3c72;
        }
    </style>
</head>
<body>

<div class="hero-section text-center">
    <div class="container">
        <h1 class="display-6 fw-bold text-uppercase">Encuesta <u>Previa a Inscripciones</u></h1>
        <p class="lead m-0">XXXVIII Olimpiadas Nacionales de Empleados de Institutos de Vivienda</p>
        <p class="lead mt-2"><i class="bi bi-geo-alt-fill"></i> La Pampa 2026</p>
        <small class="text-white-50">Ayudanos a dimensionar las olimpiadas respondiendo este breve cuestionario.</small>
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
                        <label class="form-label fw-bold">Sexo</label>
                        <select name="sexo" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-text text-muted"><i class="bi bi-info-circle"></i> Usamos la edad y el sexo para calcular qué categorías reales conviene abrir en base a los interesados.</div>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="section-title">2. ¿En qué deportes tenés intención de participar?</h3>
        <p class="text-muted small px-2 mb-3"><i class="bi bi-info-circle"></i> Podés marcar todas las opciones que consideres. <u class="text-danger"><strong>NO es una inscripción definitiva.</strong></u></p>
        
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <?php if(!empty($deportes)): ?>
                        <?php foreach($deportes as $d): ?>
                            <div class="col-6 col-sm-4">
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

<script>
// Validación básica de Bootstrap
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (forms) {
        forms.addEventListener('submit', function (event) {
            // Verificar si marcó al menos un deporte
            var checkboxes = document.querySelectorAll('input[name="deportes_interes[]"]:checked');
            if (checkboxes.length === 0) {
                alert('Por favor, seleccioná al menos un deporte de tu interés.');
                event.preventDefault();
                event.stopPropagation();
                return;
            }

            if (!forms.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            forms.classList.add('was-validated')
        }, false)
    })
})()
</script>
</body>
</html>
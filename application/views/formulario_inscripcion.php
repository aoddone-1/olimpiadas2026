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
                    FORMULARIO DE <u>INSCRIPCIÓN</u>
                </h1>
                
                <small class="d-block text-white-50 help-text">
                    <?= NOMBRE_SITIO; ?> <br/> <?= LUGAR_OLIMPICO; ?>
                </small>
            </div>

       </div>
    </div>
</div>

<<<<<<< HEAD
<div class="container mb-5" style="max-width: 800px;">
=======
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
                        <input type="text" name="hotel_alojamiento" id="txt-hotel" class="form-control" required placeholder="Nombre del hotel donde se hospeda">
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
                                        <input class="form-check-input check-tengo-ute" type="checkbox" name="opcion_ute" id="radioTengo" value="tengo" >
                                        <label class="form-check-label text-success fw-bold" for="radioTengo">
                                            Tengo EQUIPO (o UTE)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input type="hidden" class="hd-necesita-ute" value="0">
                                        <input class="form-check-input check-necesito-ute" type="checkbox" name="opcion_ute" id="radioNecesito" value="necesito"  >
                                        <label class="form-check-label text-danger fw-bold" for="radioNecesito">
                                            Necesito UTE
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 bloque-detalle-ute" style="display: none;">
                                    <textarea class="form-control txt-detalle-ute" rows="1" placeholder="Detalle de tu EQUIPO o UTE (Integrantes, equipo, etc.)"></textarea>
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
>>>>>>> ea7b9bf7a80bea80ffb6bf6127d6aa8017713193
    
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4 bg-white">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center g-4">
                
                <div class="col-6 col-md-8 text-center text-md-start">
                                        
                    <h2 class="fw-bold text-dark mb-3">LA INSCRIPCION FINALIZÓ!</h2>
                    
                    <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.6;">
                        A partir de este momento ya no es posible realizar nuevas inscripciones ni modificar los datos de la que ya completaste.
                        <br><br>
                        Ahora estamos armando el <strong>fixture, las categorías y el cronograma</strong> de cada disciplina para que todo salga como se debe. ¡Pronto vamos a estar compartiendo toda la info!
                    </p>
                </div>

                <div class="col-6 col-md-4 text-center">
                    <img src="<?= base_url('assets/img/pampito.png') ?>" 
                         alt="Mascota Olimpiadas" 
                         class="img-fluid" 
                         style="object-fit: contain; filter: drop-shadow(0px 8px 16px rgba(0,0,0,0.1));">
                </div>
                <div class="col-12 col-md-12 text-center">
                    <div class="p-3 bg-light rounded-3 d-flex align-items-start gap-2 border border-warning-subtle">
                        <i class="bi bi-bell-fill text-warning fs-5 mt-0.5"></i>
                        <span class="fw-semibold text-dark small">¿Necesitás hacer algún cambio o corrección en tus datos? Por favor, comunicate directamente con los <strong>Delegados de La Pampa</strong> para que poder ayudarte.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php $this->load->view('footer'); ?>

</body>
</html>
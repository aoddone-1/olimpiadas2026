<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción - XXXVIII Olimpiadas Nacionales IV</title>
    <meta name="description" content="Inscripción para las XXXVIII Olimpiadas Nacionales de Empleados de Institutos de Vivienda - La Pampa 2026.">
    <meta name="author" content="Comisión Organizadora">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= current_url() ?>">
    <meta property="og:title" content="Inscripción - Olimpiadas Nacionales 2026">
    <meta property="og:description" content="Inscripción para las XXXVIII Olimpiadas Nacionales de Empleados de Institutos de Vivienda - La Pampa 2026.">
    <meta property="og:image" content="<?= base_url('assets/img/compartir-card.png') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/png">
    <meta property="og:site_name" content="Olimpiadas Vivienda 2026">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .hero-section img {
            filter: drop-shadow(0px 4px 8px rgba(0,0,0,0.2)); /* Le da profundidad al logo */
        }
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px 0;
            border-bottom: 5px solid #ffc107; /* Un toque de color para resaltar */
            margin-bottom: 30px;
        }
        .row-deporte {
            border-left: 5px solid #1e3c72 !important;
            margin-bottom: 20px;
        }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .card-header { border-radius: 15px 15px 0 0 !important; font-weight: bold; }
        .btn-primary { background-color: #1e3c72; border: none; border-radius: 8px; }
        .btn-primary:hover { background-color: #2a5298; }
        .section-title { color: #1e3c72; border-left: 5px solid #ffc107; padding-left: 15px; margin-bottom: 20px; }
        .row-deporte { position: relative; background: #fff; border-radius: 10px; transition: all 0.3s; }
        .btn-remove { position: absolute; top: -10px; right: -10px; border-radius: 50%; width: 30px; height: 30px; padding: 0; line-height: 1; }
    </style>
</head>
<body>

<div class="hero-section">
    <div class="container text-center">
        <h1 class="display-5 fw-bold">INSCRIPCION XXXVIII OLIMPIADAS NACIONALES</h1>
        <h2 class="h4">de Empleados de Institutos de Vivienda</h2>
        <p class="lead mt-2"><i class="bi bi-geo-alt-fill"></i> La Pampa 2026</p>
    </div>
</div>

<div class="container mb-5">
    <form action="<?= base_url('Inscripciones/guardar') ?>" autocomplete="off" method="POST" class="needs-validation" >
        
        <!-- DATOS PERSONALES -->
       <h3 class="section-title">1. Información Personal</h3>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">DNI</label>
                        <input type="text" name="dni" class="form-control" placeholder="Sin puntos" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nombre Completo</label>
                        <input type="text" name="nombre_completo" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" placeholder="Ej: 2954123456" required>
                    </div>
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Sexo</label>
                        <select name="sexo" class="form-select" required>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fecha Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipo Empleado</label>
                        <select name="tipo_empleado" class="form-select" required>
                            <option value="Planta Permanente">Planta Permanente</option>
                            <option value="Jubilado">Jubilado</option>
                            <option value="Contratado">Contratado</option>
                            <option value="Pasante">Pasante</option>
                            <option value="Otros">Otros</option>
                        </select>
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
                        <select name="grupo_sanguineo" class="form-select" required>
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
                        <input type="text" name="obra_social" class="form-control" placeholder="Nombre de la cobertura">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Contacto Emergencia</label>
                        <input type="text" name="contacto_emergencia" class="form-control" placeholder="Nombre y Tel." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Dieta Especial</label>
                        <select name="dieta_especial" class="form-select" required>
                            <option value="Sin restricciones" selected>Sin restricciones</option>
                            <option value="Celiaco">Celiaco</option>
                            <option value="Vegetariano">Vegetariano</option>
                            <option value="Vegano">Vegano</option>
                            <option value="Diabético">Diabético</option>
                            <option value="Hipertenso (Sin Sal)">Hipertenso (Sin Sal)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Hotel de Alojamiento</label>
                        <input type="text" name="hotel_alojamiento" class="form-control" placeholder="Nombre del hotel asignado">
                    </div>
                </div>
            </div>
        </div>

        <!-- INSCRIPCIÓN DEPORTIVA -->
        <h3 class="section-title">3. Disciplinas Deportivas</h3>
        <div id="contenedor-deportes">
            <!-- Bloque semilla -->
            <div class="card mb-3 row-deporte border">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><i class="bi bi-trophy"></i> Deporte</label>
                            <select name="deporte_id[]" class="form-select select-deporte" required>
                                <option value="">Seleccione un deporte...</option>
                                <?php foreach($deportes as $d): ?>
                                    <option value="<?= $d['id_deporte'] ?>"><?= $d['nombre_deporte'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><i class="bi bi-tags"></i> Categoría</label>
                            <select name="categoria_id[]" class="form-select select-categoria" required disabled>
                                <option value="">Elija el deporte primero</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="btn btn-danger btn-remove shadow-sm" style="display:none;"><i class="bi bi-x"></i></button>
                </div>
            </div>
        </div>

        <div class="text-center mb-5">
            <button type="button" id="btn-agregar" class="btn btn-outline-primary fw-bold">
                <i class="bi bi-plus-circle-fill"></i> AGREGAR OTRA DISCIPLINA
            </button>
        </div>

        <div class="card bg-white p-4 text-center border-top border-4 border-warning">
            <p class="text-muted small">Al hacer clic en "Confirmar", declara que los datos son correctos y posee aptitud física para competir.</p>
            <button type="submit" class="btn btn-primary btn-lg shadow-lg px-5">
                <i class="bi bi-check-circle-fill"></i> CONFIRMAR INSCRIPCIÓN
            </button>
        </div>

    </form>
</div>

<script>
$(document).ready(function() {
    // Carga de categorías
    $(document).on('change', '.select-deporte', function() {
        let id_deporte = $(this).val();
        let fila = $(this).closest('.card-body');
        let selectCat = fila.find('.select-categoria');

        if(id_deporte) {
            $.get('<?= base_url('Inscripciones/getCategorias/') ?>' + id_deporte, function(data) {
                selectCat.empty().append('<option value="">Seleccione categoría...</option>');
                data.forEach(function(cat) {
                    selectCat.append(`<option value="${cat.id_categoria}">${cat.nombre_categoria}</option>`);
                });
                selectCat.prop('disabled', false);
            });
        } else {
            selectCat.prop('disabled', true).empty().append('<option value="">Elija el deporte primero</option>');
        }
    });

    // Agregar nuevo bloque de deporte
    $('#btn-agregar').click(function() {
        // Clonamos la primera fila
        let nuevoBloque = $('.row-deporte:first').clone();

        // --- EL FIX ESTÁ AQUÍ ---
        // 1. Buscamos los selects dentro del nuevo bloque
        let selectDeporte = nuevoBloque.find('.select-deporte');
        let selectCategoria = nuevoBloque.find('.select-categoria');

        // 2. Limpiamos valores y nos aseguramos que el deporte NO esté disabled
        selectDeporte.val('').prop('disabled', false); 
        
        // 3. La categoría sí debe empezar disabled hasta que elija el deporte
        selectCategoria.val('').empty().append('<option value="">Elija el deporte primero</option>').prop('disabled', true);

        // 4. Mostramos el botón de quitar
        nuevoBloque.find('.btn-remove').show();

        // 5. Lo agregamos al contenedor con una pequeña animación
        nuevoBloque.hide();
        $('#contenedor-deportes').append(nuevoBloque);
        nuevoBloque.fadeIn(400);
    });

    // Quitar bloque
    $(document).on('click', '.btn-remove', function() {
        $(this).closest('.row-deporte').fadeOut(300, function() {
            $(this).remove();
        });
    });
});
</script>

</body>
</html>
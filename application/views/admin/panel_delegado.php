<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Delegado - <?= NOMBRE_META; ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/icon.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <style>
        .header-delegado {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .card-participante {
            border-left: 4px solid #28a745;
            transition: transform 0.2s;
        }
        .card-participante:hover {
            transform: translateX(5px);
        }
        .badge-deporte {
            background-color: #e9f7ef;
            color: #28a745;
            border: 1px solid #28a745;
        }
    </style>
</head>
<body>

<!-- Header específico para delegados -->
<div class="header-delegado shadow-sm">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="m-0 fw-bold"><i class="bi bi-people-fill me-2"></i>Panel de Delegación</h4>
                <small class="opacity-75"><?= htmlspecialchars($delegacion) ?></small>
            </div>
            <div>
                <a href="<?= base_url('Inscripciones/logout') ?>" class="btn btn-light btn-sm text-success fw-bold">
                    <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">

    <!-- Tarjetas de resumen -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-indicador shadow-sm" style="border-left-color: #28a745;">
                <div class="card-body p-3">
                    <h6 class="text-muted small text-uppercase mb-1">Total Inscriptos</h6>
                    <h3 class="fw-bold text-success m-0"><?= $total_inscriptos ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de participantes -->
    <div class="mb-4 search-container">
        <div class="input-group shadow-sm rounded-3 overflow-hidden">
            <span class="input-group-text bg-white border-0 text-muted ps-3"><i class="bi bi-search"></i></span>
            <input type="text" id="buscador" class="form-control border-0 p-3 shadow-none" placeholder="Buscar competidor por Nombre o DNI...">
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-secondary m-0">Competidores de tu Delegación</h5>
        <span id="contador-resultados" class="badge bg-success text-white px-2 py-1.5 rounded" style="display: none;"></span>
    </div>
    
    <div id="lista-participantes">
        <?php if(!empty($participantes)): ?>
            <div class="table-responsive bg-white rounded-3 shadow-sm overflow-hidden">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase fw-bold">Nombre</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold">DNI</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold">Deportes</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold text-end pe-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($participantes as $p): ?>
                            <tr class="fila-participante" 
                                data-string="<?= strtoupper(htmlspecialchars($p['nombre_completo'] . ' ' . $p['dni'])) ?>" 
                                style="border-bottom: 1px solid #f0f0f0;">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" 
                                             style="width: 40px; height: 40px; min-width: 40px;">
                                            <i class="bi bi-person-fill text-success"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['nombre_completo']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($p['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['dni']) ?></span>
                                </td>
                                <td>
                                    <?php if(!empty($p['deportes'])): ?>
                                        <?php foreach($p['deportes'] as $dep): ?>
                                            <span class="badge badge-deporte me-1 mb-1">
                                                <i class="bi bi-trophy me-1"></i><?= htmlspecialchars($dep['nombre_deporte']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin deportes</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>Inscripto
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info border-0 shadow-sm">
                <i class="bi bi-info-circle-fill me-2"></i>
                No hay participantes inscriptos para tu delegación aún.
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buscador = document.getElementById('buscador');
    const filas = document.querySelectorAll('.fila-participante');
    const sinResultados = document.getElementById('sin-resultados');
    const contadorResultados = document.getElementById('contador-resultados');

    if (buscador) {
        buscador.addEventListener('input', function() {
            const query = this.value.toUpperCase();
            let encontrados = 0;

            filas.forEach(fila => {
                if (fila.getAttribute('data-string').includes(query)) {
                    fila.style.setProperty('display', 'table-row', 'important');
                    encontrados++;
                } else {
                    fila.style.setProperty('display', 'none', 'important');
                }
            });

            if (query === "") {
                contadorResultados.style.display = 'none';
            } else {
                contadorResultados.innerText = encontrados + (encontrados === 1 ? ' coincidencia' : ' coincidencias');
                contadorResultados.style.display = 'inline-block';
            }
        });
    }
});
</script>

</body>
</html>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="d-flex align-items-center">
            <i class="bi bi-list-check text-warning me-2"></i> 
            <span>Registros de Sondeo Inicial</span>
            <span class="badge bg-secondary ms-2" id="contador-encuestas"><?= count($listado_encuestas) ?> filas</span>
        </div>
        
        <div class="position-relative" style="max-width: 300px; width: 100%;">
            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" 
                   id="inputBuscarEncuesta" 
                   class="form-control form-control-sm rounded-pill ps-5 bg-light" 
                   placeholder="Buscar por Nombre, DNI, Delegación...">
        </div>
    </div>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tablaEncuestas">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Participante / DNI</th>
                        <th>Delegación</th>
                        <th class="text-center">Deportes Votados</th> 
                        <th>Edad / Sexo</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($listado_encuestas)): foreach($listado_encuestas as $enc): ?>
                    <tr class="js-fila-encuesta">
                        <td>
                            <div class="fw-bold text-dark">
                                <?= !empty($enc['nombre_participante']) ? htmlspecialchars($enc['nombre_participante'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted italic small">Sondeo Anónimo</span>'; ?>
                            </div>
                            <small class="text-muted">DNI: <?= htmlspecialchars($enc['dni'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><span class="badge bg-light text-primary border"><?= htmlspecialchars($enc['delegacion'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        
                        <td class="text-center">
                            <button type="button" 
                                    class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold js-btn-ver-deportes" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalDeportesVotados"
                                    data-participante="<?= !empty($enc['nombre_participante']) ? htmlspecialchars($enc['nombre_participante'], ENT_QUOTES, 'UTF-8') : 'Sondeo Anónimo (DNI: '.$enc['dni'].')'; ?>"
                                    data-deportes="<?= !empty($enc['deportes_votados']) ? htmlspecialchars($enc['deportes_votados'], ENT_QUOTES, 'UTF-8') : 'Ninguno'; ?>">
                                <i class="bi bi-eye-fill me-1"></i> Ver Deportes
                            </button>
                        </td>

                        <td><small><?= htmlspecialchars($enc['sexo'], ENT_QUOTES, 'UTF-8') ?></small></td>
                        <td class="text-center">
                            <a href="<?= base_url('Inscripciones/eliminar_encuesta/'.$enc['id_respuesta']) ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('¿Seguro querés borrar esta encuesta?');">
                                <i class="bi bi-trash3-fill"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr id="filaNoHayDatos"><td colspan="5" class="text-center text-muted py-4">No hay encuestas en la base de datos.</td></tr>
                    <?php endif; ?>
                    
                    <tr id="filaNoResultados" style="display: none;">
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-exclamation-circle text-danger me-2"></i>No se encontraron coincidencias para tu búsqueda.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputBuscar = document.getElementById('inputBuscarEncuesta');
    if (!inputBuscar) return; // Validación por si las dudas

    const filas = document.querySelectorAll('.js-fila-encuesta');
    const filaNoResultados = document.getElementById('filaNoResultados');
    const contador = document.getElementById('contador-encuestas');

    inputBuscar.addEventListener('keyup', function () {
        const terminoBusqueda = this.value.toLowerCase().trim();
        let filasVisibles = 0;

        filas.forEach(fila => {
            // Evaluamos todo el texto plano de la fila (Nombre, DNI, Delegación, Sexo)
            const textoFila = fila.textContent.toLowerCase();

            if (textoFila.includes(terminoBusqueda)) {
                fila.style.display = ''; // Muestra la fila (vuelve al valor por defecto)
                filasVisibles++;
            } else {
                fila.style.display = 'none'; // Oculta la fila
            }
        });

        // Actualizamos el contador dinámicamente con las filas encontradas
        if (contador) {
            contador.innerText = `${filasVisibles} ${filasVisibles === 1 ? 'fila' : 'filas'}`;
        }

        // Si no hay ninguna coincidencia, mostramos el cartel de aviso
        if (filasVisibles === 0 && filas.length > 0) {
            filaNoResultados.style.display = '';
        } else {
            filaNoResultados.style.display = 'none';
        }
    });
});
</script>
<div class="container-fluid py-4">
    <!-- Alerta de construcción -->
    <div class="alert alert-info border-info-subtle d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-calendar-event fs-3 me-3 text-info"></i>
        <div>
            <strong class="d-block text-dark">Gestión de Fixture y Cronogramas</strong>
            <span class="text-secondary small">Generá automáticamente los cruces, asigná horarios y cargá los resultados de cada categoría.</span>
        </div>
    </div>

    <!-- Filtros superiores -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="filtro_deporte" class="form-label fw-semibold">Deporte</label>
                    <select class="form-select" id="filtro_deporte">
                        <option value="">Todos los deportes</option>
                        <?php foreach ($deportes as $deporte): ?>
                            <option value="<?= $deporte['id_deporte'] ?>"><?= htmlspecialchars($deporte['nombre_deporte']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filtro_categoria" class="form-label fw-semibold">Categoría</label>
                    <select class="form-select" id="filtro_categoria" disabled>
                        <option value="">Seleccione un deporte primero</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" id="btn_cargar_fixture">
                        <i class="bi bi-search me-1"></i> Cargar Fixture
                    </button>
                    <button class="btn btn-success" id="btn_generar_fixture" data-bs-toggle="modal" data-bs-target="#modalGenerarFixture" disabled>
                        <i class="bi bi-plus-circle me-1"></i> Generar Cruces
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="row g-3 mb-4" id="panel_estadisticas" style="display: none;">
        <div class="col-md-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <i class="bi bi-trophy fs-1 mb-2"></i>
                    <h3 class="mb-0" id="stat_total_partidos">0</h3>
                    <small>Total de Partidos</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle fs-1 mb-2"></i>
                    <h3 class="mb-0" id="stat_jugados">0</h3>
                    <small>Partidos Jugados</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body text-center">
                    <i class="bi bi-clock fs-1 mb-2"></i>
                    <h3 class="mb-0" id="stat_pendientes">0</h3>
                    <small>Partidos Pendientes</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de partidos -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-task me-2"></i>Partidos Programados</span>
            <span class="badge bg-info" id="lbl_categoria_seleccionada"></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabla_fixture">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Fecha</th>
                            <th>Hora</th>
                            <th>Ronda/Grupo</th>
                            <th>Local / Visitante</th>
                            <th>Lugar</th>
                            <th>Resultado</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo_tabla_fixture">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                Seleccione una categoría y cargue el fixture
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para generar fixture -->
<div class="modal fade" id="modalGenerarFixture" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-diagram-3 me-2"></i>Generar Fixture Automático</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_id_categoria">
                
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Atención:</strong> Esta acción generará todos los cruces automáticamente. Si ya existe un fixture, deberá eliminarlo primero.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Competidores Inscriptos</label>
                    <div id="lista_competidores" class="list-group">
                        <!-- Se carga dinámicamente -->
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipo de Fixture</label>
                    <select class="form-select" id="tipo_fixture">
                        <option value="todos_contra_todos">Todos contra Todos (Liga)</option>
                        <option value="eliminatoria">Eliminatoria Simple</option>
                    </select>
                    <small class="text-muted">
                        <br><i class="bi bi-info-circle"></i> 
                        <strong>Todos contra Todos:</strong> Cada competidor juega contra todos los demás.<br>
                        <i class="bi bi-info-circle"></i> 
                        <strong>Eliminatoria:</strong> El perdedor queda eliminado en cada ronda.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn_confirmar_generar">
                    <i class="bi bi-magic me-1"></i> Generar Fixture
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cargar resultado -->
<div class="modal fade" id="modalResultado" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-scoreboard me-2"></i>Cargar Resultado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="resultado_id_partido">
                <div class="row g-3">
                    <div class="col-6 text-center">
                        <label class="form-label small" id="lbl_local"></label>
                        <input type="number" class="form-control form-control-lg text-center" id="resultado_local" min="0" value="0">
                    </div>
                    <div class="col-6 text-center">
                        <label class="form-label small" id="lbl_visitante"></label>
                        <input type="number" class="form-control form-control-lg text-center" id="resultado_visitante" min="0" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn_guardar_resultado">
                    <i class="bi bi-check-lg me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar estado -->
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-toggle-on me-2"></i>Cambiar Estado del Partido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="estado_id_partido">
                <p class="mb-3">Seleccione el nuevo estado del partido:</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn_estado_opcion" data-estado="PROGRAMADO">
                        <i class="bi bi-calendar-check me-2"></i>Programado
                    </button>
                    <button class="btn btn-outline-warning btn_estado_opcion" data-estado="EN_JUEGO">
                        <i class="bi bi-play-circle me-2"></i>En Juego
                    </button>
                    <button class="btn btn-outline-success btn_estado_opcion" data-estado="FINALIZADO">
                        <i class="bi bi-check-circle me-2"></i>Finalizado
                    </button>
                    <button class="btn btn-outline-danger btn_estado_opcion" data-estado="SUSPENDIDO">
                        <i class="bi bi-pause-circle me-2"></i>Suspendido
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para eliminar fixture -->
<div class="modal fade" id="modalEliminarFixture" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Eliminar Fixture</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="eliminar_id_categoria">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>¡Advertencia!</strong> Esta acción eliminará todos los partidos de esta categoría. No se puede deshacer.
                </div>
                <p>¿Está seguro que desea continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn_confirmar_eliminar">
                    <i class="bi bi-trash me-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let categoriaActual = null;
    
    // Cuando cambia el deporte, cargar categorías
    $('#filtro_deporte').change(function() {
        const idDeporte = $(this).val();
        const $selectCategoria = $('#filtro_categoria');
        
        if (!idDeporte) {
            $selectCategoria.prop('disabled', true).html('<option value="">Seleccione un deporte primero</option>');
            $('#btn_generar_fixture').prop('disabled', true);
            return;
        }
        
        $.get('<?= site_url("fixture/get_categorias_por_deporte") ?>/' + idDeporte, function(response) {
            let opciones = '<option value="">Todas las categorías</option>';
            response.forEach(function(cat) {
                const iconoFixture = cat.tiene_fixture ? ' ✅' : '';
                opciones += `<option value="${cat.id_categoria}">${cat.nombre_categoria} (${cat.cantidad_inscriptos} inscriptos)${iconoFixture}</option>`;
            });
            $selectCategoria.html(opciones).prop('disabled', false);
        }, 'json');
    });
    
    // Cargar fixture
    $('#btn_cargar_fixture').click(function() {
        const idCategoria = $('#filtro_categoria').val();
        
        if (!idCategoria) {
            alert('Por favor seleccione una categoría');
            return;
        }
        
        cargarFixture(idCategoria);
    });
    
    // Función para cargar fixture
    function cargarFixture(idCategoria) {
        $.get('<?= site_url("fixture/obtener_fixture") ?>', { id_categoria: idCategoria }, function(response) {
            if (response.success && response.partidos.length > 0) {
                mostrarFixture(response.partidos);
                cargarEstadisticas(idCategoria);
            } else {
                $('#cuerpo_tabla_fixture').html(`
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No hay partidos programados para esta categoría
                        </td>
                    </tr>
                `);
                $('#panel_estadisticas').hide();
            }
        }, 'json');
    }
    
    // Mostrar fixture en tabla
    function mostrarFixture(partidos) {
        let html = '';
        partidos.forEach(function(p) {
            const nombreLocal = p.nombre_equipo_local || p.nombre_participante_local || 'TBD';
            const nombreVisitante = p.nombre_equipo_visitante || p.nombre_participante_visitante || 'TBD';
            
            let badgeEstado = 'bg-secondary';
            if (p.estado === 'EN_JUEGO') badgeEstado = 'bg-warning text-dark';
            if (p.estado === 'FINALIZADO') badgeEstado = 'bg-success';
            if (p.estado === 'SUSPENDIDO') badgeEstado = 'bg-danger';
            
            const resultadoDisplay = (p.resultado_local !== null && p.resultado_visitante !== null) 
                ? `${p.resultado_local} - ${p.resultado_visitante}` 
                : '-';
            
            html += `
                <tr>
                    <td class="ps-4">${formatoFecha(p.fecha_partido)}</td>
                    <td>${formatoHora(p.hora_partido)}</td>
                    <td><span class="badge bg-info">${p.ronda ? 'Ronda ' + p.ronda : ''} ${p.grupo || ''}</span></td>
                    <td>
                        <div class="d-flex flex-column">
                            <span><i class="bi bi-house me-1"></i> ${escapeHtml(nombreLocal)}</span>
                            <span class="text-muted small"><i class="bi bi-car-front me-1"></i> ${escapeHtml(nombreVisitante)}</span>
                        </div>
                    </td>
                    <td>${p.nombre_lugar || 'Por definir'}</td>
                    <td class="fw-bold">${resultadoDisplay}</td>
                    <td><span class="badge ${badgeEstado}">${p.estado}</span></td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-primary btn-cargar-resultado" data-id="${p.id_partido}" data-local="${escapeHtml(nombreLocal)}" data-visitante="${escapeHtml(nombreVisitante)}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning btn-cambiar-estado" data-id="${p.id_partido}" data-estado="${p.estado}">
                            <i class="bi bi-toggle-on"></i>
                        </button>
                        ${p.estado === 'FINALIZADO' ? '' : '<button class="btn btn-sm btn-outline-success btn-marcar-jugando" data-id="' + p.id_partido + '"><i class="bi bi-play-fill"></i></button>'}
                    </td>
                </tr>
            `;
        });
        
        $('#cuerpo_tabla_fixture').html(html);
        $('#lbl_categoria_seleccionada').text($('#filtro_categoria option:selected').text());
    }
    
    // Cargar estadísticas
    function cargarEstadisticas(idCategoria) {
        $.get('<?= site_url("fixture/get_estadisticas") ?>/' + idCategoria, function(response) {
            if (response.success) {
                $('#stat_total_partidos').text(response.datos.total_partidos);
                $('#stat_jugados').text(response.datos.partidos_jugados);
                $('#stat_pendientes').text(response.datos.partidos_pendientes);
                $('#panel_estadisticas').fadeIn();
            }
        }, 'json');
    }
    
    // Abrir modal para generar fixture
    $('#btn_generar_fixture').click(function() {
        const idCategoria = $('#filtro_categoria').val();
        if (!idCategoria) return;
        
        $('#modal_id_categoria').val(idCategoria);
        categoriaActual = idCategoria;
        
        // Cargar competidores
        $.get('<?= site_url("fixture/get_competidores") ?>/' + idCategoria, function(response) {
            if (response.success && response.competidores.length > 0) {
                let html = '';
                response.competidores.forEach(function(c) {
                    const nombre = c.nombre_ute || c.nombre_completo;
                    const delegacion = c.delegacion ? ` (${c.delegacion})` : '';
                    const integrantes = c.cantidad_integrantes ? ` - ${c.cantidad_integrantes} jugadores` : '';
                    html += `<div class="list-group-item"><i class="bi bi-person-check me-2"></i> ${escapeHtml(nombre)}${delegacion}${integrantes}</div>`;
                });
                $('#lista_competidores').html(html);
            } else {
                $('#lista_competidores').html('<div class="alert alert-warning">No hay competidores inscriptos</div>');
            }
        }, 'json');
    });
    
    // Confirmar generación de fixture
    $('#btn_confirmar_generar').click(function() {
        const idCategoria = $('#modal_id_categoria').val();
        const tipoFixture = $('#tipo_fixture').val();
        
        $.post('<?= site_url("fixture/generar_fixture") ?>', {
            id_categoria: idCategoria,
            tipo_generacion: tipoFixture
        }, function(response) {
            if (response.success) {
                alert(response.message + ' - Se generaron ' + response.cantidad_partidos + ' partidos');
                $('#modalGenerarFixture').modal('hide');
                cargarFixture(idCategoria);
            } else {
                alert(response.message);
            }
        }, 'json');
    });
    
    // Cargar modal de resultado
    $(document).on('click', '.btn-cargar-resultado', function() {
        const idPartido = $(this).data('id');
        const local = $(this).data('local');
        const visitante = $(this).data('visitante');
        
        $('#resultado_id_partido').val(idPartido);
        $('#lbl_local').text(local.substring(0, 20));
        $('#lbl_visitante').text(visitante.substring(0, 20));
        $('#resultado_local').val(0);
        $('#resultado_visitante').val(0);
        
        $('#modalResultado').modal('show');
    });
    
    // Guardar resultado
    $('#btn_guardar_resultado').click(function() {
        const idPartido = $('#resultado_id_partido').val();
        const resultadoLocal = $('#resultado_local').val();
        const resultadoVisitante = $('#resultado_visitante').val();
        
        $.post('<?= site_url("fixture/actualizar_resultado") ?>', {
            id_partido: idPartido,
            resultado_local: resultadoLocal,
            resultado_visitante: resultadoVisitante
        }, function(response) {
            if (response.success) {
                alert(response.message);
                $('#modalResultado').modal('hide');
                cargarFixture(categoriaActual);
            } else {
                alert(response.message);
            }
        }, 'json');
    });
    
    // Cambiar estado
    $(document).on('click', '.btn-cambiar-estado', function() {
        const idPartido = $(this).data('id');
        $('#estado_id_partido').val(idPartido);
        $('#modalEstado').modal('show');
    });
    
    $('.btn_estado_opcion').click(function() {
        const idPartido = $('#estado_id_partido').val();
        const estado = $(this).data('estado');
        
        $.post('<?= site_url("fixture/cambiar_estado_partido") ?>', {
            id_partido: idPartido,
            estado: estado
        }, function(response) {
            if (response.success) {
                $('#modalEstado').modal('hide');
                cargarFixture(categoriaActual);
            } else {
                alert(response.message);
            }
        }, 'json');
    });
    
    // Marcar como "En Juego"
    $(document).on('click', '.btn-marcar-jugando', function() {
        const idPartido = $(this).data('id');
        $.post('<?= site_url("fixture/cambiar_estado_partido") ?>', {
            id_partido: idPartido,
            estado: 'EN_JUEGO'
        }, function(response) {
            if (response.success) {
                cargarFixture(categoriaActual);
            }
        }, 'json');
    });
    
    // Eliminar fixture
    function abrirModalEliminar(idCategoria) {
        $('#eliminar_id_categoria').val(idCategoria);
        $('#modalEliminarFixture').modal('show');
    }
    
    $('#btn_confirmar_eliminar').click(function() {
        const idCategoria = $('#eliminar_id_categoria').val();
        
        $.post('<?= site_url("fixture/eliminar_fixture") ?>', {
            id_categoria: idCategoria
        }, function(response) {
            if (response.success) {
                alert(response.message);
                $('#modalEliminarFixture').modal('hide');
                $('#cuerpo_tabla_fixture').html(`
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                            Fixture eliminado correctamente
                        </td>
                    </tr>
                `);
                $('#panel_estadisticas').hide();
            } else {
                alert(response.message);
            }
        }, 'json');
    });
    
    // Habilitar botón generar cuando hay categoría
    $('#filtro_categoria').change(function() {
        const idCategoria = $(this).val();
        $('#btn_generar_fixture').prop('disabled', !idCategoria);
        
        if (idCategoria) {
            categoriaActual = idCategoria;
        }
    });
    
    // Utilidades
    function formatoFecha(fecha) {
        const d = new Date(fecha);
        return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    
    function formatoHora(hora) {
        return hora.substring(0, 5);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
});
</script>
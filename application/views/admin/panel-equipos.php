<div class="card shadow-sm border-0">
    <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="d-flex align-items-center">
            <i class="bi bi-list-check text-warning me-2"></i> 
            <span>Gestion de UTE/Equipos</span>
            <span class="badge bg-secondary ms-2" id="contador-equipos">0 filas</span>
        </div>
        
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearUTE">
                <i class="bi bi-plus-circle me-1"></i> Crear UTE
            </button>
        </div>
    </div>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tabla-utes">
                <thead class="table-light">
                    <tr>
                        <th>Deporte</th>
                        <th>Categoría</th>
                        <th>Nombre UTE</th>
                        <th>Integrantes</th>
                        <th>Fecha Creación</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-tabla-utes">
                    <?php if (empty($utes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No hay UTEs creadas aún
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($utes as $ute): ?>
                        <tr id="ute-fila-<?= $ute['id_ute'] ?>">
                            <td>
                                <span class="fw-semibold"><?= htmlspecialchars($ute['nombre_deporte']) ?></span>
                                <span class="badge bg-<?= $ute['genero'] === 'MASCULINO' ? 'primary' : ($ute['genero'] === 'FEMENINO' ? 'danger' : 'info') ?> ms-1"><?= htmlspecialchars($ute['genero']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($ute['nombre_categoria']) ?></td>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($ute['nombre_ute']) ?></td>
                            <td>
                                <span class="badge bg-success"><?= $ute['cantidad_integrantes'] ?></span> integrantes
                            </td>
                            <td class="text-muted small"><?= date('d/m/Y', strtotime($ute['fecha_creacion'])) ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-info me-1 btn-ver-integrantes" 
                                        data-id-ute="<?= $ute['id_ute'] ?>" 
                                        title="Ver integrantes">
                                    <i class="bi bi-people"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning me-1 btn-agregar-integrante" 
                                        data-id-ute="<?= $ute['id_ute'] ?>" 
                                        data-id-categoria="<?= $ute['id_categoria'] ?>"
                                        title="Agregar integrante">
                                    <i class="bi bi-person-plus"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-eliminar-ute" 
                                        data-id-ute="<?= $ute['id_ute'] ?>" 
                                        data-nombre-ute="<?= htmlspecialchars($ute['nombre_ute']) ?>"
                                        title="Eliminar UTE">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear UTE -->
<div class="modal fade" id="modalCrearUTE" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Crear Nueva UTE/Equipo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-crear-ute">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deporte y Categoría</label>
                        <select class="form-select" id="select-categoria-ute" required>
                            <option value="">Seleccione una categoría...</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id_categoria'] ?>" 
                                    data-deporte="<?= htmlspecialchars($cat['nombre_deporte']) ?>"
                                    data-genero="<?= htmlspecialchars($cat['genero']) ?>">
                                <?= htmlspecialchars($cat['nombre_deporte']) ?> - <?= htmlspecialchars($cat['nombre_categoria']) ?> (<?= htmlspecialchars($cat['genero']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del Equipo/UTE</label>
                        <input type="text" class="form-control" id="nombre-ute-input" 
                               placeholder="Ej: Los Pampas FC" required maxlength="150">
                        <div class="form-text">El nombre debe ser único dentro de la categoría seleccionada.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-ute">
                    <i class="bi bi-check-circle me-1"></i> Crear UTE
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Integrantes -->
<div class="modal fade" id="modalVerIntegrantes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-people me-2"></i>Integrantes de <span id="nombre-ute-modal"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <div>
                        <strong id="info-categoria-modal"></strong>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>DNI</th>
                                <th>Delegación</th>
                                <th>Sexo</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="lista-integrantes">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Integrante -->
<div class="modal fade" id="modalAgregarIntegrante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Agregar Integrante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Seleccione un participante disponible para esta categoría:</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Participantes Disponibles</label>
                    <select class="form-select" id="select-participante-disponible" required>
                        <option value="">Cargando participantes...</option>
                    </select>
                    <input type="hidden" id="id-ute-agregar">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn-agregar-participante">
                    <i class="bi bi-check-circle me-1"></i> Agregar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar contador
    actualizarContadorEquipos();
    
    // ==========================================
    // CREAR UTE
    // ==========================================
    document.getElementById('btn-guardar-ute').addEventListener('click', function() {
        const idCategoria = document.getElementById('select-categoria-ute').value;
        const nombreUte = document.getElementById('nombre-ute-input').value.trim();
        
        if (!idCategoria || !nombreUte) {
            alert('Complete todos los campos');
            return;
        }
        
        fetch('<?= base_url("Inscripciones/ajax_crear_ute") ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id_categoria: idCategoria, nombre_ute: nombreUte})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al crear la UTE');
        });
    });
    
    // ==========================================
    // VER INTEGRANTES
    // ==========================================
    document.querySelectorAll('.btn-ver-integrantes').forEach(btn => {
        btn.addEventListener('click', function() {
            const idUte = this.getAttribute('data-id-ute');
            
            fetch('<?= base_url("Inscripciones/ajax_detalle_ute/") ?>' + idUte)
            .then(response => response.json())
            .then(ute => {
                if (ute && ute.nombre_ute) {
                    document.getElementById('nombre-ute-modal').textContent = ute.nombre_ute;
                    document.getElementById('info-categoria-modal').textContent = 
                        ute.nombre_deporte + ' - ' + ute.nombre_categoria + ' (' + ute.genero + ')';
                    
                    const tbody = document.getElementById('lista-integrantes');
                    tbody.innerHTML = '';
                    
                    if (ute.integrantes && ute.integrantes.length > 0) {
                        ute.integrantes.forEach(int => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="fw-semibold">${int.nombre_completo}</td>
                                <td>${int.dni}</td>
                                <td>${int.delegacion}</td>
                                <td>${int.sexo}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger btn-quitar-integrante" 
                                            data-id-ute="${idUte}" 
                                            data-id-participante="${int.id_participante}"
                                            title="Quitar de la UTE">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Sin integrantes</td></tr>';
                    }
                    
                    new bootstrap.Modal(document.getElementById('modalVerIntegrantes')).show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los integrantes');
            });
        });
    });
    
    // ==========================================
    // QUITAR INTEGRANTE
    // ==========================================
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-quitar-integrante')) {
            const btn = e.target.closest('.btn-quitar-integrante');
            const idUte = btn.getAttribute('data-id-ute');
            const idParticipante = btn.getAttribute('data-id-participante');
            
            if (confirm('¿Está seguro de quitar este participante de la UTE?')) {
                fetch('<?= base_url("Inscripciones/ajax_eliminar_participante_ute") ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id_ute: idUte, id_participante: idParticipante})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar modal o fila
                        btn.closest('tr').remove();
                        actualizarContadorEquipos();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al quitar el participante');
                });
            }
        }
    });
    
    // ==========================================
    // AGREGAR INTEGRANTE
    // ==========================================
    document.querySelectorAll('.btn-agregar-integrante').forEach(btn => {
        btn.addEventListener('click', function() {
            const idUte = this.getAttribute('data-id-ute');
            const idCategoria = this.getAttribute('data-id-categoria');
            
            document.getElementById('id-ute-agregar').value = idUte;
            
            // Cargar participantes disponibles
            const select = document.getElementById('select-participante-disponible');
            select.innerHTML = '<option value="">Cargando...</option>';
            
            fetch('<?= base_url("Inscripciones/ajax_participantes_disponibles/") ?>' + idCategoria)
            .then(response => response.json())
            .then(participantes => {
                select.innerHTML = '<option value="">Seleccione un participante...</option>';
                
                if (participantes && participantes.length > 0) {
                    participantes.forEach(p => {
                        const option = document.createElement('option');
                        option.value = p.id_participante;
                        option.textContent = `${p.nombre_completo} (${p.dni}) - ${p.delegacion}`;
                        select.appendChild(option);
                    });
                } else {
                    select.innerHTML = '<option value="" disabled>No hay participantes disponibles</option>';
                }
                
                new bootstrap.Modal(document.getElementById('modalAgregarIntegrante')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                select.innerHTML = '<option value="" disabled>Error al cargar</option>';
            });
        });
    });
    
    document.getElementById('btn-agregar-participante').addEventListener('click', function() {
        const idUte = document.getElementById('id-ute-agregar').value;
        const idParticipante = document.getElementById('select-participante-disponible').value;
        
        if (!idParticipante) {
            alert('Seleccione un participante');
            return;
        }
        
        fetch('<?= base_url("Inscripciones/ajax_agregar_participante_ute") ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id_ute: idUte, id_participante: idParticipante})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al agregar el participante');
        });
    });
    
    // ==========================================
    // ELIMINAR UTE
    // ==========================================
    document.querySelectorAll('.btn-eliminar-ute').forEach(btn => {
        btn.addEventListener('click', function() {
            const idUte = this.getAttribute('data-id-ute');
            const nombreUte = this.getAttribute('data-nombre-ute');
            
            if (confirm(`¿Está SEGURO que desea eliminar la UTE "${nombreUte}"?\n\nEsta acción eliminará también todos sus integrantes.`)) {
                fetch('<?= base_url("Inscripciones/ajax_eliminar_ute") ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id_ute: idUte})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const fila = document.getElementById('ute-fila-' + idUte);
                        if (fila) {
                            fila.style.transition = 'opacity 0.3s';
                            fila.style.opacity = '0';
                            setTimeout(() => {
                                fila.remove();
                                actualizarContadorEquipos();
                            }, 300);
                        }
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar la UTE');
                });
            }
        });
    });
    
    function actualizarContadorEquipos() {
        const cantidadFilas = document.querySelectorAll('#cuerpo-tabla-utes tr[id^="ute-fila-"]').length;
        document.getElementById('contador-equipos').textContent = cantidadFilas + ' filas';
    }
});
</script>
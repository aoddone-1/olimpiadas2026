<div class="modal fade" id="modalDetalleInscripcion" tabindex="-1" aria-labelledby="modalDetalleInscripcionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalDetalleInscripcionLabel">
                    <i class="bi bi-person-vcard-fill me-2"></i>Detalles de Inscripción Oficial
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle">
                <div class="d-flex align-items-center border-bottom pb-3 mb-4 gap-3">
                    <div class="bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-person text-primary fs-3"></i>
                    </div>
                    <div>
                        <h4 class="text-dark fw-bold mb-0" id="det-nombre"></h4>
                        <span class="badge bg-secondary border mt-1" id="det-rol"></span>
                        <span class="badge bg-danger border mt-1 ms-1" id="det-badge-delegado" style="display:none;">DELEGADO</span>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white">
                            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2"><i class="bi bi-info-circle me-2"></i>Datos Personales</h6>
                            <p class="mb-2"><strong>DNI:</strong> <span class="text-muted" id="det-dni"></span></p>
                            <p class="mb-2"><strong>Sexo:</strong> <span class="text-muted" id="det-sexo"></span></p>
                            <p class="mb-2"><strong>Fecha Nacimiento:</strong> <span class="text-muted" id="det-fnac"></span></p>
                            <p class="mb-2"><strong>Grupo Sanguíneo:</strong> <span class="badge bg-light text-dark border px-2 fw-bold" id="det-gsanguineo"></span></p>
                            <p class="mb-2"><strong>Obra Social:</strong> <span class="text-muted" id="det-osocial"></span></p>
                            <p class="mb-0"><strong>Tipo de Empleado:</strong> <span class="text-muted" id="det-empleado"></span></p>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white">
                            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2"><i class="bi bi-telephone me-2"></i>Contacto y Logística</h6>
                            <p class="mb-2"><strong>Email:</strong> <span class="text-muted" id="det-email"></span></p>
                            <p class="mb-2"><strong>Teléfono:</strong> <span class="text-muted" id="det-telefono"></span></p>
                            <p class="mb-2"><strong>Delegación:</strong> <span class="badge bg-light text-dark border fw-semibold" id="det-delegacion"></span></p>
                            <p class="mb-2"><strong>Contacto Emergencia:</strong> <span class="text-muted" id="det-emergencia"></span></p>
                            <p class="mb-0"><strong>Dieta Especial:</strong> <span class="text-muted fw-semibold" id="det-dieta"></span></p>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                            <h6 class="text-success fw-bold mb-3 border-bottom pb-2"><i class="bi bi-trophy me-2"></i>Disciplinas Deportivas Asignadas</h6>
                            
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">
                                        <tr>
                                            <th style="width: 25%;"><i class="bi bi-flag-fill me-1"></i> Deporte</th>
                                            <th style="width: 25%;"><i class="bi bi-tags-fill me-1"></i> Categoría</th>
                                            <th style="width: 20%;" class="text-center"><i class="bi bi-diagram-3-fill me-1"></i> UTE</th>
                                            <th style="width: 30%;"><i class="bi bi-chat-left-text-fill me-1"></i> Observación / Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody id="det-tabla-deportes-body">
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="text-secondary fw-bold mb-3 border-bottom pb-2" style="font-size: 0.9rem;"><i class="bi bi-building me-2"></i>Logística de Estadía y Registro</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Hotel Asignado:</strong><br><span class="fw-bold text-dark fs-6" id="det-hotel"></span></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Estado del Kit:</strong><br><span id="det-kit"></span></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-0"><strong>Alta de Registro:</strong><br><span class="text-muted small" id="det-finscripcion"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2">
                <button type="button" class="btn btn-secondary rounded-pill btn-sm px-4" data-bs-dismiss="modal">Cerrar Detalle</button>
            </div>
        </div>
    </div>
</div>
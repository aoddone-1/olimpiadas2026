<div class="card shadow-sm border-0">
    <div class="card-header bg-white pt-3 fw-bold text-secondary d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="d-flex align-items-center">
            <i class="bi bi-list-check text-warning me-2"></i> 
            <span>Gestion de UTE/Equipos</span>
            <span class="badge bg-secondary ms-2" id="contador-encuestas"><?= count($listado_encuestas) ?> filas</span>
        </div>
        
        <div class="position-relative" style="max-width: 300px; width: 100%;">
            
        </div>
    </div>
    
    <div class="card-body">
        <div class="alert alert-warning border-warning-subtle d-flex align-items-center my-2" role="alert">
            <i class="bi bi-tools fs-4 me-3 text-warning"></i>
            <div>
                <strong class="d-block text-dark">¡Sección en Construcción!</strong>
                <span class="text-secondary small">Estamos trabajando en el panel de gestión para que puedas administrar y agrupar tus equipos relacionales muy pronto.</span>
            </div>
        </div>

        <div class="table-responsive">
        </div>

    </div>
</div>
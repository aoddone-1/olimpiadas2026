<div class="hero-section text-center">
    <h1 class="h4 fw-bold text-uppercase m-0">Panel General de Monitoreo</h1>
    <p class="lead small m-0 text-white-50"><?= NOMBRE_SITIO; ?> <br/> <?= LUGAR_OLIMPICO; ?></p>
</div>

<div class="nav-menu mb-4 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link <?= (isset($menu_activo) && $menu_activo === 'inscriptos') ? 'active' : ''; ?>" 
                   href="<?= base_url('Inscripciones/login_staff') ?>">
                    <i class="bi bi-people-fill me-1"></i> Inscriptos
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= (isset($menu_activo) && $menu_activo === 'deportes') ? 'active' : ''; ?>" 
                   href="<?= base_url('Inscripciones/gestion_deportes') ?>">
                    <i class="bi bi-trophy-fill me-1"></i> Gestión Deportiva
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= (isset($menu_activo) && $menu_activo === 'sondeo') ? 'active' : ''; ?>" 
                   href="<?= base_url('Inscripciones/monitoreo_encuesta') ?>">
                    <i class="bi bi-bar-chart-line-fill me-1"></i> Sondeo Inicial
                </a>
            </li>
            
            <?php if($this->session->userdata('user_rol') === 'superadmin'){ ?> 
            <li class="nav-item">
                <a class="nav-link <?= (isset($menu_activo) && $menu_activo === 'control') ? 'active' : ''; ?>" 
                   href="<?= base_url('Inscripciones/control_total') ?>">
                    <i class="bi bi-person me-1"></i> Control Total
                </a>
            </li>
            <?php } ?> 
        </ul>
        
        <a href="<?= base_url('Inscripciones/logout_staff') ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">
            <i class="bi bi-box-arrow-right me-1"></i> Salir
        </a>
    </div>
</div>
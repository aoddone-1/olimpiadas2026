<div class="hero-section text-center">
    <h1 class="h4 fw-bold text-uppercase m-0">Panel General de Monitoreo</h1>
    <p class="lead small m-0 text-white-50"><?= NOMBRE_SITIO; ?> <br/> <?= LUGAR_OLIMPICO; ?></p>
</div>

<div class="nav-menu mb-4 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link <?= (isset($menu_activo) && $menu_activo === 'resumen') ? 'active' : ''; ?>" 
                   href="<?= base_url('Inscripciones/login') ?>">
                    <i class="bi bi-bar-chart me-1"></i> Resumen
                </a>
            </li>

            <li class="nav-item dropdown <?= (isset($menu_activo) && $menu_activo === 'deportes') ? 'active' : ''; ?>">
                <a class="nav-link dropdown-toggle"
                   href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-trophy-fill me-1"></i> Gestión Deportiva
                </a>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item"
                           href="<?= base_url('Inscripciones/gestion_deportes') ?>">
                            <i class="bi bi-flag-fill me-2 text-success"></i>Deportes y Categorías
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item "
                           href="<?= base_url('Inscripciones/utes_equipos') ?>">
                            <i class="bi bi-people-fill me-2 text-primary"></i>UTEs / Equipos
                        </a>
                    </li>
                </ul>
            </li>
            
            
            <li class="nav-item">
                <a class="nav-link <?= (isset($menu_activo) && $menu_activo === 'inscriptos') ? 'active' : ''; ?>" 
                   href="<?= base_url('Inscripciones/inscriptos') ?>">
                    <i class="bi bi-person-check-fill me-2"></i> Inscriptos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($menu_activo) && $menu_activo === 'fixture') ? 'active' : ''; ?>" 
                   href="<?= base_url('Inscripciones/fixture') ?>">
                    <i class="bi bi-calendar3 me-2"></i> Fixture
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($menu_activo) && $menu_activo === 'resultados') ? 'active' : ''; ?>" 
                   href="<?= base_url('Inscripciones/resultados') ?>">
                    <i class="bi bi-trophy-fill me-2"></i> Resultados
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($menu_activo) && $menu_activo === 'premiacion') ? 'active' : ''; ?>" 
                   href="<?= base_url('Inscripciones/premiacion') ?>">
                    <i class="bi bi-award-fill me-1"></i> Premiación
                </a>
            </li>
            <?php if ($this->session->userdata('user_rol') === 'superadmin'): ?>
            <li class="vr-nav d-none d-lg-block" aria-hidden="true"></li>
            <li class="nav-item ms-1">
                <a class="nav-link nav-link-usuarios <?= (isset($menu_activo) && $menu_activo === 'usuarios') ? 'active' : ''; ?>"
                   href="<?= base_url('Inscripciones/gestion_usuarios') ?>">
                    <i class="bi bi-person-gear me-2"></i> Usuarios
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <div class="d-flex align-items-center gap-3">
            <?php if ($this->session->userdata('user_nombre')): ?>
            <span class="usuario-logueado badge text-bg-secondary rounded-pill px-3 py-2">
                <i class="bi bi-person-circle me-1"></i>
                <?= html_escape($this->session->userdata('user_nombre')) ?>
                <small class="text-white-50 ms-1">(<?= html_escape($this->session->userdata('user_rol') ?: '') ?>)</small>
            </span>
            <?php endif; ?>
            <a href="<?= base_url('Inscripciones/logout') ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                <i class="bi bi-box-arrow-right me-1"></i> Salir
            </a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
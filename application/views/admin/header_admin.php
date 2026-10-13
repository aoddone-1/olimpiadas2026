<div class="hero-section text-center">
    <h1 class="h4 fw-bold text-uppercase m-0">Panel General de Monitoreo</h1>
    <p class="lead small m-0 text-white-50"><?= NOMBRE_SITIO; ?> <br/> <?= LUGAR_OLIMPICO; ?></p>
</div>

<div class="nav-menu mb-4 shadow-sm">
    <?php
    // "Control Total" fue eliminado del menú: sus paneles ahora son páginas
    // propias (Inscriptos, Fixture, Resultados) y UTEs/Equipos vive dentro de
    // Gestión Deportiva. Normalizamos cualquier valor viejo de la sesión para
    // que los formularios de inscripción vuelvan a la página correcta.
    if ($this->session->userdata('origen_form_inscripcion') === 'control_total') {
        $this->session->set_userdata('origen_form_inscripcion', 'inscriptos');
    }

    $es_superadmin = ($this->session->userdata('user_rol') === 'superadmin');
    $menu = isset($menu_activo) ? $menu_activo : '';
    ?>
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link <?= ($menu === 'resumen') ? 'active' : '' ?>"
                   href="<?= base_url('Inscripciones/login') ?>">
                    <i class="bi bi-bar-chart me-1"></i> Resumen
                </a>
            </li>

            <!-- Gestión Deportiva: deportes/categorías + UTEs/Equipos (submenú) -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle <?= in_array($menu, ['deportes', 'utes']) ? 'active' : '' ?>"
                   href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-trophy-fill me-1"></i> Gestión Deportiva
                </a>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item <?= ($menu === 'deportes') ? 'active fw-bold' : '' ?>"
                           href="<?= base_url('Inscripciones/gestion_deportes') ?>">
                            <i class="bi bi-flag-fill me-2 text-success"></i>Deportes y Categorías
                        </a>
                    </li>
                    <?php if ($es_superadmin) { ?>
                    <li>
                        <a class="dropdown-item <?= ($menu === 'utes') ? 'active fw-bold' : '' ?>"
                           href="<?= base_url('Inscripciones/panel_utes') ?>">
                            <i class="bi bi-people-fill me-2 text-primary"></i>UTEs / Equipos
                        </a>
                    </li>
                    <?php } ?>
                </ul>
            </li>

            <?php if ($es_superadmin) { ?>
            <li class="nav-item">
                <a class="nav-link <?= ($menu === 'inscriptos') ? 'active' : '' ?>"
                   href="<?= base_url('Inscripciones/inscriptos') ?>">
                    <i class="bi bi-person-check-fill me-1"></i> Inscriptos
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= ($menu === 'fixture') ? 'active' : '' ?>"
                   href="<?= base_url('Inscripciones/fixture') ?>">
                    <i class="bi bi-calendar3 me-1"></i> Fixture
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= ($menu === 'resultados') ? 'active' : '' ?>"
                   href="<?= base_url('Inscripciones/resultados') ?>">
                    <i class="bi bi-list-ol me-1"></i> Resultados
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= ($menu === 'premiacion') ? 'active' : '' ?>"
                   href="<?= base_url('Inscripciones/premiacion') ?>">
                    <i class="bi bi-award-fill me-1"></i> Premiación
                </a>
            </li>
            <?php } ?>
        </ul>

        <a href="<?= base_url('Inscripciones/logout') ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">
            <i class="bi bi-box-arrow-right me-1"></i> Salir
        </a>
    </div>
</div>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel General de Monitoreo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== RESET ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f6f9; }

        /* ========== HEADER SUPERIOR ========== */
        .top-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
            color: #fff;
            padding: 18px 30px;
            text-align: center;
            border-bottom: 4px solid #f6ad55;
        }
        .top-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .top-header .subtitle {
            font-size: 0.95rem;
            color: #cbd5e0;
            font-weight: 400;
        }
        .top-header .location {
            font-size: 0.85rem;
            color: #a0aec0;
            margin-top: 2px;
        }
        .top-header .location i { margin-right: 4px; }

        /* ========== NAVBAR ========== */
        .navbar {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Menú izquierdo */
        .nav-menu {
            display: flex;
            align-items: center;
            list-style: none;
            gap: 0;
            flex: 1;
        }
        .nav-menu > li { position: relative; }

        .nav-menu > li > a {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 14px 16px;
            text-decoration: none;
            color: #2d3748;
            font-size: 0.9rem;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .nav-menu > li > a:hover,
        .nav-menu > li.active > a {
            color: #2c5282;
            border-bottom-color: #f6ad55;
            background: #f7fafc;
        }
        .nav-menu > li > a i { font-size: 0.95rem; }

        /* Dropdown */
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: #fff;
            min-width: 220px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border-radius: 0 0 8px 8px;
            list-style: none;
            padding: 6px 0;
            z-index: 1001;
        }
        .nav-menu > li:hover > .dropdown-menu,
        .nav-menu > li.open > .dropdown-menu {
            display: block;
        }
        .dropdown-menu li a {
            display: block;
            padding: 10px 18px;
            color: #2d3748;
            text-decoration: none;
            font-size: 0.88rem;
            transition: background 0.2s;
        }
        .dropdown-menu li a:hover {
            background: #edf2f7;
            color: #2c5282;
        }
        .dropdown-menu li a i {
            width: 20px;
            margin-right: 8px;
            color: #718096;
        }

        /* Sección usuario */
        .nav-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            margin-left: auto;
        }
        .user-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #edf2f7;
            padding: 8px 14px;
            border-radius: 25px;
            font-size: 0.85rem;
            color: #2d3748;
        }
        .user-badge i { color: #2c5282; }
        .user-badge .rol {
            color: #718096;
            font-size: 0.75rem;
        }
        .btn-logout {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: 2px solid #e53e3e;
            color: #e53e3e;
            background: #fff;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background: #e53e3e;
            color: #fff;
        }

        /* Hamburguesa */
        .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #2d3748;
            cursor: pointer;
            padding: 10px;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .top-header h1 { font-size: 1.2rem; }
            .nav-menu > li > a { padding: 12px 10px; font-size: 0.82rem; }
        }

        @media (max-width: 768px) {
            .top-header { padding: 14px 16px; }
            .top-header h1 { font-size: 1rem; }
            .top-header .subtitle { font-size: 0.8rem; }

            .hamburger { display: block; }

            .navbar {
                flex-wrap: nowrap;
                padding: 0 12px;
            }

            .nav-menu {
                display: none;
                flex-direction: column;
                width: 100%;
                background: #fff;
                position: absolute;
                top: 100%;
                left: 0;
                box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                max-height: 80vh;
                overflow-y: auto;
            }
            .nav-menu.show { display: flex; }

            .nav-menu > li > a {
                padding: 14px 18px;
                border-bottom: 1px solid #edf2f7;
                border-left: 3px solid transparent;
            }
            .nav-menu > li > a:hover,
            .nav-menu > li.active > a {
                border-bottom-color: #edf2f7;
                border-left-color: #f6ad55;
            }

            .dropdown-menu {
                position: static;
                box-shadow: none;
                border-radius: 0;
                background: #f7fafc;
                padding-left: 20px;
            }

            .nav-user {
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px 12px;
                border-top: 1px solid #edf2f7;
                margin-left: 0;
            }
            .user-badge { font-size: 0.78rem; padding: 6px 10px; }
        }

        @media (max-width: 480px) {
            .top-header h1 { font-size: 0.9rem; }
            .user-badge span.user-name { display: none; }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="top-header">
    <h1>PANEL GENERAL DE MONITOREO</h1>
    <div class="subtitle">XXXVIII Olimpiadas Nacionales de Empleados de Institutos de Vivienda</div>
    <div class="location"><i class="fas fa-map-marker-alt"></i> La Pampa 2026</div>
</header>

<!-- ===== NAVBAR ===== -->
<nav class="navbar">
    <button class="hamburger" onclick="toggleMenu()" aria-label="Menú">
        <i class="fas fa-bars"></i>
    </button>

    <ul class="nav-menu" id="navMenu">
        <li class="active">
            <a href="<?= base_url('Inscripciones/login') ?>">
                <i class="fas fa-chart-bar"></i> Resumen
            </a>
        </li>

        <!-- Dropdown Gestión Deportiva -->
        <li>
            <a href="#" onclick="toggleDropdown(event, this)">
                <i class="fas fa-trophy"></i> Gestión Deportiva
                <i class="fas fa-chevron-down" style="font-size:0.7rem;margin-left:4px;"></i>
            </a>
            <ul class="dropdown-menu">
                <li><a href="<?= base_url('Inscripciones/gestion_deportes') ?>">
                    <i class="fas fa-futbol"></i> Deportes y Categorías
                </a></li>
                <li><a href="<?= base_url('Inscripciones/utes_equipos') ?>">
                    <i class="fas fa-users"></i> UTEs / Equipos
                </a></li>
            </ul>
        </li>

        <li>
            <a href="<?= base_url('Inscripciones/inscriptos') ?>">
                <i class="fas fa-user-plus"></i> Inscriptos
            </a>
        </li>
        <li>
            <a href="<?= base_url('Inscripciones/fixture') ?>">
                <i class="fas fa-calendar-alt"></i> Fixture
            </a>
        </li>
        <li>
            <a href="<?= base_url('Inscripciones/resultados') ?>">
                <i class="fas fa-medal"></i> Resultados
            </a>
        </li>
        <li>
            <a href="<?= base_url('Inscripciones/premiacion') ?>">
                <i class="fas fa-award"></i> Premiación
            </a>
        </li>

        <?php if ($this->session->userdata('user_rol') === 'superadmin'): ?>
        <li>
            <a href="<?= base_url('Inscripciones/gestion_usuarios') ?>">
                <i class="fas fa-user-shield"></i> Usuarios
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <!-- Usuario y Logout -->
    <div class="nav-user">
        <div class="user-badge">
            <i class="fas fa-user-circle"></i>
            <span class="user-name"><?= htmlspecialchars($this->session->userdata('user_nombre')) ?></span>
            <span class="rol">(<?= htmlspecialchars($this->session->userdata('user_rol') ?: '') ?>)</span>
        </div>
        <a href="<?= base_url('Inscripciones/logout') ?>" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Salir
        </a>
    </div>
</nav>

<script>
    function toggleMenu() {
        document.getElementById('navMenu').classList.toggle('show');
    }

    function toggleDropdown(e, el) {
        e.preventDefault();
        const li = el.parentElement;
        li.classList.toggle('open');
    }

    // Cerrar menú al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-menu')) {
            document.querySelectorAll('.nav-menu > li.open').forEach(li => li.classList.remove('open'));
        }
    });
</script>

</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
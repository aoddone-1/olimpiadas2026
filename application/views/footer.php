<style>
    footer {
        flex-shrink: 0;
        background-color: rgba(255, 255, 255, 0.95);
        /* Un borde superior degradado que combina con el azul de tu Hero */
        border-top: 3px solid transparent;
        border-image: linear-gradient(90deg, #1e3c72 0%, #2a5298 50%, #ffc107 100%) 1;
        width: 100%;
        backdrop-filter: blur(8px); /* Por si lo usás con elementos flotantes */
    }
    
    .footer-container {
        padding: 1.5rem 0;
    }

    .credits {
        color: #495057;
        font-size: 0.88rem;
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    /* Badge sutil para La Pampa destacando tu provincia */
    .badge-pampa {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: #ffffff;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.82rem;
        box-shadow: 0 2px 4px rgba(30, 60, 114, 0.15);
    }

    .copyright {
        color: #6c757d;
        font-size: 0.78rem;
        letter-spacing: 0.2px;
    }

    .footer-divider {
        width: 40px;
        height: 2px;
        background-color: #dee2e6;
        margin: 0.6rem auto;
        border-radius: 2px;
    }
</style>

<footer>
    <div class="container footer-container text-center">
        <div class="credits d-flex align-items-center justify-content-center flex-wrap gap-2 mb-1">
            <span>Sitio desarrollado por la</span>
            <span class="badge-pampa">Delegación de La Pampa 2026</span>
        </div>
        
        <div class="footer-divider"></div>
        
        <div class="copyright d-flex align-items-center justify-content-center gap-1">
            <i class="bi bi-c-circle small"></i>
            <span>2026 Olimpiadas Nacionales de Empleados de Institutos de Vivienda. Todos los derechos reservados.</span>
        </div>
    </div>
</footer>
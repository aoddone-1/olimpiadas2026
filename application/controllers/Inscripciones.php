<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Inscripciones (Inscripcion_controller)
 *
 * Responsabilidad: formulario público de inscripción de participantes,
 * carga/edición administrativa de inscripciones y acreditación por QR.
 *
 * Nota de compatibilidad: el archivo original era un monolito de ~1750 líneas
 * que mezclaba todas las responsabilidades del sistema. Esta clase es ahora un
 * "proxy" que hereda la implementación completa de los nuevos controladores
 * temáticos (Publica, Auth, Admin, Deporte, UTE, Fixture, Resultado, Delegado),
 * para que las URLs existentes `Inscripciones/...` sigan funcionando sin cambios.
 *
 * @see Publica_controller, Auth_controller, Admin_controller, Deporte_controller,
 *      UTE_controller, Fixture_controller, Resultado_controller, Delegado_controller
 */
class Inscripciones extends OLIM_Controller {

    public function __construct() {
        parent::__construct();
    }

    /** Proxy mágico: delega cualquier acción en los controladores temáticos. */
    public function _remap($metodo, $parametros = []) {
        static $mapa = [
            // --- Pública: inscripción pública + consulta QR ---
            'index', 'panel', 'formulario_inscripcion', 'getCategorias',
            'getDeportesPorGenero', 'buscar_por_dni', 'guardar',
            'acreditacion', 'descargar_deslinde',
            // --- Autenticación ---
            'login', 'procesar_login', 'logout', 'login_staff', 'dashboard',
            // --- Administración de inscripciones / acreditación ---
            'control_total', 'detalle_ajax', 'eliminar_inscripcion',
            'eliminar_inscripcion_ajax', 'modificar_inscripcion',
            'guardar_modificacion', 'nueva_inscripcion', 'guardar_nueva_inscripcion',
            'acreditar_kit', 'acreditar_deporte', 'imprimir_credencial',
            'descargar_csv_todos_inscriptos',
            // --- Gestión de deportes, categorías y lugares ---
            'gestion_deportes', 'guardar_categoria', 'eliminar_categoria',
            'editar_categoria', 'guardar_lugar', 'eliminar_lugar', 'editar_lugar',
            'guardar_deporte', 'eliminar_deporte', 'editar_deporte', 'monitoreo_encuesta',
            // --- UTEs / Equipos ---
            'panel_utes', 'ajax_participantes_disponibles', 'ajax_crear_ute',
            'ajax_agregar_participante_ute', 'ajax_eliminar_participante_ute',
            'ajax_eliminar_ute', 'ajax_detalle_ute',
            // --- Fixture (AJAX) ---
            'ajax_fixture_categoria', 'ajax_fixture_todo', 'ajax_generar_fixture',
            'ajax_guardar_partido', 'ajax_resultado_partido', 'ajax_resultado_masivo',
            'ajax_eliminar_fixture', 'ajax_eliminar_partido',
            // --- Resultados (AJAX) ---
            'ajax_guardar_resultado', 'ajax_resultados_todo',
            'ajax_fixtures_por_categoria', 'ajax_competidores_por_categoria',
            'ajax_eliminar_resultado',
            // --- Delegados ---
            'panel_delegado', 'descargar_csv_inscriptos',
        ];

        if (!in_array($metodo, $mapa, TRUE)) {
            show_404();
        }

        $clase  = self::_controlador_para($metodo);
        $instancia = new $clase();

        if (!method_exists($instancia, $metodo) || strpos($metodo, '_') === 0) {
            show_404();
        }

        return call_user_func_array([$instancia, $metodo], $parametros);
    }

    /** Resuelve qué controlador temático implementa una acción. */
    public static function _controlador_para($metodo) {
        $mapa_clases = [
            'Auth_controller' => [
                'login', 'procesar_login', 'logout', 'login_staff', 'dashboard',
            ],
            'Admin_controller' => [
                'control_total', 'detalle_ajax', 'eliminar_inscripcion',
                'eliminar_inscripcion_ajax', 'modificar_inscripcion',
                'guardar_modificacion', 'nueva_inscripcion', 'guardar_nueva_inscripcion',
                'acreditar_kit', 'acreditar_deporte', 'imprimir_credencial',
                'descargar_csv_todos_inscriptos',
            ],
            'Deporte_controller' => [
                'gestion_deportes', 'guardar_categoria', 'eliminar_categoria',
                'editar_categoria', 'guardar_lugar', 'eliminar_lugar', 'editar_lugar',
                'guardar_deporte', 'eliminar_deporte', 'editar_deporte', 'monitoreo_encuesta',
            ],
            'UTE_controller' => [
                'panel_utes', 'ajax_participantes_disponibles', 'ajax_crear_ute',
                'ajax_agregar_participante_ute', 'ajax_eliminar_participante_ute',
                'ajax_eliminar_ute', 'ajax_detalle_ute',
            ],
            'Fixture_controller' => [
                'ajax_fixture_categoria', 'ajax_fixture_todo', 'ajax_generar_fixture',
                'ajax_guardar_partido', 'ajax_resultado_partido', 'ajax_resultado_masivo',
                'ajax_eliminar_fixture', 'ajax_eliminar_partido',
            ],
            'Resultado_controller' => [
                'ajax_guardar_resultado', 'ajax_resultados_todo',
                'ajax_fixtures_por_categoria', 'ajax_competidores_por_categoria',
                'ajax_eliminar_resultado',
            ],
            'Delegado_controller' => [
                'panel_delegado', 'descargar_csv_inscriptos',
            ],
        ];

        foreach ($mapa_clases as $clase => $metodos) {
            if (in_array($metodo, $metodos, TRUE)) {
                return $clase;
            }
        }

        return 'Publica_controller';
    }
}

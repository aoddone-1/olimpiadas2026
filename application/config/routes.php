<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'inscripciones';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/*
| ---------------------------------------------------------------------------
| RUTAS DE COMPATIBILIDAD (reestructuración Sept-2026)
| ---------------------------------------------------------------------------
| El monolito `Inscripciones` se dividió en controladores temáticos
| (Publica, Auth, Admin, Deporte, UTE, Fixture, Resultado, Delegado).
| Para no romper URLs existentes/bookmarks/QR impresos, cualquier request a
| `(Inscripciones|inscripciones)/<accion>` se re-mapea al controlador que hoy
| la implementa.
|
| Notas:
|   - CodeIgniter 3 coincide las rutas contra el segmento URI tal como viene,
|     pero sensible a mayúsculas; por eso el patrón acepta ambas grafías.
|   - Los nombres de clase son `<Tema>_controller` (CI exige que la clase
|     esté en un archivo con el mismo nombre), por eso cada destino es
|     `Tema_controller/<accion>`.
|   - La captura `(/(.*))?` + `$2` evita el bug de CI3 que duplica el prefijo
|     cuando el grupo `(:any)` queda vacío.
*/

$_olim_acciones = array(
    // Inscripción pública y acreditación por QR => Publica
    'index'                     => 'Publica',
    'panel'                     => 'Publica',
    'formulario_inscripcion'    => 'Publica',
    'getCategorias'             => 'Publica',
    'getDeportesPorGenero'      => 'Publica',
    'buscar_por_dni'            => 'Publica',
    'guardar'                   => 'Publica',
    'acreditacion'              => 'Publica',
    'descargar_deslinde'        => 'Publica',
    // Autenticación => Auth
    'login'                     => 'Auth',
    'procesar_login'            => 'Auth',
    'logout'                    => 'Auth',
    'login_staff'               => 'Auth',
    'dashboard'                 => 'Auth',
    // Administración de inscripciones / acreditación / CSV => Admin
    'control_total'                 => 'Admin',
    'detalle_ajax'                  => 'Admin',
    'eliminar_inscripcion'          => 'Admin',
    'eliminar_inscripcion_ajax'     => 'Admin',
    'modificar_inscripcion'         => 'Admin',
    'guardar_modificacion'          => 'Admin',
    'nueva_inscripcion'             => 'Admin',
    'guardar_nueva_inscripcion'     => 'Admin',
    'acreditar_kit'                 => 'Admin',
    'acreditar_deporte'             => 'Admin',
    'imprimir_credencial'           => 'Admin',
    'descargar_csv_todos_inscriptos'=> 'Admin',
    // Deportes, categorías, lugares y sondeo => Deporte
    'gestion_deportes'      => 'Deporte',
    'guardar_categoria'     => 'Deporte',
    'eliminar_categoria'    => 'Deporte',
    'editar_categoria'      => 'Deporte',
    'guardar_lugar'         => 'Deporte',
    'eliminar_lugar'        => 'Deporte',
    'editar_lugar'          => 'Deporte',
    'guardar_deporte'       => 'Deporte',
    'eliminar_deporte'      => 'Deporte',
    'editar_deporte'        => 'Deporte',
    'monitoreo_encuesta'    => 'Deporte',
    // UTEs / Equipos => UTE
    'panel_utes'                        => 'UTE',
    'ajax_participantes_disponibles'    => 'UTE',
    'ajax_crear_ute'                    => 'UTE',
    'ajax_agregar_participante_ute'     => 'UTE',
    'ajax_eliminar_participante_ute'    => 'UTE',
    'ajax_eliminar_ute'                 => 'UTE',
    'ajax_detalle_ute'                  => 'UTE',
    // Fixture => Fixture
    'ajax_fixture_categoria'    => 'Fixture',
    'ajax_fixture_todo'         => 'Fixture',
    'ajax_generar_fixture'      => 'Fixture',
    'ajax_guardar_partido'      => 'Fixture',
    'ajax_resultado_partido'    => 'Fixture',
    'ajax_resultado_masivo'     => 'Fixture',
    'ajax_eliminar_fixture'     => 'Fixture',
    'ajax_eliminar_partido'     => 'Fixture',
    // Resultados => Resultado
    'ajax_guardar_resultado'            => 'Resultado',
    'ajax_resultados_todo'              => 'Resultado',
    'ajax_fixtures_por_categoria'       => 'Resultado',
    'ajax_competidores_por_categoria'   => 'Resultado',
    'ajax_eliminar_resultado'           => 'Resultado',
    // Delegados => Delegado
    'panel_delegado'            => 'Delegado',
    'descargar_csv_inscriptos'  => 'Delegado',
);

foreach ($_olim_acciones as $_r => $_c) {
    $route['(?i)inscripciones/'.$_r.'(/(.*))?'] = $_c.'_controller/'.$_r.'$2';
}
unset($_olim_acciones, $_r, $_c);
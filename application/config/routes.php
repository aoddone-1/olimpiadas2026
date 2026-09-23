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
$route['default_controller'] = 'Publica/index';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

| ---------------------------------------------------------------------------
| RUTAS DE COMPATIBILIDAD (reestructuración Sept-2026)
| ---------------------------------------------------------------------------
| El monolito `Inscripciones` se dividió en controladores temáticos
| (Publica, Auth, Admin, Deporte, UTE, Fixture, Resultado, Delegado).
| Para no romper URLs existentes/bookmarks/QR impresos, cualquier request a
| `Inscripciones/<accion>` se re-mapea al controlador que hoy la implementa.
*/

// Inscripción pública y acreditación por QR
foreach (array('index','panel','formulario_inscripcion','getCategorias','getDeportesPorGenero','buscar_por_dni','guardar','acreditacion','descargar_deslinde') as $r) {
    $route['inscripciones/'.$r.'(/.*)?'] = 'Publica/'.$r.'$1';
}
// Autenticación
foreach (array('login','procesar_login','logout','login_staff','dashboard') as $r) {
    $route['inscripciones/'.$r.'(/.*)?'] = 'Auth/'.$r.'$1';
}
// Administración de inscripciones / acreditación / CSV
foreach (array('control_total','detalle_ajax','eliminar_inscripcion','eliminar_inscripcion_ajax','modificar_inscripcion','guardar_modificacion','nueva_inscripcion','guardar_nueva_inscripcion','acreditar_kit','acreditar_deporte','imprimir_credencial','descargar_csv_todos_inscriptos') as $r) {
    $route['inscripciones/'.$r.'(/.*)?'] = 'Admin/'.$r.'$1';
}
// Deportes, categorías, lugares y sondeo
foreach (array('gestion_deportes','guardar_categoria','eliminar_categoria','editar_categoria','guardar_lugar','eliminar_lugar','editar_lugar','guardar_deporte','eliminar_deporte','editar_deporte','monitoreo_encuesta') as $r) {
    $route['inscripciones/'.$r.'(/.*)?'] = 'Deporte/'.$r.'$1';
}
// UTEs / Equipos
foreach (array('panel_utes','ajax_participantes_disponibles','ajax_crear_ute','ajax_agregar_participante_ute','ajax_eliminar_participante_ute','ajax_eliminar_ute','ajax_detalle_ute') as $r) {
    $route['inscripciones/'.$r.'(/.*)?'] = 'UTE/'.$r.'$1';
}
// Fixture
foreach (array('ajax_fixture_categoria','ajax_fixture_todo','ajax_generar_fixture','ajax_guardar_partido','ajax_resultado_partido','ajax_resultado_masivo','ajax_eliminar_fixture','ajax_eliminar_partido') as $r) {
    $route['inscripciones/'.$r.'(/.*)?'] = 'Fixture/'.$r.'$1';
}
// Resultados
foreach (array('ajax_guardar_resultado','ajax_resultados_todo','ajax_fixtures_por_categoria','ajax_competidores_por_categoria','ajax_eliminar_resultado') as $r) {
    $route['inscripciones/'.$r.'(/.*)?'] = 'Resultado/'.$r.'$1';
}
// Delegados
foreach (array('panel_delegado','descargar_csv_inscriptos') as $r) {
    $route['inscripciones/'.$r.'(/.*)?'] = 'Delegado/'.$r.'$1';
}
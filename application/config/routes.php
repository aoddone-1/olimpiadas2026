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

// En CodeIgniter 3 se usa el array $route
$route['inscripciones/getCategorias/(:num)'] = 'Inscripciones/getCategorias/$1';

/*
| -------------------------------------------------------------------------
| Fachada del monolito → controladores modulares (paso 3: arquitectura)
| -------------------------------------------------------------------------
| Las vistas y enlaces históricos usan `Inscripciones/...`; estos re-mapeos
| redirigen cada acción al controller nuevo que la implementa, sin romper
| ninguna URL existente. Cualquier método que NO esté listado sigue
| resolviéndose de forma nativa contra Inscripciones.php (fallback).
*/

// Pública (formulario, guardado, QR)
// IMPORTANTE: CI3 coincide contra la URI en MINÚSCULAS, por lo que todas las
// claves deben escribirse en minúscula aunque los enlaces usen `Inscripciones/...`.
// Además se declaran BOTH variantes (:num y :any) porque CI3 prueba las rutas
// en orden de definición: si `(:num)` no matchea, la URI NO cae en `(:any)`.
$route['default_controller']                    = 'Publica/panel';
$route['inscripciones']                         = 'Publica/panel';
$route['inscripciones/guardar']                 = 'Publica/guardar';
$route['inscripciones/getcategorias/(:num)']    = 'Publica/categorias/$1';
$route['inscripciones/acreditacion/(:num)']     = 'Publica/acreditacion/$1';
$route['inscripciones/acreditacion/(:any)']     = 'Publica/acreditacion/$1';
$route['inscripciones/descargar_deslinde/(:num)']   = 'Publica/deslinde/$1';
$route['inscripciones/descargar_deslinde/(:any)']   = 'Publica/deslinde/$1';
// Alias históricos del formulario público
$route['inscripciones/panel']                   = 'Publica/panel';
$route['inscripciones/formulario_inscripcion']  = 'Publica/panel';
$route['inscripciones/buscar_por_dni']          = 'Publica/buscar_por_dni';
$route['inscripciones/getdeportesporsexo/(:any)'] = 'Publica/deportes_por_sexo/$1';

// Auth
$route['inscripciones/login']          = 'Auth/login';
$route['inscripciones/login_staff']    = 'Auth/staff';
$route['inscripciones/procesar_login'] = 'Auth/procesar';
$route['inscripciones/logout']         = 'Auth/logout';
$route['inscripciones/dashboard']      = 'Auth/dashboard';

// Backend (gestión staff)
foreach ([
    'nueva_inscripcion', 'guardar_nueva_inscripcion', 'modificar_inscripcion',
    'guardar_modificacion', 'eliminar_inscripcion', 'eliminar_inscripcion_ajax',
    'detalle_ajax', 'control_total', 'acreditar_kit', 'acreditar_deporte',
    'imprimir_credencial', 'gestion_deportes', 'guardar_categoria',
    'eliminar_categoria', 'editar_categoria', 'guardar_lugar', 'eliminar_lugar',
    'editar_lugar', 'guardar_deporte', 'eliminar_deporte', 'editar_deporte',
    'monitoreo_encuesta', 'panel_utes', 'ajax_participantes_disponibles',
    'ajax_crear_ute', 'ajax_agregar_participante_ute',
    'ajax_eliminar_participante_ute', 'ajax_eliminar_ute', 'ajax_detalle_ute',
] as $m) {
    $route['inscripciones/' . $m] = 'Backend/' . $m;
    $route['inscripciones/' . $m . '/(:any)'] = 'Backend/' . $m . '/$1';
}

// Fixtures
foreach ([
    'ajax_fixture_categoria', 'ajax_fixture_todo', 'ajax_generar_fixture',
    'ajax_guardar_partido', 'ajax_resultado_partido', 'ajax_resultado_masivo',
    'ajax_eliminar_fixture', 'ajax_eliminar_partido',
    'ajax_categorias_por_deporte',
] as $m) {
    $route['inscripciones/' . $m] = 'Fixtures/' . str_replace('ajax_fixture_', 'ajax_', $m);
    $route['inscripciones/' . $m . '/(:any)'] = 'Fixtures/' . str_replace('ajax_fixture_', 'ajax_', $m) . '/$1';
}

// Resultados
foreach ([
    'ajax_guardar_resultado', 'ajax_resultados_todo',
    'ajax_fixtures_por_categoria', 'ajax_competidores_por_categoria',
    'ajax_eliminar_resultado',
] as $m) {
    $map = [
        'ajax_guardar_resultado'         => 'ajax_guardar',
        'ajax_resultados_todo'           => 'ajax_todo',
        'ajax_eliminar_resultado'        => 'ajax_eliminar',
        'ajax_fixtures_por_categoria'    => 'ajax_fixtures_por_categoria',
        'ajax_competidores_por_categoria'=> 'ajax_competidores_por_categoria',
    ];
    $route['inscripciones/' . $m] = 'Resultados/' . $map[$m];
    $route['inscripciones/' . $m . '/(:any)'] = 'Resultados/' . $map[$m] . '/$1';
}

// Delegado
$route['inscripciones/panel_delegado']              = 'Delegado/panel';
$route['inscripciones/descargar_csv_inscriptos']    = 'Delegado/csv_inscriptos';
$route['inscripciones/descargar_csv_todos_inscriptos'] = 'Delegado/csv_todos';

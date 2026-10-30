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
$route['inscripciones/guardar'] = 'Inscripciones/guardar';

// ---- Panel PÚBLICO de las olimpíadas (sin login) ----
// URL amigable: /publico  y  /panel-publico  ->  Publico::index
$route['publico']                          = 'Publico/index';
$route['panel-publico']                    = 'Publico/index';

// Filtros "limpios" por segmentos, sin query string. Ejemplos:
//   /publico/delegacion/formosa
//   /publico/deporte/13/categoria/56/dia/2026-11-04/buscar/futbol
// CI evalúa estas claves como REGEX (van delimitadas por //), por eso el
// carácter "/" dentro del regex debe escribirse escapado: \/.
$route['publico(?:\/(?:delegacion|deporte|categoria|dia|buscar|disciplina)\/[^\/]+){1,6}'] = 'Publico/index';
$route['panel-publico(?:\/(?:delegacion|deporte|categoria|dia|buscar|disciplina)\/[^\/]+){1,6}'] = 'Publico/index';

// Endpoints JSON (se sirven desde la ruta base; ya no se usan desde el panel).
$route['panel-publico/fixture']            = 'Publico/fixture_json';
$route['panel-publico/resultados']         = 'Publico/resultados_json';
$route['panel-publico/medallero']          = 'Publico/medallero_json';
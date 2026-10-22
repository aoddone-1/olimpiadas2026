<?php
error_reporting(E_ALL); ini_set('display_errors','1');
$path = '/workspace/';
define('FILE_SYSTEM_ICASE', FALSE);
define('BASEPATH', $path.'system'.DIRECTORY_SEPARATOR);
define('APPPATH', $path.'application'.DIRECTORY_SEPARATOR);
define('VIEWPATH', APPPATH.'views'.DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require_once BASEPATH.'core/Common.php';

function &get_config() {
    static $c; if ($c!==NULL) return $c;
    require APPPATH.'config/database.php';
    $c['database']=$db['default']; $c['base_url']='http://localhost/'; $c['index_page']='';
    $c['uri_protocol']='REQUEST_URI'; $c['charset']='UTF-8'; $c['enable_hooks']=FALSE;
    $c['subclass_prefix']='MY_'; $c['composer_autoload']=FALSE;
    $c['permitted_uri_chars']='a-z 0-9~%.:_\-'; $c['allow_get_array']=TRUE;
    $c['log_threshold']=0; $c['log_path']=''; $c['proxy_ips']='';
    return $c;
}
function load_controller($f='',$m='_set_filename'){return FALSE;}
function set_status_header($s=500,$t=''){}
function is_cli(){return TRUE;}
function config_item($i){$c=&get_config();return isset($c[$i])?$c[$i]:NULL;}

require_once BASEPATH.'database/DB.php';
$GLOBALS['DB'] = &DB(get_config()['database']);

class CI_Model { public function __construct(){ $this->db = $GLOBALS['DB']; } public function __get($k){ return $GLOBALS['DB']; } }

require APPPATH.'models/Fixture_model.php';
$m = new Fixture_model();

foreach (array('La Plata','Quilmes','Avellaneda','Inexistente') as $del) {
  echo "===== DELEGACION: $del =====\n";
  try {
    $r = $m->fixture_resumido_delegacion($del, null, 40);
    echo "fixture_resumido: ", count($r), " filas\n";
    foreach ($r as $f) echo "  - ", $f['fecha_txt'], ' ', $f['hora'], ' | ', $f['deporte'],'/',$f['categoria'],' | ',$f['lado1'],' vs ',$f['lado2'],' [',$f['estado'],'] marc=',var_export($f['marcador'],true),"\n";
  } catch (Throwable $t) { echo "ERROR resumido: ", get_class($t),': ',$t->getMessage(),"\n"; }
  try {
    $e = $m->equipos_de_delegacion($del);
    echo "equipos: ", count($e), "\n";
    foreach ($e as $eq) echo "  - ", $eq['nombre_ute'],' | ',$eq['nombre_deporte'],'/',$eq['nombre_categoria'],' | deleg:',count($eq['integrantes_delegacion']) ,' tot:',$eq['total_integrantes'],' prox:',count($eq['proximos_partidos']),"\n";
  } catch (Throwable $t) { echo "ERROR equipos: ", get_class($t),': ',$t->getMessage(),"\n"; }
}
echo "\nULTIMA QUERY:\n", $GLOBALS['DB']->last_query(), "\n";

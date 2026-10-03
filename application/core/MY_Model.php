<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Model — base de todos los modelos del proyecto.
 *
 * Motivo de existir: el error "Call to a member function result_array() on bool".
 *
 * En CodeIgniter 3, si una consulta SQL falla (columna/inexistente, modo
 * estricto, GROUP BY incompleto, restos de Query Builder luego de un
 * count_all_results(), etc.) $this->db->get() devuelve FALSE y el encadenar
 * ->result_array() revienta con ese mensaje. Con db_debug=TRUE además la app
 * muere en mitad del request; con db_debug=FALSE el FALSE se propaga recién
 * al usar el resultado, lejos del lugar real del problema.
 *
 * Esta clase soluciona ambas cosas:
 *   1. Intercepta cualquier método de $this->db que devuelva FALSE (falló la
 *      consulta) y lo reemplaza por un resultado vacío "en memoria", para que
 *      ->result_array()/->row_array()/->num_rows() sigan funcionando y la
 *      página nunca explote.
 *   2. Registra en application/logs la consulta exacta que falló y el error
 *      de MySQL, para poder corregir la causa raíz en vez de parcharla.
 *
 * Nota: CI_Model::__get() ya existía en el core pero no es final; al ser
 * padre de todos los modelos, la protección aplica sin tocar ningún otro
 * archivo.
 */
class MY_Model extends CI_Model {

    /** Decorador de $this->db (se crea una sola vez por request). */
    private $__db_guard = NULL;

    /**
     * Devuelve la conexión a la base envuelta en el guardián anti-FALSE.
     * Todo `$this->db` dentro de los modelos pasa por acá.
     */
    public function __get($name) {
        if ($name === 'db' && $this->__db_guard === NULL) {
            $this->__db_guard = new DB_result_guard(parent::__get('db'));
        }
        return $name === 'db' ? $this->__db_guard : parent::__get($name);
    }
}

/**
 * DB_result_guard
 *
 * Proxy sobre el objeto db de CI: delega todo tal cual, pero si un método
 * devuelve FALSE (consulta fallida), loguea el error real y devuelve un
 * resultado vacío compatible (count() devuelve 0, insert/update/delete
 * devuelven FALSE como antes).
 */
class DB_result_guard {

    /** @var CI_DB_driver */
    private $__inner;

    public function __construct($inner) {
        $this->__inner = $inner;
    }

    public function __call($method, $args) {
        $result = call_user_func_array(array($this->__inner, $method), $args);

        // get(), query(), get_where() devuelven FALSE cuando la consulta falló.
        if ($result === FALSE
            && in_array(strtolower($method), array('get', 'query', 'get_where'), TRUE)) {

            $error = method_exists($this->__inner, 'error') ? $this->__inner->error() : array();
            log_message(
                'error',
                '[DB_result_guard] Consulta fallida (' . $method . '()): '
                . (isset($error['message']) ? $error['message'] : 'sin detalle de error')
                . ' | Last query: ' . (property_exists($this->__inner, 'last_query')
                    ? $this->__inner->last_query : '(desconocida)')
                . ' | Revisar: columnas/tablas inexistentes o modo ONLY_FULL_GROUP_BY.'
            );

            // Resetear la Query Builder evita "efectos fantasma": si el fallo
            // dejó clauses armados (select/where huérfanos), el siguiente get()
            // del request también rompería.
            if (method_exists($this->__inner, 'reset_query')) {
                $this->__inner->reset_query();
            }

            return new Empty_db_result();
        }

        return $result;
    }

    /** Propiedad de lectura (table_prefix, last_query, conn_id, etc.). */
    public function __get($name) {
        return $this->__inner->$name;
    }

    /** Propiedad de escritura (keep/select/stricton/off/etc.). */
    public function __set($name, $value) {
        $this->__inner->$name = $value;
    }

    public function __isset($name) {
        return isset($this->__inner->$name);
    }

    /** Métodos especiales usados por foreach/count(): delegar al driver real. */
    public function __call_static() {}

    public function count() {
        return count($this->__inner);
    }
}

/**
 * Empty_db_result
 *
 * Objeto "resultado vacío" que imita la API de CI_DB_result: sirve para que
 * ->result_array(), ->row_array(), ->num_rows(), etc. no rompan cuando la
 * consulta original falló. Es lo mismo que obtendría el modelo si la tabla
 * estuviera vacía.
 */
class Empty_db_result {

    public function result($type = 'object') {
        return array();
    }

    public function result_array() {
        return array();
    }

    public function result_object() {
        return array();
    }

    public function row($index = 0, $type = 'object') {
        return $type === 'array' ? array() : NULL;
    }

    public function row_array() {
        return array();
    }

    public function row_object() {
        return NULL;
    }

    public function first_row($type = 'object') {
        return $type === 'array' ? array() : NULL;
    }

    public function last_row($type = 'object') {
        return $type === 'array' ? array() : NULL;
    }

    public function next_row() {
        return NULL;
    }

    public function previous_row() {
        return NULL;
    }

    public function num_rows() {
        return 0;
    }

    public function num_fields() {
        return 0;
    }

    public function list_fields() {
        return array();
    }

    public function field_data() {
        return array();
    }

    public function free_result() {
        return TRUE;
    }

    public function data_seek($n = 0) {
        return FALSE;
    }
}

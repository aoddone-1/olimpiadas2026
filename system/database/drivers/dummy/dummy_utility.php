<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Dummy_utility extends CI_DB_utility {
    protected function _list_databases(){return array();} protected function _optimize_table($t){return FALSE;}
    protected function _repair_table($t){return FALSE;} protected function _dump_tables($o){return FALSE;}
    protected function _csv_export($t){return FALSE;}
}

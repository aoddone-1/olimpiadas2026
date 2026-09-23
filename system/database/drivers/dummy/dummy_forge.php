<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Dummy_forge extends CI_DB_forge {
    public function create_database($str){return '';} public function drop_database($str){return '';}
    public function add_column($a,$b){return '';} public function drop_column($a,$b){return '';} public function modify_column($a,$b){return '';}
}

<?php
class Categoria_model extends CI_Model {
    public function get_by_deporte($id_deporte) {
        $this->db->where('id_deporte', $id_deporte);
        return $this->db->get('categorias')->result_array();
    }
}
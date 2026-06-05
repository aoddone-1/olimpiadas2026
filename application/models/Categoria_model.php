<?php
class Categoria_model extends CI_Model {
    public function get_by_deporte($id_deporte) {
        $this->db->where('id_deporte', $id_deporte);
        return $this->db->get('categorias')->result_array();
    }

    public function insertar_categoria_desde_post($post_data) {
        // Validación de negocio interna del modelo
        if (empty($post_data['id_deporte']) || empty($post_data['nombre_categoria'])) {
            return FALSE;
        }

        $datos = array(
            'id_deporte'        => intval($post_data['id_deporte']),
            'nombre_categoria'  => trim($post_data['nombre_categoria']),
            'cupo_maximo'       => !empty($post_data['cupo_maximo']) ? intval($post_data['cupo_maximo']) : 0,
            'id_lugar'          => !empty($post_data['id_lugar']) ? intval($post_data['id_lugar']) : NULL,
            'dia_competencia'   => !empty($post_data['dia_competencia']) ? $post_data['dia_competencia'] : NULL,
            'hora_competencia'  => !empty($post_data['hora_competencia']) ? $post_data['hora_competencia'] : NULL
        );

        return $this->db->insert('categorias', $datos);
    }

    public function eliminar_categoria($id_categoria) {
        $this->db->where('id_categoria', $id_categoria);
        return $this->db->delete('categorias'); // Cambiá 'categorias' por el nombre real de tu tabla
    }

    public function actualizar_categoria($id_categoria, $data) {
        $this->db->where('id_categoria', $id_categoria);
        return $this->db->update('categorias', $data);
    }
}
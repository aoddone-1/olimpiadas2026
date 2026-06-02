<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Participante_model extends CI_Model {

    public function insertar_completo($datos_persona, $categorias_ids) {
        $this->db->db_debug = FALSE; 
        $this->db->trans_start();

        $this->db->insert('participantes', $datos_persona);
        $id_participante = $this->db->insert_id();

        if (!empty($categorias_ids)) {
            foreach ($categorias_ids as $id_categoria) {
                if (!empty($id_categoria)) {
                    $this->db->insert('inscripciones_deportivas', [
                        'id_participante' => $id_participante,
                        'id_categoria'    => $id_categoria
                    ]);
                }
            }
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() !== FALSE);
    }

    public function actualizar_completo($id_participante, $datos_persona, $categorias_ids) {
        $this->db->db_debug = FALSE; 
        $this->db->trans_start();

        // Actualizamos los datos personales
        $this->db->where('id_participante', $id_participante);
        $this->db->update('participantes', $datos_persona);

        // Volamos las disciplinas viejas
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('inscripciones_deportivas');

        // Insertamos las nuevas
        if (!empty($categorias_ids)) {
            foreach ($categorias_ids as $id_categoria) {
                if (!empty($id_categoria)) {
                    $this->db->insert('inscripciones_deportivas', [
                        'id_participante' => $id_participante,
                        'id_categoria'    => $id_categoria
                    ]);
                }
            }
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() !== FALSE);
    }

    

    public function obtener_por_token($token) {
        $this->db->where('token_qr', $token);
        $query = $this->db->get('participantes');
        return $query->row_array(); // Nos devuelve los datos del participante en un array
    }

    public function obtener_deportes_inscriptos($id_participante) {
        $this->db->select('inscripciones_deportivas.id_inscripcion, deportes.nombre_deporte, categorias.nombre_categoria, inscripciones_deportivas.asistio');
        $this->db->from('inscripciones_deportivas');
        $this->db->join('categorias', 'inscripciones_deportivas.id_categoria = categorias.id_categoria');
        $this->db->join('deportes', 'categorias.id_deporte = deportes.id_deporte');
        $this->db->where('inscripciones_deportivas.id_participante', $id_participante);
        
        return $this->db->get()->result_array();
    }

    public function marcar_kit_entregado($id_participante, $nuevo_estado = 1) {
        $this->db->where('id_participante', $id_participante);
        $data = [
            'kit_entregado' => $nuevo_estado
        ];
        return $this->db->update('participantes', $data);
    }

    public function marcar_asistencia_deporte($id_inscripcion, $nuevo_estado = 1) {
        $this->db->where('id_inscripcion', $id_inscripcion);
        $data = [
            'asistio'    => $nuevo_estado,
            'fecha_hora' => ($nuevo_estado == 1) ? date('Y-m-d H:i:s') : NULL // Si es 0, limpia la fecha
        ];
        return $this->db->update('inscripciones_deportivas', $data);
    }

    // Trae el listado completo ordenado por el último inscripto
    public function obtener_todos_los_participantes() {
        $this->db->order_by('fecha_inscripcion', 'DESC');
        return $this->db->get('participantes')->result_array();
    }

    // Cuenta de forma veloz cuántos registros tienen kit_entregado = 1
    public function contar_kits_entregados() {
        $this->db->where('kit_entregado', 1);
        return $this->db->count_all_results('participantes');
    }
}
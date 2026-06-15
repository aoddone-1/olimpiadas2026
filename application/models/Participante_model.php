<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Participante_model extends CI_Model {

    public function insertar_completo($data_persona, $deportes_seleccionados) {
        // 1. Iniciamos una transacción para asegurarnos de que se guarde todo o nada
        $this->db->trans_start();

        // 2. Insertamos la información básica de la persona
        $this->db->insert('participantes', $data_persona);
        
        $id_participante = $this->db->insert_id();

        // 3. Insertamos las disciplinas asignadas (si es competidor y tiene filas)
        if (!empty($deportes_seleccionados) && $id_participante) {
            foreach ($deportes_seleccionados as $disc) {
                
                // Estructura idéntica a las columnas reales de tu tabla intermedia
                $data_relacion = [
                    'id_participante' => $id_participante,
                    'id_categoria'    => $disc['id_categoria'], // Mapeo directo a categoría
                    'tiene_ute'       => $disc['tiene_ute'],
                    'necesita_ute'    => $disc['necesita_ute'],
                    'detalle_ute'     => $disc['detalle_ute']
                ];

                $this->db->insert('inscripciones_deportivas', $data_relacion);
            }
        }

        // 4. Completamos la transacción
        $this->db->trans_complete();

        // Si la transacción falló por cualquier motivo, devuelve FALSE
        return $this->db->trans_status();
    }

    public function actualizar_completo($id_participante, $datos_persona, $deportes_seleccionados) {
        // Dejamos que CodeIgniter maneje los errores de forma nativa para las transacciones
        $this->db->trans_start();

        // 1. Actualizamos los datos personales en la tabla 'participantes'
        $this->db->where('id_participante', $id_participante);
        $this->db->update('participantes', $datos_persona);

        // 2. Volamos las disciplinas viejas (si pasa a ser acompañante, la tabla queda limpia)
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('inscripciones_deportivas');

        // 3. Insertamos las nuevas disciplinas con su estructura de UTE correspondiente
        if (!empty($deportes_seleccionados) && $id_participante) {
            foreach ($deportes_seleccionados as $disc) {
                
                $data_relacion = [
                    'id_participante' => $id_participante,
                    'id_categoria'    => $disc['id_categoria'], // Mapeo estructural correcto
                    'tiene_ute'       => $disc['tiene_ute'],
                    'necesita_ute'    => $disc['necesita_ute'],
                    'detalle_ute'     => $disc['detalle_ute']
                ];

                $this->db->insert('inscripciones_deportivas', $data_relacion);
            }
        }

        // 4. Completamos la transacción atómica
        $this->db->trans_complete();
        
        // Retorna TRUE si se ejecutó el update y los inserts sin errores, o FALSE si falló algo
        return $this->db->trans_status();
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
<?php
class Deporte_model extends CI_Model {
    public function get_all() {
        return $this->db->get('deportes')->result_array();
    }

    // 1. Arma la estructura jerárquica: trae los deportes y les inyecta sus categorías con sus lugares
    public function obtener_fixture_completo() {
        // Traemos todos los deportes
        $deportes = $this->db->get('deportes')->result_array();

        // A cada deporte le buscamos sus categorías haciendo un JOIN con lugares
        foreach ($deportes as $key => $d) {
            $this->db->select('c.*, l.nombre as nombre_lugar');
            $this->db->from('categorias c');
            $this->db->join('lugares l', 'l.id = c.id_lugar', 'left');
            $this->db->where('c.id_deporte', $d['id_deporte']);
            
            $deportes[$key]['categorias'] = $this->db->get()->result_array();
        }

        return $deportes;
    }

    // 2. Trae el listado plano de deportes para el select del modal
    public function obtener_todos_los_deportes() {
        $this->db->order_by('nombre_deporte', 'ASC');
        return $this->db->get('deportes')->result_array();
    }

    // 3. Trae el listado plano de predios para el select del modal (mapeando id y nombre)
    public function obtener_todos_los_lugares() {
        $this->db->order_by('nombre', 'ASC');
        return $this->db->get('lugares')->result_array();
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

    /**
     * Recibe el POST del controlador, procesa los datos y hace el INSERT del predio
     */
    public function insertar_lugar_desde_post($post_data) {
        if (empty($post_data['nombre'])) {
            return FALSE;
        }

        $datos = array(
            'nombre'    => trim($post_data['nombre']),
            'direccion' => !empty($post_data['direccion']) ? trim($post_data['direccion']) : NULL
        );

        return $this->db->insert('lugares', $datos);
    }

    /**
     * Guarda las respuestas del sondeo previo incluyendo sexo
     */
    public function guardar_encuesta_anonima($delegacion, $fecha_nacimiento, $sexo, $deportes_array) {
        $this->db->trans_start();

        // Insertamos el registro principal incluyendo el sexo
        $datos_encuesta = array(
            'delegacion'       => trim($delegacion),
            'fecha_nacimiento' => $fecha_nacimiento,
            'sexo'             => $sexo 
        );
        $this->db->insert('encuestas_respuestas', $datos_encuesta);
        
        $id_respuesta = $this->db->insert_id();

        // Guardamos los deportes elegidos
        foreach ($deportes_array as $id_deporte) {
            $datos_deporte = array(
                'id_respuesta' => $id_respuesta,
                'id_deporte'   => intval($id_deporte)
            );
            $this->db->insert('encuestas_deportes', $datos_deporte);
        }

        // --- EL FIX ESTÁ ACÁ: Cambiar complete() por trans_complete() ---
        $this->db->trans_complete(); 
        
        return $this->db->trans_status();
    }

    // Cuenta cuántas encuestas únicas se respondieron
    public function contar_total_encuestas() {
        return $this->db->count_all('encuestas_respuestas');
    }


    // Cuenta cuántas respuestas llegaron agrupadas por Provincia
    public function obtener_respuestas_por_delegacion() {
        $this->db->select('delegacion, COUNT(id_respuesta) as cantidad');
        $this->db->from('encuestas_respuestas');
        $this->db->group_by('delegacion');
        $this->db->order_by('cantidad', 'DESC');
        return $this->db->get()->result_array();
    }

    public function obtener_ranking_deportes_sondeo() {
        $sql = "SELECT 
                    d.nombre_deporte,
                    COUNT(ed.id_respuesta) as total_interesados,
                    
                    -- Conteo por Sexo
                    SUM(CASE WHEN er.sexo = 'Masculino' THEN 1 ELSE 0 END) as masclino,
                    SUM(CASE WHEN er.sexo = 'Femenino' THEN 1 ELSE 0 END) as femenino,
                    SUM(CASE WHEN er.sexo = 'Otro' THEN 1 ELSE 0 END) as otro,
                    
                    -- Conteo por Franja Etaria (Calculando la edad actual en base a nacimiento)
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) < 35 THEN 1 ELSE 0 END) as menos_35,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) BETWEEN 35 AND 45 THEN 1 ELSE 0 END) as entre_35_45,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) > 45 THEN 1 ELSE 0 END) as mayores_45
                    
                FROM encuestas_deportes ed
                INNER JOIN deportes d ON d.id_deporte = ed.id_deporte
                INNER JOIN encuestas_respuestas er ON er.id_respuesta = ed.id_respuesta
                GROUP BY ed.id_deporte
                ORDER BY total_interesados DESC";
                
        return $this->db->query($sql)->result_array();
    }
}
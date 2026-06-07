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

    public function guardar_deporte($post_data){
        if (empty($post_data['nombre_deporte']) || empty($post_data['genero'])) {
            return FALSE;
        }
        $datos = array(
            'nombre_deporte'  => trim($post_data['nombre_deporte']),
            'genero'  => trim($post_data['genero'])
        );

        return $this->db->insert('deportes', $datos);
    }
    public function actualizar_deporte($id_deporte, $data) {
        $this->db->where('id_deporte', $id_deporte);
        return $this->db->update('deportes', $data);
    }

    public function obtener_deportes_por_sexo($sexo_participante) {
        // Pasamos a mayúsculas para que coincida exactamente con el ENUM de la base de datos
        $sexo = strtoupper(trim($sexo_participante));

        // Por defecto, todos ven los deportes MIXTO
        $generos_permitidos = ['MIXTO'];

        // Sumamos el género específico según corresponda
        if ($sexo === 'MASCULINO') {
            $generos_permitidos[] = 'MASCULINO';
        } elseif ($sexo === 'FEMENINO') {
            $generos_permitidos[] = 'FEMENINO';
        }

        // Filtramos usando el array de géneros válidos
        $this->db->where_in('genero', $generos_permitidos);
        $this->db->order_by('nombre_deporte', 'ASC');
        
        return $this->db->get('deportes')->result_array();
    }
    // 3. Trae el listado plano de predios para el select del modal (mapeando id y nombre)
    public function obtener_todos_los_lugares() {
        $this->db->order_by('nombre', 'ASC');
        return $this->db->get('lugares')->result_array();
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
    public function eliminar_lugar($id_lugar) {
        $this->db->where('id', $id_lugar); // Ajustá 'id' si tu clave primaria se llama distinto (ej: id_lugar)
        return $this->db->delete('lugares'); // Cambiá 'lugares' por el nombre real de tu tabla
    }

    public function actualizar_lugar($id_lugar, $data) {
        $this->db->where('id', $id_lugar);
        return $this->db->update('lugares', $data);
    }

    /**
     * Guarda las respuestas del sondeo previo incluyendo sexo
     */
    public function guardar_encuesta_anonima($data) {
        $this->db->query("ALTER TABLE encuestas_respuestas AUTO_INCREMENT = 1");
        $this->db->trans_start();

        // 1. Estructuramos los datos principales mapeando desde el array unificado $data
        $datos_encuesta = array(
            'dni'              => $data['dni'], // Sacá esta línea si tu tabla encuestas_respuestas es 100% anónima y no guarda DNI
            'delegacion'       => $data['delegacion'],
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'sexo'             => $data['sexo'] 
        );
        
        $this->db->insert('encuestas_respuestas', $datos_encuesta);
        
        // Obtenemos el ID generado para esta respuesta
        $id_respuesta = $this->db->insert_id();

        // 2. Guardamos los deportes elegidos iterando el sub-array que viene dentro de $data
        if (!empty($data['deportes_interes']) && is_array($data['deportes_interes'])) {
            foreach ($data['deportes_interes'] as $id_deporte) {
                if (!empty($id_deporte)) {
                    $datos_deporte = array(
                        'id_respuesta' => $id_respuesta,
                        'id_deporte'   => intval($id_deporte)
                    );
                    $this->db->insert('encuestas_deportes', $datos_deporte);
                }
            }
        }

        $this->db->trans_complete(); 
        
         if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return TRUE;
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
                    COUNT(ed.id_respuesta) as votos,
                    
                    -- Conteo por Sexo (Corregido 'masculino')
                    SUM(CASE WHEN er.sexo = 'Masculino' THEN 1 ELSE 0 END) as masculino,
                    SUM(CASE WHEN er.sexo = 'Femenino' THEN 1 ELSE 0 END) as femenino,
                    SUM(CASE WHEN er.sexo = 'Otro' THEN 1 ELSE 0 END) as otro,
                    
                    -- Conteo por Franja Etaria segmentado cada 10 años
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) < 30 THEN 1 ELSE 0 END) as menos_30,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) BETWEEN 30 AND 39 THEN 1 ELSE 0 END) as entre_30_39,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) BETWEEN 40 AND 49 THEN 1 ELSE 0 END) as entre_40_49,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) BETWEEN 50 AND 59 THEN 1 ELSE 0 END) as entre_50_59,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) >= 60 THEN 1 ELSE 0 END) as mayores_60
                    
                FROM encuestas_deportes ed
                INNER JOIN deportes d ON d.id_deporte = ed.id_deporte
                INNER JOIN encuestas_respuestas er ON er.id_respuesta = ed.id_respuesta
                GROUP BY ed.id_deporte, d.nombre_deporte
                ORDER BY votos DESC";
                
        return $this->db->query($sql)->result_array();
    }
}
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

   public function obtener_todas_las_encuestas() {
        $this->db->select('
            er.id_respuesta,
            er.dni,
            er.delegacion,
            er.sexo,
            er.fecha_nacimiento,
            TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) as edad,
            er.creado_el,
            p.nombre_completo as nombre_participante,
            GROUP_CONCAT(d.nombre_deporte SEPARATOR ", ") as deportes_votados
        ', FALSE); // Ponemos FALSE para que CodeIgniter no intente escapar las funciones de MySQL
        
        $this->db->from('encuestas_respuestas er');
        $this->db->join('encuestas_deportes ed', 'ed.id_respuesta = er.id_respuesta', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = ed.id_deporte', 'inner');
        $this->db->join('participantes p', 'p.dni = er.dni', 'left');
        
        $this->db->group_by('er.id_respuesta');
        $this->db->order_by('er.creado_el', 'ASC');
        
        return $this->db->get()->result_array();
    }
    public function obtener_todas_las_inscripciones() {
        $this->db->select("
            p.id_participante,
            p.nombre_completo,
            p.dni,
            p.delegacion,
            p.hotel_alojamiento,
            p.kit_entregado,
            p.es_competidor, 
            p.es_delegado,
            GROUP_CONCAT(d.nombre_deporte SEPARATOR ', ') as deportes_nombres,
            GROUP_CONCAT(c.nombre_categoria SEPARATOR ', ') as categorias_nombres
        ", FALSE); // Ponemos FALSE para que CodeIgniter no rompa los alias del GROUP_CONCAT
        
        $this->db->from('participantes p');
        // LEFT JOIN secuenciales para no perder a los acompañantes (es_competidor = 0)
        $this->db->join('inscripciones_deportivas id', 'id.id_participante = p.id_participante', 'left');
        $this->db->join('categorias c', 'c.id_categoria = id.id_categoria', 'left');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
        
        // Agrupamos por el ID único del participante para tener una sola fila por persona
        $this->db->group_by('p.id_participante');
        
        // Ordenamos por los últimos registrados
        $this->db->order_by('p.id_participante', 'DESC');
        
        return $this->db->get()->result_array();
    }
    public function borrar_encuesta($id_respuesta) {
        // Al tener ON DELETE CASCADE en la base de datos para encuestas_deportes,
        // borrar el registro principal va a limpiar la tabla intermedia automáticamente.
        $this->db->where('id_respuesta', $id_respuesta);
        return $this->db->delete('encuestas_respuestas');
    }
    public function borrar_inscripcion($id_inscripcion) {
        $this->db->where('id_inscripcion', $id_inscripcion);
        return $this->db->delete('inscripciones_deportivas');
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
            d.id_deporte,
            d.nombre_deporte,
            er.sexo,
            
            -- Total de votos del deporte (Usamos una subconsulta para mantener el número global del deporte)
            (SELECT COUNT(*) FROM encuestas_deportes sub_ed WHERE sub_ed.id_deporte = ed.id_deporte) as votos_totales,
            
            -- Votos específicos de este sexo para este deporte
            COUNT(ed.id_respuesta) as votos_sexo,
            
            -- Segmentación cruzada: EDAD filtrada por el SEXO de la fila actual
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) < 30 THEN 1 ELSE 0 END) as menos_30,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) BETWEEN 30 AND 34 THEN 1 ELSE 0 END) as entre_30_34,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) BETWEEN 35 AND 39 THEN 1 ELSE 0 END) as entre_35_39,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) BETWEEN 40 AND 44 THEN 1 ELSE 0 END) as entre_40_44,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) BETWEEN 45 AND 49 THEN 1 ELSE 0 END) as entre_45_49,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) BETWEEN 50 AND 54 THEN 1 ELSE 0 END) as entre_50_54,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) BETWEEN 55 AND 59 THEN 1 ELSE 0 END) as entre_55_59,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, er.fecha_nacimiento, CURDATE()) >= 60 THEN 1 ELSE 0 END) as mayores_60
            
        FROM encuestas_deportes ed
        INNER JOIN deportes d ON d.id_deporte = ed.id_deporte
        INNER JOIN encuestas_respuestas er ON er.id_respuesta = ed.id_respuesta
        
        -- Clave: Agrupamos por deporte y también por sexo
        GROUP BY ed.id_deporte, d.nombre_deporte, er.sexo
        
            -- Ordenamos para que los mismos deportes queden siempre juntos (Masculino, Femenino, Otro)
            ORDER BY votos_totales DESC, ed.id_deporte ASC, 
                    FIELD(er.sexo, 'Masculino', 'Femenino', 'Otro') ASC";
                
        return $this->db->query($sql)->result_array();
    }
}
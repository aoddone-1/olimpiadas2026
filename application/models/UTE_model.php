<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UTE_model extends CI_Model {

    /**
     * Obtener todas las UTEs con información de categoría y deporte
     */
    public function obtener_todas_las_utes() {
        $this->db->select('
            u.id_ute,
            u.nombre_ute,
            u.genero,
            u.fecha_creacion,
            c.id_categoria,
            c.nombre_categoria,
            d.id_deporte,
            d.nombre_deporte,
            d.genero AS genero_categoria,
            COUNT(pu.id_participante) as cantidad_integrantes
        ', FALSE);
        
        $this->db->from('utes u');
        $this->db->join('categorias c', 'c.id_categoria = u.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->join('participantes_utes pu', 'pu.id_ute = u.id_ute', 'left');
        
        $this->db->group_by('u.id_ute');
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria, u.nombre_ute', 'ASC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Obtener una UTE específica con sus integrantes
     */
    public function obtener_ute_con_integrantes($id_ute) {
        // Datos de la UTE
        $this->db->select('
            u.id_ute,
            u.nombre_ute,
            u.genero,
            u.id_categoria,
            c.nombre_categoria,
            d.id_deporte,
            d.nombre_deporte,
            d.genero AS genero_categoria
        ');
        $this->db->from('utes u');
        $this->db->join('categorias c', 'c.id_categoria = u.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->where('u.id_ute', $id_ute);
        
        $ute = $this->db->get()->row_array();
        
        if ($ute) {
            // Integrantes de la UTE
            $this->db->select('
                p.id_participante,
                p.nombre_completo,
                p.dni,
                p.delegacion,
                p.sexo,
                pu.fecha_asociacion
            ');
            $this->db->from('participantes_utes pu');
            $this->db->join('participantes p', 'p.id_participante = pu.id_participante', 'inner');
            $this->db->where('pu.id_ute', $id_ute);
            $this->db->order_by('p.nombre_completo', 'ASC');
            
            $ute['integrantes'] = $this->db->get()->result_array();
        }
        
        return $ute;
    }

    /**
     * Obtener las UTEs/Equipos en los que participan integrantes de una
     * delegación dada. Se usa en el panel del delegado para que pueda ver
     * los equipos de su delegación sin acceder a la pantalla de gestión.
     * Devuelve cada UTE con deporte, categoría, género, cantidad total de
     * integrantes y los integrantes que son de ESA delegación.
     */
    public function obtener_utes_de_delegacion($delegacion) {
        if ($delegacion === NULL || trim((string) $delegacion) === '') {
            return array();
        }

        // 1. UTEs de la delegación: por integrantes registrados en
        // participantes_utes O por inscripciones que referencian el nombre
        // de la UTE en detalle_ute (fallback, mismo criterio que Fixture_model).
        $this->db->select('
            u.id_ute,
            u.nombre_ute,
            u.genero,
            c.id_categoria,
            c.nombre_categoria,
            d.id_deporte,
            d.nombre_deporte
        ', FALSE);
        $this->db->distinct();
        $this->db->from('utes u');
        $this->db->join('categorias c', 'c.id_categoria = u.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->join('participantes_utes pu', 'pu.id_ute = u.id_ute', 'left');
        $this->db->join('participantes pd', 'pd.id_participante = pu.id_participante', 'left');
        $this->db->join('inscripciones_deportivas i', 'i.id_categoria = u.id_categoria AND TRIM(UPPER(i.detalle_ute)) = TRIM(UPPER(u.nombre_ute))', 'left');
        $this->db->join('participantes pi', 'pi.id_participante = i.id_participante', 'left');
        $this->db->group_start();
        $this->db->where('pd.delegacion', $delegacion);
        $this->db->or_where('pi.delegacion', $delegacion);
        $this->db->group_end();
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria, u.nombre_ute', 'ASC');

        $utes = $this->db->get()->result_array();

        // 2. Completar totales e integrantes propios de la delegación
        foreach ($utes as &$ute) {
            // Total de integrantes de la UTE (todas las delegaciones)
            $this->db->where('id_ute', $ute['id_ute']);
            $ute['cantidad_integrantes'] = (int) $this->db->count_all_results('participantes_utes');

            // Integrantes de la delegación del delegado: primero los
            // registrados en participantes_utes; si no hay ninguno, se usa
            // el fallback por detalle_ute (compatibilidad con datos viejos).
            $this->db->select('p.id_participante, p.nombre_completo, p.dni');
            $this->db->from('participantes_utes pu');
            $this->db->join('participantes p', 'p.id_participante = pu.id_participante', 'inner');
            $this->db->where('pu.id_ute', $ute['id_ute']);
            $this->db->where('p.delegacion', $delegacion);
            $this->db->order_by('p.nombre_completo', 'ASC');
            $integrantes = $this->db->get()->result_array();

            if (empty($integrantes)) {
                $this->db->select('p.id_participante, p.nombre_completo, p.dni');
                $this->db->distinct();
                $this->db->from('inscripciones_deportivas i');
                $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
                $this->db->where('i.id_categoria', $ute['id_categoria']);
                $this->db->where('TRIM(UPPER(i.detalle_ute))', strtoupper(trim($ute['nombre_ute'])));
                $this->db->where('p.delegacion', $delegacion);
                $this->db->order_by('p.nombre_completo', 'ASC');
                $integrantes = $this->db->get()->result_array();
            }

            if ((int) $ute['cantidad_integrantes'] === 0) {
                $ute['cantidad_integrantes'] = count($integrantes);
            }
            $ute['integrantes_delegacion'] = $integrantes;
        }
        unset($ute);

        return $utes;
    }

    /**
     * Obtener las UTEs/Equipos en los que está inscripto un participante
     * concreto, con su deporte y categoría. Se usa para mostrar el equipo de
     * cada competidor en el modal de detalle de inscripción.
     */
    public function obtener_utes_de_participante($id_participante) {
        $this->db->select('
            u.id_ute,
            u.nombre_ute,
            u.genero,
            u.id_categoria,
            c.nombre_categoria,
            d.nombre_deporte
        ', FALSE);
        $this->db->from('participantes_utes pu');
        $this->db->join('utes u', 'u.id_ute = pu.id_ute', 'inner');
        $this->db->join('categorias c', 'c.id_categoria = u.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->where('pu.id_participante', $id_participante);
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Obtener las UTEs/Equipos de un participante resueltas por sus
     * inscripciones (inscripciones_deportivas.id_ute). Cubre el caso de los
     * equipos cargados desde el formulario de inscripción, donde la relación
     * se guarda en la inscripción y no en participantes_utes.
     */
    public function obtener_utes_por_inscripciones($id_participante) {
        $this->db->select('
            u.id_ute,
            u.nombre_ute,
            u.genero,
            u.id_categoria,
            c.nombre_categoria,
            d.nombre_deporte
        ', FALSE);
        $this->db->distinct();
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('utes u', 'u.id_ute = i.id_ute', 'inner');
        $this->db->join('categorias c', 'c.id_categoria = u.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->where('i.id_participante', $id_participante);
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Obtener participantes disponibles para una categoría específica
     * (los que están inscriptos en esa categoría pero no tienen UTE aún)
     */
    public function obtener_participantes_disponibles($id_categoria) {
        // Primero obtenemos los IDs de participantes que YA tienen UTE en esta categoría
        $this->db->select('pu.id_participante');
        $this->db->from('participantes_utes pu');
        $this->db->join('utes u', 'u.id_ute = pu.id_ute', 'inner');
        $this->db->where('u.id_categoria', $id_categoria);
        $con_ute = $this->db->get()->result_array();
        
        // Convertimos a array de IDs
        $ids_con_ute = array_column($con_ute, 'id_participante');
        
        // Ahora obtenemos los participantes inscriptos en la categoría
        $this->db->select('
            p.id_participante,
            p.nombre_completo,
            p.dni,
            p.delegacion,
            p.sexo
        ');
        $this->db->from('inscripciones_deportivas id');
        $this->db->join('participantes p', 'p.id_participante = id.id_participante', 'inner');
        $this->db->where('id.id_categoria', $id_categoria);
        
        // Si hay participantes con UTE, los excluimos
        if (!empty($ids_con_ute)) {
            $this->db->where_not_in('p.id_participante', $ids_con_ute);
        }
        
        $this->db->order_by('p.nombre_completo', 'ASC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Insertar una nueva UTE
     */
    public function insertar_ute($nombre_ute, $id_categoria, $genero = null) {
        $datos = array(
            'nombre_ute' => trim($nombre_ute),
            'id_categoria' => $id_categoria
        );

        // Si se envía un género válido, se guarda; si no, la BD usa el default
        $generos_validos = array('FEMENINO', 'MASCULINO', 'MIXTO');
        if ($genero !== null && in_array(strtoupper(trim($genero)), $generos_validos, true)) {
            $datos['genero'] = strtoupper(trim($genero));
        }

        $this->db->insert('utes', $datos);
        return $this->db->insert_id();
    }

    /**
     * Actualizar datos de una UTE (nombre y/o género)
     */
    public function actualizar_ute($id_ute, $nombre_ute = null, $genero = null) {
        $datos = array();

        if ($nombre_ute !== null && trim($nombre_ute) !== '') {
            $datos['nombre_ute'] = trim($nombre_ute);
        }

        $generos_validos = array('FEMENINO', 'MASCULINO', 'MIXTO');
        if ($genero !== null) {
            $genero = strtoupper(trim($genero));
            if (!in_array($genero, $generos_validos, true)) {
                return false; // Género inválido
            }
            $datos['genero'] = $genero;
        }

        if (empty($datos)) {
            return false; // No hay nada que actualizar
        }

        $this->db->where('id_ute', $id_ute);
        return $this->db->update('utes', $datos);
    }

    /**
     * Eliminar una UTE (cascade delete borrará los participantes automáticamente)
     */
    public function eliminar_ute($id_ute) {
        $this->db->where('id_ute', $id_ute);
        return $this->db->delete('utes');
    }

    /**
     * Agregar participante a una UTE
     */
    public function agregar_participante_a_ute($id_ute, $id_participante) {
        $datos = array(
            'id_ute' => $id_ute,
            'id_participante' => $id_participante,
            'fecha_asociacion' => date('Y-m-d H:i:s')
        );
        
        return $this->db->insert('participantes_utes', $datos);
    }

    /**
     * Eliminar participante de una UTE
     */
    public function eliminar_participante_de_ute($id_ute, $id_participante) {
        $this->db->where('id_ute', $id_ute);
        $this->db->where('id_participante', $id_participante);
        return $this->db->delete('participantes_utes');
    }

    /**
     * Verificar si un participante ya está en una UTE de una categoría
     */
    public function participante_ya_tiene_ute($id_participante, $id_categoria) {
        $this->db->select('COUNT(*) as tiene_ute');
        $this->db->from('participantes_utes pu');
        $this->db->join('utes u', 'u.id_ute = pu.id_ute', 'inner');
        $this->db->where('pu.id_participante', $id_participante);
        $this->db->where('u.id_categoria', $id_categoria);
        
        $resultado = $this->db->get()->row_array();
        
        return $resultado['tiene_ute'] > 0;
    }

    /**
     * Obtener categorías con deportes para el select de creación de UTE
     */
    public function obtener_categorias_con_deportes() {
        $this->db->select('
            c.id_categoria,
            c.nombre_categoria,
            d.id_deporte,
            d.nombre_deporte,
            d.genero
        ');
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Contar total de UTEs creadas
     */
    public function contar_total_utes() {
        return $this->db->count_all_results('utes');
    }

    /**
     * Contar total de participantes en UTEs
     */
    public function contar_participantes_en_utes() {
        return $this->db->count_all_results('participantes_utes');
    }
}

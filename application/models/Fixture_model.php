<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fixture_model — REINICIADO (pendiente de reestructuración).
 *
 * Se borró toda la implementación anterior. Quedan solo stubs seguros para
 * que el resto de la app (Resultados, reportes PDF/CSV) no rompa mientras se
 * reconstruye el panel fixture desde cero.
 */
class Fixture_model extends CI_Model {

    /**
     * Marcar FINALIZADO un partido del fixture (stub mínimo usado por
     * Resultado_model al confirmar un desempate eliminatorio).
     */
    public function registrar_resultado($id_fixture, $id_ganador = null) {
        $this->db->where('id_fixture', (int) $id_fixture);
        $this->db->update('fixtures', array('estado' => 'FINALIZADO'));
        return 'Partido marcado como finalizado.';
    }

    /* ============================================================
     *  LECTURA MINIMA (la usan los reportes CSV/PDF mientras el
     *  panel fixture se reconstruye desde cero). Consultas directas
     *  sobre la tabla `fixtures` con joins basicos.
     * ============================================================ */

    /** SELECT comun de las filas del fixture con nombres resueltos. */
    private function _consulta_base() {
        $this->db->select('
            f.*,
            d.nombre_deporte,
            c.nombre_categoria, c.genero AS categoria_genero, c.tipo_torneo,
            l.nombre AS nombre_lugar,
            u1.nombre_ute AS ute_1_nombre, u2.nombre_ute AS ute_2_nombre
        ', FALSE);
        $this->db->from('fixtures f');
        $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'left');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
        $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');

    }

    /** Orden por deporte/categoria o por horario (como esperaba el panel/reporte). */
    private function _orden($orden) {
        if ($orden === 'horario') {
            $this->db->order_by('f.fecha_competencia, f.hora_inicio, d.nombre_deporte, c.nombre_categoria', 'ASC');
        } else {
            $this->db->order_by('d.nombre_deporte, c.nombre_categoria, f.fecha_competencia, f.hora_inicio', 'ASC');
        }
    }

    /** Todas las filas de `fixtures` (opcional: filtrar por id_deporte). */
    public function obtener_todo_el_fixture($id_deporte = null, $orden = 'deporte') {
        $this->_consulta_base();
        if ($id_deporte) {
            $this->db->where('c.id_deporte', (int) $id_deporte);
        }
        $this->_orden($orden);
        return $this->db->get()->result_array();
    }

    /** Fixture de una delegacion: UTEs cuyos inscriptos pertenecen a esa delegacion. */
    public function obtener_fixture_por_delegacion($delegacion, $id_deporte = null) {
        $this->_consulta_base();
        $del = $this->db->escape($delegacion);
        $this->db->where('(
            EXISTS (SELECT 1 FROM participantes_utes pu
                    JOIN participantes pp ON pp.id_participante = pu.id_participante
                    WHERE pu.id_ute IN (f.id_ute_1, f.id_ute_2) AND pp.delegacion = ' . $del . ')
            OR EXISTS (SELECT 1 FROM inscripciones_deportivas jx
                    JOIN participantes px ON px.id_participante = jx.id_participante
                    WHERE jx.id_categoria = f.id_categoria AND jx.asistio = 1
                      AND px.delegacion = ' . $del . ')
        )', NULL, FALSE);
        if ($id_deporte) {
            $this->db->where('c.id_deporte', (int) $id_deporte);
        }
        $this->_orden('deporte');
        return $this->db->get()->result_array();
    }

}

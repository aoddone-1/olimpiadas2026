<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Grupo_model — FASE DE GRUPOS (Fixture tipo Mundial) — PARTE 1.
 *
 * Flujo: elegir categoría → crear grupos (A, B, C...) → asignar UTEs al grupo.
 * Las UTEs se asignan por FK directa: `utes.id_grupo` (NULL = sin grupo).
 */
class Grupo_model extends CI_Model {

    /** Todas las categorías con deporte (para el selector). */
    public function obtener_categorias_con_deportes() {
        $this->db->select('c.id_categoria, c.nombre_categoria, c.equipos_por_grupo, c.clasificados_por_grupo, c.mejores_segundos, c.tipo_torneo, d.id_deporte, d.nombre_deporte', FALSE);
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');
        return $this->db->get()->result_array();
    }

    public function obtener_categoria($id_categoria) {
        $this->db->where('id_categoria', (int) $id_categoria);
        return $this->db->get('categorias')->row_array();
    }

    /* ============================================================
     *  CRUD DE GRUPOS
     * ============================================================ */

    /** Grupos de una categoría con sus UTEs asignadas anidadas. */
    public function obtener_grupos_con_utes($id_categoria) {
        $grupos = $this->obtener_grupos($id_categoria);
        foreach ($grupos as &$g) {
            $g['utes'] = $this->obtener_utes_del_grupo((int) $g['id_grupo']);
        }
        unset($g);
        return $grupos;
    }

    /** Lista simple de grupos de una categoría (con conteo de UTEs). */
    public function obtener_grupos($id_categoria) {
        $this->db->select('g.*, COUNT(u.id_ute) AS cantidad_utes', FALSE);
        $this->db->from('grupos g');
        $this->db->join('utes u', 'u.id_grupo = g.id_grupo', 'left');
        $this->db->where('g.id_categoria', (int) $id_categoria);
        $this->db->group_by('g.id_grupo');
        $this->db->order_by('LENGTH(g.nombre_grupo), g.nombre_grupo', 'ASC');
        return $this->db->get()->result_array();
    }

    public function obtener_grupo($id_grupo) {
        $this->db->where('id_grupo', (int) $id_grupo);
        return $this->db->get('grupos')->row_array();
    }

    /** Crear un grupo con nombre explícito ("Grupo A" / "A" → guarda "A"). */
    public function crear_grupo($id_categoria, $nombre_grupo) {
        $nombre = strtoupper(trim(preg_replace('/^GRUPO\s+/i', '', $nombre_grupo)));
        if ($nombre === '' || !preg_match('/^[A-Z0-9]{1,10}$/', $nombre)) {
            return array('ok' => FALSE, 'error' => 'Nombre de grupo inválido. Use una letra (A, B, C...).');
        }

        // ¿Ya existe en esta categoría?
        $existe = $this->db->where('id_categoria', (int) $id_categoria)
                           ->where('nombre_grupo', $nombre)
                           ->count_all_results('grupos');
        if ($existe > 0) {
            return array('ok' => FALSE, 'error' => 'El Grupo ' . $nombre . ' ya existe en esta categoría.');
        }

        $this->db->insert('grupos', array('id_categoria' => (int) $id_categoria, 'nombre_grupo' => $nombre));
        return array('ok' => TRUE, 'id_grupo' => $this->db->insert_id(), 'nombre_grupo' => $nombre);
    }

    /** Proponer la siguiente letra libre para la categoría (A, B, C...). */
    public function sugerir_siguiente_nombre($id_categoria) {
        $usados = array_column($this->obtener_grupos($id_categoria), 'nombre_grupo');
        foreach (range('A', 'Z') as $letra) {
            if (!in_array($letra, $usados)) return $letra;
        }
        return NULL; // más de 26 grupos no soportado
    }

    /** Renombrar un grupo. */
    public function renombrar_grupo($id_grupo, $nuevo_nombre) {
        $grupo = $this->obtener_grupo($id_grupo);
        if (!$grupo) return array('ok' => FALSE, 'error' => 'Grupo inexistente.');

        $nombre = strtoupper(trim(preg_replace('/^GRUPO\s+/i', '', $nuevo_nombre)));
        if ($nombre === '' || !preg_match('/^[A-Z0-9]{1,10}$/', $nombre)) {
            return array('ok' => FALSE, 'error' => 'Nombre de grupo inválido.');
        }

        $conflicto = $this->db->where('id_categoria', (int) $grupo['id_categoria'])
                              ->where('nombre_grupo', $nombre)
                              ->where('id_grupo !=', (int) $grupo['id_grupo'])
                              ->count_all_results('grupos');
        if ($conflicto > 0) {
            return array('ok' => FALSE, 'error' => 'Ya existe otro grupo "' . $nombre . '" en la categoría.');
        }

        $this->db->where('id_grupo', (int) $id_grupo);
        $this->db->update('grupos', array('nombre_grupo' => $nombre));
        return array('ok' => TRUE);
    }

    /** Eliminar un grupo (las UTEs quedan sin grupo por ON DELETE SET NULL). */
    public function eliminar_grupo($id_grupo) {
        $grupo = $this->obtener_grupo($id_grupo);
        if (!$grupo) return array('ok' => FALSE, 'error' => 'Grupo inexistente.');

        // Bloquear si ya hay partidos de fase GRUPO generados para este grupo.
        $partidos = $this->db->where('f.id_categoria', (int) $grupo['id_categoria'])
                             ->where('f.fase', 'GRUPO')
                             ->where('(u1.id_grupo = ' . (int) $id_grupo . ' OR u2.id_grupo = ' . (int) $id_grupo . ')', NULL, FALSE)
                             ->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left')
                             ->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left')
                             ->from('fixtures f')
                             ->count_all_results();
        if ($partidos > 0) {
            return array('ok' => FALSE, 'error' => 'El grupo tiene partidos de fase GRUPO generados. Borralos primero.');
        }

        $this->db->where('id_grupo', (int) $id_grupo);
        $this->db->delete('grupos');
        return array('ok' => TRUE);
    }

    /* ============================================================
     *  ASIGNACIÓN DE UTEs A GRUPOS
     * ============================================================ */

    /** UTEs de una categoría con su grupo actual (NULL = sin asignar). */
    public function obtener_utes_por_categoria($id_categoria) {
        $this->db->select('u.id_ute, u.nombre_ute, u.id_grupo, g.nombre_grupo, COUNT(pu.id_participante) AS integrantes', FALSE);
        $this->db->from('utes u');
        $this->db->join('grupos g', 'g.id_grupo = u.id_grupo', 'left');
        $this->db->join('participantes_utes pu', 'pu.id_ute = u.id_ute', 'left');
        $this->db->where('u.id_categoria', (int) $id_categoria);
        $this->db->group_by('u.id_ute');
        $this->db->order_by('ISNULL(u.id_grupo) DESC, g.nombre_grupo, u.nombre_ute', 'ASC');
        return $this->db->get()->result_array();
    }

    /** UTEs asignadas a un grupo. */
    public function obtener_utes_del_grupo($id_grupo) {
        $this->db->select('u.id_ute, u.nombre_ute, COUNT(pu.id_participante) AS integrantes', FALSE);
        $this->db->from('utes u');
        $this->db->join('participantes_utes pu', 'pu.id_ute = u.id_ute', 'left');
        $this->db->where('u.id_grupo', (int) $id_grupo);
        $this->db->group_by('u.id_ute');
        $this->db->order_by('u.nombre_ute', 'ASC');
        return $this->db->get()->result_array();
    }

    /** Asignar/desasignar UNA UTE (valida que la UTE sea de la categoría del grupo). */
    public function asignar_ute_a_grupo($id_ute, $id_grupo = NULL) {
        $ute = $this->db->where('id_ute', (int) $id_ute)->get('utes')->row_array();
        if (!$ute) return array('ok' => FALSE, 'error' => 'UTE inexistente.');

        if ($id_grupo === NULL || $id_grupo === '' || (int) $id_grupo === 0) {
            $this->db->where('id_ute', (int) $id_ute);
            $this->db->update('utes', array('id_grupo' => NULL));
            return array('ok' => TRUE, 'accion' => 'desasignada');
        }

        $grupo = $this->obtener_grupo($id_grupo);
        if (!$grupo) return array('ok' => FALSE, 'error' => 'Grupo inexistente.');
        if ((int) $grupo['id_categoria'] !== (int) $ute['id_categoria']) {
            return array('ok' => FALSE, 'error' => 'La UTE no pertenece a la categoría de ese grupo.');
        }

        $this->db->where('id_ute', (int) $id_ute);
        $this->db->update('utes', array('id_grupo' => (int) $id_grupo));
        return array('ok' => TRUE, 'accion' => 'asignada', 'nombre_grupo' => $grupo['nombre_grupo']);
    }

    /** Repartir automáticamente todas las UTEs sin grupo (serpentina por delegación). */
    public function sorteo_automatico($id_categoria) {
        $grupos = $this->obtener_grupos($id_categoria);
        if (empty($grupos)) return array('ok' => FALSE, 'error' => 'La categoría no tiene grupos creados.');

        $utes_sin_grupo = $this->db->where('id_categoria', (int) $id_categoria)
                                   ->where('id_grupo IS NULL', NULL, FALSE)
                                   ->order_by('nombre_ute', 'ASC')
                                   ->get('utes')->result_array();
        if (empty($utes_sin_grupo)) return array('ok' => TRUE, 'asignadas' => 0);

        $n = count($grupos);
        $asignadas = 0;
        for ($i = 0; $i < count($utes_sin_grupo); $i++) {
            // Serpentina: A,B,C | C,B,A | A,B,C... ayuda a balancear grupos.
            $vuelta = intdiv($i, $n);
            $pos = $i % $n;
            $idx = ($vuelta % 2 === 0) ? $pos : ($n - 1 - $pos);
            $this->db->where('id_ute', (int) $utes_sin_grupo[$i]['id_ute']);
            $this->db->update('utes', array('id_grupo' => (int) $grupos[$idx]['id_grupo']));
            $asignadas++;
        }

        return array('ok' => TRUE, 'asignadas' => $asignadas);
    }

    /** Resumen para validar antes de generar partidos (Parte 2). */
    public function resumen_categoria($id_categoria) {
        $cat = $this->obtener_categoria($id_categoria);
        if (!$cat) return array('ok' => FALSE, 'error' => 'Categoría inexistente.');

        $grupos = $this->obtener_grupos($id_categoria);
        $sin_grupo = $this->db->where('id_categoria', (int) $id_categoria)
                              ->where('id_grupo IS NULL', NULL, FALSE)
                              ->count_all_results('utes');

        $tamnios = array();
        foreach ($grupos as $g) $tamnios[] = (int) $g['cantidad_utes'];

        return array(
            'ok' => TRUE,
            'categoria' => $cat,
            'cantidad_grupos' => count($grupos),
            'utes_sin_grupo' => $sin_grupo,
            'utes_totales' => array_sum($tamnios) + $sin_grupo,
            'tamnios' => $tamnios,
            'listo_para_fixture' => (count($grupos) >= 2 && $sin_grupo === 0),
        );
    }
}

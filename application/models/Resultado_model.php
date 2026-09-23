<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Resultado_model
 * Carga de resultados (pestaña "Resultados" de Control Total):
 *  - MARCADOR: enfrentamientos (fútbol, básquet, vóley...) -> Equipo A 4 - 2 Equipo B.
 *  - TIEMPO:   deportes de tiempo/masivos (running, ciclismo, natación...)
 *              -> posiciones con tiempos.
 * Tablas: resultados + resultado_detalle (ver sql/resultados.sql).
 */
class Resultado_model extends CI_Model {

    /** ¿Existe la tabla? (aviso amigable si todavía no se corrió el SQL). */
    private function _tabla_existe($tabla) {
        try {
            return $this->db->table_exists($tabla);
        } catch (Throwable $e) {
            return false;
        }
    }

    /** ¿Existen las tablas de resultados? */
    public function tablas_existentes() {
        static $ok = null;
        if ($ok === null) {
            $ok = $this->_tabla_existe('resultados') && $this->_tabla_existe('resultado_detalle');
        }
        return $ok;
    }

    /* ============================================================
     *  GUARDAR / ELIMINAR
     * ============================================================ */

    /**
     * Guarda un resultado nuevo (MARCADOR o TIEMPO) con su detalle.
     * $datos llega del formulario AJAX de la pestaña Resultados.
     */
    public function guardar_resultado($datos, $id_usuario = null) {
        $id_cat = isset($datos['id_categoria']) ? (int) $datos['id_categoria'] : 0;
        if (!$id_cat) throw new Exception('Elegí el deporte/categoría.');

        $this->db->where('id_categoria', $id_cat);
        if (!$this->db->get('categorias')->num_rows()) {
            throw new Exception('La categoría seleccionada no existe.');
        }

        $tipo = strtoupper(trim(isset($datos['tipo_resultado']) ? $datos['tipo_resultado'] : ''));
        if (!in_array($tipo, array('MARCADOR', 'TIEMPO'), true)) {
            throw new Exception('Tipo de resultado inválido.');
        }

        $nombre = trim(isset($datos['nombre_evento']) ? $datos['nombre_evento'] : '');
        if ($nombre === '') throw new Exception('Poné un nombre para el partido/prueba.');

        $fecha = trim(isset($datos['fecha_resultado']) ? $datos['fecha_resultado'] : '');
        if ($fecha !== '') {
            $ts = strtotime($fecha);
            if (!$ts) throw new Exception('Fecha inválida.');
            $fecha = date('Y-m-d', $ts);
        } else {
            $fecha = null;
        }

        // fixture opcional: debe existir y pertenecer a la misma categoría
        $id_fixture = !empty($datos['id_fixture']) ? (int) $datos['id_fixture'] : null;
        if ($id_fixture) {
            $this->db->where('id_fixture', $id_fixture);
            $fx = $this->db->get('fixtures')->row_array();
            if (!$fx) throw new Exception('El partido del fixture seleccionado no existe.');
            if ((int) $fx['id_categoria'] !== $id_cat) {
                throw new Exception('El partido del fixture no pertenece a esa categoría.');
            }
        }

        // ---------- armar el detalle según el tipo ----------
        $detalle = array();
        if ($tipo === 'MARCADOR') {
            $eq1 = trim(isset($datos['equipo_1']) ? $datos['equipo_1'] : '');
            $eq2 = trim(isset($datos['equipo_2']) ? $datos['equipo_2'] : '');
            if ($eq1 === '' || $eq2 === '') throw new Exception('Completá los dos equipos.');
            if (!isset($datos['goles_1']) || $datos['goles_1'] === ''
                || !isset($datos['goles_2']) || $datos['goles_2'] === ''
                || !ctype_digit((string) $datos['goles_1']) || !ctype_digit((string) $datos['goles_2'])) {
                throw new Exception('Los goles/tantos deben ser números (ej: 4 y 2).');
            }
            $detalle[] = array(
                'id_ute' => $this->_resolver_ute($id_cat, $datos['id_ute_1'] ?? null, $eq1),
                'nombre_libre' => $eq1,
                'marcador_local' => (int) $datos['goles_1'],
                'marcador_visita' => (int) $datos['goles_2'],
            );
            $detalle[] = array(
                'id_ute' => $this->_resolver_ute($id_cat, $datos['id_ute_2'] ?? null, $eq2),
                'nombre_libre' => $eq2,
                'marcador_local' => (int) $datos['goles_1'],
                'marcador_visita' => (int) $datos['goles_2'],
            );
        } else { // TIEMPO
            $nombres = isset($datos['comp_nombre']) ? (array) $datos['comp_nombre'] : array();
            $posiciones = isset($datos['comp_posicion']) ? (array) $datos['comp_posicion'] : array();
            $tiempos = isset($datos['comp_tiempo']) ? (array) $datos['comp_tiempo'] : array();
            $utes = isset($datos['comp_ute']) ? (array) $datos['comp_ute'] : array();

            foreach ($nombres as $i => $nom) {
                $nom = trim($nom);
                $pos = isset($posiciones[$i]) ? trim((string) $posiciones[$i]) : '';
                $tie = isset($tiempos[$i]) ? trim($tiempos[$i]) : '';
                if ($nom === '' && $pos === '' && $tie === '') continue; // fila vacía
                if ($nom === '') throw new Exception('Fila de posición sin competidor.');
                if ($pos === '' || !ctype_digit($pos) || (int) $pos < 1) {
                    throw new Exception('La posición de "' . $nom . '" debe ser un número (1, 2, 3...).');
                }
                if ($tie !== '' && !preg_match('/^(\d{1,2}:)?\d{1,2}:\d{1,2}(\.\d{1,6})?$/', $tie)) {
                    throw new Exception('Tiempo inválido para "' . $nom . '" (usá mm:ss o hh:mm:ss).');
                }
                $detalle[] = array(
                    'id_ute' => $this->_resolver_ute($id_cat, isset($utes[$i]) ? $utes[$i] : null, $nom),
                    'nombre_libre' => $nom,
                    'posicion' => (int) $pos,
                    'tiempo' => $tie !== '' ? $tie : null,
                );
            }
            if (!$detalle) throw new Exception('Cargá al menos una posición con su tiempo.');
        }

        // ---------- insertar cabecera + detalle ----------
        $payload = array(
            'id_categoria'    => $id_cat,
            'id_fixture'      => $id_fixture,
            'nombre_evento'   => $nombre,
            'tipo_resultado'  => $tipo,
            'fecha_resultado' => $fecha,
            'lugar'           => trim(isset($datos['lugar']) ? $datos['lugar'] : '') ?: null,
            'observaciones'   => trim(isset($datos['observaciones']) ? $datos['observaciones'] : '') ?: null,
            'creado_por'      => $id_usuario ? (int) $id_usuario : null,
        );
        $this->db->insert('resultados', $payload);
        $id_resultado = $this->db->insert_id();
        if (!$id_resultado) throw new Exception('No se pudo guardar el resultado.');

        // Unificar claves para que insert_batch genere columnas consistentes.
        $cols = array('id_resultado', 'id_ute', 'nombre_libre', 'posicion', 'tiempo', 'marcador_local', 'marcador_visita');
        $filas = array();
        foreach ($detalle as $d) {
            $d['id_resultado'] = $id_resultado;
            $fila = array();
            foreach ($cols as $c) {
                $fila[$c] = isset($d[$c]) ? $d[$c] : null;
            }
            $filas[] = $fila;
        }
        $this->db->insert_batch('resultado_detalle', $filas);

        return $id_resultado;
    }

    /** Resuelve el id_ute: valor positivo elegido del select, o lo buscamos por nombre. */
    private function _resolver_ute($id_cat, $id_ute_post, $nombre) {
        $id = (int) $id_ute_post;
        if ($id > 0) {
            $this->db->where('id_ute', $id)->where('id_categoria', $id_cat);
            if ($this->db->get('utes')->num_rows()) return $id;
        }
        // búsqueda por nombre exacto (sin distinción de mayúsculas gracias al collation)
        $this->db->where('id_categoria', $id_cat)->where('nombre_ute', trim($nombre));
        $u = $this->db->get('utes')->row_array();
        return $u ? (int) $u['id_ute'] : null;
    }

    /** Elimina un resultado (el detalle cae en cascada). */
    public function eliminar_resultado($id_resultado) {
        $this->db->where('id_resultado', (int) $id_resultado);
        return $this->db->delete('resultados');
    }

    /* ============================================================
     *  CONSULTAS
     * ============================================================ */

    /** Todos los resultados con su deporte/categoría (para la pestaña). */
    public function obtener_todos_los_resultados() {
        $this->db->select('r.*, c.nombre_categoria, d.nombre_deporte, f.nombre_prueba', FALSE);
        $this->db->from('resultados r');
        $this->db->join('categorias c', 'c.id_categoria = r.id_categoria', 'left');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
        $this->db->join('fixtures f', 'f.id_fixture = r.id_fixture', 'left');
        $this->db->order_by('r.fecha_resultado DESC, r.id_resultado DESC');
        $rows = $this->db->get()->result_array();

        if (!$rows) return array();

        // Adjuntar el detalle de cada resultado en un solo query.
        $ids = array_column($rows, 'id_resultado');
        $this->db->where_in('id_resultado', $ids);
        $this->db->order_by('id_detalle', 'ASC');
        $detalles = $this->db->get('resultado_detalle')->result_array();

        $por_res = array();
        foreach ($detalles as $d) {
            $por_res[(int) $d['id_resultado']][] = $d;
        }
        foreach ($rows as &$r) {
            $r['detalle'] = isset($por_res[(int) $r['id_resultado']])
                ? $por_res[(int) $r['id_resultado']] : array();
        }
        unset($r);
        return $rows;
    }

    /** Partidos/jornadas del fixture de una categoría (para vincular el resultado). */
    public function obtener_fixtures_por_categoria($id_categoria) {
        $this->db->select('id_fixture, nombre_prueba, fase, numero_fecha, estado', FALSE);
        $this->db->where('id_categoria', (int) $id_categoria);
        $this->db->order_by('numero_fecha, fecha_competencia, hora_inicio', 'ASC');
        return $this->db->get('fixtures')->result_array();
    }
}

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

    /** Modalidad_competencia del deporte asociado a una categoría. */
    public function modalidad_de_categoria($id_categoria) {
        $this->db->select('d.modalidad_competencia');
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->where('c.id_categoria', (int) $id_categoria);
        $row = $this->db->get()->row_array();
        return $row ? $row['modalidad_competencia'] : null;
    }

    /**
     * Competidores de una categoría para la carga de resultados masivos:
     *  - Participantes inscriptos individualmente ("INSCRIPCION PERSONAL":
     *    tabla inscripciones_deportivas) -> id negativo (= -id_inscripcion),
     *    igual que el resto del sistema (fixture masivo).
     *  - Equipos/UTEs dados de alta en la categoría -> id positivo.
     */
    public function obtener_competidores_por_categoria($id_categoria) {
        $id_categoria = (int) $id_categoria;
        $out = array();

        // 1) Inscripciones personales (cada participante inscripto en la categoría)
        $this->db->select('\n            i.id_inscripcion,\n            p.nombre_completo,\n            p.dni,\n            p.delegacion\n        ', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->where('i.id_categoria', $id_categoria);
        $this->db->order_by('p.nombre_completo', 'ASC');
        foreach ($this->db->get()->result_array() as $r) {
            $out[] = array(
                'id'          => -(int) $r['id_inscripcion'],
                'tipo'        => 'PERSONAL',
                'nombre'      => $r['nombre_completo'],
                'dni'         => $r['dni'],
                'delegacion'  => $r['delegacion'],
            );
        }

        // 2) UTEs / equipos de la categoría
        $this->db->select('u.id_ute, u.nombre_ute, COUNT(pu.id_participante) cantidad', FALSE);
        $this->db->from('utes u');
        $this->db->join('participantes_utes pu', 'pu.id_ute = u.id_ute', 'left');
        $this->db->where('u.id_categoria', $id_categoria);
        $this->db->group_by('u.id_ute, u.nombre_ute');
        $this->db->order_by('u.nombre_ute', 'ASC');
        foreach ($this->db->get()->result_array() as $r) {
            $out[] = array(
                'id'         => (int) $r['id_ute'],
                'tipo'       => 'EQUIPO',
                'nombre'     => $r['nombre_ute'],
                'dni'        => null,
                'delegacion' => null,
                'cantidad'   => (int) $r['cantidad'],
            );
        }

        return $out;
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

        // ---------- el TIPO de resultado lo define la modalidad del deporte ----------
        // ENFRENTAMIENTO -> MARCADOR (goles/tantos por equipo)
        // MASIVO_TIEMPO  -> TIEMPO   (posición + tiempo por participante inscripto)
        $modalidad = $this->modalidad_de_categoria($id_cat);
        if ($modalidad === null) {
            throw new Exception('La categoría seleccionada no existe.');
        }
        $tipo = ($modalidad === 'MASIVO_TIEMPO') ? 'TIEMPO' : 'MARCADOR';

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

        // fixture OPCIONAL en la POST; si viene vacío pero el partido ya tiene
        // marcador cargado, se infiere automáticamente desde fixtures.
        $id_fixture = !empty($datos['id_fixture']) ? (int) $datos['id_fixture'] : null;
        $inferido = false;
        if (!$id_fixture && $tipo === 'MARCADOR') {
            $id_fixture = $this->_detectar_fixture_por_marcador(
                $id_cat,
                trim(isset($datos['equipo_1']) ? $datos['equipo_1'] : ''),
                trim(isset($datos['equipo_2']) ? $datos['equipo_2'] : ''),
                isset($datos['goles_1']) ? (int) $datos['goles_1'] : null,
                isset($datos['goles_2']) ? (int) $datos['goles_2'] : null
            );
            $inferido = (bool) $id_fixture;
        }
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
        } else { // TIEMPO (deporte MASIVO_TIEMPO): posiciones de los participantes inscriptos
            $nombres = isset($datos['comp_nombre']) ? (array) $datos['comp_nombre'] : array();
            $posiciones = isset($datos['comp_posicion']) ? (array) $datos['comp_posicion'] : array();
            $tiempos = isset($datos['comp_tiempo']) ? (array) $datos['comp_tiempo'] : array();
            $utes = isset($datos['comp_ute']) ? (array) $datos['comp_ute'] : array();

            foreach ($nombres as $i => $nom) {
                $nom = trim($nom);
                $pos = isset($posiciones[$i]) ? trim((string) $posiciones[$i]) : '';
                $tie = isset($tiempos[$i]) ? trim($tiempos[$i]) : '';
                $id_comp = isset($utes[$i]) ? (int) $utes[$i] : 0; // >0 UTE, <0 inscripción personal
                if ($nom === '' && !$id_comp && $pos === '' && $tie === '') continue; // fila vacía

                if ($id_comp) {
                    // El competidor sale de la lista de inscriptos de la categoría:
                    // se guarda el nombre real resuelto desde la BD (no del form).
                    $resuelto = $this->_resolver_competidor($id_cat, $id_comp);
                    $nom = $resuelto['nombre'];
                } elseif ($nom === '') {
                    throw new Exception('Fila de posición sin competidor.');
                }

                if ($pos === '' || !ctype_digit($pos) || (int) $pos < 1) {
                    throw new Exception('La posición de "' . $nom . '" debe ser un número (1, 2, 3...).');
                }
                if ($tie !== '' && !preg_match('/^(\d{1,2}:)?\d{1,2}:\d{1,2}(\.\d{1,6})?$/', $tie)) {
                    throw new Exception('Tiempo inválido para "' . $nom . '" (usá mm:ss o hh:mm:ss).');
                }
                $detalle[] = array(
                    'id_ute' => $id_comp ?: null,
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

        // Si el resultado se vinculó (a mano o inferido) a un partido del
        // fixture, lo marcamos como FINALIZADO para que Fixture y Resultados
        // queden sincronizados.
        if ($id_fixture) {
            $this->db->where('id_fixture', $id_fixture);
            $this->db->update('fixtures', array('estado' => 'FINALIZADO'));
        }

        return array('id_resultado' => $id_resultado, 'fixture_inferido' => $inferido);
    }

    /**
     * Intenta deducir a qué partido del fixture pertenece un marcador cargado
     * a mano: busca en la categoría un partido cuyos dos equipos y goles
     * coincidan con los datos ingresados (en cualquiera de los dos órdenes).
     */
    private function _detectar_fixture_por_marcador($id_cat, $eq1, $eq2, $g1, $g2) {
        if ($eq1 === '' || $eq2 === '' || $g1 === null || $g2 === null) return null;

        $ute_ids = array();
        foreach (array($eq1, $eq2) as $nom) {
            $this->db->select('id_ute');
            $this->db->where('id_categoria', (int) $id_cat);
            $this->db->where('nombre_ute', $nom);
            $u = $this->db->get('utes')->row_array();
            if (!$u) return null;
            $ute_ids[] = (int) $u['id_ute'];
        }

        $this->db->where('id_categoria', (int) $id_cat);
        $this->db->where_in('estado', array('PROGRAMADO', 'EN_CURSO', 'FINALIZADO'));
        $partidos = $this->db->get('fixtures')->result_array();

        foreach ($partidos as $p) {
            $id1 = (int) $p['id_ute_1'];
            $id2 = (int) $p['id_ute_2'];
            if (!$id1 || !$id2) continue;
            if (!in_array($id1, $ute_ids, true) || !in_array($id2, $ute_ids, true)) continue;
            if ($id1 === $id2) continue;

            $pg1 = isset($p['marcador_1']) ? (int) $p['marcador_1'] : -1;
            $pg2 = isset($p['marcador_2']) ? (int) $p['marcador_2'] : -1;
            $mismo_orden   = ($id1 === $ute_ids[0] && $id2 === $ute_ids[1]);
            $orden_invertido = !$mismo_orden;

            if ($pg1 >= 0 && $pg2 >= 0) {
                // El fixture ya tiene marcador: debe coincidir (en el orden que esté).
                if ($mismo_orden && $pg1 === $g1 && $pg2 === $g2) return (int) $p['id_fixture'];
                if ($orden_invertido && $pg1 === $g2 && $pg2 === $g1) return (int) $p['id_fixture'];
                continue;
            }

            // Sin marcador en el fixture: si es el único partido pendiente entre
            // ambos equipos, se asume ese (solo cuando no hay desempate posible).
            $candidatos_pendientes = $this->_contar_pendientes_entre($partidos, $ute_ids);
            if ($candidatos_pendientes === 1) return (int) $p['id_fixture'];
            return null; // hay más de un partido pendiente: mejor no adivinar
        }

        return null;
    }

    /** Cantidad de partidos (de la lista) sin marcador entre los dos UTEs dados. */
    private function _contar_pendientes_entre($partidos, $ute_ids) {
        $cant = 0;
        foreach ($partidos as $p) {
            $id1 = (int) $p['id_ute_1'];
            $id2 = (int) $p['id_ute_2'];
            if (!$id1 || !$id2 || $id1 === $id2) continue;
            if (!in_array($id1, $ute_ids, true) || !in_array($id2, $ute_ids, true)) continue;
            $tiene_marca = isset($p['marcador_1']) && $p['marcador_1'] !== null
                && isset($p['marcador_2']) && $p['marcador_2'] !== null;
            if (!$tiene_marca) $cant++;
        }
        return $cant;
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

    /**
     * Verifica que el competidor elegido en la planilla masiva exista y
     * pertenezca a la categoría, y devuelve su nombre real.
     *   id > 0  -> UTE/equipo de la categoría (tabla utes)
     *   id < 0  -> inscripción personal (inscripciones_deportivas, id = -id_inscripcion)
     */
    private function _resolver_competidor($id_cat, $id_comp) {
        $id_comp = (int) $id_comp;
        if ($id_comp > 0) {
            $this->db->select('nombre_ute');
            $this->db->where('id_ute', $id_comp)->where('id_categoria', $id_cat);
            $u = $this->db->get('utes')->row_array();
            if (!$u) throw new Exception('El equipo seleccionado no pertenece a esa categoría.');
            return array('nombre' => $u['nombre_ute']);
        }

        $this->db->select('p.nombre_completo, p.dni', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->where('i.id_inscripcion', -$id_comp)->where('i.id_categoria', $id_cat);
        $r = $this->db->get()->row_array();
        if (!$r) throw new Exception('El participante seleccionado no pertenece a esa categoría.');
        return array('nombre' => $r['nombre_completo'] . ' (' . $r['dni'] . ')');
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
        // Se resuelven también los nombres de los equipos (UTE 1 y UTE 2) para
        // que la pestaña Resultados pueda autocompletar el formulario entero
        // desde el fixture.
        $this->db->select('
            f.id_fixture, f.nombre_prueba, f.fase, f.numero_fecha, f.estado,
            f.fecha_competencia, f.hora_inicio, f.hora_fin,
            f.id_ute_1, f.id_ute_2,
            u1.nombre_ute as ute_1_nombre, u2.nombre_ute as ute_2_nombre,
            l.nombre as lugar_nombre
        ', FALSE);
        $this->db->from('fixtures f');
        $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
        $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->where('f.id_categoria', (int) $id_categoria);
        $this->db->order_by('numero_fecha, fecha_competencia, hora_inicio', 'ASC');
        return $this->db->get()->result_array();
    }
}

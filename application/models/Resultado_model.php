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

    /** ¿Existe la columna fixtures.resultado? (ver sql/fixture_resultado_masivo.sql). */
    private function _existe_columna_resultado() {
        static $existe = null;
        if ($existe === null) {
            try {
                $cols = $this->db->field_names('fixtures');
                $existe = is_array($cols) && in_array('resultado', $cols, true);
            } catch (Throwable $e) {
                $existe = false;
            }
        }
        return $existe;
    }

    /**
     * Asegura que la categoría masiva tenga al menos una jornada (fila JORNADA_UNICA
     * en fixtures). Sin esto, los deportes MASIVO_TIEMPO nunca muestran participantes
     * en la pestaña Resultados porque no hay jornada a la cual vincularlos.
     * Devuelve el id_fixture de la jornada (existente o recién creada), o 0 si falla.
     */
    public function asegurar_jornada_masiva($id_categoria) {
        $id_categoria = (int) $id_categoria;
        if (!$id_categoria) return 0;

        $this->db->select('id_fixture');
        $this->db->where('id_categoria', $id_categoria);
        $this->db->order_by('numero_fecha, fecha_competencia, hora_inicio, id_fixture', 'ASC');
        $this->db->limit(1);
        $fx = $this->db->get('fixtures')->row_array();
        if ($fx) return (int) $fx['id_fixture'];

        // No hay fixture: se crea la "Largada General" con los datos de la categoría.
        $this->db->select('c.dia_competencia, c.hora_competencia, c.id_lugar', FALSE);
        $this->db->where('c.id_categoria', $id_categoria);
        $cat = $this->db->get('categorias')->row_array();
        if (!$cat) return 0;

        $lugar = !empty($cat['id_lugar']) ? (int) $cat['id_lugar'] : 0;
        if (!$lugar) {
            $this->db->order_by('id', 'ASC');
            $this->db->limit(1);
            $l = $this->db->get('lugares')->row_array();
            $lugar = $l ? (int) $l['id'] : 0;
        }
        if (!$lugar) return 0; // sin lugares no se puede crear (fixtures.id_lugar NOT NULL)

        $cant_equipos = (int) $this->db
            ->where('id_categoria', $id_categoria)
            ->count_all_results('utes', FALSE);

        $fecha = $cat['dia_competencia'] ?: date('Y-m-d');
        $hora  = $cat['hora_competencia'] ?: '09:00:00';
        $this->db->insert('fixtures', array(
            'id_categoria'      => $id_categoria,
            'id_lugar'          => $lugar,
            'id_ute_1'          => null,
            'id_ute_2'          => null,
            'nombre_prueba'     => 'Largada General (' . $cant_equipos . ' equipos)',
            'fase'              => 'JORNADA_UNICA',
            'numero_fecha'      => 1,
            'fecha_competencia' => $fecha,
            'hora_inicio'       => $hora,
            'hora_fin'          => $hora,
            'estado'            => 'PROGRAMADO',
        ));
        return (int) $this->db->insert_id();
    }

    /**
     * Competidores de una categoría para la carga de resultados masivos.
     * Criterio UNIFICADO con el fixture (Fixture_model::obtener_utes_por_categoria):
     * cada UTE/equipo dado de alta en la categoría es un competidor (id positivo),
     * con sus integrantes resueltos desde participantes_utes e inscripciones.
     * Si la categoría no tiene ninguna UTE, se listan los inscriptos individuales
     * (inscripciones_deportivas) con id negativo (= -id_inscripcion).
     * ANTES se devolvían las inscripciones personales + las UTEs por separado,
     * y si el participante estaba en una UTE la lista quedaba vacía o desdoblada.
     */
    public function obtener_competidores_por_categoria($id_categoria) {
        $id_categoria = (int) $id_categoria;
        $out = array();

        // 1) UTEs / equipos de la categoría (mismo criterio que usa el fixture)
        $this->db->select('u.id_ute, u.nombre_ute', FALSE);
        $this->db->from('utes u');
        $this->db->where('u.id_categoria', $id_categoria);
        $this->db->order_by('u.nombre_ute', 'ASC');
        $utes = $this->db->get()->result_array();

        if ($utes) {
            // Integrantes de cada UTE (para mostrar "Equipo X (Juan Pérez, Ana G.)")
            $ute_ids = array_map('intval', array_column($utes, 'id_ute'));
            $this->db->select('pu.id_ute, p.nombre_completo', FALSE);
            $this->db->from('participantes_utes pu');
            $this->db->join('participantes p', 'p.id_participante = pu.id_participante', 'inner');
            $this->db->where_in('pu.id_ute', $ute_ids);
            $this->db->order_by('p.nombre_completo', 'ASC');
            $integrantes = array();
            $cant = array();
            foreach ($this->db->get()->result_array() as $r) {
                $uid = (int) $r['id_ute'];
                $integrantes[$uid][] = $r['nombre_completo'];
                $cant[$uid] = isset($cant[$uid]) ? $cant[$uid] + 1 : 1;
            }

            foreach ($utes as $u) {
                $uid = (int) $u['id_ute'];
                $out[] = array(
                    'id'          => $uid,
                    'tipo'        => 'EQUIPO',
                    'nombre'      => $u['nombre_ute'],
                    'dni'         => null,
                    'delegacion'  => null,
                    'cantidad'    => isset($cant[$uid]) ? $cant[$uid] : 0,
                    'integrantes' => isset($integrantes[$uid]) ? implode(', ', $integrantes[$uid]) : '',
                );
            }
            return $out;
        }

        // 2) Sin UTEs creadas: inscriptos individuales de la categoría
        $this->db->select('i.id_inscripcion, p.nombre_completo, p.dni, p.delegacion', FALSE);
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
            $ids = isset($datos['comp_id']) ? (array) $datos['comp_id']
                : (isset($datos['comp_ute']) ? (array) $datos['comp_ute'] : array());

            // --- PROCESAR TIEMPOS (soporta string separado por comas o array) ---
            $tiempos_raw = isset($datos['comp_tiempo']) ? $datos['comp_tiempo'] : array();
            if (is_string($tiempos_raw)) {
                $tiempos = array_map('trim', explode(',', $tiempos_raw));
            } else {
                $tiempos = (array) $tiempos_raw;
            }

            // Helper para convertir formato hh:mm:ss o mm:ss a segundos totales
            $a_segundos = function($str) {
                if (!$str) return PHP_INT_MAX; // Si no hay tiempo, va al final
                $p = explode(':', $str);
                if (count($p) === 3) return ((int)$p[0] * 3600) + ((int)$p[1] * 60) + (float)$p[2];
                if (count($p) === 2) return ((int)$p[0] * 60) + (float)$p[1];
                return (float)$p[0];
            };

            foreach ($nombres as $i => $nom) {
                $nom = trim($nom);
                $tie = isset($tiempos[$i]) ? trim((string)$tiempos[$i]) : '';
                $id_comp = isset($ids[$i]) ? (int) $ids[$i] : 0;

                if ($nom === '' && !$id_comp && $tie === '') continue;

                if ($id_comp) {
                    $resuelto = $this->_resolver_competidor($id_cat, $id_comp);
                    $nom = $resuelto['nombre'];
                } elseif ($nom === '') {
                    throw new Exception('Fila de posición sin competidor.');
                }

                $tie_limpio = preg_replace('/\s+/', '', $tie);

                if ($tie_limpio !== '') {
                    if (!preg_match('/^(?:(?:\d{1,2}:)?\d{1,2}:\d{2}|\d{1,2})(?:\.\d{1,6})?$/', $tie_limpio)) {
                        throw new Exception('Tiempo inválido para "' . $nom . '" (usá mm:ss o hh:mm:ss). Recibido: ' . $tie);
                    }
                }

                $detalle[] = array(
                    'id_ute' => $id_comp ?: null,
                    'nombre_libre' => $nom,
                    'posicion' => 0, // Se recalculará tras el ordenamiento
                    'tiempo' => $tie_limpio !== '' ? $tie_limpio : null,
                    '_segundos' => $a_segundos($tie_limpio) // Clave auxiliar para ordenar
                );
            }

            if (!$detalle) throw new Exception('Cargá al menos un tiempo.');

            // --- REORDENAR DETALLE POR TIEMPO Y ASIGNAR POSICIONES ---
            usort($detalle, function($a, $b) {
                if ($a['_segundos'] == $b['_segundos']) return 0;
                return ($a['_segundos'] < $b['_segundos']) ? -1 : 1;
            });

            foreach ($detalle as $idx => &$d) {
                $d['posicion'] = $idx + 1; // Asigna 1 al más rápido, 2 al segundo, etc.
                unset($d['_segundos']);    // Limpiamos la propiedad auxiliar antes de insertar
            }
            unset($d); // Romper referencia
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
        // desde el fixture. En deportes MASIVO_TIEMPO se agrega además el
        // "podio" guardado en fixtures.resultado con los nombres resueltos.
        $this->db->select('
            f.id_fixture, f.nombre_prueba, f.fase, f.numero_fecha, f.estado,
            f.fecha_competencia, f.hora_inicio, f.hora_fin,
            f.id_ute_1, f.id_ute_2,
            u1.nombre_ute as ute_1_nombre, u2.nombre_ute as ute_2_nombre,
            l.nombre as lugar_nombre
        ', FALSE);
        // fixtures.resultado (podio masivo en JSON) solo si la columna existe.
        if ($this->_existe_columna_resultado()) {
            $this->db->select('f.resultado');
        }
        $this->db->from('fixtures f');
        $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
        $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->where('f.id_categoria', (int) $id_categoria);
        $this->db->order_by('numero_fecha, fecha_competencia, hora_inicio', 'ASC');
        $rows = $this->db->get()->result_array();

        // MASIVO_TIEMPO: adjuntar a cada jornada los competidores que participaron
        // (resueltos desde el fixture + las inscripciones_deportivas).
        if ($this->modalidad_de_categoria($id_categoria) === 'MASIVO_TIEMPO') {
            foreach ($rows as &$f) {
                $f['competidores'] = $this->obtener_participantes_de_jornada((int) $f['id_fixture']);
                // Adjuntar también el "podio" guardado (fixtures.resultado) para la vista.
                if ($this->_existe_columna_resultado() && !empty($f['resultado'])) {
                    $arr = json_decode($f['resultado'], true);
                    $f['resultado'] = is_array($arr) ? array_map('intval', $arr) : null;
                } else {
                    $f['resultado'] = null;
                }
            }
            unset($f);
        }

        return $rows;
    }

    /**
     * Participantes de una JORNADA_UNICA de deporte MASIVO_TIEMPO.
     * Va al fixture a buscar el partido/jornada y, además, busca en
     * inscripciones_deportivas a los participantes que compitieron en ese evento:
     *   - Podio cargado desde el fixture (fixtures.resultado, JSON con ids de
     *     llegada; >0 UTEs/equipos, <0 inscripciones personales).
     *   - Slots individuales de la jornada (id_ute_1/id_ute_2 negativos).
     *   - Si la jornada todavía no registró a nadie, se toman todos los
     *     inscriptos de la categoría desde inscripciones_deportivas (con su
     *     UTE si la inscripción está asociada a una), sin repetir competidores.
     * Devuelve array con: id (>0 UTE / <0 inscripción), tipo, nombre, dni,
     * delegacion y posicion (o null si aún no llegó).
     */
    public function obtener_participantes_de_jornada($id_fixture) {
        $this->db->where('id_fixture', (int) $id_fixture);
        $fx = $this->db->get('fixtures')->row_array();
        if (!$fx) return array();

        $id_cat = (int) $fx['id_categoria'];
        $orden = array();      // ids ya agregados (en orden de aparición)
        $out = array();

        // --- 1) Podio/orden de llegada guardado en el fixture (deportes masivos)
        if ($this->_existe_columna_resultado() && !empty($fx['resultado'])) {
            $arr = json_decode($fx['resultado'], true);
            if (is_array($arr)) {
                foreach ($arr as $pos => $id) {
                    $id = (int) $id;
                    if ($id === 0 || isset($orden[$id])) continue;
                    $orden[$id] = count($out);
                    $out[] = array(
                        'id' => $id, 'tipo' => null, 'nombre' => null,
                        'dni' => null, 'delegacion' => null,
                        'posicion' => (int) $pos + 1,
                    );
                }
            }
        }

        // --- 2) Slots individuales de la jornada (inscripciones personales: id negativo)
        foreach (array('id_ute_1', 'id_ute_2') as $campo) {
            $id = isset($fx[$campo]) ? (int) $fx[$campo] : 0;
            if ($id < 0 && !isset($orden[$id])) {
                $orden[$id] = count($out);
                $out[] = array(
                    'id' => $id, 'tipo' => null, 'nombre' => null,
                    'dni' => null, 'delegacion' => null, 'posicion' => null,
                );
            }
        }

        // --- 3) TODOS los competidores de la categoría, con el MISMO criterio
        // que obtener_competidores_por_categoria(): si hay UTEs creadas, cada
        // UTE es un competidor (con sus integrantes entre paréntesis); si no,
        // se listan los inscriptos individuales. Así la planilla por jornada
        // coincide exactamente con la lista general y nunca queda vacía.
        foreach ($this->obtener_competidores_por_categoria($id_cat) as $c) {
            $id = (int) $c['id'];
            if ($id === 0 || isset($orden[$id])) continue;
            $orden[$id] = count($out);
            $out[] = array(
                'id'          => $id,
                'tipo'        => $c['tipo'],
                'nombre'      => $c['nombre'],
                'dni'         => isset($c['dni']) ? $c['dni'] : null,
                'delegacion'  => isset($c['delegacion']) ? $c['delegacion'] : null,
                'integrantes' => isset($c['integrantes']) ? $c['integrantes'] : '',
                'posicion'    => null,
            );
        }

        // --- Resolver nombres/DNI/delegación de los ids sacados del fixture
        $ute_ids = array();
        $ins_ids = array();
        foreach ($out as $c) {
            if ($c['nombre'] !== null) continue; // ya viene resuelto (paso 3)
            if ($c['id'] > 0) $ute_ids[] = $c['id'];
            else $ins_ids[] = -$c['id'];
        }

        $nombres_ute = array();
        if ($ute_ids) {
            $this->db->select('id_ute, nombre_ute, id_categoria');
            $this->db->where_in('id_ute', array_unique($ute_ids));
            foreach ($this->db->get('utes')->result_array() as $u) {
                $nombres_ute[(int) $u['id_ute']] = $u;
            }
        }
        $nombres_ins = array();
        if ($ins_ids) {
            $this->db->select('i.id_inscripcion, i.id_ute, p.nombre_completo, p.dni, p.delegacion', FALSE);
            $this->db->from('inscripciones_deportivas i');
            $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
            $this->db->where_in('i.id_inscripcion', array_unique($ins_ids));
            foreach ($this->db->get()->result_array() as $r) {
                $nombres_ins[(int) $r['id_inscripcion']] = $r;
            }
        }

        foreach ($out as &$c) {
            if ($c['nombre'] !== null) continue;
            if ($c['id'] > 0) {
                $u = isset($nombres_ute[$c['id']]) ? $nombres_ute[$c['id']] : null;
                $c['tipo'] = 'EQUIPO';
                $c['nombre'] = $u ? $u['nombre_ute'] : ('UTE #' . $c['id']);
            } else {
                $r = isset($nombres_ins[-$c['id']]) ? $nombres_ins[-$c['id']] : null;
                $c['tipo'] = 'PERSONAL';
                $c['nombre'] = $r ? $r['nombre_completo'] : ('Inscripto #' . (-$c['id']));
                $c['dni'] = $r ? $r['dni'] : null;
                $c['delegacion'] = $r ? $r['delegacion'] : null;
            }
        }
        unset($c);

        return $out;
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fixture_model
 * Gestión del calendario/fixture de competencias:
 *  - Deportes MASIVO_TIEMPO (running, ciclismo, pesca...): una sola jornada.
 *  - Deportes ENFRENTAMIENTO + categorías MULTIDIA: bracket de eliminatoria directa.
 */
class Fixture_model extends CI_Model {

    const FASE_ORDEN = ['GRUPO', '16AVOS', 'OCTAVOS', 'CUARTOS', 'SEMIFINAL', 'TERCER_PUESTO', 'FINAL'];

    /* ============================================================
     *  CONSULTAS BÁSICAS
     * ============================================================ */

    /** Categorías con su deporte y lugar (para el selector del panel). */
    public function obtener_categorias_para_fixture() {
        $this->db->select('
            c.id_categoria, c.nombre_categoria, c.genero, c.tipo_torneo,
            c.dia_competencia, c.hora_competencia,
            d.id_deporte, d.nombre_deporte, d.modalidad_competencia, d.tipo_duracion,
            l.nombre as nombre_lugar
        ', FALSE);
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->join('lugares l', 'l.id = c.id_lugar', 'left');
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');
        return $this->db->get()->result_array();
    }

    /** IDs de categorías que YA tienen fixture generado (al menos un partido). */
    public function obtener_categorias_con_fixture() {
        $this->db->distinct();
        $this->db->select('id_categoria');
        $this->db->where('id_categoria IS NOT NULL', NULL, FALSE);
        return array_map('intval', array_column($this->db->get('fixtures')->result_array(), 'id_categoria'));
    }

    /** Categorías disponibles para generar fixture (las que aún NO tienen). */
    public function obtener_categorias_sin_fixture() {
        $con_fixture = $this->obtener_categorias_con_fixture();
        $todas = $this->obtener_categorias_para_fixture();

        if (!$con_fixture) {
            return $todas;
        }

        return array_values(array_filter($todas, function ($c) use ($con_fixture) {
            return !in_array((int) $c['id_categoria'], $con_fixture, TRUE);
        }));
    }

    /** Todas las UTEs de una categoría ordenadas alfabéticamente. */
    public function obtener_utes_por_categoria($id_categoria) {
        $this->db->where('id_categoria', $id_categoria);
        $this->db->order_by('nombre_ute', 'ASC');
        $utes = $this->db->get('utes')->result_array();

        return $utes;
    }


    /** Partidos (fixtures) de una categoría, con nombres resueltos. */
    public function obtener_fixtures_por_categoria($id_categoria) {
        $this->db->select('
            f.*,
            u1.nombre_ute as ute_1_nombre,
            u2.nombre_ute as ute_2_nombre,
            l.nombre as lugar_nombre,
            c.nombre_categoria,
            c.genero as genero_categoria,
            d.nombre_deporte,
            d.modalidad_competencia,
            d.tipo_duracion
        ', FALSE);
        $this->db->from('fixtures f');
        $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
        $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'left');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
        $this->db->where('f.id_categoria', $id_categoria);
        // Orden por HORARIO: primero fecha, luego la hora de inicio (y la jornada
        // como desempate) para que el fixture se lea cronológicamente.
        $this->db->order_by('f.fecha_competencia, f.hora_inicio, f.numero_fecha, f.id_fixture', 'ASC');
        $fixtures = $this->db->get()->result_array();

        // Adjuntar las UTEs de la categoría (necesario para el modal de
        // resultados de deportes masivos).
        $utes = $this->obtener_utes_por_categoria($id_categoria);
        foreach ($fixtures as &$f) {
            $f['utes_categoria'] = $utes;
        }
        unset($f);

        return $fixtures;
    }

    public function obtener_fixture_por_id($id_fixture) {
        $this->db->where('id_fixture', $id_fixture);
        return $this->db->get('fixtures')->row_array();
    }

    /** Un fixture con todos los datos contextuales (deporte/categoría/lugar/nombres). */
    public function obtener_fixture_completo_por_id($id_fixture) {
        $this->db->select('
            f.*,
            u1.nombre_ute as ute_1_nombre,
            u2.nombre_ute as ute_2_nombre,
            l.nombre as lugar_nombre,
            c.nombre_categoria,
            c.genero as genero_categoria,
            d.nombre_deporte,
            d.modalidad_competencia,
            d.tipo_duracion
        ', FALSE);
        $this->db->from('fixtures f');
        $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
        $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'left');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
        $this->db->where('f.id_fixture', (int) $id_fixture);
        return $this->db->get()->row_array();
    }

    /* ============================================================
     *  DETALLE DE PARTICIPANTES (modal de fixture)
     * ============================================================ */

    /**
     * Integrantes de una UTE (equipo/dupla), resueltos por id_ute.
     * Si la UTE no tiene integrantes registrados en participantes_utes,
     * se hace un fallback a las inscripciones de la categoría que la
     * mencionan en detalle_ute.
     */
    public function integrantes_de_ute($id_ute) {
        $id_ute = (int) $id_ute;
        if (!$id_ute) return array();

        $this->db->select('
            p.dni, p.nombre_completo, p.sexo, p.fecha_nacimiento,
            p.delegacion, pu.id_participante
        ', FALSE);
        $this->db->from('participantes_utes pu');
        $this->db->join('participantes p', 'p.id_participante = pu.id_participante', 'inner');
        $this->db->where('pu.id_ute', $id_ute);
        $this->db->order_by('p.nombre_completo', 'ASC');
        $integrantes = $this->db->get()->result_array();

        if ($integrantes) {
            return $this->_disciplinas_de_integrantes($integrantes);
        }

        // Fallback: inscriptos cuya inscripción referencia la UTE por nombre
        $this->db->select('nombre_ute, id_categoria');
        $this->db->where('id_ute', $id_ute);
        $ute = $this->db->get('utes')->row_array();
        if (!$ute) return array();

        $this->db->select('
            p.dni, p.nombre_completo, p.sexo, p.fecha_nacimiento,
            p.delegacion, i.id_participante
        ', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->where('i.id_categoria', $ute['id_categoria']);
        $this->db->where('TRIM(UPPER(i.detalle_ute))', strtoupper(trim($ute['nombre_ute'])));
        $this->db->order_by('p.nombre_completo', 'ASC');
        $integrantes = $this->db->get()->result_array();

        return $this->_disciplinas_de_integrantes($integrantes);
    }

    /** Datos de un competidor individual (slots negativos del fixture = -id_inscripcion). */
    public function competidor_individual($id_inscripcion) {
        $this->db->select(
            'p.dni, p.nombre_completo, p.sexo, p.fecha_nacimiento,
            p.delegacion, i.id_participante, i.id_categoria,
            c.nombre_categoria, d.nombre_deporte', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->join('categorias c', 'c.id_categoria = i.id_categoria', 'left');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
        $this->db->where('i.id_inscripcion', (int) $id_inscripcion);
        $fila = $this->db->get()->row_array();
        if (!$fila) return NULL;

        $fila['disciplinas'] = array($fila['nombre_deporte'] . ' — ' . $fila['nombre_categoria']);
        unset($fila['id_categoria'], $fila['nombre_categoria'], $fila['nombre_deporte']);
        return $fila;
    }

    /** Inscriptos de una categoría SIN UTE asignada (compiten como individuales). */
    public function individuales_de_categoria($id_categoria) {
        $this->db->select('
            p.dni, p.nombre_completo, p.sexo, p.fecha_nacimiento,
            p.delegacion, i.id_participante
        ', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->where('i.id_categoria', (int) $id_categoria);
        $this->db->where('(i.tiene_ute IS NULL OR i.tiene_ute = 0)', NULL, FALSE);
        $this->db->where("(i.detalle_ute IS NULL OR TRIM(i.detalle_ute) = '')", NULL, FALSE);
        $this->db->order_by('p.nombre_completo', 'ASC');
        $rows = $this->db->get()->result_array();

        return $this->_disciplinas_de_integrantes($rows);
    }

    /**
     * Detalle de participantes de un partido/jornada para el modal del fixture:
     *   - lado_1 y lado_2: con tipo (EQUIPO / INDIVIDUAL), datos y, si es equipo,
     *     sus integrantes resueltos desde ya (para que el modal abra al instante).
     *   - Si el deporte es MASIVO_TIEMPO y la jornada todavía no tiene slots,
     *     se devuelve la lista completa de competidores de la categoría
     *     (mismo criterio que usa el fixture al generarse: UTEs si existen,
     *     inscriptos individuales si no).
     */
    public function detalle_participantes_del_partido($id_fixture) {
        $fx = $this->obtener_fixture_completo_por_id($id_fixture);
        if (!$fx) return NULL;

        $es_masivo = ($fx['fase'] === 'JORNADA_UNICA'
                      || $fx['modalidad_competencia'] === 'MASIVO_TIEMPO');

        // Podio/orden de llegada guardado en fixtures.resultado (ids >0 UTE, <0 inscripción)
        $podio = array();
        if ($es_masivo && !empty($fx['resultado'])) {
            $arr = json_decode($fx['resultado'], true);
            if (is_array($arr)) {
                foreach ($arr as $pos => $id) {
                    $podio[] = array('posicion' => (int) $pos + 1, 'id' => (int) $id);
                }
            }
        }

        $respuesta = array(
            'fixture'    => $fx,
            'es_masivo'  => $es_masivo,
            'podio'      => $podio,
            'lado_1'   => $this->_responder_participante($fx['id_ute_1'], $fx['ute_1_nombre']),
            'lado_2'   => $this->_responder_participante($fx['id_ute_2'], $fx['ute_2_nombre']),
            'todos'      => array(),
        );

        // Deportes masivos sin slots asignados: mostrar TODOS los inscriptos de la categoría
        if ($es_masivo && empty($fx['id_ute_1']) && empty($fx['id_ute_2'])) {
            $this->load->model('Resultado_model');
            $competidores = $this->Resultado_model->obtener_competidores_por_categoria((int) $fx['id_categoria']);
            foreach ($competidores as $c) {
                $item = array(
                    'tipo'       => $c['tipo'] === 'EQUIPO' ? 'EQUIPO' : 'INDIVIDUAL',
                    'nombre'     => $c['nombre'],
                    'dni'        => isset($c['dni']) ? $c['dni'] : null,
                    'delegacion' => isset($c['delegacion']) ? $c['delegacion'] : null,
                    'integrantes' => array(),
                );
                if ($item['tipo'] === 'EQUIPO') {
                    $item['integrantes'] = $this->integrantes_de_ute((int) $c['id']);
                }
                $respuesta['todos'][] = $item;
            }
        }

        return $respuesta;
    }

    /** Resuelve un slot de fixture (>0 UTE / <0 inscripción individual). */
    private function _responder_participante($id_slot, $nombre_resuelto) {
        $id_slot = (int) $id_slot;
        if (!$id_slot) return NULL;

        if ($id_slot > 0) {
            return array(
                'tipo'        => 'EQUIPO',
                'nombre'      => $nombre_resuelto ?: ('UTE #' . $id_slot),
                'integrantes' => $this->integrantes_de_ute($id_slot),
            );
        }

        $individual = $this->competidor_individual(-$id_slot);
        if ($individual) {
            $individual['tipo'] = 'INDIVIDUAL';
            return $individual;
        }
        return array(
            'tipo'   => 'INDIVIDUAL',
            'nombre' => $nombre_resuelto ?: ('Inscripto #' . (-$id_slot)),
        );
    }

    /**
     * Lista simple de los inscriptos de una categoría (para el botón "ver
     * participantes" que aparece en cada bloque de categoría del fixture).
     * Agrupa por persona: si compiten en equipo, se muestra el equipo.
     */
    public function inscriptos_de_categoria($id_categoria) {
        $this->db->select('
            i.id_inscripcion, i.id_ute, i.detalle_ute, i.tiene_ute,
            p.dni, p.nombre_completo, p.sexo, p.fecha_nacimiento, p.delegacion
        ', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->where('i.id_categoria', (int) $id_categoria);
        $this->db->order_by('p.nombre_completo', 'ASC');
        $rows = $this->db->get()->result_array();

        // Nombres de las UTEs de la categoría (clave: id_ute)
        $nombres_ute = array();
        foreach ($this->obtener_utes_por_categoria((int) $id_categoria) as $u) {
            $nombres_ute[(int) $u['id_ute']] = $u['nombre_ute'];
        }

        // Integrantes reales por UTE (tabla participantes_utes), para saber
        // quiénes forman cada equipo además de los que están inscriptos acá.
        $por_ute = array();
        if ($nombres_ute) {
            $this->db->select('pu.id_ute, p.nombre_completo', FALSE);
            $this->db->from('participantes_utes pu');
            $this->db->join('participantes p', 'p.id_participante = pu.id_participante', 'inner');
            $this->db->where_in('pu.id_ute', array_keys($nombres_ute));
            $this->db->order_by('p.nombre_completo', 'ASC');
            foreach ($this->db->get()->result_array() as $r) {
                $por_ute[(int) $r['id_ute']][] = $r['nombre_completo'];
            }
        }

        $individuales = array();
        $equipos = array();   // clave: nombre del equipo
        foreach ($rows as $r) {
            $persona = array(
                'dni'         => $r['dni'],
                'nombre'      => $r['nombre_completo'],
                'sexo'        => $r['sexo'],
                'fecha_nacimiento' => $r['fecha_nacimiento'],
                'delegacion'  => $r['delegacion'],
            );
            $nombre_eq = null;
            if (!empty($r['id_ute']) && isset($nombres_ute[(int) $r['id_ute']])) {
                $nombre_eq = $nombres_ute[(int) $r['id_ute']];
            } elseif (!empty($r['detalle_ute']) && trim($r['detalle_ute']) !== '') {
                $nombre_eq = trim($r['detalle_ute']);
            }

            if ($nombre_eq !== null) {
                $equipos[$nombre_eq]['personas'][] = $persona;
            } else {
                $individuales[] = $persona;
            }
        }

        // Ordenar ambos grupos alfabéticamente
        ksort($equipos, SORT_NATURAL | SORT_FLAG_CASE);
        usort($individuales, function ($a, $b) {
            return strcasecmp($a['nombre'], $b['nombre']);
        });

        // Armar la respuesta: equipos con sus integrantes confirmados + inscriptos
        $out_equipos = array();
        foreach ($equipos as $nombre => $data) {
            $miembros = array();
            foreach ($data['personas'] as $p) $miembros[] = $p;
            // Si la UTE existe, agregar también los integrantes registrados que
            // no figuran como inscriptos directos en esta categoría
            $id_ute = array_search($nombre, $nombres_ute);
            if ($id_ute !== false) {
                $ya = array_column($miembros, 'nombre');
                foreach (isset($por_ute[$id_ute]) ? $por_ute[$id_ute] : array() as $nom) {
                    if (!in_array($nom, $ya, true)) {
                        $miembros[] = array(
                            'dni' => null, 'nombre' => $nom, 'sexo' => null,
                            'fecha_nacimiento' => null, 'delegacion' => null,
                        );
                    }
                }
            }
            $out_equipos[] = array('nombre' => $nombre, 'integrantes' => $miembros);
        }

        return array('equipos' => $out_equipos, 'individuales' => $individuales);
    }

    /** Agrega a cada fila la lista de disciplinas (deporte — categoría) del participante. */
    private function _disciplinas_de_integrantes($filas) {
        $ids = array();
        foreach ($filas as $f) {
            if (!empty($f['id_participante'])) $ids[(int) $f['id_participante']] = true;
        }
        if (!$ids) return $filas;

        $this->db->select('
            i.id_participante, d.nombre_deporte, c.nombre_categoria
        ', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('categorias c', 'c.id_categoria = i.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->where_in('i.id_participante', array_keys($ids));
        $disc = array();
        foreach ($this->db->get()->result_array() as $r) {
            $pid = (int) $r['id_participante'];
            $disc[$pid][] = $r['nombre_deporte'] . ' — ' . $r['nombre_categoria'];
        }
        foreach ($filas as &$f) {
            $pid = (int) $f['id_participante'];
            $f['disciplinas'] = isset($disc[$pid]) ? array_values(array_unique($disc[$pid])) : array();
        }
        unset($f);
        return $filas;
    }


    /**
     * Devuelve TODO el fixture de todas las categorías (vista general sin filtros).
     * Opciones:
     *  - $id_deporte (> 0): filtra por deporte.
     *  - $orden ('horario' | 'deporte'): cómo se agrupa/ordena el listado.
     *    'horario' ordena por Fecha → Hora → Deporte → Categoría (para ver el
     *    día a día cronológicamente); 'deporte' (por defecto) mantiene el orden
     *    Deporte → Categoría → Fecha → Hora.
     */
    public function obtener_todo_el_fixture($id_deporte = null, $orden = 'deporte') {
        $this->db->select('
            f.*,
            u1.nombre_ute as ute_1_nombre,
            u2.nombre_ute as ute_2_nombre,
            l.nombre as lugar_nombre,
            c.nombre_categoria,
            c.genero as genero_categoria,
            d.nombre_deporte,
            d.modalidad_competencia,
            d.tipo_duracion
        ', FALSE);
        $this->db->from('fixtures f');
        $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
        $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'left');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
        // Filtro opcional por deporte (para el reporte de fixture filtrado).
        if ($id_deporte !== null && (int) $id_deporte > 0) {
            $this->db->where('d.id_deporte', (int) $id_deporte);
        }
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria, f.fecha_competencia, f.hora_inicio, f.numero_fecha', 'ASC');

        $fixtures = $this->db->get()->result_array();
        unset($f);

        // Orden por HORARIO: se reordena cronológicamente (Fecha → Hora) y, si
        // el usuario lo pide, se agrupa por deporte/categoría dentro de cada franja.
        if ($orden === 'horario') {
            usort($fixtures, function ($a, $b) {
                return [
                    (string) ($a['fecha_competencia'] ?? ''),
                    (string) ($a['hora_inicio'] ?? ''),
                    strtolower((string) ($a['nombre_deporte'] ?? '')),
                    strtolower((string) ($a['nombre_categoria'] ?? '')),
                    (int) ($a['numero_fecha'] ?? 0),
                ] <=> [
                    (string) ($b['fecha_competencia'] ?? ''),
                    (string) ($b['hora_inicio'] ?? ''),
                    strtolower((string) ($b['nombre_deporte'] ?? '')),
                    strtolower((string) ($b['nombre_categoria'] ?? '')),
                    (int) ($b['numero_fecha'] ?? 0),
                ];
            });
        }

        // Resolver nombres de competidores individuales (ids negativos del podio
        // masivo y slots libres del fixture, p. ej. running donde corre cada uno).
        $nombres_ind = $this->_nombres_individuales_en_fixtures($fixtures);
        foreach ($fixtures as &$f) {
            if (empty($f['ute_1_nombre']) && !empty($f['id_ute_1'])) {
                $f['ute_1_nombre'] = isset($nombres_ind[(int) $f['id_ute_1']])
                    ? $nombres_ind[(int) $f['id_ute_1']] : null;
            }
            if (empty($f['ute_2_nombre']) && !empty($f['id_ute_2'])) {
                $f['ute_2_nombre'] = isset($nombres_ind[(int) $f['id_ute_2']])
                    ? $nombres_ind[(int) $f['id_ute_2']] : null;
            }
        }
        unset($f);

        return $fixtures;
    }

    /**
     * Fixture filtrado por delegación (reporte del delegado).
     *
     * Un partido entra al reporte cuando la delegación participa en él, ya sea:
     *   - como integrante de una UTE (participantes_utes), o
     *   - como competidor individual (inscripciones_deportivas con id_ute = -id_inscripcion), o
     *   - como parte de la jornada masiva del partido (MASIVO_TIEMPO / JORNADA_UNICA),
     *     donde los inscriptos de la categoría compiten aunque todavía no tengan slot.
     *
     * $id_deporte (opcional) limita además el reporte a un deporte; si el
     * deporte elegido no tiene partidos de la delegación, el resultado es vacío.
     */
    public function obtener_fixture_por_delegacion($delegacion, $id_deporte = null) {
        $delegacion = trim((string) $delegacion);
        if ($delegacion === '') return array();

        $this->db->select('
            f.*,
            u1.nombre_ute as rival_ute_1_nombre,
            u2.nombre_ute as rival_ute_2_nombre,
            l.nombre as lugar_nombre,
            c.nombre_categoria,
            c.genero as genero_categoria,
            d.nombre_deporte,
            d.modalidad_competencia,
            d.tipo_duracion
        ', FALSE);
        $this->db->from('fixtures f');
        // Nombres crudos de las UTEs enfrentadas: se conservan en el reporte del
        // delegado SOLO para el lado rival (el lado propio muestra participantes).
        $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
        $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'left');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');

        // Partido "propio" de la delegación por alguno de los tres caminos.
        $cond_equipo = "EXISTS (
            SELECT 1 FROM participantes_utes pu
            INNER JOIN participantes p ON p.id_participante = pu.id_participante
            WHERE pu.id_ute IN (f.id_ute_1, f.id_ute_2)
              AND p.delegacion = " . $this->db->escape($delegacion) . ")";

        $cond_individual = "EXISTS (
            SELECT 1 FROM inscripciones_deportivas i
            INNER JOIN participantes p ON p.id_participante = i.id_participante
            WHERE i.id_categoria = f.id_categoria
              AND (-i.id_inscripcion) IN (f.id_ute_1, f.id_ute_2)
              AND p.delegacion = " . $this->db->escape($delegacion) . ")";

        $cond_masivo = "(
            (f.fase = 'JORNADA_UNICA' OR d.modalidad_competencia = 'MASIVO_TIEMPO')
            AND EXISTS (
                SELECT 1 FROM inscripciones_deportivas i
                INNER JOIN participantes p ON p.id_participante = i.id_participante
                WHERE i.id_categoria = f.id_categoria
                  AND p.delegacion = " . $this->db->escape($delegacion) . ")
        )";

        $this->db->where('(' . $cond_equipo . ' OR ' . $cond_individual . ' OR ' . $cond_masivo . ')', NULL, FALSE);

        if ($id_deporte !== null && (int) $id_deporte > 0) {
            $this->db->where('d.id_deporte', (int) $id_deporte);
        }

        $this->db->order_by('d.nombre_deporte, c.nombre_categoria, f.fecha_competencia, f.hora_inicio, f.numero_fecha', 'ASC');
        $fixtures = $this->db->get()->result_array();

        // Nombre de las UTEs enfrentadas (siempre se muestra el equipo rival).
        $nombres_utes = array();
        $ids_ute = array();
        foreach ($fixtures as $fx) {
            if (!empty($fx['id_ute_1']) && (int) $fx['id_ute_1'] > 0) $ids_ute[] = (int) $fx['id_ute_1'];
            if (!empty($fx['id_ute_2']) && (int) $fx['id_ute_2'] > 0) $ids_ute[] = (int) $fx['id_ute_2'];
        }
        $ids_ute = array_values(array_unique($ids_ute));
        if ($ids_ute) {
            $this->db->select('id_ute, nombre_ute', FALSE);
            $this->db->where_in('id_ute', $ids_ute);
            foreach ($this->db->get('utes')->result_array() as $u) {
                $nombres_utes[(int) $u['id_ute']] = $u['nombre_ute'];
            }
        }

        $comp_delegacion = $this->_competidores_de_delegacion($delegacion);

        // Integrantes propios de cada UTE de la delegación (clave: id_ute), para
        // que el delegado vea los nombres de sus jugadores en cada partido.
        $utes_propias = array();
        foreach ($fixtures as $fx) {
            foreach (array('id_ute_1', 'id_ute_2') as $campo) {
                $idl = isset($fx[$campo]) ? (int) $fx[$campo] : 0;
                if ($idl > 0 && !isset($utes_propias[$idl])) {
                    $l = $this->_describir_lado_del_partido($fx, $campo, $nombres_utes, $comp_delegacion);
                    if (!empty($l['es_delegacion'])) $utes_propias[$idl] = true;
                }
            }
        }
        $integrantes_ute = $this->_integrantes_de_utes(array_keys($utes_propias));

        foreach ($fixtures as &$f) {
            $lado1 = $this->_describir_lado_del_partido($f, 'id_ute_1', $nombres_utes, $comp_delegacion);
            $lado2 = $this->_describir_lado_del_partido($f, 'id_ute_2', $nombres_utes, $comp_delegacion);

            $idl1 = isset($f['id_ute_1']) ? (int) $f['id_ute_1'] : 0;
            $idl2 = isset($f['id_ute_2']) ? (int) $f['id_ute_2'] : 0;

            // Lado propio: se envía el nombre CRUDO de la UTE (sin el prefijo
            // "Equipo: ...") porque el reporte del delegado muestra únicamente
            // los participantes. El lado rival conserva el nombre del equipo.
            // Si el partido es individual o de jornada masiva ($lado['nombre']
            // ya trae los nombres resueltos y no hay UTE real), se conserva ese
            // nombre para que el reporte nunca quede vacío.
            $f['ute_1_nombre'] = $this->_nombre_lado_reporte(
                $lado1, $idl1 > 0 && $lado1['es_delegacion'] && isset($nombres_utes[$idl1])
                    ? $nombres_utes[$idl1] : $lado1['nombre']
            );
            $f['ute_2_nombre'] = $this->_nombre_lado_reporte(
                $lado2, $idl2 > 0 && $lado2['es_delegacion'] && isset($nombres_utes[$idl2])
                    ? $nombres_utes[$idl2] : $lado2['nombre']
            );
            $f['delegacion_en_ute_1'] = $lado1['es_delegacion'];
            $f['delegacion_en_ute_2'] = $lado2['es_delegacion'];

            // Rivales: si del otro lado hay una UTE de otra delegación, se envía
            // su nombre para poder mostrar "Equipo A vs Equipo B" en el reporte.
            $f['rival_ute_1_nombre'] = (!$lado1['es_delegacion'] && $idl1 > 0 && isset($nombres_utes[$idl1]))
                ? $nombres_utes[$idl1] : NULL;
            $f['rival_ute_2_nombre'] = (!$lado2['es_delegacion'] && $idl2 > 0 && isset($nombres_utes[$idl2]))
                ? $nombres_utes[$idl2] : NULL;

            // Flag del modo delegado: la vista (reporte_fixture) lo usa para
            // decidir si cada lado muestra participantes o el nombre del equipo.
            $f['es_reporte_delegado'] = TRUE;

            // Integrantes de la delegación que juegan ese partido por cada lado.
            $jug1 = ($lado1['es_delegacion'] && isset($integrantes_ute[$idl1])) ? $integrantes_ute[$idl1] : array();
            $jug2 = ($lado2['es_delegacion'] && isset($integrantes_ute[$idl2])) ? $integrantes_ute[$idl2] : array();
            $f['jugadores_ute_1'] = $this->_fusionar_nombres_con_inscriptos(
                $jug1, $this->_inscriptos_de_ute_en_categoria($idl1, isset($f['id_categoria']) ? (int) $f['id_categoria'] : 0, $delegacion)
            );
            $f['jugadores_ute_2'] = $this->_fusionar_nombres_con_inscriptos(
                $jug2, $this->_inscriptos_de_ute_en_categoria($idl2, isset($f['id_categoria']) ? (int) $f['id_categoria'] : 0, $delegacion)
            );

            // Safety net: si el lado es propio pero quedó sin lista de jugadores
            // (p. ej. inscriptos reconocidos solo por detalle_ute), se extraen
            // los nombres del texto "NombreEquipo: J1, J2" que devolvió
            // _describir_lado_del_partido, para que el reporte SIEMPRE muestre
            // participantes y nunca el nombre del equipo suelto.
            if ($lado1['es_delegacion'] && !$f['jugadores_ute_1']) {
                $f['jugadores_ute_1'] = $this->_participantes_desde_texto_lado($lado1['nombre']);
            }
            if ($lado2['es_delegacion'] && !$f['jugadores_ute_2']) {
                $f['jugadores_ute_2'] = $this->_participantes_desde_texto_lado($lado2['nombre']);
            }
        }
        unset($f);

        return $fixtures;
    }

    /**
     * Nombre del lado del partido para el reporte del delegado:
     *   - equipo propio  → nombre crudo de la UTE, SIN el prefijo "Equipo: J1, J2"
     *     (los participantes se listan aparte en jugadores_ute_X);
     *   - individual / jornada masiva → se conservan los nombres ya resueltos.
     */
    private function _nombre_lado_reporte($lado, $nombre_crudo) {
        if (!empty($lado['es_delegacion']) && $nombre_crudo !== NULL && $nombre_crudo !== '') {
            return $nombre_crudo;
        }
        return $lado['nombre'];
    }

    /**
     * Extrae la lista de participantes del texto que arma
     * _describir_lado_del_partido para un lado propio:
     *   "NombreEquipo: Juan Pérez, Ana Gómez"  →  ["Juan Pérez", "Ana Gómez"]
     * Se usa como respaldo cuando jugadores_ute_X quedó vacío, para que el
     * reporte del delegado nunca imprima el nombre del equipo suelto.
     */
    private function _participantes_desde_texto_lado($texto) {
        $texto = trim((string) $texto);
        if ($texto === '') return array();
        $pos = strpos($texto, ': ');
        if ($pos === FALSE) return array();
        $lista = preg_split('/\s*,\s*/', substr($texto, $pos + 2), -1, PREG_SPLIT_NO_EMPTY);
        return $this->_fusionar_nombres_con_inscriptos(array(), (array) $lista);
    }

    /** Nombres completos de los integrantes registrados de varias UTEs (clave: id_ute). */
    private function _integrantes_de_utes($ids_ute) {
        $ids_ute = array_values(array_unique(array_filter(array_map('intval', (array) $ids_ute))));
        if (!$ids_ute) return array();

        $this->db->select('pu.id_ute, p.nombre_completo', FALSE);
        $this->db->from('participantes_utes pu');
        $this->db->join('participantes p', 'p.id_participante = pu.id_participante', 'inner');
        $this->db->where_in('pu.id_ute', $ids_ute);
        $this->db->order_by('p.nombre_completo', 'ASC');

        $por_ute = array();
        foreach ($this->db->get()->result_array() as $r) {
            $por_ute[(int) $r['id_ute']][] = $r['nombre_completo'];
        }
        return $por_ute;
    }

    /**
     * Inscriptos de la delegación vinculados a una UTE de la categoría, aun
     * cuando todavía no estén dados de alta en participantes_utes (se los
     * reconoce por inscripcion.id_ute o por el nombre guardado en detalle_ute).
     */
    private function _inscriptos_de_ute_en_categoria($id_ute, $id_categoria, $delegacion) {
        $id_ute = (int) $id_ute;
        $id_categoria = (int) $id_categoria;
        if ($id_ute <= 0 || $id_categoria <= 0) return array();

        $this->db->select('nombre_ute', FALSE);
        $this->db->where('id_ute', $id_ute);
        $ute = $this->db->get('utes')->row_array();
        if (!$ute) return array();
        $nombre_ute = strtoupper(trim((string) $ute['nombre_ute']));

        $this->db->select('p.nombre_completo', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->where('i.id_categoria', $id_categoria);
        $this->db->where('p.delegacion', $delegacion);
        $this->db->group_start();
        $this->db->where('i.id_ute', $id_ute);
        if ($nombre_ute !== '') {
            $this->db->or_where('TRIM(UPPER(i.detalle_ute))', $nombre_ute);
        }
        $this->db->group_end();
        $this->db->order_by('p.nombre_completo', 'ASC');

        return array_column($this->db->get()->result_array(), 'nombre_completo');
    }

    /** Une dos listas de nombres sin repetir personas (comparación flexible). */
    private function _fusionar_nombres_con_inscriptos($principales, $adicionales) {
        $fuera = array();
        $out = array();
        foreach (array_merge((array) $principales, (array) $adicionales) as $n) {
            $n = trim((string) $n);
            if ($n === '') continue;
            $clave = preg_replace('/\s+/', ' ', mb_strtoupper($n, 'UTF-8'));
            if (isset($fuera[$clave])) continue;
            $fuera[$clave] = true;
            $out[] = $n;
        }
        sort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return $out;
    }

    /** Competidores inscriptos de la delegación, agrupados por categoría. */
    private function _competidores_de_delegacion($delegacion) {
        $this->db->select('
            i.id_inscripcion, i.id_categoria, i.id_ute, i.detalle_ute,
            p.nombre_completo, p.dni
        ', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->where('p.delegacion', $delegacion);
        $rows = $this->db->get()->result_array();

        $por_categoria = array();
        foreach ($rows as $r) {
            $id_cat = (int) $r['id_categoria'];
            if (!isset($por_categoria[$id_cat])) $por_categoria[$id_cat] = array();
            $por_categoria[$id_cat][] = array(
                'id_inscripcion' => (int) $r['id_inscripcion'],
                'id_ute'         => $r['id_ute'] !== NULL ? (int) $r['id_ute'] : NULL,
                'detalle_ute'    => trim((string) $r['detalle_ute']),
                'nombre'         => $r['nombre_completo'],
                'dni'            => $r['dni'],
            );
        }
        return $por_categoria;
    }

    /**
     * Descripción de un lado del partido (UTE / individual / jornada masiva)
     * mostrando únicamente los competidores de la delegación del delegado.
     * Devuelve ['nombre' => string|null, 'es_delegacion' => bool].
     */
    private function _describir_lado_del_partido($f, $campo_ute, $nombres_utes, $comp_delegacion) {
        $id_lado = isset($f[$campo_ute]) ? (int) $f[$campo_ute] : 0;
        $id_cat  = isset($f['id_categoria']) ? (int) $f['id_categoria'] : 0;
        $lista   = isset($comp_delegacion[$id_cat]) ? $comp_delegacion[$id_cat] : array();

        // Slot pendiente (ej. "Ganador Llave 1"): sin delegación posible.
        if ($id_lado === 0) {
            return array('nombre' => NULL, 'es_delegacion' => FALSE);
        }

        // Individual: el slot negativo es la inscripción de un competidor.
        if ($id_lado < 0) {
            foreach ($lista as $c) {
                if ($c['id_inscripcion'] === -$id_lado) {
                    return array('nombre' => $c['nombre'] . ' (' . $c['dni'] . ')', 'es_delegacion' => TRUE);
                }
            }
            return array('nombre' => NULL, 'es_delegacion' => FALSE);
        }

        // UTE real: se listan solo los integrantes de esta delegación.
        $propios = array();
        foreach ($lista as $c) {
            if ($c['id_ute'] === $id_lado) $propios[] = $c['nombre'];
        }
        // Fallback histórico: inscripciones sin id_ute que citan la UTE por nombre.
        if (!$propios) {
            $nombre_ute = isset($nombres_utes[$id_lado]) ? $nombres_utes[$id_lado] : '';
            if ($nombre_ute !== '') {
                foreach ($lista as $c) {
                    if ($c['id_ute'] === NULL
                        && strcasecmp($c['detalle_ute'], $nombre_ute) === 0) {
                        $propios[] = $c['nombre'];
                    }
                }
            }
        }
        if ($propios) {
            sort($propios);
            return array(
                'nombre'        => (isset($nombres_utes[$id_lado]) ? $nombres_utes[$id_lado] : 'Equipo')
                                   . ': ' . implode(', ', $propios),
                'es_delegacion' => TRUE,
            );
        }

        // Jornada masiva: compiten todos los inscriptos de la categoría,
        // incluidos los de la delegación (aunque aún no tengan slot asignado).
        $es_masivo = (($f['fase'] ?? '') === 'JORNADA_UNICA'
                      || ($f['modalidad_competencia'] ?? '') === 'MASIVO_TIEMPO');
        if ($es_masivo) {
            if (!$lista) return array('nombre' => NULL, 'es_delegacion' => FALSE);
            $nombres = array();
            foreach ($lista as $c) $nombres[] = $c['nombre'] . ' (' . $c['dni'] . ')';
            sort($nombres);
            return array('nombre' => implode(', ', $nombres), 'es_delegacion' => TRUE);
        }

        return array('nombre' => NULL, 'es_delegacion' => FALSE);
    }

    /** Nombres de inscripciones individuales usadas en fixtures (clave: id negativo). */
    private function _nombres_individuales_en_fixtures(&$fixtures) {
        $nombres = array();
        /*if (!$this->_existe_columna_resultado()) {
            return $nombres;
        }*/
        $ids_neg = array();
        foreach ($fixtures as $f) {
            if (!empty($f['id_ute_1']) && (int) $f['id_ute_1'] < 0) $ids_neg[] = -(int) $f['id_ute_1'];
            if (!empty($f['id_ute_2']) && (int) $f['id_ute_2'] < 0) $ids_neg[] = -(int) $f['id_ute_2'];
            if (!empty($f['resultado'])) {
                $arr = json_decode($f['resultado'], true);
                if (is_array($arr)) {
                    foreach ($arr as $id) {
                        if ((int) $id < 0) $ids_neg[] = -(int) $id;
                    }
                }
            }
        }
        $ids_neg = array_values(array_unique($ids_neg));
        if (!$ids_neg) return $nombres;

        $this->db->select('i.id_inscripcion, p.dni, p.nombre_completo', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->where_in('i.id_inscripcion', $ids_neg);
        $rows = $this->db->get()->result_array();
        foreach ($rows as $r) {
            $nombres[-(int) $r['id_inscripcion']] = $r['nombre_completo'] . ' (' . $r['dni'] . ')';
        }
        return $nombres;
    }

    /* ============================================================
     *  GENERACIÓN AUTOMÁTICA: DADA DE BAJA
     * ============================================================ */

    /**
     * La generación automática de fixtures fue eliminada del sistema.
     * El fixture ahora se arma SIEMPRE a mano desde el panel ("Nuevo partido")
     * y las jornadas masivas se crean como un partido con fase JORNADA_UNICA.
     * Este método existe solo para responder con un mensaje claro ante
     * llamadas antiguas (bookmarks, scripts, etc.).
     */
    public function generar_fixture_para_categoria($id_categoria) {
        throw new Exception(
            'La generación automática de fixture está deshabilitada. '
            . 'Armá el fixture manualmente desde el panel: botón "Nuevo partido".'
        );
    }

    /* ============================================================
     *  EDICIÓN MANUAL / RESULTADOS
     * ============================================================ */

    /** Fases válidas (coinciden con el ENUM de la tabla fixtures). */
    private function _fases_validas() {
        return array('GRUPO', '16AVOS', 'OCTAVOS', 'CUARTOS', 'SEMIFINAL',
                     'TERCER_PUESTO', 'FINAL', 'JORNADA_UNICA');
    }

    /** Crear/editar un partido manualmente. */
    public function guardar_partido($datos) {
        // --- Validaciones defensivas: cualquier valor inválido habría hecho
        // explotar el INSERT/UPDATE contra el ENUM o una FK (HTTP 500). ---

        // Fase: debe existir y ser una de las del ENUM (si viene vacía o
        // inválida, se usa GRUPO en vez de reventar).
        $fase = strtoupper(trim(isset($datos['fase']) ? $datos['fase'] : ''));
        if ($fase === '') $fase = 'GRUPO';
        if (!in_array($fase, $this->_fases_validas(), true)) {
            throw new Exception('Fase inválida: ' . $fase);
        }

        // Categoría: tiene FK; verificar que exista.
        $id_cat = (int) $datos['id_categoria'];
        $this->db->where('id_categoria', $id_cat);
        if (!$this->db->get('categorias')->num_rows()) {
            throw new Exception('La categoría seleccionada no existe.');
        }

        // id_lugar es NOT NULL con FK: si el formulario no envía lugar (o
        // manda 0), se usa el primer lugar cargado. Sin esto, el INSERT
        // revienta con error SQL (HTTP 500).
        $lugar = !empty($datos['id_lugar']) ? (int) $datos['id_lugar'] : $this->_primer_lugar();
        if (!$lugar) {
            throw new Exception('No hay lugares cargados. Creá al menos un lugar antes de guardar partidos.');
        }
        $this->db->where('id', $lugar);
        if (!$this->db->get('lugares')->num_rows()) {
            throw new Exception('El lugar seleccionado no existe.');
        }

        // Fechas/horas: normalizar a formatos que MySQL acepta.
        $fecha = trim($datos['fecha_competencia']);
        $ts = strtotime($fecha);
        if (!$ts) throw new Exception('Fecha inválida: ' . $fecha);
        $fecha = date('Y-m-d', $ts);

        $h_ini = trim($datos['hora_inicio']);
        $h_fin = trim($datos['hora_fin']);
        if (!preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $h_ini) || !preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $h_fin)) {
            throw new Exception('Horario inválido.');
        }
        $h_ini = strlen($h_ini) === 5 ? $h_ini . ':00' : $h_ini;
        $h_fin = strlen($h_fin) === 5 ? $h_fin . ':00' : $h_fin;

        // Equipos: pueden ir vacíos ('' -> NULL). Si vienen con valor, validar
        // que existan (ids negativos = competidores individuales, no se validan).
        $ute1 = isset($datos['id_ute_1']) && $datos['id_ute_1'] !== '' ? (int) $datos['id_ute_1'] : null;
        $ute2 = isset($datos['id_ute_2']) && $datos['id_ute_2'] !== '' ? (int) $datos['id_ute_2'] : null;
        foreach (array($ute1, $ute2) as $u) {
            if ($u !== null && $u > 0) {
                $this->db->where('id_ute', $u);
                if (!$this->db->get('utes')->num_rows()) {
                    throw new Exception('Uno de los equipos seleccionados no existe.');
                }
            }
        }

        $payload = array(
            'id_categoria'      => $id_cat,
            'id_lugar'          => $lugar,
            'id_ute_1'          => $ute1,
            'id_ute_2'          => $ute2,
            'nombre_prueba'     => trim($datos['nombre_prueba']),
            'fase'              => $fase,
            'numero_fecha'      => !empty($datos['numero_fecha']) ? max(1, (int) $datos['numero_fecha']) : 1,
            'fecha_competencia' => $fecha,
            'hora_inicio'       => $h_ini,
            'hora_fin'          => $h_fin,
            'estado'            => !empty($datos['estado']) ? $datos['estado'] : 'PROGRAMADO',
        );

        if (!empty($datos['id_fixture'])) {
            $this->db->where('id_fixture', (int) $datos['id_fixture']);
            $this->db->update('fixtures', $payload);
            return (int) $datos['id_fixture'];
        }

        $this->db->insert('fixtures', $payload);
        return $this->db->insert_id();
    }

    /** ¿Existe la columna fixtures.resultado? (requiere el ALTER del sql provisto). */
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
     * Buscar la instancia (partido) siguiente donde clasifica el ganador.
     * Se basa en el ORDEN DE FASES (GRUPO -> 16AVOS -> OCTAVOS -> CUARTOS ->
     * SEMIFINAL -> TERCER_PUESTO -> FINAL), NO en numero_fecha, porque los
     * partidos creados manualmente pueden compartir o invertir las fechas.
     *
     * Ejemplo: el ganador de una SEMIFINAL pasa al primer hueco libre de la
     * FINAL de la misma categoría. Con 2 semis, la semi 1 ocupa id_ute_1 y
     * la semi 2 ocupa id_ute_2 de la final.
     *
     * @return array|null array('fixture' => fila_destino, 'campo' => 'id_ute_1'|'id_ute_2') o NULL si no hay siguiente.
     */
    public function buscar_siguiente_instancia($partido) {
        $fase_actual = $partido['fase'];

        if ($fase_actual === 'FINAL' || $fase_actual === 'TERCER_PUESTO') {
            return null;
        }

        $pos_fase = array_search($fase_actual, self::FASE_ORDEN);
        if ($pos_fase === false || $pos_fase === count(self::FASE_ORDEN) - 1) {
            return null; 
        }

        $hueco = $this->buscar_hueco_en_fases_superiores($partido['id_categoria'], $pos_fase + 1);
        if ($hueco !== null) {
            return $hueco;
        }

        return null;
    }
    
    /**
     * Verifica si el partido actual pertenece a la fase inmediatamente previa a la FINAL.
     */
    private function _es_fase_previa_a_final($partido) {
        // 1. Si la fase actual es explícitamente SEMIFINAL
        if ($partido['fase'] === 'SEMIFINAL') {
            return true;
        }

        // 2. Si es fase GRUPO (o cualquier otra), comprobamos si la fase "Siguiente" en el orden 
        // corresponde a la FINAL o si directamente existe un partido de FINAL creado para esta categoría.
        $this->db->where('id_categoria', $partido['id_categoria']);
        $this->db->where('fase', 'FINAL');
        $partido_final = $this->db->get('fixtures')->row_array();

        if (!$partido_final) {
            return false;
        }

        // Si la fase actual es la inmediatamente anterior a la FINAL según el torneo
        // (Ejemplo: si la fase actual es GRUPO y la siguiente ronda creada en la BD es la FINAL)
        $fases_torneo = array('GRUPO', '16AVOS', 'OCTAVOS', 'CUARTOS', 'SEMIFINAL', 'FINAL');
        $pos_actual = array_search($partido['fase'], $fases_torneo);
        $pos_final  = array_search('FINAL', $fases_torneo);

        // Si la diferencia entre la posición actual y la final es de 1 paso (es decir, es la previa)
        // O si en la BD solo existen GRUPO y FINAL como rondas registradas.
        if ($pos_actual !== false && $pos_final !== false) {
            // Consultamos qué fases existen en la BD para esta categoría
            $this->db->select('DISTINCT(fase) as fase');
            $this->db->where('id_categoria', $partido['id_categoria']);
            $fases_existentes = array_column($this->db->get('fixtures')->result_array(), 'fase');

            // Si la fase del partido actual es la penúltima fase existente en la BD
            $pos_en_existentes = array_search($partido['fase'], $fases_existentes);
            $pos_final_existente = array_search('FINAL', $fases_existentes);

            if ($pos_en_existentes !== false && $pos_final_existente !== false) {
                return ($pos_en_existentes === $pos_final_existente - 1);
            }
        }

        return false;
    }

    public function buscar_instancia_tercer_puesto($id_categoria) {
        $this->db->where('id_categoria', $id_categoria);
        $this->db->where('fase', 'TERCER_PUESTO');
        $partido_tercer = $this->db->get('fixtures')->row_array();

        if (!$partido_tercer) {
            return null;
        }

        // Comprobamos estrictamente si el campo está vacío, nulo o es cero/cadena vacía
        if (empty($partido_tercer['id_ute_1'])) {
            return array('fixture' => $partido_tercer, 'campo' => 'id_ute_1');
        } elseif (empty($partido_tercer['id_ute_2'])) {
            return array('fixture' => $partido_tercer, 'campo' => 'id_ute_2');
        }

        return null; // Ya están ocupados ambos casilleros
    }

    /**
     * Recorrer las fases superiores (desde el índice indicado hacia la FINAL)
     * buscando el primer partido pendiente con un slot libre.
     *
     * @param string $id_categoria
     * @param int    $desde_idx   índice inicial dentro de FASE_ORDEN
     * @return array|null array('fixture' => fila, 'campo' => ...) o NULL.
     */
    private function buscar_hueco_en_fases_superiores($id_categoria, $desde_idx) {
        for ($i = $desde_idx; $i < count(self::FASE_ORDEN); $i++) {
            $fase = self::FASE_ORDEN[$i];

            // Excluir TERCER_PUESTO como destino de clasificación.
            if ($fase === 'TERCER_PUESTO') {
                continue;
            }

            $partidos = $this->db->where('id_categoria', $id_categoria)
                                 ->where('fase', $fase)
                                 ->order_by('numero_fecha', 'ASC')
                                 ->order_by('id_fixture', 'ASC')
                                 ->get('fixtures')->result_array();

            foreach ($partidos as $fx) {
                if (empty($fx['id_ute_1'])) {
                    return array('fixture' => $fx, 'campo' => 'id_ute_1');
                }
                if (empty($fx['id_ute_2'])) {
                    return array('fixture' => $fx, 'campo' => 'id_ute_2');
                }
            }
        }
        return null;
    }

    /**
     * Registrar resultado: ganador pasa a la siguiente fecha de la misma categoría.
     */
    public function registrar_resultado($id_fixture, $id_ganador) {
        $partido = $this->obtener_fixture_por_id($id_fixture);
        if (!$partido) {
            throw new Exception('Partido no encontrado.');
        }
        if ($partido['fase'] === 'JORNADA_UNICA') {
            throw new Exception('Los deportes de jornada única no generan clasificados.');
        }

        // Validar que el ganador sea uno de los dos participantes del partido
        if (!in_array((int) $id_ganador, array((int) $partido['id_ute_1'], (int) $partido['id_ute_2']), true)) {
            throw new Exception('El ganador seleccionado no corresponde a este partido.');
        }

        // Obtener ID del perdedor
        $id_perdedor = ((int) $id_ganador === (int) $partido['id_ute_1']) 
            ? $partido['id_ute_2'] 
            : $partido['id_ute_1'];

        // Marcar partido como finalizado
        $this->db->where('id_fixture', $id_fixture);
        $this->db->update('fixtures', array('estado' => 'FINALIZADO'));

        if ($partido['fase'] === 'FINAL' || $partido['fase'] === 'TERCER_PUESTO') {
            return 'Resultado registrado. No hay instancia superior.';
        }

        // 1. EL GANADOR SIEMPRE AVANZA A LA SIGUIENTE INSTANCIA
        $siguiente = $this->buscar_siguiente_instancia($partido);
        if ($siguiente) {
            $this->db->where('id_fixture', $siguiente['fixture']['id_fixture']);
            $this->db->update('fixtures', array($siguiente['campo'] => $id_ganador));
        }

        // 2. EL PERDEDOR SOLO AVANZA AL TERCER PUESTO SI VIENE DE LA INSTANCIA PREVIA A LA FINAL
        if ($id_perdedor !== null && $this->_es_fase_previa_a_final($partido)) {
            $siguiente_tercer = $this->buscar_instancia_tercer_puesto($partido['id_categoria']);
            if ($siguiente_tercer) {
                $this->db->where('id_fixture', $siguiente_tercer['fixture']['id_fixture']);
                $this->db->update('fixtures', array($siguiente_tercer['campo'] => $id_perdedor));
            }
        }

        return 'Resultado guardado correctamente.';
    }

    /**
     * Registrar resultado de un deporte MASIVO_TIEMPO (running, ciclismo, pesca...):
     * se guarda la lista de UTEs en orden de llegada (1° = índice 0).
     * No hay clasificación: solo se marca la jornada como FINALIZADA.
     */
    public function registrar_resultado_masivo($id_fixture, $ute_ids_ordenados) {
        $partido = $this->obtener_fixture_por_id($id_fixture);
        if (!$partido) {
            throw new Exception('La jornada no existe.');
        }

        // Detectar deporte masivo: por fase JORNADA_UNICA o por el deporte
        // asociado a la categoría (modalidad MASIVO_TIEMPO).
        $es_masivo = ($partido['fase'] === 'JORNADA_UNICA');
        if (!$es_masivo) {
            $this->db->select('d.modalidad_competencia');
            $this->db->from('categorias c');
            $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
            $this->db->where('c.id_categoria', (int) $partido['id_categoria']);
            $cat = $this->db->get()->row_array();
            $es_masivo = $cat && $cat['modalidad_competencia'] === 'MASIVO_TIEMPO';
        }

        // Validar que los competidores existan y pertenezcan a la categoría.
        // Acepta UTEs/equipos reales Y competidores individuales (inscripciones
        // sin equipo, id negativo), para deportes como running/ciclismo.
        $utes_cat = $this->obtener_utes_por_categoria($partido['id_categoria']);
        $ids_validos = array_map('intval', array_column($utes_cat, 'id_ute'));

        $orden = array();
        foreach ((array) $ute_ids_ordenados as $id) {
            $id = (int) $id;
            if ($id === 0) continue;
            if (!in_array($id, $ids_validos, true)) {
                throw new Exception('Hay un competidor que no pertenece a esta categoría.');
            }
            if (in_array($id, $orden, true)) {
                throw new Exception('Hay equipos repetidos en la planilla de resultados.');
            }
            $orden[] = $id;
        }

        if (empty($orden)) {
            if (!$es_masivo) {
                throw new Exception('Este partido no es una jornada de deporte masivo.');
            }
            throw new Exception('No seleccionaste ningún competidor. Elegí al menos al ganador.');
        }

        // Si la columna fixtures.resultado no existe todavía, avisar en vez de
        // reventar con un error SQL críptico.
        if (!$this->_existe_columna_resultado()) {
            throw new Exception('Falta la columna "resultado" en la tabla fixtures. '
                . 'Ejecutá el script sql/fixture_resultado_masivo.sql (ALTER TABLE) y recargá la página.');
        }

        $this->db->where('id_fixture', $id_fixture);
        $this->db->update('fixtures', array(
            'estado'    => 'FINALIZADO',
            'resultado' => json_encode($orden),
        ));

        return 'Resultado de la jornada guardado (' . count($orden) . ' equipo/s posicionados).';
    }

    /** Borrar todo el fixture de una categoría. */
    public function eliminar_fixture_por_categoria($id_categoria) {
        // Si algún partido usa competidores individuales (ids negativos), la FK
        // fk_fixture_ute1/2 hacia utes rechazaría el UPDATE en cascada. Para
        // evitar errores, se limpian primero esos slots y recién se borra.
        $this->db->select('id_fixture', FALSE);
        $this->db->from('fixtures');
        $this->db->where('id_categoria', (int) $id_categoria);
        $this->db->group_start();
        $this->db->where('id_ute_1 <', 0);
        $this->db->or_where('id_ute_2 <', 0);
        $this->db->group_end();
        $rows = $this->db->get()->result_array();
        foreach ($rows as $r) {
            $this->db->where('id_fixture', (int) $r['id_fixture']);
            $this->db->update('fixtures', array('id_ute_1' => null, 'id_ute_2' => null));
        }

        $this->db->where('id_categoria', $id_categoria);
        return $this->db->delete('fixtures');
    }

    public function eliminar_partido($id_fixture) {
        // Misma protección que arriba: sin esto, borrar una jornada de running
        // con podio de individuos da error de clave foránea.
        $p = $this->obtener_fixture_por_id($id_fixture);
        if ($p && ((int) $p['id_ute_1'] < 0 || (int) $p['id_ute_2'] < 0)) {
            $this->db->where('id_fixture', (int) $id_fixture);
            $this->db->update('fixtures', array('id_ute_1' => null, 'id_ute_2' => null));
        }

        $this->db->where('id_fixture', $id_fixture);
        return $this->db->delete('fixtures');
    }

    /* ============================================================
     *  HELPERS
     * ============================================================ */

    private function _primer_lugar() {
        $this->db->order_by('id', 'ASC');
        $this->db->limit(1);
        $lugar = $this->db->get('lugares')->row_array();
        return $lugar ? (int) $lugar['id'] : null;
    }

    private function _sumar_horas($hora, $horas) {
        return date('H:i:s', strtotime($hora . ' +' . $horas . ' hours'));
    }
}

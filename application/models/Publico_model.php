<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PUBLICO_model — Consultas READ-ONLY para el panel público de las Olimpíadas.
 *
 * Este modelo NO toca ninguna lógica del backend privado (Inscripciones).
 * Todas sus consultas son de solo lectura y devuelven ÚNICAMENTE información
 * pública del evento: fixture, resultados, medallero y delegaciones.
 *
 * Privacidad: los nombres de los competidores que se muestran son los de los
 * EQUIPOS/UTEs o pruebas (no DNI, ni emails, ni datos personales), por lo que
 * no expone información individual sensible.
 */
class Publico_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /* ============================================================
     *  RESUMEN GENERAL DEL EVENTO (cards de la home pública)
     * ============================================================ */

    /** Totales públicos: delegaciones, deportes, categorías, partidos, resultados cargados. */
    public function obtener_resumen_evento() {
        $resumen = array(
            'delegaciones' => 0,
            'deportes'     => 0,
            'categorias'   => 0,
            'partidos'     => 0,
            'finalizados'  => 0,
            'resultados'   => 0,
            'premiados'    => 0,
        );

        try {
            // Delegaciones con inscriptos
            if ($this->_tabla_existe('participantes')) {
                $this->db->select('COUNT(DISTINCT delegacion) AS cant', FALSE);
                $this->db->where('delegacion IS NOT NULL');
                $this->db->where('delegacion !=', '');
                $row = $this->db->get('participantes')->row_array();
                $resumen['delegaciones'] = isset($row['cant']) ? (int) $row['cant'] : 0;
            }

            // Deportes y categorías
            if ($this->_tabla_existe('deportes')) {
                $resumen['deportes'] = (int) $this->db->count_all('deportes');
            }
            if ($this->_tabla_existe('categorias')) {
                $resumen['categorias'] = (int) $this->db->count_all('categorias');
            }

            // Partidos del fixture (programados / finalizados)
            if ($this->_tabla_existe('fixtures')) {
                $resumen['partidos'] = (int) $this->db->count_all('fixtures');
                if ($this->_columna_existe('fixtures', 'estado')) {
                    $this->db->where('estado', 'FINALIZADO');
                    $resumen['finalizados'] = (int) $this->db->count_all_results('fixtures');
                }
            }

            // Resultados cargados
            if ($this->_tabla_existe('resultados')) {
                $resumen['resultados'] = (int) $this->db->count_all('resultados');
            }

            // Premios entregados (medallas confirmadas)
            if ($this->_tabla_existe('premiaciones') && $this->_columna_existe('premiaciones', 'entregado')) {
                $this->db->where('entregado', 1);
                $resumen['premiados'] = (int) $this->db->count_all_results('premiaciones');
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::obtener_resumen_evento: ' . $e->getMessage());
        }

        return $resumen;
    }

    /** Lista DISTINCT de delegaciones con participantes inscriptos. */
    public function obtener_delegaciones() {
        try {
            if (!$this->_tabla_existe('participantes') || !$this->_columna_existe('participantes', 'delegacion')) {
                return array();
            }
            $this->db->select('delegacion');
            $this->db->where('delegacion IS NOT NULL');
            $this->db->where('delegacion !=', '');
            $this->db->group_by('delegacion');
            $this->db->order_by('delegacion', 'ASC');
            $rows = $this->db->get('participantes')->result_array();
            return array_column($rows, 'delegacion');
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::obtener_delegaciones: ' . $e->getMessage());
            return array();
        }
    }

    /** Deportes con sus categorías (para los filtros públicos). */
    public function obtener_deportes_con_categorias() {
        try {
            $this->db->select('d.id_deporte, d.nombre_deporte, d.modalidad_competencia, c.id_categoria, c.nombre_categoria, c.genero', FALSE);
            $this->db->from('deportes d');
            $this->db->join('categorias c', 'c.id_deporte = d.id_deporte', 'left');
            $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');
            $rows = $this->db->get()->result_array();
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::obtener_deportes_con_categorias: ' . $e->getMessage());
            return array();
        }

        // Agrupar: deporte -> [categorias]
        $deportes = array();
        foreach ($rows as $r) {
            $id_d = (int) $r['id_deporte'];
            if (!isset($deportes[$id_d])) {
                $deportes[$id_d] = array(
                    'id_deporte' => $id_d,
                    'nombre_deporte' => $r['nombre_deporte'],
                    'categorias' => array(),
                );
            }
            if (!empty($r['id_categoria'])) {
                $deportes[$id_d]['categorias'][] = array(
                    'id_categoria'    => (int) $r['id_categoria'],
                    'nombre_categoria' => $r['nombre_categoria'],
                    'genero'          => $r['genero'],
                );
            }
        }
        return array_values($deportes);
    }

    /* ============================================================
     *  FIXTURE PÚBLICO
     * ============================================================ */

    /**
     * Fixture público: partidos con equipos (UTEs), categoría, deporte, lugar,
     * fecha/hora, fase y estado. Opcionalmente filtrado por delegación
     * (solo muestra los partidos donde esa delegación tiene algún integrante
     * en alguno de los equipos, o compite en la prueba individual).
     */
    public function obtener_fixture_publico($delegacion = null, $id_deporte = null) {
        try {
            if (!$this->_tabla_existe('fixtures')) {
                return array();
            }
            // Columnas opcionales según migraciones aplicadas: si la base todavía
            // no tiene alguna columna (p. ej. fixtures.nombre_prueba), seleccionarla
            // rompe TODO el query (Unknown column) y el controlador explota justo
            // en la línea del 'fixture' => obtener_fixture_publico(...).
            $select = '
                f.id_fixture, f.id_categoria, f.id_lugar, f.fecha_competencia, f.hora_inicio, f.hora_fin,
                f.fase, f.numero_fecha, f.estado,
                f.id_ute_1, f.id_ute_2';
            if ($this->_columna_existe('fixtures', 'nombre_prueba')) {
                $select .= ', f.nombre_prueba';
            }
            if ($this->_columna_existe('fixtures', 'resultado')) {
                $select .= ', f.resultado AS orden_llegada_json';
            }
            $select .= ',
                u1.nombre_ute AS ute_1_nombre,
                u2.nombre_ute AS ute_2_nombre,
                l.nombre AS lugar_nombre,
                c.nombre_categoria, c.genero AS genero_categoria,
                d.nombre_deporte, d.modalidad_competencia';

            $this->db->select($select, FALSE);
            $this->db->from('fixtures f');
            $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
            $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');
            $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
            $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'left');
            $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');

            if ($id_deporte !== null && (int) $id_deporte > 0) {
                $this->db->where('d.id_deporte', (int) $id_deporte);
            }

            // Filtro por DELEGACIÓN: se resuelve en SQL, con los MISMOS criterios
            // que usa el panel privado (partido de equipo, de individual o jornada
            // masiva de la categoría). Antes se filtraba en PHP comparando solo
            // participantes_utes, y como los fixtures guardan individuales como
            // slots negativos (-id_inscripcion), el filtro dejaba todo vacío.
            if (!empty($delegacion)) {
                $del = $this->db->escape($delegacion);
                $cond_equipo = "EXISTS (
                    SELECT 1 FROM participantes_utes pu
                    INNER JOIN participantes p ON p.id_participante = pu.id_participante
                    WHERE pu.id_ute IN (f.id_ute_1, f.id_ute_2)
                      AND p.delegacion = {$del})";
                $cond_individual = "EXISTS (
                    SELECT 1 FROM inscripciones_deportivas i
                    INNER JOIN participantes p ON p.id_participante = i.id_participante
                    WHERE i.id_categoria = f.id_categoria
                      AND (-i.id_inscripcion) IN (f.id_ute_1, f.id_ute_2)
                      AND p.delegacion = {$del})";
                $cond_masivo = "(
                    (f.fase = 'JORNADA_UNICA' OR d.modalidad_competencia = 'MASIVO_TIEMPO')
                    AND EXISTS (
                        SELECT 1 FROM inscripciones_deportivas i
                        INNER JOIN participantes p ON p.id_participante = i.id_participante
                        WHERE i.id_categoria = f.id_categoria
                          AND p.delegacion = {$del}))";
                $this->db->where('(' . $cond_equipo . ' OR ' . $cond_individual . ' OR ' . $cond_masivo . ')', NULL, FALSE);
            }

            // Marcador: traer el resultado MARCADOR vinculado al partido (si existe)
            // en una sola pasada, sin N+1 queries.
            $marcadores = $this->_obtener_marcadores_por_fixture();

            $this->db->order_by('f.fecha_competencia, f.hora_inicio, d.nombre_deporte, c.nombre_categoria', 'ASC');
            $fixtures = $this->db->get()->result_array();

            // Nombres de los competidores individuales (slots negativos -id_inscripcion).
            $nombres_ind = $this->_nombres_individuales_en_fixtures($fixtures);

            foreach ($fixtures as &$fx) {
                $idf = (int) $fx['id_fixture'];
                $fx['marcador'] = isset($marcadores[$idf]) ? $marcadores[$idf] : null;
                if (!isset($fx['nombre_prueba'])) {
                    $fx['nombre_prueba'] = null;
                }
                if (!isset($fx['orden_llegada_json'])) {
                    $fx['orden_llegada_json'] = null;
                }
                // Slots negativos: el join contra `utes` no devuelve nombre, así
                // que se completa con el del inscripto individual.
                foreach (array(1, 2) as $lado) {
                    $campo_id = 'id_ute_' . $lado;
                    $campo_nom = 'ute_' . $lado . '_nombre';
                    $idl = isset($fx[$campo_id]) ? (int) $fx[$campo_id] : 0;
                    if (empty($fx[$campo_nom]) && $idl < 0 && isset($nombres_ind[$idl])) {
                        $fx[$campo_nom] = $nombres_ind[$idl];
                    } elseif (empty($fx[$campo_nom])) {
                        $fx[$campo_nom] = null;
                    }
                }
            }
            unset($fx);

            return $fixtures;
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::obtener_fixture_publico: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Nombres de las inscripciones individuales usadas en el fixture
     * (clave: id negativo = -id_inscripcion). PÚBLICO: nunca expone el DNI.
     */
    private function _nombres_individuales_en_fixtures($fixtures) {
        $nombres = array();
        try {
            if (!$this->_tabla_existe('inscripciones_deportivas')) {
                return $nombres;
            }
            $ids_neg = array();
            foreach ($fixtures as $f) {
                foreach (array('id_ute_1', 'id_ute_2') as $campo) {
                    $idl = isset($f[$campo]) ? (int) $f[$campo] : 0;
                    if ($idl < 0) $ids_neg[] = abs($idl);
                }
                if (!empty($f['orden_llegada_json'])) {
                    $arr = json_decode($f['orden_llegada_json'], true);
                    if (is_array($arr)) {
                        foreach ($arr as $id) {
                            if ((int) $id < 0) $ids_neg[] = abs((int) $id);
                        }
                    }
                }
            }
            $ids_neg = array_values(array_unique($ids_neg));
            if (!$ids_neg) return $nombres;

            $this->db->select('i.id_inscripcion, p.nombre_completo', FALSE);
            $this->db->from('inscripciones_deportivas i');
            $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
            $this->db->where_in('i.id_inscripcion', $ids_neg);
            $rows = $this->db->get()->result_array();
            foreach ($rows as $r) {
                $nombres[-(int) $r['id_inscripcion']] = $r['nombre_completo'];
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_nombres_individuales_en_fixtures: ' . $e->getMessage());
        }
        return $nombres;
    }

    /** Marcadores (goles/sets) por id_fixture, tomados de resultados MARCADOR. */
    private function _obtener_marcadores_por_fixture() {
        if (!$this->_tabla_existe('resultados') || !$this->_tabla_existe('resultado_detalle')) {
            return array();
        }
        try {
            $sel = 'r.id_fixture';
            foreach (array('hubo_desempate', 'desempate_metodo') as $col) {
                if ($this->_columna_existe('resultados', $col)) {
                    $sel .= ', r.' . $col;
                }
            }
            $sel .= ', rd.id_ute, rd.nombre_libre';
            if ($this->_columna_existe('resultado_detalle', 'marcador_local')) {
                $sel .= ', rd.marcador_local, rd.marcador_visita';
            }
            $this->db->select($sel, FALSE);
            $this->db->from('resultados r');
            $this->db->join('resultado_detalle rd', 'rd.id_resultado = r.id_resultado', 'inner');
            if ($this->_columna_existe('resultados', 'tipo_resultado')) {
                $this->db->where('r.tipo_resultado', 'MARCADOR');
            }
            $this->db->where('r.id_fixture IS NOT NULL', null, false);
            $rows = $this->db->get()->result_array();
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_obtener_marcadores_por_fixture: ' . $e->getMessage());
            return array();
        }

        $por_fx = array();
        foreach ($rows as $r) {
            $idf = (int) $r['id_fixture'];
            if (!isset($por_fx[$idf])) {
                $por_fx[$idf] = array(
                    'equipos' => array(),
                    'hubo_desempate' => isset($r['hubo_desempate']) ? (int) $r['hubo_desempate'] : 0,
                    'desempate_metodo' => isset($r['desempate_metodo']) ? $r['desempate_metodo'] : null,
                );
            }
            $nombre = !empty($r['nombre_libre']) ? $r['nombre_libre'] : null;
            $por_fx[$idf]['equipos'][] = array(
                'id_ute' => isset($r['id_ute']) ? (int) $r['id_ute'] : null,
                'nombre' => $nombre,
                'a_favor' => isset($r['marcador_local']) && $r['marcador_local'] !== null ? (int) $r['marcador_local'] : null,
                'en_contra' => isset($r['marcador_visita']) && $r['marcador_visita'] !== null ? (int) $r['marcador_visita'] : null,
            );
        }
        return $por_fx;
    }

    /** ids de UTE en las que la delegación tiene integrantes. */
    private function _utes_de_la_delegacion($delegacion) {
        $map = array();
        try {
            if ($this->_tabla_existe('participantes_utes')) {
                $this->db->select('pu.id_ute', FALSE);
                $this->db->from('participantes_utes pu');
                $this->db->join('participantes p', 'p.id_participante = pu.id_participante', 'inner');
                $this->db->where('p.delegacion', $delegacion);
                $rows = $this->db->get()->result_array();
                foreach ($rows as $r) {
                    if (isset($r['id_ute'])) $map[(int) $r['id_ute']] = true;
                }
            }

            // Equipos "históricos" sin fila en participantes_utes: reconocidos por
            // el id_ute guardado en inscripciones_deportivas.
            if ($this->_tabla_existe('inscripciones_deportivas') && $this->_columna_existe('inscripciones_deportivas', 'id_ute')) {
                $this->db->select('i.id_ute', FALSE);
                $this->db->distinct();
                $this->db->from('inscripciones_deportivas i');
                $this->db->join('participantes p2', 'p2.id_participante = i.id_participante', 'inner');
                $this->db->where('p2.delegacion', $delegacion);
                $this->db->where('i.id_ute IS NOT NULL', null, false);
                $rows2 = $this->db->get()->result_array();
                foreach ($rows2 as $r) {
                    if (isset($r['id_ute'])) $map[(int) $r['id_ute']] = true;
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_utes_de_la_delegacion: ' . $e->getMessage());
        }
        return $map;
    }

    /** ids de inscripción de los participantes de la delegación (slots individuales negativos). */
    private function _ids_inscripcion_de_la_delegacion($delegacion) {
        try {
            if (!$this->_tabla_existe('inscripciones_deportivas')) {
                return array();
            }
            $this->db->select('i.id_inscripcion', FALSE);
            $this->db->from('inscripciones_deportivas i');
            $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
            $this->db->where('p.delegacion', $delegacion);
            $rows = $this->db->get()->result_array();
            $map = array();
            foreach ($rows as $r) {
                if (isset($r['id_inscripcion'])) $map[(int) $r['id_inscripcion']] = true;
            }
            return $map;
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_ids_inscripcion_de_la_delegacion: ' . $e->getMessage());
            return array();
        }
    }

    /* ============================================================
     *  RESULTADOS PÚBLICOS
     * ============================================================ */

    /**
     * Resultados públicos (MARCADOR y TIEMPO/PUNTAJE) con su detalle.
     * Filtrable por delegación: solo eventos donde participa un equipo/competidor
     * de esa delegación.
     */
    public function obtener_resultados_publicos($delegacion = null, $id_deporte = null) {
        try {
            if (!$this->_tabla_existe('resultados')) {
                return array();
            }
            $sel = 'r.id_resultado, r.id_categoria, r.id_fixture, r.nombre_evento, r.tipo_resultado, r.fecha_resultado, r.lugar';
            foreach (array('hubo_desempate', 'desempate_metodo') as $col) {
                if ($this->_columna_existe('resultados', $col)) {
                    $sel .= ', r.' . $col;
                }
            }
            $sel .= ', c.nombre_categoria, d.nombre_deporte, d.id_deporte';
            $this->db->select($sel, FALSE);
            $this->db->from('resultados r');
            $this->db->join('categorias c', 'c.id_categoria = r.id_categoria', 'left');
            $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
            if ($id_deporte !== null && (int) $id_deporte > 0) {
                $this->db->where('d.id_deporte', (int) $id_deporte);
            }
            $this->db->order_by('r.fecha_resultado DESC, r.id_resultado DESC');
            $rows = $this->db->get()->result_array();
            if (!$rows) return array();

            // Detalle en un solo query
            $ids = array_column($rows, 'id_resultado');
            $this->db->where_in('id_resultado', $ids);
            if ($this->_columna_existe('resultado_detalle', 'id_detalle')) {
                $this->db->order_by('id_detalle', 'ASC');
            }
            $detalles = $this->db->get('resultado_detalle')->result_array();
            $por_res = array();
            foreach ($detalles as $d) {
                $por_res[(int) $d['id_resultado']][] = $d;
            }
            foreach ($rows as &$r) {
                $det = isset($por_res[(int) $r['id_resultado']]) ? $por_res[(int) $r['id_resultado']] : array();
                if ($r['tipo_resultado'] === 'TIEMPO' || $r['tipo_resultado'] === 'PUNTAJE') {
                    usort($det, function ($a, $b) {
                        return (int) $a['posicion'] - (int) $b['posicion'];
                    });
                }
                $r['detalle'] = $det;
            }
            unset($r);

            if (!empty($delegacion)) {
                $utes_delegacion = $this->_utes_de_la_delegacion($delegacion);
                $inscripciones_delegacion = $this->_ids_inscripcion_de_la_delegacion($delegacion);
                $categorias_delegacion = $this->_categorias_de_la_delegacion($delegacion);
                $rows = array_values(array_filter($rows, function ($r) use ($utes_delegacion, $inscripciones_delegacion, $categorias_delegacion) {
                    // Eventos masivos (TIEMPO de una jornada): la delegación
                    // participó si tiene inscriptos en esa categoría.
                    $idc = isset($r['id_categoria']) ? (int) $r['id_categoria'] : 0;
                    $es_tiempo = isset($r['tipo_resultado']) && $r['tipo_resultado'] === 'TIEMPO';
                    if ($es_tiempo && $idc > 0 && isset($categorias_delegacion[$idc])) {
                        return true;
                    }
                    foreach ($r['detalle'] as $d) {
                        $idl = isset($d['id_ute']) ? (int) $d['id_ute'] : 0;
                        if ($idl > 0 && isset($utes_delegacion[$idl])) return true;
                        // Individual: id_ute negativo = -id_inscripcion
                        if ($idl < 0 && isset($inscripciones_delegacion[abs($idl)])) return true;
                    }
                    return false;
                }));
            }

            return $rows;
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::obtener_resultados_publicos: ' . $e->getMessage());
            return array();
        }
    }

    /** ids de categoría en las que la delegación tiene inscriptos. */
    private function _categorias_de_la_delegacion($delegacion) {
        try {
            if (!$this->_tabla_existe('inscripciones_deportivas')) {
                return array();
            }
            $this->db->select('i.id_categoria', FALSE);
            $this->db->distinct();
            $this->db->from('inscripciones_deportivas i');
            $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
            $this->db->where('p.delegacion', $delegacion);
            $rows = $this->db->get()->result_array();
            $map = array();
            foreach ($rows as $r) {
                if (isset($r['id_categoria'])) $map[(int) $r['id_categoria']] = true;
            }
            return $map;
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_categorias_de_la_delegacion: ' . $e->getMessage());
            return array();
        }
    }

    /* ============================================================
     *  MEDALLERO PÚBLICO
     * ============================================================ */

    /**
     * Medallero: oro/plata/bronce por delegación, calculado sobre las
     * premiaciones ENTREGADAS. El premiado es el nombre del equipo/prueba
     * (snapshot), nunca un dato personal sensible.
     */
    public function obtener_medallero() {
        try {
            if (!$this->_tabla_existe('premiaciones')) {
                return array();
            }
            // Delegación de cada premiación: se resuelve por la UTE premiada
            // (la delegación mayoritaria de sus integrantes).
            $sel = 'p.puesto, p.nombre';
            foreach (array('id_ute', 'fecha_entrega') as $col) {
                if ($this->_columna_existe('premiaciones', $col)) {
                    $sel .= ', p.' . $col;
                }
            }
            $sel .= ', c.nombre_categoria, d.nombre_deporte';
            $this->db->select($sel, FALSE);
            $this->db->from('premiaciones p');
            $this->db->join('categorias c', 'c.id_categoria = p.id_categoria', 'left');
            $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
            if ($this->_columna_existe('premiaciones', 'entregado')) {
                $this->db->where('p.entregado', 1);
            }
            if ($this->_columna_existe('premiaciones', 'fecha_entrega')) {
                $this->db->order_by('p.fecha_entrega ASC, p.puesto ASC');
            } else {
                $this->db->order_by('p.puesto ASC');
            }
            $rows = $this->db->get()->result_array();
            if (!$rows) return array();

            // Mapa id_ute -> delegación (mayoría de integrantes). Si la tabla
            // participantes_utes está vacía (equipos armados por nombre en la
            // inscripción), se completa con inscripciones_deportivas.
            $deleg_por_ute = $this->_delegacion_por_ute();
            if (!$deleg_por_ute) {
                $deleg_por_ute = $this->_delegacion_por_ute_desde_inscripciones();
            }

            $medallero = array();
            foreach ($rows as $r) {
                $deleg = null;
                $idl = isset($r['id_ute']) ? (int) $r['id_ute'] : 0;
                if ($idl > 0 && isset($deleg_por_ute[$idl])) {
                    $deleg = $deleg_por_ute[$idl];
                }
                if ($deleg === null) {
                    continue; // premio sin delegación identificable (p. ej. individual libre)
                }
                if (!isset($medallero[$deleg])) {
                    $medallero[$deleg] = array('delegacion' => $deleg, 'oro' => 0, 'plata' => 0, 'bronce' => 0, 'total' => 0, 'podios' => array());
                }
                $puesto = (int) $r['puesto'];
                if ($puesto === 1)      $medallero[$deleg]['oro']++;
                elseif ($puesto === 2) $medallero[$deleg]['plata']++;
                elseif ($puesto === 3) $medallero[$deleg]['bronce']++;
                $medallero[$deleg]['total']++;
                $medallero[$deleg]['podios'][] = array(
                    'puesto' => $puesto,
                    'deporte' => $r['nombre_deporte'],
                    'categoria' => $r['nombre_categoria'],
                    'nombre' => $r['nombre'],
                );
            }

            $lista = array_values($medallero);
            usort($lista, function ($a, $b) {
                if ($b['oro'] !== $a['oro']) return $b['oro'] - $a['oro'];
                if ($b['plata'] !== $a['plata']) return $b['plata'] - $a['plata'];
                return $b['bronce'] - $a['bronce'];
            });
            return $lista;
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::obtener_medallero: ' . $e->getMessage());
            return array();
        }
    }

    /** Delegación "dominante" de cada UTE (la de más integrantes). */
    private function _delegacion_por_ute() {
        try {
            if (!$this->_tabla_existe('participantes_utes')) {
                return array();
            }
            $this->db->select('pu.id_ute, p.delegacion, COUNT(*) AS cant', FALSE);
            $this->db->from('participantes_utes pu');
            $this->db->join('participantes p', 'p.id_participante = pu.id_participante', 'inner');
            $this->db->where('p.delegacion IS NOT NULL');
            $this->db->where('p.delegacion !=', '');
            $this->db->group_by('pu.id_ute, p.delegacion');
            $rows = $this->db->get()->result_array();
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_delegacion_por_ute: ' . $e->getMessage());
            return array();
        }

        $mejor = array(); // id_ute => ['delegacion' => ..., 'cant' => ...]
        foreach ($rows as $r) {
            if (!isset($r['id_ute']) || !isset($r['delegacion'])) continue;
            $idl = (int) $r['id_ute'];
            $cant = isset($r['cant']) ? (int) $r['cant'] : 1;
            if (!isset($mejor[$idl]) || $cant > $mejor[$idl]['cant']) {
                $mejor[$idl] = array('delegacion' => $r['delegacion'], 'cant' => $cant);
            }
        }
        return array_map(function ($m) { return $m['delegacion']; }, $mejor);
    }

    /**
     * Fallback del mapa id_ute -> delegación para equipos que NO tienen filas
     * en participantes_utes (se arman por nombre en la inscripción): se resuelve
     * por inscripciones_deportivas.id_ute + delegación del inscripto.
     */
    private function _delegacion_por_ute_desde_inscripciones() {
        $mejor = array();
        try {
            if (!$this->_tabla_existe('inscripciones_deportivas')
                || !$this->_columna_existe('inscripciones_deportivas', 'id_ute')) {
                return array();
            }
            $this->db->select('i.id_ute, p.delegacion, COUNT(*) AS cant', FALSE);
            $this->db->from('inscripciones_deportivas i');
            $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
            $this->db->where('i.id_ute IS NOT NULL', null, false);
            $this->db->where('p.delegacion IS NOT NULL');
            $this->db->where('p.delegacion !=', '');
            $this->db->group_by('i.id_ute, p.delegacion');
            $rows = $this->db->get()->result_array();
            foreach ($rows as $r) {
                $idl = (int) $r['id_ute'];
                $cant = (int) $r['cant'];
                if (!isset($mejor[$idl]) || $cant > $mejor[$idl]['cant']) {
                    $mejor[$idl] = array('delegacion' => $r['delegacion'], 'cant' => $cant);
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_delegacion_por_ute_desde_inscripciones: ' . $e->getMessage());
            return array();
        }
        return array_map(function ($m) { return $m['delegacion']; }, $mejor);
    }

    /* ============================================================
     *  Utilidades
     * ============================================================ */

    private function _tabla_existe($tabla) {
        try {
            return !empty($this->db->table_exists($tabla));
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * ¿Existe la columna en la tabla? (cachée por request: cada consulta del
     * panel puede validar columnas sin golpear el esquema varias veces).
     */
    private function _columna_existe($tabla, $columna) {
        static $cache = array();
        $key = $tabla . '.' . $columna;
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        try {
            $cache[$key] = $this->db->field_exists($columna, $tabla);
        } catch (Throwable $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }
}

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
    public function obtener_fixture_publico($delegacion = null, $id_deporte = null, $id_categoria = null) {
        try {
            // NOTA DE DISEÑO: el panel público se renderiza DESPUÉS de varias
            // otras consultas (resumen → delegaciones → deportes). Si una de
            // esas consultas previas deja la Query Builder "sucia" (p. ej. por
            // un error de DB en `count_all_results`), un `$this->db->get()`
            // simple arma un SELECT * FROM <última tabla usada> y no desde
            // `fixtures`, devolviendo 0 filas → "No hay partidos programados"
            // aunque haya fixture cargado. Para blindarlo, acá se lee la tabla
            // con raw SQL SIN pasar por la Query Builder compartida.
            $sql = 'SELECT * FROM `fixtures`';
            $conds = array();
            if ($id_deporte !== null && (int) $id_deporte > 0) {
                $ids = $this->_ids_categorias_del_deporte((int) $id_deporte);
                $conds[] = 'id_categoria IN (' . implode(',', array_map('intval', $ids)) . ')';
            }
            // Filtro por CATEGORÍA específica (cascada Deporte → Categoría del panel).
            if ($id_categoria !== null && (int) $id_categoria > 0) {
                $conds[] = 'id_categoria = ' . (int) $id_categoria;
            }
            if ($conds) {
                $sql .= ' WHERE ' . implode(' AND ', $conds);
            }
            $sql .= ' ORDER BY fecha_competencia ASC, hora_inicio ASC, id_categoria ASC, numero_fecha ASC, id_fixture ASC';

            $query = $this->db->query($sql);
            $fixtures = ($query === false || !is_object($query)) ? array() : $query->result_array();
            if (!$fixtures) {
                log_message('error', 'Publico_model::obtener_fixture_publico — sin filas. SQL: ' . $sql);
                return array();
            }

            // Filtro por DELEGACIÓN: se resuelve en PHP (igual que el panel
            // privado, que sí funciona) para no depender de tablas auxiliares.
            $comp = null;
            if (!empty($delegacion)) {
                $comp = $this->_competidores_de_delegacion($delegacion);
            }

            // Joins de nombres resueltos en PHP, sobre CONSULTAS SEPARADAS limpias
            // (sin `$this->db->reset_query()`): así el panel público nunca depende
            // de que una tabla auxiliar exista. Si falta, esa columna queda null y
            // el fixture se muestra igual.
            $nombres_utes   = $this->_mapa('utes', 'id_ute', 'nombre_ute');
            $nombres_lugares = $this->_mapa('lugares', 'id', 'nombre');
            $cats = $this->_mapa(
                'categorias', 'id_categoria',
                array('nombre_categoria', 'genero', 'id_deporte')
            );
            $deportes = $this->_mapa('deportes', 'id_deporte', array('nombre_deporte', 'modalidad_competencia'));

            foreach ($fixtures as &$fx) {
                $idl1 = isset($fx['id_ute_1']) ? (int) $fx['id_ute_1'] : 0;
                $idl2 = isset($fx['id_ute_2']) ? (int) $fx['id_ute_2'] : 0;
                $cat  = isset($fx['id_categoria']) ? (int) $fx['id_categoria'] : 0;
                $dep  = isset($cats[$cat]['id_deporte']) ? (int) $cats[$cat]['id_deporte'] : 0;

                $fx['ute_1_nombre'] = isset($nombres_utes[$idl1]) ? $nombres_utes[$idl1] : null;
                $fx['ute_2_nombre'] = isset($nombres_utes[$idl2]) ? $nombres_utes[$idl2] : null;
                $fx['lugar_nombre'] = isset($nombres_lugares[(int) $fx['id_lugar']])
                    ? $nombres_lugares[(int) $fx['id_lugar']] : null;
                $fx['nombre_categoria']  = isset($cats[$cat]['nombre_categoria']) ? $cats[$cat]['nombre_categoria'] : null;
                $fx['genero_categoria']  = isset($cats[$cat]['genero']) ? $cats[$cat]['genero'] : null;
                $fx['nombre_deporte']    = isset($deportes[$dep]['nombre_deporte']) ? $deportes[$dep]['nombre_deporte'] : null;
                $fx['modalidad_competencia'] = isset($deportes[$dep]['modalidad_competencia']) ? $deportes[$dep]['modalidad_competencia'] : null;
                // Columnas opcionales (migraciones): leídas del SELECT *, sin field_exists().
                $fx['nombre_prueba']      = isset($fx['nombre_prueba']) ? $fx['nombre_prueba'] : null;
                $fx['orden_llegada_json'] = isset($fx['resultado']) ? $fx['resultado'] : null;

            }
            unset($fx);

            // Filtro por delegación (en PHP, con los mismos 3 caminos que el panel
            // privado): se aplica AHORA, con los nombres ya resueltos.
            if ($comp !== null) {
                $fixtures = array_values(array_filter($fixtures, function ($fx) use ($comp) {
                    return $this->_fixture_es_de_la_delegacion($fx, $comp);
                }));
            }

            // Marcadores (resultados MARCADOR), en una sola pasada sin N+1.
            $marcadores = $this->_obtener_marcadores_por_fixture();

            // Nombres de los competidores individuales (slots negativos -id_inscripcion).
            $nombres_ind = $this->_nombres_individuales_en_fixtures($fixtures);

            foreach ($fixtures as &$fx) {
                $idf = (int) $fx['id_fixture'];
                $fx['marcador'] = isset($marcadores[$idf]) ? $marcadores[$idf] : null;
                // Slots negativos: `utes` no tiene filas para ids < 0, así que se
                // completa con el nombre del inscripto individual.
                foreach (array(1, 2) as $lado) {
                    $campo_id = 'id_ute_' . $lado;
                    $campo_nom = 'ute_' . $lado . '_nombre';
                    $idl = isset($fx[$campo_id]) ? (int) $fx[$campo_id] : 0;
                    if (empty($fx[$campo_nom]) && $idl < 0 && isset($nombres_ind[$idl])) {
                        $fx[$campo_nom] = $nombres_ind[$idl];
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
        return $this->_nombres_individuales_por_ids($ids_neg);
    }

    /** Idem, pero desde filas de `resultado_detalle` (columna id_ute negativa). */
    private function _nombres_individuales_en_detalle($detalles) {
        $ids_neg = array();
        foreach ($detalles as $d) {
            $idl = isset($d['id_ute']) ? (int) $d['id_ute'] : 0;
            if ($idl < 0) $ids_neg[] = abs($idl);
        }
        return $this->_nombres_individuales_por_ids($ids_neg);
    }

    /** Resuelve nombres por lista de ids de inscripción (raw SQL, sin QB compartida). */
    private function _nombres_individuales_por_ids($ids_neg) {
        $nombres = array();
        try {
            $ids_neg = array_values(array_unique(array_map('intval', $ids_neg)));
            if (!$ids_neg || !$this->_tabla_existe('inscripciones_deportivas')) {
                return $nombres;
            }
            $cols_i = $this->_columnas_tabla('inscripciones_deportivas');
            $cols_p = $this->_columnas_tabla('participantes');
            if (!isset($cols_i['id_participante'])) return $nombres;
            // Columna del nombre en `participantes` (varía según esquema).
            $campo_nombre = null;
            foreach (array('nombre_completo', 'nombre', 'apellido', 'nombres') as $cand) {
                if (isset($cols_p[$cand])) { $campo_nombre = $cand; break; }
            }
            if ($campo_nombre === null) return $nombres;

            $q = $this->db->query(
                'SELECT i.`id_inscripcion`, p.`' . $campo_nombre . '` AS nombre'
                . ' FROM `inscripciones_deportivas` i'
                . ' INNER JOIN `participantes` p ON p.`id_participante` = i.`id_participante`'
                . ' WHERE i.`id_inscripcion` IN (' . implode(',', $ids_neg) . ')'
            );
            $rows = ($q === false || !is_object($q)) ? array() : $q->result_array();
            foreach ($rows as $r) {
                $nombres[-(int) $r['id_inscripcion']] = $r['nombre'];
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_nombres_individuales_por_ids: ' . $e->getMessage());
        }
        return $nombres;
    }

    /** Marcadores (goles/sets) por id_fixture, tomados de resultados MARCADOR. */
    private function _obtener_marcadores_por_fixture() {
        if (!$this->_tabla_existe('resultados') || !$this->_tabla_existe('resultado_detalle')) {
            return array();
        }
        try {
            // Raw SQL (mismo blindaje que el fixture): sin QB compartida y con
            // columnas opcionales validadas sobre SHOW COLUMNS cacheado.
            $cols_r = $this->_columnas_tabla('resultados');
            $cols_d = $this->_columnas_tabla('resultado_detalle');
            $sel = 'r.id_fixture, rd.id_ute, rd.nombre_libre';
            foreach (array('hubo_desempate', 'desempate_metodo') as $col) {
                if (isset($cols_r[$col])) $sel .= ', r.' . $col;
            }
            if (isset($cols_d['marcador_local'])) {
                $sel .= ', rd.marcador_local, rd.marcador_visita';
            }
            $where = array('r.id_fixture IS NOT NULL');
            if (isset($cols_r['tipo_resultado'])) {
                $where[] = "r.tipo_resultado = 'MARCADOR'";
            }
            $q_m = $this->db->query(
                'SELECT ' . $sel . ' FROM `resultados` r'
                . ' INNER JOIN `resultado_detalle` rd ON rd.id_resultado = r.id_resultado'
                . ' WHERE ' . implode(' AND ', $where)
            );
            $rows = ($q_m === false || !is_object($q_m)) ? array() : $q_m->result_array();
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

    /** Categorías (id_categoria) en las que la delegación tiene inscriptos. */
    private function _categorias_con_inscriptos_de($delegacion) {
        $map = array();
        try {
            if (!$this->_tabla_existe('inscripciones_deportivas')) {
                return $map;
            }
            $this->db->select('i.id_categoria', FALSE);
            $this->db->distinct();
            $this->db->from('inscripciones_deportivas i');
            $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
            $this->db->where('p.delegacion', $delegacion);
            $rows = $this->db->get()->result_array();
            foreach ($rows as $r) {
                if (isset($r['id_categoria'])) $map[(int) $r['id_categoria']] = true;
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_categorias_con_inscriptos_de: ' . $e->getMessage());
        }
        return $map;
    }

    /**
     * Set de "competidores" de una delegación para filtrar el fixture EN PHP
     * (mismos 3 caminos que el panel privado): UTEs propias, inscripciones
     * individuales (-id_inscripcion) y categorías con inscriptos (jornadas masivas).
     */
    private function _competidores_de_delegacion($delegacion) {
        return array(
            'utes'        => $this->_utes_de_la_delegacion($delegacion),
            'inscripciones' => $this->_ids_inscripcion_de_la_delegacion($delegacion),
            'categorias'  => $this->_categorias_con_inscriptos_de($delegacion),
        );
    }

    /** ¿El partido (ya con nombres resueltos) pertenece a la delegación? */
    private function _fixture_es_de_la_delegacion($fx, $comp) {
        $idl1 = isset($fx['id_ute_1']) ? (int) $fx['id_ute_1'] : 0;
        $idl2 = isset($fx['id_ute_2']) ? (int) $fx['id_ute_2'] : 0;
        // Camino 1: partido de EQUIPO — alguna UTE del match tiene integrantes de la delegación.
        if ($idl1 > 0 && isset($comp['utes'][$idl1])) return true;
        if ($idl2 > 0 && isset($comp['utes'][$idl2])) return true;
        // Camino 2: INDIVIDUAL — slot negativo -id_inscripcion de un inscripto de la delegación.
        if ($idl1 < 0 && isset($comp['inscripciones'][abs($idl1)])) return true;
        if ($idl2 < 0 && isset($comp['inscripciones'][abs($idl2)])) return true;
        // Camino 3: JORNADA MASIVA — la categoría es de un deporte masivo/única y la
        // delegación tiene inscriptos en esa categoría.
        $cat = isset($fx['id_categoria']) ? (int) $fx['id_categoria'] : 0;
        $es_masivo = (isset($fx['fase']) && $fx['fase'] === 'JORNADA_UNICA')
                  || (isset($fx['modalidad_competencia']) && $fx['modalidad_competencia'] === 'MASIVO_TIEMPO');
        if ($es_masivo && $cat > 0 && isset($comp['categorias'][$cat])) return true;
        // Fallback: si los nombres resueltos ya incluyen la delegación (equipos
        // cuyo nombre de UTE coincide con la delegación, p. ej. "Los Pumas").
        return false;
    }

    /**
     * Mapa clave→valor(es) leído de una tabla auxiliar. Cada llamada usa su
     * PROPIA cadena de Query Builder (select/from/get) sobre tablas SIN alias,
     * para no dejar nunca la conexión "sucia". Si la tabla no existe o la
     * consulta falla, devuelve array vacío (el fixture se muestra igual).
     */
    private function _mapa($tabla, $campo_clave, $campos_valor) {
        $mapa = array();
        $campos = is_array($campos_valor) ? $campos_valor : array($campos_valor);
        try {
            if (!$this->_tabla_existe($tabla)) {
                return $mapa;
            }
            $select = array_merge(array($campo_clave), $campos);
            $this->db->select(implode(', ', $select), FALSE);
            $rows = $this->db->get($tabla)->result_array();
            foreach ($rows as $r) {
                if (!isset($r[$campo_clave])) continue;
                $k = (int) $r[$campo_clave];
                if (count($campos) === 1) {
                    $mapa[$k] = isset($r[$campos[0]]) ? $r[$campos[0]] : null;
                } else {
                    $fila = array();
                    foreach ($campos as $c) {
                        $fila[$c] = isset($r[$c]) ? $r[$c] : null;
                    }
                    $mapa[$k] = $fila;
                }
            }
        } catch (Throwable $e) {
            log_message('error', "Publico_model::_mapa($tabla): " . $e->getMessage());
        }
        return $mapa;
    }

    /**
     * Nombres de UTEs por id, leídos con raw SQL acotado a los ids pedidos
     * (no depende del estado de la Query Builder ni del tamaño de la tabla).
     */
    private function _nombres_utes_por_ids($ids) {
        $mapa = array();
        try {
            $ids = array_values(array_unique(array_map('intval', $ids)));
            if (!$ids || !$this->_tabla_existe('utes')) return $mapa;
            $cols = $this->_columnas_tabla('utes');
            if (!isset($cols['nombre_ute'])) return $mapa;
            $q = $this->db->query(
                'SELECT `id_ute`, `nombre_ute` FROM `utes` WHERE `id_ute` IN (' . implode(',', $ids) . ')'
            );
            $rows = ($q === false || !is_object($q)) ? array() : $q->result_array();
            foreach ($rows as $r) {
                $mapa[(int) $r['id_ute']] = $r['nombre_ute'];
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_nombres_utes_por_ids: ' . $e->getMessage());
        }
        return $mapa;
    }

    /** Clave normalizada para emparejar nombres libre vs. registrados. */
    public function normalizar_nombre($s) {
        $s = strtolower(trim((string) $s));
        if (function_exists('iconv') && @iconv('UTF-8', 'ASCII//TRANSLIT', $s) !== false) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
            if ($ascii !== false) $s = $ascii;
        }
        $s = preg_replace('/[^a-z0-9]+/', '', $s);
        return $s;
    }

    /** Mapa nombre-normalizado → id_ute (primera coincidencia gana). */
    private function _ute_por_nombre_normalizado() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = array();
        try {
            if (!$this->_tabla_existe('utes')) return $cache;
            $cols = $this->_columnas_tabla('utes');
            if (!isset($cols['nombre_ute'])) return $cache;
            $q = $this->db->query('SELECT `id_ute`, `nombre_ute` FROM `utes`');
            $rows = ($q === false || !is_object($q)) ? array() : $q->result_array();
            foreach ($rows as $r) {
                $n = $this->normalizar_nombre($r['nombre_ute']);
                if ($n !== '' && !isset($cache[$n])) $cache[$n] = (int) $r['id_ute'];
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_ute_por_nombre_normalizado: ' . $e->getMessage());
        }
        return $cache;
    }

    /**
     * Mapa nombre-normalizado → delegación, construido desde las inscripciones
     * de EQUIPO (`detalle_ute` guarda el nombre libre con el que se anotó el
     * equipo cuando no existe una UTE registrada). Es el último fallback del
     * medallero: si el premiado se cargó "libre", igual sabemos su delegación.
     */
    private function _equipo_por_nombre_normalizado() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = array();
        try {
            if (!$this->_tabla_existe('inscripciones_deportivas')) return $cache;
            $cols_i = $this->_columnas_tabla('inscripciones_deportivas');
            $cols_p = $this->_columnas_tabla('participantes');
            if (!isset($cols_i['detalle_ute'], $cols_i['id_participante'], $cols_p['delegacion'])) {
                return $cache;
            }
            $q = $this->db->query(
                'SELECT i.`detalle_ute`, p.`delegacion`'
                . ' FROM `inscripciones_deportivas` i'
                . ' INNER JOIN `participantes` p ON p.`id_participante` = i.`id_participante`'
                . " WHERE i.`detalle_ute` IS NOT NULL AND i.`detalle_ute` != ''"
                . " AND p.`delegacion` IS NOT NULL AND p.`delegacion` != ''"
            );
            $rows = ($q === false || !is_object($q)) ? array() : $q->result_array();
            foreach ($rows as $r) {
                $n = $this->normalizar_nombre($r['detalle_ute']);
                if ($n !== '' && !isset($cache[$n])) $cache[$n] = $r['delegacion'];
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_equipo_por_nombre_normalizado: ' . $e->getMessage());
        }
        return $cache;
    }

    /** ids de categoría de un deporte (para filtrar el fixture sin JOIN). */
    private function _ids_categorias_del_deporte($id_deporte) {

        $ids = array(-1); // centinela: si `categorias` no existe, WHERE IN no rompe.
        try {
            if ($this->_tabla_existe('categorias')) {
                $this->db->select('id_categoria', FALSE);
                $this->db->where('id_deporte', (int) $id_deporte);
                $rows = $this->db->get('categorias')->result_array();
                foreach ($rows as $r) {
                    $ids[] = (int) $r['id_categoria'];
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_ids_categorias_del_deporte: ' . $e->getMessage());
        }
        return $ids;
    }

    /* ============================================================
     *  RESULTADOS PÚBLICOS
     * ============================================================ */

    /**
     * Resultados públicos (MARCADOR y TIEMPO/PUNTAJE) con su detalle.
     * Filtrable por delegación: solo eventos donde participa un equipo/competidor
     * de esa delegación.
     */
    public function obtener_resultados_publicos($delegacion = null, $id_deporte = null, $id_categoria = null) {
        try {
            if (!$this->_tabla_existe('resultados')) {
                log_message('error', 'Publico_model::obtener_resultados_publicos — la tabla `resultados` no existe.');
                return array();
            }

            // MISMO BLINDAJE QUE EL FIXTURE: lectura con raw SQL, sin pasar por
            // la Query Builder compartida (si una consulta previa quedó "sucia",
            // un $this->db->get() simple arma el SELECT desde la última tabla y
            // devuelve 0 filas → "no hay resultados" aunque sí los haya).
            // Columnas opcionales resueltas sobre SHOW COLUMNS (cacheado), nunca
            // sobre `field_exists()` (que usa la QB y puede fallar en MySQL 8).
            $cols_r = $this->_columnas_tabla('resultados');
            $campos_r = array('id_resultado', 'id_categoria', 'id_fixture', 'nombre_evento',
                              'tipo_resultado', 'fecha_resultado', 'lugar');
            if (isset($cols_r['observaciones'])) $campos_r[] = 'observaciones';
            foreach (array('hubo_desempate', 'desempate_metodo', 'id_ute_ganador') as $col) {
                if (isset($cols_r[$col])) $campos_r[] = $col;
            }
            $sql = 'SELECT `' . implode('`, `', $campos_r) . '` FROM `resultados`';
            $conds_r = array();
            if ($id_deporte !== null && (int) $id_deporte > 0) {
                $ids = $this->_ids_categorias_del_deporte((int) $id_deporte);
                $conds_r[] = '`id_categoria` IN (' . implode(',', array_map('intval', $ids)) . ')';
            }
            // Filtro por CATEGORÍA específica (cascada Deporte → Categoría del panel).
            if ($id_categoria !== null && (int) $id_categoria > 0) {
                $conds_r[] = '`id_categoria` = ' . (int) $id_categoria;
            }
            if ($conds_r) {
                $sql .= ' WHERE ' . implode(' AND ', $conds_r);
            }
            $sql .= ' ORDER BY `fecha_resultado` DESC, `id_resultado` DESC';

            $query = $this->db->query($sql);
            $rows = ($query === false || !is_object($query)) ? array() : $query->result_array();
            if (!$rows) {
                log_message('error', 'Publico_model::obtener_resultados_publicos — sin filas. SQL: ' . $sql);
                return array();
            }

            // Nombres de deporte/categoría resueltos en PHP (mapas auxiliares,
            // igual que en el fixture) — sin JOINs que dependan del estado de la QB.
            $cats = $this->_mapa('categorias', 'id_categoria', array('nombre_categoria', 'genero', 'id_deporte'));
            $deportes = $this->_mapa('deportes', 'id_deporte', array('nombre_deporte', 'modalidad_competencia'));

            // UTEs y competidores individuales (para completar nombres_libre cuando
            // el detalle no lo guarda o es un slot negativo -id_inscripcion).
            $ins_map      = $this->_delegacion_por_inscripcion(); // id_inscripcion -> delegación
            $deleg_por_ute = $this->_delegacion_por_ute();         // id_ute -> delegación (UTEs/equipos)

            // Detalle en un solo raw query
            $ids_res = array_map('intval', array_column($rows, 'id_resultado'));
            $cols_d = $this->_columnas_tabla('resultado_detalle');
            $campos_d = array('id_resultado', 'id_ute', 'nombre_libre', 'posicion', 'tiempo');
            foreach (array('marcador_local', 'marcador_visita') as $col) {
                if (isset($cols_d[$col])) $campos_d[] = $col;
            }
            $orden_d = isset($cols_d['id_detalle']) ? ' ORDER BY `id_detalle` ASC' : '';
            $sql_d = 'SELECT `' . implode('`, `', $campos_d) . '` FROM `resultado_detalle`'
                   . ' WHERE `id_resultado` IN (' . implode(',', $ids_res) . ')' . $orden_d;
            $q_d = $this->db->query($sql_d);
            $detalles = ($q_d === false || !is_object($q_d)) ? array() : $q_d->result_array();

            // Nombres de los slots negativos (-id_inscripcion): se resuelven por
            // inscripciones_deportivas+participantes. Si la consulta auxiliar
            // fallara, usamos el snapshot de `nombre_libre` como fallback.
            $nombres_ind = $this->_nombres_individuales_en_detalle($detalles);
            // ids de UTE positivos presentes en el detalle → nombres desde `utes`.
            $ids_ute_pos = array();
            foreach ($detalles as $d) {
                $idl = isset($d['id_ute']) ? (int) $d['id_ute'] : 0;
                if ($idl > 0) $ids_ute_pos[$idl] = true;
            }
            $nombres_utes = $ids_ute_pos
                ? $this->_nombres_utes_por_ids(array_keys($ids_ute_pos))
                : array();

            // Datos del partido vinculado (fase/fecha/hora/prueba) para mostrar
            // DEPORTE + CATEGORÍA como encabezado, en vez del `nombre_evento`
            // (que suele ser el nombre del partido: "Final - Partido 1").
            $fx_info = $this->_info_fixtures_de_resultados($rows);

            $por_res = array();
            foreach ($detalles as &$d) {
                $idl = isset($d['id_ute']) ? (int) $d['id_ute'] : 0;
                if (empty($d['nombre_libre'])) {
                    if ($idl > 0 && isset($nombres_utes[$idl])) {
                        $d['nombre_libre'] = $nombres_utes[$idl];
                    } elseif ($idl < 0 && isset($nombres_ind[$idl])) {
                        // Público: nombre + delegación del individual.
                        $dl = isset($ins_map[abs($idl)]) ? ' (' . $ins_map[abs($idl)] . ')' : '';
                        $d['nombre_libre'] = $nombres_ind[$idl] . $dl;
                    }
                }
                /* Deporte de largada masiva: la planilla guarda "Nombre (DNI)"
                   en `nombre_libre`. El DNI es un dato personal y el panel
                   público promete "sin datos personales", así que acá se
                   reemplaza por la DELEGACIÓN del competidor:
                   "Nombre — Delegación". Si no hay delegación conocida, se
                   muestra solo el nombre (nunca el DNI). */
                if ($idl <= 0 && preg_match('/^(.*)\s*\((\d{6,9})\)\s*$/u', (string) $d['nombre_libre'], $mm)) {
                    $dl = ($idl < 0 && isset($ins_map[abs($idl)])) ? trim((string) $ins_map[abs($idl)]) : '';
                    $d['nombre_libre'] = trim($mm[1]) . ($dl !== '' ? ' — ' . $dl : '');
                }
                /* Equipos/UTEs de una jornada masiva: agregar SU delegación al
                   nombre para que el público identifique a quién representa. */
                if ($idl > 0 && strpos((string) $d['nombre_libre'], '(') === false) {
                    $dl_u = isset($deleg_por_ute[$idl]) ? trim((string) $deleg_por_ute[$idl]) : '';
                    if ($dl_u !== '') {
                        $d['nombre_libre'] = (string) $d['nombre_libre'] . ' — ' . $dl_u;
                    }
                }
                $por_res[(int) $d['id_resultado']][] = $d;
            }
            unset($d);

            foreach ($rows as &$r) {
                $idc = (int) $r['id_categoria'];
                $dep = isset($cats[$idc]['id_deporte']) ? (int) $cats[$idc]['id_deporte'] : 0;
                $r['nombre_categoria'] = isset($cats[$idc]['nombre_categoria']) ? $cats[$idc]['nombre_categoria'] : null;
                $r['nombre_deporte']   = isset($deportes[$dep]['nombre_deporte']) ? $deportes[$dep]['nombre_deporte'] : null;
                $r['id_deporte']       = $dep ?: null;
                $r['modalidad_competencia'] = isset($deportes[$dep]['modalidad_competencia']) ? $deportes[$dep]['modalidad_competencia'] : null;

                // Contexto del partido (fixture vinculado): fase legible, fecha/hora
                // reales de juego y nombre de prueba. Nunca `nombre_evento` en el
                // encabezado público (es interno: "Final - Partido 1").
                $idf = isset($r['id_fixture']) ? (int) $r['id_fixture'] : 0;
                $fxi = ($idf > 0 && isset($fx_info[$idf])) ? $fx_info[$idf] : array();
                $r['fase']           = isset($fxi['fase']) ? $fxi['fase'] : null;
                $r['numero_fecha']   = isset($fxi['numero_fecha']) ? $fxi['numero_fecha'] : null;
                $r['nombre_prueba']  = isset($fxi['nombre_prueba']) ? $fxi['nombre_prueba'] : null;
                $r['fixture_fecha']  = isset($fxi['fecha_competencia']) ? $fxi['fecha_competencia'] : null;
                $r['fixture_hora']   = isset($fxi['hora_inicio']) ? $fxi['hora_inicio'] : null;
                // Nombre del ganador por desempate (resuelto desde la UTE o el detalle).
                $gano = isset($r['id_ute_ganador']) ? (int) $r['id_ute_ganador'] : 0;
                $r['nombre_ganador_desempate'] = null;
                if (!empty($r['hubo_desempate']) && $gano > 0) {
                    if (isset($nombres_utes[$gano]) && $nombres_utes[$gano] !== '') {
                        $r['nombre_ganador_desempate'] = $nombres_utes[$gano];
                    } else {
                        foreach ($por_res[(int) $r['id_resultado']] as $d) {
                            if ((int) $d['id_ute'] === $gano && !empty($d['nombre_libre'])) {
                                $r['nombre_ganador_desempate'] = $d['nombre_libre'];
                                break;
                            }
                        }
                    }
                }

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
                $comp = $this->_competidores_de_delegacion($delegacion);
                // Fallback por NOMBRE del detalle: resultados "libres" (sin id_ute
                // ni -id_inscripcion) se reconocen si el snapshot coincide con una
                // UTE o un equipo inscripto de la delegación.
                $nombres_propios = $this->_nombres_equipo_de_delegacion($delegacion, $comp);
                $model = $this;
                $rows = array_values(array_filter($rows, function ($r) use ($comp, $nombres_propios, $model) {
                    // Eventos masivos (TIEMPO de una jornada): la delegación
                    // participó si tiene inscriptos en esa categoría.
                    $idc = isset($r['id_categoria']) ? (int) $r['id_categoria'] : 0;
                    $es_tiempo = isset($r['tipo_resultado']) && $r['tipo_resultado'] === 'TIEMPO';
                    if ($es_tiempo && $idc > 0 && isset($comp['categorias'][$idc])) {
                        return true;
                    }
                    foreach ($r['detalle'] as $d) {
                        $idl = isset($d['id_ute']) ? (int) $d['id_ute'] : 0;
                        if ($idl > 0 && isset($comp['utes'][$idl])) return true;
                        // Individual: id_ute negativo = -id_inscripcion
                        if ($idl < 0 && isset($comp['inscripciones'][abs($idl)])) return true;
                        // Libre: match por nombre normalizado (sin datos personales).
                        $n = $model->normalizar_nombre((string) $d['nombre_libre']);
                        if ($n !== '' && isset($nombres_propios[$n])) return true;
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

    /**
     * Datos de los `fixtures` vinculados a una lista de resultados (fase, fecha,
     * hora, prueba), leídos con raw SQL acotado a los ids pedidos — igual que el
     * resto del panel público: sin Query Builder compartida y tolerando tablas o
     * columnas de migraciones que no existan.
     */
    private function _info_fixtures_de_resultados($rows) {
        $ids = array();
        foreach ($rows as $r) {
            $idf = isset($r['id_fixture']) ? (int) $r['id_fixture'] : 0;
            if ($idf > 0) $ids[$idf] = true;
        }
        if (!$ids || !$this->_tabla_existe('fixtures')) return array();

        $cols = $this->_columnas_tabla('fixtures');
        $campos = array('id_fixture');
        foreach (array('fase', 'numero_fecha', 'nombre_prueba', 'fecha_competencia', 'hora_inicio') as $c) {
            if (isset($cols[$c])) $campos[] = $c;
        }
        try {
            $q = $this->db->query(
                'SELECT `' . implode('`, `', $campos) . '` FROM `fixtures`'
                . ' WHERE `id_fixture` IN (' . implode(',', array_keys($ids)) . ')'
            );
            $fxs = ($q === false || !is_object($q)) ? array() : $q->result_array();
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_info_fixtures_de_resultados: ' . $e->getMessage());
            return array();
        }
        $mapa = array();
        foreach ($fxs as $f) {
            $mapa[(int) $f['id_fixture']] = $f;
        }
        return $mapa;
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
                log_message('error', 'Publico_model::obtener_medallero — la tabla `premiaciones` no existe.');
                return array();
            }

            // BLINDAJE IGUAL QUE FIXTURE/RESULTADOS: raw SQL, sin Query Builder
            // compartida y sin `field_exists()` (usa la QB y puede fallar).
            $cols_p = $this->_columnas_tabla('premiaciones');
            $campos_p = array('id_categoria', 'puesto', 'nombre');
            foreach (array('id_ute', 'fecha_entrega', 'entregado') as $col) {
                if (isset($cols_p[$col])) $campos_p[] = $col;
            }
            $sql = 'SELECT `' . implode('`, `', $campos_p) . '` FROM `premiaciones`';
            if (isset($cols_p['entregado'])) {
                $sql .= ' WHERE `entregado` = 1';
            }
            $orden = isset($cols_p['fecha_entrega']) ? '`fecha_entrega` ASC, ' : '';
            $sql .= ' ORDER BY ' . $orden . '`puesto` ASC';

            $query = $this->db->query($sql);
            $rows = ($query === false || !is_object($query)) ? array() : $query->result_array();
            if (!$rows) {
                log_message('error', 'Publico_model::obtener_medallero — sin filas. SQL: ' . $sql);
                return array();
            }

            // Nombres de deporte/categoría resueltos en PHP (mapas auxiliares).
            $cats = $this->_mapa('categorias', 'id_categoria', array('nombre_categoria', 'genero', 'id_deporte'));
            $deportes = $this->_mapa('deportes', 'id_deporte', array('nombre_deporte', 'modalidad_competencia'));

            // Mapa id_ute -> delegación (mayoría de integrantes). Si la tabla
            // participantes_utes está vacía (equipos armados por nombre en la
            // inscripción), se completa con inscripciones_deportivas.
            $deleg_por_ute = $this->_delegacion_por_ute();
            if (!$deleg_por_ute) {
                $deleg_por_ute = $this->_delegacion_por_ute_desde_inscripciones();
            }

            // Fallback por NOMBRE del premiado: si el id_ute no está registrado
            // (podios cargados "libres" o equipos armados por nombre), la
            // delegación se reconoce porque el snapshot `nombre` coincide con
            // el nombre de una UTE o de un equipo inscripto.
            $ute_por_nombre   = $this->_ute_por_nombre_normalizado();
            $equipo_por_nombre = $this->_equipo_por_nombre_normalizado();

            $medallero = array();
            foreach ($rows as $r) {
                $idc = isset($r['id_categoria']) ? (int) $r['id_categoria'] : 0;
                $dep = isset($cats[$idc]['id_deporte']) ? (int) $cats[$idc]['id_deporte'] : 0;
                $deleg = null;
                $idl = isset($r['id_ute']) ? (int) $r['id_ute'] : 0;
                if ($idl > 0 && isset($deleg_por_ute[$idl])) {
                    $deleg = $deleg_por_ute[$idl];
                }
                if ($deleg === null) {
                    // Individuales (id_ute NULL o negativo): la delegación del
                    // inscripto dueño de -id_inscripcion.
                    $ins_map = $this->_delegacion_por_inscripcion();
                    if ($idl < 0 && isset($ins_map[abs($idl)])) {
                        $deleg = $ins_map[abs($idl)];
                    }
                }
                if ($deleg === null && !empty($r['nombre'])) {
                    // Camino por nombre (sin DNI ni datos personales).
                    $n = $this->normalizar_nombre((string) $r['nombre']);
                    if ($n !== '' && isset($ute_por_nombre[$n])
                        && isset($deleg_por_ute[(int) $ute_por_nombre[$n]])) {
                        $deleg = $deleg_por_ute[(int) $ute_por_nombre[$n]];
                    } elseif ($n !== '' && isset($equipo_por_nombre[$n])) {
                        $deleg = $equipo_por_nombre[$n];
                    }
                }
                if ($deleg === null) {
                    continue; // premio sin delegación identificable
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
                    'deporte' => isset($deportes[$dep]['nombre_deporte']) ? $deportes[$dep]['nombre_deporte'] : null,
                    'categoria' => isset($cats[$idc]['nombre_categoria']) ? $cats[$idc]['nombre_categoria'] : null,
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

    /** Mapa id_inscripcion -> delegación (para podios individuales, id_ute < 0). */
    private function _delegacion_por_inscripcion() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = array();
        try {
            if (!$this->_tabla_existe('inscripciones_deportivas')) {
                return $cache;
            }
            $cols_i = $this->_columnas_tabla('inscripciones_deportivas');
            if (!isset($cols_i['id_participante'])) {
                return $cache;
            }
            $q = $this->db->query(
                'SELECT i.id_inscripcion, p.delegacion'
                . ' FROM inscripciones_deportivas i'
                . ' INNER JOIN participantes p ON p.id_participante = i.id_participante'
                . " WHERE p.delegacion IS NOT NULL AND p.delegacion != ''"
            );
            $rows = ($q === false || !is_object($q)) ? array() : $q->result_array();
            foreach ($rows as $r) {
                $cache[(int) $r['id_inscripcion']] = $r['delegacion'];
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_delegacion_por_inscripcion: ' . $e->getMessage());
        }
        return $cache;
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

    /**
     * Columnas reales de una tabla (clave = nombre de columna), leídas con
     * `SHOW COLUMNS` vía raw SQL y cacheadas por request. Nunca pasa por la
     * Query Builder compartida.
     */
    private function _columnas_tabla($tabla) {
        static $cache = array();
        if (isset($cache[$tabla])) {
            return $cache[$tabla];
        }
        $cols = array();
        try {
            // Identificador sanitizado: solo [A-Za-z0-9_] (nombres propios del
            // esquema, nunca input del usuario).
            $t = preg_replace('/[^A-Za-z0-9_]/', '', (string) $tabla);
            if ($t !== '') {
                $q = $this->db->query('SHOW COLUMNS FROM `' . $t . '`');
                if ($q !== false && is_object($q)) {
                    foreach ($q->result_array() as $r) {
                        if (isset($r['Field'])) $cols[$r['Field']] = true;
                    }
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_columnas_tabla(' . $tabla . '): ' . $e->getMessage());
        }
        $cache[$tabla] = $cols;
        return $cache[$tabla];
    }

    /**
     * ¿Existe la tabla? Sobre INFORMATION_SCHEMA con raw SQL (cacheado): no
     * depende del estado de la Query Builder ni de `protect_identifiers()`
     * como `table_exists()` de CI. Ante duda devuelve true: si la tabla no
     * existe, la consulta real falla igual — pero dentro del try/catch de
     * cada método, que loguea el error exacto en vez de vaciar la sección.
     */
    private function _tabla_existe($tabla) {
        static $cache = array();
        if (isset($cache[$tabla])) {
            return $cache[$tabla];
        }
        $existe = true;
        try {
            $t = preg_replace('/[^A-Za-z0-9_]/', '', (string) $tabla);
            if ($t === '') return false;
            $q = $this->db->query(
                "SELECT COUNT(*) AS c FROM information_schema.tables"
                . " WHERE table_schema = DATABASE() AND table_name = '" . $this->db->escape_str($t) . "'"
            );
            if ($q !== false && is_object($q)) {
                $row = $q->row_array();
                $existe = isset($row['c']) ? ((int) $row['c'] > 0) : true;
            }
        } catch (Throwable $e) {
            log_message('error', 'Publico_model::_tabla_existe(' . $tabla . '): ' . $e->getMessage());
            $existe = true; // ante duda, dejar intentar la consulta real (loguea el motivo)
        }
        $cache[$tabla] = $existe;
        return $existe;
    }

    /**
     * ¿Existe la columna en la tabla? Implementación BLINDADA sobre
     * `_columnas_tabla()` (SHOW COLUMNS con raw SQL): `field_exists()` de CI
     * usa la Query Builder compartida y, si una consulta previa quedó "sucia"
     * o el driver falla, devuelve false / lanza — y así se vaciaban las
     * secciones del panel público aunque los datos existieran.
     */
    private function _columna_existe($tabla, $columna) {
        $cols = $this->_columnas_tabla($tabla);
        return isset($cols[$columna]);
    }
}

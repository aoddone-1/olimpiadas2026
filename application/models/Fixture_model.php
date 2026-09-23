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

    /** Todas las UTEs de una categoría ordenadas alfabéticamente. */
    public function obtener_utes_por_categoria($id_categoria) {
        $this->db->where('id_categoria', $id_categoria);
        $this->db->order_by('nombre_ute', 'ASC');
        $utes = $this->db->get('utes')->result_array();

        // Complemento: en deportes INDIVIDUALES / MASIVOS (running, ciclismo,
        // ajedrez...) el competidor corre por su cuenta, no arma UTE/equipo.
        // Cada inscripción sin UTE se convierte en un "participante individual"
        // con id_ute negativo (para no pisar ids reales de la tabla utes).
        $individuales = $this->obtener_individuales_por_categoria($id_categoria);
        foreach ($individuales as $i) {
            $utes[] = array(
                'id_ute'      => -(int) $i['id_inscripcion'],
                'id_categoria'=> (int) $id_categoria,
                'nombre_ute'  => $i['nombre_completo'] . ' (' . $i['dni'] . ')',
            );
        }
        return $utes;
    }

    /** Inscripciones SIN UTE de una categoría (competidores individuales). */
    public function obtener_individuales_por_categoria($id_categoria) {
        $this->db->select('i.id_inscripcion, p.dni, p.nombre_completo', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->where('i.id_categoria', (int) $id_categoria);
        $this->db->where('(i.id_ute IS NULL OR i.id_ute = 0)', NULL, FALSE);
        $this->db->order_by('p.nombre_completo', 'ASC');
        return $this->db->get()->result_array();
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
        $this->db->order_by('f.numero_fecha, f.fecha_competencia, f.hora_inicio', 'ASC');
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

    /** Todas las UTEs agrupadas por categoría: id_categoria => [ute, ute, ...] */
    public function obtener_utes_agrupadas_por_categoria() {
        $this->db->order_by('nombre_ute', 'ASC');
        $utes = $this->db->get('utes')->result_array();
        $por_cat = array();
        foreach ($utes as $u) {
            $por_cat[(int) $u['id_categoria']][] = $u;
        }

        // Sumar los competidores individuales (inscripciones sin UTE), igual
        // que hace obtener_utes_por_categoria(), para que el listado general
        // también los muestre en deportes masivos/individuales.
        $this->db->select('i.id_inscripcion, i.id_categoria, p.dni, p.nombre_completo', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->where('(i.id_ute IS NULL OR i.id_ute = 0)', NULL, FALSE);
        $this->db->order_by('p.nombre_completo', 'ASC');
        $individuales = $this->db->get()->result_array();
        foreach ($individuales as $i) {
            $por_cat[(int) $i['id_categoria']][] = array(
                'id_ute'       => -(int) $i['id_inscripcion'],
                'id_categoria' => (int) $i['id_categoria'],
                'nombre_ute'   => $i['nombre_completo'] . ' (' . $i['dni'] . ')',
            );
        }
        return $por_cat;
    }

    /** Devuelve TODO el fixture de todas las categorías (vista general sin filtros). */
    public function obtener_todo_el_fixture() {
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
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria, f.numero_fecha, f.fecha_competencia, f.hora_inicio', 'ASC');

        $fixtures = $this->db->get()->result_array();

        // Adjuntar las UTEs de cada categoría (necesario para cargar los podios
        // de deportes masivos sin tener que hacer un request extra por jornada).
        $por_cat = $this->obtener_utes_agrupadas_por_categoria();
        foreach ($fixtures as &$f) {
            $f['utes_categoria'] = isset($por_cat[(int) $f['id_categoria']])
                ? $por_cat[(int) $f['id_categoria']] : array();
        }
        unset($f);

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

    /** Nombres de inscripciones individuales usadas en fixtures (clave: id negativo). */
    private function _nombres_individuales_en_fixtures(&$fixtures) {
        $nombres = array();
        if (!$this->_existe_columna_resultado()) {
            return $nombres;
        }
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
     *  GENERACIÓN AUTOMÁTICA
     * ============================================================ */

    /**
     * Genera el fixture de una categoría según el tipo de deporte:
     *  - MASIVO_TIEMPO o tipo_torneo JORNADA_UNICA -> 1 solo registro JORNADA_UNICA.
     *  - ENFRENTAMIENTO -> bracket de ELIMINACION_DIRECTA con cruces aleatorios.
     * Devuelve la cantidad de partidos generados.
     */
    public function generar_fixture_para_categoria($id_categoria) {
        $this->db->select('c.*, d.modalidad_competencia, d.tipo_duracion');
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->where('c.id_categoria', $id_categoria);
        $categoria = $this->db->get()->row_array();

        if (!$categoria) {
            throw new Exception('La categoría no existe.');
        }

        // Si ya tiene fixture, no duplicar (el borrado es una acción explícita aparte).
        $this->db->where('id_categoria', $id_categoria);
        if ($this->db->count_all_results('fixtures') > 0) {
            throw new Exception('Esta categoría ya tiene fixture generado. Usá "Borrar fixture" primero si querés regenerarlo.');
        }

        $es_masivo = ($categoria['modalidad_competencia'] === 'MASIVO_TIEMPO')
                  || ($categoria['tipo_torneo'] === 'JORNADA_UNICA');

        if ($es_masivo) {
            return $this->_generar_jornada_unica($categoria);
        }

        return $this->_generar_eliminatoria($categoria);
    }

    /**
     * Asegura que toda categoría de deporte MASIVO_TIEMPO tenga al menos una
     * jornada JORNADA_UNICA creada (para poder cargar resultados). Se ejecuta
     * cada vez que se lista el fixture, así los deportes masivos creados a mano
     * o por generación previa quedan siempre cubiertos. Devuelve la cantidad
     * de jornadas auto-creadas.
     */
    public function asegurar_jornadas_masivas() {
        // Categorías de deportes masivos (o de jornada única) que aún NO tienen
        // ninguna fila JORNADA_UNICA en fixtures.
        $this->db->select('c.id_categoria, c.dia_competencia, c.hora_competencia, c.id_lugar', FALSE);
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->group_start();
        $this->db->where('d.modalidad_competencia', 'MASIVO_TIEMPO');
        $this->db->or_where('c.tipo_torneo', 'JORNADA_UNICA');
        $this->db->group_end();
        // Sin subquery NOT EXISTS (CI3 la interpreta mal): se filtran en PHP.
        $cats = $this->db->get()->result_array();

        if (empty($cats)) {
            return 0;
        }

        // Cuáles categorías ya tienen alguna jornada creada.
        $this->db->select('DISTINCT id_categoria', FALSE);
        $this->db->where_in('fase', array('JORNADA_UNICA'));
        $con_jornada = array_column($this->db->get('fixtures')->result_array(), 'id_categoria');

        $creadas = 0;
        foreach ($cats as $c) {
            $id_cat = (int) $c['id_categoria'];
            if (in_array($id_cat, $con_jornada)) {
                continue; // ya tiene su largada
            }
            try {
                $this->_generar_jornada_unica(array(
                    'id_categoria'      => $id_cat,
                    'dia_competencia'   => $c['dia_competencia'],
                    'hora_competencia'  => $c['hora_competencia'],
                    'id_lugar'          => $c['id_lugar'],
                ));
                $creadas++;
            } catch (Exception $e) {
                // si falla una, seguimos con las demás
                log_message('error', 'asegurar_jornadas_masivas: ' . $e->getMessage());
            }
        }
        return $creadas;
    }

    /** Un solo "partido": la largada/jornada de un deporte masivo. */
    private function _generar_jornada_unica($categoria) {
        $utes = $this->obtener_utes_por_categoria($categoria['id_categoria']);

        $fecha = $categoria['dia_competencia'] ?: date('Y-m-d');
        $hora   = $categoria['hora_competencia'] ?: '09:00:00';

        // El fixture exige un lugar NOT NULL: si la categoría no tiene uno,
        // se usa el primer lugar disponible (si no hay ninguno, se avisa claro).
        $lugar = !empty($categoria['id_lugar']) ? (int) $categoria['id_lugar'] : $this->_primer_lugar();
        if (!$lugar) {
            throw new Exception('No hay lugares cargados. Creá al menos un lugar antes de generar jornadas masivas.');
        }

        $datos = array(
            'id_categoria'      => $categoria['id_categoria'],
            'id_lugar'          => $lugar,
            'id_ute_1'          => null,
            'id_ute_2'          => null,
            'nombre_prueba'     => 'Largada General (' . count($utes) . ' equipos)',
            'fase'              => 'JORNADA_UNICA',
            'numero_fecha'      => 1,
            'fecha_competencia' => $fecha,
            'hora_inicio'       => $hora,
            'hora_fin'          => $this->_sumar_horas($hora, 2),
            'estado'            => 'PROGRAMADO'
        );

        $this->db->insert('fixtures', $datos);
        return 1;
    }

    /** Bracket de eliminatoria directa con emparejamiento aleatorio. */
    private function _generar_eliminatoria($categoria) {
        $utes = $this->obtener_utes_por_categoria($categoria['id_categoria']);
        $cant = count($utes);

        if ($cant < 2) {
            throw new Exception('Se necesitan al menos 2 UTEs/equipos para generar un fixture de enfrentamiento.');
        }

        // Orden aleatorio para el cruce inicial
        shuffle($utes);

        // Tamaño del bracket: siguiente potencia de 2
        $tamano_bracket = 2;
        while ($tamano_bracket < $cant) {
            $tamano_bracket *= 2;
        }

        $slots = array_column($utes, 'id_ute');
        $excedencia = $tamano_bracket - $cant; // "bye"s en primera ronda

        // Los equipos que entran directo (bye) van a los primeros slots,
        // cruzados con NULL; el resto se empareja de a dos.
        $pares = array();
        for ($i = 0; $i < $excedencia; $i++) {
            $pares[] = array(array_shift($slots), null);
        }
        for ($i = 0; $i < count($slots); $i += 2) {
            $pares[] = array($slots[$i], isset($slots[$i + 1]) ? $slots[$i + 1] : null);
        }

        // Fases según tamaño del bracket (8 equipos -> OCTAVOS, 4 -> SEMIFINAL).
        // Para brackets de más de 8 se etiqueta como "GRUPO" (fase preliminar),
        // ya que el enum de fixtures no incluye 16AVOS/32AVOS.
        $ronda_inicial_log = (int) log($tamano_bracket, 2);
        $nombres_fases = array(1 => 'FINAL', 2 => 'SEMIFINAL', 3 => 'OCTAVOS');
        $fase_inicial = $nombres_fases[$ronda_inicial_log] ?? 'GRUPO';

        $lugar = $categoria['id_lugar'] ?: $this->_primer_lugar();
        $fecha_base = $categoria['dia_competencia'] ?: date('Y-m-d');
        $hora_base  = $categoria['hora_competencia'] ?: '09:00:00';

        $generados = 0;
        $partidos_ronda_actual = $pares;
        $fase_actual = $fase_inicial;
        $numero_fecha = 1;

        while (!empty($partidos_ronda_actual)) {
            $es_final = ($fase_actual === 'FINAL');

            foreach ($partidos_ronda_actual as $i => $par) {
                $datos = array(
                    'id_categoria'      => $categoria['id_categoria'],
                    'id_lugar'          => $lugar,
                    'id_ute_1'          => $par[0],
                    'id_ute_2'          => $par[1],
                    'nombre_prueba'     => $es_final ? 'GRAN FINAL' : $fase_actual . ' - Partido ' . ($i + 1),
                    'fase'              => $fase_actual,
                    'numero_fecha'      => $numero_fecha,
                    'fecha_competencia' => date('Y-m-d', strtotime($fecha_base . ' +' . ($numero_fecha - 1) . ' days')),
                    'hora_inicio'       => $hora_base,
                    'hora_fin'          => $this->_sumar_horas($hora_base, 1),
                    'estado'            => 'PROGRAMADO'
                );
                $this->db->insert('fixtures', $datos);
                $generados++;
            }

            if ($es_final) break;

            // Siguiente ronda: la mitad de partidos, rivales pendientes (NULL)
            $siguientes = (int) ceil(count($partidos_ronda_actual) / 2);
            $partidos_ronda_actual = array();
            for ($j = 0; $j < $siguientes; $j++) {
                $partidos_ronda_actual[] = array(null, null);
            }
            $numero_fecha++;

            // Avanzar de fase
            $pos = array_search($fase_actual, self::FASE_ORDEN);
            $fase_actual = self::FASE_ORDEN[min($pos + 1, count(self::FASE_ORDEN) - 1)];
            // Forzar semántica correcta: última ronda siempre FINAL
            if ($siguientes === 1) $fase_actual = 'FINAL';
            elseif ($siguientes === 2) $fase_actual = 'SEMIFINAL';
        }

        return $generados;
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

        // Si ya está en fase FINAL, no hay instancia superior.
        if ($fase_actual === 'FINAL') {
            return null;
        }

        // Determinar la fase destino:
        // - TERCER_PUESTO: no clasifica a nadie (define el 3er puesto) -> sin destino.
        if ($fase_actual === 'TERCER_PUESTO') {
            return null;
        }

        // Fase inmediatamente superior según el orden canónico.
        $pos_fase = array_search($fase_actual, self::FASE_ORDEN);
        if ($pos_fase === false || $pos_fase === count(self::FASE_ORDEN) - 1) {
            return null; // fase desconocida o ya es la última
        }
        $fase_destino = self::FASE_ORDEN[$pos_fase + 1];

        // La semifinal puede mandar a FINAL o a TERCER_PUESTO... pero el perdedor
        // no avanza, así que siempre buscamos la fase superior real con huecos.
        // Buscamos primero en la fase destino; si no existe, probamos más arriba.
        $hueco = $this->buscar_hueco_en_fases_superiores($partido['id_categoria'], $pos_fase + 1);
        if ($hueco !== null) {
            return $hueco;
        }

        return null;
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

        // Marcar finalizado
        $this->db->where('id_fixture', $id_fixture);
        $this->db->update('fixtures', array('estado' => 'FINALIZADO'));

        if ($partido['fase'] === 'FINAL') {
            return 'Campeón registrado. No hay instancia superior.';
        }

        $siguiente = $this->buscar_siguiente_instancia($partido);

        if (!$siguiente) {
            return 'Resultado guardado. No se encontró la instancia siguiente para clasificar.';
        }

        $this->db->where('id_fixture', $siguiente['fixture']['id_fixture']);
        $this->db->update('fixtures', array($siguiente['campo'] => $id_ganador));

        return 'Resultado guardado. El ganador clasificó a ' . $siguiente['fixture']['nombre_prueba'] . '.';
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

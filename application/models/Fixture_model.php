<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fixture_model
 * ------------
 * Genera, consulta, edita y limpia la grilla deportiva (tabla `fixtures`)
 * de la Semana Deportiva.
 *
 * Reglas de negocio implementadas en generar_todo():
 *  1. Deportes MASIVO_TIEMPO  -> 1 solo registro por categoría con fase JORNADA_UNICA,
 *     usando la fecha/hora configuradas en `categorias` (nombre_prueba, sin UTEs).
 *  2. Deportes ENFRENTAMIENTO -> cruces según tipo_torneo:
 *       TODOS_CONTRA_TODOS / GRUPO_Y_ELIMINATORIA -> Round Robin (Berger) + etapas
 *         eliminatorias (semifinales / tercer puesto / final).
 *       ELIMINACION_DIRECTA -> llaves potencias de 2 con bye rounds.
 *       JORNADA_UNICA       -> todos contra todos en una sola jornada.
 *  3. Control estricto de solapamientos: nunca dos eventos comparten
 *     id_lugar + fecha_competencia + rango horario [hora_inicio, hora_fin).
 *  4. Bloques de tiempo según disciplina (60m fútbol/vóley, 45m pádel/tenis,
 *     30m truco, 120-180m carreras, etc.).
 */
class Fixture_model extends CI_Model {

    const DIA_INICIO_POR_DEFECTO = '2026-03-09'; // lunes de la semana deportiva
    const HORA_INICIO_BLOQUE     = '08:00';      // arranque de la agenda diaria
    const TOPE_AGENDA_DIARIA     = '22:00';      // límite de corrimiento diario
    const MAX_DIAS_CORRIMIENTO   = 30;           // tope de búsqueda de hueco libre

    /** Duración (en minutos) de un partido según el deporte */
    public function duracion_por_deporte() {
        return [
            'FUTBOL 5'              => 60,
            'FUTBOL 7'              => 60,
            'FUTBOL 8'              => 60,
            'FUTBOL 11'             => 60,
            'FUTBOL'                => 60,
            'VOLEY'                 => 60,
            'BEACH VOLEY'           => 60,
            'NEWCOM'                => 60,
            'BASQUET 3X3'           => 30,
            'BASQUET 5X5'           => 60,
            'BASQUET'               => 60,
            'HANDBALL'              => 60,
            'HOCKEY'                => 60,
            'PADEL'                 => 45,
            'TENIS'                 => 45,
            'TENIS DE MESA'         => 45,
            'PING PONG'             => 45,
            'TRUCO'                 => 30,
            'BOCHAS'                => 45,
            'TEJO'                  => 45,
            'POOL'                  => 60,
            'BILLAR'                => 60,
            'CARRERA DE OBSTÁCULOS' => 120,
            'RUNNING'               => 120,
            'NATACION'              => 120,
            'MTB'                   => 180,
            'CICLISMO CALLE'        => 180,
            'CICLISMO'              => 180,
            'PESCA'                 => 180,
        ];
    }

    /** Duración en minutos de un bloque para un deporte dado */
    public function duracion_bloque($nombre_deporte) {
        $mapa = $this->duracion_por_deporte();
        $clave = $this->normalizar(strtoupper(trim((string)$nombre_deporte)));
        foreach ($mapa as $deporte => $min) {
            if ($this->normalizar($deporte) === $clave) {
                return $min;
            }
        }
        return 60; // valor por defecto seguro
    }

    /** Quita acentos y normaliza espacios para comparar nombres de deportes */
    private function normalizar($texto) {
        $texto = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $texto = strtoupper(preg_replace('/[^A-Z0-9 ]/i', '', $texto));
        return trim(preg_replace('/\s+/', ' ', $texto));
    }

    // =========================================================================
    // CONSULTAS PARA LA VISTA / FILTROS
    // =========================================================================

    /** Lugares usados como filtro */
    public function obtener_lugares() {
        $this->db->order_by('nombre', 'ASC');
        return $this->db->get('lugares')->result_array();
    }

    /** Deporte + categorías (con su tipo de torneo/lugar/día) para los filtros */
    public function obtener_deportes_con_categorias() {
        $this->db->select('d.id_deporte, d.nombre_deporte, d.modalidad_competencia,
                           c.id_categoria, c.nombre_categoria, c.tipo_torneo');
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');
        return $this->db->get()->result_array();
    }

    /** Fechas distintas ya programadas (para el filtro de fecha) */
    public function obtener_fedas_disponibles() {
        if (!$this->tabla_existe('fixtures')) {
            return [];
        }

        $this->db->distinct();
        $this->db->select('fecha_competencia');
        $this->db->from('fixtures');
        $this->db->order_by('fecha_competencia', 'ASC');
        return array_column($this->db->get()->result_array(), 'fecha_competencia');
    }

    /** Fixture completo con datos descriptivos para renderizar la grilla.
     *  $filtros: ['id_deporte', 'id_categoria', 'id_lugar', 'fecha'] (opcionales) */
    public function obtener_fixture($filtros = []) {
        if (!$this->tabla_existe('fixtures')) {
            return [];
        }

        $campos_cat = $this->columna_existe('categorias', 'tipo_torneo')
            ? 'c.id_categoria, c.nombre_categoria, c.tipo_torneo, c.id_deporte'
            : 'c.id_categoria, c.nombre_categoria, c.id_deporte';
        $d_select = $this->columna_existe('deportes', 'modalidad_competencia')
            ? 'd.nombre_deporte, d.modalidad_competencia'
            : 'd.nombre_deporte';

        $this->db->select("f.*, {$campos_cat}, {$d_select},
                           l.nombre AS nombre_lugar,
                           u1.nombre_ute AS nombre_ute_1,
                           u2.nombre_ute AS nombre_ute_2", FALSE);
        $this->db->from('fixtures f');
        $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
        $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');

        if (!empty($filtros['id_deporte']))   $this->db->where('c.id_deporte', $filtros['id_deporte']);
        if (!empty($filtros['id_categoria'])) $this->db->where('f.id_categoria', $filtros['id_categoria']);
        if (!empty($filtros['id_lugar']))     $this->db->where('f.id_lugar', $filtros['id_lugar']);
        if (!empty($filtros['fecha']))        $this->db->where('f.fecha_competencia', $filtros['fecha']);

        $this->db->order_by('f.fecha_competencia, f.hora_inicio, f.id_lugar, f.id_fixture', 'ASC');
        return $this->db->get()->result_array();
    }

    /** Resumen rápido para las tarjetas del header del panel */
    public function obtener_resumen() {
        if (!$this->tabla_existe('fixtures')) {
            return ['total' => 0, 'finalizados' => 0, 'categorias' => 0, 'dias' => 0];
        }

        $total = (int)$this->db->count_all('fixtures');

        $this->db->select('COUNT(*) AS cant', FALSE);
        $this->db->where('estado', 'FINALIZADO');
        $finalizados = (int)$this->db->get('fixtures')->row()->cant;

        $this->db->distinct();
        $this->db->select('COUNT(DISTINCT id_categoria) AS cant', FALSE);
        $cats = (int)$this->db->get('fixtures')->row()->cant;

        $dias = $this->db->query("SELECT COUNT(DISTINCT fecha_competencia) AS cant FROM fixtures")->row()->cant;

        return [
            'total'        => $total,
            'finalizados'  => $finalizados,
            'categorias'   => $cats,
            'dias'         => (int)$dias,
        ];
    }

    // =========================================================================
    // GENERACIÓN AUTOMÁTICA DEL FIXTURE
    // =========================================================================

    /**
     * Punto de entrada del botón "Generar Fixture Automático".
     * Devuelve ['exito' => bool, 'mensaje' => string, 'creados' => int]
     */
    public function generar_todo($forzar_regeneracion = false) {
        if (!$this->tabla_existe('fixtures')) {
            return [
                'exito'   => false,
                'mensaje' => 'La tabla `fixtures` no existe todavía en la base de datos. Ejecutá los cambios SQL (ver cambios.sql) para crearla.',
                'creados' => 0,
            ];
        }

        $ya_existe = (int)$this->db->count_all('fixtures') > 0;

        if ($ya_existe && !$forzar_regeneracion) {
            return [
                'exito'   => false,
                'mensaje' => 'Ya existe un fixture generado. Usá "Limpiar/Reiniciar Fixture" o activá "Forzar regeneración" antes de volver a generar.',
                'creados' => 0,
            ];
        }

        if ($ya_existe && $forzar_regeneracion) {
            $this->limpiar_todo();
        }

        $categorias = $this->obtener_categorias_para_generar();
        if (empty($categorias)) {
            return ['exito' => false, 'mensaje' => 'No hay categorías configuradas para generar.', 'creados' => 0];
        }

        $agenda      = []; // estado en memoria de los bloques ocupados por lugar/fecha
        $pendientes  = []; // partidos sin sede asignada (se reubican al final)
        $creados     = 0;
        $avisos      = [];

        foreach ($categorias as $cat) {
            $uts = $this->obtener_utes_de_categoria($cat['id_categoria']);

            if ($cat['modalidad_competencia'] === 'MASIVO_TIEMPO') {
                $creados += $this->generar_masivo($cat, $agenda, $pendientes);
                continue;
            }

            if (count($uts) < 2) {
                $avisos[] = "Se omitió «{$cat['nombre_deporte']} - {$cat['nombre_categoria']}» (necesita al menos 2 UTEs).";
                continue;
            }

            switch ($cat['tipo_torneo']) {
                case 'TODOS_CONTRA_TODOS':
                    $partidos = $this->plan_round_robin($uts, 1, true);
                    break;
                case 'JORNADA_UNICA':
                    $partidos = $this->plan_round_robin($uts, 1, false);
                    break;
                case 'ELIMINACION_DIRECTA':
                    $partidos = $this->plan_eliminacion_directa($uts);
                    break;
                case 'GRUPO_Y_ELIMINATORIA':
                default:
                    $partidos = $this->plan_grupo_y_eliminatoria($uts);
                    break;
            }

            foreach ($partidos as $p) {
                $slot = $this->agendar($cat, $p, $agenda, $pendientes);
                if ($slot !== null) {
                    $creados++;
                }
            }
        }

        // Segundo intento para los eventos que quedaron sin sede asignada:
        // se insertan ahora, reubicados en el primer lugar con hueco libre.
        $reubicados = 0;
        foreach ($pendientes as $pe) {
            if ($this->insertar_reubicado($pe['fila'], $pe['plazo'], $agenda)) {
                $reubicados++;
                $creados++;
            } else {
                $avisos[] = "«{$pe['fila']['nombre_prueba']}» no pudo ubicarse en ninguna sede dentro del cronograma.";
            }
        }

        $mensaje = "Fixture generado correctamente: {$creados} eventos creados.";
        if ($reubicados > 0) {
            $mensaje .= " Se reubicaron {$reubicados} eventos sin sede en la categoría.";
        }
        if (!empty($avisos)) {
            $mensaje .= ' ' . implode(' ', array_slice($avisos, 0, 6));
        }

        return ['exito' => true, 'mensaje' => $mensaje, 'creados' => $creados];
    }

    /** Categorías con su deporte, sede y día/hora base */
    private function obtener_categorias_para_generar() {
        $this->db->select('c.*, d.nombre_deporte, d.modalidad_competencia, l.nombre AS nombre_lugar');
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->join('lugares l', 'l.id = c.id_lugar', 'left');

        // Filtro defensivo: solo categorías cuyo deporte ya tiene la columna
        // modalidad_competencia (evita generar fixture sobre un esquema desactualizado).
        if ($this->columna_existe('deportes', 'modalidad_competencia')) {
            $this->db->where('d.modalidad_competencia !=', '');
        } else {
            return [];
        }
        if (!$this->columna_existe('categorias', 'tipo_torneo')) {
            return [];
        }

        $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');
        return $this->db->get()->result_array();
    }

    /** Comprueba si una columna existe en la tabla (esquema desactualizado) */
    private function columna_existe($tabla, $columna) {
        try {
            foreach ($this->db->list_fields($tabla) as $campo) {
                if (strcasecmp($campo, $columna) === 0) return true;
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /** Comprueba si la tabla fixtures ya fue creada */
    private function tabla_existe($tabla) {
        try {
            return in_array($tabla, $this->db->list_tables());
        } catch (Exception $e) {
            return false;
        }
    }

    /** UTEs de una categoría ordenadas por nombre */
    private function obtener_utes_de_categoria($id_categoria) {
        $this->db->select('id_ute, nombre_ute');
        $this->db->from('utes');
        $this->db->where('id_categoria', $id_categoria);
        $this->db->order_by('nombre_ute', 'ASC');
        return $this->db->get()->result_array();
    }

    // ---------------------------------------------------------------
    // Planificadores (devuelven lista de partidos abstractos)
    // ---------------------------------------------------------------

    /** MASIVO_TIEMPO: una única jornada con largada concentrada */
    private function generar_masivo($cat, &$agenda, &$pendientes) {
        $dur = $this->duracion_bloque($cat['nombre_deporte']);
        $fecha = $cat['dia_competencia'] ?: self::DIA_INICIO_POR_DEFECTO;
        $hora  = $cat['hora_competencia'] ?: self::HORA_INICIO_BLOQUE;

        $fila = [
            'id_categoria'      => $cat['id_categoria'],
            'id_lugar'          => $cat['id_lugar'] ?: 0,
            'id_ute_1'          => null,
            'id_ute_2'          => null,
            'nombre_prueba'     => 'LARGADA GENERAL - ' . strtoupper($cat['nombre_categoria']),
            'fase'              => 'JORNADA_UNICA',
            'numero_fecha'      => 1,
            'fecha_competencia' => $fecha,
            'hora_inicio'       => $hora,
            'hora_fin'          => $this->sumar_minutos($hora, $dur),
            'estado'            => 'PROGRAMADO',
        ];

        if (!$cat['id_lugar']) {
            // Sin sede configurada: se reubica al final en cualquier lugar libre
            $pendientes[] = ['fila' => $fila, 'plazo' => ['fecha' => $fecha, 'hora' => $hora]];
            return 0;
        }

        if ($this->insertar_seguro($fila, $agenda, $cat, $dur)) {
            return 1;
        }
        return 0;
    }

    /** Round Robin (método Berger). Si $todos_vs_todos divide todas las fechas en jornadas. */
    private function plan_round_robin($uts, $jornada_inicial = 1, $todos_vs_todos = true) {
        $equipos = $uts;
        if (count($equipos) % 2 !== 0) {
            $equipos[] = null; // descarte
        }
        $n     = count($equipos);
        $vueltas = $todos_vs_todos ? 2 : 1;
        $rondas = [];

        for ($vuelta = 0; $vuelta < $vueltas; $vuelta++) {
            for ($ronda = 0; $ronda < $n - 1; $ronda++) {
                $pares = [];
                for ($i = 0; $i < $n / 2; $i++) {
                    $a = $equipos[$i];
                    $b = $equipos[$n - 1 - $i];
                    if ($a !== null && $b !== null) {
                        if ($vuelta === 1) { // segunda vuelta: invierte localía
                            $tmp = $a; $a = $b; $b = $tmp;
                        }
                        $pares[] = ['eq1' => $a, 'eq2' => $b];
                    }
                }
                $rondas[] = $pares;
                // rotación Berger (fijo el primero)
                $last = array_pop($equipos);
                array_splice($equipos, 1, 0, [$last]);
            }
        }

        $partidos = [];
        foreach ($rondas as $idx => $pares) {
            foreach ($pares as $k => $par) {
                $partidos[] = [
                    'eq1'          => $par['eq1'],
                    'eq2'          => $par['eq2'],
                    'fase'         => 'GRUPO',
                    'numero_fecha' => $jornada_inicial + $idx,
                    'etiqueta'     => 'Fecha ' . ($jornada_inicial + $idx),
                    'orden'        => $k,
                ];
            }
        }
        return $partidos;
    }

    /** Eliminatoria directa con bye rounds hasta completar potencia de 2 */
    private function plan_eliminacion_directa($uts) {
        $partidos = [];
        $tamanos  = [2, 4, 8, 16, 32, 64];
        $size = 64;
        foreach ($tamanos as $t) {
            if (count($uts) <= $t) { $size = $t; break; }
        }
        $byes     = $size - count($uts);
        $num_byes = (int)floor($byes / 2);   // llaves 1..num_byes pasan directo
        $num_ronda1 = $size / 2 - $num_byes; // llaves restantes juegan la primera ronda

        $siguiente = []; // clasificados por posición de llave

        // Ronda inicial: mezclas para repartir los byes
        $orden = $this->sembrar_cruces_iniciales($uts, $size);
        for ($i = 0; $i < $size / 2; $i++) {
            $e1 = $orden[$i];
            $e2 = $orden[$size - 1 - $i];
            if ($i < $num_byes) {
                $siguiente[$i] = $e1; // bye
                continue;
            }
            $r1 = $e1;
            $r2 = $e2;
            $partidos[] = [
                'eq1'          => $r1,
                'eq2'          => $r2,
                'fase'         => $this->fase_por_llaves($size / 2),
                'numero_fecha' => 1,
                'etiqueta'     => 'Llave ' . ($i + 1),
                'orden'        => $i,
            ];
            $siguiente[$i] = ['pend' => 'Ganador Llave ' . ($i + 1)];
        }

        $llaves = $size / 2;
        $fecha  = 2;
        while ($llaves > 1) {
            $siguiente2 = [];
            $fase = $this->fase_por_llaves($llaves / 2);
            for ($i = 0; $i < $llaves / 2; $i++) {
                $e1 = $siguiente[$i * 2];
                $e2 = $siguiente[$i * 2 + 1];
                $partidos[] = [
                    'eq1'          => $e1,
                    'eq2'          => $e2,
                    'fase'         => $fase,
                    'numero_fecha' => $fecha,
                    'etiqueta'     => 'Llave ' . ($i + 1),
                    'orden'        => $i,
                ];
                $siguiente2[$i] = ['pend' => 'Ganador Llave ' . (($llaves / 2) + $i + 1)];
            }
            $siguiente = $siguiente2;
            $llaves /= 2;
            $fecha++;
        }

        // Definición de tercer puesto (si hubo semifinales)
        if ($size >= 4) {
            $partidos[] = [
                'eq1'          => ['pend' => 'Perdedor Semifinal 1'],
                'eq2'          => ['pend' => 'Perdedor Semifinal 2'],
                'fase'         => 'TERCER_PUESTO',
                'numero_fecha' => $fecha,
                'etiqueta'     => 'Tercer Puesto',
                'orden'        => 0,
            ];
            $partidos[] = [
                'eq1'          => $siguiente[0],
                'eq2'          => $siguiente[1] ?? ['pend' => 'Ganador Llave Final'],
                'fase'         => 'FINAL',
                'numero_fecha' => $fecha,
                'etiqueta'     => 'Final',
                'orden'        => 1,
            ];
        } else {
            $partidos[] = [
                'eq1'          => $siguiente[0],
                'eq2'          => $siguiente[1] ?? ['pend' => 'Ganador'],
                'fase'         => 'FINAL',
                'numero_fecha' => $fecha,
                'etiqueta'     => 'Final',
                'orden'        => 1,
            ];
        }

        return $partidos;
    }

    /** GRUPO_Y_ELIMINATORIA: todos contra todos en grupos + cruces A vs B */
    private function plan_grupo_y_eliminatoria($uts) {
        $shuffled = $uts;
        shuffle($shuffled);
        $grupos = [[], []];
        foreach ($shuffled as $i => $u) {
            $grupos[$i % 2][] = $u;
        }

        $partidos = [];
        $gA = $this->plan_round_robin($grupos[0], 1, true);
        $gB = $this->plan_round_robin($grupos[1], 1, true);
        foreach ($gA as $p) { $p['etiqueta'] = 'Grupo A - ' . $p['etiqueta']; $partidos[] = $p; }
        foreach ($gB as $p) { $p['etiqueta'] = 'Grupo B - ' . $p['etiqueta']; $partidos[] = $p; }

        $fecha_base = max(
            !empty($gA) ? (int)end($gA)['numero_fecha'] : 1,
            !empty($gB) ? (int)end($gB)['numero_fecha'] : 1
        ) + 1;

        $semis = [
            ['Ganador Grupo A', 'Segundo Grupo B', 'Semifinal 1'],
            ['Ganador Grupo B', 'Segundo Grupo A', 'Semifinal 2'],
        ];
        foreach ($semis as $i => $s) {
            $partidos[] = [
                'eq1'          => ['pend' => $s[0]],
                'eq2'          => ['pend' => $s[1]],
                'fase'         => 'SEMIFINAL',
                'numero_fecha' => $fecha_base,
                'etiqueta'     => $s[2],
                'orden'        => $i,
            ];
        }
        $partidos[] = [
            'eq1'          => ['pend' => 'Perdedor Semifinal 1'],
            'eq2'          => ['pend' => 'Perdedor Semifinal 2'],
            'fase'         => 'TERCER_PUESTO',
            'numero_fecha' => $fecha_base + 1,
            'etiqueta'     => 'Tercer Puesto',
            'orden'        => 0,
        ];
        $partidos[] = [
            'eq1'          => ['pend' => 'Ganador Semifinal 1'],
            'eq2'          => ['pend' => 'Ganador Semifinal 2'],
            'fase'         => 'FINAL',
            'numero_fecha' => $fecha_base + 1,
            'etiqueta'     => 'Final',
            'orden'        => 1,
        ];

        return $partidos;
    }

    /** Nombre de fase según la cantidad de llaves de esa ronda */
    private function fase_por_llaves($llaves) {
        switch ((int)$llaves) {
            case 1:  return 'FINAL';
            case 2:  return 'SEMIFINAL';
            case 4:  return 'CUARTOS';
            case 8:  return 'OCTAVOS';
            case 16: return '16AVOS';
            default: return '16AVOS';
        }
    }

    /** Orden inicial de equipos para repartir bye rounds de forma pareja */
    private function sembrar_cruces_iniciales($uts, $size) {
        $orden = [];
        $idx = 0;
        while (count($orden) < $size) {
            $orden[] = $uts[$idx % count($uts)];
            $idx++;
        }
        return $orden;
    }

    // ---------------------------------------------------------------
    // Agendado con control de solapamiento
    // ---------------------------------------------------------------

    /** Inserta un partido buscando el primer hueco libre en la sede de la categoría */
    private function agendar($cat, $p, &$agenda, &$pendientes) {
        $eq1 = $this->resolver_equipo($p['eq1']);
        $eq2 = $this->resolver_equipo($p['eq2']);
        $dur = $this->duracion_bloque($cat['nombre_deporte']);

        $fecha_base = $cat['dia_competencia'] ?: self::DIA_INICIO_POR_DEFECTO;
        $hora_base  = $cat['hora_competencia'] ?: self::HORA_INICIO_BLOQUE;

        // Los partidos de fases avanzadas se juegan después de la fase de grupos
        $plazo = $this->calcular_plazo($p, $cat, $fecha_base, $hora_base, $dur);

        $fila = [
            'id_categoria'      => $cat['id_categoria'],
            'id_lugar'          => $cat['id_lugar'] ?: 0,
            'id_ute_1'          => $eq1['id'],
            'id_ute_2'          => $eq2['id'],
            'nombre_prueba'     => $eq1['pend'] || $eq2['pend'] ? $p['etiqueta'] : null,
            'fase'              => $p['fase'],
            'numero_fecha'      => $p['numero_fecha'],
            'fecha_competencia' => $plazo['fecha'],
            'hora_inicio'       => $plazo['hora'],
            'hora_fin'          => $this->sumar_minutos($plazo['hora'], $dur),
            'estado'            => 'PROGRAMADO',
        ];

        if (!$cat['id_lugar']) {
            // Sin sede configurada: se deja pendiente para reubicarlo al final
            $pendientes[] = ['fila' => $fila, 'plazo' => $plazo];
            return null;
        }

        if ($this->insertar_seguro($fila, $agenda, $cat, $dur, $plazo)) {
            return $fila;
        }

        // No hubo hueco en la sede de la categoría: reintento global al final
        $pendientes[] = ['fila' => $fila, 'plazo' => $plazo];
        return null;
    }

    /** Convierte un equipo (UTE real o clasificado pendiente) en [id, pend] */
    private function resolver_equipo($eq) {
        if (is_array($eq) && isset($eq['pend'])) {
            return ['id' => null, 'pend' => $eq['pend']];
        }
        return ['id' => $eq['id_ute'], 'pend' => null];
    }

    /** Fecha/hora más temprana posible para un partido (respetando predecesores) */
    private function calcular_plazo($p, $cat, $fecha_base, $hora_base, $dur) {
        $offset_dias = (int)max(0, $p['numero_fecha'] - 1);
        $fecha = date('Y-m-d', strtotime($fecha_base . " +{$offset_dias} days"));

        // Fase de grupos: arranca en el día base. Fases finales: día siguiente.
        if ($p['fase'] !== 'GRUPO') {
            $fecha = date('Y-m-d', strtotime($fecha . ' +1 day'));
        }

        return ['fecha' => $fecha, 'hora' => $hora_base];
    }

    /** Intenta insertar respetando solapamientos; devuelve true si tuvo éxito */
    private function insertar_seguro(&$fila, &$agenda, $cat, $dur, $plazo = null) {
        $lugar = (int)$fila['id_lugar'];
        $plazo = $plazo ?: ['fecha' => $fila['fecha_competencia'], 'hora' => $fila['hora_inicio']];

        $hueco = $this->buscar_hueco($lugar, $plazo['fecha'], $plazo['hora'], $dur, $agenda);
        if ($hueco === null) {
            return false;
        }

        $fila['fecha_competencia'] = $hueco['fecha'];
        $fila['hora_inicio']       = $hueco['inicio'];
        $fila['hora_fin']          = $hueco['fin'];

        $this->db->insert('fixtures', $fila);
        $id = $this->db->insert_id();
        if ($id) {
            $fila['id_fixture'] = $id;
            $this->marcar_bloque($lugar, $hueco['fecha'], $hueco['ini_min'], $hueco['fin_min'], $agenda);
            return true;
        }
        return false;
    }

    /** Busca el primer bloque horario libre (lugar+fecha) desde la hora pedida */
    private function buscar_hueco($lugar, $fecha_desde, $hora_desde, $dur, &$agenda) {
        for ($d = 0; $d <= self::MAX_DIAS_CORRIMIENTO; $d++) {
            $fecha = date('Y-m-d', strtotime($fecha_desde . " +{$d} days"));
            $ocupados = $this->ocupados_del_dia($lugar, $fecha, $agenda);
            $inicio_min = ($d === 0) ? $this->to_min($hora_desde) : $this->to_min(self::HORA_INICIO_BLOQUE);
            $tope = $this->to_min(self::TOPE_AGENDA_DIARIA);

            for ($t = $inicio_min; $t + $dur <= $tope; $t += 15) {
                if (!$this->hay_solapamiento($t, $t + $dur, $ocupados)) {
                    return [
                        'fecha'   => $fecha,
                        'inicio'  => $this->to_hhmm($t),
                        'fin'     => $this->to_hhmm($t + $dur),
                        'ini_min' => $t,
                        'fin_min' => $t + $dur,
                    ];
                }
            }
        }
        return null;
    }

    /** Inserta por primera vez un evento diferido, buscando sede con hueco libre */
    private function insertar_reubicado(&$fila, $plazo, &$agenda) {
        $lugares = $this->db->get('lugares')->result_array();
        $dur = $this->duracion_bloque($this->nombre_deporte_de_categoria($fila['id_categoria']));
        foreach ($lugares as $lug) {
            $hueco = $this->buscar_hueco((int)$lug['id'], $plazo['fecha'], $plazo['hora'], $dur, $agenda);
            if ($hueco !== null) {
                $fila['id_lugar'] = (int)$lug['id'];
                $fila['fecha_competencia'] = $hueco['fecha'];
                $fila['hora_inicio'] = $hueco['inicio'];
                $fila['hora_fin'] = $hueco['fin'];
                $this->db->insert('fixtures', $fila);
                $id = $this->db->insert_id();
                if ($id) {
                    $fila['id_fixture'] = $id;
                    $this->marcar_bloque((int)$lug['id'], $hueco['fecha'], $hueco['ini_min'], $hueco['fin_min'], $agenda);
                    return true;
                }
                return false;
            }
        }
        return false;
    }

    private function nombre_deporte_de_categoria($id_categoria) {
        $this->db->select('d.nombre_deporte');
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->where('c.id_categoria', $id_categoria);
        $row = $this->db->get()->row_array();
        return $row ? $row['nombre_deporte'] : '';
    }

    // ---------------------------------------------------------------
    // Utilidades de agenda / horarios
    // ---------------------------------------------------------------

    private function marcar_bloque($lugar, $fecha, $ini, $fin, &$agenda) {
        $clave = $lugar . '|' . $fecha;
        if (!isset($agenda[$clave])) $agenda[$clave] = [];
        $agenda[$clave][] = [$ini, $fin];
    }

    private function ocupados_del_dia($lugar, $fecha, &$agenda) {
        $clave = $lugar . '|' . $fecha;
        if (!isset($agenda[$clave])) {
            // Carga perezosa desde BD (incluye registros previos si los hubiera)
            $this->db->select('TIME_TO_SEC(hora_inicio)/60 AS ini, TIME_TO_SEC(hora_fin)/60 AS fin');
            $this->db->where('id_lugar', $lugar);
            $this->db->where('fecha_competencia', $fecha);
            $rows = $this->db->get('fixtures')->result_array();
            $agenda[$clave] = array_map(function ($r) {
                return [(int)$r['ini'], (int)$r['fin']];
            }, $rows);
        }
        return $agenda[$clave];
    }

    private function hay_solapamiento($ini, $fin, $ocupados) {
        foreach ($ocupados as $o) {
            if ($ini < $o[1] && $o[0] < $fin) {
                return true;
            }
        }
        return false;
    }

    private function to_min($hhmm) {
        $p = explode(':', $hhmm);
        return (int)$p[0] * 60 + (int)($p[1] ?? 0);
    }

    private function to_hhmm($min) {
        return sprintf('%02d:%02d', floor($min / 60), $min % 60);
    }

    private function sumar_minutos($hhmm, $minutos) {
        return $this->to_hhmm($this->to_min($hhmm) + (int)$minutos);
    }

    // =========================================================================
    // EDICIÓN MANUAL Y LIMPIEZA
    // =========================================================================

    /** Actualiza fecha/hora/lugar/estado de un evento puntual */
    public function actualizar_evento($id_fixture, $datos) {
        $update = [];
        if (!empty($datos['fecha_competencia'])) $update['fecha_competencia'] = $datos['fecha_competencia'];
        if (!empty($datos['hora_inicio']))       $update['hora_inicio'] = $datos['hora_inicio'] . ':00';
        if (!empty($datos['hora_fin']))          $update['hora_fin'] = $datos['hora_fin'] . ':00';
        if (!empty($datos['id_lugar']))          $update['id_lugar'] = (int)$datos['id_lugar'];
        if (!empty($datos['estado']))            $update['estado'] = $datos['estado'];

        if (empty($update)) return false;

        $this->db->where('id_fixture', $id_fixture);
        return $this->db->update('fixtures', $update);
    }

    /** Verifica solapamientos manuales devolviendo el conflicto encontrado */
    public function encontrar_conflicto($id_lugar, $fecha, $hora_ini, $hora_fin, $excepto = null) {
        $this->db->select('f.id_fixture, TIME(f.hora_inicio) hi, TIME(f.hora_fin) hf,
                           COALESCE(u1.nombre_ute, f.nombre_prueba) eq1,
                           COALESCE(u2.nombre_ute, \'\') eq2');
        $this->db->from('fixtures f');
        $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
        $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');
        $this->db->where('f.id_lugar', $id_lugar);
        $this->db->where('f.fecha_competencia', $fecha);
        $this->db->where('f.hora_inicio <', $hora_fin);
        $this->db->where('f.hora_fin >', $hora_ini);
        if ($excepto) $this->db->where('f.id_fixture !=', $excepto);
        return $this->db->get()->row_array();
    }

    /** Borra todo el fixture generado */
    public function limpiar_todo() {
        return $this->db->empty_table('fixtures');
    }
}

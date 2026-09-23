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
        return $this->db->get('utes')->result_array();
    }

    /** Partidos (fixtures) de una categoría, con nombres resueltos. */
    public function obtener_fixtures_por_categoria($id_categoria) {
        $this->db->select('
            f.*,
            u1.nombre_ute as ute_1_nombre,
            u2.nombre_ute as ute_2_nombre,
            l.nombre as lugar_nombre,
            c.nombre_categoria,
            d.nombre_deporte
        ', FALSE);
        $this->db->from('fixtures f');
        $this->db->join('utes u1', 'u1.id_ute = f.id_ute_1', 'left');
        $this->db->join('utes u2', 'u2.id_ute = f.id_ute_2', 'left');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'left');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
        $this->db->where('f.id_categoria', $id_categoria);
        $this->db->order_by('f.numero_fecha, f.fecha_competencia, f.hora_inicio', 'ASC');
        return $this->db->get()->result_array();
    }

    public function obtener_fixture_por_id($id_fixture) {
        $this->db->where('id_fixture', $id_fixture);
        return $this->db->get('fixtures')->row_array();
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

    /** Un solo "partido": la largada/jornada de un deporte masivo. */
    private function _generar_jornada_unica($categoria) {
        $utes = $this->obtener_utes_por_categoria($categoria['id_categoria']);

        $fecha = $categoria['dia_competencia'] ?: date('Y-m-d');
        $hora   = $categoria['hora_competencia'] ?: '09:00:00';

        $datos = array(
            'id_categoria'      => $categoria['id_categoria'],
            'id_lugar'          => $categoria['id_lugar'] ?: $this->_primer_lugar(),
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

    /** Crear/editar un partido manualmente. */
    public function guardar_partido($datos) {
        $payload = array(
            'id_categoria'      => (int) $datos['id_categoria'],
            'id_lugar'          => !empty($datos['id_lugar']) ? (int) $datos['id_lugar'] : null,
            'id_ute_1'          => !empty($datos['id_ute_1']) ? (int) $datos['id_ute_1'] : null,
            'id_ute_2'          => !empty($datos['id_ute_2']) ? (int) $datos['id_ute_2'] : null,
            'nombre_prueba'     => trim($datos['nombre_prueba']),
            'fase'              => $datos['fase'],
            'numero_fecha'      => (int) $datos['numero_fecha'],
            'fecha_competencia' => $datos['fecha_competencia'],
            'hora_inicio'       => $datos['hora_inicio'],
            'hora_fin'          => $datos['hora_fin'],
            'estado'            => $datos['estado'],
        );

        if (!empty($datos['id_fixture'])) {
            $this->db->where('id_fixture', (int) $datos['id_fixture']);
            $this->db->update('fixtures', $payload);
            return (int) $datos['id_fixture'];
        }

        $this->db->insert('fixtures', $payload);
        return $this->db->insert_id();
    }

    /**
     * Registrar resultado: ganador pasa a la siguiente fecha de la misma categoría.
     * Busca el próximo partido (numero_fecha mayor) con un slot libre y lo completa.
     */
    public function registrar_resultado($id_fixture, $id_ganador) {
        $partido = $this->obtener_fixture_por_id($id_fixture);
        if (!$partido) {
            throw new Exception('Partido no encontrado.');
        }
        if ($partido['fase'] === 'JORNADA_UNICA') {
            throw new Exception('Los deportes de jornada única no generan clasificados.');
        }

        // Marcar finalizado
        $this->db->where('id_fixture', $id_fixture);
        $this->db->update('fixtures', array('estado' => 'FINALIZADO'));

        if ($partido['fase'] === 'FINAL') {
            return 'Campeón registrado. No hay instancia superior.';
        }

        // Buscar el siguiente partido con un hueco libre
        $this->db->where('id_categoria', $partido['id_categoria']);
        $this->db->where('numero_fecha >', $partido['numero_fecha']);
        $this->db->where('(id_ute_1 IS NULL OR id_ute_2 IS NULL)', null, false);
        $this->db->order_by('numero_fecha', 'ASC');
        $this->db->limit(1);
        $siguiente = $this->db->get('fixtures')->row_array();

        if (!$siguiente) {
            return 'Resultado guardado. No se encontró la instancia siguiente para clasificar.';
        }

        $campo = is_null($siguiente['id_ute_1']) ? 'id_ute_1' : 'id_ute_2';
        $this->db->where('id_fixture', $siguiente['id_fixture']);
        $this->db->update('fixtures', array($campo => $id_ganador));

        return 'Resultado guardado. El ganador clasificó a ' . $siguiente['nombre_prueba'] . '.';
    }

    /** Borrar todo el fixture de una categoría. */
    public function eliminar_fixture_por_categoria($id_categoria) {
        $this->db->where('id_categoria', $id_categoria);
        return $this->db->delete('fixtures');
    }

    public function eliminar_partido($id_fixture) {
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

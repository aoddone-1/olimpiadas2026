<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Premiacion_model
 * Pestaña "Premiación" de Control Total: resumen diario de las entregas de
 * premios de las competencias CERRADAS que ya tienen resultados.
 *
 * Reglas de negocio:
 *  - Deportes UNICO_DIA: cierran el mismo día -> se premian esa noche.
 *  - Deportes MULTIDIA: cierran el día de la definición (fecha de la FINAL /
 *    última jornada) -> se premian esa noche.
 *  - TIEMPO / PUNTAJE (MASIVO_TIEMPO): se premia a los puestos 1, 2 y 3 del
 *    resultado con más posiciones cargadas.
 *  - MARCADOR (ENFRENTAMIENTO): el podio es el del TORNEO COMPLETO, no del
 *    último partido. Hay DOS sistemas, definidos POR CATEGORÍA en
 *    categorias.sistema_definicion (ver sql/migracion_puntaje_categoria.sql;
 *    dentro de un mismo deporte puede haber categorías con uno y con otro):
 *      FASE_FINAL (por defecto):
 *        1º = ganador de la FINAL
 *        2º = perdedor de la FINAL
 *        3º = ganador del partido por el TERCER_PUESTO (si existe; si no, queda
 *             "por definir").
 *      PUNTAJE (liga / todos contra todos): se arma la TABLA DE PUNTOS de ESA
 *        categoría con TODOS los partidos cargados (MARCADOR y/o PUNTAJE):
 *          + puntosVictoria de la categoría (def: 3) al ganador
 *          + puntosEmpate de la categoría (def: 1) a cada equipo si empataron
 *          + 0 al perdedor
 *        El podio sale de esa tabla: 1º = puntero, 2º = segundo, 3º = tercero.
 *        Desempates entre equipos con iguales puntos (en orden):
 *          1) diferencia de goles/tantos, 2) tantos a favor, 3) menos tantos
 *          contra, 4) resultado directo entre ambos, 5) sorteo (se avisa).
 *        No hace falta que exista una FINAL: la premiación "cierra" cuando
 *        todas las parejas de equipos ya jugaron entre sí (todos contra
 *        todos) o cuando el fixture tiene su FINAL/última jornada marcada
 *        como FINALIZADO.
 * La entrega queda registrada en la tabla `premiaciones` (ver sql/premiaciones.sql).
 */
class Premiacion_model extends CI_Model {

    /** ¿Existe la tabla de premiaciones? (aviso amigable si falta el SQL). */
    public function tablas_existentes() {
        return $this->_tabla_existe('premiaciones');
    }

    /**
     * Diagnóstico rápido para el mensaje de error: qué tablas/columnas hacen
     * falta para que la Premiación pueda leer los resultados.
     */
    public function diagnostico_faltantes() {
        $faltan = array();
        foreach (array('resultados', 'resultado_detalle', 'fixtures', 'premiaciones') as $t) {
            if (!$this->_tabla_existe($t)) $faltan[] = 'la tabla `' . $t . '`';
        }
        if ($this->_tabla_existe('resultados') && !$this->_columna_resultado_existe('id_fixture')) {
            $faltan[] = 'la columna `resultados.id_fixture`';
        }
        return $faltan;
    }

    /**
     * ¿El ENUM `resultados.tipo_resultado` acepta PUNTAJE? (sql/migracion_puntaje.sql).
     * Si no, avisamos que los puntajes NO se pueden guardar ni premiar.
     */
    public function puntaje_soportado() {
        static $ok = null;
        if ($ok !== null) return $ok;
        try {
            $q = $this->db->query(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'resultados'
                   AND COLUMN_NAME = 'tipo_resultado'
                   AND COLUMN_TYPE LIKE '%PUNTAJE%'"
            );
            $ok = (bool) ($q && $q->num_rows() > 0);
        } catch (Throwable $e) {
            log_message('error', '[Premiación] no se pudo verificar el ENUM tipo_resultado: ' . $e->getMessage());
            $ok = true; // si no se puede comprobar, no bloquear la carga
        }
        return $ok;
    }

    /**
     * Normaliza cualquier fecha ('2026-09-24', '2026-09-24 00:00:00',
     * '24/09/2026'...) a 'Y-m-d'. Devuelve null si no se entiende.
     */
    private function _norm_fecha($f) {
        $f = trim((string) $f);
        if ($f === '') return null;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $f, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})#', $f, $m)) {
            return $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }
        $ts = strtotime($f);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    /** ¿Existe una tabla? (tolera bases sin alguna dependencia). */
    private function _tabla_existe($tabla) {
        static $cache = array();
        if (!isset($cache[$tabla])) {
            try {
                $cache[$tabla] = (bool) $this->db->table_exists($tabla);
            } catch (Throwable $e) {
                $cache[$tabla] = false;
            }
        }
        return $cache[$tabla];
    }

    /**
     * Filas de la última consulta construida (select/from/join/where/order_by),
     * o de una tabla si se pasa por argumento. Nunca revienta: si la tabla no
     * existe o la consulta falla, devuelve array vacío (era la causa del error
     * "Call to a member function result_array() on bool").
     */
    private function _filas_seguras($tabla = null) {
        try {
            if ($tabla !== null && !$this->_tabla_existe($tabla)) return array();
            $q = $tabla === null ? $this->db->get() : $this->db->get($tabla);
            if (!$q) return array();
            return $q->result_array();
        } catch (Throwable $e) {
            log_message('error', '[Premiación] no se pudo leer ' . ($tabla ?: 'última consulta') . ': ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Fila de la última consulta construida (o de una tabla si se pasa por
     * argumento). Devuelve null ante error en lugar de fatal
     * "call to a member function row_array() on bool".
     */
    private function _fila_segura($tabla = null) {
        try {
            $q = $tabla === null ? $this->db->get() : $this->db->get($tabla);
            if (!$q) return null;
            $row = $q->row_array();
            return $row ? $row : null;
        } catch (Throwable $e) {
            log_message('error', '[Premiación] consulta fallida: ' . $e->getMessage());
            return null;
        }
    }

    /* ============================================================
     *  PODIOS (resolución desde resultados / fixture)
     * ============================================================ */

    /**
     * Lee un resultado MARCADOR (dos filas, cada una desde la perspectiva de
     * su propio equipo) y devuelve los equipos con sus tantos. Tolera datos
     * viejos "espejados" (ambas filas con el mismo par de números).
     */
    private function _parsear_marcador($detalle) {
        if (count($detalle) < 2) return null;
        $a = $detalle[0];
        $b = $detalle[1];
        $eqA = array(
            'id'    => $a['id_ute'] !== null ? (int) $a['id_ute'] : null,
            'nombre'=> trim((string) $a['nombre_libre']),
            'tantos'=> (int) $a['marcador_local'],
            'rival' => (int) $a['marcador_visita'],
        );
        $eqB = array(
            'id'    => $b['id_ute'] !== null ? (int) $b['id_ute'] : null,
            'nombre'=> trim((string) $b['nombre_libre']),
            'tantos'=> (int) $b['marcador_local'],
            'rival' => (int) $b['marcador_visita'],
        );
        // Datos espejados (patrón viejo): ambas filas con los mismos números.
        if ($eqA['tantos'] === $eqB['tantos'] && $eqA['rival'] === $eqB['rival']) {
            $tmp = $eqB['tantos'];
            $eqB['tantos'] = $eqB['rival'];
            $eqB['rival'] = $tmp;
        }
        if ($eqA['nombre'] === '' && $eqB['nombre'] === '') return null;
        return compact('eqA', 'eqB');
    }

    /** Podio de un resultado TIEMPO / PUNTAJE: posiciones 1, 2 y 3 del detalle. */
    private function _podio_desde_tiempo($resultado) {
        $es_puntaje = (isset($resultado['tipo_resultado']) && $resultado['tipo_resultado'] === 'PUNTAJE');
        $ordenadas = $resultado['detalle'];
        usort($ordenadas, function ($a, $b) {
            return (int) $a['posicion'] - (int) $b['posicion'];
        });
        $nombres = array();
        foreach (array_slice($ordenadas, 0, 3) as $i => $d) {
            $pos = (int) $d['posicion'];
            if ($pos < 1 || $pos > 3) continue;
            $nombres[$pos] = array(
                'id'     => $d['id_ute'] !== null ? (int) $d['id_ute'] : null,
                'nombre' => trim((string) $d['nombre_libre']),
                // PUNTAJE se guarda en la misma columna `tiempo`: el texto del
                // podio debe decir "Pts:", no "Tiempo:".
                'extra'  => $d['tiempo'] ? ($es_puntaje ? 'Puntaje: ' : 'Tiempo: ') . $d['tiempo'] : null,
            );
        }
        return $nombres;
    }

    /**
     * Podio de una categoría ENFRENTAMIENTO (multidia o single elimination):
     *  1º/2º desde el resultado de la FINAL, 3º desde el TERCER_PUESTO.
     * Devuelve también el id_resultado de referencia de cada puesto.
     */
    private function _podio_marcador_categoria($id_categoria) {
        $out = array();

        $final = $this->_resultado_por_fase($id_categoria, 'FINAL');
        if ($final) {
            $m = $this->_parsear_marcador($final['detalle']);
            if ($m) {
                // ¿Hubo desempate registrado? (penales, prórroga, puntos de
                // oro...). Si no hay ganador explícito, lo buscamos por nombre
                // en el detalle por si el equipo fue cargado "libre" sin UTE.
                $des = $this->_resolver_desempate($final, $m);
                if ($m['eqA']['tantos'] !== $m['eqB']['tantos'] || $des) {
                    if ($des) {
                        $ganador  = $des['eq_ganador'];
                        $perdedor = $des['eq_perdedor'];
                        $sufijo = ' · 🏅 ganó ' . $ganador['nombre'] . ' en ' . $this->_nombre_metodo_desempate($des['metodo']);
                    } else {
                        $ganador  = $m['eqA']['tantos'] > $m['eqB']['tantos'] ? $m['eqA'] : $m['eqB'];
                        $perdedor = $ganador === $m['eqA'] ? $m['eqB'] : $m['eqA'];
                        $sufijo = '';
                    }
                    $marca = 'Final: ' . $this->_texto_marca($m) . $sufijo;
                    $out[1] = array('id' => $ganador['id'], 'nombre' => $ganador['nombre'],
                                    'extra' => $marca,
                                    'desempate' => $des ? $this->_nombre_metodo_desempate($des['metodo']) : null,
                                    'id_resultado_ref' => (int) $final['id_resultado'],
                                    // día en que se jugó la final (fixture) y
                                    // partido del fixture al que pertenece
                                    'fecha_jugada' => $this->_norm_fecha($final['fx_fecha'] ?? null),
                                    'id_fixture' => !empty($final['fx_id']) ? (int) $final['fx_id'] : (!empty($final['id_fixture']) ? (int) $final['id_fixture'] : null));
                    $out[2] = array('id' => $perdedor['id'], 'nombre' => $perdedor['nombre'],
                                    'extra' => $marca,
                                    'desempate' => $des ? $this->_nombre_metodo_desempate($des['metodo']) : null,
                                    'id_resultado_ref' => (int) $final['id_resultado'],
                                    'fecha_jugada' => $this->_norm_fecha($final['fx_fecha'] ?? null),
                                    'id_fixture' => !empty($final['fx_id']) ? (int) $final['fx_id'] : (!empty($final['id_fixture']) ? (int) $final['id_fixture'] : null));
                } else {
                    // Final en empate SIN desempate registrado: no hay podio
                    // definido todavía (falta cargar cómo se quebró el empate).
                    return array();
                }
            }
        }

        $tp = $this->_resultado_por_fase($id_categoria, 'TERCER_PUESTO');
        if ($tp) {
            $m = $this->_parsear_marcador($tp['detalle']);
            if ($m) {
                $des = $this->_resolver_desempate($tp, $m);
                if ($m['eqA']['tantos'] !== $m['eqB']['tantos'] || $des) {
                    if ($des) {
                        $ganador = $des['eq_ganador'];
                        $sufijo = ' · 🏅 ganó ' . $ganador['nombre'] . ' en ' . $this->_nombre_metodo_desempate($des['metodo']);
                    } else {
                        $ganador = $m['eqA']['tantos'] > $m['eqB']['tantos'] ? $m['eqA'] : $m['eqB'];
                        $sufijo = '';
                    }
                    $out[3] = array('id' => $ganador['id'], 'nombre' => $ganador['nombre'],
                                    'extra' => 'Tercer puesto: ' . $this->_texto_marca($m) . $sufijo,
                                    'desempate' => $des ? $this->_nombre_metodo_desempate($des['metodo']) : null,
                                    'id_resultado_ref' => (int) $tp['id_resultado']);
                }
            }
        }

        return $out;
    }

    /**
     * Resuelve el desempate registrado en la cabecera de un resultado MARCADOR
     * con marcador empatado. Devuelve el método + los equipos ordenados
     * (ganador/perdedor) o null si no hay desempate utilizable. Tolera bases
     * donde las columnas de desempate aún no existen.
     */
    private function _resolver_desempate($resultado, $m) {
        if (empty($resultado['hubo_desempate'])) return null;
        $metodo = trim((string) ($resultado['desempate_metodo'] ?? ''));
        if ($metodo === '') return null;
        $id_gan = isset($resultado['id_ute_ganador']) ? (int) $resultado['id_ute_ganador'] : 0;
        $nombre_gan = trim((string) ($resultado['nombre_ganador_desempate'] ?? ''));

        $ganador = null;
        $perdedor = null;
        foreach (array('eqA', 'eqB') as $k) {
            $otro = $k === 'eqA' ? 'eqB' : 'eqA';
            if (($id_gan && $m[$k]['id'] === $id_gan)
                || ($nombre_gan !== '' && strcasecmp($m[$k]['nombre'], $nombre_gan) === 0)) {
                $ganador = $m[$k];
                $perdedor = $m[$otro];
                break;
            }
        }
        if (!$ganador || !$perdedor) return null;
        return array('metodo' => $metodo, 'eq_ganador' => $ganador, 'eq_perdedor' => $perdedor);
    }

    /** Nombre legible del método de desempate (PENALES -> Penales). */
    private function _nombre_metodo_desempate($metodo) {
        $mapa = array(
            'PENALES'          => 'penales',
            'PRORROGA'         => 'prórroga',
            'PUNTOS_DE_ORO'    => 'puntos de oro',
            'MUERTE_SUBITA'    => 'muerte súbita',
            'LANZAMIENTO_TIRLIBRE' => 'lanzamiento tirlibre',
            'OTRO'             => 'desempate',
        );
        $metodo = strtoupper(trim((string) $metodo));
        if (isset($mapa[$metodo])) return $mapa[$metodo];
        return strtolower(str_replace('_', ' ', $metodo));
    }

    private function _texto_marca($m) {
        return $m['eqA']['nombre'] . ' ' . $m['eqA']['tantos'] . ' - ' . $m['eqB']['tantos'] . ' ' . $m['eqB']['nombre'];
    }

    /** Último resultado MARCADOR cargado para una fase de la categoría. */
    private function _resultado_por_fase($id_categoria, $fase) {
        // Se traen también id_fixture y fecha_competencia del fixture: son las
        // que definen la NOCHE de entrega (el día en que se jugó).
        $this->db->select('r.*, f.fase AS fx_fase, f.id_fixture AS fx_id, f.fecha_competencia AS fx_fecha', FALSE);
        if ($this->_columna_resultado_existe('id_ute_ganador')) {
            // Nombre del ganador del desempate para el texto del podio.
            $this->db->select(', ug.nombre_ute AS nombre_ganador_desempate', FALSE);
            $this->db->join('utes ug', 'ug.id_ute = r.id_ute_ganador', 'left');
        }
        $this->db->from('resultados r');
        $this->db->join('fixtures f', 'f.id_fixture = r.id_fixture', 'inner');
        $this->db->where('r.id_categoria', (int) $id_categoria);
        $this->db->where('r.tipo_resultado', 'MARCADOR');
        $this->db->where('f.fase', $fase);
        $this->db->order_by('r.id_resultado', 'DESC');
        $this->db->limit(1);
        $row = $this->_fila_segura();
        if (!$row) return null;
        $row['detalle'] = $this->_detalle_de_resultados(array((int) $row['id_resultado']))
            [(int) $row['id_resultado']] ?? array();
        if (!empty($row['id_ute_ganador']) && empty($row['nombre_ganador_desempate'])) {
            // El ganador puede ser un equipo cargado "libre" (sin UTE):
            // recuperar su nombre desde el detalle para mostrarlo igual.
            foreach ($row['detalle'] as $d) {
                if (!empty($d['id_ute']) && (int) $d['id_ute'] === (int) $row['id_ute_ganador']) {
                    $row['nombre_ganador_desempate'] = trim((string) $d['nombre_libre']);
                    break;
                }
            }
        }
        return count($row['detalle']) >= 2 ? $row : null;
    }

    /**
     * Resultado de la FINAL al que debería estar vinculado el partido del
     * fixture (para detectar podios armados con resultados cargados "sin
     * vincular al fixture": el resultado existe pero nunca se unió al partido
     * y por eso la Premiación no lo encontraba).
     */
    private function _resultado_por_fase_vinculado_por_marcador($id_categoria, $fase, $fx) {
        $res = $this->_resultados_de_la_instancia((int) $fx['id_fixture'], true);
        if (!$res) return null;
        foreach ($res as $r) {
            if ($r['tipo_resultado'] !== 'MARCADOR') continue;
            if (count($r['detalle']) < 2) continue;
            if ($this->_equipos_coinciden_con_el_partido($fx, $r['detalle'])) {
                return $r;
            }
        }
        return null;
    }

    /**
     * Resultados vinculados a un partido/jornada del fixture (con su detalle).
     * $solo_marcador limita a los de tipo MARCADOR (un solo query).
     */
    private function _resultados_de_la_instancia($id_fixture, $solo_marcador = false) {
        $this->db->where('id_fixture', (int) $id_fixture);
        if ($solo_marcador) {
            $this->db->where('tipo_resultado', 'MARCADOR');
        }
        $this->db->order_by('id_resultado', 'DESC');
        $rows = $this->_filas_seguras('resultados');
        if (!$rows) return array();
        $detalles = $this->_detalle_de_resultados(array_column($rows, 'id_resultado'));
        foreach ($rows as &$r) {
            $r['detalle'] = isset($detalles[(int) $r['id_resultado']])
                ? $detalles[(int) $r['id_resultado']] : array();
        }
        unset($r);
        return $rows;
    }

    /** ¿Los dos equipos del resultado son los del partido del fixture? */
    private function _equipos_coinciden_con_el_partido($fx, $detalle) {
        $esperados = array_filter(array(
            !empty($fx['id_ute_1']) ? (int) $fx['id_ute_1'] : 0,
            !empty($fx['id_ute_2']) ? (int) $fx['id_ute_2'] : 0,
        ));
        $cargados = array();
        $nombres_cargados = array();
        foreach ($detalle as $d) {
            if (!empty($d['id_ute'])) {
                $cargados[] = (int) $d['id_ute'];
            } else {
                $nombres_cargados[] = mb_strtolower(trim((string) $d['nombre_libre']));
            }
        }
        if ($cargados && count($cargados) === count($detalle)) {
            sort($cargados);
            sort($esperados);
            return $cargados === $esperados;
        }
        // Equipos cargados "libres" (sin UTE): comparar por nombre.
        if (!$nombres_cargados || !$esperados) return false;
        $this->db->select('id_ute, nombre_ute', FALSE);
        $this->db->where_in('id_ute', $esperados);
        $nombres_esperados = array();
        foreach ($this->_filas_seguras('utes') as $u) {
            $nombres_esperados[] = mb_strtolower(trim((string) $u['nombre_ute']));
        }
        sort($nombres_cargados);
        sort($nombres_esperados);
        return $nombres_cargados === $nombres_esperados && count($nombres_cargados) >= 2;
    }

    /** ¿Existe una columna en `resultados`? (tolera bases sin el ALTER). */
    private function _columna_resultado_existe($columna) {
        static $cache = array();
        $key = strtolower((string) $columna);
        if (!isset($cache[$key])) {
            try {
                $q = $this->db->query(
                    "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'resultados'
                       AND COLUMN_NAME = ?",
                    array($columna)
                );
                $cache[$key] = (bool) ($q && $q->num_rows() > 0);
            } catch (Throwable $e) {
                log_message('error', '[Premiación] no se pudo verificar columna resultados.' . $columna . ': ' . $e->getMessage());
                $cache[$key] = false;
            }
        }
        return $cache[$key];
    }

    /** Detalle agrupado por id_resultado (una sola consulta). */
    private function _detalle_de_resultados($ids) {
        if (!$ids || !$this->_tabla_existe('resultado_detalle')) return array();
        $this->db->where_in('id_resultado', $ids);
        $this->db->order_by('id_detalle', 'ASC');
        $out = array();
        foreach ($this->_filas_seguras('resultado_detalle') as $d) {
            $out[(int) $d['id_resultado']][] = $d;
        }
        // Jornada única / largada masiva: en Premiación también rige el
        // formato "PROVINCIA (NOMBRE)" (antes se mostraba "NOMBRE (DNI)").
        $this->_normalizar_nombres_provincia($out);
        return $out;
    }

    /**
     * Convierte los nombres "NOMBRE (DNI)" del detalle de resultados
     * TIEMPO/PUNTAJE al formato "PROVINCIA (NOMBRE)", buscando la
     * provincia/delegación del participante (inscripción con id negativo).
     * Solo afecta el texto mostrado; Resultado_model además lo persiste al
     * leer los resultados. Tolerante a fallos: si algo falla, deja los
     * nombres como están.
     */
    private function _normalizar_nombres_provincia(&$detalles_por_res) {
        $ins_ids = array();
        foreach ($detalles_por_res as $det) {
            foreach ($det as $d) {
                $id = isset($d['id_ute']) ? (int) $d['id_ute'] : 0;
                if ($id < 0) $ins_ids[] = -$id;
            }
        }
        if (!$ins_ids) return;

        $prov_por_ins = array();
        try {
            $this->db->select('i.id_inscripcion, p.delegacion', FALSE);
            $this->db->from('inscripciones_deportivas i');
            $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
            $this->db->where_in('i.id_inscripcion', array_unique($ins_ids));
            foreach ($this->db->get()->result_array() as $r) {
                $prov_por_ins[(int) $r['id_inscripcion']] = $r['delegacion'];
            }
        } catch (Throwable $e) {
            return; // base sin columna delegacion: mostrar como viene
        }

        foreach ($detalles_por_res as &$det) {
            foreach ($det as &$d) {
                $nom = trim((string) ($d['nombre_libre'] ?? ''));
                if ($nom === '') continue;
                $id = isset($d['id_ute']) ? (int) $d['id_ute'] : 0;
                if ($id >= 0) continue;
                $prov = isset($prov_por_ins[-$id]) ? $prov_por_ins[-$id] : null;
                if ($prov === null || trim((string) $prov) === '') continue;
                // Sufijo numérico (DNI) o sin sufijo -> PROVINCIA (NOMBRE).
                if (!preg_match('/^(.*)\(([^()]*)\)$/u', $nom, $m)) {
                    $cuerpo = $nom;
                    $sufijo = null;
                } else {
                    $cuerpo = trim($m[1]);
                    $sufijo = trim($m[2]);
                }
                if ($cuerpo === '') $cuerpo = $nom;
                $prov_norm = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string) $prov)), 'UTF-8');
                if ($sufijo !== null && strcasecmp($cuerpo, $prov_norm) === 0) continue; // ya normalizado
                if ($sufijo === null || preg_match('/^[\d.\-]+$/', $sufijo)) {
                    $d['nombre_libre'] = $prov_norm . ' (' . $cuerpo . ')';
                }
            }
            unset($d);
        }
        unset($det);
    }

    /* ============================================================
     *  CIERRE DE CATEGORÍAS + NOCHE DE ENTREGA
     * ============================================================ */

    /** Categorías con sus deportes y tipo de duración. */
    private function _categorias_base() {
        $this->db->select('
            c.id_categoria, c.nombre_categoria, c.genero, c.dia_competencia,
            d.id_deporte, d.nombre_deporte, d.modalidad_competencia, d.tipo_duracion
        ', FALSE);
        // EL SISTEMA DE DEFINICIÓN VIVE EN LA CATEGORÍA (cada categoría de un
        // mismo deporte puede definir por FINAL+3er puesto o por tabla de
        // puntos). Columnas de sql/migracion_puntaje_categoria.sql: se agregan
        // solo si existen, para no romper en bases todavía sin el ALTER.
        if ($this->_columna_existe('categorias', 'sistema_definicion')) {
            $this->db->select(", IFNULL(c.sistema_definicion, 'FASE_FINAL') AS sistema_definicion", FALSE);
        } elseif ($this->_columna_existe('deportes', 'sistema_definicion')) {
            // Retrocompatibilidad: si se llegó a aplicar la migración vieja
            // (columnas en deportes), se usa ese valor como default del deporte.
            $this->db->select(", IFNULL(d.sistema_definicion, 'FASE_FINAL') AS sistema_definicion", FALSE);
        }
        if ($this->_columna_existe('categorias', 'puntosVictoria')) {
            $this->db->select(', IFNULL(NULLIF(c.puntosVictoria, -1), 3) AS puntos_victoria', FALSE);
        } elseif ($this->_columna_existe('deportes', 'puntosVictoria')) {
            $this->db->select(', IFNULL(NULLIF(d.puntosVictoria, -1), 3) AS puntos_victoria', FALSE);
        }
        if ($this->_columna_existe('categorias', 'puntosEmpate')) {
            $this->db->select(', IFNULL(NULLIF(c.puntosEmpate, -1), 1) AS puntos_empate', FALSE);
        } elseif ($this->_columna_existe('deportes', 'puntosEmpate')) {
            $this->db->select(', IFNULL(NULLIF(d.puntosEmpate, -1), 1) AS puntos_empate', FALSE);
        }
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');
        return $this->_filas_seguras();
    }

    /** ¿Existe una columna en una tabla? (tolera bases sin el ALTER). */
    private function _columna_existe($tabla, $columna) {
        static $cache = array();
        $key = strtolower((string) $tabla) . '.' . strtolower((string) $columna);
        if (!isset($cache[$key])) {
            try {
                $q = $this->db->query(
                    "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = ?
                       AND COLUMN_NAME = ?",
                    array($tabla, $columna)
                );
                $cache[$key] = (bool) ($q && $q->num_rows() > 0);
            } catch (Throwable $e) {
                log_message('error', '[Premiación] no se pudo verificar columna ' . $tabla . '.' . $columna . ': ' . $e->getMessage());
                $cache[$key] = false;
            }
        }
        return $cache[$key];
    }

    /* ============================================================
     *  TABLA DE PUNTAJE (ENFRENTAMIENTO que define por puntos)
     * ============================================================
     *
     * Reglas:
     *  - Cada partido cargado (MARCADOR o PUNTAJE) suma:
     *      ganador  -> puntos_victoria (def: 3)
     *      empate   -> puntos_empate (def: 1) a cada lado
     *      perdedor -> 0
     *  - Empates SIN desempate registrado en fase eliminatoria NO se toman
     *    como empate de liga: el partido está incompleto hasta que se cargue
     *    el desempate. En GRUPO/JORNADA el empate es válido.
     *  - El podio sale de la tabla ordenada por: puntos -> diferencia de
     *    tantos -> tantos a favor -> menos tantos contra -> nombre.
     */

    /** Clave estable de un equipo (id UTE o nombre normalizado). */
    private function _clave_equipo($eq, &$map) {
        $nombre = trim((string) ($eq['nombre'] ?? ''));
        if (empty($eq['id']) && $nombre === '') return null;
        $k = !empty($eq['id']) ? ('u' . (int) $eq['id']) : ('n:' . mb_strtolower($nombre));
        if (!isset($map[$k])) {
            $map[$k] = array(
                'id'     => !empty($eq['id']) ? (int) $eq['id'] : null,
                'nombre' => $nombre !== '' ? $nombre : ('UTE #' . (int) $eq['id']),
                'puntos' => 0, 'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0,
                'gf' => 0, 'gc' => 0, 'dif' => 0,
            );
        } elseif ($map[$k]['nombre'] === '' && $nombre !== '') {
            $map[$k]['nombre'] = $nombre;
        }
        $map[$k]['pj']++;
        return $k;
    }

    /** Clave estable de un cruce entre dos equipos (orden alfabético). */
    private function _clave_cruce($ka, $kb) {
        $ks = array($ka, $kb);
        sort($ks);
        return $ks[0] . '|' . $ks[1];
    }

    /**
     * Tabla de puntos de una categoría ENFRENTAMIENTO con sistema PUNTAJE.
     *
     * @param int   $id_categoria
     * @param array $resultados resultados de la categoría (con 'detalle')
     * @param array $cat        fila de _categorias_base()
     * @param array $fixtures   fixtures de la categoría
     * @return array:
     *   tabla            lista ordenada de equipos (puntos, pj, pg, pe, pp, gf, gc, dif)
     *   pendientes       partidos del fixture aún sin resultado
     *   faltan_jugar     parejas empatadas en puntos que aún deben jugar entre sí
     *   sorteo_necesario podio (3º vs 4º) totalmente igualado
     */
    private function _tabla_puntaje($id_categoria, $resultados, $cat, $fixtures = array()) {
        $pts_v = isset($cat['puntos_victoria']) ? max(0, (int) $cat['puntos_victoria']) : 3;
        $pts_e = isset($cat['puntos_empate']) ? max(0, (int) $cat['puntos_empate']) : 1;

        // Fases eliminatorias: un empate ahí sin desempate cargado es un
        // partido INCOMPLETO, no un empate de liga.
        $fases_eliminatorias = array('16AVOS', 'OCTAVOS', 'CUARTOS', 'SEMIFINAL', 'TERCER_PUESTO', 'FINAL');

        $equipos = array();  // clave => fila de la tabla
        $cruces  = array();  // "ka|kb" => ['jugado' => bool, 'gano' => 'a'|'b'|null]
        $usados  = array();  // id_fixture cubiertos por algún resultado

        foreach ($resultados as $r) {
            if (!in_array($r['tipo_resultado'], array('MARCADOR', 'PUNTAJE'), true)) continue;
            if (!empty($r['id_fixture'])) $usados[(int) $r['id_fixture']] = true;

            $m = $this->_parsear_marcador($r['detalle']);
            if (!$m) continue;
            $ka = $this->_clave_equipo($m['eqA'], $equipos);
            $kb = $this->_clave_equipo($m['eqB'], $equipos);
            if ($ka === null || $kb === null || $ka === $kb) continue;

            $tantosA = (int) $m['eqA']['tantos'];
            $tantosB = (int) $m['eqB']['tantos'];
            $fase = strtoupper(trim((string) ($r['fx_fase'] ?? '')));
            $des = $this->_resolver_desempate($r, $m);

            $key = $this->_clave_cruce($ka, $kb);

            if ($tantosA === $tantosB && !$des && in_array($fase, $fases_eliminatorias, true)) {
                // Eliminatoria empatada sin desempate: NO computa todavía.
                $equipos[$ka]['pj']--;
                $equipos[$kb]['pj']--;
                $cruces[$key] = array('jugado' => false, 'gano' => null);
                continue;
            }

            if ($des) {
                $kgan = $this->_clave_equipo($des['eq_ganador'], $equipos);
                $kper = ($kgan === $ka) ? $kb : $ka;
                $equipos[$kgan]['puntos'] += $pts_v;
                $equipos[$kgan]['pg']++;
                $equipos[$kper]['pp']++;
                $gan_t = (int) $des['eq_ganador']['tantos'];
                $per_t = (int) $des['eq_perdedor']['tantos'];
                $equipos[$kgan]['gf'] += $gan_t; $equipos[$kgan]['gc'] += $per_t;
                $equipos[$kper]['gf'] += $per_t; $equipos[$kper]['gc'] += $gan_t;
                $directo = ($kgan === $ka) ? 'a' : 'b';
            } elseif ($tantosA === $tantosB) {
                $equipos[$ka]['puntos'] += $pts_e;
                $equipos[$kb]['puntos'] += $pts_e;
                $equipos[$ka]['pe']++; $equipos[$kb]['pe']++;
                $equipos[$ka]['gf'] += $tantosA; $equipos[$ka]['gc'] += $tantosB;
                $equipos[$kb]['gf'] += $tantosB; $equipos[$kb]['gc'] += $tantosA;
                $directo = null;
            } else {
                $kgan = $tantosA > $tantosB ? $ka : $kb;
                $kper = ($kgan === $ka) ? $kb : $ka;
                $gan = ($kgan === $ka) ? $m['eqA'] : $m['eqB'];
                $per = ($kgan === $ka) ? $m['eqB'] : $m['eqA'];
                $equipos[$kgan]['puntos'] += $pts_v;
                $equipos[$kgan]['pg']++;
                $equipos[$kper]['pp']++;
                $equipos[$kgan]['gf'] += (int) $gan['tantos']; $equipos[$kgan]['gc'] += (int) $per['tantos'];
                $equipos[$kper]['gf'] += (int) $per['tantos']; $equipos[$kper]['gc'] += (int) $gan['tantos'];
                $directo = ($kgan === $ka) ? 'a' : 'b';
            }
            $cruces[$key] = array('jugado' => true, 'gano' => $directo);
        }

        foreach ($equipos as &$e) { $e['dif'] = $e['gf'] - $e['gc']; }
        unset($e);

        // Partidos del fixture con ambos equipos ya definidos y sin resultado:
        // son los que faltan jugar para cerrar el torneo.
        $pendientes = 0;
        $cruces_fx  = array();
        foreach ($fixtures as $f) {
            if (empty($f['id_ute_1']) || empty($f['id_ute_2'])) continue;
            $k1 = 'u' . (int) $f['id_ute_1'];
            $k2 = 'u' . (int) $f['id_ute_2'];
            if (isset($cruces_fx[$this->_clave_cruce($k1, $k2)])) {
                $cruces_fx[$this->_clave_cruce($k1, $k2)]++;
            } else {
                $cruces_fx[$this->_clave_cruce($k1, $k2)] = 1;
            }
            if (!isset($usados[(int) $f['id_fixture']])) $pendientes++;
        }

        // Equipos empatados EN PUNTOS que aún deben jugar entre sí (según el
        // fixture): la tabla no puede darlos por definidos.
        $faltan_jugar = array();
        $por_puntos = array();
        foreach ($equipos as $k => $e) $por_puntos[(int) $e['puntos']][] = $k;
        foreach ($por_puntos as $p => $ks) {
            if (count($ks) < 2) continue;
            for ($i = 0; $i < count($ks); $i++) {
                for ($j = $i + 1; $j < count($ks); $j++) {
                    $key = $this->_clave_cruce($ks[$i], $ks[$j]);
                    $ya_jugaron = isset($cruces[$key]) && !empty($cruces[$key]['jugado']);
                    $debe_jugar = !empty($cruces_fx[$key]);
                    if (!$ya_jugaron && $debe_jugar) {
                        $faltan_jugar[] = array(
                            'a' => $equipos[$ks[$i]]['nombre'],
                            'b' => $equipos[$ks[$j]]['nombre'],
                            'puntos' => (int) $p,
                        );
                    }
                }
            }
        }

        // Orden de la tabla: puntos -> dif -> gf -> menos gc -> nombre.
        $tabla = array_values($equipos);
        usort($tabla, function ($x, $y) {
            foreach (array('puntos', 'dif', 'gf') as $c) {
                if ($y[$c] != $x[$c]) return ($y[$c] > $x[$c]) ? 1 : -1;
            }
            if ($x['gc'] != $y['gc']) return ($x['gc'] > $y['gc']) ? 1 : -1;
            return strcasecmp($x['nombre'], $y['nombre']);
        });

        // ¿El corte del podio (3º vs 4º) queda totalmente igualado?
        $sorteo = false;
        if (count($tabla) >= 4) {
            $t3 = $tabla[2]; $t4 = $tabla[3];
            if ($t3['puntos'] == $t4['puntos'] && $t3['dif'] == $t4['dif']
                && $t3['gf'] == $t4['gf'] && $t3['gc'] == $t4['gc']) {
                $sorteo = true;
            }
        }

        return array(
            'tabla' => $tabla,
            'cruces' => $cruces,
            'pendientes' => $pendientes,
            'faltan_jugar' => $faltan_jugar,
            'sorteo_necesario' => $sorteo,
            'puntos_victoria' => $pts_v,
            'puntos_empate' => $pts_e,
        );
    }

    /** Podio desde la tabla de puntaje: puestos 1, 2 y 3. */
    private function _podio_desde_tabla($info) {
        $out = array();
        $extra_base = 'Tabla de puntos: ' . $info['puntos_victoria']
            . ' pts victoria / ' . $info['puntos_empate'] . ' pts empate / 0 derrota';
        foreach (array_slice($info['tabla'], 0, 3) as $i => $e) {
            $out[$i + 1] = array(
                'id' => $e['id'],
                'nombre' => $e['nombre'],
                'extra' => $extra_base . ' · ' . $e['puntos'] . ' pts ('
                    . $e['pj'] . ' PJ, ' . $e['pg'] . 'G/' . $e['pe'] . 'E/' . $e['pp'] . 'P, dif '
                    . ($e['dif'] > 0 ? '+' : '') . $e['dif'] . ')',
                'id_resultado_ref' => null,
            );
        }
        return $out;
    }


    /**
     * Todas las categorías con su estado de cierre y su podio resuelto.
     * Se usa tanto para el resumen diario como para el histórico.
     *
     * @return array de items:
     *   categoria, deporte, modalidad, tipo_duracion, cerrada, fecha_entrega,
     *   motivo_cierre, podio (puesto => {nombre,id,extra,id_resultado_ref}|null),
     *   origen (MARCADOR/TIEMPO), entregadas (cant. de puestos ya registrados),
     *   total_puestos
     */
    public function obtener_estado_premiables() {
        if (!$this->_tabla_existe('resultados')) return array();
        if (!$this->_tabla_existe('fixtures')) return array();

        $categorias = $this->_categorias_base();
        if (!$categorias) return array();

        // Resultados por categoría (con detalle) y fixtures por categoría.
        $rows = $this->_filas_seguras('resultados');
        if (!$rows) return array();
        $detalles = $this->_detalle_de_resultados(array_column($rows, 'id_resultado'));
        $resultados_por_cat = array();
        foreach ($rows as $r) {
            $r['detalle'] = isset($detalles[(int) $r['id_resultado']])
                ? $detalles[(int) $r['id_resultado']] : array();
            $resultados_por_cat[(int) $r['id_categoria']][] = $r;
        }

        $this->db->select('f.id_fixture, f.id_categoria, f.fase, f.estado, f.fecha_competencia, f.numero_fecha', FALSE);
        $fixtures_por_cat = array();
        $fx_por_id = array();
        foreach ($this->_filas_seguras('fixtures') as $f) {
            $fixtures_por_cat[(int) $f['id_categoria']][] = $f;
            $fx_por_id[(int) $f['id_fixture']] = $f;
        }

        // Premios ya registrados por categoría (tolera tabla inexistente).
        $entregadas_por_cat = array();
        foreach ($this->_filas_seguras('premiaciones') as $p) {
            $entregadas_por_cat[(int) $p['id_categoria']][] = $p;
        }

        // Fecha del día en que se jugó cada partido/prueba: sale del FIXTURE
        // (fecha_competencia), nunca de la fecha de carga del resultado.
        // Se normaliza SIEMPRE a 'Y-m-d' (el driver puede devolverla con hora).
        $fecha_jugada = function ($r) use ($fx_por_id) {
            if (!empty($r['id_fixture']) && isset($fx_por_id[(int) $r['id_fixture']])) {
                return $this->_norm_fecha($fx_por_id[(int) $r['id_fixture']]['fecha_competencia'] ?? null);
            }
            return null;
        };
        // Fallback: si el fixture no tiene fecha, la fecha guardada en el propio
        // resultado (Resultado_model la copia del fixture al cargar, así que
        // suele coincidir) y recién después dia_competencia de la categoría.
        $fecha_resultado = function ($r) {
            return !empty($r['fecha_resultado']) ? $this->_norm_fecha($r['fecha_resultado']) : null;
        };

        $items = array();
        // Índice de resultados por id para resolver fechas del podio (MARCA-
        // DOR) a partir del id_resultado_ref.
        $res_por_id = array();
        foreach ($rows as $r) $res_por_id[(int) $r['id_resultado']] = $r;

        foreach ($categorias as $cat) {
            $id_cat = (int) $cat['id_categoria'];
            $resultados = isset($resultados_por_cat[$id_cat]) ? $resultados_por_cat[$id_cat] : array();
            $fixtures = isset($fixtures_por_cat[$id_cat]) ? $fixtures_por_cat[$id_cat] : array();
            if (!$resultados) continue; // sin resultados no hay nada que premiar

            $es_masiva = ($cat['modalidad_competencia'] === 'MASIVO_TIEMPO');
            // ENFRENTAMIENTO que define por tabla de puntos (3/1/0) en lugar
            // de FINAL + Tercer puesto: categorias.sistema_definicion = 'PUNTAJE'
            // (es configuración de LA CATEGORÍA, no del deporte).
            $define_por_puntaje = (!$es_masiva
                && strtoupper((string) ($cat['sistema_definicion'] ?? 'FASE_FINAL')) === 'PUNTAJE');
            $podio = array();
            $origen = $es_masiva ? 'TIEMPO' : 'MARCADOR';
            $info_tabla = null;

            if ($es_masiva) {
                // Podio = el mejor resultado TIEMPO cargado (más posiciones).
                // Desempate: el más nuevo. Y se guarda la fecha del día en que
                // se corrió la prueba (fixture -> fecha_resultado) para poder
                // filtrar por noche de competencia.
                $mejor = null;
                $fecha_mejor = null;
                foreach ($resultados as $r) {
                    if ($r['tipo_resultado'] !== 'TIEMPO') continue;
                    if (!$r['detalle']) continue;
                    $toma = false;
                    if ($mejor === null) {
                        $toma = true;
                    } elseif (count($r['detalle']) > count($mejor['detalle'])) {
                        $toma = true;
                    } elseif (count($r['detalle']) === count($mejor['detalle'])
                              && (int) $r['id_resultado'] > (int) $mejor['id_resultado']) {
                        $toma = true;
                    }
                    if ($toma) {
                        $mejor = $r;
                        $fecha_mejor = $fecha_jugada($r) ?: $fecha_resultado($r);
                    }
                }
                if (!$mejor) continue;
                $podio_nombres = $this->_podio_desde_tiempo($mejor);
                foreach ($podio_nombres as $pos => $p) {
                    $podio[$pos] = array_merge($p, array('id_resultado_ref' => (int) $mejor['id_resultado']));
                }
            } elseif ($define_por_puntaje) {
                // Podio = tabla de puntos del torneo completo (3/1/0).
                $info_tabla = $this->_tabla_puntaje($id_cat, $resultados, $cat, $fixtures);
                $podio = $this->_podio_desde_tabla($info_tabla);
                if (!isset($podio[1])) continue; // todavía no hay ningún partido computable
            } else {
                $podio = $this->_podio_marcador_categoria($id_cat);
                if (!isset($podio[1])) continue; // sin final resuelta todavía no se premia
            }

            // ---------- ¿cerró la categoría? ----------
            // La noche de entrega SIEMPRE es el día en que se jugó/hizo la
            // competencia (fixtures.fecha_competencia), nunca la fecha en que
            // se cargó el resultado.
            $cerrada = false;
            $motivo = '';
            $fecha_entrega = null;
            if ($es_masiva) {
                // Jornada única: cierra cuando hay resultado cargado. La noche
                // es el día en que se corrió la prueba (ya resuelto arriba).
                $cerrada = true;
                $motivo = 'Jornada cerrada con resultados cargados';
                $fecha_entrega = $fecha_mejor;
                if (!$fecha_entrega && !empty($cat['dia_competencia'])) {
                    $fecha_entrega = $this->_norm_fecha($cat['dia_competencia']);
                }
                if (!$fecha_entrega) {
                    $cerrada = false;
                    $motivo = 'Sin fecha de competencia: cargá el día de la jornada en el Fixture de esta categoría.';
                }
            } elseif ($define_por_puntaje) {
                // LIGA POR PUNTAJE: no hay FINAL que esperar. Cierra cuando
                // NO quedan partidos por jugar entre equipos ya definidos en
                // el fixture (todos contra todos completo) — o, si el torneo
                // se jugó sin fixture, cuando hay al menos un partido cargado.
                $pend = (int) $info_tabla['pendientes'];
                $dias_jugados = array();
                foreach ($resultados as $r) {
                    if (!in_array($r['tipo_resultado'], array('MARCADOR', 'PUNTAJE'), true)) continue;
                    $fd = $fecha_jugada($r) ?: $fecha_resultado($r);
                    if ($fd) $dias_jugados[] = $fd;
                }
                $fecha_entrega = $dias_jugados ? max($dias_jugados) : null;
                if (!$fecha_entrega && !empty($cat['dia_competencia'])) {
                    $fecha_entrega = $this->_norm_fecha($cat['dia_competencia']);
                }
                if ($pend > 0) {
                    $cerrada = false;
                    $motivo = 'Torneo por puntaje: todavía faltan ' . $pend
                        . ' partido(s) del fixture por cargar resultados.';
                } elseif (!$fecha_entrega) {
                    $cerrada = false;
                    $motivo = 'Sin fecha de competencia: cargá el día de los partidos en el Fixture de esta categoría.';
                } else {
                    $cerrada = true;
                    $motivo = 'Torneo por puntaje (' . $info_tabla['puntos_victoria'] . '/'
                        . $info_tabla['puntos_empate'] . '/0): tabla definida, se premia la última noche jugada';
                    if (!empty($info_tabla['faltan_jugar'])) {
                        $motivo .= ' — ojo: ' . count($info_tabla['faltan_jugar'])
                            . ' pareja(s) empatada(s) en puntos aún deben jugar entre sí';
                    }
                    if (!empty($info_tabla['sorteo_necesario'])) {
                        $motivo .= ' — el 3º puesto queda igualado en todo: definílo por sorteo antes de entregar';
                    }
                }
            } elseif ($cat['tipo_duracion'] === 'UNICO_DIA') {
                $cerrada = isset($podio[1]);
                $motivo = 'Deporte de un solo día: se premia esa misma noche';
                // Día en que se jugó la FINAL (fixture del partido del podio);
                // si no está, el día programado de la categoría.
                $final_fx = null;
                foreach ($fixtures as $f) {
                    if ($f['fase'] === 'FINAL' && !empty($f['fecha_competencia'])) { $final_fx = $f; break; }
                }
                $fecha_entrega = $final_fx ? $this->_norm_fecha($final_fx['fecha_competencia']) : null;
                if (!$fecha_entrega) {
                    $fecha_entrega = $podio[1]['fecha_jugada']
                        ?: $this->_fecha_del_resultado_ref((int) ($podio[1]["id_resultado_ref"] ?? 0), $res_por_id, $fx_por_id);
                }
                if (!$fecha_entrega && !empty($cat['dia_competencia'])) {
                    $fecha_entrega = $this->_norm_fecha($cat['dia_competencia']);
                }
                if (!$fecha_entrega) {
                    $cerrada = false;
                    $motivo = 'Sin fecha de competencia: cargá el día de los partidos en el Fixture de esta categoría.';
                }
            } else {
                // MULTIDIA: cierra el día de la definición. Se toma la fecha
                // MÁS RECIENTE en que se jugó una instancia decisiva (FINAL o
                // TERCER_PUESTO con resultado cargado): si la final y el
                // tercer puesto se juegan en noches distintas, la premiación
                // corresponde a la última noche. Si ninguna tiene fecha real,
                // se usa la fecha programada de la FINAL del fixture.
                $dias_def = array();
                foreach (array('FINAL', 'TERCER_PUESTO') as $fase_def) {
                    $r = $this->_resultado_por_fase($id_cat, $fase_def);
                    if (!$r) continue;
                    $fd = $this->_norm_fecha($r['fx_fecha'] ?? null)
                        ?: $this->_norm_fecha($r['fecha_resultado'] ?? null);
                    if ($fd) $dias_def[] = $fd;
                }
                if (!$dias_def) {
                    foreach ($fixtures as $f) {
                        if ($f['fase'] === 'FINAL' && !empty($f['fecha_competencia'])) {
                            $fd = $this->_norm_fecha($f['fecha_competencia']);
                            if ($fd) $dias_def[] = $fd;
                            break;
                        }
                    }
                }
                $fecha_def = $dias_def ? max($dias_def) : null;
                $cerrada = isset($podio[1]) && !empty($fecha_def);
                $motivo = 'Multidía: cierra el día de la definición';
                $fecha_entrega = $fecha_def;
            }

            if ($cerrada && !$fecha_entrega) {
                // Sin fecha de competencia conocida no se puede asignar noche:
                // se marca como no-cerrada con motivo claro (así el filtro por
                // día no "muestra de más") y se avisa en pantalla.
                $cerrada = false;
                $motivo = 'Sin fecha de competencia: cargá el día de la FINAL en el Fixture de esta categoría.';
            }

            $ya = isset($entregadas_por_cat[$id_cat]) ? $entregadas_por_cat[$id_cat] : array();
            $items[] = array(
                'categoria'     => $cat,
                'cerrada'       => $cerrada,
                'motivo'        => $motivo,
                'fecha_entrega' => $fecha_entrega,
                'origen'        => $origen,
                'podio'         => $podio,
                'entregadas'    => $ya,
                // Tabla de puntos completa (solo en deportes por puntaje):
                // la vista puede mostrarla como tooltip/mini-tabla.
                'tabla_puntaje' => $define_por_puntaje ? $info_tabla['tabla'] : null,
            );
        }

        return $items;
    }

    /**
     * Fecha del día en que se jugó un resultado concreto (id_resultado):
     * fixture.fecha_competencia -> resultados.fecha_resultado. Normalizada.
     */
    private function _fecha_del_resultado_ref($id_resultado, $res_por_id, $fx_por_id) {
        $r = isset($res_por_id[(int) $id_resultado]) ? $res_por_id[(int) $id_resultado] : null;
        if (!$r) return null;
        $f = $this->_norm_fecha($r['fecha_resultado'] ?? null);
        if (!empty($r['id_fixture']) && isset($fx_por_id[(int) $r['id_fixture']])) {
            $f = $this->_norm_fecha($fx_por_id[(int) $r['id_fixture']]['fecha_competencia'] ?? null) ?: $f;
        }
        return $f;
    }

    /**
     * Resumen para una noche (fecha): qué se premió/compitió ESE día.
     * Filtro estricto por fecha de competencia: solo categorías cerradas cuya
     * noche de entrega es exactamente la fecha pedida. Se normalizan ambas
     * fechas a 'Y-m-d' para comparar sin sorpresas de formato.
     */
    public function obtener_resumen_noche($fecha) {
        $items = $this->obtener_estado_premiables();
        $pedido = $this->_norm_fecha($fecha);
        $out = array();
        if (!$pedido) return $out;
        foreach ($items as $it) {
            if (!$it['cerrada']) continue;
            if ($this->_norm_fecha($it['fecha_entrega']) !== $pedido) continue; // solo el día que se compitió
            $out[] = $it;
        }
        return $out;
    }

    /**
     * Categorías de noches ANTERIORES a la fecha pedida que todavía no fueron
     * premiadas completas (para que ninguna entrega quede en el olvido).
     *
     * IMPORTANTE: las competencias SIN fecha de competencia conocida (fixture
     * sin día cargado) tampoco pueden mostrarse acá — si no, aparecerían como
     * "atraso" en cualquier fecha elegida y el filtro daría la sensación de no
     * funcionar. Se reportan aparte con obtener_sin_fecha() para mostrar un
     * aviso claro en pantalla.
     */
    public function obtener_atrasos_hasta($fecha) {
        $items = $this->obtener_estado_premiables();
        $pedido = $this->_norm_fecha($fecha);
        $out = array();
        if (!$pedido) return $out;
        foreach ($items as $it) {
            if (!$it['cerrada']) continue;
            $fe = $this->_norm_fecha($it['fecha_entrega']);
            if (!$fe || $fe >= $pedido) continue; // no es anterior / sin fecha
            // si ya tiene todos los puestos registrados, no es un atraso
            $puestos_esperados = array_keys($it['podio']);
            $entregados = array();
            foreach ($it['entregadas'] as $e) $entregados[(int) $e['puesto']] = $e;
            $completa = !empty($puestos_esperados);
            foreach ($puestos_esperados as $p) {
                if (!isset($entregados[$p])) { $completa = false; break; }
            }
            if ($completa) continue;
            $out[] = $it;
        }
        return $out;
    }

    /**
     * Resultados/premios POTENCIALMENTE premiables pero cuya categoría NO tiene
     * fecha de competencia en el fixture (no se puede asignar noche). Devuelve
     * una lista simple para el aviso: categoria, motivo.
     */
    public function obtener_sin_fecha() {
        $out = array();
        foreach ($this->obtener_estado_premiables() as $it) {
            if (!empty($it['cerrada'])) continue;
            if (empty($it['motivo']) || strpos($it['motivo'], 'Sin fecha') === false) continue;
            $out[] = array(
                'categoria' => $it['categoria']['nombre_deporte'] . ' › ' . $it['categoria']['nombre_categoria'],
                'motivo'    => $it['motivo'],
            );
        }
        return $out;
    }

    /** Histórico: todas las premiaciones registradas (por noche). */
    public function obtener_historico_entregas() {
        if (!$this->tablas_existentes()) return array();
        $this->db->select('p.*, c.nombre_categoria, d.nombre_deporte, d.modalidad_competencia', FALSE);
        $this->db->from('premiaciones p');
        $this->db->join('categorias c', 'c.id_categoria = p.id_categoria', 'left');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'left');
        $this->db->order_by('p.fecha_entrega DESC, p.puesto ASC, p.id_premiacion ASC');
        return $this->_filas_seguras();
    }

    /* ============================================================
     *  ALTA / EDICIÓN DE LA ENTREGA
     * ============================================================ */

    /**
     * Confirma la premiación de una categoría (los 3 puestos resueltos).
     * $puestos llega del frontend: [{puesto, nombre, id_ute}, ...].
     */
    public function confirmar_podio($id_categoria, $puestos, $fecha_entrega, $id_usuario = null, $observaciones = null) {
        if (!$this->tablas_existentes()) {
            throw new Exception('Falta la tabla premiaciones. Ejecutá el script sql/premiaciones.sql y recargá la página.');
        }
        $id_cat = (int) $id_categoria;
        if (!$id_cat) throw new Exception('Categoría inválida.');

        $ts = strtotime($fecha_entrega ?: 'today');
        if (!$ts) throw new Exception('Fecha de entrega inválida.');
        $fecha = date('Y-m-d', $ts);

        $items = $this->obtener_estado_premiables();
        $item = null;
        foreach ($items as $it) {
            if ((int) $it['categoria']['id_categoria'] === $id_cat) { $item = $it; break; }
        }
        if (!$item) throw new Exception('La categoría no tiene resultados cargados todavía.');
        if (!$item['cerrada']) {
            throw new Exception('La competencia todavía no cerró: no corresponde premiarla esta noche.');
        }

        $registrados = array();
        foreach ((array) $puestos as $p) {
            $pos = (int) ($p['puesto'] ?? 0);
            $nom = trim((string) ($p['nombre'] ?? ''));
            if ($pos < 1 || $pos > 3 || $nom === '') continue;
            $registrados[$pos] = array(
                'nombre' => mb_substr($nom, 0, 150),
                'id_ute' => !empty($p['id_ute']) ? (int) $p['id_ute'] : null,
            );
        }
        if (!$registrados) throw new Exception('No hay ningún puesto para confirmar.');

        foreach ($registrados as $pos => $dato) {
            $ref = isset($item['podio'][$pos]) ? ($item['podio'][$pos]['id_resultado_ref'] ?? null) : null;
            $payload = array(
                'id_categoria'     => $id_cat,
                'puesto'           => $pos,
                'id_ute'           => $dato['id_ute'],
                'nombre'           => $dato['nombre'],
                'origen_resultado' => $item['origen'],
                'id_resultado_ref' => $ref,
                'fecha_entrega'    => $fecha,
                'entregado'        => 1,
                'fecha_entregado'  => date('Y-m-d H:i:s'),
                'observaciones'    => $observaciones !== null && trim((string) $observaciones) !== ''
                    ? mb_substr(trim((string) $observaciones), 0, 255) : null,
                'creado_por'       => $id_usuario ? (int) $id_usuario : null,
            );
            $this->db->where('id_categoria', $id_cat);
            $this->db->where('puesto', $pos);
            $existe = $this->db->get('premiaciones')->num_rows();
            if ($existe) {
                $this->db->update('premiaciones', $payload);
            } else {
                $this->db->insert('premiaciones', $payload);
            }
        }
        return count($registrados);
    }

    /** Anula una premiación registrada (vuelve a "pendiente"). */
    public function anular_premiacion($id_categoria) {
        if (!$this->tablas_existentes()) {
            throw new Exception('Falta la tabla premiaciones (sql/premiaciones.sql).');
        }
        $this->db->where('id_categoria', (int) $id_categoria);
        return $this->db->delete('premiaciones');
    }
}

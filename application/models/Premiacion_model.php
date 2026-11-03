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
 *  - TIEMPO / PUNTAJE (MASIVO_TIEMPO): se premian los puestos que diga la
 *    REGLA DE PREMIACIÓN de la categoría (por defecto 1, 2 y 3) tomados del
 *    resultado con más posiciones cargadas.
 *  - REGLA DE PREMIACIÓN POR CATEGORÍA (categorias.premiacion_regla, JSON —
 *    ver sql/migracion_premiacion_categoria.sql): cada categoría define
 *    cuántos puestos premia y qué tipo de premio recibe cada uno, p. ej.:
 *      [{"puesto":1,"premio":"Trofeo"},{"puesto":2,"premio":"Medalla"},
 *       {"puesto":3,"premio":"Medalla"}]
 *      [{"puesto":1,"premio":"Trofeo"}]                          -> solo el 1º
 *      [{"puesto":1,"premio":"Trofeo"},{"puesto":2,"premio":"Trofeo"}]
 *    Si la columna es NULL o está vacía, se usa el default clásico:
 *    1º/2º/3º con medallas Oro/Plata/Bronce (compatibilidad total).
 *    El catálogo de tipos de premio vive en la tabla `premio_tipo` (Trofeo,
 *    Medalla, Diploma, ...); si no existe, se usan nombres libres.
 *  - DÍA SUGERIDO DE ENTREGA (categorias.dia_premiacion_sugerido): si está
 *    configurado, la noche de premiación de esa categoría es ESE día (el
 *    organizador lo eligió como ceremonia). Si no, la noche se deriva del
 *    fixture exactamente como antes.
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
                // SHOW TABLES LIKE: a diferencia de information_schema,
                // funciona también con el driver pdo de CodeIgniter.
                $q = $this->db->query('SHOW TABLES LIKE "' . str_replace('"', '', (string) $tabla) . '"');
                $cache[$tabla] = (bool) ($q && $q->num_rows() > 0);
            } catch (Throwable $e) {
                try {
                    $cache[$tabla] = (bool) $this->db->table_exists($tabla);
                } catch (Throwable $e2) {
                    $cache[$tabla] = false;
                }
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
     *  REGLA DE PREMIACIÓN POR CATEGORÍA
     * ============================================================ */

    /** Puestos que premia una categoría cuando NO tiene regla configurada. */
    const PUESTOS_DEFAULT = array(1, 2, 3);

    /** Tipos de premio "por defecto" (si la tabla premio_tipo no existe). */
    private static $PREMIOS_DEFAULT = array('Trofeo', 'Medalla', 'Diploma');

    /** Íconos conocidos por nombre de premio (para mostrar en pantalla). */
    private static $ICONOS_PREMIO = array(
        'trofeo'  => '🏆',
        'medalla' => '🎖️',
        'diploma' => '📜',
        'copa'    => '🏆',
        'certificado' => '📜',
        'premio'  => '🎁',
    );

    /**
     * Catálogo de tipos de premio (tabla `premio_tipo`). Si la tabla todavía
     * no existe (base sin la migración), devuelve los nombres hardcodeados
     * para que la configuración de premios nunca quede bloqueada.
     */
    public function obtener_tipos_premio() {
        if ($this->_tabla_existe('premio_tipo')) {
            $out = array();
            foreach ($this->_filas_seguras('premio_tipo') as $t) {
                $nombre = trim((string) $t['nombre']);
                if ($nombre === '') continue;
                $out[] = array(
                    'nombre' => $nombre,
                    'icono'  => trim((string) ($t['icono'] ?? '')) ?: $this->icono_premio($nombre),
                );
            }
            if ($out) return $out;
        }
        $out = array();
        foreach (self::$PREMIOS_DEFAULT as $n) {
            $out[] = array('nombre' => $n, 'icono' => $this->icono_premio($n));
        }
        return $out;
    }

    /** Ícono sugerido para un tipo de premio por su nombre. */
    public function icono_premio($nombre) {
        $clave = mb_strtolower(trim(preg_replace('/\s+/u', '', (string) $nombre)), 'UTF-8');
        return isset(self::$ICONOS_PREMIO[$clave]) ? self::$ICONOS_PREMIO[$clave] : '🏅';
    }

    /** Regla default clásica: 1º/2º/3º con medallas Oro/Plata/Bronce. */
    public function regla_default() {
        return array(
            array('puesto' => 1, 'premio' => 'Medalla', 'etiqueta' => 'Oro'),
            array('puesto' => 2, 'premio' => 'Medalla', 'etiqueta' => 'Plata'),
            array('puesto' => 3, 'premio' => 'Medalla', 'etiqueta' => 'Bronce'),
        );
    }

    /**
     * Lee la regla de premiación de una categoría y la normaliza.
     * Acepta el JSON guardado en categorias.premiacion_regla:
     *   [{"puesto":1,"premio":"Trofeo"},{"puesto":2,"premio":"Medalla"}]
     * Tolera variantes (clave "pos"/"puesto", campo extra "etiqueta").
     * Si está vacía o es inválida, devuelve la regla default (1º/2º/3º).
     *
     * @return array lista ordenada de puestos:
     *   [{puesto:int, premio:string, etiqueta:string|null, icono:string}]
     */
    public function parsear_regla_premiacion($json) {
        if (is_array($json)) {
            $data = $json;
        } else {
            $json = trim((string) $json);
            if ($json === '') return $this->regla_default();
            $data = json_decode($json, true);
            if (!is_array($data)) return $this->regla_default();
        }

        $regla = array();
        foreach ($data as $item) {
            if (!is_array($item)) continue;
            $pos = isset($item['puesto']) ? (int) $item['puesto']
                 : (isset($item['pos']) ? (int) $item['pos'] : 0);
            if ($pos < 1 || $pos > 99) continue;
            $premio = trim((string) ($item['premio'] ?? ''));
            if ($premio === '') $premio = 'Puesto';
            $regla[$pos] = array(
                'puesto'   => $pos,
                'premio'   => mb_substr($premio, 0, 50),
                'etiqueta' => isset($item['etiqueta']) && trim((string) $item['etiqueta']) !== ''
                    ? mb_substr(trim((string) $item['etiqueta']), 0, 40) : null,
                'icono'    => $this->icono_premio($premio),
            );
        }
        if (!$regla) return $this->regla_default();
        ksort($regla);
        return array_values($regla);
    }

    /** Regla de una categoría ya cargada (fila con 'premiacion_regla'). */
    public function regla_de_categoria($cat) {
        return $this->parsear_regla_premiacion($cat['premiacion_regla'] ?? null);
    }

    /**
     * ¿La tabla `categorias` ya tiene las columnas de configuración de
     * premiación? (sql/migracion_premiacion_categoria.sql). Lo usa Gestión
     * Deportiva para mostrar u ocultar el editor de reglas sin explotar.
     */
    public function columnas_regla_existentes() {
        return $this->_columna_existe('categorias', 'premiacion_regla');
    }

    /**
     * Regla guardada + día sugerido de UNA categoría (para el modal editor de
     * Gestión Deportiva / Premiación). Si la columna no existe todavía,
     * devuelve la default sin día sugerido.
     */
    public function obtener_regla_categoria($id_categoria) {
        $id_cat = (int) $id_categoria;
        if (!$id_cat) throw new Exception('Categoría inválida.');

        $fila = null;
        if ($this->columnas_regla_existentes()) {
            $this->db->select('nombre_categoria, premiacion_regla, dia_premiacion_sugerido');
            $this->db->where('id_categoria', $id_cat);
            $fila = $this->_fila_segura('categorias');
        }
        if (!$fila) {
            $this->db->select('nombre_categoria');
            $this->db->where('id_categoria', $id_cat);
            $fila = $this->_fila_segura('categorias');
        }
        if (!$fila) throw new Exception('La categoría no existe.');

        return array(
            'id_categoria' => $id_cat,
            'regla'        => $this->parsear_regla_premiacion($fila['premiacion_regla'] ?? null),
            'dia_sugerido' => !empty($fila['dia_premiacion_sugerido'])
                ? substr((string) $fila['dia_premiacion_sugerido'], 0, 10) : '',
            'default'      => empty(trim((string) ($fila['premiacion_regla'] ?? ''))),
        );
    }

    /** Puestos que premia la regla (ej: [1,2,3] o [1] o [1,2]). */
    public function puestos_de_regla($regla) {
        $out = array();
        foreach ((array) $regla as $r) {
            $out[] = (int) $r['puesto'];
        }
        return $out;
    }

    /** ¿La regla es la default (podio clásico con medallas)? */
    public function regla_es_default($regla) {
        $default = $this->regla_default();
        return count($regla) === count($default)
            && json_encode($regla, JSON_UNESCAPED_UNICODE) === json_encode($default, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Resumen legible de la regla para mostrar en pantalla / guion:
     * "1º 🏆 Trofeo · 2º 🎖️ Medalla · 3º 🎖️ Medalla".
     */
    public function texto_regla_premiacion($regla) {
        $partes = array();
        foreach ((array) $regla as $r) {
            $txt = $r['puesto'] . 'º';
            if (!empty($r['icono'])) $txt .= ' ' . $r['icono'];
            $txt .= ' ' . $r['premio'];
            if (!empty($r['etiqueta'])) $txt .= ' (' . $r['etiqueta'] . ')';
            $partes[] = $txt;
        }
        return implode(' · ', $partes);
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

    /** Podio de un resultado TIEMPO / PUNTAJE: los puestos que diga la regla. */
    public function _podio_desde_tiempo($resultado, $puestos = array(1, 2, 3)) {
        $es_puntaje = (isset($resultado['tipo_resultado']) && $resultado['tipo_resultado'] === 'PUNTAJE');
        $ordenadas = $resultado['detalle'];
        usort($ordenadas, function ($a, $b) {
            return (int) $a['posicion'] - (int) $b['posicion'];
        });
        $necesita = max($puestos); // solo hay que recorrer hasta el último puesto premiado
        $maximo = min(count($ordenadas), max(0, $necesita));
        $nombres = array();
        for ($i = 0; $i < $maximo; $i++) {
            $d = $ordenadas[$i];
            $pos = (int) $d['posicion'];
            if (!in_array($pos, $puestos, true)) continue;
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
            // SIN coma inicial: CI concatena los select() con comas y una coma
            // extra al principio rompe el SQL ("SELECT r.*, , ug...").
            $this->db->select('ug.nombre_ute AS nombre_ganador_desempate', FALSE);
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

    /**
     * Nombres "reales" de las categorías por deporte (consulta única).
     *
     * ¿Por qué? El reporte del locutor LIMPIA el título de cada bloque
     * (`_limpiar_nombre_categoria()` en rep_locutor_view) sacando género,
     * tipo de largada y modalidad — porque en varias provincias se cargan
     * TODAS las categorías con el mismo nombre ("Libre", "Sub-18",
     * "Principiantes...") y lo único que las distingue es justamente ese
     * sufijo: "Libre · Masculino" y "Libre · Femenino" terminarían con el
     * mismo título y el locutor no podría distinguirlas. Con este mapa, si
     * al limpiar dos categorías del mismo deporte quedan idénticas, el
     * título conserva el nombre COMPLETO tal como está cargado.
     */
    private function _nombres_categoria_por_deporte() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = array();
        try {
            $this->db->select('c.id_deporte, c.id_categoria, c.nombre_categoria', FALSE);
            foreach ($this->_filas_seguras('categorias c') as $r) {
                $cache[(int) $r['id_deporte']][] = array(
                    'id'   => (int) $r['id_categoria'],
                    'nom'  => trim((string) $r['nombre_categoria']),
                );
            }
        } catch (Throwable $e) {
            log_message('error', '[Premiación] no se pudo leer el nombre de las categorías: ' . $e->getMessage());
        }
        return $cache;
    }

    /**
     * Ids de categoría cuyo nombre, dentro del DEPORTE, es ambiguo una vez
     * limpio (dos o más categorías comparten el mismo nombre base: "Libre ·
     * Masculino" vs "Libre · Femenino"). Esas conservan el nombre completo
     * en el título del guion del locutor. Se calcula UNA sola vez por request.
     */
    private function _categorias_ambiguas_en_deporte() {
        static $ambiguas = null;
        if ($ambiguas !== null) return $ambiguas;
        $ambiguas = array();
        // La limpieza vive en la vista del PDF (misma clase): si no está
        // disponible, simplemente no se marca ninguna como ambigua.
        if (!method_exists($this, '_limpiar_nombre_categoria')) return $ambiguas;
        foreach ($this->_nombres_categoria_por_deporte() as $grupos) {
            $vistas = array();
            foreach ($grupos as $g) {
                $clave = mb_strtolower($this->_limpiar_nombre_categoria($g['nom']), 'UTF-8');
                if ($clave === '') continue;
                $vistas[$clave][] = $g['id'];
            }
            foreach ($vistas as $ids) {
                if (count($ids) > 1) {
                    foreach ($ids as $id) $ambiguas[$id] = true;
                }
            }
        }
        return $ambiguas;
    }

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
        // Regla de premiación POR CATEGORÍA (JSON con puestos + tipo de premio)
        // y día sugerido de entrega: sql/migracion_premiacion_categoria.sql.
        // Se agregan solo si existen, para no romper en bases sin el ALTER.
        if ($this->_columna_existe('categorias', 'premiacion_regla')) {
            $this->db->select(', c.premiacion_regla', FALSE);
        }
        if ($this->_columna_existe('categorias', 'dia_premiacion_sugerido')) {
            $this->db->select(', c.dia_premiacion_sugerido', FALSE);
        }
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');
        return $this->_filas_seguras();
    }

    /** ¿Existe una columna? (tolera bases sin el ALTER). */
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

    /**
     * ¿Existe una columna en `resultados`? (tolera bases sin el ALTER).
     * Atajo de _columna_existe() para la tabla resultados.
     */
    private function _columna_resultado_existe($columna) {
        return $this->_columna_existe('resultados', $columna);
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

    /** Podio desde la tabla de puntaje: los puestos que diga la regla. */
    private function _podio_desde_tabla($info, $puestos = array(1, 2, 3)) {
        $out = array();
        $extra_base = 'Tabla de puntos: ' . $info['puntos_victoria']
            . ' pts victoria / ' . $info['puntos_empate'] . ' pts empate / 0 derrota';
        foreach ($info['tabla'] as $i => $e) {
            $pos = $i + 1;
            if (!in_array($pos, $puestos, true)) continue;
            $out[$pos] = array(
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
            // REGLA DE PREMIACIÓN de la categoría: puestos que se premian y
            // tipo de premio de cada uno (default clásico 1º/2º/3º si no está
            // configurada o la base todavía no tiene la columna).
            $regla = $this->parsear_regla_premiacion($cat['premiacion_regla'] ?? null);
            $puestos_regla = $this->puestos_de_regla($regla);
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
                $podio_nombres = $this->_podio_desde_tiempo($mejor, $puestos_regla);
                foreach ($podio_nombres as $pos => $p) {
                    $podio[$pos] = array_merge($p, array('id_resultado_ref' => (int) $mejor['id_resultado']));
                }
            } elseif ($define_por_puntaje) {
                // Podio = tabla de puntos del torneo completo (3/1/0).
                $info_tabla = $this->_tabla_puntaje($id_cat, $resultados, $cat, $fixtures);
                $podio = $this->_podio_desde_tabla($info_tabla, $puestos_regla);
                if (!isset($podio[1])) continue; // todavía no hay ningún partido computable
            } else {
                $podio = $this->_podio_marcador_categoria($id_cat);
                if (!isset($podio[1]) && !isset($podio[2]) && !isset($podio[3])) {
                    continue; // sin ningún resultado definitorio todavía
                }
                // El podio "bruto" de enfrentamiento trae 1º/2º/3º: se recorta
                // a los puestos que la regla de la categoría decida premiar.
                $podio = array_intersect_key($podio, array_flip($puestos_regla));
            }

            // ¿Cuántos puestos del podio están realmente definidos? Si se
            // inscribieron menos participantes de los que premia la regla
            // (ej: podio de 3 pero solo llegaron 2), NUNCA habrá un 3º: la
            // categoría igual debe poder premiarse esa noche con lo que hay.
            $puestos_definidos = count($podio);
            $faltan_por_inasistencia = $puestos_definidos < count($puestos_regla);

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
                // Alcanza con que haya al menos UN puesto definido (final o
                // tercer puesto): si la categoría quedó con menos participantes
                // que puestos a premiar, igual cierra y se premia lo que hay.
                $cerrada = $puestos_definidos > 0;
                $motivo = 'Deporte de un solo día: se premia esa misma noche';
                // Día en que se jugó la FINAL (fixture del partido del podio);
                // si no está, el día programado de la categoría.
                $final_fx = null;
                foreach ($fixtures as $f) {
                    if ($f['fase'] === 'FINAL' && !empty($f['fecha_competencia'])) { $final_fx = $f; break; }
                }
                $fecha_entrega = $final_fx ? $this->_norm_fecha($final_fx['fecha_competencia']) : null;
                if (!$fecha_entrega) {
                    // El primer puesto DEFINIDO puede ser el 3º (si no se jugó
                    // la final porque quedaron pocos participantes): se toma
                    // el primero que exista, no siempre $podio[1].
                    $primero_def = null;
                    foreach (array(1, 2, 3) as $pos_aux) {
                        if (isset($podio[$pos_aux])) { $primero_def = $podio[$pos_aux]; break; }
                    }
                    $fecha_entrega = ($primero_def['fecha_jugada'] ?? null)
                        ?: $this->_fecha_del_resultado_ref((int) ($primero_def['id_resultado_ref'] ?? 0), $res_por_id, $fx_por_id);
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
                // Cierra con cualquier puesto definido (ver nota UNICO_DIA):
                // si quedaron menos participantes que puestos a premiar, la
                // noche de la definición igual es la de la premiación.
                $cerrada = $puestos_definidos > 0 && !empty($fecha_def);
                $motivo = 'Multidía: cierra el día de la definición';
                $fecha_entrega = $fecha_def;
            }

            // Aviso claro cuando la regla premia más puestos de los que hay:
            // la categoría se puede premiar igual, solo se aclara el motivo.
            if ($cerrada && $faltan_por_inasistencia) {
                $motivo .= ' · ⚠️ hay ' . $puestos_definidos
                    . ' puesto(s) definidos de ' . count($puestos_regla)
                    . ': se premia solo lo que participó';
            }

            if ($cerrada && !$fecha_entrega) {
                // Sin fecha de competencia conocida no se puede asignar noche:
                // se marca como no-cerrada con motivo claro (así el filtro por
                // día no "muestra de más") y se avisa en pantalla.
                $cerrada = false;
                $motivo = 'Sin fecha de competencia: cargá el día de la FINAL en el Fixture de esta categoría.';
            }

            // ---------- DÍA SUGERIDO DE PREMIACIÓN (configurable) ----------
            // Si la categoría tiene `dia_premiacion_sugerido`, ESA es la noche
            // de su ceremonia (el organizador decide, por ejemplo, premiar todo
            // el viernes aunque la final se haya jugado el jueves). No cambia
            // el cierre: solo reasigna la noche de entrega.
            $dia_sugerido = $this->_norm_fecha($cat['dia_premiacion_sugerido'] ?? null);
            if ($cerrada && $dia_sugerido) {
                $fecha_entrega = $dia_sugerido;
                $motivo .= ' · premiación sugerida para el ' . date('d/m/Y', strtotime($dia_sugerido));
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
                // REGLA DE PREMIACIÓN de la categoría (puestos + tipo de
                // premio). La vista/locutor la usan para dibujar cada puesto.
                'regla'         => $regla,
                'regla_default' => $this->regla_es_default($regla),
                'texto_regla'   => $this->texto_regla_premiacion($regla),
                'dia_sugerido'  => $dia_sugerido,
                // Tabla de puntos completa (solo en deportes por puntaje):
                // la vista puede mostrarla como tooltip/mini-tabla.
                'tabla_puntaje' => $define_por_puntaje ? $info_tabla['tabla'] : null,
            );
        }

        return $items;
    }

    /**
     * PODIOS PARA EL PANEL PÚBLICO (pestaña "Resultados").
     *
     * Reutiliza EXACTAMENTE la misma lógica de `obtener_estado_premiables()`
     * (podio por FINAL+TERCER_PUESTO, por tabla de puntos o por tiempos), así
     * lo que ve el público es idéntico a lo que ve el staff en Premiación.
     *
     * Solo devuelve categorías de ENFRENTAMIENTO (MARCADOR): son las que tienen
     * un "camino al podio" (grupos, semis, final). Los deportes de tiempo/
     * puntaje masivo se siguen mostrando como cards simples en el panel.
     *
     * Cada item trae además `partidos`: TODOS los resultados MARCADOR de la
     * categoría (incluidos los anteriores a la final), marcados según su peso:
     *   decisivo = 1  -> la FINAL / el TERCER_PUESTO que definieron el podio
     *   decisivo = 0  -> partido del camino (fase real: GRUPO, SEMIFINAL, ...)
     * Así, aunque todavía no exista la final, el público igual ve los partidos
     * cargados dentro del bloque de la categoría.
     *
     * @return array items: {id_categoria, categoria, genero, id_deporte,
     *         deporte, sistema, cerrada, podium{1,2,3}, regla[], texto_regla,
     *         fecha_entrega, partidos[]} ordenados por deporte y luego por
     *         relevancia (podio definido primero).
     */
    public function podios_para_panel_publico() {
        if (!$this->_tabla_existe('resultados') || !$this->_tabla_existe('fixtures')) return array();

        // FASES que definen el podio de un torneo de enfrentamiento. El resto
        // del fixture es "el camino": se muestra pero sin corona.
        static $fases_decisivas = array('FINAL', 'TERCER_PUESTO');

        $items = array();
        foreach ($this->obtener_estado_premiables() as $it) {
            // Solo enfrentamientos resueltos por marcador (los masivos ya se
            // ven como listas de posiciones en la pestaña Resultados).
            if ((string) $it['origen'] !== 'MARCADOR') continue;

            $cat    = $it['categoria'];
            $id_cat = (int) $cat['id_categoria'];
            $define_por_puntaje = ((string) ($cat['sistema_definicion'] ?? 'FASE_FINAL')) === 'PUNTAJE';

            // ---------- Podio (mismos datos que usa la Premiación) ----------
            $regla = isset($it['regla']) ? $it['regla'] : $this->regla_default();
            $puestos = $this->puestos_de_regla($regla);
            $podium = array();
            foreach ($puestos as $pos) {
                $p = isset($it['podio'][$pos]) ? $it['podio'][$pos] : null;
                $r = null;
                foreach ($regla as $rr) {
                    if ((int) ($rr['puesto'] ?? 0) === $pos) { $r = $rr; break; }
                }
                $podium[$pos] = array(
                    'puesto'   => $pos,
                    'nombre'   => $p && !empty($p['nombre']) ? trim((string) $p['nombre']) : null,
                    'extra'    => $p && !empty($p['extra']) ? (string) $p['extra'] : null,
                    'desempate'=> $p && !empty($p['desempate']) ? (string) $p['desempate'] : null,
                    'premio'   => $r ? trim((string) ($r['premio'] ?? '')) : '',
                    'etiqueta' => $r ? trim((string) ($r['etiqueta'] ?? '')) : '',
                    'icono'    => $r ? trim((string) ($r['icono'] ?? '')) : '',
                );
            }

            // ---------- Partidos de la categoría (todos los MARCADOR) ----------
            $ids_ref = array();
            foreach ($it['podio'] as $p) {
                if (!empty($p['id_resultado_ref'])) $ids_ref[(int) $p['id_resultado_ref']] = true;
            }

            $this->db->select('r.id_resultado, r.id_fixture, r.fecha_resultado, r.hubo_desempate, r.desempate_metodo', FALSE);
            if ($this->_columna_resultado_existe('id_ute_ganador')) {
                // OJO: sin coma inicial — CI une los select() consecutivos con
                // coma; si arranca con coma genera SQL inválido (".. , , r.x").
                $this->db->select('r.id_ute_ganador, ug.nombre_ute AS nombre_ganador_desempate', FALSE);
                $this->db->join('utes ug', 'ug.id_ute = r.id_ute_ganador', 'left');
            }
            $this->db->from('resultados r');
            $this->db->where('r.id_categoria', $id_cat);
            $this->db->where('r.tipo_resultado', 'MARCADOR');
            $rows = $this->_filas_seguras();
            if (!$rows) continue;

            // Fixture vinculado: fase + día/hora en que se JUGÓ. Se leen con
            // raw SQL los ids concretos (la Query Builder compartida puede
            // quedar "sucia" tras la consulta anterior y devolver 0 filas).
            $fx_ids = array();
            foreach ($rows as $r) {
                $idf = (int) ($r['id_fixture'] ?? 0);
                if ($idf > 0) $fx_ids[$idf] = true;
            }
            $fx_map = array();
            if ($fx_ids) {
                try {
                    $q_fx = $this->db->query(
                        'SELECT `id_fixture`, `fase`, `numero_fecha`, `fecha_competencia`, `hora_inicio`'
                        . ' FROM `fixtures` WHERE `id_fixture` IN ('
                        . implode(',', array_map('intval', array_keys($fx_ids))) . ')'
                    );
                    foreach (($q_fx && is_object($q_fx)) ? $q_fx->result_array() : array() as $f) {
                        $fx_map[(int) $f['id_fixture']] = $f;
                    }
                } catch (Throwable $e) {
                    log_message('error', '[Premiación] podio público — fixture: ' . $e->getMessage());
                }
            }

            $detalles = $this->_detalle_de_resultados(array_column($rows, 'id_resultado'));
            $partidos = array();
            foreach ($rows as $r) {
                $idr = (int) $r['id_resultado'];
                $det = isset($detalles[$idr]) ? $detalles[$idr] : array();
                $m = $this->_parsear_marcador($det);
                if (!$m) continue; // resultado incompleto: nada que mostrar

                $idf = (int) ($r['id_fixture'] ?? 0);
                $fxi = isset($fx_map[$idf]) ? $fx_map[$idf] : array();
                $fase = strtoupper(trim((string) ($fxi['fase'] ?? '')));
                // Decisivo si ES la fase del podio o si el resultado de ese
                // partido es la referencia directa de algún puesto del podio.
                $es_final = in_array($fase, $fases_decisivas, true);
                $decisivo = ($es_final || isset($ids_ref[$idr])) ? 1 : 0;

                $partidos[] = array(
                    'id_resultado' => $idr,
                    'id_fixture'   => $idf ?: null,
                    'fase'         => $fase !== '' ? $fase : null,
                    'numero_fecha' => isset($fxi['numero_fecha']) ? (int) $fxi['numero_fecha'] : null,
                    'decisivo'     => $decisivo,
                    // ids de los equipos: el panel público los usa para saber
                    // qué partidos son de la delegación filtrada.
                    'eq1_id'       => $m['eqA']['id'],
                    'eq2_id'       => $m['eqB']['id'],
                    'eq1_nombre'   => $m['eqA']['nombre'],
                    'eq1_tantos'   => (int) $m['eqA']['tantos'],
                    'eq2_nombre'   => $m['eqB']['nombre'],
                    'eq2_tantos'   => (int) $m['eqB']['tantos'],
                    'gana1'        => $m['eqA']['tantos'] > $m['eqB']['tantos'],
                    'gana2'        => $m['eqB']['tantos'] > $m['eqA']['tantos'],
                    'empate'       => $m['eqA']['tantos'] === $m['eqB']['tantos'],
                    'hubo_desempate' => !empty($r['hubo_desempate']),
                    'desempate_metodo' => trim((string) ($r['desempate_metodo'] ?? '')),
                    'ganador_desempate' => trim((string) ($r['nombre_ganador_desempate'] ?? '')),
                    'fecha'        => $this->_norm_fecha($fxi['fecha_competencia'] ?? null)
                                      ?: $this->_norm_fecha($r['fecha_resultado'] ?? null),
                    'hora'         => trim((string) ($fxi['hora_inicio'] ?? '')),
                );
            }

            // Orden interno: los decisivos primero, después cronológico y por
            // jornada del fixture (el público lee el torneo como se jugó).
            usort($partidos, function ($a, $b) {
                if ($a['decisivo'] !== $b['decisivo']) return $b['decisivo'] - $a['decisivo'];
                $cmp = strcmp((string) ($a['fecha'] ?: '9999'), (string) ($b['fecha'] ?: '9999'));
                if ($cmp !== 0) return $cmp;
                if ((int) $a['numero_fecha'] !== (int) $b['numero_fecha']) {
                    return (int) $a['numero_fecha'] - (int) $b['numero_fecha'];
                }
                return (int) $a['id_resultado'] - (int) $b['id_resultado'];
            });

            $items[] = array(
                'id_categoria'  => $id_cat,
                'categoria'     => trim((string) ($cat['nombre_categoria'] ?? '')),
                'genero'        => trim((string) ($cat['genero'] ?? '')),
                'id_deporte'    => (int) ($cat['id_deporte'] ?? 0),
                'deporte'       => trim((string) ($cat['nombre_deporte'] ?? '')),
                'sistema'       => $define_por_puntaje ? 'PUNTAJE' : 'FASE_FINAL',
                'cerrada'       => !empty($it['cerrada']),
                'tabla_puntaje' => $define_por_puntaje ? ($it['tabla_puntaje'] ?? null) : null,
                'podium'        => $podium,
                'regla'         => $regla,
                'texto_regla'   => $this->texto_regla_premiacion($regla),
                'fecha_entrega' => $it['fecha_entrega'] ?? null,
                'partidos'      => $partidos,
            );
        }

        // Por deporte (nombre) y, dentro del deporte, podio definido primero.
        usort($items, function ($a, $b) {
            $cmp = strcasecmp($a['deporte'], $b['deporte']);
            if ($cmp !== 0) return $cmp;
            if ($a['cerrada'] !== $b['cerrada']) return $a['cerrada'] ? -1 : 1;
            return strcasecmp($a['categoria'], $b['categoria']);
        });

        return $items;
    }

    /**
     * PODIOS PARA EL PANEL PÚBLICO — DEPORTES DE TIEMPO / PUNTAJE MASIVO.
     *
     * Complemento de `podios_para_panel_publico()`: devuelve las categorías
     * MASIVO_TIEMPO (atletismo, natación, etc.) con el podio resuelto por la
     * MISMA lógica que usa la Premiación (`obtener_estado_premiables()`), para
     * que en el panel TODO quede agrupado por deporte: adentro del bloque del
     * deporte va una card por categoría y dentro se listan TODAS las jornadas
     * cargadas (cada prueba con su planilla de posiciones), no solo la mejor.
     *
     * Se respeta el MODELO DE PREMIACIÓN de cada categoría: `premiacion_regla`
     * decide qué puestos se suben al podio y con qué etiqueta/icono/premio
     * (1º/2º/3º por defecto; pueden ser "4tos", trofeos especiales, etc.).
     *
     * @return array items {id_categoria, categoria, genero, id_deporte,
     *         deporte, masivo, cerrada, podium{puesto=>...}, regla[],
     *         texto_regla, fecha_entrega, jornadas[]} — misma forma que los
     *         podios de enfrentamiento, para que la vista los pinte igual.
     */
    public function podios_masivos_para_panel_publico() {
        if (!$this->_tabla_existe('resultados')) return array();

        // Resultados con su detalle, agrupados por categoría (una sola pasada).
        $rows = $this->_filas_seguras('resultados');
        if (!$rows) return array();
        $detalles = $this->_detalle_de_resultados(array_column($rows, 'id_resultado'));
        $por_cat = array();
        foreach ($rows as $r) {
            $r['detalle'] = isset($detalles[(int) $r['id_resultado']])
                ? $detalles[(int) $r['id_resultado']] : array();
            $por_cat[(int) $r['id_categoria']][] = $r;
        }

        // Día/hora en que se corrió cada prueba (fixtures vinculados), raw SQL.
        $fx_ids = array();
        foreach ($rows as $r) {
            $idf = (int) ($r['id_fixture'] ?? 0);
            if ($idf > 0) $fx_ids[$idf] = true;
        }
        $fx_map = array();
        if ($fx_ids && $this->_tabla_existe('fixtures')) {
            try {
                $q_fx = $this->db->query(
                    'SELECT `id_fixture`, `fecha_competencia`, `hora_inicio`, `nombre_prueba`'
                    . ' FROM `fixtures` WHERE `id_fixture` IN ('
                    . implode(',', array_map('intval', array_keys($fx_ids))) . ')'
                );
                foreach (($q_fx && is_object($q_fx)) ? $q_fx->result_array() : array() as $f) {
                    $fx_map[(int) $f['id_fixture']] = $f;
                }
            } catch (Throwable $e) {
                // `nombre_prueba` puede no existir en bases viejas: reintento sin ella.
                try {
                    $q_fx = $this->db->query(
                        'SELECT `id_fixture`, `fecha_competencia`, `hora_inicio` FROM `fixtures` WHERE `id_fixture` IN ('
                        . implode(',', array_map('intval', array_keys($fx_ids))) . ')'
                    );
                    foreach (($q_fx && is_object($q_fx)) ? $q_fx->result_array() : array() as $f) {
                        $fx_map[(int) $f['id_fixture']] = $f;
                    }
                } catch (Throwable $e2) {
                    log_message('error', '[Premiación] podios masivos — fixture: ' . $e2->getMessage());
                }
            }
        }

        $items = array();
        foreach ($this->obtener_estado_premiables() as $it) {
            $cat    = $it['categoria'];
            $id_cat = (int) $cat['id_categoria'];

            // Sólo deportes de largada masiva (tiempo/puntaje): los de
            // enfrentamiento ya vienen de `podios_para_panel_publico()`.
            if ((string) $cat['modalidad_competencia'] !== 'MASIVO_TIEMPO') continue;

            // ---------- Podio según la REGLA de premiación de la categoría ---
            $regla = isset($it['regla']) ? $it['regla'] : $this->regla_default();
            $puestos = $this->puestos_de_regla($regla);
            $podium = array();
            foreach ($puestos as $pos) {
                $p = isset($it['podio'][$pos]) ? $it['podio'][$pos] : null;
                $r = null;
                foreach ($regla as $rr) {
                    if ((int) ($rr['puesto'] ?? 0) === $pos) { $r = $rr; break; }
                }
                $podium[$pos] = array(
                    'puesto'    => $pos,
                    'nombre'    => $p && !empty($p['nombre']) ? trim((string) $p['nombre']) : null,
                    'extra'     => $p && !empty($p['extra']) ? (string) $p['extra'] : null,
                    'desempate' => $p && !empty($p['desempate']) ? (string) $p['desempate'] : null,
                    'premio'    => $r ? trim((string) ($r['premio'] ?? '')) : '',
                    'etiqueta'  => $r ? trim((string) ($r['etiqueta'] ?? '')) : '',
                    'icono'     => $r ? trim((string) ($r['icono'] ?? '')) : '',
                );
            }

            // ---------- TODAS las jornadas/pruebas cargadas de la categoría --
            // Cada resultado TIEMPO/PUNTAJE es una carrera con su planilla.
            // Se muestran todas (con sus posiciones completas), así el público
            // ve también los resultados anteriores al que definió el podio.
            $jornadas = array();
            foreach (isset($por_cat[$id_cat]) ? $por_cat[$id_cat] : array() as $r) {
                $tipo = (string) $r['tipo_resultado'];
                if (!in_array($tipo, array('TIEMPO', 'PUNTAJE'), true)) continue;
                if (!$r['detalle']) continue;

                $det = $r['detalle'];
                usort($det, function ($a, $b) {
                    $pa = (int) $a['posicion']; $pb = (int) $b['posicion'];
                    if ($pa !== $pb) return $pa - $pb;
                    return strcmp((string) $a['nombre_libre'], (string) $b['nombre_libre']);
                });

                $idf = (int) ($r['id_fixture'] ?? 0);
                $fxi = isset($fx_map[$idf]) ? $fx_map[$idf] : array();
                $fecha = $this->_norm_fecha($fxi['fecha_competencia'] ?? null)
                    ?: $this->_norm_fecha($r['fecha_resultado'] ?? null);

                $filas = array();
                foreach ($det as $d) {
                    $filas[] = array(
                        'posicion' => (int) $d['posicion'],
                        'nombre'   => trim((string) $d['nombre_libre']),
                        'marca'    => trim((string) ($d['tiempo'] ?? '')),
                        // En PUNTAJE la columna `tiempo` guarda el puntaje: se
                        // muestra como "X pts" en la planilla del panel público.
                        'es_puntos'=> ($tipo === 'PUNTAJE'),
                        'es_podio' => in_array((int) $d['posicion'], $puestos, true),
                    );
                }

                $prueba = trim((string) ($fxi['nombre_prueba'] ?? ''));
                if ($prueba === '') $prueba = trim((string) ($r['nombre_evento'] ?? ''));

                $jornadas[] = array(
                    'id_resultado' => (int) $r['id_resultado'],
                    'tipo'         => $tipo,
                    'prueba'       => $prueba,
                    'fecha'        => $fecha,
                    'hora'         => trim((string) ($fxi['hora_inicio'] ?? '')),
                    'lugar'        => trim((string) ($r['lugar'] ?? '')),
                    'es_mejor'     => false, // se marca abajo: base del podio
                    'filas'        => $filas,
                );
            }

            if (!$jornadas) continue; // sin planillas cargadas: nada que mostrar

            // La prueba que originó el podio (id_resultado_ref del 1º puesto)
            // se destaca como la planilla oficial del podio.
            $ref = isset($it['podio'][1]['id_resultado_ref'])
                ? (int) $it['podio'][1]['id_resultado_ref'] : null;
            if (!$ref) {
                // Sin podio cerrado, se destaca la jornada más nueva cargada.
                $max_id = 0;
                foreach ($jornadas as $j) $max_id = max($max_id, $j['id_resultado']);
                $ref = $max_id;
            }
            foreach ($jornadas as &$j) {
                if ($j['id_resultado'] === $ref) $j['es_mejor'] = true;
            }
            unset($j);

            // Orden: la planilla del podio primero, después cronológico inverso.
            usort($jornadas, function ($a, $b) {
                if ($a['es_mejor'] !== $b['es_mejor']) return $a['es_mejor'] ? -1 : 1;
                $cmp = strcmp((string) ($b['fecha'] ?: '0000'), (string) ($a['fecha'] ?: '0000'));
                if ($cmp !== 0) return $cmp;
                return (int) $b['id_resultado'] - (int) $a['id_resultado'];
            });

            $items[] = array(
                'id_categoria'  => $id_cat,
                'categoria'     => trim((string) ($cat['nombre_categoria'] ?? '')),
                'genero'        => trim((string) ($cat['genero'] ?? '')),
                'id_deporte'    => (int) ($cat['id_deporte'] ?? 0),
                'deporte'       => trim((string) ($cat['nombre_deporte'] ?? '')),
                'masivo'        => true,
                'cerrada'       => !empty($it['cerrada']),
                'tabla_puntaje' => null,
                'podium'        => $podium,
                'regla'         => $regla,
                'texto_regla'   => $this->texto_regla_premiacion($regla),
                'fecha_entrega' => $it['fecha_entrega'] ?? null,
                'partidos'      => array(),
                'jornadas'      => $jornadas,
            );
        }

        // Por deporte (nombre) y, dentro del deporte, podio definido primero.
        usort($items, function ($a, $b) {
            $cmp = strcasecmp($a['deporte'], $b['deporte']);
            if ($cmp !== 0) return $cmp;
            if ($a['cerrada'] !== $b['cerrada']) return $a['cerrada'] ? -1 : 1;
            return strcasecmp($a['categoria'], $b['categoria']);
        });

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
     *
     * @param bool $incluir_completas Por defecto se excluyen las categorías que
     *        ya tienen TODOS sus puestos registrados (porque 'premiacion.php'
     *        las muestra aparte, en la columna "Premiados"). El reporte del
     *        locutor pasa TRUE para que ninguna entrega confirmada de noches
     *        anteriores quede fuera del guion impreso.
     */
    public function obtener_atrasos_hasta($fecha, $incluir_completas = false) {
        $items = $this->obtener_estado_premiables();
        $pedido = $this->_norm_fecha($fecha);
        $out = array();
        if (!$pedido) return $out;
        foreach ($items as $it) {
            if (!$it['cerrada']) continue;
            $fe = $this->_norm_fecha($it['fecha_entrega']);
            if (!$fe || $fe >= $pedido) continue; // no es anterior / sin fecha
            // si ya tiene todos los puestos registrados, no es un atraso
            $puestos_esperados = $this->puestos_de_regla(
                isset($it['regla']) ? $it['regla'] : $this->regla_default()
            );
            $entregados = array();
            foreach ($it['entregadas'] as $e) $entregados[(int) $e['puesto']] = $e;
            $completa = !empty($puestos_esperados);
            foreach ($puestos_esperados as $p) {
                if (!isset($entregados[$p])) { $completa = false; break; }
            }
            if ($completa && $incluir_completas !== true) continue;
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
     * Confirma la premiación de una categoría (los puestos que define su
     * REGLA DE PREMIACIÓN).
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

        // Solo se pueden registrar los puestos definidos en la regla de la
        // categoría (default 1º/2º/3º si no está configurada).
        $regla = isset($item['regla']) ? $item['regla'] : $this->regla_default();
        $permitidos = $this->puestos_de_regla($regla);
        // Si quedaron menos participantes que puestos a premiar (ej: podio de
        // 3 y se presentaron 2), los puestos SIN definir se perdonan: se puede
        // confirmar la premiación igual con lo que hay.
        $podio_definido = isset($item['podio']) ? $item['podio'] : array();
        $sin_definir = array();
        foreach ($permitidos as $p_aux) {
            if (!isset($podio_definido[$p_aux])) $sin_definir[] = $p_aux;
        }
        $perdonadas = count($sin_definir) > 0 && count($podio_definido) > 0;
        $premio_por_puesto = array();
        foreach ($regla as $r) $premio_por_puesto[(int) $r['puesto']] = $r['premio'];

        $registrados = array();
        $ignorados = 0;
        foreach ((array) $puestos as $p) {
            $pos = (int) ($p['puesto'] ?? 0);
            $nom = trim((string) ($p['nombre'] ?? ''));
            if ($pos < 1 || $nom === '') continue;
            if (!in_array($pos, $permitidos, true)) { $ignorados++; continue; }
            $registrados[$pos] = array(
                'nombre' => mb_substr($nom, 0, 150),
                'id_ute' => !empty($p['id_ute']) ? (int) $p['id_ute'] : null,
            );
        }
        if (!$registrados) {
            throw new Exception($ignorados
                ? 'Ningún puesto enviado corresponde a la regla de premiación de esta categoría ('
                  . $this->texto_regla_premiacion($regla) + ').'
                : 'No hay ningún puesto para confirmar.');
        }

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
            // La columna `tipo_premio` existe desde sql/migracion_premiacion_
            // categoria.sql: guarda qué recibió cada puesto (Trofeo/Medalla/...).
            // Se agrega solo si está, para no romper bases sin el ALTER.
            if ($this->_columna_existe('premiaciones', 'tipo_premio')) {
                $payload['tipo_premio'] = $premio_por_puesto[$pos] ?? null;
            }
            $this->db->where('id_categoria', $id_cat);
            $this->db->where('puesto', $pos);
            $existe = $this->db->get('premiaciones')->num_rows();
            if ($existe) {
                $this->db->update('premiaciones', $payload);
            } else {
                $this->db->insert('premiaciones', $payload);
            }
        }

        // Puestos de la regla que nunca se definieron (quedaron menos
        // participantes que premios): se registran como "no entregado" para
        // que la premiación quede COMPLETA y no vuelva a aparecer pendiente.
        if ($perdonadas) {
            foreach ($sin_definir as $pos) {
                $this->db->where('id_categoria', $id_cat);
                $this->db->where('puesto', $pos);
                if ($this->db->get('premiaciones')->num_rows()) continue;
                $nota = 'No se presentó / no hubo participante para este puesto';
                $observacion = trim((string) ($observaciones ?? ''));
                $this->db->insert('premiaciones', array(
                    'id_categoria'     => $id_cat,
                    'puesto'           => $pos,
                    'id_ute'           => null,
                    'nombre'           => '(sin premiar)',
                    'origen_resultado' => $item['origen'],
                    'id_resultado_ref' => null,
                    'fecha_entrega'    => $fecha,
                    'entregado'        => 1,
                    'fecha_entregado'  => date('Y-m-d H:i:s'),
                    'observaciones'    => mb_substr($nota
                        . ($observacion !== '' ? ' — ' . $observacion : ''), 0, 255),
                    'creado_por'       => $id_usuario ? (int) $id_usuario : null,
                ));
                $registrados[$pos] = true;
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

    /* ============================================================
     *  CONFIGURACIÓN DE LA REGLA POR CATEGORÍA (edición en pantalla)
     * ============================================================ */

    /**
     * Guarda la regla de premiación (y opcionalmente el día sugerido de
     * entrega) de una categoría en `categorias`.
     *
     * @param mixed $regla array [{puesto, premio, etiqueta?}, ...] o JSON ya
     *                     armado. NULL/vacío = volver a la regla default.
     * @param mixed $dia_sugerido 'YYYY-MM-DD' | '' (lo borra) | null (no toca).
     * @return array resumen guardado: regla normalizada + texto legible.
     */
    public function guardar_regla_categoria($id_categoria, $regla, $dia_sugerido = null) {
        if (!$this->_columna_existe('categorias', 'premiacion_regla')) {
            throw new Exception(
                'La tabla categorías todavía no tiene la columna "premiacion_regla". '
                . 'Ejecutá sql/migracion_premiacion_categoria.sql y recargá la página.'
            );
        }
        $id_cat = (int) $id_categoria;
        if (!$id_cat) throw new Exception('Categoría inválida.');

        $this->db->where('id_categoria', $id_cat);
        $fila = $this->_fila_segura('categorias');
        if (!$fila) throw new Exception('La categoría no existe.');

        // Normalizar la regla enviada (acepta array o JSON de string).
        $regla_norm = $this->parsear_regla_premiacion($regla);
        // parsear_regla_premiacion() devuelve la default si viene vacía: para
        // poder "limpiar" la regla se guarda NULL explícitamente abajo cuando
        // el input estaba vacío.
        $input_vacio = ($regla === null || $regla === '' || (is_array($regla) && count($regla) === 0));

        $update = array(
            'premiacion_regla' => $input_vacio ? null : json_encode($regla_norm, JSON_UNESCAPED_UNICODE),
        );

        if ($dia_sugerido !== null && $this->_columna_existe('categorias', 'dia_premiacion_sugerido')) {
            $norm = $this->_norm_fecha($dia_sugerido);
            if ((string) $dia_sugerido !== '' && !$norm) {
                throw new Exception('Fecha de premiación sugerida inválida.');
            }
            $update['dia_premiacion_sugerido'] = $norm ?: null;
        }

        $this->db->where('id_categoria', $id_cat);
        $this->db->update('categorias', $update);

        return array(
            'id_categoria'    => $id_cat,
            'regla'           => $regla_norm,
            'texto_regla'     => $this->texto_regla_premiacion($regla_norm),
            'regla_default'   => $input_vacio || $this->regla_es_default($regla_norm),
            'dia_sugerido'    => array_key_exists('dia_premiacion_sugerido', $update)
                ? $update['dia_premiacion_sugerido']
                : (isset($fila['dia_premiacion_sugerido']) ? $this->_norm_fecha($fila['dia_premiacion_sugerido']) : null),
        );
    }

    /* ============================================================
     *  REPORTE DEL LOCUTOR (guion de la ceremonia de premiación)
     * ============================================================ */

    /**
     * Arma el "guion" de la noche para el locutor de la cena: una lista
     * ordenada de las competencias a premiar con sus medallistas ya resueltos
     * (incluye las categorías cerradas pendientes y las ya entregadas ese día,
     * más los atrasos si se piden). Los nombres salen del mismo podio que ve
     * el organizador — en jornada única / largada masiva con el formato
     * "PROVINCIA (NOMBRE)" (ver _normalizar_nombres_provincia()).
     */
    public function obtener_guion_locutor($fecha, $incluir_atrasos = true) {
        $pedido = $this->_norm_fecha($fecha);
        if (!$pedido) return array('fecha' => null, 'bloques' => array(), 'total_medallas' => 0);

        $items = $this->obtener_resumen_noche($pedido);
        if ($incluir_atrasos) {
            // TRUE: el guion del locutor debe mostrar TODAS las entregas que
            // todavía no se anunciaron, incluso las de noches anteriores que
            // ya fueron confirmadas en pantalla (premiacion.php las lista
            // aparte como "Premiados", pero si no se leyeron en la cena, el
            // locutor tiene que tenerlas en su hoja).
            foreach ($this->obtener_atrasos_hasta($pedido, true) as $it) {
                $items[] = array_merge($it, array('atraso' => true));
            }
        }

        $bloques = array();
        $total_medallas = 0;
        foreach ($items as $it) {
            $podio = $it['podio'];
            $entregados = array();
            foreach ($it['entregadas'] as $e) {
                $entregados[(int) $e['puesto']] = $e;
            }
            // Puestos a anunciar: los que define la REGLA DE PREMIACIÓN de la
            // categoría (1º, 1º/2º, 1º/2º/3º, etc.), no siempre el podio clásico.
            $puestos = array();
            $regla_guion = $this->regla_de_categoria($it['categoria']);
            foreach ($this->puestos_de_regla($regla_guion) as $pos) {
                $p = isset($podio[$pos]) ? $podio[$pos] : null;
                $reg = isset($entregados[$pos]) ? $entregados[$pos] : null;
                $nombre = null;
                if ($reg && trim((string) $reg['nombre']) !== '') {
                    $nombre = trim((string) $reg['nombre']);
                } elseif ($p && trim((string) $p['nombre']) !== '') {
                    $nombre = trim((string) $p['nombre']);
                }
                if ($nombre === null || $nombre === '') continue;
                // Puesto "perdonado" (no hubo participante: se presentó de más):
                // no va al guion del locutor, solo queda como registro interno.
                if (stripos($nombre, '(sin premiar)') !== false) continue;
                $premio_txt = null;
                foreach ($regla_guion as $rgl) {
                    if ((int) $rgl['puesto'] === $pos) {
                        $premio_txt = $rgl['premio'];
                        if (!empty($rgl['etiqueta'])) $premio_txt .= ' (' . $rgl['etiqueta'] . ')';
                    }
                }
                $puestos[$pos] = array(
                    'nombre'    => $nombre,
                    // id UTE del premiado: primero el podio resuelto; si ya
                    // está entregado, el registrado en la premiación.
                    'id'        => ($p && !empty($p['id'])) ? (int) $p['id']
                              : (($reg && !empty($reg['id_ute'])) ? (int) $reg['id_ute'] : null),
                    'premio'    => $premio_txt,
                    'desempate' => ($p && !empty($p['desempate'])) ? $p['desempate'] : null,
                    'extra'     => ($p && !empty($p['extra'])) ? $p['extra'] : null,
                );
                $total_medallas++;
            }
            if (!$puestos) continue; // sin medallistas resueltos: no va al guion

            // Integrantes de los equipos premiados: en deportes de EQUIPO el
            // locutor necesita saber QUIÉNES suben al escenario — se listan
            // separados por " - " (ver rep_locutor_view). Se resuelven con una
            // sola consulta para todos los id_ute del bloque.
            $ids_ute_bloque = array();
            foreach ($puestos as $pp) {
                if (!empty($pp['id'])) $ids_ute_bloque[] = (int) $pp['id'];
            }

            $c = $it['categoria'];
            $bloques[] = array(
                'id_categoria'  => (int) $c['id_categoria'],
                'deporte'       => trim((string) $c['nombre_deporte']),
                'categoria'     => trim((string) $c['nombre_categoria']),
                'genero'        => trim((string) ($c['genero'] ?? '')),
                'modalidad'     => trim((string) ($c['modalidad_competencia'] ?? '')),
                'es_equipo'     => $this->_deporte_es_de_equipos($c),
                'integrantes'   => $this->_integrantes_por_ute($ids_ute_bloque),
                'masiva'        => ($c['modalidad_competencia'] === 'MASIVO_TIEMPO'),
                'origen'        => $it['origen'],
                'fecha_entrega' => $it['fecha_entrega'],
                'atraso'        => !empty($it['atraso']),
                'entregada'     => !empty($entregados) && count(array_intersect_key($entregados, $puestos)) >= count($puestos),
                'puestos'       => $puestos,
                // Nota para el locutor: en jornadas únicas / largada masiva los
                // nombres salen como "PROVINCIA (NOMBRE)": se anuncia la
                // PROVINCIA y el nombre queda como referencia escrita.
                'anunciar'      => ($it['origen'] === 'TIEMPO' || $c['modalidad_competencia'] === 'MASIVO_TIEMPO')
                    ? 'provincia' : 'nombre',
            );
        }

        // Orden del guion: deporte -> categoría -> género (para leerlo seguido).
        usort($bloques, function ($a, $b) {
            return [strtolower($a['deporte']), strtolower($a['categoria']), strtolower($a['genero'])]
                 <=> [strtolower($b['deporte']), strtolower($b['categoria']), strtolower($b['genero'])];
        });

        return array(
            'fecha'         => $pedido,
            'bloques'       => $bloques,
            'total_medallas' => $total_medallas,
        );
    }

    /**
     * ¿La categoría compite por EQUIPOS? Se usa en el guion del locutor para
     * decidir si conviene listar los integrantes de cada equipo premiado.
     * Criterios (en orden):
     *  - deportes.modalidad = 'EQUIPO' -> equipos.
     *  - deportes.modalidad = 'INDIVIDUAL' y la prueba es masiva por tiempo
     *    (Running, Ciclismo...) -> individual.
     *  - ENFRENTAMIENTO con UTEs en el fixture -> equipos (aunque el deporte
     *    esté cargado como 'AMBAS': dobles de pádel, mixtos...).
     */
    private function _deporte_es_de_equipos($c) {
        static $cache = array();
        $id_dep = (int) ($c['id_deporte'] ?? 0);
        if ($id_dep && isset($cache[$id_dep])) return $cache[$id_dep];

        $es_equipo = false;
        if ($id_dep) {
            $this->db->select('modalidad', FALSE);
            $this->db->where('id_deporte', $id_dep);
            $this->db->limit(1);
            $dep = $this->_fila_segura('deportes');
            $mod = strtoupper(trim((string) ($dep['modalidad'] ?? '')));
            if ($mod === 'EQUIPO') {
                $es_equipo = true;
            } elseif ($mod === 'INDIVIDUAL') {
                $es_equipo = (($c['modalidad_competencia'] ?? '') !== 'ENFRENTAMIENTO');
            }
        }
        if (!$es_equipo && ($c['modalidad_competencia'] ?? '') === 'ENFRENTAMIENTO') {
            // Torneo de partidos con equipos registrados como UTE.
            $id_cat = (int) ($c['id_categoria'] ?? 0);
            if ($id_cat && $this->_tabla_existe('fixtures')) {
                $this->db->where('id_categoria', $id_cat);
                $this->db->group_start();
                $this->db->where('id_ute_1 IS NOT NULL');
                $this->db->or_where('id_ute_2 IS NOT NULL');
                $this->db->group_end();
                $this->db->limit(1);
                $es_equipo = $this->_fila_segura('fixtures') ? true : false;
            }
        }

        if ($id_dep) $cache[$id_dep] = $es_equipo;
        return $es_equipo;
    }

    /**
     * Integrantes (nombres completos) de varias UTEs/equipos en un solo query:
     * devuelve id_ute => array('JUAN PEREZ', 'MARIA GOMEZ', ...) alfabético.
     */
    private function _integrantes_por_ute($ids_ute) {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids_ute))));
        if (!$ids || !$this->_tabla_existe('participantes_utes')) return array();

        $this->db->select('pu.id_ute, p.nombre_completo', FALSE);
        $this->db->from('participantes_utes pu');
        $this->db->join('participantes p', 'p.id_participante = pu.id_participante', 'inner');
        $this->db->where_in('pu.id_ute', $ids);
        $this->db->order_by('pu.id_ute, p.nombre_completo', 'ASC');
        $out = array();
        foreach ($this->_filas_seguras() as $f) {
            $nombre = trim((string) $f['nombre_completo']);
            if ($nombre === '') continue;
            $out[(int) $f['id_ute']][] = $nombre;
        }
        return $out;
    }
}

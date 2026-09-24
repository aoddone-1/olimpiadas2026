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
 *  - TIEMPO (MASIVO_TIEMPO): se premia a los puestos 1, 2 y 3 del resultado.
 *  - MARCADOR (ENFRENTAMIENTO): el podio es el del TORNEO COMPLETO, no del
 *    último partido:
 *      1º = ganador de la FINAL
 *      2º = perdedor de la FINAL
 *      3º = ganador del partido por el TERCER_PUESTO (si existe; si no, queda
 *           "por definir").
 * La entrega queda registrada en la tabla `premiaciones` (ver sql/premiaciones.sql).
 */
class Premiacion_model extends CI_Model {

    /** ¿Existe la tabla de premiaciones? (aviso amigable si falta el SQL). */
    public function tablas_existentes() {
        return $this->_tabla_existe('premiaciones');
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

    /** Podio de un resultado TIEMPO: posiciones 1, 2 y 3 del detalle. */
    private function _podio_desde_tiempo($resultado) {
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
                'extra'  => $d['tiempo'] ? 'Tiempo: ' . $d['tiempo'] : null,
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
                if ($m['eqA']['tantos'] !== $m['eqB']['tantos']) {
                    $ganador  = $m['eqA']['tantos'] > $m['eqB']['tantos'] ? $m['eqA'] : $m['eqB'];
                    $perdedor = $ganador === $m['eqA'] ? $m['eqB'] : $m['eqA'];
                    $out[1] = array('id' => $ganador['id'], 'nombre' => $ganador['nombre'],
                                    'extra' => 'Final: ' . $this->_texto_marca($m),
                                    'id_resultado_ref' => (int) $final['id_resultado']);
                    $out[2] = array('id' => $perdedor['id'], 'nombre' => $perdedor['nombre'],
                                    'extra' => 'Final: ' . $this->_texto_marca($m),
                                    'id_resultado_ref' => (int) $final['id_resultado']);
                } else {
                    // Final en empate: no hay podio definido todavía.
                    return array();
                }
            }
        }

        $tp = $this->_resultado_por_fase($id_categoria, 'TERCER_PUESTO');
        if ($tp) {
            $m = $this->_parsear_marcador($tp['detalle']);
            if ($m && $m['eqA']['tantos'] !== $m['eqB']['tantos']) {
                $ganador = $m['eqA']['tantos'] > $m['eqB']['tantos'] ? $m['eqA'] : $m['eqB'];
                $out[3] = array('id' => $ganador['id'], 'nombre' => $ganador['nombre'],
                                'extra' => 'Tercer puesto: ' . $this->_texto_marca($m),
                                'id_resultado_ref' => (int) $tp['id_resultado']);
            }
        }

        return $out;
    }

    private function _texto_marca($m) {
        return $m['eqA']['nombre'] . ' ' . $m['eqA']['tantos'] . ' - ' . $m['eqB']['tantos'] . ' ' . $m['eqB']['nombre'];
    }

    /** Último resultado MARCADOR cargado para una fase de la categoría. */
    private function _resultado_por_fase($id_categoria, $fase) {
        $this->db->select('r.*, f.fase', FALSE);
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
        return count($row['detalle']) >= 2 ? $row : null;
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
        return $out;
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
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->order_by('d.nombre_deporte, c.nombre_categoria', 'ASC');
        return $this->_filas_seguras();
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
        $fecha_jugada = function ($r) use ($fx_por_id) {
            if (!empty($r['id_fixture']) && isset($fx_por_id[(int) $r['id_fixture']])) {
                $fc = $fx_por_id[(int) $r['id_fixture']]['fecha_competencia'];
                if (!empty($fc)) return substr((string) $fc, 0, 10);
            }
            return null;
        };

        $items = array();
        foreach ($categorias as $cat) {
            $id_cat = (int) $cat['id_categoria'];
            $resultados = isset($resultados_por_cat[$id_cat]) ? $resultados_por_cat[$id_cat] : array();
            $fixtures = isset($fixtures_por_cat[$id_cat]) ? $fixtures_por_cat[$id_cat] : array();
            if (!$resultados) continue; // sin resultados no hay nada que premiar

            $es_masiva = ($cat['modalidad_competencia'] === 'MASIVO_TIEMPO');
            $podio = array();
            $origen = $es_masiva ? 'TIEMPO' : 'MARCADOR';

            if ($es_masiva) {
                // Podio = el mejor resultado TIEMPO cargado (más posiciones).
                $mejor = null;
                foreach ($resultados as $r) {
                    if ($r['tipo_resultado'] !== 'TIEMPO') continue;
                    if (!$r['detalle']) continue;
                    if ($mejor === null || count($r['detalle']) > count($mejor['detalle'])) {
                        $mejor = $r;
                    }
                }
                if (!$mejor) continue;
                $podio_nombres = $this->_podio_desde_tiempo($mejor);
                foreach ($podio_nombres as $pos => $p) {
                    $podio[$pos] = array_merge($p, array('id_resultado_ref' => (int) $mejor['id_resultado']));
                }
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
                // Jornada única: cierra cuando hay resultado cargado.
                $cerrada = true;
                $motivo = 'Jornada cerrada con resultados cargados';
                $fecha_entrega = $fecha_jugada($mejor);
                if (!$fecha_entrega && !empty($cat['dia_competencia'])) {
                    $fecha_entrega = substr((string) $cat['dia_competencia'], 0, 10);
                }
                if (!$fecha_entrega && $mejor['fecha_resultado']) {
                    $fecha_entrega = substr((string) $mejor['fecha_resultado'], 0, 10);
                }
            } elseif ($cat['tipo_duracion'] === 'UNICO_DIA') {
                $cerrada = isset($podio[1]);
                $motivo = 'Deporte de un solo día: se premia esa misma noche';
                // Día de la final; si no está en el fixture, el día programado
                // de la categoría.
                $final_fx = null;
                foreach ($fixtures as $f) {
                    if ($f['fase'] === 'FINAL' && !empty($f['fecha_competencia'])) { $final_fx = $f; break; }
                }
                $fecha_entrega = $final_fx ? substr((string) $final_fx['fecha_competencia'], 0, 10) : null;
                if (!$fecha_entrega) {
                    $fecha_entrega = $fecha_jugada(array('id_fixture' => $podio[1]['id_resultado_ref'] ?? null));
                }
                if (!$fecha_entrega && !empty($cat['dia_competencia'])) {
                    $fecha_entrega = substr((string) $cat['dia_competencia'], 0, 10);
                }
            } else {
                // MULTIDIA: cierra el día de la definición (fecha en que se
                // jugó la FINAL según el fixture).
                $final_fx = null;
                foreach ($fixtures as $f) {
                    if ($f['fase'] === 'FINAL') { $final_fx = $f; break; }
                }
                $fecha_def = null;
                if (!empty($final_fx['fecha_competencia'])) {
                    $fecha_def = substr((string) $final_fx['fecha_competencia'], 0, 10);
                }
                if (!$fecha_def) {
                    $fecha_def = $fecha_jugada(array('id_fixture' => $podio[1]['id_resultado_ref'] ?? null));
                }
                $cerrada = isset($podio[1]) && !empty($fecha_def);
                $motivo = 'Multidía: cierra el día de la definición';
                $fecha_entrega = $fecha_def;
            }

            if ($cerrada && !$fecha_entrega) {
                // Sin fecha de competencia conocida no se puede asignar noche.
                $cerrada = false;
                $motivo = 'Sin fecha de competencia en el fixture';
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
            );
        }

        return $items;
    }

    /**
     * Resumen para una noche (fecha): qué se premió/compitió ESE día.
     * Filtro estricto por fecha de competencia: solo categorías cerradas cuya
     * noche de entrega es exactamente la fecha pedida.
     */
    public function obtener_resumen_noche($fecha) {
        $items = $this->obtener_estado_premiables();
        $out = array();
        foreach ($items as $it) {
            if (!$it['cerrada']) continue;
            if ($it['fecha_entrega'] !== $fecha) continue; // solo el día que se compitió
            $out[] = $it;
        }
        return $out;
    }

    /**
     * Categorías de noches ANTERIORES a la fecha pedida que todavía no fueron
     * premiadas completas (para que ninguna entrega quede en el olvido).
     */
    public function obtener_atrasos_hasta($fecha) {
        $items = $this->obtener_estado_premiables();
        $out = array();
        foreach ($items as $it) {
            if (!$it['cerrada']) continue;
            if ($it['fecha_entrega'] >= $fecha) continue; // no es anterior
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

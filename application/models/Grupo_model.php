<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Grupo_model
 * ------------------------------------------------------------------
 * Fase de grupos formato "Mundial" (reemplazo del generador automático):
 *
 *   1. crear_grupos(): reparte las UTEs inscriptas en la categoría en
 *      grupos A, B, C... balanceados (nunca queda un grupo de 1).
 *      Soporta cantidades IMPARES: ej. 7 equipos con tamaño deseado 4
 *      -> grupos de 4 y 3; 14 con 4 -> 4+4+3+3.
 *   2. generar_partidos_grupo(): todos contra todos dentro de cada grupo
 *      (round-robin simple). Con cantidad impar de equipos por grupo,
 *      una fecha queda con "libre" automáticamente.
 *   3. recalcular_posiciones(): tabla de posiciones por grupo
 *      (Pts = 3*G + 1*E) ordenada por Pts > DG > GF.
 *   4. clasificar_grupos(): marca pos_grupo y clasificado=1 a los N
 *      primeros de cada grupo (config categoria.clasificados_por_grupo).
 *   5. armar_bracket(): crea OCTAVOS/CUARTOS/SEMIFINAL/FINAL/TERCER_PUESTO
 *      con slots simbólicos ("A-1" = 1° del Grupo A) que se van llenando
 *      cuando se cargan los resultados.
 *
 * Requiere las tablas del script sql/migracion_mundial.sql
 * (grupos, grupo_utes, posiciones_grupo + columnas id_grupo/llave/origen_*).
 */
class Grupo_model extends CI_Model {

    /** Letras de grupo estilo Mundial. */
    const LETRAS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** Fases eliminatorias en orden, desde octavos hacia la final. */
    const FASES_ELIM = array('OCTAVOS', 'CUARTOS', 'SEMIFINAL', 'FINAL');

    /* ============================================================
     *  CONSULTAS
     * ============================================================ */

    /** Grupos de una categoría con sus miembros. */
    public function obtener_grupos($id_categoria) {
        $this->db->select('g.*, c.nombre_categoria, c.equipos_por_grupo, c.clasificados_por_grupo');
        $this->db->from('grupos g');
        $this->db->join('categorias c', 'c.id_categoria = g.id_categoria', 'left');
        $this->db->where('g.id_categoria', (int) $id_categoria);
        $this->db->order_by('g.orden_grupo', 'ASC');
        $grupos = $this->db->get()->result_array();
        if (!$grupos) return array();

        foreach ($grupos as &$g) {
            $g['miembros'] = $this->_miembros_del_grupo((int) $g['id_grupo']);
        }
        unset($g);
        return $grupos;
    }

    private function _miembros_del_grupo($id_grupo) {
        $this->db->select('gu.id_ute, u.nombre_ute, u.delegacion, p.pos_grupo, p.clasificado,
                           p.pj, p.pg, p.pe, p.pp, p.gf, p.gc, p.dg, p.pts', FALSE);
        $this->db->from('grupo_utes gu');
        $this->db->join('utes u', 'u.id_ute = gu.id_ute', 'inner');
        $this->db->join('posiciones_grupo p', 'p.id_grupo = gu.id_grupo AND p.id_ute = gu.id_ute', 'left');
        $this->db->where('gu.id_grupo', $id_grupo);
        $this->db->order_by('p.pos_grupo IS NULL, p.pos_grupo ASC, p.dg DESC, p.pts DESC, u.nombre_ute ASC');
        return $this->db->get()->result_array();
    }

    /** Posiciones de todos los grupos de una categoría (para la tabla general). */
    public function posiciones_de_categoria($id_categoria) {
        $out = array();
        foreach ($this->obtener_grupos($id_categoria) as $g) {
            $out[] = array('grupo' => $g, 'filas' => $g['miembros']);
        }
        return $out;
    }

    /* ============================================================
     *  PASO 1: CREAR LOS GRUPOS (sorteo balanceado)
     * ============================================================ */

    /**
     * Reparte las UTEs de la categoría en grupos balanceados.
     * Es destructiva: borra los grupos/partidos de fase GRUPO previos.
     *
     * @param int $id_categoria
     * @param int $tam desiredo   tamaño deseado de grupo (3, 4 o 5)
     * @return array resumen: cant_grupos, distribucion, letras
     */
    public function crear_grupos($id_categoria, $tamanio = 4) {
        $id_categoria = (int) $id_categoria;
        $tamanio = max(2, min(6, (int) $tamanio));

        $utes = $this->_utes_inscriptos($id_categoria);
        $n = count($utes);
        if ($n < 2) {
            throw new Exception('La categoría necesita al menos 2 equipos inscriptos para armar grupos (tiene ' . $n . ').');
        }

        // Distribución balanceada: nunca deja un grupo de 1.
        //  n=7, tam=4 -> [4,3] ; n=14, tam=4 -> [4,4,3,3] ; n=5, tam=4 -> [3,2]
        $cant = max(2, (int) ceil($n / $tamanio));
        if ($cant > strlen(self::LETRAS)) {
            throw new Exception('Demasiados equipos para el alfabeto de grupos.');
        }
        $base   = intdiv($n, $cant);
        $sobran = $n % $cant;              // $sobran grupos quedan con $base+1
        if ($base < 2 && $n >= 4) {        // ej. 5 equipos con tam=2 -> mejor 3+2
            $cant = (int) ceil($n / 3);
            $base = intdiv($n, $cant);
            $sobran = $n % $cant;
        }
        if ($base < 2) {
            throw new Exception('Con ' . $n . ' equipos no se pueden armar grupos de al menos 2 integrantes.');
        }

        $this->db->trans_start();

        // Idempotencia: limpiar grupos anteriores y sus partidos de fase GRUPO.
        $this->_borrar_grupos_de_categoria($id_categoria);

        $letras = str_split(substr(self::LETRAS, 0, $cant));

        // Sorteo "serpiente": equipos con nombres parecidos (misma delegación)
        // tienden a quedar separados entre grupos.
        usort($utes, function ($a, $b) {
            return strcasecmp($a['nombre_ute'], $b['nombre_ute']);
        });
        $buckets = array_fill(0, $cant, array());
        $sizes   = array();
        for ($i = 0; $i < $cant; $i++) $sizes[$i] = $base + ($i < $sobran ? 1 : 0);
        $dir = 1; $i = 0;
        foreach ($utes as $ute) {
            while (count($buckets[$i]) >= $sizes[$i]) {
                $i += $dir;
                if ($i < 0 || $i >= $cant) { $dir = -$dir; $i += $dir; }
            }
            $buckets[$i][] = $ute;
        }

        $creados = array();
        foreach ($letras as $gi => $letra) {
            $this->db->insert('grupos', array(
                'id_categoria' => $id_categoria,
                'nombre_grupo' => $letra,
                'orden_grupo'  => $gi + 1,
            ));
            $id_grupo = $this->db->insert_id();
            foreach ($buckets[$gi] as $ute) {
                $this->db->insert('grupo_utes', array(
                    'id_grupo' => $id_grupo,
                    'id_ute'   => (int) $ute['id_ute'],
                ));
                $this->db->insert('posiciones_grupo', array(
                    'id_grupo' => $id_grupo,
                    'id_ute'   => (int) $ute['id_ute'],
                ));
            }
            $creados[] = array('id_grupo' => $id_grupo, 'letra' => $letra, 'equipos' => count($buckets[$gi]));
        }

        // Persistir el tamaño usado en la categoría (si la columna existe).
        if ($this->_columna_existe('categorias', 'equipos_por_grupo')) {
            $this->db->where('id_categoria', $id_categoria);
            $this->db->update('categorias', array('equipos_por_grupo' => $tamanio));
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Error de base de datos al crear los grupos.');
        }

        return array(
            'cant_grupos'  => $cant,
            'distribucion' => array_map(function ($g) { return $g['letra'] . ': ' . $g['equipos']; }, $creados),
            'letras'       => $letras,
        );
    }

    /** UTEs efectivamente inscriptas en la categoría (mismo criterio que el fixture manual). */
    private function _utes_inscriptos($id_categoria) {
        $this->db->distinct();
        $this->db->select('u.id_ute, u.nombre_ute', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('utes u', 'u.id_ute = i.id_ute', 'inner');
        $this->db->where('i.id_categoria', (int) $id_categoria);
        $this->db->where('i.id_ute >', 0);
        $this->db->order_by('u.nombre_ute', 'ASC');
        return $this->db->get()->result_array();
    }

    private function _borrar_grupos_de_categoria($id_categoria) {
        // FK ON DELETE CASCADE limpia grupo_utes y posiciones_grupo.
        $this->db->where('id_categoria', (int) $id_categoria);
        $this->db->delete('grupos');
        // Partidos de fase GRUPO huérfanos (id_grupo ya no existe tras el cascade).
        $this->db->where('id_categoria', (int) $id_categoria);
        $this->db->where('fase', 'GRUPO');
        $this->db->delete('fixtures');
    }

    /* ============================================================
     *  PASO 2: PARTIDOS TODOS CONTRA TODOS DENTRO DE CADA GRUPO
     * ============================================================ */

    /**
     * Genera los cruces de la fase de grupos (round-robin simple).
     * Los partidos quedan PROGRAMADOS con fecha/hora de la categoría
     * (se ajustan a mano desde el panel si hace falta).
     *
     * @return int cantidad de partidos creados
     */
    public function generar_partidos_grupo($id_categoria) {
        $id_categoria = (int) $id_categoria;
        $grupos = $this->obtener_grupos($id_categoria);
        if (!$grupos) {
            throw new Exception('Primero creá los grupos con el botón "Crear grupos".');
        }

        $cat = $this->_datos_categoria($id_categoria);
        if (!$cat) throw new Exception('La categoría no existe.');

        // Evitar duplicados: borrar los partidos GRUPO existentes de la categoría.
        $this->db->where('id_categoria', $id_categoria);
        $this->db->where('fase', 'GRUPO');
        $this->db->delete('fixtures');

        $fecha = !empty($cat['dia_competencia']) ? $cat['dia_competencia'] : date('Y-m-d');
        $h_ini = !empty($cat['hora_competencia']) ? substr($cat['hora_competencia'], 0, 5) : '09:00';
        $h_fin = $this->_sumar_horas($h_ini, 1);

        $creados = 0;
        $this->db->trans_start();
        foreach ($grupos as $g) {
            $ids = array_map('intval', array_column($g['miembros'], 'id_ute'));
            $nombr = array();
            foreach ($g['miembros'] as $m) $nombr[(int) $m['id_ute']] = $m['nombre_ute'];
            $n = count($ids);
            $jornada = 0;
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $jornada++;
                    $this->db->insert('fixtures', array(
                        'id_categoria'      => $id_categoria,
                        'id_lugar'          => !empty($cat['id_lugar']) ? (int) $cat['id_lugar'] : $this->_primer_lugar(),
                        'id_ute_1'          => $ids[$i],
                        'id_ute_2'          => $ids[$j],
                        'nombre_prueba'     => 'Grupo ' . $g['nombre_grupo'] . ' — ' . $nombr[$ids[$i]] . ' vs ' . $nombr[$ids[$j]],
                        'fase'              => 'GRUPO',
                        'numero_fecha'      => $jornada,
                        'fecha_competencia' => $fecha,
                        'hora_inicio'       => $h_ini,
                        'hora_fin'          => $h_fin,
                        'estado'            => 'PROGRAMADO',
                    ) + ($this->_columna_existe('fixtures', 'id_grupo')
                            ? array('id_grupo' => (int) $g['id_grupo']) : array()));
                    $creados++;
                }
            }
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Error al generar los partidos de la fase de grupos.');
        }
        return $creados;
    }

    /* ============================================================
     *  PASO 3: TABLA DE POSICIONES (3-1-0)
     * ============================================================ */

    /** Recalcula posiciones_grupo desde los partidos GRUPO FINALIZADOS. */
    public function recalcular_posiciones($id_grupo) {
        $id_grupo = (int) $id_grupo;

        $stats = array();   // id_ute => pj/pg/pe/pp/gf/gc
        foreach ($this->_miembros_del_grupo($id_grupo) as $m) {
            $stats[(int) $m['id_ute']] = array('pj'=>0,'pg'=>0,'pe'=>0,'pp'=>0,'gf'=>0,'gc'=>0);
        }
        if (!$stats) return array();

        $partidos = $this->db
            ->where('id_grupo', $id_grupo)
            ->where('fase', 'GRUPO')
            ->where('estado', 'FINALIZADO')
            ->get('fixtures')->result_array();

        foreach ($partidos as $p) {
            $r = $this->_parsearResultado($p);
            if ($r === null) continue;   // sin marcador registrado
            list($g1, $g2) = $r;
            $e1 = (int) $p['id_ute_1'];
            $e2 = (int) $p['id_ute_2'];
            if (!isset($stats[$e1]) || !isset($stats[$e2])) continue;

            $stats[$e1]['pj']++; $stats[$e2]['pj']++;
            $stats[$e1]['gf'] += $g1; $stats[$e1]['gc'] += $g2;
            $stats[$e2]['gf'] += $g2; $stats[$e2]['gc'] += $g1;
            if ($g1 > $g2)      { $stats[$e1]['pg']++; $stats[$e2]['pp']++; }
            elseif ($g1 < $g2)  { $stats[$e2]['pg']++; $stats[$e1]['pp']++; }
            else                { $stats[$e1]['pe']++; $stats[$e2]['pe']++; }
        }

        $this->db->trans_start();
        foreach ($stats as $id_ute => $s) {
            $this->db->where('id_grupo', $id_grupo);
            $this->db->where('id_ute', $id_ute);
            $this->db->update('posiciones_grupo', $s);   // pts y dg son GENERATED
        }
        $this->db->trans_complete();

        return $this->_miembros_del_grupo($id_grupo);
    }

    /** Extrae el marcador [goles1, goles2] de fixtures.resultado (json o "2-1"). */
    private function _parsearResultado($partido) {
        if (empty($partido['resultado'])) return null;
        $raw = $partido['resultado'];
        $dec = json_decode($raw, true);
        if (is_array($dec) && count($dec) >= 2
            && is_numeric($dec[0]) && is_numeric($dec[1]) && $dec[0] >= 0 && $dec[1] >= 0) {
            return array((int) $dec[0], (int) $dec[1]);
        }
        if (preg_match('/^\s*(\d+)\s*[-:]\s*(\d+)\s*$/', $raw, $m)) {
            return array((int) $m[1], (int) $m[2]);
        }
        return null;
    }

    /* ============================================================
     *  PASO 4: CERRAR GRUPOS Y DEFINIR CLASIFICADOS
     ============================================================ */

    /**
     * Setea pos_grupo (1..N) y clasificado=1 a los primeros de cada grupo.
     * Desempate simple: Pts > DG > GF > sorteo (orden alfabético estable).
     * TODO: enfrentar "mejores segundos" (categoria.mejores_segundos) si hace falta.
     */
    public function clasificar_grupos($id_categoria) {
        $id_categoria = (int) $id_categoria;
        $grupos = $this->obtener_grupos($id_categoria);
        if (!$grupos) throw new Exception('Esta categoría no tiene grupos creados.');

        $cat = $this->_datos_categoria($id_categoria);
        $ava = max(1, (int) (isset($cat['clasificados_por_grupo']) ? $cat['clasificados_por_grupo'] : 2));

        $resumen = array();
        $this->db->trans_start();
        foreach ($grupos as $g) {
            $this->recalcular_posiciones((int) $g['id_grupo']);
            $filas = $this->_miembros_del_grupo((int) $g['id_grupo']);
            // _miembros_del_grupo ya ordena por pos/DG/pts; reordenar duro:
            usort($filas, function ($a, $b) {
                if ((int)$b['pts'] !== (int)$a['pts']) return (int)$b['pts'] - (int)$a['pts'];
                if ((int)$b['dg']   !== (int)$a['dg'])   return (int)$b['dg']   - (int)$a['dg'];
                if ((int)$b['gf']   !== (int)$a['gf'])   return (int)$b['gf']   - (int)$a['gf'];
                return strcasecmp($a['nombre_ute'], $b['nombre_ute']);
            });
            foreach ($filas as $i => $f) {
                $this->db->where('id_grupo', (int) $g['id_grupo']);
                $this->db->where('id_ute', (int) $f['id_ute']);
                $this->db->update('posiciones_grupo', array(
                    'pos_grupo'   => $i + 1,
                    'clasificado' => ($i < $ava) ? 1 : 0,
                ));
            }
            $resumen[] = array(
                'grupo'       => $g['nombre_grupo'],
                'clasificados'=> array_slice(array_map(function ($f) { return $f['nombre_ute']; }, $filas), 0, $ava),
            );
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Error al cerrar los grupos.');
        }
        return $resumen;
    }

    /* ============================================================
     *  PASO 5: ARMAR EL BRACKET ELIMINATORIO
     * ============================================================ */

    /**
     * Crear los cruces eliminatorios con slots simbólicos tipo Mundial.
     * La ronda base se empareja en espejo entre grupos vecinos:
     *   2 grupos (S1: A-1 vs B-2 | S2: B-1 vs A-2) -> Final -> 3er puesto
     *   4 grupos (Q1..Q4 espejados)               -> Semis  -> Final -> 3°
     * Con 3 grupos: el 1° del grupo C queda "bye" directo a semi.
     * Los ganadores reales se resuelven al cerrar los grupos y registrar
     * resultados (Fixture_model::_resolver_slots_grupo / _propagar_en_llaves).
     */
    public function armar_bracket($id_categoria) {
        $id_categoria = (int) $id_categoria;
        $grupos = $this->obtener_grupos($id_categoria);
        $ng = count($grupos);
        if ($ng < 2) throw new Exception('Hace falta al menos 2 grupos para armar cruces.');

        $letas = array_column($grupos, 'nombre_grupo');
        $ava = max(1, (int) (isset($grupos[0]['clasificados_por_grupo']) ? $grupos[0]['clasificados_por_grupo'] : 2));
        if ($ava < 2 && $ng < 4) {
            throw new Exception('Con clasificados_por_grupo = 1 y menos de 4 grupos el bracket necesita ajustes manuales (crear los cruces a mano).');
        }

        $cat = $this->_datos_categoria($id_categoria);
        $lugar = !empty($cat['id_lugar']) ? (int) $cat['id_lugar'] : $this->_primer_lugar();
        if (!$lugar) throw new Exception('No hay lugares cargados: creá al menos uno antes de armar los cruces.');
        $fecha = !empty($cat['dia_competencia']) ? $cat['dia_competencia'] : date('Y-m-d');
        $h_ini = !empty($cat['hora_competencia']) ? substr($cat['hora_competencia'], 0, 5) : '09:00';
        $h_fin = $this->_sumar_horas($h_ini, 1);

        // Limpiar bracket previo de la categoría (no toca la fase de grupos).
        $this->db->where('id_categoria', $id_categoria);
        $this->db->where_in('fase', array_merge(self::FASES_ELIM, array('TERCER_PUESTO')));
        $this->db->delete('fixtures');

        // Armar los cruces de la primera ronda eliminatoria según la cantidad
        // de grupos y clasificados. Se emparejan grupos VECINOS en espejo
        // (estilo Mundial): 1° del Grupo A vs 2° del Grupo B, y viceversa.
        $cruces = array();         // lista de ["A-1", "B-2"] (letra-posición)
        $byes = array();           // "letra-pos" que salta directo a la semi
        if ($ava >= 2) {
            if ($ng % 2 === 0) {
                for ($i = 0; $i < $ng; $i += 2) {
                    $a = $letas[$i]; $b = $letas[$i + 1];
                    $cruces[] = array("$a-1", "$b-2");
                    $cruces[] = array("$b-1", "$a-2");
                }
            } else {
                // 3 grupos: emparejar A/B y C recibe bye (el mejor 1°).
                $cruces[] = array("{$letas[0]}-1", "{$letas[1]}-2");
                $cruces[] = array("{$letas[1]}-1", "{$letas[0]}-2");
                $byes[] = "{$letas[2]}-1";
            }
        } else {
            // clasificados=1: 1° contra 1° de grupos vecinos
            for ($i = 0; $i + 1 < $ng; $i += 2) {
                $cruces[] = array("{$letas[$i]}-1", "{$letas[$i+1]}-1");
            }
            if ($ng % 2 === 1) $byes[] = "{$letas[$ng-1]}-1";
        }

        // Nombre de la ronda base según cuántos partidos queden:
        //   2 -> semifinales | 4 -> cuartos | 8 o más -> octavos.
        $nc = count($cruces);
        $fase_base = $nc <= 2 ? 'SEMIFINAL' : ($nc <= 4 ? 'CUARTOS' : 'OCTAVOS');
        $nombre_base = array('SEMIFINAL' => 'Semifinal', 'CUARTOS' => 'Cuartos de final',
                             'OCTAVOS' => 'Octavos de final');

        /* ---------- Ronda base: slots GRUPO_POS ("A-1" vs "B-2") ---------- */
        // Al cerrarse los grupos, esos huecos se resuelven solos con el
        // botón "Resolver pendientes" (o automáticamente al cargar un
        // resultado). Las rondas siguientes ya no referencian grupos:
        // referencian la LLAVE (id_fixture) del cruce anterior.
        $ids_ronda = array();
        $llave = 0;
        $this->db->trans_start();
        foreach ($cruces as $pair) {
            $llave++;
            $ids_ronda[] = $this->_insertar_cruce(
                $id_categoria, $lugar, $fecha, $h_ini, $h_fin, $llave, $fase_base,
                $nombre_base[$fase_base] . ' — Llave ' . $llave,
                $this->_origen_desde_pos($pair[0]), $this->_origen_desde_pos($pair[1]));
        }

        /* ---------- Semifinales / Final ---------- */
        // Si la ronda base NO fue semifinal, los ganadores de pares de esa
        // ronda arman las semifinales (los byes entran acá directamente).
        $ids_semis = array();
        if ($fase_base !== 'SEMIFINAL') {
            $slots_semi = array();
            for ($i = 0; $i < count($ids_ronda); $i += 2) {
                $slots_semi[] = array('LLAVE_GANADOR', (string) $ids_ronda[$i]);
                $sig = $i + 1;
                if ($sig < count($ids_ronda)) {
                    $slots_semi[] = array('LLAVE_GANADOR', (string) $ids_ronda[$sig]);
                } elseif ($byes) {
                    $slots_semi[] = $this->_origen_desde_pos(array_shift($byes));
                }
            }
            while ($byes) $slots_semi[] = $this->_origen_desde_pos(array_shift($byes));

            for ($i = 0; $i + 1 < count($slots_semi); $i += 2) {
                $llave++;
                $ids_semis[] = $this->_insertar_cruce(
                    $id_categoria, $lugar, $fecha, $h_ini, $h_fin, $llave, 'SEMIFINAL',
                    'Semifinal — Llave ' . $llave, $slots_semi[$i], $slots_semi[$i + 1]);
            }
            // Si sobra un slot semi (caso raro), lo mandamos directo a la final.
            $semis_directas = array();
            if (count($slots_semi) % 2 === 1) {
                $semis_directas[] = end($slots_semi);
            }

            // Final: ganadores de las semis (+ eventuales directos)
            $slots_final = array();
            foreach ($ids_semis as $idsemi) $slots_final[] = array('LLAVE_GANADOR', (string) $idsemi);
            foreach ($semis_directas as $s) $slots_final[] = $s;
        } else {
            // La ronda base ya eran las semifinales: la final juega contra sus ganadores.
            $ids_semis = $ids_ronda;
            $slots_final = array(
                array('LLAVE_GANADOR', (string) $ids_ronda[0]),
                array('LLAVE_GANADOR', (string) $ids_ronda[1]),
            );
        }

        $llave_final = $llave + 1;
        $this->_insertar_cruce(
            $id_categoria, $lugar, $fecha, $h_ini, $h_fin, $llave_final, 'FINAL',
            'FINAL — Llave ' . $llave_final, $slots_final[0], $slots_final[1]);

        // Tercer puesto: perdedores de las semifinales
        if (count($ids_semis) >= 2) {
            $llave_tp = $llave_final + 1;
            $this->_insertar_cruce(
                $id_categoria, $lugar, $fecha, $h_ini, $h_fin, $llave_tp, 'TERCER_PUESTO',
                'Por el 3er puesto — Llave ' . $llave_tp,
                array('LLAVE_PERDEDOR', (string) $ids_semis[0]),
                array('LLAVE_PERDEDOR', (string) $ids_semis[1]));
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            throw new Exception('Error al armar el bracket eliminatorio.');
        }

        return array(
            'base'     => count($cruces),
            'semis'    => count($ids_semis),
            'final'    => 1,
            'tercer'   => count($ids_semis) >= 2 ? 1 : 0,
            'mensaje'  => 'Bracket armado: los huecos se completan solos al cargar resultados.',
        );
    }

    /** Traduce "A-2" a origen GRUPO_POS. */
    private function _origen_desde_pos($pos) {
        if (preg_match('/^([A-Z])-(\d)$/', $pos, $m)) {
            return array('GRUPO_POS', $m[1] . '-' . $m[2]);
        }
        return array('UTE', $pos);
    }

    private function _insertar_cruce($id_categoria, $lugar, $fecha, $h_ini, $h_fin,
                                     $llave, $fase, $nombre, $origen1, $origen2) {
        $payload = array(
            'id_categoria'      => $id_categoria,
            'id_lugar'          => $lugar,
            'nombre_prueba'     => $nombre,
            'fase'              => $fase,
            'numero_fecha'      => $llave,
            'fecha_competencia' => $fecha,
            'hora_inicio'       => $h_ini,
            'hora_fin'          => $h_fin,
            'estado'            => 'PROGRAMADO',
        );
        if ($this->_columna_existe('fixtures', 'llave')) {
            $payload['llave']           = $llave;
            $payload['origen_1_tipo']   = $origen1[0];
            $payload['origen_1_valor']  = $origen1[1];
            $payload['origen_2_tipo']   = $origen2[0];
            $payload['origen_2_valor']  = $origen2[1];
        }
        $this->db->insert('fixtures', $payload);
        return (int) $this->db->insert_id();
    }

    /* ============================================================
     *  HELPERS
     * ============================================================ */

    private function _datos_categoria($id_categoria) {
        $cols = $this->_columnas('categorias');
        $sel = array('id_categoria', 'id_lugar', 'dia_competencia', 'hora_competencia');
        foreach (array('clasificados_por_grupo', 'equipos_por_grupo', 'mejores_segundos') as $extra) {
            if (in_array($extra, $cols, true)) $sel[] = $extra;
        }
        $this->db->select(implode(', ', $sel));
        $this->db->where('id_categoria', (int) $id_categoria);
        return $this->db->get('categorias')->row_array();
    }

    /** Cache por-request de SHOW COLUMNS por tabla. */
    private function _columnas($tabla) {
        static $cache = array();
        if (!isset($cache[$tabla])) {
            try {
                $q = $this->db->query('SHOW COLUMNS FROM `' . $tabla . '`');
                $cache[$tabla] = array_map(function ($r) { return isset($r['Field']) ? $r['Field'] : ''; },
                                           $q ? $q->result_array() : array());
            } catch (Throwable $e) {
                $cache[$tabla] = array();
            }
        }
        return $cache[$tabla];
    }

    private function _columna_existe($tabla, $col) {
        return in_array($col, $this->_columnas($tabla), true);
    }

    private function _primer_lugar() {
        $this->db->order_by('id', 'ASC');
        $this->db->limit(1);
        $l = $this->db->get('lugares')->row_array();
        return $l ? (int) $l['id'] : null;
    }

    private function _sumar_horas($hora, $horas) {
        return date('H:i', strtotime($hora . ' +' . $horas . ' hours'));
    }
}

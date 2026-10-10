<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Participante_model extends CI_Model {

    public function insertar_completo($data_persona, $deportes_seleccionados) {
        // 1. Iniciamos una transacción para asegurarnos de que se guarde todo o nada
        $this->db->trans_start();

        // 2. Insertamos la información básica de la persona
        $this->db->insert('participantes', $data_persona);
        
        $id_participante = $this->db->insert_id();

        // 3. Insertamos las disciplinas asignadas (si es competidor y tiene filas)
        if (!empty($deportes_seleccionados) && $id_participante) {
            foreach ($deportes_seleccionados as $disc) {
                
                // Estructura idéntica a las columnas reales de tu tabla intermedia
                $data_relacion = [
                    'id_participante' => $id_participante,
                    'id_categoria'    => $disc['id_categoria'], // Mapeo directo a categoría
                    'tiene_ute'       => $disc['tiene_ute'],
                    'necesita_ute'    => $disc['necesita_ute'],
                    'detalle_ute'     => $disc['detalle_ute']
                ];

                $this->db->insert('inscripciones_deportivas', $data_relacion);
            }
        }

        // 4. Completamos la transacción
        $this->db->trans_complete();

        // Si la transacción falló por cualquier motivo, devuelve FALSE
        return $this->db->trans_status();
    }

    public function actualizar_completo($id_participante, $datos_persona, $deportes_seleccionados) {
        // Dejamos que CodeIgniter maneje los errores de forma nativa para las transacciones
        $this->db->trans_start();

        // 1. Actualizamos los datos personales en la tabla 'participantes'
        $this->db->where('id_participante', $id_participante);
        $this->db->update('participantes', $datos_persona);

        // 2. Obtenemos las inscripciones deportivas actuales del participante
        $this->db->where('id_participante', $id_participante);
        $inscripciones_actuales = $this->db->get('inscripciones_deportivas')->result_array();
        
        // 3. Armamos un array con los IDs de las inscripciones que vienen en el formulario
        $ids_a_mantener = [];
        $nuevas_inscripciones = [];
        
        if (!empty($deportes_seleccionados)) {
            foreach ($deportes_seleccionados as $disc) {
                if (!empty($disc['id_categoria'])) {
                    // Si viene con id_inscripcion, es una inscripción existente
                    if (!empty($disc['id_inscripcion'])) {
                        // Marcamos esta inscripción como existente para mantenerla
                        $ids_a_mantener[] = $disc['id_inscripcion'];
                        
                        // Actualizamos los datos de UTE y deporte/categoria si cambiaron
                        $data_actualizacion = [
                            'id_categoria' => $disc['id_categoria'],
                            'tiene_ute'    => $disc['tiene_ute'],
                            'necesita_ute' => $disc['necesita_ute'],
                            'detalle_ute'  => $disc['detalle_ute']
                        ];
                        $this->db->where('id_inscripcion', $disc['id_inscripcion']);
                        $this->db->update('inscripciones_deportivas', $data_actualizacion);
                    } else {
                        // Es una nueva inscripción (no tiene id_inscripcion)
                        $nuevas_inscripciones[] = $disc;
                    }
                }
            }
        }
        
        // 4. Eliminamos solo las inscripciones que NO están en el formulario
        if (!empty($inscripciones_actuales)) {
            foreach ($inscripciones_actuales as $inscripcion) {
                if (!in_array($inscripcion['id_inscripcion'], $ids_a_mantener)) {
                    $this->db->where('id_inscripcion', $inscripcion['id_inscripcion']);
                    $this->db->delete('inscripciones_deportivas');
                }
            }
        }

        // 5. Insertamos las nuevas inscripciones deportivas con su estructura de UTE correspondiente
        if (!empty($nuevas_inscripciones) && $id_participante) {
            foreach ($nuevas_inscripciones as $disc) {
                
                $data_relacion = [
                    'id_participante' => $id_participante,
                    'id_categoria'    => $disc['id_categoria'],
                    'tiene_ute'       => $disc['tiene_ute'],
                    'necesita_ute'    => $disc['necesita_ute'],
                    'detalle_ute'     => $disc['detalle_ute']
                ];

                $this->db->insert('inscripciones_deportivas', $data_relacion);
            }
        }

        // 6. Completamos la transacción atómica
        $this->db->trans_complete();
        
        // Retorna TRUE si se ejecutó el update y los inserts sin errores, o FALSE si falló algo
        return $this->db->trans_status();
    }

    

    public function obtener_por_token($token) {
        $this->db->where('token_qr', $token);
        $query = $this->db->get('participantes');
        return $query->row_array(); // Nos devuelve los datos del participante en un array
    }

    public function obtener_deportes_inscriptos($id_participante) {
        $this->db->select('
            inscripciones_deportivas.id_inscripcion,
            inscripciones_deportivas.id_ute,
            categorias.id_categoria,
            deportes.nombre_deporte,
            categorias.nombre_categoria,
            categorias.dia_competencia,
            categorias.hora_competencia,
            lugares.nombre as nombre_lugar,
            lugares.direccion as direccion_lugar,
            inscripciones_deportivas.asistio,
            inscripciones_deportivas.tiene_ute,
            inscripciones_deportivas.necesita_ute,
            inscripciones_deportivas.detalle_ute
        ');
        $this->db->from('inscripciones_deportivas');
        $this->db->join('categorias', 'inscripciones_deportivas.id_categoria = categorias.id_categoria');
        $this->db->join('deportes', 'categorias.id_deporte = deportes.id_deporte');
        $this->db->join('lugares', 'categorias.id_lugar = lugares.id', 'left');
        $this->db->where('inscripciones_deportivas.id_participante', $id_participante);

        $deportes = $this->db->get()->result_array();

        // FIXTURE: si la categoría tiene partidos/jornadas cargados, el día y
        // la hora que ve el competidor en su pase son los del fixture (no los
        // genéricos de la categoría). Se toman las próximas jornadas propias;
        // las ya jugadas se muestran aparte como resultados.
        foreach ($deportes as &$dep) {
            $id_cat_dep = (int) $dep['id_categoria'];
            $detalle_dep = trim((string) (isset($dep['detalle_ute']) ? $dep['detalle_ute'] : ''));

            // UTE propia: preferimos el id_ute de la inscripción; si no está
            // cargado (inscripciones viejas), resolvemos por nombre del equipo
            // dentro de la misma categoría o por participantes_utes del inscripto.
            $id_ute_dep = isset($dep['id_ute']) ? (int) $dep['id_ute'] : 0;
            if ($id_ute_dep <= 0 && $detalle_dep !== '') {
                $this->db->select('id_ute');
                $this->db->where('id_categoria', $id_cat_dep);
                $this->db->where('TRIM(UPPER(nombre_ute))', strtoupper($detalle_dep));
                $this->db->limit(1);
                $u = $this->db->get('utes')->row_array();
                if ($u) $id_ute_dep = (int) $u['id_ute'];
            }
            if ($id_ute_dep <= 0) {
                $this->db->select('pu.id_ute');
                $this->db->from('participantes_utes pu');
                $this->db->join('utes u', 'u.id_ute = pu.id_ute', 'inner');
                $this->db->where('pu.id_participante', (int) $id_participante);
                $this->db->where('u.id_categoria', $id_cat_dep);
                $this->db->limit(1);
                $u = $this->db->get()->row_array();
                if ($u) $id_ute_dep = (int) $u['id_ute'];
            }

            $dep['fixture_proximos'] = $this->_proximos_fixtures_del_participante(
                $id_cat_dep, (int) $id_participante, $id_ute_dep, $detalle_dep);

            if (!empty($dep['fixture_proximos'])) {
                $primero = $dep['fixture_proximos'][0];
                $dep['dia_competencia']  = $primero['fecha_competencia'];
                $dep['hora_competencia'] = $primero['hora_inicio'];
                if (!empty($primero['lugar_nombre'])) {
                    $dep['nombre_lugar'] = $primero['lugar_nombre'];
                }
            }

            // Resultados YA cargados donde participa (por jornada o por equipo).
            $dep['resultados'] = $this->_resultados_del_participante(
                (int) $dep['id_categoria'], (int) $id_participante, $id_ute_dep, $detalle_dep);
        }
        unset($dep);

        return $deportes;
    }

    /**
     * Próximas jornadas/partidos del fixture en los que participa el
     * competidor dentro de una categoría. Trae los fixtures de la categoría
     * y filtra en PHP con reglas simples:
     *   - deporte masivo / JORNADA_UNICA: compite todo inscripto
     *   - slot negativo = -id_inscripcion (individual)
     *   - slot positivo = id_ute del equipo propio
     *   - UTE resuelta por nombre (inscripciones con detalle_ute sin id_ute)
     *   - slot pendiente (NULL) en fase eliminatoria: todavía puede ser él
     */
    private function _proximos_fixtures_del_participante($id_categoria, $id_participante, $id_ute, $detalle_ute) {
        if (!$id_categoria || !$this->_tabla_existe_silenciosa('fixtures')) return array();

        $this->db->select('
            f.id_fixture, f.fase, f.numero_fecha, f.nombre_prueba,
            f.fecha_competencia, f.hora_inicio, f.hora_fin, f.estado,
            f.id_ute_1, f.id_ute_2, l.nombre AS lugar_nombre,
            d.modalidad_competencia
        ', FALSE);
        $this->db->from('fixtures f');
        $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->where('f.id_categoria', $id_categoria);
        $this->db->order_by('f.fecha_competencia', 'ASC');
        $this->db->order_by('f.hora_inicio', 'ASC');
        $rows = $this->db->get()->result_array();
        if (!$rows) return array();

        $ahora = time();
        $out = array();
        foreach ($rows as $f) {
            // Solo partidos que aún no terminaron y cuya fecha no pasó claramente.
            if (!in_array($f['estado'], array('PROGRAMADO', 'EN_CURSO'), true)) continue;
            $ts = strtotime($f['fecha_competencia'] . ' ' . $f['hora_inicio']);
            if ($ts !== false && $ts < $ahora - 6 * 3600) continue;

            $masivo = ($f['fase'] === 'JORNADA_UNICA' || $f['modalidad_competencia'] === 'MASIVO_TIEMPO');
            if (!$this->_fixture_es_del_participante($f, $id_participante, $id_ute, $detalle_ute, $masivo)) {
                continue;
            }

            $f['es_masivo'] = $masivo;
            $out[] = $f;
            if (count($out) >= 5) break;
        }
        return $out;
    }

    /** ¿El partido/jornada tiene al participante en alguno de sus slots? */
    private function _fixture_es_del_participante($f, $id_participante, $id_ute, $detalle_ute, $masivo) {
        if ($masivo) return true; // jornada masiva: compiten todos los inscriptos

        $s1 = isset($f['id_ute_1']) ? (int) $f['id_ute_1'] : 0;
        $s2 = isset($f['id_ute_2']) ? (int) $f['id_ute_2'] : 0;

        if ($s1 === -$id_participante || $s2 === -$id_participante) return true; // individual
        if ($id_ute > 0 && ($s1 === $id_ute || $s2 === $id_ute)) return true;    // su equipo

        // Slot pendiente en fase eliminatoria (ej. "Ganador Llave 1").
        if (($s1 === 0 || $s2 === 0) && $f['fase'] !== 'GRUPO') return true;

        // Fallback: inscripción con nombre de equipo pero sin id_ute.
        $nombres = trim((string) $detalle_ute);
        if ($nombres !== '') {
            if (($s1 > 0 && $this->_ute_coincide_con_detalle($s1, $nombres))
             || ($s2 > 0 && $this->_ute_coincide_con_detalle($s2, $nombres))) {
                return true;
            }
        }
        return false;
    }

    /** Compara un id_ute del fixture con el nombre de equipo de la inscripción. */
    private function _ute_coincide_con_detalle($id_ute, $detalle_ute) {
        static $cache = array();
        $id_ute = (int) $id_ute;
        if (!isset($cache[$id_ute])) {
            $this->db->select('nombre_ute');
            $this->db->where('id_ute', $id_ute);
            $row = $this->db->get('utes')->row_array();
            $cache[$id_ute] = $row ? $row['nombre_ute'] : null;
        }
        if ($cache[$id_ute] === null) return false;
        return strtoupper(trim(preg_replace('/\s+/', ' ', $cache[$id_ute])))
            === strtoupper(trim(preg_replace('/\s+/', ' ', $detalle_ute)));
    }

    /**
     * Resultados YA CARGADOS (panel Resultados) que involucran al participante
     * en esa categoría: por jornada propia (mismo id_fixture) o cuando el
     * resultado menciona a su equipo (UTE por id o por nombre). Incluye el
     * detalle por lado y los datos de desempate para armar la tarjeta.
     */
    private function _resultados_del_participante($id_categoria, $id_participante, $id_ute, $detalle_ute) {
        if (!$id_categoria || !$this->_tabla_existe_silenciosa('resultados')) return array();

        $con_desempate = false;
        try {
            $q = $this->db->query("SELECT COUNT(*) AS n FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'resultados'
                  AND COLUMN_NAME = 'hubo_desempate'");
            $r = $q ? $q->row_array() : null;
            $con_desempate = !empty($r['n']);
        } catch (Throwable $e) {
            $con_desempate = false;
        }

        $this->db->select('r.*', FALSE);
        // Fecha/hora del fixture asociado (para mostrar "cuándo se jugó" en el pase).
        $this->db->select('f.fecha_competencia AS fixture_fecha, f.hora_inicio AS fixture_hora', FALSE);
        if ($con_desempate) {
            $this->db->select('ug.nombre_ute AS nombre_ganador_desempate', FALSE);
            $this->db->join('utes ug', 'ug.id_ute = r.id_ute_ganador', 'left');
        }
        $this->db->from('resultados r');
        if ($this->_tabla_existe_silenciosa('fixtures')) {
            $this->db->join('fixtures f', 'f.id_fixture = r.id_fixture', 'left');
        }
        $this->db->where('r.id_categoria', $id_categoria);
        $this->db->order_by('r.fecha_resultado', 'DESC');
        $this->db->order_by('r.id_resultado', 'DESC');
        $this->db->limit(10);
        $rows = $this->db->get()->result_array();
        if (!$rows) return array();

        // Detalle de todos los resultados en un solo query.
        $ids = array_column($rows, 'id_resultado');
        $this->db->where_in('id_resultado', $ids);
        $this->db->order_by('id_detalle', 'ASC');
        $detalles = array();
        foreach ($this->db->get('resultado_detalle')->result_array() as $d) {
            $detalles[(int) $d['id_resultado']][] = $d;
        }

        $out = array();
        foreach ($rows as $r) {
            $rid = (int) $r['id_resultado'];
            $det = isset($detalles[$rid]) ? $detalles[$rid] : array();

            // ¿El resultado menciona directamente a su equipo (sin fixture)?
            $menciona_equipo_propio = false;
            foreach ($det as $d) {
                if (!empty($d['id_ute']) && $id_ute > 0 && (int) $d['id_ute'] === $id_ute) {
                    $menciona_equipo_propio = true;
                    break;
                }
                if (trim((string) $d['nombre_libre']) !== '' && trim((string) $detalle_ute) !== ''
                    && strtoupper(trim($d['nombre_libre'])) === strtoupper(trim($detalle_ute))) {
                    $menciona_equipo_propio = true;
                    break;
                }
            }

            $vinculado = false;
            if (!empty($r['id_fixture'])) {
                $vinculado = $this->_fixture_del_participante_existe(
                    (int) $r['id_fixture'], $id_participante, $id_ute, $detalle_ute);
            }
            if (!$vinculado && !$menciona_equipo_propio) continue;

            if (!$con_desempate) {
                $r['hubo_desempate'] = 0;
                $r['desempate_metodo'] = null;
                $r['id_ute_ganador'] = null;
                $r['nombre_ganador_desempate'] = null;
            } elseif (empty($r['nombre_ganador_desempate']) && !empty($r['id_ute_ganador'])) {
                // Ganador "libre" (sin UTE): recuperar su nombre del detalle.
                foreach ($det as $d) {
                    if ((int) $d['id_ute'] === (int) $r['id_ute_ganador'] && !empty($d['nombre_libre'])) {
                        $r['nombre_ganador_desempate'] = $d['nombre_libre'];
                        break;
                    }
                }
            }
            $r['detalle'] = $det;
            $out[] = $r;
        }
        return $out;
    }

    /** ¿El fixture indicado tiene al participante en alguno de sus slots? */
    private function _fixture_del_participante_existe($id_fixture, $id_participante, $id_ute, $detalle_ute) {
        static $cache = array();
        if (isset($cache[$id_fixture])) return $cache[$id_fixture];

        $this->db->select('f.fase, f.id_ute_1, f.id_ute_2, d.modalidad_competencia', FALSE);
        $this->db->from('fixtures f');
        $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->where('f.id_fixture', $id_fixture);
        $f = $this->db->get()->row_array();
        $ok = false;
        if ($f) {
            $masivo = ($f['fase'] === 'JORNADA_UNICA' || $f['modalidad_competencia'] === 'MASIVO_TIEMPO');
            $ok = $this->_fixture_es_del_participante($f, $id_participante, $id_ute, $detalle_ute, $masivo);
        }
        $cache[$id_fixture] = $ok;
        return $ok;
    }

    /** table_exists sin romperse si la BD aún no tiene las tablas nuevas. */
    private function _tabla_existe_silenciosa($tabla) {
        try {
            return $this->db->table_exists($tabla);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function marcar_kit_entregado($id_participante, $nuevo_estado = 1) {
        $this->db->where('id_participante', $id_participante);
        $data = [
            'kit_entregado' => $nuevo_estado
        ];
        return $this->db->update('participantes', $data);
    }

    public function marcar_asistencia_deporte($id_inscripcion, $nuevo_estado = 1) {
        $this->db->where('id_inscripcion', $id_inscripcion);
        $data = [
            'asistio'    => $nuevo_estado,
            'fecha_hora' => ($nuevo_estado == 1) ? date('Y-m-d H:i:s') : NULL // Si es 0, limpia la fecha
        ];
        return $this->db->update('inscripciones_deportivas', $data);
    }

    // Trae el listado completo ordenado por el último inscripto
    public function obtener_todos_los_participantes() {
        $this->db->order_by('fecha_inscripcion', 'DESC');
        return $this->db->get('participantes')->result_array();
    }

    // Cuenta de forma veloz cuántos registros tienen kit_entregado = 1
    public function contar_kits_entregados() {
        $this->db->where('kit_entregado', 1);
        return $this->db->count_all_results('participantes');
    }

    public function obtener_detalle_participante($id_participante) {
        // 1. Traemos los datos base del participante
        // REVISIÓN: Asegurate de que todas estas columnas existan tal cual en tu tabla 'participantes'
        $this->db->select('
            id_participante, dni, nombre_completo, email, telefono, delegacion, 
            sexo, fecha_nacimiento, grupo_sanguineo, obra_social, tipo_empleado, 
            dieta_especial, hotel_alojamiento, contacto_emergencia, 
            es_competidor, es_delegado, kit_entregado, fecha_inscripcion, token_qr
        ');
        $this->db->from('participantes');
        $this->db->where('id_participante', $id_participante);
        $participante = $this->db->get()->row_array();

        // 2. Si el participante existe, le anexamos sus deportes de forma segura
        if ($participante) {
            $participante['deportes'] = array(); // Por defecto vacío
            
            // Hacemos un try-catch interno por si las tablas de deportes tienen nombres distintos
            try {
                $this->db->select('*');
                $this->db->from('inscripciones_deportivas id');
                $this->db->join('categorias c', 'c.id_categoria = id.id_categoria', 'inner');
                $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
                $this->db->where('id.id_participante', $id_participante);
                
                $resultado_deportes = $this->db->get()->result_array();
                if ($resultado_deportes) {
                    $participante['deportes'] = $resultado_deportes;
                }
            } catch (Exception $e) {
                // Si falla la consulta de deportes, no truncamos los datos personales del tipo
                $participante['deportes'] = array(['nombre_deporte' => 'Error al cargar', 'nombre_categoria' => 'Verificar tablas']);
            }
        }

        return $participante;
    }
    
    /**
     * Obtiene todos los participantes de una delegación específica
     */
    public function obtener_participantes_por_delegacion($delegacion) {
        $this->db->where('delegacion', $delegacion);
        $this->db->order_by('nombre_completo', 'ASC');
        $query = $this->db->get('participantes');
        
        $participantes = $query->result_array();
        
        // Agregar deportes a cada participante
        foreach ($participantes as &$participante) {
            try {
                $id_p = $participante['id_participante'];
                
                $this->db->select('d.nombre_deporte, c.nombre_categoria');
                $this->db->from('inscripciones_deportivas i');
                $this->db->join('categorias c', 'i.id_categoria = c.id_categoria');
                $this->db->join('deportes d', 'c.id_deporte = d.id_deporte');
                $this->db->where('i.id_participante', $id_p);
                
                $resultado_deportes = $this->db->get()->result_array();
                if ($resultado_deportes) {
                    $participante['deportes'] = $resultado_deportes;
                } else {
                    $participante['deportes'] = [];
                }
            } catch (Exception $e) {
                $participante['deportes'] = [];
            }
        }
        
        return $participantes;
    }
    
    /**
     * Obtiene todos los participantes de una delegación específica con campo es_competidor
     */
    public function obtener_participantes_por_delegacion_completo($delegacion) {
        $this->db->select('id_participante, dni, nombre_completo, email, delegacion, es_competidor, sexo, fecha_nacimiento');
        $this->db->where('delegacion', $delegacion);
        $this->db->order_by('nombre_completo', 'ASC');
        $query = $this->db->get('participantes');
        
        $participantes = $query->result_array();
        
        // Agregar deportes a cada participante
        foreach ($participantes as &$participante) {
            try {
                $id_p = $participante['id_participante'];
                
                $this->db->select('d.nombre_deporte, c.nombre_categoria');
                $this->db->from('inscripciones_deportivas i');
                $this->db->join('categorias c', 'i.id_categoria = c.id_categoria');
                $this->db->join('deportes d', 'c.id_deporte = d.id_deporte');
                $this->db->where('i.id_participante', $id_p);
                
                $resultado_deportes = $this->db->get()->result_array();
                if ($resultado_deportes) {
                    $participante['deportes'] = $resultado_deportes;
                } else {
                    $participante['deportes'] = [];
                }
            } catch (Exception $e) {
                $participante['deportes'] = [];
            }
        }
        
        return $participantes;
    }
    
    /**
     * Deportes en los que la delegación tiene competidores inscriptos.
     * Se usa para el filtro del reporte de fixture del delegado: el select
     * solo muestra los deportes propios de su delegación.
     */
    public function obtener_deportes_de_delegacion($delegacion) {
        if ($delegacion === NULL || trim((string) $delegacion) === '') {
            return array();
        }

        $this->db->distinct();
        $this->db->select('d.id_deporte, d.nombre_deporte', FALSE);
        $this->db->from('inscripciones_deportivas i');
        $this->db->join('participantes p', 'p.id_participante = i.id_participante', 'inner');
        $this->db->join('categorias c', 'c.id_categoria = i.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->where('p.delegacion', $delegacion);
        $this->db->order_by('d.nombre_deporte', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Obtiene todos los participantes de una delegación para exportar a CSV
     * Incluye: dni, nombre completo, sexo, fecha nacimiento, edad, delegacion, deporte, categoria, dieta_especial
     */
    public function obtener_participantes_para_csv($delegacion) {
        $this->db->select('id_participante, dni, nombre_completo, sexo, fecha_nacimiento, delegacion, dieta_especial');
        if($delegacion!==NULL){
            $this->db->where('delegacion', $delegacion);
        }
        
        $this->db->order_by('nombre_completo', 'ASC');
        $query = $this->db->get('participantes');
        
        $participantes = $query->result_array();
        $resultado = [];
        
        foreach ($participantes as $participante) {
            $id_p = $participante['id_participante'];
            
            // Calcular edad
            $fecha_nac = new DateTime($participante['fecha_nacimiento']);
            $hoy = new DateTime();
            $edad = $hoy->diff($fecha_nac)->y;
            
            // Obtener deportes inscritos
            $this->db->select('d.nombre_deporte, c.nombre_categoria');
            $this->db->from('inscripciones_deportivas i');
            $this->db->join('categorias c', 'i.id_categoria = c.id_categoria');
            $this->db->join('deportes d', 'c.id_deporte = d.id_deporte');
            $this->db->where('i.id_participante', $id_p);
            $deportes_query = $this->db->get()->result_array();
            
            // Si tiene deportes, crear una fila por cada deporte
            if (!empty($deportes_query)) {
                foreach ($deportes_query as $deporte) {
                    $resultado[] = [
                        'dni' => $participante['dni'],
                        'nombre_completo' => $participante['nombre_completo'],
                        'sexo' => $participante['sexo'],
                        'fecha_nacimiento' => $participante['fecha_nacimiento'],
                        'edad' => $edad,
                        'delegacion' => $participante['delegacion'],
                        'deporte' => $deporte['nombre_deporte'],
                        'categoria' => $deporte['nombre_categoria'],
                        'dieta_especial' => $participante['dieta_especial']
                    ];
                }
            } else {
                // Si no tiene deportes, igual lo agregamos (acompañante)
                $resultado[] = [
                    'dni' => $participante['dni'],
                    'nombre_completo' => $participante['nombre_completo'],
                    'sexo' => $participante['sexo'],
                    'fecha_nacimiento' => $participante['fecha_nacimiento'],
                    'edad' => $edad,
                    'delegacion' => $participante['delegacion'],
                    'deporte' => '',
                    'categoria' => '',
                    'dieta_especial' => $participante['dieta_especial']
                ];
            }
        }
        
        return $resultado;
    }
}
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fixture_model extends CI_Model {
    
    /**
     * Obtiene todos los partidos del fixture con información completa
     * @param int|null $id_categoria Filtro por categoría (opcional)
     * @return array
     */
    public function obtener_fixture_completo($id_categoria = null) {
        $this->db->select('
            f.id_partido,
            f.id_categoria,
            f.id_equipo_local,
            f.id_equipo_visitante,
            f.id_participante_local,
            f.id_participante_visitante,
            f.fecha_partido,
            f.hora_partido,
            f.id_lugar,
            f.resultado_local,
            f.resultado_visitante,
            f.estado,
            f.ronda,
            f.grupo,
            c.nombre_categoria,
            c.genero as genero_categoria,
            d.nombre_deporte,
            l.nombre as nombre_lugar,
            u_local.nombre_ute as nombre_equipo_local,
            u_visitante.nombre_ute as nombre_equipo_visitante,
            p_local.nombre_completo as nombre_participante_local,
            p_visitante.nombre_completo as nombre_participante_visitante
        ');
        
        $this->db->from('fixture_partidos f');
        $this->db->join('categorias c', 'c.id_categoria = f.id_categoria', 'inner');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte', 'inner');
        $this->db->join('lugares l', 'l.id = f.id_lugar', 'left');
        $this->db->join('utes u_local', 'u_local.id_ute = f.id_equipo_local', 'left');
        $this->db->join('utes u_visitante', 'u_visitante.id_ute = f.id_equipo_visitante', 'left');
        $this->db->join('participantes p_local', 'p_local.id_participante = f.id_participante_local', 'left');
        $this->db->join('participantes p_visitante', 'p_visitante.id_participante = f.id_participante_visitante', 'left');
        
        if ($id_categoria !== null) {
            $this->db->where('f.id_categoria', $id_categoria);
        }
        
        $this->db->order_by('f.fecha_partido', 'ASC');
        $this->db->order_by('f.hora_partido', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Genera cruces automáticos para una categoría
     * @param int $id_categoria ID de la categoría
     * @param string $tipo_generacion 'todos_contra_todos' o 'eliminatoria'
     * @return array Resultado de la operación
     */
    public function generar_cruces($id_categoria, $tipo_generacion = 'todos_contra_todos') {
        // Obtener información de la categoría
        $this->db->select('c.*, d.modalidad');
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte');
        $this->db->where('c.id_categoria', $id_categoria);
        $categoria = $this->db->get()->row_array();
        
        if (!$categoria) {
            return ['success' => false, 'message' => 'Categoría no encontrada'];
        }
        
        // Verificar si ya existen partidos para esta categoría
        $this->db->where('id_categoria', $id_categoria);
        $existing = $this->db->count_all_results('fixture_partidos');
        
        if ($existing > 0) {
            return ['success' => false, 'message' => 'Ya existen partidos generados para esta categoría'];
        }
        
        // Obtener participantes/UTEs según la modalidad
        if ($categoria['modalidad'] === 'EQUIPO' || $categoria['modalidad'] === 'AMBAS') {
            $competidores = $this->_obtener_utes_por_categoria($id_categoria);
        } else {
            $competidores = $this->_obtener_participantes_por_categoria($id_categoria);
        }
        
        if (count($competidores) < 2) {
            return ['success' => false, 'message' => 'Se necesitan al menos 2 competidores para generar el fixture'];
        }
        
        // Generar cruces según el tipo
        if ($tipo_generacion === 'todos_contra_todos') {
            $partidos = $this->_generar_todos_contra_todos($competidores, $id_categoria, $categoria);
        } else {
            $partidos = $this->_generar_eliminatoria($competidores, $id_categoria, $categoria);
        }
        
        // Insertar los partidos
        $this->db->trans_start();
        foreach ($partidos as $partido) {
            $this->db->insert('fixture_partidos', $partido);
        }
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Error al guardar los partidos'];
        }
        
        return [
            'success' => true, 
            'message' => 'Fixture generado correctamente',
            'cantidad_partidos' => count($partidos)
        ];
    }
    
    /**
     * Obtiene las UTEs inscritas en una categoría
     */
    private function _obtener_utes_por_categoria($id_categoria) {
        $this->db->select('u.id_ute, u.nombre_ute');
        $this->db->from('utes u');
        $this->db->join('inscripciones_deportivas i', 'i.id_ute = u.id_ute');
        $this->db->where('i.id_categoria', $id_categoria);
        $this->db->group_by('u.id_ute');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Obtiene los participantes individuales inscritos en una categoría
     */
    private function _obtener_participantes_por_categoria($id_categoria) {
        $this->db->select('p.id_participante, p.nombre_completo');
        $this->db->from('participantes p');
        $this->db->join('inscripciones_deportivas i', 'i.id_participante = p.id_participante');
        $this->db->where('i.id_categoria', $id_categoria);
        $this->db->where('i.id_ute IS NULL', null, false); // Solo participantes sin UTE
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Genera fixture todos contra todos (liga)
     */
    private function _generar_todos_contra_todos($competidores, $id_categoria, $categoria) {
        $partidos = [];
        $n = count($competidores);
        $ronda = 1;
        
        // Si es impar, agregamos un "bye" (descansa)
        if ($n % 2 != 0) {
            $competidores[] = ['id_ute' => null, 'id_participante' => null, 'nombre_ute' => 'DESCANSA', 'nombre_completo' => 'DESCANSA'];
            $n++;
        }
        
        // Algoritmo round-robin
        for ($round = 1; $round < $n; $round++) {
            for ($i = 0; $i < $n / 2; $i++) {
                $local = $competidores[$i];
                $visitante = $competidores[$n - 1 - $i];
                
                // Saltar si alguno es "DESCANSA"
                if ($local['nombre_ute'] === 'DESCANSA' || $local['nombre_completo'] === 'DESCANSA' ||
                    $visitante['nombre_ute'] === 'DESCANSA' || $visitante['nombre_completo'] === 'DESCANSA') {
                    continue;
                }
                
                // Determinar fecha y hora basada en la configuración de la categoría
                $fecha_base = $categoria['dia_competencia'] ?? date('Y-m-d');
                $hora_base = $categoria['hora_competencia'] ?? '09:00:00';
                
                // Calcular fecha para esta ronda (sumar días según la ronda)
                $fecha_partido = date('Y-m-d', strtotime($fecha_base . " + " . (($round - 1) * 7) . " days"));
                
                // Calcular hora escalonada por partido
                $hora_partido = date('H:i:s', strtotime($hora_base . " + " . ($i * 60) . " minutes"));
                
                $partido = [
                    'id_categoria' => $id_categoria,
                    'ronda' => $round,
                    'fecha_partido' => $fecha_partido,
                    'hora_partido' => $hora_partido,
                    'id_lugar' => $categoria['id_lugar'] ?? null,
                    'estado' => 'PROGRAMADO'
                ];
                
                // Asignar local y visitante según sea equipo o individual
                if (isset($local['id_ute'])) {
                    $partido['id_equipo_local'] = $local['id_ute'];
                    $partido['id_equipo_visitante'] = $visitante['id_ute'];
                } else {
                    $partido['id_participante_local'] = $local['id_participante'];
                    $partido['id_participante_visitante'] = $visitante['id_participante'];
                }
                
                $partidos[] = $partido;
            }
            
            // Rotar competidores (mantener el primero fijo)
            $last = array_pop($competidores);
            array_splice($competidores, 1, 0, [$last]);
        }
        
        return $partidos;
    }
    
    /**
     * Genera fixture eliminatorio (simple)
     */
    private function _generar_eliminatoria($competidores, $id_categoria, $categoria) {
        $partidos = [];
        $n = count($competidores);
        
        // Completar a potencia de 2 si es necesario (byes)
        $potencia = 1;
        while ($potencia < $n) {
            $potencia *= 2;
        }
        
        while ($n < $potencia) {
            $competidores[] = ['id_ute' => null, 'id_participante' => null, 'nombre_ute' => 'BYE', 'nombre_completo' => 'BYE'];
            $n++;
        }
        
        $ronda = 1;
        $siguiente_ronda = $competidores;
        
        while (count($siguiente_ronda) > 1) {
            $nueva_ronda = [];
            
            for ($i = 0; $i < count($siguiente_ronda); $i += 2) {
                $local = $siguiente_ronda[$i];
                $visitante = $siguiente_ronda[$i + 1];
                
                // Si hay BYE, el otro pasa directo
                if ($local['nombre_ute'] === 'BYE' || $local['nombre_completo'] === 'BYE') {
                    $nueva_ronda[] = $visitante;
                    continue;
                }
                if ($visitante['nombre_ute'] === 'BYE' || $visitante['nombre_completo'] === 'BYE') {
                    $nueva_ronda[] = $local;
                    continue;
                }
                
                $fecha_base = $categoria['dia_competencia'] ?? date('Y-m-d');
                $hora_base = $categoria['hora_competencia'] ?? '09:00:00';
                $fecha_partido = date('Y-m-d', strtotime($fecha_base . " + " . (($ronda - 1) * 7) . " days"));
                $hora_partido = date('H:i:s', strtotime($hora_base . " + " . (count($partidos) * 60) . " minutes"));
                
                $partido = [
                    'id_categoria' => $id_categoria,
                    'ronda' => $ronda,
                    'grupo' => 'Eliminatoria',
                    'fecha_partido' => $fecha_partido,
                    'hora_partido' => $hora_partido,
                    'id_lugar' => $categoria['id_lugar'] ?? null,
                    'estado' => 'PROGRAMADO'
                ];
                
                if (isset($local['id_ute'])) {
                    $partido['id_equipo_local'] = $local['id_ute'];
                    $partido['id_equipo_visitante'] = $visitante['id_ute'];
                } else {
                    $partido['id_participante_local'] = $local['id_participante'];
                    $partido['id_participante_visitante'] = $visitante['id_participante'];
                }
                
                $partidos[] = $partido;
            }
            
            $siguiente_ronda = $nueva_ronda;
            $ronda++;
        }
        
        return $partidos;
    }
    
    /**
     * Actualiza el resultado de un partido
     */
    public function actualizar_resultado($id_partido, $resultado_local, $resultado_visitante) {
        $data = [
            'resultado_local' => $resultado_local,
            'resultado_visitante' => $resultado_visitante,
            'estado' => 'FINALIZADO'
        ];
        
        $this->db->where('id_partido', $id_partido);
        return $this->db->update('fixture_partidos', $data);
    }
    
    /**
     * Cambia el estado de un partido
     */
    public function cambiar_estado($id_partido, $estado) {
        $this->db->where('id_partido', $id_partido);
        return $this->db->update('fixture_partidos', ['estado' => $estado]);
    }
    
    /**
     * Elimina el fixture de una categoría
     */
    public function eliminar_fixture($id_categoria) {
        $this->db->where('id_categoria', $id_categoria);
        return $this->db->delete('fixture_partidos');
    }
    
    /**
     * Obtiene estadísticas de una categoría
     */
    public function obtener_estadisticas_categoria($id_categoria) {
        $this->db->select('COUNT(*) as total_partidos');
        $this->db->from('fixture_partidos');
        $this->db->where('id_categoria', $id_categoria);
        $total = $this->db->get()->row_array();
        
        $this->db->select('COUNT(*) as partidos_jugados');
        $this->db->from('fixture_partidos');
        $this->db->where('id_categoria', $id_categoria);
        $this->db->where('estado', 'FINALIZADO');
        $jugados = $this->db->get()->row_array();
        
        return [
            'total_partidos' => $total['total_partidos'] ?? 0,
            'partidos_jugados' => $jugados['partidos_jugados'] ?? 0,
            'partidos_pendientes' => ($total['total_partidos'] ?? 0) - ($jugados['partidos_jugados'] ?? 0)
        ];
    }
}

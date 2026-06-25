<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Participante_model extends CI_Model {

    public function insertar_completo($data_persona, $deportes_seleccionados) {
        $this->db->trans_start(); // Iniciamos transacción por seguridad

        // 1. Insertamos la persona en la tabla participantes
        $this->db->insert('participantes', $data_persona);
        $id_participante = $this->db->insert_id();

        // 2. Procesamos el guardado relacional de deportes y UTEs
        $this->_procesar_deportes_y_utes($id_participante, $deportes_seleccionados);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function actualizar_completo($id_participante, $data_persona, $deportes_seleccionados) {
        $this->db->trans_start();

        // 1. Actualizamos los datos del participante principal
        $this->db->where('id_participante', $id_participante);
        $this->db->update('participantes', $data_persona);

        // 2. Limpiamos sus inscripciones previas en esta tabla antes de re-insertar
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('inscripciones_deportivas');

        // Nota operativa: Si querés desvincularlo de UTEs previas en las que ya no participa,
        // podés limpiar su registro de la tabla intermedia participantes_utes:
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('participantes_utes');

        // 3. Procesamos las nuevas disciplinas y UTEs estructuradas
        $this->_procesar_deportes_y_utes($id_participante, $deportes_seleccionados);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    private function _procesar_deportes_y_utes($id_participante, $deportes_seleccionados) {
        if (empty($deportes_seleccionados)) {
            return;
        }

        foreach ($deportes_seleccionados as $disc) {
            $id_ute_asignada = NULL;

            // ¿El participante declaró que TIENE una UTE y le puso nombre?
            if ((int)$disc['tiene_ute'] === 1 && !empty($disc['nombre_ute'])) {
                
                // === CONTROL DE DUPLICADOS: Buscamos si la UTE ya existe ===
                $this->db->where('id_categoria', $disc['id_categoria']);
                $this->db->where('nombre_ute', $disc['nombre_ute']);
                $query_ute_existente = $this->db->get('utes');

                if ($query_ute_existente->num_rows() > 0) {
                    // Si ya existe, REUSAMOS el ID existente en vez de crear uno nuevo
                    $ute_vieja = $query_ute_existente->row_array();
                    $id_ute_asignada = $ute_vieja['id_ute'];
                } else {
                    // Si no existe, la creamos desde cero
                    $data_ute = [
                        'id_categoria' => $disc['id_categoria'],
                        'nombre_ute'   => $disc['nombre_ute']
                    ];
                    $this->db->insert('utes', $data_ute);
                    $id_ute_asignada = $this->db->insert_id();
                }

                // B) Vinculamos al creador de la inscripción (Pepe) a la UTE
                // Usamos un chequeo previo para no duplicar la fila intermedia
                $this->db->where('id_ute', $id_ute_asignada);
                $this->db->where('id_participante', $id_participante);
                if ($this->db->get('participantes_utes')->num_rows() == 0) {
                    $this->db->insert('participantes_utes', [
                        'id_ute'          => $id_ute_asignada,
                        'id_participante' => $id_participante
                    ]);
                }

                // C) Procesamos la bolsa de DNIs de los compañeros que tipeó Pepe
                if (!empty($disc['companeros']) && is_array($disc['companeros'])) {
                    foreach ($disc['companeros'] as $dni_comp) {
                        
                        // Verificamos si ese DNI ya está registrado en el sistema
                        $this->db->where('dni', $dni_comp);
                        $query_p = $this->db->get('participantes');

                        if ($query_p->num_rows() > 0) {
                            $comp_row = $query_p->row_array();
                            $id_socio_ute = $comp_row['id_participante'];
                        } else {
                            // El DNI no existe. Lo insertamos como fantasma temporal.
                            $data_fantasma = [
                                'dni'             => $dni_comp,
                                'nombre_completo' => ' ',
                                'email'           => ' ',
                                'es_competidor'   => 1,
                                'es_delegado'     => 0,
                                'sexo'     => 'Otro',
                                'tipo_empleado'     => 'Planta Permanente',
                                'dieta_especial'     => 'Sin restrictions'
                            ];
                            $this->db->insert('participantes', $data_fantasma);
                            $id_socio_ute = $this->db->insert_id();
                        }

                        // 1. Lo vinculamos a la UTE (tabla intermedia de equipos) si no estaba ya vinculado
                        $this->db->where('id_ute', $id_ute_asignada);
                        $this->db->where('id_participante', $id_socio_ute);
                        if ($this->db->get('participantes_utes')->num_rows() == 0) {
                            $this->db->insert('participantes_utes', [
                                'id_ute'          => $id_ute_asignada,
                                'id_participante' => $id_socio_ute
                            ]);
                        }

                        // 2. Le generamos su propia inscripción deportiva al compañero si no la tenía
                        $this->db->where('id_participante', $id_socio_ute);
                        $this->db->where('id_categoria', $disc['id_categoria']);
                        $check_ins = $this->db->get('inscripciones_deportivas');

                        if ($check_ins->num_rows() == 0) {
                            $this->db->insert('inscripciones_deportivas', [
                                'id_participante' => $id_socio_ute,
                                'id_categoria'    => $disc['id_categoria'],
                                'tiene_ute'       => 1,
                                    'necesita_ute'    => 0,
                                    'id_ute'          => $id_ute_asignada
                                ]);
                            }
                        }
                    }
                }

                // 3. Finalmente guardamos el registro del participante principal (Pepe)
                $data_inscripcion = [
                    'id_participante' => $id_participante,
                    'id_categoria'    => $disc['id_categoria'],
                    'tiene_ute'       => $disc['tiene_ute'],
                    'necesita_ute'    => $disc['necesita_ute'],
                    'id_ute'          => $id_ute_asignada
                ];
            
            $this->db->insert('inscripciones_deportivas', $data_inscripcion);
        }
    }

    

    public function obtener_por_token($token) {
        $this->db->where('token_qr', $token);
        $query = $this->db->get('participantes');
        return $query->row_array(); // Nos devuelve los datos del participante en un array
    }

    public function obtener_deportes_inscriptos($id_participante) {
        $this->db->select('inscripciones_deportivas.id_inscripcion, deportes.nombre_deporte, categorias.nombre_categoria, inscripciones_deportivas.asistio');
        $this->db->from('inscripciones_deportivas');
        $this->db->join('categorias', 'inscripciones_deportivas.id_categoria = categorias.id_categoria');
        $this->db->join('deportes', 'categorias.id_deporte = deportes.id_deporte');
        $this->db->where('inscripciones_deportivas.id_participante', $id_participante);
        
        return $this->db->get()->result_array();
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
            es_competidor, es_delegado, kit_entregado, fecha_inscripcion
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
}
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin_controller
 *
 * Responsabilidad: panel de administración de inscripciones (Control Total),
 * detalle/edición/eliminación de participantes, carga administrativa de
 * nuevas inscripciones, acreditación de kits y deportes, impresión de
 * credenciales y exportación CSV.
 */
class Admin_controller extends OLIM_Controller {

    /**
     * Descarga un CSV de inscriptos (delimitador ';' con BOM UTF-8).
     * Si $delegacion es NULL exporta todos los inscriptos del sistema.
     */
    private function _exportar_csv_inscriptos($delegacion) {
        $this->load->model('Participante_model');

        $datos = $this->Participante_model->obtener_participantes_para_csv($delegacion);

        $nombre_archivo = 'inscriptos_'
            . ($delegacion ? str_replace(' ', '_', strtolower($delegacion)) . '_' : '')
            . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');

        $output = fopen('php://output', 'w');

        // BOM para que Excel reconozca UTF-8 correctamente
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $encabezados = ['DNI', 'Nombre Completo', 'Sexo', 'Fecha de Nacimiento', 'Edad', 'Delegación', 'Deporte', 'Categoría'];
        fputcsv($output, $encabezados, ';');

        foreach ($datos as $fila) {
            fputcsv($output, [
                $fila['dni'],
                $fila['nombre_completo'],
                $fila['sexo'],
                $fila['fecha_nacimiento'],
                $fila['edad'],
                $fila['delegacion'],
                $fila['deporte'],
                $fila['categoria']
            ], ';');
        }

        fclose($output);
        exit;
    }

    public function control_total() {
        // Seguridad Superadmin
        if (!$this->session->userdata('is_organizador') || $this->session->userdata('user_rol') !== 'superadmin') {
            redirect('Auth/login');
        }

        $this->load->model('Deporte_model');
        $this->load->model('UTE_model');

        // Datos para la pestaña de Inscripciones
        $data['listado_inscripciones'] = $this->Deporte_model->obtener_todas_las_inscripciones();
        $data['deportes_db'] = $this->Deporte_model->obtener_todos_los_deportes();
        
        // Datos para la pestaña de UTEs/Equipos
        $data['utes'] = $this->UTE_model->obtener_todas_las_utes();
        $data['categorias'] = $this->UTE_model->obtener_categorias_con_deportes();

        // Datos para la pestaña de Fixture
        $this->load->model('Fixture_model');
        $data['categorias_fixture'] = $this->Fixture_model->obtener_categorias_para_fixture();
        $data['lugares_db'] = $this->Deporte_model->obtener_todos_los_lugares();

        $data['menu_activo'] = 'control';
        $this->load->view('admin/control_total', $data);
    }

    /* ============================================================
     *  FIXTURE (pestaña de Control Total) - endpoints AJAX
     * ============================================================ */

    public function detalle_ajax($id_participante) {
        // Validar que el usuario esté logueado (staff o delegado)
        if (!$this->session->userdata('is_organizador') && !$this->session->userdata('is_delegado')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autorizado']);
            return;
        }
        
        // Si es delegado, verificar que el participante sea de su delegación
        if ($this->session->userdata('is_delegado')) {
            $this->load->model('Participante_model');
            $participante = $this->Participante_model->obtener_detalle_participante($id_participante);
            $user_nombre = $this->session->userdata('user_nombre');
            
            if (!$participante || $participante['delegacion'] !== $user_nombre) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'No tenés permiso para ver este participante']);
                return;
            }
        }
        
        if (!$id_participante) {
            echo json_encode(['error' => 'ID no válido']);
            return;
        }

        $this->load->model('Participante_model');
        $data = $this->Participante_model->obtener_detalle_participante($id_participante);

        // Seteamos la cabecera para decirle al navegador que es un JSON puro
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Eliminar una inscripción completa (participante y todas sus inscripciones deportivas)
     */

    public function eliminar_inscripcion($id_participante) {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Admin/control_total');
            return;
        }

        if (!$id_participante) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Admin/control_total');
            return;
        }

        $this->load->model('Participante_model');
        
        // Iniciamos transacción para borrar todo de forma atómica
        $this->db->trans_start();
        
        // 1. Primero borramos las inscripciones deportivas relacionadas
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('inscripciones_deportivas');
        
        // 2. Luego borramos al participante
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('participantes');
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === TRUE) {
            $this->session->set_flashdata('success', 'Inscripción eliminada correctamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al eliminar la inscripción. Intente nuevamente.');
        }
        
        redirect('Admin/control_total');
    }

    /**
     * Endpoint AJAX para eliminar inscripción sin recargar página
     */

    public function eliminar_inscripcion_ajax($id_participante) {
        header('Content-Type: application/json');
        
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode([
                'success' => false,
                'error' => 'No tenés permisos para realizar esta acción.'
            ]);
            return;
        }

        if (!$id_participante) {
            echo json_encode([
                'success' => false,
                'error' => 'ID de participante no válido.'
            ]);
            return;
        }

        $this->load->model('Participante_model');
        
        // Iniciamos transacción para borrar todo de forma atómica
        $this->db->trans_start();
        
        // 1. Primero borramos las inscripciones deportivas relacionadas
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('inscripciones_deportivas');
        
        // 2. Luego borramos al participante
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('participantes');
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === TRUE) {
            echo json_encode([
                'success' => true,
                'mensaje' => 'Inscripción eliminada correctamente.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Error al eliminar la inscripción. Intente nuevamente.'
            ]);
        }
    }

    /**
     * Mostrar formulario para modificar una inscripción (staff)
     */

    public function modificar_inscripcion($id_participante) {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Admin/control_total');
            return;
        }

        if (!$id_participante) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Admin/control_total');
            return;
        }

        $this->load->model('Participante_model');
        $this->load->model('Deporte_model');
        
        // Obtener datos del participante
        $data['participante'] = $this->Participante_model->obtener_detalle_participante($id_participante);
        
        if (!$data['participante']) {
            $this->session->set_flashdata('error', 'Participante no encontrado.');
            redirect('Admin/control_total');
            return;
        }
        
        // Obtener todos los deportes para el formulario
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        
        // Cargar vista de edición
        $this->load->view('admin/formulario_editar_inscripcion', $data);
    }

    /**
     * Guardar modificaciones de una inscripción
     */

    public function guardar_modificacion() {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Admin/control_total');
            return;
        }

        $this->load->model('Participante_model');
        $post = $this->input->post();

        if (empty($post['id_participante'])) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Admin/control_total');
            return;
        }

        $id_participante = $post['id_participante'];

        // Estructura de datos actualizados
        $data_persona = olim_normalizar_persona($post);

        // CONTROL Y CAPTURA DE DISCIPLINAS + PANEL UTE (para edición)
        $deportes_seleccionados = olim_disciplinas_desde_post($post, TRUE);

        // Ejecutar actualización
        $resultado = $this->Participante_model->actualizar_completo(
            $id_participante, 
            $data_persona, 
            $deportes_seleccionados
        );

        if ($resultado) {
            $this->session->set_flashdata('success', 'Inscripción modificada correctamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al modificar la inscripción. Intente nuevamente.');
        }

        redirect('Admin/control_total');
    }

    /**
     * Mostrar formulario para nueva inscripción (desde panel-inscripciones)
     */

    public function nueva_inscripcion() {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Admin/control_total');
            return;
        }

        $this->load->model('Deporte_model');
        
        // Obtener todos los deportes para el formulario
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        
        // Cargar vista de nueva inscripción
        $this->load->view('admin/formulario_nueva_inscripcion', $data);
    }

    /**
     * Guardar nueva inscripción
     */

    public function guardar_nueva_inscripcion() {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Admin/control_total');
            return;
        }

        $this->load->model('Participante_model');
        $post = $this->input->post();

        // Estructura de datos nuevos
        $data_persona = olim_normalizar_persona($post, bin2hex(random_bytes(16)));
        $data_persona['dni'] = trim($post['dni']);
        $data_persona['fecha_inscripcion'] = date('Y-m-d H:i:s');

        // CONTROL Y CAPTURA DE DISCIPLINAS + PANEL UTE
        $deportes_seleccionados = olim_disciplinas_desde_post($post);

        // Ejecutar inserción
        $resultado = $this->Participante_model->insertar_completo(
            $data_persona, 
            $deportes_seleccionados
        );

        if ($resultado) {
            $this->session->set_flashdata('success', 'Nueva inscripción guardada correctamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al guardar la nueva inscripción. Intente nuevamente.');
        }

        redirect('Admin/control_total');
    }

    // =========================================================================
    // GESTION DE UTES / EQUIPOS
    // =========================================================================

    /**
     * Panel principal de gestión de UTEs/Equipos
     */

    public function acreditar_kit() {
        if (!$this->session->userdata('is_organizador')) { show_error('No autorizado', 403); }
        
        $this->load->model('Participante_model');
        $id_participante = $this->input->post('id_participante');
        $token = $this->input->post('token_qr');
        $nuevo_estado = $this->input->post('nuevo_estado'); // Recibe 1 o 0 desde la Vista
        
        // Pasamos el ID y el nuevo valor
        $this->Participante_model->marcar_kit_entregado($id_participante, $nuevo_estado);
        redirect('Inscripciones/acreditacion/' . $token);
    }

    // --- NUEVO: ACCIÓN PARA CAMBIAR EL ASISTIO DEL DEPORTE A 1 ---

    public function acreditar_deporte() {
        if (!$this->session->userdata('is_organizador')) { show_error('No autorizado', 403); }
        
        $this->load->model('Participante_model');
        $id_inscripcion = $this->input->post('id_inscripcion');
        $token = $this->input->post('token_qr');
        $nuevo_estado = $this->input->post('nuevo_estado'); // Recibe 1 o 0 desde la Vista
        
        // Pasamos el ID y el nuevo valor
        $this->Participante_model->marcar_asistencia_deporte($id_inscripcion, $nuevo_estado);
        redirect('Inscripciones/acreditacion/' . $token);
    }

    public function imprimir_credencial($token = NULL) {
        // Seguridad: solo el staff logueado puede generar credenciales
        if (!$this->session->userdata('is_organizador') || empty($token)) {
            show_error('No autorizado o token no válido', 403);
        }

        $this->load->model('Participante_model');
        // Buscamos al participante por su token QR
        $data['participante'] = $this->Participante_model->obtener_por_token($token);

        if (empty($data['participante'])) {
            show_error('Participante no encontrado', 404);
        }

        // Cargamos la vista especial de impresión
        $this->load->view('admin/imprimir_credencial', $data);
    }

    /**
     * Nueva pantalla de visualización y monitoreo de los resultados del sondeo preliminar
     */

public function descargar_csv_todos_inscriptos() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Auth/login');
        }

        // Exporta a TODOS los inscriptos (sin filtrar por delegación)
        $this->_exportar_csv_inscriptos(NULL);
    }
}

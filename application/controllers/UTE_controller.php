<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * UTE_controller
 *
 * Responsabilidad: gestión de UTEs/equipos (endpoints AJAX usados por la
 * pestaña "Equipos" del Control Total).
 */
class UTE_controller extends OLIM_Controller {

    public function panel_utes() {
        // Seguridad Superadmin
        if (!$this->session->userdata('is_organizador') || $this->session->userdata('user_rol') !== 'superadmin') {
            redirect('Auth/login');
        }

        $this->load->model('UTE_model');
        
        $data['utes'] = $this->UTE_model->obtener_todas_las_utes();
        $data['categorias'] = $this->UTE_model->obtener_categorias_con_deportes();
        $data['menu_activo'] = 'control';
        
        $this->load->view('admin/control_total', $data);
    }

    /**
     * AJAX: Obtener participantes disponibles para una categoría
     */

    public function ajax_participantes_disponibles($id_categoria) {
        header('Content-Type: application/json');
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['error' => 'No tenés permisos']);
            return;
        }

        $this->load->model('UTE_model');
        $participantes = $this->UTE_model->obtener_participantes_disponibles($id_categoria);
        
        // Asegurarse de que siempre devolvemos un array válido
        if (empty($participantes)) {
            echo json_encode([]);
        } else {
            echo json_encode($participantes);
        }
    }

    /**
     * AJAX: Crear nueva UTE
     */

    public function ajax_crear_ute() {
        header('Content-Type: application/json');
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['success' => false, 'error' => 'No tenés permisos']);
            return;
        }

        $nombre_ute = $this->input->post('nombre_ute');
        $id_categoria = $this->input->post('id_categoria');

        if (empty($nombre_ute) || empty($id_categoria)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $this->load->model('UTE_model');
        $id_ute = $this->UTE_model->insertar_ute($nombre_ute, $id_categoria);

        if ($id_ute) {
            echo json_encode(['success' => true, 'id_ute' => $id_ute]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear la UTE']);
        }
    }

    /**
     * AJAX: Agregar participante a UTE
     */

    public function ajax_agregar_participante_ute() {
        header('Content-Type: application/json');
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['success' => false, 'error' => 'No tenés permisos']);
            return;
        }

        $id_ute = $this->input->post('id_ute');
        $id_participante = $this->input->post('id_participante');

        if (empty($id_ute) || empty($id_participante)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $this->load->model('UTE_model');
        
        // Verificar si ya tiene UTE
        $ute_info = $this->db->select('id_categoria')->from('utes')->where('id_ute', $id_ute)->get()->row_array();
        if ($ute_info && $this->UTE_model->participante_ya_tiene_ute($id_participante, $ute_info['id_categoria'])) {
            echo json_encode(['success' => false, 'error' => 'El participante ya está en una UTE de esta categoría']);
            return;
        }

        $resultado = $this->UTE_model->agregar_participante_a_ute($id_ute, $id_participante);

        if ($resultado) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al agregar el participante']);
        }
    }

    /**
     * AJAX: Eliminar participante de UTE
     */

    public function ajax_eliminar_participante_ute() {
        header('Content-Type: application/json');
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['success' => false, 'error' => 'No tenés permisos']);
            return;
        }

        $id_ute = $this->input->post('id_ute');
        $id_participante = $this->input->post('id_participante');

        if (empty($id_ute) || empty($id_participante)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $this->load->model('UTE_model');
        $resultado = $this->UTE_model->eliminar_participante_de_ute($id_ute, $id_participante);

        if ($resultado) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar']);
        }
    }

    /**
     * AJAX: Eliminar UTE completa
     */

    public function ajax_eliminar_ute() {
        header('Content-Type: application/json');
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['success' => false, 'error' => 'No tenés permisos']);
            return;
        }

        $id_ute = $this->input->post('id_ute');

        if (empty($id_ute)) {
            echo json_encode(['success' => false, 'error' => 'ID de UTE no válido']);
            return;
        }

        $this->load->model('UTE_model');
        $resultado = $this->UTE_model->eliminar_ute($id_ute);

        if ($resultado) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar la UTE']);
        }
    }

    /**
     * AJAX: Obtener detalles de una UTE con sus integrantes
     */

    public function ajax_detalle_ute($id_ute) {
        header('Content-Type: application/json');
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['error' => 'No tenés permisos']);
            return;
        }

        $this->load->model('UTE_model');
        $ute = $this->UTE_model->obtener_ute_con_integrantes($id_ute);
        
        echo json_encode($ute);
    }
    
    // ==========================================
    // VISTAS PARA DELEGADOS
    // ==========================================
    
    /**
     * Panel principal para delegados - muestra información de su delegación
     */
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth_controller
 *
 * Responsabilidad: autenticación y sesión del staff/organizadores y delegados
 * (login, logout, redirección inteligente post-QR y dashboard principal).
 */
class Auth_controller extends OLIM_Controller {

    public function login() {
        if ($this->session->userdata('is_organizador')) {
            if ($this->session->userdata('url_retorno_qr')) {
                $redirigir = $this->session->userdata('url_retorno_qr');
                $this->session->unset_userdata('url_retorno_qr');
                redirect($redirigir);
            }

            // --- DASHBOARD PRINCIPAL: SOLAMENTE PARTICIPANTES ---
            $this->load->model('Participante_model');
            
            $data['participantes'] = $this->Participante_model->obtener_todos_los_participantes();
            $data['total_inscriptos'] = count($data['participantes']);
            $data['total_kits'] = $this->Participante_model->contar_kits_entregados();
            $data['menu_activo'] = 'inscriptos';
            // Cargamos la vista principal (limpia)
            $this->load->view('admin/dashboard', $data);
            return;
        }
        
        if ($this->session->userdata('is_delegado')) {
            redirect('Delegado_controller/panel_delegado');
        }
        
        $this->load->view('admin/login');
    }
    
    /**
     * Dashboard para staff/organizador - muestra todos los participantes
     */

    public function procesar_login() {
        $this->load->model('Usuario_model');
        $usuario = $this->input->post('usuario');
        $password = $this->input->post('password');

        $logged_user = $this->Usuario_model->login($usuario, $password);

        if ($logged_user) {
            // Verificar si es delegado
            $es_delegado = $this->Usuario_model->es_delegado($logged_user['id_usuario']);
            
            if ($es_delegado) {
                // Session para delegados
                $this->session->set_userdata([
                    'is_delegado' => TRUE,
                    'user_id'     => $logged_user['id_usuario'], 
                    'user_nombre' => $logged_user['nombre_usuario'], 
                    'user_rol'    => $logged_user['rol']
                ]);
                
                redirect('Delegado_controller/panel_delegado');
            } else {
                // Session para staff/organizador
                $this->session->set_userdata([
                    'is_organizador' => TRUE,
                    'user_id'        => $logged_user['id_usuario'], 
                    'user_nombre'    => $logged_user['nombre_usuario'], 
                    'user_rol'       => $logged_user['rol']
                ]);
                
                // NUEVO: ¡El redireccionador inteligente!
                // Si venías de escanear un QR, te devuelve derecho a ese participante
                if ($this->session->userdata('url_retorno_qr')) {
                    $redirigir_a = $this->session->userdata('url_retorno_qr');
                    $this->session->unset_userdata('url_retorno_qr'); // Limpiamos la sesión
                    redirect($redirigir_a);
                } else {               
                    redirect('Auth_controller/dashboard');
                }
            }
        } else {
            $this->session->set_flashdata('error', 'Usuario o contraseña incorrectos.');
            redirect('Auth_controller/login');
        }
    }

    // 4. Cerrar sesión staff

    public function logout() {
        $this->session->sess_destroy();
        redirect('Auth_controller/login');
    }

    public function login_staff() {
        redirect('Auth_controller/login');
    }

    // --- NUEVO: ACCIÓN PARA CAMBIAR EL KIT ENTREGADO A 1 ---

    public function dashboard() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Auth_controller/login');
        }
        
        $this->load->model('Participante_model');
        
        $data['participantes'] = $this->Participante_model->obtener_todos_los_participantes();
        $data['total_inscriptos'] = count($data['participantes']);
        $data['total_kits'] = $this->Participante_model->contar_kits_entregados();
        $data['menu_activo'] = 'inscriptos';
        
        $this->load->view('admin/dashboard', $data);
    }

    // --- NUEVA PANTALLA: GESTIÓN DE DEPORTES ---
}

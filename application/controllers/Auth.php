<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth — Sesión del sistema (login staff/delegado, redirección inteligente y logout).
 *
 * Extracción del monolito `Inscripciones.php` (paso 3: arquitectura).
 * Las URLs originales siguen funcionando vía re-mapeos en config/routes.php.
 */
class Auth extends OLIM_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Usuario_model');
        $this->load->model('Participante_model');
    }

    /** Muestra el login, o salta directo al panel si ya hay sesión activa. */
    public function login() {
        if ($this->_es_organizador()) {
            // Si venía de escanear un QR, lo devuelve derecho a ese participante
            if ($this->session->userdata('url_retorno_qr')) {
                $redirigir = $this->session->userdata('url_retorno_qr');
                $this->session->unset_userdata('url_retorno_qr');
                redirect($redirigir);
            }
            $this->_render_dashboard();
            return;
        }

        if ($this->_es_delegado()) {
            redirect('Delegado/panel');
        }

        $this->load->view('admin/login');
    }

    /** Alias histórico: Inscripciones/login_staff. */
    public function staff() {
        $this->login();
    }

    /** Procesa el formulario de login (staff o delegado). */
    public function procesar() {
        $usuario  = $this->input->post('usuario');
        $password = $this->input->post('password');

        $logged_user = $this->Usuario_model->login($usuario, $password);

        if (!$logged_user) {
            $this->session->set_flashdata('error', 'Usuario o contraseña incorrectos.');
            redirect('Auth/login');
            return;
        }

        if ($this->Usuario_model->es_delegado($logged_user['id_usuario'])) {
            $this->session->set_userdata([
                'is_delegado' => TRUE,
                'user_id'     => $logged_user['id_usuario'],
                'user_nombre' => $logged_user['nombre_usuario'],
                'user_rol'    => $logged_user['rol'],
            ]);
            redirect('Delegado/panel');
        }

        $this->session->set_userdata([
            'is_organizador' => TRUE,
            'user_id'        => $logged_user['id_usuario'],
            'user_nombre'    => $logged_user['nombre_usuario'],
            'user_rol'       => $logged_user['rol'],
        ]);

        // Redireccionador inteligente: si venía de escanear un QR, vuelve ahí
        if ($this->session->userdata('url_retorno_qr')) {
            $redirigir_a = $this->session->userdata('url_retorno_qr');
            $this->session->unset_userdata('url_retorno_qr');
            redirect($redirigir_a);
        }

        redirect('Backend/inscriptos');
    }

    /** Cierre de sesión. */
    public function logout() {
        $this->session->sess_destroy();
        redirect('Auth/login');
    }

    /** Dashboard del staff (listado de inscriptos). */
    public function dashboard() {
        $this->_requerir_organizador();
        $this->_render_dashboard();
    }

    private function _render_dashboard() {
        $data['participantes']   = $this->Participante_model->obtener_todos_los_participantes();
        $data['total_inscriptos'] = count($data['participantes']);
        $data['total_kits']       = $this->Participante_model->contar_kits_entregados();
        $data['menu_activo']      = 'inscriptos';
        $this->load->view('admin/dashboard', $data);
    }
}

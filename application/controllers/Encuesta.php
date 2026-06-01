<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Encuesta extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Cargamos el modelo que maneja los deportes y las encuestas
        $this->load->model('Deporte_model');
    }

    /**
     * Muestra el formulario de la encuesta
     */
    public function index() {
        // Reutilizamos el método que ya teníamos para listar los 15 deportes
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        
        $this->load->view('encuesta_previa', $data);
    }

   /**
     * Procesa y guarda las respuestas enviadas por el formulario
     */
    public function guardar_respuesta() {
        $post = $this->input->post();

        $dni              = $this->input->post('dni');
        $delegacion       = $this->input->post('delegacion');
        $fecha_nacimiento = $this->input->post('fecha_nacimiento');
        $sexo             = $this->input->post('sexo');
        $deportes_interes = $this->input->post('deportes_interes');

        // Validación de campos obligatorios
        if (empty($delegacion) || empty($dni) || empty($fecha_nacimiento) || empty($sexo) || empty($deportes_interes)) {
            $this->session->set_flashdata('mensaje_error', 'Todos los campos son obligatorios.');
            redirect('Inscripciones');
        }

        // Si pasa todas las validaciones individuales, agrupamos
        $data = [
            'dni'              => trim($dni),
            'delegacion'       => mb_strtoupper(trim($delegacion), 'UTF-8'),
            'fecha_nacimiento' => $fecha_nacimiento,
            'sexo'             => mb_strtoupper(trim($sexo), 'UTF-8'),
            'deportes_interes' => $deportes_interes
        ];

        $this->load->model('Deporte_model');
        $guardado_exitoso = $this->Deporte_model->guardar_encuesta_anonima($data);

        if (!$guardado_exitoso) {
            $db_error = $this->db->error();
            $mensaje = 'Verifique si el DNI ya está registrado o si faltan datos obligatorios.';
            
            if (isset($db_error['code']) ) {
                $mensaje = 'El DNI <strong>' . $post['dni'] . '</strong> ya se encuentra registrado en nuestro sistema.';
            }
            $this->load->view('encuesta_resultado', ['error' => $mensaje]);
        } else {
           $this->load->view('encuesta_resultado');
        }
    }

    /**
     * Pantalla dedicada de agradecimiento
     */
    public function gracias() {
        $this->load->view('encuesta_gracias');
    }
}
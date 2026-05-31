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
        $delegacion       = $this->input->post('delegacion');
        $fecha_nacimiento = $this->input->post('fecha_nacimiento');
        $sexo             = $this->input->post('sexo');
        $deportes_interes = $this->input->post('deportes_interes');

        if (empty($delegacion) || empty($fecha_nacimiento) || empty($sexo) || empty($deportes_interes)) {
            $this->session->set_flashdata('mensaje_error', 'Todos los campos son obligatorios.');
            redirect('Inscripciones/encuesta'); // O la ruta donde tengas tu formulario
        }

        $this->load->model('Deporte_model');
        $guardado_exitoso = $this->Deporte_model->guardar_encuesta_anonima($delegacion, $fecha_nacimiento, $sexo, $deportes_interes);

        if ($guardado_exitoso) {
            // En vez de flashdata, mandamos directo a la pantalla de éxito
            redirect('Encuesta/gracias');
        } else {
            $this->session->set_flashdata('mensaje_error', 'Hubo un error al procesar tus respuestas.');
            redirect('Inscripciones/encuesta');
        }
    }

    /**
     * Pantalla dedicada de agradecimiento
     */
    public function gracias() {
        $this->load->view('encuesta_gracias');
    }
}
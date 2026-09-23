<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Deporte_controller
 *
 * Responsabilidad: ABM de deportes, categorías y lugares/predios, y
 * monitoreo del sondeo preliminar de disciplinas.
 */
class Deporte_controller extends OLIM_Controller {

    public function gestion_deportes() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Auth/login');
        }

        $this->load->model('Deporte_model');
        
        // Cargamos todo lo necesario para los fixtures y modales deportivos
        $data['deportes'] = $this->Deporte_model->obtener_fixture_completo(); 
        $data['todos_los_deportes'] = $this->Deporte_model->obtener_todos_los_deportes(); 
        $data['todos_los_lugares'] = $this->Deporte_model->obtener_todos_los_lugares(); 
        $data['menu_activo'] = 'deportes';

        $this->load->view('admin/gestion_deportes', $data);
    }

    

    // --- PROCESAR EL MODAL DE NUEVA CATEGORÍA ---

    public function guardar_categoria() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Auth/login');
        }

        $this->load->model('Categoria_model');
        
        // Le mandamos todo el POST crudo al modelo para que él decida qué hacer
        $guardado = $this->Categoria_model->insertar_categoria_desde_post($this->input->post());

        if ($guardado) {
            $this->session->set_flashdata('mensaje_exito', 'Categoría registrada correctamente.');
        } else {
            $this->session->set_flashdata('mensaje_error', 'Faltan datos obligatorios para crear la categoría.');
        }

        redirect('Deporte/gestion_deportes');
    }

    public function eliminar_categoria($id_categoria) {
        if (!empty($id_categoria)) {
            $this->Categoria_model->eliminar_categoria($id_categoria);
            $this->session->set_flashdata('msg_ok', 'Categoría eliminada correctamente.');
        }
        redirect('Deporte/gestion_deportes');
    }

    // --- EDITAR CATEGORÍA ---

    public function editar_categoria() {
        $id_categoria = $this->input->post('id_categoria');
        
        $data = [
            'nombre_categoria' => $this->input->post('nombre_categoria'),
            'genero' => $this->input->post('genero_categoria'),
            'cupo_maximo'      => $this->input->post('cupo_maximo'),
            'id_lugar'         => $this->input->post('id_lugar'),
            'dia_competencia'  => $this->input->post('dia_competencia'),
            'hora_competencia' => $this->input->post('hora_competencia')
        ];

        if (!empty($id_categoria)) {
            $this->Categoria_model->actualizar_categoria($id_categoria, $data);
            $this->session->set_flashdata('msg_ok', 'Categoría actualizada correctamente.');
        }
        redirect('Deporte/gestion_deportes');
    }

    public function guardar_lugar() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Auth/login');
        }

        $this->load->model('Deporte_model');
        
        $guardado = $this->Deporte_model->insertar_lugar_desde_post($this->input->post());

        if ($guardado) {
            $this->session->set_flashdata('mensaje_exito', 'Predio registrado correctamente.');
        } else {
            $this->session->set_flashdata('mensaje_error', 'El nombre del predio es obligatorio.');
        }

        redirect('Deporte/gestion_deportes');
    }

    // --- ELIMINAR LUGAR ---

    public function eliminar_lugar($id_lugar) {
        if (!empty($id_lugar)) {
            $this->Deporte_model->eliminar_lugar($id_lugar);
            $this->session->set_flashdata('msg_ok', 'Sede/Predio eliminado correctamente.');
        }
        redirect('Deporte/gestion_deportes');
    }

    // --- EDITAR LUGAR ---

    public function editar_lugar() {
        $id_lugar = $this->input->post('id_lugar');
        
        $data = [
            'nombre'    => $this->input->post('nombre'),
            'direccion' => $this->input->post('direccion')
        ];

        if (!empty($id_lugar)) {
            $this->Deporte_model->actualizar_lugar($id_lugar, $data);
            $this->session->set_flashdata('msg_ok', 'Predio actualizado correctamente.');
        }
        redirect('Deporte/gestion_deportes');
    }


    // 3. Procesar el formulario de Login

    public function guardar_deporte() {
        if (!$this->session->userdata('is_organizador')) { redirect('Auth/login'); }

        $data['nombre_deporte'] = $this->input->post('nombre_deporte', TRUE);
        $data['genero'] = $this->input->post('genero', TRUE);
        $this->Deporte_model->guardar_deporte($data);
        redirect('Deporte/gestion_deportes');
    }

    /**
     * Elimina un deporte y sus categorías asociadas por ID
     */

    public function eliminar_deporte($id_deporte) {
        if (!$this->session->userdata('is_organizador')) { redirect('Auth/login'); }

        if (!empty($id_deporte) && is_numeric($id_deporte)) {
            // Al borrar el deporte, quitamos también sus categorías para no dejar registros huérfanos
            $this->db->delete('categorias', ['id_deporte' => $id_deporte]);
            $this->db->delete('deportes', ['id_deporte' => $id_deporte]);
        }
        redirect('Deporte/gestion_deportes');
    }

    public function editar_deporte() {
        $id_deporte = $this->input->post('id_deporte');
        $data = [
            'nombre_deporte' => $this->input->post('nombre_deporte'),
            'genero'         => $this->input->post('genero')
        ];
        
        // Acá llamas a tu modelo para hacer el update correspondinte, por ej:
        $this->Deporte_model->actualizar_deporte($id_deporte, $data);
        
        redirect('Deporte/gestion_deportes');
    }

    public function monitoreo_encuesta() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Auth/login');
        }

        $this->load->model('Deporte_model');

        // Consultamos datos rápidos al modelo para armar las métricas básicas
        $data['total_encuestas'] = $this->Deporte_model->contar_total_encuestas();
        $data['ranking_deportes'] = $this->Deporte_model->obtener_ranking_deportes_sondeo();
        $data['respuestas_por_delegacion'] = $this->Deporte_model->obtener_respuestas_por_delegacion();
        $data['menu_activo'] = 'sondeo';

        $this->load->view('admin/monitoreo_encuesta', $data);
    }

    /**
     * Guarda un nuevo deporte general desde la ventana modal
     */
}

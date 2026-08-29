<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fixture extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Fixture_model');
        $this->load->model('Categoria_model');
        $this->load->model('Deporte_model');
        
        // Verificar sesión (ajustar según tu sistema de autenticación)
        if (!$this->session->userdata('id_usuario')) {
            redirect('admin/login');
        }
    }
    
    /**
     * Vista principal del panel de fixture
     */
    public function index() {
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        $data['lugares'] = $this->Deporte_model->obtener_todos_los_lugares();
        $this->load->view('admin/header_admin');
        $this->load->view('admin/panel-fixture', $data);
        $this->load->view('footer');
    }
    
    /**
     * Obtiene las categorías de un deporte (AJAX)
     */
    public function get_categorias_por_deporte($id_deporte) {
        $categorias = $this->Categoria_model->get_by_deporte($id_deporte);
        
        // Agregar estadísticas de inscriptos a cada categoría
        foreach ($categorias as $key => $cat) {
            $this->db->select('COUNT(*) as cantidad');
            $this->db->from('inscripciones_deportivas');
            $this->db->where('id_categoria', $cat['id_categoria']);
            $resultado = $this->db->get()->row_array();
            $categorias[$key]['cantidad_inscriptos'] = $resultado['cantidad'] ?? 0;
            
            // Verificar si ya tiene fixture generado
            $this->db->select('COUNT(*) as tiene_fixture');
            $this->db->from('fixture_partidos');
            $this->db->where('id_categoria', $cat['id_categoria']);
            $fixture_check = $this->db->get()->row_array();
            $categorias[$key]['tiene_fixture'] = $fixture_check['tiene_fixture'] > 0;
        }
        
        echo json_encode($categorias);
    }
    
    /**
     * Genera el fixture automáticamente (AJAX POST)
     */
    public function generar_fixture() {
        $id_categoria = $this->input->post('id_categoria');
        $tipo_generacion = $this->input->post('tipo_generacion', 'todos_contra_todos');
        
        if (empty($id_categoria)) {
            echo json_encode([
                'success' => false,
                'message' => 'Debe seleccionar una categoría'
            ]);
            return;
        }
        
        $resultado = $this->Fixture_model->generar_cruces($id_categoria, $tipo_generacion);
        echo json_encode($resultado);
    }
    
    /**
     * Obtiene el fixture completo o filtrado (AJAX)
     */
    public function obtener_fixture() {
        $id_categoria = $this->input->get('id_categoria');
        $id_deporte = $this->input->get('id_deporte');
        
        if ($id_categoria) {
            $partidos = $this->Fixture_model->obtener_fixture_completo($id_categoria);
        } else {
            $partidos = $this->Fixture_model->obtener_fixture_completo();
        }
        
        // Si se filtra por deporte, filtramos manualmente
        if ($id_deporte && !$id_categoria) {
            $partidos = array_filter($partidos, function($p) use ($id_deporte) {
                // Necesitaríamos cargar el modelo de categoría para filtrar correctamente
                return true; // Por ahora retornamos todos
            });
        }
        
        echo json_encode([
            'success' => true,
            'partidos' => $partidos
        ]);
    }
    
    /**
     * Actualiza el resultado de un partido (AJAX POST)
     */
    public function actualizar_resultado() {
        $id_partido = $this->input->post('id_partido');
        $resultado_local = $this->input->post('resultado_local');
        $resultado_visitante = $this->input->post('resultado_visitante');
        
        if (empty($id_partido) || !is_numeric($resultado_local) || !is_numeric($resultado_visitante)) {
            echo json_encode([
                'success' => false,
                'message' => 'Datos inválidos'
            ]);
            return;
        }
        
        $resultado = $this->Fixture_model->actualizar_resultado($id_partido, $resultado_local, $resultado_visitante);
        
        echo json_encode([
            'success' => $resultado,
            'message' => $resultado ? 'Resultado actualizado correctamente' : 'Error al actualizar'
        ]);
    }
    
    /**
     * Cambia el estado de un partido (AJAX POST)
     */
    public function cambiar_estado_partido() {
        $id_partido = $this->input->post('id_partido');
        $estado = $this->input->post('estado');
        
        $estados_validos = ['PROGRAMADO', 'EN_JUEGO', 'FINALIZADO', 'SUSPENDIDO'];
        
        if (empty($id_partido) || !in_array($estado, $estados_validos)) {
            echo json_encode([
                'success' => false,
                'message' => 'Datos inválidos'
            ]);
            return;
        }
        
        $resultado = $this->Fixture_model->cambiar_estado($id_partido, $estado);
        
        echo json_encode([
            'success' => $resultado,
            'message' => $resultado ? 'Estado actualizado correctamente' : 'Error al actualizar'
        ]);
    }
    
    /**
     * Elimina el fixture de una categoría (AJAX POST)
     */
    public function eliminar_fixture() {
        $id_categoria = $this->input->post('id_categoria');
        
        if (empty($id_categoria)) {
            echo json_encode([
                'success' => false,
                'message' => 'Categoría inválida'
            ]);
            return;
        }
        
        $resultado = $this->Fixture_model->eliminar_fixture($id_categoria);
        
        echo json_encode([
            'success' => $resultado,
            'message' => $resultado ? 'Fixture eliminado correctamente' : 'Error al eliminar'
        ]);
    }
    
    /**
     * Obtiene estadísticas de una categoría (AJAX)
     */
    public function get_estadisticas($id_categoria) {
        $estadisticas = $this->Fixture_model->obtener_estadisticas_categoria($id_categoria);
        
        echo json_encode([
            'success' => true,
            'datos' => $estadisticas
        ]);
    }
    
    /**
     * Obtiene los competidores (UTEs o participantes) de una categoría (AJAX)
     */
    public function get_competidores($id_categoria) {
        $this->db->select('c.*, d.modalidad');
        $this->db->from('categorias c');
        $this->db->join('deportes d', 'd.id_deporte = c.id_deporte');
        $this->db->where('c.id_categoria', $id_categoria);
        $categoria = $this->db->get()->row_array();
        
        if (!$categoria) {
            echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
            return;
        }
        
        if ($categoria['modalidad'] === 'EQUIPO' || $categoria['modalidad'] === 'AMBAS') {
            // Obtener UTEs
            $this->db->select('u.id_ute, u.nombre_ute, COUNT(i.id_inscripcion) as cantidad_integrantes');
            $this->db->from('utes u');
            $this->db->join('inscripciones_deportivas i', 'i.id_ute = u.id_ute');
            $this->db->where('u.id_categoria', $id_categoria);
            $this->db->group_by('u.id_ute');
            $competidores = $this->db->get()->result_array();
            $tipo = 'equipos';
        } else {
            // Obtener participantes individuales
            $this->db->select('p.id_participante, p.nombre_completo, p.delegacion');
            $this->db->from('participantes p');
            $this->db->join('inscripciones_deportivas i', 'i.id_participante = p.id_participante');
            $this->db->where('i.id_categoria', $id_categoria);
            $this->db->where('i.id_ute IS NULL', null, false);
            $competidores = $this->db->get()->result_array();
            $tipo = 'individuales';
        }
        
        echo json_encode([
            'success' => true,
            'competidores' => $competidores,
            'tipo' => $tipo,
            'modalidad' => $categoria['modalidad']
        ]);
    }
}

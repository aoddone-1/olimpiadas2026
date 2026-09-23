<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Delegado_controller
 *
 * Responsabilidad: panel del delegado de delegación (listado de los
 * inscriptos de su delegación y descarga de CSV propio).
 */
class Delegado_controller extends OLIM_Controller {

    public function panel_delegado() {
        if (!$this->session->userdata('is_delegado')) {
            redirect('Auth_controller/login');
        }
        
        $this->load->model('Participante_model');
        
        // Obtener la delegación del usuario logueado
        $user_nombre = $this->session->userdata('user_nombre');
        
        // Buscar todos los participantes de esa delegación (con es_competidor)
        $data['participantes'] = $this->Participante_model->obtener_participantes_por_delegacion_completo($user_nombre);
        $data['total_inscriptos'] = count($data['participantes']);
        $data['delegacion'] = $user_nombre;
        
        $this->load->view('admin/panel_delegado', $data);
    }
    
    /**
     * Descarga la lista de inscriptos en formato CSV
     */

    public function descargar_csv_inscriptos() {
        if (!$this->session->userdata('is_delegado')) {
            redirect('Auth_controller/login');
        }
        
        $this->load->model('Participante_model');
        
        // Obtener la delegación del usuario logueado
        $delegacion = $this->session->userdata('user_nombre');
        
        // Obtener datos para el CSV
        $datos = $this->Participante_model->obtener_participantes_para_csv($delegacion);
        
        // Nombre del archivo
        $nombre_archivo = 'inscriptos_' . str_replace(' ', '_', strtolower($delegacion)) . '_' . date('Y-m-d') . '.csv';
        
        // Configurar headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        
        // Crear el output
        $output = fopen('php://output', 'w');
        
        // Agregar BOM para que Excel reconozca UTF-8 correctamente
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Escribir encabezados con punto y coma como delimitador
        fputcsv($output, ['DNI', 'Nombre Completo', 'Sexo', 'Fecha de Nacimiento', 'Edad', 'Delegación', 'Deporte', 'Categoría'], ';');
        
        // Escribir datos con punto y coma como delimitador
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
}

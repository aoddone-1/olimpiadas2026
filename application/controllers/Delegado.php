<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Delegado — Panel y descargas propias del delegado de una delegación.
 *
 * Extracción del monolito `Inscripciones.php` (paso 3: arquitectura).
 */
class Delegado extends OLIM_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Participante_model');
    }

    /** Panel principal del delegado: información de su delegación. */
    public function panel() {
        if (!$this->_es_delegado()) {
            redirect('Auth/login');
        }

        $user_nombre = $this->session->userdata('user_nombre');

        $data['participantes']    = $this->Participante_model->obtener_participantes_por_delegacion_completo($user_nombre);
        $data['total_inscriptos'] = count($data['participantes']);
        $data['delegacion']       = $user_nombre;

        $this->load->view('admin/panel_delegado', $data);
    }

    /** CSV de los inscriptos de la delegación del usuario logueado. */
    public function csv_inscriptos() {
        if (!$this->_es_delegado()) {
            redirect('Auth/login');
        }

        $delegacion = $this->session->userdata('user_nombre');
        $datos = $this->Participante_model->obtener_participantes_para_csv($delegacion);
        $nombre_archivo = 'inscriptos_' . str_replace(' ', '_', strtolower((string) $delegacion)) . '_' . date('Y-m-d') . '.csv';

        $this->_enviar_csv($datos, $nombre_archivo);
    }

    /** CSV global de TODOS los inscriptos (todos los organizadores). */
    public function csv_todos() {
        if (!$this->_es_organizador()) {
            redirect('Auth/login');
        }

        $datos = $this->Participante_model->obtener_participantes_para_csv(NULL);
        $this->_enviar_csv($datos, 'inscriptos_' . date('Y-m-d') . '.csv');
    }

    /**
     * Escribe el CSV estándar de inscriptos (BOM UTF-8 + separador ';') y
     * finaliza la respuesta. Única implementación para todos los exports.
     */
    protected function _enviar_csv($datos, $nombre_archivo) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para Excel
        fputcsv($output, ['DNI', 'Nombre Completo', 'Sexo', 'Fecha de Nacimiento', 'Edad', 'Delegación', 'Deporte', 'Categoría'], ';');

        foreach ($datos as $fila) {
            fputcsv($output, [
                $fila['dni'],
                $fila['nombre_completo'],
                $fila['sexo'],
                $fila['fecha_nacimiento'],
                $fila['edad'],
                $fila['delegacion'],
                $fila['deporte'],
                $fila['categoria'],
            ], ';');
        }

        fclose($output);
        exit;
    }
}

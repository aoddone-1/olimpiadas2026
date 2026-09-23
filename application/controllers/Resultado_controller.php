<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Resultado_controller
 *
 * Responsabilidad: carga y consulta de resultados deportivos
 * (endpoints AJAX de la pestaña "Resultados" del Control Total).
 */
class Resultado_controller extends OLIM_Controller {

    public function ajax_guardar_resultado() {
        try {
            if (!$this->_resultados_auth_json()) return;

            if (!$this->Resultado_model->tablas_existentes()) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'ok' => false,
                        'error' => 'Faltan las tablas de resultados. Ejecutá el script sql/resultados.sql y recargá la página.'
                    )));
                return;
            }

            $datos = $this->input->post();
            $res = $this->Resultado_model->guardar_resultado($datos, $this->session->userdata('user_id'));
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'ok' => true,
                    'id_resultado' => $res['id_resultado'],
                    'fixture_inferido' => !empty($res['fixture_inferido']),
                    'mensaje' => !empty($res['fixture_inferido'])
                        ? 'Resultado guardado y vinculado automáticamente al partido del fixture.'
                        : 'Resultado guardado.'
                )));
        } catch (Throwable $e) {
            log_message('error', '[Resultados] ' . $e->getMessage());
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }

    /** Lista TODOS los resultados cargados (con su detalle). */

    public function ajax_resultados_todo() {
        try {
            if (!$this->_resultados_auth_json()) return;

            if (!$this->Resultado_model->tablas_existentes()) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'ok' => false,
                        'error' => 'Faltan las tablas de resultados. Ejecutá el script sql/resultados.sql y recargá la página.'
                    )));
                return;
            }

            $rows = $this->Resultado_model->obtener_todos_los_resultados();
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => true, 'resultados' => $rows)));
        } catch (Throwable $e) {
            log_message('error', '[Resultados] ' . $e->getMessage());
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }

    /** Partidos del fixture de una categoría (para vincular el resultado a uno). */

    public function ajax_fixtures_por_categoria($id_categoria) {
        try {
            if (!$this->_resultados_auth_json()) return;

            $this->load->model('Resultado_model');
            $fixtures = $this->Resultado_model->obtener_fixtures_por_categoria((int) $id_categoria);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => true, 'fixtures' => $fixtures)));
        } catch (Throwable $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }

    /** Competidores (inscripciones personales + UTEs) de una categoría. */

    public function ajax_competidores_por_categoria($id_categoria) {
        try {
            if (!$this->_resultados_auth_json()) return;

            $competidores = $this->Resultado_model->obtener_competidores_por_categoria((int) $id_categoria);
            $respuesta = array('ok' => true, 'competidores' => $competidores);

            // Deporte MASIVO_TIEMPO: además de los inscriptos de la categoría,
            // se envían los participantes que compitieron en cada partido/jornada
            // del fixture (resueltos desde fixtures + inscripciones_deportivas),
            // para autocompletar la planilla al seleccionar la jornada.
            if ($this->Resultado_model->modalidad_de_categoria((int) $id_categoria) === 'MASIVO_TIEMPO') {
                $por_jornada = array();
                foreach ($this->Resultado_model->obtener_fixtures_por_categoria((int) $id_categoria) as $f) {
                    $por_jornada[(int) $f['id_fixture']] = isset($f['competidores']) ? $f['competidores'] : array();
                }
                $respuesta['participantes_por_fixture'] = $por_jornada;
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($respuesta));
        } catch (Throwable $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }

    /** Elimina un resultado cargado por error. */

    public function ajax_eliminar_resultado() {
        try {
            if (!$this->_resultados_auth_json()) return;

            $id = (int) $this->input->post('id_resultado');
            if (!$id) throw new Exception('Resultado inexistente.');
            $this->Resultado_model->eliminar_resultado($id);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => true, 'mensaje' => 'Resultado eliminado.')));
        } catch (Throwable $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }
}

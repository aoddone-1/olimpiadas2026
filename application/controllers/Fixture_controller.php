<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fixture_controller
 *
 * Responsabilidad: generación y gestión del fixture/cronograma de partidos
 * (endpoints AJAX de la pestaña "Fixture" del Control Total).
 */
class Fixture_controller extends OLIM_Controller {

    public function ajax_fixture_categoria($id_categoria) {
        if (!$this->_fixture_auth_json()) return;

        try {
            $fixtures = $this->Fixture_model->obtener_fixtures_por_categoria((int) $id_categoria);
            $utes = $this->Fixture_model->obtener_utes_por_categoria((int) $id_categoria);
        } catch (Throwable $e) {
            $this->_fixture_error_json('Error al consultar el fixture de la categoría.', $e->getMessage());
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('ok' => true, 'fixtures' => $fixtures, 'utes' => $utes)));
    }

    /** Devuelve TODO el fixture de todas las categorías (vista general sin filtros). */

    public function ajax_fixture_todo() {
        if (!$this->_fixture_auth_json()) return;

        try {
            // Auto-crear la jornada de los deportes masivos que aún no tienen
            // fixture, para que siempre se puedan cargar resultados.
            $fixtures = $this->Fixture_model->obtener_todo_el_fixture();
        } catch (Throwable $e) {
            $this->_fixture_error_json('Error al consultar la tabla fixtures.', $e->getMessage());
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('ok' => true, 'fixtures' => $fixtures)));
    }

    /** Genera automáticamente el fixture de una categoría. */

    public function ajax_generar_fixture() {
        if (!$this->_fixture_auth_json()) return;

        $id_categoria = (int) $this->input->post('id_categoria');
        try {
            $cantidad = $this->Fixture_model->generar_fixture_para_categoria($id_categoria);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'ok' => true,
                    'mensaje' => "Fixture generado: {$cantidad} partido(s)/jornada(s)."
                )));
        } catch (Throwable $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }

    /** Crear/editar un partido manualmente. */

    public function ajax_guardar_partido() {
        // Captura cualquier error fatal del modelo para SIEMPRE responder JSON
        // (nunca un HTTP 500 con HTML que el JS no puede parsear).
        try {
            if (!$this->_fixture_auth_json()) return;

            $datos = $this->input->post();
            if (empty($datos['id_categoria']) || empty($datos['nombre_prueba'])
                || empty($datos['fecha_competencia']) || empty($datos['hora_inicio']) || empty($datos['hora_fin'])) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode(array('ok' => false, 'error' => 'Completá todos los campos obligatorios.')));
                return;
            }

            $id = $this->Fixture_model->guardar_partido($datos);
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(array('ok' => true, 'id_fixture' => $id, 'mensaje' => 'Partido guardado.')));
        } catch (Throwable $e) {
            $this->_fixture_error_json('No se pudo guardar el partido.', $e->getMessage());
        }
    }

    /** Registrar ganador de un partido (clasifica a la siguiente fase). */

    public function ajax_resultado_partido() {
        if (!$this->_fixture_auth_json()) return;

        $id_fixture = (int) $this->input->post('id_fixture');
        $id_ganador = (int) $this->input->post('id_ganador');

        try {
            $mensaje = $this->Fixture_model->registrar_resultado($id_fixture, $id_ganador);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => true, 'mensaje' => $mensaje)));
        } catch (Throwable $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }

    /** Registrar resultado de un deporte masivo (JORNADA_UNICA): orden de llegada. */

    public function ajax_resultado_masivo() {
        if (!$this->_fixture_auth_json()) return;

        $id_fixture = (int) $this->input->post('id_fixture');
        $ute_ids = $this->input->post('ute_ids'); // array en el orden elegido

        try {
            $mensaje = $this->Fixture_model->registrar_resultado_masivo($id_fixture, (array) $ute_ids);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => true, 'mensaje' => $mensaje)));
        } catch (Throwable $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }

    /** Borrar todo el fixture de una categoría. */

    public function ajax_eliminar_fixture() {
        if (!$this->_fixture_auth_json()) return;

        $id_categoria = (int) $this->input->post('id_categoria');
        $this->Fixture_model->eliminar_fixture_por_categoria($id_categoria);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('ok' => true, 'mensaje' => 'Fixture eliminado.')));
    }

    /** Borrar un partido individual. */

    public function ajax_eliminar_partido() {
        if (!$this->_fixture_auth_json()) return;

        $id_fixture = (int) $this->input->post('id_fixture');
        $this->Fixture_model->eliminar_partido($id_fixture);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('ok' => true, 'mensaje' => 'Partido eliminado.')));
    }

    /* ============================================================
     *  RESULTADOS (pestaña de Control Total) - endpoints AJAX
     * ============================================================ */
}

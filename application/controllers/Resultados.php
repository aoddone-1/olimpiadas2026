<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Resultados — Endpoints AJAX de carga y consulta de resultados deportivos.
 *
 * Extracción del monolito `Inscripciones.php` (paso 3: arquitectura).
 * Usa el gate único _auth_admin_json() heredado de OLIM_Controller
 * (reemplaza al antiguo _resultados_auth_json duplicado).
 */
class Resultados extends OLIM_Controller {

    private $mensaje_tablas = 'Faltan las tablas de resultados. Ejecutá el script sql/resultados.sql y recargá la página.';

    public function __construct() {
        parent::__construct();
        $this->load->model('Resultado_model');
    }

    /** Guarda un resultado cargado desde la pestaña "Resultados". */
    public function ajax_guardar() {
        try {
            if (!$this->_auth_admin_json()) return;

            if (!$this->Resultado_model->tablas_existentes()) {
                $this->_json(['ok' => false, 'error' => $this->mensaje_tablas]);
                return;
            }

            $datos = $this->input->post();
            $res = $this->Resultado_model->guardar_resultado($datos, $this->session->userdata('user_id'));
            $this->_json([
                'ok'                => true,
                'id_resultado'      => $res['id_resultado'],
                'fixture_inferido'  => !empty($res['fixture_inferido']),
                'mensaje'           => !empty($res['fixture_inferido'])
                    ? 'Resultado guardado y vinculado automáticamente al partido del fixture.'
                    : 'Resultado guardado.',
            ]);
        } catch (Throwable $e) {
            $this->_json_error('Resultados', $e->getMessage());
        }
    }

    /** Lista TODOS los resultados cargados (con su detalle). */
    public function ajax_todo() {
        try {
            if (!$this->_auth_admin_json()) return;

            if (!$this->Resultado_model->tablas_existentes()) {
                $this->_json(['ok' => false, 'error' => $this->mensaje_tablas]);
                return;
            }

            $rows = $this->Resultado_model->obtener_todos_los_resultados();
            $this->_json(['ok' => true, 'resultados' => $rows]);
        } catch (Throwable $e) {
            $this->_json_error('Resultados', $e->getMessage());
        }
    }

    /** Partidos del fixture de una categoría (para vincular el resultado a uno). */
    public function ajax_fixtures_por_categoria($id_categoria) {
        try {
            if (!$this->_auth_admin_json()) return;

            $fixtures = $this->Resultado_model->obtener_fixtures_por_categoria((int) $id_categoria);
            $this->_json(['ok' => true, 'fixtures' => $fixtures]);
        } catch (Throwable $e) {
            $this->_json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /** Competidores (inscripciones personales + UTEs) de una categoría. */
    public function ajax_competidores_por_categoria($id_categoria) {
        try {
            if (!$this->_auth_admin_json()) return;

            $id_categoria = (int) $id_categoria;
            $competidores = $this->Resultado_model->obtener_competidores_por_categoria($id_categoria);
            $respuesta = ['ok' => true, 'competidores' => $competidores];

            // Deporte MASIVO_TIEMPO: además de los inscriptos de la categoría,
            // se envían los participantes que compitieron en cada partido/jornada
            // del fixture, para autocompletar la planilla al seleccionar la jornada.
            if ($this->Resultado_model->modalidad_de_categoria($id_categoria) === 'MASIVO_TIEMPO') {
                $por_jornada = [];
                foreach ($this->Resultado_model->obtener_fixtures_por_categoria($id_categoria) as $f) {
                    $por_jornada[(int) $f['id_fixture']] = isset($f['competidores']) ? $f['competidores'] : [];
                }
                $respuesta['participantes_por_fixture'] = $por_jornada;
            }

            $this->_json($respuesta);
        } catch (Throwable $e) {
            $this->_json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /** Elimina un resultado cargado por error. */
    public function ajax_eliminar() {
        try {
            if (!$this->_auth_admin_json()) return;

            $id = (int) $this->input->post('id_resultado');
            if (!$id) throw new Exception('Resultado inexistente.');
            $this->Resultado_model->eliminar_resultado($id);
            $this->_json(['ok' => true, 'mensaje' => 'Resultado eliminado.']);
        } catch (Throwable $e) {
            $this->_json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PUBLICO — Panel público de las Olimpíadas (sin login ni contraseña).
 *
 * Pensado para proyectarse en una pantalla del evento o consultarse desde el
 * celular: muestra el resumen de las olimpíadas, el fixture, los resultados y
 * el medallero. Si se selecciona una DELEGACIÓN, todo se filtra a lo de esa
 * delegación únicamente.
 *
 * Diseño seguro:
 *  - Controlador NUEVO e independiente: no reutiliza Inscripciones ni su sesión.
 *  - Solo métodos GET/lectura: no existe ninguna ruta de escritura aquí.
 *  - No expone datos personales sensibles (DNI, email, teléfono): se muestran
 *    equipos/pruebas, no personas.
 */
class Publico extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Publico_model');
        // Cache corto: los datos cambian durante la semana, pero no hace falta
        // recargar en cada segundo.
        $this->output->set_header('Cache-Control: max-age=30');
    }

    /** Panel principal: resumen + pestañas Fixture / Resultados / Medallero. */
    public function index() {
        $delegacion = $this->_delegacion_filtro();
        $id_deporte = $this->_deporte_filtro();

        // Cada consulta va envuelta en try/catch: si falta una tabla/columna
        // (migraciones no aplicadas) el panel se muestra igual, con esa sección
        // vacía y el error real registrado en application/logs/.
        $data = array(
            'titulo_evento'  => NOMBRE_META,
            'nombre_sitio'   => NOMBRE_SITIO,
            'lugar_olimpico' => LUGAR_OLIMPICO2,
            'resumen'        => $this->_seguro(fn() => $this->Publico_model->obtener_resumen_evento(), array(
                'delegaciones' => 0, 'deportes' => 0, 'categorias' => 0,
                'partidos' => 0, 'finalizados' => 0, 'resultados' => 0, 'premiados' => 0,
            )),
            'delegaciones'   => $this->_seguro(fn() => $this->Publico_model->obtener_delegaciones(), array()),
            'deportes'       => $this->_seguro(fn() => $this->Publico_model->obtener_deportes_con_categorias(), array()),
            'delegacion'     => $delegacion,
            'id_deporte'     => $id_deporte,
            'fixture'        => $this->_seguro(fn() => $this->Publico_model->obtener_fixture_publico($delegacion, $id_deporte), array()),
            'resultados'     => $this->_seguro(fn() => $this->Publico_model->obtener_resultados_publicos($delegacion, $id_deporte), array()),
            'medallero'      => $this->_seguro(fn() => $this->Publico_model->obtener_medallero(), array()),
            'ahora'          => date('d/m/Y H:i'),
        );

        $this->load->view('public/panel_publico', $data);
    }

    /** Ejecuta una consulta del modelo; ante cualquier error devuelve $fallback. */
    private function _seguro(callable $fn, $fallback) {
        try {
            $r = $fn();
            return $r === null ? $fallback : $r;
        } catch (Throwable $e) {
            log_message('error', 'Publico::index — ' . $e->getMessage());
            return $fallback;
        }
    }

    /** JSON del fixture (para auto-refresh sin recargar la página). */
    public function fixture_json() {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok' => true,
                'fixture' => $this->Publico_model->obtener_fixture_publico(
                    $this->_delegacion_filtro(), $this->_deporte_filtro()
                ),
            )));
    }

    /** JSON de resultados. */
    public function resultados_json() {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok' => true,
                'resultados' => $this->Publico_model->obtener_resultados_publicos(
                    $this->_delegacion_filtro(), $this->_deporte_filtro()
                ),
            )));
    }

    /** JSON del medallero. */
    public function medallero_json() {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok' => true,
                'medallero' => $this->Publico_model->obtener_medallero(),
            )));
    }

    /* ============================================================
     *  Helpers de filtros (GET sanitizados)
     * ============================================================ */

    private function _delegacion_filtro() {
        $delegacion = trim((string) $this->input->get('delegacion'));
        if ($delegacion === '') return null;
        try {
            // Validar que sea una delegación real (evita inyección de texto
            // arbitrary en el HTML y consultas inútiles).
            $reales = $this->Publico_model->obtener_delegaciones();
            return in_array($delegacion, $reales, true) ? $delegacion : null;
        } catch (Throwable $e) {
            log_message('error', 'Publico::_delegacion_filtro — ' . $e->getMessage());
            return null;
        }
    }

    private function _deporte_filtro() {
        $id_deporte = (int) $this->input->get('deporte');
        return $id_deporte > 0 ? $id_deporte : null;
    }
}

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
        // Filtros por URL LIMPIA (segmentos), p. ej.:
        //   /publico/delegacion/formosa/deporte/13/categoria/56/dia/2026-11-04/buscar/futbol
        // Antes se usaba query string y la barra quedaba ilegible
        // (/publico?delegacion=Formosa&deporte=13&...&q=#tab-fixture).
        $segmentos = $this->uri->uri_segments();
        $filtros_url = $this->_filtros_por_segmentos($segmentos);

        $delegacion   = $filtros_url['delegacion'];
        $id_deporte   = $filtros_url['deporte'];
        $id_categoria = $filtros_url['categoria'];

        // Cada consulta va envuelta en try/catch: si falta una tabla/columna
        // (migraciones no aplicadas) el panel se muestra igual, con esa sección
        // vacía y el error real registrado en application/logs/.
        $fixture = $this->_seguro(fn() => $this->Publico_model->obtener_fixture_publico($delegacion, $id_deporte, $id_categoria), array());

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
            'id_categoria'   => $id_categoria,
            'fixture'        => $fixture,
            // Resultados: SIEMPRE se pide sin filtro de delegación y se filtra acá,
            // solo sobre los partidos que efectivamente están en $fixture. Así las
            // dos pestañas nunca discrepan (antes, con delegación elegida, Resultados
            // podía quedar vacío mientras el Fixture mostraba partidos).
            'resultados'     => $this->_resultados_del_fixture($delegacion, $id_deporte, $id_categoria, $fixture),
            'medallero'      => $this->_seguro(fn() => $this->Publico_model->obtener_medallero(), array()),
            'ahora'          => date('d/m/Y H:i'),
            // Segmentos "informativos" de la URL limpia (los lee la vista para
            // saber qué día/búsqueda/disciplina mostrar y para armar enlaces).
            'dia_url'        => $filtros_url['dia'],
            'buscar_url'     => $filtros_url['buscar'],
            'disciplina_url' => $filtros_url['disciplina'],
            'delegacion_url' => $delegacion,
            'deporte_url'    => $id_deporte !== null ? (string) $id_deporte : '',
            'categoria_url'  => $id_categoria !== null ? (string) $id_categoria : '',
        );

        $this->load->view('public/panel_publico', $data);
    }

    /**
     * Convierte los segmentos pares clave/valor de la URL en filtros listos.
     * Delegación: se valida contra las delegaciones reales comparando la forma
     * normalizada (así "formosa", "Formosa" o "San Miguel de Tucumán" funcionan
     * indistintamente). Deporte/Categoría: enteros. Día/Buscar/Disciplina: texto.
     */
    private function _filtros_por_segmentos($parts) {
        $vals = array();
        $n = is_array($parts) ? count($parts) : 0;
        for ($i = 0; $i < $n - 1; $i++) {
            $k = strtolower(trim((string) $parts[$i]));
            if ($k === '') continue;
            $v = trim((string) $parts[$i + 1]);
            // Un valor nunca debe contener "/" (vendría de otra ruta): lo ignoramos.
            if ($v === '' || strpos($v, '/') !== FALSE) continue;
            $v = rawurldecode($v);
            if (!isset($vals[$k])) $vals[$k] = $v;
            $i++; // consume el valor
        }

        $norm = function ($txt) {
            $t = mb_strtolower(trim((string) $txt), 'UTF-8');
            if (function_exists('iconv')) {
                $deg = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
                if ($deg !== false) $t = $deg;
            }
            $t = preg_replace('/[^a-z0-9]+/', '-', $t);
            return trim($t, '-');
        };

        // --- Delegación (nombre legible en la URL) ---
        $delegacion = null;
        if (!empty($vals['delegacion'])) {
            $pedido = trim((string) $vals['delegacion']);
            $reales = $this->_seguro(fn() => $this->Publico_model->obtener_delegaciones(), array());
            foreach ($reales as $real) {
                if (strcasecmp($pedido, $real) === 0) { $delegacion = $real; break; }
            }
            if ($delegacion === null) {
                $np = $norm($pedido);
                foreach ($reales as $real) {
                    if ($np !== '' && $np === $norm($real)) { $delegacion = $real; break; }
                }
            }
            if ($delegacion === null) {
                log_message('error', 'Publico::_filtros_por_segmentos — delegación desconocida: ' . $pedido);
            }
        }

        // --- Deporte / Categoría (ids numéricos) ---
        $id_deporte   = isset($vals['deporte'])   && (int) $vals['deporte'] > 0   ? (int) $vals['deporte']   : null;
        $id_categoria = isset($vals['categoria']) && (int) $vals['categoria'] > 0 ? (int) $vals['categoria'] : null;

        return array(
            'delegacion' => $delegacion,
            'deporte'    => $id_deporte,
            'categoria'  => $id_categoria,
            'dia'        => isset($vals['dia']) ? $vals['dia'] : '',
            'buscar'     => isset($vals['buscar']) ? $vals['buscar'] : '',
            'disciplina' => isset($vals['disciplina']) ? $vals['disciplina'] : '',
        );
    }

    /**
     * Resultados coherentes con el fixture mostrado: se traen todos (filtrados
     * por deporte/categoría en SQL) y se quedan solo los vinculados a un partido
     * del fixture ya filtrado por delegación. Si el vínculo id_fixture está
     * ausente (datos históricos), se conserva el comportamiento antiguo.
     */
    private function _resultados_del_fixture($delegacion, $id_deporte, $id_categoria, $fixture) {
        $todos = $this->_seguro(fn() => $this->Publico_model->obtener_resultados_publicos(null, $id_deporte, $id_categoria), array());
        if (!$todos) return array();

        $ids_fixture = array();
        foreach ($fixture as $fx) {
            if (isset($fx['id_fixture'])) $ids_fixture[(int) $fx['id_fixture']] = true;
        }
        // Sin fixture (delegación sin partidos, o fixture vacío) no tiene sentido
        // mostrar resultados "sueltos": la pestaña queda vacía con su mensaje.
        if (!$ids_fixture && !empty($delegacion)) return array();

        $fuera = $this->_seguro(fn() => $this->Publico_model->obtener_resultados_publicos($delegacion, $id_deporte, $id_categoria), array());
        $ids_delegacion = array();
        foreach ($fuera as $r) {
            if (isset($r['id_resultado'])) $ids_delegacion[(int) $r['id_resultado']] = true;
        }

        $salida = array();
        foreach ($todos as $r) {
            $idf = isset($r['id_fixture']) ? (int) $r['id_fixture'] : 0;
            if ($idf > 0) {
                if (!isset($ids_fixture[$idf])) continue;              // no está en el fixture visible
                if ($delegacion !== null && !isset($ids_delegacion[(int) $r['id_resultado']])) continue;
            } elseif ($delegacion !== null) {
                if (!isset($ids_delegacion[(int) $r['id_resultado']])) continue;
            }
            $salida[] = $r;
        }
        return $salida;
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

    private function _categoria_filtro() {
        $id_categoria = (int) $this->input->get('categoria');
        return $id_categoria > 0 ? $id_categoria : null;
    }
}

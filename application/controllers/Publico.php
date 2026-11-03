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
        // Solo LECTURA: la Premiación resuelve los podios de los deportes de
        // enfrentamiento (FINAL / 3er puesto / tabla de puntos) para mostrarlos
        // en la pestaña Resultados del panel público.
        $this->load->model('Premiacion_model');
        // Cache corto: los datos cambian durante la semana, pero no hace falta
        // recargar en cada segundo.
        $this->output->set_header('Cache-Control: max-age=30');
    }

    /** Panel principal: resumen + pestañas Fixture / Resultados / Medallero. */
    public function index() {
        // ===== URL SIEMPRE limpia: "/publico" y nada más =====
        // Los filtros de servidor (Delegación / Deporte / Categoría) llegan por
        // POST desde el formulario del panel: viajan en el cuerpo de la petición,
        // no en la barra de direcciones. Día y búsqueda se aplican en el cliente
        // (el fixture completo se renderiza oculto y el JS muestra lo que toca),
        // así que tampoco aparecen nunca en la URL.
        // "Limpiar filtros" (botón del formulario): ignora todos los filtros.
        $limpiar = (string) $this->input->post('limpiar');
        if ($limpiar !== '') {
            $delegacion = $id_deporte = $id_categoria = null;
        } else {
            $delegacion   = $this->_delegacion_filtro();
            $id_deporte   = $this->_deporte_filtro();
            $id_categoria = $this->_categoria_filtro();
        }

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
            // Podios de los deportes de ENFRENTAMIENTO (MARCADOR): mismo podio
            // que ve el staff en Premiación, agrupado por deporte/categoría y
            // con todos los partidos cargados (incluidos los anteriores a la
            // final). Se filtra acá por delegación/deporte/categoría.
            'podios'         => $this->_podios_visibles($delegacion, $id_deporte, $id_categoria),
            'medallero'      => $this->_seguro(fn() => $this->Publico_model->obtener_medallero(), array()),
            'ahora'          => date('d/m/Y H:i'),
            // Informativos para la vista (sin filtros de URL: todo por POST/cliente).
            'delegacion_url' => $delegacion,
            'deporte_url'    => $id_deporte !== null ? (string) $id_deporte : '',
            'categoria_url'  => $id_categoria !== null ? (string) $id_categoria : '',
        );

        $this->load->view('public/panel_publico', $data);
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

    /**
     * Podios de enfrentamiento visibles en la pestaña Resultados, aplicando los
     * mismos filtros del panel:
     *  - Deporte / Categoría: por ids directos.
     *  - Delegación: se conservan TODAS las categorías del filtro (así el podio
     *    se ve completo), pero dentro de cada una solo los partidos en los que
     *    juega esa delegación; si el podio no la incluye y ninguno de sus
     *    partidos es suyo, la categoría desaparece de la lista.
     * Ante cualquier falla (tablas sin migrar, etc.) devuelve array vacío: la
     * pestaña Results sigue funcionando con las cards simples de siempre.
     */
    private function _podios_visibles($delegacion, $id_deporte, $id_categoria) {
        // Enfrentamientos (marcador) + deportes de tiempo/puntaje masivo: así
        // el panel puede agrupar TODO por deporte con sus categorías adentro.
        $podios = $this->_seguro(fn() => $this->Premiacion_model->podios_para_panel_publico(), array());
        $masivos = $this->_seguro(fn() => $this->Premiacion_model->podios_masivos_para_panel_publico(), array());
        if (is_array($masivos) && $masivos) {
            $podios = array_merge(is_array($podios) ? $podios : array(), $masivos);
        }
        if (!is_array($podios) || !$podios) return array();

        $comp = null;
        if (!empty($delegacion)) {
            $comp = $this->_seguro(fn() => $this->Publico_model->competidores_de_delegacion($delegacion), null);
        }

        $salida = array();
        foreach ($podios as $p) {
            if ($id_deporte !== null && (int) $p['id_deporte'] !== (int) $id_deporte) continue;
            if ($id_categoria !== null && (int) $p['id_categoria'] !== (int) $id_categoria) continue;

            if ($comp !== null) {
                $partidos = array();
                $es_propio = false;
                foreach ($p['partidos'] as $pt) {
                    $suyo = $this->_partido_es_de_delegacion($pt, $comp);
                    if ($suyo) {
                        $es_propio = true;
                        $partidos[] = $pt;
                    }
                }
                // Categorías masivas: la delegación participa si tiene
                // inscriptos en la categoría; se filtran las jornadas para
                // mostrar solo las posiciones de SUS competidores.
                if (!empty($p['masivo'])) {
                    $id_cat_p = (int) $p['id_categoria'];
                    if (isset($comp['categorias'][$id_cat_p])) $es_propio = true;
                    $nombres = $this->_nombres_de_delegacion($delegacion, $comp);
                    $jornadas = array();
                    foreach (isset($p['jornadas']) ? $p['jornadas'] : array() as $j) {
                        $filas = array();
                        foreach ($j['filas'] as $f) {
                            if ($this->_fila_es_de_delegacion((string) $f['nombre'], $nombres, $comp)) {
                                $filas[] = $f;
                            }
                        }
                        if ($filas) {
                            $j['filas'] = $filas;
                            $jornadas[] = $j;
                        }
                    }
                    if ($jornadas) $p['jornadas'] = $jornadas;
                }
                // El podio también "pertenece" a la delegación si alguno de los
                // premiados es un equipo suyo (aunque el partido decisivo se
                // muestre igual para todos).
                foreach ($p['podium'] as $puesto) {
                    if (!$es_propio && $this->_nombre_podio_es_de_delegacion((string) $puesto['nombre'], $comp)) {
                        $es_propio = true;
                    }
                }
                if (!$es_propio) continue;
                $p['partidos'] = $partidos;
            }
            $salida[] = $p;
        }

        // Orden final: deporte (A-Z) y dentro del deporte las categorías
        // cerradas primero — la vista agrupa en este mismo orden.
        usort($salida, function ($a, $b) {
            $cmp = strcasecmp((string) $a['deporte'], (string) $b['deporte']);
            if ($cmp !== 0) return $cmp;
            if (!empty($a['cerrada']) !== !empty($b['cerrada'])) return !empty($a['cerrada']) ? -1 : 1;
            return strcasecmp((string) $a['categoria'], (string) $b['categoria']);
        });
        return $salida;
    }

    /** ¿Una fila de planilla masiva es de la delegación? (nombres normalizados) */
    private function _fila_es_de_delegacion($nombre, $nombres_propios, $comp) {
        $nn = $this->Publico_model->normalizar_nombre($nombre);
        if ($nn !== '' && isset($nombres_propios[$nn])) return true;
        // Los nombres masivos suelen traer sufijo "— Delegación": alcanza con
        // que el nombre contenga el nombre normalizado de un equipo/UTE propio.
        foreach ($nombres_propios as $n => $_v) {
            if ($n !== '' && strlen($n) >= 4 && strpos($nn, $n) !== false) return true;
        }
        return false;
    }

    /** Nombres normalizados de los equipos/inscriptos de una delegación. */
    private function _nombres_de_delegacion($delegacion, $comp) {
        $out = array();
        // UTEs/equipos de la delegación -> su nombre desde `utes`.
        $ids_ute = array();
        foreach (array_keys(isset($comp['utes']) ? $comp['utes'] : array()) as $idl) {
            if ($idl > 0) $ids_ute[] = (int) $idl;
        }
        if ($ids_ute) {
            $nombres = $this->_seguro(fn() => $this->Publico_model->_nombres_utes_por_ids($ids_ute), array());
            foreach ($nombres as $nom) {
                $nn = $this->Publico_model->normalizar_nombre((string) $nom);
                if ($nn !== '') $out[$nn] = true;
            }
        }
        // Individuales inscriptos: se resuelven por -id_inscripcion en el
        // snapshot del detalle; acá se usa la delegación como clave de match.
        $nn_del = $this->Publico_model->normalizar_nombre((string) $delegacion);
        if ($nn_del !== '') $out[$nn_del] = true;
        return $out;
    }

    /** ¿El partido (fila de `podios_para_panel_publico`) es de la delegación? */
    private function _partido_es_de_delegacion($pt, $comp) {
        foreach (array('eq1' => (int) ($pt['eq1_id'] ?? 0), 'eq2' => (int) ($pt['eq2_id'] ?? 0)) as $idl) {
            if ($idl > 0 && isset($comp['utes'][$idl])) return true;
            if ($idl < 0 && isset($comp['inscripciones'][abs($idl)])) return true;
        }
        $nombres = array((string) ($pt['eq1_nombre'] ?? ''), (string) ($pt['eq2_nombre'] ?? ''));
        foreach ($nombres as $n) {
            $nn = $this->Publico_model->normalizar_nombre($n);
            if ($nn !== '' && isset($comp['nombres'][$nn])) return true;
        }
        return false;
    }

    /** ¿El nombre de un puesto del podio pertenece a un equipo de la delegación? */
    private function _nombre_podio_es_de_delegacion($nombre, $comp) {
        $nn = $this->Publico_model->normalizar_nombre($nombre);
        return $nn !== '' && isset($comp['nombres'][$nn]);
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
     *  Los endpoints JSON siguen aceptando ?delegacion=..., pero el panel
     *  ahora usa URLs limpias por segmentos: se reutiliza el mismo parser
     *  de index() para no duplicar la validación de delegaciones reales.
     * ============================================================ */

    /** Los filtros del panel llegan por POST (body de la petición), nunca por
     *  la URL. input->post() devuelve NULL si la petición fue GET normal. */
    private function _delegacion_filtro() {
        $delegacion = trim((string) $this->input->post('delegacion'));
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
        $id_deporte = (int) $this->input->post('deporte');
        return $id_deporte > 0 ? $id_deporte : null;
    }

    private function _categoria_filtro() {
        $id_categoria = (int) $this->input->post('categoria');
        return $id_categoria > 0 ? $id_categoria : null;
    }
}

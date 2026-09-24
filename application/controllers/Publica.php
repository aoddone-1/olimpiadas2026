<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Publica — Inscripción pública (formulario, guardado y acreditación por QR).
 *
 * Extracción del monolito `Inscripciones.php` (paso 3: arquitectura).
 * Todas las URLs originales (`Inscripciones/...`) siguen funcionando gracias
 * a los re-mapeos de config/routes.php.
 */
class Publica extends OLIM_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Participante_model');
    }

    /** Formulario público de inscripción. */
    public function index() {
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        $this->load->view('formulario_inscripcion', $data);
    }

    /** Alias histórico del formulario. */
    public function formulario() {
        $this->index();
    }

    /** AJAX: categorías disponibles de un deporte. */
    public function categorias($id_deporte) {
        $categorias = $this->Categoria_model->get_by_deporte((int) $id_deporte);
        $this->_json($categorias);
    }

    /**
     * Guarda una inscripción pública (nueva o actualización por DNI).
     */
    public function guardar() {
        $this->load->library('form_validation');
        $this->form_validation->set_rules($this->_reglas_validacion_inscripcion());

        $post = $this->input->post();

        // Validación de entrada server-side (nunca confiar solo en el HTML)
        if ($this->form_validation->run() === FALSE) {
            $this->load->view('inscripcion_erronea', [
                'mensaje' => validation_errors('<p>', '</p>') ?: 'Revisá los campos obligatorios del formulario.',
                'dni'     => htmlspecialchars($post['dni'] ?? '', ENT_QUOTES, 'UTF-8'),
            ]);
            return;
        }

        $dni = $this->_limpiar($post['dni'] ?? '');

        // Verificar si el participante ya existe (permite actualizar la inscripción)
        $existente = $this->Participante_model->buscar_por_dni($dni);
        $existe = !empty($existente);

        if ($existe) {
            $id_participante = $existente['id_participante'];
            $token = $existente['token_qr'];
        } else {
            // Token criptográficamente seguro e impredecible
            $token = bin2hex(random_bytes(32));
        }

        // Disciplinas + validación de negocio (cupos, duplicados, coherencia)
        $proc = $this->_procesar_disciplinas($post, $existe ? $id_participante : NULL);
        if (!$proc['ok']) {
            $this->load->view('inscripcion_erronea', [
                'mensaje' => implode('<br>', $proc['errores']),
                'dni'     => htmlspecialchars($dni, ENT_QUOTES, 'UTF-8'),
            ]);
            return;
        }

        $data_persona = $this->_normalizar_persona($post);

        // Ejecución en Base de Datos (transacción atómica dentro del modelo)
        if ($existe) {
            $resultado = $this->Participante_model->actualizar_completo(
                $id_participante,
                $data_persona,
                $proc['disciplinas']
            );
        } else {
            $data_persona['dni'] = $dni;
            $data_persona['fecha_inscripcion'] = date('Y-m-d H:i:s');
            $data_persona['token_qr'] = $token;
            $resultado = $this->Participante_model->insertar_completo(
                $data_persona,
                $proc['disciplinas']
            );
        }

        if (!$resultado) {
            $db_error = $this->db->error();
            $mensaje = 'Ocurrió un error al guardar la inscripción. Volvé a intentarlo o contactá a la organización.';

            // 1062 = duplicate key: el UNIQUE(dni) de la BD actúa como red de seguridad
            if (isset($db_error['code']) && (int) $db_error['code'] === 1062) {
                $mensaje = 'El DNI <strong>' . htmlspecialchars($dni, ENT_QUOTES, 'UTF-8') . '</strong> ya se encuentra registrado.';
            } elseif (isset($db_error['code']) && in_array((int) $db_error['code'], [1452, 1406], TRUE)) {
                $mensaje = 'Los datos enviados no son válidos para alguna de las relaciones. Revisá las categorías seleccionadas.';
            }

            log_message('error', 'Fallo al guardar inscripción DNI=' . $dni . ' error=' . json_encode($db_error));

            $this->load->view('inscripcion_erronea', [
                'mensaje' => $mensaje,
                'dni'     => htmlspecialchars($dni, ENT_QUOTES, 'UTF-8'),
            ]);
        } else {
            $this->load->view('inscripcion_exitosa', [
                'delegacion' => $data_persona['delegacion'],
                'nombre'     => $data_persona['nombre_completo'],
                'token'      => $token,
            ]);
        }
    }

    /** Pantalla que abre el código QR (pública o panel de acreditación si hay staff). */
    public function acreditacion($token = NULL) {
        if (!$token) { show_404(); }

        $participante = $this->Participante_model->obtener_por_token($token);

        if (!$participante) {
            echo "<h3>Código QR inválido.</h3>";
            return;
        }

        $data['participante'] = $participante;
        $data['deportes'] = $this->Participante_model->obtener_deportes_inscriptos($participante['id_participante']);

        if ($this->_es_organizador()) {
            // PANTALLA PRO: la organización ve las opciones de deportes
            $this->load->view('admin/panel_acreditacion', $data);
        } else {
            // PANTALLA PÚBLICA: el participante escanea su propio QR.
            // Guardamos a qué QR quería ir, por si el staff inicia sesión desde acá.
            $this->session->set_userdata('url_retorno_qr', 'Publica/acreditacion/' . $token);
            $this->load->view('public/pase_valido', $data);
        }
    }

    /** PDF del Deslinde de Responsabilidad. */
    public function deslinde($token = NULL) {
        if (!$token) { show_404(); return; }

        $participante = $this->Participante_model->obtener_por_token($token);
        if (!$participante) { show_404(); return; }

        $this->load->view('deslinde_resp_view', ['participante' => $participante]);
    }

    /* ============================================================
     *  Lógica compartida con el backend de inscripciones
     * ============================================================
     * _reglas_validacion_inscripcion(), _procesar_disciplinas() y
     * _normalizar_persona() viven ahora en traits reutilizables.
     */

    use Traits_Inscripcion_Reglas;
    use Traits_Inscripcion_Disciplinas;
    use Traits_Inscripcion_Persona;
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Backend — Administración del staff: inscripción de participantes, gestión
 * de deportes/categorías/predios, acreditación y control total.
 *
 * Extracción del monolito `Inscripciones.php` (paso 3: arquitectura).
 * Las URLs originales (`Inscripciones/...`) siguen funcionando gracias a los
 * re-mapeos de config/routes.php; las vistas AJAX propias (panel_fixture,
 * panel_resultados) apuntan directamente a estas nuevas rutas.
 */
class Backend extends OLIM_Controller {

    use Traits_Inscripcion_Reglas;
    use Traits_Inscripcion_Disciplinas;
    use Traits_Inscripcion_Persona;

    public function __construct() {
        parent::__construct();
        $this->load->model('Participante_model');
        $this->load->model('UTE_model');
    }

    /* ============================================================
     *  INSCRIPCIONES (gestión desde el panel)
     * ============================================================ */

    /** Formulario para nueva inscripción manual (staff). */
    public function nueva_inscripcion() {
        $this->_requerir_organizador();
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        $this->load->view('admin/formulario_nueva_inscripcion', $data);
    }

    /** Guarda una nueva inscripción creada por el staff. */
    public function guardar_nueva_inscripcion() {
        $this->_requerir_organizador();

        $this->load->library('form_validation');
        $this->form_validation->set_rules($this->_reglas_validacion_inscripcion());
        // DNI único contra toda la tabla (excluye NULLs propios de MySQL en UNIQUE)
        $this->form_validation->set_rules('dni', 'DNI', 'required|trim|max_length[20]|regex_match[/^[0-9]+$/]|is_unique[participantes.dni]');
        $post = $this->input->post();

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors('<p>', '</p>') ?: 'Revisá los campos obligatorios del formulario.');
            redirect('Backend/nueva_inscripcion');
            return;
        }

        $proc = $this->_procesar_disciplinas($post);
        if (!$proc['ok']) {
            $this->session->set_flashdata('error', implode('<br>', $proc['errores']));
            redirect('Backend/nueva_inscripcion');
            return;
        }

        $data_persona = $this->_normalizar_persona($post);
        $data_persona['dni']               = $this->_limpiar($post['dni'] ?? '');
        $data_persona['fecha_inscripcion'] = date('Y-m-d H:i:s');
        $data_persona['token_qr']          = bin2hex(random_bytes(32));

        $resultado = $this->Participante_model->insertar_completo($data_persona, $proc['disciplinas']);

        if ($resultado) {
            $this->session->set_flashdata('success', 'Nueva inscripción guardada correctamente.');
        } else {
            $db_error = $this->db->error();
            log_message('error', 'Fallo al dar alta inscripción DNI=' . $data_persona['dni'] . ' error=' . json_encode($db_error));
            $mensaje = 'Error al guardar la nueva inscripción. Intente nuevamente.';
            if (isset($db_error['code']) && (int) $db_error['code'] === 1062) {
                $mensaje = 'El DNI ' . htmlspecialchars($data_persona['dni'], ENT_QUOTES, 'UTF-8') . ' ya se encuentra registrado.';
            }
            $this->session->set_flashdata('error', $mensaje);
        }

        redirect('Backend/control_total');
    }

    /** Formulario de edición de una inscripción existente. */
    public function modificar_inscripcion($id_participante) {
        $this->_requerir_organizador();

        if (!$id_participante) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Backend/control_total');
            return;
        }

        $data['participante'] = $this->Participante_model->obtener_detalle_participante((int) $id_participante);

        if (!$data['participante']) {
            $this->session->set_flashdata('error', 'Participante no encontrado.');
            redirect('Backend/control_total');
            return;
        }

        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        $this->load->view('admin/formulario_editar_inscripcion', $data);
    }

    /** Guarda las modificaciones de una inscripción existente. */
    public function guardar_modificacion() {
        $this->_requerir_organizador();

        $this->load->library('form_validation');
        $this->form_validation->set_rules($this->_reglas_validacion_inscripcion());
        $this->form_validation->set_rules('id_participante', 'ID de participante', 'required|integer');
        $post = $this->input->post();

        if ($this->form_validation->run() === FALSE || empty($post['id_participante'])) {
            $this->session->set_flashdata('error', validation_errors('<p>', '</p>') ?: 'ID de participante no válido.');
            redirect('Backend/control_total');
            return;
        }

        $id_participante = (int) $post['id_participante'];

        // El participante debe existir
        if (!$this->Participante_model->existe((int) $id_participante)) {
            $this->session->set_flashdata('error', 'El participante indicado no existe.');
            redirect('Backend/control_total');
            return;
        }

        $proc = $this->_procesar_disciplinas($post, $id_participante);
        if (!$proc['ok']) {
            $this->session->set_flashdata('error', implode('<br>', $proc['errores']));
            redirect('Backend/control_total');
            return;
        }

        $data_persona = $this->_normalizar_persona($post);
        $resultado = $this->Participante_model->actualizar_completo($id_participante, $data_persona, $proc['disciplinas']);

        if ($resultado) {
            $this->session->set_flashdata('success', 'Inscripción modificada correctamente.');
        } else {
            log_message('error', 'Fallo al modificar inscripción id=' . $id_participante . ' error=' . json_encode($this->db->error()));
            $this->session->set_flashdata('error', 'Error al modificar la inscripción. Intente nuevamente.');
        }

        redirect('Backend/control_total');
    }

    /** Elimina un participante y todas sus inscripciones (transacción atómica). */
    public function eliminar_inscripcion($id_participante) {
        $this->_requerir_organizador();

        if (!$id_participante) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Backend/control_total');
            return;
        }

        if ($this->Participante_model->eliminar_inscripcion_completa((int) $id_participante)) {
            $this->session->set_flashdata('success', 'Inscripción eliminada correctamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al eliminar la inscripción. Intente nuevamente.');
        }

        redirect('Backend/control_total');
    }

    /** Endpoint AJAX: eliminar inscripción sin recargar página. */
    public function eliminar_inscripcion_ajax($id_participante) {
        if (!$this->_es_organizador()) {
            $this->_json(['success' => false, 'error' => 'No tenés permisos para realizar esta acción.']);
            return;
        }
        if (!$id_participante) {
            $this->_json(['success' => false, 'error' => 'ID de participante no válido.']);
            return;
        }

        if ($this->Participante_model->eliminar_inscripcion_completa((int) $id_participante)) {
            $this->_json(['success' => true, 'mensaje' => 'Inscripción eliminada correctamente.']);
        } else {
            $this->_json(['success' => false, 'error' => 'Error al eliminar la inscripción. Intente nuevamente.']);
        }
    }

    /** Endpoint AJAX: detalle de un participante (con gate de delegación). */
    public function detalle_ajax($id_participante) {
        if (!$this->_es_organizador() && !$this->_es_delegado()) {
            $this->_json(['error' => 'No autorizado']);
            return;
        }

        if (!$id_participante) {
            $this->_json(['error' => 'ID no válido']);
            return;
        }

        $data = $this->Participante_model->obtener_detalle_participante((int) $id_participante);

        // Si es delegado, solo puede ver participantes de su delegación
        if ($this->_es_delegado()) {
            if (!$data || $data['delegacion'] !== $this->session->userdata('user_nombre')) {
                $this->_json(['error' => 'No tenés permiso para ver este participante']);
                return;
            }
        }

        $this->_json($data);
    }

    /** Pantalla principal de control total (inscripciones + UTEs + fixture + resultados). */
    public function control_total() {
        $this->_requerir_superadmin();

        $data['listado_inscripciones'] = $this->Deporte_model->obtener_todas_las_inscripciones();
        $data['deportes_db']   = $this->Deporte_model->obtener_todos_los_deportes();
        $data['utes']          = $this->UTE_model->obtener_todas_las_utes();
        $data['categorias']    = $this->UTE_model->obtener_categorias_con_deportes();
        $data['lugares_db']    = $this->Deporte_model->obtener_todos_los_lugares();
        $data['menu_activo']   = 'control';

        $this->load->model('Fixture_model');
        $data['categorias_fixture'] = $this->Fixture_model->obtener_categorias_para_fixture();

        $this->load->view('admin/control_total', $data);
    }

    /* ============================================================
     *  ACREDITACIÓN
     * ============================================================ */

    /** Cambia el estado de entrega del kit de un participante. */
    public function acreditar_kit() {
        $this->_requerir_organizador();

        $id_participante = (int) $this->input->post('id_participante');
        $token  = $this->input->post('token_qr');
        $estado = (int) $this->input->post('nuevo_estado');

        $this->Participante_model->marcar_kit_entregado($id_participante, $estado);
        redirect('Publica/acreditacion/' . rawurlencode((string) $token));
    }

    /** Cambia el estado "asistió" de una inscripción deportiva. */
    public function acreditar_deporte() {
        $this->_requerir_organizador();

        $id_inscripcion = (int) $this->input->post('id_inscripcion');
        $token  = $this->input->post('token_qr');
        $estado = (int) $this->input->post('nuevo_estado');

        $this->Participante_model->marcar_asistencia_deporte($id_inscripcion, $estado);
        redirect('Publica/acreditacion/' . rawurlencode((string) $token));
    }

    /** Credencial imprimible de un participante (solo staff). */
    public function imprimir_credencial($token = NULL) {
        if (!$this->_es_organizador() || empty($token)) {
            show_error('No autorizado o token no válido', 403);
        }

        $data['participante'] = $this->Participante_model->obtener_por_token($token);

        if (empty($data['participante'])) {
            show_error('Participante no encontrado', 404);
        }

        $this->load->view('admin/imprimir_credencial', $data);
    }

    /* ============================================================
     *  GESTIÓN DE DEPORTES / CATEGORÍAS / PREDIOS
     * ============================================================ */

    public function gestion_deportes() {
        $this->_requerir_organizador();

        $data['deportes']           = $this->Deporte_model->obtener_fixture_completo();
        $data['todos_los_deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        $data['todos_los_lugares']  = $this->Deporte_model->obtener_todos_los_lugares();
        $data['menu_activo']        = 'deportes';

        $this->load->view('admin/gestion_deportes', $data);
    }

    /** Alta de categoría desde el modal de gestión. */
    public function guardar_categoria() {
        $this->_requerir_organizador();

        $this->load->library('form_validation');
        $this->form_validation->set_rules([
            ['field' => 'id_deporte',       'label' => 'Deporte',    'rules' => 'required|integer'],
            ['field' => 'nombre_categoria', 'label' => 'Categoría',  'rules' => 'required|trim|max_length[100]'],
            ['field' => 'cupo_maximo',      'label' => 'Cupo máximo','rules' => 'permit_empty|integer|min_value[0]|max_value[10000]'],
            ['field' => 'id_lugar',         'label' => 'Lugar',      'rules' => 'permit_empty|integer'],
        ]);

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('mensaje_error', validation_errors('<p>', '</p>') ?: 'Faltan datos obligatorios para crear la categoría.');
            redirect('Backend/gestion_deportes');
            return;
        }

        if ($this->Categoria_model->insertar_categoria_desde_post($this->input->post())) {
            $this->session->set_flashdata('mensaje_exito', 'Categoría registrada correctamente.');
        } else {
            $this->session->set_flashdata('mensaje_error', 'No se pudo crear la categoría. Verificá los datos ingresados.');
        }

        redirect('Backend/gestion_deportes');
    }

    public function eliminar_categoria($id_categoria) {
        $this->_requerir_organizador();

        if (!empty($id_categoria)) {
            $this->Categoria_model->eliminar_categoria((int) $id_categoria);
            $this->session->set_flashdata('msg_ok', 'Categoría eliminada correctamente.');
        }
        redirect('Backend/gestion_deportes');
    }

    public function editar_categoria() {
        $this->_requerir_organizador();

        $id_categoria = (int) $this->input->post('id_categoria');
        $data = [
            'nombre_categoria' => $this->_limpiar($this->input->post('nombre_categoria')),
            'genero'           => $this->_limpiar($this->input->post('genero_categoria')),
            'cupo_maximo'      => (int) $this->input->post('cupo_maximo'),
            'id_lugar'         => ((int) $this->input->post('id_lugar')) ?: NULL,
            'dia_competencia'  => $this->_limpiar($this->input->post('dia_competencia')),
            'hora_competencia' => $this->_limpiar($this->input->post('hora_competencia')),
        ];

        if ($id_categoria > 0) {
            $this->Categoria_model->actualizar_categoria($id_categoria, $data);
            $this->session->set_flashdata('msg_ok', 'Categoría actualizada correctamente.');
        }
        redirect('Backend/gestion_deportes');
    }

    public function guardar_lugar() {
        $this->_requerir_organizador();

        if ($this->Deporte_model->insertar_lugar_desde_post($this->input->post())) {
            $this->session->set_flashdata('mensaje_exito', 'Predio registrado correctamente.');
        } else {
            $this->session->set_flashdata('mensaje_error', 'El nombre del predio es obligatorio.');
        }

        redirect('Backend/gestion_deportes');
    }

    public function eliminar_lugar($id_lugar) {
        $this->_requerir_organizador();

        if (!empty($id_lugar)) {
            $this->Deporte_model->eliminar_lugar((int) $id_lugar);
            $this->session->set_flashdata('msg_ok', 'Sede/Predio eliminado correctamente.');
        }
        redirect('Backend/gestion_deportes');
    }

    public function editar_lugar() {
        $this->_requerir_organizador();

        $id_lugar = (int) $this->input->post('id_lugar');
        $data = [
            'nombre'    => $this->_limpiar($this->input->post('nombre')),
            'direccion' => $this->_limpiar($this->input->post('direccion')),
        ];

        if ($id_lugar > 0) {
            $this->Deporte_model->actualizar_lugar($id_lugar, $data);
            $this->session->set_flashdata('msg_ok', 'Predio actualizado correctamente.');
        }
        redirect('Backend/gestion_deportes');
    }

    /** Alta de un deporte general desde el modal. */
    public function guardar_deporte() {
        $this->_requerir_organizador();

        $this->load->library('form_validation');
        $this->form_validation->set_rules([
            ['field' => 'nombre_deporte', 'label' => 'Deporte', 'rules' => 'required|trim|max_length[100]'],
            ['field' => 'genero',         'label' => 'Género',  'rules' => 'required|trim|max_length[20]'],
        ]);

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('mensaje_error', validation_errors('<p>', '</p>') ?: 'El nombre del deporte es obligatorio.');
            redirect('Backend/gestion_deportes');
            return;
        }

        $this->Deporte_model->guardar_deporte([
            'nombre_deporte' => $this->_limpiar($this->input->post('nombre_deporte')),
            'genero'         => $this->_limpiar($this->input->post('genero')),
        ]);
        redirect('Backend/gestion_deportes');
    }

    /** Elimina un deporte y sus categorías asociadas (en el modelo, con transacción). */
    public function eliminar_deporte($id_deporte) {
        $this->_requerir_organizador();

        if (!empty($id_deporte) && is_numeric($id_deporte)) {
            $this->Deporte_model->eliminar_deporte((int) $id_deporte);
        }
        redirect('Backend/gestion_deportes');
    }

    public function editar_deporte() {
        $this->_requerir_organizador();

        $id_deporte = (int) $this->input->post('id_deporte');
        $data = [
            'nombre_deporte' => $this->_limpiar($this->input->post('nombre_deporte')),
            'genero'         => $this->_limpiar($this->input->post('genero')),
        ];

        if ($id_deporte > 0) {
            $this->Deporte_model->actualizar_deporte($id_deporte, $data);
        }
        redirect('Backend/gestion_deportes');
    }

    /** Monitoreo de los resultados del sondeo preliminar. */
    public function monitoreo_encuesta() {
        $this->_requerir_organizador();

        $data['total_encuestas']         = $this->Deporte_model->contar_total_encuestas();
        $data['ranking_deportes']        = $this->Deporte_model->obtener_ranking_deportes_sondeo();
        $data['respuestas_por_delegacion'] = $this->Deporte_model->obtener_respuestas_por_delegacion();
        $data['menu_activo']             = 'sondeo';

        $this->load->view('admin/monitoreo_encuesta', $data);
    }

    /* ============================================================
     *  UTEs / EQUIPOS (endpoints AJAX del panel-equipos)
     * ============================================================ */

    public function ajax_participantes_disponibles($id_categoria) {
        if (!$this->_es_organizador()) {
            $this->_json(['error' => 'No tenés permisos']);
            return;
        }
        $participantes = $this->UTE_model->obtener_participantes_disponibles((int) $id_categoria);
        $this->_json(!empty($participantes) ? $participantes : []);
    }

    public function ajax_crear_ute() {
        if (!$this->_es_organizador()) {
            $this->_json(['success' => false, 'error' => 'No tenés permisos']);
            return;
        }

        $nombre_ute    = $this->_limpiar($this->input->post('nombre_ute'));
        $id_categoria  = (int) $this->input->post('id_categoria');

        if (empty($nombre_ute) || $id_categoria <= 0) {
            $this->_json(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $id_ute = $this->UTE_model->insertar_ute($nombre_ute, $id_categoria);
        $this->_json($id_ute
            ? ['success' => true, 'id_ute' => $id_ute]
            : ['success' => false, 'error' => 'Error al crear la UTE']);
    }

    public function ajax_agregar_participante_ute() {
        if (!$this->_es_organizador()) {
            $this->_json(['success' => false, 'error' => 'No tenés permisos']);
            return;
        }

        $id_ute          = (int) $this->input->post('id_ute');
        $id_participante = (int) $this->input->post('id_participante');

        if ($id_ute <= 0 || $id_participante <= 0) {
            $this->_json(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        // Un participante no puede estar en dos UTEs de la misma categoría
        $ute_info = $this->UTE_model->obtener_categoria_de_ute($id_ute);
        if ($ute_info && $this->UTE_model->participante_ya_tiene_ute($id_participante, $ute_info['id_categoria'])) {
            $this->_json(['success' => false, 'error' => 'El participante ya está en una UTE de esta categoría']);
            return;
        }

        $this->_json($this->UTE_model->agregar_participante_a_ute($id_ute, $id_participante)
            ? ['success' => true]
            : ['success' => false, 'error' => 'Error al agregar el participante']);
    }

    public function ajax_eliminar_participante_ute() {
        if (!$this->_es_organizador()) {
            $this->_json(['success' => false, 'error' => 'No tenés permisos']);
            return;
        }

        $id_ute          = (int) $this->input->post('id_ute');
        $id_participante = (int) $this->input->post('id_participante');

        if ($id_ute <= 0 || $id_participante <= 0) {
            $this->_json(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $this->_json($this->UTE_model->eliminar_participante_de_ute($id_ute, $id_participante)
            ? ['success' => true]
            : ['success' => false, 'error' => 'Error al eliminar']);
    }

    public function ajax_eliminar_ute() {
        if (!$this->_es_organizador()) {
            $this->_json(['success' => false, 'error' => 'No tenés permisos']);
            return;
        }

        $id_ute = (int) $this->input->post('id_ute');
        if ($id_ute <= 0) {
            $this->_json(['success' => false, 'error' => 'ID de UTE no válido']);
            return;
        }

        $this->_json($this->UTE_model->eliminar_ute($id_ute)
            ? ['success' => true]
            : ['success' => false, 'error' => 'Error al eliminar la UTE']);
    }

    public function ajax_detalle_ute($id_ute) {
        if (!$this->_es_organizador()) {
            $this->_json(['error' => 'No tenés permisos']);
            return;
        }
        $this->_json($this->UTE_model->obtener_ute_con_integrantes((int) $id_ute));
    }
}

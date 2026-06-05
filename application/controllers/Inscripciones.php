<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inscripciones extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Cargamos los modelos esenciales
        $this->load->model('Deporte_model');
        $this->load->model('Categoria_model');
    }

    public function index() {
        redirect('Inscripciones/panel');
    }
    public function panel(){
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        $this->load->view('encuesta_previa', $data);
    }
    public function formulario_inscripcion(){
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        $this->load->view('formulario_inscripcion', $data);
    }
    

    public function getCategorias($id_deporte) {
        $categorias = $this->Categoria_model->get_by_deporte($id_deporte);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($categorias));
    }

    public function buscar_por_dni() {
        // Usamos GET o POST según prefieras, pero mantengamos consistencia con el AJAX
        $dni = $this->input->post('dni');

        if (empty($dni)) {
            echo json_encode(['existe' => false]);
            return;
        }

        $this->load->model('Participante_model');

        // 1. Buscamos los datos en la tabla 'participantes'
        $this->db->where('dni', trim($dni));
        $query_participante = $this->db->get('participantes');

        if ($query_participante->num_rows() > 0) {
            $participante = $query_participante->row_array();
            $id_p = $participante['id_participante'];

            // 2. Buscamos sus deportes y categorías vinculadas
            // Hacemos un JOIN con la tabla categorias para obtener el id_deporte que pide tu UI
            $this->db->select('i.id_categoria, c.id_deporte');
            $this->db->from('inscripciones_deportivas i');
            $this->db->join('categorias c', 'i.id_categoria = c.id_categoria');
            $this->db->where('i.id_participante', $id_p);
            $query_deportes = $this->db->get();
            
            $disciplinas = $query_deportes->result_array();

            // Enviamos la respuesta completa
            echo json_encode([
                'existe'      => true,
                'datos'       => $participante,
                'disciplinas' => $disciplinas // Array de objetos {id_categoria, id_deporte}
            ]);
        } else {
            echo json_encode(['existe' => false]);
        }
    }

    public function guardar() {
        $this->load->model('Participante_model');
        $post = $this->input->post();

        // 1. Preguntamos al modelo si el participante existe (usando la query de control básica)
        $this->db->where('dni', trim($post['dni']));
        $query_check = $this->db->get('participantes');
        $existe = ($query_check->num_rows() > 0);

        if ($existe) {
            // [MODO ACTUALIZACIÓN]
            $participante_viejo = $query_check->row_array();
            $id_participante = $participante_viejo['id_participante'];
            $token = $participante_viejo['token_qr']; 
        } else {
            // [MODO NUEVA INSCRIPCIÓN]
            $semilla = $post['dni'] . 'olimpiadas2026' . time();
            $token = sha1($semilla);
        }

        // Data estructurada
        $data_persona = [
            'nombre_completo'     => mb_strtoupper(trim($post['nombre_completo']), 'UTF-8'),
            'email'               => strtolower(trim($post['email'])), 
            'telefono'            => trim($post['telefono']),
            'delegacion'          => trim($post['delegacion']),
            'sexo'                => trim($post['sexo']),
            'fecha_nacimiento'    => $post['fecha_nacimiento'], 
            'grupo_sanguineo'     => trim($post['grupo_sanguineo']),
            'obra_social'         => mb_strtoupper(trim($post['obra_social']), 'UTF-8'),
            'tipo_empleado'       => trim($post['tipo_empleado']),
            'dieta_especial'      => trim($post['dieta_especial']),
            'hotel_alojamiento'   => mb_strtoupper(trim($post['hotel_alojamiento']), 'UTF-8'),
            'contacto_emergencia' => mb_strtoupper(trim($post['contacto_emergencia']), 'UTF-8'),
            'token_qr'            => $token
        ];

        // 2. DELEGAMOS TODO AL MODELO SEGÚN CORRESPONDA
        if ($existe) {
            $resultado = $this->Participante_model->actualizar_completo(
                $id_participante, 
                $data_persona, 
                $post['categoria_id']
            );
        } else {
            $data_persona['dni'] = trim($post['dni']);
            $data_persona['fecha_inscripcion'] = date('Y-m-d H:i:s');

            $resultado = $this->Participante_model->insertar_completo(
                $data_persona, 
                $post['categoria_id']
            );
        }

        // 3. RESPUESTA DE VISTAS
        if (!$resultado) {
            $db_error = $this->db->error();
            $mensaje = 'Verifique si ocurrió un error en el sistema o si faltan datos obligatorios.';
            
            if (isset($db_error['code']) && $db_error['code'] == 1062) {
                $mensaje = 'El DNI <strong>' . $post['dni'] . '</strong> ya se encuentra registrado.';
            }

            $this->load->view('inscripcion_erronea', [
                'mensaje' => $mensaje,
                'dni'     => $post['dni']
            ]);
        } else {
            // $this->enviar_comprobante_real($data_persona, $token);
            $this->load->view('inscripcion_exitosa', [
                'nombre' => $post['nombre_completo'],
                'token'  => $token
            ]);
        }
    }

    // 1. Pantalla que abre el código QR
    public function acreditacion($token = NULL) {
        if (!$token) { show_404(); }

        $this->load->model('Participante_model');
        $participante = $this->Participante_model->obtener_por_token($token);

        if (!$participante) {
            echo "<h3>Código QR inválido.</h3>";
            return;
        }

        // Guardamos los datos del participante para las vistas
        $data['participante'] = $participante;

        // NUEVO: Buscamos las inscripciones deportivas asociadas usando tu modelo real
        $data['deportes'] = $this->Participante_model->obtener_deportes_inscriptos($participante['id_participante']);

        // ¿Es organizador logueado?
        if ($this->session->userdata('is_organizador')) {
            // PANTALLA PRO: Vos organizadora ves las opciones de deportes
            $this->load->view('admin/panel_acreditacion', $data);
        } else {
            // PANTALLA PÚBLICA: El participante escanea su propio QR
            // NUEVO: Guardamos en sesión a qué QR querías ir, por si el staff inicia sesión desde acá
            $this->session->set_userdata('url_retorno_qr', 'Inscripciones/acreditacion/' . $token);
            
            $this->load->view('public/pase_valido', $data);
        }
    }



    // 2. Mostrar formulario de Login manual
   public function login_staff() {
        if ($this->session->userdata('is_organizador')) {
            if ($this->session->userdata('url_retorno_qr')) {
                $redirigir = $this->session->userdata('url_retorno_qr');
                $this->session->unset_userdata('url_retorno_qr');
                redirect($redirigir);
            }

            // --- DASHBOARD PRINCIPAL: SOLAMENTE PARTICIPANTES ---
            $this->load->model('Participante_model');
            
            $data['participantes'] = $this->Participante_model->obtener_todos_los_participantes();
            $data['total_inscriptos'] = count($data['participantes']);
            $data['total_kits'] = $this->Participante_model->contar_kits_entregados();

            // Cargamos la vista principal (limpia)
            $this->load->view('admin/dashboard', $data);
            return;
        }
        $this->load->view('admin/login');
    }

    // --- NUEVA PANTALLA: GESTIÓN DE DEPORTES ---
    public function gestion_deportes() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login_staff');
        }

        $this->load->model('Deporte_model');
        
        // Cargamos todo lo necesario para los fixtures y modales deportivos
        $data['deportes'] = $this->Deporte_model->obtener_fixture_completo(); 
        $data['todos_los_deportes'] = $this->Deporte_model->obtener_todos_los_deportes(); 
        $data['todos_los_lugares'] = $this->Deporte_model->obtener_todos_los_lugares(); 

        $this->load->view('admin/gestion_deportes', $data);
    }

    

    // --- PROCESAR EL MODAL DE NUEVA CATEGORÍA ---
    public function guardar_categoria() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login_staff');
        }

        $this->load->model('Categoria_model');
        
        // Le mandamos todo el POST crudo al modelo para que él decida qué hacer
        $guardado = $this->Categoria_model->insertar_categoria_desde_post($this->input->post());

        if ($guardado) {
            $this->session->set_flashdata('mensaje_exito', 'Categoría registrada correctamente.');
        } else {
            $this->session->set_flashdata('mensaje_error', 'Faltan datos obligatorios para crear la categoría.');
        }

        redirect('Inscripciones/gestion_deportes');
    }
    public function eliminar_categoria($id_categoria) {
        if (!empty($id_categoria)) {
            $this->Categoria_model->eliminar_categoria($id_categoria);
            $this->session->set_flashdata('msg_ok', 'Categoría eliminada correctamente.');
        }
        redirect('Inscripciones/gestion_deportes');
    }

    // --- EDITAR CATEGORÍA ---
    public function editar_categoria() {
        $id_categoria = $this->input->post('id_categoria');
        
        $data = [
            'nombre_categoria' => $this->input->post('nombre_categoria'),
            'genero_categoria' => $this->input->post('genero_categoria'),
            'cupo_maximo'      => $this->input->post('cupo_maximo'),
            'id_lugar'         => $this->input->post('id_lugar'),
            'dia_competencia'  => $this->input->post('dia_competencia'),
            'hora_competencia' => $this->input->post('hora_competencia')
        ];

        if (!empty($id_categoria)) {
            $this->Categoria_model->actualizar_categoria($id_categoria, $data);
            $this->session->set_flashdata('msg_ok', 'Categoría actualizada correctamente.');
        }
        redirect('Inscripciones/gestion_deportes');
    }

    public function guardar_lugar() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login_staff');
        }

        $this->load->model('Deporte_model');
        
        $guardado = $this->Deporte_model->insertar_lugar_desde_post($this->input->post());

        if ($guardado) {
            $this->session->set_flashdata('mensaje_exito', 'Predio registrado correctamente.');
        } else {
            $this->session->set_flashdata('mensaje_error', 'El nombre del predio es obligatorio.');
        }

        redirect('Inscripciones/gestion_deportes');
    }

    // --- ELIMINAR LUGAR ---
    public function eliminar_lugar($id_lugar) {
        if (!empty($id_lugar)) {
            $this->Deporte_model->eliminar_lugar($id_lugar);
            $this->session->set_flashdata('msg_ok', 'Sede/Predio eliminado correctamente.');
        }
        redirect('Inscripciones/gestion_deportes');
    }

    // --- EDITAR LUGAR ---
    public function editar_lugar() {
        $id_lugar = $this->input->post('id_lugar');
        
        $data = [
            'nombre'    => $this->input->post('nombre'),
            'direccion' => $this->input->post('direccion')
        ];

        if (!empty($id_lugar)) {
            $this->Deporte_model->actualizar_lugar($id_lugar, $data);
            $this->session->set_flashdata('msg_ok', 'Predio actualizado correctamente.');
        }
        redirect('Inscripciones/gestion_deportes');
    }


    // 3. Procesar el formulario de Login
    public function procesar_login() {
        $this->load->model('Usuario_model');
        $usuario = $this->input->post('usuario');
        $password = $this->input->post('password');

        $logged_user = $this->Usuario_model->login($usuario, $password);

        if ($logged_user) {
            $this->session->set_userdata([
                'is_organizador' => TRUE,
                'user_id'        => $logged_user['id_usuario'], 
                'user_nombre'    => $logged_user['nombre_usuario'], 
                'user_rol'       => $logged_user['rol']
            ]);
            
            // NUEVO: ¡El redireccionador inteligente!
            // Si venías de escanear un QR, te devuelve derecho a ese participante
            if ($this->session->userdata('url_retorno_qr')) {
                $redirigir_a = $this->session->userdata('url_retorno_qr');
                $this->session->unset_userdata('url_retorno_qr'); // Limpiamos la sesión
                redirect($redirigir_a);
            } else {               
                redirect('Inscripciones/login_staff');
            }
        } else {
            $this->session->set_flashdata('error', 'Usuario o contraseña incorrectos.');
            redirect('Inscripciones/login_staff');
        }
    }

    // 4. Cerrar sesión
    public function logout_staff() {
        $this->session->sess_destroy();
        redirect('Inscripciones/login_staff');
    }

    // --- NUEVO: ACCIÓN PARA CAMBIAR EL KIT ENTREGADO A 1 ---
    public function acreditar_kit() {
        if (!$this->session->userdata('is_organizador')) { show_error('No autorizado', 403); }
        
        $this->load->model('Participante_model');
        $id_participante = $this->input->post('id_participante');
        $token = $this->input->post('token_qr');
        $nuevo_estado = $this->input->post('nuevo_estado'); // Recibe 1 o 0 desde la Vista
        
        // Pasamos el ID y el nuevo valor
        $this->Participante_model->marcar_kit_entregado($id_participante, $nuevo_estado);
        redirect('Inscripciones/acreditacion/' . $token);
    }

    // --- NUEVO: ACCIÓN PARA CAMBIAR EL ASISTIO DEL DEPORTE A 1 ---
    public function acreditar_deporte() {
        if (!$this->session->userdata('is_organizador')) { show_error('No autorizado', 403); }
        
        $this->load->model('Participante_model');
        $id_inscripcion = $this->input->post('id_inscripcion');
        $token = $this->input->post('token_qr');
        $nuevo_estado = $this->input->post('nuevo_estado'); // Recibe 1 o 0 desde la Vista
        
        // Pasamos el ID y el nuevo valor
        $this->Participante_model->marcar_asistencia_deporte($id_inscripcion, $nuevo_estado);
        redirect('Inscripciones/acreditacion/' . $token);
    }

    public function imprimir_credencial($token = NULL) {
        // Seguridad: solo el staff logueado puede generar credenciales
        if (!$this->session->userdata('is_organizador') || empty($token)) {
            show_error('No autorizado o token no válido', 403);
        }

        $this->load->model('Participante_model');
        // Buscamos al participante por su token QR
        $data['participante'] = $this->Participante_model->obtener_por_token($token);

        if (empty($data['participante'])) {
            show_error('Participante no encontrado', 404);
        }

        // Cargamos la vista especial de impresión
        $this->load->view('admin/imprimir_credencial', $data);
    }

    /**
     * Nueva pantalla de visualización y monitoreo de los resultados del sondeo preliminar
     */
    public function monitoreo_encuesta() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login_staff');
        }

        $this->load->model('Deporte_model');

        // Consultamos datos rápidos al modelo para armar las métricas básicas
        $data['total_encuestas'] = $this->Deporte_model->contar_total_encuestas();
        $data['ranking_deportes'] = $this->Deporte_model->obtener_ranking_deportes_sondeo();
        $data['respuestas_por_delegacion'] = $this->Deporte_model->obtener_respuestas_por_delegacion();

        $this->load->view('admin/monitoreo_encuesta', $data);
    }

    /**
     * Guarda un nuevo deporte general desde la ventana modal
     */
    public function guardar_deporte() {
        if (!$this->session->userdata('is_organizador')) { redirect('Inscripciones/login_staff'); }

        $data['nombre_deporte'] = $this->input->post('nombre_deporte', TRUE);
        $data['genero'] = $this->input->post('genero', TRUE);
        $this->Deporte_model->guardar_deporte($data);
        redirect('Inscripciones/gestion_deportes');
    }

    /**
     * Elimina un deporte y sus categorías asociadas por ID
     */
    public function eliminar_deporte($id_deporte) {
        if (!$this->session->userdata('is_organizador')) { redirect('Inscripciones/login_staff'); }

        if (!empty($id_deporte) && is_numeric($id_deporte)) {
            // Al borrar el deporte, quitamos también sus categorías para no dejar registros huérfanos
            $this->db->delete('categorias', ['id_deporte' => $id_deporte]);
            $this->db->delete('deportes', ['id_deporte' => $id_deporte]);
        }
        redirect('Inscripciones/gestion_deportes');
    }

    public function editar_deporte() {
        $id_deporte = $this->input->post('id_deporte');
        $data = [
            'nombre_deporte' => $this->input->post('nombre_deporte'),
            'genero'         => $this->input->post('genero')
        ];
        
        // Acá llamas a tu modelo para hacer el update correspondinte, por ej:
        $this->Deporte_model->actualizar_deporte($id_deporte, $data);
        
        redirect('Inscripciones/gestion_deportes');
    }
}
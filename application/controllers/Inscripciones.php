<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inscripciones extends CI_Controller {

    public function __construct() {
        // Blindaje de sesion para hostings tipo WNpower (CloudLinux + LiteSpeed):
        // ahi las sesiones en /tmp se pierden o son "compartidas/reasignadas" y
        // el navegador llega a mandar el session ID viejo sin cookie
        // ci_session. Si existe nuestra copia app_sid_ref (seteada al hacer
        // login), restaurarla como ci_session ANTES de que CodeIgniter lea la
        // sesion; si no, los POST AJAX quedan "sin sesion" y el guard responde
        // error (que detras de un WAF puede verse como 403 HTML -> "El
        // servidor bloqueó la operación").
        if (PHP_SAPI !== 'cli'
            && !isset($_COOKIE['ci_session'])
            && !empty($_COOKIE['app_sid_ref'])) {
            $_COOKIE['ci_session'] = $_COOKIE['app_sid_ref'];
        }

        parent::__construct();
        $this->load->library('Pdf');
        $this->load->model('Deporte_model');
        $this->load->model('Categoria_model');

        // Mantener app_sid_ref sincronizada con el session ID ACTUAL en cada
        // carga de pagina (no solo en el login). CodeIgniter regenera el ID de
        // sesion periodicamente (sess_time_to_update) y en hostings con LiteSpeed/
        // CloudLinux ese ID nuevo a veces se pierde entre requests. Si la copia
        // app_sid_ref queda vieja, los POST AJAX (fetch) llegan "sin sesion" y
        // el guard responde error, que detras del WAF se ve como el falso
        // "El servidor bloqueó la operación (403)". Con la cookie siempre al dia,
        // el blindaje de arriba puede restaurar la sesion aunque ci_session se pierda.
        if (PHP_SAPI !== 'cli' && !$this->input->is_ajax_request()) {
            $sid_actual = $this->session->userdata('ci_session') ?: session_id();
            if (!empty($sid_actual)) {
                setcookie('app_sid_ref', $sid_actual, [
                    'path'     => '/',
                    'httponly' => TRUE,
                    'samesite' => 'Lax',
                ]);
            }
        }
    }

    /**
     * ¿La petición actual es AJAX/fetch? (el frontend usa fetch(), no jQuery,
     * por eso no basta con HTTP_X_REQUESTED_WITH).
     */
    private function _es_peticion_ajax() {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        return strpos($accept, 'application/json') !== FALSE
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    /**
     * Guard de sesión para endpoints AJAX. Devuelve TRUE si el usuario está
     * autorizado (organizador autenticado), FALSE en caso contrario (y ya
     * imprimió el JSON de error correspondiente).
     *
     * IMPORTANTE: nunca redirige ni muestra HTML. Si la sesión está caída o
     * el hosting/firewall cortaría la request, el navegador vería un 403/302
     * con página HTML y el fetch fallaría con "Unexpected token '<'".
     */
    private function _auth_json_or_fail($mensaje = NULL) {
        if ($this->session->userdata('is_organizador')) {
            return TRUE;
        }
        log_message('error', '[Auth AJAX] Sesión caída o inexistente. URI: '
            . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '?')
            . ' | user_rol session: ' . var_export($this->session->userdata('user_rol'), TRUE));
        header('Content-Type: application/json');
        echo json_encode(array(
            'success'      => FALSE,
            'error'        => $mensaje ?: 'Tu sesión expiró o perdiste los permisos. Recargá la página e ingresá nuevamente.',
            'sesion_caida' => TRUE,
        ));
        return FALSE;
    }

    public function index() {
        redirect('Inscripciones/panel');
    }

    public function panel(){
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        $this->load->view('formulario_inscripcion', $data);
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

    public function getDeportesPorGenero($sexo) {
        // 1. Decodificar por si viene con caracteres raros de la URL (ej: %20)
        $sexo = urldecode($sexo); 

        $this->load->model('Deporte_model');
        
        // 2. Le pasamos el $sexo al modelo (crearemos este método nuevo abajo)
        $deportes = $this->Deporte_model->obtener_deportes_por_sexo($sexo); 
        
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($deportes));
    }

    public function buscar_por_dni() {
        $dni = $this->input->post('dni');

        if (empty($dni)) {
            echo json_encode(['existe' => false]);
            return;
        }

        $this->load->model('Participante_model');

        $this->db->where('dni', trim($dni));
        $query_participante = $this->db->get('participantes');

        if ($query_participante->num_rows() > 0) {
            $participante = $query_participante->row_array();
            $id_p = $participante['id_participante'];

            $this->db->select('*');
            $this->db->from('inscripciones_deportivas i');
            $this->db->join('categorias c', 'i.id_categoria = c.id_categoria');
            $this->db->where('i.id_participante', $id_p);
            $query_deportes = $this->db->get();
            
            $disciplinas = $query_deportes->result_array();

            echo json_encode([
                'existe'      => true,
                'datos'       => $participante,
                'disciplinas' => $disciplinas
            ]);
        } else {
            echo json_encode(['existe' => false]);
        }
    }

    public function guardar() {
        $this->load->model('Participante_model');
        $post = $this->input->post();

        // =================================================================
        // BLANCO DE PRUEBAS: Descomentá la línea de abajo para testear con datos fijos
        // =================================================================
        // $post = $this->_obtener_datos_prueba(); 
        // =================================================================

        $this->db->where('dni', trim($post['dni']));
        $query_check = $this->db->get('participantes');
        $existe = ($query_check->num_rows() > 0);

        if ($existe) {
            $participante_viejo = $query_check->row_array();
            $id_participante = $participante_viejo['id_participante'];
            $token = $participante_viejo['token_qr']; 
        } else {
            $semilla = $post['dni'] . 'olimpiadas2026' . time();
            $token = sha1($semilla);
        }

        // Estructura de datos incluyendo Roles y Delegados
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
            
            // TRADUCCIÓN CLAVE PARA TU BASE DE DATOS:
            // Si el rol es 'competidor' guarda 1, si es acompañante guarda 0
            'es_competidor'       => ($post['rol_asistente'] === 'competidor') ? 1 : 0,
            
            // Solo puede ser delegado si es competidor y tildó el checkbox
            'es_delegado'         => (isset($post['es_delegado']) && $post['rol_asistente'] === 'competidor') ? 1 : 0,
            
            'token_qr'            => $token
        ];

        // CONTROL Y CAPTURA DE DISCIPLINAS + PANEL UTE
        $deportes_seleccionados = [];
        
        if ($post['rol_asistente'] === 'competidor' && isset($post['categoria_id'])) {
            // Mapeamos dinámicamente cada categoría con su respectivo estado de UTE recibido del HTML
            foreach ($post['categoria_id'] as $index => $cat_id) {
                if (!empty($cat_id)) {
                    $deportes_seleccionados[] = [
                        'id_deporte'   => isset($post['deporte_id'][$index]) ? $post['deporte_id'][$index] : null,
                        'id_categoria' => $cat_id,
                        'tiene_ute'    => isset($post['tiene_ute'][$index]) ? (int)$post['tiene_ute'][$index] : 0,
                        'necesita_ute' => isset($post['necesita_ute'][$index]) ? (int)$post['necesita_ute'][$index] : 0,
                        'detalle_ute'  => isset($post['detalle_ute'][$index]) ? mb_strtoupper(trim($post['detalle_ute'][$index]), 'UTF-8') : ''
                    ];
                }
            }
        }

        // Ejecución en Base de Datos según existencia
        if ($existe) {
            // Pasamos la estructura completa de deportes con UTE. 
            // NOTA: Si tu modelo viejo solo aceptaba IDs, adaptalo para leer este array asociativo de la forma: $disc['id_categoria']
            $resultado = $this->Participante_model->actualizar_completo(
                $id_participante, 
                $data_persona, 
                $deportes_seleccionados // <-- Asegurate de que viaje esta variable acá y no $categorias_ids
            );
        } else {
            $data_persona['dni'] = trim($post['dni']);
            $data_persona['fecha_inscripcion'] = date('Y-m-d H:i:s');

            $resultado = $this->Participante_model->insertar_completo(
                $data_persona, 
                $deportes_seleccionados
            );
        }

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
            $this->load->view('inscripcion_exitosa', [
                'delegacion' => $post['delegacion'],
                'nombre' => $post['nombre_completo'],
                'token'  => $token
            ]);
        }
    }

    /**
     * Función auxiliar con datos de prueba para testing rápido
     */
    private function _obtener_datos_prueba() {
        return [
            'dni'                 => '99888777',
            'nombre_completo'     => 'Juan Carlos Prueba',
            'email'               => 'juan.prueba@correo.com',
            'telefono'            => '2954123456',
            'delegacion'          => 'La Pampa',
            'sexo'                => 'Masculino',
            'fecha_nacimiento'    => '1995-05-15',
            'grupo_sanguineo'     => '0+',
            'obra_social'         => 'Sempre',
            'tipo_empleado'       => 'Planta Permanente',
            'dieta_especial'      => 'Sin restricciones',
            'hotel_alojamiento'   => 'Hotel Central',
            'contacto_emergencia' => 'Maria Gomez - 2954667788',
            'rol_asistente'       => 'competidor',
            'es_delegado'         => '1',
            // Arrays simulando la carga de deportes (Frontend)
            'deporte_id'          => ['1', '3'], // IDs de ejemplo de deportes
            'categoria_id'        => ['13', '15'], // IDs de ejemplo de categorías
            'tiene_ute'           => ['1', '0'],  // En el primero tiene UTE
            'necesita_ute'        => ['0', '1'],  // En el segundo necesita UTE
            'detalle_ute'         => ['Equipo Los Pampeanos FC', ''] 
        ];
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

        // NUEVO (Mesa de control): datos de la delegación (total de integrantes
        // y cuántos tienen el kit ya entregado) para el bloque del delegado.
        $data['stats_delegacion'] = $this->Participante_model->contar_delegacion(
            isset($participante['delegacion']) ? $participante['delegacion'] : null
        );

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

    /**
     * Genera y descarga el PDF del Deslinde de Responsabilidad (VERSIÓN DE PRUEBA)
     */
    public function descargar_deslinde($token = NULL) {
        // VERSIÓN DE PRUEBA: Solo muestra un PDF simple con texto de prueba
        $this->load->model('Participante_model');
        $participante = $this->Participante_model->obtener_por_token($token);

        if (!$participante) {
            show_404();
            return;
        }
        $data['participante']= $participante;
        
        $this->load->view('deslinde_resp_view',$data);
    }
    public function descargar_acreditaciones($delegacion = NULL) {
        // Aceptamos la delegación por POST (formulario oculto desde el modal) y,
        // como respaldo, también por query string (?delegacion=...).
        // Los caracteres acentuados (ej. "Córdoba") no están permitidos como
        // segmento de URI en CodeIgniter (permitted_uri_chars), y además algunos
        // hosts bloquean con 403 las URLs que llevan parámetros acentuados en el
        // query string, por eso la vía principal es el POST.
        if (!$delegacion) {
            $delegacion = $this->input->post('delegacion');
        }
        if (!$delegacion) {
            $delegacion = $this->input->get('delegacion');
        }

        if (!$delegacion || !is_string($delegacion)) {
            show_404();
            return;
        }

        // Validar que la delegación exista realmente antes de generar el PDF
        $this->load->model('Participante_model');
        $delegaciones_validas = $this->Participante_model->obtener_todas_las_delegaciones();
        if (!in_array($delegacion, $delegaciones_validas)) {
            show_404();
            return;
        }

        // Obtenemos los participantes de la delegación/provincia (ordenados por nombre)
        $participantes = $this->Participante_model->obtener_participantes_por_delegacion_completo($delegacion);

        if (!$participantes) {
            show_404();
            return;
        }

        $data['nombre_provincia'] = $delegacion;
        $data['participantes'] = $participantes;

        $this->load->view('admin/acreditaciones_view', $data);
    }



    // 2. Mostrar formulario de Login manual
    public function login() {
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
            $data['menu_activo'] = 'resumen';
            // Cargamos la vista principal (limpia)
            $this->load->view('admin/dashboard', $data);
            return;
        }
        
        if ($this->session->userdata('is_delegado')) {
            redirect('Inscripciones/panel_delegado');
        }
        
        $this->load->view('admin/login');
    }
    
    /**
     * Dashboard para staff/organizador - muestra todos los participantes
     */
    public function dashboard() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }
        
        $this->load->model('Participante_model');
        
        $data['participantes'] = $this->Participante_model->obtener_todos_los_participantes();
        $data['total_inscriptos'] = count($data['participantes']);
        $data['total_kits'] = $this->Participante_model->contar_kits_entregados();
        $data['menu_activo'] = 'resumen';
        
        $this->load->view('admin/dashboard', $data);
    }

    // --- NUEVA PANTALLA: GESTIÓN DE DEPORTES ---
    public function gestion_deportes() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }

        $this->load->model('Deporte_model');
        
        // Cargamos todo lo necesario para los fixtures y modales deportivos
        $data['deportes'] = $this->Deporte_model->obtener_fixture_completo(); 
        $data['todos_los_deportes'] = $this->Deporte_model->obtener_todos_los_deportes(); 
        $data['todos_los_lugares'] = $this->Deporte_model->obtener_todos_los_lugares(); 
        $data['menu_activo'] = 'deportes';

        $this->load->view('admin/gestion_deportes', $data);
    }

    

    // --- PROCESAR EL MODAL DE NUEVA CATEGORÍA ---
    public function guardar_categoria() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }
        // Seguridad solo_lectura: no se pueden crear categorías.
        $this->bloqueo_solo_lectura();

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
        if (!empty($id_categoria)) {            // Seguridad solo_lectura: no se pueden eliminar categorías.
            $this->bloqueo_solo_lectura();

            $this->Categoria_model->eliminar_categoria($id_categoria);
            $this->session->set_flashdata('msg_ok', 'Categoría eliminada correctamente.');
        }
        redirect('Inscripciones/gestion_deportes');
    }

    // --- EDITAR CATEGORÍA ---
    public function editar_categoria() {
        $id_categoria = $this->input->post('id_categoria');
        // Seguridad solo_lectura: no se pueden modificar categorías.
        $this->bloqueo_solo_lectura();

        
        $data = [
            'nombre_categoria' => $this->input->post('nombre_categoria'),
            'genero' => $this->input->post('genero_categoria'),
            'cupo_maximo'      => $this->input->post('cupo_maximo'),
            'id_lugar'         => $this->input->post('id_lugar'),
            'dia_competencia'  => $this->input->post('dia_competencia'),
            'hora_competencia' => $this->input->post('hora_competencia'),
            // Sistema de definición POR CATEGORÍA (FINAL+3er puesto vs tabla
            // de puntos). Categoria_model valida y descarta si aún no existe.
            'sistema_definicion' => $this->input->post('sistema_definicion'),
            'puntos_victoria'    => $this->input->post('puntos_victoria'),
            'puntos_empate'      => $this->input->post('puntos_empate')
        ];

        if (!empty($id_categoria)) {
            $this->Categoria_model->actualizar_categoria($id_categoria, $data);
            $this->session->set_flashdata('msg_ok', 'Categoría actualizada correctamente.');
        }
        redirect('Inscripciones/gestion_deportes');
    }

    public function guardar_lugar() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }
        // Seguridad solo_lectura: no se pueden crear predios.
        $this->bloqueo_solo_lectura();


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
        if (!empty($id_lugar)) {            // Seguridad solo_lectura: no se pueden eliminar predios.
            $this->bloqueo_solo_lectura();

            $this->Deporte_model->eliminar_lugar($id_lugar);
            $this->session->set_flashdata('msg_ok', 'Sede/Predio eliminado correctamente.');
        }
        redirect('Inscripciones/gestion_deportes');
    }

    // --- EDITAR LUGAR ---
    public function editar_lugar() {
        $id_lugar = $this->input->post('id_lugar');
        // Seguridad solo_lectura: no se pueden modificar predios.
        $this->bloqueo_solo_lectura();

        
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
        // Guardar una copia del session ID en una cookie propia (no depende
        // de /tmp ni del handler del hosting). Sirve para recuperar la sesion
        // si el servidor "olvida" ci_session entre requests (típico de
        // LiteSpeed/CloudLinux con cleanup agresivo de sesiones).
        @session_start();
        $id_session = session_id();
        if (!empty($id_session)) {
            setcookie('app_sid_ref', $id_session, [
                'path'     => '/',
                'httponly' => TRUE,
                'samesite' => 'Lax',
            ]);
        }

        $this->load->model('Usuario_model');
        $usuario = $this->input->post('usuario');
        $password = $this->input->post('password');

        $logged_user = $this->Usuario_model->login($usuario, $password);

        if ($logged_user) {
            // Verificar si es delegado
            $es_delegado = $this->Usuario_model->es_delegado($logged_user['id_usuario']);
            
            if ($es_delegado) {
                // Session para delegados
                $this->session->set_userdata([
                    'is_delegado' => TRUE,
                    'user_id'     => $logged_user['id_usuario'], 
                    'user_nombre' => $logged_user['nombre_usuario'], 
                    'user_rol'    => $logged_user['rol']
                ]);
                
                redirect('Inscripciones/panel_delegado');
            } else {
                // Session para staff/organizador
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
                    redirect('Inscripciones/dashboard');
                }
            }
        } else {
            $this->session->set_flashdata('error', 'Usuario o contraseña incorrectos.');
            redirect('Inscripciones/login');
        }
    }

    // 4. Cerrar sesión staff
    public function logout() {
        $this->session->sess_destroy();
        redirect('Inscripciones/login');
    }
    public function login_staff() {
        redirect('Inscripciones/login');
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

    /**
     * NUEVO (Mesa de control): "Acreditar entrega de kit" para el DELEGADO.
     * Marca kit_entregado = 1 (o 0 si se revierte) para TODOS los participantes
     * de la delegación del participante escaneado. Solo aplica si es delegado.
     */
    public function acreditar_kit_delegacion() {
        if (!$this->session->userdata('is_organizador')) { show_error('No autorizado', 403); }

        $this->load->model('Participante_model');

        $token        = $this->input->post('token_qr');
        $delegacion   = trim((string) $this->input->post('delegacion'));
        $nuevo_estado = ($this->input->post('nuevo_estado') == '0') ? 0 : 1;

        $participante = $this->Participante_model->obtener_por_token($token);

        // Seguridad extra: verificamos en BD que el escaneado sea realmente delegado
        // y que la delegación enviada desde el form coincida con la suya.
        if ($participante && !empty($participante['es_delegado'])
            && $delegacion !== '' && $delegacion === $participante['delegacion']) {

            $afectados = $this->Participante_model->marcar_kit_por_delegacion($delegacion, $nuevo_estado);

            $this->session->set_flashdata(
                'kit_msg',
                ($nuevo_estado == 1)
                    ? 'Kit entregado a la delegación ' . $delegacion . ' (' . (int) $afectados . ' participantes).'
                    : 'Se revirtió la entrega de kits de la delegación ' . $delegacion . '.'
            );
        } else {
            $this->session->set_flashdata('kit_msg', 'No se pudo registrar la entrega: el participante no es delegado o la delegación no coincide.');
        }

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
        $participante = $this->Participante_model->obtener_por_token($token);

        if (empty($participante)) {
            show_error('Participante no encontrado', 404);
        }

        // El reporte de acreditaciones (acreditaciones_view.php) espera un ARRAY
        // de participantes; le pasamos solo la credencial del inscripto pulsado.
        $data['participantes'] = array($participante);
        $data['nombre_provincia'] = !empty($participante['delegacion']) ? $participante['delegacion'] : 'Credencial';

        // Genera el PDF con la acreditación individual (8.5 x 13 cm)
        $this->load->view('admin/acreditaciones_view', $data);
    }

    /**
     * Nueva pantalla de visualización y monitoreo de los resultados del sondeo preliminar
     */
    public function monitoreo_encuesta() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }

        $this->load->model('Deporte_model');

        // Consultamos datos rápidos al modelo para armar las métricas básicas
        $data['total_encuestas'] = $this->Deporte_model->contar_total_encuestas();
        $data['ranking_deportes'] = $this->Deporte_model->obtener_ranking_deportes_sondeo();
        $data['respuestas_por_delegacion'] = $this->Deporte_model->obtener_respuestas_por_delegacion();
        $data['menu_activo'] = 'sondeo';

        $this->load->view('admin/monitoreo_encuesta', $data);
    }

    /**
     * Guarda un nuevo deporte general desde la ventana modal
     */
    public function guardar_deporte() {
        if (!$this->session->userdata('is_organizador')) { redirect('Inscripciones/login'); }
        // Seguridad solo_lectura: no se pueden crear deportes.
        $this->bloqueo_solo_lectura();


        $data['nombre_deporte'] = $this->input->post('nombre_deporte', TRUE);
        $data['genero'] = $this->input->post('genero', TRUE);
        $data['fiscales'] = $this->input->post('fiscales', TRUE);
        $this->Deporte_model->guardar_deporte($data);
        redirect('Inscripciones/gestion_deportes');
    }

    /**
     * Elimina un deporte y sus categorías asociadas por ID
     */
    public function eliminar_deporte($id_deporte) {
        if (!$this->session->userdata('is_organizador')) { redirect('Inscripciones/login'); }
        // Seguridad solo_lectura: no se pueden eliminar deportes.
        $this->bloqueo_solo_lectura();


        if (!empty($id_deporte) && is_numeric($id_deporte)) {
            // Al borrar el deporte, quitamos también sus categorías para no dejar registros huérfanos
            $this->db->delete('categorias', ['id_deporte' => $id_deporte]);
            $this->db->delete('deportes', ['id_deporte' => $id_deporte]);
        }
        redirect('Inscripciones/gestion_deportes');
    }

    public function editar_deporte() {
        $id_deporte = $this->input->post('id_deporte');
        // Seguridad solo_lectura: no se pueden modificar deportes.
        $this->bloqueo_solo_lectura();

        $data = [
            'nombre_deporte' => $this->input->post('nombre_deporte'),
            'genero'         => $this->input->post('genero'),
            // Fiscales: texto libre (se permite vaciar el campo).
            'fiscales'       => trim((string) $this->input->post('fiscales', TRUE)) ?: NULL
        ];
        
        // Acá llamas a tu modelo para hacer el update correspondinte, por ej:
        $this->Deporte_model->actualizar_deporte($id_deporte, $data);
        
        redirect('Inscripciones/gestion_deportes');
    }

    
    /**
     * ¿El usuario actual tiene rol "solo lectura"?
     * Los usuarios con rol 'solo_lectura' pueden VER la pantalla de
     * inscriptos, pero no modificar nada (ni por interfaz ni por request directa).
     */
    private function es_solo_lectura() {
        return $this->session->userdata('user_rol') === 'solo_lectura';
    }

    /**
     * Bloqueo de escrituras para el rol "solo_lectura" (seguridad del lado
     * del servidor). Si el usuario tiene ese rol, corta la ejecución con 403.
     */
    private function bloqueo_solo_lectura($es_json = FALSE) {
        if (!$this->es_solo_lectura()) {
            return;
        }
        if ($es_json || $this->_es_peticion_ajax()) {
            // NUNCA show_error() en requests AJAX: el navegador vería un 403
            // con HTML ("Unexpected token '<'" en el fetch). Siempre JSON.
            if (!headers_sent()) {
                http_response_code(200);
                header('Content-Type: application/json');
            }
            echo json_encode([
                'success'       => false,
                'error'         => 'Tu usuario es de solo lectura. No tenés permisos para modificar datos.',
                'solo_lectura'  => true
            ]);
            exit;
        }
        show_error('Tu usuario es de solo lectura. No tenés permisos para realizar esta acción.', 403);
        exit;
    }

    /**
     * Guard de sesión + rol para los endpoints de UTEs/Equipos.
     *
     * Reglas EXACTAMENTE como antes de la llegada del rol 'solo_lectura':
     *   - Cualquier organizador autenticado puede escribir, con dos excepciones:
     *     el rol 'solo_lectura' (bloqueado con mensaje claro) y el rol
     *     'delegado' (que ni siquiera debería estar aquí).
     *   - NO se usa una whitelist cerrada de roles: eso dejaba afuera a
     *     staff/mesa_control/carga_resultados y rompía todas las operaciones
     *     de la pantalla de Equipos.
     *
     * SIEMPRE responde JSON (status 200) cuando niega el acceso: jamás
     * redirige ni muestra páginas HTML de error (eso producía el
     * "Unexpected token '<'" y el falso "el servidor bloqueó la petición").
     */
    private function _ute_auth_write_json() {
        if (!$this->session->userdata('is_organizador')) {
            log_message('error', '[UTE] Sesión caída en escritura. URI: '
                . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '?'));
            header('Content-Type: application/json');
            echo json_encode([
                'success'      => false,
                'error'        => 'Tu sesión expiró o perdiste los permisos. Recargá la página e ingresá nuevamente.',
                'sesion_caida' => TRUE,
            ]);
            return FALSE;
        }
        $rol = (string) $this->session->userdata('user_rol');
        if ($rol === 'solo_lectura' || $rol === 'delegado') {
            header('Content-Type: application/json');
            echo json_encode([
                'success'      => false,
                'error'        => 'Tu usuario es de solo lectura. No tenés permisos para modificar equipos.',
                'solo_lectura' => TRUE,
            ]);
            return FALSE;
        }
        // superadmin, admin, staff, mesa_control, carga_resultados... todos
        // pueden gestionar equipos (comportamiento histórico del sistema).
        return TRUE;
    }

    public function inscriptos() {
        // Seguridad: cualquier organizador autenticado (comportamiento
        // histórico). El rol 'solo_lectura' entra pero en modo lectura; los
        // bloqueos de ESCRITURA se hacen con bloqueo_solo_lectura() en cada
        // acción. NO usar whitelist cerrada de roles acá: deja afuera a
        // admin/staff/mesa_control y "no deja hacer ninguna modificación".
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }

        $this->load->model('Deporte_model');
        $this->load->model('UTE_model');
        $this->load->model('Participante_model');

        // Datos para la pestaña de Inscripciones
        $data['delegaciones'] = $this->Participante_model->obtener_todas_las_delegaciones();
        $data['listado_inscripciones'] = $this->Deporte_model->obtener_todas_las_inscripciones();
        $data['deportes_db'] = $this->Deporte_model->obtener_todos_los_deportes();
        
        $data['categorias'] = $this->UTE_model->obtener_categorias_con_deportes();

        $data['menu_activo'] = 'inscriptos';
        $this->load->view('admin/inscripciones', $data);
    }
    public function fixture() {
        // Seguridad: cualquier organizador autenticado (comportamiento
        // histórico). 'solo_lectura' entra en modo lectura; las escrituras se
        // bloquean con bloqueo_solo_lectura(). No usar whitelist cerrada.
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }

        $this->load->model('Deporte_model');
        $this->load->model('UTE_model');
        $this->load->model('Participante_model');
        
        $data['categorias'] = $this->UTE_model->obtener_categorias_con_deportes();

        // Datos para la pestaña de Fixture
        $this->load->model('Fixture_model');
        $data['categorias_modal_partido'] = $this->Fixture_model->obtener_categorias_para_fixture();
        $data['categorias_fixture'] = $this->Fixture_model->obtener_categorias_para_fixture();
        $data['deportes_fixture'] = $this->Deporte_model->obtener_todos_los_deportes();
        $data['lugares_db'] = $this->Deporte_model->obtener_todos_los_lugares();

        $data['menu_activo'] = 'fixture';
        
        if (empty($data['categorias_fixture'])) {
            $this->load->model('Fixture_model');
            $data['categorias_fixture'] = $this->Fixture_model->obtener_categorias_para_fixture();
        }
        $this->load->view('admin/fixture', $data);
    }
    public function resultados() {
        // Seguridad: cualquier organizador autenticado (comportamiento
        // histórico). 'solo_lectura' entra en modo lectura; las escrituras se
        // bloquean con bloqueo_solo_lectura(). No usar whitelist cerrada.
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }

        $this->load->model('Deporte_model');
        $this->load->model('UTE_model');
        $this->load->model('Participante_model');
        $this->load->model('Fixture_model');
        $this->load->model('Resultado_model');
        $data['categorias_con_fixture'] = $this->Resultado_model->obtener_categorias_con_fixture();

        $data['menu_activo'] = 'resultados';
        
        $this->load->view('admin/resultados', $data);
    }
    public function utes_equipos() {
        // Seguridad: cualquier organizador autenticado (comportamiento
        // histórico). 'solo_lectura' entra en modo lectura; las escrituras se
        // bloquean con _ute_auth_write_json(). No usar whitelist cerrada de
        // roles: dejaba afuera a admin/staff/mesa_control y no dejaba hacer
        // ninguna modificación en la pantalla de Equipos.
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }

        $this->load->model('Deporte_model');
        $this->load->model('UTE_model');
        $this->load->model('Participante_model');

        // Delegaciones/provincias para el modal "Imprimir Credenciales"
        $data['delegaciones'] = $this->Participante_model->obtener_todas_las_delegaciones();

        // Datos para la pestaña de Inscripciones
        $data['listado_inscripciones'] = $this->Deporte_model->obtener_todas_las_inscripciones();
        $data['deportes_db'] = $this->Deporte_model->obtener_todos_los_deportes();
        
        // Datos para la pestaña de UTEs/Equipos
        $data['utes'] = $this->UTE_model->obtener_todas_las_utes();
        $data['categorias'] = $this->UTE_model->obtener_categorias_con_deportes();

        // Datos para la pestaña de Fixture
        $this->load->model('Fixture_model');
        // El select de categorías para GENERAR FIXTURE solo muestra las que
        // todavía NO tienen fixture generado (por eso se calcula aparte).
        $data['categorias_sin_fixture'] = $this->Fixture_model->obtener_categorias_sin_fixture();
        // El modal de partido del panel Fixture desdobla Deporte -> Categoría:
        // se le pasan TODAS las categorías (no solo las sin fixture generado).
        $data['categorias_modal_partido'] = $this->Fixture_model->obtener_categorias_para_fixture();
        // Para los filtros y el modal de CARGA DE RESULTADOS solo hacen falta las
        // categorías que YA tienen fixture generado (por orden: tiene sentido
        // cargar resultados únicamente donde ya existe calendario/jornadas).
        $this->load->model('Resultado_model');
        $data['categorias_con_fixture'] = $this->Resultado_model->obtener_categorias_con_fixture();
        // El panel Fixture (modal de partido manual) sigue necesitando TODAS las categorías.
        $data['categorias_fixture'] = $this->Fixture_model->obtener_categorias_para_fixture();
        $data['deportes_fixture'] = $this->Deporte_model->obtener_todos_los_deportes();
        $data['lugares_db'] = $this->Deporte_model->obtener_todos_los_lugares();

        $data['menu_activo'] = 'deportes';
        // Garantizar que el panel Fixture reciba TODAS las categorías para el
        // desdoblamiento Deporte -> Categoría del modal, incluso si esta vista
        // se carga desde otro controlador con $data incompleto.
        if (empty($data['categorias_fixture'])) {
            $this->load->model('Fixture_model');
            $data['categorias_fixture'] = $this->Fixture_model->obtener_categorias_para_fixture();
        }
        $this->load->view('admin/equipos', $data);
    }

    
    /**
     * PREMIACIÓN - sección del menú principal (independiente de Control Total).
     * Resumen diario de las entregas de premios de las competencias cerradas.
     */
    public function premiacion() {
        // Seguridad: cualquier organizador autenticado (comportamiento
        // histórico). 'solo_lectura' entra en modo lectura; las escrituras se
        // bloquean con bloqueo_solo_lectura(). No usar whitelist cerrada.
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }

        $data['menu_activo'] = 'premiacion';
        $this->load->view('admin/premiacion', $data);
    }

    /* ============================================================
     *  FIXTURE (pestaña de Control Total) - endpoints AJAX
     * ============================================================ */

    private function _fixture_auth_json() {
        // Solo superadmin/admin/solo_lectura pueden acceder a los endpoints del
        // fixture; las ESCRITURAS se bloquean aparte con bloqueo_solo_lectura().
        if (!$this->session->userdata('is_organizador')
            || !in_array($this->session->userdata('user_rol'), array('superadmin', 'admin', 'solo_lectura'))) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => 'No autorizado')));
            return false;
        }
        $this->load->model('Fixture_model');
        return true;
    }

    /** JSON de error con detalle para poder diagnosticar en pantalla. */
    private function _fixture_error_json($mensaje, $detalle = '') {
        log_message('error', '[Fixture] ' . $mensaje . ($detalle !== '' ? ' :: ' . $detalle : ''));
        $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode(array(
                'ok' => false,
                'error' => $mensaje,
                'detalle' => $detalle
            )));
    }

    /** Devuelve el fixture + las UTEs de una categoría (para pintar la pestaña). */
    public function ajax_fixture_categoria($id_categoria) {
        if (!$this->_fixture_auth_json()) return;
        $this->_fixture_no_cache_headers();

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

    /**
     * Categorías de un deporte (JSON). Alimenta el desdoblamiento
     * Deporte -> Categoría del modal de partido/jornada del panel Fixture.
     */
    public function ajax_categorias_por_deporte($id_deporte) {
        if (!$this->_fixture_auth_json()) return;

        try {
            $categorias = $this->Fixture_model->obtener_categorias_para_fixture((int) $id_deporte);
        } catch (Throwable $e) {
            $this->_fixture_error_json('Error al consultar las categorías del deporte.', $e->getMessage());
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('ok' => true, 'categorias' => $categorias)));
    }

    /** Cabeceras anti-cache para endpoints AJAX del fixture: sin esto el
     *  navegador/proxy puede devolver respuestas viejas (p. ej. el listado
     *  completo tras agregar/editar un partido recién se ve apretando F5). */
    private function _fixture_no_cache_headers() {
        $this->output
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('Pragma: no-cache')
            ->set_header('Expires: 0');
    }

    /** Devuelve TODO el fixture de todas las categorías (vista general sin filtros). */
    public function ajax_fixture_todo() {
        if (!$this->_fixture_auth_json()) return;
        $this->_fixture_no_cache_headers();

        // Orden del listado: 'deporte' (Deporte → Categoría → Fecha → Hora, por
        // defecto) u 'horario' (Fecha → Hora → Deporte → Categoría).
        $orden = $this->input->get('orden') === 'horario' ? 'horario' : 'deporte';

        try {
            // Auto-crear la jornada de los deportes masivos que aún no tienen
            // fixture, para que siempre se puedan cargar resultados.
            $fixtures = $this->Fixture_model->obtener_todo_el_fixture(null, $orden);
        } catch (Throwable $e) {
            $this->_fixture_error_json('Error al consultar la tabla fixtures.', $e->getMessage());
            return;
        }

        // Categorías sin fixture: para que el select "Categoría" del panel solo
        // muestre las que todavía no tienen fixture generado.
        $categorias_sin_fixture = $this->Fixture_model->obtener_categorias_sin_fixture();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok' => true,
                'orden' => $orden,
                'fixtures' => $fixtures,
                'categorias_sin_fixture' => $categorias_sin_fixture
            )));
    }

    /** Detalle de participantes de un partido/jornada (modal "Detalle" del fixture). */
    public function ajax_fixture_detalle($id_fixture) {
        if (!$this->_fixture_auth_json()) return;

        try {
            $detalle = $this->Fixture_model->detalle_participantes_del_partido((int) $id_fixture);
            if (!$detalle) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('ok' => false, 'error' => 'El partido no existe.')));
                return;
            }
        } catch (Throwable $e) {
            $this->_fixture_error_json('Error al obtener el detalle del partido.', $e->getMessage());
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('ok' => true) + $detalle));
    }

    /** Lista de inscriptos de una categoría (modal "Participantes" de la categoría). */
    public function ajax_inscriptos_categoria($id_categoria) {
        if (!$this->_fixture_auth_json()) return;

        try {
            $listado = $this->Fixture_model->inscriptos_de_categoria((int) $id_categoria);
        } catch (Throwable $e) {
            $this->_fixture_error_json('Error al obtener los inscriptos de la categoría.', $e->getMessage());
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('ok' => true) + $listado));
    }

    /** Genera automáticamente el fixture de una categoría. */
    public function ajax_generar_fixture() {
        if (!$this->_fixture_auth_json()) return;
        $this->bloqueo_solo_lectura(TRUE);

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
            $this->bloqueo_solo_lectura(TRUE);

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
        $this->bloqueo_solo_lectura(TRUE);

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
        $this->bloqueo_solo_lectura(TRUE);

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

    /**
     * Descarga un resumen del fixture cargado (todas las categorías) en formato
     * CSV TIPO CUADRO: cada COLUMNA es un día de competencia y cada FILA un
     * rango horario (franjas de 1 hora). Cada celda muestra los partidos de ese
     * día/franja como líneas "HH:MM–HH:MM · Deporte Cat · Local vs Visitante".
     * SIN filtros: ambos formatos (cuadro y listado) incluyen siempre TODOS los días.
     * Opciones de URL:
     *   ?lista=1          descarga el listado tradicional (una fila por partido).
     *   ?franja=HH        ancho de las franjas horarias del cuadro (filas).
     */
    public function descargar_csv_fixture() {
        if (!$this->session->userdata('is_organizador')
            || !in_array($this->session->userdata('user_rol'), array('superadmin', 'admin', 'solo_lectura'))) {
            redirect('Inscripciones/login');
        }

        $this->load->model('Fixture_model');

        try {
            $datos = $this->Fixture_model->obtener_todo_el_fixture();
        } catch (Throwable $e) {
            show_error('No se pudo obtener el fixture: ' . $e->getMessage(), 500);
            return;
        }

        // SIN filtro por día: ambos CSV (cuadro y listado) siempre incluyen
        // TODOS los días del fixture, ignore el parámetro ?dia= si viniera en la URL.

        // Orden garantizado del lado PHP. Por defecto: Deporte → Categoría →
        // Fecha → Hora. Con ?orden=horario se listan primero por día y horario
        // (Fecha → Hora → Deporte → Categoría).
        $orden_reporte = $this->input->get('orden') === 'horario' ? 'horario' : 'deporte';
        usort($datos, function ($a, $b) use ($orden_reporte) {
            if ($orden_reporte === 'horario') {
                return [
                    (string) ($a['fecha_competencia'] ?? ''),
                    (string) ($a['hora_inicio'] ?? ''),
                    strtolower($a['nombre_deporte'] ?? ''),
                    strtolower($a['nombre_categoria'] ?? ''),
                    (int) ($a['numero_fecha'] ?? 0)
                ] <=> [
                    (string) ($b['fecha_competencia'] ?? ''),
                    (string) ($b['hora_inicio'] ?? ''),
                    strtolower($b['nombre_deporte'] ?? ''),
                    strtolower($b['nombre_categoria'] ?? ''),
                    (int) ($b['numero_fecha'] ?? 0)
                ];
            }
            return [
                strtolower($a['nombre_deporte'] ?? ''),
                strtolower($a['nombre_categoria'] ?? ''),
                (string) ($a['fecha_competencia'] ?? ''),
                (string) ($a['hora_inicio'] ?? ''),
                (int) ($a['numero_fecha'] ?? 0)
            ] <=> [
                strtolower($b['nombre_deporte'] ?? ''),
                strtolower($b['nombre_categoria'] ?? ''),
                (string) ($b['fecha_competencia'] ?? ''),
                (string) ($b['hora_inicio'] ?? ''),
                (int) ($b['numero_fecha'] ?? 0)
            ];
        });

        $nombre_archivo = 'cuadro_fixture_'. date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');

        $output = fopen('php://output', 'w');

        // BOM para que Excel reconozca UTF-8 correctamente
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        

        // ---------- CUADRO: columnas = días, filas = rangos horarios ----------

        // 1) Días presentes (columnas), en orden cronológico
        $dias = array();
        foreach ($datos as $fila) {
            $f = substr((string) ($fila['fecha_competencia'] ?? ''), 0, 10);
            if ($f !== '') $dias[$f] = true;
        }
        $dias = array_keys($dias);
        sort($dias);

        // 2) Franjas horarias de 1 hora que cubran todos los partidos (filas).
        //    Si ?franja=HH lo pide el usuario, se agrupan los horarios en bloques
        //    de esa cantidad de horas (p. ej. franja=2 -> "08:00–10:00").
        $ancho = (int) $this->input->get('franja');
        if ($ancho < 1) $ancho = 1;
        if ($ancho > 12) $ancho = 12;

        $min_hora = NULL;
        $max_hora = NULL;
        foreach ($datos as $fila) {
            $hi = (string) ($fila['hora_inicio'] ?? '');
            $hf = (string) ($fila['hora_fin'] ?? '');
            if ($hi === '') continue;
            $h_ini = (int) substr($hi, 0, 2);
            $h_fin = $hf !== '' ? (int) substr($hf, 0, 2) : $h_ini;
            if ((int) substr($hf, 3, 2) > 0 || $h_fin === $h_ini) $h_fin++; // redondea a la siguiente hora
            if ($min_hora === NULL || $h_ini < $min_hora) $min_hora = $h_ini;
            if ($max_hora === NULL || $h_fin > $max_hora) $max_hora = $h_fin;
        }
        if ($min_hora === NULL) { $min_hora = 8; $max_hora = 9; } // sin datos: placeholder

        // Alineo el inicio a un múltiplo del ancho de franja y extiendo el fin
        $min_hora = intdiv($min_hora, $ancho) * $ancho;
        $max_hora = intdiv($max_hora - $min_hora - 1, $ancho) * $ancho + $ancho + $min_hora;
        $n_frajas = intdiv($max_hora - $min_hora, $ancho);

        // 3) Distribuir cada partido en su celda [franja][día]
        $celdas = array(); // idx_franja => dia => array de líneas
        foreach ($datos as $fila) {
            $fecha = substr((string) ($fila['fecha_competencia'] ?? ''), 0, 10);
            if ($fecha === '') continue;

            $hi = substr((string) ($fila['hora_inicio'] ?? ''), 0, 5);
            $hf = substr((string) ($fila['hora_fin'] ?? ''), 0, 5);
            $h_ini = (int) substr((string) ($fila['hora_inicio'] ?? '0'), 0, 2);
            $idx = intdiv(max(0, $h_ini - $min_hora), $ancho);
            if ($idx >= $n_frajas) $idx = $n_frajas - 1;

            $equipos = trim((string) ($fila['ute_1_nombre'] ?? ''));
            $v = trim((string) ($fila['ute_2_nombre'] ?? ''));
            if ($equipos !== '' && $v !== '') {
                $equipos .= ' vs ' . $v;
            } elseif ($v !== '') {
                $equipos = $v;
            }
            if ($equipos === '') $equipos = null;

            $linea = $hi . ($hf !== '' && $hf !== '00:00' ? '–' . $hf : '')
                . ' · ' . trim(($fila['nombre_deporte'] ?? '') . ' ' . ($fila['nombre_categoria'] ?? ''))
                . ' · ' . $equipos;
            if (!empty($fila['nombre_prueba'])) $linea .= ' — ' . $fila['nombre_prueba'];
            if (!empty($fila['lugar_nombre'])) $linea .= ' @ ' . $fila['lugar_nombre'];

            $celdas[$idx][$fecha][] = $linea;
        }

        // 4) Cabecera: Horario | Lun 03/11 | Mié 05/11 | ...
        $cabecera = array('Horario');
        $dias_abr = array('Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb');
        foreach ($dias as $f) {
            $ts = strtotime($f);
            $cabecera[] = $dias_abr[(int) date('w', $ts)] . ' ' . date('d/m', $ts);
        }
        fputcsv($output, $cabecera, ';');

        // 5) Una fila por franja horaria
        for ($i = 0; $i < $n_frajas; $i++) {
            $h_ini = $min_hora + $i * $ancho;
            $h_fin = $h_ini + $ancho;
            $fila_csv = array(sprintf('%02d:00–%02d:00', $h_ini % 24, $h_fin % 24));
            foreach ($dias as $f) {
                $fila_csv[] = isset($celdas[$i][$f]) ? implode("\n", $celdas[$i][$f]) : '';
            }
            fputcsv($output, $fila_csv, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Descarga el fixture cargado (todas las categorías) en formato PDF usando TCPDF.
     * Reemplaza a la descarga CSV del fixture. El diseño vive en la plantilla
     * application/views/admin/reporte_fixture.php.
     * Opciones de URL (las mismas que usaba el CSV):
     *   ?formato=lista    listado tradicional (una fila por partido). Por defecto: cuadro.
     *   ?dia=AAAA-MM-DD   imprime solo ese día; sin parámetro se imprime TODO el fixture.
     *   ?franja=HH        ancho en horas de las franjas horarias del cuadro (1-12).
     */
    public function descargar_pdf_fixture() {
        if (!$this->session->userdata('is_organizador')
            || !in_array($this->session->userdata('user_rol'), array('superadmin', 'admin', 'solo_lectura'))) {
            redirect('Inscripciones/login');
        }

        $this->load->model('Fixture_model');

        // Deporte opcional elegido desde el panel (?deporte=ID). Si no viene o es
        // inválido, se genera el reporte con TODOS los deportes (comportamiento previo).
        $id_deporte_filtro = (int) $this->input->get('deporte');
        if ($id_deporte_filtro <= 0) {
            $id_deporte_filtro = NULL;
        }

        $nombre_deporte_filtro = NULL;
        if ($id_deporte_filtro !== NULL) {
            $nombre_deporte_filtro = $this->_nombre_deporte($id_deporte_filtro);
            if ($nombre_deporte_filtro === NULL) {
                $id_deporte_filtro = NULL; // deporte inexistente: reporte general
            }
        }

        try {
            $datos = $this->Fixture_model->obtener_todo_el_fixture($id_deporte_filtro);
        } catch (Throwable $e) {
            show_error('No se pudo obtener el fixture: ' . $e->getMessage(), 500);
            return;
        }

        $this->_emitir_pdf_fixture($datos, $nombre_deporte_filtro);
    }

    /**
     * Descarga el fixture PROPIO de la delegación del delegado (mismo formato
     * PDF que el reporte de superadmin), con filtro opcional por deporte
     * (?deporte=ID, solo deportes en los que la delegación compite).
     */
    public function descargar_pdf_fixture_delegado() {
        if (!$this->session->userdata('is_delegado')) {
            redirect('Inscripciones/login');
        }

        $this->load->model('Fixture_model');
        $this->load->model('Participante_model');

        $delegacion = $this->session->userdata('user_nombre');

        // Deporte opcional (?deporte=ID). Solo se acepta si es un deporte en el
        // que la delegación tiene competidores inscriptos.
        $id_deporte_filtro = (int) $this->input->get('deporte');
        $nombre_deporte_filtro = NULL;
        if ($id_deporte_filtro > 0) {
            foreach ($this->Participante_model->obtener_deportes_de_delegacion($delegacion) as $dep) {
                if ((int) $dep['id_deporte'] === $id_deporte_filtro) {
                    $nombre_deporte_filtro = $dep['nombre_deporte'];
                    break;
                }
            }
            if ($nombre_deporte_filtro === NULL) {
                $id_deporte_filtro = NULL; // deporte ajeno: reporte de toda la delegación
            }
        } else {
            $id_deporte_filtro = NULL;
        }

        try {
            $datos = $this->Fixture_model->obtener_fixture_por_delegacion($delegacion, $id_deporte_filtro);
        } catch (Throwable $e) {
            show_error('No se pudo obtener el fixture: ' . $e->getMessage(), 500);
            return;
        }

        $this->_emitir_pdf_fixture($datos, $nombre_deporte_filtro, $delegacion);
    }

    /**
     * Descarga la PLANILLA DE MESA DE CONTROL de una categoría: PDF con todos
     * los partidos de esa categoría (deportes de enfrentamiento) o con los
     * participantes de la jornada (deportes masivos), pensado para imprimir y
     * llevar a las mesas de control. Reutiliza la plantilla admin/reporte_fixture
     * con formato='planilla'.
     */
    public function descargar_planilla_categoria($id_categoria = null) {
        if (!$this->session->userdata('is_organizador')
            || !in_array($this->session->userdata('user_rol'), array('superadmin', 'admin', 'solo_lectura'))) {
            redirect('Inscripciones/login');
        }

        $id_categoria = (int) ($id_categoria !== null ? $id_categoria : $this->input->get('categoria'));
        if ($id_categoria <= 0) {
            show_error('Categoría inválida.', 400);
            return;
        }

        $this->load->model('Fixture_model');
        $datos = $this->Fixture_model->obtener_fixtures_por_categoria($id_categoria);
        if (!$datos) {
            show_error('La categoría no tiene fixture cargado todavía.', 404);
            return;
        }

        // Datos de la categoría (para el título/nombre del archivo)
        $categoria_info = null;
        foreach ($this->Fixture_model->obtener_categorias_para_fixture() as $c) {
            if ((int) $c['id_categoria'] === $id_categoria) {
                $categoria_info = $c;
                break;
            }
        }
        $nombre_cat = $categoria_info
            ? trim(($categoria_info['nombre_deporte'] ?? '') . ' ' . ($categoria_info['nombre_categoria'] ?? ''))
            : 'categoria_' . $id_categoria;

        $nombre_archivo = 'planilla_'
            . preg_replace('/[^a-zA-Z0-9]+/', '_', $nombre_cat)
            . '_' . date('Y-m-d') . '.pdf';

        $this->load->vars(array(
            'datos'             => $datos,
            'formato'           => 'planilla',
            'dia_filtro'        => NULL,
            'ancho_franja'      => 1,
            'nombre_archivo'    => $nombre_archivo,
            'deporte_filtro'    => $categoria_info['nombre_deporte'] ?? NULL,
            'orden_reporte'     => 'deporte',
            'delegacion_filtro' => NULL,
            'nombres_completos' => TRUE,
            'modo_delegado'     => FALSE,
            'titulo_planilla'   => strtoupper($nombre_cat),
        ));
        $this->load->view('admin/reporte_fixture');
    }

    /**
     * Descarga la PLANILLA DE MESA DE CONTROL de un DEPORTE: PDF con una hoja
     * (página) por categoría —cada una con los partidos de esa categoría
     * (deportes de enfrentamiento) o con los participantes de la jornada
     * (deportes masivos)—, pensado para imprimir y llevar a las mesas de
     * control. Reutiliza la plantilla admin/reporte_fixture con
     * formato='planilla'; el agrupado "una categoría por hoja" lo hace la
     * propia vista (MYPDF::_body_planilla).
     */
    public function descargar_planilla_deporte($id_deporte = null) {
        if (!$this->session->userdata('is_organizador')
            || !in_array($this->session->userdata('user_rol'), array('superadmin', 'admin', 'solo_lectura'))) {
            redirect('Inscripciones/login');
        }

        $id_deporte = (int) ($id_deporte !== null ? $id_deporte : $this->input->get('deporte'));
        if ($id_deporte <= 0) {
            show_error('Deporte inválido.', 400);
            return;
        }

        $nombre_deporte = $this->_nombre_deporte($id_deporte);
        if ($nombre_deporte === NULL) {
            show_error('El deporte no existe.', 404);
            return;
        }

        $this->load->model('Fixture_model');
        // Fixture completo del deporte: todas sus categorías con fixture, con
        // los integrantes de cada equipo resueltos (jugadores_ute_X).
        $datos = $this->Fixture_model->obtener_todo_el_fixture($id_deporte);
        if (!$datos) {
            show_error('El deporte no tiene fixture cargado todavía.', 404);
            return;
        }

        $nombre_archivo = 'planillas_'
            . preg_replace('/[^a-zA-Z0-9]+/', '_', $nombre_deporte)
            . '_' . date('Y-m-d') . '.pdf';

        $this->load->vars(array(
            'datos'             => $datos,
            'formato'           => 'planilla',
            'dia_filtro'        => NULL,
            'ancho_franja'      => 1,
            'nombre_archivo'    => $nombre_archivo,
            'deporte_filtro'    => $nombre_deporte,
            'orden_reporte'     => 'deporte',
            'delegacion_filtro' => NULL,
            'nombres_completos' => TRUE,
            'modo_delegado'     => FALSE,
            // Sin título fijo: la vista arma uno por categoría en cada hoja.
            'titulo_planilla'   => NULL,
            'deporte_planilla'  => $nombre_deporte,
        ));
        $this->load->view('admin/reporte_fixture');
    }


    /**
     * Descarga las planillas VACÍAS (PDF fijo de assets/docs/planillas_vacias.pdf):
     * 2 páginas para completar a mano (participantes / partidos). No depende de
     * filtros ni de la BD; se sirve con readfile() para que quede dentro del
     * controlador y funcione aunque los assets estáticos no sean accesibles.
     */
    public function descargar_planillas_vacias() {
        if (!$this->session->userdata('is_organizador')
            || !in_array($this->session->userdata('user_rol'), array('superadmin', 'admin', 'solo_lectura'))) {
            redirect('Inscripciones/login');
        }

        $ruta_archivo = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'planillas_vacias.pdf';

        if (!is_file($ruta_archivo) || !is_readable($ruta_archivo)) {
            show_error('No se encontró el archivo de planillas vacías.', 404);
            return;
        }

        // Evitar que el output previo (buffers de CI) corrompa el PDF.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $nombre_archivo = 'planillas_vacias_' . date('Y-m-d') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Content-Length: ' . filesize($ruta_archivo));
        header('Cache-Control: public, must-revalidate');
        header('Pragma: public');

        readfile($ruta_archivo);
        exit;
    }

    /** Nombre de un deporte por id (NULL si no existe). */
    private function _nombre_deporte($id_deporte) {
        $this->load->model('Deporte_model');
        foreach ($this->Deporte_model->obtener_todos_los_deportes() as $dep) {
            if ((int) $dep['id_deporte'] === (int) $id_deporte) {
                return $dep['nombre_deporte'];
            }
        }
        return NULL;
    }

    /**
     * Arma los parámetros del reporte y dispara la plantilla admin/reporte_fixture,
     * que construye el PDF con TCPDF y lo descarga (Output ..., 'D').
     * Compartida por el reporte general (superadmin/admin) y el de delegados.
     */
    private function _emitir_pdf_fixture($datos, $nombre_deporte_filtro, $delegacion = NULL) {
        $formato = $this->input->get('formato') === 'lista' ? 'lista' : 'cuadro';

        // Día opcional elegido desde el panel (si no viene, se imprimen TODOS los días)
        $dia_filtro = trim((string) $this->input->get('dia'));
        if ($dia_filtro !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia_filtro)) {
            $dia_filtro = '';
        }

        $ancho_franja = (int) $this->input->get('franja');
        if ($ancho_franja < 1) $ancho_franja = 1;
        if ($ancho_franja > 12) $ancho_franja = 12;

        // Orden del listado: con ?orden=horario las filas salen por Fecha → Hora
        // (en vez de Deporte → Categoría → Fecha → Hora).
        $orden_reporte = $this->input->get('orden') === 'horario' ? 'horario' : 'deporte';

        // Nombres de los integrantes en los enfrentamientos (solo se imprimen en
        // el reporte del delegado): por defecto COMPLETOS ("Maria Paula Gomez"),
        // para que el delegado vea bien quiénes juegan; con ?nombres=cortos se
        // abrevian con iniciales ("María P. G.").
        $nombres_completos = $this->input->get('nombres') !== 'cortos';

        $nombre_archivo = 'fixture_' . $formato
            . ($nombre_deporte_filtro !== NULL
                ? '_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $nombre_deporte_filtro)
                : '_todos')
            . ($dia_filtro !== '' ? '_' . $dia_filtro : '_todos');
        if ($delegacion !== NULL) {
            $nombre_archivo .= '_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $delegacion);
        }
        $nombre_archivo .= '_' . date('Y-m-d') . '.pdf';

        $this->load->vars(array(
            'datos'             => $datos,
            'formato'           => $formato,
            'dia_filtro'        => $dia_filtro !== '' ? $dia_filtro : NULL,
            'ancho_franja'      => $ancho_franja,
            'nombre_archivo'    => $nombre_archivo,
            'deporte_filtro'    => $nombre_deporte_filtro,
            'orden_reporte'     => $orden_reporte,
            'delegacion_filtro' => $delegacion,
            // Nombres de los integrantes: completos por defecto (el reporte
            // general del superadmin igual solo imprime el nombre del equipo).
            'nombres_completos' => $nombres_completos,
            // Flag del modo delegado: la vista lista los INTEGRANTES de los
            // equipos (en vez del nombre del equipo) solo si es TRUE.
            'modo_delegado'     => ($delegacion !== NULL),
        ));
        $this->load->view('admin/reporte_fixture');
    }

    /** Borrar todo el fixture de una categoría. */
    public function ajax_eliminar_fixture() {
        if (!$this->_fixture_auth_json()) return;
        $this->bloqueo_solo_lectura(TRUE);

        $id_categoria = (int) $this->input->post('id_categoria');
        $this->Fixture_model->eliminar_fixture_por_categoria($id_categoria);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('ok' => true, 'mensaje' => 'Fixture eliminado.')));
    }

    /** Borrar un partido individual. */
    public function ajax_eliminar_partido() {
        if (!$this->_fixture_auth_json()) return;
        $this->bloqueo_solo_lectura(TRUE);

        $id_fixture = (int) $this->input->post('id_fixture');
        $this->Fixture_model->eliminar_partido($id_fixture);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('ok' => true, 'mensaje' => 'Partido eliminado.')));
    }

    /* ============================================================
     *  RESULTADOS (pestaña de Control Total) - endpoints AJAX
     * ============================================================ */

    private function _resultados_auth_json() {
        // Solo superadmin/admin pueden gestionar resultados
        if (!$this->session->userdata('is_organizador')
            || !in_array($this->session->userdata('user_rol'), array('superadmin', 'admin', 'solo_lectura'))) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => 'No autorizado')));
            return false;
        }
        $this->load->model('Resultado_model');
        return true;
    }

    /** Guarda un resultado cargado desde la pestaña "Resultados". */
    public function ajax_guardar_resultado() {
        try {
            if (!$this->_resultados_auth_json()) return;

            // Seguridad: los usuarios 'solo_lectura' no pueden crear/modificar resultados.
            $this->bloqueo_solo_lectura(TRUE);

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
                    'hubo_desempate' => !empty($res['hubo_desempate']),
                    'desempate_metodo' => isset($res['desempate_metodo']) ? $res['desempate_metodo'] : '',
                    'ganador_nombre' => isset($res['ganador_nombre']) ? $res['ganador_nombre'] : '',
                    'clasifico' => !empty($res['clasifico']),
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

            // Deporte MASIVO_TIEMPO: si la categoría todavía no tiene jornada en
            // el fixture, se crea automáticamente la JORNADA_UNICA. Sin esto, la
            // pestaña Resultados no encuentra a qué jornada vincular los inscriptos
            // y la lista de participantes quedaba vacía.
            if ($this->Resultado_model->modalidad_de_categoria((int) $id_categoria) === 'MASIVO_TIEMPO') {
                $this->Resultado_model->asegurar_jornada_masiva((int) $id_categoria);
            }

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

            // Seguridad: los usuarios 'solo_lectura' no pueden eliminar resultados.
            $this->bloqueo_solo_lectura(TRUE);

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

    /* ============================================================
     *  PREMIACIÓN (sección del menú principal) - endpoints AJAX
     * ============================================================ */

    private function _premiacion_auth_json() {
        if (!$this->session->userdata('is_organizador')
            || !in_array($this->session->userdata('user_rol'), array('superadmin', 'admin', 'solo_lectura'))) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => 'No autorizado')));
            return false;
        }
        $this->load->model('Premiacion_model');
        return true;
    }

    /**
     * Resumen de la noche: qué competencias cerradas se premian en la fecha
     * pedida (?fecha=YYYY-MM-DD, por defecto hoy), con su podio resuelto y
     * el estado de entrega. Incluye además el histórico ya entregado.
     */
    public function ajax_premiacion_noche() {
        try {
            if (!$this->_premiacion_auth_json()) return;

            if (!$this->Premiacion_model->tablas_existentes()) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'ok' => false,
                        'error' => 'Falta la tabla de premiaciones. Ejecutá el script sql/premiaciones.sql y recargá la página.'
                    )));
                return;
            }

            $fecha = trim((string) $this->input->get('fecha'));
            $ts = $fecha ? strtotime($fecha) : strtotime('today');
            if (!$ts) $ts = strtotime('today');
            $fecha = date('Y-m-d', $ts);

            $items = $this->Premiacion_model->obtener_resumen_noche($fecha);
            // Entregas de noches anteriores que quedaron pendientes: se muestran
            // aparte para que no "contaminen" el resumen del día.
            foreach ($this->Premiacion_model->obtener_atrasos_hasta($fecha) as $it) {
                $items[] = array_merge($it, array('atraso' => true));
            }
            // Categorías con resultados cargados pero sin fecha de competencia
            // en el fixture: no pueden asignarse a ninguna noche (se avisan).
            $sin_fecha = $this->Premiacion_model->obtener_sin_fecha();
            $premiados = array();   // categorías con todos sus puestos confirmados
            $pendientes = array();  // categorías listas para premiar (o parciales)
            foreach ($items as $it) {
                $entregados = array();
                foreach ($it['entregadas'] as $e) {
                    $entregados[(int) $e['puesto']] = $e;
                }
                $fila = array(
                    'categoria'     => $it['categoria'],
                    'origen'        => $it['origen'],
                    'motivo'        => $it['motivo'],
                    'fecha_entrega' => $it['fecha_entrega'],
                    'podio'         => $it['podio'],
                    'entregados'    => $entregados,
                    'atraso'        => !empty($it['atraso']),
                    // Tabla de puntos (deportes ENFRENTAMIENTO por puntaje 3/1/0)
                    'tabla_puntaje' => isset($it['tabla_puntaje']) ? $it['tabla_puntaje'] : null,
                );
                $puestos_esperados = array_keys($it['podio']);
                $completa = !empty($puestos_esperados);
                foreach ($puestos_esperados as $p) {
                    if (!isset($entregados[$p])) { $completa = false; break; }
                }
                if ($completa) $premiados[] = $fila;
                else $pendientes[] = $fila;
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'ok' => true,
                    'fecha' => $fecha,
                    'pendientes' => $pendientes,
                    'premiados' => $premiados,
                    'sin_fecha' => $sin_fecha,
                )));
        } catch (Throwable $e) {
            log_message('error', '[Premiación] ' . $e->getMessage());
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }

    /** Confirma la entrega del podio de una categoría en la noche indicada. */
    public function ajax_confirmar_premiacion() {
        try {
            if (!$this->_premiacion_auth_json()) return;

            $id_cat = (int) $this->input->post('id_categoria');
            $fecha = trim((string) $this->input->post('fecha_entrega'));
            $puestos_raw = $this->input->post('puestos');
            if (is_string($puestos_raw)) {
                $puestos = json_decode(stripslashes($puestos_raw), true);
            } else {
                $puestos = $puestos_raw;
            }
            if (!is_array($puestos)) $puestos = array();

            $cant = $this->Premiacion_model->confirmar_podio(
                $id_cat, $puestos, $fecha, $this->session->userdata('user_id'),
                $this->input->post('observaciones'));
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'ok' => true,
                    'mensaje' => 'Premiación registrada: ' . $cant . ' puesto/s confirmados.'
                )));
        } catch (Throwable $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }

    /** Anula la premiación registrada de una categoría (vuelve a pendiente). */
    public function ajax_anular_premiacion() {
        try {
            if (!$this->_premiacion_auth_json()) return;

            $id_cat = (int) $this->input->post('id_categoria');
            if (!$id_cat) throw new Exception('Categoría inexistente.');
            $this->Premiacion_model->anular_premiacion($id_cat);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => true, 'mensaje' => 'Premiación anulada.')));
        } catch (Throwable $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'error' => $e->getMessage())));
        }
    }

    public function detalle_ajax($id_participante) {
        // Validar que el usuario esté logueado (staff o delegado)
        if (!$this->session->userdata('is_organizador') && !$this->session->userdata('is_delegado')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autorizado']);
            return;
        }
        
        // Si es delegado, verificar que el participante sea de su delegación
        if ($this->session->userdata('is_delegado')) {
            $this->load->model('Participante_model');
            $participante = $this->Participante_model->obtener_detalle_participante($id_participante);
            $user_nombre = $this->session->userdata('user_nombre');
            
            if (!$participante || $participante['delegacion'] !== $user_nombre) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'No tenés permiso para ver este participante']);
                return;
            }
        }
        
        if (!$id_participante) {
            echo json_encode(['error' => 'ID no válido']);
            return;
        }

        $this->load->model('Participante_model');
        $data = $this->Participante_model->obtener_detalle_participante($id_participante);

        // Seteamos la cabecera para decirle al navegador que es un JSON puro
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Eliminar una inscripción completa (participante y todas sus inscripciones deportivas)
     */
    public function eliminar_inscripcion($id_participante) {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Inscripciones/inscriptos');
            return;
        }

        // Rol "solo lectura": no puede eliminar inscripciones
        $this->bloqueo_solo_lectura();

        if (!$id_participante) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Inscripciones/inscriptos');
            return;
        }

        $this->load->model('Participante_model');
        
        // Iniciamos transacción para borrar todo de forma atómica
        $this->db->trans_start();
        
        // 1. Primero borramos las inscripciones deportivas relacionadas
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('inscripciones_deportivas');
        
        // 2. Luego borramos al participante
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('participantes');
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === TRUE) {
            $this->session->set_flashdata('success', 'Inscripción eliminada correctamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al eliminar la inscripción. Intente nuevamente.');
        }
        
        redirect('Inscripciones/inscriptos');
    }

    /**
     * Endpoint AJAX para eliminar inscripción sin recargar página
     */
    public function eliminar_inscripcion_ajax($id_participante) {
        header('Content-Type: application/json');
        
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode([
                'success' => false,
                'error' => 'No tenés permisos para realizar esta acción.'
            ]);
            return;
        }

        // Rol "solo lectura": no puede eliminar inscripciones
        $this->bloqueo_solo_lectura(TRUE);

        if (!$id_participante) {
            echo json_encode([
                'success' => false,
                'error' => 'ID de participante no válido.'
            ]);
            return;
        }

        $this->load->model('Participante_model');
        
        // Iniciamos transacción para borrar todo de forma atómica
        $this->db->trans_start();
        
        // 1. Primero borramos las inscripciones deportivas relacionadas
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('inscripciones_deportivas');
        
        // 2. Luego borramos al participante
        $this->db->where('id_participante', $id_participante);
        $this->db->delete('participantes');
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === TRUE) {
            echo json_encode([
                'success' => true,
                'mensaje' => 'Inscripción eliminada correctamente.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Error al eliminar la inscripción. Intente nuevamente.'
            ]);
        }
    }

    /**
     * Mostrar formulario para modificar una inscripción (staff)
     */
    public function modificar_inscripcion($id_participante) {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Inscripciones/inscriptos');
            return;
        }

        // Rol "solo lectura": no puede modificar inscripciones
        $this->bloqueo_solo_lectura();

        if (!$id_participante) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Inscripciones/inscriptos');
            return;
        }

        $this->load->model('Participante_model');
        $this->load->model('Deporte_model');
        
        // Obtener datos del participante
        $data['participante'] = $this->Participante_model->obtener_detalle_participante($id_participante);
        
        if (!$data['participante']) {
            $this->session->set_flashdata('error', 'Participante no encontrado.');
            redirect('Inscripciones/inscriptos');
            return;
        }
        
        // Obtener todos los deportes para el formulario
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        
        // Cargar vista de edición
        $this->load->view('admin/formulario_editar_inscripcion', $data);
    }

    /**
     * Guardar modificaciones de una inscripción
     */
    public function guardar_modificacion() {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Inscripciones/inscriptos');
            return;
        }

        // Rol "solo lectura": no puede guardar modificaciones
        $this->bloqueo_solo_lectura();

        $this->load->model('Participante_model');
        $post = $this->input->post();

        if (empty($post['id_participante'])) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Inscripciones/inscriptos');
            return;
        }

        $id_participante = $post['id_participante'];

        // Estructura de datos actualizados
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
            
            // Traducción de rol
            'es_competidor'       => ($post['rol_asistente'] === 'competidor') ? 1 : 0,
            
            // Solo puede ser delegado si es competidor y tildó el checkbox
            'es_delegado'         => (isset($post['es_delegado']) && $post['rol_asistente'] === 'competidor') ? 1 : 0,
        ];

        // CONTROL Y CAPTURA DE DISCIPLINAS + PANEL UTE (para edición)
        $deportes_seleccionados = [];
        
        if ($post['rol_asistente'] === 'competidor' && isset($post['categoria_id'])) {
            foreach ($post['categoria_id'] as $index => $cat_id) {
                if (!empty($cat_id)) {
                    $deportes_seleccionados[] = [
                        'id_inscripcion' => isset($post['id_inscripcion'][$index]) ? $post['id_inscripcion'][$index] : null,
                        'id_deporte'   => isset($post['deporte_id'][$index]) ? $post['deporte_id'][$index] : null,
                        'id_categoria' => $cat_id,
                        'tiene_ute'    => isset($post['tiene_ute'][$index]) ? (int)$post['tiene_ute'][$index] : 0,
                        'necesita_ute' => isset($post['necesita_ute'][$index]) ? (int)$post['necesita_ute'][$index] : 0,
                        'detalle_ute'  => isset($post['detalle_ute'][$index]) ? mb_strtoupper(trim($post['detalle_ute'][$index]), 'UTF-8') : ''
                    ];
                }
            }
        }

        // Ejecutar actualización
        $resultado = $this->Participante_model->actualizar_completo(
            $id_participante, 
            $data_persona, 
            $deportes_seleccionados
        );

        if ($resultado) {
            $this->session->set_flashdata('success', 'Inscripción modificada correctamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al modificar la inscripción. Intente nuevamente.');
        }

        redirect('Inscripciones/inscriptos');
    }

    public function nueva_inscripcion() {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Inscripciones/inscriptos');
            return;
        }

        // Rol "solo lectura": no puede crear inscripciones
        $this->bloqueo_solo_lectura();

        $this->load->model('Deporte_model');
        
        // Obtener todos los deportes para el formulario
        $data['deportes'] = $this->Deporte_model->obtener_todos_los_deportes();
        
        // Cargar vista de nueva inscripción
        $this->load->view('admin/formulario_nueva_inscripcion', $data);
    }

    /**
     * Guardar nueva inscripción
     */
    public function guardar_nueva_inscripcion() {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Inscripciones/inscriptos');
            return;
        }

        // Rol "solo lectura": no puede guardar nuevas inscripciones
        $this->bloqueo_solo_lectura();

        $this->load->model('Participante_model');
        $post = $this->input->post();

        // Concatenación APELLIDO, NOMBRE -> nombre_completo (con respaldo en el servidor)
        $apellido = trim($post['apellido'] ?? '');
        $nombre_p = trim($post['nombre'] ?? '');
        if ($apellido !== '' && $nombre_p !== '') {
            $nombre_completo = $apellido . ', ' . $nombre_p;
        } else {
            // Si por algún motivo no vienen los campos separados, usar el campo oculto del formulario
            $nombre_completo = trim($post['nombre_completo'] ?? '');
        }

        // Estructura de datos nuevos
        $data_persona = [
            'dni'                 => trim($post['dni']),
            'nombre_completo'     => mb_strtoupper($nombre_completo, 'UTF-8'),
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
            
            // Traducción de rol
            'es_competidor'       => ($post['rol_asistente'] === 'competidor') ? 1 : 0,
            
            // Solo puede ser delegado si es competidor y tildó el checkbox
            'es_delegado'         => (isset($post['es_delegado']) && $post['rol_asistente'] === 'competidor') ? 1 : 0,
            
            // Fecha de inscripción actual
            'fecha_inscripcion'   => date('Y-m-d H:i:s'),
            
            // Generar token QR único
            'token_qr'            => bin2hex(random_bytes(16))
        ];

        // CONTROL Y CAPTURA DE DISCIPLINAS + PANEL UTE
        $deportes_seleccionados = [];
        
        if ($post['rol_asistente'] === 'competidor' && isset($post['categoria_id'])) {
            foreach ($post['categoria_id'] as $index => $cat_id) {
                if (!empty($cat_id)) {
                    $deportes_seleccionados[] = [
                        'id_deporte'   => isset($post['deporte_id'][$index]) ? $post['deporte_id'][$index] : null,
                        'id_categoria' => $cat_id,
                        'tiene_ute'    => isset($post['tiene_ute'][$index]) ? (int)$post['tiene_ute'][$index] : 0,
                        'necesita_ute' => isset($post['necesita_ute'][$index]) ? (int)$post['necesita_ute'][$index] : 0,
                        'detalle_ute'  => isset($post['detalle_ute'][$index]) ? mb_strtoupper(trim($post['detalle_ute'][$index]), 'UTF-8') : ''
                    ];
                }
            }
        }

        // Ejecutar inserción
        $resultado = $this->Participante_model->insertar_completo(
            $data_persona, 
            $deportes_seleccionados
        );

        if ($resultado) {
            $this->session->set_flashdata('success', 'Nueva inscripción guardada correctamente.');
        } else {
            $this->session->set_flashdata('error', 'Error al guardar la nueva inscripción. Intente nuevamente.');
        }

        redirect('Inscripciones/inscriptos');
    }

    

    /**
     * AJAX: Obtener participantes disponibles para una categoría
     */
    public function ajax_participantes_disponibles($id_categoria) {
        header('Content-Type: application/json');
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['error' => 'No tenés permisos']);
            return;
        }

        $this->load->model('UTE_model');
        $participantes = $this->UTE_model->obtener_participantes_disponibles($id_categoria);
        
        // Asegurarse de que siempre devolvemos un array válido
        if (empty($participantes)) {
            echo json_encode([]);
        } else {
            echo json_encode($participantes);
        }
    }

    /**
     * AJAX: Crear nueva UTE
     */
    public function ajax_crear_ute() {
        header('Content-Type: application/json');
        
        // Guard unificado: sesion + rol, SIEMPRE en JSON (nunca 403 HTML).
        if (!$this->_ute_auth_write_json()) {
            return;
        }

        $nombre_ute = $this->input->post('nombre_ute');
        $id_categoria = $this->input->post('id_categoria');
        $genero = $this->input->post('genero');

        if (empty($nombre_ute) || empty($id_categoria)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $this->load->model('UTE_model');
        $id_ute = $this->UTE_model->insertar_ute($nombre_ute, $id_categoria, $genero);

        if ($id_ute) {
            echo json_encode(['success' => true, 'id_ute' => $id_ute]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear la UTE']);
        }
    }

    /**
     * AJAX: Actualizar una UTE (nombre y/o género)
     */
    public function ajax_actualizar_ute() {
        header('Content-Type: application/json');

        // Guard unificado: sesion + rol, SIEMPRE en JSON (nunca 403 HTML).
        if (!$this->_ute_auth_write_json()) {
            return;
        }

        $id_ute = $this->input->post('id_ute');
        $nombre_ute = $this->input->post('nombre_ute');
        $genero = $this->input->post('genero');

        if (empty($id_ute)) {
            echo json_encode(['success' => false, 'error' => 'ID de UTE no válido']);
            return;
        }

        $generos_validos = array('FEMENINO', 'MASCULINO', 'MIXTO');
        if ($genero !== null && $genero !== '' && !in_array(strtoupper(trim($genero)), $generos_validos, true)) {
            echo json_encode(['success' => false, 'error' => 'Género no válido']);
            return;
        }

        $this->load->model('UTE_model');
        $resultado = $this->UTE_model->actualizar_ute(
            $id_ute,
            $nombre_ute,
            ($genero !== null && $genero !== '') ? strtoupper(trim($genero)) : null
        );

        if ($resultado) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar la UTE']);
        }
    }

    /**
     * AJAX: Agregar participante a UTE
     */
    public function ajax_agregar_participante_ute() {
        header('Content-Type: application/json');
        
        // Guard unificado: sesion + rol, SIEMPRE en JSON (nunca 403 HTML).
        if (!$this->_ute_auth_write_json()) {
            return;
        }

        $id_ute = $this->input->post('id_ute');
        $id_participante = $this->input->post('id_participante');

        if (empty($id_ute) || empty($id_participante)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $this->load->model('UTE_model');
        
        // Verificar si ya tiene UTE
        $ute_info = $this->db->select('id_categoria')->from('utes')->where('id_ute', $id_ute)->get()->row_array();
        if ($ute_info && $this->UTE_model->participante_ya_tiene_ute($id_participante, $ute_info['id_categoria'])) {
            echo json_encode(['success' => false, 'error' => 'El participante ya está en una UTE de esta categoría']);
            return;
        }

        $resultado = $this->UTE_model->agregar_participante_a_ute($id_ute, $id_participante);

        if ($resultado) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al agregar el participante']);
        }
    }

    /**
     * AJAX: Eliminar participante de UTE
     */
    public function ajax_eliminar_participante_ute() {
        header('Content-Type: application/json');
        
        // Guard unificado: sesion + rol, SIEMPRE en JSON (nunca 403 HTML).
        if (!$this->_ute_auth_write_json()) {
            return;
        }

        $id_ute = $this->input->post('id_ute');
        $id_participante = $this->input->post('id_participante');

        if (empty($id_ute) || empty($id_participante)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $this->load->model('UTE_model');
        $resultado = $this->UTE_model->eliminar_participante_de_ute($id_ute, $id_participante);

        if ($resultado) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar']);
        }
    }

    /**
     * AJAX: Eliminar UTE completa
     */
    public function ajax_eliminar_ute() {
        header('Content-Type: application/json');
        
        // Guard unificado: sesion + rol, SIEMPRE en JSON (nunca 403 HTML).
        if (!$this->_ute_auth_write_json()) {
            return;
        }

        $id_ute = $this->input->post('id_ute');

        if (empty($id_ute)) {
            echo json_encode(['success' => false, 'error' => 'ID de UTE no válido']);
            return;
        }

        $this->load->model('UTE_model');
        $resultado = $this->UTE_model->eliminar_ute($id_ute);

        if ($resultado) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar la UTE']);
        }
    }

    /**
     * AJAX: Obtener detalles de una UTE con sus integrantes
     */
    public function ajax_detalle_ute($id_ute) {
        header('Content-Type: application/json');
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['error' => 'No tenés permisos']);
            return;
        }

        $this->load->model('UTE_model');
        $ute = $this->UTE_model->obtener_ute_con_integrantes($id_ute);
        
        echo json_encode($ute);
    }
    
    // ==========================================
    // VISTAS PARA DELEGADOS
    // ==========================================
    
    /**
     * Panel principal para delegados - muestra información de su delegación
     */
    public function panel_delegado() {
        if (!$this->session->userdata('is_delegado')) {
            redirect('Inscripciones/login');
        }
        
        $this->load->model('Participante_model');
        
        // Obtener la delegación del usuario logueado
        $user_nombre = $this->session->userdata('user_nombre');
        
        // Buscar todos los participantes de esa delegación (con es_competidor)
        $data['participantes'] = $this->Participante_model->obtener_participantes_por_delegacion_completo($user_nombre);
        $data['total_inscriptos'] = count($data['participantes']);
        $data['delegacion'] = $user_nombre;

        // Deportes en los que la delegación compite: alimentan el filtro del
        // botón "Descargar reporte de fixture" (PDF, mismo formato que superadmin).
        $data['deportes_delegacion'] = $this->Participante_model->obtener_deportes_de_delegacion($user_nombre);
        
        $this->load->view('admin/panel_delegado', $data);
    }
    
    /**
     * Descarga la lista de inscriptos en formato CSV
     */
    public function descargar_csv_inscriptos() {
        if (!$this->session->userdata('is_delegado')) {
            redirect('Inscripciones/login');
        }
        
        $this->load->model('Participante_model');
        
        // Obtener la delegación del usuario logueado
        $delegacion = $this->session->userdata('user_nombre');
        
        // Obtener datos para el CSV
        $datos = $this->Participante_model->obtener_participantes_para_csv($delegacion);
        
        // Nombre del archivo
        $nombre_archivo = 'inscriptos_' . str_replace(' ', '_', strtolower($delegacion)) . '_' . date('Y-m-d') . '.csv';
        
        // Configurar headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        
        // Crear el output
        $output = fopen('php://output', 'w');
        
        // Agregar BOM para que Excel reconozca UTF-8 correctamente
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Escribir encabezados con punto y coma como delimitador
        fputcsv($output, ['DNI', 'Nombre Completo', 'Sexo', 'Fecha de Nacimiento', 'Edad', 'Delegación', 'Dieta Especial', 'Deporte', 'Categoría'], ';');
        
        // Escribir datos con punto y coma como delimitador
        foreach ($datos as $fila) {
            fputcsv($output, [
                $fila['dni'],
                $fila['nombre_completo'],
                $fila['sexo'],
                $fila['fecha_nacimiento'],
                $fila['edad'],
                $fila['delegacion'],
                $fila['dieta_especial'] ?? '',
                $fila['deporte'],
                $fila['categoria']
            ], ';');
        }
        
        fclose($output);
        exit;
    }

    public function descargar_csv_todos_inscriptos() {
        if (!$this->session->userdata('is_organizador')) {
            redirect('Inscripciones/login');
        }
        
        $this->load->model('Participante_model');
        
        // Obtener la delegación del usuario logueado
        $delegacion = $this->session->userdata('user_nombre');
        
        // Obtener datos para el CSV
        $datos = $this->Participante_model->obtener_participantes_para_csv(NULL);
        
        // Nombre del archivo
        $nombre_archivo = 'inscriptos_' . '_' . date('Y-m-d') . '.csv';
        
        // Configurar headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        
        // Crear el output
        $output = fopen('php://output', 'w');
        
        // Agregar BOM para que Excel reconozca UTF-8 correctamente
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Escribir encabezados con punto y coma como delimitador
        fputcsv($output, ['DNI', 'Nombre Completo', 'Sexo', 'Fecha de Nacimiento', 'Edad', 'Delegación', 'Dieta Especial', 'Deporte', 'Categoría'], ';');
        
        // Escribir datos con punto y coma como delimitador
        foreach ($datos as $fila) {
            fputcsv($output, [
                $fila['dni'],
                $fila['nombre_completo'],
                $fila['sexo'],
                $fila['fecha_nacimiento'],
                $fila['edad'],
                $fila['delegacion'],
                $fila['dieta_especial'] ?? '',
                $fila['deporte'],
                $fila['categoria']
            ], ';');
        }
        
        fclose($output);
        exit;
    }

    // ====================================================================
    // GESTOR DE USUARIOS (exclusivo rol superadmin)
    // ====================================================================

    /**
     * Guard: todas las acciones del gestor exigen sesión iniciada Y rol
     * 'superadmin'. Cualquier otro rol es devuelto al login.
     */
    private function requiere_superadmin() {
        if (!$this->session->userdata('is_organizador')
            && !$this->session->userdata('is_delegado')) {
            redirect('Inscripciones/login');
        }
        if ($this->session->userdata('user_rol') !== 'superadmin') {
            show_error('No tenés permisos para acceder al Gestor de Usuarios. Solo rol SUPERADMIN.', 403);
            exit;
        }
    }

    /**
     * Pantalla principal del gestor: listado de usuarios + modales.
     */
    public function gestion_usuarios() {
        $this->requiere_superadmin();

        $this->load->model('Usuario_model');

        $data['usuarios']       = $this->Usuario_model->obtener_todos();
        $data['roles']          = $this->Usuario_model->roles_validos();
        $data['menu_activo']    = 'usuarios';
        $data['yo_id']          = $this->session->userdata('user_id');

        $this->load->view('admin/gestion_usuarios', $data);
    }

    /**
     * Crear un usuario nuevo.
     */
    public function crear_usuario() {
        $this->requiere_superadmin();
        $this->load->model('Usuario_model');

        $username       = trim((string) $this->input->post('username', TRUE));
        $password       = (string) $this->input->post('password', FALSE); // cruda por compatibilidad con el login actual
        $nombre_usuario = mb_strtoupper(trim((string) $this->input->post('nombre_usuario')), 'UTF-8');
        $rol            = $this->input->post('rol', TRUE);

        $roles_validos  = array_keys($this->Usuario_model->roles_validos());

        // Validaciones
        if ($username === '' || $password === '' || !in_array($rol, $roles_validos, TRUE)) {
            $this->session->set_flashdata('mensaje_error', 'Completá usuario, contraseña y un rol válido.');
            redirect('Inscripciones/gestion_usuarios');
        }
        if (strlen($password) < 4) {
            $this->session->set_flashdata('mensaje_error', 'La contraseña debe tener al menos 4 caracteres.');
            redirect('Inscripciones/gestion_usuarios');
        }
        if ($this->Usuario_model->username_existe($username)) {
            $this->session->set_flashdata('mensaje_error', 'El usuario "' . html_escape($username) . '" ya existe.');
            redirect('Inscripciones/gestion_usuarios');
        }

        $creado = $this->Usuario_model->crear(array(
            'username'       => $username,
            'password'       => $password,
            'rol'            => $rol,
            'nombre_usuario' => $nombre_usuario ?: $username
        ));

        if ($creado) {
            $this->session->set_flashdata('mensaje_exito', 'Usuario "' . html_escape($username) . '" creado correctamente.');
        } else {
            $this->session->set_flashdata('mensaje_error', 'No se pudo crear el usuario.');
        }
        redirect('Inscripciones/gestion_usuarios');
    }

    /**
     * Editar rol y/o nombre visible de un usuario existente.
     */
    public function editar_usuario() {
        $this->requiere_superadmin();
        $this->load->model('Usuario_model');

        $id_usuario     = intval($this->input->post('id_usuario'));
        $nombre_usuario = mb_strtoupper(trim((string) $this->input->post('nombre_usuario')), 'UTF-8');
        $rol            = $this->input->post('rol', TRUE);

        $usuario_actual = $this->Usuario_model->obtener_por_id($id_usuario);
        $roles_validos  = array_keys($this->Usuario_model->roles_validos());

        if (!$usuario_actual || !in_array($rol, $roles_validos, TRUE)) {
            $this->session->set_flashdata('mensaje_error', 'Usuario o rol no válido.');
            redirect('Inscripciones/gestion_usuarios');
        }

        // Seguridad: no permitir que el sistema quede sin superadmins
        // (descender al único superadmin que queda).
        if ($usuario_actual['rol'] === 'superadmin' && $rol !== 'superadmin'
            && $this->Usuario_model->contar_superadmins() <= 1) {
            $this->session->set_flashdata('mensaje_error', 'No podés quitarle el rol superadmin al último administrador del sistema.');
            redirect('Inscripciones/gestion_usuarios');
        }

        $this->Usuario_model->actualizar($id_usuario, array(
            'rol'            => $rol,
            'nombre_usuario' => $nombre_usuario ?: $usuario_actual['username']
        ));

        // Si el editado soy yo, actualizo la sesión con el nuevo rol/nombre
        if ($id_usuario == $this->session->userdata('user_id')) {
            $this->session->set_userdata(array(
                'user_rol'    => $rol,
                'user_nombre' => $nombre_usuario ?: $usuario_actual['username']
            ));
        }

        $this->session->set_flashdata('mensaje_exito', 'Usuario "' . html_escape($usuario_actual['username']) . '" actualizado.');
        redirect('Inscripciones/gestion_usuarios');
    }

    /**
     * Resetear / cambiar la contraseña de un usuario.
     */
    public function cambiar_password_usuario() {
        $this->requiere_superadmin();
        $this->load->model('Usuario_model');

        $id_usuario = intval($this->input->post('id_usuario'));
        $password   = (string) $this->input->post('nuevo_password', FALSE);

        $usuario_actual = $this->Usuario_model->obtener_por_id($id_usuario);

        if (!$usuario_actual) {
            $this->session->set_flashdata('mensaje_error', 'Usuario no encontrado.');
            redirect('Inscripciones/gestion_usuarios');
        }
        if (strlen($password) < 4) {
            $this->session->set_flashdata('mensaje_error', 'La nueva contraseña debe tener al menos 4 caracteres.');
            redirect('Inscripciones/gestion_usuarios');
        }

        $this->Usuario_model->cambiar_password($id_usuario, $password);
        $this->session->set_flashdata('mensaje_exito', 'Contraseña de "' . html_escape($usuario_actual['username']) . '" actualizada.');
        redirect('Inscripciones/gestion_usuarios');
    }

    /**
     * Eliminar un usuario.
     */
    public function eliminar_usuario($id_usuario = NULL) {
        $this->requiere_superadmin();

        if (empty($id_usuario)) {
            redirect('Inscripciones/gestion_usuarios');
        }

        $this->load->model('Usuario_model');
        $usuario_actual = $this->Usuario_model->obtener_por_id($id_usuario);

        if (!$usuario_actual) {
            $this->session->set_flashdata('mensaje_error', 'Usuario no encontrado.');
            redirect('Inscripciones/gestion_usuarios');
        }

        // No dejar el sistema sin superadmins
        if ($usuario_actual['rol'] === 'superadmin'
            && $this->Usuario_model->contar_superadmins() <= 1) {
            $this->session->set_flashdata('mensaje_error', 'No podés eliminar al último superadmin del sistema.');
            redirect('Inscripciones/gestion_usuarios');
        }

        $this->Usuario_model->eliminar($id_usuario);
        $this->session->set_flashdata('mensaje_exito', 'Usuario "' . html_escape($usuario_actual['username']) . '" eliminado.');
        redirect('Inscripciones/gestion_usuarios');
    }
}
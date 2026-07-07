<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inscripciones extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('pdf');
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
        // Cargar librería FPDF/TCPDF si existe, sino usar método alternativo
        /*$tcpdf_path = APPPATH . '../vendor/tecnickcom/tcpdf/tcpdf.php';
        
        if (file_exists($tcpdf_path)) {
            require_once($tcpdf_path);
            
            if (!$token) { show_404(); }

            $this->load->model('Participante_model');
            $participante = $this->Participante_model->obtener_por_token($token);

            if (!$participante) {
                show_404();
                return;
            }

            // Crear nuevo documento PDF
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Configurar información del documento
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Olimpiadas Nacionales de Empleados de Institutos de Vivienda La Pampa 2026');
            $pdf->SetTitle('Deslinde de Responsabilidad - ' . $participante['nombre_completo']);
            $pdf->SetSubject('Deslinde de Responsabilidad');

            // Eliminar cabecera y pie predeterminados
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Margen superior (necesario aunque no haya header)
            $pdf->SetMargins(15, 20, 15, true);

            // Fuente principal
            $pdf->SetFont('helvetica', '', 11);

            // Agregar una página
            $pdf->AddPage();

            // Contenido HTML del deslinde
            $html = '
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #1e3c72; font-size: 20px; font-weight: bold;">DESLINDE DE RESPONSABILIDAD</h2>
                <h3 style="color: #2a5298; font-size: 14px; margin-top: 5px;">XXXVIII OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026</h3>
            </div>

            <div style="margin-bottom: 15px;">
                <p style="text-align: justify; line-height: 1.6; font-size: 11px;">
                    El abajo firmante declara:<br><br>
                    <strong>DECLARO</strong> en plena facultad por la presente que participo de forma voluntaria en las competencias de las "XXXVIII OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026", 
                    a realizarse entre los días 01 al 06 de Noviembre del corriente año, y manifiesto haber leído y comprendido los Reglamentos de las Olimpiadas, condiciones y límites de la Póliza de Seguro por Accidentes Personales 
                    que me otorga la Organización; conozco, acepto y estoy de acuerdo en todos sus puntos.<br><br>
                    
                    Que tengo pleno conocimiento que las actividades deportivas implican estar frente a riesgos físicos. Asumo voluntariamente total responsabilidad por el riesgo y lo que pueda suceder practicando el o los deportes 
                    en los que me inscribí, tanto a mi persona como a terceros por mi actuación. Declaro haber realizado los entrenamientos físicos y técnicos previos y necesarios para la práctica de la o las disciplinas deportivas 
                    y encontrarme en perfectas condiciones psicofísicas para competir en ellas, dado los reconocimientos médicos a que he sido sometido recientemente, gozando de plena salud y no tener ningún impedimento físico 
                    o deficiencia que pudiera provocarme lesiones u otro daño corporal como consecuencia de mi participación deportiva. Así mismo declaro que participo con la indumentaria adecuada para la práctica del o los deportes, 
                    conocer los circuitos y/o canchas donde se desarrollan los deportes.<br><br>
                    
                    Desligo de toda responsabilidad a los Organizadores, Coordinadores, Municipios, patrocinadores y auspiciantes, a los titulares de lugares públicos o privados, clubes, donde se desarrollen los eventos, de cualquier 
                    accidente que me ocasione lesiones que afecten mi capacidad física, intelectual, laboral, deportiva y fisiológica, psicológica u otra en general, en forma parcial o total, transitoria o permanente, muerte, robo 
                    o daños a mis pertenencias durante la competencia o como consecuencia de la misma, tanto en lo que hace a reclamos por daños y perjuicios, lucro cesante, daño moral propio o de los derechos habientes, como así 
                    mismo renuncio a reclamar cualquier otro gasto adicional o incapacidad resultante, no cubierto por el seguro contratado por la Organización.<br><br>
                    
                    De igual manera declaro que la categoría en la que he solicitado competir corresponde a mi edad y nivel deportivo.<br><br>
                    
                    Autorizo a la Organización y Sponsors, al uso de fotografías, películas, videos, grabaciones y cualquier otro medio de registro de este evento para cualquier uso legitimo, sin compensación alguna.<br><br>
                    
                    Extiendo este deslinde de responsabilidad de manera expresa, a la Organización, Comité Olímpico, Autoridades Provinciales y otros, por mi participación en la "XXXVIII OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026"
                </p>
            </div>

            <div style="margin-top: 40px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; text-align: center; vertical-align: top; padding: 10px;">
                            <div style="border-top: 1px solid #000; width: 90%; margin: 0 auto; padding-top: 5px; min-height: 60px;">
                                <strong>FIRMA DEL PARTICIPANTE</strong>
                            </div>
                        </td>
                        <td style="width: 50%; text-align: center; vertical-align: top; padding: 10px;">
                            <div style="border-top: 1px solid #000; width: 90%; margin: 0 auto; padding-top: 5px; min-height: 60px;">
                                <strong>ACLARACION</strong>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="margin-top: 30px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 33%; text-align: left; padding: 5px;">
                            <strong>DNI:</strong> ______________________
                        </td>
                        <td style="width: 33%; text-align: center; padding: 5px;">
                            <strong>F. NACIMIENTO:</strong> _______________
                        </td>
                        <td style="width: 33%; text-align: right; padding: 5px;">
                            <strong>Cel:</strong> ______________________
                        </td>
                    </tr>
                </table>
            </div>

            <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #666;">
                <p>Documento generado electrónicamente el ' . date('d/m/Y H:i:s') . '.<br>Token de verificación: ' . substr($token, 0, 12) . '...</p>
            </div>
            ';

            // Imprimir contenido HTML
            $pdf->writeHTML($html, true, false, true, false, '');

            // Forzar salida del archivo PDF para descarga
            $nombre_archivo = 'Deslinde_Responsabilidad_' . str_replace(' ', '_', $participante['nombre_completo']) . '.pdf';
            $pdf->Output($nombre_archivo, 'D');
            
        } else {
            // Generar PDF básico sin dependencias externas con el texto completo del deslinde
            
            if (!$token) { 
                // Modo demo sin token
                $nombre_participante = "PARTICIPANTE DE PRUEBA";
                $dni = "00000000";
                $fecha_nacimiento = "01/01/1990";
                $telefono = "";
            } else {
                $this->load->model('Participante_model');
                $participante = $this->Participante_model->obtener_por_token($token);
                if ($participante) {
                    $nombre_participante = $participante['nombre_completo'];
                    $dni = $participante['dni'];
                    $fecha_nacimiento = isset($participante['fecha_nacimiento']) ? date('d/m/Y', strtotime($participante['fecha_nacimiento'])) : "";
                    $telefono = isset($participante['telefono']) ? $participante['telefono'] : "";
                } else {
                    $nombre_participante = "PARTICIPANTE";
                    $dni = "";
                    $fecha_nacimiento = "";
                    $telefono = "";
                }
            }
            
            // Contenido completo del deslinde
            $texto_deslinde = "DESLINDE DE RESPONSABILIDAD\n\n";
            $texto_deslinde .= "El abajo firmante declara:\n\n";
            $texto_deslinde .= "DECLARO en plena facultad por la presente que participo de forma voluntaria en las competencias de las \"XXXVIII OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026\", a realizarse entre los días 01 al 06 de Noviembre del corriente año, y manifiesto haber leído y comprendido los Reglamentos de las Olimpiadas, condiciones y límites de la Póliza de Seguro por Accidentes Personales que me otorga la Organización; conozco, acepto y estoy de acuerdo en todos sus puntos.\n\n";
            $texto_deslinde .= "Que tengo pleno conocimiento que las actividades deportivas implican estar frente a riesgos físicos. Asumo voluntariamente total responsabilidad por el riesgo y lo que pueda suceder practicando el o los deportes en los que me inscribí, tanto a mi persona como a terceros por mi actuación. Declaro haber realizado los entrenamientos físicos y técnicos previos y necesarios para la práctica de la o las disciplinas deportivas y encontrarme en perfectas condiciones psicofísicas para competir en ellas, dado los reconocimientos médicos a que he sido sometido recientemente, gozando de plena salud y no tener ningún impedimento físico o deficiencia que pudiera provocarme lesiones u otro daño corporal como consecuencia de mi participación deportiva. Así mismo declaro que participo con la indumentaria adecuada para la práctica del o los deportes, conocer los circuitos y/o canchas donde se desarrollan los deportes.\n\n";
            $texto_deslinde .= "Desligo de toda responsabilidad a los Organizadores, Coordinadores, Municipios, patrocinadores y auspiciantes, a los titulares de lugares públicos o privados, clubes, donde se desarrollen los eventos, de cualquier accidente que me ocasione lesiones que afecten mi capacidad física, intelectual, laboral, deportiva y fisiológica, psicológica u otra en general, en forma parcial o total, transitoria o permanente, muerte, robo o daños a mis pertenencias durante la competencia o como consecuencia de la misma, tanto en lo que hace a reclamos por daños y perjuicios, lucro cesante, daño moral propio o de los derechos habientes, como así mismo renuncio a reclamar cualquier otro gasto adicional o incapacidad resultante, no cubierto por el seguro contratado por la Organización.\n\n";
            $texto_deslinde .= "De igual manera declaro que la categoría en la que he solicitado competir corresponde a mi edad y nivel deportivo.\n\n";
            $texto_deslinde .= "Autorizo a la Organización y Sponsors, al uso de fotografías, películas, videos, grabaciones y cualquier otro medio de registro de este evento para cualquier uso legitimo, sin compensación alguna.\n\n";
            $texto_deslinde .= "Extiendo este deslinde de responsabilidad de manera expresa, a la Organización, Comité Olímpico, Autoridades Provinciales y otros, por mi participación en la \"XXXVIII OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026\"\n\n\n\n";
            $texto_deslinde .= "FIRMA DEL PARTICIPANTE\t\tACLARACION\n";
            $texto_deslinde .= "…………………………………………..\t\t……………………………………….\n\n";
            $texto_deslinde .= "DNI: " . str_pad($dni, 15, ".") . "\tF. NACIMIENTO: " . str_pad($fecha_nacimiento, 15, ".") . "\tCel: " . str_pad($telefono, 15, ".");
            
            // Crear PDF básico con formato simple
            $pdf_content = "%PDF-1.4\n";
            $pdf_content .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
            $pdf_content .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
            $pdf_content .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
            
            // Escapar texto para PDF
            $texto_escaped = str_replace("(", "\\(", str_replace(")", "\\)", str_replace("\\", "\\\\", $texto_deslinde)));
            
            $pdf_content .= "4 0 obj\n<< /Length " . strlen($texto_escaped) . " >>\nstream\nBT\n/F1 12 Tf\n50 750 Td\n";
            
            // Dividir el texto en líneas y agregarlas al PDF
            $lineas = explode("\n", $texto_escaped);
            $y = 750;
            foreach ($lineas as $linea) {
                $pdf_content .= "(" . $linea . ") Tj\n";
                $pdf_content .= "0 -20 Td\n";
            }
            
            $pdf_content .= "ET\nendstream\nendobj\n";
            $pdf_content .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
            $pdf_content .= "xref\n0 6\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\n0000000266 00000 n\n000000" . (strlen($pdf_content) + 100) . " 00000 n\ntrailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . (strlen($pdf_content) + 200) . "\n%%EOF";

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Deslinde_Responsabilidad.pdf"');
            header('Content-Length: ' . strlen($pdf_content));
            echo $pdf_content;
            exit;
        }*/
        $this->load->view('deslinde_resp_view',$data);
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
            $data['menu_activo'] = 'inscriptos';
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
        $data['menu_activo'] = 'deportes';

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
        $data['menu_activo'] = 'sondeo';

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


    public function control_total() {
        // Seguridad Superadmin
        if (!$this->session->userdata('is_organizador') || $this->session->userdata('user_rol') !== 'superadmin') {
            redirect('Inscripciones/login_staff');
        }

        $this->load->model('Deporte_model');
        $this->load->model('UTE_model');

        // 1. Traemos los datos planos, fila por fila
        $data['listado_encuestas'] = $this->Deporte_model->obtener_todas_las_encuestas();
        $data['listado_inscripciones'] = $this->Deporte_model->obtener_todas_las_inscripciones();
        
        // Datos para la pestaña de UTEs/Equipos
        $data['utes'] = $this->UTE_model->obtener_todas_las_utes();
        $data['categorias'] = $this->UTE_model->obtener_categorias_con_deportes();
        
        $data['menu_activo'] = 'control';
        $this->load->view('admin/control_total', $data);
    }

    public function detalle_ajax($id_participante) {
        // Es buena práctica validar que sea una petición válida o que el admin esté logueado
        if (!$id_participante) {
            echo json_encode(['error' => 'ID no válido']);
            return;
        }

        $this->load->model('Participante_model'); // Ajustá al nombre de tu modelo
        $data = $this->Participante_model->obtener_detalle_participante($id_participante);

        // Seteamos la cabecera para decirle al navegador que es un JSON puro
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    // 2. Acción para eliminar un registro de encuesta
    public function eliminar_encuesta($id) {
        if ($this->session->userdata('user_rol') === 'superadmin') {
            $this->load->model('Deporte_model');
            $this->Deporte_model->borrar_encuesta($id);
        }
        redirect('Inscripciones/control_total');
    }

    /**
     * Eliminar una inscripción completa (participante y todas sus inscripciones deportivas)
     */
    public function eliminar_inscripcion($id_participante) {
        // Verificar que sea staff/organizador
        if (!$this->session->userdata('is_organizador')) {
            $this->session->set_flashdata('error', 'No tenés permisos para realizar esta acción.');
            redirect('Inscripciones/control_total');
            return;
        }

        if (!$id_participante) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Inscripciones/control_total');
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
        
        redirect('Inscripciones/control_total');
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
            redirect('Inscripciones/control_total');
            return;
        }

        if (!$id_participante) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Inscripciones/control_total');
            return;
        }

        $this->load->model('Participante_model');
        $this->load->model('Deporte_model');
        
        // Obtener datos del participante
        $data['participante'] = $this->Participante_model->obtener_detalle_participante($id_participante);
        
        if (!$data['participante']) {
            $this->session->set_flashdata('error', 'Participante no encontrado.');
            redirect('Inscripciones/control_total');
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
            redirect('Inscripciones/control_total');
            return;
        }

        $this->load->model('Participante_model');
        $post = $this->input->post();

        if (empty($post['id_participante'])) {
            $this->session->set_flashdata('error', 'ID de participante no válido.');
            redirect('Inscripciones/control_total');
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

        redirect('Inscripciones/control_total');
    }

    // =========================================================================
    // GESTION DE UTES / EQUIPOS
    // =========================================================================

    /**
     * Panel principal de gestión de UTEs/Equipos
     */
    public function panel_utes() {
        // Seguridad Superadmin
        if (!$this->session->userdata('is_organizador') || $this->session->userdata('user_rol') !== 'superadmin') {
            redirect('Inscripciones/login_staff');
        }

        $this->load->model('UTE_model');
        
        $data['utes'] = $this->UTE_model->obtener_todas_las_utes();
        $data['categorias'] = $this->UTE_model->obtener_categorias_con_deportes();
        $data['menu_activo'] = 'control';
        
        $this->load->view('admin/control_total', $data);
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
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['success' => false, 'error' => 'No tenés permisos']);
            return;
        }

        $nombre_ute = $this->input->post('nombre_ute');
        $id_categoria = $this->input->post('id_categoria');

        if (empty($nombre_ute) || empty($id_categoria)) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $this->load->model('UTE_model');
        $id_ute = $this->UTE_model->insertar_ute($nombre_ute, $id_categoria);

        if ($id_ute) {
            echo json_encode(['success' => true, 'id_ute' => $id_ute]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear la UTE']);
        }
    }

    /**
     * AJAX: Agregar participante a UTE
     */
    public function ajax_agregar_participante_ute() {
        header('Content-Type: application/json');
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['success' => false, 'error' => 'No tenés permisos']);
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
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['success' => false, 'error' => 'No tenés permisos']);
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
        
        if (!$this->session->userdata('is_organizador')) {
            echo json_encode(['success' => false, 'error' => 'No tenés permisos']);
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
}
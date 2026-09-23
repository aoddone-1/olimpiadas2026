<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Publica_controller
 *
 * Responsabilidad: inscripción PÚBLICA de participantes (formulario web),
 * endpoints de autocompletado (categorías/deportes), búsqueda por DNI,
 * acreditación mediante código QR (token) y descarga del deslinde de
 * responsabilidad.
 *
 * URL canónica: `Inscripciones/...` (ver config/routes.php por compatibilidad).
 */
class Publica_controller extends OLIM_Controller {

    public function index() {
        redirect('inscripciones/panel');
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
        // $post = olim_datos_prueba_inscripcion();
        // =================================================================

        $this->db->where('dni', trim($post['dni']));
        $query_check = $this->db->get('participantes');
        $existe = ($query_check->num_rows() > 0);

        if ($existe) {
            $participante_viejo = $query_check->row_array();
            $id_participante = $participante_viejo['id_participante'];
            $token = $participante_viejo['token_qr']; 
        } else {
            $token = olim_generar_token_qr($post['dni']);
        }

        // Estructura de datos incluyendo Roles y Delegados
        $data_persona = olim_normalizar_persona($post, $token);

        // CONTROL Y CAPTURA DE DISCIPLINAS + PANEL UTE
        $deportes_seleccionados = olim_disciplinas_desde_post($post);

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
            $this->session->set_userdata('url_retorno_qr', 'inscripciones/acreditacion/' . $token);
            
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
}

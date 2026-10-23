<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper con el texto del DESLINDE DE RESPONSABILIDAD.
 *
 * Está acá (y no solo dentro de deslinde_resp_view.php) porque lo usan las dos
 * vistas de PDF:
 *   - deslinde_resp_view.php ......... un deslinde suelto (por QR / token)
 *   - deslindes_masivos_view.php ..... TODOS los deslindes de una delegación en
 *                                      un único PDF (un deslinde por hoja), desde
 *                                      el botón "Descargar Deslindes" del panel
 *                                      de delegado.
 * Así el redactado del documento vive en un solo lado y no se corre el riesgo de
 * que uno de los dos PDFs quede desactualizado.
 */

if (!function_exists('deslinde_html')) {

    /**
     * Devuelve el bloque HTML del deslinde de responsabilidad de un participante,
     * listo para pasarle a TCPDF::writeHTML().
     *
     * @param array $participante Debe traer: nombre_completo, dni,
     *                            fecha_nacimiento, telefono, es_competidor.
     *                            Los campos que falten se muestran vacíos.
     * @return string
     */
    function deslinde_html($participante) {
        $nombre_completo   = isset($participante['nombre_completo']) ? $participante['nombre_completo'] : '';
        $dni               = isset($participante['dni']) ? $participante['dni'] : '';
        $telefono          = isset($participante['telefono']) ? $participante['telefono'] : '';
        $fecha_nacimiento  = isset($participante['fecha_nacimiento']) && $participante['fecha_nacimiento']
                                ? date('d/m/Y', strtotime($participante['fecha_nacimiento']))
                                : '';
        $es_competidor     = !empty($participante['es_competidor']);

        $html = "";

        $html .= '  <div style="margin-bottom: 15px;">
                        <p style="text-align: justify; line-height: 1.6; font-size: 11px;">
                            <u><b>DESLINDE DE RESPONSABILIDAD</b></u><br><br>
                            El abajo firmante declara:<br><br>
                            <strong>DECLARO</strong> en plena facultad por la presente que participo de forma voluntaria en las competencias de las <b>“XXXVIII
                            OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026”</b>, a realizarse entre los días
                            01 al 06 de Noviembre del corriente año,  y manifiesto haber leído y comprendido los Reglamentos de las Olimpiadas,
                            condiciones y límites de la Póliza de Seguro por Accidentes Personales que me otorga la Organización; conozco, acepto y
                            estoy de acuerdo en todos sus puntos.<br><br>';
        if ($es_competidor) {
            $html .= '      Que tengo pleno conocimiento que las actividades deportivas implican estar frente a riesgos físicos. Asumo
                            voluntariamente total responsabilidad por el riesgo y lo que pueda suceder practicando el o los deportes en los que me
                            inscribí, tanto a mi persona como a terceros por mi actuación. Declaro haber realizado los entrenamientos físicos y
                            técnicos previos y necesarios para la práctica de la o las disciplinas deportivas y encontrarme en perfectas condiciones
                            psicofísicas para competir en ellas, dado los reconocimientos médicos a que he sido sometido recientemente, gozando
                            de plena salud y no tener ningún impedimento físico o deficiencia que pudiera provocarme lesiones u otro daño
                            corporal como consecuencia de mi participación deportiva. Así mismo declaro que participo con la indumentaria
                            adecuada para la práctica del o los deportes, conocer los circuitos y/o canchas donde se desarrollan los deportes.<br><br>';

            $html .= '      Desligo de toda responsabilidad a los Organizadores, Coordinadores, Municipios, patrocinadores y auspiciantes, a los
                            titulares de lugares públicos o privados, clubes, donde se desarrollen los eventos, de cualquier accidente
                            que me ocasione lesiones que afecten mi capacidad física, intelectual, laboral, deportiva y fisiológica, psicológica u otra
                            en general, en forma parcial o total, transitoria o permanente, muerte, robo o daños a mis pertenencias durante
                            la competencia o como consecuencia de la misma, tanto en lo que hace a reclamos por daños y perjuicios, lucro cesante,
                            daño moral propio  o de los derechos habientes, como así mismo renuncio a reclamar cualquier otro gasto adicional o
                            incapacidad resultante, no cubierto por el seguro contratado por la Organización.<br><br>';

            $html .= '      De igual manera declaro que la categoría en la que he solicitado competir corresponde a mi edad y nivel deportivo.<br><br>';
        }
        $html .= '      Autorizo a la Organización y Sponsors, al uso de fotografías, películas, videos, grabaciones y cualquier otro medio de registro de este evento para cualquier uso legitimo, sin compensación alguna.<br><br>';

        $html .= '      Extiendo este deslinde de responsabilidad de manera expresa, a la Organización, Comité Olímpico, Autoridades Provinciales y otros, por mi participación en la <b>"XXXVIII OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026"</b>
                    </p>
                </div>';
        $html .= '<table style="width:100%; text-align:center;font-size: 10px;">';
        $html .= '  <tr>';
        $html .= '      <td style="text-align: center;">';
        $html .= '          <br/>';
        $html .= '          ___________________________________________<br/>';
        $html .= '          FIRMA ' . $nombre_completo . '';
        $html .= '          <br/>DNI ' . $dni . '<br/>';
        $html .= '      </td>';
        $html .= '      <td style="text-align: center;">';
        $html .= '          <br/>';
        $html .= '          ___________________________________________<br/>';
        $html .= '          ACLARACIÓN<br/>';
        $html .= '      </td>';
        $html .= '  </tr>';
        $html .= '  <tr>';
        $html .= '      <td style="text-align: center;">';
        $html .= '          <br/>';
        $html .= '          FECHA NACIMIENTO: ' . $fecha_nacimiento . '';
        $html .= '      </td>';
        $html .= '      <td style="text-align: center;">';
        $html .= '          <br/>';
        $html .= '          Telefono: ' . $telefono . '';
        $html .= '      </td>';
        $html .= '  </tr>';
        $html .= '</table>';

        return $html;
    }
}

if (!function_exists('deslinde_imagen_header')) {

    /**
     * Ruta absoluta del logo que se dibuja en el encabezado de cada hoja.
     * Se fija en tiempo de ejecución (no en tiempo de compilación) para que
     * funcione igual aunque CodeIgniter esté instalado en un subdirectorio.
     *
     * @return string
     */
    function deslinde_imagen_header() {
        // dirname(dirname(__FILE__)) = .../application  ->  .../assets/img/header.jpg
        return dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'header.jpg';
    }
}

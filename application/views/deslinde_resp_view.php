<?php

class MYPDF extends TCPDF {
    public function Header() {
        $this->Image('assets/img/header.png', 30, 15, 50, '', '', '', 'C', false, 50, '', false, false,1, false, false, false);
        
        
        
    }
        

    
    
    public function Body($participante) {
        $html = "";
        $html .= '  <div style="margin-bottom: 15px;">
                        <p style="text-align: justify; line-height: 1.6; font-size: 11px;">
                            <u><b>DESLINDE DE RESPONSABILIDAD</b></u><br><br>
                            El abajo firmante declara:<br><br>
                            <strong>DECLARO</strong> en plena facultad por la presente que participo de forma voluntaria en las competencias de las <b>“XXXVIII 
                            OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026”</b>, a realizarse entre los días 
                            01 al 06 de Noviembre del corriente año,  y manifiesto haber leído y comprendido los Reglamentos de las Olimpiadas, 
                            condiciones y límites de la Póliza de Seguro por Accidentes Personales que me otorga la Organización; conozco, acepto y 
                            estoy de acuerdo en todos sus puntos.<br><br>
                            
                            Que tengo pleno conocimiento que las actividades deportivas implican estar frente a riesgos físicos. Asumo 
                            voluntariamente total responsabilidad por el riesgo y lo que pueda suceder practicando el o los deportes en los que me 
                            inscribí, tanto a mi persona como a terceros por mi actuación. Declaro haber realizado los entrenamientos físicos y 
                            técnicos previos y necesarios para la práctica de la o las disciplinas deportivas y encontrarme en perfectas condiciones 
                            psicofísicas para competir en ellas, dado los reconocimientos médicos a que he sido sometido recientemente, gozando 
                            de plena salud y no tener ningún impedimento físico o deficiencia que pudiera provocarme lesiones u otro daño 
                            corporal como consecuencia de mi participación deportiva. Así mismo declaro que participo con la indumentaria 
                            adecuada para la práctica del o los deportes, conocer los circuitos y/o canchas donde se desarrollan los deportes.<br><br>
                            
                            Desligo de toda responsabilidad a los Organizadores, Coordinadores, Municipios, patrocinadores y auspiciantes, a los 
                            titulares de lugares públicos o privados, clubes, donde se desarrollen los eventos, de cualquier accidente 
                            que me ocasione lesiones que afecten mi capacidad física, intelectual, laboral, deportiva y fisiológica, psicológica u otra 
                            en general, en forma parcial o total, transitoria o permanente, muerte, robo o daños a mis pertenencias durante 
                            la competencia o como consecuencia de la misma, tanto en lo que hace a reclamos por daños y perjuicios, lucro cesante, 
                            daño moral propio  o de los derechos habientes, como así mismo renuncio a reclamar cualquier otro gasto adicional o 
                            incapacidad resultante, no cubierto por el seguro contratado por la Organización.<br><br>
                            
                            De igual manera declaro que la categoría en la que he solicitado competir corresponde a mi edad y nivel deportivo.<br><br>
                            
                            Autorizo a la Organización y Sponsors, al uso de fotografías, películas, videos, grabaciones y cualquier otro medio de registro de este evento para cualquier uso legitimo, sin compensación alguna.<br><br>
                            
                            Extiendo este deslinde de responsabilidad de manera expresa, a la Organización, Comité Olímpico, Autoridades Provinciales y otros, por mi participación en la <b>"XXXVIII OLIMPIADAS NACIONALES DE EMPLEADOS DE INSTITUTOS DE VIVIENDA LA PAMPA 2026"</b>
                        </p>
                    </div>';
        $html .= '<table style="width:100%; text-align:center;font-size: 10px;">';
        $html .= '  <tr>';
        $html .= '      <td style="text-align: center;">';
        $html .= '          <br/>';
        $html .= '          ___________________________________________<br/>';
        $html .= '          FIRMA '.$participante['nombre_completo'].'';
        $html .= '          <br/>DNI '.$participante['dni'].'<br/>';
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
        $html .= '          FECHA NACIMIENTO: '.date('d/m/Y', strtotime($participante['fecha_nacimiento'])).'';
        $html .= '      </td>';
        $html .= '      <td style="text-align: center;">';
        $html .= '          <br/>';
        $html .= '          Telefono: '.$participante['telefono'].'';
        $html .= '      </td>';
        $html .= '  </tr>';
        $html .= '</table>';

        $this->writeHTML($html, true, false, true, false, '');
    }
    public function Footer() {
    }
    

}

// create new PDF document
$pdf = new MYPDF('P', 'mm' ,'LEGAL', true,'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(NOMBRE_SITIO);
$pdf->SetTitle('Deslinde_Responsabilidad_' . $participante['nombre_completo'] . '');
$pdf->SetSubject('Deslinde_Responsabilidad_' . $participante['nombre_completo'] . '');
$pdf->SetKeywords('TCPDF, PDF, OLIMPIADAS, VIVIENDAS');


// set header and footer fonts

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(30, 50, 30);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/es.php')) {
    require_once(dirname(__FILE__).'/lang/es.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('dejavusans', '', 14, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage('P','LEGAL');
$pdf->Body($participante);
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('Deslinde_Responsabilidad_' . str_replace(' ', '_', $participante['nombre_completo']) . '.pdf', 'D');

//============================================================+
// END OF FILE
//============================================================+
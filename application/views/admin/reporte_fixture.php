<?php

class MYPDF extends TCPDF {
    public function Header() {
        $this->Image('assets/img/header.jpg', 30, 15, 50, '', '', '', 'C', false, 50, '', false, false,0, false, false, false);
        
        
        
    }
        

    
    
    public function Body() {
        $html = "";
        
           

        $this->writeHTML($html, true, false, true, false, '');
    }
    public function Footer() {
    }
    

}

// create new PDF document
$pdf = new MYPDF('L', 'mm' ,'LEGAL', true,'UTF-8', false);

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
$pdf->AddPage('L','LEGAL');
$pdf->Body();
// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('Nombre_Archivo.pdf', 'D');

//============================================================+
// END OF FILE
//============================================================+
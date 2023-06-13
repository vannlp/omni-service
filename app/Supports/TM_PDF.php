<?php


namespace App\Supports;


class TM_PDF extends \TCPDF
{
    public function Header()
    {
        $headerData = $this->getHeaderData();
        $this->SetFont('dejavusans', '', 10);
        $this->writeHTML($headerData['string']);
    }
}
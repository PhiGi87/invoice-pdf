<?php

require_once 'fpdf.php';

class Invoice_Receipt_PDF extends FPDF {

    //Logopath needs to be here.
    protected $logopath = '/Users/harjukallio/Dropbox/Sites/pdf_projekti/images/logo.png';
    protected $invoiceInformation;
    protected $sales;
    protected $sums;
    protected $jobReports;
    protected $footerPosition;
    //Possible types are invoice and receipt
    protected $type = 'invoice';

    public function __construct($type = '') {
        //Type is invoice by default
        if (!empty($type)) {
            $this->type = 'receipt';
        }
        else {
            $this->type = 'invoice';
        }

        parent::FPDF();

        //SetFont needs to be called before AddPage,
        //because addpage calls setfont and if no font is set the result is error.
        $this->SetFont('Helvetica', '', 9);
        $this->AddPage('P', 'A4');
        $this->SetMargins(15, 16, 15);
        $this->SetAutoPageBreak(true);

        $this->footerPosition = $this->h - $this->bMargin - 30;
        $this->PageBreakTrigger = $this->footerPosition;
    }

    public function generate() {
        $this->billingInfo();

        $this->SetY($this->GetY() + 10);

        $this->invoiceRows();

        $this->SetY($this->GetY() + 5);

        $this->summary();

        $this->SetY($this->GetY() + 10);

        $this->jobReports();
    }

    private function billingInfo() {
        //Billing info are printed in rows from left to right.
        
        $yTemp = array();
        $yTemp[0] = $this->GetY();
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX(), $this->GetY(), 'Laskutusosoite', true, 9);
        $this->Text($this->GetX() + 108, $this->GetY(), 'Laskun pvm.', true, 9);
        $this->Text($this->GetX() + 135, $this->GetY(), date('d.m.Y', strtotime($this->invoiceInformation['billingDate'])), null, 9);
        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['companyName'], null, 9);
        $this->Text($this->GetX() + 108, $this->GetY(), 'Laskun nro.', true, 9);
        $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['id'], null, 9);
        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['name'], null, 9);
        $this->Text($this->GetX() + 108, $this->GetY(), 'Viitenumero', true, 9);
        $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['referenceNumber'], null, 9);
        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->SetY($this->GetY() + 4);
        if ($this->GetStringWidth($this->invoiceInformation['address']) > 110) {
            $this->fitText($this->GetX(), $this->GetY(), $this->invoiceInformation['address'], 110);

            if ($this->type == 'invoice') {
                $this->Text($this->GetX() + 108, $this->GetY(), 'Maksuehto', true, 9);
                $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['paymentTerm'], null, 9);
            }

            $this->SetY($this->GetY() + 1);
            $this->Line(120, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
        }
        else {
            $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['address'], null, 9);

            if ($this->type == 'invoice') {
                $this->Text($this->GetX() + 108, $this->GetY(), 'Maksuehto', true, 9);
                $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['paymentTerm'], null, 9);
            }

            $this->SetY($this->GetY() + 1);
            $this->Line(120, $this->GetY(), $this->w - $this->rMargin, $this->GetY());
        }

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['postalCode'], null, 9);

        if ($this->type == 'invoice') {
            $this->Text($this->GetX() + 108, $this->GetY(), 'Eräpäivä', true, 9);
            $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['dueDate'], null, 9);
        }

        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['countryName'], null, 9);

        if ($this->type == 'invoice') {
            $this->Text($this->GetX() + 108, $this->GetY(), 'Viivästyskorko', true, 9);
            $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['interestPercent'], null, 9);
        }

        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX() + 108, $this->GetY(), 'Viitteenne', true, 9);
        $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['billingReference'], null, 9);
        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $yTemp[1] = $this->GetY();
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        //vertical line
        $this->Line(120, $yTemp[0], 120, $yTemp[1]);
    }

    private function invoiceRows() {
        $this->Text($this->GetX(), $this->GetY(), 'PVM', true, 9);
        $this->SetX($this->GetX() + 20);
        $this->Text($this->GetX(), $this->GetY(), 'Nimike', true, 9);
        $this->SetX($this->GetX() + 90);
        $this->Text($this->GetX(), $this->GetY(), 'ALV %', true, 9);
        $this->SetX($this->GetX() + 20);
        $this->Text($this->GetX(), $this->GetY(), 'á', true, 9);
        $this->SetX($this->GetX() + 10);
        $this->Text($this->GetX(), $this->GetY(), 'alv', true, 9);
        $this->SetX($this->GetX() + 15);
        $this->Text($this->GetX(), $this->GetY(), 'Kpl', true, 9);
        $this->SetX($this->GetX() + 15);
        $this->Text($this->GetX(), $this->GetY(), 'Summa', true, 9);

        $this->SetY($this->GetY() + 10);

        foreach ($this->sales as $sale) {
            //Rows are printed with Cell so that autopagebreak works
            $this->Cell(20, 5, $sale['sellDate']);
            $this->Cell(90, 5, $sale['productName']);
            $this->Cell(18, 5, $sale['vatPercent']);
            $this->Cell(12, 5, $sale['price']);
            $this->Cell(15, 5, $sale['vatSum']);
            $this->Cell(15, 5, $sale['quantity']);
            $this->Cell(15, 5, $sale['sum']);
            $this->SetY($this->GetY() + 10);
        }
    }

    private function summary() {
        $this->Text($this->GetX(), $this->GetY(), 'Tuotteet (alv 0%)', false, 8);
        $this->SetX($this->GetX() + 60);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['productSaleSum'] . ' €', false, 8);
        $this->SetX($this->GetX() + 45);
        $this->Text($this->GetX(), $this->GetY(), 'Tuotteiden alv.', false, 8);
        $this->SetX($this->GetX() + 30);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['productSaleVat'] . ' €', false, 8);

        $this->SetY($this->GetY() + 5);

        $this->Text($this->GetX(), $this->GetY(), 'Palvelut (alv 0%)', false, 8);
        $this->SetX($this->GetX() + 60);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['serviceSaleSum'] . ' €', false, 8);
        $this->SetX($this->GetX() + 45);
        $this->Text($this->GetX(), $this->GetY(), 'Palveluiden alv.', false, 8);
        $this->SetX($this->GetX() + 30);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['serviceSaleVat'] . ' €', false, 8);

        $this->SetY($this->GetY() + 5);

        $this->Text($this->GetX(), $this->GetY(), 'Yhteensä (alv 0%)', false, 8);
        $this->SetX($this->GetX() + 60);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['totalSum'] . ' €', false, 8);
        $this->SetX($this->GetX() + 45);
        $this->Text($this->GetX(), $this->GetY(), 'Alv yht.', false, 8);
        $this->SetX($this->GetX() + 30);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['totalVat'] . ' €', false, 8);

        $this->SetX($this->GetX() + 20);

        $this->Text($this->GetX(), $this->GetY(), 'Yht. EUR', true, 9);
        $this->SetX($this->GetX() + 15);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['total'], true, 9);
    }

    private function jobReports() {
        $this->Text($this->GetX(), $this->GetY(), 'Työraportit', true, 8);

        $this->SetY($this->GetY() + 5);

        foreach ($this->jobReports as $jobReport) {
            //Job reports are printed with multicell so that autopagebreak works
            $w = $this->w - $this->lMargin - $this->rMargin;
            $this->MultiCell($w, 5, $jobReport, 0, 'L');

            $this->SetY($this->GetY() + 10);
        }
    }

    public function setLogo($logo) {
        $this->logopath = $logo;
    }

    public function getLogo() {
        return $this->logopath;
    }

    public function setInvoiceInformation($information) {
        $this->invoiceInformation = $information;
    }

    public function getInvoiceInformation() {
        return $this->invoiceInformation;
    }

    public function setSales($sales) {
        $this->sales = $sales;
    }

    public function getSales() {
        return $this->sales;
    }

    public function setSums($sums) {
        $this->sums = $sums;
    }

    public function getSums() {
        return $this->sums;
    }

    public function setJobReports($jobReports) {
        $this->jobReports = $jobReports;
    }

    public function getJobReports() {
        return $this->jobReports;
    }

    /**
     * Overwrites FPDF Header function. Prints header to every page
     */
    public function Header() {
        $scaledImageSize = $this->scaleImage($this->logopath, 12);
        $this->Image($this->logopath, $this->GetX() + 5, $this->GetY() + 5, $scaledImageSize[0], $scaledImageSize[1], 'png');

        //sety defaults x to left margin :<
        $this->SetY($this->GetY() + 15);
        $this->SetX($this->GetX() + 130);
        if ($this->type == 'invoice') {
            $headerText = 'LASKU';
        }
        else {
            $headerText = 'KUITTI';
        }
        $this->Text($this->GetX(), $this->GetY(), $headerText, true, 21);

        //This line needs to be here so that autopagebreak starts printing from right y-coordinate
        $this->SetY($this->GetY() + 15);

        $this->Text(190, 15, $this->PageNo() . '/{nb}', false, 12);
    }

    /**
     * Overwrites FPDF Footer function. Prints footer to every page
     */
    public function Footer() {
        //Footer is printed a block at a time starting from left top. 
        
        $this->SetY($this->footerPosition);
        $this->Text($this->GetX(), $this->GetY(), 'Pyydämme käyttämään maksaessanne viitenumeroa:', true, 8);
        $this->SetX($this->GetX() + 100);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['referenceNumber'], true, 8);

        $this->SetY($this->GetY() + 2);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->SetY($this->GetY() + 5);


        $yTemp = $this->GetY();
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myCompanyName'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myCompanyAddress'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myPostalCode'] . ' ' . $this->invoiceInformation['myCity'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->Text($this->GetX(), $this->GetY(), 'Y: ' . $this->invoiceInformation['myBusinessId'], false, 8);

        $this->SetXY($this->GetX() + 60, $yTemp);

        $this->Text($this->GetX(), $this->GetY(), 'Puhelin', false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 60);
        $this->Text($this->GetX(), $this->GetY(), 'WWW', false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 60);
        $this->Text($this->GetX(), $this->GetY(), 'Sähköposti', false, 8);

        $this->SetXY($this->GetX() + 20, $yTemp);

        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myTelephone1'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 80);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myTelephone2'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 80);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myUrl'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 80);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myEmail'], false, 8);

        $this->SetXY($this->GetX() + 60, $yTemp);

        $this->Text($this->GetX(), $this->GetY(), 'Tilinumero', false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 140);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myBankName'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 140);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myIbanNumber'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 140);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['mySwift'], false, 8);
    }

    /**
     * Function fits given text to given width. This is done by reducing the font size.
     * 
     * @param float $x
     * @param float $y
     * @param string $text
     * @param integer $width
     * @param boolean $bold
     */
    protected function fitText($x, $y, $text, $width, $bold = null) {
        $fontSize = $this->FontSizePt;
        $fontSizeTemp = $fontSize;
        while ($this->GetStringWidth($text) > $width - 7) {
            $fontSize -= 0.1;
            $this->SetFontSize($fontSize);
        }
        $this->Text($x, $y, $text, $bold, null);
        $this->SetFontSize($fontSizeTemp);
    }

    /**
     * Overwrites FPDF Text(). With this function we are able to set boldness and size per printed text
     * 
     * @param float $x
     * @param float $y
     * @param string $txt
     * @param boolean $bold
     * @param integer $size
     */
    public function Text($x, $y, $txt, $bold = null, $size = null) {
        $currentSize = 0;
        if ($bold != null && $size != null) {
            $currentSize = $this->FontSizePt;
            $this->SetFont($this->FontFamily, 'B');
            $this->SetFontSize($size);
            parent::Text($x, $y, $this->chset($txt));
            $this->SetFont($this->FontFamily, '');
            $this->SetFontSize($currentSize);
        }
        else if ($bold != null && $size == null) {
            $this->SetFont($this->FontFamily, 'B');
            parent::Text($x, $y, $this->chset($txt));
            $this->SetFont($this->FontFamily, '');
        }
        else if ($size != null && $bold == null) {
            $currentSize = $this->FontSizePt;
            $this->SetFont($this->FontFamily, '');
            $this->SetFontSize($size);
            parent::Text($x, $y, $this->chset($txt));
            $this->SetFont($this->FontFamily, '');
            $this->SetFontSize($currentSize);
        }
        else {
            parent::Text($x, $y, $this->chset($txt));
        }
    }

    /**
     * Function calculates scaled width and height from the original image size and returns array containing scaled width and height 
     * 
     * @param string $imagepath
     * @param float $percentage
     * @return array
     */
    protected function scaleImage($imagepath, $percentage) {
        $imagesize = getimagesize($imagepath);
        $width = $imagesize[0] * $percentage / 100;
        $height = $imagesize[1] * $percentage / 100;

        return array($width, $height);
    }

    /**
     * Converts string from utf-8 to windows-1252. This needs be done if you want to print äöå
     * 
     * @param string $str
     * @return string
     */
    protected function chset($str) {
        return iconv('utf-8', 'windows-1252', $str);
    }

}

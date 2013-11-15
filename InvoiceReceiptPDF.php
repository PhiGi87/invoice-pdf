<?php
namespace HSW\AshaBundle\Service;

use HSW\AshaBundle\Service\FPDF;

class InvoiceReceiptPDF extends FPDF {

    //Logopath needs to be here.
    protected $logopath;
    protected $invoiceInformation;
    protected $sales;
    protected $jobReports;
    protected $sums;
    protected $footerPosition;
    //Possible types are invoice and receipt
    protected $type;
	protected $translation;
	protected $language;

    public function __construct($type = '', $logopath = '', $language='fi') {
		
		$this->language = $language;
		
		$this->setTranslation();
		if(empty($logopath) || !file_exists($logopath)) { $this->logopath = __DIR__."/logo.png"; }
		else { $this->logopath = $logopath; }

        //Type is invoice by default		
		if(empty($type)) {
			$this->type = 'invoice';
		}
		else { $this->type = $type; }
		
        parent::FPDF();

        //SetFont needs to be called before AddPage,
        //because addpage calls setfont and if no font is set the result is error.
        $this->SetFont('Helvetica', '', 9);
        $this->SetMargins(15, 0, 15);
        $this->AddPage('P', 'A4');
        $this->SetAutoPageBreak(true);

        $this->footerPosition = $this->h - $this->bMargin - 30;
        $this->PageBreakTrigger = $this->footerPosition - 10;
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
        $this->Line($this->lMargin - 1, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['billingAddress'], true, 9);
        $this->Text($this->GetX() + 108, $this->GetY(), $this->translation[$this->language]['billingDate'] , true, 9);
        $this->Text($this->GetX() + 135, $this->GetY(), date('d.m.Y', strtotime($this->invoiceInformation['billingDate'])), null, 9);
        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['companyName'], null, 9);
        $this->Text($this->GetX() + 108, $this->GetY(), $this->translation[$this->language]['invoiceNumber'], true, 9);
        $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['id'], null, 9);
        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['name'], null, 9);
        $this->Text($this->GetX() + 108, $this->GetY(), $this->translation[$this->language]['referenceNumber'] , true, 9);
        $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['referenceNumber'], null, 9);
        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());

        $this->SetY($this->GetY() + 4);
        if ($this->GetStringWidth($this->invoiceInformation['address']) > 110) {
            $this->fitText($this->GetX(), $this->GetY(), $this->invoiceInformation['address'], 110);

            if ($this->type == 'invoice') {
                $this->Text($this->GetX() + 108, $this->GetY(), $this->translation[$this->language]['termOfPayment'] , true, 9);
                $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['paymentTerm'], null, 9);
            }

            $this->SetY($this->GetY() + 1);
            $this->Line(120, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());
        }
        else {
            $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['address'], null, 9);

            if ($this->type == 'invoice' || $this->type == 'credit_invoice') {
                $this->Text($this->GetX() + 108, $this->GetY(), $this->translation[$this->language]['termOfPayment'] , true, 9);
                $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['paymentTerm'].' päivää', null, 9);
            }

            $this->SetY($this->GetY() + 1);
            $this->Line(120, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());
        }

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['postalCode'].' '.$this->invoiceInformation['city'], null, 9);

        if ($this->type == 'invoice' || $this->type == 'credit_invoice') {
            $this->Text($this->GetX() + 108, $this->GetY(), $this->translation[$this->language]['dueDate'] , true, 9);
            $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['dueDate'], null, 9);
        }

        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['countryName'], null, 9);

        if ($this->type == 'invoice' || $this->type == 'credit_invoice') {
            $this->Text($this->GetX() + 108, $this->GetY(), $this->translation[$this->language]['delayInterest'] , true, 9);
            $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['interestPercent'], null, 9);
        }

        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());

        $this->SetY($this->GetY() + 4);
        $this->Text($this->GetX() + 108, $this->GetY(), $this->translation[$this->language]['billingReference'], true, 9);
		$this->fitText($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['billingReference'], 55);
//        $this->Text($this->GetX() + 135, $this->GetY(), $this->invoiceInformation['billingReference'], null, 9);
        $this->SetY($this->GetY() + 1);
        $this->Line(120, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());

        $yTemp[1] = $this->GetY();
        $this->Line($this->lMargin - 1, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());

        //vertical line
        $this->Line(120, $yTemp[0], 120, $yTemp[1]);
    }

    private function invoiceRows() {
		$this->SetX($this->GetX() - 1);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['date'], true, 9);
        $this->SetX($this->GetX() + 19);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['productName'], true, 9);
        $this->SetX($this->GetX() + 92);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['vatPercent'] , true, 9);
        $this->SetX($this->GetX() + 20);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['a'], true, 9);
        $this->SetX($this->GetX() + 15);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['vat'], true, 9);
        $this->SetX($this->GetX() + 10);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['qty'], true, 9);
        $this->SetX($this->GetX() + 16.5);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['sum'], true, 9);

        $this->SetY($this->GetY() + 5);

        foreach ($this->sales as $sale) {
            //X needs to be incremented by 2, otherwise rows get printed a little bit too much right
            $this->SetX($this->GetX() - 2);
            //Rows are printed with Cell so that autopagebreak works
            $this->Cell(20, 5, $sale['sellDate'], 0, 0, 'L');
			$this->fitText($this->GetX(), $this->GetY() + 3.5, $sale['productName'], 100);
			            $this->SetX($this->GetX() + 93);
            $this->Cell(5, 5, $sale['vatPercent']);
            $this->Cell(19, 5, $sale['price'], 0, 0, 'R');
            $this->Cell(17, 5, $sale['vatSum'], 0, 0, 'R');
            $this->Cell(9, 5, $sale['quantity'], 0, 0, 'R');
            $this->Cell(23, 5, $sale['sum'], 0, 0, 'R');
            $this->SetY($this->GetY() + 10);
        }
    }

    private function summary() {
		if($this->getY() >= 240) { $this->AddPage('P', 'A4'); }
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['productsVat0'], false, 8);
        $this->SetX($this->GetX() + 60);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['productSaleSum'] . ' €', false, 8);
        $this->SetX($this->GetX() + 45);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['productsVat'], false, 8);
        $this->SetX($this->GetX() + 30);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['productSaleVat'] . ' €', false, 8);

        $this->SetY($this->GetY() + 5);

        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['servicesVat0'], false, 8);
        $this->SetX($this->GetX() + 60);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['serviceSaleSum'] . ' €', false, 8);
        $this->SetX($this->GetX() + 45);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['servicesVat'] , false, 8);
        $this->SetX($this->GetX() + 30);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['serviceSaleVat'] . ' €', false, 8);

        $this->SetY($this->GetY() + 5);

        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['totalVat0'], false, 8);
        $this->SetX($this->GetX() + 60);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['totalSum'] . ' €', false, 8);
        $this->SetX($this->GetX() + 45);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['vatTotal'], false, 8);
        $this->SetX($this->GetX() + 30);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['totalVat'] . ' €', false, 8);

        $this->SetX($this->GetX() + 20);

        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['totalEur'], true, 9);
        $this->SetX($this->GetX() + 15);
        $this->Text($this->GetX(), $this->GetY(), $this->sums['total'], true, 9);
    }

    private function jobReports() {
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['workReports'], true, 8);

        $this->SetY($this->GetY() + 5);

        foreach ($this->jobReports as $jobReport) {
            //Job reports are printed with multicell so that autopagebreak works
            $w = $this->w - $this->lMargin - $this->rMargin;
            $this->MultiCell($w, 5, $this->chset($jobReport), 0, 'L');

            $this->SetY($this->GetY() + 10);
        }
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
        $this->Image($this->logopath, $this->GetX(), $this->GetY() + 5, $scaledImageSize[0], $scaledImageSize[1], 'png');

        //sety defaults x to left margin :<
        $this->SetY($this->GetY() + 15);
        $this->SetX($this->GetX() + 110);
        if ($this->type === 'invoice') {
            $headerText = $this->translation[$this->language]['invoice'];
        }
        else if($this->type === 'credit_invoice') {
            $headerText = $this->translation[$this->language]['creditInvoice'] ;
        }
        else if($this->type == 'receipt') {
            $headerText = $this->translation[$this->language]['receipt'];
        }
        else {
            $headerText = $this->type;
        }
        $this->Text($this->GetX(), $this->GetY(), $headerText, true, 19);

        //This line needs to be here so that autopagebreak starts printing from right y-coordinate
        $this->SetY($this->GetY() + 10);

        $this->Text(190, 15, $this->PageNo() . ' ({nb})', false, 9);
    }

    /**
     * Overwrites FPDF Footer function. Prints footer to every page
     */
    public function Footer() {
        //Footer is printed a block at a time starting from left top. 
		$this->SetY($this->footerPosition);

		// transform Finnish VAT ID to international format in case of a non-Finnish invoice
		if($this->language != 'fi') { $businessId = 'FI'.str_replace("-", "", $this->invoiceInformation['myBusinessId']); }
		else { $businessId = $this->invoiceInformation['myBusinessId']; }

        if($this->type === 'invoice' || $this->type === 'credit_invoice') {
			$this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['sum'] .': '.$this->sums['total'].' €', true, 8);
			$this->SetX($this->GetX() + 60);
 		    $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['referenceNumber'].': '.$this->invoiceInformation['referenceNumber'], true, 8);
 		    $this->SetX($this->GetX() + 80);
			$this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['dueDate'].': '.$this->invoiceInformation['dueDate'], true, 8);
		}

        $this->SetY($this->GetY() + 2);
        $this->Line($this->lMargin - 1, $this->GetY(), $this->w - $this->rMargin + 4, $this->GetY());

        $this->SetY($this->GetY() + 5);


        $yTemp = $this->GetY();
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myCompanyName'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myCompanyAddress'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myPostalCode'] . ' ' . $this->invoiceInformation['myCity'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['businessID'].': ' . $businessId, false, 8);

        $this->SetXY($this->GetX() + 60, $yTemp);

        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['telephone'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 60);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['www'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 60);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['email'], false, 8);

        $this->SetXY($this->GetX() + 20, $yTemp);

        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['telephone1Text'].$this->invoiceInformation['myTelephone1'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 80);
        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['telephone2Text'].$this->invoiceInformation['myTelephone2'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 80);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myUrl'], false, 8);
        $this->SetY($this->GetY() + 5);
        $this->SetX($this->GetX() + 80);
        $this->Text($this->GetX(), $this->GetY(), $this->invoiceInformation['myEmail'], false, 8);

        $this->SetXY($this->GetX() + 60, $yTemp);

        $this->Text($this->GetX(), $this->GetY(), $this->translation[$this->language]['bankAccount'], false, 8);
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
	
    public function setTranslation() {
		// Finnish
		$this->translation['fi']['billingAddress'] = 'Laskutusosoite';
		$this->translation['fi']['billingDate'] = 'Laskun pvm.';
		$this->translation['fi']['invoiceNumber'] = 'Laskun nro.';
		$this->translation['fi']['referenceNumber'] = 'Viitenumero';
		$this->translation['fi']['termOfPayment'] = 'Maksuehto';
		$this->translation['fi']['dueDate'] = 'Eräpäivä';
		$this->translation['fi']['delayInterest'] = 'Viivästyskorko';
		$this->translation['fi']['billingReference'] = 'Viitteenne';
		$this->translation['fi']['date'] = 'PVM';
		$this->translation['fi']['productName'] = 'Nimike';
		$this->translation['fi']['vatPercent'] = 'ALV %';
		$this->translation['fi']['a'] = 'á';
		$this->translation['fi']['vat'] = 'ALV';
		$this->translation['fi']['qty'] = 'Kpl';
		$this->translation['fi']['sum'] = 'Summa';
		$this->translation['fi']['productsVat0'] = 'Tuotteet (alv 0%)';
		$this->translation['fi']['productsVat'] = 'Tuotteiden alv.';
		$this->translation['fi']['servicesVat0'] = 'Palvelut (alv 0%)';
		$this->translation['fi']['servicesVat'] = 'Palveluiden alv.';
		$this->translation['fi']['totalVat0'] = 'Yhteensä (alv 0%)';
		$this->translation['fi']['vatTotal'] = 'Alv yht.';
		$this->translation['fi']['totalEur'] = 'Yht. EUR';
		$this->translation['fi']['workReports'] = 'Työraportit';
		$this->translation['fi']['invoice'] = 'LASKU';
		$this->translation['fi']['creditInvoice'] = 'HYVITYSLASKU';
		$this->translation['fi']['receipt'] = 'KUITTI';
		$this->translation['fi']['telephone'] = 'Puhelin';
		$this->translation['fi']['www'] = 'WWW';
		$this->translation['fi']['email'] = 'Sähköposti';
		$this->translation['fi']['bankAccount'] = 'Tilinumero';
		$this->translation['fi']['businessID'] = 'Y';
		$this->translation['fi']['telephone1Text'] = 'Puhelintuki: ';
		$this->translation['fi']['telephone2Text'] = 'Asiakaspalvelu: ';

		// English
		$this->translation['en']['billingAddress'] = 'Billing address';
		$this->translation['en']['billingDate'] = 'Billing date.';
		$this->translation['en']['invoiceNumber'] = 'Invoice nr.';
		$this->translation['en']['referenceNumber'] = 'Ref. number';
		$this->translation['en']['termOfPayment'] = 'Payment term';
		$this->translation['en']['dueDate'] = 'Due date';
		$this->translation['en']['delayInterest'] = 'Delay interest';
		$this->translation['en']['billingReference'] = 'Reference';
		$this->translation['en']['date'] = 'Date';
		$this->translation['en']['productName'] = 'Name';
		$this->translation['en']['vatPercent'] = 'VAT %';
		$this->translation['en']['a'] = 'á';
		$this->translation['en']['vat'] = 'VAT';
		$this->translation['en']['qty'] = 'Qty.';
		$this->translation['en']['sum'] = 'Sum';
		$this->translation['en']['productsVat0'] = 'Products (VAT 0%)';
		$this->translation['en']['productsVat'] = 'Products VAT.';
		$this->translation['en']['servicesVat0'] = 'Services (VAT 0%)';
		$this->translation['en']['servicesVat'] = 'Services VAT.';
		$this->translation['en']['totalVat0'] = 'Total (VAT 0%)';
		$this->translation['en']['vatTotal'] = 'VAT total';
		$this->translation['en']['totalEur'] = 'Tot. EUR';
		$this->translation['en']['workReports'] = 'Work reports';
		$this->translation['en']['invoice'] = 'INVOICE';
		$this->translation['en']['creditInvoice'] = 'CREDIT INVOICE';
		$this->translation['en']['receipt'] = 'RECEIPT';
		$this->translation['en']['telephone'] = 'Telephone';
		$this->translation['en']['www'] = 'WWW';
		$this->translation['en']['email'] = 'E-mail';
		$this->translation['en']['bankAccount'] = 'Bank account';
		$this->translation['en']['businessID'] = 'VAT ID';
		$this->translation['en']['telephone1Text'] = 'Remote support: ';
		$this->translation['en']['telephone2Text'] = 'Customer service: ';
    }
	
}

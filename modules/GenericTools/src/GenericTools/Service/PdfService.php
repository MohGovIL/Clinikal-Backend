<?php
/**
 * Created by PhpStorm.
 * User: amiel
 * Date: 10/12/18
 * Time: 14:36
 */

namespace GenericTools\Service;

use GenericTools\Model\LangLanguagesTable;
use Laminas\View\Model\ViewModel;
use Mpdf\Mpdf;
use GenericTools\Controller\GenericToolsController;
use Interop\Container\ContainerInterface;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class PdfService
{
    private $header;
    private $footer;
    private $body;
    private $images = array();
    private $headerHeight = 16;
    private $footerHeight = 16;
    private $exportType = 'I';
    private $fileName = 'Export';
    private $container = null;
    private $renderer = null;
    private $langParameter = null;

    /**
     * PdfService constructor.
     *
     * @param $container - ContainerInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->renderer = $this->container->get('Laminas\View\Renderer\PhpRenderer');
        $langLanguagesTable= $this->container->get(LangLanguagesTable::class);
        $this->langParameter = $langLanguagesTable->getLanguageSettings();

    }

    /**
     * set file name
     * @param $fileName
     */
    public function fileName($fileName){
        $this->fileName = $fileName;
    }

    /**
     * Images in the html (<img src="") not working wen use https, the solution is to out the binary image into variable.
     * Documentation -https://mpdf.github.io/what-else-can-i-do/images.html#image-data-as-a-variable
     * @param $imageName
     * @param $imagePath
     */
    public function addImage($imageName, $imagePath)
    {
        $this->images[$imageName] = $imagePath;
    }

    /**
     * return binary string of pdf (not open in the browser)
     */
    public function returnBinaryString()
    {
        $this->exportType = 'S';
    }

    /**
     * Set header template - Not required
     * @param       $viewTemplate
     * @param array $headerParams
     * @param int   $headerHeight
     */
    public function header($viewTemplate, $headerParams = array(), $headerHeight = 36)
    {
        $pdfHeader = new ViewModel(array_merge($this->langParameter, $headerParams));
        $pdfHeader->setTemplate($viewTemplate);
        $this->header = $this->renderer->render($pdfHeader);
        $this->headerHeight = $headerHeight;
    }

    /**
     * set footer template - Not required
     * @param       $viewTemplate
     * @param array $footerParams
     * @param int   $footerHeight
     */
    public function footer($viewTemplate, $footerParams = array(), $footerHeight = 33)
    {
        $pdfFooter = new ViewModel(array_merge($this->langParameter, $footerParams));
        $pdfFooter->setTemplate($viewTemplate);
        $this->footer = $this->renderer->render($pdfFooter);
        $this->footerHeight = $footerHeight;
    }

    /**
     * set body template - Required
     * @param $viewTemplate
     * @param $parameters
     */
    public function body($viewTemplate, $parameters) {
        $vm = new ViewModel(array_merge($parameters, $this->langParameter));
        $vm->setTemplate($viewTemplate);

        $this->body = $this->renderer->render($vm);
    }

    /**
     * set body template - Required
     * @param $viewTemplate
     * @param $parameters
     */
    public function bodyBuilder($viewTemplate, $parameters) {
        $vm = new ViewModel(array_merge($parameters, $this->langParameter));
        $vm->setTemplate($viewTemplate);

        $this->body .= $this->renderer->render($vm);
    }

    /**
     * set body template - Required
     * @param $viewTemplate
     * @param $parameters
     */
    public function pageBreak() {
        $parameters=array();
        $viewTemplate="generic-tools/pdf/page-break";
        $vm = new ViewModel(array_merge($parameters, $this->langParameter));
        $vm->setTemplate($viewTemplate);

        $this->body .= $this->renderer->render($vm);
    }

    /**
     * Create pdf with standard header and footer (with logos)
     */
    public function setStandardHeaderFooter($showDate = false)
    {
        $user = $this->continer->get('GenericTools\Model\UserTable')->getUser($_SESSION['authUserID']);
        $facility = GenericToolsController::getCurrentFacility();

        $listId='moh_vaccine_clinics';
        $optionId=$facility->facility_code;
        $clinicNameEnglish=$this->continer->get('GenericTools\Model\ListsTable')->getSpecificTitle($listId, $optionId);

        $this->addImage('logoHeader', $GLOBALS['OE_SITE_DIR'] . '/images/logo_1.png');
        $this->addImage('logoFooter', $GLOBALS['OE_SITE_DIR'] . '/images/logo_2.png');


        $showDate=($showDate===true) ? 'true' : $showDate;

        switch ($showDate) {

            case "datetime":
                $showDate= oeFormatDateTime(date("Y-m-d H:m"),false, false);
                break;

            case false :
                $showDate= false;
                break;

            case 'false' :
                $showDate= false;
                break;

            case 'true':
                $showDate= oeFormatDateTime(date("Y-m-d"),false, false);
                break;

            default:
                $showDate=false;
        }

        $this->header('generic-tools/pdf/clinikal-header',array('clinicName' => $facility->name,'clinicNameEnglish' => $clinicNameEnglish,'showDate'=>$showDate));
        $this->footer('generic-tools/pdf/clinikal-footer', array('facility' => $facility));
    }

    /**
     * Render pdf
     * @return string - only if returnBinaryString() is set.
     * @throws \Exception
     */
    public function render()
    {

        if (is_null($this->body)){
            throw new \Exception('Missing pdf body');
        }

        $mpdf = new Mpdf(array('default_font_size' => 11,'default_font' => 'Arial','margin_top' => $this->headerHeight, 'margin_bottom' => $this->footerHeight, 'tempDir' => $GLOBALS['MPDF_WRITE_DIR']));
        $mpdf->showImageErrors = true;
        foreach ($this->images as $var => $image){
            $mpdf->imageVars[$var] = file_get_contents($image);
        }

        if(!is_null($this->header)){
            $mpdf->SetHTMLHeader($this->header);
        }
        if(!is_null($this->footer)){
            $mpdf->SetHTMLFooter($this->footer);
        }

        /*//convet html entities to utf8
        $mpdf->watermarkText = true;
        $wm = \Mpdf\Utils\UtfString::strcode2utf("&#1575;&#1610;&#1604;&#1575;&#1578; &#1601;&#1610;&#1605;&#1575;
    &#1575;&#1610;&#1604;&#1575;&#1578; &#1601;&#1610;&#1605;&#1575;&#10003;");
        $mpdf->SetWatermarkText($wm);*/

        $mpdf->autoLangToFont = true;
        // Write some HTML code:
        $mpdf->WriteHTML($this->body);

        /*slow ? Consider the following:
         * https://
         * /troubleshooting/slow.html
         */
        $mpdf->useSubstitutions = false;
        $mpdf->simpleTables = true;
        if ($this->exportType === 'S') {
            return $mpdf->Output("{$this->fileName}.pdf", 'S');
        } else {
            $mpdf->Output("{$this->fileName}.pdf", $this->exportType);
        }

    }

    public function outputBinaryPdfToBrowser($binaryString, $fileName, $disposition = 'inline')
    {

        $fileName =  is_null($fileName) ? $this->fileName : $fileName;

        // Header content type
        header('Content-type: application/pdf');
        header('Content-Disposition: '.$disposition.'; filename="' . $fileName . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');

        file_put_contents("php://output", $binaryString);
    }

    /**
     * Create pdf with standard header and footer (with logos)
     */
    public function setCustomHeaderFooter($headerPath,$footerPath,$data=array(),$showDate = false)
    {

        $this->addImage('logoHeader', $GLOBALS['OE_SITE_DIR'] . '/images/logo_1.png');
        $this->addImage('logoFooter', $GLOBALS['OE_SITE_DIR'] . '/images/logo_2.png');

        $showDate=($showDate===true) ? 'true' : $showDate;
        switch ($showDate) {
            case "datetime":
                $showDate= oeFormatDateTime(date("Y-m-d H:i"),false, false);
                break;
            case false :
                $showDate= false;
                break;
            case 'false' :
                $showDate= false;
                break;
            case 'true':
                $showDate= oeFormatDateTime(date("Y-m-d"),false, false);
                break;
            default:
                $showDate=false;
        }

        $this->header($headerPath,array('showDate'=>$showDate,'data' => $data));
        $this->footer($footerPath, array('data' => $data));
    }
}

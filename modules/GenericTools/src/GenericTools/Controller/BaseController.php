<?php
/**
 * Created by PhpStorm.
 * User: eyalvo
 * Date: 12/04/18
 * Time: 17:07
 */

namespace GenericTools\Controller;

use Formhandler\Validator\ServerValidationHandler;
use GenericTools\Model\AclTables;
use GenericTools\Model\ValueSetsTable;
use Laminas\Mvc\Controller\AbstractActionController;
use Application\Listener\Listener;
use Laminas\View\Model\ViewModel;
//use GenericTools\Model\CustomSql;
use Laminas\InputFilter\InputFilter;
use Mpdf\Mpdf;
use Interop\Container\ContainerInterface;
use GenericTools\Model\ListsTable;
use GenericTools\Model\PatientsTable;
use GenericTools\Model\UserTable;
use GenericTools\Model\FacilityTable;

use RabiesIncident\Model\RabLogTable;
use GenericTools\Model\LangLanguagesTable;
use GenericTools\Service\MailService;
use GenericTools\Service\PdfService;
use GenericTools\Model\ListsOpenEmrTable;

use GenericTools\Service\ExcelService;

class BaseController extends AbstractActionController
{
    CONST USERTABLE="users";
    public $pdfSettings = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer(){
        if($this->container!=null) {
            return $this->container;
        }
        else{
            return null;
        }
    }
    protected function renderView($parameters)
    {
        if ($this->params()->fromRoute('print') !== 'print') {
            return $parameters;
        } else {
            $printView = $this->params()->fromRoute('action') . '-print';
            $viewModel = new ViewModel($parameters);
            $viewModel->setTemplate("generic-tools/{$printView}.phtml");
            $this->layout('GenericTools/layout/print');
            return $viewModel;
        }
    }

    /**
     * @param int    $headerHeight
     * @param int    $footerHeight
     * @param string $fileName
     * @param array  $images - key:var name, value:full path to image file.
     */
    protected function pdfSettings($headerHeight = 16, $footerHeight = 16, $fileName = 'export', $images = array())
    {
        $this->pdfSettings['headerHeight'] = $headerHeight;
        $this->pdfSettings['footerHeight'] = $footerHeight;
        $this->pdfSettings['fileName'] = $fileName;
        //must to load content of images for https environment - documentation -https://mpdf.github.io/what-else-can-i-do/images.html#image-data-as-a-variable
        $this->pdfSettings['images'] = $images;
    }

    protected function renderPdf($viewModelName, array $parameters = array(), $headerViewModel = null, $headerParameters = null, $footerViewModel  = null, $footerParameters = null, $title  = '')
    {

        $renderer = $this->getServiceLocator()->get('Laminas\View\Renderer\PhpRenderer');
        //$this->layout('GenericTools/layout/pdf');
        $langParameter = array('langDir' => $_SESSION['language_direction'], 'langCode' => $this->getLangLanguagesTable()->getLangCode($_SESSION['language_choice']));

        $vm = new ViewModel(array_merge($parameters, $langParameter));
        $vm->setTemplate($viewModelName);

        $headerParams = is_null($headerParameters) ? array() : $headerParameters;
        $pdfHeader = new ViewModel(array_merge(array('title' => $title), $langParameter, $headerParams));
        $pdfHeader->setTemplate(is_null($headerViewModel) ? 'generic-tools/pdf/header-empty' : $headerViewModel);
        $header = $renderer->render($pdfHeader);

        $footerParams = is_null($footerParameters) ? array() : $footerParameters;
        $pdfFooter = new ViewModel(array_merge($langParameter, $footerParams));
        $pdfFooter->setTemplate(is_null($footerViewModel) ? 'generic-tools/pdf/footer-empty' : $footerViewModel);
        $footer = $renderer->render($pdfFooter);

        //Render ViewModel including childs

        $html = $renderer->render($vm);

        $marginTop = (isset($this->pdfSettings['headerHeight'])) ? $this->pdfSettings['headerHeight'] : 16;
        $marginBottom = (isset($this->pdfSettings['footerHeight'])) ? $this->pdfSettings['footerHeight'] : 16;
        $mpdf = new Mpdf(array('margin_top' => $marginTop, 'margin_bottom' => $marginBottom, 'tempDir' => $GLOBALS['MPDF_WRITE_DIR']));
        $mpdf->showImageErrors = true;
        foreach ($this->pdfSettings['images'] as $var => $image){
            $mpdf->imageVars[$var] = file_get_contents($image);
        }

        $mpdf->SetHTMLHeader($header);
        $mpdf->SetHTMLFooter($footer);
        $mpdf->autoLangToFont = true;


        // Write some HTML code:
        $mpdf->WriteHTML($html);

        // Output a PDF file directly to the browser
        $fileName = (isset($this->pdfSettings['fileName'])) ? $this->pdfSettings['fileName'] : 'export';

        /*slow ? Consider the following:
         * https://mpdf.github.io/troubleshooting/slow.html
         */

        $mpdf->useSubstitutions = false;
        $mpdf->simpleTables = true;
        $mpdf->Output("$fileName.pdf", 'I');
    }

    /**
     * Create pdf file with standard clinikal header and footer
     * @param       $viewTemplate
     * @param array $viewParams
     * @param null  $fileName (optional) default - 'pdf_' . $patient->fname . '_' . $patient->lname . '_' . date("Y_m_d")
     */
    protected function pdfWithStandardHeaderFooter($viewTemplate, array $viewParams = array(), $fileName = null)
    {
        $user = $this->getUserTable()->getUser($_SESSION['authUserID']);
        $facility = $this->getFacilityTable()->getFacility($user->facility_id);

        $listId='moh_vaccine_clinics';
        $optionId=$facility->facility_code;
        $clinicNameEnglish=$this->getListsTable()->getSpecificTitle($listId, $optionId);

        $logo = array(
            'logoHeader' => $GLOBALS['OE_SITE_DIR'] . '/images/logo_1.png',
            'logoFooter' => $GLOBALS['OE_SITE_DIR'] . '/images/logo_2.png',
        );
        if(is_null($fileName)){
            $patient = $this->getPatientsTable()->getPatientData($_SESSION['pid']);
            $fileName = 'pdf_' . $patient->fname . '_' . $patient->lname . '_' . date("Y_m_d");
        }

        $this->getPdfService()->fileName($fileName);
        $this->getPdfService()->setStandardHeaderFooter(($viewParams['showDate'] ? $viewParams['showDate']:false));
        $this->getPdfService()->body($viewTemplate,$viewParams);
        $this->getPdfService()->render();

    }


    /**
     * get instance of PatientVaccines
     * @return array|object
     */
    protected function getMailService()
    {
        if (!$this->mailService) {
            $this->mailService = $this->container->get('GenericTools\Service\MailService');
        }
        return $this->mailService;
    }

    /**
     * get instance of PatientVaccines
     * @return array|object
     */
    protected function getPdfService()
    {
        if (!$this->pdfService) {
            $this->pdfService = $this->container->get('GenericTools\Service\PdfService');
        }
        return $this->pdfService;
    }

    /**
     * get instance of PatientVaccines
     * @return array|object
     */
    protected function getExcelService()
    {

        if (!$this->excelService) {
            if(is_null($this->sm)){
                $this->excelService = $this->container->get(ExcelService::class);
            }
        }
        return $this->excelService;
    }


    /* ========== DATABASE FUNCTIONS ========== */
    /**
     * get instance of PatientVaccines
     * @return array|object
     */
    protected function getListsTable()
    {

        if (!$this->ListsTable) {

            $this->ListsTable = $this->container->get(ListsTable::class);
        }
        return $this->ListsTable;
    }

    /**
     * get instance of PatientVaccines
     * @return array|object
     */
    protected function getListsOpenEmrTable()
    {
        if (!$this->ListsOpenEmrTable) {

            $this->ListsOpenEmrTable = $this->container->get('GenericTools\Model\ListsOpenEmrTable');
        }
        return $this->ListsOpenEmrTable;
    }


    /**
     * get instance of PatientVaccines
     * @return array|object
     */
    protected function getUserTable()
    {

        if (!$this->UserTable) {
            $this->UserTable = $this->container->get(UserTable::class);
        }
        return $this->UserTable;
    }

    /**
     * get instance of PatientVaccines
     * @return array|object
     */
    protected function getPatientsTable()
    {

        if (!$this->PatientsTable) {

            $this->PatientsTable = $this->container->get(PatientsTable::class);
        }
        return $this->PatientsTable;
    }

    /**
     * get instance of PatientVaccines
     * @return array|object
     */
    protected function getFacilityTable()
    {

        if (!$this->FacilityTable) {

            $this->FacilityTable = $this->container->get(FacilityTable::class);
        }
        return $this->FacilityTable;
    }

    /**
     * get instance of langLanguagesTable
     * @return array|object
     */
    protected function getLangLanguagesTable()
    {

        if (!$this->langLanguagesTable) {
            $this->langLanguagesTable = $this->container->get('GenericTools\Model\LangLanguagesTable');
        }
        return $this->langLanguagesTable;
    }


    /**
     * get instance of AclTables
     * @return array|object
     */
    protected function getAclTables()
    {
        if (!$this->aclTables) {
            $this->aclTables = $this->container->get(AclTables::class);
        }
        return $this->aclTables;
    }

    /**
     * get instance of AclTables
     * @return array|object
     */
    protected function getRabLogTable()
    {
        if (!$this->rabLogTable) {
            $this->rabLogTable = $this->container->get(RabLogTable::class);
        }
        return $this->rabLogTable;
    }

    /**
     * @param $data
     * @param bool $convertToJson
     * @param int $responsecode
     * @return \Laminas\Stdlib\ResponseInterface
     * @comment to use this function return this $response in your controller
     */
    protected function responseWithNoLayout($data, $convertToJson=true, $responsecode=200){
        $response = $this->getResponse();
        $response->setStatusCode($responsecode);
        if($convertToJson) {
            $response->setContent(json_encode($data));
        }
        else{
            $response->setContent($data);
        }
        return $response;
    }


    /**
     *Uniform stracture for ajax response
     * */
    protected function ajaxOutPut($data, $code = 0, $status = 'success'){

        return $this->responseWithNoLayout(array(
            'code' => $code,
            'status' => $status,
            'output' => $data
        ));
    }


    public $clientSideValidation = array();
    public $serverSideValidation = array();
    public $clientSideActions = array();

    protected function addValidation($elementName, $isRequire = true, array $filters = array(), array $validation = array())
    {
        /* server side validation */
        $arrValidators = array();
        foreach ($validation as $value)
        {
            $arrValidators[] = array('name' => $value);
        }
        $arrFilters = array();
        foreach ($filters as $value)
        {
            $arrFilters[] = array('name' => $value);
        }

        $this->serverSideValidation[] = array(
            'name' => str_replace("]","",str_replace("[","",$elementName)),
            'required' => $isRequire,
            'validators' => $arrValidators,
            'filters' => $arrFilters
        );


        /* client side validation */
        $jsValidation = $this->getJsValidateConstraints();

        $this->clientSideValidation[$elementName] = array();
        if ($isRequire) $this->clientSideValidation[$elementName] = array_merge($this->clientSideValidation[$elementName], $jsValidation['require']);

        foreach($filters as $filter) {
            $filter = strtolower($filter);
            switch($filter) {
                case 'int':
                case 'digits':
                    $this->clientSideValidation[$elementName] = array_merge($this->clientSideValidation[$elementName], $jsValidation[$filter]);
                    break;
                default:
                    throw new \Exception("Not found client side validation for filter - " . $filter);
            }
        }

        foreach($validation as $validator) {
            if(is_array($validator))
            {
                $validatorCheck = strtolower($validator[0]);
            }
            else {
                $validatorCheck = strtolower($validator);
            }
            switch($validatorCheck) {
                case 'float':
                case 'IntNotZero':
                case 'intnotzero':
                case "RequiredBy" :
                case "Required" :
                case "required" :
                case "numeric" :
                case "nopastdate" :
                case "datefromnow" :
                case "time" :
                case "encounteruntiltoday" :
                case "age" :
                case "doubledigit" :
                case "intnotzero" :
                case "year" :
                case "num" :
                case "numericality" :
                case "checked" :
                case "require" :
                case "equality" :
                case 'valueisequalto' :
                case "float" :
                case "luhn":
                    $this->clientSideValidation[$elementName] = array_merge($this->clientSideValidation[$elementName], $jsValidation[$validatorCheck]);
                break;
                case 'greaterthandate' :
                    $dateMinus1 = oeFormatShortDate(date("Y-m-d",strtotime($validator[1]." -1 day")));
                    $date = oeFormatShortDate(date("Y-m-d",strtotime($validator[1])));

                    $validatorJSNeeded = $jsValidation['greaterthandate'];
                    $validatorJSNeeded['date']['earliest'] = str_replace("#Date#",$date,$validatorJSNeeded['date']['earliest']);
                    $validatorJSNeeded['date']['message'] = str_replace("#Date#",$dateMinus1,$validatorJSNeeded['date']['message']);
                    $this->clientSideValidation[$elementName] = array_merge($this->clientSideValidation[$elementName],$validatorJSNeeded);
                break;
                case "greaterthanbirthday":
                    $validatorJSNeeded = $jsValidation['greaterthanbirthday'];
                    $validatorJSNeeded['date']['earliest'] = str_replace("#Date#",$validator[1],$validatorJSNeeded['date']['earliest']);
                    $this->clientSideValidation[$elementName] = array_merge($this->clientSideValidation[$elementName],$validatorJSNeeded);
                break;

                case "smallerthandate":
                    $validatorJSNeeded = $jsValidation['smallerthandate'];
                    $validatorJSNeeded['date']['latest'] = str_replace("#Date#",$validator[1],$validatorJSNeeded['date']['latest']);

                    $datePlus1 = oeFormatShortDate(date("Y-m-d",strtotime(DateToYYYYMMDD($validator[1])." +1 day")));
                    $validatorJSNeeded['date']['message'] = str_replace("#Date#",$datePlus1,$validatorJSNeeded['date']['message']);
                    $this->clientSideValidation[$elementName] = array_merge($this->clientSideValidation[$elementName],$validatorJSNeeded);
                    break;

                case 'betweendates' :
                    $dateMinus1 = oeFormatShortDate(date("Y-m-d",strtotime($validator[1]." -1 day")));
                    $date = oeFormatShortDate(date("Y-m-d",strtotime($validator[1])));

                    $validatorJSNeeded = $jsValidation['betweendates'];
                    $validatorJSNeeded['date']['earliest'] = str_replace("#Date1#",$date,$validatorJSNeeded['date']['earliest']);
                    $validatorJSNeeded['date']['message'] = str_replace("#Date1#",$dateMinus1,$validatorJSNeeded['date']['message']);

                    $datePlus1 = oeFormatShortDate(date("Y-m-d",strtotime($validator[2]." +1 day")));
                    $date = oeFormatShortDate(date("Y-m-d",strtotime($validator[2])));

                    $validatorJSNeeded['date']['latest'] = str_replace("#Date2#",$date,$validatorJSNeeded['date']['latest']);
                    $validatorJSNeeded['date']['message'] = str_replace("#Date2#",$datePlus1,$validatorJSNeeded['date']['message']);


                    $this->clientSideValidation[$elementName] = array_merge($this->clientSideValidation[$elementName],$validatorJSNeeded);
                    break;


                default:
                    throw new \Exception("Not found client side validation for validator - " . $validator);
            }
        }
    }

    protected function getEncounterDate(){
        return oeFormatDateTime( date("Y/m/d"));
    }
    /**
     * get all constraints for client side validation (for validate.js library) with translation
     * Important - when you add new server validation that not exist here you should adding it with validate.js syntax
     * @return array
     */
    protected function getJsValidateConstraints()
    {

        if (!$this->validation) {

            $this->validation =  array(
                'RequiredBy'=>array(
                    'presence' => array(
                        'message' => xlt('Value is required')
                    )
                ),
                'Required' => array(
                    'presence' => array(
                        'message' => xlt('Value is required')
                    )
                ),
                'required' => array(
                    'presence' => array(
                        'message' => xlt('Value is required')
                    )
                ),
                'int' => array(
                    'numericality' => array(
                        "onlyInteger"=> true,
                        "message"=> xlt("Must be number"))
                ),
                'numeric' => array(
                    'numericality'=> array('strict'=> true,'message' => xlt('Only numbers'))
                ),
                'datefromnow'=> array(
                    "date"=>array(
                        "latest"=> $GLOBALS['date_display_format'] == 2 ? date("d/m/Y") : date("Y-m-d"),
                        "message"=> xlt("Can not pick future date"),
                        "format"=>  $GLOBALS['date_display_format'] == 2 ? "DD/MM/YYYY" : 'YYYY-MM-DD'

                    )
                ),
                'nopastdate'=> array(
                    "date"=>array(
                        "earliest"=> $GLOBALS['date_display_format'] == 2 ? date("d/m/Y") : date("Y-m-d"),
                        "message"=> xlt("Can not pick past date"),
                        "format"=>  $GLOBALS['date_display_format'] == 2 ? "DD/MM/YYYY" : 'YYYY-MM-DD'

                    )
                ),
                'encounteruntiltoday'=> array(
                    "date"=>array(
                        "latest"=> $GLOBALS['date_display_format'] == 2 ? date("d/m/Y") : date("Y-m-d"),
                        "earliest"=> $this->getEncounterDate(),
                        "message"=> xlt("Date is not in range"),
                        "format"=>  $GLOBALS['date_display_format'] == 2 ? "DD/MM/YYYY" : 'YYYY-MM-DD'
                    )
                ),
                'age'=> array(
                    'numericality'=>array(
                        'onlyInteger'=> true,
                        'greaterThan'=> 0,
                        'lessThan' => 999,
                        'message'=> xlt("Age must be between 0 to 999"),
                    ),
                ),
                'doubledigit'=> array(
                    'numericality'=>array(
                        'onlyInteger'=> true,
                        'greaterThan'=> 0,
                        'lessThanOrEqualTo' => 99,
                        'message'=> xlt("Should be between 0 to 99")
                    )
                ),
                'intnotzero'=> array(
                    'numericality'=>array(
                        'onlyInteger'=> true,
                        'greaterThan'=> 0,
                        'message'=> xlt("Should be greater than 0")
                    )

                ),
                'year'=> array(
                    "numericality"=>array(
                        "onlyInteger"=> true,
                        "greaterThan"=> 1000,
                        "lessThanOrEqualTo" => 9999,
                        "message"=> xlt("Age must be between 0 to 999")
                    )

                ),
                'num'=> array(
                    'numericality' => array(
                        "onlyInteger"=> true,
                        "message"=> xlt("Must be number")

                    )

                ),
                'additiveequality'=>array(

                    'numericality'=>array(
                        'greaterThan'=> "#start_date",
                        "message"=> xlt("The end time must be later then the start one")
                    )

                ),
                'checked'=>array(
                    'numericality'=>array(
                        'greaterThan'=> "0",
                        "message"=> xlt("This should be checked")
                    )
                ),
                'require' => array(
                    'presence' => array(
                        'message' => xls('Value is required')
                    )
                ),
                'int' => array(
                    'numericality' => array(
                        "onlyInteger"=> true,
                        "message"=> xls("Must be number"))
                ),
                'digits'  => array(
                    'numericality' => array(
                        "onlyInteger"=> true,
                        "message"=> xls("Must be number"))
                ),
                'float' => array(
                    'format' => array(
                        'pattern' => "(\d+(\.\d+)?)",
                        'message' => xls('Only numbers')
                    )
                ),
                "lessThanOrEqualTo" =>array (
                        "numericality"=>array(
                            "onlyInteger"=> true,
                            "lessThanOrEqualTo"=> '#Number#',
                            "message"=> xlt("Must be less than or equal to")."#Number#"
                    )

                ),
                "greaterThanOrEqualTo" =>array (
                        "numericality"=>array(
                            "onlyInteger"=> true,
                            "greaterThanOrEqualTo"=> '#Number#',
                            "message"=> xlt("Must be grater than or equal to")."#Number#"
                    )

                ),
                "lessThan" =>array (
                    "numericality"=>array(
                        "onlyInteger"=> true,
                        "lessThan"=> '#Number#',
                        "message"=> xlt("Must be less than or equal to")."#Number#"
                    )

                ),
                "greaterThan" =>array (
                    "numericality"=>array(
                        "onlyInteger"=> true,
                        "greaterThan"=> '#Number#',
                        "message"=> xlt("Must be grater than")."#Number#"
                    )

                ),
                'time'=> array(
                    'format' => array(
                                'pattern' => "^(?:2[0-3]|[01][0-9]):[0-5][0-9]$",
                                'message' => xlt("Time must be in a time format")
                                )
                ),
                'greaterthanbirthday'=> array(
                    "date"=>array(
                        "earliest"=> "#Date#",
                        "message"=> xlt("Date must be after DOB"),
                        "format"=>  $GLOBALS['date_display_format'] == 2 ? "DD/MM/YYYY" : 'YYYY-MM-DD'

                    )
                ),
                'greaterthandate'=> array(
                    "date"=>array(
                        "earliest"=> "#Date#",
                        "message"=> xlt("Date must be after")." #Date#",
                        "format"=>  $GLOBALS['date_display_format'] == 2 ? "DD/MM/YYYY" : 'YYYY-MM-DD'
                    )
                ),
                'smallerthandate'=> array(
                    "date"=>array(
                        "latest"=> "#Date#",
                        "message"=> xlt("Date must be before")." #Date#",
                        "format"=>  $GLOBALS['date_display_format'] == 2 ? "DD/MM/YYYY" : 'YYYY-MM-DD'
                    )
                ),
                'betweendates'=> array(
                    "date"=>array(
                        "earliest"=> "#Date1#",
                        "latest"=>"#Date2#",
                        "message"=> xlt("Date must be after")." #Date1#" ." ".xlt("And before date")." #Date2#",
                        "format"=>  $GLOBALS['date_display_format'] == 2 ? "DD/MM/YYYY" : 'YYYY-MM-DD'
                    )
                ),
                'luhn'=>array(
                'luhn'=> array("message"=> xlt("ID number is not valid"))
                )
        );
        }
        return $this->validation;
    }


    protected function executeJSValidation()
    {
        $this->clientSideValidation['custom_messages'] = true;
        $this->layout()->setVariable('validateJs', json_encode($this->clientSideValidation));
        $this->layout()->setVariable('constraintsJs', json_encode($this->getJsValidateConstraints()));
    }


    protected function executeThis($rules,$element) {
        switch ( $rules[1]) {
            case "ns": //not selected
                if ($element[$rules[0]]=="selected" || $element[$rules[0]]=="checked" || $element[$rules[0]]=="1") {
                    return true;
                }
                else{
                    return false;
                }
                break;
            case "eq":
            case "se": //selected and equal to
                if ($element[$rules[0]] == $rules[2]){
                    return true;
                }
                else{
                    return false;
                }
                break;

            case "ne":
                if ($element[$rules[0]] != $rules[2]){
                    return true;
                }
                else{
                    return false;
                }
                break;
            case "checked":
                if ($element[$rules[0]]=="checked" || $element[$rules[0]]=="1")
                    return true;
                break;
            case "not_checked":
                if ($element[$rules[0]]!="checked" || $element[$rules[0]]=="0")
                    return true;
                break;
            case "smaller":
                if ($element[$rules[0]] <  $rules[2])
                    return true;
                break;
            case "bigger":
                if ($element[$rules[0]] > $rules[2])
                    return true;
                break;
            case "begin_bigger_end":
            case "biggerThan":
                if ($element[$rules[0]] > $rules[2])
                    return true;
                break;
            case "smallerThan":
                if ($element[$rules[0]] < $rules[2])
                    return true;
                break;
            case "smallerThanEqual":
                if ($element[$rules[0]] <=$rules[2])
                    return true;
                break;
            case "biggerThanEqual":
                if ($element[$rules[0]] >= $rules[2])
                    return true;
                break;
            case "and":
            {

             /*   if ($this->executeThis(rules[0]) && $this->executeThis(rules[2]))
                    return true;
                break;*/
            }
            case "or":
            {

              /*  if ($this->executeThis(rules[0]) || $this->executeThis(rules[2]))
                    return true;
                break;*/
            }
        }
        return false;
    }

    function action($action,&$elementsValues,&$elementsValidationsFH) {

        $name = $action[0];
        $actionR = $action[1];
        $rules = $action[2];


        switch ( $actionR) {
          /*  case "showIf":
                if (executeThis( $rules)) {
                    $("[$name='" + $name + "'").parent().show();

                    if ($("#label_for_" + $name).length)
                        $("#label_for_" + $name).show();

                }
                else {
                    $("[$name='" + $name + "']").parent().hide();
                    $("[$name='" + $name + "']").val('');
                    if ($("#label_for_" + $name).length)
                        $("#label_for_" + $name).hide();
                }
                break;*/
            case "disableIf":
            case "hideIf":
                if ($this->executeThis( $rules,$elementsValues)) {
                    unset($elementsValidationsFH[$name]);
                }
                break;
            case "requireIf":


                if ($this->executeThis( $rules,$elementsValues)) {
                    //$elementsValidationsFH[$name] =   $this->validation ['Required'];
                    $elementsValidationsFH[$name][]['name'] =   'Required';
                }
                else {
                    foreach( $elementsValidationsFH[$name] as $key=>$value){
                        if($value['name']=='Required') {
                            unset( $elementsValidationsFH[$name][$key]);
                        }
                    }


                }
                break;

            case "cleanIf":
                if ($this->executeThis( $rules,$elementsValues)) {
                    if($elementsValues[$name]!="")
                        $elementsValues[$name]="";
                }
                break;
            case "errorIf":
                //show errors according conditions
                if ($this->executeThis( $rules,$elementsValues)) {
                    /*  $("#error_msg").remove();
                      //disable not working on this button, always return to active I didn't find why.
                      $("#button_submit").css('pointer-events', 'none');
                      $("[$name='"+$name+"']").addClass('error-border');
                      switch ( $rules[1]) {
                          // error message for begin time that later then  end time
                          case 'begin_bigger_end':
                              $("[$name='"+$name+"']").after("<p id='error_msg'><small class='error-message' ><?php echo $this->translate->z_xlt('End time smaller than begin time')?></small></p>");
                              break
                          default:
                              $("[$name='"+$name+"']").after("<small id='error_msg'><?php echo $this->translate->z_xlt('Value is valid')?></small>");
                      }*/
                }
                else {
                 /*   //hide error and enable submit
                    $("#button_submit").css('pointer-events', 'auto');
                    $("[$name='"+$name+"']").removeClass('error-border');
                    $("#error_msg").remove();*/
                }
                break;
            case "lessThanOrEqualTo" :{
                if ($this->executeThis( $rules,$elementsValues)) {



                    $elementsValidationsFH[$name]['lessThanOrEqualTo'] = str_replace("#Number",  $rules[2],$elementsValidationsFH[$name]['lessThanOrEqualTo'] );
                }
                else{

                    unset( $elementsValidationsFH[$name]['lessThanOrEqualTo']);
                }
                break;
            }
            case "lessThan" :{
                if ($this->executeThis( $rules,$elementsValues)) {

                    $elementsValidationsFH[$name]['lessThan'] = str_replace("#Number",  $rules[2],$elementsValidationsFH[$name]['lessThan'] );

                }
                else{

                    unset( $elementsValidationsFH[$name]['lessThan']);
                }
                break;
            }
            case "greaterThanOrEqualTo" :{
                if ($this->executeThis( $rules,$elementsValues)) {

                    $elementsValidationsFH[$name]['greaterThanOrEqualTo'] = str_replace("#Number",  $rules[2],$elementsValidationsFH[$name]['greaterThanOrEqualTo'] );

                }
                else{

                    unset( $elementsValidationsFH[$name]['lessThan']);
                }
                break;

            }
            case "greaterThan" :{
                if ($this->executeThis( $rules,$elementsValues)) {

                    $elementsValidationsFH[$name]['greaterThan'] = str_replace("#Number",  $rules[2],$elementsValidationsFH[$name]['greaterThan'] );

                }
                else{

                    unset( $elementsValidationsFH[$name]['greaterThan']);
                }
                break;
            }

            case "GreaterThanBirthday" :{
                if ($this->executeThis( $rules,$elementsValues)) {

                    $elementsValidationsFH[$name]['GreaterThanBirthday'] = str_replace("#Date",  $rules[2],$elementsValidationsFH[$name]['greaterThan'] );

                }
                else{

                    unset( $elementsValidationsFH[$name]['GreaterThanBirthday']);
                }
                break;
            }

            case "GreaterThanDate" :{
                if ($this->executeThis( $rules,$elementsValues)) {
                    $elementsValidationsFH[$name]['GreaterThanDate'] = str_replace("#Date",  $rules[2],$elementsValidationsFH[$name]['greaterThan'] );
                }
                else{
                    unset( $elementsValidationsFH[$name]['GreaterThanDate']);
                }
                break;
            }
            case "BetweenDates" :{
                if ($this->executeThis( $rules,$elementsValues)) {
                    $elementsValidationsFH[$name]['BetweenDates'] = str_replace("#Date",  $rules[2],$elementsValidationsFH[$name]['betweenDates'] );
                }
                else{
                    unset( $elementsValidationsFH[$name]['BetweenDates']);
                }
                break;
            }

            case "SmallerThanDate" :{
                if ($this->executeThis( $rules,$elementsValues)) {
                    $elementsValidationsFH[$name]['SmallerThanDate'] = str_replace("#Date",  $rules[2],$elementsValidationsFH[$name]['smallerThanDate'] );
                }
                else{
                    unset( $elementsValidationsFH[$name]['SmallerThanDate']);
                }
                break;
            }


        }
    }


    protected function buildValidationAccordingToActionData($elementsValues,&$clientSideActions,&$elementsValidationsFH){
        $addValidationArray=[];
        foreach($clientSideActions as $key=>$action){
            $this->action($action,$elementsValues,$elementsValidationsFH);
        }
    }
    protected function validateElements($elementsValues,$elementsValidationsZF,$clientSideActions,$collectAllValidationForFurtherChecking = false){

        //translate Amiel's zf validation to formhandler validations
        $elementsValidationsFH = $this->customizeValidationToFormhandlerValidation($elementsValidationsZF);

        //build up more validation by checking the required actions and the values that came from the client side

        $this->buildValidationAccordingToActionData($elementsValues,$clientSideActions,$elementsValidationsFH);

        //check validation on the elements and returns the an array of element that didn't meet required validations

            $validatorhandler=new ServerValidationHandler($elementsValues,$elementsValidationsFH);
            $serverValidationRes=$validatorhandler->isValid();
            foreach($serverValidationRes as $key=> $value){
                foreach($value as $k=> $v) {

                    if($v!= "OK"){
                        $elementsThatDoesntMeetValidation[$key]=xlt($v);
                    }
                    else{
                        if($collectAllValidationForFurtherChecking){
                            $elementsThatDoesntMeetValidation[$key]=$v;
                        }
                    }

                }
            }

            return $elementsThatDoesntMeetValidation;
    }

    protected function customizeValidationToFormhandlerValidation($genericValidationArray){
        $formhandlerValidationArr=[];
        foreach($genericValidationArray as $genericValidation){
            if($genericValidation['required']){
                $formhandlerValidationArr[$genericValidation['name']][] =  array ('name' => 'required');
            }
            if(isset($genericValidation['validators']) && sizeof($genericValidation['validators'])>0) {
                foreach($genericValidation['validators'] as $validator)
                $formhandlerValidationArr[$genericValidation['name']][] = $validator;
            }

        }
        return $formhandlerValidationArr;
    }
    protected function getServerSideValidation()
    {
        return $this->serverSideValidation;
    }

    protected function getInputFilter($validators)
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            foreach($validators as $input) {

                $inputFilter->add($input);
            }

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

    protected function getUserNameById($uid)
    {
        $sql = 'SELECT fname, lname FROM ' . self::USERTABLE . ' WHERE id = ?';

        $user_name = sqlQuery($sql, array($uid));
        $user_full_name = $user_name['fname'] . "   " . $user_name['lname'];

        return $user_full_name;
    }


    /**
     * function for debugger
     */
    public function die_r($dada) {
        echo "<pre>";
        print_r($dada);
        echo "</pre>";
        die;
    }

    /**
     * @param int $day
     * @param int $month
     * @param int $year
     * @return false|string
     */

    public function getCustomizedDate($dateString=null,$day=0,$month=0,$year=0){
        if($dateString==null || $dateString ==""){
            throw new \Exception(get_called_class ()." called getCustomizedDate() function with null dateWasNotSentToFunction param " . $dateString);
        }
        $incidentDate = $dateString;
        $timespan = strtotime ($incidentDate." $year year $month month $day day");

        return date("Y-m-d",$timespan);

    }

    public function getNoOfPassedDaysFrom($stringDate){
        $now = time(); // or your date as well
        $your_date = strtotime($stringDate);
        $datediff = $now - $your_date;

        return round($datediff / (60 * 60 * 24));
    }

    public function getTitleOfValueSet($value,$valueSet){
        $valueSetsTable = $this->container->get(ValueSetsTable::class);
        $where=array ('filter' => array (0 => array ('value' => $value, 'operator' => '=',),),);
        return $valueSetsTable->getValueSetById($valueSet,$where);
    }

    protected function getConnectedUserId()
    {
        return $_SESSION['authUserID'];
    }


}

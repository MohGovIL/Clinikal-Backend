<?php

namespace ClinikalAPI\Controller;

use FhirAPI\FhirRestApiBuilder\Parts\ErrorCodes;
use FhirAPI\FhirRestApiBuilder\Parts\Strategy\StrategyElement\ServiceRequest\FhirServiceRequestMapping;
use FhirAPI\FhirRestApiBuilder\Parts\Strategy\StrategyElement\ServiceRequest\ServiceRequest;
use FhirAPI\Model\FhirServiceRequestTable;
use FhirAPI\Model\QuestionnaireResponseTable;
use Formhandler\View\Helper\GenericTable;
use GenericTools\Controller\BaseController as GenericBaseController;
use GenericTools\Model\AclTables;
use GenericTools\Model\EncounterReasonCodeMapTable;
use GenericTools\Model\FormEncounterTable;
use GenericTools\Model\ListsTable;
use GenericTools\Model\Prescriptions;
use GenericTools\Model\PrescriptionsTable;
use GenericTools\Model\ValueSetsTable;
use ImportData\Model\CodesTable;
use Interop\Container\ContainerInterface;
use GenericTools\Traits\saveDocToServer;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;

class PdfBaseController extends GenericBaseController
{
    use saveDocToServer;

    const HEADER_PATH = 'clinikal-api/pdf/default-header';
    const FOOTER_PATH = 'clinikal-api/pdf/default-footer';
    const DOC_TYPE = "file_url";
    const PDF_MINE_TYPE = "application/pdf";
    const CITIES_LIST = 'mh_cities';
    const STREETS_LIST = 'mh_streets';

    private $container;
    public $postData = array();
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->container = $container;
    }

    /**
     * get facility data
     */
    public function getFacilityInfo($id = null)
    {
        if (!is_null($id)) {
            $info = $this->container->get('GenericTools\Model\FacilityTable')->getFacility(intval($id));
            $data = array();
            $data['clinic'] = $info->name;
            $data['address'] = $info->street . " " . $info->city;
            $data['phone'] = $info->phone;
            $data['email'] = $info->email;
            return $data;

        } else {
            return array();
        }
    }

    public function getPatientInfo($id = null)
    {

        if (!is_null($id)) {
            $info = $this->container->get('GenericTools\Model\PatientsTable')->getPatientDataById(intval($id));
            $idTitle = $this->container->get(ListsTable::class)->getTitles("userlist3",'"'.$info['mh_type_id'].'"');
            $data = array();
            $data['name'] = $info['fname']." ".$info['lname'];
            $data['id_number'] = $info['ss'];
            $data['id_type'] = $idTitle[$info['mh_type_id']];
            $data['Gender'] = $info['sex'];
            $data['birthdate'] = $info['DOB_DMY'];
            $data['age'] = $info['age'];
            $data['phone'] = ($info['phone_cell'] ? $info['phone_cell']  : ($info['phone_home'] ? $info['phone_home'] : ($info['phone_contact'] ? $info['phone_contact'] :""))) ;
            $data['HMO'] = $info['insurance_organiz_name'];
            $data['address'] = $this->patientAddress($info);
            return $data;

        } else {
            return array();
        }
    }

    public function patientAddress($patientInfo)
    {
        $city = !empty($patientInfo['city']) ? xlt($this->container->get(ListsTable::class)->getSpecificTitle(self::CITIES_LIST, $patientInfo['city'])) : '';
        $street = !empty($patientInfo['street']) ? xlt($this->container->get(ListsTable::class)->getSpecificTitle(self::STREETS_LIST, $patientInfo['street'])) : '';
        $numberHouse = !empty($patientInfo['mh_house_no']) ? $patientInfo['mh_house_no'] : '';
        $pobox = !empty($patientInfo['mh_pobox']) ? $patientInfo['mh_pobox'] : '';

        $address = '';
        if ($street !== '') {
          $address .= $street . ' ' . $numberHouse . ', ';
        }
        if ($pobox !== '') {
            $address .= xlt('PO Box') . ' ' . $pobox .', ';
        }
        $address .= $city;

        return $address;
    }

    public function getListsOpenEMRInfo($type=null,$pid,$encounter,$outcome)
    {
        if (!is_null($type)) {
            $info = $this->container->get('GenericTools\Model\ListsOpenEmrTable')->getListWithTheType($type,$pid,$encounter,$outcome);
            $result = [];
            foreach ($info as $item) {
                if(!in_array($item, $result)) {
                    $result[] = $item;
                }
            }
            return $result;

        } else {
            return array();
        }
    }

    public function getEncounterStartDate($encId)
    {
        $info = $this->container->get(FormEncounterTable::class)->fetchById($encId);
        return $info['date'];
    }

    public function getUserInfo($id = null)
    {
        if (!is_null($id)) {
            $info = $this->container->get('GenericTools\Model\UserTable')->getUser(intval($id));
            $aro = $this->container->get(AclTables::class)->whatIsUserAroGroups(intval($id));

            $data = array();
            if(/*$aro[1]=="doc" ||*/ $aro[0] == "emergency_doctor")
            {
                $data['name'] = xl("Dr.")." ".($info->fname?$info->fname:"")." ". ($info->mname?$info->mname:"")." ".($info->lname?$info->lname:"");
                $data['state_license_number'] = $info->state_license_number?$info->state_license_number:"";
            }
            else{
                $data['name'] = "";
                $data['state_license_number'] = "";
            }

            return $data;

        } else {
            return array();
        }
    }

    public function saveDocToStorage($data, $fileName, $date)
    {
        $dataToSave = array();
        $dataToSave['storage']['data'] = $data;
        $dataToSave['documents']['date'] = $date;
        $dataToSave['documents']['url'] = $fileName;
        $rez = $this->uploadToStorage($dataToSave);
        return $rez;
    }

    public function saveDocInfoToDb($storageSave, $configData, $pdfEncoded, $fileName = null)
    {

        if ($storageSave['id']) {

            $configData = array_merge($configData, $storageSave);

            $dbStructuredData = $this->buildArrToDb($configData);

            if (empty($dbStructuredData)) {
                ErrorCodes::http_response_code('500', 'failed to build data to db');
                return array();
            } else {

                $save = $this->saveDocToDb($dbStructuredData);

                if($save){
                    return array(
                        "id" => $save,
                        "base64_data" => $pdfEncoded,
                        "mimetype" => $configData['mimetype'],
                        "file_name" => !is_null($fileName) ? $fileName : ''
                    );
                }else{
                    ErrorCodes::http_response_code('500', 'failed to build data to db');
                    return array();
                }
            }
        } else {
            ErrorCodes::http_response_code('500', 'failed to save document');
            return array();
        }
    }

    public function createConfigData($postData,$mimetype, $category)
    {
        if (empty($postData['facility']) || empty($postData['encounter'])) {
            return array();
        }

        $configData = array(
            'mimetype' => $mimetype,
            'category' => $category,
            'encounter' => $postData['encounter']
        );

        if (empty($postData['owner'])) {
            $configData['owner'] = $postData['owner'];
        }

        if (empty($postData['patient'])) {
            $configData['patient'] = $postData['patient'];
        }
        return $configData;
    }

    public function createBase64Pdf($fileName,$bodyPath,$headerPath, $footerPath,$headerData,$bodyDataTemp)
    {
        $this->getPdfService()->fileName($fileName);
        $this->getPdfService()->setCustomHeaderFooter($headerPath,$footerPath,$headerData,"datetime");
        //added multi-paged functionality to letter creator.
        if(is_array($bodyPath))
        {
            foreach($bodyPath as $key=>$path) {
                $bodyData=$bodyDataTemp[$key];
                if ($key > 0) {
                    $this->getPdfService()->pagebreak();
                }
                $this->getPdfService()->bodyBuilder($path, $bodyData);

            }
        }
        else {
            $bodyData=$bodyDataTemp;
            $this->getPdfService()->body($bodyPath, $bodyData);
        }
        $this->getPdfService()->returnBinaryString();
        $binary=$this->getPdfService()->render();
        $pdfEncoded= base64_encode($binary);

        return $pdfEncoded;
    }

    public function getTitleOfOptionFromListTable($list,$option){
        $listsTable= $this->container->get(ListsTable::class);
        $title = $listsTable->getSpecificTitle($list,$option);
        return $title;
    }

    public function getReasonCodesTitles($list,$codes){
        $listsTable= $this->container->get(ListsTable::class);
        $titles = $listsTable->getTitles($list,$codes);
        return $titles;
    }
    public function getReasonCodes($encounter_id){
        $reasonCode= $this->container->get(EncounterReasonCodeMapTable::class);
        $reasonCodes = $reasonCode->fatchAllByEID($encounter_id,true);
        return $reasonCodes;
    }


    public function getQData($qid,$class){
        $FormMedicalAdmissionQTable= $this->container->get($class);
        $dbData=$FormMedicalAdmissionQTable->getLastQuestionnaireAnswer($this->postData['encounter'],$qid);
        return $dbData['answer'];
    }
    public function getServiceTypeAndReasonCode(){
        //get encounter
        $FormMedicalAdmissionQTable= $this->container->get(FormEncounterTable::class);
        $encounter = $FormMedicalAdmissionQTable->fetchById($this->postData['encounter']);
        //get clinikal_service_types and reason codes
        $service_type = $this->getTitleOfOptionFromListTable("clinikal_service_types",$encounter['service_type']);
        $reason_codes = $this->getReasonCodes($encounter['id'],true);
        $reason_codes_titles = $this->getReasonCodesTitles("clinikal_reason_codes",$reason_codes,true);
        foreach($reason_codes_titles as $key=>$title)
        {
            $reason_codes_titles[$key] = xl($title);
        }
        return xl($service_type)." - ".implode(",",$reason_codes_titles) ."<br/>" . $encounter['reason_codes_details'];
    }
    public function getServiceTypeAndReasonCodeArray(){
        $serviceTypeReasoncode=$this->getServiceTypeAndReasonCode();
        $arrTemp=explode("<br/>",$serviceTypeReasoncode);
        return ["service_and_reason"=>$arrTemp[0],"details"=>$arrTemp[1]];
    }

    public function getSensitivities(){
        return $this->getListsOpenEMRInfo("sensitive",$this->postData['patient'],$this->postData['encounter'],1);
    }
    public function getMedicalProblems(){
        return $this->getListsOpenEMRInfo("medical_problem",$this->postData['patient'],$this->postData['encounter'],1);
    }
    public function getMedicine(){
        return $this->getListsOpenEMRInfo("medication",$this->postData['patient'],$this->postData['encounter'],1);
    }
    public function createTableJsonFromVitals($vitals){
        $arrTemp = [];
        foreach($vitals as $rk => $rows)
        {
            foreach($rows as $ck => $column)
            {
               $arrTemp['columns'][$ck]=$column[0];
            }
            break;
        }

        $arrTemp ['rows'] = $vitals;

        return $arrTemp;
    }
    public function getConstantVitals($encounter,$pid,$category,$acitivity,$order)
    {
        $vitals = $this->getVitals($encounter,$pid,$category,$acitivity,$order);
        foreach($vitals as $k=>$v)
        {
            if($k === 0) {
                foreach ($v as $key => $value) {
                    if ($key !== 'height' && $key !== 'weight') {
                        unset($vitals[$k][$key]);
                    } else {
                        $vitals[$k][$key][2] = (is_null($value[2]) || $value[2] == "" || $value[2] == 0 && $value[2] == "0.00") ?"-":$value[2];
                        if ($vitals[$k][$key][2] !== '-') {
                            $vitals[$k][$key][2] = $key === 'weight' ? number_format($value[2],1) : number_format($value[2]);
                        }
                    }
                }
            }
            else{
                unset($vitals[$k]);
            }
        }
        return sizeof($vitals[0]) > 1 ? $this->createTableJsonFromVitals($vitals) : null;
    }
    public function getVariantVitals($encounter,$pid,$category,$acitivity,$order)
    {
        $vitals = $this->getVitals($encounter,$pid,$category,$acitivity,$order);



        foreach($vitals as $key=>$value){
            foreach($value['bpd'] as $k=>$v) {
                if($k!==2)
                {
                    $vitals[$key]['pressure'][$k] = $vitals[$key]['bpd'][$k];
                }
                else {
                    $vitals[$key]['pressure'][$k] = !is_null($vitals[$key]['bps'][$k] ) ? $vitals[$key]['bps'][$k] . "/" . $vitals[$key]['bpd'][$k] : '-';
                }
            }

            foreach($value as $k=>$v) {
                switch($k){
                    case 'height':
                    case 'weight':
                    case 'temp_method':
                    case 'BMI':
                    case 'BMI_status':
                    case 'waist_circ':
                    case 'head_circ':
                        unset($vitals[$key][$k]);
                        break;
                    case 'temperature':
                        $vitals[$key][$k][2] = $vitals[$key][$k][2] !== '0.00' && $vitals[$key][$k][2] > 0 ? number_format($vitals[$key][$k][2],1) : '-';
                        break;
                    case 'pulse':
                    case 'respiration':
                    case 'oxygen_saturation':
                        $vitals[$key][$k][2] = $vitals[$key][$k][2] !== '0.00' && $vitals[$key][$k][2] > 0 ? number_format($vitals[$key][$k][2],0) : '-';
                        break;
                    case 'pain_severity':
                        $vitals[$key][$k][2] = is_null($v[2]) ? "-":$v[2];
                        break;
                    case 'date':
                        $time = explode(":",$vitals[$key][$k][1]);
                        unset($time[2]);
                        $vitals[$key][$k][1] = implode(":",$time);
                        break;
                    default:
                        $vitals[$key][$k][2] = $vitals[$key][$k] && (is_null($v[2]) || $v[2] == "" || $v[2] == 0 && $v[2] == "0.00") ?"-":$v[2];
                        break;
                }
            }



            unset($vitals[$key]['bps']);
            unset($vitals[$key]['bpd']);
        }

        return $this->createTableJsonFromVitals($vitals);
    }
    public function getVitals($encounter,$pid,$category,$acitivity,$order){
        $observationList =  $this->container->get('GenericTools\Model\ListsTable')->getList("loinc_org");
        $observationTitleList = [];
        foreach($observationList as $key=>$value)
        {
            $notes = json_decode($value['notes']);
            $observationTitleList[$value['mapping']] = [xl($notes->label),xl($value['subtype'])];
        }
        $observations = $this->container->get('GenericTools\Model\FormVitalsTable')->getVitals($encounter,$pid,$category,$acitivity,$order);
        $observedArr = [];

        foreach($observations as $keyParent=>$valuesParent)
        {
            $observedArr[$keyParent]=[];
            foreach($valuesParent as $key=>$value) {
                if($observationTitleList[$key]) {
                    $observedArr[$keyParent][$key] = array_merge($observationTitleList[$key], [$value]);
                }
                if($key==="date"){
                    $observedArr[$keyParent][$key]=[xlt("Hour"), oeFormatDateTime($value)];
                }
            }

        }
        return $observedArr;
    }
    public function getPrescriptions($eid,$pid){
     return $FhirServiceRequestTable = $this->container->get(PrescriptionsTable::class)->getPatientPrescription($eid,$pid);
    }

    public function getServiceRequest($eid,$pid,$status,$xrayList=null){
        $FhirServiceRequestTable = $this->container->get(FhirServiceRequestTable::class);
        $serviceRequests = $FhirServiceRequestTable->getTeastAndTreatmentsPreformed($eid,$pid,$status);

        foreach($serviceRequests as $key=>$value)
        {
            $valueSet = $this->getTitleOfValueSet($value['order_detail_code'],$value['order_detail_system']);
            if($valueSet) {
                $serviceRequests[$key]['order_detail_code'] = xl($valueSet[0]['display']);
            }
            $tests_and_treatments = $this->getTitleOfValueSet($value['instruction_code'],'tests_and_treatments');
            $serviceRequests[$key]['tests_and_treatments_title'] = xl($tests_and_treatments[0]['display']);
        }

        return $serviceRequests;

    }

    public function getDrugRoute(){
        $listsTable= $this->container->get(ListsTable::class);
        $list = $listsTable->getListNormalized("drug_route");
        return $list;
    }
    public function getDrugInterval(){
        $listsTable= $this->container->get(ListsTable::class);
        $list = $listsTable->getListNormalized("drug_interval");
        return $list;
    }
    public function getDrugForms($list){
        foreach($list as $k=>$v){
            if($v!="ml" && $v!="Drops")
                 $list[$k] = $v."s";
        }
        return $list;
    }
    public function getDrugForm(){
        $listsTable= $this->container->get(ListsTable::class);
        $list = $listsTable->getListForViewFormNoTranslation("drug_form");
        return $list;
    }

    public function getQuestionareUpdatedUser($encounter, $questionaireName)
    {
        $result = $this->container->get(QuestionnaireResponseTable::class)->buildGenericSelect(['encounter' => $encounter, 'form_name' => $questionaireName]);
        return !empty($result) ? $result[0]['update_by'] : null;
    }

}

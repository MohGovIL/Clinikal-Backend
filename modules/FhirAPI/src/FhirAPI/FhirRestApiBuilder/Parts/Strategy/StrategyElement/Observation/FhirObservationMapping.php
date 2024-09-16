<?php
/**
 * Date: 21/01/20
 * @author  eyal wolanowski <eyalvo@matrix.co.il>
 * This class MAPPING FOR Condition
 */

namespace FhirAPI\FhirRestApiBuilder\Parts\Strategy\StrategyElement\Observation;

use Exception;
use FhirAPI\FhirRestApiBuilder\Parts\ErrorCodes;
use FhirAPI\FhirRestApiBuilder\Parts\Strategy\StrategyElement\MappingData;
use FhirAPI\Service\FhirBaseMapping;
use GenericTools\Model\FormVitalsTable;
use GenericTools\Model\ListsTable;
use Interop\Container\ContainerInterface;
use FhirAPI\FhirRestApiBuilder\Parts\Strategy\Traits\FHIRElementValidation;

/*include FHIR*/

use Laminas\Form\Annotation\Instance;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRObservation;
use OpenEMR\FHIR\R4\FHIRElement\FHIRDateTime;

use OpenEMR\FHIR\R4\FHIRElement\FHIRObservationStatus;
use OpenEMR\FHIR\R4\FHIRResource\FHIRObservation\FHIRObservationComponent;
use phpDocumentor\Reflection\Types\Object_;
use function DeepCopy\deep_copy;

class FhirObservationMapping extends FhirBaseMapping  implements MappingData
{
    private $adapter = null;
    private $container = null;
    private $FHIRObservation = null;

    private $loincCodes= array();
    private $lonicDbMappig= array();
    private $categoryList= array();

    CONST LONIC_ORG="loinc_org";
    CONST LONIC_SYSTEM="http://loinc.org";
    CONST CATEGORY_SYSTEM="http://hl7.org/fhir/ValueSet/observation-category";
    CONST CATEGORY_LIST="observation-category";

    use FHIRElementValidation;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->container = $container;
        $this->adapter = $container->get('Laminas\Db\Adapter\Adapter');
        $this->FHIRObservation = new FHIRObservation;

        $ListsTable = $this->container->get(ListsTable::class);
        $listOutcome = $ListsTable->getList(self::LONIC_ORG);

        $this->setLonicDbMappig($listOutcome);
        $this->setLoincCodes($listOutcome);

        $categoryList = $ListsTable->getList(self::CATEGORY_LIST);
        $this->setCategoryList($categoryList);


    }


    /**
     * set fhir object
     */
    public function setFHIR($fhir=null)
    {
        if(is_null($fhir)){
            $this->FHIRObservation = new FHIRObservation;
            return $this->FHIRObservation;
        }
        try{
            $this->FHIRObservation = new FHIRObservation($fhir);
            return $this->FHIRObservation;
        }catch(Exception $e){
            return false;
        }
    }

    /**
     * return fhir object
     */
    public function getFHIR()
    {
        return $this->FHIRObservation;
    }

    public function setLonicDbMappig($list)
    {
        foreach($list as $code =>$dataArr){
            $this->lonicDbMappig[$code]=$dataArr['mapping'];
        }
        return $this->lonicDbMappig;
    }

    public function getLonicToDbMappig()
    {
        return $this->lonicDbMappig;
    }

    public function getDbToLonicMappig()
    {
        return array_flip($this->lonicDbMappig);
    }


    public function setLoincCodes($types)
    {
        $this->loincCodes=$types;
        return $this->loincCodes;
    }

    public function getLoincCodes()
    {
        return $this->loincCodes;
    }

    public function setCategoryList($list)
    {
        foreach($list as $code =>$dataArr){
            $this->categoryList[$code]=$dataArr['title'];
        }
        return $this->categoryList;
    }

    public function getCategoryList()
    {
        return $this->categoryList;
    }



    /**
     * convert FHIRObservation to db array
     *
     * @param FHIRObservation
     *
     * @return array;
     */
    public function fhirToDb($FHIRObservation)
    {
        $dbObservation = array();

        $dbObservation['id']=$FHIRObservation->getId()->getValue();

        $FHIRdate= $FHIRObservation->getIssued()->getValue();
        $dbObservation['date']= $this->convertToDateTime($FHIRdate);

        $pidRef=$FHIRObservation->getSubject()->getReference()->getValue();
        if (strpos($pidRef, self::PATIENT_URI) !== false ) {
            $dbObservation['pid']= (!empty($pidRef)) ? substr($pidRef,strlen(self::PATIENT_URI),20) : null;
        }else{
            $dbObservation['pid']=null;
        }

        $userRef=$FHIRObservation->getPerformer()[0]->getReference()->getValue();
        if (strpos($userRef, self::PRACTITIONER_URI) !== false ) {
            $dbObservation['user']= (!empty($userRef)) ? substr($userRef,strlen(self::PRACTITIONER_URI),20) : null;
        }else{
            $dbObservation['user']=null;
        }

        $eidRef=$FHIRObservation->getEncounter()->getReference()->getValue();
        if (strpos($eidRef, self::ENCOUNTER_URI) !== false ) {
            $dbObservation['eid']= (!empty($eidRef)) ? substr($eidRef,strlen(self::ENCOUNTER_URI),20) : null;
        }else{
            $dbObservation['eid'] = null;
        }

        $dbObservation['activity'] =  ($FHIRObservation->getStatus()->getValue() !== 'cancelled') ? 1 : 0;
        $dbObservation['observation_status'] =  $FHIRObservation->getStatus()->getValue();
        $dbObservation['note'] = $FHIRObservation->getNote()[0]->getText()->getValue();
        $dbObservation['category'] = $FHIRObservation->getCategory()[0]->getCoding()[0]->getCode()->getValue();

        $components=$FHIRObservation->getComponent();

        $LonicToDbMappig=$this->getLonicToDbMappig();

        foreach($components as $index => $comp){

            $code=$comp->getValueCodeableConcept()->getCoding()[0];
            $codeVal=$code->getCode()->getValue();
            if(!is_null($codeVal)){
                $system=$code->getSystem()->getValue();
                $lonicCode=substr($system, strrpos($system, '/') + 1);
                $dbObservation[$LonicToDbMappig[$lonicCode]]=$codeVal;
            }

            $Quantity=$comp->getValueQuantity()->getValue();
            $QuantityVal=$Quantity->getValue();
            if(!is_null($QuantityVal)){
                $lonicCode=$comp->getValueQuantity()->getCode()->getValue();
                $dbObservation[$LonicToDbMappig[$lonicCode]]=trim($QuantityVal);
            }
        }

        return $dbObservation;
    }

    /**
     * create FHIRObservation
     *
     * @param  string
     * @return array
     * @throws
     */


    public function parsedJsonToDb($parsedData)
    {
        $dbObservation = array();
        return $dbObservation;
    }


    public function initFhirObject(){

        $FHIRObservation = new FHIRObservation();
        $FhirId = $this->createFHIRId(null);
        $FHIRObservation->setId($FhirId);

        $FHIRInstant = $this->createFHIRInstant(null,null);
        $FHIRObservation->setIssued($FHIRInstant);

        $FHIRReference=  $this->createFHIRReference(["reference" => null]);
        $FHIRObservation->setSubject(deep_copy($FHIRReference));
        $FHIRObservation->setEncounter(deep_copy($FHIRReference));
        $FHIRObservation->addPerformer(deep_copy($FHIRReference));

        $FHIRObservationStatus=$this->createFHIRObservationStatus();
        $FHIRObservation->setStatus($FHIRObservationStatus);

        $FHIRAnnotation = $this->createFHIRAnnotation(array());
        $FHIRObservation->addNote($FHIRAnnotation);

        $FHIRCodeableConcept=$this->createFHIRCodeableConcept(array("code"=>null,"text"=>"","system"=>""));
        $FHIRObservation->addCategory($FHIRCodeableConcept);

        $FHIRObservationComponent =$this->createFHIRObservationComponent(null,null);
        $FHIRObservation->addComponent($FHIRObservationComponent);

        $this->FHIRObservation=$FHIRObservation;
        return $FHIRObservation;

    }

    public function DBToFhir(...$params)
    {
        $observationDataFromDb = $params[0];
        $FHIRObservation =$this->FHIRObservation;

        if(!empty($observationDataFromDb)){

            $FHIRObservation->getId()->setValue($observationDataFromDb['id']);

            $FHIRInstant = $this->createFHIRInstant(null,null,$observationDataFromDb['date'])->getValue();
            $FHIRObservation->getIssued()->setValue($FHIRInstant);

            if(!is_null($observationDataFromDb['pid'])){
                $uri = self::PATIENT_URI . $observationDataFromDb['pid'];
                $FHIRSubjectString =$this->createFHIRString($uri);
                $FHIRObservation->getSubject()->setReference($FHIRSubjectString);
            }

            if(!is_null($observationDataFromDb['user'])){
                $uri = self::PRACTITIONER_URI . $observationDataFromDb['user'];
                $FHIRPerformerString =$this->createFHIRString($uri);
                $FHIRObservation->getPerformer()[0]->setReference($FHIRPerformerString);
            }

            if(!is_null($observationDataFromDb['eid'])){
                $uri = self::ENCOUNTER_URI . $observationDataFromDb['eid'];
                $FHIREncounterString =$this->createFHIRString($uri);
                $FHIRObservation->getEncounter()->setReference($FHIREncounterString);
            }

            if(!is_null($observationDataFromDb['observation_status'])){
                $FHIRObservation->getStatus()->setValue($observationDataFromDb['observation_status']);
            }

            if(!is_null($observationDataFromDb['note'])){
                $FHIRObservation->getNote()[0]->setText($observationDataFromDb['note']);
            }

            if(!is_null($observationDataFromDb['category'])){

                $category=$FHIRObservation->getCategory()[0];
                $categoryCoding=$category->getCoding()[0];

                $FHIRCode=$this->createFHIRCode($observationDataFromDb['category']);
                $categoryCoding->setCode($FHIRCode);

                $FHIRUri=$this->createFHIRUri(self::CATEGORY_SYSTEM);
                $categoryCoding->setSystem($FHIRUri);

                $categoryCodeList=$this->getCategoryList();
                $category->setText($categoryCodeList[$observationDataFromDb['category']]);
            }

            $DbToLonicMappig=$this->getDbToLonicMappig();

            $firstComp=true;
            foreach ($DbToLonicMappig as $dbField => $code){
                if(is_numeric(trim($observationDataFromDb[$dbField])) ){                 // 0.00 is default empty for several columns in openemr db
                    $FHIRElm=$this->createFHIRQuantity(array("code"=>$code,"value"=> $observationDataFromDb[$dbField] !== '0.00' ? trim($observationDataFromDb[$dbField]) : null,"system"=>self::LONIC_SYSTEM));
                }else{
                    $FHIRElm=$this->createFHIRCodeableConcept(array("code"=>$observationDataFromDb[$dbField],"system"=>self::LONIC_SYSTEM."/".$code));
                }

                if ($firstComp){
                    if(is_object($FHIRElm)){
                        $fhirElementName=$FHIRElm->get_fhirElementName();
                        $methodName="setValue".$fhirElementName;
                        $FHIRObservationComponent=$FHIRObservation->getComponent()[0];
                        if(!is_null($FHIRElm) && method_exists($FHIRObservationComponent,$methodName)){
                            $FHIRObservationComponent->$methodName($FHIRElm);
                        }
                    }
                    $firstComp=false;
                }else{
                    $FHIRObservationComponent =$this->createFHIRObservationComponent($FHIRElm,null);
                    $FHIRObservation->addComponent($FHIRObservationComponent);
                }



            }
        }

        $this->FHIRObservation=$FHIRObservation;

        return $FHIRObservation;
    }

    public function parsedJsonToFHIR($data)

    {
        $FHIRObservation =$this->FHIRObservation;


        $this->FHIRObservation=$FHIRObservation;

        return $FHIRObservation;
    }

    public function getDbDataFromRequest($data)
    {
        $this->initFhirObject();
        $this->arrayToFhirObject($this->FHIRObservation,$data);
        $dBdata = $this->fhirToDb($this->FHIRObservation);
        return $dBdata;
    }

    public function updateDbData($data,$id)
    {
        $listsOpenEmrTable = $this->container->get(FormVitalsTable::class);

        /*********************************** validate *******************************/
        $encounterDataFromDb = $listsOpenEmrTable->buildGenericSelect(["id"=>$id]);
        $allData=array('new'=>$data,'old'=>$encounterDataFromDb);
        //$mainTable=$listsOpenEmrTable->getTableName();
        $isValid=$this->validateDb($allData,null);
        /***************************************************************************/

        if($isValid){
            $primaryKey='id';
            $primaryKeyValue=$id;
            unset($data[$primaryKey]);
            $rez=$listsOpenEmrTable->safeUpdate($data,array($primaryKey=>$primaryKeyValue));
            if(is_array($rez)){
                $this->initFhirObject();
                $patient=$this->DBToFhir($rez);
                return $patient;
            }else{ //insert failed
                ErrorCodes::http_response_code('500','insert object failed :'.$rez);
            }
        }else{ // object is not valid
            ErrorCodes::http_response_code('406','failed validation');
        }
        //this never happens since ErrorCodes call to exit()
        return false;
    }


    /**
     * create FHIRObservationStatus
     *
     * @param string
     *
     * @return FHIRObservationStatus | null
     */
    public function createFHIRObservationStatus($code=null){
        $FHIRObservationStatus= new FHIRObservationStatus;
        if(!is_null($code)) {
            $codeVal=$this->createFHIRCode($code)->getValue();
            $FHIRObservationStatus->setValue($codeVal);
        }
        return $FHIRObservationStatus;

    }

    /**
     * create FHIRObservationStatus
     *
     * @param Instance
     * @param string
     *
     * @return FHIRObservationComponent | null
     */
    public function createFHIRObservationComponent( $fhirElm=null,$code=null){
        $FHIRObservationComponent= new FHIRObservationComponent;
        //resourceType

        if(is_object($fhirElm)){
            $fhirElementName=$fhirElm->get_fhirElementName();
            $methodName="setValue".$fhirElementName;
        }else{
            $fhirElementName="";
            $methodName=null;
        }


        if(!is_null($fhirElm) && method_exists($FHIRObservationComponent,$methodName)){
            $FHIRObservationComponent->$methodName($fhirElm);
        }else{
            $FHIRCodeableConcept=$this->createFHIRCodeableConcept(array("code"=>null,"text"=>"","system"=>""));
            $FHIRObservationComponent->setValueCodeableConcept($FHIRCodeableConcept);
            $FHIRQuantity=$this->createFHIRQuantity(array());
            $FHIRObservationComponent->setValueQuantity($FHIRQuantity);
        }

        $FHIRObservationComponent->setCode($code);

        return $FHIRObservationComponent;

    }






}









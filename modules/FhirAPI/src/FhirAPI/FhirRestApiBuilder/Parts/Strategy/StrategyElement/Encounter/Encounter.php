<?php
/**
 * Date: 29/01/20
 *  @author Eyal Wolanowski <eyalvo@matrix.co.il>
 * This class strategy Fhir  Encounter
 *
 *
 */


namespace FhirAPI\FhirRestApiBuilder\Parts\Strategy\StrategyElement\Encounter;

use FhirAPI\FhirRestApiBuilder\Parts\ErrorCodes;
use FhirAPI\FhirRestApiBuilder\Parts\Patch\GenericPatch;
use FhirAPI\FhirRestApiBuilder\Parts\Registry;
use FhirAPI\FhirRestApiBuilder\Parts\Restful;
use FhirAPI\FhirRestApiBuilder\Parts\Search\SearchContext;
use FhirAPI\FhirRestApiBuilder\Parts\Strategy\Strategy;
use FhirAPI\Model\QuestionnaireResponseTable;

use GenericTools\Model\DocumentsTable;
use GenericTools\Model\FormEncounterTable;
use Interop\Container\ContainerInterface;

/*include FHIR*/

use OpenEMR\FHIR\R4\FHIRDomainResource\FHIREncounter;
use OpenEMR\FHIR\R4\FHIRResource\FHIRBundle;
use OpenEMR\FHIR\R4\FHIRResourceContainer;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;


class Encounter Extends Restful implements  Strategy
{
    const SEARCHSTRATEGYPATH = "FhirAPI\FhirRestApiBuilder\Parts\Search\SearchStrategies\\";
    const LEFTJOIN = "JOIN_LEFT";
    const RIGHTJOIN = "RIGHT_LEFT";
    const JOININNER = "INNER_JOIN";


    public function __construct($params=null)
    {
        if(!is_null($params))
        {
            $this->initParams($params);
        }
    }

    private function initParams($initials){
        $this->setParamsFromUrl($initials['paramsFromUrl']);
        $this->setParamsFromBody($initials['paramsFromBody']);
        $this->setContainer($initials['container']);
        $this->setMapping($initials['container'],$initials['strategyName']);
    }

    public function doAlgorithm($arrParams)
    {
        $this->initParams($arrParams);

        $this->functionName = $arrParams['type'];
        $function = Restful::$data[$arrParams['strategyName']][self::$function][$this->functionName];
        return $this->$function();
    }


    public function setMapping($container,$strategyName)
    {
        $this->mapping = new FhirEncounterMapping($container);
        $this->mapping->setSelfFHIRType($strategyName);
    }

    public function getJoinArray(){

    }

    public function read()
    {
        $fhirEncounterMapping =$this->mapping;
        $encounterTable = $this->container->get(FormEncounterTable::class);
        $encounterDataFromDb = $encounterTable->buildGenericSelect(["id"=>$this->paramsFromUrl[0]]);
        if(!$encounterDataFromDb)
        {
            //not found
            return self::$errorCodes::http_response_code(204);
        }

        $this->mapping->initFhirObject();
        $fhirEncounter=$fhirEncounterMapping->DBToFhir($encounterDataFromDb[0],[]);
        $this->mapping->initFhirObject();
        return $fhirEncounter;
    }


    public function search()
    {
        // TODO: Implement Search() method.
        $paramsToSearch = array(
            'tableToSearchOnOrm'=>$this->container->get(FormEncounterTable::class),
            'fhirObj'=>new FhirEncounterMapping($this->container),
            'paramsToSearch'=>Registry::getSearchParamsAvailibleForThisStrategy("Encounter"),
            'container'=>$this->container,
            'paramsFromUrl'=>$this->paramsFromUrl[0],
            'paramsFromBody'=>$this->paramsFromBody,
            'buildThisSearch' => self::SEARCHSTRATEGYPATH . 'EncounterSearch',
            'join' =>  null
        );
        $searchContext = new SearchContext($paramsToSearch);
        return $searchContext->doSearch();

        //return SearchContext::search($paramsToSearch);

    }

    /**
     * create Encounter
     *
     * @param string
     * @return FHIRBundle | FHIREncounter
     * @throws
     */
    public function create()
    {
        $dBdata = $this->mapping->getDbDataFromRequest($this->paramsFromBody['POST_PARSED_JSON']);
        unset($dBdata['form_encounter']['id']);
        $dBdata["status_update_date"]= date("Y-m-d H:i:s");
        $formEncounterTable = $this->container->get(FormEncounterTable::class);


        /*********************************** validate *******************************/
        $alldata=array('new'=>$dBdata,'old'=>array());
        $mainTable=$formEncounterTable->getTableName();
        $isValid=$this->mapping->validateDb($alldata,$mainTable);
        if(!$isValid){
            ErrorCodes::http_response_code("406","failed validation");
        }
        /***************************************************************************/

        $inserted=$formEncounterTable->safeInsertEncounter($dBdata);
        $this->paramsFromUrl[0]=$inserted[0]['id'];
        return $this-> read();
    }


    /**
     * update Encounter data
     *
     * @param string
     * @return FHIRBundle | FHIREncounter
     * @throws
     */
    public function update()
    {
        $dBdata = $this->mapping->getDbDataFromRequest($this->paramsFromBody['POST_PARSED_JSON']);
        $id =$this->paramsFromUrl[0];
        return $this->mapping->updateDbData($dBdata,$id);
    }

    /**
     * update Encounter data
     *
     * @param string
     * @return FHIRBundle | FHIREncounter
     * @throws
     */
    public function patch()
    {
        $initPatch['paramsFromUrl']=$this->paramsFromUrl;
        $initPatch['paramsFromBody']=$this->paramsFromBody;
        $initPatch['container']=$this->container;
        $initPatch['mapping']=$this->mapping;
        $initPatch['selfApiCalls']=new Encounter($initPatch);

        $patch = new GenericPatch($initPatch);
        return $patch->patch();
    }


    /**
     * delete Encounter data
     *
     * @param string
     * @return FHIRBundle | FHIREncounter
     * @throws
     */
    public function delete()
    {
        //check if can delete data
        $documentsTable = $this->container->get(DocumentsTable::class);
        $encounterId = $this->paramsFromUrl[0];

        // can not delete encounter that has a document
        $params = array('encounter_id' => $encounterId);
        $documentsDataFromDb = $documentsTable->buildGenericSelect($params);
        if (!empty($documentsDataFromDb))
        {
            $moreInfo = "failed to delete from db";
            $explanation = "document linked to this encounter was found-delete it first";
            return $this->mapping->createDeleteFailRespond($encounterId, $explanation, $moreInfo);
        }

        // can not delete encounter that has forms
        $questionnaireResponseTable = $this->container->get(QuestionnaireResponseTable::class);
        $questionnaireResponseFromDb=$questionnaireResponseTable->buildGenericSelect(["questionnaire_response.encounter"=>$encounterId]);
        if (!empty($questionnaireResponseFromDb))
        {
            $moreInfo = "failed to delete from db";
            $explanation = "At least one form linked to this encounter was found-delete it first";
            return $this->mapping->createDeleteFailRespond($encounterId, $explanation, $moreInfo);
        }

        $encounterTable = $this->container->get(FormEncounterTable::class);

        // can not delete encounter that does not exist
        $encounterDataFromDb = $encounterTable->buildGenericSelect(["id" => $this->paramsFromUrl[0]]);
        if (empty($encounterDataFromDb))
        {
            $moreInfo = "failed to delete from db";
            $explanation = "encounter was not found";
            return $this->mapping->createDeleteFailRespond($encounterId, $explanation, $moreInfo);
        }

        // can not delete encounter that is not in status planned
        $encStatus = $encounterDataFromDb[0]["status"];
        if ($encStatus !== "planned")
        {
            $moreInfo = "failed to delete from db";
            $explanation = "encounter status is " . $encStatus;
            return $this->mapping->createDeleteFailRespond($encounterId, $explanation, $moreInfo);
        }

        $delete = $encounterDataFromDb = $encounterTable->deleteDataByParams(array("id" => $encounterId));
        if ($delete === 1) {
            return $this->mapping->createDeleteSuccessRespond();
        } else {
            $explanation = "failed to delete from db ";
            return $this->mapping->createDeleteFailRespond($encounterId, $explanation);
        }
    }



    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;

    }

    /**
     * @param mixed $params
     */
    public function setParams($params): void
    {
        $this->params = $params;
    }

    /**
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }




}

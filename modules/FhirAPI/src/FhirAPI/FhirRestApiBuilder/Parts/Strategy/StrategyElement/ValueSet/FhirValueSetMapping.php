<?php

namespace FhirAPI\FhirRestApiBuilder\Parts\Strategy\StrategyElement\ValueSet;

use FhirAPI\FhirRestApiBuilder\Parts\Strategy\StrategyElement\MappingData;
use FhirAPI\Service\FhirBaseMapping;
use Interop\Container\ContainerInterface;
use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRValueSet;
use OpenEMR\FHIR\R4\FHIRElement\FHIRBackboneElement;
use OpenEMR\FHIR\R4\FHIRResource\FHIRValueSet\FHIRValueSetContains;
use OpenEMR\FHIR\R4\FHIRResource\FHIRValueSet\FHIRValueSetExpansion;

class FhirValueSetMapping extends FhirBaseMapping  implements MappingData
{
    private $adapter = null;
    private $container = null;
    private $FHIRValueSet = null;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->container = $container;
        $this->adapter = $container->get('Laminas\Db\Adapter\Adapter');
        $this->FHIRValueSet = new FHIRValueSet;
    }

    public function fhirToDb($FhirObject){}

    public function DBToFhir(...$params){
        $data = $params[0];
        $FHIRValueSet =$this->FHIRValueSet;

        $FHIRValueSet->getId()->setValue($data[0]['vs_id']);
        $FHIRValueSet->getTitle()->setValue($data[0]['vs_title']);
        $FHIRValueSet->getStatus()->setValue($data[0]['vs_status']);
        $FHIRValueSet->getLanguage()->setValue($data[0]['vs_lang']);

        $operations = $params[1];

        // build expansion (contains the actual codes)
        if(in_array('$expand', $operations)) {

            $FHIRExpansion=$FHIRValueSet->getExpansion();

            $FHIRDateTime=$this->createFHIRDateTime(null,null,date("Y-m-d h:i:s"));
            $FHIRExpansion->setTimestamp($FHIRDateTime);

            // build 'contains'
            $contains=$FHIRExpansion->getContains();
            foreach ($data as $index => $row) {

                    $row['system'] = self::LIST_SYSTEM_LINK . $row['system'];

                    if($index==0){
                        $contains[$index]->getDisplay()->setValue($row['display']);
                        $contains[$index]->getCode()->setValue($row['code']);
                        $contains[$index]->getSystem()->setValue($row['system']);
                    }else{
                        $FHIRValueSetContains= $this->createFHIRValueSetContains($row);
                        $FHIRExpansion->addContains($FHIRValueSetContains);
                    }
            }
        }

        $this->FHIRValueSet = $FHIRValueSet;
        return $this->FHIRValueSet;
    }

    public function parsedJsonToDb($parsedData){}

    public function validateDb($data){}

    public function initFhirObject()
    {
        $FHIRValueSet = new FHIRValueSet;

  ;
        $FhirId = $this->createFHIRId(null);
        $FHIRValueSet->setId($FhirId);

        $FHIRString= $this->createFHIRString(null);
        $FHIRValueSet->setTitle($FHIRString);

        $FHIRCode = $this->createFHIRCode(null);
        $FHIRValueSet->setLanguage($FHIRCode);

        $FHIRPublicationStatus=$this->createFHIRPublicationStatus(null);
        $FHIRValueSet->setStatus($FHIRPublicationStatus);

        $FHIRValueSetExpansion= $this->createFHIRValueSetExpansion(array());
        $FHIRValueSet->setExpansion($FHIRValueSetExpansion);

        $this->FHIRValueSet=$FHIRValueSet;
        return $FHIRValueSet;
    }

    /**
     * create FHIRValueSetContains
     *
     * @param $string
     *
     * @return FHIRValueSetContains | null
     */
    public function createFHIRValueSetContains(array $data )
    {
        $FHIRValueSetContains = new FHIRValueSetContains;

        if(!empty($data['system'])){
            $FHIRUri= $this->createFHIRUri($data['system']);
        }else{
            $FHIRUri= $this->createFHIRUri(null);
        }
        $FHIRValueSetContains->setSystem($FHIRUri);

        if(!empty($data['code'])){
            $FHIRCode= $this->createFHIRCode($data['code']);
        }else{
            $FHIRCode= $this->createFHIRCode(null);
        }
        $FHIRValueSetContains->setCode($FHIRCode);

        if(!empty($data['display'])){
            $FHIRString= $this->createFHIRString($data['display']);
        }else{
            $FHIRString= $this->createFHIRString(null);
        }
        $FHIRValueSetContains->setDisplay($FHIRString);

        return $FHIRValueSetContains;
    }

    /**
     * create FHIRValueSetExpansion
     *
     * @param $string
     *
     * @return FHIRValueSetExpansion | null
     */
    public function createFHIRValueSetExpansion(array $data )
    {
        $FHIRValueSetExpansion = new FHIRValueSetExpansion;


        if(!empty($data['contains']) && is_array($data['contains'])){
            foreach($data['contains'] as $index => $contain){
                $FHIRValueSetContains = $this->createFHIRValueSetContains($contain);
                $FHIRValueSetExpansion->addContains($FHIRValueSetContains);
            }
        }else{
            $FHIRValueSetContains = $this->createFHIRValueSetContains(array());
            $FHIRValueSetExpansion->addContains($FHIRValueSetContains);
        }


        if(!empty($data['timestamp'])){
            $FHIRDateTime= $this->createFHIRDateTime(null,null,$data['timestamp']);
        }else{
            $FHIRDateTime= $this->createFHIRDateTime(null);
        }
        $FHIRValueSetExpansion->setTimestamp($FHIRDateTime);


        if(!empty($data['offset'])){
            $FHIRInteger= $this->createFHIRInteger($data['offset']);
        }else{
            $FHIRInteger= $this->createFHIRInteger(null);
        }
        $FHIRValueSetExpansion->setOffset($FHIRInteger);


        if(!empty($data['total'])){
            $FHIRInteger= $this->createFHIRInteger($data['total']);
        }else{
            $FHIRInteger= $this->createFHIRInteger(null);
        }
        $FHIRValueSetExpansion->setTotal($FHIRInteger);


        return $FHIRValueSetExpansion;
    }


}

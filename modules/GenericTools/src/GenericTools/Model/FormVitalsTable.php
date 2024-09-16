<?php


namespace GenericTools\Model;

use Laminas\Db\TableGateway\TableGateway;

class FormVitalsTable
{
    use baseTable;
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getVitals($encounter,$pid,$category,$activity,$order){
        return $this->buildGenericSelect(['eid' => $encounter,'pid'=>$pid,'category'=>$category,'activity'=>$activity],$order);
    }

}

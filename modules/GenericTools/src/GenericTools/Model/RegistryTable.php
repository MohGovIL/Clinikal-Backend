<?php


namespace GenericTools\Model;

use Laminas\Db\Sql\Where;
use Laminas\Db\TableGateway\TableGateway;
use GenericTools\Model\UtilsTraits\JoinBuilder;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class RegistryTable
{
    use baseTable;
    use JoinBuilder;
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
        $this->join = $this->joinTables();
    }


    private function joinTables()
    {

        $joinFieldsArr=  [

              'linkIds'=>new Expression('GROUP_CONCAT(qs.qid)'),
              'form_tables'=>new Expression('GROUP_CONCAT(qs.form_table)'),
              'column_types'=>new Expression('GROUP_CONCAT(qs.column_type)'),
              'questions'=>new Expression('GROUP_CONCAT(qs.question)'),
             ];

        $this->appendJoin(
            ["qs"=>"questionnaires_schemas"],
            new Expression("qs.form_name=registry.directory"),
            $joinFieldsArr,
            Select::JOIN_LEFT
        );

        $this->addGroupForJoin('registry.directory');

        return $this->getJoins();
    }

    public function getFormsKeyDirectoryValueName($equalConditions)
    {
        $where = new Where();
        foreach ($equalConditions as $column => $value) {
            $where->equalTo($column, $value);
        }
        $rs= $this->tableGateway->select($where);
        $rsArray=array();
        foreach($rs as $r) {
            $rsArray[$r->directory]=$r->name;
        }
        return $rsArray;
    }

}

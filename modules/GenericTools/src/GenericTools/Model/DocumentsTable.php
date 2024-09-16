<?php


namespace GenericTools\Model;

use GenericTools\Model\UtilsTraits\JoinBuilder;
use Laminas\Db\Sql\Select;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Expression;

class DocumentsTable
{
    use baseTable;
    use JoinBuilder;

    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
        $this->join = $this->joinTables();
    }

    public function lastId()
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(array(
            'id' => new Expression('MAX(id)')
        ));
        $rowSet = $this->tableGateway->selectWith($select);
        $row = $rowSet->current();
        return $row->id;
    }

    private function joinTables()
    {
        $this->join =  $this->appendJoin(
            ["cat_to_doc"=>"categories_to_documents"],
            new Expression(" documents.id = cat_to_doc.document_id"),
            ['category_id'=>'category_id'],
            Select::JOIN_INNER
        );
        $this->join =  $this->appendJoin(
            ["cat"=>"categories"],
            new Expression(" cat_to_doc.category_id = cat.id"),
            ['name'=>'name'],
            Select::JOIN_INNER
        );

        return $this->getJoins();
    }
}

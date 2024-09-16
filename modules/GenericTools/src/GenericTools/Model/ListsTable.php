<?php

namespace GenericTools\Model;

use Laminas\Db\Sql\Where;
use Laminas\Db\TableGateway\TableGateway;


class ListsTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }



    public function fetchAll()
    {
        $rsArray=array();
        $rs= $this->tableGateway->select();
        foreach($rs as $r) {
            $rsArray[]=get_object_vars($r);
        }

        return $rsArray;
    }

    /**
     * Fetches list from list_options by listId and optionally another parameter
     * @param $listId
     * @param null $optinal_cloumn_name => Name of additional column to filter by.
     * @param null $optional_value => Additional value to check for in said column.
     * @return array
     */
    public function getList($listId, $optinal_cloumn_name = null, $optional_value = null, $translated=null)
    {
        $sql="SELECT * FROM " . $this->tableGateway->table . " WHERE list_id = ? AND activity = 1 ";
        $sqlBindArray = array($listId);

        if(isset($optinal_cloumn_name) && isset($optional_value))
        {
            $sql .= " AND " . $optinal_cloumn_name . " =?";
            array_push($sqlBindArray, $optional_value);
        }

        $sql .= " ORDER BY seq";

        $statement = $this->tableGateway->adapter->createStatement($sql, $sqlBindArray);
        $return = $statement->execute();

        $results = array();
        foreach ($return as $row) {
            if($translated!==false) {
                $row['title'] = xlt($row['title']);
            }
            $results[$row['option_id']] = $row;
        }

        return $results;

    }

    public function getListNormalized($listId, $optinal_cloumn_name = null, $optional_value = null,$prefix=null,$translated=null)
    {
        $sql="SELECT * FROM " . $this->tableGateway->table . " WHERE list_id = ? AND activity = 1 ";
        $sqlBindArray = array($listId);

        if(isset($optinal_cloumn_name) && isset($optional_value))
        {
            $sql .= " AND " . $optinal_cloumn_name . " =?";
            array_push($sqlBindArray, $optional_value);
        }

        $statement = $this->tableGateway->adapter->createStatement($sql, $sqlBindArray);
        $return = $statement->execute();

        $results = array();
        foreach ($return as $row) {
            if(!is_null($prefix)){
                if($translated!==false){
                $results[$prefix.$row['option_id']] =xlt($row['title']);
                }else{
                    $results[$prefix.$row['option_id']] =$row['title'];
                }
            }
            else{
                if($translated!==false){
                    $results[$row['option_id']] =xlt($row['title']);
                }else{
                    $results[$row['option_id']] =$row['title'];
                }


            }

        }

        return $results;

    }

    /**
     * @param $listId
     * @param bool $sortAlphaBeta
     * @param array $params - array of another parameter for filter results
     * @return array - array('option_id' => value, ....)
     */
    public function getListForViewForm($listId, $sortAlphaBeta = false, $params = array(), $onlyActive = true, $orderBySeq = false)
    {
        if(!$sortAlphaBeta) {
            $sql="SELECT option_id, title FROM " . $this->tableGateway->table . " WHERE list_id = ?";
            if($onlyActive)
                $sql .= " AND activity = 1 ";
            $sqlBindArray = array($listId);
            foreach ($params as $column => $value){
                $sql .= "AND $column = ? ";
                $sqlBindArray[] = $value;
            }
            if ($orderBySeq) {
                $sql .= " ORDER BY seq";
            }

        } else {
            $lang_id = empty($_SESSION['language_choice']) ? '1' : $_SESSION['language_choice'];
            $sql = "SELECT lo.option_id,
                IF(LENGTH(ld.definition),ld.definition,lo.title) AS title
                FROM " . $this->tableGateway->table . " AS lo
                LEFT JOIN lang_constants AS lc ON lc.constant_name = lo.title
                LEFT JOIN lang_definitions AS ld ON ld.cons_id = lc.cons_id AND
                ld.lang_id = ?
                WHERE lo.list_id = ?";
            if($onlyActive)
                $sql .= " AND lo.activity = 1 ";
            $sqlBindArray = array($lang_id, $listId);
            foreach ($params as $column => $value){
                $sql .= "AND lo.${column} = ? ";
                $sqlBindArray[] = $value;
            }

            $order_sql = Array("IF(LENGTH(ld.definition),ld.definition,lo.title)", "lo.seq");
            if($orderBySeq){
                $order_sql = array_reverse($order_sql);
            }

            $sql .= "ORDER BY ".implode(", ", $order_sql);

        }

        $statement = $this->tableGateway->adapter->createStatement($sql, $sqlBindArray);
        $return = $statement->execute();

        $results = array();
        foreach ($return as $row) {
            $results[$row['option_id']] = !$sortAlphaBeta ? xlt($row['title']) : $row['title'];
        }

        return $results;
    }

    public function getSpecificTitle($listId, $optionId)
    {
        $rowset = $this->tableGateway->select(array('list_id' => $listId, 'option_id' => $optionId));
        $row = $rowset->current();
        if (!$row) {
            //throw new \Exception("Could not find row");
            return null;
        }
        return $row->title;
    }

    public function getSpecificRow($listId, $optionId)
    {
        $rowset = $this->tableGateway->select(array('list_id' => $listId, 'option_id' => $optionId));
        $row = $rowset->current();
        if (!$row) {
            //throw new \Exception("Could not find row");
            return null;
        }
        return (array) $row;
    }

    public function getAllList($listId,$orderBy = null,$typeOfOrder = null)
    {
        $rsArray=array();

        if($orderBy!==null) {
            $select = $this->tableGateway->getSql()->select();

            $where = new Where();
            $where->equalTo('list_id', $listId);
            $select->where($where);
            $order=$orderBy . " " . (!$typeOfOrder ? "ASC" : $typeOfOrder);
            $select->order($order);

            $select->columns(array('*'));

            $rs = $this->tableGateway->selectWith($select);

        }
        else{
            $rs = $this->tableGateway->select(array('list_id' => $listId));
        }
        foreach($rs as $r) {
            $record=(array)$r;
            $rsArray[$record['option_id']]=$record;
        }
        return $rsArray;
    }

    public function getTitles($listId, $optionIds)
    {
        $sql= "SELECT * FROM " . $this->tableGateway->table . " WHERE list_id = ? AND option_id in ({$optionIds})";
        $statement = $this->tableGateway->adapter->createStatement($sql, array($listId));
        $rs = $statement->execute();
        foreach($rs as $r) {
            $rsArray[$r['option_id']]=$r['title'];
        }
        return $rsArray;
    }

    public function getListForViewFormNoTranslation($listId,  $params = array(), $onlyActive = true, $orderBySeq = false)
    {
        if(!$sortAlphaBeta) {
            $sql="SELECT option_id, title FROM " . $this->tableGateway->table . " WHERE list_id = ?";
            if($onlyActive)
                $sql .= " AND activity = 1 ";
            $sqlBindArray = array($listId);
            foreach ($params as $column => $value){
                $sql .= "AND $column = ? ";
                $sqlBindArray[] = $value;
            }
            if ($orderBySeq) {
                $sql .= " ORDER BY seq";
            }

        } else {
            $lang_id = empty($_SESSION['language_choice']) ? '1' : $_SESSION['language_choice'];
            $sql = "SELECT lo.option_id,
                IF(LENGTH(ld.definition),ld.definition,lo.title) AS title
                FROM " . $this->tableGateway->table . " AS lo
                LEFT JOIN lang_constants AS lc ON lc.constant_name = lo.title
                LEFT JOIN lang_definitions AS ld ON ld.cons_id = lc.cons_id AND
                ld.lang_id = ?
                WHERE lo.list_id = ?";
            if($onlyActive)
                $sql .= " AND lo.activity = 1 ";
            $sqlBindArray = array($lang_id, $listId);
            foreach ($params as $column => $value){
                $sql .= "AND lo.${column} = ? ";
                $sqlBindArray[] = $value;
            }

            $order_sql = Array("IF(LENGTH(ld.definition),ld.definition,lo.title)", "lo.seq");
            if($orderBySeq){
                $order_sql = array_reverse($order_sql);
            }

            $sql .= "ORDER BY ".implode(", ", $order_sql);

        }

        $statement = $this->tableGateway->adapter->createStatement($sql, $sqlBindArray);
        $return = $statement->execute();

        $results = array();
        foreach ($return as $row) {
            $results[$row['option_id']] =  $row['title'] ;
        }

        return $results;
    }

    public function insert(Lists $listObj)
    {
        return $this->tableGateway->insert((get_object_vars($listObj))) ? true : false;
    }

    public function update($set, $where)
    {
        return $this->tableGateway->update($set, $where) ? true : false;
    }

    public function delete($data) {
        return $this->tableGateway->delete($data) ? true : false;
    }

}

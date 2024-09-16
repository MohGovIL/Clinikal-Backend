<?php

/**
 * Date: 02/06/2020
 *  @author Dror Golan <drorgo@matrix.co.il>
 */


namespace ClinikalAPI\Model;

use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Where;
use Laminas\Db\TableGateway\TableGateway;

class GetTemplatesServiceTable
{
    const LEFT_JOIN = "LEFT";
    const ALL_REASON_CODE = 'all_reasons';
    const ALL_REASON_CODE_STRING = 'All the reason codes';
    protected $tableGateway;
    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }
    private   $listArr = array(
        "option_id" => "option_id" ,
        "title" => "title" ,
        "seq"  => "seq",
    );
    public function getTemplatesForForm($form_id=null,$form_field=null,$service_type=null,$reason_code=null){

        if($form_id === null  || $form_field === null || $service_type ===null || $reason_code===null) //primary keys cannot be null
            return null;

        $rsArray = array();
        $select = $this->tableGateway->getSql()->select();
        $joinExp= new Expression("clinikal_templates_map.message_id = list.option_id AND list.list_id = 'clinikal_templates' ");
        $select->join(array ("list"=>"list_options") , $joinExp, $this->listArr, self::LEFT_JOIN);
        $where = new Where();
        $where->equalTo("active", 1);

        if(!is_null($service_type)){
            $where->equalTo("service_type",$service_type)->AND->
                    NEST->
                        in("reason_code",explode(",",$reason_code))->OR->
                        equalTo("reason_code",self::ALL_REASON_CODE)->
                    UNNEST->AND->
                    equalTo("form_field",$form_field)->AND->
                    equalTo("form_id",$form_id);
        }

        $select->where($where);
        $select->order(array('clinikal_templates_map.seq ASC'));
       // $debug = $select->getSqlString();
        //echo $debug;die;
        $rs = $this->tableGateway->selectWith($select);
        foreach ($rs as $r) {
            $rsArray[] = get_object_vars($r);
        }

        return $rsArray;
    }

    /**
     * @return array -   all of the facilities from facility table
     */
    public function fetchNormalizedData($langCode, $equalFilters = null)
    {
        $sql = "
        SELECT
            ctm.form_id as form_id,
            translateString(r.name, ?) as form,
            ctm.form_field as field_id,
            translateString(GetOptionTitle('clinikal_form_fields_templates', ctm.form_field),?) as field,
            ctm.service_type as service_type_id,
            translateString(GetOptionTitle('clinikal_service_types', ctm.service_type),?) as service_type,
            ctm.reason_code as reason_code_id,
            translateString(ifnull(GetOptionTitle('clinikal_reason_codes', ctm.reason_code),'" . self::ALL_REASON_CODE_STRING . "'),?) as reason_code,
            ctm.message_id as template_id,
            translateString(GetOptionTitle('clinikal_templates', ctm.message_id),?) as template,
            ctm.active as active
        FROM  clinikal_templates_map AS ctm
                  JOIN registry AS r ON ctm.form_id = r.directory
        ";
        $bindParams = [$langCode, $langCode, $langCode, $langCode, $langCode];

        $where = [];
        foreach ($equalFilters as $column => $value) {
            $where[] = " ctm.$column = ? ";
            $bindParams[] = $value;
        }
        if (!empty($equalFilters)) {
            $sql .= " WHERE " . implode('AND', $where);
        }

        $sql .= " ORDER BY form, field, service_type, reason_code, seq";

        $statement = $this->tableGateway->adapter->createStatement($sql, $bindParams);
        $return = $statement->execute();
        $results = array();
        foreach ($return as $row) {
            $results[] = $row;
        }
        return $results;
    }

    public function get($equalFilters)
    {
        $select = $this->tableGateway->getSql()->select();
        $where = new Where();
        foreach ($equalFilters as $column => $value) {
            $where->equalTo($column, $value);
        }
        $select->where($where);
        //$debug = $select->getSqlString();
        //echo $debug;die;
        $rs = $this->tableGateway->selectWith($select);
        $rsArray = [];
        foreach ($rs as $r) {
            $rsArray[] = get_object_vars($r);
        }
        return $rsArray;
    }

    public function insert($data) {
        return $this->tableGateway->insert($data) ? true : false;
    }

    public function delete($data) {
        return $this->tableGateway->delete($data) ? true : false;
    }

    public function update($set, $where)
    {
        return $this->tableGateway->update($set, $where) ? true : false;
    }
}

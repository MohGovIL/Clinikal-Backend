<?php


namespace GenericTools\Model;

use Laminas\Db\TableGateway\TableGateway;

class ValueSetsTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /*
     * Get value set by the value set id
     */
    public function getValueSetById($id,$where = array())
    {
        $params= array();
        $params[]=$id;

        $type = $this->getValueSetType($id);
        if ($type !== 'Codes') {
            $sql = "SELECT
                    fvs.id AS vs_id,
                    fvs.title AS vs_title,
                    fvs.status AS vs_status,
                    fvs.language AS vs_lang,
                    fvss.system AS system,
                    lo.option_id AS code,
                    lo.title AS display
                FROM
                    fhir_value_sets AS fvs
                        JOIN
                    fhir_value_set_systems AS fvss ON fvs.id = fvss.vs_id
                        LEFT JOIN
                    fhir_value_set_codes AS fvsc ON fvss.id = fvsc.vss_id
                        JOIN list_options AS lo ON fvss.system = lo.list_id
                        AND ((fvss.type = 'All'
                        )
                        OR (fvss.type = 'Partial'
                        AND fvsc.code = lo.option_id
                        )
                        OR (fvss.type = 'Exclude'
                        AND fvsc.code != lo.option_id
                        )
                        OR (fvss.type = 'Filter'
                        AND fvss.filter = lo.notes
                        )
                        OR (fvss.type = 'Codes')
                        )
                WHERE
                    fvs.id = ? ";
        } else {
            $sql = "SELECT
                    fvs.id AS vs_id,
                    fvs.title AS vs_title,
                    fvs.status AS vs_status,
                    fvs.language AS vs_lang,
                    fvss.system AS system,
                    co.code AS code,
                    co.code_text AS display
                FROM
                    fhir_value_sets AS fvs
                        JOIN
                    fhir_value_set_systems AS fvss ON fvs.id = fvss.vs_id
                        LEFT JOIN
                    fhir_value_set_codes AS fvsc ON fvss.id = fvsc.vss_id
                        JOIN codes AS co ON fvss.system = co.code_type
                WHERE
                    fvs.id = ? ";
        }

        // put original sql in comment - cause very serious performance problems
       /* $sql=
            "
            SELECT fvs.id as vs_id, fvs.title as vs_title, fvs.status as vs_status, fvss.system as system, lo.a_option_id as code, lo.a_title as display
            FROM {$this->tableGateway->table} AS fvs
            JOIN fhir_value_set_systems AS fvss ON fvs.id = fvss.vs_id
            LEFT JOIN fhir_value_set_codes AS fvsc ON fvss.id = fvsc.vss_id
            JOIN
                (
                    select list_id as a_list_id, option_id as a_option_id, title as a_title, notes as a_notes, seq as a_seq, 'lists' as a_type
                    from list_options
                    union
                    select code_type as a_list_id, code as a_option_id, code_text as a_title, '' as a_notes, code as a_seq, 'codes' as a_type
                    from codes
                ) AS lo
                ON
                fvss.system = lo.a_list_id
                AND
                (
                    (fvss.type = 'All' AND lo.a_type = 'lists')
                    OR
                    (fvss.type = 'Partial' AND fvsc.code = lo.a_option_id AND lo.a_type = 'lists')
                    OR
                    (fvss.type = 'Exclude' AND fvsc.code != lo.a_option_id AND lo.a_type = 'lists')
                    OR
                    (fvss.type = 'Filter' AND fvss.filter = lo.a_notes AND lo.a_type = 'lists')
                    OR
                    (fvss.type = 'Codes' AND lo.a_type = 'codes')

                )
                ";
        $sql .= " WHERE fvs.id = ? ";*/

        foreach($where as $index => $conditionSet){

            if($index==='filter'){
                foreach($conditionSet as $pos => $condition) {
                    if (!empty($condition['value']) && !empty($condition['operator'])) {

                        if($condition['operator']==="LIKE"){
                            $condition['value']="%".$condition['value']."%";
                            $action = $condition['operator'] . " ?";
                        }else{
                            $action = $condition['operator'] . " ?";
                        }


                        $params[]=$condition['value'];
                        $params[]=$condition['value'];
                        if ($type !== 'Codes') {
                            $sql .= "AND ( lo.option_id " . $action . " OR lo.title " . $action . " ) ";
                        } else {
                            $sql .= "AND ( co.code " . $action . " OR co.code_text " . $action . " ) ";
                        }
                    }
                }
            }
        }
        if ($type !== 'Codes') {
            $sql .= " ORDER BY lo.list_id, lo.seq ";
        } else {
            $sql .= " ORDER BY co.code_type , co.code ";
        }

        $statement = $this->tableGateway->adapter->createStatement($sql, $params);
        $return = $statement->execute();

        $results = array();
        foreach ($return as $row) {
            $results[] = $row;
        }

        return $results;
    }

    public function getCodeTypeByValueSet($valueSet)
    {
        $params= array();
        $params[]=$valueSet;

        $sql  = "SELECT ct.ct_key FROM  code_types ct ";
        $sql .= "LEFT JOIN fhir_value_set_systems vss ON vss.system=ct.ct_id ";
        $sql .= "WHERE vss.vs_id = ? ";

        $statement = $this->tableGateway->adapter->createStatement($sql, $params);
        $return = $statement->execute();

        $results = array();
        foreach ($return as $row) {
            $results[] = $row;
        }
        if (!empty($results)){
            return $results[0]['ct_key'];
        }else{
            return null;
        }
     }

    public function getValueSetType($valueSetId)
    {
        $params= array();
        $params[]=$valueSetId;

        $sql  = "SELECT type FROM  fhir_value_set_systems WHERE vs_id = ?";

        $statement = $this->tableGateway->adapter->createStatement($sql, $params);
        $return = $statement->execute();

        $results = array();
        foreach ($return as $row) {
            $results[] = $row;
        }
        if (!empty($results)){
            return $results[0]['type'];
        }else{
            return null;
        }
    }

    public function getValueSetInfo($valueSetId)
    {
        $rsArray=array();
        $rs =$this->tableGateway->select(['id' => $valueSetId]);
        foreach($rs as $r) {
            $rsArray[]=get_object_vars($r);
        }
        return $rsArray;
    }

}

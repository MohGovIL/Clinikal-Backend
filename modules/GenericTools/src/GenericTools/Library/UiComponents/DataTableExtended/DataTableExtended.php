<?php

namespace GenericTools\Library\UiComponents\DataTableExtended;

class DataTableExtended
{

    private $columnsNames=array();
    private $columnsSize=array();
    private $tableId="";
    private $searchBox = true;
    private $lengthMenu = '[10,25,50,100]';
    private $orderingArrows = false;
    private $ordering = true;

    function __construct($table_id,$columns_names,$columns_size =array()) {

        $this->tableId=$table_id;
        $this->columnsNames=$columns_names;
        $this->columnsSize=$columns_size;

    }

    public function echoBaseHTML()
    {
        $columnsNames=$this->columnsNames;
        $columnsSize=$this->columnsSize;
        $tableId=$this->tableId;

        if( !isset($tableId) || $tableId=="" || !isset($columnsNames) ){
            exit();
        }

        $table_html_string='<table style="width:100%" id="'.$tableId.'" class="table">';
        $table_html_string.='<thead>';
        //$table_html_string.='<tr>';

        $colSizeFlag=(!empty($columnsSize) &&count($columnsNames)===count($columnsSize));

        foreach ($columnsNames as $key=>$name) {

            if($colSizeFlag){
                $width=$columnsSize[$key];
                $table_html_string.='<th width="'.$width.'%" >'.$name.'</th>';
            }else{
                $table_html_string.='<th>'.$name.'</th>';
            }
        }
        //$table_html_string.='<tr>';
        $table_html_string.=' </thead>';
        $table_html_string.='</table>';

        echo $table_html_string;
    }


    public function LoadEssentialCssJs()
    {
        //$uiComponentsPath='/vendor/clinikal/clinikal-backend/modules/GenericTools/src/GenericTools/Library/UiComponents';
        $uiPath='/'.$GLOBALS['webroot'].$GLOBALS['baseModDir'].$GLOBALS['zendModDir'].'/public';
        $header_html='';
        $header_html.='<link href="'.$uiPath.'/css/generictools/datatable/jquery.dataTables.min.css" media="screen" rel="stylesheet" type="text/css">';
        $header_html.='<link href="'.$uiPath.'/css/generictools/datatable/dataTablesExtend.css" media="screen" rel="stylesheet" type="text/css">';

        $header_html.='<script type="text/javascript" src="'.$uiPath.'/js/generictools/datatable/datatables.min.js"></script>';

        $header_html.='<script type="text/javascript" src="'.$uiPath.'/js/generictools/datatable/dataTables.bootstrap.min.js"></script>';
        $header_html.='<script type="text/javascript" src="'.$uiPath.'/js/generictools/datatable/dataTables.buttons.min.js"></script>';
        $header_html.='<script type="text/javascript" src="'.$uiPath.'/js/generictools/datatable/buttons.print.min.js"></script>';
        $header_html.='<script type="text/javascript" src="'.$uiPath.'/js/generictools/datatable/buttons.html5.min.js"></script>';

        $header_html.='<script type="text/javascript" src="'.$uiPath.'/js/generictools/datatable/dataTablesExtend.js"></script>';

        //$header_html.='<script type="text/javascript" src="'.$GLOBALS['webroot'].'/interface/modules/zend_modules/public/js/bootstrap.min.js"></script>';
        echo $header_html;
    }


    public function getJsonPageAction()
    {
        $offset=$_POST['start'];
        $limit=$_POST['length'];
        $table_name=$_POST['table_name'];
        $filter="";
        if (!empty($_POST['filter'])) {
            $filter=$_POST['filter'];
        }


        $number_of_rows="".$this->getCodesTable()->countList($table_name,$filter);

        if (isset($table_name)) {
            $list_elements= $this->createList($offset,$limit,$table_name,$filter);
            $data=array(
                "recordsTotal"=> $number_of_rows,
                "recordsFiltered"=> $number_of_rows,
                "data"=>$list_elements
            );

            echo json_encode($data);
        }
        exit();
    }

    /**
     * hide/show ui element, must be called before initDataTable()
     * @param null $searchBox (default - true)
     * @param null $lengthMenu (default - '[10,25,50,100])
     * @param null $orderingArrows (default - false) - hide/show ordering arrows
     * @param null $ordering (default - true) - enable/disable
     */
    public function setUiSettings($searchBox = null, $lengthMenu = null, $orderingArrows = null,$ordering= null)
    {
        if(!is_null($searchBox))$this->searchBox = $searchBox;
        if(!is_null($lengthMenu))$this->lengthMenu = $lengthMenu;
        if(!is_null($orderingArrows))$this->lengthMenu = $orderingArrows;
        if(!is_null($ordering))$this->ordering = $ordering;
    }

    /**
     * @param $tableId
     * @param array $data
     * @param null $dataAjaxUrl
     * @param bool $ajaxPagination
     * @param string $function
     * @param array $ordering - key- column index(0..), value- [asc/desc], Example - array('5'=>'desc', '3'=>'asc')
     */
    public function initDataTable($tableId, array $data = array(), $dataAjaxUrl = null, $ajaxPagination = false ,$function="", array $ordering = array() )
    {
        $scriptOutput = "var custom_table = $('#{$tableId}').dataTable({\n";
        $scriptOutput .= "searching:" ;
        $scriptOutput .= $this->searchBox ? 'true' : 'false';
        $scriptOutput .= ",\n";
        $scriptOutput .= "paging:" ;
        $scriptOutput .= $this->ordering ? 'true' : 'false';
        $scriptOutput .= ",\n";
        $scriptOutput .= $this->lengthMenu ? "lengthMenu:$this->lengthMenu" : 'bLengthChange : false';
        $scriptOutput .= ",\n";
        if (!empty($ordering)){
            $scriptOutput .= "order:[\n";
            foreach ($ordering as $column => $orderType){
                $scriptOutput .= "['$column','$orderType'],\n";
            }
            $scriptOutput .= "],\n";
        }else{
            $scriptOutput .= "ordering:false,\n";
        }
        if (!empty($data)) {
            $scriptOutput .= "data:";
            $scriptOutput .= json_encode($data);
            $scriptOutput .= ",\n";
            }

            $scriptOutput .= "fnDrawCallback:";
            $scriptOutput .= "function(oSettings) {  ".$function." }";
            $scriptOutput .= ",\n";

        if (!empty($dataAjaxUrl)){
            $scriptOutput .= "ajax:{url:'". $dataAjaxUrl ."',dataSrc:''}";
            $scriptOutput .= ",\n";
        }

        $scriptOutput .= "columnDefs: [{render: function (data, type, full, meta) {  if (typeof customCellBuilder === 'function') {return customCellBuilder(data, type, full, meta) } else{ return data}}, targets: '_all' }]";
        $scriptOutput .= ",\n";

        echo $scriptOutput;
    }

    public function closeDataTable()
    {
        echo "\n})\n";
        if(!$this->orderingArrows){
            echo "$('#{$this->tableId}').addClass('hide-ordering');";
        }
    }


    private function createList($offset,$limit,$table_name,$filter)
    {
        $rows = $this->getCodesTable()->fetchPartialList($offset,$limit,$table_name,$filter);

        $array = array();
        $i = 0;
        if ($rows) {
            foreach ($rows as $row) {
                $array[$i]['name'] = $row['name'];
                $array[$i]['code'] = $row['code'];
                $i++;
            }
        }

        return $array;

    }


}

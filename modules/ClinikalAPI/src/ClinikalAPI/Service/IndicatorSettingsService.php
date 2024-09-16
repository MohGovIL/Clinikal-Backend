<?php

/**
 * Date: 01/07/2020
 * @author  Dror Golan <drorgo@matrix.co.il>
 */

namespace ClinikalAPI\Service;


use \GenericTools\Model\ListsTable;


trait IndicatorSettingsService
{
    public function getIndicatorSettings($indicator)
    {

        $indicators=array();
        $indicators['constant'];
        $indicators['variant'];
        $listsTable=$this->container->get(ListsTable::class);
        $lionicListTable =  $listsTable->getAllList($indicator,'seq','ASC');
        foreach($lionicListTable as $key=>$val){
            $searchParam = $val['option_id'];
            $arrTemp=[];
            switch($searchParam){
                case '20564-1'://"Oxygen saturation in Blood":
                case '69000-8'://"Heart rate --sitting":
                case '72514-3'://"Pain severity - 0-10 verbal numeric rating [Score] - Reported":
                case '8310-5'://"Body temperature":
                case '8462-4'://"Diastolic blood pressure":
                case '8480-6'://"Systolic blood pressure":
                case '74774-1'://"Glucose [Mass/volume] in Serum, Plasma or Blood":
                case '9303-9'://"Respiratory rate --resting":

                        $arrTemp['description'] =  $val['title'];
                        $arrTemp['code'] = $key;
                        $arrTemp['unit'] = $val['subtype'];
                    if($val['notes']!=='') {
                        $notes = json_decode($val['notes']);
                        $arrTemp['label'] =  $notes->label;
                        if (isset($notes->mask)) {
                            $arrTemp['mask'] = $notes->mask;
                        }
                        if (isset($notes->placeholder)) {
                            $arrTemp['placeholder'] = $notes->placeholder;
                        }
                        if (isset($notes->symbol)) {
                            $arrTemp['symbol'] = $notes->symbol;
                        }
                        $indicators['variant'][$key]=$arrTemp;
                    }else{
                        $arrTemp['label'] = $val['mapping'];
                        $indicators['variant'][$key]=$arrTemp;
                    }
                break;
                case '8308-9'://"Body height --standing":
                case '8335-2'://"Body weight Estimated":

                $arrTemp['description'] =  $val['title'];
                $arrTemp['code'] = $key;
                $arrTemp['unit'] = $val['subtype'];
                if($val['notes']!=='') {
                    $notes = json_decode($val['notes']);
                    $arrTemp['label'] =  $notes->label;
                    if (isset($notes->mask)) {
                        $arrTemp['mask'] = $notes->mask;
                    }
                    if (isset($notes->placeholder)) {
                        $arrTemp['placeholder'] = $notes->placeholder;
                    }
                    if (isset($notes->symbol)) {
                        $arrTemp['symbol'] = $notes->symbol;
                    }
                    $indicators['constant'][$key]=$arrTemp;
                }else{
                    $arrTemp['label'] = $val['mapping'];
                    $indicators['constant'][$key]=$arrTemp;
                }
                break;
            }

        }
        return $indicators;
    }
}



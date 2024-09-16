<?php
/**
 * Created by PhpStorm.
 * User: amiel
 * Date: 04/09/18
 * Time: 08:53
 */
namespace GenericTools\Library\UiComponents\Header;

class ClinikalHeaders
{
    public function singleTitle()
    {

    }

    public function titleWithButtons($title, array $buttons = array(),array $leftButtons = array())
    {
        $html = $this->openTags();
        $html .= '<li role="presentation" class="active"><p style="margin-top: 18px;" class="oe-bold-black fa-lg ">' . xlt($title) . '</p></li>';
        if (!empty($buttons)) {
        $html .= '<li class="center-buttons">';
                foreach ($buttons as $button) {
                    $html .= '<button type="button" id ="' . text($button['id']) . '" class="btn btn-info" style="margin-left:10px">' . xlt($button['title']) . '</button>';
                }
            $html .= '</li>';
        }
        if (!empty($leftButtons)) {
            $html .= '<li class="left-buttons">';
            foreach ($leftButtons as $button) {
                $html .= '<button type="button" id ="' . text($button['id']) . '" class="btn btn-info" >' . xlt($button['title']) . '</button>';
            }
            $html .= '</li>';
        }
        $html .= $this->closeTags();
        return $html;
    }

    public function tabsMenu()
    {

    }

    private function openTags()
    {
        $html = '<div id="header" class="row mb-3">';
        $html .= '   <nav class="navbar navbar-light navbar-expand-lg list-group-item-primary w-100 ">';
        $html .= '        <div class="container-fluid">';
        $html .= '            <ul id="header-links" class="nav navbar-nav col-md-12">';
        return $html;
    }

    private function closeTags()
    {
        $html = '            </ul>';
        $html .= '       </div>';
        $html .= '    </nav>';
        $html .= '</div>';
        return $html;
    }


}

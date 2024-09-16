<?php
/**
 * Created by PhpStorm.
 * User: amiel
 * Date: 13/05/18
 * Time: 12:24
 */
namespace GenericTools\Library\UiComponents\ValidateJS;

class ValidateJsFunctions
{
    static function append(){


        /* js function for remove required constraint from list
        *  @param - array or string of element ids
        */
        $output = "\nfunction removeRequiredConstraint(elementId, constraints) {\n";
        $output .= "\tif (typeof elementId === 'string') {\n";
        $output .= "\t\tdelete constraints[elementId].presence;\n";
        $output .= "\t\t$('#'+elementId).removeClass('error-border');$('#error_' + elementId).text('');\n";
        $output .= "\t} else {\n";
        $output .= "\t\tfor(var i = 0; i < elementId.length ; i++){\n";
        $output .= "\t\t\tdelete constraints[elementId[i]].presence;\n";
        $output .= "\t\t\t$('#'+elementId[i]).removeClass('error-border');$('#error_' + elementId[i]).text('');\n";
        $output .= "\t\t}\n";
        $output .= "\t}\n";
        $output .= "}\n\n";

        /* js function for add required constraint to list
        *  @param - array or string of element ids
        */
        $output .= "\nfunction addRequiredConstraint(elementId, constraints) {\n";
        $output .= "\tif (typeof elementId === 'string') {\n";
        $output .= "\t\tif (typeof constraints[elementId] === 'undefined')constraints[elementId] = {};\n";
        $output .= "\t\tconstraints[elementId].presence = {message: '" . xls('Value is required') . "'};\n";
        $output .= "\t} else {\n";
        $output .= "\t\tfor(var i = 0; i < elementId.length ; i++){\n";
        $output .= "\t\t\tif (typeof constraints[elementId[i]] === 'undefined')constraints[elementId][i] = {};\n";
        $output .= "\t\t\tconstraints[elementId[i]].presence = {message: '" . xls('Value is required') . "'};\n";
        $output .= "\t\t}\n";
        $output .= "\t}\n";
        $output .= "}\n\n";

        $output .= "\nfunction removeAllErrors(){\n";
        $output .= "\t$('select, input').removeClass('error-border');\n";
        $output .= "\t$('.error-message').remove();\n";
        $output .= "}\n\n";

        /* todo - show error if radio button is empty - ui broken with the validatejs functions
        *  @param - array or string of element ids
        */
        $output .= "\nfunction radioButtonRequired(elementId) {\n";
        $output .= "}\n\n";

        echo $output;
    }
}

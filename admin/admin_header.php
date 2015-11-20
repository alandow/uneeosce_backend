<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(__FILE__) . "/../backend/config.inc.php");
require_once(dirname(__FILE__) . "/../backend/lib/StringLib.php");
require_once(dirname(__FILE__) . "/../backend/lib/authlib.php");
require_once(dirname(__FILE__) . "/../backend/lib/EnumLib.php");

$stringlib = new StringLib();

// check token
$token = $_COOKIE['uneeoscetoken'];
$authlib = new authlib();
$authresult = '';

$loggedinuserdata = new SimpleXMLElement($authlib->getDetailsByToken($token));

if (strlen($loggedinuserdata->error) > 1) {
  //  print_r($loggedinuserdata);
    //$headerStr = $loggedinuserdata->name;
    header("Location: {$CFG->wwwroot}{$CFG->basedir}index.php");
} else {
    $headerStr= $CFG->istrainingsite?" <style>
        .ui-widget-header{
            background: #C24641;
        }
    </style>":"";
    $headerStr .= "
        <div class='ui-widget-header' style='height: 50px; width: 100%;  text-align: center'>
        <div style='position:absolute; right:10px; top:10px'></div>
        <span style='width: 100%; text-align: center'>";
    if ($CFG->wwwroot == "http://srm-itd01/") {
        $headerStr.="DEV: {$CFG->sysname} admin".($CFG->istrainingsite?' (TRAINING)':'')."</span><br/>";
    }else{
         $headerStr.="{$CFG->sysname} admin".($CFG->istrainingsite?' (TRAINING)':'')."</span><br/>";
    }
    $headerStr.="<span id='user_feedback' style='width: 80%'>";

    $headerStr.= $loggedinuserdata->name . "</span></div>";
    $headerStr.="<script>
        function showHelp(helpStr) {
    var helpdialog = \"<div></div>\";
    $(helpdialog).dialog({
        buttons: {
            \"OK\": function() {
                $(this).dialog(\"close\");
            }

        },
        position: ['center', 'top+50'],
        open: function(event, ui) {
            $(this).parent().find('.ui-dialog-titlebar').html(\"<img src='{$CFG->wwwroot}{$CFG->basedir}icons/gtk-dialog-question48.png' style='vertical-align:middle'>Help\");
        }
    }).html(\"<fieldset><div>\" + helpStr + \"</div></fielset>\");
}</script>";
}
?>

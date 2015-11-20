<?php

require_once(dirname(__FILE__) . "/../backend/config.inc.php");
require_once(dirname(__FILE__) . "/../backend/lib/ReportsLib.php");
include dirname(__FILE__) . "/../backend/lib/authlib.php";
include dirname(__FILE__) . "/../backend/lib/EnumLib.php";
include dirname(__FILE__) . "/../backend/lib/StringLib.php";
//include dirname(__FILE__) . "/../backend/lib/ReportsLib.php";
$stringlib = new StringLib();
$enumLib = new EnumLib();
$reportLib = new ReportsLib();


// check token
if (isset($_COOKIE['uneeoscetoken'])) {
    $token = $_COOKIE['uneeoscetoken'];
    $authlib = new authlib();
    $reportLib = new ReportsLib();
    $authresult = '';

    $loggedinuserdata = new SimpleXMLElement($authlib->getDetailsByToken($token));

    if (strlen($loggedinuserdata->error) > 1) {
        //$headerStr = $loggedinuserdata->name;
        header("Location: ../index.php");
    } else {

        if (strlen($loggedinuserdata->error) > 1) {
            //$headerStr = $loggedinuserdata->name;
            header("Location: ../index.php");
        } else {
            $headerStr = $CFG->istrainingsite ? " <style>
        .ui-widget-header{
            background: #C24641;
        }
    </style>" : "";
        }

        $headerStr .= "<div class='ui-widget-header' style='height: 50px; width: 100%;  text-align: center'>
        <div style='position:absolute; right:10px; top:10px'><a href='" . ("{$CFG->wwwroot}{$CFG->basedir}") . "index.php'>Home</a></div>
        <span style='width: 100%; text-align: center'>";
        if ($CFG->wwwroot == "http://srm-itd01/") {
            $headerStr.="DEV: {$CFG->sysname} admin" . ($CFG->istrainingsite ? ' (TRAINING)' : '') . "</span><br/>";
        } else {
            $headerStr.="{$CFG->sysname} admin" . ($CFG->istrainingsite ? ' (TRAINING)' : '') . "</span><br/>";
        }
        $headerStr.="<span id='user_feedback' style='width: 80%'>";

        $headerStr.= $loggedinuserdata->name . "</span></div>";
    }
} else {
    header("Location: ../index.php");
}
?>

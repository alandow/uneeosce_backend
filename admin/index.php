<!DOCTYPE html>

<?php
include 'admin_header.php';
$actionStr = "<br/>
<div style='display:table'>
    <div style='display:table-row'>
        <div style='display:table-cell'><a href='users'>{$stringlib->get_string('system_users_setup')}</a>
        </div>
        <div style='display:table-cell'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='showHelp(\"{$stringlib->get_string('system_users_setup_help')}\"); return false;'/>
        </div>
    </div>
    <div style='display:table-row'>
        <div style='display:table-cell'><a href='criteriatypes/'>{$stringlib->get_string('string_criteria_types_label')}</a>
        </div>
        <div style='display:table-cell'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='showHelp(\"{$stringlib->get_string('criteria_types_help')}\"); return false;'/>
        </div>
     </div>
    <div style='display:table-row'>
        <div style='display:table-cell'><a href='strings/'>{$stringlib->get_string('string_management_form_label')}</a>
        </div>
        <div style='display:table-cell'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='showHelp(\"{$stringlib->get_string('system_labels_help')}\"); return false;'/>
        </div>
     </div>
      <div style='display:table-row'>
        <div style='display:table-cell'><a href='lookups/'>{$stringlib->get_string('system_lookups_form_label')}</a>
        </div>
        <div style='display:table-cell'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='showHelp(\"{$stringlib->get_string('system_lookups_help')}\"); return false;'/>
        </div>
     </div>
     
 </div>   ";
?>
<!--
This page handles system user management
-->



<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title><?php print($stringlib->get_string('system_administration_label')); ?></title>
        <link type="text/css" href="../css/une-theme/jquery-ui-1.10.4.custom.min.css" rel="stylesheet" />
        <link type="text/css" href="../css/eOSCENonMobile.css" rel="stylesheet" />

        <script src="../js/jquery-1.9.1.js"></script>
        <script src="../js/jquery.blockUI.js"></script>
        <script type="text/javascript" src="../js/jquery-ui-1.10.1.custom.min.js"></script>
        <script src="../js/utils.js"></script>

    </head>


    <body>
        <?php
        print($headerStr);
        print"<div style='width:100%; border:solid 1px #cccccc; background-color:#e0e0e0'>";
        print("<a href='{$CFG->wwwroot}{$CFG->basedir}'>Home</a>->");
        $currentpath = str_replace("/$CFG->basedir", '', $_SERVER['PHP_SELF']);
        //print($currentpath);
        $patharr = explode('/', $currentpath);
        $linkStr = '';
        $displayStr = '';

        for ($i = 0; $i < count($patharr) - 1; $i++) {
            print(($i < count($patharr) - 2) ? "<a href='" : "");
            // print "$patharr[$i]";            
            $linkStr.="$patharr[$i]/";
            print ($i < count($patharr) - 2) ? "{$CFG->wwwroot}{$CFG->basedir}$linkStr'>" : "";
            print (file_get_contents("{$CFG->wwwroot}{$CFG->basedir}$linkStr" . "label.txt") ? $stringlib->get_string(file_get_contents("{$CFG->wwwroot}{$CFG->basedir}$linkStr" . "label.txt")) : $patharr[$i]) . (($i < count($patharr) - 2) ? "</a>" : "") . (($i < count($patharr) - 2) ? "->" : '');
        }


        print"</div>";

        print("$actionStr");
        ?>

    </body>
</html>

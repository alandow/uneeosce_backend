<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<?php
include '../backend/lib/authlib.php';
include '../backend/lib/StringLib.php';
include '../backend/lib/EnumLib.php';
require_once('../backend/config.inc.php');

$enumlib = new EnumLib();
$authlib = new authlib();
$stringlib = new StringLib();

if (isset($_REQUEST['logout'])) {
    setcookie('uneeoscetoken', "", -3600);
//do redirect in Java?
    print("<script>window.location.assign('{$CFG->wwwroot}{$CFG->basedir}login.php');</script>");
    exit();
}

if (isset($_COOKIE['uneeoscetoken'])) {

    $token = $_COOKIE['uneeoscetoken'];
    $authresult = '';
    $stringlib = new StringLib();

    $loggedinuserdata = new SimpleXMLElement($authlib->getDetailsByToken($token));

    if (strlen($loggedinuserdata->error) > 1) {
        setcookie('uneeoscetoken', "", -3600);
        header("Location: {$CFG->wwwroot}{$CFG->basedir}login.php");
        exit();
    }
} else {

    header("Location: {$CFG->wwwroot}{$CFG->basedir}login.php");
    exit();
}

// breadcrumbs
$breadcrumbStr = "<a href='{$CFG->wwwroot}{$CFG->basedir}'>Home</a>>";
$currentpath = str_replace("/$CFG->basedir", '', $_SERVER['PHP_SELF']);
//print($currentpath);
$patharr = explode('/', $currentpath);
$linkStr = '';
$displayStr = '';

for ($i = 0; $i < count($patharr) - 1; $i++) {
    $breadcrumbStr.=(($i < count($patharr) - 2) ? "<a href='" : "");
    $linkStr.="$patharr[$i]/";
    $breadcrumbStr.= ($i < count($patharr) - 2) ? "{$CFG->wwwroot}{$CFG->basedir}$linkStr'>" : "";
    $breadcrumbStr.= (file_get_contents("{$CFG->wwwroot}{$CFG->basedir}$linkStr" . "label.txt") ? $stringlib->get_string(file_get_contents("{$CFG->wwwroot}{$CFG->basedir}$linkStr" . "label.txt")) : $patharr[$i]) . (($i < count($patharr) - 2) ? "</a>" : "") . (($i < count($patharr) - 2) ? ">" : '');
}
//print('path array is:' . $patharr[$i - 1]);
// the navigation menu
$menuStr = "";
//$displayAdminStr = false;
$adminStr = "";

if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_assessments) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_reports)) {
    $menuStr .= "<li class='showing'><label class='tree-toggle nav-header'>Examination Management</label><ul class='nav nav-list tree'>";
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_assessments)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('participants_setup_help')}\"); return false;'/></div>"
                . "<div style='float:left;display:table-cell; vertical-align:middle'>";
        $menuStr .=(($patharr[$i - 1] == 'sessions') ? "<span class='currentmenulocation'>{$stringlib->get_string('eosce_setup')}</span>" : "<a href='javascript:window.location.assign(\"{$CFG->wwwroot}{$CFG->basedir}sessions/\")'>{$stringlib->get_string('eosce_setup')}</a>");
        $menuStr .="</div></div></li>";
    }
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_reports)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('reports_help')}\"); return false;'/></div>"
                . "<div style='float:left;display:table-cell; vertical-align:middle'>";
        $menuStr .=(($patharr[$i - 1] == 'reports') ? "<span class='currentmenulocation'>{$stringlib->get_string('reports_index_label')}</span>" : "<a href='javascript:window.location.assign(\"{$CFG->wwwroot}{$CFG->basedir}reports/\")'>{$stringlib->get_string('reports_index_label')}</a>");
    }
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_assessments)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('osce_archive_help')}\"); return false;'/></div>"
                . "<div style='float:left;display:table-cell; vertical-align:middle'>";
        $menuStr .=(($patharr[$i - 1] == 'archive') ? "<span class='currentmenulocation'>{$stringlib->get_string('eosce_archive')}</span>" : "<a href='javascript:window.location.assign(\"{$CFG->wwwroot}{$CFG->basedir}archive/\")'>{$stringlib->get_string('eosce_archive')}</a>");
        $menuStr .="</div></div></li>";
    }
    $menuStr .="</ul><li>";
}
$from = isset($_REQUEST['from']) ? $_REQUEST['from'] : 0;
$datefrom = isset($_REQUEST['datefrom']) ? $_REQUEST['datefrom'] : 0;
$dateto = isset($_REQUEST['dateto']) ? $_REQUEST['dateto'] : 0;
$increment = 20;


$examdata = simplexml_load_string($enumlib->getExamInstances($increment, $from, 'true'));

$listTableStr = "<table><tr><th>{$stringlib->get_string('osce_sessions')}</th>";

$listTableStr .= "   <th>{$stringlib->get_string('osce_session_unit')}</th>
            <th>{$stringlib->get_string('osce_session_owner')}</th>
                <th>{$stringlib->get_string('osce_session_date')}</th>";
// $listTableStr .= "<th>{$stringlib->get_string('osce_session_created_by')}</th>
$listTableStr .= "<th>Clone <input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/dialog-question.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='showHelp(\"{$stringlib->get_string('archive_clone_help_string')}\"); return false;'/></th>" . ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments) ? "<th>Delete</th>" : "") . "</tr>";
foreach ($examdata->instance as $instance) {
    $listTableStr .= "<tr class='" . (($instance->active == 'true') ? 'active' : 'inactive') . "'>";
    $listTableStr .= "<td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/dialog-information.png' BORDER='0' style='vertical-align: text-bottom;' onclick='show_information(\"{$instance->description}\"); return false;'/>" . ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments) ? "<a href='view/index.php?id={$instance->id}'>{$instance->name}</a>" : $instance->name) . "</td>";
    $listTableStr .= "<td>{$instance->unit}</td>
        <td>{$instance->owner}</td>
        <td>{$instance->exam_starttimestamp}</td>";
//        <td>{$instance->created_by}</td>
    $listTableStr .= "<td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-copy.png' BORDER='0' style='vertical-align: text-bottom;' onclick='clone_assessment({$instance->id}, \"{$instance->name}\"); return false;'/></td>";
    $listTableStr .=($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments) ? "<td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-cancel.png' BORDER='0' style='vertical-align: text-bottom;' onclick='delete_instance({$instance->id}); return false;'/></td>" : "") . "</tr>";
}
$listTableStr .= "</table>";
    $buttonStr = '<div id="nav_div" style="float: left">';
    $buttonStr .= '<button id="first" style="float: left" ' . (($from == 0) ? 'disabled="disabled"' : '') . '  onclick="goFirst()">First</button>';
    $buttonStr .= '<button id="prev" onclick="goPrev()" style="float: left" ' . (($from == 0) ? 'disabled="disabled"' : '') . '>Prev ' . $increment . '</button>';
    $buttonStr .= '<button id="next" onclick="goNext()" style="float: left" ' . ((($from + $increment) > ($examdata->count)) ? 'disabled="disabled"' : '') . '>Next ' . $increment . '</button>';
    $buttonStr .= '<button id="last" style="float: left" ' . ((($from + $increment) > ($examdata->count)) ? 'disabled="disabled"' : '') . ' onclick="goLast()">Last</button><br/></div>';
    $buttonStr .="<div> {$stringlib->get_string('osce_sessions_count')}: {$examdata->count}</div>";
   
$usersXML = simplexml_load_string($enumlib->getUsers(''));

$usersStr = '';
foreach ($usersXML->user as $value) {
    $usersStr.="<option value='{$value->id}'>{$value->name}({$value->username})</option>";
}

?>
<!--
This page handles assessment reports
-->


?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=10"/>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
        <link type="text/css" href="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>css/jquery-ui.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>css/eOSCE.css" />
        <link type="text/css" href="<?php print($CFG->wwwroot . $CFG->basedir); ?>/css/select2.css" rel="stylesheet" />
        <link href="<?php print($CFG->wwwroot . $CFG->basedir); ?>/css/skins/all.css" rel="stylesheet">
        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>js/jquery-2.0.3.min.js"></script>
        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>js/jquery-ui.min.js"></script>
        <script src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/utils.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/select2.js"></script>
        <title></title>
        <script>

    var serviceurl = '<?php print($CFG->serviceURL); ?>';

            var currentSelectedInstanceID = 0;

// page code initiation
            $(document).ready(function() {
                // set up the waiting feedback dialog
                $("#waiting_dialog").dialog({autoOpen: false, modal: true,
                    open: function(event, ui) {
                        $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                        $(".ui-dialog-titlebar", $(this).parent()).hide();
                        $(".ui-resizable-handle", $(this).parent()).hide();
                    }
                });

                $("#help_dialog").dialog({
                    autoOpen: false, modal: true,
                    title: '<img src="../icons/dialog-question.png" />Help',
                    buttons: {
                        Ok: function() {
                            $(this).dialog("close");
                        }
                    }
                });

                $("#info_dialog").dialog({
                    autoOpen: false, modal: true,
                    title: '<img src="../icons/dialog-information.png" />Info',
                    buttons: {
                        Ok: function() {
                            $(this).dialog("close");
                        }
                    }
                });

                $("#clone_exam_dialog").dialog({
                    autoOpen: false, modal: true


                });
                // turn waiting feedback on
                //waitOn();


                // pretty icons on buttons
                $("#first").button({
                    icons: {
                        primary: "ui-icon-seek-first"
                    }
                });
                $("#prev").button({
                    icons: {
                        primary: "ui-icon-seek-prev"
                    }
                });
                $("#next").button({
                    icons: {
                        primary: "ui-icon-seek-next"
                    }
                });
                $("#last").button({
                    icons: {
                        primary: "ui-icon-seek-end"
                    }
                });

                $("select").select2();


                waitOff();
            });


            function clone_assessment(id, title) {
                $("#clone_exam_dialog").dialog({autoOpen: true, modal: true, title: 'Clone ' + title,
                    width: 600,
                    buttons: {
                        "<?php print($stringlib->get_string('clone_osce_session_btn_lbl')); ?> ": function() {
                            $(this).dialog("close");
                            clone_examfile_action(id);
                        },
                        Cancel: function() {
                            $(this).dialog("close");
                        }
                    }
                });
                validate_form('clone_exam_dialog', '<?php print($stringlib->get_string('clone_osce_session_btn_lbl')); ?>');
            }

            function clone_examfile_action(id) {
                waitOn();
                var dataObj = new FormData();
                // file
                dataObj.append('action', 'cloneexam');
                dataObj.append('id', id)
                dataObj.append('ownerID', $("#clone_instance_owner").val());
                dataObj.append('user', '<?php print($loggedinuserdata->userID); ?>');
                dataObj.append('token', '<?php print($token); ?>');
                $.ajax({
                    url: serviceurl,
                    data: dataObj,
                    cache: false,
                    contentType: false,
                    processData: false,
                    type: 'POST',
                    dataType: isie() ? "text" : "xml",
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function(data, textStatus, jqXHR) {
                        waitOff();
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        if ($(xml).find('error').length > 0) {
                            alert('Clone exam failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {
                            alert('Cloned exam successfully!');

                            location.assign('../sessions');
                        }
                    }
                });
            }


// delete an instance. (actually just set a deleted flag to 'true')
            function delete_instance(id) {
                var $deleteConfirmer = $('<div></div>')
                        .html('Really delete session?')
                        .dialog({
                    title: '<?php print($stringlib->get_string('really_delete_session')); ?>',
                    buttons: [
                        {
                            text: "OK",
                            click: function() {
                                $(this).dialog('close');
                                waitOn();
                                var dataObj = new Object();
                                dataObj = {
                                    action: 'deleteinstance',
                                    id: id,
                                    token: '<?php print($token); ?>'
                                };
                                $.ajax({
                                    url: serviceurl,
                                    type: 'post',
                                    data: dataObj,
                                    dataType: isie() ? "text" : "xml",
                                    error: function(jqXHR, textStatus, errorThrown) {
                                        alert(errorThrown);
                                    },
                                    success: function(data) {
                                        waitOff();
                                        var xml;
                                        if (typeof data == "string") {
                                            xml = new ActiveXObject("Microsoft.XMLDOM");
                                            xml.async = false;
                                            xml.loadXML(data);
                                        } else {
                                            xml = data;
                                        }

                                        if ($(xml).find('error').length > 0) {

                                            alert('Delete session failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                        } else {
                                            location.reload(true);
                                        }
                                    }
                                });
                            }
                        },
                        {
                            text: "Cancel",
                            click: function() {
                                $(this).dialog('close');
                            }
                        },
                    ]
                });
            }


            function validate_form(formID, buttonLabel) {
                var valid = true;
                $("#" + formID + " input[type='text']").each(function() {

                    if (($(this).val().length < 1) && ($(this).attr('required') == 'required')) {
                        valid = false;
                    }
                });
                $("#" + formID + " textarea").each(function() {

                    if (($(this).val().length < 1) && ($(this).attr('required') == 'required')) {
                        valid = false;
                    }
                });
                $("#" + formID + " select").each(function() {

                    if (($(this).select2('val') == '-1') && ($(this).attr('required') == 'required')) {
                        valid = false;
                    }
                });

                $(".ui-dialog-buttonpane button:contains('" + buttonLabel + "')").button(valid ? "enable" : "disable");

            }


   var increment = <?php print($increment); ?>;
            function goNext() {
                var from = <?php print(isset($_REQUEST['from']) ? $_REQUEST['from'] : '0'); ?>;
                window.location = 'index.php?from=' + (from + <?php print($increment); ?>);
            }

            function goPrev() {
                var from = <?php print(isset($_REQUEST['from']) ? $_REQUEST['from'] : '0'); ?>;
                window.location = 'index.php?from=' + (((from - increment) < 0) ? 0 : (from - <?php print($increment); ?>));
            }

            function goFirst() {
                window.location = 'index.php?from=0';
            }

            function goLast() {
                window.location = 'index.php?from=' +<?php print(($examdata->count) - $increment); ?>;
            }
            

            // waiting feedback
            function waitOn() {
                $(".ui-dialog").css("border", "none");
                $(".ui-dialog").css("background", "transparent");
                $("#waiting_dialog").dialog('open');
                $("#waiting_dialog").position({
                    my: "center",
                    at: "center",
                    of: document
                })
            }

            function waitOff() {
                $(".ui-dialog").css("border", "1px solid rgb(223, 217, 195)");
                $(".ui-dialog").css("background", "rgb(245, 243, 229)");
                $("#waiting_dialog").dialog('close');
            }

            function showHelp(helpStr) {
                var helpdialog = "<div></div>";
                $(helpdialog).dialog({
                    buttons: {
                        "OK": function () {
                            $(this).dialog("close");
                        }

                    },
                    position: {my: 'top', at: 'top+150'},
                    open: function (event, ui) {
                        $(this).parent().find('.ui-dialog-titlebar').html("<img src='<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>icons/gtk-dialog-question48.png' style='vertical-align:middle'>Help");
                    }
                }).html("<fieldset><div>" + helpStr + "</div></fielset>");
            }
        </script>
    </head>
    <body>
        <!-- overall container -->
        <div class="overallcontainer">
            <!-- header -->
            <div class="headercontainer">
                <div class="logininfo">Logged in as <u><?php print($loggedinuserdata->name); ?></u> (<a href='javascript:window.location.assign("<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>index.php?logout");' >Log Out</a>)</div>
                <div class="pageheader">
                    <div style="display: table">
                        <div style="display: table-row; ">
                            <div style="display: table-cell;"><img  src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>icons/eoscelogoune.png"></div><div style="display: table-cell; vertical-align: bottom;" class="headertitle">:<?php print("{$CFG->sysname}"); ?></div>
                        </div>
                    </div>
                </div>
                <div class="breadcrumbs"> 
                    <?php
                    print($breadcrumbStr);
                    ?>
                </div>
            </div>

            <div class="contentareacontainer">

                <div class="navcontainer">

                    <div class="navheader">
                        Navigation
                    </div>    
                    <div class="navcontent">
                        <?php print($menuStr); ?>
                    </div>    
                </div>

                <div class="contentcontainer">
                    <div class="contentinner">

                        <div class="contentcontentnostatus">

                            <h3> <?php print($stringlib->get_string('eosce_archive')); ?></h3>
                            <?php print($listTableStr); ?>
                            <?php print($buttonStr); ?>
                        </div>
                    </div>

                </div>
                <!--                <div class="statusarea">
                                    <div class="statusheader " style="color: #050708; background-color:rgba(255,165,0, 0.8); border-bottom: 6px solid #050708;">
                                        Site filter
                                    </div>    
                                    <div class="statuscontent" >
                
                                    </div>    
                                </div>-->
            </div>
        </div>


        <div id="info_dialog">

        </div>
        <!-- Waiting-->
        <div id="waiting_dialog">
            <p style="text-align: center"><img src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>icons/ajax-loader.gif" style="vertical-align: middle"/><br/>Please wait...</p>
        </div>
     <?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments)) { ?>


            <div id="clone_exam_dialog">
                <fieldset style="width: 90%"><legend></legend>
                    <div>
                        <label for="clone_instance_owner"><?php print($stringlib->get_string('osce_session_owner')); ?></label><br/>
                        <select id="clone_instance_owner" required="required" name="instance_owner" onchange="validate_form('clone_exam_dialog', '<?php print($stringlib->get_string('clone_osce_session_btn_lbl')); ?>')"><option value="-1">Select a user...</option><?php print($usersStr); ?></select><br/>
                    </div>
                </fieldset>          
            </div>
        <?php } ?>

    </body>
</html>

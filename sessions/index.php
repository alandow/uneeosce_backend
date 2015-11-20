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
$breadcrumbStr = "<a href='{$CFG->wwwroot}{$CFG->basedir}'>Home</a>->";
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
print($patharr[$i - 1]);
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
        $menuStr .=(($patharr[$i - 1] == 'reports') ? "<span class='currentmenulocation'>{$stringlib->get_string('reports_help')}</span>" : "<a href='javascript:window.location.assign(\"{$CFG->wwwroot}{$CFG->basedir}reports/\")'>{$stringlib->get_string('reports_index_label')}</a>");
    }
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_assessments)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('osce_archive_help')}\"); return false;'/></div>"
                . "<div style='float:left;display:table-cell; vertical-align:middle'>";
        $menuStr .=(($patharr[$i - 1] == 'archive') ? "<span class='currentmenulocation'>{$stringlib->get_string('eosce_archive')}</span>" : "<a href='javascript:window.location.assign(\"{$CFG->wwwroot}{$CFG->basedir}archive/\")'>{$stringlib->get_string('eosce_archive')}</a>");
        $menuStr .="</div></div></li>";
    }
    $menuStr .="</ul><li>";

//    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_system_users) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_strings)) {
//        $menuStr .= "<li class='showing'><label class='tree-toggle nav-header'>{$stringlib->get_string('system_administration_label')}</label>";
//
//        $menuStr .= "<ul class='nav nav-list tree'>"
//                . "<li><div style='vertical-align: middle; display:table;'>"
//                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_users_setup_help')}\"); return false;'/></div>"
//                . "<div style='float:left;display:table-cell; vertical-align:middle'>";
//        $menuStr .=(($patharr[$i - 1] == 'users') ? "<span class='currentmenulocation'>{$stringlib->get_string('system_users_setup')}</span>" : "<a href='javascript:window.location.assign(\"{$CFG->wwwroot}{$CFG->basedir}admin/users/\")'>{$stringlib->get_string('system_users_setup')}</a>");
//        $menuStr .="</div></div></li>";
//
//        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
//                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_criteria_setup_help')}\"); return false;'/></div>"
//                . "<div style='float:left;display:table-cell; vertical-align:middle'>";
//        $menuStr .=(($patharr[$i - 1] == 'criteriatypes') ? "<span class='currentmenulocation'>{$stringlib->get_string('string_criteria_types_label')}</span>" : "<a href='javascript:window.location.assign(\"{$CFG->wwwroot}{$CFG->basedir}admin/criteriatypes/\")'>{$stringlib->get_string('string_criteria_types_label')}</a>");
//        $menuStr .="</div></div></li>";
//
//        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
//                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_labels_help')}\"); return false;'/></div>"
//                . "<div style='float:left;display:table-cell; vertical-align:middle'>";
//        $menuStr .=(($patharr[$i - 1] == 'strings') ? "<span class='currentmenulocation'>{$stringlib->get_string('string_management_form_label')}</span>" : "<a href='javascript:window.location.assign(\"{$CFG->wwwroot}{$CFG->basedir}admin/strings/\")'>{$stringlib->get_string('string_management_form_label')}</a>");
//        $menuStr .="</div></div></li>";
//        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
//                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_lookups_help')}\"); return false;'/></div>"
//                . "<div style='float:left;display:table-cell; vertical-align:middle'>";
//        $menuStr .=(($patharr[$i - 1] == 'lookups') ? "<span class='currentmenulocation'>{$stringlib->get_string('system_lookups_form_label')}</span>" : "<a href='javascript:window.location.assign(\"{$CFG->wwwroot}{$CFG->basedir}admin/lookups/\")'>{$stringlib->get_string('system_lookups_form_label')}</a>");
//        $menuStr .="</div></div></li>";
//
//        if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_students)) {
//            $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
//                    . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('participants_setup_help')}\"); return false;'/></div>"
//                    . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='{$CFG->wwwroot}{$CFG->basedir}students'>{$stringlib->get_string('participants_setup')}</a></div>"
//                    . "</div></li>";
//        }
//        $menuStr .="</ul></li>";
//    }
}

$from = isset($_REQUEST['from']) ? ($_REQUEST['from'] > 0 ? $_REQUEST['from'] : 0) : 0;
$increment = 10;


$examdata = simplexml_load_string($enumlib->getExamInstances($increment, $from));


$listTableStr = "<table><tr><th>{$stringlib->get_string('osce_sessions')}</th>

<th>{$stringlib->get_string('osce_session_status')}</th>";
//<th>{$stringlib->get_string('osce_session_description')}</th>
$listTableStr .= "   <th>{$stringlib->get_string('osce_session_unit')}</th>
            <th>{$stringlib->get_string('osce_session_owner')}</th>
                <th>{$stringlib->get_string('osce_session_date')}</th>
                    <th>Created Date</th>";
// $listTableStr .= "<th>{$stringlib->get_string('osce_session_created_by')}</th>
$listTableStr .= "<th>{$stringlib->get_string('osce_session_assessors')}</th>
                        <th>{$stringlib->get_string('osce_session_participants')}</th>
                            
                                <th>Print</th><th>Clone</th>" . ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments) ? "<th>Delete</th>" : "") . "</tr>";
foreach ($examdata->instance as $instance) {
    $listTableStr .= "<tr class='" . (($instance->active == 'true') ? 'active' : (($instance->practicing == 'true') ? 'practicing' : 'inactive')) . "'>";
    $listTableStr .= "<td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/dialog-information.png' BORDER='0' style='vertical-align: text-bottom;' onclick='show_information(\"" . addslashes($instance->description) . "\"); return false;'/>" . ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments) ? "<a href='edit/index.php?id={$instance->id}'>{$instance->name}</a>" : $instance->name) . "</td>";
//    $listTableStr .= ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments) ? "<td>
//        " . (($instance->finalised == 'true') ? "<img src='{$CFG->wwwroot}{$CFG->basedir}icons/gtk-preferences-disabled.png'>" : "<input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-preferences.png' BORDER='0' style='vertical-align: text-bottom;' onclick='edit_instance({$instance->id}); return false;'/>") . "</td>" : "");
    $listTableStr .= "<td>";
    if ($instance->finalised == 'true') {
        $listTableStr .= '<span style="color:#800000">Locked,</span>';
    } else {
        $listTableStr .= 'Unlocked,';
    }

    if ($instance->active == 'true') {
        $listTableStr .= '<span style="color:#66CD00"><br/>Active</span>';
    } else {
        if ($instance->practicing == 'true') {
            $listTableStr .= '<span style="color:#913700"><br/>Practicing</span>';
        } else {
            $listTableStr .= '<span style="color:#800000"><br/>Stopped</span>';
        }
    }

    $listTableStr .= "</td>";
    //  $listTableStr .= "<td>{$instance->description}</td>
    $listTableStr .= "<td>{$instance->unit}</td>
        <td>{$instance->owner}</td>
        <td>{$instance->exam_starttimestamp}</td>
        <td>{$instance->created_date}</td>";
//        <td>{$instance->created_by}</td>
    $listTableStr .= "<td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/emblem-people.png' BORDER='0' style='vertical-align: text-bottom;' onclick='assign_assessors({$instance->id}, \"{$instance->name}\"); return false;'/>({$instance->usercount})</td>
        <td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/emblem-people.png' BORDER='0' style='vertical-align: text-bottom;' onclick='assign_students({$instance->id}, \"{$instance->name}\"); return false;'/>({$instance->studentcount})</td>";

    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_print_word_exam)) {
        $listTableStr .= "<td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/Gnome-Printer-32.png' BORDER='0' style='vertical-align: text-bottom;' onclick='makeOutputChoice({$instance->id}); return false;'/></td>";
    } else {
        $listTableStr .= "<td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/Gnome-Printer-32.png' BORDER='0' style='vertical-align: text-bottom;' onclick='window.open(\"{$CFG->serviceURL}?action=getprintableassessmentformaspdf&exam_ID={$instance->id}&token={$token}\",\"_blank\"); return false;'/></td>";
    }
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

$enumLib = new EnumLib();
$unitsXML = simplexml_load_string($enumLib->getUnitsLookup());
$unitsStr = '';
foreach ($unitsXML->option as $value) {
    $unitsStr.="<option value='{$value->ID}'>{$value->description}</option>";
}

$usersXML = simplexml_load_string($enumLib->getUsers(''));

$usersStr = '';
foreach ($usersXML->user as $value) {
    $usersStr.="<option value='{$value->id}'>{$value->name}({$value->username})</option>";
}

$criteriaScalesXML = simplexml_load_string($enumLib->getCriteriaTypesLookup());
$scalesStr = "";
foreach ($criteriaScalesXML->item as $value) {
    $scalesStr.="<option value='{$value->id}'>{$value->description}</option>";
}
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
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/date.js"></script>
        <script src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/utils.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/select2.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/icheck.min.js"></script>
        <title></title>
        <script>
            var serviceurl = '<?php print($CFG->serviceURL); ?>';

            var currentSelectedInstanceID = 0;

// page code initiation
            $(document).ready(function () {

                $('.tree-toggle').click(function () {
                    $(this).parent().children('ul.tree').toggle(200, function () {
                        $(this).parent().removeClass($(this).parent().hasClass('showing') ? 'showing' : 'hidden');
                        $(this).parent().addClass(($(this).css('display') == 'none') ? 'hidden' : 'showing')
                        console.log('toggling');
                    });

                });
                // set up the waiting feedback dialog
                $("#waiting_dialog").dialog({autoOpen: false, modal: true,
                    open: function (event, ui) {
                        $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                        $(".ui-dialog-titlebar", $(this).parent()).hide();
                        $(".ui-resizable-handle", $(this).parent()).hide();
                    }
                });

                $("#help_dialog").dialog({
                    autoOpen: false, modal: true,
                    title: '<img src="../icons/dialog-question.png" />Help',
                    buttons: {
                        Ok: function () {
                            $(this).dialog("close");
                        }
                    }
                });

                $("#csvhelp").click(function () {
                    showHelp("<?php print($stringlib->get_string('add_student_by_csv_help')); ?>");
                })

                $("#info_dialog").dialog({
                    autoOpen: false, modal: true, width: 600,
                    title: '<img src="../icons/dialog-information.png" />Info',
                    buttons: {
                        Ok: function () {
                            $(this).dialog("close");
                        }
                    }
                });

                $("#clone_exam_dialog").dialog({
                    autoOpen: false, modal: true
                });
                // turn waiting feedback on
                //waitOn();

                ////////////////////////////////////////////////////////////////////////////////
                //  set up buttons
                //////////////////////////////////////////////////////////////////////////////// 

                // New exam instance button
                $("#newBut").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-plusthick"
                    }

                    // what happens when the button is clicked
                }).click(function () {
                    create_instance();
                });

                $("#add_students_by_csv_but").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-plusthick"
                    }, disabled: true
                });

                // import exam instance button
                $("#importBut").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-gear"
                    }

                    // what happens when the button is clicked
                }).click(function () {
                    import_instance();
                });

                // set up the date pickers for start and end dates
                $("#instance_startdate").datepicker();



                // setting up some dialogs
                // tell the system that the exam_instance_setup div is a dialog
                $("#exam_instance_setup").dialog({autoOpen: false, modal: true});
                $("#edit_exam_instance_setup").dialog({autoOpen: false, modal: true});

                // the assessor link dialog, used to associate assessors with instances
                $("#assessor_link_setup").dialog({autoOpen: false, modal: true});

                $("#student_link_setup").dialog({autoOpen: false, modal: true});

                $("#import_exam_dialog").dialog({autoOpen: false, modal: true});


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
                $("#assign_but").button();
                validateAssignBut();

                $("select").select2({minimumResultsForSearch: 5});

                // give an output choice if authorised
<?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments)) { ?>
                    $("#output_dialog").dialog({autoOpen: false});

<?php } ?>


                waitOff();
            });


<?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_print_word_exam)) { ?>
                function makeOutputChoice(examid) {
                    $("#output_dialog").dialog({autoOpen: true, modal: true,
                        title: 'Select output format',
                        buttons: {
                            Ok: function () {

                                if ($("#output_select").val() == 'word') {
                                    window.open(serviceurl + "?action=getprintableassessmentformasword&exam_ID=" + examid + "&token=<?php print($token); ?>", "_blank");
                                } else {
                                    window.open(serviceurl + "?action=getprintableassessmentformaspdf&exam_ID=" + examid + "&token=<?php print($token); ?>", "_blank");
                                }
                                $(this).dialog("close");
                            },
                            Cancel: function () {
                                $(this).dialog("close");
                            }
                        }});
                }
<?php } ?>
            function create_instance() {

                editmode = false;
                // open the new user dialog
                $("#exam_instance_setup").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('new_osce_session')); ?>',
                    width: 600,
                    buttons: {
                        "OK": function () {
                            $(this).dialog("close");
                            create_instance_action();
                        },
                        Cancel: function () {
                            $(this).dialog("close");
                        }
                    }
                });
                // disable the go button 
                $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('create_osce_session_btn_lbl')); ?>')").button("disable");
                // return control to the main app. This ajax operation takes the longest so we'll wait until it's over before wait off

                waitOff();


            }

            // AJAX functionality to create an instance
            function create_instance_action() {
                waitOn();
                var dataObj = new Object();
                dataObj = {action: 'newexaminstance',
                    instance_name: $("#instance_name").val(),
                    instance_description: $("#instance_description").val(),
                    unitid: $("#unit").val(),
                    scaleid: $("#scale").val(),
//                    instance_starttimestamp: Date.parse($("#instance_startdate").val()).getTime() / 1000,
                    userID: <?php print($loggedinuserdata->userID); ?>,
                    ownerID: $("#instance_owner").val(),
                    token: '<?php print($token); ?>'};
                $.ajax({
                    url: serviceurl,
                    type: 'post',
                    data: dataObj,
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
                        waitOff();
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        //   console.log($(xml).find('error').length);
                        if ($(xml).find('error').length > 0) {
                            alert('create session failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            //eraseCookie('token');
                            //window.location = "../index.php";
                        } else {
                            console.log(data);
                            window.location = "edit/index.php?id=" + $(xml).find('id').text();
//                            location.reload(true);
                        }
                    }
                });
            }

//  functionality to eit an instance. Opens a dialog box after getting data about the instence from the backend
            function edit_instance(id) {
                waitOn();
                currentID = id;
                dataObj = {action: 'getinstancebyid', id: id, token: '<?php print($token); ?>'};
                $.ajax({
                    url: serviceurl,
                    type: 'post',
                    data: dataObj,
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
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
                            // if there's an error, log out and go to home page
                            //  eraseCookie('token');
                            // window.location = "../index.php";
                            alert($(xml).find('error'));
                        } else {
                            // make the system_user_setup div a dialog and populate it
                            $("#edit_exam_instance_setup").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('edit_osce_session')); ?>',
                                width: 600,
                                open: function (event, ui) {
                                    $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                                },
                                buttons: {
                                    "<?php print($stringlib->get_string('update_osce_session_btn_lbl')); ?>": function () {
                                        $(this).dialog("close");
                                        update_instance_action();
                                    },
                                    Cancel: function () {
                                        $(this).dialog("close");
                                    }
                                }
                            });
                            $("#edit_instance_name").val($(xml).find('name').text());
                            $("#edit_instance_description").val($(xml).find('description').text());
                            $("#edit_unit").select2("val", $(xml).find('unit_id').text());
                            $("#edit_scale").select2("val", $(xml).find('scale').text());
                            // $("#edit_instance_startdate").val($(xml).find('exam_starttimestamp').text());
                            $("#edit_instance_owner").select2("val", $(xml).find('owner_id').text());
                            console.log('owner ID is:' + $(xml).find('owner_id').text());
                            $(".ui-dialog-buttonpane button:contains('update_osce_session_btn_lbl')").button("disable");
                            validate_form('edit_exam_instance_setup', '<?php print($stringlib->get_string('update_osce_session_btn_lbl')); ?>');
                            waitOff();
                        }
                    }
                });
            }

            // AJAX functionality to update an instance
            function update_instance_action() {
                waitOn();
                dataObj = {action: 'updateinstance',
                    instance_name: $("#edit_instance_name").val(),
                    instance_description: $("#edit_instance_description").val(),
                    unitid: $("#edit_unit").select2('val'),
                    scaleid: $("#edit_scale").val(),
//                    instance_starttimestamp: Date.parse($("#instance_startdate").val()).getTime() / 1000,
                    userID: <?php print($loggedinuserdata->userID); ?>,
                    id: currentID,
                    ownerID: $("#edit_instance_owner").select2("val"),
                    token: '<?php print($token); ?>'};
                $.ajax({
                    url: serviceurl,
                    type: 'post',
                    data: dataObj,
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
                        waitOff();
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        //   console.log($(xml).find('error').length);
                        if ($(xml).find('error').length > 0) {
                            alert('update instance failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            //eraseCookie('token');
                            //window.location = "../index.php";
                        } else {
                            waitOff();
                            location.reload(true);
                        }
                    }
                });
            }

// import an exam instance
            function import_instance() {
                $("#import_exam_dialog").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('import_exam')); ?>',
                    width: 600,
                    buttons: {
                        "<?php print($stringlib->get_string('import_osce_session_btn_lbl')); ?>": function () {
                            $(this).dialog("close");
                            upload_examfile_action();
                        },
                        Cancel: function () {
                            $(this).dialog("close");
                        }
                    }
                });
                validate_form('import_exam_dialog', '<?php print($stringlib->get_string('import_osce_session_btn_lbl')); ?>');
            }

// upload the exam instance file
            function upload_examfile_action() {
                waitOn();
                var dataObj = new FormData();
                // file
                jQuery.each($('#exam_import_file')[0].files, function (i, file) {
                    dataObj.append('file', file);
                });

                dataObj.append('action', 'importexamfromxmlfile');
                dataObj.append('ownerID', $("#import_instance_owner").val());
                dataObj.append('unit', $("#import_unit").val());
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
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data, textStatus, jqXHR) {
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
                            alert('upload file failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            waitOff();
                        } else {
                            alert('Imported exam successfully!');
                            location.reload(true);
                        }
                        // location.reload(true);
                    }
                });
            }

// open the clone assessment dialog
            function clone_assessment(id, title) {
                $("#clone_exam_dialog").dialog({autoOpen: true, modal: true, title: 'Clone ' + title,
                    width: 600,
                    buttons: {
                        "<?php print($stringlib->get_string('clone_osce_session_btn_lbl')); ?> ": function () {
                            $(this).dialog("close");
                            clone_examfile_action(id);
                        },
                        Cancel: function () {
                            $(this).dialog("close");
                        }
                    }
                });
                validate_form('clone_exam_dialog', '<?php print($stringlib->get_string('clone_osce_session_btn_lbl')); ?>');
            }

// perform the clone action
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
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data, textStatus, jqXHR) {
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
                            waitOff();
                        } else {
                            alert('Cloned exam successfully!');
                            location.reload(true);
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
                                    click: function () {
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
                                            error: function (jqXHR, textStatus, errorThrown) {
                                                alert(errorThrown);
                                            },
                                            success: function (data) {
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
                                                    waitOff();
                                                } else {
                                                    location.reload(true);
                                                }
                                            }
                                        });
                                    }
                                },
                                {
                                    text: "Cancel",
                                    click: function () {
                                        $(this).dialog('close');
                                    }
                                },
                            ]
                        });
            }

            //////////////////////////////////////////////////////////////////
            // activate/deactivate
            /////////////////////////////////////////////////////////////////

            function activate_assessment(id) {
                var $activateConfirmer = $('<div></div>')
                        .html('Really start session?')
                        .dialog({
                            title: '<?php print($stringlib->get_string('really_activate_session')); ?>',
                            buttons: [
                                {
                                    text: "OK",
                                    click: function () {
                                        $(this).dialog('close');
                                        waitOn();
                                        var dataObj = new Object();
                                        dataObj = {
                                            action: 'activatesession',
                                            id: id,
                                            token: '<?php print($token); ?>'
                                        };
                                        $.ajax({
                                            url: serviceurl,
                                            type: 'post',
                                            data: dataObj,
                                            dataType: isie() ? "text" : "xml",
                                            error: function (jqXHR, textStatus, errorThrown) {
                                                alert(errorThrown);
                                            },
                                            success: function (data) {
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

                                                    alert('Activate session failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                                    waitOff();
                                                } else {
                                                    location.reload(true);
                                                }
                                            }
                                        });
                                    }
                                },
                                {
                                    text: "Cancel",
                                    click: function () {
                                        $(this).dialog('close');
                                    }
                                },
                            ]
                        });
            }


            function deactivate_assessment(id) {
                var $deactivateConfirmer = $('<div></div>')
                        .html('Really stop session?')
                        .dialog({
                            title: '<?php print($stringlib->get_string('really_deactivate_session')); ?>',
                            buttons: [
                                {
                                    text: "OK",
                                    click: function () {
                                        $(this).dialog('close');
                                        waitOn();
                                        var dataObj = new Object();
                                        dataObj = {
                                            action: 'deactivatesession',
                                            id: id,
                                            token: '<?php print($token); ?>'
                                        };
                                        $.ajax({
                                            url: serviceurl,
                                            type: 'post',
                                            data: dataObj,
                                            dataType: isie() ? "text" : "xml",
                                            error: function (jqXHR, textStatus, errorThrown) {
                                                alert(errorThrown);
                                            },
                                            success: function (data) {
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

                                                    alert('Activate session failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                                    waitOff();
                                                } else {
                                                    location.reload(true);
                                                }
                                            }
                                        });
                                    }
                                },
                                {
                                    text: "Cancel",
                                    click: function () {
                                        $(this).dialog('close');
                                    }
                                },
                            ]
                        });
            }


/////////////////////////////////////////////////////////////////////////////
//User assigning to instances
///////////////////////////////////////////////////////////////////////////////
            // associate instances with users
            function assign_assessors(id, name) {
                $('#assessorfeedback').hide();
                // get currently assigned users
                waitOn();
                $.ajax({
                    url: serviceurl + "?action=listusersassociatedwithinstance&id=" + id + "&token=<?php print($token); ?>",
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        $("#selectedassessors_table").html('');
                        $(xml).find('user').each(function () {
                            var inneruserID = ($(this).find('id')).text();
                            var alreadyselected = false;
                            $('#selectedassessors_table').find('tr').each(function () {
                                if ($(this).attr('id') == 'td_' + inneruserID) {
                                    alreadyselected = true;
                                }
                            });
                            if (!alreadyselected) {
                                $('#selectedassessors_table').append('<tr id="td_' + ($(this).find('id')).text() + '"><td>' + ($(this).find('name')).text() + ' (' + ($(this).find('username')).text() + ')</td><td><input type=\'image\' src=\'<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/icons/gtk-cancel.png\' BORDER=\'0\' style=\'vertical-align: text-bottom;\' onclick=\'remove_single_assessor(' + ($(this).find('id')).text() + '); return false;\'/></td></tr>');

                            }
                        });
                        waitOff();
                    }
                });
                currentSelectedInstanceID = id;
                $("#instance_name").html(name);
                $("#assessor_link_setup").dialog({autoOpen: true, modal: true, title: 'Assign assessors to ' + name,
                    width: 800,
                    buttons: {
                        "OK": function () {
                            $(this).dialog("close");
                            location.reload(true);
                        }

                    }
                });
            }

// find asessors using a searchstr. TODO if there's more than a certain number it'll be weird.
            var searchingassessors = false;
            var xhr = new Object();
            function find_assessors(searchstr) {
                // waitOn()
                if (!searchingassessors) {

                    $('#assessorfeedback').show();
                    searchingassessors = true;
                    xhr = $.ajax({
                        url: serviceurl + "?action=listusers&searchstr=" + searchstr + '&token=<?php print($token); ?>',
                        dataType: isie() ? "text" : "xml",
                        error: function (jqXHR, textStatus, errorThrown) {
                            if (errorThrown != 'abort') {
                                alert(errorThrown);
                            }
                            searchingassessors = false;
                        },
                        success: function (data) {
                            searchingassessors = false;

                            var xml;
                            if (typeof data == "string") {
                                xml = new ActiveXObject("Microsoft.XMLDOM");
                                xml.async = false;
                                xml.loadXML(data);
                            } else {
                                xml = data;
                            }
                            // console.log(data);
                            $('#available_assessors_div').html('<table>');
                            $(xml).find('user').each(function () {
                                var inneruserID = ($(this).find('id')).text();
                                var alreadyselected = false;
                                $('#selectedassessors_table').find('tr').each(function () {
                                    if ($(this).attr('id') == 'td_' + inneruserID) {
                                        alreadyselected = true;
                                    }
                                });
                                if (!alreadyselected) {
                                    $('#available_assessors_div').append('<tr><td>' + ($(this).find('name')).text() + ' (' + ($(this).find('username')).text() + ')</td><td><input type=\'image\' src=\'<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/icons/gtk-go-right.png\' BORDER=\'0\' style=\'vertical-align: text-bottom;\' onclick=\'select_single_assessor(' + ($(this).find('id')).text() + '); return false;\'/></td></tr>');
                                }
                            });
                            $('#available_assessors_div').append("</table>")
                            // waitOff();
                            $('#assessorfeedback').hide();
                        }
                    });
                }
            }

// select an assessor from the available list and put it in the 'selected' list
            function select_single_assessor(id) {
                var alreadyselected = false;
                $('#selectedassessors_table').find('tr').each(function () {
                    if ($(this).attr('id') == 'td_' + id) {
                        alreadyselected = true;
                    }
                });
                if (!alreadyselected) {
                    waitOn()
                    $.ajax({
                        url: serviceurl + "?action=associateuser&id=" + currentSelectedInstanceID + "&userid=" + id + '&token=<?php print($token); ?>',
                        dataType: isie() ? "text" : "xml",
                        error: function (jqXHR, textStatus, errorThrown) {
                            alert(errorThrown);
                        },
                        success: function (data) {
                            var xml;
                            if (typeof data == "string") {
                                xml = new ActiveXObject("Microsoft.XMLDOM");
                                xml.async = false;
                                xml.loadXML(data);
                            } else {
                                xml = data;
                            }

                            console.log(data);
                            $(xml).find('user').each(function () {
                                $('#selectedassessors_table').append('<tr id="td_' + ($(this).find('id')).text() + '"><td>' + ($(this).find('name')).text() + ' (' + ($(this).find('username')).text() + ')</td><td><input type=\'image\' src=\'<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/icons/gtk-cancel.png\' BORDER=\'0\' style=\'vertical-align: text-bottom;\' onclick=\'remove_single_assessor(' + ($(this).find('id')).text() + '); return false;\'/></td></tr>');
                            });

                            waitOff();
                        }
                    });
                }
            }

// remove an assessor from the 'selected' list
            function remove_single_assessor(id) {
                $.ajax({
                    // url: serviceurl + "?action=getstudentbyid&id=" + id + "&token=" + readCookie('token'),
                    url: serviceurl + "?action=dissociateuser&id=" + currentSelectedInstanceID + "&userid=" + id + '&token=<?php print($token); ?>',
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        if ($(xml).find('status').text() == 'success') {
                            $('std_' + id).remove();
                        }
                        waitOff();
                    }
                });
                $('#td_' + id).remove();
            }

// send the assessor data to the database for storage
            function send_assessor_data() {
                waitOn();
                var selectedIDS = '';
                $('#selectedassessors_table').find('tr').each(function () {
                    selectedIDS += ($(this).attr('id')).split('_')[1] + ','
                });

                $.ajax({
                    url: serviceurl + "?action=associateusers&id=" + currentSelectedInstanceID + "&userids=" + selectedIDS + '&token=<?php print($token); ?>',
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }

                        if ($(xml).find('error').length > 0) {
                            alert('Associate session failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            waitOff();
                            //eraseCookie('token');
                            //window.location = "../index.php";
                        } else {
                            $("#assessor_link_setup").dialog('close');
                        }

                        waitOff();
                    }
                });

            }

            /////////////////////////////////////////////////////////////////////////////
//Student assigning to instances
///////////////////////////////////////////////////////////////////////////////

            // associate instances with users
            function assign_students(id, name) {
                // get currently assigned students
                waitOn();
                $.ajax({
                    url: serviceurl + "?action=liststudentsassociatedwithinstance&id=" + id + '&token=<?php print($token); ?>',
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
                        var xml;
                        var rowStr;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        $("#selectedstudents_table").empty();

                        $(xml).find('student').each(function () {
                            var inneruserID = ($(this).find('id')).text();
                            var alreadyselected = false;
                            $('#selectedstudents_table').find('tr').each(function () {
                                if ($(this).attr('id') == 'std_' + inneruserID) {
                                    alreadyselected = true;
                                }
                            });
                            if (!alreadyselected) {
                                rowStr = '<tr id="std_' + ($(this).find('id')).text() + '"><td>' + ($(this).find('fname')).text() + ' ' + ($(this).find('lname')).text() + '  (' + ($(this).find('studentnum')).text() + ')</td><td>\n\
                                     <select onchange="updateSiteForStudent(' + ($(this).find('entryid')).text() + ', \'studentsite' + ($(this).find('entryid')).text() + '\')" id=\'studentsite' + ($(this).find('entryid')).text() + '\'>';
                                rowStr += "<?php print($enumLib->getSiteCodeLookup()); ?>" + "</select></td>";
                                rowStr += '<td><input type=\'image\' src=\'<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/icons/gtk-cancel.png\' BORDER=\'0\' style=\'vertical-align: text-bottom;\' onclick=\'remove_single_student(' + ($(this).find('id')).text() + '); return false;\'/></td></tr>'

                                $('#selectedstudents_table').append(rowStr);
                                //$("#studentsite" + ($(this).find('id')).text()).select2();
                                $("#studentsite" + ($(this).find('entryid')).text()).val(($(this).find('site')).text());
                                // $("#studentsite" + ($(this).find('id')).text()).select2({val: ($(this).find('site')).text(), minimumResultsForSearch:-1});
                            }
                        });
                        currentSelectedInstanceID = id;
                        var currentwidth = window.innerWidth;
                        $("#instance_name").html(name);
                        $('#studentfeedback').hide();
                        validateStudentCSVUploadBut()
                        $("#student_link_setup").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('assign_students_to_session')); ?>' + name,
                            width: currentwidth * 0.9,
                            buttons: {
                                "OK": function () {
                                    $(this).dialog("close");
                                    location.reload(true);
                                }
                            }
                        });
                        waitOff();
                    }
                });


            }

            function updateSiteForStudent(entryid) {
                // get currently assigned students
                waitOn();
                $.ajax({
                    url: serviceurl + "?action=updatesiteforstudent&entryid=" + entryid + '&site=' + $("#studentsite" + entryid).val() + '&token=<?php print($token); ?>',
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
                        var xml;
                        var rowStr;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        if ($(xml).find('error').length > 0) {
                            alert('Updating site failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());

                            //eraseCookie('token');
                            //window.location = "../index.php";
                        }
                        waitOff();
                    }
                });
            }

// find student using a searchstr. TODO if there's more than a certain number it'll be weird.
            var searchingstudents = false;
            var xhr2 = new Object();

            function find_studentsByName(searchstr) {
                $('#studentfeedback').show();
                if (searchingstudents) {
                    xhr2.abort();
                }
                xhr2 = $.ajax({
                    url: serviceurl + "?action=liststudentsbysearchstr&searchstr=" + searchstr + '&token=<?php print($token); ?>',
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        if (errorThrown != 'abort') {
                            alert(errorThrown);
                        }
                        searchingstudents = false;
                    },
                    success: function (data) {
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        // console.log(data);
                        $('#available_students_table').empty();
                        $(xml).find('student').each(function () {
                            var inneruserID = ($(this).find('id')).text();
                            var alreadyselected = false;
                            $('#selectedstudents_table').find('tr').each(function () {
                                if ($(this).attr('id') == 'std_' + inneruserID) {
                                    alreadyselected = true;
                                }
                            });
                            if (!alreadyselected) {
                                $('#available_students_table').append('<tr id="astd_' + ($(this).find('id')).text() + '"><td>' + ($(this).find('fname')).text() + ' ' + ($(this).find('lname')).text() + ' (' + ($(this).find('studentnum')).text() + ')</td><td><input type=\'checkbox\' class=\'inp-checkbox\' id=\'ascb_' + ($(this).find('id')).text() + '\' /></td></tr>');
//                        <input type=\'image\' src=\'<?php print($CFG->wwwroot . $CFG->basedir); ?>/icons/gtk-go-right.png\' BORDER=\'0\' style=\'vertical-align: text-bottom;\' onclick=\'select_single_student(' + ($(this).find('id')).text() + '); return false;\'/></td></tr>');

                            }
                        });
                        $('.inp-checkbox').iCheck({checkboxClass: 'icheckbox_square-purple',
                            increaseArea: '30%'}).on('ifChanged', validateAssignBut);
                        // $('#available_students_div').append("</table>")
                        $('#studentfeedback').hide();
                    }
                });
            }


            //   var progressVar;
            function add_studentsByCSV() {

                waitOn();
                var dataObj = new FormData();
                // file
                jQuery.each($('#new_student_csv_file')[0].files, function (i, file) {
                    dataObj.append('file', file);
                });

                dataObj.append('action', 'associatestudentsbycsv');
                dataObj.append('id', currentSelectedInstanceID);
                dataObj.append('token', '<?php print($token); ?>');
                $.ajax({
                    url: serviceurl,
                    data: dataObj,
                    cache: false,
                    contentType: false,
                    processData: false,
                    type: 'POST',
                    dataType: "text",
                    beforeSend: function (jqXHR, settings) {
                        var self = this;
                        var xhr = settings.xhr;
                        settings.xhr = function () {
                            var output = xhr();
                            output.onreadystatechange = function () {
                                if (typeof (self.readyStateChanged) == "function") {
                                    self.readyStateChanged(this);
                                }
                            };
                            return output;
                        };
                    },
                    readyStateChanged: function (xhr) {

                        console.log(xhr.readyState);
                        if (xhr.readyState == 3) {
                            console.log(xhr.responseText);

                            $("#waitmessage").html(xhr.responseText.split(",")[xhr.responseText.split(",").length - 2]);

                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        waitOff();
                        alert(errorThrown);
                    },
                    success: function (data, status, xhr) {
                        waitOff();
                        var returnedval = data.split(',');

                        var xml = returnedval.pop();

                        if ($(xml).find('error').length > 0) {
                            alert('Associate operation failed:' + $(xml).find('detail').text());

                            assign_students(currentSelectedInstanceID);
                        } else {
                            assign_students(currentSelectedInstanceID);
                        }

                    }
                });



            }



// Sends selected students from the available list and put them in the 'selected' list
            function assignSelectedStudents() {
                // find selected students
                // an array to store IDs
                var studentIDs = [];
                $('.inp-checkbox').each(function () {
                    if ($(this).is(':checked')) {
                        studentIDs.push($(this).attr('id').split("_")[1]);
                    }
                });

                waitOn()
                $.ajax({
                    // url: serviceurl + "?action=getstudentbyid&id=" + id + "&token=" + readCookie('token'),
                    url: serviceurl + "?action=associatemultiplestudents&id=" + currentSelectedInstanceID + "&students=" + studentIDs.join(',') + '&siteid=' + $("#site_selector").val() + '&token=<?php print($token); ?>',
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        $(xml).find('student').each(function () {
                            $('#selectedstudents_table').append('<tr id="std_' + ($(this).find('id')).text() + '"><td>' + ($(this).find('fname')).text() + ' ' + ($(this).find('lname')).text() + '  (' + ($(this).find('studentnum')).text() + ')</td><td><input type=\'image\' src=\'<?php print($CFG->wwwroot . $CFG->basedir); ?>/icons/gtk-cancel.png\' BORDER=\'0\' style=\'vertical-align: text-bottom;\' onclick=\'remove_single_student(' + ($(this).find('id')).text() + '); return false;\'/></td></tr>');
                            $('#astd_' + ($(this).find('id')).text()).remove();
                        });

                        waitOff();
                    }
                });
            }




// remove an assessor from the 'selected' list
            function remove_single_student(id) {
                waitOn()
                $.ajax({
                    // url: serviceurl + "?action=getstudentbyid&id=" + id + "&token=" + readCookie('token'),
                    url: serviceurl + "?action=dissociatestudents&id=" + currentSelectedInstanceID + "&userid=" + id + '&token=<?php print($token); ?>',
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        if ($(xml).find('status').text() == 'success') {
                            $('#std_' + id).remove();
                        }
                        waitOff();
                    }
                });

            }

// send the student data to the database for storage
            function send_student_data() {
                waitOn();
                var selectedIDS = '';
                $('#selectedstudents_table').find('tr').each(function () {
                    selectedIDS += ($(this).attr('id')).split('_')[1] + ','
                });

                $.ajax({
                    url: serviceurl + "?action=associatestudents&id=" + currentSelectedInstanceID + "&userids=" + selectedIDS + '&token=<?php print($token); ?>',
                    dataType: isie() ? "text" : "xml",
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                    },
                    success: function (data) {
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }

                        if ($(xml).find('error').length > 0) {
                            alert('Associate session failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            //eraseCookie('token');
                            //window.location = "../index.php";
                        } else {
                            $("#student_link_setup").dialog('close');
                        }

                        waitOff();
                    }
                });

            }

///////////////////////////////////////////////////////////////////////////////////////
//Helper functions
///////////////////////////////////////////////////////////////////////////////////////
// Validation function for the instance creation form
            function validate_create_form() {
                if (($("#instance_name").val().length > 0) &&
                        ($("#instance_description").val().length > 0) &&
                        ($("#unit").val() > -1)) {

                    $(".ui-dialog-buttonpane button:contains('Create Session')").button("enable");
                    //                $("#userupdatebut").button({ disabled: false });
                    //                $("#userupdatebut").button({ disabled: false });
                } else {
                    $(".ui-dialog-buttonpane button:contains('Create Session')").button("disable");
                }
            }


            function validate_form(formID, buttonLabel) {
                var valid = true;
                $("#" + formID + " input[type='text']").each(function () {

                    if (($(this).val().length < 1) && ($(this).attr('required') == 'required')) {
                        valid = false;
                    }
                });
                $("#" + formID + " textarea").each(function () {

                    if (($(this).val().length < 1) && ($(this).attr('required') == 'required')) {
                        valid = false;
                    }
                });
                $("#" + formID + " select").each(function () {

                    if (($(this).select2('val') == '-1') && ($(this).attr('required') == 'required')) {
                        valid = false;
                    }
                });

                $(".ui-dialog-buttonpane button:contains('" + buttonLabel + "')").button(valid ? "enable" : "disable");

            }

            function validateAssignBut() {
                $("#assign_but").button(($('.inp-checkbox').is(':checked') ? 'enable' : 'disable'));
            }

            function validateStudentCSVUploadBut() {
                console.log(document.getElementById("new_student_csv_file").files.length)
                if (document.getElementById("new_student_csv_file").files.length > 0) {
                    $("#add_students_by_csv_but").button("option", "disabled", false);
                }
            }

// show a little dialog box with information
            function show_information(info) {
                $("#info_dialog").dialog('open').html(info);

            }

            function showHelp(helpStr) {
                $("#help_dialog").dialog('open').html(helpStr);
            }


// waiting feedback
            function waitOn(message) {
                message = (typeof message === 'undefined') ? 'Please Wait...' : message;
                $(".ui-dialog").css("border", "none");
                $(".ui-dialog").css("background", "transparent");
                $("#waiting_dialog").dialog('open');
                $("#waiting_dialog").position({
                    my: "center",
                    at: "center",
                    of: document
                });
                $("#waitmessage").html(message);
            }

            function waitOff() {
                $(".ui-dialog").css("border", "1px solid rgb(223, 217, 195)");
                $(".ui-dialog").css("background", "rgb(245, 243, 229)");
                $("#waiting_dialog").dialog('close');
            }

            var increment = 10;
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
                    <?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments)) { ?>
                        <div class="actionheader">
                            Actions
                        </div>    
                        <div class="actioncontent" style="height: auto">
                            
                            <button id="newBut" class="actionbutton" style="width:100%"><?php print($stringlib->get_string('new_osce_session')); ?></button>
                            <p></p>
                            <button id="importBut" class="actionbutton" style="width:100%"><?php print($stringlib->get_string('import_osce_session')); ?></button>
                            
                        </div> 
                    <?php } ?>
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
                            <div class="contentinnerheader">
                            <?php print($stringlib->get_string('osce_sessions_page_legend')); ?>
                        </div>
                            <?php print($listTableStr); ?>
                            <?php print($buttonStr); ?>

                        </div>
                    </div>
                    <!--                    <div class="statusarea">
                                            <div class="statusheader">
                                                Status Header
                                            </div>    
                                            <div class="statuscontent">
                                                Status Content
                                            </div>    
                                        </div>-->
                </div>

            </div>
        </div>

        <div id="exam_instance_setup" >
            <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('osce_session_details')); ?></legend>
                <div>
                    <label for="instance_name"><?php print($stringlib->get_string('osce_session_name')); ?></label><br/>
                    <input type="text" id="instance_name" style="width:100%" name="instance_name" required="required" onkeyup="validate_form('exam_instance_setup', '<?php print($stringlib->get_string('create_osce_session_btn_lbl')); ?>')" onchange="validate_create_form()"><br/>
                    <label for="instance_description"><?php print($stringlib->get_string('osce_session_description')); ?></label><br/>
                    <textarea id="instance_description" required="required" style="width:100%" name="instance_description" onkeyup="validate_form('exam_instance_setup', '<?php print($stringlib->get_string('create_osce_session_btn_lbl')); ?>')" onchange="validate_form('exam_instance_setup', '<?php print($stringlib->get_string('create_osce_session_btn_lbl')); ?>')"></textarea><br/>
                    <label for="scale"><?php print($stringlib->get_string('osce_session_scale')); ?></label><br/>
                    <span><select id="scale" required="required" name="scale" onchange="validate_form('exam_instance_setup', '<?php print($stringlib->get_string('create_osce_session_btn_lbl')); ?>');"><option value="-1">Select a <?php print($stringlib->get_string('osce_session_scale')); ?>...</option><?php print($scalesStr); ?></select></span>
                    <span><input type='image' src='<?php print("{$CFG->wwwroot}{$CFG->basedir}/icons/dialog-information.png"); ?>' BORDER='0' style='vertical-align: text-bottom;' onclick='show_information("<?php print($enumLib->getCriteriaScalesDescription()); ?>");
                            return false;'/></span><br/>
                    <label for="unit"><?php print($stringlib->get_string('osce_session_unit')); ?></label><br/>
                    <select id="unit" required="required" name="unit" onchange="validate_form('exam_instance_setup', '<?php print($stringlib->get_string('create_osce_session_btn_lbl')); ?>');"><option value="-1">Select a <?php print($stringlib->get_string('osce_session_unit')); ?>...</option><?php print($unitsStr); ?></select><br/>
<!--                    <label for="instance_startdate"><?php print($stringlib->get_string('osce_session_date')); ?></label><br/>
                    <input type="text" id="instance_startdate" required="required" name="instance_startdate" onkeyup="validate_form('exam_instance_setup', '<?php print($stringlib->get_string('create_osce_session_btn_lbl')); ?>')" onchange="validate_form('exam_instance_setup', '<?php print($stringlib->get_string('create_osce_session_btn_lbl')); ?>')"><br/>-->
                    <label for="instance_owner"><?php print($stringlib->get_string('osce_session_owner')); ?></label><br/>
                    <select id="instance_owner" required="required" name="instance_owner" onchange="validate_form('exam_instance_setup', '<?php print($stringlib->get_string('create_osce_session_btn_lbl')); ?>')"><option value="-1">Select a user...</option><?php print($usersStr); ?></select><br/>
                </div>
            </fieldset>
        </div>

        <div id="edit_exam_instance_setup" >
            <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('edit_osce_session_details')); ?></legend>
                <div>
                    <label for="edit_instance_name"><?php print($stringlib->get_string('edit_osce_session_name')); ?></label><br/>
                    <input type="text" id="edit_instance_name" style="width:100%" name="edit_instance_name" required="required" onkeyup="validate_form('edit_exam_instance_setup', '<?php print($stringlib->get_string('update_osce_session_btn_lbl')); ?>')" onchange="validate_edit_form()"><br/>
                    <label for="edit_instance_description"><?php print($stringlib->get_string('osce_session_description')); ?></label><br/>
                    <textarea id="edit_instance_description" style="width:100%" name="edit_instance_description" onkeyup="validate_form('edit_exam_instance_setup', '<?php print($stringlib->get_string('update_osce_session_btn_lbl')); ?>')" onchange="validate_form('edit_exam_instance_setup', '<?php print($stringlib->get_string('update_osce_session_btn_lbl')); ?>')"></textarea><br/>
                    <label for="edit_scale"><?php print($stringlib->get_string('osce_session_scale')); ?></label><br/>
                    <span><select id="edit_scale" required="required" name="scale" onchange="validate_form('edit_exam_instance_setup', '<?php print($stringlib->get_string('update_osce_session_btn_lbl')); ?>');"><option value="-1">Select a <?php print($stringlib->get_string('osce_session_scale')); ?>...</option><?php print($scalesStr); ?></select></span>
                    <span><input type='image' src='<?php print("{$CFG->wwwroot}{$CFG->basedir}/icons/dialog-information.png"); ?>' BORDER='0' style='vertical-align: text-bottom;' onclick='show_information("<?php print($enumLib->getCriteriaScalesDescription()); ?>");
                            return false;'/></span><br/>
                    <label for="edit_unit"><?php print($stringlib->get_string('edit_osce_session_unit')); ?></label><br/>
                    <select id="edit_unit" required="required" style="min-width: 200px" onchange="validate_form('edit_exam_instance_setup', '<?php print($stringlib->get_string('update_osce_session_btn_lbl')); ?>');"><option value="-1">Select a <?php print($stringlib->get_string('osce_session_unit')); ?>...</option><?php print($unitsStr); ?></select><br/>
                    <label for="edit_instance_owner"><?php print($stringlib->get_string('edit_osce_session_owner')); ?></label><br/>
                    <select id="edit_instance_owner" required="required" onchange="validate_form('edit_exam_instance_setup', '<?php print($stringlib->get_string('update_osce_session_btn_lbl')); ?>')"><option value="-1">Select a user...</option><?php print($usersStr); ?></select><br/>
                </div>
            </fieldset>
        </div>

        <div id="assessor_link_setup">
            <fieldset style="width: 95%"><legend><?php print($stringlib->get_string('osce_session_pick_assessors')); ?></legend>
                <table style="margin: 0; padding: 0">
                    <tr><th><?php print($stringlib->get_string('osce_session_available_assessors')); ?></th><th><?php print($stringlib->get_string('osce_session_assigned_assessors')); ?></th></tr>
                    <tr>
                        <td style="vertical-align: top">
                            Search for assessors<br/>
                            <input name="assessorselect" placeholder="Type to search" id="assessorselect" onkeyup="find_assessors($('#assessorselect').val());" style="width:250px; overflow-y: scroll" type="text">
                            <img id="assessorfeedback" src="../icons/dots16.gif">
                            <br/>
                            <!--                            <button onclick="find_assessors();">Find</button>
                                                        <br/>-->
                            <div id="available_assessors_div" style="width: 300px; height: 450px; overflow-y: scroll"></div>
                        </td>

                        <td>
                            <div id="selectedassessors_div" style="width: 300px; height: 500px; overflow-y: scroll">
                                <table id="selectedassessors_table" >

                                </table>

                            </div>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </div>

        <div id="student_link_setup">
            <fieldset style="width: 95%"><legend><?php print($stringlib->get_string('osce_session_pick_participants')); ?></legend>
                Add by CSV list of student numbers<input type='image' id="csvhelp" src='../icons/dialog-question.png' BORDER='0' style='vertical-align: middle; display:table-cell'/>
                <fieldset>
                    <label for="new_student_csv_file" id="new_file_upload_label">CSV file for upload (<a href="<?php print("{$CFG->wwwroot}{$CFG->basedir}templates/sessionstudentuploadtemplate.csv"); ?>" download>Open an example here</a>)</label><br/>
                    <input type="file" id="new_student_csv_file" onchange="validateStudentCSVUploadBut()" accept=".csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" name="new_student_image_file">
                    <button id="add_students_by_csv_but" onclick='add_studentsByCSV();'>Add students</button>

                </fieldset>
                <table style="margin: 0; padding: 0; width: 100%">
                    <tr><th><?php print($stringlib->get_string('osce_session_available_participants')); ?></th><th>Available sites</th><th><?php print($stringlib->get_string('osce_session_assigned_participante')); ?></th></tr>
                    <tr>
                        <td style="vertical-align: top; width: 33%;">
                            Search by name  <input name="studentselect"  id="studentselect" style="" placeholder="Type to search" onkeyup="find_studentsByName($('#studentselect').val());" type="text">
                            <img id="studentfeedback" src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>icons/dots16.gif">
<!--                            <input type='image' src='<?php print("{$CFG->wwwroot}{$CFG->basedir}icons/find.png"); ?>' BORDER='0' style='vertical-align: text-bottom;' onclick='find_studentsByName();
                return false;'/>-->

                            <hr/>

                            <div id="available_students_div" style="height: 500px; overflow-y:scroll;" >
                                <table id="available_students_table" >

                                </table>
                            </div>
                        </td>
                        <td style="vertical-align: top; width: 33%;">
                            <label for="site_selector">Select site</label><br/>
                            <select id="site_selector" style="width:100%">  
                                <?php print($enumLib->getSiteLookup()); ?>
                            </select>
                            <br/>
                            <br/>
                            <button id="assign_but" onclick="assignSelectedStudents()" style="width:100%">Assign</button>
                        </td>
                        <td style="vertical-align: top; width: 33%;">
                            <div id="selectedstudents_div" style="width: 100%; height: 500px; overflow-y:scroll;">
                                <table id="selectedstudents_table" >

                                </table>

                            </div>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </div>
        <?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments)) { ?>
            <div id="import_exam_dialog">
                <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('import_exam')); ?></legend>
                    <div>
                        <label for="import_unit"><?php print($stringlib->get_string('osce_session_unit')); ?></label><br/>
                        <select id="import_unit" required="required" name="unit" onchange="validate_form('import_exam_dialog', '<?php print($stringlib->get_string('import_osce_session_btn_lbl')); ?>');"><option value="-1">Select a <?php print($stringlib->get_string('osce_session_unit')); ?>...</option><?php print($unitsStr); ?></select><br/>
                        <label for="import_instance_owner"><?php print($stringlib->get_string('osce_session_owner')); ?></label><br/>
                        <select id="import_instance_owner" required="required" name="instance_owner" onchange="validate_form('import_exam_dialog', '<?php print($stringlib->get_string('import_osce_session_btn_lbl')); ?>')"><option value="-1">Select a user...</option><?php print($usersStr); ?></select><br/>
                        <label for="exam_import_file" required="required" id="new_file_upload_label">Exam file for import</label><br/>
                        <input type="file" id="exam_import_file" accept=".xml" name="exam_import_file">
                    </div>
                </fieldset>          
            </div>

            <div id="clone_exam_dialog">
                <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('clone_exam')); ?></legend>
                    <div>
                        <label for="clone_instance_owner"><?php print($stringlib->get_string('osce_session_owner')); ?></label><br/>
                        <select id="clone_instance_owner" required="required" name="instance_owner" onchange="validate_form('clone_exam_dialog', '<?php print($stringlib->get_string('clone_osce_session_btn_lbl')); ?>')"><option value="-1">Select a user...</option><?php print($usersStr); ?></select><br/>
                    </div>
                </fieldset>          
            </div>
        <?php } ?>

        <!-- choose output format-->
        <?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments)) { ?>
            <div id="output_dialog">
                <select id="output_select" style="width: 100%"><option value='pdf'>PDF</option><option value='word'>Word</option></select>
            </div>

        <?php } ?>


        <!-- help-->
        <div id="help_dialog">

        </div>

        <div id="info_dialog">

        </div>
        <!-- Waiting-->
        <div id="waiting_dialog">
            <p style="text-align: center"><img src="https://corvid.une.edu.au/eOSCE//icons/ajax-loader.gif" style="vertical-align: middle"/><br/><span id="waitmessage">Please wait...</span></p>
        </div>



    </body>
</html>

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
$increment = 100;

// get completed examinations


$listTableStr = ""; //<button id='refreshbut' onclick='location.reload(true)'>Refresh</button>";


$completedExams = simplexml_load_string($enumlib->getCompletedExamInstances($increment, $from, $datefrom, $dateto));

if ($completedExams->count > 0) {
    $listTableStr .= "<table><tr><th>{$stringlib->get_string('osce_session_completed_date')}</th><th>{$stringlib->get_string('osce_session')}</th><th>Enrolled student count</th><th>Completed examinations</th><th>Remaining</th></tr>";

    foreach ($completedExams->instance as $instance) {

        $listTableStr .= "<tr><td> {$instance->exam_endtimestamp}</td><td><a href='detail/index.php?id={$instance->id}'>{$instance->name}</a></td><td>{$instance->enrolmentcount}</td><td>{$instance->completedcount}</td><td><a href='javascript:void(0)' onclick='showMissingStudents({$instance->id});'>{$instance->remainingcount}</a></td></tr>";
    }
    $listTableStr .= "</table>";

    $buttonStr = '<div id="nav_div" style="float: left">';
    $buttonStr .= '<button id="first" style="float: left" ' . (($from == 0) ? 'disabled="disabled"' : '') . '  onclick="goFirst()">First</button>';
    $buttonStr .= '<button id="prev" onclick="goPrev()" style="float: left" ' . (($from == 0) ? 'disabled="disabled"' : '') . '>Prev ' . $increment . '</button>';
    $buttonStr .= '<button id="next" onclick="goNext()" style="float: left" ' . ((($from + $increment) > ($completedExams->count)) ? 'disabled="disabled"' : '') . '>Next ' . $increment . '</button>';
    $buttonStr .= '<button id="last" style="float: left" ' . ((($from + $increment) > ($completedExams->count)) ? 'disabled="disabled"' : '') . ' onclick="goLast()">Last</button><br/></div>';
    $buttonStr .="<div>{$stringlib->get_string('osce_sessions_count')}:{$completedExams->count}</div>";
} else {
    //$listTableStr = '<hr/><h3>There are no finished exam results</h3>';
    $listTableStr = '<hr/>';
}

//$actionStr = "<a href='examinat'>{$stringlib->get_string('system_users_setup')}</a><p/><a href='students/'>{$stringlib->get_string('participants_setup')}</a><p/><a href='eOSCE_setup/'>{$stringlib->get_string('eosce_setup')}</a>";
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


            var activeSessionsTableStr = "";
            var activeSessionsHeaderStr = "<tr class='header_row'><th><?php print($stringlib->get_string('osce_session_start_date_time')); ?></th><th><?php print($stringlib->get_string('osce_session')); ?></th><th>Enrolled student count</th><th>Completed examinations</th><th>Remaining</th></tr>"
            var missingStudentsHeaderStr = "<tr class='header_row'><th>Student ID</th><th>Name</th></tr>"

            // the web backend service URL
            var serviceurl = '<?php print($CFG->serviceURL); ?>';
            var currentsite = -1;
            //  Define friendly data store name
            var dataStore = window.sessionStorage;
            $(document).ready(function () {

                $("#waiting_dialog").dialog({autoOpen: false, modal: true,
                    open: function (event, ui) {
                        $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                        $(".ui-dialog-titlebar", $(this).parent()).hide();
                        $(".ui-resizable-handle", $(this).parent()).hide();
                    }
                });

                $("#loader").hide();


                $("#refreshbut").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-refresh"
                    }
                });
                $("#refreshbut0").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-refresh"
                    }
                });

                //  Start magic!
                try {
                    // getter: Fetch previous value
                    var currentsite = dataStore.getItem(site);
                } catch (e) {
                    // getter: Always default to first tab in error state
                    currentsite = -1;
                }
                $("#site").select2({val: currentsite});
                // refresh the current data
                window.setInterval(function () {
                    refreshActiveSessions();
                }, 10000);
                refreshActiveSessions();


                $("#missingstudentsreport").dialog({autoOpen: false, modal: true, width: 500,
                    buttons: [
                        {text: "Ok", click: function () {
                                $(this).dialog("close");
                            }}]
                });
                //  $("#active_sessions_table").html(activeSessionsHeaderStr + activeSessionsTableStr);
            });


            function refreshActiveSessions() {
                $("#loader").show();

                var dataObj = new Object();
                dataObj = {action: 'getactiveexamdata', site: currentsite,
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
                            $("#loader").hide();
                            activeSessionsTableStr = "";
                            $(xml).find('instance').each(function () {
                                activeSessionsTableStr += "<tr><td>" + $(this).find('exam_starttimestamp').text() + "</td><td><a href='javascript:window.location.assign(\"detail/index.php?id=" + $(this).find('id').text() + "\");'>" + $(this).find('name').text() + "</a></td><td>" + $(this).find('enrolmentcount').text() + "</td><td>" + $(this).find('completedcount').text() + "</td><td><a href='javascript:void(0)' onclick='showMissingStudents(" + $(this).find('id').text() + ");'>" + $(this).find('remainingcount').text() + "</a></td></tr>"
                            });
                            $("#active_sessions_table").html(activeSessionsHeaderStr + activeSessionsTableStr);
                        }
                    }
                });
            }

            function showMissingStudents(id) {
                waitOn();
                console.log('showMissingStudents');
                var dataObj = new Object();
                dataObj = {action: 'liststudentsbysearchstrforform', site: currentsite,
                    formid: id,
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
                        console.log('showMissingStudents says success');
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
                            alert('showMissingStudents failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            //eraseCookie('token');
                            //window.location = "../index.php";
                        } else {

                            missingStudentsTableStr = "";
                            $(xml).find('student').each(function () {
                                missingStudentsTableStr += "<tr><td>" + $(this).find('studentnum').text() + "</td><td>" + $(this).find('fname').text() + " " + $(this).find('lname').text() + "</td></tr>"
                            });
                            $("#missingtable").html(missingStudentsHeaderStr + missingStudentsTableStr);

                              $("#missingstudentsreport").dialog('open');
                        }
                    }
                });
            }

            function filterBySite() {
                currentsite = $("#site").val();
                dataStore.setItem(site, currentsite)
                refreshActiveSessions();
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
                            <div class="contentinnerheader">
                                Summary of Current Examinations
                            </div>
                            <h3> <?php print($stringlib->get_string('active_osce_sessions')); ?><img id='loader' src='../icons/dots16.gif'/></h3>
                            Filter by site: <select onchange='filterBySite()' id='site' style='width:300px'><option value='-1'>All</option>  <?php print($enumlib->getSiteCodeLookup()); ?></select>
                            <table id='active_sessions_table'></table>


                            <h3> <?php print($stringlib->get_string('completed_osce_sessions')); ?></h3>

                            <?php print($listTableStr); ?>
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

        <div id="missingstudentsreport" title="Remaining">
            <table id="missingtable"></table>
        </div>

    </body>
</html>

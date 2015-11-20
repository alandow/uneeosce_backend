<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
include './backend/lib/authlib.php';
include './backend/lib/StringLib.php';
include './backend/lib/EnumLib.php';
include './backend/lib/Mobile_Detect.php';
require_once('backend/config.inc.php');


if (isset($_REQUEST['logout'])) {
    setcookie('uneeoscetoken', "", -3600);
//do redirect in Java?
    print("<script>window.location.assign('./login.php');</script>");
//header("Location: ./login.php");
    exit();
}


if (isset($_COOKIE['uneeoscetoken'])) {

    $token = $_COOKIE['uneeoscetoken'];
    $authlib = new authlib();
    $authresult = '';
    $stringlib = new StringLib();

    $loggedinuserdata = new SimpleXMLElement($authlib->getDetailsByToken($token));

    if (strlen($loggedinuserdata->error) > 1) {
// print_r($loggedinuserdata);
        setcookie('uneeoscetoken', "", -3600);
        header("Location: ./login.php");
        exit();
    }
} else {

    header("Location: ./login.php");
    exit();
}

$mobiledetect = new Mobile_Detect();


$CFG->site_root = realpath(dirname(__FILE__));


$headerStr = "<div class='header-wrapper' style=''>
        <div style='position:absolute; left:10px; top:10px'><a href='javascript:window.location.assign(\"{$CFG->wwwroot}{$CFG->basedir}index.php?logout\");' >Log Out</a></div>";
if ($CFG->wwwroot == "https://srm-itd01/") {
    $headerStr.="DEV: {$CFG->sysname} " . ($CFG->istrainingsite ? ' (TRAINING)' : '') . " </span><br/>";
} else {
    $headerStr.="PROD: {$CFG->sysname} " . ($CFG->istrainingsite ? ' (TRAINING)' : '') . "</span><br/>";
}
$headerStr.=" <span id='user_feedback' style='width: 80%'>";
$headerStr.= $loggedinuserdata->name . "</span></div>";

$examActionStr = "";

// determine if user has the ability to conduct an assessment
if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_conduct_assessment)) {
// are they using an iPad? then they should download the app.
    if ($mobiledetect->isiOS()) {
        $examActionStr .= "<br/><button onclick='startApp()' class='actionbut'>Start UNE eOSCE app</button><p/>";
        $examActionStr.="Don't have the app?<br/><a href='https://geo.itunes.apple.com/au/app/une-eosce/id930335514?mt=8&uo=6' target='itunes_store' style='display:inline-block;overflow:hidden;background:url(http://linkmaker.itunes.apple.com/images/badges/en-us/badge_appstore-lrg.png) no-repeat;width:165px;height:40px;@media only screen{background-image:url(http://linkmaker.itunes.apple.com/images/badges/en-us/badge_appstore-lrg.svg);}'></a>";
    } else {
        $enumlib = new EnumLib();
        $availableExams = simplexml_load_string($enumlib->getExamsForAssessor($loggedinuserdata->userID));
        if ($availableExams->instance->count() > 0) {
            $examActionStr.= "<h3>{$stringlib->get_string('assessor_available_examinations')}</h3>";
            foreach ($availableExams->instance as $instance) {
                $examActionStr .= "<button onclick='window.location=\"app/?id={$instance->id}\"' class='actionbut'>{$instance->name}</button><p/>";
            }
        }
    }
}

// show an overview of running assessments if permitted
if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_assessments)) {
    $enumlib = new EnumLib();
    $availableExams = simplexml_load_string($enumlib->getActiveExamInstances());
    if ($availableExams->instance->count() > 0) {
        $examActionStr.= "<h3>{$stringlib->get_string('running_examinations')}</h3>";
        foreach ($availableExams->instance as $instance) {
            $examActionStr .= "<table id='active_sessions_table'></table>";
        }
    } else {
        $examActionStr .= "<p/>No examinations running";
    }
}

$menuStr = "";

$adminStr = "";

// build a menu
if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_assessments) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_reports)) {
    $menuStr .= "<li class='showing'><label class='tree-toggle nav-header'>Examination Management</label><ul class='nav nav-list tree'>";
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_assessments)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='./icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('participants_setup_help')}\"); return false;'/></div>"
                . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='javascript:window.location.assign(\"sessions/\")'>{$stringlib->get_string('eosce_setup')}</a></div>"
                . "</div></li>";
    }
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_reports)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='./icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('reports_help')}\"); return false;'/></div>"
                . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='javascript:window.location.assign(\"reports/\")'>{$stringlib->get_string('reports_index_label')}</a></div>"
                . "</div></li>";
    }
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_assessments)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='./icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('osce_archive_help')}\"); return false;'/></div>"
                . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='javascript:window.location.assign(\"archive/\")'>{$stringlib->get_string('eosce_archive')}</a></div>"
                . "</div></li>";
    }
    $menuStr .="</ul><li>";

    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_system_users) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_strings)) {
  $menuStr .= "<li class='showing'><label class='tree-toggle nav-header'>{$stringlib->get_string('system_administration_label')}</label>";
    $menuStr .= "<ul class='nav nav-list tree'>"
            . "<li><div style='vertical-align: middle; display:table;'>"
            . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_users_setup_help')}\"); return false;'/></div>"
            . "<div style='float:left; display:table-cell; vertical-align:middle'><a href='{$CFG->wwwroot}{$CFG->basedir}admin/users'>{$stringlib->get_string('system_users_setup')}</a></div>"
            . "</div></li>";
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_students)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='./icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('participants_setup_help')}\"); return false;'/></div>"
                . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='{$CFG->wwwroot}{$CFG->basedir}students'>{$stringlib->get_string('participants_setup')}</a></div>"
                . "</div></li>";
    }
    $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
            . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_criteria_setup_help')}\"); return false;'/></div>"
            . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='{$CFG->wwwroot}{$CFG->basedir}admin/criteriatypes'>{$stringlib->get_string('string_criteria_types_label')}</a></div>"
            . "</div></li>";
    $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
            . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_labels_help')}\"); return false;'/></div>"
            . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='{$CFG->wwwroot}{$CFG->basedir}admin/strings'>{$stringlib->get_string('string_management_form_label')}</a></div>"
            . "</div></li>";
    $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
            . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_lookups_help')}\"); return false;'/></div>"
            . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='{$CFG->wwwroot}{$CFG->basedir}admin/lookups'>{$stringlib->get_string('system_lookups_form_label')}</a></div>"
            . "</div></li>";

    $menuStr .="</ul></li>";
    }
}
?>
<html>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=10"/>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
        <link type="text/css" href="css/jquery-ui.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="css/eOSCE.css" />
        <script src="js/jquery-2.0.3.min.js"></script>
        <script src="js/jquery-ui.min.js"></script>
        <script>

            // some variables
            // the web backend service URL
            var serviceurl = '<?php print($CFG->serviceURL); ?>';

        // check for app installation, is assessor and is on an ipad and has a current assessment

        var appurl = 'uneeosce://?token=<?php print($token); ?>&config=<?php print(urlencode($CFG->serviceURL)); ?>';

        function startApp() {
            document.location = appurl;
            //alert('about to launch');
            timeout = setTimeout(function () {

            }, 1000);

        }

            /**
             * initiate application
             */

// I think that this fixes the navigation in IOS Safari
            var a = document.getElementsByTagName("a");
            for (var i = 0; i < a.length; i++) {
                if (!a[i].onclick && a[i].getAttribute("target") != "_blank") {
                    a[i].onclick = function () {
                        window.location = this.getAttribute("href");
                        return false;
                    }
                }
            }

// initial function
            $(document).ready(function () {

                $('.tree-toggle').click(function () {
                    $(this).parent().children('ul.tree').toggle(200, function () {
                        $(this).parent().removeClass($(this).parent().hasClass('showing') ? 'showing' : 'hidden');
                        $(this).parent().addClass(($(this).css('display') == 'none') ? 'hidden' : 'showing')
                        console.log('toggling');
                    });

                });

                // set up the login dialog
                $("#waiting_dialog").dialog({autoOpen: false, modal: true,
                    open: function (event, ui) {
                        $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                        $(".ui-dialog-titlebar", $(this).parent()).hide();
                        $(".ui-resizable-handle", $(this).parent()).hide();

                    }

                });

                $(".actionbut").button().css({width: '100%'});

                window.setInterval(function () {
                    refreshActiveSessions();
                }, 10000);

                refreshActiveSessions();
            });

            var activeSessionsHeaderStr = "<tr class='header_row'><th><?php print($stringlib->get_string('osce_session_start_date_time')); ?></th><th><?php print($stringlib->get_string('osce_session')); ?></th><th>Enrolled student count</th><th>Completed examinations</th><th>Remaining</th></tr>"
            function refreshActiveSessions() {
                $("#loader").show();

                var dataObj = new Object();
                dataObj = {action: 'getactiveexamdata', site: '-1',
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
                            alert('create session failed:' + $(xml).find('error').text());
                           
                            window.location = "./index.php?logout";
                        } else {
                            activeSessionsTableStr = "";
                            $(xml).find('instance').each(function () {
                                activeSessionsTableStr += "<tr><td>" + $(this).find('exam_starttimestamp').text() + "</td><td>" + $(this).find('name').text() + "</td><td>" + $(this).find('enrolmentcount').text() + "</td><td>" + $(this).find('completedcount').text() + "</td><td>" + $(this).find('remainingcount').text() + "</td></tr>"
                            });
                            $("#active_sessions_table").html(activeSessionsHeaderStr + activeSessionsTableStr);
                        }
                    }
                });
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
                        $(this).parent().find('.ui-dialog-titlebar').html("<img src='icons/gtk-dialog-question48.png' style='vertical-align:middle'>Help");
                    }
                }).html("<fieldset><div>" + helpStr + "</div></fielset>");
            }

            function isie() {
                return (/MSIE (\d+\.\d+);/.test(navigator.userAgent));
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
                            <div style="display: table-cell;"><img  src="icons/eoscelogoune.png"></div><div style="display: table-cell; vertical-align: bottom;" class="headertitle">:<?php print($CFG->sysname); ?></div>
                        </div>
                    </div>
                </div>
                <div class="breadcrumbs">Home</div>
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
                            <?php print($examActionStr); ?>
                        </div>
                    </div>

                </div>

            </div>
        </div>


        <!-- Waiting-->
        <div id="waiting_dialog">
            <p style="text-align: center"><img src="icons/ajax-loader.gif" style="vertical-align: middle"/><br/>Please wait...</p>
        </div>


    </body>
</html>

<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<?php
include '../../backend/lib/authlib.php';
include '../../backend/lib/StringLib.php';
include '../../backend/lib/EnumLib.php';
include '../../backend/lib/ReportsLib.php';
require_once('../../backend/config.inc.php');

$enumlib = new EnumLib();
$authlib = new authlib();
$stringlib = new StringLib();
$reportlib = new ReportsLib();

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
}
$reportTableStr = '';

//$stringlib
if (isset($_REQUEST['id'])) {
    $reportXML = simplexml_load_string($reportlib->getSummaryReportForExamInstance($_REQUEST['id']));
    //print_r($reportXML);
    $reportTable = "<p>Date:{$reportXML->summary->examdate}</p>";
    $reportTableStr .= "<table id='resultstable'><thead><th>View</th><th class='headerSortable'>Student Name</th><th class='headerSortable'>Student ID</th><th class='headerSortable'>Exam Date/Time</th><th class='headerSortable'>Site</th><th class='headerSortable'>Score</th><th class='headerSortable'>Overall Rating</th><th class='headerSortable'>Additional Rating</th><th class='headerSortable'>Examiner</th><th>Signature</th></th></thead><tbody>";
    foreach ($reportXML->session as $session) {
        $additional_rating = '';
        switch ($session->additional_rating) {
            case '2':
                $additional_rating = 'Excellent';
                break;
            case '1':
                $additional_rating = 'Expected Standard';
                break;
            case '0':
                $additional_rating = 'Marginal Pass';
                break;
            default:
                $additional_rating = '';
                break;
        }
        $sitedata = simplexml_load_string($enumlib->getSiteByID($session->siteid));

        // work out any modifications feedback
        $titlestr = "";
        if (($session->modifycount > 0) || ($session->moderatecount > 0) || ($session->lastmodifiedby > 0) || ($session->lastmoderatedby > 0)) {
            $titlestr = "title=";
            //print_r ($enumLib->getUserByID($session->lastmodifiedby));
            $moderatedstr = ($session->moderatecount > 0 ? "Moderated" : "");
            $modifystr = ($session->modifycount > 0 ? "Changed" : "");
            $titlestr.="'$modifystr" . (((strlen($modifystr) > 0) && (strlen($moderatedstr) > 0)) ? ', ' : '') . $moderatedstr . "'";
        }
        $reportTableStr .= "<tr $titlestr class='" . (($session->modifycount > 0 || $session->lastmodifiedby > 0) ? 'modified' : "") . " " . (($session->moderatecount > 0 || $session->lastmoderatedby > 0) ? 'moderated' : "") . "'><td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-print-preview.png' BORDER='0' style='vertical-align: text-bottom;' onclick='openExamDetailForSession({$session->id}); return false;'/></td>";
        $reportTableStr .= "<td>{$session->fname} {$session->lname}</td><td>{$session->studentnum}</td>";
        $reportTableStr .= "<td>{$session->datetime}</td>";
        $reportTableStr .= "<td>{$sitedata->code}</td>";
        $reportTableStr .= "<td>{$session->score}/{$session->total}</td>";
        $reportTableStr .= "<td>" . (($session->overall_rating == 1) ? 'S' : 'NS') . "</td>";
        $reportTableStr .= "<td>" . (($session->overall_rating == 1) ? $additional_rating : "n/a") . "</td>";
        $reportTableStr .= "<td>{$session->examiner}</td>";
        $reportTableStr .= "<td><img src=\"{$CFG->serviceURL}?action=showsignatureimage&session_ID={$session->id}&token={$token}\"/></td>";
        $reportTableStr .= "</tr>";
    }

    $reportTableStr .= "</tbody></table><br/></p>";
    

    $criteriaXML = $enumlib->getCriteriaScaleItems($reportXML->summary->scale_id[0]);

    // print_r($reportXML->summary->scale_id);
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
        <script src="<?php print($CFG->wwwroot . $CFG->basedir); ?>js/jquery.tablesorter.min.js"></script>
        <script src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/utils.js"></script>
        <script src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/tinymce/jquery.tinymce.min.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/select2.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/icheck.min.js"></script>
        <title></title>
        <script>
            $(document).on('focusin', function (e) {
                if ($(e.target).closest(".mce-window").length) {
                    e.stopImmediatePropagation();
                }
            });
            var serviceurl = '<?php print($CFG->serviceURL); ?>';
            // var oldcss;
            var currentSelectedID = -1;
            var edited = false;
            var moderated = false;
            var oldcss;
            $(document).ready(function () {
                oldcss = $(".ui-widget-header").css('background');
                // set up the waiting feedback dialog
                $("#waiting_dialog").dialog({autoOpen: false, modal: true,
                    open: function (event, ui) {
                        $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                        $(".ui-dialog-titlebar", $(this).parent()).hide();
                        $(".ui-resizable-handle", $(this).parent()).hide();
                    }
                });
                $("#inidividual_report_dialog").dialog({autoOpen: false, modal: true, width: 500,
                    buttons: [
                        {text: "Show as PDF", click: function () {
                                var win = window.open(serviceurl + '?action=getreportforsessionaspdf&token=<?php print($token); ?>&session_ID=' + currentSelectedID, '_blank');
                                win.focus();
                            }},
                        {text: "Ok", click: function () {
                                $(this).dialog("close");
                                $(".ui-dialog:visible").find(".ui-widget-header").css('background', oldcss);
                                if (edited || moderated) {
                                    location.reload();
                                }
                            }}]
                });
                //itOff();
                $('button').button();
                $(".genericdialog").dialog({autoOpen: false, modal: true})
                $("#logfeedback").dialog({autoOpen: false});
                $("#resultstable").tablesorter({
                    // pass the headers argument and assing a object 
                    headers: {
                        // assign the secound column (we start counting zero) 
                        0: {
                            // disable it by setting the property sorter to false 
                            sorter: false
                        },
                        // assign the third column (we start counting zero) 
                        9: {
                            // disable it by setting the property sorter to false 
                            sorter: false
                        }
                    }
                });
                $("#resultstable").tooltip();
                $(".inp-checkbox").each(function (i) {
                    // as previously noted, we asign this function to a variable in order to get the return and console log it for your future vision!
                    var newCheckBox = createCheckBox($(this), i, $(this).attr('id'));
                });
                waitOff();
            });

            // open a specific session to inspect
            function openExamDetailForSession(sessionID) {
                waitOn();
                var dataObj = new Object();
                currentSelectedID = sessionID;
                dataObj = {action: 'getreportforsession', session_ID: sessionID, token: '<?php print($token); ?>'};
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
                        //alert(data);
                        // console.log((new XMLSerializer()).serializeToString(data));
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }


                        if ($(xml).find('error').length > 0) {

                            alert('Listing failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {
                            var name = $(xml).find('overview').first().find('studentname').text();
                            switch ($(xml).find('overview').first().find('additional_rating').text()) {
                                case '2':
                                    additional_rating = 'Excellent';
                                    break;
                                case '1':
                                    additional_rating = 'Expected Standard';
                                    break;
                                case '0':
                                    additional_rating = 'Marginal Pass';
                                    break;
                                default:
                                    additional_rating = '';
                                    break;
                            }
                            var criteriaDefXML = $.parseXML('<?php print($criteriaXML); ?>');
                            // console.log(criteriaDefXML);
                            var currentQuestionScore = 0;
                            var listStr = "<strong>Student Name: </strong>" + name + "<br/>";
                            listStr += "<strong>Student Number: </strong>" + $(xml).find('overview').first().find('studentnum').text() + "<br/>";
                            //listStr += "<strong>Exam date: </strong>" + $(xml).find('question').first().find('datetime').text() + "<br/>";
                            // include this using php if appropriate permissions exist
                            listStr += "<fieldset><span class='edit_item_td' id='editoverviewbuttonspan'><input type='image' src='<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>icons/gtk-edit.png' BORDER='0' style='vertical-align: text-bottom;' onclick='editOverview(" + $(xml).find('overview').first().find('sessionid').text() + "); return false;'/></span><br/>";
                            if (($(xml).find('overview').first().find('moderated_by').text().length > 0) || ($(xml).find('overview').first().find('modified_by').text().length > 0)) {
                                listStr += "<fieldset><input type='image' src='<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>icons/dialog-information.png' BORDER='0' style='vertical-align: text-bottom;' onclick='showOverviewHistory(" + $(xml).find('overview').first().find('sessionid').text() + "); return false;'/></span><br/>";
                            }
                            listStr += "<strong>Overall Rating: </strong><span id='detail_overall_rating'>" + $(xml).find('overview').first().find('overall_rating').text() + "</span><br/>";
                            listStr += "<strong>Additional Rating: </strong><span id='detail_additional_rating'>" + $(xml).find('overview').first().find('additional_rating').text() + "</span><br/>";
                            listStr += "<strong>Comments: </strong><br/><span id='detail_comment'>" + $(xml).find('overview').first().find('comments').text() + "</span><br/>";
                            listStr += "</fieldset>";
                            listStr += "<table><th>Question</th><th>Result</th><th>Comments</th><th class='edit_item_td' >Update</th></tr>";
                            $(xml).find('questiondata').first().find('question').each(function () {
//                                switch ($(this).find('type').text()) {
                                //case '0':
                                listStr += "<tr id='tr_" + $(this).find('answerid').text() + "' class='" + ($(this).find('modified').text().length > 1 ? "modified " : "") + ($(this).find('moderated').text().length > 1 ? "moderated " : "") + "'><td>";
                                listStr += ($(this).find('modified').text().length > 1 || $(this).find('moderated').text().length > 1) ? "<input type='image' src='<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>icons/dialog-information.png' onclick='showItemHistory(" + $(this).find('answerid').text() + ")'/>" : "";
                                listStr += $(this).find('text').text() + "</td>";
                                listStr += "<td id='a_" + $(this).find('answerid').text() + "'>";
                                currentQuestionScore = $(this).find('answer').text();
                                //    console.log('currentQuestionScore is'+currentQuestionScore);
                                $(criteriaDefXML).find('item').each(function () {
                                    if ($(this).find('value').text() == currentQuestionScore) {
                                        listStr += $(this).find('short_description').text();
                                    }
                                })
                                listStr += "</td><td id='c_" + $(this).find('answerid').text() + "'>" + $(this).find('comment').text() + "</td>";
                                listStr += "<td class='edit_item_td'><input type='image' src='<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>icons/gtk-edit.png' BORDER='0' style='vertical-align: text-bottom;' onclick='editItem(" + $(this).find('answerid').text() + "); return false;'/></td></tr>";
                            });
                            listStr += "</table>";
                            var wWidth = $(window).width();
                            var dWidth = wWidth * 0.9;
                            $("#inidividual_report_fieldset").html(listStr);
                            $("#inidividual_report_dialog").dialog({
                                autoOpen: true,
                                modal: true,
                                width: dWidth,
                                open: function () {
                                    $(".ui-dialog:visible").find(".ui-widget-header").css("background", oldcss);
                                },
                                close: function () {
                                    $(this).dialog("close");
                                    $(".ui-dialog:visible").find(".ui-widget-header").css('background', oldcss);
                                    if (edited || moderated) {
                                        location.reload();
                                    }
                                    $("#inidividual_report_dialog").dialog('option', 'title', '');
                                    $('.edit_item_td').hide();
                                }
                            });
                        }


                    }
                });
            }

////////////////////////////////////////////////////////////
//  moderation and modification
//  ///////////////////////////////////////////////////////


// Action to edit a specific item in a result set
            function editItem(id) {
                $("#update_entry_result").find("option").filter(function () {
                    return (($(this).val() == $("#a_" + id).text()) || ($(this).text() == $("#a_" + id).text()))
                }).prop('selected', true);
                $("#update_entry_comment").val($("#c_" + id).text());
                $("#update_reason_text").val('');
                $("#update_entry_dialog").dialog({
                    title: (editing ? 'Change item' : 'Moderate item'),
                    modal: true,
                    autoOpen: true,
                    buttons: {
                        "OK": function () {
                            $(this).dialog("close");
                            waitOn();
                            dataObj = {action: (editing ? 'modifyassessmentitem' : 'moderateassessmentitem'),
                                id: id,
                                value: $("#update_entry_result").val(),
                                reason: $("#update_reason_text").val(),
                                comment: $("#update_entry_comment").val(),
                                userid: <?php print($loggedinuserdata->userID); ?>,
                                token: '<?php print($token); ?>'};
                            $.ajax({
                                url: serviceurl,
                                type: 'post',
                                data: dataObj,
                                dataType: isie() ? "text" : "xml",
                                error: function (jqXHR, textStatus, errorThrown) {
                                    waitOff();
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
                                        alert('altering result failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                    } else {

                                        $("#a_" + id).text(($("#update_entry_result").val() == "1") ? "S" : "NS");
                                        $("#c_" + id).text($("#update_entry_comment").val());
                                        edited = true;
                                    }
                                }
                            });
                        },
                        Cancel: function () {
                            $(this).dialog("close");
                        }
                    }
                }
                );
                validate_update_entry();
            }

            // show the item history of an item
            function showItemHistory(id) {
                waitOn();
                var dataObj = new Object();
                dataObj = {action: 'getitemhistory', id: id, token: '<?php print($token); ?>'};
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
                        //alert(data);
                        // console.log((new XMLSerializer()).serializeToString(data));
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }


                        if ($(xml).find('error').length > 0) {

                            alert('operation failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {

                            var listStr = "<strong>Item History: </strong>" + name + "<br/>";
                            listStr += "<table><th>Changed at</th><th>Changed by</th><th>Change type</th><th>Old value</th><th>New value</th><th>Old comment</th><th>New comment</th><th>Reason</th></tr>";
                            $(xml).find('item').each(function () {
                                listStr += "<tr><td>" + $(this).find('datetime').text() + "</td>";
                                listStr += "<td>" + $(this).find('changed_by').text() + "</td>";
                                listStr += "<td>" + $(this).find('type').text() + "</td>";
                                listStr += "<td>" + ($(this).find('oldvalue').text() == '1' ? 'S' : 'NS') + "</td>";
                                listStr += "<td>" + ($(this).find('newvalue').text() == '1' ? 'S' : 'NS') + "</td>";
                                listStr += "<td>" + $(this).find('oldcomment').text() + "</td>";
                                listStr += "<td>" + $(this).find('newcomment').text() + "</td>";
                                listStr += "<td>" + $(this).find('description').text() + "</td></tr>";
                            });
                            listStr += "</table>";
                            var wWidth = $(window).width();
                            var dWidth = wWidth * 0.9;
                            $("#history_report_fieldset").html(listStr);
                            $("#itemhistoryfeedback").dialog({
                                autoOpen: true,
                                modal: true,
                                width: dWidth,
                                buttons: {
                                    OK: function () {
                                        $(this).dialog("close");
                                    }
                                }
                            });
                        }


                    }
                });
            }


            // show the item history of an item
            function showOverviewHistory(id) {
                waitOn();
                var dataObj = new Object();
                dataObj = {action: 'getsessionhistory', id: id, token: '<?php print($token); ?>'};
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
                        //alert(data);
                        // console.log((new XMLSerializer()).serializeToString(data));
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }


                        if ($(xml).find('error').length > 0) {

                            alert('operation failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {

                            var listStr = "<strong>Item History: </strong>" + name + "<br/>";
                            listStr += "<table><th>Changed at</th><th>Changed by</th><th>Change type</th><th>Old Overall Rating</th><th>New Overall Rating</th><th>Old Additional Rating</th><th>New Additional Rating</th><th>Old comment</th><th>New comment</th><th>Reason</th></tr>";
                            $(xml).find('item').each(function () {

                                oldadditional_rating = "";
                                newadditional_rating = "";
                                switch ($(this).find('oldadditionalrating').text()) {
                                    case '2':
                                        oldadditional_rating = 'Excellent';
                                        break;
                                    case '1':
                                        oldadditional_rating = 'Expected Standard';
                                        break;
                                    case '0':
                                        oldadditional_rating = 'Marginal Pass';
                                        break;
                                    default:
                                        oldadditional_rating = '';
                                        break;
                                }

                                switch ($(this).find('newadditionalrating').text()) {
                                    case '2':
                                        newadditional_rating = 'Excellent';
                                        break;
                                    case '1':
                                        newadditional_rating = 'Expected Standard';
                                        break;
                                    case '0':
                                        newadditional_rating = 'Marginal Pass';
                                        break;
                                    default:
                                        newadditional_rating = '';
                                        break;
                                }
                                listStr += "<tr><td>" + $(this).find('datetime').text() + "</td>";
                                listStr += "<td>" + $(this).find('changed_by').text() + "</td>";
                                listStr += "<td>" + $(this).find('type').text() + "</td>";
                                listStr += "<td>" + ($(this).find('oldrating').text() == '1' ? 'S' : 'NS') + "</td>";
                                listStr += "<td>" + ($(this).find('newrating').text() == '1' ? 'S' : 'NS') + "</td>";
                                listStr += "<td>" + oldadditional_rating + "</td>";
                                listStr += "<td>" + newadditional_rating + "</td>";
                                listStr += "<td>" + $(this).find('oldcomments').text() + "</td>";
                                listStr += "<td>" + $(this).find('newcomments').text() + "</td>";
                                listStr += "<td>" + $(this).find('description').text() + "</td></tr>";
                            });
                            listStr += "</table>";
                            var wWidth = $(window).width();
                            var dWidth = wWidth * 0.9;
                            $("#history_report_fieldset").html(listStr);
                            $("#itemhistoryfeedback").dialog({
                                autoOpen: true,
                                modal: true,
                                width: dWidth,
                                buttons: {
                                    OK: function () {
                                        $(this).dialog("close");
                                    }
                                }
                            });
                        }


                    }
                });
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
                    of: window
                });
                $("#waitmessage").html(message);
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

                    <div class="actionheader">
                        Reporting
                    </div>    
                    <div class="actioncontent" style="height: auto">
                        <button id='downloadexcelBut' class="actionbutton" onclick="window.open(serviceurl + '?action=getreportforexamasexcel&exam_ID=<?php print("{$_REQUEST['id']}&token={$token}"); ?>', '_blank');"><?php print($stringlib->get_string("download_report_summary_excel")); ?></button>
                        <p></p>
                        <button id='downloadexcelAllBut' class="actionbutton" onclick="window.open(serviceurl + '?action=getreportforallformsasexcel&exam_ID=<?php print("{$_REQUEST['id']}&token={$token}"); ?>', '_blank');"><?php print($stringlib->get_string("download_report_comprehensive_excel")); ?></button>
                        <p></p>
                    </div> 

                    <div class ="navheader">
                        Navigation
                    </div>    
                    <div class="navcontent">

                        <?php print($menuStr); ?>
                    </div>    
                </div>

                <div class="contentcontainer">
                    <div class="contentinner">

                        <div class="contentcontentnostatus">
                            <h3>Results for <?php print($reportXML->summary->name); ?> (Archived)</h3>
                            <?php print($reportTableStr); ?>
                            <?php print($buttonStr); ?>
                        </div>

                    </div>

                </div>

            </div>
        </div>


        <div id="inidividual_report_dialog" style="width: 95%" class='dialog'>


            <fieldset id="inidividual_report_fieldset" style="width:95%; height: 100%"></fieldset>
        </div>

        <div id="logfeedback" title="Results" class='genericdialog'>

        </div>

        <div id="itemhistoryfeedback" title="Item History" style="width: 95%" class='genericdialog'>
            <fieldset id="history_report_fieldset" style="width:95%; height: 100%"></fieldset>

        </div>

        <div id="sessionhistoryfeedback" title="Item History" style="width: 95%" class='genericdialog'>
            <fieldset id="session_history_report_fieldset" style="width:95%; height: 100%"></fieldset>

        </div>



        <div id="waiting_dialog">
            <p style="text-align: center"><img src="https://corvid.une.edu.au/eOSCE//icons/ajax-loader.gif" style="vertical-align: middle"/><br/><span id="waitmessage">Please wait...</span></p>
        </div>


    </body>
</html>

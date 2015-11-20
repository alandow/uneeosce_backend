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
    $reportTable = "<button id='reviewEmailBut' onclick='edit_feedback_stem_popup();'>Review/Edit Feedback Email</button><button id='mailReportBut'>Send Feedback Email</button>
        <p>Date:{$reportXML->summary->examdate}</p>";
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
                        {text: "Update (change) assessment", click: function () {
                                enable_editing();
                            }},
//                        {text: "Moderate assessment", click: function() {
//                                enable_moderating();
//                            }},
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
                $("#email-confirm").dialog({
                    resizable: false,
                    height: 300,
                    width: 400,
                    modal: true,
                    autoOpen: false,
                    buttons: {
                        "Email results": function () {
                            $(this).dialog("close");
                            waitOn();
                            $.ajax({
                                url: serviceurl + '?action=mailfeedbacktoall&exam_ID=<?php print($_REQUEST['id']); ?>&includefinalmark=' + ($("#feedback_include_final_judgement")[0].checked ? 'true' : 'false') + '&token=<?php print($token); ?>',
                                type: 'POST',
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
                                        console.log('update is:' + xhr.responseText);

                                        $("#waitmessage").html(xhr.responseText.split(",")[xhr.responseText.split(",").length - 2]);

                                    }
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    alert(errorThrown);
                                },
                                success: function (data) {
                                    var returnedval = data.split(',');

                                    var xml = returnedval.pop();
                                    // TODO make feedback meaningful: to log perhaps?
                                    $("#logfeedback").html('Sent:' + $(xml).find('logresults').first().find('totalcount').text() + "<br/>Succeeded:" + $(xml).find('logresults').first().find('successcount').text() + "<br/>Failed:" + $(xml).find('logresults').first().find('failcount').text());
                                    $("#logfeedback").dialog('open');
                                    //  alert(data);
                                    waitOff();
                                }
                            });
                        },
                        Cancel: function () {
                            $(this).dialog("close");
                        }
                    }
                });
//                $("#showpdfAllbut").button();
                $("#mailReportBut").button().click(function (event) {
                    $("#email-confirm").dialog('open');
                });
                // $("#edit_email_dialog").dialog({autoOpen: false, modal: true})
                $("#edit_email_dialog").dialog({autoOpen: false, modal: true, title: '<?php print($stringlib->get_string('edit_email_stem')); ?>',
                    width: 600,
                    buttons: {
                        "<?php print($stringlib->get_string('edit_email_stem_btn_lbl')); ?>": function () {
                            $(this).dialog("close");
                            edit_feedback_stem_action();
                        },
                        Cancel: function () {
                            $(this).dialog("close");
                        }
                    }
                });
                $("#send_test_email_dialog").dialog({autoOpen: false, modal: true, title: '<?php print($stringlib->get_string('edit_email_send_test_btn_lbl')); ?>',
                    width: 600,
                    buttons: {
                        "<?php print($stringlib->get_string('email_send_test_btn_lbl')); ?>": function () {
                            $(this).dialog("close");
                            send_test_email();
                        },
                        Cancel: function () {
                            $(this).dialog("close");
                        }
                    }
                });
                $("#reviewEmailBut").button({
                    // nice icon
//                    icons: {
//                        primary: "ui-icon-mail-closed"
//                    }
                    // what happens when the button is clicked
                });
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

// Modification

            var editing = false;
            var moderating = false;
// enable editing of 
            function enable_editing() {
                $("#inidividual_report_dialog").dialog('option', 'title', 'Updating enabled...');
                oldcss = $(".ui-dialog:visible").find(".ui-widget-header").css("background");
                $(".ui-dialog:visible").find(".ui-widget-header").css("background", "#ff0000");
                $('.edit_item_td').show();
                editing = true;
                moderating = false;
            }

//            function enable_moderating() {
//                $("#inidividual_report_dialog").dialog('option', 'title', 'Moderating enabled...');
//                $(".ui-dialog:visible").find(".ui-widget-header").css("background", "#FF8811");
//                $('.edit_item_td').show();
//                moderating = true;
//                editing = false;
//            }

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

// action to edit the overview of a result set
            function editOverview(id) {
                // set the drop down entries
                $("#update_overall_rating").find("option").filter(function () {
                    return (($(this).val() == $("#detail_overall_rating").text()) || ($(this).text() == $("#detail_overall_rating").text()))
                }).prop('selected', true);
                $("#update_additional_rating").find("option").filter(function () {
                    return (($(this).val() == $("#detail_additional_rating").text()) || ($(this).text() == $("#detail_additional_rating").text()))
                }).prop('selected', true);
                $("#update_overall_comment").val($("#detail_comment").text());
                $("#update_overall_reason_text").val('');
                $("#update_overview_dialog").dialog({
                    title: (editing ? 'Change overview' : 'Moderate overview'),
                    modal: true,
                    autoOpen: true,
                    buttons: {
                        "OK": function () {
                            $(this).dialog("close");
                            waitOn();
                            dataObj = {action: (editing ? 'modifyassessmentoverview' : 'moderateassessmentoverview'),
                                id: id,
                                rating: $("#update_overall_rating").val(),
                                additionalrating: $("#update_additional_rating").val(),
                                reason: $("#update_overall_reason_text").val(),
                                comment: $("#update_overall_comment").val(),
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
                                        var additional_rating;
                                        switch ($(xml).find('additional_rating').text()) {
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
                                        $("#detail_overall_rating").text($(xml).find('rating').text() == '1' ? 'Satisfactory' : 'Non-satisfactory');
                                        $("#detail_additional_rating").text($(xml).find('rating').text() == '1' ? additional_rating : '');
                                        $("#detail_comment").text($(xml).find('comment').text());
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


            // Validates the entry form for updating a specific entry within an assessment
            function validate_update_entry() {
                var valid = false;
                if ($("#update_entry_result").val() == '0') {
                    valid = $("#update_entry_comment").val().length > 0;
                } else {
                    valid = true;
                }
                $(".ui-dialog-buttonpane button:contains('OK')").button((($("#update_reason_text").val().length > 3) && (valid)) ? "enable" : "disable");
            }

// Validates the entry form for updating a n overview for an assessment
            function validate_update_overview() {
                var valid = false;
                if ($("#update_overall_rating").val() == 0) {
                    $("#update_additional_rating").hide();
                    $("label[for='update_additional_rating']").hide();
                } else {
                    $("#update_additional_rating").show();
                    $("label[for='update_additional_rating']").show();
                }
                valid = ($("#update_overall_rating").val() == 1) ? $(($("#update_overall_rating").val() > 0) && ($("#update_additional_rating").val().length > 0)) : true;
                $(".ui-dialog-buttonpane button:contains('OK')").button((($("#update_overall_reason_text").val().length > 3) && (valid)) ? "enable" : "disable");
            }
            /**
             * Trigger the pop-up to edit the feedback email stem for this exam. It gets details for the email stem from the database first             
             * @param {type} id
             * @returns {edit_question_popup} */
            function edit_feedback_stem_popup() {
                waitOn();
                dataObj = {action: 'getemailstemdetails',
                    id: <?php print($_REQUEST['id']); ?>,
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
                            alert('Loading Email stem failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {
                            // load tinyMCE

                            // open dialog here
                            $("#edit_email_dialog").dialog('open');
                            $("#edit_email_text").tinymce({
                                script_url: '../../js/tinymce/tinymce.min.js',
                                toolbar: "undo redo | bold italic ",
                                menubar: false
                            });
                            $("#edit_email_text").html($(xml).find('text').first().text());
                        }
                    }
                });
            }

            /**
             * The ajax action for updating an email stem
             * @returns {edit_question_action} */
            function edit_feedback_stem_action() {
                waitOn();
                dataObj = {action: 'updateemailstemdetails',
                    id: <?php print($_REQUEST['id']); ?>,
                    text: $("#edit_email_text").html(),
                    userid: <?php print($loggedinuserdata->userID); ?>,
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
                            alert('Updating email stem failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {
                            //  if (openSendTestAfter) {
                            //$("#send_test_email_dialog").dialog('open');
                            //   } else {
                            $("#edit_email_dialog").dialog('close');
                            //  }
                            //  location.reload(true);
                        }
                    }
                });
            }


            function send_test_email() {
                waitOn();
                dataObj = {action: 'updateemailstemdetails',
                    id: <?php print($_REQUEST['id']); ?>,
                    text: $("#edit_email_text").html(),
                    userid: <?php print($loggedinuserdata->userID); ?>,
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
                            alert('Updating email stem failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {
                            send_test_email_action();
                        }
                    }
                });
            }

            function send_test_email_action(){
                dataObj = {action: 'sendtestemail',
                    id: <?php print($_REQUEST['id']); ?>,
                    address: $("#test_email_address").val(),
                    emailtext: $("#edit_email_text").html(),
                    token: '<?php print($token); ?>'};
                waitOn();
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
                            alert('Send test email stem failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            $("#edit_email_text").tinymce().remove();
                            $("#edit_email_text").tinymce({
                                script_url: '../../js/tinymce/tinymce.min.js',
                                toolbar: "undo redo | bold italic ",
                                menubar: false
                            });
                        } else {
                            alert('Email sent');
                            $("#send_test_email_dialog").dialog('close');
                            $("#edit_email_text").tinymce().remove();
                            $("#edit_email_text").tinymce({
                                script_url: '../../js/tinymce/tinymce.min.js',
                                toolbar: "undo redo | bold italic ",
                                menubar: false
                            });
                        }
                    }
                });
            }

            function validateTestEmail() {
                if (validateEmail($("#test_email_address").val())) {
                    $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('email_send_test_btn_lbl')); ?>')").button("enable");
                } else {
                    $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('email_send_test_btn_lbl')); ?>')").button("disable");
                }
            }

            function validateEmail(email) {
                var re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(email);
            }

            function createCheckBox(ele, i, existingID) {
                //  First I simply create the new ID here, of course you can do this inline, but this gives us a bottleneck for possible errors
                if (!existingID) {
                    var newID = "cbx-" + i;
                } else {
                    newID = existingID;
                }
                //  below we use the param "ele" wich will be a jQuery Element object like $("#eleID")
                //  This gives us the "chainability" we want so we don't need to waste time writing more lines to recall our element
                //  You will also notice, the first thing i do is asign the "attribute" ID
                ele.prop({"type": "checkbox"})
                        //  Here we see "chainability at work, by not closing the last line, we can move right on to the next bit of code to apply to our element
                        //  In this case, I'm changing a "property", keep in mind this is kinda new to jQuery,
                        //  In older versions, you would have used .attr but now jQuery distinguishes between "attributes" and "properties" on elements (note we are using "edge", aka. the latest jQuery version

                        //  .after allows us to add an element after, but maintain our chainability so that we can continue to work on the input
                        // here of course, I create a NEW label and then immidiatly add its "for" attribute to relate to our input ID
                        .after($("<label />").attr({for : newID}))
                        //  I should note, by changing your CSS and/or changing input to <button>, you can ELIMINATE the previous step all together
                        // Now that the new label is added, lets set our input to be a button,
                        .button({text: false}) // of course, icon only
                        //  finally, let's add that click function and move on!
                        //  again, notice jQuery's chainability allows us no need to recall our element
                        .click(function (e) {
                            //  FYI, there are about a dozen ways to achieve this, but for now, I'll stick with your example as it's not far from correct
                            //var toConsole = 
                            $(this).button("option", {
                                icons: {
                                    primary: $(this)[0].checked ? "ui-icon-check" : ""
                                }
                            });
                            // console.log(toConsole, toConsole[0].checked);
                        });
                //  Finally, for sake of consoling this new button creation and showing you how it works, I'll return our ORIGINAL (yet now changed) element
                return ele;
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
<!--                        <p></p>-->
<!--                        <button id = 'showpdfbut' class="actionbutton" onclick = "window.open(serviceurl + '?action=getallreportsforsessionaspdf&exam_ID=<?php print("{$_REQUEST['id']}&token={$token}"); ?>', '_blank');"><?php print($stringlib->get_string('show_all_pdf_reports')); ?></button>-->
                    </div> 
                    <div class="actionheader">
                        Feedback
                    </div>    
                    <div class="actioncontent" style="height: auto">
                        <button id='reviewEmailBut' class="actionbutton" onclick='edit_feedback_stem_popup();'>Review/Edit Feedback Email</button>
                        <p></p>
                        <button id='mailReportBut' class="actionbutton">Send Feedback Email</button>
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
                            <h3>Results for <?php print($reportXML->summary->name); ?></h3>
                            <?php print($reportTableStr); ?>
                        </div>

                    </div>

                </div>

            </div>
        </div>


        <div id="inidividual_report_dialog" style="width: 95%" class='dialog'>


            <fieldset id="inidividual_report_fieldset" style="width:95%; height: 100%"></fieldset>
        </div>

        <div id="email-confirm" title="Really send emails?" class='genericdialog'>
            <p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span>This will send an email containing a report of results to all students in this exam. Are you sure?</p>
            <input class='inp-checkbox' id='feedback_include_final_judgement' name='feedback_include_final_judgement'  style='width: 100%'><?php print($stringlib->get_string('feedback_include_final_judgement')); ?>
        </div>
        <div id="logfeedback" title="Results" class='genericdialog'>

        </div>

        <div id="itemhistoryfeedback" title="Item History" style="width: 95%" class='genericdialog'>
            <fieldset id="history_report_fieldset" style="width:95%; height: 100%"></fieldset>

        </div>

        <div id="sessionhistoryfeedback" title="Item History" style="width: 95%" class='genericdialog'>
            <fieldset id="session_history_report_fieldset" style="width:95%; height: 100%"></fieldset>

        </div>

        <div id="edit_email_dialog">
            <fieldset style="width: 90%">
                <div>
                    <span style="font-size: 11px">Dear (student name here),</span>
                    <textarea id="edit_email_text"  style="width: 100%; height: 100%"> </textarea><br/>
                </div>
                <button onclick="$('#send_test_email_dialog').dialog('open');"><?php print($stringlib->get_string('edit_email_send_test_btn_lbl')); ?></button>
            </fieldset>
        </div>

        <div id="send_test_email_dialog">
            <fieldset style="width: 90%">
                <div>
                    <label for="test_email_address"><?php print($stringlib->get_string('test_email_to')); ?></label>
                    <input type="text" id="test_email_address" onkeyup="validateTestEmail()" style="width: 100%; height: 100%"><br/>
                </div>
            </fieldset>
        </div>

        <div id="update_entry_dialog" class='genericdialog'>
            <fieldset style="width: 90%">
                <label for="update_entry_result">Result</label><br/>
                <select id="update_entry_result" onchange="validate_update_entry()">
                    <?php
                    foreach (simplexml_load_string($criteriaXML)->item as $item) {
                        print("<option value='{$item->value}'>$item->short_description</option>");
                    }
                    ?>
                    <!--                    <option value="1">S</option>
                                        <option value="0">NS</option>-->
                </select><br/>
                <label for="update_entry_comment">Comment</label><br/>
                <textarea id="update_entry_comment" style="width: 100%; height: 100%" onkeyup="validate_update_entry()"></textarea><br/>
                <label for="update_reason_text">Reason (required)</label><br/>
                <textarea id="update_reason_text" style="width: 100%; height: 100%" onkeyup="validate_update_entry()"> </textarea><br/>
            </fieldset>
        </div>

        <div id="update_overview_dialog" class='genericdialog'>
            <fieldset style="width: 90%">
                <label for="update_overall_rating">Overall Rating</label><br/>
                <select id="update_overall_rating" onchange="validate_update_overview()">
                    <option value="1">Satisfactory</option>
                    <option value="0">Not Satisfactory</option>
                </select><br/>
                <label for="update_additional_rating">Additional Rating</label><br/>
                <select id="update_additional_rating" onchange="validate_update_overview()">
                    <option value="2">Excellent</option>
                    <option value="1">Expected Standard</option>
                    <option value="0">Marginal Pass</option>
                </select><br/>
                <label for="update_overall_comment">Comments</label><br/>
                <textarea id="update_overall_comment" style="width: 100%; height: 100%" onkeyup="validate_update_overview()"></textarea><br/>
                <label for="update_overall_reason_text">Reason (required)</label><br/>
                <textarea id="update_overall_reason_text" style="width: 100%; height: 100%" onkeyup="validate_update_overview()"> </textarea><br/>
            </fieldset>
        </div>
        <div id="waiting_dialog">
            <p style="text-align: center"><img src="https://corvid.une.edu.au/eOSCE//icons/ajax-loader.gif" style="vertical-align: middle"/><br/><span id="waitmessage">Please wait...</span></p>
        </div>


    </body>
</html>

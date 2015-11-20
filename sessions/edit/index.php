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
require_once('../../backend/config.inc.php');

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

$from = isset($_REQUEST['from']) ? ($_REQUEST['from'] > 0 ? $_REQUEST['from'] : 0) : 0;
$increment = 10;

$instanceData = simplexml_load_string($enumlib->getExamInstanceOverviewByID($_REQUEST['id']));
$questiondata = simplexml_load_string($enumlib->getQuestionsForSession($_REQUEST['id']));

// is this locked?
$locked = (strval($instanceData->instance[0]->finalised) == 'true');
// exam active?
$active = (strval($instanceData->instance[0]->active) == 'true');
// is it in practice mode?
$practicing = (strval($instanceData->instance[0]->practicing) == 'true');
// is the currently logged on user an admin or the owner?
$isowner = ((strval($loggedinuserdata->userID) == strval($instanceData->instance[0]->owner_id)) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_finalise_other_assessment));

$listTableStr = "<table id='questionstbl'><thead><tr><th style='width:50px;'>Reorder</th>
    <th>Edit</th>
    <th>{$stringlib->get_string('assessment_item_list_type')}<input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/dialog-question.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='showHelp(\"{$stringlib->get_string('item_type_help_string')}\"); return false;'/></th><th>{$stringlib->get_string('assessment_item_list_text')}</th><th>{$stringlib->get_string('assessment_item_list_remove')}</th></tr></thead><tbody>";
foreach ($questiondata->question as $question) {
    $listTableStr .= "<tr class='sortablerow' entryid='{$question->id}'><td class='draghandle'><img src='{$CFG->wwwroot}{$CFG->basedir}/icons/object-flip-vertical.png'/></td>
        <td class='editimg'>
        <input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-edit.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='edit_question_popup(\"{$question->id}\"); return false;'/></td>
        <td>" . ($question->type == '1' ? 'Yes' : 'No') . "</td>
        <td>{$question->text}</td>
        
        ";
    $listTableStr .= "<td class='deletehandle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-cancel.png' BORDER='0' style='vertical-align: text-bottom;' onclick='delete_question({$question->id}); return false;'/></td></tr>";
}
$listTableStr .= "</tbody></table>";

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
        <script src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/tinymce/jquery.tinymce.min.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/select2.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/icheck.min.js"></script>
        <title></title>
        <script>
            var serviceurl = '<?php print($CFG->serviceURL); ?>';

            var questionsFrom = 0;
            var questionsCount = 10;

// workaround for tinyMCE in a dialog
// See http://www.tinymce.com/wiki.php/Tutorials:TinyMCE_in_a_jQuery_UI_dialog
            $(document).on('focusin', function (e) {
                if ($(e.target).closest(".mce-window").length) {
                    e.stopImmediatePropagation();
                }
            });
            $(document).ready(function () {


                // Experimental toolbar



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
                    title: '<img src="<?php print($CFG->wwwroot . $CFG->basedir); ?>icons/dialog-question.png" />Help',
                    buttons: {
                        Ok: function () {
                            $(this).dialog("close");
                        }
                    }
                });
                // turn waiting feedback on
                waitOn();

                // initialise dialogs

                $("#info_dialog").dialog({
                    autoOpen: false, modal: true, width: 600,
                    title: '<img src="<?php print($CFG->wwwroot . $CFG->basedir); ?>icons/dialog-information.png" />Info',
                    buttons: {
                        Ok: function () {
                            $(this).dialog("close");
                        }
                    }
                });
                $("#edit_email_dialog").dialog({autoOpen: false, modal: true})
<?php if (!$locked) { ?>
                    $("#edit_exam_instance_setup").dialog({autoOpen: false, modal: true});

                    $("#new_question_dialog").dialog({autoOpen: false, modal: true});

                    $("#edit_question_dialog").dialog({autoOpen: false, modal: true})
<?php } ?>


                //
                //  upload media dialogue
                //
                $("#upload_dialog").dialog({
                    autoOpen: false, modal: true, width: 600,
                    title: 'Upload media',
                    buttons: {
                        "Upload": function () {
                            file_upload_action();
                            //$(this).dialog("close");
                        },
                        "Cancel": function () {
                            $(this).dialog("close");
                        }
                    }
                });
                ////////////////////////////////////////////////////////////////////////////////
                //  set up buttons
                //////////////////////////////////////////////////////////////////////////////// 



                $("#play").button({
                    icons: {
                        primary: "<?php print($active ? 'ui-icon-pause' : 'ui-icon-play'); ?>"
                    }
                })


                $("#editBut").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-gear"
                    }
                });

                $("#testBut").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-gear"
                    }
                });

                $("#new_assessment_but").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-plusthick"
                    }
                });

                // New exam instance button
                $("#newBut").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-plusthick"
                    }

                    // what happens when the button is clicked
                }).click(function () {
                    add_question_popup();
                });

                $("#saveBut").button({
                    disabled: false,
                    // nice icon
                    icons: {
                        primary: "ui-icon-disk"
                    }

                    // what happens when the button is clicked
                }).click(function () {
                    save_action();
                });


                $("#lockBut").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-locked"
                    }
                    // what happens when the button is clicked
                });


                $("#defineEmailBut").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-mail-closed"
                    }
                    // what happens when the button is clicked
                });

                $("#archiveBut").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-gear"
                    }
                    // what happens when the button is clicked
                });

                $("#exportBut").button({
                    disabled: false,
                    icons: {
                        primary: "ui-icon-gear"
                    }
                    // what happens when the button is clicked
                });

                // initialise dialogs
                $("#question_link_setup").dialog({autoOpen: false, modal: true, width: 800});

                $("#question_preview_popup").dialog({autoOpen: false, modal: true, width: 800})

                // set up the date pickers for start and end dates
                $("#instance_startdate").datepicker();

                $(".inp-checkbox").each(function (i) {
                    // as previously noted, we asign this function to a variable in order to get the return and console log it for your future vision!
                    var newCheckBox = createCheckBox($(this), i, $(this).attr('id'));
                });


                // initialise the tabs. Capture current tab
                var index = 'key';
                //  Define friendly data store name
                var dataStore = window.sessionStorage;
                //  Start magic!
                try {
                    // getter: Fetch previous value
                    var oldIndex = dataStore.getItem(index);
                } catch (e) {
                    // getter: Always default to first tab in error state
                    var oldIndex = 0;
                }

                $("#tabs").tabs({
                    // The zero-based index of the panel that is active (open)
                    active: oldIndex,
                    // Triggered after a tab has been activated
                    activate: function (event, ui) {
                        //  Get future value
                        var newIndex = ui.newTab.parent().children().index(ui.newTab);
                        //  Set future value
                        dataStore.setItem(index, newIndex)
                    },
                    heightStyle: "content"
                }).css({'overflow': 'auto', 'min-height': '400px'});

                $("select").select2({minimumResultsForSearch: 5, width: 300});

                getMedia(<?php print($_REQUEST['id']); ?>);

<?php if ($locked) { ?>


                    $(".editimg").css('opacity', 0.2);
                    $(".draghandle").css('opacity', 0.5);
                    $(".deletehandle").css('opacity', 0.2);
<?php } else { ?>
                    $("#questionstbl tbody").disableSelection();
                    $("#questionstbl tbody").sortable({distance: 15, handle: '.draghandle', deactivate: function (event, ui) {
                            reorder_questions();
                        }
                    });
<?php } ?>

            });

            /**
             * Gets some details about this exam.
             * @returns {getDetails}
             */
//            function getDetails() {
//                dataObj = {action: 'getinstancebyid',
//                    id: <?php print($_REQUEST['id']); ?>,
//                    userid: <?php print($loggedinuserdata->userID); ?>,
//                    token: '<?php print($token); ?>'};
//                $.ajax({
//                    url: serviceurl,
//                    type: 'post',
//                    data: dataObj,
//                    dataType: isie() ? "text" : "xml",
//                    error: function(jqXHR, textStatus, errorThrown) {
//                        alert(errorThrown);
//                    },
//                    success: function(data) {
//                        waitOff();
//                        var xml;
//                        if (typeof data == "string") {
//                            xml = new ActiveXObject("Microsoft.XMLDOM");
//                            xml.async = false;
//                            xml.loadXML(data);
//                        } else {
//                            xml = data;
//                        }
//                        //   console.log($(xml).find('error').length);
//                        if ($(xml).find('error').length > 0) {
//                            alert('getting details failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
//                        } else {
//                            //                            $("#instance_name").val($(xml).find('name').text());
//                            //                            $("#instance_description").val($(xml).find('description').text());
//                            //                            $("#unit").val($(xml).find('unit_id').text());
//                            //                            $("#instance_owner").val($(xml).find('owner_id').text());
//                            if (($(xml).find('finalised').text() == 'true')) {
//                                if (($(xml).find('owner_id').text() == '<?php print($loggedinuserdata->userID); ?>')) {
//                                    $("#unlockBut").button({
//                                        disabled: false,
//                                        // nice icon
//                                        icons: {
//                                            primary: "ui-icon-unlocked"
//                                        }
//                                        // what happens when the button is clicked
//                                    }).click(function() {
//                                        unlock_session();
//                                    });
//                                }
//
//                                $("#newBut").button('disable');
//                                $(".editimg").css('opacity', 0.2);
//                                $(".draghandle").css('opacity', 0.5);
//                                $(".deletehandle").css('opacity', 0.2);
//                            } else {
//                                $("#lockBut").button({
//                                    disabled: false,
//                                    // nice icon
//                                    icons: {
//                                        primary: "ui-icon-locked"
//                                    }
//                                    // what happens when the button is clicked
//                                }).click(function() {
//                                    lock_session();
//                                });
//                                $("#questionstbl tbody").sortable({distance: 15, handle: '.draghandle', deactivate: function(event, ui) {
//                                        reorder_questions();
//                                    }
//                                });
//                                $("#questionstbl tbody").disableSelection();
//                            }
//
//
//                        }
//                    }
//                });
//            }

<?php if (!$locked) { ?>
                //  functionality to eit an instance. Opens a dialog box after getting data about the instence from the backend
                function update_overview_popup() {
                    waitOn();

                    dataObj = {action: 'getinstancebyid', id: <?php print($_REQUEST['id']); ?>, token: '<?php print($token); ?>'};
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
                                $("#edit_exam_instance_setup").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('edit_osce_session_overview')); ?>',
                                    width: 600,
                                    open: function (event, ui) {
                                        $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                                    },
                                    buttons: {
                                        "<?php print($stringlib->get_string('update_osce_session_btn_lbl')); ?>": function () {
                                            $(this).dialog("close");
                                            update_overview_action();
                                        },
                                        Cancel: function () {
                                            $(this).dialog("close");
                                        }
                                    }
                                });
                                $("#edit_instance_name").val($(xml).find('name').text());
                                $("#edit_instance_description").val($(xml).find('description').text());
                                $("#edit_unit").select2("val", $(xml).find('unit_id').text());
                                $("#edit_scale").select2("val", $(xml).find('scale_id').text());
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
                function update_overview_action() {
                    waitOn();
                    dataObj = {action: 'updateinstance',
                        instance_name: $("#edit_instance_name").val(),
                        instance_description: $("#edit_instance_description").val(),
                        unitid: $("#edit_unit").select2('val'),
                        scaleid: $("#edit_scale").val(),
                        //                    instance_starttimestamp: Date.parse($("#instance_startdate").val()).getTime() / 1000,
                        userID: <?php print($loggedinuserdata->userID); ?>,
                        id: <?php print($_REQUEST['id']); ?>,
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

                /**
                 * Open a dialog box to add a question
                 * @returns {undefined} */
                function add_question_popup() {
                    $("#suggestionloader").hide();
                    $("#new_question_dialog").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('create_assessment_item_dialog_title')); ?>',
                        width: 600,
                        open: function (event, ui) {
                            $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                            $(this).prepend($(this).parent().find('.ui-dialog-buttonpane'));
                        },
                        buttons: {
                            "<?php print($stringlib->get_string('create_assessment_item_btn_lbl')); ?>": function () {
                                $(this).dialog("close");
                                create_question_action();
                            },
                            Cancel: function () {
                                $(this).dialog("close");
                            }
                        }
                    });
                }


                /**
                 * AJAX functionality to actually create a question
                 * @returns {undefined}             */
                function create_question_action() {
                    waitOn();
                    var dataObj = new Object();
                    dataObj = {action: 'addassessmentitemtosession',
                        text: $("#question_text").val(),
                        type: ($("#question_required")[0].checked ? '1' : '0'),
                        id: '<?php print($_REQUEST['id']); ?>',
                        userid: '<?php print($loggedinuserdata->userID); ?>',
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
                            //  waitOff();
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
                                alert('create assessment item failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            } else {
                                // console.log(data);
                                location.reload(true);
                            }
                        }
                    });
                }

                var loadingsuggestion = false;
                /**
                 * Gets suggestions from the database when making a new question
                 * It's actually pretty inefficient and flogs the SQL server pretty hard if a few people are using it...
                 * @returns {undefined} */
                function getSuggestions() {
                    if (!loadingsuggestion) {
                        loadingsuggestion = true;
                        var dataObj = new Object();
                        $("#suggestionloader").show();
                        dataObj = {
                            action: 'listquestionsbysearchstr',
                            searchstr: $("#question_text").val(),
                            token: '<?php print($token); ?>'
                        };
                        $.ajax({
                            url: serviceurl,
                            type: 'post',
                            data: dataObj,
                            dataType: isie() ? "text" : "xml",
                            error: function (jqXHR, textStatus, errorThrown) {
                                alert(errorThrown);
                                loadingsuggestion = false;
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

                                    alert('get suggestions failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                } else {
                                    var suggestedItemsTable = '<table><tr><th>Item</th><th>Last used in</th><th>Copy</th></tr>';
                                    $(xml).find('question').each(function () {
                                        suggestedItemsTable += '<tr><td>' + $(this).find('text').text() + ($(this).find('type').text() == '1' ? '(Essential)' : '(non-essential)') + '</td><td>' + $(this).find('examname').text() + '</td><td><input type="image" src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/icons/gtk-copy.png" BORDER="0" style="vertical-align: middle; display:table-cell" onclick="copyItem(\'' + encodeURI($(this).find('text').text()).replace(/[!'()*]/g, escape) + '\', \'' + $(this).find('type').text() + '\'); return false;"/></td></tr>';
                                    });
                                    suggestedItemsTable += '</table>';
                                    $("#suggested_items_list").html(suggestedItemsTable);
                                    $("#suggestionloader").hide();
                                    loadingsuggestion = false;
                                }
                            }
                        });
                    }
                }

                var currentQuestionID = 0;
                //function edit_question_popup(id, text, essential) {

                /**
                 * Trigger the pop-up to edit a question. It gets details for an item from the database first             
                 * @param {type} id
                 * @returns {edit_question_popup} */
                function edit_question_popup(id) {
                    currentQuestionID = id;
                    dataObj = {action: 'getassessmentitemdetails',
                        id: id,
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
                                alert('Loading Assessment Item failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            } else {
                                $("#edit_question_dialog").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('edit_assessment_item')); ?>',
                                    width: 600,
                                    buttons: {
                                        "<?php print($stringlib->get_string('edit_assessment_item_btn_lbl')); ?>": function () {
                                            $(this).dialog("close");
                                            edit_question_action();
                                        },
                                        Cancel: function () {
                                            $(this).dialog("close");
                                        }
                                    }
                                });
                                $("#edit_question_text").val($(xml).find('text').first().text());
                                if ($(xml).find('type').first().text() == '1') {
                                    if (!$("#edit_question_required")[0].checked) {
                                        $("#edit_question_required").click();
                                    }
                                } else {
                                    if ($("#edit_question_required")[0].checked) {
                                        $("#edit_question_required").click();
                                    }
                                }
                            }
                        }
                    });

                }

                /**
                 * The ajax action for editing a question
                 * @returns {edit_question_action} */
                function edit_question_action() {
                    dataObj = {action: 'updateassessmentitem',
                        id: currentQuestionID,
                        text: $("#edit_question_text").val(),
                        type: ($("#edit_question_required")[0].checked ? '1' : '0'),
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
                                alert('Updating Assessment Item failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            } else {
                                $("#question_builder_popup").dialog('close');
                                location.reload(true);
                            }
                        }
                    });
                }

                /**
                 * Sends the AJAX request to re-order the questions in the database
                 * @returns {undefined} */
                function reorder_questions() {
                    waitOn();
                    var dataObj = new Object();
                    var orderdef = '<data>';
                    var orderint = 0;
                    $(".sortablerow").each(function () {
                        orderdef += '<def><order>' + orderint + '</order><id>' + $(this).attr('entryid') + '</id></def>';
                        orderint++;
                    });
                    orderdef += '</data>';
                    dataObj = {
                        action: 'reorderquestionwithsession',
                        token: '<?php print($token); ?>',
                        id: <?php print($_REQUEST['id']); ?>,
                        orderdef: orderdef,
                        userid: <?php print($loggedinuserdata->userID); ?>
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
                            //waitOff();
                            var xml;

                            if (typeof data == "string") {
                                xml = new ActiveXObject("Microsoft.XMLDOM");
                                xml.async = false;
                                xml.loadXML(data);
                            } else {
                                xml = data;
                            }

                            if ($(xml).find('error').length > 0) {

                                alert('reodering failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            } else {
                                location.reload(true);
                            }

                        }
                    });

                }

                /**
                 * Copy a suggested question to the new question input fields
                 * @param {type} text
                 * @param {type} essential
                 * @returns {undefined} */
                function copyItem(text, essential) {
                    $("#question_text").val(decodeURI(text));
                    if (essential == '1') {
                        if (!$("#question_required")[0].checked) {
                            $("#question_required").click();
                        }
                    } else {
                        if ($("#question_required")[0].checked) {
                            $("#question_required").click();
                        }
                    }
                }


                /**
                 * Delete a question defined by id
                 * @param {type} id
                 * @returns {undefined}
                 */
                function delete_question(id) {
                    var deleteConfirmer = $('<div></div>')
                            .html('<?php print($stringlib->get_string('really_delete_item')); ?>')
                            .dialog({
                                title: '<?php print($stringlib->get_string('really_delete_item')); ?>',
                                modal: true,
                                buttons: [
                                    {
                                        text: "OK",
                                        click: function () {
                                            $(this).dialog('close');
                                            waitOn();
                                            var dataObj = new Object();
                                            dataObj = {
                                                action: 'deleteassessmentitem',
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

                                                        alert('Dissociate item failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
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



                /**
                 * lock this session
                 * @returns {undefined} */
                function lock_session() {
                    var $lockConfirmer = $('<div></div>')
                            .html('<?php print($stringlib->get_string('edit_osce_session_finalise')); ?>')
                            .dialog({
                                title: 'Really?',
                                modal: true,
                                buttons: [
                                    {
                                        text: "OK",
                                        click: function () {
                                            $(this).dialog('close');
                                            waitOn();
                                            var dataObj = new Object();
                                            dataObj = {
                                                action: 'locksession',
                                                id: <?php print($_REQUEST['id']) ?>,
                                                user: <?php print($loggedinuserdata->userID) ?>,
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

                                                        alert('Setting session to test mode failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
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
<?php } ?>
            /**
             * unlock this session
             * @returns {undefined}             */
            function unlock_session() {
                var $lockConfirmer = $('<div></div>')
                        .html('<?php print($stringlib->get_string('edit_osce_session_unfinalise')); ?>')
                        .dialog({
                            title: 'Really?',
                            modal: true,
                            buttons: [
                                {
                                    text: "OK",
                                    click: function () {
                                        $(this).dialog('close');
                                        waitOn();
                                        var dataObj = new Object();
                                        dataObj = {
                                            action: 'unlocksession',
                                            id: <?php print($_REQUEST['id']) ?>,
                                            user: <?php print($loggedinuserdata->userID) ?>,
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

                                                    alert('Unlock Session failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
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

            function activate_session() {
                var $activateConfirmer = $('<div></div>')
                        .html('Really start session?')
                        .dialog({
                            title: '<?php print($stringlib->get_string('really_activate_session')); ?>',
                            modal: true,
                            buttons: [
                                {
                                    text: "OK",
                                    click: function () {
                                        $(this).dialog('close');
                                        waitOn();
                                        var dataObj = new Object();
                                        dataObj = {
                                            action: 'activatesession',
                                            id: '<?php print($_REQUEST['id']); ?>',
                                            token: '<?php print($token); ?>'
                                        };
                                        $.ajax({
                                            url: serviceurl,
                                            type: 'post',
                                            data: dataObj,
                                            dataType: isie() ? "text" : "xml",
                                            error: function (jqXHR, textStatus, errorThrown) {
                                                alert(errorThrown);
                                                waitOff();
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


            function deactivate_session() {
                var $deactivateConfirmer = $('<div></div>')
                        .html('Really stop session?')
                        .dialog({
                            title: '<?php print($stringlib->get_string('really')); ?>',
                            modal: true,
                            buttons: [
                                {
                                    text: "OK",
                                    click: function () {
                                        $(this).dialog('close');
                                        waitOn();
                                        var dataObj = new Object();
                                        dataObj = {
                                            action: 'deactivatesession',
                                            id: '<?php print($_REQUEST['id']); ?>',
                                            token: '<?php print($token); ?>'
                                        };
                                        $.ajax({
                                            url: serviceurl,
                                            type: 'post',
                                            data: dataObj,
                                            dataType: isie() ? "text" : "xml",
                                            error: function (jqXHR, textStatus, errorThrown) {
                                                alert(errorThrown);
                                                waitOff();
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

            /**
             * Starts the 'testing' mode of this exam
             * @returns {undefined} */
            function activate_practice() {
                var $testConfirmer = $('<div></div>')
                        .html('<?php print($stringlib->get_string('start_practicing')); ?>')
                        .dialog({
                            title: 'Really?',
                            modal: true,
                            buttons: [
                                {
                                    text: "OK",
                                    click: function () {
                                        $(this).dialog('close');
                                        waitOn();
                                        var dataObj = new Object();
                                        dataObj = {
                                            action: 'activatepracticesession',
                                            id: <?php print($_REQUEST['id']) ?>,
                                            user: <?php print($loggedinuserdata->userID) ?>,
                                            token: '<?php print($token); ?>'
                                        };
                                        $.ajax({
                                            url: serviceurl,
                                            type: 'post',
                                            data: dataObj,
                                            dataType: isie() ? "text" : "xml",
                                            error: function (jqXHR, textStatus, errorThrown) {
                                                alert(errorThrown);
                                                waitOff();
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

                                                    alert('Locking session failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
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

            function deactivate_practice() {
                var $deactivateConfirmer = $('<div></div>')
                        .html('Really stop practicing?')
                        .dialog({
                            title: '<?php print($stringlib->get_string('really')); ?>',
                            modal: true,
                            buttons: [
                                {
                                    text: "OK",
                                    click: function () {
                                        $(this).dialog('close');
                                        waitOn();
                                        var dataObj = new Object();
                                        dataObj = {
                                            action: 'deactivatepracticesession',
                                            id: '<?php print($_REQUEST['id']); ?>',
                                            token: '<?php print($token); ?>'
                                        };
                                        $.ajax({
                                            url: serviceurl,
                                            type: 'post',
                                            data: dataObj,
                                            dataType: isie() ? "text" : "xml",
                                            error: function (jqXHR, textStatus, errorThrown) {
                                                alert(errorThrown);
                                                waitOff();
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

                                                    alert('Deactivate practice session failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
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
                            $("#edit_email_text").tinymce({
                                script_url: '../../js/tinymce/tinymce.min.js',
                                toolbar: "undo redo | bold italic ",
                                menubar: false
                            });
                            $("#edit_email_dialog").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('edit_email_stem')); ?>',
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
                            $("#edit_email_text").val($(xml).find('text').first().text());
                        }
                    }
                });

            }

            /**
             * The ajax action for updating an email stem
             * @returns {edit_question_action} */
            function edit_feedback_stem_action() {
                dataObj = {action: 'updateemailstemdetails',
                    id: <?php print($_REQUEST['id']); ?>,
                    text: $("#edit_email_text").val(),
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
                            $("#edit_email_dialog").dialog('close');
                            //  location.reload(true);
                        }
                    }
                });
            }

            /**
             *  archive this session
             * @returns {undefined}             */
            function archive_session() {
                var archiveConfirmer = $('<div></div>')
                        .html('<?php print($stringlib->get_string('edit_osce_session_archive_confirm_msg')); ?>')
                        .dialog({
                            title: 'Really?',
                            modal: true,
                            buttons: [
                                {
                                    text: "OK",
                                    click: function () {
                                        $(this).dialog('close');
                                        waitOn();
                                        var dataObj = new Object();
                                        dataObj = {
                                            action: 'archivesession',
                                            id: <?php print($_REQUEST['id']) ?>,
                                            user: <?php print($loggedinuserdata->userID) ?>,
                                            token: '<?php print($token); ?>'
                                        };
                                        $.ajax({
                                            url: serviceurl,
                                            type: 'post',
                                            data: dataObj,
                                            dataType: isie() ? "text" : "xml",
                                            error: function (jqXHR, textStatus, errorThrown) {
                                                alert(errorThrown);
                                                waitOff();
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

                                                    alert('Archive Session failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                                    waitOff();
                                                } else {
                                                    location.assign('../');
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


            //////////////////////////////////////////////////////////////////////////////
            //
            //File operations
            //
            //////////////////////////////////////////////////////////////////////////////

            /**
             * Opens the upload dialog
             
             * @returns {undefined} */
            function open_upload_dialog() {
                $('#upload_dialog').dialog('open');
                validate_upload_form();
            }

            function validate_upload_form() {
                valid = (($("#upload_file")[0].files.length > 0) && ($("#description").val().length > 0));
                $(".ui-dialog-buttonpane button:contains('Upload')").button(valid ? "enable" : "disable");
                return (valid);

            }

            /**
             * Actually performs the file upload
             * @returns {undefined}
             */
            function file_upload_action() {
                waitOn();
                var formdata = new FormData();

                formdata.append('description', $("#description").val());
                formdata.append('id', <?php print($_REQUEST['id']); ?>);
                formdata.append('action', 'uploadmedia');
                formdata.append('token', '<?php print($token); ?>');
                // file
                // formdata.append("userfile", $('#upload_file')[0].files[0]);
                console.log($('#upload_file').get(0).files[0]);
                formdata.append("userfile", $('#upload_file').get(0).files[0]);
//                jQuery.each($('#upload_file')[0].files, function (i, file) {
//                    formdata.append('userfile', file);
//                });
                waitOn();
                $.ajax({
                    url: serviceurl,
                    type: 'POST',
                    data: formdata,
                    //cache: false,
                    contentType: false,
                    processData: false,
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                        waitOff();
                    },
                    success: function (data) {
                        waitOff();
                        alert(data);
                        // getMedia(<?php print($_REQUEST['id']); ?>);
                    }
                });
            }

            /**
             * Get media for a given record ID, show it in the media tab
             * @param {type} id
             * @returns {undefined}
             */
            function getMedia(id) {
                waitOn();
                var dataObj = new Object();
                dataObj = {action: 'getmediaforrecordid', id: id, token: '<?php print($token); ?>'};
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
                            alert('get media failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            waitOff();
                        } else {


                            var mediatable = "<table style='width:100%'><tr><th>Thumb</th><th>File Name</th><th>Description</th><th>Delete</th></tr>";

                            $(xml).find('image').each(function () {
                                mediatable += "<tr><td><a href='" + serviceurl + "?action=downloadmedia&token=<?php print($token); ?>&id=" + $(this).find('id').text() + "'><img src='" + serviceurl + "?action=showthumbformedia&token=<?php print($token); ?>&getbig=false&id=" + $(this).find('id').text() + "'></img></a></td><td><a href='" + serviceurl + "?action=downloadmedia&token=<?php print($token); ?>&id=" + $(this).find('id').text() + "'>" + $(this).find('filename').text() + "</a></td><td>" + $(this).find('label').text() + "</td><td><input type='image' src='./../../icons/gtk-cancel.png' BORDER='0' style='vertical-align: text-bottom;' onclick='delete_media_entry(" + $(this).find('id').text() + "); return false;'/></td></tr>";
                            });
                            mediatable += "</table>";
                            document.getElementById("file_list").innerHTML = mediatable;
                        }
                        waitOff();
                    }
                });
            }


            function delete_media_entry(id) {
                $("#dialog-confirm").dialog({
                    resizable: false,
                    height: 300,
                    width: 400,
                    modal: true,
                    buttons: {
                        "Delete item": function () {
                            $(this).dialog("close");
                            really_delete_media_entry(id);
                        },
                        Cancel: function () {
                            $(this).dialog("close");
                        }
                    }
                }).html('<p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span>This item will be permanently deleted and cannot be recovered. Are you sure?</p>');
            }

            /**
             * Delete a media entry
             * @param {type} id
             * @returns {undefined} 
             */
            function really_delete_media_entry(id) {
                waitOn();
                var dataObj = new Object();
                dataObj = {action: 'deletemedia', id: id, token: '<?php print($token); ?>'};
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
                            alert('delete media entry failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            waitOff();
                        } else {
                            getMedia(<?php print($_REQUEST['id']); ?>);
                        }
                        waitOff();
                    }
                });
            }


            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //
            //UI feedback and support functions
            //
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            /**
             * Validates a form, enables a button defined by buttonLabel when conditions are met             
             * @param {type} formID
             * @param {type} buttonLabel
             * @returns {undefined} */
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

                    if (($(this).val() == '-1') && ($(this).attr('required') == 'required')) {
                        valid = false;
                    }
                });

                $(".ui-dialog-buttonpane button:contains('" + buttonLabel + "')").button(valid ? "enable" : "disable");
                // adding some extra to get suggestions
                if (formID == 'new_question_dialog') {
                    getSuggestions();
                }
            }


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

            // show a little dialog box with information
            function show_information(info) {
                $("#info_dialog").dialog('open').html(info);

            }

            function showHelp(helpStr) {
                $("#help_dialog").dialog('open').html(helpStr);
            }


            function rfc3986EncodeURIComponent(str) {
                return encodeURIComponent(str).replace(/[!'()*]/g, escape);
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

            /**
             * DEPRECATED
             * update the current question data- we'll revisit this if we mode the editing of the question details to this page
             * @returns {undefined}             */
            function save_action() {
                waitOn();
                var dataObj = new Object();
                var orderdef = '<data>';
                var orderint = 0;
                $(".sortablerow").each(function () {
                    orderdef += '<def><order>' + orderint + '</order><id>' + $(this).attr('entryid') + '</id></def>';
                    orderint++;
                });
                orderdef += '</data>';
                dataObj = {
                    action: 'updateinstance',
                    token: '<?php print($token); ?>',
                    id: <?php print($_REQUEST['id']); ?>,
                    instance_name: $("#instance_name").val(),
                    instance_description: $("#instance_description").val(),
                    unitid: $("#unit").val(),
                    ownerID: $("#instance_owner").val()
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
                        //waitOff();
                        var xml;

                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }

                        if ($(xml).find('error').length > 0) {

                            alert('saving failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {
                            location.assign('../');
                        }

                    }
                });

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
                            <?php
                            if ($locked) {
                                print("<img src='{$CFG->wwwroot}{$CFG->basedir}/icons/emblem-important.png'>Examination is locked");
                            } else {
                                print("<img src='{$CFG->wwwroot}{$CFG->basedir}/icons/emblem-important.png'>Examination is unlocked");
                            }
                            ?>
                            <button class="actionbutton"   <?php print($locked ? ($isowner ? '' : 'disabled') : ''); ?> id="lockBut" onclick="<?php print($locked ? 'unlock_session()' : 'lock_session()'); ?>"><?php print($locked ? $stringlib->get_string('edit_osce_session_unfinalise_lbl') : $stringlib->get_string('edit_osce_session_finalise_lbl')); ?></button>
                            <p></p>
                            <button class="actionbutton" id="defineEmailBut" onclick="edit_feedback_stem_popup();">Edit Feedback stem email</button> 
                            <p></p>
                            <button class="actionbutton" id="exportBut" onclick="window.open('<?php print("{$CFG->serviceURL}?action=exportexamasxmlfile&id={$_REQUEST['id']}&token={$token}"); ?>', '_blank')">Export</button>
                            <p></p>
                            <button class="actionbutton" <?php print($active ? 'disabled' : ''); ?> id="archiveBut" onclick="archive_session();"><?php print($stringlib->get_string('edit_osce_session_archive_lbl')); ?></button>
                        </div> 
                    <?php } ?>
                    <div class="navheader">
                        Navigation
                    </div>    
                    <div class="navcontent">

                        <?php print($menuStr); ?>
                    </div>    
                </div>

                <div class="contentcontainer withstatus">
                    <div class="contentinner">

                        <div class="contentcontentwithstatus">
                            <div class="contentinnerheader <?php print(($active ? 'active ' : '') . ($practicing ? 'practicing' : '') . (!($active || $practicing) ? 'stopped' : '')); ?>">
                                <?php
                                if ($active) {
                                    print("<img src='{$CFG->wwwroot}{$CFG->basedir}/icons/emblem-important.png'>Examination is active");
                                } else if ($practicing) {
                                    print("<img src='{$CFG->wwwroot}{$CFG->basedir}/icons/emblem-bin.png'>Examination is in Practice Mode");
                                } else {
                                    print("<img src='{$CFG->wwwroot}{$CFG->basedir}/icons/emblem-bin.png'>Examination is not running");
                                }
                                ?>
                            </div>
                            <div id="tabs" >
                                <ul>
                                    <li><a href="#tabs-1"><?php print($stringlib->get_string('edit_osce_session_overview_heading')); ?></a></li>
                                    <li><a href="#tabs-2">Assessment Items</a></li>
                                    <li><a href="#tabs-3">Associated Media</a></li>
                                </ul>
                                <div id="tabs-1">
                                    <fieldset style="width: 90%">
                                        <div style="width: 100%; display: table">
                                            <div style="width: 99%; display: table-row">
                                                <div style="display: table-cell;"><b><?php print($stringlib->get_string('osce_session_name')); ?>:</b></div>
                                                <div style="display: table-cell;"><?php print($instanceData->instance[0]->name); ?></div>
                                            </div>
                                            <div style="width: 99%; display: table-row">
                                                <div style="display: table-cell"><b><?php print($stringlib->get_string('osce_session_unit')); ?>:</b></div>    
                                                <div style="display: table-cell"><?php print(simplexml_load_string($enumLib->getUnitDescriptionByID($instanceData->instance[0]->unit_id))); ?></div>
                                            </div>
                                            <div style="width: 99%; display: table-row">
                                                <div style="display: table-cell"><b><?php print($stringlib->get_string('osce_session_scale')); ?>:</b></div>    
                                                <div style="display: table-cell"><?php print(simplexml_load_string($enumLib->getCriteriaScaleOverview($instanceData->instance[0]->scale_id))->item[0]->description); ?><span><input type='image' src='<?php print("{$CFG->wwwroot}{$CFG->basedir}/icons/dialog-information.png"); ?>' BORDER='0' style='vertical-align: text-bottom;' onclick='show_information("<?php print(simplexml_load_string($enumLib->getCriteriaScaleOverview($instanceData->instance[0]->scale_id))->item[0]->notes); ?>");
                                                        return false;'/></span><br/></div>
                                            </div>
                                            <div style="width: 99%; display: table-row">
                                                <div style="display: table-cell"><b><?php print($stringlib->get_string('osce_session_owner')); ?>:</b></div>
                                                <div style="display: table-cell"><?php print(simplexml_load_string($enumLib->getUserByID($instanceData->instance[0]->owner_id))->user[0]->name); ?></div>
                                            </div>
                                            <div style="width: 99%; display: table-row">
                                                <div style="display: table-cell"><b><?php print($stringlib->get_string('osce_session_description')); ?>:</b></div>
                                                <div style="display: table-cell"><?php print($instanceData->instance[0]->description); ?></div>
                                            </div>
                                        </div>
                                        <?php
                                        if (($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_assessments))) {
                                            ?>
                                            <button id="editBut" <?php print($locked ? 'disabled' : ''); ?> class="disableiflocked" style='vertical-align: middle; display:table-cell' onclick='update_overview_popup();'>Edit</button>

                                            <?php
                                        }
                                        ?>
                                    </fieldset>
                                </div>
                                <div id="tabs-2">
                                    <div id="questions_list"><span style="font-size: 1em; font-weight: bold"><?php print($stringlib->get_string('edit_osce_session_questions_heading')); ?></span>&nbsp;&nbsp;<span><button id="newBut" <?php print($locked ? 'disabled' : ''); ?>><?php print($stringlib->get_string('edit_osce_add_assessment_item')); ?></button></span>
                                        <div id="actions_div"><br/></div>
                                        <?php print($listTableStr); ?>
                                    </div>
                                </div>
                                <div id="tabs-3">
                                    Media for this assessment
                                    <button id="new_assessment_but" class="disableiflocked" onclick="open_upload_dialog()">Upload new</button>
                                    <div id="file_list">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <div class="statusarea">
                    <div class="statusheader " style="color: #050708; background-color:rgba(255,165,0, 0.8); border-bottom: 6px solid #050708;">
                        Examination Control
                    </div>    
                    <div class="statuscontent" >


                        <button class="actionbutton"  <?php print($locked ? ($practicing ? 'disabled' : '') : 'disabled'); ?> onclick="<?php print($active ? 'deactivate_session()' : 'activate_session()'); ?>" id="play"><?php print($active ? $stringlib->get_string('deactivate_osce') : $stringlib->get_string('activate_osce')); ?></button>
                        <p></p>

                        <button class="actionbutton" <?php print($active ? 'disabled' : ''); ?> onclick="<?php print($practicing ? 'deactivate_practice()' : 'activate_practice()'); ?>" id="testBut"><?php print($practicing ? $stringlib->get_string('deactivate_practice_osce') : $stringlib->get_string('activate_practice_osce')); ?></button>
                    </div>    
                </div>
            </div>
        </div>

        <?php if (strval($instanceData->instance[0]->finalised) != 'true') { ?>
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


            <div id="new_question_dialog" >
                <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('create_assessment_item')); ?></legend>
                    <div>
                        <label for="question_text">Assessment Item Text (required)</label><br/>
                        <textarea id="question_text" name="question_text" required='required' style="width: 100%" onkeyup="validate_form('new_question_dialog', '<?php print($stringlib->get_string('create_assessment_item')); ?>')" onchange="  validate_form('new_question_dialog', '<?php print($stringlib->get_string('create_assessment_item')); ?>')"></textarea><br/>

                        <p>Essential?&nbsp;<input class="inp-checkbox" id="question_required" />&nbsp;&nbsp;&nbsp;<?php print("<input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/dialog-question.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='showHelp(\"{$stringlib->get_string('item_type_help_string')}\"); return false;'/>"); ?></p>
                    </div>
                </fieldset>
                <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('suggested_assessment_items')); ?><img id="suggestionloader" src="<?php print("{$CFG->wwwroot}{$CFG->basedir}/icons/dots16.gif"); ?>"></legend>
                    <div id="suggested_items_list"></div>
                </fieldset>
            </div>

            <div id="edit_question_dialog" >
                <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('edit_assessment_item')); ?></legend>
                    <div>
                        <label for="edit_question_text">Assessment Item Text (required)</label><br/>
                        <textarea id="edit_question_text" name="edit_question_text" required='required' style="width: 100%" onkeyup="validate_form('edit_question_dialog', '<?php print($stringlib->get_string('edit_assessment_item')); ?>')" onchange="  validate_form('edit_question_dialog', '<?php print($stringlib->get_string('edit_assessment_item')); ?>')"></textarea><br/>
                        <p>Essential?&nbsp;<input class="inp-checkbox" id="edit_question_required" />&nbsp;&nbsp;&nbsp;<?php print("<input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/dialog-question.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='showHelp(\"{$stringlib->get_string('item_type_help_string')}\"); return false;'/>"); ?></p>
                    </div>
                </fieldset>
            </div>

        <?php } ?>

        <div id="edit_email_dialog" >
            <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('edit_email_stem')); ?></legend>
                <div>
                    <span style="font-size: 11px">Dear (student name here),</span>
                    <textarea id="edit_email_text"  style="width: 100%; height: 100%"> </textarea><br/>
                </div>
            </fieldset>
        </div>

        <div id="upload_dialog">

            <fieldset style="width: 90%"><legend>File details</legend>
                <div>
                    <label for="file description">Description</label><br/>
                    <input type="text" id="description" name="description" onkeyup="validate_upload_form()" onchange="validate_upload_form()"><br/>
                    <label for="upload_file" id="file_upload_label">Select File (image, documents, small movies only)</label><br/>
                    <input type="file" id="upload_file" name="upload_file" onchange="validate_upload_form()">
                </div>
            </fieldset>
        </div>

        <div id="dialog-confirm" title="Really delete?">

        </div>

        <div id="waiting_dialog">
            <p style="text-align: center"><img src="<?php print($CFG->wwwroot . $CFG->basedir); ?>icons/ajax-loader.gif" style="vertical-align: middle"/><br/>Please wait...</p>
        </div>

        <div id="info_dialog">

        </div>
        <!-- help-->
        <div id="help_dialog">

        </div>

    </body>
</html>

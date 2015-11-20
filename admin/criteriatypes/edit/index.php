<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
include '../../../backend/lib/authlib.php';
include '../../../backend/lib/StringLib.php';
include '../../../backend/lib/EnumLib.php';
include '../../../backend/lib/Mobile_Detect.php';
require_once('../../../backend/config.inc.php');


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
    $enumlib = new EnumLib();

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


// list criteria types here
$instanceData = simplexml_load_string($enumlib->getCriteriaScaleOverview($_REQUEST['id']));
$itemdata = simplexml_load_string($enumlib->getCriteriaScaleItems($_REQUEST['id']));

//print_r($instanceData->item[0]);
$listTableStr = "<table id='itemstbl'><thead><tr><th style='width:50px;'>Reorder</th><th>Edit</th><th>{$stringlib->get_string('assessment_criteria_short_description')}</th>
    <th>{$stringlib->get_string('assessment_criteria_long_description')}</th>
        <th>{$stringlib->get_string('assessment_criteria_value')}</th>
            <th>{$stringlib->get_string('assessment_criteria_needs_comment')}</th>
    <th>{$stringlib->get_string('assessment_item_list_remove')}</th></tr></thead><tbody>";
foreach ($itemdata->item as $item) {
    $listTableStr .= "<tr class='sortablerow' entryid='{$item->id}'><td class='draghandle'><img src='{$CFG->wwwroot}{$CFG->basedir}/icons/object-flip-vertical.png'/></td>
        <td class='editimg'>
        <input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-edit.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='update_item_popup(\"{$item->id}\"); return false;'/></td>
<td>{$item->short_description}</td>        
<td>{$item->long_description}</td>        
<td>{$item->value}</td>         
    <td>" . ($item->needs_comment == 'true' ? 'Yes' : 'No') . "</td>         
        ";
    $listTableStr .= "<td class='deletehandle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-cancel.png' BORDER='0' style='vertical-align: text-bottom;' onclick='delete_item({$item->id}); return false;'/></td></tr>";
}
$listTableStr .= "</tbody></table>";
if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_system_users) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_strings)) {
    $menuStr .= "<li class='showing'><label class='tree-toggle nav-header'>{$stringlib->get_string('system_administration_label')}</label>";
    $menuStr .= "<ul class='nav nav-list tree'>"
            . "<li><div style='vertical-align: middle; display:table;'>"
            . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_users_setup_help')}\"); return false;'/></div>"
            . "<div style='float:left; display:table-cell; vertical-align:middle'><a href='{$CFG->wwwroot}{$CFG->basedir}admin/users'>{$stringlib->get_string('system_users_setup')}</a></div>"
            . "</div></li>";
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_students)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('participants_setup_help')}\"); return false;'/></div>"
                . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='{$CFG->wwwroot}{$CFG->basedir}students'>{$stringlib->get_string('participants_setup')}</a></div>"
                . "</div></li>";
    }
    $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
            . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_criteria_setup_help')}\"); return false;'/></div>"
            . "<div style='float:left;display:table-cell; vertical-align:middle'>{$stringlib->get_string('string_criteria_types_label')}</div>"
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

// breadcrumbs
$breadcrumbStr = "<a href='{$CFG->wwwroot}{$CFG->basedir}'>Home</a>>";
$currentpath = str_replace("/$CFG->basedir", '', $_SERVER['PHP_SELF']);
//print($currentpath);
$patharr = explode('/', $currentpath);
$linkStr = '';
$displayStr = '';

for ($i = 0; $i < count($patharr) - 1; $i++) {
    $linkStr.="$patharr[$i]/";

    if (file_get_contents("{$CFG->wwwroot}{$CFG->basedir}$linkStr" . "label.txt")) {
        $arr = explode(',', file_get_contents("{$CFG->wwwroot}{$CFG->basedir}$linkStr" . "label.txt"));
        if ($arr[count($arr) - 1] == 'nolink') {
            $breadcrumbStr.= $stringlib->get_string($arr[0]) . ">";
        } else {
            $breadcrumbStr.=(($i < count($patharr) - 2) ? "<a href='" : "");
            $breadcrumbStr.= ($i < count($patharr) - 2) ? "{$CFG->wwwroot}{$CFG->basedir}$linkStr'>" : "";
            $breadcrumbStr.= (file_get_contents("{$CFG->wwwroot}{$CFG->basedir}$linkStr" . "label.txt") ? $stringlib->get_string(file_get_contents("{$CFG->wwwroot}{$CFG->basedir}$linkStr" . "label.txt")) : $patharr[$i]) . (($i < count($patharr) - 2) ? "</a>" : "") . (($i < count($patharr) - 2) ? ">" : '');
        }
    }
}
?>
<html>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=10"/>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
        <link type="text/css" href="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>css/jquery-ui.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>css/eOSCE.css" />
        <link type="text/css" href="<?php print($CFG->wwwroot . $CFG->basedir); ?>/css/select2.css" rel="stylesheet" />
        <link href="<?php print($CFG->wwwroot . $CFG->basedir); ?>/css/skins/all.css" rel="stylesheet"/>
        <style>
            .select2-choices {
                min-height: 500px;
                max-height: 500px;
                overflow-y: auto;
            }
        </style>
        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>js/jquery-2.0.3.min.js"></script>
        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/date.js"></script>
        <script src="<?php print($CFG->wwwroot . $CFG->basedir); ?>js/utils.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/select2.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/icheck.min.js"></script>
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
                $("#new_item_dialog").dialog({autoOpen: false, modal: true});

                $("#update_item_dialog").dialog({autoOpen: false, modal: true})

                $("#update_overview_dialog").dialog({autoOpen: false, modal: true});

                $('.spinner').spinner({
                    stop: function (event, ui) {
                        validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>');
                        validate_form('update_item_dialog', '<?php print($stringlib->get_string('update_criteria_item')); ?>')
                    }
                });

                ////////////////////////////////////////////////////////////////////////////////
                //  set up buttons
                //////////////////////////////////////////////////////////////////////////////// 

                $("#new_item_but").button({
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
                    add_item_popup();
                });

                $("#updateBut").button({
                    // nice icon
                    icons: {
                        primary: "ui-icon-wrench"
                    }

                    // what happens when the button is clicked
                });

                $("#itemstbl tbody").sortable({distance: 15, handle: '.draghandle', deactivate: function (event, ui) {
                        reorder_items();
                    }
                });
                $("#itemstbl tbody").disableSelection();

                $(".inp-checkbox").each(function (i) {
                    // as previously noted, we asign this function to a variable in order to get the return and console log it for your future vision!
                    var newCheckBox = createCheckBox($(this), i, $(this).attr('id'));
                });


                waitOff();

            });



            /**
             * Open a dialog box to add an item
             * @returns {undefined} */
            function add_item_popup() {

                $("#new_item_dialog").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('create_criteria_item_dialog_title')); ?>',
                    width: 600,
                    open: function (event, ui) {
                        $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                        //  $(this).prepend($(this).parent().find('.ui-dialog-buttonpane'));
                    },
                    buttons: {
                        "<?php print($stringlib->get_string('create_criteria_item')); ?>": function () {
                            $(this).dialog("close");
                            create_item_action();
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
            function create_item_action() {
                waitOn();
                var dataObj = new Object();
                dataObj = {action: 'addcriteriascaleitem',
                    shortdescription: $("#item_short_description").val(),
                    longdescription: $("#item_long_description").val(),
                    needscomment: $("#item_comment")[0].checked ? 'true' : 'false',
                    value: $("#item_value").val(),
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
                            alert('create item failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {
                            // console.log(data);
                            location.reload(true);
                        }
                    }
                });
            }

            /**
             * Open a dialog box to update an item
             * @returns {undefined} */
            function update_item_popup(id) {
                var dataObj = new Object();
                dataObj = {action: 'getcriteriaitembyid', id: id, token: '<?php print($token); ?>'};
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
                            eraseCookie('token');
                            window.location = "../index.php";
                        } else {
                            // make the system_user_setup div a dialog and populate it
                            $("#update_item_dialog").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('update_criteria_item_dialog_title')); ?>',
                                width: 600,
                                open: function (event, ui) {
                                    $(".ui-dialog-titlebar-close", $(this).parent()).hide();

                                },
                                buttons: {
                                    "<?php print($stringlib->get_string('update_criteria_item')); ?>": function () {
                                        $(this).dialog("close");
                                        update_item_action(id);
                                    },
                                    Cancel: function () {
                                        $(this).dialog("close");
                                    }
                                }
                            });
                            $("#update_item_long_description").val($(xml).find('long_description').text());
                            $("#update_item_short_description").val($(xml).find('short_description').text());
                            $("#update_item_value").val($(xml).find('value').text());
                            if ($(xml).find('needs_comment').first().text() == 'true') {
                                if (!$("#update_item_comment")[0].checked) {
                                    $("#update_item_comment").click();
                                }
                            } else {
                                if ($("#update_item_comment")[0].checked) {
                                    $("#update_item_comment").click();
                                }
                            }
                            validate_form();
                        }
                    }
                });


            }


            /**
             * AJAX functionality to actually update an item
             * @returns {undefined}             */
            function update_item_action(id) {
                waitOn();
                var dataObj = new Object();
                dataObj = {action: 'updatecriteriascaleitem',
                    shortdescription: $("#update_item_short_description").val(),
                    longdescription: $("#update_item_long_description").val(),
                    value: $("#update_item_value").val(),
                    needscomment: $("#update_item_comment")[0].checked ? 'true' : 'false',
                    id: id,
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
                            alert('update item failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {
                            // console.log(data);
                            location.reload(true);
                        }
                    }
                });
            }
            /**
             * Sends the AJAX request to re-order the questions in the database
             * @returns {undefined} */
            function reorder_items() {
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
                    action: 'reordercriteriascaleitems',
                    token: '<?php print($token); ?>',
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
             * Delete a question defined by id
             * @param {type} id
             * @returns {undefined}
             */
            function delete_item(id) {
                var deleteConfirmer = $('<div></div>')
                        .html('<?php print($stringlib->get_string('really_delete_item')); ?>')
                        .dialog({
                            title: '<?php print($stringlib->get_string('really_delete_item')); ?>',
                            buttons: [
                                {
                                    text: "OK",
                                    click: function () {
                                        $(this).dialog('close');
                                        waitOn();
                                        var dataObj = new Object();
                                        dataObj = {
                                            action: 'deletecriteriascaleitem',
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


            function update_overview_popup() {
                $("#update_overview_dialog").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('update_criteria_scale_dialog_title')); ?>',
                    width: 600,
                    open: function (event, ui) {
                        $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                        //  $(this).prepend($(this).parent().find('.ui-dialog-buttonpane'));
                    },
                    buttons: {
                        "<?php print($stringlib->get_string('update_criteria_scale_btn_lbl')); ?>":
                                function () {
                                    $(this).dialog("close");
                                    update_overview_action();
                                },
                        Cancel: function () {
                            $(this).dialog("close");
                        }
                    }
                });
                $("#update_overview_text").val('<?php print(addslashes($instanceData->item[0]->description)); ?>')
                $("#update_overview_notes").val('<?php print(addslashes($instanceData->item[0]->notes)); ?>')

                validate_form('update_overview_dialog', '<?php print($stringlib->get_string('update_criteria_scale_btn_lbl')); ?>');
            }

            function update_overview_action() {
                waitOn();
                var dataObj = new Object();
                dataObj = {action: 'updatecriteriascale',
                    description: $("#update_overview_text").val(),
                    notes: $("#update_overview_notes").val(),
                    id: <?php print($_REQUEST['id']); ?>,
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
                            alert('update overview item failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                        } else {
                            // console.log(data);
                            location.reload(true);
                        }
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

                $("#" + formID + " .spinner").each(function () {

                    if (($(this).val().length < 1) && ($(this).attr('required') == 'required')) {
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
                            <div style="display: table-cell;"><img  src="<?php print($CFG->wwwroot . $CFG->basedir); ?>icons/eoscelogoune.png"></div><div style="display: table-cell; vertical-align: bottom;" class="headertitle">:<?php print($CFG->sysname); ?></div>
                        </div>
                    </div>
                </div>
                <div class="breadcrumbs">     <?php
                    print($breadcrumbStr);
                    ?></div>
            </div>

            <div class="contentareacontainer">

                <div class="navcontainer">
                    <?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users)) { ?>
                        <div class="actionheader">
                            Actions
                        </div>    
                        <div class="actioncontent" style="height: auto">
                            <p>
                                <button class="actionbutton" id="updateBut" onclick='update_overview_popup();
                                        return false;'><?php print($stringlib->get_string('update_criteria_overview')); ?></button></p>
                            </p>

                            <p>
                                <button id="newBut" class="actionbutton" style="width:100%"><?php print($stringlib->get_string('add_criteria_item')); ?></button>
                            </p>
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
                        <div class="contentinnerheader">
                            <?php print($stringlib->get_string('criteriatypelist_legend')); ?>
                        </div>
                        <div class="contentcontentnostatus">
                            <p>
                                <div style="display: table">
                                    <div style="display: table-row">
                                        <div style="display: table-cell;"><b><?php print($stringlib->get_string('criteriatype_name')); ?>:</b></div>
                                        <div style="display: table-cell;"><?php print($instanceData->item[0]->description); ?></div>
                                    </div>
                                    <div style="display: table-row">
                                        <div style="display: table-cell; "><b><?php print($stringlib->get_string('criteriatype_description')); ?>:</b></div>    
                                        <div style="display: table-cell; "><?php print($instanceData->item[0]->notes); ?></div>
                                    </div>
                                </div>
                            </p>


                            <div id="tabs-2">
                                <div id="questions_list">
                                    <div class="contentinnerheader">
                                        <?php print($stringlib->get_string('update_criteria_heading')); ?>
                                    </div>
                                    <p>
                                        <?php print($listTableStr); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>


        <div id="new_item_dialog" >
            <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('create_criteria_item')); ?></legend>
                <div>
                    <label for="item_text"><?php print($stringlib->get_string('assessment_criteria_short_description')); ?>(required)</label><br/>
                    <input type="text" id="item_short_description" name="item_short_description" required='required' style="width: 100%" onkeyup="validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')" onchange="  validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')"/><br/>
                    <label for="item_text"><?php print($stringlib->get_string('assessment_criteria_long_description')); ?>(required)</label><br/>
                    <textarea id="item_long_description" name="item_long_description" required='required' style="width: 100%" onkeyup="validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')" onchange="  validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')"></textarea><br/>
                    <label for="item_text"><?php print($stringlib->get_string('assessment_criteria_value')); ?>(required)</label><br/>
                    <input class='spinner' id="item_value" name="item_value" required='required' style="width: 100%" onkeyup="validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')" onchange="  validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')"/><br/>
                    <input class='inp-checkbox' id="item_comment" name="item_comment" onchange="  validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')"><?php print($stringlib->get_string('update_assessment_criteria_comment_required')); ?>
                </div>
            </fieldset>

        </div>

        <div id="update_item_dialog" >
            <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('update_criteria_item')); ?></legend>
                <div>
                    <label for="update_item_short_description"><?php print($stringlib->get_string('assessment_criteria_short_description')); ?>(required)</label><br/>
                    <input type="text" id="update_item_short_description" name="update_item_short_description" required='required' style="width: 100%" onkeyup="validate_form('update_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')" onchange="  validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')"/><br/>
                    <label for="update_item_long_description"><?php print($stringlib->get_string('update_assessment_criteria_long_description')); ?>(required)</label><br/>
                    <textarea id="update_item_long_description" name="update_item_long_description" required='required' style="width: 100%" onkeyup="validate_form('update_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')" onchange="  validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')"></textarea><br/>
                    <label for="item_text"><?php print($stringlib->get_string('update_assessment_criteria_value')); ?>(required)</label><br/>
                    <input class='spinner' id="update_item_value" name="update_item_value" required='required' style="width: 100%" onkeyup="validate_form('update_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')" onchange="  validate_form('update_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')"/><br/>
                    <input class='inp-checkbox' id="update_item_comment" name="update_item_comment" required='required' onkeyup="validate_form('update_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')" onchange="  validate_form('update_item_dialog', '<?php print($stringlib->get_string('create_criteria_item')); ?>')"><?php print($stringlib->get_string('update_assessment_criteria_comment_required')); ?>


                </div>
            </fieldset>
        </div>

        <div id="update_overview_dialog" >
            <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('update_criteria_scale')); ?></legend>
                <div>
                    <label for="update_overview_text"><?php print($stringlib->get_string('assessment_criteria_scale_description')); ?>(required)</label><br/>
                    <input type="text" id="update_overview_text" name="update_overview_text" required='required' style="width: 100%" onkeyup="validate_form('update_overview_dialog', '<?php print($stringlib->get_string('update_criteria_scale_btn_lbl')); ?>')" onchange="  validate_form('update_overview_dialog', '<?php print($stringlib->get_string('update_criteria_scale_btn_lbl')); ?>')"><br/>
                        <label for="update_overview_notes"><?php print($stringlib->get_string('assessment_criteria_scale_notes')); ?>(required)</label><br/>
                        <textarea id="update_overview_notes" name="update_overview_notes" required='required' style="width: 100%" onkeyup="validate_form('update_overview_dialog', '<?php print($stringlib->get_string('update_criteria_scale_btn_lbl')); ?>')" onchange="  validate_form('update_overview_dialog', '<?php print($stringlib->get_string('update_criteria_scale_btn_lbl')); ?>')"></textarea><br/>
                </div>
            </fieldset>
        </div>

        <!-- Waiting-->
        <div id="waiting_dialog">
            <p style="text-align: center"><img src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/icons/ajax-loader.gif" style="vertical-align: middle"/><br/>Please wait...</p>
        </div>
        <!-- help-->
        <div id="help_dialog">

        </div>



    </body>
</html>

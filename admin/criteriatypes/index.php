<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
include '../../backend/lib/authlib.php';
include '../../backend/lib/StringLib.php';
include '../../backend/lib/EnumLib.php';
include '../../backend/lib/Mobile_Detect.php';
require_once('../../backend/config.inc.php');


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

$criteriaTypesXML = simplexml_load_string($enumlib->getCriteriaTypesLookup());

$listTableStr = "<table><tr><th>{$stringlib->get_string('criteria_description_lbl')}</th><th>{$stringlib->get_string('criteria_notes_lbl')}</th><th style='width:50px'>Delete</th></tr>";
foreach ($criteriaTypesXML->item as $item) {
    $listTableStr .= "<tr><td><a href='{$CFG->wwwroot}{$CFG->basedir}admin/criteriatypes/edit/index.php?id={$item->id}'>{$item->description}</a></td>";
    $listTableStr .= "<td>{$item->notes}</td>";
    $listTableStr .= "<td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-cancel.png' BORDER='0' style='vertical-align: text-bottom;' onclick='delete_item({$item->id}); return false;'/></td></tr>";
}
$listTableStr .= "</table>";

if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_system_users) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_strings)) {
    $menuStr .= "<li class='showing'><label class='tree-toggle nav-header'>{$stringlib->get_string('system_administration_label')}</label>";
    $menuStr .= "<ul class='nav nav-list tree'>"
            . "<li><div style='vertical-align: middle; display:table;'>"
            . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_users_setup_help')}\"); return false;'/></div>"
            . "<div style='float:left; display:table-cell; vertical-align:middle'><a href='admin/users'>{$stringlib->get_string('system_users_setup')}</a></div>"
            . "</div></li>";
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_students)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('participants_setup_help')}\"); return false;'/></div>"
                . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='{$CFG->wwwroot}{$CFG->basedir}students'>{$stringlib->get_string('participants_setup')}</a></div>"
                . "</div></li>";
    }
    $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
            . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_criteria_setup_help')}\"); return false;'/></div>"
            . "<div class='currentmenulocation' style='float:left;display:table-cell; vertical-align:middle'>{$stringlib->get_string('string_criteria_types_label')}</div>"
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
        <link href="<?php print($CFG->wwwroot . $CFG->basedir); ?>/css/skins/all.css" rel="stylesheet">
            <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>js/jquery-2.0.3.min.js"></script>
            <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>js/jquery-ui.min.js"></script>
            <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/date.js"></script>
            <script src="<?php print($CFG->wwwroot . $CFG->basedir); ?>js/utils.js"></script>
            <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/select2.js"></script>
            <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/icheck.min.js"></script>
            <script>
// the web backend service URL
                var serviceurl = '<?php print($CFG->serviceURL); ?>';

                var currentID = 0;

                var editmode = false;

                // initialise the page
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
                        title: '<img src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/icons/dialog-question.png" />Help',
                        buttons: {
                            Ok: function () {
                                $(this).dialog("close");
                            }
                        }
                    });

                    $("#newBut").button({
                        // nice icon
                        icons: {
                            primary: "ui-icon-plusthick"
                        }

                        // what happens when the button is clicked
                    }).click(function () {
                        add_item_popup();
                    });

                    $("#new_item_dialog").dialog({autoOpen: false});
                    $("#edit_item_dialog").dialog({autoOpen: false});

                });

                /**
                 * Open a dialog box to add an item
                 * @returns {undefined} */
                function add_item_popup() {

                    $("#new_item_dialog").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('create_criteria_scale_dialog_title')); ?>',
                        width: 600,
                        open: function (event, ui) {
                            $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                            //  $(this).prepend($(this).parent().find('.ui-dialog-buttonpane'));
                        },
                        buttons: {
                            "<?php print($stringlib->get_string('create_criteria_scale_btn_lbl')); ?>":
                                    function () {
                                        $(this).dialog("close");
                                        create_item_action();
                                    },
                            Cancel: function () {
                                $(this).dialog("close");
                            }
                        }
                    });
                    validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_scale_btn_lbl')); ?>');
                }


                /**
                 * AJAX functionality to actually create a question
                 * @returns {undefined}             */
                function create_item_action() {
                    waitOn();
                    var dataObj = new Object();
                    dataObj = {action: 'addcriteriascale',
                        description: $("#item_description").val(),
                        notes: $("#item_notes").val(),
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
                 * Deletes a user
                 * @param {type} id
                 * @returns {undefined} */
                function delete_item(id) {
                    var $deleteConfirmer = $('<div></div>')
                            .html('<?php print($stringlib->get_string('really_delete_scale')); ?>')
                            .dialog({
                                title: '<?php print($stringlib->get_string('really_delete')); ?>',
                                buttons: [
                                    {
                                        text: "OK",
                                        click: function () {
                                            $(this).dialog('close');
                                            waitOn();
                                            var dataObj = new Object();
                                            dataObj = {
                                                action: 'deletecriteriascale',
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

                                                        alert('Delete criteria scale failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
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
                            $(this).parent().find('.ui-dialog-titlebar').html("<img src='<?php print($CFG->wwwroot . $CFG->basedir); ?>icons/gtk-dialog-question48.png' style='vertical-align:middle'>Help");
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

                            <button id="newBut" class="actionbutton" style="width:100%"><?php print($stringlib->get_string('new_criteriatype')); ?></button>
                            <p></p>


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
                                <?php print($listTableStr); ?>
                            </p>

                        </div>
                    </div>

                </div>

            </div>
        </div>



        <?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users)) { ?>
            <div id="new_item_dialog" >
                <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('create_criteria_scale')); ?></legend>
                    <div>
                        <label for="item_text"><?php print($stringlib->get_string('assessment_criteria_scale_description')); ?>(required)</label><br/>
                        <input type="text" id="item_description" name="item_description" required='required' style="width: 100%" onkeyup="validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_scale_btn_lbl')); ?>')" onchange="  validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_scale_btn_lbl')); ?>')"><br/>
                            <label for="item_notes"><?php print($stringlib->get_string('assessment_criteria_scale_notes')); ?>(required)</label><br/>
                            <textarea id="item_notes" name="item_notes" required='required' style="width: 100%" onkeyup="validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_scale_btn_lbl')); ?>')" onchange="  validate_form('new_item_dialog', '<?php print($stringlib->get_string('create_criteria_scale_btn_lbl')); ?>')"></textarea><br/>
                    </div>
                </fieldset>

            </div>

        <?php } ?>

        <!-- Waiting-->
        <div id="waiting_dialog">
            <p style="text-align: center"><img src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/icons/ajax-loader.gif" style="vertical-align: middle"/><br/>Please wait...</p>
        </div>
        <!-- help-->
        <div id="help_dialog">

        </div>
    </body>
</html>

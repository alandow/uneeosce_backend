<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
include '../backend/lib/authlib.php';
include '../backend/lib/StringLib.php';
include '../backend/lib/EnumLib.php';
include '../backend/lib/Mobile_Detect.php';
require_once('../backend/config.inc.php');


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

// list users here
// generate the student table and action buttons
$from = isset($_REQUEST['from']) ? $_REQUEST['from'] : 0;
$increment = 10;

$searchstr = isset($_REQUEST['searchstr']) ? $_REQUEST['searchstr'] : null;

$enumlib = new EnumLib();


$userdata = simplexml_load_string($enumlib->getStudents($increment, $from, $searchstr));



$listTableStr = "<table><tr><th>{$stringlib->get_string('participant_num')}</th><th>{$stringlib->get_string('participant_fname')}</th><th>{$stringlib->get_string('participant_lname')}</th><th>{$stringlib->get_string('participant_photo')}</th><th>Delete</th></tr>";
foreach ($userdata->student as $student) {
    $listTableStr .= "<tr><td><a href='#' onclick='edit_student({$student->id});'>{$student->studentnum}</a></td><td>{$student->fname}</td><td>{$student->lname}</td><td><img src='{$CFG->serviceURL}?action=showstudentimage&studentid={$student->id}&getbig=false&token={$token}'></td>";

    $listTableStr .= "<td><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-cancel.png' BORDER='0' style='vertical-align: text-bottom;' onclick='delete_student({$student->id}); return false;'/></td></tr>";
}
$listTableStr .= "</table>";

$buttonStr = '<div id="nav_div" style="float: left">';
$buttonStr .= '<button id="first" style="float: left" ' . (($from == 0) ? 'disabled="disabled"' : '') . '  onclick="goFirst()">First</button>';
$buttonStr .= '<button id="prev" onclick="goPrev()" style="float: left" ' . (($from == 0) ? 'disabled="disabled"' : '') . '>Prev ' . $increment . '</button>';
$buttonStr .= '<button id="next" onclick="goNext()" style="float: left" ' . ((($from + $increment) > ($userdata->count)) ? 'disabled="disabled"' : '') . '>Next ' . $increment . '</button>';
$buttonStr .= '<button id="last" style="float: left" ' . ((($from + $increment) > ($userdata->count)) ? 'disabled="disabled"' : '') . ' onclick="goLast()">Last</button><br/></div>';
$buttonStr .="<div>{$stringlib->get_string('participants_count')}:{$userdata->count}</div>";
//  $menuStr = '<div style="display:table; width:100%"><div style="display:table-row; width:100%">';
$menuStr = "";
//$displayAdminStr = false;
$adminStr = "";

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
                . "<div class='currentmenulocation' style='float:left;display:table-cell; vertical-align:middle'>{$stringlib->get_string('participants_setup')}</div>"
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
                var a = document.getElementsByTagName("a");
                for (var i = 0; i < a.length; i++) {
                    if (!a[i].onclick && a[i].getAttribute("target") != "_blank") {
                        a[i].onclick = function () {
                            window.location = this.getAttribute("href");
                            return false;
                        }
                    }
                }
                // the web backend service URL
                var serviceurl = '<?php print($CFG->serviceURL); ?>';

                var currentID = 0;

                var editmode = false;

                var increment = 2;

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

                    waitOn();
<?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_students)) { ?>

                        $("#new_student_dialog").dialog({autoOpen: false});
                        $("#edit_student_dialog").dialog({autoOpen: false});
                        $("#upload_students_dialog").dialog({autoOpen: false});


                        $("#newBut").button({
                            // nice icon
                            icons: {
                                primary: "ui-icon-plusthick"
                            },
                            // what happens when the button is clicked
                        }).click(function (event) {
                            editmode = false;
                            // open the new user dialog
                            //$("#student_setup").dialog({autoOpen: false});
                            //$("#student_setup").dialog('destroy');
                            $("#new_student_dialog").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('new_participant')); ?>',
                                width: 600,
                                buttons: {
                                    "<?php print($stringlib->get_string('create_participant')); ?>": function () {
                                        $(this).dialog("close");
                                        new_student_action();
                                    },
                                    Cancel: function () {
                                        $(this).dialog("close");
                                    }
                                }
                            });
                            $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('create_participant')); ?>')").button("disable");

                        });

                        $("#uploadcsvBut").button({
                            // nice icon
                            icons: {
                                primary: "ui-icon-plusthick"
                            },
                            // what happens when the button is clicked
                        }).click(function (event) {
                            editmode = false;
                            // open the new user dialog
                            //$("#student_setup").dialog({autoOpen: false});
                            //$("#student_setup").dialog('destroy');
                            $("#upload_students_dialog").dialog({autoOpen: true, modal: true, title: 'Upload CSV student list',
                                width: 600,
                                buttons: {
                                    "Upload": function () {
                                        $(this).dialog("close");
                                        upload_csv_action();
                                    },
                                    Cancel: function () {
                                        $(this).dialog("close");
                                    }
                                }
                            });
                            $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('create_participant')); ?>')").button("disable");

                        });
<?php } ?>
                    $("#searchstudent_but").button({
                        icons: {
                            primary: "ui-icon-search"
                        }
                    });


                    $("#clear_search_but").button({
                        icons: {
                            primary: "ui-icon-refresh"
                        }
                    }).click(function () {
                        window.location = 'index.php?searchstr=';
                    });

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


                    waitOff();
                });

                // add a new student details. File upload courtesy of http://stackoverflow.com/questions/5392344/sending-multipart-formdata-with-jquery-ajax
                // Wish I'd know about this earlier...
<?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_students)) { ?>
                    function new_student_action() {
                        var data = new FormData();
                        // file
                        jQuery.each($('#new_student_image_file')[0].files, function (i, file) {
                            data.append('file', file);
                        });
                        data.append('student_fname', $("#new_student_fname").val());
                        data.append('student_lname', $("#new_student_lname").val());
                        data.append('student_num', $("#new_student_num").val());
                        data.append('student_email', $("#new_student_email").val());
                        //data.append('student_cohort', $("#new_student_cohort").val());
                        data.append('action', 'newstudent');
                        data.append('token', '<?php print($token); ?>');
                        waitOn();
                        $.ajax({
                            url: serviceurl,
                            data: data,
                            cache: false,
                            contentType: false,
                            processData: false,
                            type: 'POST',
                            error: function (jqXHR, textStatus, errorThrown) {
                                alert(errorThrown);
                            },
                            success: function (data) {

                                waitOff();
                                var xml;
                                if (isie()) {
                                    xml = new ActiveXObject("Microsoft.XMLDOM");
                                    xml.async = false;
                                    xml.loadXML(data);
                                } else {
                                    xml = data;
                                }
                                if ($(xml).find('error').length > 0) {
                                    alert('<?php print($stringlib->get_string('create_participant')); ?> failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                } else {
                                    location.reload(true);
                                }
                            }
                        });
                    }

                    /**
                     * Function to edit a student
                     */
                    // edit a student details. Gets student data, opens a pop-up
                    function edit_student(studentID) {
                        editmode = true;
                        currentID = studentID;
                        dataObj = {action: 'getstudentbyid', id: studentID, token: '<?php print($token); ?>'};
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
                                    //    $("#student_setup").dialog('destroy');
                                    $("#edit_student_dialog").dialog({
                                        modal: true,
                                        title: "<?php print($stringlib->get_string('update_participant')); ?>",
                                        autoOpen: true,
                                        width: 600,
                                        buttons: {
                                            "<?php print($stringlib->get_string('update_participant')); ?>": function () {
                                                $(this).dialog("close");
                                                edit_student_action();
                                            },
                                            Cancel: function () {
                                                $(this).dialog("close");
                                            }
                                        }
                                    });
                                    $("#edit_student_fname").val($(xml).find('fname').text());
                                    $("#edit_student_lname").val($(xml).find('lname').text());
                                    $("#edit_student_num").val($(xml).find('studentnum').text());
                                    $("#edit_student_email").val($(xml).find('email').text());
                                    //$("#edit_student_cohort").val($(xml).find('cohort').text());
                                    $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('update_participant')); ?>')").button("disable");
                                    validate_form('edit_student_form', '<?php print($stringlib->get_string('update_participant')); ?>')
                                }
                            }
                        });
                    }

                    // Action to update a student record in the database
                    function edit_student_action() {
                        waitOn();
                        var dataObj = new FormData();
                        // file
                        jQuery.each($('#edit_student_image_file')[0].files, function (i, file) {
                            dataObj.append('file', file);
                        });
                        dataObj.append('student_fname', $("#edit_student_fname").val());
                        dataObj.append('student_lname', $("#edit_student_lname").val());
                        dataObj.append('student_num', $("#edit_student_num").val());
                        dataObj.append('student_email', $("#edit_student_email").val());
                        //dataObj.append('student_cohort', $("#edit_student_cohort").val());
                        dataObj.append('studentID', currentID);
                        dataObj.append('action', 'updatestudent');
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
                                waitOff();
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
                                console.log(jqXHR.responseText);
                                if ($(xml).find('error').length > 0) {
                                    alert('Update student failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                } else {
                                    location.reload(true);
                                }
                            }
                        });
                    }

                    function upload_csv_action() {
                        waitOn();
                        var dataObj = new FormData();
                        // file
                        jQuery.each($('#new_student_csv_file')[0].files, function (i, file) {
                            dataObj.append('file', file);
                        });

                        dataObj.append('action', 'uploadstudentbycsv');
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
                                    alert('upload CSV failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                } else {
                                    alert('Created ' + $(xml).find('createsuccess').text() + ' successfully, failed to create ' + $(xml).find('createfail').text() + '\nUpdated ' + $(xml).find('updatesuccess').text() + ' successfully, failed to update ' + $(xml).find('updatefail').text());
                                    location.reload(true);
                                }
                                // location.reload(true);
                            }
                        });
                    }

                    // delete a student with confirmation
                    function delete_student(id) {
                        var $deleteConfirmer = $('<div></div>')
                                .html('<?php print($stringlib->get_string('really_delete_participant')); ?>')
                                .dialog({
                                    title: '<?php print($stringlib->get_string('really_delete')); ?>',
                                    buttons: [
                                        {
                                            text: "OK",
                                            click: function () {
                                                waitOn();
                                                $(this).dialog('close');

                                                var dataObj = new Object();
                                                dataObj = {
                                                    action: 'deletestudent',
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
                                                        var xml;
                                                        if (typeof data == "string") {
                                                            xml = new ActiveXObject("Microsoft.XMLDOM");
                                                            xml.async = false;
                                                            xml.loadXML(data);
                                                        } else {
                                                            xml = data;
                                                        }
                                                        if ($(xml).find('error').length > 0) {
                                                            alert('<?php print($stringlib->get_string('delete_student')); ?> failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
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
                     * s         
                     * @param {type} formID
                     * @param {type} buttonLabel
                     * @returns {undefined} */
                    function validate_form(formID, buttonLabel) {
                        var valid = true;
                        $("#" + formID + " input[type='text']").each(function () {
                            if ($(this).val().length < 1) {
                                valid = false;
                            }
                        });

                        $(".ui-dialog-buttonpane button:contains('" + buttonLabel + "')").button(valid ? "enable" : "disable");

                    }

<?php } ?>

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
                            $(this).parent().find('.ui-dialog-titlebar').html("<img src='../icons/gtk-dialog-question48.png' style='vertical-align:middle'>Help");
                        }
                    }).html("<fieldset><div>" + helpStr + "</div></fielset>");
                }

                function isie() {
                    return (/MSIE (\d+\.\d+);/.test(navigator.userAgent));
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

                function goNext() {
                    var from = <?php print(isset($_REQUEST['from']) ? $_REQUEST['from'] : '0'); ?>;
                    window.location = 'index.php?from=' + (from + <?php print($increment); ?>);
                }

                function goPrev() {
                    var from = <?php print(isset($_REQUEST['from']) ? $_REQUEST['from'] : '0'); ?>;
                    window.location = 'index.php?from=' + (((from - <?php print($increment); ?>) < 0) ? 0 : (from - <?php print($increment); ?>));
                }

                function goFirst() {
                    window.location = 'index.php?from=0';
                }

                function goLast() {
                    window.location = 'index.php?from=' +<?php print(($userdata->count) - $increment); ?>;
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
                    <?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_students)) { ?>
                        <div class="actionheader">
                            Actions
                        </div>
                        <p>
                            <button id="newBut" class="actionbutton">
                                <?php print($stringlib->get_string('new_participant')); ?>
                            </button>
                        </p>
                        <p>
                            <button id="uploadcsvBut" style=" background: rgba(255,165,0, 0.8); text-align: left">
                                Upload CSV list
                            </button>
                            <input type='image' src='../icons/dialog-question.png' BORDER='0' style='vertical-align: middle;' onclick='showHelp("<?php print($stringlib->get_string('upload_csv_help')); ?>");
                                    return false;'/>
                        </p>
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
                            <?php print($stringlib->get_string('participant_legend')); ?>
                        </div>
                        <div class="contentcontentnostatus">
                            <p>
                                <form action="" method="get" style="display: table-cell; text-align: right">
                                    Find student(s)
                                    <input type="hidden" name='from' value="<?php print($from); ?>"/>
                                    <input type="text" name='searchstr' value='<?php print (isset($_REQUEST['searchstr']) ? $_REQUEST['searchstr'] : "") ?>' class="ui-widget"/>
                                    <button id="searchstudent_but">Search</button>
                                </form>
                                <div style="display: table-cell"> 
                                    <button id="clear_search_but">Clear search</button>
                                </div>
                            </p>
                            <div id="users_list" >
                                <p>
                                    <?php
                                    print($listTableStr);
                                    print($buttonStr);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>



        <?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_students)) { ?>
            <div id="new_student_dialog" >
                <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('participant_details')); ?></legend>
                    <div>
                        <label for="new_student_num"><?php print($stringlib->get_string('participant_num')); ?></label><br/>
                        <input type="text" id="new_student_num" name="new_student_num" onkeyup="validate_form('new_student_form', '<?php print($stringlib->get_string('create_participant')); ?>')" onchange="validate_form('new_student_form', '<?php print($stringlib->get_string('create_participant')); ?>')"/><br/>
                        <label for="new_student_fname"><?php print($stringlib->get_string('participant_fname')); ?></label><br/>
                        <input type="text" id="new_student_fname" name="new_student_fname" onkeyup="validate_form('new_student_form', '<?php print($stringlib->get_string('create_participant')); ?>')" onchange="validate_form('new_student_form', '<?php print($stringlib->get_string('create_participant')); ?>')"/><br/>
                        <label for="new_student_lname"><?php print($stringlib->get_string('participant_lname')); ?></label><br/>
                        <input type="text" id="new_student_lname" name="new_student_lname" onkeyup="validate_form('new_student_form', '<?php print($stringlib->get_string('create_participant')); ?>')" onchange="validate_form('new_student_form', '<?php print($stringlib->get_string('create_participant')); ?>')"/><br/>
                        <label for="new_student_email"><?php print($stringlib->get_string('participant_email')); ?></label><br/>
                        <input type="text" id="new_student_email" name="new_student_email" onkeyup="validate_form('new_student_form', '<?php print($stringlib->get_string('create_participant')); ?>')" onchange="validate_form('new_student_form', '<?php print($stringlib->get_string('create_participant')); ?>')"/><br/>
    <!--                            <label for="new_student_cohort"><?php print($stringlib->get_string('participant_cohort')); ?></label><br/>-->
    <!--                            <select id="new_student_cohort" required="required" name="new_student_cohort" onchange="validate_form('new_student_form', '<?php print($stringlib->get_string('create_participant')); ?>');"><option value="-1">Select...</option><?php print($cohortsStr); ?></select><br/>-->
                        <label for="new_student_image_file" id="new_file_upload_label"><?php print($stringlib->get_string('participant_upload_image_lbl')); ?></label><br/>
                        <input type="file" id="new_student_image_file" accept="image/jpg,image/png" name="new_student_image_file"/>
                    </div>
                </fieldset>  
            </div>
            <div id="edit_student_dialog" >

                <fieldset style="width: 90%"><legend><?php print($stringlib->get_string('participant_details')); ?></legend>
                    <div>
                        <label for="edit_student_num"><?php print($stringlib->get_string('participant_num')); ?></label><br/>
                        <input type="text" id="edit_student_num" name="edit_student_num" onkeyup="validate_form('edit_student_form', '<?php print($stringlib->get_string('update_participant')); ?>')"  onchange="validate_form('edit_student_form', '<?php print($stringlib->get_string('update_participant')); ?>')"/><br/>
                        <label for="edit_student_fname"><?php print($stringlib->get_string('participant_fname')); ?></label><br/>
                        <input type="text" id="edit_student_fname" name="edit_student_fname" onkeyup="validate_form('edit_student_form', '<?php print($stringlib->get_string('update_participant')); ?>')"  onchange="validate_form('edit_student_form', '<?php print($stringlib->get_string('update_participant')); ?>')"/><br/>
                        <label for=edit_student_lname"><?php print($stringlib->get_string('participant_lname')); ?></label><br/>
                        <input type="text" id="edit_student_lname" name="edit_student_lname" onkeyup="validate_form('edit_student_form', '<?php print($stringlib->get_string('update_participant')); ?>')" onchange="validate_form('edit_student_form', '<?php print($stringlib->get_string('update_participant')); ?>')" /><br/>
                        <label for="edit_student_email"><?php print($stringlib->get_string('participant_email')); ?></label><br/>
                        <input type="text" id="edit_student_email" name="edit_student_email" onkeyup="validate_form('edit_student_form', '<?php print($stringlib->get_string('update_participant')); ?>')" onchange="validate_form('edit_student_form', '<?php print($stringlib->get_string('update_participant')); ?>')"/><br/>
    <!--                            <label for="edit_student_cohort"><?php print($stringlib->get_string('participant_cohort')); ?></label><br/>
                        <select id="edit_student_cohort" required="required" name="edit_student_cohort" onchange="validate_form('edit_student_form', '<?php print($stringlib->get_string('participant_cohort')); ?>');"><option value="-1">Select...</option><?php print($cohortsStr); ?></select><br/>-->
                        <label for="edit_upload_image" id="edit_file_upload_label"><?php print($stringlib->get_string('participant_upload_image_lbl')); ?></label><br/>
                        <input type="file" id="edit_student_image_file" accept="image/jpg,image/png" name="edit_student_image_file"/>
                    </div>
                </fieldset>

            </div>

            <div id="upload_students_dialog" >
                <fieldset style="width: 95%"><legend><?php print($stringlib->get_string('participant_details')); ?></legend>
                    <div>
                        <label for="new_student_csv_file" id="new_file_upload_label">CSV file for upload</label><br/>
                        <input type="file" id="new_student_csv_file" accept=".csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" name="new_student_image_file"/>
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

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

$rolesXML = simplexml_load_string($enumlib->getRolesLookup());
$rolesStr = '';
$rolesHelpStr = "<strong>Roles</strong><br/>";
foreach ($rolesXML->option as $value) {
    $rolesStr.="<option value='{$value->ID}'>{$value->description}</option>";
    $rolesHelpStr .= "{$value->notes}<br/>";
}
// list users here

$userdata = simplexml_load_string($enumlib->getUsers(isset($_REQUEST['searchstr']) ? $_REQUEST['searchstr'] : ''));

$listTableStr = "<table id='userlist'><tr><th>{$stringlib->get_string('user_login')}</th><th>{$stringlib->get_string('user_name')}</th><th>{$stringlib->get_string('user_role')}</th><th>Type</th><th style='width:50px'>Delete</th></tr>";
foreach ($userdata->user as $user) {
    $listTableStr .= "<tr><td>" . ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users) ? "<a href='#' onclick='open_edit_user_dialog({$user->id});'>{$user->username}</a>" : "{$user->username}") . "</td><td>{$user->name}</td><td>{$user->role}</td><td>{$user->type}</td>";
    $listTableStr .= "<td>" . ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users) ? "<input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-cancel.png' BORDER='0' style='vertical-align: text-bottom;' onclick='delete_user({$user->id}); return false;'/>" : "") . "</td></tr>";
}
$listTableStr .= "</table>";
//  $menuStr = '<div style="display:table; width:100%"><div style="display:table-row; width:100%">';
$menuStr = "";
//$displayAdminStr = false;
$adminStr = "";

if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_system_users) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users) || $authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_strings)) {
    $menuStr .= "<li class='showing'><label class='tree-toggle nav-header'>{$stringlib->get_string('system_administration_label')}</label>";
    $menuStr .= "<ul class='nav nav-list tree'>"
            . "<li><div style='vertical-align: middle; display:table;'>"
            . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_users_setup_help')}\"); return false;'/></div>"
            . "<div class='currentmenulocation' style='float:left; display:table-cell; vertical-align:middle'>{$stringlib->get_string('system_users_setup')}</div>"
            . "</div></li>";
    if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_view_students)) {
        $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
                . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('participants_setup_help')}\"); return false;'/></div>"
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

                    // tell the system that the system_user_setup div is a dialog


                    // set up buttons
<?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users)) { ?>

                        $("#system_user_setup").dialog({autoOpen: false, modal: true});
                        $("#newUserBut").button({
                            // nice icon
                            icons: {
                                primary: "ui-icon-plusthick"
                            },
                            // what happens when the button is clicked: opens a new user dialog
                        }).click(function (event) {
                            editmode = false;
                            $("#type").val('manual');
                            $("#fullname").prop('disabled', false).val('');
                            $("#password").prop('disabled', false).val('');
                            $("#password2").prop('disabled', false).val('');
                            $("#username").prop('disabled', false).val('');
                            validate_user_form();

                            // open the new user dialog
                            $("#system_user_setup").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('new_user')); ?>',
                                width: 500,
                                buttons: {
                                    "<?php print($stringlib->get_string('create_user')); ?>": function () {
                                        $(this).dialog("close");
                                        create_user();
                                    },
                                    Cancel: function () {
                                        $(this).dialog("close");
                                    }
                                }
                            });

                            // disable the 'create user' button
                            $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('create_user')); ?>')").button("disable");
                        });
<?php } ?>

                    // search user button
                    $("#search_users_but").button({
                        // nice icon
                        icons: {
                            primary: "ui-icon-search"
                        }
                    });
                    $("#search_users_input").val('<?php print(isset($_REQUEST['searchstr']) ? $_REQUEST['searchstr'] : ''); ?>');


                });

                var searchingusers = false;
                var xhr = new Object();
                function listUsers(searchstr) {
                    if (!searchingusers) {
                        // $('#searchfeedback').show();
                        searchingusers = true;
                        xhr = $.ajax({
                            url: serviceurl + "?action=listusers&searchstr=" + searchstr + '&token=<?php print($token); ?>',
                            dataType: isie() ? "text" : "xml",
                            error: function (jqXHR, textStatus, errorThrown) {
                                if (errorThrown != 'abort') {
                                    alert(errorThrown);
                                }
                                searchingusers = false;
                            },
                            success: function (data) {
                                searchingusers = false;
                                var xml;
                                if (typeof data == "string") {
                                    xml = new ActiveXObject("Microsoft.XMLDOM");
                                    xml.async = false;
                                    xml.loadXML(data);
                                } else {
                                    xml = data;
                                }
                                $('#userlist').html("<?php print("<tr><th>{$stringlib->get_string('user_login')}</th><th>{$stringlib->get_string('user_name')}</th><th>{$stringlib->get_string('user_role')}</th><th>Type</th><th style='width:50px'>Delete</th></tr>"); ?>");
                                // console.log(data);
                                $(xml).find('user').each(function () {
                                    //$('#userlist').append();
                                    var rowStr = '<tr><td>';
<?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users)) { ?>
                                        rowStr += "<a href='#' onclick='open_edit_user_dialog(" + ($(this).find('id')).text() + ");'>" + ($(this).find('username')).text() + "</a>";
<?php } else { ?>
                                        rowStr += ($(this).find('username')).text();
<?php } ?>
                                    rowStr += '</td><td>' + ($(this).find('name')).text() + '</td><td>' + ($(this).find('role')).text() + '</td><td>' + ($(this).find('type')).text() + '</td><td>';

<?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users)) { ?>
                                        rowStr += "<input type='image' src='<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/icons/gtk-cancel.png' BORDER='0' style='vertical-align: text-bottom;' onclick='delete_user(" + ($(this).find('id')).text() + "); return false;'/>";
<?php } ?>

                                    rowStr += '</tr>';
                                    //$listTableStr .= "<td>" . ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users) ? "<input type='image' src='{$CFG->wwwroot}{$CFG->basedir}/icons/gtk-cancel.png' BORDER='0' style='vertical-align: text-bottom;' onclick='delete_user({$user->id}); return false;'/>" : "") . "</td></tr>";
                                    $('#userlist').append(rowStr);
                                });
                                //$('#searchfeedback').hide();
                            }
                        });
                    }
                }

<?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users)) { ?>

                    // functionality to create a user
                    function create_user() {
                        waitOn();

                        var dataObj = new Object();
                        dataObj = {action: 'newuser', user_username: $("#username").val(), user_fullname: $("#fullname").val(), user_roleid: $("#role").val(), user_password: $("#password").val(), type: $("#type").val(), token: '<?php print($token); ?>'};
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
                                    alert('create user failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                    //eraseCookie('token');
                                    //window.location = "../index.php";
                                } else {
                                    location.reload(true);
                                }
                            }
                        });
                    }

                    /**
                     *Gets data for a user, opens a dialog
                     */
                    function open_edit_user_dialog(userID) {
                        // some feedback for waiting
                        $("#system_user_setup").dialog("close");
                        $("#fullname").prop('disabled', false);
                        $("#password").prop('disabled', false);
                        $("#password2").prop('disabled', false);
                        $("#username").prop('disabled', false);
                        waitOn();
                        // first, get some user details to populate the user edit form from the webservice
                        currentID = userID;
                        // we're in edit mode
                        editmode = true;
                        var dataObj = new Object();
                        dataObj = {action: 'getuserbyid', id: userID, token: '<?php print($token); ?>'};
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
                                    $("#system_user_setup").dialog({autoOpen: true, modal: true, title: '<?php print($stringlib->get_string('edit_user')); ?>',
                                        width: 500,
                                        buttons: {
                                            "<?php print($stringlib->get_string('update_user')); ?>": function () {
                                                $(this).dialog("close");
                                                update_user_action();
                                            },
                                            Cancel: function () {
                                                $(this).dialog("close");
                                            }
                                        }
                                    });
                                    $("#type").val($(xml).find('type').text());
                                    $("#fullname").val($(xml).find('name').text());
                                    $("#role").val($(xml).find('roleID').text());
                                    $("#username").val($(xml).find('username').text());
                                    $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('update_user')); ?>')").button("disable");
                                    validate_user_form();
                                }
                            }
                        });
                    }

                    /**
                     * Perform the update user acrton
                     
                     * @returns {undefined} */
                    function update_user_action() {
                        waitOn();

                        var dataObj = new Object();
                        dataObj = {action: 'updateuser',
                            id: currentID,
                            user_username: $("#username").val(),
                            user_type: $("#type").val(),
                            user_fullname: $("#fullname").val(),
                            user_roleid: $("#role").val(),
                            user_password: $("#password").val(),
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
                                    alert('update user failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                                    waitOff();
                                    //eraseCookie('token');
                                    //window.location = "../index.php";
                                } else {
                                    location.reload(true);
                                }
                            }
                        });
                    }

                    /**
                     * Deletes a user
                     * @param {type} id
                     * @returns {undefined} */
                    function delete_user(id) {
                        var $deleteConfirmer = $('<div></div>')
                                .html('<?php print($stringlib->get_string('really_delete_user')); ?>')
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
                                                    action: 'deleteuser',
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

                                                            alert('Delete User failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
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



                    function validate_user_form() {


                        if (editmode) {
                            switch ($("#type").val()) {
                                case 'manual':
                                    $("#fullname").prop('disabled', false);
                                    $("#password").prop('disabled', false);
                                    $("#password2").prop('disabled', false);
                                    if (($("#fullname").val().length > 0) && ($("#username").val().length > 0) && ($("#role").val() > -1) && ($("#password").val() == $("#password2").val())) {
                                        $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('update_user')); ?>')").button("enable");
                                    } else {
                                        $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('update_user')); ?>')").button("disable");
                                    }
                                    break;
                                case 'ad':
                                    $("#username").prop('disabled', true);
                                    $("#fullname").prop('disabled', true);
                                    $("#password").prop('disabled', true);
                                    $("#password2").prop('disabled', true);
                                    if (($("#username").val().length > 0) && ($("#role").val() > -1)) {
                                        $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('update_user')); ?>')").button("enable");
                                    } else {
                                        $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('update_user')); ?>')").button("disable");
                                    }
                                    break;

                                default:
                                    break;
                            }
                        } else {


                            switch ($("#type").val()) {
                                case 'manual':
                                    $("#fullname").prop('disabled', false);
                                    $("#password").prop('disabled', false);
                                    $("#password2").prop('disabled', false);

                                    if (($("#fullname").val().length > 0) && ($("#username").val().length > 0) && ($("#role").val() > -1) && ($("#password").val().length > 0) && ($("#password").val() == $("#password2").val())) {
                                        $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('create_user')); ?>')").button("enable");
                                        //                $("#userupdatebut").button({ disabled: false });
                                        //                $("#userupdatebut").button({ disabled: false });
                                    } else {
                                        $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('create_user')); ?>')").button("disable");
                                    }
                                    break;
                                case 'ad':
                                    $("#fullname").prop('disabled', true);
                                    $("#password").prop('disabled', true);
                                    $("#password2").prop('disabled', true);
                                    if (($("#username").val().length > 0) && ($("#role").val() > -1)) {
                                        $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('create_user')); ?>')").button("enable");
                                    } else {
                                        $(".ui-dialog-buttonpane button:contains('<?php print($stringlib->get_string('create_user')); ?>')").button("disable");
                                    }
                                    break;

                                default:
                                    break;
                            }
                        }
                    }



<?php } ?>

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

                            <button id="newUserBut" class="actionbutton" style="width:100%"><?php print($stringlib->get_string('new_user')); ?></button>
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
                            <?php print($stringlib->get_string('userlist_legend')); ?>
                        </div>
                        <div class="contentcontentnostatus">
                            <p><input id="search_users_input" class='ui-widget' type='text' onkeyup="listUsers($('#search_users_input').val());"/>Filter by login or name </p>
                            <div id="users_list" >
                                <?php print($listTableStr); ?>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>



        <?php if ($authlib->user_has_capability($loggedinuserdata->userID, $authlib::roles_edit_system_users)) { ?>
            <div id="system_user_setup" >

                <fieldset style="width: 90%"><legend id="systemusersetuplegend">User details</legend>
                    <div>
                        <label for="type">Login Type</label><br/>
                        <select id="type" name="type" onchange="validate_user_form();">
                            <option value='manual'>Manual</option>
                            <option value='ad'>Active Directory</option>
                        </select>
                    </div>
                    <div>
                        <label for="username"><?php print($stringlib->get_string('user_login')); ?></label><br/>
                        <input type="text" id="username" onkeyup="validate_user_form()" onchange="validate_user_form()" name="user_username">
                    </div>

                    <div>
                        <label for="role"><?php print($stringlib->get_string('user_role')); ?></label><input type='image' src='<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/icons/dialog-question.png' BORDER='0' style='vertical-align: middle; display:table-cell' onclick='showHelp("<?php print($rolesHelpStr); ?>");
                                return false;'/><br/>
                        <select id="role" name="role" onchange="validate_user_form();"><option value='-1'>Select a role...</option><?php print($rolesStr); ?></select>
                    </div>
                    <div>
                        <label for="fullname"><?php print($stringlib->get_string('user_full_name')); ?></label><br/>
                        <input type="text" id="fullname" name="user_fullname" onkeyup="validate_user_form()" onchange="validate_user_form()">
                    </div>
                    <div>
                        <label for="password"><?php print($stringlib->get_string('user_password')); ?></label><br/>
                        <input type="password" onkeyup="validate_user_form();" onchange="validate_user_form()"  name="user_password" id="password">
                    </div>
                    <div>
                        <label for="password2">Confirm</label><br/>
                        <input type="password" onkeyup="validate_user_form();" onchange="validate_user_form()"  id="password2">
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

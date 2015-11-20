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
            . "<div style='float:left;display:table-cell; vertical-align:middle'><a href='{$CFG->wwwroot}{$CFG->basedir}admin/criteriatypes'>{$stringlib->get_string('string_criteria_types_label')}</a></div>"
            . "</div></li>";
    $menuStr .= "<li><div style='vertical-align: middle; display:table;'>"
            . "<div style='display:table-cell; vertical-align:middle'><input type='image' src='{$CFG->wwwroot}{$CFG->basedir}icons/dialog-question.png' BORDER='0'  onclick='showHelp(\"{$stringlib->get_string('system_labels_help')}\"); return false;'/></div>"
            . "<div class='currentmenulocation' style='float:left;display:table-cell; vertical-align:middle'>{$stringlib->get_string('string_management_form_label')}</div>"
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
        <link type="text/css" href="../../css/editablegrid-2.0.1.css" rel="stylesheet" />
        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>js/jquery-2.0.3.min.js"></script>
        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/date.js"></script>
        <script src="<?php print($CFG->wwwroot . $CFG->basedir); ?>js/utils.js"></script>
        <script type="text/javascript" src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/select2.js"></script>
        <script src="../../js/editablegrid.js"></script>
        <script src="../../js/editablegrid_utils.js"></script>
        <script src="../../js/editablegrid_renderers.js" ></script>
        <script src="../../js/editablegrid_editors.js" ></script>
        <script src="../../js/editablegrid_validators.js" ></script>

        <script>
            // the web backend service URL
            var serviceurl = '<?php print($CFG->serviceURL); ?>';

            var stringsgrid;



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

                stringsgrid = new DatabaseGrid();
            });


            /**
             *  highlightRow and highlight are used to show a visual feedback. If the row has been successfully modified, it will be highlighted in green. Otherwise, in red
             */
            function highlightRow(rowId, bgColor, after)
            {
                var rowSelector = $("#" + rowId);
                rowSelector.css("background-color", bgColor);
                rowSelector.fadeTo("normal", 0.5, function () {
                    rowSelector.fadeTo("fast", 1, function () {
                        rowSelector.css("background-color", '');
                    });
                });
            }

            function highlight(div_id, style) {
                highlightRow(div_id, style == "error" ? "#e5afaf" : style == "warning" ? "#ffcc00" : "#8dc70a");
            }


            function fetchGrid(grid, table, columns) {
                // call a PHP script to get the data
                grid.editableGrid.loadXML(serviceurl + "?token=<?php print($token); ?>&action=getgrid&showdelete=false&table=" + table + "&columns=" + columns);
            }

            /**
             updateCellValue calls the PHP script that will update the database. 
             */
            function updateCellValue(editableGrid, rowIndex, columnIndex, oldValue, newValue, row, onResponse)
            {
                //   waitOn();
                $.ajax({
                    url: serviceurl,
                    type: 'POST',
                    dataType: isie() ? "text" : "xml",
                    data: {
                        table: editableGrid.name,
                        action: "updategrid",
                        token: "<?php print($token); ?>",
                        id: rowIndex,
                        newvalue: editableGrid.getColumnType(columnIndex) == "boolean" ? (newValue ? 1 : 0) : newValue,
                        colname: editableGrid.getColumnName(columnIndex),
                        coltype: editableGrid.getColumnType(columnIndex)
                    },
                    success: function (data)
                    {
                        //   waitOff();
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        if ($(xml).find('error').length > 0) {
                            alert('Update failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            editableGrid.setValueAt(rowIndex, columnIndex, oldValue);
                        } else {
                            highlight(row.id, "ok");

                        }

                    },
                    error: function (XMLHttpRequest, textStatus, exception) {
                        //   waitOff();

                        alert("Ajax failure\n" + exception);
                    },
                    async: true
                });

            }

            function DatabaseGrid()
            {
                this.editableGrid = new EditableGrid("dict", {
                    tableLoaded: function () {
                        stringsgrid.initializeGrid(this);
                        //stringsgrid.editableGrid.renderGrid("string_list", "string_list", 'dict');
                    },
                    modelChanged: function (rowIndex, columnIndex, oldValue, newValue, row) {

                        //alert('modelChanged');
                        updateCellValue(this, this.getDisplayValueAt(rowIndex, 0), columnIndex, oldValue, newValue, row);
                    }
                });
                fetchGrid(this, 'dict', 'string,definition_en,definition_cy');

            }

            DatabaseGrid.prototype.initializeGrid = function (grid) {

                // render for the action column
                //            with (grid) {
                //                setCellRenderer("Delete", new CellRenderer({render: function(cell, value) {
                //                        // this action will remove the row, so first find the ID of the row containing this cell 
                //                        var rowId = grid.getRowId(cell.rowIndex);
                //                        cell.style = 'width:30px;';
                //                        cell.innerHTML = "<a onclick=\"if (confirm('Are you sure you want to delete this entry ?" + grid.getDisplayValueAt(cell.rowIndex, 0) + " ')) { deleteRow('doctors', " + grid.getDisplayValueAt(cell.rowIndex, 0) + "); } \" style=\"cursor:pointer\">" +
                //                                "<img src='../icons/edit-delete.png'\" border=\"0\" alt=\"delete\" title=\"Delete row\"/></a>";
                //                    }}));
                //            }
                grid.renderGrid("string_list", "string_list", 'dict');
            };

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
                            <p>
                                <div id="instructions_div"><?php print($stringlib->get_string('string_management_instructions')); ?></div>
                            </p>
                            <p>
                                <div id="string_list" >

                                </div>
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

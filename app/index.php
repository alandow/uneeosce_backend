<!--
To change this template, choose Tools | Templates
and open the template in the editor.
-->
<!DOCTYPE html>
<?php
//ini_set('display_errors', '1');
// bring in the configuration file
require_once(dirname(__FILE__) . "/../backend/config.inc.php");
include dirname(__FILE__) . "/../backend/lib/authlib.php";
include dirname(__FILE__) . "/../backend/lib/EnumLib.php";
include dirname(__FILE__) . "/../backend/lib/AssessmentLib.php";
// check token
$token = $_COOKIE['uneeoscetoken'];
$formid = $_REQUEST['id'];
$authlib = new authlib();
$authresult = '';

// checking token
$loggedinuserdata = new SimpleXMLElement($authlib->getDetailsByToken($token));

if (strlen($loggedinuserdata->error) > 1) {
    // print_r($loggedinuserdata);
    //$headerStr = $loggedinuserdata->name;
    header("Location: ../index.php");
    exit();
}

// getting form definition
$titleStr = '';
$enumlib = new EnumLib();
$formdef = simplexml_load_string($enumlib->getExamInstanceQuestionsByID($formid));
$titleStr = $formdef->overview->data->instance->name;
$scaleXML = simplexml_load_string($enumlib->getCriteriaScaleItems($formdef->overview->data->instance->scale_id));
$formtable = "<table style='width:100%' id='assessment_form_table'><tr style='background-color:#F3F3F3;'><th style='width:20%'>Assessment item</th><th colspan='{$scaleXML->count()}' style='width:40%'>Quality of Performance</th><th>Comments</th></tr>";


$count = 1;
$i = 0;
$questionArr = "[";

foreach ($formdef->questiondata->question as $question) {
    switch ($question->type) {
        case '0':
            $formtable.="<tr id='qrow_{$question->id}' style='background-color:rgba(255, 200, 200, 1)'><td><span >{$count}) {$question->text}</span></td>";
            $i = 0;
            foreach ($scaleXML->item as $item) {
                $formtable.="<td><input type='radio' name='choice_id_{$question->id}' id='choice_id_{$question->id}_$i' value='{$item->value}' data-iconpos='left' data-theme='e' needs_comment='{$item->needs_comment}' onclick='markQuestion($question->id)'/><label for='choice_id_{$question->id}_$i'>{$item->short_description}</label></td>";
                $i++;
            }
            $formtable.= "<td><input type='text' id='comments_id_{$question->id}' onkeyup='validateAssessment()' onblur='submitcomment($question->id)'></input></td></tr>";
            $questionArr.="{$question->id},";

            $count++;
            break;
        case '1':
            $formtable.="<tr id='qrow_{$question->id}' style='background-color:rgba(255, 200, 200, 1)'><td><span >{$count}) {$question->text}</span></td>";
            $i = 0;
            foreach ($scaleXML->item as $item) {
                   $formtable.="<td><input type='radio' name='choice_id_{$question->id}' id='choice_id_{$question->id}_$i' value='{$item->value}' data-iconpos='left' data-theme='e' needs_comment='{$item->needs_comment}' onclick='markQuestion($question->id)'/><label for='choice_id_{$question->id}_$i'>{$item->short_description}</label></td>";
                $i++;
            }
            $formtable.= "<td><input type='text' id='comments_id_{$question->id}' onkeyup='validateAssessment()' onblur='submitcomment($question->id)'></input></td></tr>";
            $questionArr.="{$question->id},";

            $count++;
            break;
        default:
            break;
    }
}
if (strlen($questionArr) > 1) {
    $questionArr = substr_replace($questionArr, "", -1);
}
$questionArr .= "]";

$formtable .= "</table>";


// Check for an already started assessmemnt by this assessor 
$assesslib = new AssessmentLib();
$currentexamXMLStr = $assesslib->checkAssessment($formid, $loggedinuserdata->userID);
$currentexam = simplexml_load_string($currentexamXMLStr);
//print('Current exam is:'.$currentexamXMLStr);
//
//if (isset($currentexam->questiondata)) {
//    print('Current exam is:');
//    print_r($currentexam);
//} else {
//    print('Got nothing');
//}
?>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>OSCE Rating Form</title> 
        <meta name="viewport" content="width=device-width, initial-scale=1"> 

        <link type="text/css" href="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/css/une-theme/jquery-ui-1.10.4.custom.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/css/jquery.mobile-1.3.0.css" />

        <link rel="stylesheet" href="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/css/jquery.mobile.structure-1.3.0.min.css" />

        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/js/jquery-1.10.2.min.js"></script>
        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/js/jquery.blockUI.js"></script>


        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/js/jquery.mobile-1.3.0.js"></script>
<!--<script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/js/jquery-ui-1.10.1.custom.min.js"></script>-->
        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/js/mobiscroll-2.0.3.custom.min.js"></script>
        <script src="<?php print("{$CFG->wwwroot}{$CFG->basedir}"); ?>/js/jquery.imagesloaded.js"></script>

<!--<script src="<?php print($CFG->wwwroot . $CFG->basedir); ?>/js/utils.js"></script>-->
        <style>
            /*            Styles*/


            .heading{
                border-top: 1px solid grey;
                font-size: 1.2em;
                font-weight: bold;
                text-align: left;
                background-color: #FFF;
                padding: 0;
            }

            .ui-header .ui-title {
                margin-right: 10%;
                margin-left: 10%;
                height: 38px;
            }

            .questiontext{
                border-top: 1px solid grey;
                border-bottom: none;
                font-size: 1.2em;
                font-weight: bold;
                font-style: normal;
                text-align: left;
                background-color: #F3F3F3;
                padding: 0;
            }

            table{
                border-collapse: collapse;
            }
            td{
                border-left: 1px solid grey;
                border-top: none!important;
                border-bottom: none!important;
                padding: 0;
            }
            tr{

                border-bottom: 1px solid grey;
                border-top: none!important;
                padding: 0;
            }
            th{
                /*                border-bottom: 1px solid grey;
                                border-top: none;*/
                border-left: 1px solid grey;
                font-style: italic;
                font-size: large;
                height: 50px;
            }

            .ui-icon-unlock { 
                background-image: url("../icons/lock.png");
            }

            .ui-icon-lock {
                background-image: url("../icons/unlock.png");
            }

            .ui-page { background:  #D6D6D6;}

            .ui-page-active{ background:  #D6D6D6;}

            .bottomnav .ui-btn-inner .ui-btn-text{
                font-weight: bold;
                font-size: 1.9em;
            }

            .checkBox{
                width: 18px;
                height: 18px;
                background: #d9d9d9;
                border-radius: 9px;  
                margin: 0 auto;
                margin-bottom: 5px;
            }

            .not-checked, .checked {
                background-image: url("http://www.fajrunt.org/icons-18-white.png");
                background-repeat: no-repeat;
            }

            .not-checked {
                background-position: 0 0;       
                background-color:#d9d9d9;
            }

            .checked {
                background-position: 0 0;    
                background-color:#6294bc;
            }

            .hidden {
                display: none;
            }
        </style>

        <script>

            // the web backend service URL
            var serviceurl = '<?php print($CFG->serviceURL); ?>';
            // a canvas element to hold image data. Used for signatures
            var canvas; // = '';

// the current session (if set)

            var sessionID = <?php print(isset($currentexam->overview->sessionid) ? $currentexam->overview->sessionid : '-1'); ?>;
            // a persistent datastore
            var dataStore = window.localStorage;

            // all student data
            var student_data = '';
            // the current student data. Saved as a cookie if the student is locked in
            var current_student_data = '';
            // the current station name
            var station_name = '';
            // are we looking at a currently locked student?
            var locked = false;
            // current assessment data, saved as a cookie
            //      var current_assessment_data = '<data></data>';
            // an array to keep track of the assessment radiobutton groups. Used for validation and marking
            var assessment_radiobutton_names = new Array();
            var assessment_commentsfields_names = new Array();
            // a handy variable for iterating
            var i = 0;
            // an interval variable, so that we can 'ping' the server every few seconds to keep the connection alive.
            var heartbeatvar;
            var studentimage;
            // Kick it all off...
            $(document).ready(function() {
                inlineWaitOff();
                questionsIDsArr = <?php print($questionArr); ?>;
                studentimage = document.getElementById('student_img');
                // display control. Block things before they should be accessible
                $('#assessment_tab_tab').block({"message": 'Complete student details first'});
                $('#rating_tab_tab').block({"message": 'Complete assessment first'});
                // hide some things until they're needed
                $("#confirm_student_but").parent().hide();
                // $("#student_img").hide();
                $("#student_img").attr('class', 'hidden');
                $('#unlock_student_but').parent().hide();
                $('#goto_rating_tab_but').parent().hide();
                $('#goto_assessment_but').parent().hide();
                $("#additional_rating_contain").hide();
                // disable the save assessment button
                //      $('#saveAssessmentBut').button('disable');
                // hide comment fields initially
//                $('[id^="comments_id_"]').each(function() {
//                    $(this).parent().hide()
//                });

                // add a listener to the search student button
                $('#seach_student_but').click(function() {
                    showStudentChoicePopup();
                });
                $('#headertext').html('Station: <?php print($titleStr); ?>');
//                //   $('[id^="comments_id_"]').textinput("disable");

//                    changeTabTo('#overall_tab');
                if (dataStore.currentstudentdata) {
                    console.log('got some preexisting student data');

                    current_student_data = dataStore.currentstudentdata;//
                    populateStudentFields();

                    $('.student_details_button').parent().hide();
                    $('.student_details').parent().attr("disabled", "disabled");
                    $('#assessment_tab_tab').unblock();
                    $('#headertext').html($('#headertext').html() + ' (Locked in)');
                    $('#goto_assessment_but').parent().show();
                    $("#confirm_student_but").parent().show();
                    $("#confirm_student_but").text('Unlock').button('refresh');
                    locked = true;
                    changeTabTo('#assessment_tab');
                    if (dataStore.currentexamdata) {
                        console.log('got some preexisting exam data');
                        var currentexamdata = dataStore.currentexamdata;
                        currentexamdataxmlDoc = $.parseXML(currentexamdata);
                        $currentexamdata = $(currentexamdataxmlDoc);
                        $currentexamdata.find('answer').each(function() {
                            $('input:radio[name=choice_id_' + $(this).find('question_id').text() + '][value=' + $(this).find('value').text() + ']').prop('checked', true);
                            $('input:radio[name=choice_id_' + $(this).find('question_id').text() + '][value=' + $(this).find('value').text() + ']').checkboxradio("refresh");
                            $('#comments_id_' + $(this).find('question_id').text()).val($(this).find('comment').text())
                            //$('#comments_id_' + $(this).find('id').text()).val($(this).find('comment').text()).textinput("enable");


                        });
                    }
                    // validateSubmitForm();
                    validateAssessment();
                    validateSubmitForm();
                    //$(document).trigger("create");
                } else {
                    changeTabTo('#overall_tab');
                }

                //alert(readCookie('currentstudent'));
                //if (readCookie('currentstudent') != null) {
                //   current_student_data = $.parseXML(readCookie('currentstudent'));
                //   console.log(readCookie('currentstudent'));
                // check for if the student's already locked
                //  var dataObj = new Object();
                //  dataObj = {action: 'checklockstudent', id: $(current_student_data).find('id').text(), token: '<?php print($token); ?>'};
//                    $.ajax({
//                        url: serviceurl,
//                        type: 'post',
//                        data: dataObj,
//                        dataType: isie() ? "text" : "xml",
//                        error: function(jqXHR, textStatus, errorThrown) {
//                            alert(errorThrown);
//                        },
//                        success: function(data) {
//                            waitOff();
//                            var xml;
//                            if (typeof data === "string") {
//                                xml = new ActiveXObject("Microsoft.XMLDOM");
//                                xml.async = false;
//                                xml.loadXML(data);
//                            } else {
//                                xml = data;
//                            }
//                            //   console.log($(xml).find('error').length);
//                            if ($(xml).find('error').length > 0) {
//                                alert('Could not check the lock:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
//                            } else {
//                                // Start the assessment here
//                                waitOn();
//                                var dataObj = new Object();
//                                dataObj = {action: 'startassessment', studentid: $(current_student_data).find('id').text(), formid: <?php print($_REQUEST['id']); ?>, token: '<?php print($token); ?>'};
//                                $.ajax({
//                                    url: serviceurl,
//                                    type: 'post',
//                                    data: dataObj,
//                                    dataType: isie() ? "text" : "xml",
//                                    error: function(jqXHR, textStatus, errorThrown) {
//                                        alert(errorThrown);
//                                    },
//                                    success: function(data) {
//                                        waitOff();
//                                        var xml;
//                                        if (typeof data === "string") {
//                                            xml = new ActiveXObject("Microsoft.XMLDOM");
//                                            xml.async = false;
//                                            xml.loadXML(data);
//                                        } else {
//                                            xml = data;
//                                        }
//                                        waitOff();
//                                        //   console.log($(xml).find('error').length);
//                                        if ($(xml).find('error').length > 0) {
//                                            alert('Could not list students:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
//                                            //window.location = "../index.php";
//                                        } else {
//                                            console.log($(xml).text());
//                                            //   student_data = xml;
//                                            if ($(xml).find('student').length > 0) {
//                                                var studentSelectHTML = ' <ul data-role="listview" data-inset="true" style="min-width:210px;" data-theme="b">';
//                                                $(xml).find('student').each(function() {
//                                                    studentSelectHTML += "<li class=\"studentselect\" ident='" + $(this).find('studentnum').text() + "'><a href=\"#\">" + $(this).find('fname').text() + ' ' + $(this).find('lname').text() + "<br/>" + $(this).find('studentnum').text() + "<img src=\"" + serviceurl + '?action=showstudentimage&studentid=' + $(this).find('id').text() + "&getbig=false&token=<?php print($token); ?>\"/></a></li>";
//
//                                                });
//
//                                                $("#student_choice_popup").html(studentSelectHTML);
//                                                // we do this to refresh the jquery-mobiley goodness: see http://xomino.com/2012/04/24/re-styling-dynamic-content-with-jquery-mobile/
//                                                $('#student_choice_popup').trigger("create");
//                                                $("#student_choice_popup").popup("open");
//                                                $('.studentselect').click(function(data) {
//                                                    // alert($(this).attr('ident'));
//                                                    chooseStudent($(this).attr('ident'));
//                                                    $("#student_choice_popup").popup("close");
//                                                });
//                                            } else {
//                                                showError('There are no available students');
//                                            }
//                                        }
//
//                                        //alert(data);
//
//                                    }
//                                });
//                              
//                                //changeTabTo('#overall_tab');
//                            }
//                            //alert(data);
//
//                        }
//                    });



                //   }

// set up signature pad
                canvas = document.getElementById("thecanvas");
                context = canvas.getContext('2d');
                context.fillStyle = "rgba(200, 200, 200, 1)";
                context.fillRect(0, 0, 600, 200);
                position = getOffset(document.getElementById('thecanvas'));
                // set up listeners for the signature pad for a computer based browser
                $("#thecanvas").mousedown(handleMouseDown);
                $("#thecanvas").mouseup(handleMouseUp);
                $("#thecanvas").mousemove(handleMouseMove);
                // set up listeners for the signature pad for a mobile browser
                document.getElementById('thecanvas').addEventListener('touchstart', handleMouseDown, false);
                document.getElementById('thecanvas').addEventListener('touchmove', handleMouseMove, false);
                document.getElementById('thecanvas').addEventListener('touchend', handleMouseUp, false);
                $("#search-student").keyup(function() {
                    bindSearchBut();
                });
                // heartbeat function, as an attempt to keep teh wifi going
                heartbeatvar = window.setInterval(function() {
                    //heartbeat();
                }, 5000);
                //   heartbeat();

                //$(document).trigger("create");
            });
            /**
             * Get a list of students and display a pop-up to choose
             */
            function showStudentChoicePopup() {
                $("#confirm_student_but").parent().hide();
                $("#student_img").attr('class', 'hidden');
                waitOn();
                var dataObj = new Object();
                dataObj = {action: 'liststudentsbysearchstrforform', searchstr: $("#search-student").val(), formid: <?php print($_REQUEST['id']); ?>, token: '<?php print($token); ?>'};
                $.ajax({
                    url: serviceurl,
                    type: 'post',
                    data: dataObj,
                    dataType: isie() ? "text" : "xml",
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert(errorThrown);
                        waitOff();
                    },
                    success: function(data) {
                        waitOff();
                        var xml;
                        if (typeof data === "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        waitOff();
                        //   console.log($(xml).find('error').length);
                        if ($(xml).find('error').length > 0) {
                            alert('Could not list students:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            //window.location = "../index.php";
                        } else {
                            console.log($(xml).text());
                            student_data = xml;
                            if ($(xml).find('student').length > 0) {
                                var studentSelectHTML = ' <ul data-role="listview" data-inset="true" style="min-width:210px;" data-theme="b">';
                                $(xml).find('student').each(function() {
                                    studentSelectHTML += "<li class=\"studentselect\" ident='" + $(this).find('studentnum').text() + "'><a href=\"#\">" + $(this).find('fname').text() + ' ' + $(this).find('lname').text() + "<br/>" + $(this).find('studentnum').text() + "<img src=\"" + serviceurl + '?action=showstudentimage&studentid=' + $(this).find('id').text() + "&getbig=false&token=<?php print($token); ?>\"/></a></li>";
                                });
                                $("#student_choice_popup").html(studentSelectHTML);
                                // we do this to refresh the jquery-mobiley goodness: see http://xomino.com/2012/04/24/re-styling-dynamic-content-with-jquery-mobile/
                                $('#student_choice_popup').trigger("create");
                                $("#student_choice_popup").popup("open");
                                // what happens when the student is clicked
                                $('.studentselect').tap(function(data) {
                                    // alert($(this).attr('ident'));
                                    chooseStudent($(this).attr('ident'));
                                    $("#student_choice_popup").popup("close");
                                });
                            } else {
                                showError('There are no available students');
                            }
                        }

                        //alert(data);

                    }
                });
            }

            // Handle the choosing of a student from the popup
            function chooseStudent(studentid) {
                console.log('choosing student' + studentid);
                xmlDoc = $(student_data);
                $(xmlDoc).find('student').each(function() {
                    if ($(this).find('studentnum').text() === studentid) {
                        current_student_data = (new XMLSerializer().serializeToString(this));
                        dataStore.setItem('currentstudentdata', (new XMLSerializer().serializeToString(this)));
                        populateStudentFields();
                    }
                });
            }

            // pupulate the student fields on teh first page

            function populateStudentFields() {
                waitOn();
                xmlDoc = $.parseXML(current_student_data);
                $xml = $(xmlDoc);
                $("#student_img").attr('class', '');
                $('#student_name_disp').html($xml.find('fname').text() + ' ' + $xml.find('lname').text());
                $('#student_id_disp').html($xml.find('studentnum').text());
                $('#headertext').html('Station: <?php print($titleStr); ?> <br/> Student: ' + $xml.find('fname').text() + ' ' + $xml.find('lname').text());
                // $('#student_img').attr('src', serviceurl + '?action=showstudentimage&studentid=' + $(current_student_data).find('id').text() + '&getbig=true&token=<?php print($token); ?>');
                $('#student_img').attr('class', '');
                //$("#confirm_student_but").parent().show();
// console.log(serviceurl + '?action=showstudentimage&studentid=' + $(current_student_data).find('id').text() + '&getbig=true&token=<?php print($token); ?>');
//                $("#student_img").imagesLoaded(function() {
//
//                    waitOff();
//                    $("#confirm_student_but").parent().show();
//
//                });
//                //$('#search-student').val($(current_student_data).find('studentnum').text());

                //   $('#student_id_disp').html($(current_student_data).find('studentnum').text());
                //   $('#student_colour_disp').html('<strong><span style="color:'+$(this).find('colour').text().toLowerCase()+'">'+$(this).find('colour').text()+'</span></strong>');
                //  $('#student_scenario_disp').html($(this).find('scenario').text());
                //  $('#examiner_name_disp').html($(this).find('examiner').text());

                // validateStudentCompletion();
                // clicking on the lock button...

                //   $("#confirm_student_but").parent().show();
//                $("#confirm_student_but").text('Confirm').button('refresh').click(function() {
//                    lockStudent();
//                });
                $('#goto_assessment_but').parent().show();
                waitOff();
                // $(document).trigger("create");
            }

// lock the student in. Basically this can be shown to mean that the student has been identified..
//            function lockStudent() {
//                waitOn();
//                var dataObj = new Object();
//
//                dataObj = {action: locked ? 'abandonassessment' : 'startassessment', studentid: $(current_student_data).find('id').text(), formid: '<?php print($formid); ?>', userid: <?php print($loggedinuserdata->userID); ?>, token: '<?php print($token); ?>'};
//                $.ajax({
//                    url: serviceurl,
//                    type: 'get',
//                    data: dataObj,
//                    dataType: isie() ? "text" : "xml",
//                    error: function(jqXHR, textStatus, errorThrown) {
//                        waitOff();
//                        alert(errorThrown);
//                    },
//                    success: function(data) {
//                        waitOff();
//                        var xml;
//                        if (typeof data === "string") {
//                            xml = new ActiveXObject("Microsoft.XMLDOM");
//                            xml.async = false;
//                            xml.loadXML(data);
//                        } else {
//                            xml = data;
//                        }
//                        //   console.log($(xml).find('error').length);
//                        if ($(xml).find('error').length > 0) {
//
//                            alert('Could not change lock on student:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
//                            //window.location = "../index.php";
//                        } else {
//                            if (locked) {
//                                //  eraseCookie('currentstudent')
//                                // 
//                                location.reload(true);
//                            } else {
//                                // createCookie('currentstudent', (new XMLSerializer()).serializeToString(current_student_data[0]), 1);
//                                //createCookie('currentstudent', (new XMLSerializer()).serializeToString(current_student_data[0]), 1);
//                                //  createCookie('currentdate', $('#date_input').val(), 1);
//                                $('.student_details_button').parent().hide();
//                                $('.student_details').parent().attr("disabled", "disabled");
//                                $('#assessment_tab_tab').unblock();
//                                $('#headertext').html($('#headertext').html() + ' (Locked in)');
//                                $('#goto_assessment_but').parent().show();
//                                //$("#confirm_student_but").text('Unlock').button('refresh');
//                                sessionID = $(xml).find('id').text();
//                                validateAssessment();
//                            }
//                            locked = !locked;
//                        }
//                    }
//                });
//            }


            /*
             * Starts the actual assessment. Should we start the assessment here?
             
             * @returns {undefined} */
            function startAssessment() {
                changeTabTo('#overall_tab')
            }

            // send a single assessment to the database
//            var sendmarkattempts = 0;
//            function markQuestion(itemid) {
//                inlineWaitOn();
//                var dataObj = new Object();
//
//                dataObj = {action: 'markitem', itemid: itemid, sessionid: sessionID, userid: <?php print($loggedinuserdata->userID); ?>, value: $('input:radio[name=choice_id_' + itemid + ']:checked').val(), token: '<?php print($token); ?>'};
//                $.ajax({
//                    url: serviceurl,
//                    type: 'get',
//                    data: dataObj,
//                    dataType: isie() ? "text" : "xml",
//                    error: function(jqXHR, textStatus, errorThrown) {
//
//                        if (sendmarkattempts < 3) {
//                            sendmarkattempts++;
//                            markQuestion(itemid);
//                        } else {
//                            alert("lost connection...");
//                        }
//                    },
//                    success: function(data) {
//                        waitOff();
//                        var xml;
//                        if (typeof data === "string") {
//                            xml = new ActiveXObject("Microsoft.XMLDOM");
//                            xml.async = false;
//                            xml.loadXML(data);
//                        } else {
//                            xml = data;
//                        }
//                        //   console.log($(xml).find('error').length);
//                        if ($(xml).find('error').length > 0) {
//                            inlineWaitOff();
//                            alert('Could not record mark:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
//                            //window.location = "../index.php";
//                        } else {
//                            inlineWaitOff();
//                            console.log('success!')
//                            $('#comments_id_' + itemid).textinput("enable");
//
//                        }
//                    }
//                });
//                validateAssessment();
//            }

            function markQuestion(itemid) {

                validateAssessment();
            }

            function submitcomment(itemid) {
                validateAssessment();
            }

//            // send a single comment to the database
//            var sendcommentattempts = 0;
//            function submitcomment(itemid) {
//                inlineWaitOn();
//                var dataObj = new Object();
//
//                dataObj = {action: 'makecomment', itemid: itemid, sessionid: sessionID, userid: <?php print($loggedinuserdata->userID); ?>, value: $('#comments_id_' + itemid).val(), token: '<?php print($token); ?>'};
//                $.ajax({
//                    url: serviceurl,
//                    type: 'get',
//                    data: dataObj,
//                    dataType: isie() ? "text" : "xml",
//                    error: function(jqXHR, textStatus, errorThrown) {
//                        inlineWaitOff();
//                        if (sendcommentattempts < 3) {
//                            sendcommentattempts++;
//                            submitcomment(itemid);
//                        } else {
//                            alert("lost connection...");
//                            //  window.location = "../index.php";
//                        }
//                    },
//                    success: function(data) {
//                        inlineWaitOff();
//                        var xml;
//                        if (typeof data === "string") {
//                            xml = new ActiveXObject("Microsoft.XMLDOM");
//                            xml.async = false;
//                            xml.loadXML(data);
//                        } else {
//                            xml = data;
//                        }
//                        //   console.log($(xml).find('error').length);
//                        if ($(xml).find('error').length > 0) {
//                            inlineWaitOff();
//                            alert('Could not record mark:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
//                            //window.location = "../index.php";
//                        } else {
//                            console.log('success!')
//                            inlineWaitOff();
//
//
//                        }
//                    }
//                });
//                validateAssessment();
//            }

            // validators

            /**
             * Validates the assessment form, allows entry to the final form
             * TODO use 'for' instead of 'each': see http://code.tutsplus.com/tutorials/10-ways-to-instantly-increase-your-jquery-performance--net-5551 for why
             */
            function validateAssessment() {
// save teh assessment data for later recall
                var assessmentdata = '<data><answers>';
                for (i = 0; i < questionsIDsArr.length; i++) {
                    assessmentdata += '<answer><question_id>' + questionsIDsArr[i] + '</question_id><value>' + $('input:radio[name=choice_id_' + questionsIDsArr[i] + ']:checked').val() + '</value><comment><![CDATA['
                            + $('#comments_id_' + questionsIDsArr[i]).val() + ']]></comment></answer>';
                }
                assessmentdata += '</answers></data>';
                dataStore.currentexamdata = assessmentdata;
                var validCount = 0;
                var questioncount = questionsIDsArr.length;
                // console.log('question count is' + questioncount);
                var currentQuestion = 0;
                $('input:radio[name^="choice_id_"]:checked').each(function() {
                    //  console.log('Current input ' + $(this).attr('name') + ' count:' + currentQuestion);
                    //   console.log('Current input is:' + $(this).val());
                    if ($(this).attr('needs_comment') == 'true') {
                        if ($("#comments_id_" + ($(this).attr('name').split('_'))[2]).val().length < 1) {
                            validCount++;
                            $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('border', '2px solid red'); //show();
                            $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('background-color', 'rgba(255, 200, 200, 1)')
                            $("#qrow_" + ($(this).attr('name').split('_'))[2]).css('background-color', 'rgba(255, 200, 200, 1)')
                        } else {
                            $("#qrow_" + ($(this).attr('name').split('_'))[2]).css('background-color', 'rgba(200, 255, 200, 1)')
                            $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('border', ''); //.hide();
                            $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('background-color', '');
                        }
                    } else {
                        $("#qrow_" + ($(this).attr('name').split('_'))[2]).css('background-color', 'rgba(200, 255, 200, 1)')
                        $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('border', ''); //.hide();
                        $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('background-color', '');
                    }

//                    switch ($(this).val()) {
//                        case "0":
//                            //$("#comments_id_" + ($(this).attr('name').split('_'))[2]).css('background-color', 'rgba(255, 0, 0, 0.1)')
//                            if ($("#comments_id_" + ($(this).attr('name').split('_'))[2]).val().length < 1) {
//                                validCount++;
//                                $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('border', '2px solid red'); //show();
//                                $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('background-color', 'rgba(255, 200, 200, 1)')
//                                $("#qrow_" + ($(this).attr('name').split('_'))[2]).css('background-color', 'rgba(255, 200, 200, 1)')
//                            } else {
//                                $("#qrow_" + ($(this).attr('name').split('_'))[2]).css('background-color', 'rgba(200, 255, 200, 1)')
//                                $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('border', ''); //.hide();
//                                $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('background-color', '');
//                            }
//                            break;
//                        case "1":
//                            $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('border', ''); //.hide();
//                            $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().css('background-color', '');
//                            $("#qrow_" + ($(this).attr('name').split('_'))[2]).css('background-color', 'rgba(200, 255, 200, 1)')
//                            //    console.log('Hiding' + "#comments_id_" + ($(this).attr('name').split('_'))[2]);
//                            break;
//                        default:
//                            //  $("#comments_id_" + ($(this).attr('name').split('_'))[2]).parent().hide();
//                            break;
//                    }
                    currentQuestion++
                });
                if ((validCount == 0) && (currentQuestion >= questioncount)) {

                    $('#rating_tab_tab').unblock();
                    $('#goto_rating_tab_but').parent().show();
                } else {
                    $('#rating_tab_tab').block({"message": 'Complete assessment first', fadeIn: 0});
                    $('#goto_rating_tab_but').parent().hide();
                }
                return ((validCount == 0) && (currentQuestion >= questioncount));
            }

            /**
             * Validates the student data entry form
             */
            function validateStudentCompletion() {
                //  createCookie('currentdate', $('#date_input').val(), 1);
                console.log('validating');
                if ($('#student_name_disp').html().length > 0) {
                    $("#confirm_student_but").parent().show();
                }
            }



            // validate the submission form
            function validateSubmitForm() {
                if ($('input:radio[name=overall_rating]:checked').val() == 1) {
                    $('#additional_rating_contain').show()
                } else {
                    $('#additional_rating_contain').hide()
                }


                if (validateAssessment()) {
//               /     var validCount = 0;
                    if ($('input:radio[name=overall_rating]:checked').val() != undefined) {
                        if ((($('input:radio[name=overall_rating]:checked').val() == 1) && ($('input:radio[name=additional_rating]:checked').val() != undefined)) || ($('input:radio[name=overall_rating]:checked').val() == 0)) {
                            $('#saveAssessmentBut').button('enable');
                        } else {
                            $('#saveAssessmentBut').button('disable');
                        }
                    } else {
                        $('#saveAssessmentBut').button('disable');
                    }

                }

            }


            // cancel and go back. 
            function cancel() {
                var answer = confirm("Are you sure? This will delete any existing data for this assessment")
                if (answer) {


                    //  eraseCookie('currentstudent');
                    //eraseCookie('currentdate');
                    dataStore.removeItem('currentexamdata');
                    dataStore.removeItem('currentstudentdata');
                    window.location = "../index.php";

                }
            }

            /**
             * Actually submit the assessment
             * @param {type} tabname
             * @returns {undefined}
             */

            var tries = 0;
            function saveAssessment() {
                waitOn();

                var assessmentdata = '<data><answers>';
                for (i = 0; i < questionsIDsArr.length; i++) {
                    console.log($('input:radio[name=choice_id_' + questionsIDsArr[i] + ']:checked').val());
                    assessmentdata += '<answer><question_id>' + questionsIDsArr[i] + '</question_id><value>' + $('input:radio[name=choice_id_' + questionsIDsArr[i] + ']:checked').val() + '</value><comment>'
                            + (($('input:radio[name=choice_id_' + questionsIDsArr[i] + ']:checked').val() == '0') ? $('#comments_id_' + questionsIDsArr[i]).val() : "") + '</comment></answer>';
                }
                assessmentdata += '</answers></data>';
                //      console.log(assessmentdata);
                var dataObj = new Object();
                dataObj = {
                    action: 'submitwholeassessment',
                    studentid: $(current_student_data).find('id').text(),
                    formid: <?php print($_REQUEST['id']); ?>,
                    sessionid: sessionID,
                    practicing: <?php print($formdef->overview->data->instance->practicing); ?>,
                    userid: <?php print($loggedinuserdata->userID); ?>,
                    overall_rating: $('input:radio[name=overall_rating]:checked').val(),
                    additional_rating: ($('input:radio[name=additional_rating]:checked').val() == undefined) ? '0' : $('input:radio[name=additional_rating]:checked').val(),
                    assessmentXML: assessmentdata,
                    comments: $("#additional_comments").val(),
                    token: '<?php print($token); ?>',
                    imagedata: document.getElementById("thecanvas").toDataURL()};
                $.ajax({
                    url: serviceurl,
                    type: 'post',
                    data: dataObj,
                    dataType: isie() ? "text" : "xml",
                    error: function(jqXHR, textStatus, errorThrown) {
                        waitOff();
                        tries++;
                        if (tries < 3) {
                            alert('Connection error:' + errorThrown + '\n Trying again...');
                            saveAssessment()
                        } else {
                            alert('Connection error:' + errorThrown + '\n Giving up. You can try again or use paper backup');
                            tries = 0;
                        }
                    },
                    success: function(data) {
                        //alert(data);
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
                            alert('Saving failed:' + $(xml).find('error').text() + ':' + $(xml).find('detail').text());
                            eraseCookie('currentstudent');

                            //eraseCookie('currentdate');
                            location.reload(true);
                        } else {

                            if ($(xml).find('status').text() == '0') {
                                // erase data
                                dataStore.removeItem('currentexamdata');
                                dataStore.removeItem('currentstudentdata');
                                eraseCookie('currentstudent');
                                alert('Submission successful!');
                                //eraseCookie('currentdate');
                                location.reload(true);
                            } else {
                                alert('Saving failed: there was a database error');
                                location.reload(true);
                            }
                        }
                    }
                });
            }

            // change tabs function. We do it this way because jQuery Mobile likes to have different pages as tabs, but we want all of this to be on the one page
            // UPDATE took out funky visuals. I think it screws stuff up
            function changeTabTo(tabname) {
                // hide all pages, set opacity to 0

                $('div[type="page"]').each(function() {
                    $(this).hide();
                });
                // show the page we changed to, with funky visuals
                $(tabname).show();
                $.mobile.silentScroll(0);
                // clear active on the navbar buttons
                $("#navbar_div").find("[id$='_tab']").each(function() {
                    $(this).find('a:first').removeClass('ui-btn-active');
                });
                // make teh current navbar button active
                $(tabname + '_tab').find('a:first').addClass('ui-btn-active');
                validateAssessment();
                if (tabname == 'overall_tab') {
                    bindSearchBut();
                } else {
                    unbindSearchBut();
                }
            }


            ///////////////////////////////////////////////////////////////////////////////////////////////
            //
            //Supporting functions
            //
            ////////////////////////////////////////////////////////////////////////////////////////////////

            // waiting feedback
            function waitOn() {
                $.mobile.loading('show');
                $("body").block({"message": null});
            }

            function waitOff() {
                $.mobile.loading('hide');
                $("body").unblock();
            }

            function inlineWaitOn() {
                $("#smallwaitimg").show();
            }

            function inlineWaitOff() {
                $("#smallwaitimg").hide();
            }

            function showError(messsage) {
                $("#anerrormsg").html(messsage);
                $("#feedback_popup").popup("open");
            }

            // make a cookie
            function createCookie(name, value, days) {
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    var expires = "; expires=" + date.toGMTString();
                }
                else
                    var expires = "";
                document.cookie = name + "=" + value + expires + "; path=/";
            }

            // read a cookie
            function readCookie(name) {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ')
                        c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0) {
                        return c.substring(nameEQ.length, c.length);
                    }
                }
                return null;
            }

            // erase a cookie
            function eraseCookie(name) {
                createCookie(name, "", -1);
            }

            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //
            //Drawing (signature pad)
            //
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            var drawing = false;
            //var thing = $("#thecanvas");

            var position; // = getOffset(document.getElementById('thecanvas'));



            var oldx = 0;
            var oldy = 0;
            // jQuery (browser) way



            function handleMouseDown(e) {
                document.ontouchmove = function(event) {
                    event.preventDefault();
                }
                // console.log('mouse down');
                drawing = true;
                context.beginPath();
                oldx = e.pageX - this.offsetLeft;
                oldy = e.pageY - this.offsetTop;
                context.moveTo(oldx, oldy);
            }

            function handleMouseUp(e) {

                drawing = false;
                var x = e.pageX - this.offsetLeft;
                var y = e.pageY - this.offsetTop;
                context.moveTo(x, y);
                // context.lineTo(x, y);
                //context.stroke();
                document.ontouchmove = function(event) {
                    return true;
                }
            }

            function handleMouseMove(e) {
                if (drawing) {
                    //console.log(oldx+' '+oldy);        
                    //                    var x = e.pageX - position.left;//this.offsetLeft;
                    //                    var y = e.pageY - position.top;//this.offsetTop;
                    var x = e.pageX - this.offsetLeft;
                    var y = e.pageY - this.offsetTop;
                    context.moveTo(oldx, oldy);
                    context.lineTo(x, y);
                    context.stroke();
                    oldx = x;
                    oldy = y;
                }
            }

            function reset() {
                context.clearRect(0, 0, canvas.width, canvas.height);
                context.fillStyle = "rgba(200, 200, 200, 1)";
                context.fillRect(0, 0, 600, 200);
            }


            // from http://stackoverflow.com/questions/442404/dynamically-retrieve-html-element-x-y-position-with-javascript
            function getOffset(el) {
                var _x = 0;
                var _y = 0;
                while (el && !isNaN(el.offsetLeft) && !isNaN(el.offsetTop)) {
                    _x += el.offsetLeft - el.scrollLeft;
                    _y += el.offsetTop - el.scrollTop;
                    el = el.offsetParent;
                }
                return {top: _y, left: _x};
            }


            function isie() {
                return (/MSIE (\d+\.\d+);/.test(navigator.userAgent));
            }

            function createCookie(name, value, days) {
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    var expires = "; expires=" + date.toGMTString();
                }
                else
                    var expires = "";
                document.cookie = name + "=" + value + expires + "; path=/";
            }

            function readCookie(name) {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ')
                        c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0)
                        return c.substring(nameEQ.length, c.length);
                }
                return null;
            }

            function eraseCookie(name) {
                createCookie(name, "", -1);
            }

// from http://jagadeeshm.wordpress.com/2009/09/15/jquery-setting-default-submit-button-for-enter-key-using-jquery/
            var bound = false;
            function bindSearchBut() {
                if (!bound) {
                    bound = true;
                    $("#search-student").bind("keydown", function(event) {
                        // track enter key
                        var keycode = (event.keyCode ? event.keyCode : (event.which ? event.which : event.charCode));
                        if (keycode == 13) { // keycode for enter key
                            // force the 'Enter Key' to implicitly click the Update button
                            document.getElementById('seach_student_but').click();
                            $("#search-student").blur();
                            return false;
                        } else {
                            return true;
                        }
                    });
                }
            }

            function unbindSearchBut() {
                $("#search-student").unbind();
                bound = false;
            }


            function heartbeat() {
                // inlineWaitOn();
                var dataObj = new Object();
                dataObj = {action: 'ping',
                    token: '<?php print($token); ?>'};
                $.ajax({
                    url: serviceurl,
                    type: 'get',
                    data: dataObj,
                    dataType: isie() ? "text" : "xml",
                    error: function(jqXHR, textStatus, errorThrown) {
                        // the ping failed: give a warning
                        //   inlineWaitOff();
                        $("#networkwarning").show();
                    },
                    success: function(data) {
                        //inlineWaitOff();
                        var xml;
                        if (typeof data == "string") {
                            xml = new ActiveXObject("Microsoft.XMLDOM");
                            xml.async = false;
                            xml.loadXML(data);
                        } else {
                            xml = data;
                        }
                        console.log($(xml).find('response').text());
                        if ($(xml).find('response').text() == 'pong') {
                            $("#networkwarning").hide();
                        } else {
                            $("#networkwarning").show();
                        }
                    }
                });
            }


        </script>

    </head>

    <!-- the page header -->
    <body  data-theme="b">
        <div data-role="header" data-tap-toggle="false" >
            <button id="cancelbut" onclick="cancel()" data-icon="delete">Cancel</button>
            <img id="networkwarning" src="../icons/Gnome-Dialog-Warning-32.png" class="ui-btn-right" style="right: 200px; top: 10px">
            <h1 id="headertext" ><?php print($titleStr); ?></h1>
            <img id="smallwaitimg" src="../icons/ajax-loader32.gif" class="ui-btn-right" style="right: 150px; top: 10px ">
            <div class="ui-btn-right" >Logged in as: <br/><?php print($loggedinuserdata->name); ?></div>

            <div id="navbar_div" data-role="navbar" >
                <ul>
                    <li id="overall_tab_tab" >
                        <a href="#" onclick="startAssessment();
                return false;">Examination Details</a></li>
                    <li id="assessment_tab_tab"><a href="#" onclick=" changeTabTo('#assessment_tab');
                return false;">Assessment</a></li>
                    <li id="rating_tab_tab"><a href="#" onclick=" changeTabTo('#rating_tab');
                return false;">Additional Comments and Submit</a></li>
                </ul>
            </div> 
        </div>
        <!-- the content tabs. -->
        <!-- Examination details tab -->
        <div id="overall_tab" type="page" class="ui-grid-a">

            <div class="ui-block-a" style="width: 40%">
                <div class="ui-bar" style="border-bottom: 1px #8aa6c1 solid; font-size: 2em;" >
                    Student details
                </div>  
                <div class="ui-bar" style="border-bottom: 1px #8aa6c1 solid" >
                    <label for="search-student" style=" font-weight: bold">Find student by name or ID:</label>
                    <div style="display: inline-table; vertical-align: text-top"><input type="search" name="search-student" id="search-student" data-inline="true" class="student_details"/></div>
                    <div style="display: inline-table; vertical-align: text-top"><button name="seach_student_but" id="seach_student_but" data-icon="search" data-inline="true" class="student_details_button">Search</button></div>
                </div>
                <div class="ui-bar" style="border-bottom: 1px #8aa6c1 solid">
                    <strong>Student Name:</strong>
                    <br/><div id="student_name_disp"></div>   
                </div>
                <div class="ui-bar" style="border-bottom: 1px #8aa6c1 solid">
                    <strong>Student ID:</strong>
                    <br/><div id="student_id_disp"></div>   
                </div>

            </div>

            <div class="ui-block-b" style="width: 60%">
                <div style="float: right; padding-right: 50px">

                    <div id="student_img_disp" >
                        <img id="student_img" src="../backend/resources/unknown-person.jpg"></img>
                    </div>
                    <!--                    <button name="confirm_student_but"  id="confirm_student_but" data-iconpos="right" data-icon="arrow-r">Confirm</button>-->
                </div>             
            </div>
            <div data-position="fixed" class="bottomnav" data-role="footer" style="width: 100%">
                <button  data-inline="false" data-iconpos="right" name="goto_assessment_but" data-theme="b" id="goto_assessment_but" data-icon="arrow-r" onclick="changeTabTo('#assessment_tab');">Start</button>
            </div>
        </div>


        <div type="page" id="assessment_tab" style="position:absolute; top: 100px; width: 100%" data-scroll="true">
            <?php print($formtable); ?>
            <br/>
            <br/>
            <div data-position="fixed" class="bottomnav" data-role="footer" style="width: 100%">
                <button  data-inline="false" data-iconpos="right" name="goto_rating_tab_but" data-theme="b" id="goto_rating_tab_but" data-icon="arrow-r" onclick=" changeTabTo('#rating_tab');">Next</button>
            </div>
        </div>

        <!--   The Overall tab-->
        <div type="page" id="rating_tab" style="top: 100px; width: 100%">
            <div style="float: left; width: 100%; ">
                <div>
                    <p style="font-style: italic; font-size: large; font-weight: bold; height: 30px; text-align: left">Overall Rating</p>
                    <table style="width: 100%">
                        <tr>
                            <td> <label for="overall_rating_s">Satisfactory</label><input type="radio" data-theme="e" name="overall_rating" data-iconpos='left' id="overall_rating_s" value="1" onclick="validateSubmitForm();"/></td>
                            <td>  <label for="overall_rating_u">Not Satisfactory</label><input type="radio" data-theme="e" name="overall_rating" data-iconpos='left' id="overall_rating_u" value="0" onclick="validateSubmitForm();"/></td>
                        </tr>
                    </table>
                    <div id="additional_rating_contain" style="width: 100%">
                        <p style="font-style: italic; font-size: large; font-weight: bold; height: 30px; text-align: left">Additional rating if Satisfactory </p>
                        <table style="width: 100%">
                            <tr>
                                <td><label for="additional_rating_0">Excellent</label><input type="radio" data-theme="e" data-iconpos='left' name="additional_rating" id="additional_rating_0" value="2" onclick='validateSubmitForm()'/></td>
                                <td><label for="additional_rating_1">Expected Standard</label><input type="radio" data-theme="e" data-iconpos='left' name="additional_rating" id="additional_rating_1" value="1" onclick='validateSubmitForm()'/></td>
                                <td><label for="additional_rating_2">Marginal Pass</label><input type="radio" data-theme="e" data-iconpos='left' name="additional_rating" id="additional_rating_2" value="0" onclick='validateSubmitForm()'/></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div>
                    <p style="font-style: italic; font-size: large; font-weight: bold; height: 30px; text-align: left">Additional Comments</p>
                    <textarea name="additional_comments" id="additional_comments"></textarea>
                </div>
                <div style="float: left; width: 100%; border: 1px #c0c0c0 solid;">
                    <strong>Examiner Signature</strong><button onclick="reset();" data-inline="true" >Reset</button>
                    <p/><div style="vertical-align: text-top; float: left"> <canvas style=" border: solid 1px #000;" id="thecanvas" width="600" height="100">Your browser does not support this</canvas></div>
                    <div style="float: left; vertical-align: top"> 
                        <button onclick="saveAssessment();" data-inline="true" id="saveAssessmentBut" style="float: right; height: 100px;"data-icon="check" data-iconpos="top">Finalise and Submit</button></div>
                </div>
            </div>
        </div>


    </body>
    <!-- choice of student popup -->
    <div data-role="popup" id="student_choice_popup" data-theme="a">
    </div>




    <div data-role="popup" id="feedback_popup" data-overlay-theme="a" data-theme="c" style="max-width:400px;" class="ui-corner-all">
        <div data-role="header" data-theme="a" class="ui-corner-top">
            <h1>Error</h1>
        </div>
        <div data-role="content" data-theme="d" class="ui-corner-bottom ui-content">

            <p id="anerrormsg"></p>
            <a href="#" data-role="button" data-inline="true" data-rel="back" data-theme="c">OK</a>    
        </div>
    </div>
    <?php
//print $enumlib->getExamInstanceQuestionsByID($formid);
//print_r($formdef);
    ?>

</html>

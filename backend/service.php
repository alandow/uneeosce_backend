<?php

/*
 * This is where the magic happens. This is the webservice for the app and any other async operations
 */

//phpinfo();
//ini_set('display_errors', '-1');
header('Access-Control-Allow-Origin: *');

require_once('config.inc.php');

// include some basic classes
include 'lib/authlib.php';
include 'lib/DbLib.php';
include 'lib/ConfigLib.php';

//echo $_SERVER['REQUEST_METHOD'];
        
//print_r($_REQUEST);
//
//$params = json_decode(file_get_contents('php://input'));
//
//print_r($params);

$action = $_REQUEST['action'];

$token = isset($_REQUEST['token']) ? $_REQUEST['token'] : "";



// temp override for development.
$override = false;
//if (isset($_REQUEST['override'])) {
//    if ($_REQUEST['override'] == '1') {
//        $override = true;
//    }
//}

if (isset($_FILES['userfile'])) {
    $uploaded_file = $_FILES['userfile'];
 //print_r($uploaded_file['error']);
}



$authlib = new authlib();

$returnStr = '';


if ($authlib->validateToken($token) || ($action == 'login') || ($action == 'checksunbeam') || $override) {
    switch ($action) {
        
        // Put this at the top, because it needs to be the most responsive
        // Show the student image
        case 'showstudentimage':
            include 'lib/MediaLib.php';
            $medialib = new MediaLib();
            if ($_REQUEST['getbig'] == 'true') {
                $path = $medialib->displayRawStudentImage($_REQUEST['studentid']);
                //$fp = fopen($path, 'rb');
                header("Content-Type: image/png");
                header("Content-Length: " . filesize($path));

                readfile($path);
                die();
            } else {
                $thumb = $medialib->displayStudentThumb($_REQUEST['studentid'], $_REQUEST['getbig']);
//   $path = 
                //  header("HTTP/1.0 304 Not Modified");
                header('Content-type: image/jpeg');
                imagejpeg($thumb, NULL, 70);
                imagedestroy($thumb);
                die();

            }
            break;
            
/////////////////////////////
//Authentication management
/////////////////////////////
        case 'login':
            $returnStr = $authlib->login($_REQUEST['user'], $_REQUEST['password']);
            break;
        case 'validate':
            $returnStr = $authlib->validateToken($_REQUEST['token']);
            break;
        case 'getdetailsbytoken':
            $returnStr = $authlib->getDetailsByToken($_REQUEST['token']);
            break;

// mobile configuration
        case "checksunbeam":
            $returnStr = "<data><sysname>{$CFG->sysname}</sysname></data>";
            break;

//////////////////////////////////////////////
//User parameters enumeration
/////////////////////////////////////////////
        case 'getroles':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getRolesLookup();
            break;

///////////////////////////////////////////////////////////////////////////////////////////////
// user management
///////////////////////////////////////////////////////////////////////////////////////////////
        case 'newuser':
            $returnStr = $authlib->new_user($_REQUEST['user_username'], $_REQUEST['user_password'], $_REQUEST['user_fullname'], $_REQUEST['user_roleid'], $_REQUEST['type']);
            break;

        case 'updateuser':
            $returnStr = $authlib->update_user($_REQUEST['id'], $_REQUEST['user_type'], $_REQUEST['user_username'], $_REQUEST['user_fullname'], $_REQUEST['user_roleid'], $_REQUEST['user_password']);
            break;

        case 'deleteuser':
            $returnStr = $authlib->delete_user($_REQUEST['id']);
            break;

        case 'listusers':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getUsers((isset($_REQUEST['searchstr'])) ? $_REQUEST['searchstr'] : '');
            break;

        case 'getuserbyid':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getUserByID($_REQUEST['id']);
            break;

///////////////////////////////////////////////////////////////////////////////////////////////
//  student management
///////////////////////////////////////////////////////////////////////////////////////////////
        case 'newstudent':
            include 'lib/MediaLib.php';
            $returnStr = $authlib->new_student($_REQUEST['student_fname'], $_REQUEST['student_lname'], $_REQUEST['student_num'], $_REQUEST['student_email'], $_REQUEST['student_cohort'], isset($_FILES['file'])?$_FILES['file']:null);
            break;

        case 'updatestudent':
            include 'lib/MediaLib.php';
            $returnStr = $authlib->update_student($_REQUEST['studentID'], $_REQUEST['student_fname'], $_REQUEST['student_lname'], $_REQUEST['student_num'], $_REQUEST['student_email'], isset($_FILES['file'])?$_FILES['file']:null);
            break;

        case 'deletestudent':
            $returnStr = $authlib->delete_student($_REQUEST['id']);
            break;

        case 'uploadstudentbycsv':
            $returnStr = $authlib->upload_csv($_FILES['file']);
            break;

        case 'liststudents':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getStudents();
            break;

        case 'liststudentsbysearchstr':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->findStudents((isset($_REQUEST['searchstr'])) ? $_REQUEST['searchstr'] : '');
            break;

        case 'liststudentsbysearchstrforform':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->findStudentsForForm($_REQUEST['formid'], ((isset($_REQUEST['searchstr'])) ? $_REQUEST['searchstr'] : ''), ((isset($_REQUEST['site'])) ? $_REQUEST['site'] : -1));
            break;

        case 'listunusedstudentsforformAsExcel':
            include 'lib/EnumLib.php';
            $reportsLib = new ReportsLib();
            $phpexcelObj = $reportsLib->findUnusedStudentsForFormAsExcel($_REQUEST['formid']);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="OSCE_Missing_students_report.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = new PHPExcel_Writer_Excel2007($phpexcelObj);
            $objWriter->save('php://output');
            break;

        case 'getstudentbyid':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getStudentByID($_REQUEST['id']);
            break;

        case 'lockstudent':
            $returnStr = $authlib->lock_student($_REQUEST['id']);
            break;
        
        case 'unlockstudent':
            $returnStr = $authlib->unlock_student($_REQUEST['id']);
            break;
        case 'checklockstudent':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->check_lock_student($_REQUEST['id']);
            break;

/////////////////////////////////////////////////////////////////////////////////////////
//form management
/////////////////////////////////////////////////////////////////////////////////////////
// enumerate units  
        case 'getunits':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getUnitsLookup();
            break;

// make a new exam instance
        case 'newexaminstance':
            include 'lib/EnumLib.php';
            include 'lib/FormsLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->newExamInstance($_REQUEST['instance_name'], $_REQUEST['instance_description'], $_REQUEST['unitid'], $_REQUEST['scaleid'], $_REQUEST['ownerID'], $_REQUEST['userID']);
            break;

// list all exam instances
        case 'listexaminstances':
            include 'lib/EnumLib.php';
            include 'lib/FormsLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getExamInstances($_REQUEST['count'], $_REQUEST['from']);
            break;

// list exam instances available for an assessor
        case 'listexaminstancesforassessor':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getExamsForAssessor($_REQUEST['userid']);
            break;

// list exam instances available for an assessor, with additional data for caching by the app
        case 'listexaminstancesforassessorforapp':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getExamsForAssessorForApp($_REQUEST['userid']);
            break;

        case 'getinstancebyid':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getExamInstanceOverviewByID($_REQUEST['id']);
            break;

        case 'updateinstance':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->updateExamInstance($_REQUEST['id'], $_REQUEST['instance_name'], $_REQUEST['instance_description'], $_REQUEST['unitid'], $_REQUEST['scaleid'], $_REQUEST['ownerID']);
            break;

        case 'deleteinstance':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->deleteInstance($_REQUEST['id']);
            break;

        case 'associateuser':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
// print_r($_REQUEST);
            $returnStr = $formslib->associateUsersWithInstance($_REQUEST['id'], $_REQUEST['userid']);
            break;

        case 'dissociateuser':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->dissociateUserWithInstance($_REQUEST['id'], $_REQUEST['userid']);
            break;

        case 'listusersassociatedwithinstance':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->listUsersAssociatedWithInstance($_REQUEST['id']);
            break;

        // DEPRECATED
        case 'associatestudents':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->associateStudentWithInstance($_REQUEST['id'], $_REQUEST['userid']);
            break;

        case 'associatemultiplestudents':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->associateStudentsWithInstance($_REQUEST['id'], $_REQUEST['students'], $_REQUEST['siteid']);
            break;

        case 'associatestudentsbycohort':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->associateStudentsWithInstanceByCohort($_REQUEST['id'], $_REQUEST['cohortid']);
            break;

        case 'associatestudentsbycsv':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->associateStudentsWithInstanceByCSV($_REQUEST['id'], $_FILES['file']);
            break;

        case 'dissociatestudents':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->dissociateStudentsWithInstance($_REQUEST['id'], $_REQUEST['userid']);
            break;

        case 'liststudentsassociatedwithinstance':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->listStudentsAssociatedWithInstance($_REQUEST['id']);
            break;

        case 'updatesiteforstudent':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->updateStudentAssociatedWithInstanceSite($_REQUEST['entryid'], $_REQUEST['site']);
            break;


        case 'addassessmentitemtosession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->addQuestionToInstance($_REQUEST['id'], $_REQUEST['text'], $_REQUEST['type'], $_REQUEST['userid']);
            break;


        case 'updateassessmentitem':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->update_assessment_item($_REQUEST['id'], $_REQUEST['text'], $_REQUEST['type'], $_REQUEST['userid']);
            break;

        case 'getassessmentitemdetails':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->get_assessment_item($_REQUEST['id']);
            break;

        case 'removeassessmentitemfromsession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->dissociateQuestionWithInstance($_REQUEST['id']);
            break;

        case 'reorderquestionwithsession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->reorder_QuestionWithInstanceAssociation($_REQUEST['id'], $_REQUEST['orderdef']);
            break;

        case 'locksession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->lockInstance($_REQUEST['id']);
            break;

        case 'unlocksession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->unlockInstance($_REQUEST['id']);
            break;

        case 'activatepracticesession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->activatePracticeForInstance($_REQUEST['id']);
            break;

        case 'deactivatepracticesession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->deactivatePracticeForInstance($_REQUEST['id']);
            break;

        case 'activatesession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->activateInstance($_REQUEST['id']);
            break;

        case 'deactivatesession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->deactivateInstance($_REQUEST['id']);
            break;

        case 'archivesession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->archiveInstance($_REQUEST['id']);
            break;

        case 'unarchivesession':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->unarchiveInstance($_REQUEST['id']);
            break;

        // Email stem
        case 'getemailstemdetails':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $enumlib = new EnumLib();
            $returnStr = $enumlib->getExamEmailStemByID($_REQUEST['id']);
            break;

        case 'updateemailstemdetails':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->setEmailStemText($_REQUEST['id'], $_REQUEST['text'], $_REQUEST['userid']);
            break;

        case 'exportexamasxmlfile':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $output = $enumLib->exportAssessment($_REQUEST['id']);
            $overview = simplexml_load_string($enumLib->getExamInstanceOverviewByID($_REQUEST['id']));
            header('Content-Disposition: attachment;filename="' . $overview->instance->name . ' export.xml"');
            header('Content-Type: text/plain'); # Don't use application/force-download - it's not a real MIME type, and the Content-Disposition header is sufficient
            header('Content-Length: ' . strlen($output));
            header('Connection: close');
            print($output);
            break;

        case 'importexamfromxmlfile':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->importExamInstance($_FILES['file'], $_REQUEST['ownerID'], $_REQUEST['unit'], $_REQUEST['user']);
            break;

        case 'cloneexam':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->cloneExamInstance($_REQUEST['id'], $_REQUEST['ownerID'], $_REQUEST['user']);
            break;
        case 'listquestions':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getQuesionsForMarkingSheet($_REQUEST['id']);
            break;

        case 'listquestionsbysearchstr':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getQuestionsBySearchStr($_REQUEST['searchstr']);
            break;


        case 'deleteassessmentitem':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->removeQuestionFromInstance($_REQUEST['id']);
            break;

        case 'getmarkingsheetdefinition':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getExamInstanceQuestionsByID($_REQUEST['id']);
            break;

        /////////////////////////
        //Criteria types management
        //////////////////////////

        case 'getcriteriaitembyid':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getCriteriaItemByID($_REQUEST['id']);
            break;

        case 'addcriteriascale':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formsLib = new FormsLib();
            $returnStr = $formsLib->addCriteriaScale($_REQUEST['description'], $_REQUEST['notes']);
            break;

        case 'updatecriteriascale':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formsLib = new FormsLib();
            $returnStr = $formsLib->updateCriteriaScale($_REQUEST['id'], $_REQUEST['description'], $_REQUEST['notes']);
            break;


        case 'deletecriteriascale':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formsLib = new FormsLib();
            $returnStr = $formsLib->deleteCriteriaScale($_REQUEST['id']);
            break;

        case 'addcriteriascaleitem':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formsLib = new FormsLib();
            $returnStr = $formsLib->addCriteriaScaleItem($_REQUEST['id'], $_REQUEST['shortdescription'], $_REQUEST['longdescription'], $_REQUEST['value'], $_REQUEST['needscomment']);
            break;

        case 'updatecriteriascaleitem':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formsLib = new FormsLib();
            $returnStr = $formsLib->updateCriteriaScaleItem($_REQUEST['id'], $_REQUEST['shortdescription'], $_REQUEST['longdescription'], $_REQUEST['value'], $_REQUEST['needscomment']);
            break;

        case 'reordercriteriascaleitems':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formsLib = new FormsLib();
            $returnStr = $formsLib->reorderCriteriaScaleItems($_REQUEST['orderdef']);
            break;

        case 'deletecriteriascaleitem':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formsLib = new FormsLib();
            $returnStr = $formsLib->deleteCriteriaScaleItem($_REQUEST['id']);
            break;

/////////////////////////////////////////////////////////////////////////////////////////
//Assessment management
/////////////////////////////////////////////////////////////////////////////////////////
// micromanagement
        case 'startassessment':
            include 'lib/AssessmentLib.php';
            include 'lib/EnumLib.php';
            $assesslib = new AssessmentLib();
            $returnStr = $assesslib->startAssessment($_REQUEST['studentid'], $_REQUEST['formid'], $_REQUEST['userid']);
            break;

        case 'endassessment':
            include 'lib/AssessmentLib.php';
            include 'lib/EnumLib.php';
            $img_data = substr($_REQUEST['imagedata'], strpos($_REQUEST['imagedata'], ",") + 1);
            $decodedData = base64_decode($img_data);
            $assesslib = new AssessmentLib();
            $returnStr = $assesslib->endAssessment($_REQUEST['sessionid'], $_REQUEST['overall_rating'], $_REQUEST['additional_rating'], $_REQUEST['comments'], $decodedData, $_REQUEST['userid']);
            break;

        case 'markitem':
            include 'lib/AssessmentLib.php';
            include 'lib/EnumLib.php';
            $assesslib = new AssessmentLib();
            $returnStr = $assesslib->markSingleItem($_REQUEST['sessionid'], $_REQUEST['itemid'], $_REQUEST['value'], $_REQUEST['userid']);
            break;

        case 'makecomment':
            include 'lib/AssessmentLib.php';
            include 'lib/EnumLib.php';
            $assesslib = new AssessmentLib();
            $returnStr = $assesslib->makeComment($_REQUEST['sessionid'], $_REQUEST['itemid'], $_REQUEST['value']);
            break;

        case 'abandonassessment':
            include 'lib/AssessmentLib.php';
            include 'lib/EnumLib.php';
            $assesslib = new AssessmentLib();
            $returnStr = $assesslib->abandonAssessment($_REQUEST['studentid'], $_REQUEST['formid'], $_REQUEST['userid']);
            break;

// a non-micromanagement way of doing it
        case 'submitwholeassessment':
            include 'lib/AssessmentLib.php';
            include 'lib/EnumLib.php';
            //$assesslib = new AssessmentLib();
//the app sends imagedata as pure base64 encoded bitmap data.
            if (isset($_REQUEST['app'])) {
                if (($_REQUEST['app'] == 'true')) {
                    $img_data = base64_decode($_REQUEST['imagedata']);
                } else {
// The web page app sends some extra bollocks that needs to be dealt with
                    $img_data = substr($_REQUEST['imagedata'], strpos($_REQUEST['imagedata'], ",") + 1);
                    $img_data = base64_decode($img_data);
                }
            } else {
                // $img_data = base64_decode($_REQUEST['imagedata']);
                $img_data = substr($_REQUEST['imagedata'], strpos($_REQUEST['imagedata'], ",") + 1);
                $img_data = base64_decode($img_data);
            }
            $siteid = -1;
            if (isset($_REQUEST['siteid'])) {
                $siteid = $_REQUEST['siteid'];
            }
            $assesslib = new AssessmentLib();
            $returnStr = $assesslib->submitWholeAssessment($_REQUEST['studentid'], $_REQUEST['formid'], $_REQUEST['userid'], $_REQUEST['overall_rating'], $_REQUEST['additional_rating'], $_REQUEST['assessmentXML'], $_REQUEST['comments'], $img_data, $_REQUEST['practicing']);
            break;

        case 'nukeresults':
            include 'lib/AssessmentLib.php';
            include 'lib/EnumLib.php';
            $assesslib = new AssessmentLib();
            $returnStr = $assesslib->nuke();
            break;

        case 'getprintableassessmentform':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->get_printable_assessment_form($_REQUEST['exam_ID']);
            break;

        case 'getprintableassessmentformaspdf':
            include 'lib/mpdf60/mpdf.php';
            include 'lib/EnumLib.php';
            include 'lib/FormsLib.php';
            $formslib = new FormsLib();
            $returnStr = $formslib->get_printable_assessment_form_as_pdf($_REQUEST['exam_ID']);
            break;

        case 'getprintableassessmentformasword':
            include 'lib/FormsLib.php';
            include 'lib/EnumLib.php';
            require_once './lib/PhpWord/Autoloader.php';
            \PhpOffice\PhpWord\Autoloader::register();
            $formslib = new FormsLib();
            $enumLib = new EnumLib();
            $phpwordObj = new \PhpOffice\PhpWord\PhpWord();
            $PHPWord = $formslib->get_printable_assessment_form_as_word($_REQUEST['exam_ID'], $phpwordObj);
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($PHPWord, 'Word2007');
            $overview = simplexml_load_string($enumLib->getExamInstanceOverviewByID($_REQUEST['exam_ID']));
            header('Content-Type: application/application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="' . $overview->instance->name . '.docx"');
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output');
            break;

        ////////////////////////////////////////////////////////////////////////////////
        //Update assessment after the fact
        ////////////////////////////////////////////////////////////////////////////////
        case 'moderateassessmentitem':
            include 'lib/FormsLib.php';

            include 'lib/AssessmentLib.php';
            $assesslib = new AssessmentLib();
            $returnStr = $assesslib->moderateAssessmentItem($_REQUEST['id'], $_REQUEST['value'], $_REQUEST['comment'], $_REQUEST['reason'], $_REQUEST['userid']);
            break;

        case 'modifyassessmentitem':
            include 'lib/FormsLib.php';
            //include 'lib/DbLib.php';
            include 'lib/AssessmentLib.php';
            $assesslib = new AssessmentLib();
            $returnStr = $assesslib->updateAssessmentItem($_REQUEST['id'], $_REQUEST['value'], $_REQUEST['comment'], $_REQUEST['reason'], $_REQUEST['userid']);
            break;

        case 'moderateassessmentoverview':

            break;

        case 'modifyassessmentoverview':
            include 'lib/FormsLib.php';
            // include 'lib/DbLib.php';
            include 'lib/AssessmentLib.php';
            $assesslib = new AssessmentLib();
            $returnStr = $assesslib->updateAssessmentOverview($_REQUEST['id'], $_REQUEST['rating'], $_REQUEST['additionalrating'], $_REQUEST['comment'], $_REQUEST['reason'], $_REQUEST['userid']);

            break;

        case 'getitemhistory':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getItemEditHistory($_REQUEST['id']);
            break;

        case 'getsessionhistory':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getOverviewHistory($_REQUEST['id']);
            break;
/////////////////////////////////////////////////////////////////////////////////////////
//Reports
/////////////////////////////////////////////////////////////////////////////////////////

        case 'getactiveexamdata':
            include 'lib/EnumLib.php';
            $enumLib = new EnumLib();
            $returnStr = $enumLib->getActiveExamInstances($_REQUEST['site']);
            break;

        case 'showsignatureimage':
            include 'lib/ReportsLib.php';
            $reportslib = new ReportsLib();
            $thumb = $reportslib->displaySignature($_REQUEST['session_ID']);
            header('Content-type: image/jpeg');
            imagejpeg($thumb);
            imagedestroy($thumb);
            break;

        case 'getreportforsession':
            include 'lib/ReportsLib.php';
            include 'lib/EnumLib.php';
            $reportslib = new ReportsLib();
            $returnStr = $reportslib->getReportForStudentSession($_REQUEST['session_ID']);
            break;

        case 'getreportforsessionaspdf':
            include 'lib/EnumLib.php';
            include 'lib/mpdf60/mpdf.php';
            include 'lib/ReportsLib.php';
            $reportslib = new ReportsLib();
            $reportslib->getReportForStudentSessionAsPDF($_REQUEST['session_ID'], isset($_REQUEST['showoverall'])?$_REQUEST['showoverall']=='true':false);
            break;

        case 'getreportforexamasexcel':
            include 'lib/PHPExcel.php';
            include 'lib/ReportsLib.php';
            include 'lib/EnumLib.php';
            $enumlib = new EnumLib();
            $overview = simplexml_load_string($enumlib->getExamInstanceOverviewByID($_REQUEST['exam_ID']));
            $reportslib = new ReportsLib();
            $phpexcelObj = $reportslib->getSummaryReportAsExcel($_REQUEST['exam_ID']);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="OSCE Summary for ' . $overview->instance->name . '.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = new PHPExcel_Writer_Excel2007($phpexcelObj);
            $objWriter->save('php://output');
            break;

        case 'getreportforallformsasexcel':
            include 'lib/EnumLib.php';
            include 'lib/PHPExcel.php';
            include 'lib/ReportsLib.php';
            $reportslib = new ReportsLib();
//       print('yo');
            $enumlib = new EnumLib();
            $overview = simplexml_load_string($enumlib->getExamInstanceOverviewByID($_REQUEST['exam_ID']));
            $phpexcelObj = $reportslib->getFullReportAsExcel($_REQUEST['exam_ID']);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="OSCE Analysis report for ' . $overview->instance->name . '.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = new PHPExcel_Writer_Excel2007($phpexcelObj);
            $objWriter->save('php://output');
            break;

        case 'getallreportsforsessionaspdf':
            include 'lib/EnumLib.php';
            include 'lib/mpdf60/mpdf.php';
            include 'lib/ReportsLib.php';
            include 'lib/StringLib.php';
            $reportslib = new ReportsLib();
            $reportslib->getAllReportsForStudentsAsPDF($_REQUEST['exam_ID']);
            break;

        case "sendtestemail":
            include 'lib/EnumLib.php';
            include 'lib/ReportsLib.php';
            include 'lib/FeedbackLib.php';
            include 'lib/StringLib.php';
            include 'lib/mpdf60/mpdf.php';
            require_once './lib/class.phpmailer.php';
            $feedbacklib = new FeedbackLib();
// this often times out. We'll give it a large value
            set_time_limit(300);
            $returnStr = $feedbacklib->sendTestMail($_REQUEST['id'], $_REQUEST['address'], $_REQUEST['emailtext'], true, true);
            break;

        case 'mailfeedbacktoall':
            include 'lib/EnumLib.php';
            include 'lib/ReportsLib.php';
            include 'lib/FeedbackLib.php';
            include 'lib/StringLib.php';
           include 'lib/mpdf60/mpdf.php';
            require_once './lib/class.phpmailer.php';
            $feedbacklib = new FeedbackLib();
// this often times out. We'll give it a large value
            set_time_limit(300);
            $returnStr = $feedbacklib->sendMail($_REQUEST['exam_ID'], $_REQUEST['includefinalmark'] == 'true');
            break;

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        //Asset management
        //
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////
        case "uploadmedia":
            include 'lib/MediaLib.php';
            //ini_set('short_open_tag', 'on');
            ini_set('max_execution_time', 300);
            //ini_set('memory_limit', '64M');
            ini_set('file_uploads', 'On');
            //ini_set('upload_max_filesize', '1024M');
            $medialib = new MediaLib();
            $allowedExtensions = array("doc", "docx", "pdf", "ppt", "pptx", "jpg", "jpeg", "png", "bmp", "mp4", "mpg", "m4v", "avi", "wmv");
            if (strlen($uploaded_file['tmp_name']) > 0) {
                $extensionArr = explode(".", strtolower($uploaded_file['name']));
                $extension = end($extensionArr);
                if (!in_array($extension, $allowedExtensions)) {
                    die("<data><error>Invalid file</error><detail>Invalid file type</detail></data>");
                } else {
                    $imageextensions = array("jpg", "jpeg", "png", "bmp");
//                    print("extension is $extension");
                    $videoextensions = array("mp4", "mpg", "m4v", "avi", "wmv");
                    if (in_array($extension, $imageextensions)) {
                        $returnStr = $medialib->upload_media_image($_REQUEST['id'], $uploaded_file, $_REQUEST['description']);
                    }else  if (in_array($extension, $videoextensions)) {
                        require_once './lib/phpvideotoolkitautoloader.php';
                        $config = new \PHPVideoToolkit\Config(array(
                            'temp_directory' => $CFG->ffmpegtemppath,
                            'ffmpeg' => $CFG->ffmpegpath,
                            'ffprobe' => $CFG->ffmpegprobepath,
                        ));
                        $ffmpeg = new PHPVideoToolkit\FfmpegParser();
                        $returnStr = $medialib->upload_media_video($ffmpeg, $_REQUEST['id'], $uploaded_file, $_REQUEST['description']);
                    } else {
                        $returnStr = $medialib->upload_document_v4($_REQUEST['id'], $uploaded_file, $_REQUEST['description']);
                    }
                }
            }
            break;
        case "getmediaforrecordid":
            include 'lib/MediaLib.php';
            include 'lib/EnumLib.php';
            $medialib = new MediaLib();
            $returnStr = $medialib->get_assessment_media_by_record_id($_REQUEST['id']);
            break;

        case "showthumbformedia":
            include 'lib/MediaLib.php';
            include 'lib/EnumLib.php';
            $medialib = new MediaLib();
            $medialib->displayMediaThumb($_REQUEST['id'], ($_REQUEST['getbig'] == 'true'));
            break;

        case "downloadmedia":
            include 'lib/MediaLib.php';
            include 'lib/EnumLib.php';
            $outputReturnStr = false;
            $medialib = new MediaLib();
            $medialib->download_raw_media($_REQUEST['id']);
            break;

        case "deletemedia":
            include 'lib/MediaLib.php';
            include 'lib/EnumLib.php';
            $medialib = new MediaLib();
            $returnStr = $medialib->deleteMediaItem($_REQUEST['id']);
            break;

//////////////////////////////////////////////////////////////
//
        //Datagrid stuff
//
        //////////////////////////////////////////////////////////////
        case "getgrid":
            include 'lib/EditableGrid.php';
            include 'lib/GridLib.php';
            $gridlib = new GridLib();
            $returnStr = $gridlib->getGridForTable($_REQUEST['table'], $_REQUEST['columns'], $_REQUEST['showdelete']=='true');
            break;

        case "newgridentry":
            include 'lib/EditableGrid.php';
            include 'lib/GridLib.php';
            $gridlib = new GridLib();
            $returnStr = $gridlib->addRow($_REQUEST['table']);
            break;

        case "updategrid":
            include 'lib/EditableGrid.php';
            include 'lib/GridLib.php';
            $gridlib = new GridLib();
            $returnStr = $gridlib->updateTable($_REQUEST['table'], $_REQUEST['id'], $_REQUEST['colname'], $_REQUEST['newvalue']);
            break;

        case "deletegridentry":
            include 'lib/EditableGrid.php';
            include 'lib/GridLib.php';
            $gridlib = new GridLib();
            $returnStr = $gridlib->deleteRow($_REQUEST['table'], $_REQUEST['id']);
            break;

///////////////////////////////////////////////////////////////////////////////
//
        //Network keep-alive thing
//
        //////////////////////////////////////////////////////////////////////////////
        case "ping":
            $returnStr = "<data><response>pong</response></data>";
            break;
        
        /**
         * Fallback position if the action is invalid
         */
        default:
            $returnStr = 'Invalid action';
            break;
    }
} else {
    $returnStr = "<data><error>Invalid token: are you logged in somewhere else?</error><detail>$token</detail></data>";
}

// output to page
print ($returnStr);

// some input sanitisation. Fom http://css-tricks.com/snippets/php/sanitize-database-inputs/

function cleanInput($input) {
    $search = array(
        '@<script[^>] * ? > . * ?</script>@si', // Strip out javascript
        /*  '@<[\/\!]*?[^<>]*?>@si', // Strip out HTML tags */
        '@<style[^>]*?>.*?</style>@siU', // Strip style tags properly
        '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
    );

    $output = preg_replace($search, '', $input);
    return $output;
}

function sanitize($input) {
    global $CFG;
    $conn = mysql_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysql_error() . '</detail></data>');
    if (is_array($input)) {
        foreach ($input as $var => $val) {
            $output[$var] = sanitize($val);
        }
    } else {
        if (get_magic_quotes_gpc()) {
            $input = stripslashes($input);
        }
        $input = cleanInput($input);
        $output = mysql_real_escape_string($input);
    }
    return $output;
}

?>

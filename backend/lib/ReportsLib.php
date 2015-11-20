<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ReportsLib
 *
 * @author alandow
 */
//require_once('./../config.inc.php');
//include '/lib/PHPExcel.php';

class ReportsLib {

    /**
     * Get summary report for an assessment (all of the assessments for a session) as an XML document
     * @global type $CFG
     * @param int a $instance_ID the assessment instance
     * @return string an XML formatted string containing summary information about teh results of an assessment instance
     */
    public function getSummaryReportForExamInstance($instance_ID) {
        global $CFG;
//print_r($CFG);
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT * FROM exam_instances WHERE ID = :instance_ID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':instance_ID', $instance_ID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getSummaryReportForExamInstance query failed</error><detail>' . $stmt->errorInfo() . '</detail></data>');

        $results_available = false;
        while ($rows = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results_available = true;

            $returnVal.="<summary><id>{$rows['ID']}</id><name><![CDATA[{$rows['name']}]]></name><description><![CDATA[{$rows['description']}]]></description><scale_id>{$rows['scaleID']}</scale_id><examdate><![CDATA[{$this->toDateTimeString($rows['exam_starttimestamp'])}]]></examdate></summary>";
        }
        $stmt->closeCursor();

        if (!$results_available) {
            return "<data><error>no results available</error></data>";
        }

// big-ass query!
        $query = "SELECT a.ID, a.student_id, a.overall_rating, a.additional_rating, a.site_ID,
            (SELECT fname FROM students WHERE students.ID = a.student_id) as fname, 
            (SELECT lname FROM students WHERE students.ID = a.student_id) as lname, 
            (SELECT studentnum FROM students WHERE students.ID = a.student_id) as studentnum, a.form_id, a.start_timestamp, a.comments, b.description, ";
        $query .= "(SELECT SUM(answer) from student_exam_sessions_responses where student_exam_sessions_responses.student_exam_session_ID = a.ID) as score, ";
        // need to get top score here, and add them

        $query .= "(SELECT (SELECT `value` FROM `assessment_criteria_scales_items` WHERE assessment_criteria_scale_typeID = b.scaleID ORDER BY `assessment_criteria_scales_items`.`value` DESC LIMIT 1)*(SELECT COUNT(answer) from student_exam_sessions_responses where student_exam_sessions_responses.student_exam_session_ID = a.ID)) as total, ";
        $query .= "(SELECT COUNT(last_modified_timestamp) from student_exam_sessions_responses where student_exam_sessions_responses.student_exam_session_ID = a.ID AND COALESCE(last_modified_timestamp, 0)>0) as modifycount, ";
        $query .= "(SELECT COUNT(moderated_timestamp) from student_exam_sessions_responses where student_exam_sessions_responses.student_exam_session_ID = a.ID AND COALESCE(moderated_timestamp, 0)>0) as moderatecount, ";
        $query .= "(SELECT last_modified_by_ID from student_exam_sessions_responses where student_exam_sessions_responses.student_exam_session_ID = a.ID AND last_modified_timestamp IS NOT NULL ORDER BY created_timestamp DESC LIMIT 0,1) as lastitemmodifiedby, ";
        $query .= "(SELECT moderated_by_ID from student_exam_sessions_responses where student_exam_sessions_responses.student_exam_session_ID = a.ID AND moderated_timestamp IS NOT NULL ORDER BY created_timestamp DESC LIMIT 0,1) as lastitemmoderatedby, ";
        $query .= "(SELECT name FROM users WHERE users.ID = a.created_by_ID) as examiner, ";
        $query .= "a.last_modified_by_ID as lastmodifiedby, a.moderated_by_id as lastmoderatedby ";
        $query .= "from student_exam_sessions a ";
        $query .= "inner join exam_instances b on a.form_ID = b.ID WHERE a.form_ID = :instance_ID AND a.status='complete'";
        //   print("<br/>$query<br/>");

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':instance_ID', $instance_ID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getSummaryReportForExamInstance query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');
        //       $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $results_available = false;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results_available = true;
            $returnVal.="<session><id><![CDATA[{$row['ID']}]]></id>
                <student_id><![CDATA[{$row['student_id']}]]></student_id>
                    <studentnum><![CDATA[{$row['studentnum']}]]></studentnum>
                        <fname><![CDATA[{$row['fname']}]]></fname>
                            <lname><![CDATA[{$row['lname']}]]></lname>
                                <datetime><![CDATA[" . $this->toDateTimeString($row['start_timestamp']) . "]]></datetime>
                                    <siteid>{$row['site_ID']}</siteid>
                                    <score><![CDATA[{$row['score']}]]></score>
                                        <total><![CDATA[{$row['total']}]]></total>
                                            <overall_rating>{$row['overall_rating']}</overall_rating>
                                                <additional_rating>{$row['additional_rating']}</additional_rating>
                                                    <comments><![CDATA[{$row['comments']}]]></comments>
                                                        <examiner><![CDATA[{$row['examiner']}]]></examiner>
                                                            <description><![CDATA[{$row['description']}]]></description>
                                                                <modifycount><![CDATA[{$row['modifycount']}]]></modifycount>
                                                                    <lastmodifiedby><![CDATA[{$row['lastmodifiedby']}]]></lastmodifiedby>
                                                                        <moderatecount><![CDATA[{$row['moderatecount']}]]></moderatecount>
                                                                            <lastmoderatedby><![CDATA[{$row['lastmoderatedby']}]]></lastmoderatedby>
                 </session>";
        }
        if (!$results_available) {
            return "<data><error>no results available</error></data>";
        }

        return $returnVal . "</data>";
    }

    /**
     * Get a summary report for an assessment session as Excel
     * @param type $session_ID
     * @return PHPExcel an Excel spreadsheet containing a summary report of the results of an assessment session
     */
    public function getSummaryReportAsExcel($session_ID) {

        $enumlib = new EnumLib();
// get the data
        $dataXML = simplexml_load_string($this->getSummaryReportForExamInstance($session_ID));
        $phpexcelObj = new PHPExcel();

        // make a new Excel sheet    
        $summaryWorksheet = new PHPExcel_Worksheet($phpexcelObj, 'Assessment summary');
        // $phpexcelObj->createSheet();
        $phpexcelObj->addSheet($summaryWorksheet, 0);

        // put some headings in        
        $summaryWorksheet->setCellValue('A1', "Assessment summary: {$dataXML->summary->description} {$dataXML->summary->examdate}");
        $summaryWorksheet->getStyle('A1')->getFont()->setSize(16);
        $summaryWorksheet->setCellValue('A2', "Student Number");
        $summaryWorksheet->getStyle('A2')->getFont()->setBold(true);
        $summaryWorksheet->setCellValue('B2', "Student Name");
        $summaryWorksheet->getStyle('B2')->getFont()->setBold(true);
        $summaryWorksheet->setCellValue('C2', "Assessment Date/Time");
        $summaryWorksheet->getStyle('C2')->getFont()->setBold(true);
        $summaryWorksheet->setCellValue('D2', "Site");
        $summaryWorksheet->getStyle('D2')->getFont()->setBold(true);
        $summaryWorksheet->setCellValue('E2', "Score");
        $summaryWorksheet->getStyle('E2')->getFont()->setBold(true);
        $summaryWorksheet->setCellValue('F2', "Out of a Possible");
        $summaryWorksheet->getStyle('F2')->getFont()->setBold(true);
        $summaryWorksheet->setCellValue('G2', "Comments");
        $summaryWorksheet->getStyle('G2')->getFont()->setBold(true);
        $summaryWorksheet->setCellValue('H2', "Assessor");
        $summaryWorksheet->getStyle('H2')->getFont()->setBold(true);
        $summaryWorksheet->setCellValue('I2', "Overall Rating");
        $summaryWorksheet->getStyle('I2')->getFont()->setBold(true);
        $summaryWorksheet->setCellValue('J2', "Additional rating if Satisfactory");
        $summaryWorksheet->getStyle('J2')->getFont()->setBold(true);
// format a bit
        $summaryWorksheet->getColumnDimension('A')->setWidth(26);

        $summaryWorksheet->getColumnDimension('B')->setAutoSize(true);
        $summaryWorksheet->getColumnDimension('C')->setAutoSize(true);
        $summaryWorksheet->getColumnDimension('D')->setAutoSize(true);
        $summaryWorksheet->getColumnDimension('E')->setAutoSize(true);
        $summaryWorksheet->getColumnDimension('F')->setAutoSize(true);
        $summaryWorksheet->getColumnDimension('G')->setAutoSize(true);
        $summaryWorksheet->getColumnDimension('H')->setAutoSize(true);
        $summaryWorksheet->getColumnDimension('I')->setAutoSize(true);
        $summaryWorksheet->getColumnDimension('J')->setAutoSize(true);

        $additional_rating = '';


        for ($i = 0; $i < count($dataXML->session); $i++) {
            if ($dataXML->session[$i]->overall_rating == 1) {
                switch ($dataXML->session[$i]->additional_rating) {
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
            } else {
                $additional_rating = 'n/a';
            }
            $sitedata = simplexml_load_string($enumlib->getSiteByID($dataXML->session[$i]->siteid));
            $summaryWorksheet->setCellValue('A' . ($i + 3), $dataXML->session[$i]->studentnum);
            $summaryWorksheet->setCellValue('B' . ($i + 3), ($dataXML->session[$i]->fname . ' ' . $dataXML->session[$i]->lname));
            $summaryWorksheet->setCellValue('C' . ($i + 3), $dataXML->session[$i]->datetime);
            $summaryWorksheet->setCellValue('D' . ($i + 3), $sitedata->code);
            $summaryWorksheet->setCellValue('E' . ($i + 3), $dataXML->session[$i]->score);
            $summaryWorksheet->setCellValue('F' . ($i + 3), $dataXML->session[$i]->total);
            $summaryWorksheet->setCellValue('G' . ($i + 3), $dataXML->session[$i]->comments);
            $summaryWorksheet->setCellValue('H' . ($i + 3), $dataXML->session[$i]->examiner);
            $summaryWorksheet->setCellValue('I' . ($i + 3), ($dataXML->session[$i]->overall_rating == 1) ? 'S' : 'NS');
            $summaryWorksheet->getStyle('I' . ($i + 3))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB(($dataXML->session[$i]->overall_rating == 1) ? 'C6EFCE' : 'FFC7CE');
            //, ($dataXML->session[$i]->overall_rating == 1) ? 'S' : 'NS');
            $summaryWorksheet->setCellValue('J' . ($i + 3), $additional_rating);
        }



        return $phpexcelObj;
    }

    /**
     * Get a more complete report- a data dump for analysis
     * @global type $CFG
     * @param type $formID
     * @return PHPExcel an Excel spreadsheet containing a detailed report of the results of an assessment session
     */
    public function getFullReportAsExcel($session_ID) {
        $overviewXML = simplexml_load_string($this->getSummaryReportForExamInstance($session_ID));
        // print("form ID is: $session_ID <br/>");
        global $CFG;
        // questions labels.
        // alphabet array
        $alphabetArr = array();
        foreach (range('A', 'Z') as $i) {
            $alphabetArr[] = $i;
        }
        // this extends the possible spreadsheet cells a bit.
        foreach (range('A', 'Z') as $i) {
            $alphabetArr[] = "A" . $i;
        }
        foreach (range('A', 'Z') as $i) {
            $alphabetArr[] = "B" . $i;
        }
        foreach (range('A', 'Z') as $i) {
            $alphabetArr[] = "C" . $i;
        }
        // There shouldn't be more than, say, 70
        $j = 0;

        // get student sessions
        $sessionsXMLStr = "<data>";
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT a.ID, a.student_id, a.overall_rating, a.additional_rating, a.site_ID,
            (SELECT fname FROM students WHERE students.ID = a.student_id) as fname, 
            (SELECT lname FROM students WHERE students.ID = a.student_id) as lname, 
            (SELECT studentnum FROM students WHERE students.ID = a.student_id) as studentnum, a.form_id, a.start_timestamp
            from student_exam_sessions a inner join exam_instances b on a.form_ID = b.ID WHERE a.form_ID = :session_ID";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':session_ID', $session_ID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getSummaryReportForExamInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sessionsXMLStr .= "<session><id>{$row['ID']}</id><student_id>{$row['student_id']}</student_id><fname>{$row['fname']}</fname><lname>{$row['lname']}</lname><studentnum>{$row['studentnum']}</studentnum><siteid>{$row['site_ID']}</siteid><created_timedate>{$row['start_timestamp']}</created_timedate></session>";
        }
        $sessionsXMLStr .= "</data>";
        // print($query);
        $sessionsXML = simplexml_load_string($sessionsXMLStr);
        // get question definitions for this session
        $enumlib = new EnumLib();
        $questionsXML = simplexml_load_string($enumlib->getQuestionsForSession($session_ID));

        // create excel sheet
        $phpexcelObj = new PHPExcel();
// make a new Excel sheet    
        $quantitativeWorksheet = new PHPExcel_Worksheet($phpexcelObj, 'Quantitative Cohort Outcomes');
        $phpexcelObj->addSheet($quantitativeWorksheet, 0);

        // set labels
        $quantitativeWorksheet->getColumnDimension("A")->setWidth(200);
        $quantitativeWorksheet->setCellValue('A1', "Quantitative Cohort Outcomes for: {$overviewXML->summary->description} {$overviewXML->summary->examdate}, n=" . count($sessionsXML->session));
        $quantitativeWorksheet->getStyle('A1')->getFont()->setSize(16);

        $quantitativeWorksheet->setCellValue('A2', "Item");
        $quantitativeWorksheet->getStyle("A2")->getFont()->setBold(true);


//        $quantitativeWorksheet->setCellValue('C2', "Percentage");
//        $quantitativeWorksheet->getStyle("C2")->getFont()->setBold(true);
//        $quantitativeWorksheet->getColumnDimension("C")->setAutoSize(true);


        $questionscount = 0;
        $questionsArr = array();
        $k = 3;

        // 
        foreach ($questionsXML->question as $question) {
            //set all criteria labels
            $quantitativeWorksheet->setCellValue("A$k", $question->text);
            //    $quantitativeWorksheet->getStyle("A$k")->getFont()->setBold(true);

            $quantitativeWorksheet->getColumnDimension("A")->setWidth(150);

            // get the criteria
            $currentColumn = 1;
            $criteriaDef = simplexml_load_string($enumlib->getCriteriaScaleItems($overviewXML->summary->scale_id));
            foreach ($criteriaDef->item as $item) {
                $quantitativeWorksheet->setCellValue("{$alphabetArr[$currentColumn]}2", "n marked {$item->short_description}");
                $quantitativeWorksheet->getStyle("{$alphabetArr[$currentColumn]}2")->getFont()->setBold(true);
                $quantitativeWorksheet->getColumnDimension("{$alphabetArr[$currentColumn]}")->setAutoSize(true);
                $quantitativeWorksheet->setCellValue("{$alphabetArr[$currentColumn + 1]}2", "% marked {$item->short_description}");
                $quantitativeWorksheet->getStyle("{$alphabetArr[$currentColumn + 1]}2")->getFont()->setBold(true);
                $quantitativeWorksheet->getColumnDimension("{$alphabetArr[$currentColumn + 1]}")->setAutoSize(true);
                $query = "SELECT COUNT(answer) as total FROM student_exam_sessions_responses WHERE question_ID = :id AND answer = :answer AND student_exam_sessions_responses.student_exam_session_ID IN(SELECT ID FROM student_exam_sessions WHERE student_exam_sessions.form_ID = :session_ID)";
                $stmt = $conn->prepare($query);
                $stmt->bindValue(':id', $question->id, PDO::PARAM_INT);
                $stmt->bindValue(':answer', $item->value, PDO::PARAM_STR);
                $stmt->bindValue(':session_ID', $session_ID, PDO::PARAM_INT);
                $stmt->execute() or die('<data><error>select query failed query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

                $resultArr = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $quantitativeWorksheet->setCellValue("{$alphabetArr[$currentColumn]}$k", $row['total']);
                    $quantitativeWorksheet->setCellValue("{$alphabetArr[$currentColumn + 1]}$k", (round($row['total'] / count($sessionsXML->session), 2) * 100) . "%");
                }
                $currentColumn+=2;
                $stmt->closeCursor();
            }
            $k++;
        }



        // qualitative data
        $criteriaWorksheet = new PHPExcel_Worksheet($phpexcelObj, 'Criteria Comments');
        $phpexcelObj->addSheet($criteriaWorksheet, 1);

        // set labels
        $criteriaWorksheet->getColumnDimension("A")->setWidth(150);
        $criteriaWorksheet->setCellValue('A1', "Qualitative Comments on Criteria Requiring Comments: {$overviewXML->summary->description} {$overviewXML->summary->examdate}, n=" . count($sessionsXML->session));
        $criteriaWorksheet->getStyle('A1')->getFont()->setSize(16);

        $criteriaWorksheet->setCellValue('A2', "Item");
        $criteriaWorksheet->getStyle("A2")->getFont()->setBold(true);
        $criteriaWorksheet->setCellValue('B2', "Comments");
        $criteriaWorksheet->getStyle("B2")->getFont()->setBold(true);
        $criteriaWorksheet->getColumnDimension("B")->setAutoSize(true);


        $k = 3;

        // 
        foreach ($questionsXML->question as $question) {
            //set all criteria labels
            // add up questions here
            //$currentrow = 4;
            //print('num'.$studentexamsession->studentnum);
            $sql = "SELECT COUNT(*) FROM student_exam_sessions_responses WHERE question_ID = :id AND answer = 0 AND student_exam_sessions_responses.student_exam_session_ID IN(SELECT ID FROM student_exam_sessions WHERE student_exam_sessions.form_ID = :session_ID)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $question->id, PDO::PARAM_INT);
            $stmt->bindValue(':session_ID', $session_ID, PDO::PARAM_INT);
            if ($stmt->execute()) {
                if ($stmt->fetchColumn() > 0) {
                    $stmt->closeCursor();
                    $criteriaWorksheet->setCellValue("A$k", html_entity_decode($question->text, ENT_QUOTES,'UTF-8'));
                    //    $quantitativeWorksheet->getStyle("A$k")->getFont()->setBold(true);

                    $criteriaWorksheet->getColumnDimension("A")->setAutoSize(true);
                    $query = "SELECT comments FROM student_exam_sessions_responses WHERE question_ID = :id AND answer IN (SELECT value FROM assessment_criteria_scales_items WHERE assessment_criteria_scale_typeID = :criteriaid AND needs_comment ='true')";
                    $stmt = $conn->prepare($query);
                    $stmt->bindValue(':id', $question->id, PDO::PARAM_INT);
                    $stmt->bindValue(':criteriaid', $overviewXML->summary->scale_id, PDO::PARAM_STR);

                    $stmt->execute() or die('<data><error>select query failed query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

                    $resultArr = array();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $criteriaWorksheet->setCellValue("B$k", $row['comments']);
                        $k++;
                    }
                    $stmt->closeCursor();
                }
            }
        }

        // qualitative data
        $overallCommentsWorksheet = new PHPExcel_Worksheet($phpexcelObj, 'Overall Comments');
        $phpexcelObj->addSheet($overallCommentsWorksheet, 2);

        // set labels
        $overallCommentsWorksheet->getColumnDimension("A")->setWidth(200);
        $overallCommentsWorksheet->setCellValue('A1', "Qualitative Overall Comments: {$overviewXML->summary->description} {$overviewXML->summary->examdate}, n=" . count($sessionsXML->session));
        $overallCommentsWorksheet->getStyle('A1')->getFont()->setSize(16);

        $overallCommentsWorksheet->setCellValue('A2', "Comments");
        $overallCommentsWorksheet->getStyle("A2")->getFont()->setBold(true);


        $k = 3;


        // 
        foreach ($overviewXML->session as $session) {

            if (strlen($session->comments) > 0) {
                $overallCommentsWorksheet->setCellValue("A$k", html_entity_decode($session->comments, ENT_QUOTES,'UTF-8'));
                //    $quantitativeWorksheet->getStyle("A$k")->getFont()->setBold(true);

                $k++;
            }
        }

        $rawWorksheet = new PHPExcel_Worksheet($phpexcelObj, 'Raw Scores Data Dump');
        $phpexcelObj->addSheet($rawWorksheet, 3);
// assessment raw data
        $rawWorksheet->setCellValue('A1', "Assessment Raw Data: {$overviewXML->summary->description} {$overviewXML->summary->examdate}");
        $rawWorksheet->getStyle('A1')->getFont()->setSize(16);
        $rawWorksheet->setCellValue('A2', "Student Number");
        $rawWorksheet->getStyle('A2')->getFont()->setBold(true);
        $rawWorksheet->setCellValue('B2', "Student Name");
        $rawWorksheet->getStyle('B2')->getFont()->setBold(true);
        $rawWorksheet->setCellValue('C2', "Site ID");
        $rawWorksheet->getStyle('C2')->getFont()->setBold(true);
        $rawWorksheet->setCellValue('D2', "Assessment Timestamp");
        $rawWorksheet->getStyle('D2')->getFont()->setBold(true);
//        $rawWorksheet->getColumnDimension('B')->setAutoSize(true);
//        $rawWorksheet->getColumnDimension('C')->setAutoSize(true);


        $questionscount = 0;
        $questionsArr = array();
        $k = 0;
        // get all criteria labels
        foreach ($questionsXML->question as $question) {
            //   $criterias = simplexml_load_string($enumlib->getCriteriaForQuestion($question->id, false));
            // foreach ($criterias as $criteria) {
            //  print($criteria->text . '<br/>');
            if ($question->type != 'label') {
                $rawWorksheet->setCellValue($alphabetArr[$k] . '2', $question->text);
                $rawWorksheet->getStyle($alphabetArr[$k] . '2')->getFont()->setBold(true);
                $rawWorksheet->getColumnDimension($alphabetArr[$k])->setAutoSize(true);
                $questionsArr[] = array("id" => (string) $question->id, "column" => $alphabetArr[$k]);
                // get student results, loop to populate all answers to this criteria
                // can probably do this better...               
                $questionscount++;
                $k++;
            }
            // }
        }
        // print('questionsArr:<br/>');
        //  print_r($questionsArr);
        // populate student details
        $currentrow = 4;
        foreach ($sessionsXML->session as $studentexamsession) {
            //print('num'.$studentexamsession->studentnum);
            $rawWorksheet->setCellValue('A' . $currentrow, $studentexamsession->studentnum);
            $rawWorksheet->setCellValue('B' . $currentrow, $studentexamsession->fname . ' ' . $studentexamsession->lname);
            $rawWorksheet->setCellValue('C' . $currentrow, $studentexamsession->siteid);
            $rawWorksheet->setCellValue('D' . $currentrow, $studentexamsession->created_timedate);
            $rawWorksheet->setCellValue('E' . $currentrow, $studentexamsession->created_timedate);
            $query = "SELECT * FROM student_exam_sessions_responses WHERE student_exam_session_ID = :sessionid";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':sessionid', $studentexamsession->id, PDO::PARAM_INT);
            $stmt->execute() or die('<data><error>>select query failed query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

            $resultArr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $resultArr[] = array("id" => $row['question_ID'], "answer" => $row['answer']);
            }

            for ($i = 0; $i < count($questionsArr); $i++) {
                $rawWorksheet->setCellValue($questionsArr[$i]['column'] . $currentrow, $this->findAnswer($questionsArr[$i]['id'], $resultArr));
            }
            $currentrow++;
            $stmt->closeCursor();
        }

        $phpexcelObj->setActiveSheetIndex(0);

        return $phpexcelObj;
    }

    // 
    /**
     * a little helper function to find an answer with a specific ID from a results array
     * @param type $id the ID to look for
     * @param type $resultsArr the array element with the key 'answer' 
     * @return string the array element with the key 'answer' 
     */
    private function findAnswer($id, $resultsArr) {
        $returnVal = '';
        for ($j = 0; $j < count($resultsArr); $j++) {
            if ((string) $resultsArr[$j]['id'] == (string) $id) {
                $returnVal = $resultsArr[$j]['answer'];
            }
        }
        return $returnVal;
    }

    /**
     * Gets the report for a participant session as an XML
     * @global type $CFG
     * @param type $session_ID the session ID this is from
     * @return string an XML formatted string containing summary information about teh results of an assessment session for a student
     */
    public function getReportForStudentSession($session_ID) {
        global $CFG;
        $additional_rating = '';
        $moderated_additional_rating = "";
        $enumLib = new EnumLib();
        $returnVal = '<data><overview>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        // get exam definition (and information about the actual exam instance)
        $query = "SELECT (SELECT name FROM exam_instances WHERE ID = a.form_ID) as exam, (SELECT scaleID FROM exam_instances WHERE ID = a.form_ID) as scale, a.ID as sessionid, a.form_ID, a.start_timestamp, a.overall_rating, a.moderated_overall_rating, a.additional_rating, a.moderated_additional_rating, a.comments, a.moderated_comments, b.fname, b.lname, b.ID as studentid, b.studentnum, b.email,
            (SELECT users.name FROM users WHERE users.ID = a.created_by_ID) as examiner,
            a.moderated_by_id as moderatedby, a.last_modified_by_ID as modifiedby
            FROM student_exam_sessions a INNER JOIN students b ON a.student_ID = b.ID WHERE a.ID = :sessionid";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionid', $session_ID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>select query failed query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');
// get some details about the criteria

        $resultArr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal .= "<studentname><![CDATA[{$row['fname']} {$row['lname']}]]></studentname>
                <fname><![CDATA[{$row['fname']}]]></fname>                
                <lname><![CDATA[{$row['lname']}]]></lname>
                <studentid>{$row['studentid']}</studentid>
                <sessionid>{$row['sessionid']}</sessionid>
                <formid>{$row['form_ID']}</formid>
                    <scaleid>{$row['scale']}</scaleid>
                    <exam><![CDATA[{$row['exam']}]]></exam>
                        <datetime>" . date('d/m/Y', $row['start_timestamp']) . "</datetime>
                <studentnum>{$row['studentnum']}</studentnum>
                    <studentemail><![CDATA[{$row['email']}]]></studentemail>
                        <examiner><![CDATA[{$row['examiner']}]]></examiner>";

            switch ($row['additional_rating']) {
                case "2":
                    $additional_rating = "Excellent";
                    break;
                case "1":
                    $additional_rating = "Expected Standard";
                    break;
                case "0":
                    $additional_rating = "Marginal Pass";
                    break;
                default:
                    break;
            }
            switch ($row['moderated_additional_rating']) {
                case "2":
                    $moderated_additional_rating = "Excellent";
                    break;
                case "1":
                    $moderated_additional_rating = "Expected Standard";
                    break;
                case "0":
                    $moderated_additional_rating = "Marginal Pass";
                    break;
                default:
                    break;
            }
            $returnVal .="<overall_rating>" . (($row['overall_rating'] == '1') ? 'Satisfactory' : 'Not satisfactory') . "</overall_rating>
                            <moderated_overall_rating>" . (($row['moderated_overall_rating'] == '1') ? 'Satisfactory' : 'Not satisfactory') . "</moderated_overall_rating>
                            <overall_rating_value>{$row['overall_rating']}</overall_rating_value>
                            <moderated_overall_rating_value>{$row['moderated_overall_rating']}</moderated_overall_rating_value>
                            <additional_rating>" . (($row['overall_rating'] == '1') ? "{$additional_rating}" : "n/a") . "</additional_rating>
                            <additional_rating_value>{$row['additional_rating']}</additional_rating_value>
                            <moderated_additional_rating>" . (($row['moderated_overall_rating'] == '1') ? "{$moderated_additional_rating}" : "n/a") . "</moderated_additional_rating>
                            <moderated_additional_rating_value>{$row['moderated_additional_rating']}</moderated_additional_rating_value>
                                <moderated_by>{$row['moderatedby']}</moderated_by>
                                <modified_by>{$row['modifiedby']}</modified_by>
                            <comments><![CDATA[{$row['comments']}]]></comments>
                            <moderated_comments><![CDATA[{$row['moderated_comments']}]]></moderated_comments>
                            ";
            $instanceID = $row['form_ID'];
        }

        $returnVal .= "</overview><questiondata>";
        $stmt->closeCursor();
        $formDef = simplexml_load_string($enumLib->getExamInstanceQuestionsByID($instanceID));
        // print($enumLib->getExamInstanceQuestionsByID($instanceID));
        foreach ($formDef->questiondata->question as $question) {

            $returnVal .="<question><id>{$question->id}</id><text><![CDATA[{$question->text}]]></text><type>{$question->type}</type>";
            //    foreach ($question->criteriadata->data->criteria as $criteria) {
            $query = "SELECT ID, answer,moderated_answer, comments,moderated_comments, last_modified_timestamp, moderated_timestamp FROM student_exam_sessions_responses WHERE question_ID = :questionid  AND student_exam_session_ID = :sessionid";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':sessionid', $session_ID, PDO::PARAM_INT);
            $stmt->bindValue(':questionid', $question->id, PDO::PARAM_INT);
            $stmt->execute() or die('<data><error>select query failed query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $returnVal .= "<answerid>{$row['ID']}</answerid>
                    <answer>{$row['answer']}</answer>
                        <moderated_answer>{$row['moderated_answer']}</moderated_answer>
                            <comment><![CDATA[{$row['comments']}]]></comment>
                                <moderated_comment><![CDATA[{$row['moderated_comments']}]]></moderated_comment>
                                    <moderated>{$row['moderated_timestamp']}</moderated><modified>{$row['last_modified_timestamp']}</modified>";
            }
            $stmt->closeCursor();
            $returnVal .= "</question>";
        }
        $returnVal .= "</questiondata></data>";

        return($returnVal);
    }

    /**
     * Gets the report for a participant session as an HTML document.  Used to generate a printable PDF for emailing
     * @global type $CFG
     * @param type $session_ID the session ID this is from
     * @return string an HTML formatted string containing summary information about teh results of an assessment session.
     */
    public function getReportForStudentSessionAsHTML($session_ID, $showOverallResult = false) {
        global $CFG;
        $count = 0;
        $enumlib = new EnumLib();
        // get the session information
        $formdef = simplexml_load_string($this->getReportForStudentSession($session_ID));

        $criteriaXML = simplexml_load_string($enumlib->getCriteriaScaleItems($formdef->overview->scaleid));

        // build an HTML string
        // overview section
        $htmlStr = "<html><head>" . $CFG->printableformCSS .
                "</head><br/>
    <h3 style='text-align:left'>{$formdef->overview->exam} feedback</h3><p/>
            <div style=''>
                <div style='width:100%; position:relative; float:left;'>
                    <div style='width:170px; position:relative; float:left; font-weight:bold'>Student Name:</div>
                    <div style='width:170px; position:relative; float:left;'>{$formdef->overview->studentname}</div>
                </div>
                 <div style='width:100%; position:relative; float:left; '>
                    <div style='width:170px; position:relative; float:left; font-weight:bold'>Student Number:</div>
                    <div style='width:170px; position:relative; float:left;'>{$formdef->overview->studentnum}</div>
                </div> 
                  <div style='width:100%; position:relative; float:left;'>
                    <div style='width:170px; position:relative; float:left; font-weight:bold'>Assessment Date:</div>
                    <div style='width:300px; position:relative; float:left;'>{$formdef->overview->datetime}</div>
                </div> 
            </div><br/>";

        if ($showOverallResult) {
            $display_overall_rating = $formdef->overview->overall_rating;
            $htmlStr .= "<span style='font-weight:bold'>Overall Rating:</span> $display_overall_rating<br/>";
            $display_additional_rating = (strval($display_overall_rating) == 'Not satisfactory') ? "n/a" : $formdef->overview->additional_rating;
            $htmlStr .= "<span style='font-weight:bold'>Additional Rating:</span> $display_additional_rating<br/><br/>";
        }

        //$htmlStr.="<table ><tr><th class='form'>Assessment Criteria</th><th class='form' colspan='3'>Quality of Performance</th></tr>";
//        $htmlStr.="<div style='position:relative; float:left; '>
//                <div style='width:250px; position:relative; float:left' class='header'>Assessment Criteria</div>
//                <div class='header' style='width:400px; position:relative; float:left; border-bottom: solid 1px #000;'>Performance<br/></div></div>";

        $htmlStr.="<div style='width:100%; position:relative; float:left; '>
                <div style='width:250px; position:relative; float:left' class='header'>Assessment Criteria</div>
                <div class='header' style='width:100px;  position:relative; float:left'>Result</div>
                <div class='header' style='width:298px;  position:relative; float:left'>Comments</div>
               </div>";
        // Loop through questions
        foreach ($formdef->questiondata->question as $question) {
            // limit each page of questions to 15
            if ((($count % 15) != 0) || ($count == 0)) {
                switch ($question->type) {
                    case '0':
                        $count++;
                        $htmlStr.="<div style='width:100%;  position:relative; float:left; padding:0px; height:30px'>
                <div class='formcell' style='width:250px; position:relative; float:left' >{$count})<span style='font-size:" . (1 - (0.1 * (strlen($question->text) / 30))) . "em'> {$question->text}</span></div>
                <div class='formcell' style='width:100px; text-align:center; position:relative; float:left;'><strong>";
                        foreach ($criteriaXML->item as $item) {
                            // print('Answer is:'.$question->answer.'<br/>');
                            // print('Comparing to:'.$item->value.'<br/>');
                            if (strval($item->value) == strval($question->answer)) {
                                //    print('Match!');
                                $htmlStr.=$item->short_description;
                            }
                        }
                        //die();
                        //. (($question->answer == '1') ? "S" : "NS") . 
                        $htmlStr.="</strong></div>
                
                <div class='formcell' style='width:298px;  position:relative; float:left; font-family: opensansemoji'>".htmlentities($question->comment)."</div>
               </div>";
                        //  $htmlStr.="<tr><th class='form' colspan='4'>{$question->text}</th></tr>";
                        break;
                    case '1':
                        $count++;
                        $htmlStr.="<div style='width:100%;  position:relative; float:left; padding:0px; height:30px'>
                <div class='formcell' style='width:250px; position:relative; float:left' >{$count})<span style='font-size:" . (1 - (0.1 * (strlen($question->text) / 30))) . "em'> {$question->text}</span></div>
                <div class='formcell' style='width:100px; text-align:center; position:relative; float:left;'><strong>";

                        foreach ($criteriaXML->item as $item) {
                            // print('Answer is:'.$question->answer.'<br/>');
                            // print('Comparing to:'.$item->value.'<br/>');
                            if (strval($item->value) == strval($question->answer)) {
                                //    print('Match!');
                                $htmlStr.=$item->short_description;
                            }
                        }
                        //die();
                        //. (($question->answer == '1') ? "S" : "NS") . 
                        $htmlStr.="</strong></div>
                
                <div class='formcell' style='width:298px;  position:relative; float:left'>".htmlentities($question->comment)."</div>
               </div>";
                        //$htmlStr.="<tr><td class='form' style=''><span>{$count}) {$question->text}</span></td class='form'><td class='form' style='font-size:1.5em;'>&#x274f;</td class='form'><td class='form' style='font-size:1.5em;'>&#x274f;</td><td class='form'>&nbsp;</td></tr>";
                        //  $htmlStr.="<div><div style='width:200px; position:relative; float:left'>".$count.") ".$question->text."</div><div style='width:50px; position:relative; float:left; font-size:1.5em'>&#x274f;</div><div style='width:50px; font-size:1.5em; position:relative; float:left'>&#x274f;</div><div style='width:398px; position:relative; float:left'>Comments</div></div>";
                        break;
                    default:
                        break;
                }
            } else {



                $count++;
                $htmlStr.="<pagebreak /><br/>";
                $htmlStr.="<div style='position:relative; float:left; '>
                <div style='width:250px; position:relative; float:left' class='header'>Assessment Criteria</div>
                <div class='header' style='width:400px; position:relative; float:left; border-bottom: solid 1px #000;'>Quality of Performance</div>
             </div>";
                $htmlStr.="<div style='width:100%; position:relative; float:left; '>
                <div style='width:250px; position:relative; float:left' class='header'>&nbsp;</div>
                <div class='header' style='width:100px;  position:relative; float:left'>Result</div>
                <div class='header' style='width:298px;  position:relative; float:left'>Comments</div>
               </div>";
                $htmlStr.="<div style='width:100%;  position:relative; float:left; padding:0px; height:30px'>
                <div class='formcell' style='width:250px; position:relative; float:left' >{$count})<span style='font-size:" . (1 - (0.1 * (strlen($question->text) / 30))) . "em'> {$question->text}</span></div>
                <div class='formcell' style='width:100px; text-align:center; position:relative; float:left'><strong>" . (($question->answer == '1') ? "S" : "NS") . "</strong></div>
                
                <div class='formcell' style='width:298px;  position:relative; float:left'>".htmlentities($question->comment)."</div>
               </div>";
            }
        }


        $htmlStr.="<span style='font-weight:bold; font-size:0.8em'>Legend:</span><br/>";
        foreach ($criteriaXML->item as $item) {
            $htmlStr.= "<span style='font-weight:bold; font-size:0.8em'>{$item->short_description}:</span><span style='font-size:0.8em'>{$item->long_description}</span><br/>";
        }
        $htmlStr.='<br/>';
        // overall comments
        $htmlStr.=" <div style='width:170px; position:relative; float:left; font-weight:bold'>Additional Comments:</div>";
        $htmlStr.="<div  style='width:650px;'>{$formdef->overview->comments}</div>";
        $htmlStr.='<br/>';

        if (!$showOverallResult) {
            $htmlStr.="<hr/><span style='font-size:0.6em'><i>This form is designed to provide each student with feedback from the examiner on which competencies were not performed satisfactorily, and why. It is a learning support for you and is NOT designed as a Grading Sheet to reflect your OVERALL GLOBAL RATING.<br/> 
The OVERALL GLOBAL RATING is a separate independent global rating by the examiner and you will be notified of this Grade as part of the official Examination reporting process.  As with all OSCE examinations the OVERALL  GLOBAL RATING is final, meaning that remarks are not possible. Any queries should be directed to your Course/Unit Co-odinator, and not to the examiners.</span>
</i><br/>";
        } else {
            $htmlStr.="<hr/><span style='font-size:0.6em'><i>Any queries should be directed to your Course/Unit Co-odinator, and not to the examiners.</span>";
        }


        return $htmlStr . '</html>';
    }

    /**
     * Gets the report for a participant session as a PDF document for emailing
     * @global type $CFG
     * @param type $session_ID the ID of the session we're looking at
     */
    public function getReportForStudentSessionAsPDF($session_ID, $showOverallResult = false) {
        global $CFG;
        // get the form title
        $mpdf = new mPDF();

        //$mpdf->SetHTMLHeader("<table style='width:100%'><tr><td style='width:600px'>JOINT MEDICAL PROGRAMMME<br/>BACHELOR OF MEDICINE</td><td style='font-align:right; width:90px'><img width='90px'; src='../icons/osce_header.jpg'/></td></tr></table>", 'O');
        $mpdf->SetHTMLHeader("", 'O');
        $output = $this->getReportForStudentSessionAsHTML($session_ID, $showOverallResult);
        //print($output);
        $mpdf->SetHTMLFooter("<p style='font-size: 0.5em; font-style:italic; text-align:right'>Generated: " . date("j F, Y, g:i a") . "</p>");
        $mpdf->WriteHTML($output);
        $mpdf->Output();
    }
    
       /**
     * Gets the report for a participant session as a PDF document for emailing
     * @global type $CFG
     * @param type $session_ID the ID of the session we're looking at
     */
    public function getReportForStudentSessionAsPDFForEmail($session_ID, $showOverallResult = false) {
        global $CFG;
        // get the form title
        $mpdf = new mPDF();

        //$mpdf->SetHTMLHeader("<table style='width:100%'><tr><td style='width:600px'>JOINT MEDICAL PROGRAMMME<br/>BACHELOR OF MEDICINE</td><td style='font-align:right; width:90px'><img width='90px'; src='../icons/osce_header.jpg'/></td></tr></table>", 'O');
        $mpdf->SetHTMLHeader("", 'O');
        $output = $this->getReportForStudentSessionAsHTML($session_ID, $showOverallResult);
        //print($output);
        $mpdf->SetHTMLFooter("<p style='font-size: 0.5em; font-style:italic; text-align:right'>Generated: " . date("j F, Y, g:i a") . "</p>");
        $mpdf->WriteHTML($output);
        return $mpdf->Output('', 'S');;
    }

    /**
     * Gets all results for an assessment as an HTML document. Handy for printing for hardcopies
     * @global type $CFG
     * @param type $exam_ID
     * @return type
     */
    public function getAllReportsForStudentsAsHTML($exam_ID) {
        global $CFG;
        $output = ''; //$CFG->reportCSS;
        // get all ID's
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT a.ID FROM student_exam_sessions a WHERE a.form_ID = :exam_ID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':exam_ID', $exam_ID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getAllReportsForStudentsAsHTML failed </error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $output.= $this->getReportForStudentSessionAsHTML($row['ID']);
        }
        return $output;
    }

    /**
     * Gets all results for an assessment as a PDF document. Handy for printing for hardcopies
     * @global type $CFG
     * @param type $exam_ID
     */
    public function getAllReportsForStudentsAsPDF($exam_ID, $showOverallResult = false) {
        global $CFG;
        $mpdf = new mPDF();
        $output = $CFG->reportCSS;
        $mpdf->WriteHTML($output . $this->getAllReportsForStudentsAsHTML($exam_ID));
        $mpdf->Output();
    }

    /**
     * Displays a signature for a given student assessment
     * @global type $CFG
     * @param type $mediaID
     * @param type $getbig
     * @param type $db 
     */
    public function displaySignature($sessionID) {
        global $CFG;

        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
// select the db
        $query = "select signature_image from student_exam_sessions where ID = :sessionID LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getAllReportsForStudentsAsHTML failed </error><detail>' . $stmt->errorCode() . '</detail></data>');
        $image = $stmt->fetch(PDO::FETCH_ASSOC);


        $img = imagecreatefromstring($image['signature_image']);
        $width = imagesx($img);
        $height = imagesy($img);
        $new_height = 50;
        $new_width = floor($width * ( $new_height / $height));
        $thumb = imagecreatetruecolor($new_width, $new_height);
        imagecopyresized($thumb, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        return $thumb;
    }

    public function findUnusedStudentsForFormAsExcel($form_ID) {
        // get the data
        $enumlib = new EnumLib();
        $dataXML = simplexml_load_string($enumlib->findStudentsForForm($form_ID, ""));

        // make a new Excel sheet
        $phpexcelObj = new PHPExcel();
        $phpexcelObj->createSheet();
        // put some headings in        
        $phpexcelObj->getActiveSheet()->setCellValue('A1', "Student Number");
        $phpexcelObj->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $phpexcelObj->getActiveSheet()->setCellValue('B1', "Student name");
        $phpexcelObj->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);

// format a bit


        $phpexcelObj->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $phpexcelObj->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $phpexcelObj->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $phpexcelObj->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $phpexcelObj->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        $phpexcelObj->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
        $phpexcelObj->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
        $phpexcelObj->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);




        for ($i = 0; $i < count($dataXML->student); $i++) {

            $phpexcelObj->getActiveSheet()->setCellValue('A' . ($i + 2), $dataXML->student[$i]->studentnum);
            $phpexcelObj->getActiveSheet()->setCellValue('B' . ($i + 2), ($dataXML->student[$i]->fname . ' ' . $dataXML->student[$i]->lname));
        }


        return $phpexcelObj;
    }

    // a little function to take a UNIX timestamp and return a formatted string
    public function toDateTimeString($timestamp) {
        return date('d/m/y g:i a', $timestamp);
    }

}

?>

<?php

/**
 * Handles assessment tasks
 *
 * @author alandow
 */
//require_once('../config.inc.php');

class AssessmentLib {

    /**
     * Checks to see if a student is already being assessed by this user 
     * @param type $studentID
     * @param type $examID
     * @param type $userID
     */
    public function checkAssessment($examID, $userID) {
        //  print("$examID, $userID");
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        // is this examiner already marking someone?
        $query = "SELECT * FROM {$CFG->schema}.student_exam_sessions WHERE form_ID = :examID AND created_by_ID = :userID AND status <> 'complete'";
        $stmt = $conn->prepare($query);

        // print($query);

        $stmt->bindValue(':examID', $examID, PDO::PARAM_INT);
        $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);

        // $result = mysqli_query($conn, $query) or die('<data><error>checkAssessment query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt->execute() or die('<data><error>checkAssessment query failed</error><detail>' . $stmt->errorInfo() . $query . '</detail></data>');

        if ($stmt->rowCount() > 0) {
            //  print('Got a result');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $reportslib = new ReportsLib();
                return $reportslib->getReportForStudentSession($row['ID']);
            }
        } else {
            return "<data></data>";
        }
    }

    /**
     * Starts an assessment and logs. Allows for resumption of assessment 
     * @param type $studentID
     * @param type $examinstanceID
     * @param type $userID
     */
    public function startAssessment($studentID, $examID, $userID) {
        global $CFG;
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');

        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        // there's a weird bug where sometimes this happens twice.
        // check for the existence of an exam first
        $query = "SELECT count(*) FROM student_exam_sessions WHERE student_ID = :studentID AND form_ID = :examID AND created_by_ID = :userID;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':examID', $examID, PDO::PARAM_INT);
        $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);

        //$result = mysqli_query($conn, $query) or die('<data><error>check query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt->execute() or die('<data><error>checkAssessment query failed</error><detail>' . $stmt->errorInfo() . $query . '</detail></data>');

        $numrows = $stmt->fetch(PDO::FETCH_NUM);
        //if (mysqli_num_rows($result) == 0) {
        if ($numrows == 0) {
            // there's nothing there, go ahead and insert one    
            $query2 = "INSERT INTO {$CFG->schema}.student_exam_sessions (student_ID, form_ID, created_by_ID, start_timestamp, status) 
            VALUES(:studentID,  :examID, :userID, " . time() . ",  'started');";
            $stmt2 = $conn->prepare($query2);
            $stmt2->bindValue(':examID', $examID, PDO::PARAM_INT);
            $stmt2->bindValue(':userID', $userID, PDO::PARAM_INT);
            $stmt2->bindValue(':studentID', $studentID, PDO::PARAM_INT);

            $result = $stmt2->execute() or die('<data><error>checkAssessment query failed</error><detail>' . $stmt2->errorInfo() . $query2 . '</detail></data>');
            // $result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            if ($result) {

                $returnStr = $conn->lastInsertId();
                //   print($conn->lastInsertId());
                // log the event
                $this->doLog($userID, $returnStr, -1, 'examstart', "");
            }
        } else {
            $stmt->closeCursor();
            $query = "SELECT ID FROM student_exam_sessions WHERE student_ID = :studentID AND form_ID = :examID AND created_by_ID = :userID;";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':examID', $examID, PDO::PARAM_INT);
            $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
            $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);

            //$result = mysqli_query($conn, $query) or die('<data><error>check query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            $stmt->execute() or die('<data><error>checkAssessment query failed</error><detail>' . $stmt->errorInfo() . $query . '</detail></data>');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $returnStr = $row['ID'];
            }
        }

        return "<data><id>$returnStr</id></data>";
    }

    /**
     * Abandons an assessment and logs. 
     * @param type $studentID
     * @param type $examinstanceID
     * @param type $userID
     */
    public function abandonAssessment($studentID, $examID, $userID) {
        global $CFG;
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT ID  FROM student_exam_sessions WHERE student_ID = :studentID AND form_ID = :examID AND created_by_ID = :userID;";
        // $result = mysqli_query($conn, $query) or die('<data><error>check query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':examID', $examID, PDO::PARAM_INT);
        $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);

        //$result = mysqli_query($conn, $query) or die('<data><error>check query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt->execute() or die('<data><error>abandonAssessment query failed</error><detail>' . $stmt->errorInfo() . $query . '</detail></data>');
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // delete references to it
                $query2 = "SELECT ID FROM {$CFG->schema}.student_exam_sessions_responses WHERE student_exam_session_ID = {$row['ID']}";
                $stmt2 = $conn->prepare($query2);
                $success2 = $stmt2->execute() or die('<data><error>select for delete query failed</error><detail>' . $stmt2->errorInfo() . $query2 . '</detail></data>');
                while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                    $query3 = "DELETE FROM  {$CFG->schema}.student_exam_sessions_responses WHERE ID = {$row2['ID']}";
                    $stmt3 = $conn->prepare($query3);
                    $success3 = $stmt3->execute() or die('<data><error>delete query failed</error><detail>' . $stmt3->errorInfo() . $query3 . '</detail></data>');
                }
            }
        }
        $query4 = "DELETE FROM  {$CFG->schema}.student_exam_sessions WHERE student_ID = :studentID AND form_ID = :examID AND created_by_ID = :userID;";
        $stmt4 = $conn->prepare($query4);
        $stmt4->bindValue(':examID', $examID, PDO::PARAM_INT);
        $stmt4->bindValue(':userID', $userID, PDO::PARAM_INT);
        $stmt4->bindValue(':studentID', $studentID, PDO::PARAM_INT);


        $result = $stmt4->execute() or die('<data><error>delete query failed</error><detail>' . $stmt4->errorInfo() . $query4 . '</detail></data>');
        if ($result) {
            $returnStr = $stmt4->rowCount();
            // log the event
            $this->doLog($userID, $returnStr, -1, 'examcancel', "");
        }


        return "<data><id>$returnStr</id></data>";
    }

    /**
     * Starts an assessment and logs. Allows for resumption of assessment 
     * @param type $studentID
     * @param type $examinstanceID
     * @param type $userID
     */
    public function endAssessment($id, $overall_rating, $additional_rating, $comments, $signature_image, $userID) {
        global $CFG;
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        // storage of signature image code: make a database storable string out of the incoming signature image
        // create a useful image object
        $img_jpg_data = imagecreatefromstring($signature_image);
        // start capturing te output buffer
        ob_start();
        // output the image data into the buffer
        imagejpeg($img_jpg_data);
        // capture the image data as a variable
        $img_data = ob_get_contents(); // the raw jpeg image data. 
        // clean the buffer 
        ob_end_clean();
        // Escape the image data for storage in the database
        //  $imgData = mysqli_real_escape_string($conn, $img_data);
        // perform the query
        $query = "UPDATE {$CFG->schema}.student_exam_sessions SET status = 'complete', end_timestamp = " . time() . ", overall_rating = :overall_rating, additional_rating = :additional_rating, comments = :comments, signature_image = :imgData WHERE ID = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':overall_rating', $overall_rating, PDO::PARAM_STR);
        $stmt->bindValue(':additional_rating', $additional_rating, PDO::PARAM_STR);
        $stmt->bindValue(':comments', $comments, PDO::PARAM_STR);
        $stmt->bindValue(':imgData', $img_data, PDO::PARAM_STR);

        $result = $stmt->execute() or die('<data><error>submit query failed</error><detail>' . $stmt->errorInfo() . '</detail></data>');
        if ($result) {
            $returnStr = $stmt->rowCount();
            // log the event
            $this->doLog($userID, $id, -1, 'examend', "");
        }
//        $result = mysqli_query($conn, $query) or die('<data><error>update query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
//        if ($result) {
//            $returnStr = mysqli_affected_rows($conn);
//            // log the event
//           
//        }


        return "<data><status>$returnStr</status></data>";
    }

    /**
     * 
     * @param type $sessionID
     * @param type $item
     * @param type $value
     * @param type $userID
     */
    public function markSingleItem($sessionID, $itemID, $value, $userID) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //  $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $mode = 'update';
        $oldvalue = '';
        $responseID = 0;
        // is this item already marked?
        $query = "SELECT * FROM student_exam_sessions_responses WHERE student_exam_session_ID = :sessionID AND question_ID = :itemID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
        $stmt->bindValue(':itemID', $itemID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>markSingleItem query failed</error><detail>' . $stmt->errorInfo() . '</detail></data>');

        //$result = mysqli_query($conn, $query) or die('<data><error>check query failed</error><detail>' . mysql_error() . $query . '</detail></data>');
        if ($stmt->rowCount() > 0) {
            // this has already been answered, update it
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $query2 = "UPDATE student_exam_sessions_responses SET answer= :value  WHERE ID = {$row['ID']}";
                $stmt2 = $conn->prepare($query2);
                $stmt2->bindValue(':value', $value, PDO::PARAM_STR);
                $mode = 'update';
                $oldvalue = $row['answer'];
                $responseID = $row['ID'];
            }
        } else {
            // or else make a new thing
            $query2 = "INSERT INTO student_exam_sessions_responses (student_exam_session_ID, question_ID, answer, created_timestamp)";
            $query2 .= "VALUES(:sessionID, :itemID, :value, " . time() . ");";
            //$query2 = "UPDATE student_exam_sessions_responses SET answer= :value  WHERE ID = {$row['ID']}";
            $stmt2 = $conn->prepare($query2);
            $stmt2->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
            $stmt2->bindValue(':itemID', $itemID, PDO::PARAM_INT);
            $stmt2->bindValue(':value', $value, PDO::PARAM_STR);

            $mode = 'insert';
        }

        //  $result = mysqli_query($conn, $query) or die('<data><error>markSingleItem query failed</error><detail>' . mysql_error() . $query . '</detail></data>');
        $result = $stmt2->execute() or die('<data><error>markSingleItem query failed</error><detail>' . $stmt2->errorInfo() . '</detail></data>');
        if ($mode == 'insert') {
            $responseID = $conn->lastInsertId(); // mysqli_insert_id($conn);
        }
        $this->doLog($userID, $sessionID, $responseID, $mode, $value, $oldvalue);
        if ($result) {
            return "<data><status>true</status></data>";
        } else {
            return "<data><status>false</status></data>";
        }
    }

    /**
     * 
     * @param type $sessionID
     * @param type $itemID
     * @param type $value
     */
    public function makeComment($sessionID, $itemID, $value) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "UPDATE student_exam_sessions_responses SET comments= :value WHERE student_exam_session_ID = :sessionID AND question_ID = :itemID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
        $stmt->bindValue(':itemID', $itemID, PDO::PARAM_INT);
        $stmt->bindValue(':value', $value, PDO::PARAM_STR);
        $result = $stmt->execute() or die('<data><error>makeComment query failed</error><detail>' . $stmt->errorInfo() . '</detail></data>');
        //  $result = mysqli_query($conn, $query) or die('<data><error>markSingleItem query failed</error><detail>' . mysql_error() . $query . '</detail></data>');
        if ($result) {
            return "<data><status>true</status></data>";
        } else {
            return "<data><status>false</status></data>";
        }
    }

    /**
     * Submit a whole assessment into the database
     * @global object $CFG
     * @param int $studentID the ID of student being assessed
     * @param int $examinstanceID the ID of the examination
     * @param int $userID the ID of the assessor
     * @param string $assessmentDataXML an XML formatted string containing the assessment data.
     * @param string $comments teh global comments for this assessment
     * @param string $signature_image a JPG-formatted string of the assessor's signature
     * @param string $practicing 'true' if the submission is in practice mode, 'false' (or anything else really) of it's not
     * @return XML-formatted string containing the status of the insert, or an error if the insert query fails
     */
    public function submitWholeAssessment($studentID, $examinstanceID, $userID, $overall_rating, $additional_rating, $assessmentDataXML, $comments, $signature_image, $practicing) {
        global $CFG;
               
        // an initial status variable
        $success_status = 0;
        
// if we're practing, we don't need to do anything. This may change later if it's decided to store the practice data for some reason
        if (strval($practicing) == 'true') {
            return "<data><status>$success_status</status></data>";
        }
        
        // But if we're not practicing, store the data!
 
        // connect to db
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
// Update 29/10/13- if the same student is being marked simultaneously there could be some... unpleasantness
        // check to see that this student hasn't already been marked
        $query = "SELECT student_id, form_id FROM student_exam_sessions WHERE student_id = :studentID AND form_id = :examinstanceID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        $stmt->bindValue(':examinstanceID', $examinstanceID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>submitWholeAssessment check assessment linkage query failed</error><detail>' . $stmt->errorInfo() . '</detail></data>');
        //$result = mysqli_query($conn, $query) or die('<data><error>check query failed</error><detail>' . mysql_error() . $query . '</detail></data>');
        //if (mysqli_num_rows($result) > 0) {
        if ($stmt->rowCount() > 0) {
            return '<data><error>student already assessed</error><detail>The student has already been assessed for this OSCE</detail></data>';
        }
        $stmt->closeCursor();
        // storage of signature image code: make a database storable string out of the incoming signature image
        // create a useful image object
        // print('image data is:'.$signature_image);
        $img_jpg_data = imagecreatefromstring($signature_image);
        // start capturing te output buffer

        ob_start();
        // output the image data into the buffer

        imagejpeg($img_jpg_data);

        // capture the image data as a variable
        $img_data = ob_get_contents(); // the raw jpeg image data. 
        // clean the buffer 
        ob_end_clean();

        // get the site ID of this assessment
        $query = "SELECT site_ID FROM student_exam_instance_link WHERE students_ID = :studentID AND exam_instances_ID = :examinstanceID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        $stmt->bindValue(':examinstanceID', $examinstanceID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>submitWholeAssessment check existing assessment query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');
        //$result = mysqli_query($conn, $query) or die('<data><error>check query failed</error><detail>' . mysql_error() . $query . '</detail></data>');
        //if (mysqli_num_rows($result) > 0) {
        $site_ID = -1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            //$result = mysqli_query($conn, $query) or die('<data><error>query failed</error><detail>' . mysqli_error($conn) . '</detail></data>');
            //while ($row = mysqli_fetch_array($result)) {
            $siteid = $row['site_ID'];
        }
        $stmt->closeCursor();


        $conn->beginTransaction();
        // initial query
        $query = "INSERT INTO student_exam_sessions (student_ID, form_ID, site_ID, created_timestamp, created_by_ID, overall_rating, additional_rating, comments, signature_image, status, start_timestamp)";
        $query .= "VALUES(:studentID, :examinstanceID, :siteid, " . time() . ", :userID, :overall_rating, :additional_rating, :comments, :imgData, 'complete', " . time() . ");";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        $stmt->bindValue(':examinstanceID', $examinstanceID, PDO::PARAM_INT);
        $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        $stmt->bindValue(':siteid', $siteid, PDO::PARAM_INT);
        $stmt->bindValue(':overall_rating', $overall_rating, PDO::PARAM_INT);
        $stmt->bindValue(':additional_rating', $additional_rating, PDO::PARAM_INT);
        $stmt->bindValue(':comments', $comments, PDO::PARAM_STR);
        $stmt->bindValue(':imgData', $img_data, PDO::PARAM_STR);
        //$result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysql_error() . $query . '</detail></data>');
        $result = $stmt->execute() or die('<data><error>submitWholeAssessment overall query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        if ($result) {
            //   print('yay!');
            $insertID = $conn->lastInsertId();
            $stmt->closeCursor();
            $questionXML = simplexml_load_string($assessmentDataXML);
            // print_r($questionXML);
            foreach ($questionXML->answers->answer as $answer) {
                $query2 = "INSERT INTO student_exam_sessions_responses (student_exam_session_ID, question_ID, answer, comments, created_timestamp)";
                $query2 .= "VALUES(:insertID, :question_id, :value, :comment, " . time() . ");";
                $stmt = $conn->prepare($query2);
                $stmt->bindValue(':insertID', $insertID, PDO::PARAM_INT);
                $stmt->bindValue(':question_id', $answer->question_id, PDO::PARAM_INT);
                $stmt->bindValue(':value', $answer->value, PDO::PARAM_INT);
                $stmt->bindValue(':comment', $answer->comment, PDO::PARAM_STR);

                $result2 = $stmt->execute();
                if (!$result2) {
                    $success_status++;
                }
                $stmt->closeCursor();
            }
        } else {
            $success_status++;
        }
        if ($success_status > 0) {
            $conn->rollBack();
            return "<data><error>commit failed</error><detail>status was $success_status</detail></data>";
        } else {
            $conn->commit();
            return "<data><status>$success_status</status></data>";
        }

        return "<data><status>$success_status</status></data>";
    }

    /**
     * Updates an assessment item.
     * In future we might do this using a MySQL trigger?
     * @param type $itemid
     * @param type $newvalue
     * @param type $reason
     * @param type $user
     */
    public function updateAssessmentItem($itemid, $value, $comment, $reason, $user) {
        $dblib = new DbLib();
        $conn = $dblib->getConnection();
        // get old value
        $oldvalue = '';
        $oldcomment = '';
        $questionid = '';
        $query = "SELECT * FROM student_exam_sessions_responses WHERE ID = :itemid";
        $stmt = $dblib->buildStatement($conn, $query, array('itemid' => $itemid));
        if ($dblib->execute($stmt)) {
            $result = $dblib->getResult($stmt);
            foreach ($result as $row) {
                //print_r($row);
                $oldvalue = $row['answer'];
                $oldcomment = $row['comments'];
                $questionid = $row['question_ID'];
            }
        } else {
            return "<data><error>could not execute updateAssessmentItem</error></data>";
        }
        $newcomment = (strlen($comment) == 0) ? $oldcomment : $comment;
        $newvalue = (strlen($value) == 0) ? $oldvalue : $value;
        $now = time();
        $query = "UPDATE student_exam_sessions_responses SET answer = :answer, comments = :comments, last_modified_by_ID = :id, last_modified_timestamp = $now WHERE ID = :itemid";
        $stmt = $dblib->buildStatement($conn, $query, array('answer' => $value, 'comments' => $comment, 'id' => $user, 'itemid' => $itemid));
        $result = $dblib->execute($stmt);

        if ($result) {
            $stmt->closeCursor();
            $query = "INSERT INTO student_exam_sessions_responses_changelog (changed_by_ID, student_exam_sessions_responses_ID, oldvalue, newvalue, timestamp, description, oldcomment, newcomment, type) 
                VALUES (:user, :itemid, :oldvalue, :newvalue, $now, :reason, :oldcomment, :newcomment, :type) ;";
            $stmt = $dblib->buildStatement($conn, $query, array('user' => $user, 'itemid' => $itemid, 'oldvalue' => $oldvalue, 'newvalue' => $newvalue, 'reason' => $reason, 'oldcomment' => $oldcomment, 'newcomment' => $newcomment, 'type' => 'update'));
            $result = $dblib->execute($stmt);
        } else {
            return "<data><error>could not execute updateAssessmentItem2</error></data>";
        }
        return "<data><value>{$newvalue}</value><comment><![CDATA[{$newcomment}]]></comment></data>";
    }

    /**
     * Updates an assessment overview
     * @param type $assessmentid
     * @param type $rating
     * @param type $additionalrating
     * @param type $comment
     * @param type $reason
     * @param type $user
     * @return string
     */
    public function updateAssessmentOverview($assessmentid, $rating, $additionalrating, $comment, $reason, $user) {
        $dblib = new DbLib();
        $conn = $dblib->getConnection();
        // get old value
        $oldrating = '';
        $oldadditionalrating = '';
        $oldcomment = '';
        $query = "SELECT * FROM student_exam_sessions WHERE ID = :itemid";
        $stmt = $dblib->buildStatement($conn, $query, array('itemid' => $assessmentid));
        if ($dblib->execute($stmt)) {
            $result = $dblib->getResult($stmt);
            foreach ($result as $row) {
                $oldrating = $row['overall_rating'];
                $oldadditionalrating = $row['additional_rating'];
                $oldcomment = $row['comments'];
            }
        } else {
            return "<data><error>could not execute updateAssessmentOverview</error></data>";
        }
        $newcomment = (strlen($comment) == 0) ? $oldcomment : $comment;
        $newrating = (strlen($rating) == 0) ? $oldrating : $rating;
        $newadditionalrating = (strlen($additionalrating) == 0) ? $oldadditionalrating : $additionalrating;
        $now = time();
        $query = "UPDATE student_exam_sessions SET overall_rating = :overall_rating, additional_rating=:additional_rating, comments = :comments, last_modified_by_ID = :id, last_modified_timestamp = $now WHERE ID = :assessmentid";
        $stmt = $dblib->buildStatement($conn, $query, array('overall_rating' => $newrating, 'additional_rating' => $newadditionalrating, 'comments' => $comment, 'id' => $user, 'assessmentid' => $assessmentid));
        $result = $dblib->execute($stmt);

        if ($result) {
            $stmt->closeCursor();
            $query = "INSERT INTO student_exam_sessions_changelog (changed_by_ID, student_exam_sessions_ID, oldrating, newrating, oldadditionalrating, newadditionalrating, oldcomments, newcomments, timestamp, description, type) 
                VALUES (:user, :itemid, :oldrating, :newrating, :oldadditionalrating, :newadditionalrating, :oldcomments, :newcomments, $now, :reason, :type) ;";
            $stmt = $dblib->buildStatement($conn, $query, array('user' => $user, 'itemid' => $assessmentid, 'oldrating' => $oldrating, 'newrating' => $newrating, 'oldadditionalrating' => $oldadditionalrating, 'newadditionalrating' => $newadditionalrating, 'oldcomments' => $oldcomment, 'newcomments' => $newcomment, 'reason' => $reason, 'type' => 'update'));
            $result = $dblib->execute($stmt);
        } else {
            return "<data><error>could not execute updateAssessmentOverview log</error></data>";
        }
        return "<data><rating>{$newrating}</rating><additionalrating>{$newadditionalrating}</additionalrating><comment><![CDATA[{$newcomment}]]></comment></data>";
    }

    /**
     * Moderates an assessment item.
     * In future we might do this using a MySQL trigger?
     * @param type $itemid
     * @param type $newvalue
     * @param type $reason
     * @param type $user
     */
    public function moderateAssessmentItem($itemid, $value, $comment, $reason, $user) {
        $dblib = new DbLib();
        $conn = $dblib->getConnection();
        // get old value
        $oldvalue = '';
        $oldcomment = '';
        $questionid = '';
        $query = "SELECT * FROM student_exam_sessions_responses WHERE ID = :itemid";
        $stmt = $dblib->buildStatement($conn, $query, array('itemid' => $itemid));
        if ($dblib->execute($stmt)) {
            $result = $dblib->getResult($stmt);
            foreach ($result as $row) {
                //print_r($row);
                $oldvalue = $row['answer'];
                $oldcomment = $row['comments'];
                $questionid = $row['question_ID'];
            }
        } else {
            return "<data><error>could not execute moderateAssessmentItem</error></data>";
        }
        $newcomment = (strlen($comment) == 0) ? $oldcomment : $comment;
        $newvalue = (strlen($value) == 0) ? $oldvalue : $value;
        $now = time();
        $query = "UPDATE student_exam_sessions_responses SET moderated_answer = :answer, moderated_comments = :comments, moderated_by_ID = :id, moderated_timestamp = $now WHERE ID = :itemid";
        $stmt = $dblib->buildStatement($conn, $query, array('answer' => $value, 'comments' => $comment, 'id' => $user, 'itemid' => $itemid));
        $result = $dblib->execute($stmt);

        if ($result) {
            $stmt->closeCursor();
            $query = "INSERT INTO student_exam_sessions_responses_changelog (changed_by_ID, student_exam_sessions_responses_ID, oldvalue, newvalue, timestamp, description, oldcomment, newcomment, type) 
                VALUES (:user, :itemid, :oldvalue, :newvalue, $now, :reason, :oldcomment, :newcomment, :type) ;";
            $stmt = $dblib->buildStatement($conn, $query, array('user' => $user, 'itemid' => $itemid, 'oldvalue' => $oldvalue, 'newvalue' => $newvalue, 'reason' => $reason, 'oldcomment' => $oldcomment, 'newcomment' => $newcomment, 'type' => 'moderate'));
            $result = $dblib->execute($stmt);
        } else {
            return "<data><error>could not execute log of moderateAssessmentItem</error></data>";
        }
        return "<data><value>{$newvalue}</value><comment><![CDATA[{$newcomment}]]></comment></data>";
    }

    /**
     * Deprecated. Intended to clear all results
     * @global type $CFG
     * @return type
     */
    public function nuke() {
        global $CFG;
        // an initial status variable
        $success_status = 'false';
        // connect to db
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "truncate student_exam_sessions;";
        $result = mysql_query($conn, $query);
        if ($result) {
            $query = "truncate student_exam_sessions_responses;";
            $result = mysql_query($conn, $query);
            if ($result) {
                $success_status = 'true';
            }
        }
        return "<data><status>$success_status</status></data>";
    }

    /**
     * Log an assessment event
     * @param type $user
     * @param type $sessionid
     * @param type $questionid
     * @param type $type
     * @param type $newvalue
     * @param type $oldvalue
     */
    public function doLog($user, $sessionID, $responseid, $type, $newvalue, $oldvalue = "") {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->server};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "INSERT INTO exam_log (student_exam_session_ID, student_exam_sessions_responses_ID, event_type, timestamp, old_value, new_value, user_ID) ";
        $query .= "VALUES (:sessionid, :responseid, :type, " . time() . ", :oldvalue, :newvalue, :user)";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionid', $sessionID, PDO::PARAM_INT);
        $stmt->bindValue(':responseid', $responseid, PDO::PARAM_INT);
        $stmt->bindValue(':user', $user, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':oldvalue', $oldvalue, PDO::PARAM_STR);
        $stmt->bindValue(':newvalue', $newvalue, PDO::PARAM_STR);

        $result = $stmt->execute();
        // $result = mysqli_query($conn, $query) or die('<data><error>doLog query failed</error><detail>' . mysql_error() . $query . '</detail></data>');
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

}

?>

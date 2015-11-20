<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Provides public feedback to participants
 *
 * @author alandow
 */
class FeedbackLib {

    /**
     * Sends an email to all students assessed in a session, containing a PDF of their assessment results (but not global comments or overall assessment)
     * @global type $CFG
     * @param type $sessionID
     * @param type $includeComments
     * @return string an XML-formatted string containing the success and failure count of the operation
     */
    public function sendMail($sessionID, $showOverallResult = false) {
        //  getExamEmailStemByID
        global $CFG;
        $reportsLib = new ReportsLib();
        $stringlib = new StringLib();
        $enumlib = new EnumLib();
        $failcount = 0;
        $successcount = 0;

        $i = 0;
        $length = 0;
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        // get count
        $countquery = "SELECT count(*) as count FROM student_exam_sessions a WHERE a.form_ID = :sessionID";
        $stmt = $conn->prepare($countquery);
        $stmt->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>sendMail count query failed</error><detail>' . $stmt->errorInfo() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $linecount = $row['count'];
        }
        $stmt->closeCursor();

        $query = "SELECT a.ID,  a.student_id, (SELECT email from students WHERE ID = a.student_id) as email, (SELECT CONCAT(fname, ' ', lname) as studentname from students WHERE ID = a.student_id) as studentname  FROM student_exam_sessions a WHERE a.form_ID = :sessionID";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
//$result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt->execute() or die('<data><error>sendMail query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stemXML = simplexml_load_string($enumlib->getExamEmailStemByID($sessionID));
            $mail = new PHPMailer();
            $mail->SetFrom("donotreply@une.edu.au", "eOSCE System");

            $mail->Body = "Dear {$row['studentname']}<br/>" . $stemXML->text;
            $mail->IsHTML(true);
            $mail->Subject = 'OSCE Feedback';
            if ($CFG->sendemailtoonlyoneperson) {
                $to = $CFG->sendemailtoonlyonepersonrecipient;
            } else {
                $to = $row['email'];
            }
            ////$row['email'];

            $mail->AddAddress($to);
            //ob_start();
            $pdf = $reportsLib->getReportForStudentSessionAsPDFForEmail($row['ID'], $showOverallResult);
            //$body = ob_get_contents(); // the pdf data. 
            //ob_end_clean();

            $mail->AddStringAttachment($pdf, "report.pdf");

            if (!$mail->Send()) {
                $this->doLog($sessionID, $row['student_id'], $mail->ErrorInfo);
                $failcount++;
                //echo "Mailer Error: " . $mail->ErrorInfo;
            } else {
                $this->doLog($sessionID, $row['student_id'], 'true');
                $successcount++;
            }
            // set a progress variable as event
          //  ob_start();
            $i++;
            echo round(($i / $linecount) * 100) . "%,";
            ob_flush();
            flush();
            //ob_end_clean();
        }
        $enumlib = new EnumLib();
        $logresults = $enumlib->getFeedbackSummary($sessionID);
        return "<data><status>done</status><logresults><successcount>{$successcount}</successcount><failcount>{$failcount}</failcount><totalcount>" . ($failcount + $successcount) . "</totalcount></logresults></data>";
    }

    /**
     * Sends an email to all students assessed in a session, containing a PDF of their assessment results (but not global comments or overall assessment)
     * @global type $CFG
     * @param type $sessionID
     * @param type $includeComments
     * @return string an XML-formatted string containing the success and failure count of the operation
     */
    public function sendTestMail($sessionID, $emailaddress, $emailtext, $includeComments, $showOverallResult = false) {
        //  getExamEmailStemByID
        global $CFG;
        $reportsLib = new ReportsLib();
        $stringlib = new StringLib();
        $enumlib = new EnumLib();
        $failcount = 0;
        $successcount = 0;
        $errordetail = "";
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT a.ID,  a.student_id, (SELECT email from students WHERE ID = a.student_id) as email, (SELECT CONCAT(fname, ' ', lname) as studentname from students WHERE ID = a.student_id) as studentname  FROM student_exam_sessions a WHERE a.form_ID = :sessionID LIMIT 0,1";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
//$result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt->execute() or die('<data><error>sendMail query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            //$stemXML = simplexml_load_string($enumlib->getExamEmailStemByID($sessionID));
            $mail = new PHPMailer();
            $mail->SetFrom("donotreply@une.edu.au", "eOSCE System");

            $mail->Body = "Dear {$row['studentname']}<br/>" . $emailtext;
            $mail->IsHTML(true);
            $mail->Subject = 'OSCE Feedback';

            $to = $emailaddress;

            ////$row['email'];

            $mail->AddAddress($to);
            ob_start();
            $reportsLib->getReportForStudentSessionAsPDF($row['ID'], $showOverallResult);
            $body = ob_get_contents(); // the raw jpeg image data. 
            ob_end_clean();

            $mail->AddStringAttachment($body, "report.pdf");

            if (!$mail->Send()) {
                //    $this->doLog($sessionID, $row['student_id'], $mail->ErrorInfo);
                $failcount++;
                $errordetail = $mail->ErrorInfo;
            } else {
                //  $this->doLog($sessionID, $row['student_id'], 'true');
                $successcount++;
            }
        }
        // $enumlib = new EnumLib();
        //$logresults = $enumlib->getFeedbackSummary($sessionID);
        return "<data><status>$failcount</status>" . ($failcount > 0 ? "<error><detail>$errordetail</detail></error>" : "") . "</data>";
    }

    /**
     * Adds an entry to the feedback log table
     * @global type $CFG
     * @param type $sessionID the examination session ID for this log entry
     * @param type $participantID the ID of the student this entry is for
     * @param type $status the status of the mail event
     */
    private function doLog($sessionID, $participantID, $status) {
        global $CFG;
        $reportsLib = new ReportsLib();
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "INSERT INTO mail_log(session_id, participant_to_id, status) VALUES (:sessionID, :participantID, :status);";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
        $stmt->bindValue(':participantID', $participantID, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->execute() or die('<data><error>doLog query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
    }

}

?>

<?php

/**
 * Functions relating to the actual OSCE forms
 *
 * @author alandow
 */
//require_once('config.inc.php');

class FormsLib {

    /**
     * Make a new examination instance definition
     * @global type $CFG
     * @param type $name The name of this examinadion
     * @param type $description a description of this examination
     * @param type $unitid the unit this examination is for. Look up from unit lookup table
     * @param type $exam_starttimestamp the start date of this exam (as a UNIX timestamp)
     * @param type $ownerID the ID of the owner of this exam
     * @param type $userID the ID
     * @return XML-formatted string containing the id of the newly created instance, or an error if the insert query fails
     */
    public function newExamInstance($name, $description, $unitid, $scaleid, $ownerID, $userID, $feedbackstem = "") {
        global $CFG;
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "INSERT INTO exam_instances (name, description, unit_id, scaleID, created_by_id, created_timestamp, owner_id, active, archived, deleted, emailtext) 
            VALUES(:name, :description, :unitid, :scaleid, :userID, " . time() . ", :ownerID, 'false', false, false, :emailtext );";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':unitid', $unitid, PDO::PARAM_INT);
        $stmt->bindValue(':scaleid', $scaleid, PDO::PARAM_INT);
        $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        $stmt->bindValue(':ownerID', $ownerID, PDO::PARAM_INT);
        $stmt->bindValue(':emailtext', $feedbackstem, PDO::PARAM_STR);

        // print('scaleid:' . $scaleid . ',owner:' . $ownerID . ',user:' . $userID);
        //  $result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $result = $stmt->execute() or die('<data><error>newExamInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        if ($result) {
            $returnStr = $conn->lastInsertId();
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * Updates an examination instance definition
     * @global type $CFG
     * @param type $id the ID of the exam to update
     * @param type $name The name of this examinadion
     * @param type $description a description of this examination
     * @param type $unitid the unit this examination is for. Look up from unit lookup table
     * @param type $exam_starttimestamp the start date of this exam (as a UNIX timestamp)
     * @param type $ownerID the ID of the owner of this exam
     * @return XML-formatted string containing the id of the newly created instance, or an error if the insert query fails
     */
    public function updateExamInstance($id, $name, $description, $unitid, $scaleid, $ownerID) {
        global $CFG;
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_instances SET name = :name, description= :description, unit_id = :unitid, scaleID=:scaleid, owner_id = :ownerID WHERE ID = :id;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':unitid', $unitid, PDO::PARAM_INT);
        $stmt->bindValue(':scaleid', $scaleid, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':ownerID', $ownerID, PDO::PARAM_INT);

        //  $result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $result = $stmt->execute() or die('<data><error>updateExamInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        // $result = mysqli_query($conn, $query) or die('<data><error>update query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        if ($result) {
            $returnStr = $stmt->rowCount(); // mysqli_affected_rows($conn);
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * Imports an exam instance 
     * @param type $file
     * @param type $ownerID
     * @param type $unitID
     * @param type $userID
     * @return string
     */
    function importExamInstance($file, $ownerID, $unitID, $userID) {
        if (is_uploaded_file($file['tmp_name'])) {
            $examdef = simplexml_load_file($file['tmp_name']) or die("<data><error>bad import file</error></data>");
            // check sanity
            if (isset($examdef->overview->data->instance)) {
                // do the name, description and questiondata exist?
                if (isset($examdef->overview->data->instance->name) && isset($examdef->overview->data->instance->description) && isset($examdef->questiondata->data)) {
                    // make a new exam
                    $overviewResult = simplexml_load_string($this->newExamInstance($examdef->overview->data->instance->name, $examdef->overview->data->instance->description, $unitID, $ownerID, $userID, $examdef->feedbackemailstem->data->text));
                    if (!isset($overviewResult->error)) {
                        // if there's no error, upload questions
                        foreach ($examdef->questiondata->data->question as $question) {
                            $insertQuestionResult = simplexml_load_string($this->addQuestionToInstance($overviewResult->id, $question->text, $question->type, $userID));
                            if (isset($insertQuestionResult->error)) {
                                $this->deleteInstance($overviewResult->id);
                                return "<data><error>bad import file</error></data>";
                            }
                        }
                    } else {
                        return "<data><error>bad import file</error></data>";
                    }
                } else {
                    return "<data><error>bad import file</error></data>";
                }
            } else {
                return "<data><error>bad import file</error></data>";
            }
        } else {
            return "<data><error>Not a proper file</error></data>";
        }
        return "<data><id>{$overviewResult->id}</id></data>";
    }

    function cloneExamInstance($id, $ownerID, $userID) {
        $enumLib = new EnumLib();
        $examdef = simplexml_load_string($enumLib->exportAssessment($id));
        $overview = simplexml_load_string($enumLib->getExamInstanceOverviewByID($id));

        $overviewResult = simplexml_load_string($this->newExamInstance($examdef->overview->data->instance->name, $examdef->overview->data->instance->description, $examdef->overview->data->instance->unit_id, $examdef->overview->data->instance->scale_id, $ownerID, $userID, $examdef->feedbackemailstem->data->text));


//public function newExamInstance($name, $description, $unitid, $scaleid, $ownerID, $userID, $feedbackstem = "") 
        if (!isset($overviewResult->error)) {
            // if there's no error, upload questions
            foreach ($examdef->questiondata->data->question as $question) {
                $insertQuestionResult = simplexml_load_string($this->addQuestionToInstance($overviewResult->id, $question->text, $question->type, $userID));
                //
                if (isset($insertQuestionResult->error)) {
                    $this->deleteInstance($overviewResult->id);
                    return "<data><error>import failed</error></data>";
                }
            }
        } else {
            return "<data><error>{$overviewResult->error}</error></data>";
        }
    }

    /**
     * Marke an examination instance as deleted
     * @global type $CFG
     * @param type $instanceID the ID of the examination instance to delete
     * @return XML-formatted string containing the status of the operation, or an error if the insert query fails
     */
    public function deleteInstance($instanceID) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //  $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "UPDATE exam_instances SET deleted = 'true' WHERE ID = :instanceID;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':instanceID', $instanceID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>deleteInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        //  $result = mysqli_query($conn, $query) or die('<data><error>delete query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        if ($stmt->rowCount() > 0) {
            $returnStr = 'true';
        } else {
            $returnStr = 'false';
        }
        return "<data><status>$returnStr</status></data>";
    }

    /**
     * Associates users with an instance ID: ie make them an assessor
     * @global type $CFG
     * @param type $id the instance ID
     * @param type $userID a specific user ID
     * @return XML-formatted string containing details of the user, or an error if the insert query fails
     */
    public function associateUsersWithInstance($id, $userID) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');

        $query = "INSERT INTO users_exam_instances_link (exam_instances_ID, users_ID) 
            VALUES(:id, :userID);";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>associateUsersWithInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        //   $result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');

        if ($conn->lastInsertId() > 0) {
            $enumlib = new EnumLib();
            return $enumlib->getUserByID($userID);
        } else {

            return '<data><error>operation failed</error><detail>' . mysqli_error($conn) . '</detail></data>';
        }
        // return "<data><id>$returnStr</id></data>";
    }

    /**
     * Updates the association of a student to a site for a specific exam
     * @global type $CFG
     * @param type $entryid the association instance entry ID
     * @param type $newsite the new site ID
     * @return XML-formatted string containing the success of the operation, or an error if the insert query fails
     */
    public function updateStudentAssociatedWithInstanceSite($entryid, $newsite) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');

        $query = "UPDATE student_exam_instance_link SET site_ID = :newsite WHERE ID = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':newsite', $newsite, PDO::PARAM_INT);
        $stmt->bindValue(':id', $entryid, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>updateStudentAssociatedWithInstanceSite query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        //   $result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');

        if ($stmt->rowCount() > 0) {
            $enumlib = new EnumLib();
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . mysqli_error($conn) . '</detail></data>';
        }
        // return "<data><id>$returnStr</id></data>";
    }

    /**
     * Dissociates user with an instance ID: ie removes them as an assessor
     * @global type $CFG
     * @param type $id
     * @param type $userID
     * @return XML-formatted string containing details of the user, or an error if the insert query fails
     */
    public function dissociateUserWithInstance($id, $userID) {

        global $CFG;
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "DELETE FROM {$CFG->schema}.users_exam_instances_link WHERE exam_instances_ID = :id AND users_ID = :userID;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        //$result = mysqli_query($conn, $query) or die('<data><error>delete query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $result = $stmt->execute() or die('<data><error>dissociateUserWithInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        if ($stmt->rowCount() > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . $stmt->errorCode() . '</detail></data>';
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * DEPRECATED
     * Associates student with an exam instance ID
     * @global type $CFG
     * @param type $id
     * @param type $studentID The ID of the student to associate
     * @return XML-formatted string containing details of the student, or an error if the insert query fails
     */
    public function associateStudentWithInstance($id, $studentID) {

        global $CFG;
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        // check that this student isn't apready assigned:
        $query = "SELECT COUNT(*) FROM student_exam_instance_link WHERE exam_instances_ID = :id AND students_ID = :studentID";
        //$result = mysqli_query($conn, $query) or die('<data><error>associateStudentsWithInstance insert query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        //$result = mysqli_query($conn, $query) or die('<data><error>delete query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        //$result = $stmt->execute() or die('<data><error>check associateStudentsWithInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        $rows = $stmt->fetch(PDO::FETCH_NUM) or die('<data><error>check associateStudentsWithInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        if ($rows[0] > 0) {

            $query = "INSERT INTO {$CFG->schema}.student_exam_instance_link (exam_instances_ID, students_ID) 
            VALUES(:id, :studentID);";

            //$result = mysqli_query($conn, $query) or die('<data><error>associateStudentsWithInstance insert query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
            //$result = mysqli_query($conn, $query) or die('<data><error>delete query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            $result = $stmt->execute() or die('<data><error>associateStudentsWithInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
            if ($stmt->rowCount() > 0) {
                $enumlib = new EnumLib();
                return $enumlib->getStudentByID($studentID);
            } else {

                return '<data><error>operation failed</error><detail>' . mysqli_error($conn) . '</detail></data>';
            }
        } else {

            return '<data><error>operation failed</error><detail>' . mysqli_error($conn) . '</detail></data>';
        }
        // return "<data><id>$returnStr</id></data>";
    }

    /**
     * Associates multiple students with an exam instance ID and a location
     * @global type $CFG
     * @param type $id
     * @param type $studentIDs The IDs of the student to associate, as a comma separated list
     * @return XML-formatted string containing details of the student, or an error if the insert query fails
     */
    public function associateStudentsWithInstance($id, $studentIDs, $siteID) {
        // a little cleanup
        $ids = explode(',', $studentIDs);
        $ids_sql = implode(",", array_map("intval", $ids));
        global $CFG;
        $success = true;
        $studentsStr = "";

        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        // check that this student isn't apready assigned:
        $query = "SELECT COUNT(*) FROM student_exam_instance_link WHERE exam_instances_ID = :id AND students_ID IN ($ids_sql)";
        //$result = mysqli_query($conn, $query) or die('<data><error>associateStudentsWithInstance insert query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':studentIDs', $studentIDs, PDO::PARAM_INT);
        //$result = mysqli_query($conn, $query) or die('<data><error>delete query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        //$result = $stmt->execute() or die('<data><error>check associateStudentsWithInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        $rows = $stmt->fetch(PDO::FETCH_NUM); // or die('<data><error>check associateStudentsWithInstance query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');


        if ($rows[0] == 0) {

            for ($i = 0; $i < count($ids); $i++) {
                $stmt->closeCursor();
                $query = "INSERT INTO student_exam_instance_link (exam_instances_ID, students_ID, site_ID) VALUES(:id, :studentID, :site);";

                //$result = mysqli_query($conn, $query) or die('<data><error>associateStudentsWithInstance insert query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
                $stmt = $conn->prepare($query);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->bindValue(':studentID', $ids[$i], PDO::PARAM_INT);
                $stmt->bindValue(':site', $siteID, PDO::PARAM_INT);
                //$result = mysqli_query($conn, $query) or die('<data><error>delete query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
                $result = $stmt->execute() or die('<data><error>associateStudentsWithInstance query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');
                if ($stmt->rowCount() < 1) {
                    $success = false;
                } else {
                    $enumlib = new EnumLib();
                    $info = simplexml_load_string($enumlib->getStudentByID($ids[$i]));
                    //$siteinfo = simplexml_load_string($enumlib->getSiteByID($id));
                    $studentsStr.= $info->student[0]->asXML();
                }
            }
        }

        if ($success) {
            return "<data>$studentsStr</data>";
        } else {
            return '<data><error>operation failed</error><detail></detail></data>';
        }
    }

    /**
     * Associate a list of students defined by a CSV list to an exam instance, checking with LDAP if necessary
     * @global type $CFG
     * @param type $id the examination instance
     * @param type $file teh CSV file. It needs to have the header 'studentid'
     * @return XML-formatted string containing a count of the successful operations, a count of the failed, or an error
     */
    public function associateStudentsWithInstanceByCSV($id, $file) {
        session_start();
        global $CFG;

        $i = 0;
        $length = 0;
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $isfirstrow = true;
        $studentnumrow = 0;
        $siterow = 0;
        $createsuccesscount = 0;
        $createfailcount = 0;
        $criticalerror = false;
        $criticalerrordetails = "";
        if ($CFG->use_ldap_for_student_lookups == true) {
            // get it from LDAP
            $authlib = new authlib();
            // set up LDAP connection
            $ldap = ldap_connect($CFG->student_ldap) or die('cannot connect to student directory');
            $ldappassword = $CFG->student_ldap_adminpass;
            $ldaprdn = $CFG->student_ldap_adminuser . $CFG->student_ldap_account_suffix;
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            $bind = ldap_bind($ldap, $ldaprdn, $ldappassword);
        }

        // get CSV
        if (is_uploaded_file($file['tmp_name'])) {
            if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
// get the number of rows in the file
                $linecount = count(file($file['tmp_name']));
                // get the rows using headers, check for sanity
                while (($data = fgetcsv($handle)) !== FALSE) {
                    // print_r($data);
                    if ($isfirstrow) {
                        if (array_search('studentid', $data) !== false) {
                            $studentnumrow = array_search('studentid', $data);
                        } else {
                            return '<data><error>Field header missing</error><detail>Needs to have a header called studentid</detail></data>';
                        }
                        if (array_search('site', $data) !== false) {
                            $siterow = array_search('site', $data);
                        } else {
                            return '<data><error>Field header missing</error><detail>Needs to have a header called site</detail></data>';
                        }
                        $isfirstrow = false;
                    } else {
                        // we've got the header fields, do something with them
                        // check that this student is already in the user table. If not, we'll have to check LDAP for an entry, and failing that we'll need to pass
                        $query = "SELECT COUNT(*) as count FROM students WHERE studentnum = :studentnum";
                        $stmt = $conn->prepare($query);
                        $stmt->bindValue(':studentnum', $data[$studentnumrow], PDO::PARAM_STR);
                        $stmt->execute() or die('<data><error>check studente exist query failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>');
                        // if it doesn't exist in the student table, *AND* we're getting it from LDAP

                        if (($stmt->fetchObject()->count) == 0) {
                            if ($CFG->use_ldap_for_student_lookups == true) {
                                // get it from LDAP
                                $filter = "(&(objectCategory=person)({$CFG->student_ldap_searchfield}={$CFG->student_ldap_search_prefix}$data[$studentnumrow]{$CFG->student_ldap_search_suffix}))";
                                $result = ldap_search($ldap, $CFG->student_ldap_base_dn, $filter);
                                //  ldap_sort($ldap, $result, "sn");
                                $info = ldap_get_entries($ldap, $result);
                                // print_r($info);
                                //  for ($i = 0; $i < $info["count"]; $i++) {
                                if ($info['count'] > 0) {
                                    $criticalerror = false;
                                    // check student
                                    //   $returnStr = "<fname>{$info[0][$CFG->student_ldap_fname][0]}</fname><lname>{$info[0][$CFG->student_ldap_lname][0]}</lname><email>{$info[0][$CFG->student_ldap_email][0]}</email>";
                                } else {
                                    $criticalerror = true;
                                    $criticalerrordetails .= 'The student ID ' . $studentnum . ' is not valid;';
                                }

                                //  $studentdata = simplexml_load_string($authlib->getStudentDetailsFromLDAP($data[$studentnumrow]));
                                if (!$criticalerror) {
                                    //  $createfailcount++;
                                    // insert it into the student table
                                    $insertresult = $authlib->new_student($info[0][$CFG->student_ldap_fname][0], $info[0][$CFG->student_ldap_lname][0], $data[$studentnumrow], $info[0][$CFG->student_ldap_email][0], 0);
                                } else {
                                    $criticalerror = true;
                                    $criticalerrordetails .= 'The student ID ' . $data[$studentnumrow] . ' is not valid;';
                                }
                            }

                            $stmt->closeCursor();
//                            // set a progress variable as event
                            $i++;
                            echo round(($i / $linecount) * 100) . "%,";
                            ob_flush();
                            flush();
                        } else {
                            // there's an entry in the student table, but is there an entry in LDAP?
                            if ($CFG->use_ldap_for_student_lookups == true) {
                                // a MASSIVE hack- send out to the world a status update
                                $i++;
                                echo round(($i / $linecount) * 100) . "%,";
                                ob_flush();
                                flush();
                                $filter = "({$CFG->student_ldap_searchfield}={$CFG->student_ldap_search_prefix}$data[$studentnumrow]{$CFG->student_ldap_search_suffix})";
                                $result = ldap_search($ldap, $CFG->student_ldap_base_dn, $filter);
                                //  ldap_sort($ldap, $result, "sn");
                                $info = ldap_get_entries($ldap, $result);
                                // print_r($info);
                                //  for ($i = 0; $i < $info["count"]; $i++) {
                                if ($info['count'] > 0) {
                                    $criticalerror = false;
                                }
                                if (isset($studentdata->error)) {
                                    $criticalerror = true;
                                    $criticalerrordetails .= 'No student with ID ' . $data[$studentnumrow] . ' in LDAP;';
                                    $createfailcount++;
                                }
                            }
                        }
                    }

                    // moving on...

                    if (!$criticalerror) {
                        // check that this student hasn't already been associated with this exam
                        $query = "SELECT COUNT(*) as count FROM student_exam_instance_link WHERE students_ID = (SELECT ID FROM students WHERE studentnum = :studentnum) AND exam_instances_ID = :id";
                        $stmt = $conn->prepare($query);
                        $stmt->bindValue(':studentnum', $data[$studentnumrow], PDO::PARAM_STR);
                        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                        $stmt->execute() or die('<data><error>check linkage query failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>');
                        //$result = mysqli_query($conn, $query) or die('<data><error>check linkage query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
                        if (($stmt->fetchObject()->count) == 0) {
                            $stmt->closeCursor();
                            $query = "SELECT ID FROM students WHERE studentnum = :studentnum";
                            // print($query);
                            $stmt = $conn->prepare($query);
                            $stmt->bindValue(':studentnum', $data[$studentnumrow], PDO::PARAM_STR);
                            $stmt->execute() or die('<data><error>check student ID query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
//$result2 = mysqli_query($conn, $query) or die('<data><error>check student ID query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $studentid = $row['ID'];
                            }
                            if (isset($studentid)) {
                                $enumlib = new EnumLib();
                                $resultXMLStr = $this->associateStudentsWithInstance($id, $studentid, $enumlib->getSiteIDByShortCode($data[$siterow]));

                                $resultXML = simplexml_load_string($resultXMLStr);
                                // print_r($resultXML);
                                if ($resultXML->student[0]->id > 0) {
                                    $createsuccesscount++;
                                } else {
                                    $createfailcount++;
                                }
                            }
                        }
                    } else {
                        $createfailcount++;
                        $criticalerror = false;
                    }
                }
            }
            fclose($handle);
        }
        return(",<data><success>$createsuccesscount</success><fail>$createfailcount</fail>" . (strlen($criticalerrordetails) > 0 ? "<error><detail>$criticalerrordetails</detail></error>" : "") . "<report></report></data>");
    }

    /**
     * Dissociates student from an instance ID
     * @global type $CFG
     * @param type $id the instance ID
     * @param type $studentID the ID of the student to dissociate
     * @return XML-formatted string containing a status of the operation, or an error
     */
    public function dissociateStudentsWithInstance($id, $studentID) {

        global $CFG;
        //  $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        $query = "DELETE FROM student_exam_instance_link WHERE exam_instances_ID = :id AND students_ID = :studentID;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>dissociateStudentsWithInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        if ($stmt->rowCount() > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . mysqli_error($conn) . '</detail></data>';
        }
        // return "<data><id>$returnStr</id></data>";
    }

    /**
     * Adds a question to an exam instance ID
     * @global type $CFG
     * @param type $id the exam instance ID
     * @param type $questionID The question ID
     * @return XML-formatted string containing ID of the newly created link, or an error
     */
    public function addQuestionToInstance($id, $text, $type, $user) {

        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $maxquery = "SELECT IFNULL(MAX(`order`)+1,0) as nextorder FROM exam_questions WHERE exam_ID = :id";
        $stmt = $conn->prepare($maxquery);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>addQuestionToInstance query 1 failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        //  print($maxquery);
        $nextorder = 0;
        //$result = mysqli_query($conn, $maxquery) or die('<data><error>SELECT query failed</error><detail>' . mysqli_error($conn) . $maxquery . '</detail></data>');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($result)) {
            $nextorder = $result['nextorder'];
        }
        $stmt->closeCursor();
//print("nextorder is:$nextorder");
        $query = "INSERT INTO `exam_questions` (`exam_ID`, `text`, `type`, `order`, `created_byID`) 
            VALUES(:id, :text, :type, $nextorder, :user);";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user', $user, PDO::PARAM_INT);
        $stmt->bindValue(':text', $text, PDO::PARAM_STR);
        $stmt->bindValue(':type', $type, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>addQuestionToInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        //$result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');

        if ($conn->lastInsertId() > 0) {
            $returnStr = $conn->lastInsertId();
        } else {

            return '<data><error>operation failed</error><detail>' . mysqli_error($conn) . '</detail></data>';
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * Updates an assessment item
     * @global type $CFG
     * @param type $id the id of the asessment item
     * @param type $text the text of the asessment item
     * @param type $required if this is required (not used at this time)
     * @param type $type Either 'question' or 'label'. 'label' is not really used, but it possibly could be
     * @param type $notes Notes explaining the question. Currently unused
     * @param type $userID teh ID of the user updating this question
     * 
     * @return an XML formatted string containing the number of affected rows (should be 1), or an error
     * 
     */
    public function get_assessment_item($id) {
        global $CFG;
        $returnStr = "";
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "SELECT * FROM exam_questions WHERE ID = :id;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        //$result = mysqli_query($conn, $query) or die('<data><error>check query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt->execute() or die('<data><error>get_assessment_item query failed</error><detail>' . $stmt->errorInfo() . $query . '</detail></data>');
        //print($stmt->rowCount());
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                //print_r($row);
                $returnStr.="<question><id>{$row['ID']}</id><text><![CDATA[{$row['text']}]]></text><type>{$row['type']}</type></question>";
            }
        }
        return "<data>$returnStr</data>";
    }

    /**
     * Updates an assessment item
     * @global type $CFG
     * @param type $id the id of the asessment item
     * @param type $text the text of the asessment item
     * @param type $required if this is required (not used at this time)
     * @param type $type Either 'question' or 'label'. 'label' is not really used, but it possibly could be
     * @param type $notes Notes explaining the question. Currently unused
     * @param type $userID teh ID of the user updating this question
     * 
     * @return an XML formatted string containing the number of affected rows (should be 1), or an error
     * 
     */
    public function update_assessment_item($id, $text, $type, $userID) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_questions SET text = :text, type = :type, modified_timestamp = " . time() . ", modified_byID = :user WHERE ID = :id;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user', $userID, PDO::PARAM_INT);
        $stmt->bindValue(':text', $text, PDO::PARAM_STR);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $result = $stmt->execute() or die('<data><error>update_assessment_item query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        if ($result) {
            $returnStr = $stmt->rowCount();
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * Remove an assessment item from an exam instance
     * @global type $CFG
     * @param type $id
     * @return string
     */
    public function removeQuestionFromInstance($id) {
        global $CFG;
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_questions SET `deleted` ='true' WHERE ID = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>removeQuestionFromInstance query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        if ($stmt->rowCount() > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . mysqli_error($conn) . '' . $query . ' because there were no affected rows</detail></data>';
        }
    }

    /**
     * Re-orders a list of questions
     * @global type $CFG
     * @param type $id the exam instance ID
     * @param type $orderdefXML an XML-formatted list of ID's and orders.
     * @return an XML formatted string containing the status of the operation, or an error
     */
    public function reorder_QuestionWithInstanceAssociation($id, $orderdefXML) {
        global $CFG;
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $success_status = 0;
        // loop and order
        // Start the transaction
//        mysqli_query($conn, "SET AUTOCOMMIT=0");
//        mysqli_query($conn, "START TRANSACTION");
        $orderXML = simplexml_load_string($orderdefXML);
        $conn->beginTransaction();
        foreach ($orderXML->def as $def) {
            $query = "UPDATE exam_questions SET `order` = :neworder  WHERE ID = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':neworder', $def->order, PDO::PARAM_INT);
            $stmt->bindValue(':id', $def->id, PDO::PARAM_INT);
            // print($query);
            //$result = mysqli_query($conn, $query) or die('<data><error>order set operation failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            if (!$stmt->execute()) {
                $success_status++;
            }
        }
        if ($success_status > 0) {
            $conn->rollBack();
            return '<data><error>order set operation failed</error><detail>' . $conn->errorCode() . '</detail></data>';
        } else {
            $conn->commit();
            return "<data><status>$success_status</status></data>";
        }
    }

    /**
     * Sets the feedback email stem text for an assessment
     * @global type $CFG
     * @param type $id
     * @param type $text
     * @param type $userID
     * @return type
     */
    public function setEmailStemText($id, $text, $userID) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_instances SET emailtext = :text, modified_timestamp = " . time() . ", modified_byID = :user WHERE ID = :id;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user', $userID, PDO::PARAM_INT);
        $stmt->bindValue(':text', $text, PDO::PARAM_STR);
        $result = $stmt->execute() or die('<data><error>setEmailStemText query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');

        if ($result) {
            $returnStr = $stmt->rowCount();
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * Locks an examination instance. Cheating a bit and not using prepared statements as there's not much of a chance of SQL injection here :)
     * @global type $CFG
     * @param type $id the ID of the exam instance
     * @return an XML formatted string containing the status of the operation, or an error
     */
    public function lockInstance($id) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_instances SET exam_instances.finalised = 'true' WHERE ID = $id";
        if ($conn->exec($query) > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . $conn->errorCode() . '</detail></data>';
        }
    }

    /**
     * Unlocks an examination instance
     * @global type $CFG
     * @param type $id the ID of the exam instance
     * @return an XML formatted string containing the status of the operation, or an error
     */
    public function unlockInstance($id) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_instances SET exam_instances.finalised = '' WHERE ID = $id";
        if ($conn->exec($query) > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . $conn->errorCode() . '</detail></data>';
        }
    }

    /**
     * Activates a practice of examination instance
     * @global type $CFG
     * @param type $id the ID of the exam instance
     * @return an XML formatted string containing the status of the operation, or an error
     */
    public function activatePracticeForInstance($id) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_instances SET exam_instances.practicing = 'true' WHERE ID = $id";
        if ($conn->exec($query) > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . $conn->errorCode() . '</detail></data>';
        }
    }

    /**
     * Deactivates a practice of examination instance
     * @global type $CFG
     * @param type $id the ID of the exam instance
     * @return an XML formatted string containing the status of the operation, or an error
     */
    public function deactivatePracticeForInstance($id) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_instances SET exam_instances.practicing = 'false' WHERE ID = $id";
        if ($conn->exec($query) > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . $conn->errorCode() . '</detail></data>';
        }
    }

    /**
     * Activates an examination instance
     * @global type $CFG
     * @param type $id the ID of the exam instance
     * @return an XML formatted string containing the status of the operation, or an error
     */
    public function activateInstance($id) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_instances SET exam_instances.active = 'true', exam_starttimestamp = " . time() . " WHERE ID = $id";
        if ($conn->exec($query) > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . $conn->errorCode() . '</detail></data>';
        }
    }

    /**
     * Deactivates an examination instance
     * @global type $CFG
     * @param type $id the ID of the exam instance
     * @return an XML formatted string containing the status of the operation, or an error
     */
    public function deactivateInstance($id) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_instances SET exam_instances.active = 'false', exam_endtimestamp = " . time() . " WHERE ID = $id";
        if ($conn->exec($query) > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . $conn->errorCode() . '</detail></data>';
        }
    }

    /**
     * archives an examination instance
     * @global type $CFG
     * @param type $id the ID of the exam instance
     * @return an XML formatted string containing the status of the operation, or an error
     */
    public function archiveInstance($id) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_instances SET exam_instances.archived = 'true' WHERE ID = $id";
        if ($conn->exec($query) > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . $conn->errorCode() . '</detail></data>';
        }
    }

    /**
     * un-archives an examination instance
     * @global type $CFG
     * @param type $id the ID of the exam instance
     * @return an XML formatted string containing the status of the operation, or an error
     */
    public function unarchiveInstance($id) {
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE exam_instances SET exam_instances.archived = 'false' WHERE ID = $id";
        if ($conn->exec($query) > 0) {
            return '<data><status>success</status></data>';
        } else {

            return '<data><error>operation failed</error><detail>' . $conn->errorCode() . '</detail></data>';
        }
    }

    /**
     * get a printer-friendly HTML form for backup of assessment if the technology fails...
     * @global type $CFG
     * @param type $id the ID of the assessment form
     * @return string the HTML of the printable assessment form
     */
    public function get_printable_assessment_form($id) {
        global $CFG;
        $count = 0;
        $enumlib = new EnumLib();
        $formdef = simplexml_load_string($enumlib->getExamInstanceQuestionsByID($id));
        $scaleXML = simplexml_load_string($enumlib->getCriteriaScaleItems($formdef->overview->data->instance->scale_id));
        $htmlStr = "<html><head>" . $CFG->printableformCSS .
                "</head><br/>
    <h3 style='text-align:left'>{$formdef->overview->data->instance->name}</h3><p/>
            <div style=''>
                <div style='width:100%; position:relative; float:left;'>
                    <div style='width:170px; position:relative; float:left; font-weight:bold'>Student Name:</div>
                    <div style='width:170px; position:relative; float:left; border-bottom: solid 1px #000'>&nbsp;</div>
                </div>
                 <div style='width:100%; position:relative; float:left; '>
                    <div style='width:170px; position:relative; float:left; font-weight:bold'>Student Number:</div>
                    <div style='width:170px; position:relative; float:left; border-bottom: solid 1px #000'>&nbsp;</div>
                </div> 
                  <div style='width:100%; position:relative; float:left;'>
                    <div style='width:170px; position:relative; float:left; font-weight:bold'>Campus:</div>
                    <!-- <div style='width:300px; position:relative; float:left;'><span style='font-size:1.5em'>&#x274f;</span>Armidale <span style='font-size:1.5em;'>&#x274f;</span>Newcastle</div> -->
                </div> 
            </div><br/>";



        $htmlStr.="<div style='position:relative; float:left; '>
                <div style='width:250px; position:relative; float:left' class='header'>Assessment Criteria</div>
                <div class='header' style='width:400px; position:relative; float:left; border-bottom: solid 1px #000;'>Quality of Performance</div>
             </div>";


        $htmlStr.="<div style='width:100%; position:relative; float:left; '>
                <div style='width:250px; position:relative; float:left' class='header'>&nbsp;</div>";

        $cellwidth = 0;


        foreach ($scaleXML->item as $item) {
            $htmlStr.="<div class='header' style='width:50px;  position:relative; float:left'>{$item->short_description}</div>";
            $cellwidth+=51;
        }

        $htmlStr.="<div class='header' style='width:" . (400 - $cellwidth) . "px;  position:relative; float:left'>Comments</div>
               </div>";
        foreach ($formdef->questiondata->question as $question) {
            if ((($count % 14) != 0) || ($count == 0)) {
                switch ($question->type) {
                    case '0':
                        //  $htmlStr.="<tr><th class='form' colspan='4'>{$question->text}</th></tr>";
                        $count++;
                        $htmlStr.="<div style='width:100%;  position:relative; float:left; padding:0px; height:30px'>
                <div class='formcell' style='width:250px; position:relative; float:left' >{$count})<span style='font-size:" . (1 - (0.1 * (strlen($question->text) / 30))) . "em'> {$question->text}</span></div>";
                        $cellwidth = 0;
                        foreach ($scaleXML->item as $item) {
                            $htmlStr.="<div class='formcell' style='width:50px; text-align:center; position:relative; float:left; font-size:1.5em'>&#x274f;</div>";
                            $cellwidth+=51;
                        }

                        //$htmlStr.="<div class='formcell' style='width:50px; text-align:center; position:relative; float:left; font-size:1.5em'>&#x274f;</div>
                        $htmlStr.="<div class='formcell' style='width:" . (400 - $cellwidth) . "px;  position:relative; float:left'>&nbsp;</div>
               </div>";
                        break;
                    case '1':
                        $count++;
                        $htmlStr.="<div style='width:100%;  position:relative; float:left; padding:0px; height:30px'>
                <div class='formcell' style='width:250px; position:relative; float:left' >{$count})<span style='font-size:" . (1 - (0.1 * (strlen($question->text) / 30))) . "em'> {$question->text}</span></div>";

                        $cellwidth = 0;
                        foreach ($scaleXML->item as $item) {
                            $htmlStr.="<div class='formcell' style='width:50px; text-align:center; position:relative; float:left; font-size:1.5em'>&#x274f;</div>";
                            $cellwidth+=51;
                        }

                        //$htmlStr.="<div class='formcell' style='width:50px; text-align:center; position:relative; float:left; font-size:1.5em'>&#x274f;</div>
                        $htmlStr.="<div class='formcell' style='width:" . (400 - $cellwidth) . "px;  position:relative; float:left'>&nbsp;</div>
               </div>";
                        //$htmlStr.="<tr><td class='form' style=''><span>{$count}) {$question->text}</span></td class='form'><td class='form' style='font-size:1.5em;'>&#x274f;</td class='form'><td class='form' style='font-size:1.5em;'>&#x274f;</td><td class='form'>&nbsp;</td></tr>";
                        //  $htmlStr.="<div><div style='width:200px; position:relative; float:left'>".$count.") ".$question->text."</div><div style='width:50px; position:relative; float:left; font-size:1.5em'>&#x274f;</div><div style='width:50px; font-size:1.5em; position:relative; float:left'>&#x274f;</div><div style='width:398px; position:relative; float:left'>Comments</div></div>";
                        break;
                    default:
                        break;
                }
            } else {

                $count++;
                $htmlStr.="<span style='font-weight:bold; font-size:0.8em'>Legend: </span>";
                foreach ($scaleXML->item as $item) {
                    $htmlStr.= "<span style='font-weight:bold; font-size:0.8em'>{$item->short_description}:</span><span style='font-size:0.8em'>{$item->long_description}; </span>";
                }
                $htmlStr.="<pagebreak /><br/>";
                $htmlStr.="<div style='position:relative; float:left; '>
                <div style='width:250px; position:relative; float:left' class='header'>Assessment Criteria</div>
                <div class='header' style='width:400px; position:relative; float:left; border-bottom: solid 1px #000;'>Quality of Performance</div>
             </div>";


                $htmlStr.="<div style='width:100%; position:relative; float:left; '>
                <div style='width:250px; position:relative; float:left' class='header'>&nbsp;</div>";

                $cellwidth = 0;


                foreach ($scaleXML->item as $item) {
                    $htmlStr.="<div class='header' style='width:50px;  position:relative; float:left'>{$item->short_description}</div>";
                    $cellwidth+=51;
                }

                $htmlStr.="<div class='header' style='width:" . (400 - $cellwidth) . "px;  position:relative; float:left'>Comments</div>
               </div>";

                switch ($question->type) {
                    case '0':
                        //  $htmlStr.="<tr><th class='form' colspan='4'>{$question->text}</th></tr>";
                        $count++;
                        $htmlStr.="<div style='width:100%;  position:relative; float:left; padding:0px; height:30px'>
                <div class='formcell' style='width:250px; position:relative; float:left' >{$count})<span style='font-size:" . (1 - (0.1 * (strlen($question->text) / 30))) . "em'> {$question->text}</span></div>";

                        $cellwidth = 0;
                        foreach ($scaleXML->item as $item) {
                            $htmlStr.="<div class='formcell' style='width:50px; text-align:center; position:relative; float:left; font-size:1.5em'>&#x274f;</div>";
                            $cellwidth+=51;
                        }

                        //$htmlStr.="<div class='formcell' style='width:50px; text-align:center; position:relative; float:left; font-size:1.5em'>&#x274f;</div>
                        $htmlStr.="<div class='formcell' style='width:" . (400 - $cellwidth) . "px;  position:relative; float:left'>&nbsp;</div>
               </div>";
                        break;
                    case '1':
                        $count++;
                        $htmlStr.="<div style='width:100%;  position:relative; float:left; padding:0px; height:30px'>
                <div class='formcell' style='width:250px; position:relative; float:left' >{$count})<span style='font-size:" . (1 - (0.1 * (strlen($question->text) / 30))) . "em'> {$question->text}</span></div>";

                        $cellwidth = 0;
                        foreach ($scaleXML->item as $item) {
                            $htmlStr.="<div class='formcell' style='width:50px; text-align:center; position:relative; float:left; font-size:1.5em'>&#x274f;</div>";
                            $cellwidth+=51;
                        }

                        //$htmlStr.="<div class='formcell' style='width:50px; text-align:center; position:relative; float:left; font-size:1.5em'>&#x274f;</div>
                        $htmlStr.="<div class='formcell' style='width:" . (400 - $cellwidth) . "px;  position:relative; float:left'>&nbsp;</div>
               </div>";
                        //$htmlStr.="<tr><td class='form' style=''><span>{$count}) {$question->text}</span></td class='form'><td class='form' style='font-size:1.5em;'>&#x274f;</td class='form'><td class='form' style='font-size:1.5em;'>&#x274f;</td><td class='form'>&nbsp;</td></tr>";
                        //  $htmlStr.="<div><div style='width:200px; position:relative; float:left'>".$count.") ".$question->text."</div><div style='width:50px; position:relative; float:left; font-size:1.5em'>&#x274f;</div><div style='width:50px; font-size:1.5em; position:relative; float:left'>&#x274f;</div><div style='width:398px; position:relative; float:left'>Comments</div></div>";
                        break;
                    default:
                        break;
                }
            }
        }

        $htmlStr.="<span style='font-weight:bold; font-size:0.8em'>Legend: </span>";
        foreach ($scaleXML->item as $item) {
            $htmlStr.= "<span style='font-weight:bold; font-size:0.8em'>{$item->short_description}:</span><span style='font-size:0.8em'>{$item->long_description} </span>";
        }

        $htmlStr.='<br/><br/>'; //<i>Insert text from Amanda here</i><br/>';
        $htmlStr.="<strong>Overall Global rating</strong><p/><table style='width:100%'><tr><th class='header' style='width:50%;'>Satisfactory <span style='font-size:1.5em;'>&#x274f;</span></th><th class='header' style='width:50%;'>Not Satisfactory <span style='font-size:1.5em;'>&#x274f;</span></th></tr></table>";
        $htmlStr.="<br/><strong>Additional rating if Satisfactory</strong><table style='width:100%'><tr><th class='header' style='width:33%;'>Excellent <span style='font-size:1.5em;'>&#x274f;</span></th><th class='header' style='width:33%;'>Expected Standard <span style='font-size:1.5em;'>&#x274f;</span></th><th class='header' style='width:33%;'>Marginal Pass <span style='font-size:1.5em;'>&#x274f;</span></th></tr></table><br/>";
        $htmlStr.="<div style='width:99%; height:50px;'><div style='width:200px; position:relative; float:left'><strong>Overall Comments:</strong></div><div style='position:relative; float:left'><div style='width:450px; height:24px; border-bottom: solid 1px #000;'>&nbsp;</div><div style='width:450px; height:24px; border-bottom: solid 1px #000;'>&nbsp;</div></div></div><br/>";
        $htmlStr.="<div style='width:99%'><div style='width:120px; position:relative; float:left'><strong>Mark:</strong></div><div style='width:170px; position:relative; float:left; border-bottom: solid 1px #000;text-align:right'>&nbsp;</div></div>";
        $htmlStr.="<div style='width:99%'><div style='width:120px; position:relative; float:left'><strong>Examiner Name</strong></div><div style='width:170px; position:relative; float:left; border-bottom: solid 1px #000;'>&nbsp;</div></div>
               <div style='width:99%'><div style='position:relative; float:left; width:120px; '><strong>Signature</strong></div><div style='width:200px; position:relative; float:left; border-bottom: solid 1px #000;'>&nbsp;</div><div style='width:120px; position:relative; float:left; text-align:right'><strong>Date:</strong></div><div style='width:170px; position:relative; float:left; border-bottom: solid 1px #000;'>&nbsp;</div></div>";


        return $htmlStr . '</html>';
    }

    /**
     * get a printer-friendly form for backup of assessment if the technology fails as a PDF. Outputs to the browser window directly
     * @global type $CFG
     * @param type $id the ID of the assessment form
     * 
     */
    public function get_printable_assessment_form_as_pdf($id) {
        global $CFG;
        // get the form title
        $mpdf = new mPDF();
        //$mpdf->shrink_tables_to_fit = 0;
        // $mpdf->SetHTMLHeaderByName('MyHeader1');
        $output = $this->get_printable_assessment_form($id);
        //$mpdf->SetHTMLFooter("<p style='font-size: 0.8em; font-style:italic'>Note: for each component the student may be Satisfactory or Non-satisfactory. If they are non-satisfactory for any element of the examination please make some additional comments</p><p style='font-size: 0.5em; font-style:italic; text-align:right'>Printed " . date("j F, Y, g:i a") . "</p>", 'O');
        //  $mpdf->SetHTMLHeader("<table style='width:100%'><tr><td style='width:600px'>JOINT MEDICAL PROGRAMME<br/>BACHELOR OF MEDICINE</td><td style='font-align:right; width:90px'><img width='90px'; src='../icons/osce_header.jpg'/></td></tr></table>", 'O');
        //UNE Health version
        $mpdf->SetHTMLHeader("<table style='width:100%'><tr><td style='width:600px'>UNIVERSITY OF NEW ENGLAND<br/></td><td style='font-align:right; width:90px'><img width='90px'; src='../icons/une_header_logo.jpg'/></td></tr></table>", 'O');
        $mpdf->WriteHTML($output);
        $mpdf->Output();
    }

    /* get a printer-friendly form for backup of assessment if the technology fails as a Word document. Outputs to the browser window directly
     * @global type $CFG
     * @param type $id the ID of the assessment form
     * 
     */

    public function get_printable_assessment_form_as_word($id, $PHPWord) {
        global $CFG;

        $count = 0;
        $enumlib = new EnumLib();
        $formdef = simplexml_load_string($enumlib->getExamInstanceQuestionsByID($id));
        $scaleXML = simplexml_load_string($enumlib->getCriteriaScaleItems($formdef->overview->data->instance->scale_id));
        // Create a new PHPWord Object
// Every element you want to append to the word document is placed in a section. So you need a section:
        $section = $PHPWord->createSection();
// add the title
        $section->addText($formdef->overview->data->instance->name, array('name' => 'Tahoma', 'size' => 14, 'bold' => true));
        $section->addText("Student Name:	____________________", array('name' => 'Tahoma', 'size' => 12, 'bold' => true));
        $section->addText("Student Number:	____________________", array('name' => 'Tahoma', 'size' => 12, 'bold' => true));

// add the table for the asessment
        $tableStyle = array(
            'border' => 'single',
            'borderColor' => '000000',
            'borderSize' => 6,
        );

        $headerCellStyle = array(
            'bgColor' => 'D9D9D9',
            'border' => 'single',
            'borderColor' => '000000',
            'borderSize' => 6
        );
        $headerSpanCellStyle = array(
            'bgColor' => 'D9D9D9',
            'border' => 'single',
            'borderColor' => '000000',
            'borderSize' => 6,
            'gridSpan' => count($scaleXML->children())+1,
        );

        $bodyCellStyle = array(
            'border' => 'single',
            'borderColor' => '000000',
            'borderSize' => 6
        );

        $table = $section->addTable($tableStyle);
        $table->addRow();
        $table->addCell(3750, $headerCellStyle)->addText('Assessment Criteria', array('name' => 'Tahoma', 'size' => 12));

        $cell = $table->addCell(6000, $headerSpanCellStyle)->addText('Quality of Performance', array('name' => 'Tahoma', 'size' => 12));

        $table->addRow();
        $table->addCell(3750, $headerCellStyle);

        $cellwidth = 0;

        foreach ($scaleXML->item as $item) {
            $table->addCell(750, $headerCellStyle)->addText($item->short_description, array('name' => 'Tahoma', 'size' => 12));
            $cellwidth +=750;
        }



        $table->addCell(6000-$cellwidth, $headerCellStyle)->addText('Comments', array('name' => 'Tahoma', 'size' => 12));

        foreach ($formdef->questiondata->question as $question) {
            //   if ((($count % 15) != 0) || ($count == 0)) {
            switch ($question->type) {
                case '0':
                    //  $htmlStr.="<tr><th class='form' colspan='4'>{$question->text}</th></tr>";
                    $count++;
                    $table->addRow();

                    $textrun = $table->addCell(3750, $bodyCellStyle)->addTextRun();
                    $textrun->addText("{$count}) ", array('name' => 'Tahoma', 'size' => 12));
                    $textrun->addText($question->text, array('name' => 'Tahoma', 'size' => (12 - (1 * (strlen($question->text) / 25)))));
                     $cellwidth = 0;

        foreach ($scaleXML->item as $item) {
           $textrun = $table->addCell(750, $bodyCellStyle)->addText("□", array('name' => 'Tahoma', 'size' => 20, 'bold' => true));
            $cellwidth +=750;
        }
                   // $textrun = $table->addCell(750, $bodyCellStyle)->addText("□", array('name' => 'Tahoma', 'size' => 20, 'bold' => true));
                   // $table->addCell(750, $bodyCellStyle)->addText('□', array('name' => 'Tahoma', 'size' => 20, 'bold' => true));
                 //   $table->addCell(6000-$cellwidth, $headerCellStyle)->addText('Comments', array('name' => 'Tahoma', 'size' => 12));
                    $table->addCell(6000-$cellwidth, $bodyCellStyle);

                    break;
                case '1':
                    $count++;
                    $table->addRow();
                    $textrun = $table->addCell(3750, $bodyCellStyle)->addTextRun();
                    $textrun->addText("{$count}) ", array('name' => 'Tahoma', 'size' => 12));
                    $textrun->addText($question->text, array('name' => 'Tahoma', 'size' => (12 - (1 * (strlen($question->text) / 25)))));
                    $textrun = $table->addCell(750, $bodyCellStyle)->addText("□", array('name' => 'Tahoma', 'size' => 20, 'bold' => true));
                    $table->addCell(750, $bodyCellStyle)->addText('□', array('name' => 'Tahoma', 'size' => 20, 'bold' => true));
                    $table->addCell(4500, $bodyCellStyle);
                    break;
                default:
                    break;
            }
        }

        $section->addTextBreak(1);
        $section->addText("Overall Global rating", array('name' => 'Tahoma', 'size' => 12));
        $table = $section->addTable($tableStyle);
        $table->addRow();

        $textrun = $table->addCell(4875, $headerCellStyle)->addTextRun();
        $textrun->addText('Satisfactory');
        $textrun->addText("□", array('name' => 'Tahoma', 'size' => 20, 'bold' => true));
        $textrun = $table->addCell(4875, $headerCellStyle)->addTextRun();
        $textrun->addText('Not Satisfactory');
        $textrun->addText("□", array('name' => 'Tahoma', 'size' => 20, 'bold' => true));

        $section->addTextBreak(1);
        $section->addText("Additional Rating if Satisfactory", array('name' => 'Tahoma', 'size' => 12));
        $table = $section->addTable($tableStyle);
        $table->addRow();

        $textrun = $table->addCell(3250, $headerCellStyle)->addTextRun();
        $textrun->addText('Excellent');
        $textrun->addText("□", array('name' => 'Tahoma', 'size' => 20, 'bold' => true));
        $textrun = $table->addCell(3250, $headerCellStyle)->addTextRun();
        $textrun->addText('Expected Standard');
        $textrun->addText("□", array('name' => 'Tahoma', 'size' => 20, 'bold' => true));
        $textrun = $table->addCell(3250, $headerCellStyle)->addTextRun();
        $textrun->addText('Marginal Pass');
        $textrun->addText("□", array('name' => 'Tahoma', 'size' => 20, 'bold' => true));

        $section->addTextBreak(1);
        $section->addText("Overall Comments", array('name' => 'Tahoma', 'size' => 12));
        $section->addText("____________________________________________________________________", array('name' => 'Tahoma', 'size' => 12));
        $section->addText("____________________________________________________________________", array('name' => 'Tahoma', 'size' => 12));

        $section->addTextBreak(1);
        $section->addText("Mark:			____________________", array('name' => 'Tahoma', 'size' => 12));
        $section->addText("Examiner Name:	____________________", array('name' => 'Tahoma', 'size' => 12));
        $section->addText("Signature:		____________________    Date:_____________________", array('name' => 'Tahoma', 'size' => 12));


        // headers and footers
        $header = $section->addHeader();
        $header->addText('UNCONTROLLED DRAFT ONLY- NOT FOR USE IN EXAMINATION');
        $header = $section->addFooter();
        $header->addText('Generated:' . date("j F, Y, g:i a"));
        return $PHPWord;
    }

    ////////////////////////////////////////////////////////////////////////
    //
    // Criteria schemes management
    //
    /////////////////////////////////////////////////////////////////////////

    /**
     * Creates a criteria scale
     * @global type $CFG
     * @param type $description
     * @param type $notes
     * @return type
     */
    public function addCriteriaScale($description, $notes) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "INSERT INTO `assessment_criteria_scale_types` (`description`, `notes`) 
            VALUES(:description, :notes);";

        $stmt = $conn->prepare($query);

        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);

        $stmt->execute() or die('<data><error>addCriteriaScale query failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>');
        //$result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');

        if ($conn->lastInsertId() > 0) {
            $returnStr = $conn->lastInsertId();
        } else {

            return '<data><error>operation failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>';
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * Updates a criteria scale
     * @global type $CFG
     * @param type $id
     * @param type $description
     * @param type $notes
     * @return type
     */
    public function updateCriteriaScale($id, $description, $notes) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE assessment_criteria_scale_types SET description = :description, notes = :notes WHERE ID = :id;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
        $result = $stmt->execute() or die('<data><error>updateCriteriaScale query failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>');

        if ($result) {
            $returnStr = $stmt->rowCount();
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * Marks a criteria scale as deleted
     * @global type $CFG
     * @param type $id
     * @return type
     */
    public function deleteCriteriaScale($id) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE assessment_criteria_scale_types SET deleted = 'true' WHERE ID = :id;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>deleteCriteriaScale query failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>');

        if ($result) {
            $returnStr = $stmt->rowCount();
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * Adds an item to a criteria scale
     * @global type $CFG
     * @param type $id
     * @param type $shortdescription
     * @param type $longdescription
     * @param type $value
     * @return type
     */
    public function addCriteriaScaleItem($id, $shortdescription, $longdescription, $value, $needscomment) {

        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $maxquery = "SELECT IFNULL(MAX(`order`)+1,0) as nextorder FROM assessment_criteria_scales_items WHERE assessment_criteria_scale_typeID = :id AND IFNULL(deleted, '') <> 'true'";
        $stmt = $conn->prepare($maxquery);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>addCriteriaScaleItem query 1 failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>');
        //  print($maxquery);
        $nextorder = 0;
        //$result = mysqli_query($conn, $maxquery) or die('<data><error>SELECT query failed</error><detail>' . mysqli_error($conn) . $maxquery . '</detail></data>');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($result)) {
            $nextorder = $result['nextorder'];
        }
        $stmt->closeCursor();
//print("nextorder is:$nextorder");
        $query = "INSERT INTO `assessment_criteria_scales_items` (`assessment_criteria_scale_typeID`, `long_description`, `short_description`, `value`, `needs_comment`, `order`) 
            VALUES(:id, :longdescription, :shortdescription, :value, :needscomment, $nextorder);";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':longdescription', $longdescription, PDO::PARAM_STR);
        $stmt->bindValue(':shortdescription', $shortdescription, PDO::PARAM_STR);
        $stmt->bindValue(':value', $value, PDO::PARAM_STR);
        $stmt->bindValue(':needscomment', $needscomment, PDO::PARAM_STR);
        $stmt->execute() or die('<data><error>addCriteriaTypeItem query failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>');
        //$result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');

        if ($conn->lastInsertId() > 0) {
            $returnStr = $conn->lastInsertId();
        } else {

            return '<data><error>operation failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>';
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * 
     * @global type $CFG
     * @param type $id
     * @param type $shortdescription
     * @param type $longdescription
     * @param type $value
     * @return type
     */
    public function updateCriteriaScaleItem($id, $shortdescription, $longdescription, $value, $needscomment) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE assessment_criteria_scales_items SET long_description = :longdescription, short_description = :shortdescription, value = :value, needs_comment = :needscomment WHERE ID = :id;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':longdescription', $longdescription, PDO::PARAM_STR);
        $stmt->bindValue(':shortdescription', $shortdescription, PDO::PARAM_STR);
        $stmt->bindValue(':value', $value, PDO::PARAM_STR);
        $stmt->bindValue(':needscomment', $needscomment, PDO::PARAM_STR);
        $result = $stmt->execute() or die('<data><error>updateCriteriaScaleItem query failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>');

        if ($result) {
            $returnStr = $stmt->rowCount();
        }
        return "<data><id>$returnStr</id></data>";
    }

    public function reorderCriteriaScaleItems($orderdefXML) {
        global $CFG;

        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $success_status = 0;
        $orderXML = simplexml_load_string($orderdefXML);
        $conn->beginTransaction();
        foreach ($orderXML->def as $def) {
            $query = "UPDATE assessment_criteria_scales_items SET `order` = :neworder  WHERE ID = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':neworder', $def->order, PDO::PARAM_INT);
            $stmt->bindValue(':id', $def->id, PDO::PARAM_INT);
            // print($query);
            //$result = mysqli_query($conn, $query) or die('<data><error>order set operation failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            if (!$stmt->execute()) {
                $success_status++;
            }
        }
        if ($success_status > 0) {
            $conn->rollBack();
            return '<data><error>order set operation failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>';
        } else {
            $conn->commit();
            return "<data><status>$success_status</status></data>";
        }
    }

    public function deleteCriteriaScaleItem($id) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE assessment_criteria_scales_items SET deleted = 'true' WHERE ID = :id;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>deleteCriteriaItem query failed</error><detail><![CDATA[' . var_dump($stmt->errorInfo()) . ']]></detail></data>');

        if ($result) {
            $returnStr = $stmt->rowCount();
        }
        return "<data><id>$returnStr</id></data>";
    }

////////////////////////////////////////////////////////////////////////
//
    //REFERENCE TO MARKING SHEETS- DEPRECATED
//We used to refer to exam instances as 'marking sheets' but the terminology has changed.
//
    /////////////////////////////////////////////////////////////////////////

    /**
     * DEPRECATED
     * Make a new instruction sheet
     * @global type $CFG
     * @param type $description
     * @param type $instructions_url 
     * @return an XML string containing the ID of the newly crearted marking sheet
     */
    public function new_marking_sheet($description, $userID) {
        global $CFG;

        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "INSERT INTO {$CFG->schema}.marking_sheets (description, instructions_url, created_timestamp, created_byID) VALUES('$description', " . time() . ", $userID);";
        $result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        if ($result) {
            $returnStr = mysqli_insert_id($conn);
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * * DEPRECATED
     * Updates a marking sheet with new values
     * @global type $CFG
     * @param type $description
     * @param type $instructions_url
     * @param type $userID
     * @return type 
     */
    public function update_marking_sheet($id, $description) {
        global $CFG;

        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "UPDATE {$CFG->schema}.marking_sheets SET description = '$description', modified_timestamp =  " . time() . " WHERE ID = $id";
        $result = mysqli_query($query, $conn) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        if ($result) {
            $returnStr = mysqli_affected_rows($conn);
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * * DEPRECATED
     * Deletes a marking sheet and all questions associated with it
     * @global type $CFG
     * @param type $sheetID
     * @return type 
     * TODO make into a transaction
     */
    public function delete_marking_sheet($sheetID) {
        global $CFG;

        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "DELETE FROM {$CFG->schema}.marking_sheets WHERE ID = $sheetID";
        $result = mysqli_query($query, $conn) or die('<data><error>delete query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        if (mysqli_affected_rows($conn) > 0) {
            // delete student image
            $query = $query = "DELETE FROM {$CFG->schema}.form_questions WHERE form_ID = $sheetID";
            $result = mysqli_query($query, $conn) or die('<data><error>delete form questions image query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            if (mysqli_affected_rows($conn) > 0) {
                $returnStr = 'true';
            }
        } else {
            $returnStr = 'false';
        }
        return "<data><status>$returnStr</status></data>";
    }

////////////////////////////////////////////////////////////////////////
//
    //Criteria for competencies- DEPRECATED
//
    /////////////////////////////////////////////////////////////////////////

    /**
     * DEPRECATED
     * @global type $CFG
     * @param type $formID
     * @param type $text
     * @param type $type
     * @param type $notes
     * @param type $userID
     * @return type 
     */
    public function new_criteria($questionID, $text, $required, $userID) {
        global $CFG;

        $nextorder = '0';

        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        // get the next available order
        $maxquery = "SELECT MAX(`order`)+1 as nextorder FROM questions_criteria WHERE questions_criteria.questions_ID = $questionID";

        //  print($maxquery);
        $result = mysqli_query($conn, $maxquery) or die('<data><error>SELECT query failed</error><detail>' . mysqli_error($conn) . $maxquery . '</detail></data>');
        if ($result) {
            while ($row = mysqli_fetch_array($result)) {
                if (isset($row['nextorder'])) {
                    $nextorder = $row['nextorder'];
                }
            }
        }

        $query = "INSERT INTO questions_criteria (questions_ID, text, required, created_timestamp, created_byID, `order`) VALUES($questionID, '$text', '$required',  " . time() . ", $userID, $nextorder);";
        $result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        if ($result) {
            $returnStr = mysqli_insert_id($conn);
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * DEPRECATED
     * @global type $CFG
     * @param type $id
     * @param type $text
     * @param type $type
     * @param type $notes
     * @return type 
     */
    public function update_criteria($id, $text, $required, $userID) {
        global $CFG;

        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "UPDATE questions_criteria SET text = '$text', required = '$required', modified_timestamp = " . time() . ", modified_byID = $userID WHERE ID = $id;";
        $result = mysqli_query($conn, $query) or die('<data><error>update query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        if ($result) {
            $returnStr = mysqli_affected_rows($conn);
        }
        return "<data><id>$returnStr</id></data>";
    }

    /**
     * DEPRECATED
     * @global type $CFG
     * @param type $questionID
     * @return type 
     */
    public function delete_criteria($criteriaID) {
        global $CFG;

        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "DELETE FROM questions_criteria WHERE ID = $criteriaID";
        $result = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        if ($result) {
            $returnStr = mysqli_affected_rows($conn);
        }
        return "<data><id>$returnStr</id></data>";
    }

////////////////////////////////////////////////////////////////////////
//
    //Other random things...
//
    /////////////////////////////////////////////////////////////////////////
    /**
     * DEPRECATED (?)
     * @global type $CFG
     * @param type $orderdefXML
     * @return type
     */
    public function reorder_assessment_items($orderdefXML) {
        global $CFG;
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $success_status = 0;
        // loop and order
        // Start the transaction
        mysqli_query($conn, "SET AUTOCOMMIT=0");
        mysqli_query($conn, "START TRANSACTION");
        $orderXML = simplexml_load_string($orderdefXML);
        foreach ($orderXML->def as $def) {
            $query = "UPDATE assessment_items SET assessment_items.order = $def->order WHERE ID = $def->id";
            $result = mysqli_query($conn, $query) or die('<data><error>order set operation failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            if (!$result) {
                $success_status++;
            }
        }
        if ($success_status > 0) {
            mysqli_query("ROLLBACK");
            return '<data><error>order set operation failed</error><detail>' . mysqli_error($conn) . '</detail></data>';
        } else {
            mysqli_query($conn, "COMMIT");
            return "<data><status>$success_status</status></data>";
        }
    }

    /**
     * DEPRECATED- this seemed like a good idea at the time, but we're now doing it using CSV lists
     *  Associates students with an exam instance ID by cohort
     * @global type $CFG
     * @param type $id
     * @param type $studentID The ID of the student to associate
     * @return XML-formatted string containing details of the student, or an error if the insert query fails
     */
    public function associateStudentsWithInstanceByCohort($id, $cohortID) {

        global $CFG;
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');

        // get all students belonging to a cohort
        $query = "SELECT ID FROM students WHERE cohort = $cohortID;";


        $result = mysqli_query($conn, $query) or die('<data><error>lookup query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');

        while ($row = mysqli_fetch_array($result)) {
            // check to see we're not doubling up
            $checkQuery = "SELECT * FROM student_exam_instance_link WHERE students_ID = {$row['ID']} AND exam_instances_ID = $id";

            $checkresult = mysqli_query($conn, $checkQuery) or die('<data><error>check query failed</error><detail>' . mysqli_error($conn) . $checkQuery . '</detail></data>');
            //  print("result count:".mysqli_num_rows($checkresult));
            if (mysqli_num_rows($checkresult) == 0) {
                //   print('inserting...');
                $query = "INSERT INTO {$CFG->schema}.student_exam_instance_link (exam_instances_ID, students_ID) 
            VALUES($id, {$row['ID']});";

                $result2 = mysqli_query($conn, $query) or die('<data><error>insert query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            }
        }

        if (mysqli_insert_id($conn) > 0) {
            // return all just added
            $enumlib = new EnumLib();
            return $enumlib->getStudentsByCohortIDAssociatedWithInstance($id, $cohortID);
        } else {

            return '<data><error>associateStudentsWithInstanceByCohort operation failed</error><detail>' . mysqli_error($conn) . '</detail></data>';
        }
        // return "<data><id>$returnStr</id></data>";
    }

}

?>

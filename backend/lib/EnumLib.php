<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EnumLib
 * This library lists (enumerates) things
 * @author alandow
 */
//require_once('config.inc.php');

class EnumLib {

    /**
     * Gets a lookup of the scale types
     * @global type $CFG
     * @return an XML formatted string containing descriptors of the roles
     */
    public function getCriteriaTypesLookup() {
        global $CFG;
        $returnVal = '<data>';
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //if ($conn) {
        $query = "SELECT * FROM assessment_criteria_scale_types WHERE IFNULL(deleted, '') <> 'true'";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute() or die('<data><error>getCriteriaTypesLookup query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            //$result = mysqli_query($conn, $query) or die('<data><error>query failed</error><detail>' . mysqli_error($conn) . '</detail></data>');
            //while ($row = mysqli_fetch_array($result)) {
            $returnVal.="<item><id>{$row['ID']}</id><description><![CDATA[{$row['description']}]]></description><notes><![CDATA[{$row['notes']}]]></notes></item>";
        }
        //}
        $returnVal .= '</data>';
        return $returnVal;
    }

    /**
     * Gets an overview description of a given criteria scale defined by id
     * @global type $CFG
     * @param type $id
     * @return string
     */
    public function getCriteriaScaleOverview($id) {
        global $CFG;
        $returnVal = '<data>';
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //if ($conn) {
        $query = "SELECT * FROM assessment_criteria_scale_types WHERE ID = :id AND IFNULL(deleted, '') <> 'true'";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>getCriteriaScaleOverview query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<item><id>{$row['ID']}</id><description><![CDATA[{$row['description']}]]></description><notes><![CDATA[{$row['notes']}]]></notes></item>";
        }
        $returnVal .= '</data>';
        return $returnVal;
    }

    /**
     * Gets the scale items for a given criteria scale defined by id
     * @global type $CFG
     * @param type $id
     * @return string
     */
    public function getCriteriaScaleItems($id) {
        global $CFG;
        $returnVal = '<data>';
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //if ($conn) {
        $query = "SELECT * FROM assessment_criteria_scales_items WHERE assessment_criteria_scale_typeID = :id AND IFNULL(deleted, '') <> 'true' ORDER BY `order` ASC";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>getCriteriaScaleItems query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<item><id>{$row['ID']}</id><long_description><![CDATA[{$row['long_description']}]]></long_description><short_description><![CDATA[{$row['short_description']}]]></short_description><needs_comment>{$row['needs_comment']}</needs_comment><value>{$row['value']}</value></item>";
        }
        $returnVal .= '</data>';
        return $returnVal;
    }

    public function getCriteriaItemByID($id) {
        global $CFG;
        $returnVal = '<data>';
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //if ($conn) {
        $query = "SELECT * FROM assessment_criteria_scales_items WHERE ID = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>getCriteriaItemByID query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<item><id>{$row['ID']}</id><long_description><![CDATA[{$row['long_description']}]]></long_description><short_description><![CDATA[{$row['short_description']}]]></short_description><needs_comment>{$row['needs_comment']}</needs_comment><value>{$row['value']}</value></item>";
        }
        $returnVal .= '</data>';
        return $returnVal;
    }

    /**
     * Gets a nicely HTML formatted description of the available scales
     */
    public function getCriteriaScalesDescription() {
        global $CFG;
        $returnVal = '<strong>Available scales</strong><br/><ul>';
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //if ($conn) {
        $query = "SELECT * FROM assessment_criteria_scale_types WHERE IFNULL(deleted, '') <> 'true'";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute() or die('<data><error>getCriteriaScalesDescription query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            //$result = mysqli_query($conn, $query) or die('<data><error>query failed</error><detail>' . mysqli_error($conn) . '</detail></data>');
            //while ($row = mysqli_fetch_array($result)) {
            $returnVal.="<li><strong>{$row['description']}</strong>:{$row['notes']}</li>";
        }
        //}
        $returnVal .= '</ul>';
        return $returnVal;
    }

    /**
     * Gets a lookup of the roles
     * @global type $CFG
     * @return an XML formatted string containing descriptors of the roles
     */
    public function getRolesLookup() {
        global $CFG;
        $returnVal = '<data>';
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //if ($conn) {
        $query = "SELECT * FROM roles";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute() or die('<data><error>getRolesLookup query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            //$result = mysqli_query($conn, $query) or die('<data><error>query failed</error><detail>' . mysqli_error($conn) . '</detail></data>');
            //while ($row = mysqli_fetch_array($result)) {
            $returnVal.="<option><ID>{$row['ID']}</ID><description><![CDATA[{$row['description']}]]></description><notes><![CDATA[{$row['notes']}]]></notes></option>";
        }
        //}
        $returnVal .= '</data>';
        return $returnVal;
    }

    /**
     * Gets a lookup of the roles
     * @global type $CFG
     * @return an XML formatted string containing descriptors of the units
     */
    public function getUnitsLookup() {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        $query = "SELECT * FROM unit_lookup WHERE IFNULL(deleted, '') <> 'true' ";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute() or die('<data><error>getUnitsLookup query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<option><ID>{$row['ID']}</ID><description><![CDATA[{$row['description']}]]></description></option>";
        }

        $returnVal .= '</data>';
        return $returnVal;
    }

    /**
     * Gets a lookup of the roles
     * @global type $CFG
     * @return an XML formatted string containing descriptors of the units
     */
    public function getUnitDescriptionByID($id) {
        global $CFG;
        $returnVal = '<data><![CDATA[';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT description FROM unit_lookup WHERE ID = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>getUnitDescriptionByID query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="{$row['description']}";
        }

        $returnVal .= ']]></data>';
        return $returnVal;
    }

    /**
     * DEPRECATED
     * Gets a lookup of the cohorts
     * @global type $CFG
     * @return an XML formatted string containing descriptors of the cohorts
     */
    public function getCohortLookup() {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT * FROM cohort_lookup";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute() or die('<data><error>getCohortLookup query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<option><ID>{$row['ID']}</ID><description><![CDATA[{$row['label']}]]></description></option>";
        }

        $returnVal .= '</data>';
        return $returnVal;
    }

    /**
     * Gets a lookup of the sites
     * @global type $CFG
     * @return an XML formatted string containing descriptors of the cohorts
     */
    public function getSiteLookup() {
        global $CFG;
        $returnVal = '';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT * FROM site_lookup WHERE IFNULL(deleted, '') <> 'true'";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute() or die('<data><error>getSiteLookup query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<option value='{$row['ID']}'>{$row['description']}({$row['code']})</option>";
        }

        $returnVal .= '';
        return $returnVal;
    }

    /**
     * Gets a lookup of the sites
     * @global type $CFG
     * @return an XML formatted string containing descriptors of the cohorts
     */
    public function getSiteCodeLookup() {
        global $CFG;
        $returnVal = '';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT * FROM site_lookup WHERE IFNULL(deleted, '') <> 'true'";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute() or die('<data><error>getSiteCodeLookup query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<option value='{$row['ID']}'>{$row['code']}</option>";
        }

        // $returnVal .= '';
        // print($returnVal);
        return $returnVal;
    }

    /**
     * Gets site information by ID
     * @global type $CFG
     * @param type $searchstr a string to search for 
     * @return an XML formatted string containing descriptors of the cohorts
     */
    public function getSiteByID($id) {
        global $CFG;
        $returnVal = '<data>';
        if ($id > -1) {
            try {
                $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
            } catch (PDOException $e) {
                die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
            }
            $query = "SELECT * FROM site_lookup WHERE ID = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute() or die('<data><error>getSiteByID query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $returnVal.="<id>'{$row['ID']}'</id><description><![CDATA[{$row['description']}]]></description><code><![CDATA[{$row['code']}]]></code>";
            }
        } else {
            $returnVal.="<id>-1</id><description>No site specified</description><code>NULL</code>";
        }
        $returnVal .= '</data>';
        return $returnVal;
    }

    /**
     * Gets the ID of a site given it's short code
     * @global type $CFG
     * @param type $code a short code
     * @return int the ID of the short code, or the first one it finds if the code is invalid
     */
    public function getSiteIDByShortCode($code) {
        global $CFG;
        //  $returnVal = '<data>';
        $returnVal = -1;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT * FROM site_lookup WHERE code = :code LIMIT 0,1";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':code', $code, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>getSiteIDByShortCode query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal = $row['ID'];
        }
        $stmt->closeCursor();
        // if there's nothing, make a default
        if ($returnVal < 0) {
            $query = "SELECT * FROM site_lookup LIMIT 0,1";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute() or die('<data><error>getSiteIDByShortCode query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $returnVal = $row['ID'];
            }
        }
        return $returnVal;
    }

    /**
     * Gets a list of users by search string
     * @global type $CFG
     * @param type $searchstr a string to search for 
     * @return an XML formatted string containing data about the found users
     */
    public function getUsers($searchstr) {
        global $CFG;
        $returnVal = '<data>';
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT a.ID, a.name, a.username, a.type, b.ID as roleID, b.description FROM users a inner join roles b on a.roleID = b.ID WHERE IFNULL(a.deleted, '') <> 'true' AND (a.name LIKE :searchstr OR username LIKE :searchstr);";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
//        $stmt->bindValue(':searchstr2', '%' . $searchstr . '%', PDO::PARAM_STR);

        $result = $stmt->execute() or die('<data><error>getUsers query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<user><id>{$row['ID']}</id><type>{$row['type']}</type><name><![CDATA[{$row['name']}]]></name><username><![CDATA[{$row['username']}]]></username><role>{$row['description']}</role><roleID>{$row['roleID']}</roleID></user>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets info about a specific user  by ID
     * @global type $CFG
     * @param type $userID the ID of teh user to get info about
     * @return an XML formatted string containing data about the found user
     */
    public function getUserByID($userID) {
        global $CFG;

        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        //mysqli_select_db($CFG->schema, $conn);
        $query = "SELECT a.ID, a.name, a.username, a.type, b.ID as roleID, b.description FROM users a inner join roles b on a.roleID = b.ID WHERE a.ID = :userID;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>getUserByID query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<user><id>{$row['ID']}</id><type>{$row['type']}</type><name><![CDATA[{$row['name']}]]></name><username><![CDATA[{$row['username']}]]></username><role><![CDATA[{$row['description']}]]></role><roleID>{$row['roleID']}</roleID></user>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Lists some some students. Filter by searchstr
     * @global type $CFG
     * @param type $count the number of results to return
     * @param type $offset the offset: where to return 'from'
     * @param type $searchstr a search string to filter by. Filters fname or lname or student number
     * @return an XML formatted string containing data about the listed users
     */
    public function getStudents($count, $offset, $searchstr = null) {
        global $CFG;
        $returnVal = '<data>';
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $countquery = "SELECT COUNT(*) as count FROM students " . (isset($searchstr) ? "WHERE CONCAT (fname, ' ', lname) LIKE :searchstr OR studentnum LIKE :searchstr " : "") . "";
        $stmt = $conn->prepare($countquery);
        if (isset($searchstr)) {
            $stmt->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
        }
        $result = $stmt->execute() or die('<data><error>getStudents count query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<count>{$row['count']}</count>";
        }
        $stmt->closeCursor();
        //$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);

        $query = "SELECT a.ID, a.fname, a.lname, a.studentnum FROM students a " . (isset($searchstr) ? "WHERE CONCAT(a.fname, ' ', a.lname) LIKE :searchstr OR a.studentnum LIKE :searchstr" : "") . " ORDER BY a.lname DESC LIMIT $offset, $count";
        $stmt2 = $conn->prepare($query);
        //   print($query);
        if (isset($searchstr)) {
            $stmt2->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
        }
        //  $stmt2->bindValue(':offset', $offset, PDO::PARAM_INT);
        // $stmt2->bindValue(':count', $count, PDO::PARAM_INT);
        $result = $stmt2->execute() or die('<data><error>getStudents enum query failed</error><detail>' . $stmt2->errorCode() . '</detail></data>');
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<student><id>{$row['ID']}</id><fname><![CDATA[{$row['fname']}]]></fname><lname><![CDATA[{$row['lname']}]]></lname><studentnum><![CDATA[{$row['studentnum']}]]></studentnum></student>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets a listing of all students based on a search string
     * @global type $CFG
     * @param type $searchstr a search string to filter by. Filters fname or lname or student number
     * @return an XML formatted string containing data about the listed users
     */
    public function findStudents($searchstr, $limit = 20) {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT a.ID, a.fname, a.lname, a.studentnum FROM students a WHERE CONCAT (a.fname, ' ', a.lname) LIKE :searchstr OR a.studentnum LIKE :searchstr ORDER BY a.lname DESC LIMIT 0, $limit;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
        $result = $stmt->execute() or die('<data><error>findStudents query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<student><id>{$row['ID']}</id><fname><![CDATA[{$row['fname']}]]></fname><lname><![CDATA[{$row['lname']}]]></lname><studentnum><![CDATA[{$row['studentnum']}]]></studentnum></student>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Not really used. Gets a list of students by cohort
     * @global type $CFG
     * @param type $cohortid
     * @return an XML formatted string containing data about the listed users
     */
    public function findStudentsByCohort($cohortid) {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT ID, fname, lname, studentnum FROM students  WHERE cohort  = :cohortid ORDER BY lname DESC ;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':cohortid', $cohortid, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>findStudentsByCohort query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<student><id>{$row['ID']}</id><fname>{$row['fname']}</fname><lname>{$row['lname']}</lname><studentnum>{$row['studentnum']}</studentnum><email>{$row['email']}</email></student>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets ID's of teh students attached to a specific assessment that haven't completed the exam
     * @global type $CFG
     * @param type $searchstr
     * @param type $assessmentID
     * @return an XML formatted string containing the found student's IDs
     */
    public function findStudentsForForm($assessmentID, $searchstr, $site = -1, $limit = 20) {
        global $CFG;
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
// find completed students
        $completedstudents = array();
        $query = "SELECT student_id FROM student_exam_sessions WHERE form_id = :assessmentID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':assessmentID', $assessmentID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>check student query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $completedstudents[] = $row['student_id'];
        }
        $stmt->closeCursor();
        $returnVal = '<data>';

        //     mysqli_select_db($CFG->schema, $conn);

        $query = "SELECT a.ID, a.fname, a.lname, a.studentnum, b.site_ID,
            (SELECT COUNT(*) FROM student_images WHERE student_images.student_ID = a.ID) as hasimage
            FROM students a inner join student_exam_instance_link b on a.ID = b.students_ID 
            WHERE b.exam_instances_ID = :assessmentID AND (CONCAT (a.fname, ' ', a.lname) LIKE :searchstr OR a.studentnum LIKE :searchstr)" . ($site > -1 ? " AND b.site_ID = :site" : "") . " ORDER BY a.lname DESC ;";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':assessmentID', $assessmentID, PDO::PARAM_INT);
        $stmt->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
        if ($site > -1) {
            $stmt->bindValue(':site', $site, PDO::PARAM_INT);
        }
//print($query);
        $result = $stmt->execute() or die('<data><error>findStudentsForForm check student query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!(in_array($row['ID'], $completedstudents))) {
                $returnVal.="<student><id>{$row['ID']}</id><searchfield><![CDATA[{$row['fname']} {$row['lname']} {$row['studentnum']}]]></searchfield><fname><![CDATA[{$row['fname']}]]></fname><lname><![CDATA[{$row['lname']}]]></lname><siteid>{$row['site_ID']}</siteid><studentnum><![CDATA[{$row['studentnum']}]]></studentnum><hasimage>{$row['hasimage']}</hasimage></student>";
            }
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets ID's of the students attached to a specific assessment that *have* completed the exam
     * @global type $CFG
     * @param type $searchstr
     * @param type $assessmentID
     * @return an XML formatted string containing the found student's IDs
     */
    public function findCompletedStudentsForForm($assessmentID, $searchstr) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
// find completed students
        $completedstudents = array();
        $query = "SELECT student_id FROM student_exam_sessions WHERE form_id = :assessmentID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':assessmentID', $assessmentID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>check student query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $completedstudents[] = $row['student_id'];
        }
        $stmt->closeCursor();
        $returnVal = '<data>';

        //     mysqli_select_db($CFG->schema, $conn);

        $query = "SELECT a.ID, a.fname, a.lname, a.studentnum, b.site FROM students a inner join student_exam_instance_link b on a.ID = b.students_ID WHERE b.exam_instances_ID = :assessmentID AND (CONCAT(a.fname. ' ', a.lname) LIKE :searchstr OR a.studentnum LIKE searchstr) ORDER BY a.lname DESC ;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':assessmentID', $assessmentID, PDO::PARAM_INT);
        $stmt->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
        //print($query);
        $result = $stmt->execute() or die('<data><error>findCompletedStudentsForForm check student query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ((in_array($row['ID'], $completedstudents))) {
                $returnVal.="<student><id>{$row['ID']}</id><fname><![CDATA[{$row['fname']}]]></fname><lname><![CDATA[{$row['lname']}]]></lname><studentnum><![CDATA[{$row['studentnum']}]]></studentnum><siteid>{$row['site']}</siteid></student>";
            }
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets a specific student details by ID
     * @global type $CFG
     * @param type $studentID
     * @return an XML formatted string containing information about the found student
     */
    public function getStudentByID($studentID) {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT ID, fname, lname, studentnum, email, cohort FROM students WHERE ID = :studentID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>getStudentByID query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<student><id>{$row['ID']}</id><fname><![CDATA[{$row['fname']}]]></fname><lname><![CDATA[{$row['lname']}]]></lname><studentnum><![CDATA[{$row['studentnum']}]]></studentnum><cohort>{$row['cohort']}</cohort><email><![CDATA[{$row['email']}]]></email></student>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets locking information about a given student ID
     * @global type $CFG
     * @param type $studentID
     * @return an XML formatted string containing status information 
     */
    public function check_lock_student($studentID) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $returnStr = 'false';
        // Delete student entry
        $query = "SELECT locked FROM students WHERE ID = :studentID;";
        $stmt = $conn->prepare($query);
        //    $result = mysqli_query($conn, $query) or die('<data><error>check lock student query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>getStudentByID query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnStr = ($row['locked'] == '1' ? 'true' : 'false');
        }
        return "<data><status>$returnStr</status></data>";
    }

    /**
     * Gats a list of exam instances
     * TODO make it a bit searchable.
     * @global type $CFG
     * @param type $count the number of results to return
     * @param type $offset the offset: where to return 'from'
     * @return an XML formatted string containing information about the examination instances
     */
    public function getExamInstances($count, $offset, $archived = 'false') {
        global $CFG;
        $returnVal = '<data>';
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error_list($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //    mysqli_select_db($conn, $CFG->schema);
        $countquery = "SELECT COUNT(*) as count FROM exam_instances  WHERE deleted<>'true' AND archived " . ($archived == 'true' ? "=" : "<>") . " 'true'";
        $stmt = $conn->prepare($countquery);
        //print($countquery);
        $result = $stmt->execute() or die('<data><error>check exam_instances query failed</error><detail>' . var_dump($stmt->errorInfo()) . $countquery . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<count>{$row['count']}</count>";
        }
        $stmt->closeCursor();
        $query = "SELECT a.ID, a.name, a.description, 
            (select description from unit_lookup where unit_lookup.ID = a.unit_id) as unit,
            a.exam_starttimestamp,
            a.exam_endtimestamp, 
            a.created_timestamp,
            a.finalised,
            a.archived,
            a.active,
            a.practicing,
            (select name from users where users.ID = a.owner_id) as owner,
            (SELECT COUNT(*) FROM student_exam_instance_link WHERE exam_instances_ID = a.ID) as studentcount,
            (SELECT COUNT(*) FROM users_exam_instances_link WHERE exam_instances_ID = a.ID) as usercount,
            b.name as created_by FROM exam_instances a INNER JOIN users b ON a.created_by_id = b.ID WHERE a.deleted<>'true' AND a.archived " . ($archived == 'true' ? "=" : "<>") . " 'true' ORDER BY a.ID DESC LIMIT $offset, $count";

        // print($query);
        $stmt = $conn->prepare($query);
        $result = $stmt->execute() or die('<data><error>getStudentByID query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');
        //$result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<instance><id>{$row['ID']}</id>
                <name>{$row['name']}</name>
                <description><![CDATA[{$row['description']}]]></description>
                <unit><![CDATA[{$row['unit']}]]></unit>
                <owner><![CDATA[{$row['owner']}]]></owner>
                <finalised><![CDATA[{$row['finalised']}]]></finalised>
                    <active><![CDATA[{$row['active']}]]></active>
                        <practicing><![CDATA[{$row['practicing']}]]></practicing>
                <studentcount><![CDATA[{$row['studentcount']}]]></studentcount>
                <usercount><![CDATA[{$row['usercount']}]]></usercount>
                <exam_starttimestamp> " . (strlen($row['exam_starttimestamp']) > 0 ? date('d/m/Y', $row['exam_starttimestamp']) : "N/A") . "</exam_starttimestamp>
                <exam_endtimestamp> " . date('d/m/Y', $row['exam_endtimestamp']) . "</exam_endtimestamp>
                <created_date>" . date('d/m/Y', $row['created_timestamp']) . "</created_date>
                <created_by>{$row['created_by']}</created_by></instance>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets a listing of examinations associated with a given assessor
     * @global type $CFG
     * @param type $assessorID the ID of the assessor
     * @return an XML formatted string containing information about the associated examination instances
     */
    public function getExamsForAssessor($assessorID) {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error_list($conn) . '</detail></data>');
        $query = "SELECT a.ID, a.name, a.description, a.finalised, a.exam_starttimestamp, a.exam_endtimestamp FROM exam_instances a INNER JOIN 
            users_exam_instances_link b on a.ID = b.exam_instances_ID WHERE b.users_ID = :assessorID AND a.deleted <> 'true' AND a.active = 'true'"; // AND a.exam_starttimestamp < ".time()." AND a.exam_endtimestamp >".time();
        //   $result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':assessorID', $assessorID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>getExamsForAssessor query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<instance><id>{$row['ID']}</id>
                <name>{$row['name']}</name>
                <description><![CDATA[{$row['description']}]]></description></instance>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets a listing of examinations associated with a given assessor
     * @global type $CFG
     * @param type $assessorID the ID of the assessor
     * @return an XML formatted string containing information about the associated examination instances
     */
    public function getExamsForAssessorForApp($assessorID) {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT a.ID, a.name, a.description, a.scaleID, a.finalised, a.exam_starttimestamp, a.exam_endtimestamp, a.practicing FROM exam_instances a INNER JOIN 
            users_exam_instances_link b on a.ID = b.exam_instances_ID WHERE b.users_ID = :assessorID AND a.deleted <> 'true' AND (a.active = 'true' OR a.practicing = 'true')";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':assessorID', $assessorID, PDO::PARAM_INT);
        $result = $stmt->execute() or die('<data><error>getExamsForAssessor query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<instance><id>{$row['ID']}</id>
                <name><![CDATA[{$row['name']}]]></name>
                <practicing><![CDATA[{$row['practicing']}]]></practicing>
                <description><![CDATA[{$row['description']}]]></description><scale>";
            // get scale
            $scaleXML = simplexml_load_string($this->getCriteriaScaleItems($row['scaleID']));
            foreach ($scaleXML->item as $item) {
                $returnVal.="<item id='{$item->id}'><id>{$item->id}</id><short_description><![CDATA[{$item->short_description}]]></short_description><long_description><![CDATA[{$item->long_description}]]></long_description><needs_comment>{$item->needs_comment}</needs_comment><value>{$item->value}</value></item>";
//            $returnVal.=$this->getCriteriaForQuestion($question->id, false) . "</criteriadata>";
            }
            $returnVal.="</scale><questiondata>";
            $questionsXML = simplexml_load_string($this->getQuestionsForSession($row['ID']));
            foreach ($questionsXML->question as $question) {
                $returnVal.="<question id='{$question->id}'><id>{$question->id}</id><text><![CDATA[{$question->text}]]></text><type>{$question->type}</type></question>";
//            $returnVal.=$this->getCriteriaForQuestion($question->id, false) . "</criteriadata>";
            }

            $returnVal.= "</questiondata>";
            $returnVal .= "<students>{$this->findStudentsForForm($row['ID'], '')}</students></instance>";
            // get students for this instance
        }

        return $returnVal . "</data>";
    }

    /**
     * Gets a examination session overview by ID
     * @global type $CFG
     * @param type $instanceID teh instanec ID
     * @return an XML formatted string containing information about the associated examination instance
     */
    public function getExamInstanceOverviewByID($instanceID) {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        //mysqli_select_db($CFG->schema, $conn);
        $query = "SELECT ID, name, description, unit_id, scaleID, exam_starttimestamp, exam_endtimestamp, owner_id, finalised, active, practicing, archived FROM {$CFG->schema}.exam_instances WHERE ID = $instanceID";
        $stmt = $conn->prepare($query);
        $stmt->execute() or die('<data><error>getExamInstanceOverviewByID query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $returnVal.="<instance><id>{$row['ID']}</id>
                <name><![CDATA[{$row['name']}]]></name>
                <description>{$row['description']}</description>
                <unit_id>{$row['unit_id']}</unit_id>
                <scale_id>{$row['scaleID']}</scale_id>
                <exam_starttimestamp>" . date('d/m/Y', $row['exam_starttimestamp']) . "</exam_starttimestamp>
                <exam_endtimestamp>" . date('d/m/Y', $row['exam_endtimestamp']) . "</exam_endtimestamp>
                <owner_id>{$row['owner_id']}</owner_id>
                <finalised>{$row['finalised']}</finalised>
                    <active>{$row['active']}</active>
                        <practicing>{$row['practicing']}</practicing>
                    <archived>{$row['archived']}</archived>
                </instance>";
        }
        
        return $returnVal . "</data>";
    }

    /**
     * Gets a examination session questions by ID for use in the iPad app
     * @global type $CFG
     * @param type $instanceID the instance ID
     * @return an XML formatted string containing information about the associated examination instance
     */
    public function getExamInstanceQuestionsByID($instanceID) {
        global $CFG;
        $returnVal = '<data>';
        $returnVal.="<overview>" . $this->getExamInstanceOverviewByID($instanceID) . "</overview><questiondata>";
        $questionsXML = simplexml_load_string($this->getQuestionsForSession($instanceID));
        foreach ($questionsXML->question as $question) {
            $returnVal.="<question id='{$question->id}'><id>{$question->id}</id><text><![CDATA[{$question->text}]]></text><type>{$question->type}</type></question>";
//            $returnVal.=$this->getCriteriaForQuestion($question->id, false) . "</criteriadata>";
        }
        $returnVal.="</questiondata></data>";
        return $returnVal;
    }

    /**
     * Retrieves a feedback email stem for editing or use
     * @global type $CFG
     * @param type $instanceID
     * @return string
     */
    public function getExamEmailStemByID($instanceID) {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT ID, emailtext FROM exam_instances WHERE ID = :instanceID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':instanceID', $instanceID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getExamEmailStemByID query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<text><![CDATA[{$row['emailtext']}]]></text>";
        }
        $returnVal.="</data>";
        return $returnVal;
    }

    /**

      /**
     * Gets *completed* exam instances, with some information about them
     * Updated 14/10/13 to include data about enrolled students and completed exams
     * @global type $CFG
     * @param type $count teh number of assessment items to return
     * @param type $offset The offset, where to return items from
     * @return an XML formatted string containing information about the returned examination instances
     */
    public function getActiveExamInstances($site = -1) {
        global $CFG;

        $returnVal = '<data>';
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error_list($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
//    mysqli_select_db($conn, $CFG->schema);
        $countquery = "SELECT COUNT(*) as count FROM exam_instances  WHERE deleted<>'true' AND finalised='true' AND active = 'true' AND archived<>'true'";
        $stmt = $conn->prepare($countquery);
        $stmt->execute() or die('<data><error>getActiveExamInstances count query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        //print($countquery);
        // $result = mysqli_query($conn, $countquery) or die('<data><error>check exam_instances query failed</error><detail>' . var_dump(mysqli_error($conn)) . $countquery . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<count>{$row['count']}</count>";
        }
        $stmt->closeCursor();
        $query = "SELECT a.ID, a.`name`, a.description, 
             (SELECT COUNT(*) FROM student_exam_instance_link b WHERE b.exam_instances_ID = a.ID " . (($site > -1) ? "AND b.site_ID = :site" : "") . ") as enrolmentcount,
(SELECT COUNT(*) FROM student_exam_sessions d WHERE d.form_id = a.ID AND d.status='complete' " . (($site > -1) ? "AND d.site_ID = :site" : "") . ") as completedcount,
            (select description from unit_lookup where unit_lookup.ID = a.unit_id) as unit,
            a.exam_starttimestamp,
            a.created_timestamp,
            a.finalised,         
            (select username from users where users.ID = a.owner_id) as owner
             FROM exam_instances a WHERE a.deleted<>'true' AND a.finalised='true' AND active = 'true' AND archived<>'true'  ORDER BY a.exam_starttimestamp DESC";

        // print($query);
        // $result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');

        $stmt = $conn->prepare($query);
        if ($site > -1) {
            $stmt->bindValue(':site', $site, PDO::PARAM_INT);
        }
        $stmt->execute() or die('<data><error>getActiveExamInstances enum query failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<instance><id>{$row['ID']}</id>
                <name><![CDATA[{$row['name']}]]></name>
                <description><![CDATA[{$row['description']}]]></description>
                <unit><![CDATA[{$row['unit']}]]></unit>
                <owner><![CDATA[{$row['owner']}]]></owner>
                <finalised><![CDATA[{$row['finalised']}]]></finalised>
                <completedcount><![CDATA[{$row['completedcount']}]]></completedcount>
                <enrolmentcount><![CDATA[{$row['enrolmentcount']}]]></enrolmentcount>
                <remainingcount><![CDATA[" . ($row['enrolmentcount'] - $row['completedcount']) . "]]></remainingcount>
                <exam_starttimestamp><![CDATA[" . date('d/m/Y h:i A', $row['exam_starttimestamp']) . "]]></exam_starttimestamp>
                <created_date><![CDATA[" . date('d/m/Y h:i A', $row['created_timestamp']) . "]]></created_date></instance>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets *completed* exam instances, with some information about them
     * Updated 14/10/13 to include data about enrolled students and completed exams
     * @global type $CFG
     * @param type $count teh number of assessment items to return
     * @param type $offset The offset, where to return items from
     * @return an XML formatted string containing information about the returned examination instances
     */
    public function getCompletedExamInstances($count, $offset, $datefrom, $dateto) {
        global $CFG;

        $now = time();

        if (count(explode('/', $datefrom)) == 3) {
            $dateparts = explode('/', $datefrom);
            $datefromUNIX = mktime(0, 0, 0, $dateparts[1], $dateparts[0], $dateparts[2]);
        }
        if (count(explode('/', $dateto)) == 3) {
            $dateparts = explode('/', $dateto);
            $datetoUNIX = mktime(0, 0, 0, $dateparts[1], $dateparts[0], $dateparts[2]);
        }

        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $countquery = "SELECT COUNT(*) as count FROM exam_instances  WHERE deleted<>'true' AND finalised='true' AND active <> 'true' AND COALESCE(exam_endtimestamp,0)>0";
        $stmt = $conn->prepare($countquery);
        $stmt->execute() or die('<data><error>getCompletedExamInstances count query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<count>{$row['count']}</count>";
        }

        $stmt->closeCursor();

        $query = "SELECT a.ID, a.`name`, a.description, 
             (SELECT COUNT(*) FROM student_exam_instance_link b WHERE b.exam_instances_ID = a.ID) as enrolmentcount,
(SELECT COUNT(*) FROM student_exam_sessions d WHERE d.form_id = a.ID AND d.status='complete') as completedcount,
            (select description from unit_lookup where unit_lookup.ID = a.unit_id) as unit,
            a.exam_starttimestamp,
            a.exam_endtimestamp,
            a.created_timestamp,
            a.finalised,         
            (select username from users where users.ID = a.owner_id) as owner
             FROM exam_instances a WHERE a.deleted<>'true' AND a.finalised='true'  AND active <> 'true' AND COALESCE(exam_endtimestamp,0)>0 ORDER BY a.exam_starttimestamp DESC LIMIT $offset, $count ";

        $stmt = $conn->prepare($query);
        $stmt->execute() or die('<data><error>getCompletedExamInstances enum query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<instance><id>{$row['ID']}</id>
                <name><![CDATA[{$row['name']}]]></name>
                <description><![CDATA[{$row['description']}]]></description>
                <unit><![CDATA[{$row['unit']}]]></unit>
                <owner><![CDATA[{$row['owner']}]]></owner>
                <finalised><![CDATA[{$row['finalised']}]]></finalised>
                <completedcount><![CDATA[{$row['completedcount']}]]></completedcount>
                <enrolmentcount><![CDATA[{$row['enrolmentcount']}]]></enrolmentcount>
                <remainingcount><![CDATA[" . ($row['enrolmentcount'] - $row['completedcount']) . "]]></remainingcount>
                <exam_starttimestamp><![CDATA[" . date('d/m/Y', $row['exam_starttimestamp']) . "]]></exam_starttimestamp>
                    <exam_endtimestamp><![CDATA[" . date('d/m/Y', $row['exam_endtimestamp']) . "]]></exam_endtimestamp>
                <created_date><![CDATA[" . date('d/m/Y', $row['created_timestamp']) . "]]></created_date></instance>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Lists assessors associated with an instance ID
     * @global type $CFG
     * @param type $id
     * @return an XML formatted string containing information about the associated assessors
     */
    public function listUsersAssociatedWithInstance($id) {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        $query = "SELECT a.ID, a.name, a.username FROM users a inner join users_exam_instances_link b on a.ID = b.users_ID WHERE b.exam_instances_ID = :id;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>listUsersAssociatedWithInstance enum query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<user><id>{$row['ID']}</id><name><![CDATA[{$row['name']}]]></name><username><![CDATA[{$row['username']}]]></username></user>";
        }
        return $returnVal . "</data>";
    }

    /**
     * list students associated with an instance ID
     * @global type $CFG
     * @param type $id
     * @return an XML formatted string containing information about the students associates with the instance
     */
    public function listStudentsAssociatedWithInstance($id) {
        global $CFG;
        $returnVal = '<data>';
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        $query = "SELECT a.ID, a.fname, a.lname, a.studentnum, b.ID as entryid, b.site_ID FROM students a inner join student_exam_instance_link b on a.ID = b.students_ID WHERE b.exam_instances_ID = :id;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>listUsersAssociatedWithInstance enum query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<student><id>{$row['ID']}</id><entryid>{$row['entryid']}</entryid><fname><![CDATA[{$row['fname']}]]></fname><lname><![CDATA[{$row['lname']}]]></lname><studentnum><![CDATA[{$row['studentnum']}]]></studentnum><site>{$row['site_ID']}</site></student>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets a listing of assessment items
     * @global type $CFG
     * @param type $count teh number of assessment items to return
     * @param type $offset The offset, where to return items from
     * @return an XML formatted string containing information about the assessment items
     * TODO make this searchable, and taggable
     */
    public function getAssessmentItems($count, $offset, $searchstr) {
        global $CFG;
        $returnVal = '<data>';
        // sanitizing teh count and offset. I don't know how to use these in PDO, all I know is that it breaks things if I try
        $count = intval($count);
        $offset = intval($offset);
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $countquery = "SELECT COUNT(*) as count FROM assessment_items a WHERE a.deleted<>'true'" . (isset($searchstr) ? " AND text LIKE :searchstr" : '');
        // $result = mysqli_query($conn, $countquery) or die('<data><error>check AssessmentItems query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt = $conn->prepare($countquery);
        if (isset($searchstr)) {
            $stmt->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
        }
        $stmt->execute() or die('<data><error>getCompletedExamInstances count query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<count>{$row['count']}</count>";
        }

        $stmt->closeCursor();

        $query = "SELECT a.ID, a.text, a.type, a.notes, a.created_timestamp, a.modified_timestamp, b.name,
                (SELECT COUNT(ID) from question_exam_instance_link WHERE question_exam_instance_link.question_ID = a.ID) as count
                FROM assessment_items a INNER JOIN users b ON a.created_byID = b.ID WHERE a.deleted<>'true' " . (isset($searchstr) ? " AND a.text LIKE :searchstr" : '') . " LIMIT $offset, $count";
        //$returnVal.="<query><![CDATA[$query]]></query>";
        $stmt = $conn->prepare($query);
        if (isset($searchstr)) {
            $stmt->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
        }
        $stmt->execute() or die('<data><error>getAssessmentItems enum query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<item>
                <id>{$row['ID']}</id>
                <text><![CDATA[{$row['text']}]]></text>
                <notes><![CDATA[{$row['notes']}]]></notes>
                <type>{$row['type']}</type>
                <use_count>{$row['count']}</use_count>
                <created_date>" . date('d/m/Y', $row['created_timestamp']) . "</created_date>
                <modified_date>" . date('d/m/Y', $row['modified_timestamp']) . "</modified_date>
                <created_by><![CDATA[{$row['name']}]]></created_by></item>";
        }
        return $returnVal . "</data>";
    }

    /**
     * Gets questions by search string.
     * Updated: using Levenshtein distance as a MySQL function to get suggestions
     * Uses mySQL functions http://www.artfulsoftware.com/infotree/qrytip.php?id=552
     * @global type $CFG
     * @param type $searchstr
     * @param type $sessionID
     * @return an XML formatted string containing information about the assessment items
     *  TODO merge this with the above function
     */
    public function getQuestionsBySearchStr($searchstr) {
        global $CFG;
        if (strlen($searchstr) > 3) {
            $returnVal = '<data>';

            // use PDO
            try {
                $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
            } catch (PDOException $e) {
                die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
            }
            // bah- the Levenstein function absolutely canes a mysql server
            // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
//        $query = "SELECT a.ID, a.text, a.type, a.notes, a.created_timestamp, a.modified_timestamp, b.name, 
//                (SELECT COUNT(ID) from question_exam_instance_link WHERE question_exam_instance_link.question_ID = a.ID) as count
//                FROM {$CFG->schema}.assessment_items a INNER JOIN {$CFG->schema}.users b ON a.created_byID = b.ID
//                WHERE a.text LIKE '%$searchstr%'";

            $query = "SELECT DISTINCT `a`.`ID`,  `a`.`text`, `a`.`type`, `b`.`name`
FROM exam_questions a inner join exam_instances b ON `b`.`ID` = `a`.`exam_id`
WHERE `a`.`text` LIKE :searchstr GROUP BY `a`.`text` LIMIT 10;";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
            $stmt->execute() or die('<data><error>getQuestionsBySearchStr query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
            //$result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                //           $returnVal.="<question>
//                <id>{$row['ID']}</id>
//                <text><![CDATA[{$row['text']}]]></text>
//                    <type><![CDATA[{$row['type']}]]></type>
//                <examname><![CDATA[{$row['name']}]]></examname>
//                <diffcount>{$row['diffcount']}</diffcount></question>";
                $returnVal.="<question><id>{$row['ID']}</id>
                <text><![CDATA[{$row['text']}]]></text>
                    <type><![CDATA[{$row['type']}]]></type>
                <examname><![CDATA[{$row['name']}]]></examname>
                </question>";
            }
            return $returnVal . "</data>";
        } else {
            return "<data/>";
        }
    }

    /**
     * Gets questions for a given exam session
     * @global type $CFG
     * @param type $sessionID teh ID of the exam session
     * @return an XML formatted string containing information about the assessment items
     */
    public function getQuestionsForSession($sessionID) {
        global $CFG;
        $returnVal = '<data>';
        //  $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT a.ID, a.text, a.type, a.order from exam_questions a WHERE a.exam_id = :sessionID AND a.deleted <>'true' ORDER BY a.order ASC;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getQuestionsForSession query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        //$result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<question><id>{$row['ID']}</id><text><![CDATA[{$row['text']}]]></text><type>{$row['type']}</type></question>";
        }
        //  print($returnVal);
        return $returnVal . "</data>";
    }

//
//    public function getQuestionsForSession($sessionID) {
//        global $CFG;
//        $returnVal = '<data>';
//        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
//        $query = "SELECT a.ID, b.ID as linkID, a.text, a.type, (SELECT COUNT(ID) from question_exam_instance_link WHERE question_exam_instance_link.question_ID = a.ID) as count
//                FROM assessment_items a inner join question_exam_instance_link b on a.ID = b.question_ID WHERE b.exam_instances_ID = $sessionID ORDER BY b.order ASC;
//                ";
//
//        $result = mysqli_query($conn, $query) or die('<data><error>getQuestionsForSession failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
//        while ($row = mysqli_fetch_array($result)) {
//            $returnVal.="<question><id>{$row['ID']}</id><linkid>{$row['linkID']}</linkid><text>{$row['text']}</text><use_count>{$row['count']}</use_count><type>{$row['type']}</type></question>";
//        }
//        return $returnVal . "</data>";
    //   }

    /**
     * Gets question details by ID
     * @global type $CFG
     * @param type $questionID
     * @return an XML formatted string containing information about the selected assessment item
     */
    public function getQuestionByID($questionID) {
        global $CFG;
        $returnVal = '';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT a.ID, a.text, a.required, a.notes, a.created_timestamp, a.type, b.name FROM assessment_items a INNER JOIN users b ON a.created_byID = b.ID WHERE a.ID = :questionID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':questionID', $questionID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getQuestionByID query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        //$result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<question><id>{$row['ID']}</id><text><![CDATA[{$row['text']}]]></text><required><![CDATA[{$row['required']}]]></required><notes><![CDATA[{$row['notes']}]]></notes><created_date>" . date('d/m/Y', $row['created_timestamp']) . "</created_date><created_by>{$row['name']}</created_by><type>{$row['type']}</type></question>";
        }
        return $returnVal . "";
    }

    /**
     * Exports a whole assessment as an XML string
     * @global type $CFG
     * @param type $examID
     */
    public function exportAssessment($examID) {
        global $CFG;
        $returnStr = "<data>";
        // get overview
        $returnStr.="<overview>" . $this->getExamInstanceOverviewByID($examID) . "</overview>";
        // get questions
        $returnStr.="<questiondata>" . $this->getQuestionsForSession($examID) . "</questiondata>";
        // get the email stem
        $returnStr.="<feedbackemailstem>" . $this->getExamEmailStemByID($examID) . "</feedbackemailstem></data>";
        // send it out...
        return $returnStr;
    }

    /**
     * Gets a success and failure summary for the feedback mailout for a particular assessment session
     * @global type $CFG
     * @param type $sessionID The session ID this summary is for
     * @return string an XML formatted string containing the success and failure counts 
     */
    public function getFeedbackSummary($sessionID) {
        global $CFG;
        $returnVal = '';
        $successcount = 0;
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT DISTINCT (select count(*) from mail_log WHERE session_id = :sessionID AND status = 'true') as successcount, (select count(*) from mail_log WHERE session_id = :sessionID AND status <> 'true') as failcount, (select count(*) from mail_log WHERE session_id = :sessionID) as totalcount from mail_log;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getFeedbackSummary query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<successcount>{$row['successcount']}</successcount><failcount>{$row['failcount']}</failcount><totalcount>{$row['totalcount']}</totalcount>";
        }
        return $returnVal . "";
    }

    /**
     * Gets the detailed logs for the feedback mailout for a particular assessment session
     * @global type $CFG
     * @param type $sessionID
     * @return string an XML formatted string containing the detailed logs for the feedback mailout for a particular assessment session
     */
    public function getFeedbackLog($sessionID) {
        global $CFG;
        $returnVal = '';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT * from mail_log WHERE session_id = :sessionID";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sessionID', $sessionID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getFeedbackLog query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<criteria><id>{$row['ID']}</id><text><![CDATA[{$row['text']}]]></text><required>{$row['required']}</required></criteria>";
        }
        return $returnVal . "";
    }

    /**
     * Gets the edit history of a particular student answer
     * @global type $CFG
     * @param type $itemID
     * @return type
     */
    public function getItemEditHistory($itemID) {
        global $CFG;

        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $returnVal = '<data>';
        $query = "SELECT * from student_exam_sessions_responses_changelog WHERE student_exam_sessions_responses_ID = :itemID ORDER BY timestamp DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':itemID', $itemID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getItemEditHistory query failed</error><detail>' . $stmt->errorInfo() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<item><id>{$row['ID']}</id><datetime>" . date('d/m/Y, g:i a', $row['timestamp']) . "</datetime><changed_by><![CDATA[" . simplexml_load_string($this->getUserByID($row['changed_by_ID']))->user->name . "]]></changed_by><type>{$row['type']}</type><oldvalue>{$row['oldvalue']}</oldvalue><newvalue>{$row['newvalue']}</newvalue><oldcomment><![CDATA[{$row['oldcomment']}]]></oldcomment><newcomment><![CDATA[{$row['newcomment']}]]></newcomment><description>{$row['description']}</description></item>";
        }

        return $returnVal . '</data>';
    }

    /**
     * Gets the edit history of an assessment instance
     * @global type $CFG
     * @param type $itemID
     * @return type
     */
    public function getOverviewHistory($assessmentID) {
        global $CFG;

        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $returnVal = '<data>';
        $query = "SELECT * from student_exam_sessions_changelog WHERE student_exam_sessions_ID = :assessmentID ORDER BY timestamp DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':assessmentID', $assessmentID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>getOverviewHistory query failed</error><detail>' . $stmt->errorInfo() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<item><id>{$row['ID']}</id><datetime>" . date('d/m/Y, g:i a', $row['timestamp']) . "</datetime><changed_by><![CDATA[" . simplexml_load_string($this->getUserByID($row['changed_by_id']))->user->name . "]]></changed_by>
                <type>{$row['type']}</type>
                    <oldrating>{$row['oldrating']}</oldrating>
                        <newrating>{$row['newrating']}</newrating>
                            <oldadditionalrating>{$row['oldadditionalrating']}</oldadditionalrating>
                            <newadditionalrating>{$row['newadditionalrating']}</newadditionalrating>
                            <oldcomments><![CDATA[{$row['oldcomments']}]]></oldcomments><newcomments><![CDATA[{$row['newcomments']}]]></newcomments><description><![CDATA[{$row['description']}]]></description></item>";
        }

        return $returnVal . '</data>';
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    // Deprecated functions
    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns results of a search of students with a given student number, who have not filled in this form. Used to pick a student for an examination
     * @global type $CFG
     * @param type $searchstr the student number so search for
     * @param type $assessmentID
     * @return type
     */
    public function getStudentsByStudentNum($searchstr, $assessmentID) {
        global $CFG;
        $returnVal = '<data>';
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        // get count
        $countquery = "SELECT COUNT(*) FROM students WHERE ID NOT IN(SELECT student_id from student_exam_sessions WHERE form_id = :assessmentID ) AND studentnum LIKE :searchstr AND locked != 1";
        $stmt = $conn->prepare($countquery);
        $stmt->bindValue(':assessmentID', $assessmentID, PDO::PARAM_INT);
        $stmt->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
        $stmt->execute() or die('<data><error>getStudentsByStudentNum count query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['count'] < 1)
                return '<data><error>No results</error><detail>No available students match that student number</detail></data>';
        }
        $stmt->closeCursor();
        //mysqli_select_db($CFG->schema, $conn);
        // a query that excludes students that have already 
        $query = "SELECT * FROM students WHERE ID NOT IN(SELECT student_id from student_exam_sessions WHERE form_id = :assessmentID ) AND studentnum LIKE :searchstr AND locked != 1";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':assessmentID', $assessmentID, PDO::PARAM_INT);
        $stmt->bindValue(':searchstr', '%' . $searchstr . '%', PDO::PARAM_STR);
        $stmt->execute() or die('<data><error>getStudentsByStudentNum query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<student><id>{$row['ID']}</id><fname><![CDATA[{$row['fname']}]]></fname><lname><![CDATA[{$row['lname']}]]></lname><studentnum>{$row['studentnum']}</studentnum></student>";
        }
        return $returnVal . "</data>";
    }

    /**
     * DEPREACATED
     * Gets a student details by cohort
     * @global type $CFG
     * @param type $cohort the cohort to list the students by
     * @return an XML formatted string containing information about the found students
     */
    public function getStudentsByCohortID($cohort) {
        global $CFG;
        $returnVal = '<data>';
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        //mysqli_select_db($CFG->schema, $conn);
        $query = "SELECT ID, fname, lname, studentnum, email, cohort FROM {$CFG->schema}.students WHERE cohort = $cohort";
        $result = mysqli_query($conn, $query) or die('<data><error>check user query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        while ($row = mysqli_fetch_array($result)) {
            $returnVal.="<student><id>{$row['ID']}</id><fname>{$row['fname']}</fname><lname>{$row['lname']}</lname><studentnum>{$row['studentnum']}</studentnum><cohort>{$row['cohort']}</cohort><email>{$row['email']}</email></student>";
        }
        return $returnVal . "</data>";
    }

    /**
     * DEPREACATED
     * @global type $CFG
     * @param type $criteriaID
     * @return type
     */
    public function getCriteriaByID($criteriaID) {
        global $CFG;
        $returnVal = '';
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "SELECT * from questions_criteria WHERE ID = $criteriaID";
        $result = mysqli_query($conn, $query) or die('<data><error>getQuestionByID select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        while ($row = mysqli_fetch_array($result)) {
            $returnVal.="<criteria><id>{$row['ID']}</id><text><![CDATA[{$row['text']}]]></text><required>{$row['required']}</required></criteria>";
        }
        return $returnVal . "";
    }

    /**
     *  * DEPREACATED
     * @global type $CFG
     * @param type $questionID
     * @param type $includeQuestion
     * @return type
     */
    public function getCriteriaForQuestion($questionID, $includeQuestion) {
        global $CFG;
        //print('Question id is'.$questionID);
        $returnVal = '<data>';
        if ($includeQuestion) {
            $returnVal .= $this->getQuestionByID($questionID);
        }
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "SELECT a.ID, a.text, a.required, a.created_timestamp, a.order, b.name FROM questions_criteria a INNER JOIN users b ON a.created_byID = b.ID WHERE a.questions_ID = $questionID ORDER BY a.order ASC";
        $result = mysqli_query($conn, $query) or die('<data><error>select query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        while ($row = mysqli_fetch_array($result)) {
            $returnVal.="<criteria><id>{$row['ID']}</id><text><![CDATA[{$row['text']}]]></text><required>{$row['required']}</required><created_date>" . date('d/m/Y', $row['created_timestamp']) . "</created_date><created_by>{$row['name']}</created_by></criteria>";
        }
        return $returnVal . "</data>";
    }

    /**
     *  * DEPREACATED
     * @global type $CFG
     * @param type $instanceID
     * @param type $cohort
     * @return type
     */
    public function getStudentsByCohortIDAssociatedWithInstance($instanceID, $cohort) {
        global $CFG;
        $returnVal = '<data>';
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        //mysqli_select_db($CFG->schema, $conn);
        $query = "SELECT a.ID, a.fname, a.lname, a.studentnum, a.cohort, b.exam_instances_ID FROM {$CFG->schema}.students a INNER JOIN student_exam_instance_link b on a.ID = b.students_ID WHERE a.cohort = $cohort AND b.exam_instances_ID = $instanceID";
        $result = mysqli_query($conn, $query) or die('<data><error>check user query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        while ($row = mysqli_fetch_array($result)) {
            $returnVal.="<student><id>{$row['ID']}</id><fname><![CDATA[{$row['fname']}]]></fname><lname><![CDATA[{$row['lname']}]]></lname><studentnum>{$row['studentnum']}</studentnum><cohort>{$row['cohort']}</cohort></student>";
        }
        return $returnVal . "</data>";
    }

}

?>

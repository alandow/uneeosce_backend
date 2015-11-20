<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *Gets a string from the database for display in a page. We do this because it's easier to make a change here than to make it in the main app code, and people get really funny about wording.
 *
 * @author alandow
 */
class StringLib {
    
    /**
     * 
     * @global type $CFG
     * @param type $string the string definition to retrieve from teh database
     * @return string a string definition
     */
        public function get_string($string, $lang='en') {
        global $CFG;
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysql_error() . '</detail></data>');
        $returnStr = "[$string]";
        // Delete student entry
        $query = "SELECT definition_$lang FROM dict WHERE string = '$string' LIMIT 1";
        $result = mysqli_query($conn, $query) or die('check string query failed:' . mysql_error() . $query );

        while ($row = mysqli_fetch_array($result)) {
            $returnStr = $row['definition_'.$lang];
        }
        if($CFG->showstringsource=='true'){
            $returnStr.="[$string]";
        }
        return $returnStr;
    }
}

?>

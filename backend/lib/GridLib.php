<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GridLib
 *
 * @author alandow
 */
class GridLib {

    //put your code here
    public function getGridForTable($table, $columns, $showdelete) {
        global $CFG;

        // Database connection
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die("<data><error>cannot select  {$CFG->schema}</error><detail>" . mysqli_error($conn) . "</detail></data>");

        // build a mapping of the table for comparison
        $query = "SHOW COLUMNS FROM `$table`";

        $result = mysqli_query($conn, $query) or die("<data><error>cannot show columns from records, query was $query</error><detail>" . mysqli_error($conn) . "</detail></data>");

        $rawfieldsmap = array();

        // build a map of existing fields
        while ($row = mysqli_fetch_array($result)) {
            $rawfieldsmap[] = $row['Field'] . ',' . $row['Type'];
        }

        $result = mysqli_query($conn, "SELECT * FROM $table WHERE `editable` = 'true' AND COALESCE (`deleted`, '') <> 'true' ORDER BY ID DESC");


// create a new EditableGrid object
        $grid = new EditableGrid();
        // get the columns
        $columnsArr = explode(',', $columns);
        //  print_r($columnsArr);
        /*
         *  Add columns. The first argument of addColumn is the name of the field in the databse. 
         *  The second argument is the label that will be displayed in the header
         */
        $grid->addColumn('ID', 'ID', 'integer', NULL, false);
        foreach ($columnsArr as $column) {
            foreach ($rawfieldsmap as $fieldsmapelement) {
                $fieldsmapelementArr = explode(',', $fieldsmapelement);
                if ($column == $fieldsmapelementArr[0]) {
                    if (stripos($fieldsmapelementArr[0], "int") !== false) {
                        $grid->addColumn($column, $column, 'integer');
                    } else {
                        $grid->addColumn($column, $column, 'string');
                    }
                }
            }
        }

        if ($showdelete) {
            $grid->addColumn("Delete", 'Delete', 'string', NULL, false);
        }
// send data to the browser
        return $grid->renderXML($result);
    }

    public function updateTable($table, $id, $colname, $newvalue) {
        global $CFG;

        // Database connection
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die("<data><error>cannot select  {$CFG->schema}</error><detail>" . mysqli_error($conn) . "</detail></data>");

        $query = "UPDATE $table SET $colname='$newvalue' WHERE id=$id";
        $result = mysqli_query($conn, $query) or die('<data><error>update grid failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        return('<data><result>' . ((mysqli_affected_rows($conn) > 0) ? 'true' : 'false') . '</result></data>');
    }

    public function addRow($table) {
        global $CFG;

        // Database connection
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die("<data><error>cannot select  {$CFG->schema}</error><detail>" . mysqli_error($conn) . "</detail></data>");

        $query = "INSERT INTO `$table` (`ID`) VALUES (NULL);";
        $result = mysqli_query($conn, $query) or die('<data><error>insert into grid failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        return('<data><result>' . ((mysqli_affected_rows($conn) > 0) ? 'true' : 'false') . '</result></data>');
    }

    public function deleteRow($table, $id) {
        global $CFG;
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die("<data><error>cannot select  {$CFG->schema}</error><detail>" . mysqli_error($conn) . "</detail></data>");
        // check if it's in use
        //    if ($this->checkRow($table, $id)) {
        // if so, just set it to deleted so we don't break everything
        $query = "UPDATE $table SET `deleted` = 'true' WHERE ID = $id";
        //  } else {
        // or lose the entry entirely
        //   $query = "DELETE FROM $table WHERE ID = $id";
        //   }
        $result = mysqli_query($conn, $query) or die('<data><error>update grid failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        return('<data><result>' . ((mysqli_affected_rows($conn) > 0) ? 'true' : 'false') . '</result></data>');
    }

    public function checkRow($table, $id) {
        global $CFG;
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die("<data><error>cannot select  {$CFG->schema}</error><detail>" . mysqli_error($conn) . "</detail></data>");
        // check if it's in use
        $returnval = false;
        // print("table is".$table);

        $query = "SELECT COUNT(*) as count FROM $table WHERE ID = $id";


        $result = mysqli_query($conn, $query) or die("<data><error>cannot check entry</error><detail>" . mysqli_error($conn) . "</detail></data>");

        while ($row = mysqli_fetch_array($result)) {

            $returnval = ($row['count'] > 0);
        }

        return $returnval;
    }

    public function fetch_pairs($mysqli, $query) {
        if (!($res = $mysqli->query($query)))
            return FALSE;
        $rows = array();
        while ($row = $res->fetch_assoc()) {
            $first = true;
            $key = $value = null;
            foreach ($row as $val) {
                if ($first) {
                    $key = $val;
                    $first = false;
                } else {
                    $value = $val;
                    break;
                }
            }
            $rows[$key] = $value;
        }
        return $rows;
    }

}

?>

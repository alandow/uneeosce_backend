<?php

/**
 * Access the 'config' table for semi-persistent variables.
 *
 * @author alandow
 */
class ConfigLib {

    //put your code here
    public function getConfig($name) {
        global $CFG;
        $returnVal = '<data>';
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "SELECT * FROM config WHERE name = :name";
        $stmt = $conn->prepare($query);
        if (isset($searchstr)) {
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        }
        $result = $stmt->execute() or die('<data><error>getConfig failed</error><detail>' . var_dump($stmt->errorInfo()) . '</detail></data>');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $returnVal.="<count>{$row['count']}</count>";
        }
    }

}

?>

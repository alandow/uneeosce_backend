<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * At some point, this will be an abstraction layer for the database so that we can have other database types
 *
 * @author alandow
 */
class DbLib {

    /**
     * 
     * @global type $CFG
     * @return boolean|\PDO
     */
    public function getConnection() {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            return false;
        }
        return $conn;
    }

    public function buildStatement($connection, $query, $vars) {
        $stmt = $connection->prepare($query);
        foreach ($vars as $key => $value) {
            $stmt->bindValue(':'.$key, $value);
        }
        return $stmt;
    }

    public function execute($statement) {
        return $statement->execute()  or die('<data><error>statement execution failed</error><detail>' . var_dump($statement->errorInfo()) . '</detail></data>');;
    }
    
    public function getResult($statement){
        return $statement->fetchAll();
    }

}

?>

<?php

/*
  PHP SimpleToken
  Written by: Nick Comer
  Version: 0.4.5

  ADDITIONAL INFO:
  Installation Time: 0-1 Minutes
  Resources Needed: This Script and a server with PHP 5 Support

  Licensing:#################################################################
  Copyright [2011] [Nick Comer]

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

  http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
  ###########################################################################

  Modified by Adam

 */



require_once('config.inc.php');

class SimpleToken {

    private function connectToTokenDB() {
        global $CFG;
        $connect = @mysql_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('DB CONNECTION ERROR! CHECK CONSTANTS!');
        mysql_select_db($CFG->schema, $connect);
        return $connect;
    }

    public function fetchTokenInfo($tokenID, $fieldToGet) {
        global $CFG;
        $con = $this->connectToTokenDB();
        $query = mysql_query("SELECT * FROM `" . $CFG->schema . "`.`token` WHERE tokenID='$tokenID'");
        while ($row = mysql_fetch_array($query)) {
            $value = $row[$fieldToGet];
        }
        return $value;
    }

   public function doesExist($tokenID, $key) {
        global $CFG;
        $con = $this->connectToTokenDB();
        $query = mysql_query("SELECT * FROM `" . $CFG->schema . "`.`token` WHERE tokenID='$tokenID'");
        $result = mysql_num_rows($query);
        switch ($result) {
            case 1:
                $rightKey = $this->fetchTokenInfo($tokenID, 'key');
                if ($rightKey == $key)
                    return true;
                else
                    return false;
                break;
            case 0:
                return false;
                break;
            default:
                return false;
                break;
        }
    }

  public  function putToken($tokenID, $key, $type, $info) {
        global $CFG;
        $con = $this->connectToTokenDB();
        $query = mysql_query("INSERT INTO `" . $CFG->schema . "`.`token` (`tokenID`, `key`, `type`, `info`) VALUES ('$tokenID', '$key', '$type', '$info');") or die(mysql_error());
        return $query;
    }

//GENERATES A COMPLETELY RANDOM VALUE
    private function randomValue() {
        global $CFG;
        $start = uniqid();
        $start = md5($start);
        $part1 = substr($start, 0, 16);
        $part2 = substr($start, 16, 16);
        $new = "";
        for ($i = 0; $i < 32; $i++) {
            if ($i == 0)
                $new .= substr($part1, 0, 1);
            else {
                if ($i % 2 == 0)
                    $new .= substr($part1, $i, 1);
                else
                    $new .= substr($part2, $i, 1);
            }
        }
        return $new;
    }

   public function oneClickToken() {
        global $CFG;
        $tokenID = uniqid();
        $key = $this->randomValue();
        $type = 0;
        $put = $this->putToken($tokenID, $key, $type, "0");
        if ($put) {
            $token = base64_encode($tokenID . ":" . $key);
            return $token;
        }
        else
            return false;
    }

   public function timeSensitiveToken($lifetime) {
        global $CFG;
        $tokenID = uniqid();
        $key = $this->randomValue();
        $type = 1;
        if (is_int($lifetime)) {
            $put = $this->putToken($tokenID, $key, $type, $lifetime . " " . time());
            if ($put) {
                $token = base64_encode($tokenID . ":" . $key);
                return $token;
            }
            else
                return false;
        }
        else
            return false;
    }

    public function authorizeToken($token, $deleteAfterExpiration = false) {
        global $CFG;
        $con = $this->connectToTokenDB();
        list($tokenID, $key) = explode(":", base64_decode($token));
        if ($this->doesExist($tokenID, $key)) {
            $type = $this->fetchTokenInfo($tokenID, 'type');
            $info = $this->fetchTokenInfo($tokenID, 'info');
            if ($type == 0) {
                switch ($info) {
                    case 0:
                        if ($deleteAfterExpiration)
                            mysql_query("DELETE * FROM `" . $CFG->schema . "`.`token` WHERE tokenID='$tokenID'");
                        mysql_query("UPDATE `" . $CFG->schema . "`.`token` SET `info` = '1' WHERE `token`.`tokenID`='$tokenID'");
                        return true;
                        break;
                    case 1:
                        if ($deleteAfterExpiration)
                            mysql_query("DELETE * FROM `" . $CFG->schema . "`.`token` WHERE tokenID='$tokenID'");
                        return false;
                        break;
                }
            }
            else if ($type == 1) {
                list($lifetime, $timeSet) = explode(" ", $info);
                $lifetime = (int) $lifetime;
                $timeSet = (int) $timeSet;
                $currentTime = time();
                if (($currentTime - $timeSet) <= $lifetime) {
                    if ($deleteAfterExpiration)
                        mysql_query("DELETE * FROM `" . $CFG->schema . "`.`token` WHERE tokenID='$tokenID'");
                    mysql_query("UPDATE `" . $CFG->schema . "`.`token` SET `info` = '0 " . $timeSet . "' WHERE `token`.`tokenID`='$tokenID'");
                    return true;
                }
                else {
                    if ($deleteAfterExpiration)
                        mysql_query("DELETE * FROM `" . $CFG->schema . "`.`token` WHERE tokenID='$tokenID'");
                    return false;
                }
            }
        }
        else
            return false;
    }

}

?>
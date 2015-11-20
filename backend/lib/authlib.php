<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of authlib
 *
 * @author alandow
 */
//require_once('config.inc.php');

class authlib {
    // some role constants

    const roles_conduct_assessment = "conduct_assessment";
    const roles_view_system_users = "view_system_users";
    const roles_edit_system_users = "edit_system_users";
    const roles_view_students = "view_students";
    const roles_edit_students = "edit_students";
    const roles_view_assessments = "view_assessments";
    const roles_edit_assessments = "edit_assessments";
    const roles_finalise_assessment = "finalise_assessment";
    const roles_finalise_other_assessment = "finalise_other_assessment";
    const roles_view_assessment_items = "view_assessment_items";
    const roles_edit_assessment_items = "edit_assessment_items";
    const roles_assign_students_to_assessment = "assign_students_to_assessment";
    const roles_assign_assessors_to_assessment = "assign_assessors_to_assessment";
    const roles_view_reports = "view_reports";
    const roles_edit_strings = "edit_system_strings";
    const roles_print_word_exam = "print_word_exam";
    const roles_edit_lookups = "edit_lookups";
    const roles_edit_system_config = "edit_system_config";

//define("roles_conduct_assessment", "conduct_assessment");

    /**
     * Logs in and returns a unique token
     * We can use manual or Active Directory login.
     * @TODO other login types: an easy one would be LDAP
     * @global type $CFG
     * @param type $username
     * @param type $password
     * @return XMLstr a valid token, or an error 
     */
    public function login($username, $password) {
        global $CFG;

        if ((strlen($username) == 0) || (strlen($password) == 0)) {
            return "<data><error>Bad logon</error><detail>The username/password pair is incorrect</detail></data>";
        }

        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
//Do a COUNT(*) query first to check that they're a valis user
        $query = "SELECT COUNT(*) as count FROM users WHERE username = :username AND IFNULL(deleted, '') <> 'true'";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        //$stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        //$result = mysqli_query($conn, $query) or die('<data><error>check user type query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt->execute() or die('<data><error>login query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

        // if there's a count (therefore a user with that name)
        if (($stmt->fetchObject()->count) > 0) {
            $stmt->closeCursor();
            // get some info about the user trying to log in
            $query = "SELECT * FROM users WHERE username = :username";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute() or die('<data><error>login query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $userID = $row['ID'];
                $roleID = $row['roleID'];
                $name = $row['name'];
                $type = $row['type'];
                $storedpassword = $row['password'];
            }
        } else {
            return "<data><error>Bad logon</error><detail>The username/password pair is incorrect</detail></data>";
        }
// 
        switch ($type) {
            case 'manual':
                $newpass = md5($CFG->password_salt . $password);

                if ($storedpassword != $newpass) {
                    return "<data><error>Bad logon</error><detail>The username/password pair is incorrect</detail></data>";
                }
                //  }
                break;
            case "ad":
                try {
                    // log on to AD here
                    // update: added ability to look at another server
                    $adldap = ldap_connect($CFG->domain_controller) or die("cannot connect to {$CFG->domain_controller}");
                    // set options for AD
                    ldap_set_option($adldap, LDAP_OPT_PROTOCOL_VERSION, 3);
                    ldap_set_option($adldap, LDAP_OPT_REFERRALS, 0);

                    // if there's a second domain controller defined
                    if (isset($CFG->domain_controller2)) {
                        $adldap2 = ldap_connect($CFG->domain_controller2) or die("cannot connect to {$CFG->domain_controller2}");
                        ldap_set_option($adldap2, LDAP_OPT_PROTOCOL_VERSION, 3);
                        ldap_set_option($adldap2, LDAP_OPT_REFERRALS, 0);
                    }

                    $success = false;

                    // test against first AD
                    if (@ldap_bind($adldap, $username . $CFG->account_suffix, $password)) {
                        $success = true;
                    }
                    // if set, test against 2nd
                    if (isset($CFG->domain_controller2)) {
                        if (@ldap_bind($adldap2, $username . $CFG->account_suffix2, $password)) {
                            $success = true;
                        }
                    }
                    if (!$success) {
                        return "<data><error>Bad logon</error><detail>The username/password pair is incorrect</detail></data>";
                    }
                } catch (Exception $e) {
                    // something went wrong with AD
                    return "<data><error>AD connection failed. Error was: " . var_dump($e) . "</error></data>";
                }

                break;
            case 'ldap':
                // connect to the LDAP server
                $ds = ldap_connect($CFG->ldapserver);
                // Try and bind to the server. This will only work if the supplied username and password are correct. @ suppresses errors in this function (the moar you know!)
                $r = @ldap_bind($ds, "{$CFG->login_field}=$username,{$CFG->base_dn}", $password);
                // check other parameters if binding is successful (ie username and password is valid)
                if ($r) {
                    // a fiter
                    $filter = "{$CFG->login_field}=$username";
                    $sr = ldap_search($ds, $CFG->base_dn, $filter);
                    $info = ldap_get_entries($ds, $sr);
                    for ($i = 0; $i < $info["count"]; $i++) {
                        $name = $info[$i]["{$CFG->displayname_field}"][0];
                    }
                    // be polite
                    ldap_unbind($ds);
                    // check the database
                    $ldapquery = "SELECT * FROM users WHERE username = :username";
                    $ldapstmt = $conn->prepare($ldapquery);
                    $ldapstmt->bindValue(':username', $username, PDO::PARAM_STR);
                    //$stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
                    //$result = mysqli_query($conn, $query) or die('<data><error>check user type query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
                    $ldapstmt->execute() or die('<data><error>check user query failed</error><detail>' . $ldapstmt->errorCode() . '</detail></data>');
//                    $query = "SELECT * FROM users WHERE username = '$username'";
//                    $result2 = mysqli_query($conn, $query) or die('<data><error>check user query failed</error><detail>' . mysql_error($conn) . $query . '</detail></data>');
                    while ($row1 = $stmt->fetch(PDO::FETCH_ASSOC)) {
//                        $userID = $row1['ID'];
//                        $roleID = $row1['roleID'];
                        // update the user display name
                        if ($name != $row1['name']) {
                            $ldapupdatequery = "UPDATE users SET `name` = :name WHERE ID = $userID";
                            $ldapupdatestmt = $conn->prepare($ldapupdatequery);
                            $ldapupdatestmt->bindValue(':name', $name, PDO::PARAM_STR);
                            $ldapupdatestmt->execute() or die('<data><error>update user details query failed</error><detail>' . $ldapupdatestmt->errorCode() . '</detail></data>');
                            //$result3 = mysqli_query($conn, $query) or die('<data><error>update user fullname query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
                        }
                    }
                } else {
                    return "<data><error>Bad logon</error><detail>The username/password pair is incorrect</detail></data>";
                }

                break;
            default:
                return "<data><error>Bad logon</error><detail>The username/password pair is incorrect</detail></data>";
                break;
        }

        // generate a new token for this session for this user
        // delete any existing tokens
        $deletequery = "DELETE FROM token WHERE userID = :userid;";
        $deletestmt = $conn->prepare($deletequery);
        $deletestmt->bindValue(':userid', $userID, PDO::PARAM_INT);
        $deletestmt->execute() or die('<data><error>delete token failed</error><detail>' . $deletestmt->errorCode() . '</detail></data>');

        // generate a new token
        $token = uniqid(php_uname('n'), true);

        // store the new token in the database
        $insertquery = "INSERT INTO token (token, userID, expiry) VALUES(:token, :userID, " . (time() + $CFG->tokenlifespan) . ");";
        $insertstmt = $conn->prepare($insertquery);
        $insertstmt->bindValue(':token', $token, PDO::PARAM_STR);
        $insertstmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        $insertstmt->execute() or die('<data><error>insert token query failed</error><detail>' . $deletestmt->errorCode() . '</detail></data>');

        return "<data><userID>$userID</userID><roleID>$roleID</roleID><name>$name</name><token>$token</token></data>";
    }

    /**
     * Get some user details from a token
     * @global type $CFG
     * @param type $token
     * @return type 
     */
    public function getDetailsByToken($token) {
        global $CFG;

        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        if ($this->validateToken($token)) {
            $query = "SELECT * FROM token a inner join users b on a.userID = b.ID WHERE a.token = :token";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':token', $token, PDO::PARAM_STR);
            $stmt->execute() or die('<data><error>select token failed</error><detail>' . $stmt->errorCode() . '</detail></data>');

            $hasresult = false;

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $hasresult = true;
                $userID = $row['userID'];
                $roleID = $row['roleID'];
                $name = $row['name'];
            }
            if (!$hasresult) {
                return "<data><error>Bad token</error><detail>The token is incorrect</detail></data>";
            } else {
                return "<data><userID>$userID</userID><roleID>$roleID</roleID><name>$name</name></data>";
            }
        } else {
            return "<data><error>Bad token</error><detail>The token is incorrect</detail></data>";
        }
    }

    /**
     * Validates a token and updates the expiry time
     * @global type $CFG
     * @param type $token
     * @return bool the success  or failure of this call
     */
    public function validateToken($token) {
        //return true;

        global $CFG;

        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        // $conn = mysql_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysql_error() . '</detail></data>');
        $query = "SELECT id, expiry FROM token WHERE token = :token";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);

        $stmt->execute() or die('<data><error>insert token query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
//      $result = mysql_query($query, $conn);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $expiry = $row['expiry'];
                if ($expiry > time()) {
                    //  print('yay!');
                    $newexpiry = (time() + $CFG->tokenlifespan);

                    $updatequery = "UPDATE {$CFG->schema}.token SET expiry = $newexpiry WHERE ID =  {$row['id']};";
                    $updatestmt = $conn->prepare($updatequery);
                    $updatestmt->execute() or die('<data><error>update expiry token query failed</error><detail>' . $updatestmt->errorCode() . '</detail></data>');
                    //$result = mysql_query($query, $conn) or die('<data><error>update expiry failed</error><detail>' . mysql_error() . $query . '</detail></data>');
                    return true;
                    // update expiry time
                } else {
                    //  print('Boo!- expired');
                    return false;
                }
            }
        } else {
            //print('Boo!');
            return false;
        }

        // catch all return
        return false;
    }

    /**
     * Make a new user
     * @global type $CFG
     * @param type $username
     * @param type $password
     * @param type $name
     * @param type $roleID
     * @return XMLstr the newly created user ID, or an error
     */
    public function new_user($username, $password, $name, $roleID, $type) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        $returnVal = '';
        //$conn = mysql_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysql_error() . '</detail></data>');
        // check for existing user with that username
        $query = "SELECT * FROM {$CFG->schema}.users WHERE username = :username AND IFNULL(deleted, '') <> 'true'";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        //$stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        //$result = mysqli_query($conn, $query) or die('<data><error>check user type query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        $stmt->execute() or die('<data><error>new_user query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        // return '<data><count>'+mysql_num_rows($result)+'</count></data>';
        if ($stmt->rowCount() == 0) {
            switch ($type) {
                case 'manual':
                    $newpass = md5($CFG->password_salt . $password);
                    $insertquery = "INSERT INTO {$CFG->schema}.users (username, password, name, roleID, type) VALUES(:username, :newpass, :name, :roleid, :type);";
                    $insertstmt = $conn->prepare($insertquery);
                    $insertstmt->bindValue(':username', $username, PDO::PARAM_STR);
                    $insertstmt->bindValue(':newpass', $newpass, PDO::PARAM_STR);
                    $insertstmt->bindValue(':name', $name, PDO::PARAM_STR);
                    $insertstmt->bindValue(':roleid', $roleID, PDO::PARAM_INT);
                    $insertstmt->bindValue(':type', $type, PDO::PARAM_STR);
                    break;
                case 'ad':
                    // check if the user exists in AD 1
                    $errordetail = "";
                    $error = true;
                    try {
                        $error = false;
                        // log on to AD 1 here
                        $ldap = ldap_connect($CFG->domain_controller) or die('cannot connect to Active Directory 1');
                        $password = $CFG->adminpass;
                        $ldaprdn = $CFG->adminuser . $CFG->account_suffix;
                        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
                        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

                        $bind = ldap_bind($ldap, $ldaprdn, $password);


                        if ($bind) {
                            $filter = "(CN=$username)";
                            $result = ldap_search($ldap, $CFG->ad_base_dn, $filter);
                            ldap_sort($ldap, $result, "sn");
                            $info = ldap_get_entries($ldap, $result);
                            //  for ($i = 0; $i < $info["count"]; $i++) {
                            if ($info['count'] > 0) {
                                $error = false;
                                $insertquery = "INSERT INTO {$CFG->schema}.users (username, name, roleID, type) VALUES(:username, :name, :roleid, :type);";
                                $insertstmt = $conn->prepare($insertquery);
                                $insertstmt->bindValue(':username', $username, PDO::PARAM_STR);
                                $insertstmt->bindValue(':name', $this->strtotitle($info[0]["displayname"][0]), PDO::PARAM_STR);
                                $insertstmt->bindValue(':roleid', $roleID, PDO::PARAM_INT);
                                $insertstmt->bindValue(':type', $type, PDO::PARAM_STR);
                            } else {
                                $error = true;
                                $errordetail .= 'The username ' . $username . ' is not a valid Active Directory 1 user';
                            }
                            //}
                            @ldap_close($ldap);
                        } else {
                            $error = true;
                            $errordetail .= 'Could not bind to Active Directory ' . $CFG->domain_controller;
                        }
                    } catch (Exception $e) {
                        // try second connection
                        $error = true;
                        $errordetail .= "connection to server 1 failed";
                    }

                    // if a second AD source is set, and we had no joy with the first try it
                    if (isset($CFG->domain_controller2) && $error) {
                        try {
                            $error = false;
                            // log on to AD 1 here
                            $ldap = ldap_connect($CFG->domain_controller2) or die('cannot connect to Active Directory 2');
                            $password = $CFG->adminpass2;
                            $ldaprdn = $CFG->adminuser2 . $CFG->account_suffix2;
                            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
                            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

                            $bind = @ldap_bind($ldap, $ldaprdn, $password);
                            if ($bind) {
                                $filter = "(CN=$username)";
                                $result = ldap_search($ldap, $CFG->ad_base_dn2, $filter);
                                ldap_sort($ldap, $result, "sn");
                                $info = ldap_get_entries($ldap, $result);
                                //  for ($i = 0; $i < $info["count"]; $i++) {
                                if ($info['count'] > 0) {
                                    $error = false;
                                    $insertquery = "INSERT INTO {$CFG->schema}.users (username, name, roleID, type) VALUES(:username, :name, :roleid, :type);";
                                    $insertstmt = $conn->prepare($insertquery);
                                    $insertstmt->bindValue(':username', $username, PDO::PARAM_STR);
                                    $insertstmt->bindValue(':name', $this->strtotitle($info[0]["displayname"][0]), PDO::PARAM_STR);
                                    $insertstmt->bindValue(':roleid', $roleID, PDO::PARAM_INT);
                                    $insertstmt->bindValue(':type', $type, PDO::PARAM_STR);
                                } else {
                                    $error = true;
                                    $errordetail .= 'The username ' . $username . ' is not a valid Active Directory 2 user';
                                }
                                //}
                                @ldap_close($ldap);
                            } else {
                                $error = true;
                                $errordetail .= 'Could not bind to Active Directory ' . $CFG->domain_controller2;
                            }
                        } catch (Exception $e) {
                            // try second connection
                            $error = true;
                            $errordetail = "connection to server 2 failed";
                        }
                    }

                    // use cURL to interrogate another (arbitrary) source
                    if ($CFG->usecURL && $error) {
                        $ch = curl_init();

                        // set url
                        curl_setopt($ch, CURLOPT_URL, "{$CFG->cURLaddress}?user={$username}");

                        //return the transfer as a string
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                        // $output contains the output string
                        $output = curl_exec($ch);

                        // close curl resource to free up system resources
                        curl_close($ch);

                        //  print($output);
                        $resultXML = simplexml_load_string($output);

                        if (count($resultXML->error) > 0) {
                            $error = true;
                            $errordetail .= "cURL source had error";
                        } else {
                            $insertquery = "INSERT INTO {$CFG->schema}.users (username, name, roleID, type) VALUES(:username, :name, :roleid, :type);";
                            $insertstmt = $conn->prepare($insertquery);
                            $insertstmt->bindValue(':username', $username, PDO::PARAM_STR);
                            $insertstmt->bindValue(':name', $resultXML->name[0], PDO::PARAM_STR);
                            $insertstmt->bindValue(':roleid', $roleID, PDO::PARAM_INT);
                            $insertstmt->bindValue(':type', $type, PDO::PARAM_STR);
                            //$query = "INSERT INTO {$CFG->schema}.users (username, name, roleID, type) VALUES('$username', '{$resultXML->name[0]}', $roleID, '$type');";
                        }
                    }

                    if ($error) {
                        return( $returnVal . '<data><error>Active Directory error</error><detail>' . $errordetail . '</detail></data>');
                    }

                    break;
//                case 'ldap':
//                    // check if the user exists
//                    $ds = ldap_connect($CFG->ldapserver);
//                    // Try and bind to the server. This will only work if the supplied username and password are correct. @ suppresses errors in this function (the moar you know!)
//                    $r = @ldap_bind($ds);
//                    $filter = "{$CFG->login_field}=$username";
//                    $sr = ldap_search($ds, $CFG->base_dn, $filter);
//                    $info = ldap_get_entries($ds, $sr);
//                    ldap_unbind($ds);
//                    if ($info["count"] > 0) {
//                        $query = "INSERT INTO {$CFG->schema}.users (username, name, roleID, type) VALUES('$username', '{$info[0]["displayname"][0]}', $roleID, '$type');";
//                    } else {
//                        return( '<data><error>invalid username</error><detail>The username ' . $username . ' is not a valid LDAP user</detail></data>');
//                    }
//                    break;
                default:
                    break;
            }

            //  $result = mysql_query($query, $conn) or die('<data><error>insert user query failed</error><detail>' . mysql_error() . $query . '</detail></data>');
            $insertstmt->execute() or die('<data><error>insert user query failed</error><detail>' . $insertstmt->errorCode() . '</detail></data>');
            // if ($result) {
            $returnStr = $conn->lastInsertId();
            // }
            return "<data><id>$returnStr</id></data>";
        } else {
            return( '<data><error>duplicate username</error><detail>The username ' . $username . ' is already in use</detail></data>');
        }
    }

    /**
     * Updates a user's details
     * @global type $CFG
     * @param type $username
     * @param type $password
     * @param type $name
     * @param type $roleID
     * @return XMLStr an XML string detailing the results of the exercise
     */
    public function update_user($userID, $type, $username, $name, $roleID, $password) {

        //print("incoming password is".$password);
        global $CFG;
        // $conn = mysql_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysql_error() . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }

        // check for existing user with that username
        $query = "SELECT count(*) FROM users WHERE ID = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id', $userID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>update_user query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        $rows = $stmt->fetch(PDO::FETCH_NUM);

        if ($rows[0] > 0) {
            $newpass = md5($CFG->password_salt . $password);
            $updatequery = "UPDATE {$CFG->schema}.users SET username = :username, type = :type, " . ((strlen($password) > 1) ? "password = :newpass," : '' ) . " name = :name, roleID = :roleid WHERE users.ID = :userID";
            $updatestmt = $conn->prepare($updatequery);
            $updatestmt->bindValue(':username', $username, PDO::PARAM_STR);
            if (strlen($password) > 1) {
                $updatestmt->bindValue(':newpass', $newpass, PDO::PARAM_STR);
            }
            $updatestmt->bindValue(':name', $name, PDO::PARAM_STR);
            $updatestmt->bindValue(':roleid', $roleID, PDO::PARAM_INT);
            $updatestmt->bindValue(':type', $type, PDO::PARAM_STR);
            $updatestmt->bindValue(':userID', $userID, PDO::PARAM_INT);
            $updatestmt->execute() or die('<data><error>update_user query failed</error><detail>' . $updatestmt->errorCode() . '</detail></data>');
            if ($stmt->rowCount() > 0) {
                $returnStr = 'true';
            } else {
                $returnStr = 'false';
            }
            return "<data><status>$returnStr</status></data>";
        } else {
            return('<data><error>No such user</error><detail>The ID ' . $userID . ' is not valid</detail></data>');
        }
    }

    /**
     * Deletes a user
     * @global type $CFG
     * @param type $userID
     * @return type 
     * TODO delete all other references to this user
     */
    public function delete_user($userID) {
        global $CFG;
        $conn = mysql_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass) or die('<data><error>failed connecting to database</error><detail>' . mysql_error() . '</detail></data>');

        // check for existing user with that username
        $query = "UPDATE {$CFG->schema}.users SET deleted = 'true' WHERE ID = $userID;";
        $result = mysql_query($query, $conn) or die('<data><error>delete user query failed</error><detail>' . mysql_error() . $query . '</detail></data>');

        if (mysql_affected_rows($conn) > 0) {
            $returnStr = 'true';
        } else {
            $returnStr = 'false';
        }
        return "<data><status>$returnStr</status></data>";
    }

    /**
     * Check to see if a user has a capability
     * @global type $CFG
     * @param type $userID
     * @param type $capability
     * @return type
     */
    public function user_has_capability($userID, $capability) {
        global $CFG;
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');

        // check for existing user with that username
        $query = "SELECT b.$capability FROM users a inner join roles b on a.roleID = b.ID WHERE a.ID = $userID";
        $result = mysqli_query($conn, $query) or die('<data><error>user_has_capability query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');


        while ($row = mysqli_fetch_array($result)) {
            $returnStr = $row["$capability"];
        }
//print('<br/>capability here:'.$capability);
        return ($returnStr == 'true');
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Student 
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * make a new student record in the database
     * @global type $CFG
     * @param type $fname
     * @param type $lname
     * @param type $student_num
     * @param type $img_file
     * @return type 
     */
    public function new_student($fname, $lname, $student_num, $email, $cohort, $img_file = null) {
        global $CFG;
        //$conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
// Clean up in coming arguments
//        $args = get_defined_vars();
//        foreach ($args as $var_name => $value) {
//            if (is_string($value)) {
//                ${$var_name} = mysqli_real_escape_string($conn, $value);
//            }
//        }
        $query = "SELECT count(*) FROM {$CFG->schema}.students WHERE studentnum = :student_num;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':student_num', $student_num, PDO::PARAM_STR);
        $stmt->execute() or die('<data><error>new_student query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        $rows = $stmt->fetch(PDO::FETCH_NUM);

        if ($rows[0] < 1) {
            $insertquery = "INSERT INTO {$CFG->schema}.students (fname, lname, studentnum, email, cohort) VALUES(:fname, :lname, :student_num, :email, :cohort);";
            $insertstmt = $conn->prepare($insertquery);
            $insertstmt->bindValue(':fname', $fname, PDO::PARAM_STR);
            $insertstmt->bindValue(':lname', $lname, PDO::PARAM_STR);
            $insertstmt->bindValue(':student_num', $student_num, PDO::PARAM_STR);
            $insertstmt->bindValue(':cohort', $cohort, PDO::PARAM_STR);
            $insertstmt->bindValue(':email', $email, PDO::PARAM_STR);
            $result = $insertstmt->execute() or die('<data><error>new_student query failed</error><detail>' . $updatestmt->errorCode() . '</detail></data>');
            if ($result) {
                $record_id = $conn->lastInsertId();
                // load the image to the image table
                // if (is_uploaded_file($img_file['tmp_name'])) {
                if (isset($img_file)) {
                    $medialib = new MediaLib();
                    if ($medialib->upload_image($record_id, $img_file, false)) {
                        $returnStr = 'true';
                    }
                }
                // }else{
                $returnStr = 'false';
                //}

                return "<data><id>$record_id</id><upload_image_status>$returnStr</upload_image_status><status>true</status></data>";
            }
        } else {
            return( '<data><error>duplicate student</error><detail>The student number ' . $student_num . ' is already in use</detail></data>');
        }
    }

    /**
     * 
     * @global type $CFG
     * @param type $id
     * @param type $fname
     * @param type $lname
     * @param type $student_num
     * @param type $email
     * @param type $cohort
     * @param type $img_file
     * @return type
     */
    public function update_student($id, $fname, $lname, $student_num, $email,  $img_file = null) {
        global $CFG;
        $returnStr = 'false';
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
// Clean up in coming arguments
        //    $updateargs = get_defined_vars();
//        foreach ($updateargs as $var_name => $value) {
//            if (is_string($value)) {
//                ${$var_name} = mysqli_real_escape_string($conn, $value);
//            }
//        }
        //  $query = "SELECT * FROM {$CFG->schema}.students WHERE studentnum = '$student_num';";
        //    $result = mysqli_query($conn, $query) or die('<data><error>check student query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');

        $query = "SELECT * FROM {$CFG->schema}.students WHERE studentnum = :student_num;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':student_num', $student_num, PDO::PARAM_STR);
        $stmt->execute() or die('<data><error>check student for update query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        //$row = mysqli_fetch_assoc($result);
        //  print_r($row);
        if ((count($row) > 0) && ($row['ID'] != $id)) {
            return( '<data><error>duplicate student</error><detail>The student number ' . $student_num . ' is already in use</detail></data>');
        } else {
            $updatequery = "UPDATE {$CFG->schema}.students SET fname = :fname, lname = :lname, studentnum = :student_num, email=:email WHERE ID = :id;";
            $updatestmt = $conn->prepare($updatequery);
            $updatestmt->bindValue(':fname', $fname, PDO::PARAM_STR);
            $updatestmt->bindValue(':lname', $lname, PDO::PARAM_STR);
            $updatestmt->bindValue(':student_num', $student_num, PDO::PARAM_STR);
            $updatestmt->bindValue(':email', $email, PDO::PARAM_STR);
            //$updatestmt->bindValue(':cohort', $cohort, PDO::PARAM_STR);
            $updatestmt->bindValue(':id', $id, PDO::PARAM_STR);
            $updatestmt->execute() or die('<data><error>update student query failed</error><detail>' . $updatestmt->errorCode() . '</detail></data>');
            //  $result = mysqli_query($conn, $query) or die('<data><error>update student query failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
            //     if ($updatestmt-> > 0) {
            // load the image to the image table
            if (isset($img_file)) {
                if (is_uploaded_file($img_file['tmp_name'])) {
                    $medialib = new MediaLib();
                    if ($medialib->upload_image($id, $img_file, true)) {
                        $returnStr = 'true';
                    } else {
                        $returnStr = 'false';
                    }
                }
            } else {

                $returnStr = 'false';
            }
        }
        return "<data><status>true</status><upload_image_status>$returnStr</upload_image_status></data>";
    }

    /**
     * Delete a student by ID
     * @global type $CFG
     * @param type $studentID
     * @return type 
     * TODO make this transaction based so we don't end up with orphan data
     */
    public function delete_student($studentID) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $returnStr = 'false';
        // Delete student entry
        $query = "DELETE FROM students WHERE ID = :studentID;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>delete_student query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        if ($stmt->rowCount() > 0) {
            $medialib = new MediaLib();
            if ($medialib->deleteStudentImage($studentID)) {
                $returnStr = 'true';
            } else {
                $returnStr = 'false';
            }
        } else {
            $returnStr = 'false';
        }
        return "<data><status>$returnStr</status></data>";
    }

    /**
     *  Mass upload students by CSV
     * @global type $CFG
     * @param type $file
     * @return string
     */
    public function upload_csv($file) {
        global $CFG;
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $studentnumrow = 0;
        $fnamerow = 0;
        $lnamerow = 0;
        $emailrow = 0;
        $cohortrow = 0;
        $isfirstrow = true;
        $createsuccesscount = 0;
        $createfailcount = 0;
        $updatesuccesscount = 0;
        $updatefailcount = 0;
        if (is_uploaded_file($file['tmp_name'])) {
            if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
                // get the rows using headers, check for sanity
                while (($data = fgetcsv($handle)) !== FALSE) {
//                    foreach ($data as $key => $value) {
//                        if (is_string($value)) {
//                            $value = mysqli_real_escape_string($conn, $value);
//                        }
//                    }
                    if ($isfirstrow) {
                        if (array_search('studentid', $data) !== false) {
                            $studentnumrow = array_search('studentid', $data);
                        } else {
                            return '<data><error>Field header missing</error><detail>Needs to have a header called studentid</detail></data>';
                        }
                        if (array_search('fname', $data) !== false) {
                            $fnamerow = array_search('fname', $data);
                        } else {
                            return '<data><error>Field header missing</error><detail>Needs to have a header called fname</detail></data>';
                        }if (array_search('surname', $data) !== false) {
                            $lnamerow = array_search('surname', $data);
                        } else {
                            return '<data><error>Field header missing</error><detail>Needs to have a header called surname</detail></data>';
                        }if (array_search('email', $data) !== false) {
                            $emailrow = array_search('email', $data);
                        } else {
                            return '<data><error>Field header missing</error><detail>Needs to have a header called email</detail></data>';
                        }
                        $isfirstrow = false;
                    } else {
                        // we've got the header fields, do something with them
                        // check that this student doesn't already exist...
                        $query = "SELECT count(*) FROM students WHERE studentnum = :studentnum; ";
                        $stmt = $conn->prepare($query);
                        $stmt->bindValue(':studentnum', $data[$studentnumrow], PDO::PARAM_STR);
                        $stmt->execute() or die('<data><error>upload_csv query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
                        $rows = $stmt->fetch(PDO::FETCH_NUM);

                        if ($rows[0] == 0) {
                            $resultXMLStr = $this->new_student($data[$fnamerow], $data[$lnamerow], $data[$studentnumrow], $data[$emailrow], '-1');
                            $resultXML = simplexml_load_string($resultXMLStr);
                            // print_r($resultXML);
                            if ($resultXML->status == 'true') {
                                $createsuccesscount++;
                            } else {
                                $createfailcount++;
                            }
                        } else {
                            $stmt->closeCursor();
                            $query = "SELECT ID FROM students WHERE studentnum = :studentnum; ";
                            $stmt = $conn->prepare($query);
                            $stmt->bindValue(':studentnum', $data[$studentnumrow], PDO::PARAM_STR);
                            $stmt->execute() or die('<data><error>upload_csv query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $recordid = $row['ID'];
                            }
                            //  print("student $recordid exists");
                            // they obviously exist- update them
                            $resultXMLStr = $this->update_student($recordid, $data[$fnamerow], $data[$lnamerow], $data[$studentnumrow], $data[$emailrow], $this->getcohortIDbyValue($data[$cohortrow]));

                            $resultXML = simplexml_load_string($resultXMLStr);
                            // print_r($resultXML);
                            if ($resultXML->status == 'true') {
                                $updatesuccesscount++;
                            } else {
                                $updatefailcount++;
                            }
                        }
                        $stmt->closeCursor();
                    }
                }
                fclose($handle);
            }
        }
        return("<data><createsuccess>$createsuccesscount</createsuccess><createfail>$createfailcount</createfail><updatesuccess>$updatesuccesscount</updatesuccess><updatefail>$updatefailcount</updatefail></data>");
    }

    /**
     * Gets student details from the LDAP server. (Not very efficient in a loop)
     * @global type $CFG
     * @param type $studentnum
     * @return type
     */
    public function getStudentDetailsFromLDAP($studentnum) {
        global $CFG;
        //print_r($CFG);
        $returnStr = "";
        $errordetail = "";
        try {
            $error = false;
            // log on to AD 1 here
            $ldap = ldap_connect($CFG->student_ldap) or die('cannot connect to student directory');
            $ldappassword = $CFG->student_ldap_adminpass;
            $ldaprdn = $CFG->student_ldap_adminuser . $CFG->student_ldap_account_suffix;
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            $bind = ldap_bind($ldap, $ldaprdn, $ldappassword);

            if ($bind) {
                $filter = "({$CFG->student_ldap_searchfield}={$CFG->student_ldap_search_prefix}$studentnum{$CFG->student_ldap_search_suffix})";
                $result = ldap_search($ldap, $CFG->student_ldap_base_dn, $filter);
                //  ldap_sort($ldap, $result, "sn");
                $info = ldap_get_entries($ldap, $result);
                // print_r($info);
                //  for ($i = 0; $i < $info["count"]; $i++) {
                if ($info['count'] > 0) {
                    $error = false;
                    // check student
                    $returnStr = "<fname>{$info[0][$CFG->student_ldap_fname][0]}</fname><lname>{$info[0][$CFG->student_ldap_lname][0]}</lname><email>{$info[0][$CFG->student_ldap_email][0]}</email>";
                } else {
                    $error = true;
                    $errordetail .= 'The student ID ' . $studentnum . ' is not valid';
                }
                //}
                @ldap_close($ldap);
            } else {
                $error = true;
                $errordetail .= 'Could not bind to student LDAP ' . $CFG->student_ldap;
            }
        } catch (Exception $e) {
            // try second connection
            $error = true;
            $errordetail = "connection to student_ldap failed";
        }
        return("<data>" . (strlen($errordetail) > 0 ? "<error><detail>$errordetail</detail></error>" : "") . $returnStr . "</data>");
    }

    /**
     * DEPRECATED- we don't do cohorts anymore
     * @global type $CFG
     * @param type $value
     * @return type
     */
    private function getcohortIDbyValue($value) {
        global $CFG;
        $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        $query = "SELECT ID FROM {$CFG->schema}.cohort_lookup WHERE value = '$value' LIMIT 0,1";
        // print("looking up cohort:$value: using query $query");
        $result = mysqli_query($conn, $query) or die('<data><error>getcohortIDbyValue failed</error><detail>' . mysqli_error($conn) . $query . '</detail></data>');
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
                return $row['ID'];
            }
        } else {
            return -1;
        }
    }

    /**
     * Lock a student- used when assessing
     * @global type $CFG
     * @param type $studentID
     * @return type
     */
    public function lock_student($studentID) {
        global $CFG;
        $returnStr = 'false';
        // $conn = mysqli_connect($CFG->db, $CFG->dbuser, $CFG->dbuserpass, $CFG->schema) or die('<data><error>failed connecting to database</error><detail>' . mysqli_error($conn) . '</detail></data>');
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $query = "UPDATE students SET locked = 1 WHERE ID = :studentID;";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>lock_student query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        if ($stmt->rowCount() > 0) {
            $returnStr = 'true';
        }
        //$assesslib = new AssessmentLib();

        return "<data><status>$returnStr</status></data>";
    }

    /**
     *   Unlocks a student- used when assessing
     * @global type $CFG
     * @param type $studentID
     * @return type
     */
    public function unlock_student($studentID) {
        global $CFG;
        try {
            $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
        } catch (PDOException $e) {
            die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
        }
        $returnStr = 'false';
        // Delete student entry
        $query = "UPDATE students SET locked = 0 WHERE ID = $studentID;";
        $result = mysqli_query($conn, $query) or die('<data><error>lock student query failed</error><detail>' . mysql_error($conn) . $query . '</detail></data>');
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':studentID', $studentID, PDO::PARAM_INT);
        $stmt->execute() or die('<data><error>lock_student query failed</error><detail>' . $stmt->errorCode() . '</detail></data>');
        if ($stmt->rowCount() > 0) {

            $returnStr = 'true';
        }
        return "<data><status>$returnStr</status></data>";
    }

    /**
     * A little helper 
     * @param type $title
     * @return type
     */
    public function strtotitle($title) {
// Converts $title to Title Case, and returns the result.
// Our array of 'small words' which shouldn't be capitalised if
// they aren't the first word. Add your own words to taste.
        $smallwordsarray = array(
            'of', 'a', 'the', 'and', 'an', 'or', 'nor', 'but', 'is', 'if', 'then', 'else', 'when',
            'at', 'from', 'by', 'on', 'off', 'for', 'in', 'out', 'over', 'to', 'into', 'with'
        );

// Split the string into separate words
        $words = explode(" ", $title);

        foreach ($words as $key => $word) {
// If this word is the first, or it's not one of our small words, capitalise it
// with ucwords().
            if ($key == 0 or !in_array($word, $smallwordsarray))
                $words[$key] = ucwords($word);
        }

// Join the words back into a string
        $newtitle = implode(' ', $words);

        return $newtitle;
    }

}

?>

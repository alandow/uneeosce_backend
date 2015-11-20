
<?php

//this is a little experiment. Basically the server I was hosting on could not access the LDAP service, so I made this and hosted it elsewhere
// It takes a username, gets the details from LDAP and returns an XML with the user details.
// Modify it to suit :)

$username = $_REQUEST['user'];


try {
    $error = false;
    $adServer = "ldaps://ldap.someserver.edu.au";

    $ldap = ldap_connect($adServer) or die('cannot connect to ldap');

    $password = "password";

      $ldaprdn = "username";

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    $bind = ldap_bind($ldap, $ldaprdn, $password);

    if ($bind) {
        $filter = "(CN=$username)";
        $result = ldap_search($ldap, "OU=Others,DC=yourorganisation,DC=edu,DC=au", $filter);
        ldap_sort($ldap, $result, "sn");
        $info = ldap_get_entries($ldap, $result);
        if ($info["count"] > 0) {
            print("<data><name>{$info[0]["displayname"][0]}</name></data>");
            exit(); 
        } else {
            $error = true;
            $errordetail = "The username ' . $username . ' is not a valid UoN Active Directory user";
        }
    }
} catch (Exception $e) {
    $error = true;
    // try second connection
    $errordetail = "connection to server 2 failed";
}

if ($error) {
    print( '<data><error>AD error</error><detail>' . $errordetail . '</detail></data>');
    exit();
}

function strtotitle($title) {
// Converts $title to Title Case, and returns the result.
// Our array of 'small words' which shouldn't be capitalised if
// they aren't the first word. Add your own words to taste.
    $smallwordsarray = array(
        'of', 'a', 'the', 'and', 'an', 'or', 'nor', 'but', 'is', 'if', 'then', 'else', 'when',
        'at', 'from', 'by', 'on', 'off', 'for', 'in', 'out', 'over', 'to', 'into', 'with'
    );

// Split the string into separate words
    $words = explode(' ', $title);

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

?>

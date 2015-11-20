<?php
// show all errors. Comment these out on production instances!
ini_set('display_errors', 1);
error_reporting(-1);

// fix an issue where uploading CSV files from mac fails
ini_set("auto_detect_line_endings", true);

date_default_timezone_set('Australia/NSW');

// force HTTPS
if ($_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}


$CFG = new stdClass();

// the system instance name. Displays in a few places
$CFG->sysname = 'UNE eOSCE';

// database parameters
$CFG->db = 'localhost';

$CFG->schema = 'schema';

$CFG->dbuser = 'dbuser';

$CFG->dbuserpass = 'dbuserpass';

// an optional byt highly recommended password hashing salt. Prevents rainbow table side-channel hacking of passwords
$CFG->password_salt = '';

// the authentication token lifespan in seconds. Here we make it a day
$CFG->tokenlifespan = 86400;

// the site www root
$CFG->wwwroot = "https://yoursite/";

// the site base directory
$CFG->basedir = "eOSCE/";
$CFG->site_root = "/var/www/html/eOSCE/";

// the URL of the service file. This should not really change
$CFG->serviceURL = "{$CFG->wwwroot}{$CFG->basedir}backend/service.php";

// LDAP integration
// the login server parameters
$CFG->ldapserver = "ldap.your.server";
// the base DN (Distinguished Name)
$CFG->base_dn = "ou=People,dc=yourorganisation,dc=edu,dc=au";
// teh field containing the username/login
$CFG->login_field = "uid";
// the field containing the user display name. All lower case
$CFG->displayname_field = "displayname";

//Active Directory/LDAPS integration
// the domain controller- IP address or NETBIOS name
$CFG->domain_controller = 'ldap://ad.yourorganisation.edu.au';
// the base DN of the AD
$CFG->ad_base_dn = 'OU=People,DC=ad,DC=yourorganisation,DC=edu,DC=au';
// The full account suffix for your domain. 
$CFG->account_suffix = '@ad.yourorganisation.edu.au';
// a user used to bind to AD. LDAP can bind anonymmously, AD doesn't seem to support that.
$CFG->adminuser = 'binduser';
// password for above
$CFG->adminpass = 'bindpass';

// a secondary domain controller if necessary
//$CFG->domain_controller2 = 'ldaps://ad.yourotherorganisation.edu.au';
//// the base DN of the AD
//$CFG->ad_base_dn2 = 'OU=Others,DC=yourotherorganisation,DC=edu,DC=au';
//// The full account suffix for your domain. 
//$CFG->account_suffix2 = '@something.yourotherorganisation.edu.au';
//// The full account prefix for your domain. 
//$CFG->account_prefix2 = "prefix\\";
//// a user used to bind to AD. LDAP can bind anonymmously, AD doesn't seem to support that
//$CFG->adminuser2 = 'binduser';
//// password for above
//$CFG->adminpass2 = 'bindpass';

// an LDAP lookup for looking up student details.
$CFG->use_ldap_for_student_lookups = true;
$CFG->student_ldap = 'ldap://ad.yourorganisation.edu.au';
// the base DN of the AD for students
$CFG->student_ldap_base_dn = 'OU=Students,DC=yourorganisation,DC=edu,DC=au';
// a user used to bind to AD. LDAP can bind anonymmously, AD doesn't seem to support that
$CFG->student_ldap_adminuser = 'binduser';
// password for above
$CFG->student_ldap_adminpass = 'bindpass';

// search field parameters
$CFG->student_ldap_searchfield = 'studentID';
$CFG->student_ldap_search_prefix = '';
$CFG->student_ldap_search_suffix = '';
// name parameters
$CFG->student_ldap_fname = 'givenname';
$CFG->student_ldap_lname = 'sn';
$CFG->student_ldap_email = 'mail';

// a little hack to talk to some arbitrary authentication system using cURL
// use cURL to contact another page for checking users
$CFG->usecURL = false;
// the cURL address to check
//$CFG->cURLaddress = "curladdress/eOSCE/ad_curl.php";

// media upload/ffmpeg configuration
// the path to the ffmpeg binaries
$CFG->ffmpegpath = '/var/bin/ffmpeg';
$CFG->ffmpegprobepath = '/usr/bin/ffprobe';

// a path to a temporary directory for ffmpeg
$CFG->ffmpegtemppath = "../tmp";

// Show the definition of a string, to aid in editing
$CFG->showstringsource = "false";

// show detailed error messages
$CFG->showdebugerrormessages = true;

// send emails to a specific user only (for debugging or testing)
// TODO take this out of config.inc, make it an in-app configuration
$CFG->sendemailtoonlyoneperson = true;
$CFG->sendemailtoonlyonepersonrecipient = "someone@yourorganisation.edu.au";

// set up whether or not it's a training site. This makes the header a funny colour to differentiate between a real and training site (but that's about it...)
$CFG->istrainingsite = false;

// some CSS for teh printable forms
$CFG->printableformCSS = "<style>
            root { 
                display: block;
            }
            
            body{
                font-family: Verdana,Arial,sans-serif;
            }
          
            h2{
                font-family: Verdana,Arial,sans-serif;
                font-size: 2em; 
            }

                   
            .header {
                border-right:  1px solid #000;
                border-bottom: 1px solid #000;
                background: #CCC;
                font-weight:bold;
                text-align:center;
                
            }

            .formcell{
                text-align: left;
                border-right:  1px solid #000;
                border-bottom: 1px solid #000;
               height:40px;
               padding:0px
            }
#footer {
   position:absolute;
   bottom:0;
   width:100%;
   height:60px;   /* Height of the footer */
   background:#6cf;
}

        </style>";

// Reports parameters
// CSS to apply to reports
$CFG->reportCSS = "<style>
            root { 
                display: block;
            }
            body{
                font-family: Verdana,Arial,sans-serif;
                /*             font-size: 1.1em;    */
            }
            body{
                font-family: Verdana,Arial,sans-serif;
                font-size: 1.1em;    
            }

            h2{
                font-family: Verdana,Arial,sans-serif;
                font-size: 2em; 
            }

            table{
                width: 90%;
                text-align: left;
               
            }
            tr {
                text-align: left;
                border-bottom: 1px solid #000;

            }
            td {
                border-right:  1px solid #000;
                border-bottom: 1px solid #000;

            }
            tr:nth-child(odd) {background: #CCC;}
            tr:nth-child(even) {background: #FFF;}
            th{
                text-align: left;
                border-right:  1px solid #000;
                border-bottom: 1px solid #000;
            }


        </style>";

?>

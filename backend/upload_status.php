<?php
//session_write_close();
session_start();
//header("Content-Type: text/event-stream\n\n");
require_once('config.inc.php');
//global $CFG;
//// Using php server events. See https://developer.mozilla.org/en-US/docs/Server-sent_events/Using_server-sent_events
//
//try {
//    $conn = new PDO("mysql:host={$CFG->db};dbname={$CFG->schema}", $CFG->dbuser, $CFG->dbuserpass);
//} catch (PDOException $e) {
//    die('<data><error>failed connecting to database</error><detail>' . $e->getMessage() . '</detail></data>');
//}

$i = 0;
while (1) {
    print_r($_SESSION);
    echo "$i,";
    ob_flush();
    flush();
    $i++;
    sleep(5);
}
?>

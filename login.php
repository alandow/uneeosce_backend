<?php
include './backend/config.inc.php';
include './backend/lib/authlib.php';

//// check for login
// Are we already logged in? If so, just go to index
//print_r($_COOKIE);
if (isset($_COOKIE['uneeoscetoken'])) {
    setcookie('uneeoscetoken', $_COOKIE['uneeoscetoken'], time() + 86400);
    header("Location: index.php");
    exit();
}

//
//if(isset($_REQUEST['logout'])){
//    session_destroy();
//     session_start();
//}
//
//
$feedbackStr = "";
//
//// are we logging in?
if (isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
    //print_r($_REQUEST);
    $authlib = new authlib();
    //print(strlen($_REQUEST['password']));
    $result = simplexml_load_string($authlib->login($_REQUEST['username'], $_REQUEST['password']));
    //print_r($result);
    if (isset($result->error)) {
        setcookie('uneeoscetoken', "", -3600);
        $feedbackStr = '<p style="color:red">Bad username/password</p>';
        //exit();
    } else {
        print('all good!');
        //print_r($_SESSION);
         setcookie('uneeoscetoken', $result->token, time() + 86400);
         header("Location: index.php");
        die();
    }
}
//else{
//    print('nothing');
//}
//
////print_r($_SESSION);
//
//
?>

<!DOCTYPE html 
    PUBLIC "-//W3C//DTD HTML 4.01//EN"
    "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en-US">
    <head profile="http://www.w3.org/2005/10/profile"><link rel="icon" type="image/png" href="./favicon.png">
        <script src="js/jquery-2.0.3.min.js"></script>
        <script src="js/jquery-ui.min.js"></script>
        <link type="text/css" href="css/jquery-ui.css" rel="stylesheet" />
        <?php
        if ($CFG->istrainingsite) {
            ?>
            <style>
                .ui-widget-header{
                    background: #C24641;
                }
            </style>
            <?php
        }
        ?>
        <script>
            var a = document.getElementsByTagName("a");
            for (var i = 0; i < a.length; i++) {
                if (!a[i].onclick && a[i].getAttribute("target") != "_blank") {
                    a[i].onclick = function () {
                        window.location = this.getAttribute("href");
                        return false;
                    }
                }
            }
            $(function () {
                $("input[type='submit']").button();
                $('#login').dialog({modal: true,
                    autoOpen: true,
                    closeOnEscape: false,
                    width: 400,
                    open: function (event, ui) {
                        $(".ui-dialog-titlebar-close", $(this).parent()).hide();
                        $(".ui-dialog").position({
                            my: "top",
                            at: "center top+15%",
                            of: window
                        });
                    }
                });


            })</script>  
    <body>

        <div id='login' title='login to <?php print($CFG->sysname) ?>' style="">
            <?php print $feedbackStr; ?>
            <form method="POST" action="login.php">
                <div style="display: table; ">
                    <div style="display: table-row; height: 40px; vertical-align: middle; padding-right: 10px">
                        <div style="display: table-cell; width: 100px"><label for="username">Username:</label></div><div style="display: table-cell"><input type="text" name="username" autocorrect="off" autocapitalize="off"></div>
                    </div>
                    <div style="display: table-row; height: 40px; vertical-align: middle; padding-right: 10px">
                        <div style="display: table-cell; width: 100px"> <label for="password">Password:</label></div><div style="display: table-cell"><input type="password" name="password"></div>
                    </div>
                </div> 
                <input type="submit" value="Sign in" style="display: ">
            </form>
        </div>

    <input type='image' src='./icons/gtk-dialog-question48.png' BORDER='0' style='position: relative; z-index: 100000' onclick='window.open("https://corvid.une.edu.au/eOSCEWiki/doku.php?id=logon", "_blank");
                    return false;'/>

</body>
</html>

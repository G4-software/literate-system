<?php
    require_once __DIR__."/config.php";
    require_once __DIR__."/database/db_connection.php";

    if(isset($_COOKIE['ls-username']) && isset($_COOKIE['ls-shown_username']) && isset($_COOKIE['ls-login_stamp']))
    {
        $username = $_COOKIE['ls-username'];
        $shown_username = $_COOKIE['ls-shown_username'];
        $login_stamp = $_COOKIE['ls-login_stamp'];
        $db_query = $db->prepare("SELECT `login_stamp` FROM `users` WHERE `username` = :username");
        $db_query->bindParam(":username", $username);
        $db_query->execute();
        $result = $db_query->fetchColumn();
        if($result != $login_stamp)
        {
            setcookie("ls-username", "", time()-60*60*24);
            setcookie("ls-login_stamp", "", time()-60*60*24);
            echo "cookies deleted";
            //header("Location: login.php");
        }
        define("USER_LOGGED_IN", TRUE);
        define("USERNAME", $username);
        define("SHOWN_USERNAME", $shown_username);
        echo "user logged in<br />".USER_LOGGED_IN;
    }
    else
    {
        define("USER_LOGGED_IN", FALSE);
        echo "user not logged in";
    }

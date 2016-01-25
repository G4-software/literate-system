<?php
    require "database/db_connection.php";

    if(isset($_COOKIE['ls-username']) && isset($_COOKIE['ls-logged_in']))
    {
        $username = $_COOKIE['ls-username'];
        $stamp = $_COOKIE['ls-logged_in'];
        $db_query = $db->prepare("SELECT `login_stamp` FROM `users` WHERE `username` = :username");
        $db_query->bindParam(":username", $username);
        $db_query->execute();
        $result = $db_query->fetchColumn();
        if($result != $stamp)
        {
            setcookie("ls-username", "", time()-60*60*24);
            setcookie("ls-logged_in", "", time()-60*60*24);
            header("Location: login.php");
        }
        echo "Log in complete";
    }
    else
    {
        readfile("login.html");
        echo "Cookies not set";
    }
?>

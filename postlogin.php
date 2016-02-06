<?php
    require_once __DIR__."/config.php";
    require_once __DIR__."/database/db_connection.php";

    if(isset($_COOKIE['ls-username']) && isset($_COOKIE['ls-shown_username']) && isset($_COOKIE['ls-login_stamp']))
    {
        $username = $_COOKIE['ls-username'];
        $shown_username = $_COOKIE['ls-shown_username'];
        $login_stamp = $_COOKIE['ls-login_stamp'];
        $db_query = $db->prepare("SELECT `user_id`, `login_stamp` FROM `users` WHERE `username` = :username");
        $db_query->bindParam(":username", $username);
        $db_query->execute();
        $result = $db_query->fetch(PDO::FETCH_ASSOC);
        if($result['login_stamp'] != $login_stamp)
        {
            setcookie("ls-username", "", time()-60*60*24);
            setcookie("ls-login_stamp", "", time()-60*60*24);
            define("USER_LOGGED_IN", FALSE);
        }
        else{
            define("USER_LOGGED_IN", TRUE);
            define("USER_ID", $result['user_id']);
            define("USERNAME", $username);
            define("SHOWN_USERNAME", $shown_username);

            $user['logged_in'] = 1;
            $user['name'] = SHOWN_USERNAME;

            if(isset($_GET['location']))
            {
                header("Location: ".urldecode($_GET['location']));
            }
        }
    }
    else
    {
        define("USER_LOGGED_IN", FALSE);
    }

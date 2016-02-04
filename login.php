<?php
    require_once __DIR__."/config.php";
    require_once __DIR__."/database/db_connection.php";
    require_once __DIR__."/postlogin.php";
    require_once __DIR__."/twig_config.php";

    $page = array(  'title' => "Login",
                    'page_title' => "Login",
                    'blocks' => array(  'login' => array(   'type' => 'form',
                                                            'script' => "login.php",
                                        					'method' => "POST",
                                        					'inputs' => array(	'username' => array(	'label' => "Username:",
                                        																'type' => "text",
                                        																'name' => "username"),
                                        										'password' => array(	'label' => "Password:",
                                        																'type' => "password",
                                        																'name' => "password"),),
                                                            'submit_button_text' => "Login")));

    if(USER_LOGGED_IN)
    {
        header("Location: index.php");
    }
    elseif(empty($_POST))	//Escape if no data sent
    {
        echo $twig->render("template.html", $page);
        die();
    }

//Get data from POST query
    $data['username'] = strtoupper(trim($_POST['username']));
    $data['shown_username'] = trim($_POST['username']);
    $data['pass_hash'] = md5($_POST['password']);

//Check if user exists
    $db_query = $db->prepare("SELECT `username` FROM `users` WHERE `username` = :username");
    $db_query->bindParam(":username", $data['username']);
    $db_query->execute();
    $result = $db_query->fetchColumn();
    //echo "$result<br>";
    if($result != $data['username'])
    {
        echo $twig->render("template.html", $page);
        die("User does not exist");
    }

//Check if password matches
    $db_query = $db->prepare("SELECT `password_hash` FROM `users` WHERE `username` = :username");
    $db_query->bindParam(":username", $data['username']);
    $db_query->execute();
    $result = $db_query->fetchColumn();
    //echo "$result<br>";
    if($result != $data['pass_hash'])
    {
        echo $twig->render("template.html", $page);
        die("Wrong password");
    }

//Login
    $logged_in = time();
    //echo "$logged_in<br>";
    $stamp = md5($data['username'] . rand(0,1024)+time()%1024);
    //echo "$stamp<br>";
    $db_query = $db->prepare("UPDATE `users` SET `logged_in` = :logged_in, `login_stamp` = :stamp WHERE `users`.`username` = :username;");
    $db_query->bindParam(":username", $data['username']);
    $db_query->bindParam(":logged_in", $logged_in);
    $db_query->bindParam(":stamp", $stamp);
    $db_query->execute();
    setcookie("ls-username", $data['username'], time()+60*60*24);
    setcookie("ls-shown_username", $data['shown_username'], time()+60*60*24);
    setcookie("ls-login_stamp", $stamp, time()+60*60*24);
    header("Location: postlogin.php?location=".urlencode("index.php"));

<?php
    require_once __DIR__."/../../config.php";
    require_once __DIR__."/../../database/db_connection.php";
    require_once __DIR__."/../../postlogin.php";
    require_once __DIR__."/../../twig_config.php";

//Redirect to homepage if no GET query present
    if((empty($_POST) && isset($_GET['team']) && isset($_GET['stamp'])) || (empty($_GET) && !empty($_POST['team']) && !empty($_POST['stamp']) && count(array_filter($_POST))<7))
    {
        if(empty($_POST))
        {
            $team = html_entity_decode($_GET['team']);
            $stamp = html_entity_decode($_GET['stamp']);
        }
        else
        {
            $team = html_entity_decode($_POST['team']);
            $stamp = html_entity_decode($_POST['stamp']);
        }

        //Checking invitation
        $db_query = $db->prepare("SELECT `team`, `is_used`, `expire_on` FROM `invitations` WHERE `stamp` = :stamp;");
    	$db_query->bindParam(':stamp', $stamp);
    	$db_query->execute();
    	$result = $db_query->fetch(PDO::FETCH_ASSOC);
        if(($result['team'] != $team) || ($result['is_used'] == 1) || ($result['expire_on'] <= time()))
        {
            header("Location: ../../index.php");
        }

        $db_query = $db->prepare("SELECT `shown_team_name` FROM `teams` WHERE `team_id` = :team_id;");
        $db_query->bindParam(':team_id', $team);
        $db_query->execute();
        $team_name = $db_query->fetchColumn();

        $db_query = $db->prepare("SELECT `issued_by` FROM `invitations` WHERE `stamp` = :stamp;");
        $db_query->bindParam(':stamp', $stamp);
        $db_query->execute();
        $invited_by = $db_query->fetchColumn();

        $db_query = $db->prepare("SELECT `shown_username` FROM `users` WHERE `user_id` = :user_id;");
        $db_query->bindParam(':user_id', $invited_by);
        $db_query->execute();
        $invited_by_name = $db_query->fetchColumn();

        $inputs = array(    'username' => array(	'label' => "Username:",
                                                    'type' => "text",
                                                    'name' => "username"),
                            'password' => array(	'label' => "Password:",
                                                    'type' => "password",
                                                    'name' => "password"),
                            'password_confirmation' => array(	'label' => "Enter again:",
                                                                'type' => "password",
                                                                'name' => "password_confirmation"),
                            'name' => array(	'label' => "Your name:",
                                                'type' => "text",
                                                'name' => "name"),
                            'email' => array(   'label' => "Your email:",
                                                'type' => "email",
                                                'name' => "email"),
                            'invited_by_name' => array( 'label' => "You are invited by",
                                                        'type' => "text",
                                                        'name' => "invited_by_name",
                                                        'args' => "readonly value=$invited_by_name"),
                            'team_name' => array(   'label' => "to team",
                                                    'type' => "text",
                                                    'name' => "team_name",
                                                    'args' => "readonly value=$team_name"),
                            'team' => array(    'label' => "",
                                                'type' => "text",
                                                'name' => "team_id",
                                                'args' => "hidden value=$team"),
                            'stamp' => array(   'label' => "",
                                                'type' => "text",
                                                'name' => "stamp",
                                                'args' => "hidden value='$stamp'"));
        $form = array(  'type' => "form",
                        'script' => "register.php",
                        'method' => "POST",
                        'inputs' => $inputs,
                        'submit_button_text' => "Register");
        $page = array(  'title' => "Register by invite",
                        'page_title' => "Register",
                        'blocks' => array( 'register' => $form));

        echo $twig->render("template.html", $page);
        die();
    }
    else
    {
        header("Location: ../../../index.php");
    }

//Get data from POST query
	$data['username'] = strtoupper(trim($_POST['username']));
	$data['shown_username'] = trim($_POST['username']);
	$data['password'] = $_POST['password'];
	$data['pass_hash'] = md5($_POST['password']);
	$data['name'] = trim($_POST['name']);
    $data['email'] = $_POST['email'];
    $stamp = $_POST['stamp'];
    $team_id = $_POST['team_id'];

//Checking invitation
    $db_query = $db->prepare("SELECT `team_id`, `is_used`, `expire_on`, `issued_by` FROM `invitations` WHERE `stamp` = :stamp;");
    $db_query->bindParam(':stamp', $stamp);
    $db_query->execute();
    $result = $db_query->fetch(PDO::FETCH_ASSOC);
    if(($result['team_id'] != $team_id) || ($result['is_used'] == 1) || ($result['expire_on'] <= time()))
    {
        header("Location: ../../index.php");
    }
    $data['invited_by'] = $result['invited_by'];

//Test if passwords match and length is OK
	$pass_hash_alt = md5($_POST['password_confirmation']);
	if($data['pass_hash'] != $pass_hash_alt)
	{
		//Error
	}
	if(strlen($data['password'])<8)
	{
		//Error
		die("Password is too short (<8 symbols)");
	}

//Test if the email is valid
	$email_regex = '/^[^0-9][_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
	if (!preg_match($email_regex, $data['email']))
	{
		//Error
		die("Email's not valid");
	}

//Test if user already is in DB
	$db_query = $db->prepare("SELECT `username` FROM `users` WHERE `username` = :username;");
	$db_query->bindParam(':username', $data['username']);
	$db_query->execute();
	$result = $db_query->fetchColumn();
	if($result == $data['username'])
	{
		//Error
		die("The user '{$data['username']}' is alredy registered.");
	}

//Register user
	$db_query = $db->prepare("INSERT INTO `users` (`user_id`, `username`, `shown_username`, `password_hash`, `password`, `name`, `email`, `logged_in`, `login_stamp`, `invited_by`)
                                            VALUES (NULL, :username, :shown_username, :pass_hash, :password, :name, :email, NULL, NULL, :invited_by);");
	$db_query->execute($data);
	$db_query = $db->prepare("UPDATE `invitations` SET `is_used` = '1' WHERE `invitations`.`stamp` = :stamp;");
    $db_query->bindParam(':stamp', $data['stamp']);
    $db_query->execute();

    $db_query = $db->prepare("SELECT `user_id` FROM `users` WHERE `username` = :username;");
    $db_query->bindParam(':username', $data['username']);
    $db_query->execute();
    $result = $db_query->fetchColumn();
    $user_id = $result;

    $db_query = $db->prepare("SELECT `team_members` FROM `teams` WHERE `team_id` = :team_id;");
    $db_query->bindParam(':team_id', $team_id);
    $db_query->execute();
    $result = $db_query->fetchColumn();
    $team_members = unserialize($result);
    $team_members[] = $user_id;

    $db_query = $db->prepare("UPDATE `teams` SET `team_members` = :team_members WHERE `teams`.`team_id` = :team_id");
    $db_query->bindParam(':team_members', serialize($team_members));
    $db_query->bindParam('team_id', $team_id);
    $db_query->execute();

	header("Location: ../../login.php");

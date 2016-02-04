<?php
	require_once __DIR__."/config.php";
	require_once __DIR__."/database/db_connection.php";
	require_once __DIR__."/postlogin.php";
	require_once __DIR__."/twig_config.php";

	$page = array(	'title' => "Register",
					'page_title' => "Register",
					'blocks' => array(  'register' => array(   	'type' => 'form',
																'script' => "register.php",
																'method' => "POST",
																'inputs' => array(	'username' => array(	'label' => "Username:",
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
																					'email' => array(	'label' => "Your email:",
																										'type' => "email",
																										'name' => "email")),
																'submit_button_text' => "Register")));

	if(USER_LOGGED_IN)
	{
		header("Location: index.php");
	}
	elseif(empty($_POST))	//Escape if no data sent
	{
		echo $twig->render("template.html", $page);
		die();
	}

//Test if any variable is empty
	if(count(array_filter($_POST))<5)
	{
		echo $twig->render("template.html", $page);
		die("$exit_code not present, retry");
	}

//Get data from POST query
	$data['username'] = strtoupper(trim($_POST['username']));
	$data['shown_username'] = trim($_POST['username']);
	$data['password'] = $_POST['password'];
	$data['pass_hash'] = md5($_POST['password']);
	$data['name'] = trim($_POST['name']);
	$data['email'] = strtolower(trim($_POST['email']));

//Test if passwords match and length is OK
	$pass_hash_alt = md5($_POST['password_confirmation']);
	if($data['pass_hash'] != $pass_hash_alt)
	{
		echo $twig->render("template.html", $page);
		die("Passwords not match");
	}
	if(strlen($data['password'])<8)
	{
		echo $twig->render("template.html", $page);
		die("Password is too short (<8 symbols)");
	}

//Test if the email is valid
	$email_regex = '/^[^0-9][_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
	if (!preg_match($email_regex, $data['email']))
	{
		echo $twig->render("template.html", $page);
		die("Email's not valid");
	}

//Test if user or email already are in DB
	$db_query = $db->prepare("SELECT `username` FROM `users` WHERE `username` = :username;");
	$db_query->bindParam(':username', $data['username']);
	$db_query->execute();
	$result = $db_query->fetchColumn();
	if($result == $data['username'])
	{
		echo $twig->render("template.html", $page);
		die("The user '{$data['username']}' is alredy registered.");
	}
	$db_query = $db->prepare("SELECT `user_id` FROM `users` WHERE `email` = :email;");
	$db_query->bindParam(':email', $data['email']);
	$db_query->execute();
	$result = $db_query->fetchColumn();
	if($result == $data['email'])
	{
		echo $twig->render("template.html", $page);
		die("The user with email '{$data['email']}' is alredy registered.");
	}

//Register user
	$db_query = $db->prepare("INSERT INTO `users` (`user_id`, `username`, `shown_username`, `password_hash`, `password`, `name`, `email`, `logged_in`, `login_stamp`, `invited_by`) VALUES (NULL, :username, :shown_username, :pass_hash, :password, :name, :email, NULL, NULL, NULL);");
	$db_query->execute((array)$data);
	$now = date('YmdHis');

	header("Location: login.php");

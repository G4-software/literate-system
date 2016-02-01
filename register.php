<?php
	require_once __DIR__."/config.php";
	require_once __DIR__."/database/db_connection.php";
	require_once __DIR__."/postlogin.php";

	if(USER_LOGGED_IN)
	{
		header("Location: index.php");
	}

	if(empty($_POST))	//Escape if no data sent
	{
		header("Location: register.html");
	}

    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 01 Jan 1980 05:00:00 GMT");

//Get data from POST query
	$data['username'] = strtoupper(trim($_POST['username']));
	$data['shown_username'] = trim($_POST['username']);
	$data['password'] = $_POST['password'];
	$data['pass_hash'] = md5($_POST['password']);	//I do not work with passwords "as is"
	$data['name'] = trim($_POST['name']);
	$data['email'] = strtolower(trim($_POST['email']));

//Trying to get image from form
	$upload_dir = "/files/profile_pictures/";
	$image_fieldname = "userpic";
	$php_errors = array("File's too large (server config)",
						"File's too large (HTML form)",
						"Only part of file was sent",
						"File was not attached");
	if(($_FILES[$image_fieldname]['error'] != 0) && ($_FILES[$image_fieldname]['error'] != 4))
	{
		readfile('register.html');
		die($php_errors[$_FILES[$image_fieldname]['error']-1]);
	}
	if((!is_uploaded_file($_FILES[$image_fieldname]['tmp_name'])) && ($_FILES[$image_fieldname]['error'] != 4))
	{
		readfile('register.html');
		die("You tried to violate access. Don't try do do it anymore.");
	}
	if((!@getimagesize($_FILES[$image_fieldname]['tmp_name'])) && ($_FILES[$image_fieldname]['error'] != 4))
	{
        readfile("register.html");
        die("The file you sent is not an image.");
    }

//Test if any variable is empty
	$exit_flag = false;
	$exit_code = "";
	foreach ($data as $key => $value)
	{
		if(empty($value))
		{
			$exit_code .= $key." ";
			$exit_flag = true;
		}
	}
	if($exit_flag)
	{
		readfile("register.html");
		die("$exit_code not present, retry");
	}

//Test if passwords match and length is OK
	$pass_hash_alt = md5($_POST['password_confirmation']);
	if($data['pass_hash'] != $pass_hash_alt)
	{
		readfile("register.html");
		die("Passwords not match");
	}
	if(strlen($data['password'])<8)
	{
		readfile("register.html");
		die("Password is too short (<8 symbols)");
	}

//Test if the email is valid
	$email_regex = '/^[^0-9][_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
	if (!preg_match($email_regex, $data['email']))
	{
		readfile("register.html");
		die("Email's not valid");
	}

//Test if user or email already are in DB
	$db_query = $db->prepare("SELECT `username` FROM `users` WHERE `username` = :username;");
	$db_query->bindParam(':username', $data['username']);
	$db_query->execute();
	$result = $db_query->fetchColumn();
	if($result == $data['username'])
	{
		readfile("register.html");
		die("The user '{$data['username']}' is alredy registered.");
	}
	$db_query = $db->prepare("SELECT `id` FROM `users` WHERE `email` = :email;");
	$db_query->bindParam(':email', $data['email']);
	$db_query->execute();
	$result = $db_query->fetchColumn();
	if($result == $data['email'])
	{
		readfile("register.html");
		die("The user with email '{$data['email']}' is alredy registered.");
	}

//Register user
	$db_query = $db->prepare("INSERT INTO `users` (`user_id`, `username`, `shown_username`, `password_hash`, `password`, `name`, `email`, `logged_in`, `login_stamp`, `invited_by`) VALUES (NULL, :username, :shown_username, :pass_hash, :password, :name, :email, NULL, NULL, NULL);");
	$db_query->execute((array)$data);
	$now = date('YmdHis');
	$userpic_location = "/userpics/";
	@move_uploaded_file($_FILES[$image_fieldname]['tmp_name'], "$userpic_location{$data['username']}_$now");
	header("Location: login.php");

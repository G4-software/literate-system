<?php
	require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/database/db_connection.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/postlogin.php";
	require_once $_SERVER['DOCUMENT_ROOT']."/twig_config.php";

	$register_form = array( 'type' => 'form',
							'script' => "register.php",
							'method' => "POST",
							'inputs' => array(	'username' => array(	'label' => "Имя пользователя:",
																		'type' => "text",
																		'name' => "username"),
												'password' => array(	'label' => "Пароль:",
																		'type' => "password",
																		'name' => "password"),
												'password_confirmation' => array(	'label' => "Повторите ввод:",
																					'type' => "password",
																					'name' => "password_confirmation"),
												'name' => array(	'label' => "Ваше имя:",
																	'type' => "text",
																	'name' => "name"),
												'email' => array(	'label' => "Ваш email:",
																	'type' => "email",
																	'name' => "email")),
							'submit_button_text' => "Зарегистрироваться!");

	$page = array(	'title' => "Регистрация",
					'page_title' => "Регистрация",
					'site' => $site,
					'blocks' => array(  'register' => $register_form));

	if(USER_LOGGED_IN)
	{
		header("Location: {$site['root']}/index.php");
	}
	elseif(empty($_POST))	//Escape if no data sent
	{
		echo $twig->render("template.html", $page);
		die();
	}

//Test if any variable is empty
	if(count(array_filter($_POST))<5)
	{
		$error = array( 'type' => "error",
						'summary' => "Мало данных",
						'content' => "Все поля обязательны для заполнения");
		$page['blocks'] = array('error' => $error,
								'register' => $register_form);
		echo $twig->render("template.html", $page);
		die();
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
		$error = array( 'type' => "error",
						'summary' => "Пароли не совпали",
						'content' => "Повторите ввод, обратив внимание на пароли");
		$page['blocks'] = array('error' => $error,
								'register' => $register_form);
		echo $twig->render("template.html", $page);
		die();
	}
	if(strlen($data['password'])<8)
	{
		$error = array( 'type' => "error",
						'summary' => "Пароль слишком короткий",
						'content' => "Для вашей же безопасности минимальная длина пароля - 8 символов");
		$page['blocks'] = array('error' => $error,
								'register' => $register_form);
		echo $twig->render("template.html", $page);
		die();
	}

//Test if the email is valid
	$email_regex = '/^[^0-9][_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
	if (!preg_match($email_regex, $data['email']))
	{
		$error = array( 'type' => "error",
						'summary' => "Неправильный email",
						'content' => "Адрес, который вы ввели, недействителен");
		$page['blocks'] = array('error' => $error,
								'register' => $register_form);
		echo $twig->render("template.html", $page);
		die();
	}

//Test if user or email already are in DB
	$db_query = $db->prepare("SELECT `username` FROM `users` WHERE `username` = :username;");
	$db_query->bindParam(':username', $data['username']);
	$db_query->execute();
	$result = $db_query->fetchColumn();
	if($result == $data['username'])
	{
		$error = array( 'type' => "error",
						'summary' => "Пользователь уже зарегистрирован",
						'content' => "Пользователь {$data['username']} уже существует. Пожалуйста, войдите в аккаунт или придумайте другое имя пользователя");
		$page['blocks'] = array('error' => $error,
								'register' => $register_form);
		echo $twig->render("template.html", $page);
	}
	$db_query = $db->prepare("SELECT `user_id` FROM `users` WHERE `email` = :email;");
	$db_query->bindParam(':email', $data['email']);
	$db_query->execute();
	$result = $db_query->fetchColumn();
	if($result == $data['email'])
	{
		$error = array( 'type' => "error",
						'summary' => "Пользователь с таким email уже существует",
						'content' => "Пользователь с email {$data['email']} уже есть. Пожалуйста, войдите в аккаунт или используйте другой почтовый аккаунт");
		$page['blocks'] = array('error' => $error,
								'register' => $register_form);
		echo $twig->render("template.html", $page);
	}

//Register user
	$db_query = $db->prepare("INSERT INTO `users` (`user_id`, `username`, `shown_username`, `password_hash`, `password`, `name`, `email`, `logged_in`, `login_stamp`, `invited_by`) VALUES (NULL, :username, :shown_username, :pass_hash, :password, :name, :email, NULL, NULL, NULL);");
	$db_query->execute((array)$data);
	$now = date('YmdHis');

	header("Location: {$site['root']}/login.php");

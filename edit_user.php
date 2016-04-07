<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/database/db_connection.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/postlogin.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/twig_config.php";

    if(!USER_LOGGED_IN)
    {
        header("Location: {$site['root']}/login.php");
    }

    $db_query = $db->prepare("SELECT `name`, `email`, `password_hash` FROM `users` WHERE `user_id` = :user_id");
    $user_id = USER_ID;
    $db_query->bindParam(':user_id', $user_id);
    $db_query->execute();
    $result = $db_query->fetch(PDO::FETCH_ASSOC);

    $input_data['name'] = $result['name'];
    $input_data['email'] = $result['email'];
    $input_data['password'] = "password";

    $inputs = array(    'username' => array(	'label' => "Имя пользователя:",
                                                'type' => "text",
                                                'name' => "shown_username",
                                                'args' => "readonly value=".SHOWN_USERNAME),
                        'password' => array(	'label' => "Пароль:",
                                                'type' => "password",
                                                'name' => "password",
                                                'args' => "value=\"password\""),
                        'password_confirmation' => array(	'label' => "Повторите ввод:",
                                                            'type' => "password",
                                                            'name' => "password_confirmation",
                                                            'args' => "value=\"password\""),
                        'name' => array(	'label' => "Ваше имя:",
                                            'type' => "text",
                                            'name' => "name",
                                            'args' => "value=\"{$result['name']}\""),
                        'email' => array(   'label' => "Ваш email:",
                                            'type' => "email",
                                            'name' => "email",
                                            'args' => "value=\"{$result['email']}\""));
    $form = array(  'type' => "form",
                    'script' => "edit_user.php",
                    'method' => "POST",
                    'inputs' => $inputs,
                    'submit_button_text' => "Обновить данные");
    $text = array(  'type' => "text_html",
                    'content' => "<p class='text'>Тут вы можете обновить какие-то данные о себе. Измените значение поля для этого.</p>");
    $blocks = array('text' => $text,
                    'update_form' => $form);
    $page = array(  'title' => "Изменение профиля",
                    'page_title' => "Изменение профиля",
                    'menu' => array('teams' => $menu_teams),
                    'site' => $site,
                    'user' => $user,
                    'blocks' => $blocks);

    if(empty($_POST))
    {
        echo $twig->render("template.html", $page);
        die();
    }

	$data['password'] = $_POST['password'];
	$data['name'] = trim($_POST['name']);
	$data['email'] = strtolower(trim($_POST['email']));

    foreach($input_data as $key => $input_data_value)
    {
        if($data[$key] != $input_data_value)
            $diff[$key] = 1;
    }

    if(empty($diff))
    {
        echo $twig->render("template.html", $page);
        die();
    }

    $text['content'] = "";

    foreach ($diff as $key => $value) {
        if($key == "password")
        {
            if($_POST['password'] != $_POST['password_confirmation'])
            {
                echo $twig->render("template.html", $page);
                die();
            }
            $data['password_hash'] = md5($data['password']);

            $db_query = $db->prepare("UPDATE `users` SET `password` = :password, `password_hash` = :password_hash WHERE `users`.`user_id` = :user_id;");
            $db_query->bindParam(':password', $data['password']);
            $db_query->bindParam(':password_hash', md5($data['password']));
            $db_query->bindParam(':user_id', $user_id);
            $db_query->execute();
        }
        elseif($key == "email")
        {
            $email_regex = '/^[^0-9][_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
        	if(!preg_match($email_regex, $data['email']))
        	{
                $page['blocks']['error'] = array(   'type' => "error",
                                                    'summary' => "Неправильный email",
                                                    'content' => "Адрес, который вы ввели, недействителен");
        		echo $twig->render("template.html", $page);
        		die();
        	}

            $db_query = $db->prepare("UPDATE `users` SET `email` = :email WHERE `users`.`user_id` = :user_id");
            $db_query->bindParam(':email', $data['email']);
            $db_query->bindParam(':user_id', $user_id);
            $db_query->execute();
        }
        elseif($key == "name")
        {
            $db_query = $db->prepare("UPDATE `users` SET `name` = :name WHERE `users`.`user_id` = :user_id");
            $db_query->bindParam(':name', $data['name']);
            $db_query->bindParam(':user_id', $user_id);
            $db_query->execute();
        }

        $text['content'] = $text['content']."<p class='success'>$key обновлен!</p>";
        $blocks = array('text' => $text);
        $page['title'] = "Успех";
        $page['page_title'] = "Профиль обновлен";
        $page['blocks'] = $blocks;
    }

    echo $twig->render("template.html", $page);

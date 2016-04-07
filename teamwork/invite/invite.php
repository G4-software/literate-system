<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/database/db_connection.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/postlogin.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/twig_config.php";

    if(!USER_LOGGED_IN)
    {
        header("Location: {$site['root']}/login.php");
    }

    $db_query = $db->prepare("SELECT `team_id`, `team_name`, `team_managers` FROM `teams`");
    $db_query->execute();
    $teams_raw = $db_query->fetchAll(PDO::FETCH_ASSOC);
    $user_id = USER_ID;
    $teams;
    foreach ($teams_raw as $team)
    {
        $team_managers = unserialize($team['team_managers']);
        if(isset($team_managers))
        {
            if(in_array($user_id, $team_managers))
            {
                $temp = array(  'value' => $team['team_id'],
                                'label' => $team['team_name']);
                $teams[] = $temp;
            }
        }
    }

    for($var = 5; $var <= 60; $var+=5)
    {
        $temp = array(  'value' => $var,
                        'label' => "$var days");
        $expire_periods[] = $temp;
    }

    if(isset($teams))
    {
        $inputs = array(    'team' => array(    'label' => "Выберите команду:",
                                                'type' => "list",
                                                'name' => "team",
                                                'options' => $teams),
                            'expire' => array(	'label' => "Истекает:",
                                                'type' => "list",
                                                'name' => "expire_period",
                                                'options' => $expire_periods));
        $form = array(  'type' => "form",
                        'script' => "invite.php",
                        'method' => "POST",
                        'inputs' => $inputs,
                        'submit_button_text' => "Получить ссылку!");
        $blocks = array('invite_form' => $form);
        $page = array(  'title' => "Приглашения",
                        'page_title' => "Укажите, чтобы создать приглашение:",
                        'menu' => array('teams' => $menu_teams),
                        'site' => $site,
                        'user' => $user,
                        'blocks' => $blocks);
    }
    else
    {
        $page = array(  'title' => "Приглашения",
                        'page_title' => "Что-то не так",
                        'menu' => array('teams' => $menu_teams),
                        'site' => $site,
                        'user' => $user,
                        'blocks' => array(  'text' => array('type' => "text",
                                                            'content' => "Видимо, пока вы не являетесь управляющим ни одной из групп и не можете создавать приглашения. :(")));
    }

    if(empty($_POST))
    {
        echo $twig->render("template.html", $page);
        die();
    }

    if(count($_POST)<2)
    {
        array_unshift($page['blocks'], array(   'type' => "error",
                                                'summary' => "Повторите ввод",
                                                'content' => "Почему-то форма передала не все аргументы, если эта ошибка повторится, свяжитесь с нами"));
        echo $twig->render("template.html", $page);
        die();
    }

    $data['expire_period'] = $_POST['expire_period'];
    $data['team_id'] = $_POST['team'];

    $db_query = $db->prepare("INSERT INTO `invitations` (`team`, `is_used`, `expire_on`, `issued_by`, `stamp`)
                                                 VALUES (:team_id, 0, :expire_on, :issued_by, :stamp);");
    $db_query->bindParam(':team_id', $data['team_id']);
    $expire_on = mktime(date('H'), date('i'), date('s'), date('n'), date('j')+$data['expire_period']);
    $db_query->bindParam(':expire_on', $expire_on);
    $user_id = USER_ID;
    $db_query->bindParam(':issued_by', $user_id);
    $stamp = md5($data['team_id']*(rand(0,1024)+time()%1024));
    $db_query->bindParam(':stamp', $stamp);
    $db_query->execute();

    $link = "{$site['root']}/teamwork/invite/register.php?team={$data['team_id']}&stamp=$stamp";

    $text = array(  'type' => "text_html",
                    'content' => "<p class='text'>Это ссылка для регистрации по вашему приглашению: <a class='link' href='$link'>$link</a></p>");
    $blocks = array( 'text' => $text);
    $page['title'] = "Успех";
    $page['page_title'] = "Приглашение создано";
    $page['blocks'] = $blocks;
    echo $twig->render("template.html", $page);

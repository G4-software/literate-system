<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/database/db_connection.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/postlogin.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/twig_config.php";

    if(!USER_LOGGED_IN)
    {
        header("Location: {$site['root']}/login.php");
    }

    if(!isset($_GET) && !isset($_POST))
    {
        header("Location: {$site['root']}/view_issue.php");
    }

    if(isset($_GET['issue_in_project_id']) && isset($_GET['team_id']))
    {
        $user_id = USER_ID;
        $db_query = $db->prepare("SELECT `team_members` FROM `teams` WHERE `teams`.`team_id` = :team_id");
        $db_query->bindParam('team_id', $_GET['team_id']);
        $db_query->execute();
        $result = $db_query->fetchColumn();
        $team_members = unserialize($result);
        $user_id = USER_ID;
        if(!in_array($user_id, $team_members))
        {
            header("Location: {$site['root']}/view_issue.php");
        }

        $inputs = array('close_message' => array(   'label' => "Введите сообщение:",
                                                    'type' => "textarea",
                                                    'name' => "close_message",
                                                    'args' => "rows=10"),
                        'team_id' => array( 'type' => "text",
                                            'name' => "team_id",
                                            'args' => "hidden readonly value='{$_GET['team_id']}'"),
                        'issue_in_project_id' => array( 'type' => "text",
                                                        'name' => "issue_in_project_id",
                                                        'args' => "hidden readonly value='{$_GET['issue_in_project_id']}'"));
        $close_form = array(  'type' => "form",
                                'script' => "close_issue.php",
                                'method' => "POST",
                                'inputs' => $inputs,
                                'submit_button_text' => "Close");
        $blocks['close_form'] = $close_form;
        $page = array(  'title' => "Закрыть #{$_GET['issue_in_project_id']}",
                        'page_title' => "Закрыть #{$_GET['issue_in_project_id']}",
                        'menu' => array('teams' => $menu_teams),
                        'site' => $site,
                        'user' => $user,
                        'blocks' => $blocks);
        echo $twig->render("template.html", $page);
        die();
    }

    if(count($_POST) == 3)
    {
        $user_id = USER_ID;
        $db_query = $db->prepare("SELECT `team_members` FROM `teams` WHERE `teams`.`team_id` = :team_id");
        $db_query->bindParam('team_id', $_POST['team_id']);
        $db_query->execute();
        $result = $db_query->fetchColumn();
        $team_members = unserialize($result);
        $user_id = USER_ID;
        if(!in_array($user_id, $team_members))
        {
            header("Location: {$site['root']}/view_issue.php");
        }

        $data['team_id'] = $_POST['team_id'];
        $data['issue_in_project_id'] = $_POST['issue_in_project_id'];
        $data['close_message'] = preg_replace('/\s{2,}/', ' ', nl2br($_POST['close_message']));

        $db_query = $db->prepare("UPDATE `issues` SET `closed_by` = :user_id, `closed_on` = :closed_on, `close_message` = :close_message, `is_closed` = 1 WHERE `issues`.`in_team` = :in_team AND `issues`.`issue_in_project_id` = :issue_in_project_id;");
        $db_query->bindParam(':user_id', $user_id);
        $time = time();
        $db_query->bindParam(':closed_on', $time);
        $db_query->bindParam(':close_message', $data['close_message']);
        $db_query->bindParam(':in_team', $data['team_id']);
        $db_query->bindParam(':issue_in_project_id', $data['issue_in_project_id']);
        $db_query->execute();

        $text = array(  'type' => "text_html");
        $blocks = array( 'text' => $text);
        $page = array(  'title' => "Успех",
                        'page_title' => "Заявка закрыта",
                        'menu' => array('teams' => $menu_teams),
                        'site' => $site,
                        'user' => $user,
                        'blocks' => $blocks);
        echo $twig->render("template.html", $page);
    }

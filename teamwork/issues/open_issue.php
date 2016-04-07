<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/database/db_connection.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/postlogin.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/twig_config.php";

    if(!USER_LOGGED_IN)
    {
        header("LocationL {$site['root']}/login.php");
    }

    $db_query = $db->prepare("SELECT `team_id`, `team_name`, `team_members` FROM `teams`");
    $db_query->execute();
    $teams_raw = $db_query->fetchAll(PDO::FETCH_ASSOC);
    $user_id = USER_ID;
    $teams;

    $team_selection;
    if(!isset($_GET['team_id']))
    {
        foreach ($teams_raw as $team)
        {
            $team_members = unserialize($team['team_members']);
            if(in_array($user_id, $team_members))
            {
                $temp = array(  'value' => $team['team_id'],
                                'label' => $team['team_name']);
                $teams[] = $temp;
            }
        }
        $team_selection = array('label' => "Команда:",
                                'type' => "list",
                                'name' => "in_team",
                                'options' => $teams);
        $get_flag = false;
    }
    else
    {
        foreach ($teams_raw as $team)
        {
            $team_members = unserialize($team['team_members']);
            if(in_array($user_id, $team_members) && $_GET['team_id']==$team['team_id'])
            {
                $temp = array(  'value' => $team['team_id'],
                                'label' => $team['team_name']);
                $teams[] = $temp;
            }
        }
        $team_selection = array('label' => "Команда:",
                                'type' => "list",
                                'name' => "in_team",
                                'options' => $teams,
                                'args' => "readonly");
        $get_flag = true;
    }

    $inputs = array(    'issue_summary' => array(   'label' => "Заголовок:",
                                                    'type' => "text",
                                                    'name' => "issue_summary"),
                        'issue_description' => array(   'label' => "Описание:",
                                                        'type' => "textarea",
                                                        'name' => "issue_description",
                                                        'args' => "rows=4"),
                        'in_team' => $team_selection);

    $form = array(  'type' => "form",
                    'script' => "open_issue.php",
                    'method' => "POST",
                    'inputs' => $inputs,
                    'submit_button_text' => "Создать заявку");
    $blocks = array('text' => array(    'type' => 'text',
                                        'content' => "Заполните, чтобы создать заявку:"),
                    'open_form' => $form);
    $page = array(  'title' => "Создать заявку",
                    'page_title' => "Создать заявку",
                    'menu' => array('teams' => $menu_teams),
                    'site' => $site,
                    'user' => $user,
                    'blocks' => $blocks);

    if($get_flag)
    {
        if(empty($_POST))
        {
            echo $twig->render("template.html", $page);
            die();
        }

        if(count($_POST)<3)
        {
            $page['blocks']['error'] = array(   'type' => "error",
                                                'summary' => "Данные не отправлены",
                                                'content' => "Сервер ничего не получил и обиделся.");
            echo $twig->render("template.html", $page);
            die();
        }
    }

    $data['issue_summary'] = $_POST['issue_summary'];
    $data['issue_description'] = preg_replace('/\s{2,}/', ' ', nl2br($_POST['issue_description']));
    $data['in_team'] = $_POST['in_team'];

    $db_query = $db->prepare("SELECT `team_members` FROM `teams` WHERE `teams`.`team_id` = :team_id");
    $db_query->bindParam('team_id', $data['in_team']);
    $db_query->execute();
    $result = $db_query->fetchColumn();
    $team_members = unserialize($result);
    $data['opened_by'] = USER_ID;
    if(!in_array($data['opened_by'], $team_members))
    {
        header("Location: {$site['root']}/open_issue.php");
    }

    $db_query = $db->prepare("SELECT `issue_in_project_id` FROM `issues` WHERE `in_team` = :in_team ORDER BY `issues`.`issue_in_project_id` DESC;");
    $db_query->bindParam(':in_team', $data['in_team']);
    $db_query->execute();
    $result = $db_query->fetchColumn();
    $data['issue_in_project_id'] = ($result == "") ? 1 : $result+1;

    $db_query = $db->prepare("INSERT INTO `issues` (`issue_id`, `issue_in_project_id`, `issue_summary`, `issue_description`, `in_team`, `opened_by`, `opened_on`, `is_closed`, `closed_by`, `closed_on`, `close_message`, `comments`)
                                            VALUES (NULL, :issue_in_project_id, :issue_summary, :issue_description, :in_team, :opened_by, :opened_on, '0', NULL, NULL, NULL, NULL);");
    $data['opened_on'] = time();
    $db_query->execute($data);

    $text = array(  'type' => "text_html");
    $blocks = array( 'text' => $text);
    $page['title'] = "Успех";
    $page['page_title'] = "Заявка создана";
    $page['blocks'] = $blocks;
    echo $twig->render("template.html", $page);

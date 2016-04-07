<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/database/db_connection.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/postlogin.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/twig_config.php";

    if(!USER_LOGGED_IN)
    {
        header("Location: {$site['root']}/login.php");
    }

    if(empty($_GET) && empty($_POST))
    {
        $db_query = $db->prepare("SELECT `team_id`, `team_name`, `team_members` FROM `teams`");
        $db_query->execute();
        $teams_raw = $db_query->fetchAll(PDO::FETCH_ASSOC);
        $user_id = USER_ID;
        $teams;
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

        $inputs = array('team_id' => array( 'label' => "Команда:",
                                            'type' => "list",
                                            'name' => "team_id",
                                            'options' => $teams));

        $form = array(  'type' => "form",
                        'script' => "edit_team.php",
                        'method' => "GET",
                        'inputs' => $inputs,
                        'submit_button_text' => "Далее >>");
        $blocks = array('text' => array(    'type' => 'text_html',
                                            'content' => "<p class='text'>Выберите команду:</p>"),
                        'open_form' => $form);
        $page = array(  'title' => "Управление командой",
                        'page_title' => "Управление командой",
                        'menu' => array('teams' => $menu_teams),
                        'site' => $site,
                        'user' => $user,
                        'blocks' => $blocks);

        echo $twig->render("template.html", $page);
        die();
    }

    if(isset($_GET['team_id']))
    {
        $db_query = $db->prepare("SELECT `shown_team_name`, `team_description` FROM `teams` WHERE `team_id` = :team_id;");
        $db_query->bindParam(':team_id', $_GET['team_id']);
        $db_query->execute();
        $team = $db_query->fetchAll(PDO::FETCH_ASSOC);

        $inputs = array('shown_team_name' => array( 'label' => "Название команды:",
                                                    'type' => "text",
                                                    'name' => "shown_team_name",
                                                    'args' => "value='{$team[0]['shown_team_name']}'"),
                        'team_description' => array('label' => "Описание команды:",
                                                    'type' => "textarea",
                                                    'name' => "team_description",
                                                    'args' => "value='{$team[0]['team_description']}'"));
        $form = array(  'type' => "form",
                        'script' => "edit_team.php",
                        'method' => "POST",
                        'inputs' => $inputs,
                        'submit_button_text' => "Редактировать");
        $blocks = array('form' => $form);
        $page = array(  'title' => "Управление командой",
                        'page_title' => "Управление командой",
                        'menu' => array('teams' => $menu_teams),
                        'site' => $site,
                        'user' => $user,
                        'blocks' => $blocks);

        echo $twig->render("template.html", $page);
        die();
    }

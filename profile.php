<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/database/db_connection.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/postlogin.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/twig_config.php";

    if(!USER_LOGGED_IN)
    {
        header("Location: {$site['root']}/login.php");
    }

    if(isset($_GET['username']))
    {
        $db_query = $db->prepare("SELECT `user_id` from `users` WHERE `shown_username` = :username;");
        $db_query->bindParam(':username', $_GET['username']);
        $db_query->execute();
        $user_id = $db_query->fetchColumn();
    }
    elseif(isset($_GET['user_id']))
    {
        $user_id = $_GET['user_id'];
    }
    else
    {
        $user_id = USER_ID;
    }

    $db_query = $db->prepare("SELECT `shown_username` FROM `users` WHERE `user_id` = :user_id;");
    $db_query->bindParam(':user_id', $user_id);
    $db_query->execute();
    $shown_username = $db_query->fetchColumn();

    $db_query = $db->prepare("SELECT `issue_summary`, `in_team`, `opened_on`, `is_closed`, `closed_on`, `closed_by`, `issue_in_project_id` FROM `issues` WHERE `opened_by` = :user_id;");
    $db_query->bindParam(':user_id', $user_id);
    $db_query->execute();
    $issues_opened_raw = $db_query->fetchAll(PDO::FETCH_ASSOC);
    $issues_opened;
    foreach ($issues_opened_raw as $issue)
    {
        $issue['team_id'] = $issue['in_team'];
        $db_query = $db->prepare("SELECT `shown_team_name` FROM `teams` WHERE `team_id` = :team_id");
        $db_query->bindParam(':team_id', $issue['in_team']);
        $db_query->execute();
        $shown_team_name = $db_query->fetchColumn();
        $issue['in_team'] = $shown_team_name;
        $issue['opened_on'] = date("H:i:s d.m.y", $issue['opened_on']);

        if($issue['is_closed'] == 1)
        {
            $db_query = $db->prepare("SELECT `shown_username` FROM `users` WHERE `user_id` = :user_id");
            $db_query->bindParam(':user_id', $issue['closed_by']);
            $db_query->execute();
            $closed_by = $db_query->fetchColumn();
            $issue['closed_by'] = $closed_by;

            $issue['closed_on'] = date("H:i:s d.m.y", $issue['closed_on']);
        }

        $issues_opened[] = $issue;
    }

    $db_query = $db->prepare("SELECT `issue_summary`, `in_team`, `opened_on`, `opened_by`, `closed_on`, `issue_in_project_id` FROM `issues` WHERE `closed_by` = :user_id;");
    $db_query->bindParam(':user_id', $user_id);
    $db_query->execute();
    $issues_closed_raw = $db_query->fetchAll(PDO::FETCH_ASSOC);
    $issued_closed;
    foreach ($issues_closed_raw as $issue)
    {
        $issue['team_id'] = $issue['in_team'];
        $db_query = $db->prepare("SELECT `shown_team_name` FROM `teams` WHERE `team_id` = :team_id");
        $db_query->bindParam(':team_id', $issue['in_team']);
        $db_query->execute();
        $shown_team_name = $db_query->fetchColumn();
        $issue['in_team'] = $shown_team_name;
        $db_query = $db->prepare("SELECT `shown_username` FROM `users` WHERE `user_id` = :user_id");
        $db_query->bindParam(':user_id', $issue['opened_by']);
        $db_query->execute();
        $opened_by = $db_query->fetchColumn();
        $issue['opened_by'] = $opened_by;
        $issue['opened_on'] = date("H:i:s d.m.y", $issue['opened_on']);
        $issue['closed_on'] = date("H:i:s d.m.y", $issue['closed_on']);

        $issues_closed[] = $issue;
    }

    $profile = array(   'type' => "profile",
                        'username' => $shown_username,
                        'issues_opened_num' => count($issues_opened),
                        'issues_closed_num' => count($issues_closed),
                        'issues_opened' => $issues_opened,
                        'issues_closed' => $issues_closed);

    $page = array(  'title' => "$shown_username",
                    'page_title' => "Профиль",
                    'menu' => array('teams' => $menu_teams),
                    'site' => $site,
                    'user' => $user,
                    'blocks' => array(  'profile' => $profile));

    echo $twig->render("template.html", $page);

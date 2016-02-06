<?php
    require_once __DIR__."/../../config.php";
    require_once __DIR__."/../../database/db_connection.php";
    require_once __DIR__."/../../postlogin.php";
    require_once __DIR__."/../../twig_config.php";

    if(!USER_LOGGED_IN)
    {
        header("Location: ../../../index.php");
    }

    if(empty($_GET))
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

        $inputs = array('in_team' => array( 'label' => "Team:",
                                            'type' => "list",
                                            'name' => "in_team",
                                            'options' => $teams));

        $form = array(  'type' => "form",
                        'script' => "view_issue.php",
                        'method' => "GET",
                        'inputs' => $inputs,
                        'submit_button_text' => "Next >>");
        $blocks = array('text' => array(    'type' => 'text_html',
                                            'content' => "<p class='text'>Issue not specified, so, please, select a team, first:</p>"),
                        'open_form' => $form);
        $page = array(  'title' => "View issue",
                        'page_title' => "View issue",
                        'user' => $user,
                        'blocks' => $blocks);

        echo $twig->render("template.html", $page);
        die();
    }

    if(isset($_GET['in_team']) && !isset($_GET['issue_in_project_id']))
    {
        $blocks = array('text' => array(    'type' => 'text_html',
                                            'content' => "<p class='text'>Select issue:</p>"));
        $page = array(  'title' => "View issue",
                        'page_title' => "View issue",
                        'user' => $user,
                        'blocks' => $blocks);

        $db_query = $db->prepare("SELECT `team_members` FROM `teams` WHERE `teams`.`team_id` = :team_id");
        $db_query->bindParam('team_id', $_GET['in_team']);
        $db_query->execute();
        $result = $db_query->fetchColumn();
        $team_members = unserialize($result);
        $user_id = USER_ID;
        if(!in_array($user_id, $team_members))
        {
            echo $twig->render("template.html", $page);
            die("Not member of the team");
        }

        $db_query = $db->prepare("SELECT `issue_in_project_id`, `issue_summary`, `opened_by`, `opened_on` FROM `issues` WHERE `in_team` = :in_team AND `is_closed` = 0");
        $db_query->bindParam(':in_team', $_GET['in_team']);
        $db_query->execute();
        $issues_raw = $db_query->fetchAll(PDO::FETCH_ASSOC);
        $user_id = USER_ID;
        // print_r($issues_raw);

        $issues;
        foreach($issues_raw as $issue)
        {
            $db_query = $db->prepare("SELECT `shown_username` FROM `users` WHERE `user_id` = :opened_by;");
            $db_query->bindParam(':opened_by', $issue['opened_by']);
            $db_query->execute();
            $opened_by = $db_query->fetchColumn();
            $temp = array(  "<a href='view_issue.php?in_team={$_GET['in_team']}&issue_in_project_id={$issue['issue_in_project_id']}'>{$issue['issue_summary']}</a>",
                            $opened_by,
                            date("H:i:s d.m.Y", $issue['opened_on']));
            $issues[] = $temp;
        }

        $table = array( 'type' => 'table',
                        'rows' => $issues);

        $page = array(	'title' => "View issue",
    					'page_title' => "View issue",
                        'user' => $user,
    					'blocks' => array(  'table' => $table));
        echo $twig->render("template.html", $page);
        die();
    }

    if(isset($_GET['in_team']) && isset($_GET['issue_in_project_id']))
    {
        $blocks = array('text' => array(    'type' => 'text_html',
                                            'content' => "<p class='text'>Select issue:</p>"));
        $page = array(  'title' => "View issue",
                        'page_title' => "View issue",
                        'user' => $user,
                        'blocks' => $blocks);

        $db_query = $db->prepare("SELECT `team_members` FROM `teams` WHERE `teams`.`team_id` = :team_id");
        $db_query->bindParam('team_id', $_GET['in_team']);
        $db_query->execute();
        $result = $db_query->fetchColumn();
        $team_members = unserialize($result);
        $user_id = USER_ID;
        if(!in_array($user_id, $team_members))
        {
            echo $twig->render("template.html", $page);
            die("Not member of the team");
        }

        $db_query = $db->prepare("SELECT `issue_id`, `issue_summary`, `issue_description`, `opened_by`, `opened_on`, `is_closed`, `closed_by`, `closed_on`, `close_message`, `comments` FROM `issues` WHERE `in_team` = :in_team AND `issue_in_project_id` = :issue_in_project_id");
        $db_query->bindParam(':in_team', $_GET['in_team']);
        $db_query->bindParam(':issue_in_project_id', $_GET['issue_in_project_id']);
        $db_query->execute();
        $issue = $db_query->fetch(PDO::FETCH_ASSOC);
        $blocks = array('issue_summary' => array(   'type' => "h2",
                                                    'content' => $issue['issue_summary']),
                        'opened_table' => array('type' => "table",
                                                'rows' => array('1' =>  array(  'opened_by' => $issue['opened_by'],
                                                                                'opened_on' => date("H:i:s d.m.Y", $issue['opened_on'])))),
                        'issue_description' => array(   'type' => 'text_html',
                                                        'content' => $issue['issue_description']));

        if($issue['is_closed'] == 1)
        {
            $blocks['is_closed'] = array(   'type' => "h3",
                                            'content' => "Closed");
            $blocks['closed_table'] = array('type' => "table",
                                            'rows' => array('1' =>  array(  'closed_by' => $issue['closed_by'],
                                                                            'closed_on' => date("H:i:s d.m.Y", $issue['closed_on']))));
            $blocks['close_message'] = array(   'type' => "text",
                                                'content' => $issue['close_message']);
        }
        else
        {
            $blocks['is_closed'] = array(   'type' => "h3",
                                            'content' => "Not closed");
            $blocks['close_text'] = array(  'type' => "text",
                                            'content' => "You may close this issue:");
            $inputs = array('issue_id' => array('type' => "text",
                                                'name' => "issue_in_project_id",
                                                'args' => "hidden readonly value='{$_GET['issue_in_project_id']}'"),
                            'team_id' => array( 'type' => "text",
                                                'name' => "team_id",
                                                'args' => "hidden readonly value='{$_GET['in_team']}'"));
            $close_button = array(  'type' => "form",
                                    'script' => "close_issue.php",
                                    'method' => "GET",
                                    'inputs' => $inputs,
                                    'submit_button_text' => "Close");
            $blocks['close_form'] = $close_button;
        }

        $blocks['messages'] = array('type' => "h3",
                                    'content' => "Messages");

        $messages = unserialize($issue['comments']);
        $messages_table = array('type' => 'table_esc',
                                'rows' => $issue);
        $blocks['messages_table'] = $messages_table;

        $inputs = array('send_message' => array('label' => "Leave comment:",
                                                'type' => "textarea",
                                                'name' => "message_text",
                                                'args' => "rows=3"));
        $message_form = array(  'type' => "form",
                                'script' => "send_comment.php",
                                'method' => "POST",
                                'inputs' => $inputs,
                                'submit_button_text' => "Comment");
        $blocks['message_form'] = $message_form;
        $page = array(  'title' => "View issue #{$_GET['issue_in_project_id']}",
                        'page_title' => "Issue #{$_GET['issue_in_project_id']}",
                        'user' => $user,
                        'blocks' => $blocks);
        echo $twig->render("template.html", $page);
        die();
    }

    header("Location: view_issue.php");

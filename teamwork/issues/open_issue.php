<?php
    require_once __DIR__."/../../config.php";
    require_once __DIR__."/../../database/db_connection.php";
    require_once __DIR__."/../../postlogin.php";
    require_once __DIR__."/../../twig_config.php";

    if(!USER_LOGGED_IN)
    {
        header("LocationL ../../../index.php");
    }

    $db_query = $db->prepare("SELECT `team_id`, `team_name` FROM `teams` WHERE `team_owner` = :user_id");
    $user_id = USER_ID;
    $db_query->bindParam(':user_id', $user_id);
    $db_query->execute();
    $result = $db_query->fetchAll(PDO::FETCH_ASSOC);

    foreach($result as $option)
    {
        $temp = array(  'value' => $option['team_id'],
                        'label' => $option['team_name']);
        $teams[] = $temp;
    }

    $inputs = array(    'issue_summary' => array(   'label' => "Summary:",
                                                    'type' => "text",
                                                    'name' => "issue_summary"),
                        'issue_description' => array(   'label' => "Description:",
                                                        'type' => "textarea",
                                                        'name' => "issue_description",
                                                        'args' => "rows=4"),
                        'in_team' => array( 'label' => "Team:",
                                            'type' => "list",
                                            'name' => "in_team",
                                            'options' => $teams));

    $form = array(  'type' => "form",
                    'script' => "open_issue.php",
                    'method' => "POST",
                    'inputs' => $inputs,
                    'submit_button_text' => "Open issue");
    $blocks = array('text' => array(    'type' => 'text_html',
                                        'content' => "<p class='text'>Fill following form to create issue:</p>"),
                    'open_form' => $form);
    $page = array(  'title' => "Open_issue",
                    'page_title' => "Open issue",
                    'user' => $user,
                    'blocks' => $blocks);

    if(empty($_POST))
    {
        echo $twig->render("template.html", $page);
        die();
    }

    if(count($_POST)<3)
    {
        echo $twig->render("template.html", $page);
        die("Wrong input");
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
        echo $twig->render("template.html", $page);
        die("Not member of the team");
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
    $page['title'] = "Success";
    $page['page_title'] = "Issue opened";
    $page['blocks'] = $blocks;
    echo $twig->render("template.html", $page);

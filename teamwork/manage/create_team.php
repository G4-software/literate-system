<?php
    require_once __DIR__."/../../config.php";
    require_once __DIR__."/../../database/db_connection.php";
    require_once __DIR__."/../../postlogin.php";
    require_once __DIR__."/../../twig_config.php";

    $page = array(  'title' => "Team creation",
                    'page_title' => "Complete this form create new team:",
                    'blocks' => array(  'comment' => array( 'type' => "text_html",
                                                            'content' => "<p class='comment'>You can invite people to your team from team management panel after creation of one.</p>"),
                                        'form' => array(    'type' => "form",
                                                            'script' => "create_team.php",
                                        					'method' => "POST",
                                        					'inputs' => array(	'team_name' => array(	'label' => "Select name for your team:",
                                            															'type' => "text_html",
                                            															'name' => "team_name"),
                                        										'expire' => array(	'label' => "Descript your team:",
                                        															'type' => "textarea",
                                        															'name' => "team_description",
                                                                                                    'args' => "rows=5")),
                                                            'submit_button_text' => "Create team")));

    if(!USER_LOGGED_IN)
    {
        header("Location: ../../login.php");
    }
    elseif(empty($_POST))
    {
        echo $twig->render("template.html", $page);
        die();
    }

    if(!isset($_POST['team_name']) || !isset($_POST['team_description']))
    {
        echo $twig->render("template.html", $page);
    }

    $data['team_name'] = strtoupper(trim($_POST['team_name']));
    $data['shown_team_name'] = trim($_POST['team_name']);
    $data['team_description'] = preg_replace('/\s{2,}/', ' ', nl2br($_POST['team_description']));

    $db_query = $db->prepare("SELECT `user_id` FROM `users` WHERE `username` = :username");
    $username = USERNAME;
    $db_query->bindParam(":username", $username);
    $db_query->execute();
    $result = $db_query->fetchColumn();
    $data['team_owner'] = $result;

    $db_query = $db->prepare("SELECT `team_name` FROM `teams` WHERE `team_name = :team_name`");
    $db_query->bindParam(":team_name", $team_name);
    $db_query->execute();
    $result = $db_query->fetchColumn();
    if($result == $team_name)
    {
        echo $twig->render("template.html", $page);
        echo "The team $team_name already exists";
    }

    $team_members[] = $data['team_owner'];
    $team_managers[] = $data['team_owner'];
    $data['team_managers'] = serialize($team_managers);
    $data['team_members'] = serialize($team_members);

    $db_query = $db->prepare("INSERT INTO `teams` (`team_id`, `team_name`, `shown_team_name`, `team_description`, `team_owner`, `team_managers`, `team_members`)
                                            VALUES (NULL, :team_name, :shown_team_name, :team_description, :team_owner, :team_managers, :team_members);");
    $db_query->execute((array)$data);
    header("Location: edit_team.php");

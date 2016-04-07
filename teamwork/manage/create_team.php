<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/database/db_connection.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/postlogin.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/twig_config.php";

    $page = array(  'title' => "Создать команду",
                    'page_title' => "Заполните, чтобы создать команду:",
                    'menu' => array('teams' => $menu_teams),
                    'site' => $site,
                    'user' => $user,
                    'blocks' => array(  'comment' => array( 'type' => "text_html",
                                                            'content' => "<p class='comment'>Позже вы сможете пригласить участников в команду.</p>"),
                                        'form' => array(    'type' => "form",
                                                            'script' => "create_team.php",
                                        					'method' => "POST",
                                        					'inputs' => array(	'team_name' => array(	'label' => "Название команды:",
                                            															'type' => "text_html",
                                            															'name' => "team_name"),
                                        										'expire' => array(	'label' => "Описание:",
                                        															'type' => "textarea",
                                        															'name' => "team_description",
                                                                                                    'args' => "rows=5")),
                                                            'submit_button_text' => "Создать команду")));

    if(!USER_LOGGED_IN)
    {
        header("Location: {$site['root']}/login.php");
        die();
    }
    elseif(empty($_POST))
    {
        echo $twig->render("template.html", $page);
        die();
    }

    if(empty($_POST['team_name']) || empty($_POST['team_description']))
    {
        echo $twig->render("template.html", $page);
        die();
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
        echo "Такая команда уже есть: $team_name ";
        die();
    }

    $team_members[] = $data['team_owner'];
    $team_managers[] = $data['team_owner'];
    $data['team_managers'] = serialize($team_managers);
    $data['team_members'] = serialize($team_members);

    $db_query = $db->prepare("INSERT INTO `teams` (`team_id`, `team_name`, `shown_team_name`, `team_description`, `team_owner`, `team_managers`, `team_members`)
                                            VALUES (NULL, :team_name, :shown_team_name, :team_description, :team_owner, :team_managers, :team_members);");
    $db_query->bindParam(':team_name', $data['team_name']);
    $db_query->bindParam(':shown_team_name', $data['shown_team_name']);
    $db_query->bindParam(':team_description', $data['team_description']);
    $db_query->bindParam(':team_owner', $data['team_owner']);
    $db_query->bindParam(':team_managers', $data['team_managers'], PDO::PARAM_LOB);
    $db_query->bindParam(':team_members', $data['team_members'], PDO::PARAM_LOB);
    $db_query->execute();
    header("Location: {$site['root']}/edit_team.php");

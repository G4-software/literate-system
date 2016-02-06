<?php
    require_once __DIR__."/../../config.php";
    require_once __DIR__."/../../database/db_connection.php";
    require_once __DIR__."/../../postlogin.php";
    require_once __DIR__."/../../twig_config.php";

    if(!USER_LOGGED_IN)
    {
        header("Location: ./../../login.php");
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

    for($var = 5; $var <= 60; $var+=5)
    {
        $temp = array(  'value' => $var,
                        'label' => "$var days");
        $expire_periods[] = $temp;
    }

    $inputs = array(    'team' => array(    'label' => "Select team:",
                                            'type' => "list",
                                            'name' => "team",
                                            'options' => $teams),
                        'expire' => array(	'label' => "Expires:",
                                            'type' => "list",
                                            'name' => "expire_period",
                                            'options' => $expire_periods));
    $form = array(  'type' => "form",
                    'script' => "invite.php",
                    'method' => "POST",
                    'inputs' => $inputs,
                    'submit_button_text' => "Get link!");
    $blocks = array('invite_form' => $form);
    $page = array(  'title' => "Invite",
                    'page_title' => "Complete this form to send invitation:",
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

    $link = "https://{$_SERVER['HTTP_HOST']}/teamwork/invite/register.php?team={$data['team_id']}&stamp=$stamp";

    $text = array(  'type' => "text_html",
                    'content' => "<p class='text'>This is register link for your invitation: <a class='link' href='$link'>$link</a></p>");
    $blocks = array( 'text' => $text);
    $page['title'] = "Success";
    $page['page_title'] = "Invitation created";
    $page['blocks'] = $blocks;
    echo $twig->render("template.html", $page);

<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/twig_config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/postlogin.php";

    $blocks = array('where_am_i' => array(  'type' => "h2",
                                            'content' => "Где я?"),
                    'where_description' => array(   'type' => 'text',
                                                    'content' => "Вы находитесь на главной странице Literate-System, это небольшая система управления заявками. Она постоянно развивается, поэтому не забывайте следить за версиями!"));

    $page = array(  'title' => "Главная",
                    'page_title' => "Главная",
                    'site' => $site,
                    'blocks' => $blocks);
    if(USER_LOGGED_IN)
    {
        $page['user'] = $user;
        $page['menu'] = array('teams' => $menu_teams);
    }

    echo $twig->render("template.html", $page);

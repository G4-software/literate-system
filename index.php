<?php
    require_once __DIR__."/config.php";
    require_once __DIR__."/twig_config.php";

    $title = "Main";
    echo $twig->render("base.html", array('title' => $title));

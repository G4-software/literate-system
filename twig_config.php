<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/vendor/autoload.php";
    Twig_Autoloader::register();
    $loader = new Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'].'/templates');
    $twig = new Twig_Environment($loader,
        array(
            'cache'       => 'compilation_cache',
            'auto_reload' => true));

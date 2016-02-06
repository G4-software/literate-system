<?php
    require_once __DIR__."/config.php";
    require_once __DIR__."/vendor/autoload.php";
    Twig_Autoloader::register();
    $loader = new Twig_Loader_Filesystem(__DIR__.'/templates');
    $twig = new Twig_Environment($loader,
        array(
            'cache'       => 'compilation_cache',
            'auto_reload' => true));

<?php
////Redirect to SSL connection
    if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "")
    {
        $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: $redirect");
    }

    error_reporting(E_ALL);
?>

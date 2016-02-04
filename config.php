<?php
//Redirect to SSL connection
    if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "")
    {
        $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: $redirect");
    }

    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 01 Jan 1980 05:00:00 GMT");

    error_reporting(E_ALL);

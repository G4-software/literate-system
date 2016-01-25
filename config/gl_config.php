<?php
////Redirect to SSL connection
    if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "")
    {
        $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: $redirect");
    }

    function set_title($title)
    {
        echo
"<head>
    <title>LS – $title</title>
</head>";
    }

    error_reporting(E_ALL);
?>

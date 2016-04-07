<?php
    require_once $_SERVER['DOCUMENT_ROOT']."/config.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/database/db_config.php";

    try
    {
        $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USERNAME, DB_PASSWORD);
    }
    catch (PDOException $e)
    {
        echo "<p>DB access malfunction: " . $e->getMessage() . "</p>";
        die();
    }

<?php
    require_once __DIR__."/../config.php";
    require_once __DIR__."/db_config.php";

    try
    {
        $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USERNAME, DB_PASSWORD);
    }
    catch (PDOException $e)
    {
        echo "<p>DB access malfunction: " . $e->getMessage() . "</p>";
        die();
    }

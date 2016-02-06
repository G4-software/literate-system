<?php
    setcookie("ls-username", "", time()-60*60*24);
    setcookie("ls-logged_in", "", time()-60*60*24);
    header("Location: login.php");

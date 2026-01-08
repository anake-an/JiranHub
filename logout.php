<?php
/*
KP34903: Logout
Group 7
JiranHub Web
*/

session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit();
?>
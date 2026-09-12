<?php
session_start();
$_SESSION = [];
session_destroy();
header("Location: /TimosaTech/pages/homepage.php");
exit;
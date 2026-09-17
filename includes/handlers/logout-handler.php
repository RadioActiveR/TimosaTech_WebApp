<?php

/* INFO: Logout Handler
 *
 * INFO: Linked Files:
 * 
 * NONE
 *
 * INFO: Is used by:
 * 
 * components/header.php
 */

session_start();
$_SESSION = [];
session_destroy();
header("Location: /TimosaTech/pages/homepage.php");
exit;
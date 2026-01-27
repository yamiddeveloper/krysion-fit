<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/wp-load.php';

wp_logout();
session_destroy();

header("Location: login.php");
exit();
?>
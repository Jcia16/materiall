<?php
session_start();

if (!isset($_SESSION['log']) || $_SESSION['log'] !== true) {
    header('location:login.php');
    exit;
}
?>

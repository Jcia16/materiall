<?php
require 'function.php';

$id = $_GET['id'];
mysqli_query($conn,
    "UPDATE notifikasi SET status='dibaca' WHERE id_notif='$id'");
header("Location:index.php");

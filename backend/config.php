<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "retail_db";
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) { die("Error: " . mysqli_connect_error()); }
?>
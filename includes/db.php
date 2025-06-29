<?php
$host = "localhost";
$user = "root";
$password = ""; // Default XAMPP password is empty
$db = "salesinventorydb"; // Replace with your DB name

$conn = mysqli_connect($host, $user, $password, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

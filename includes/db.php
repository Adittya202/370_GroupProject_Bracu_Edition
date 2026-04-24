<?php
$servername = "localhost";
$username = "root";
$password = "canon6d"; // Updated password
$dbname = "credit_transfer_system";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
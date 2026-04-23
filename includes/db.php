<?php
$servername = "localhost";
$username = "root";
$password = ""; // Default Fedora MariaDB password is usually empty for root
$dbname = "credit_transfer_db"; // We will confirm this name shortly

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
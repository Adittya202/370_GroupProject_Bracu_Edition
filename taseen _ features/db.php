<?php
// Connect to XAMPP MySQL. 
// (If you are still using port 3307, change "localhost" to "localhost:3307")
$conn = mysqli_connect("localhost:3307", "root", "", "credit_transfer_system");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>

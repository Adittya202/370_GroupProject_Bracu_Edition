<?php 
include('includes/db.php'); 
// These lines help bypass the 500 screen to show the actual error
ini_set('display_errors', 1);
error_reporting(E_ALL);

$sql = "SELECT * FROM SOP_TEMPLATE";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>SOP Templates</title>
</head>
<body>
    <?php
    if ($result) {
        while($row = mysqli_fetch_assoc($result)) {
            echo "<h3>" . $row['Category'] . "</h3>";
            echo "<p>" . $row['Content'] . "</p>";
        }
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    ?>
</body>
</html>
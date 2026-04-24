<?php 
include('includes/db.php'); 

// MOCK USER ID (In a real app, this comes from a login session)
$current_user_id = 1;

// LOGIC TO SAVE AN SOP
if (isset($_POST['save_sop'])) {
    $template_id = $_POST['template_id'];
    
    // Check if already saved to prevent duplicates
    $check = "SELECT * FROM STUDENT_SAVES_SOP WHERE UserID = $current_user_id AND TemplateID = $template_id";
    $exists = mysqli_query($conn, $check);
    
    if (mysqli_num_rows($exists) == 0) {
        $insert = "INSERT INTO STUDENT_SAVES_SOP (UserID, TemplateID) VALUES ($current_user_id, $template_id)";
        mysqli_query($conn, $insert);
        echo "<script>alert('SOP Saved to Favorites!');</script>";
    } else {
        echo "<script>alert('You already saved this one.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SOP Database</title>
    <link rel="stylesheet" href="sop.css">
</head>
<body>
    <h1>SOP Database</h1>
    <main>
        <?php
        $sql = "SELECT * FROM SOP_TEMPLATE";
        $result = mysqli_query($conn, $sql);

        while($row = mysqli_fetch_assoc($result)) {
            echo "<div class='sop-card' style='border:1px solid #ccc; padding:15px; margin:10px;'>";
            echo "<h3>" . $row['Category'] . "</h3>";
            echo "<p>" . $row['Content'] . "</p>";
            
            // The Save Button Form
            echo "<form method='POST' action='sop.php'>";
            echo "<input type='hidden' name='template_id' value='" . $row['TemplateID'] . "'>";
            echo "<button type='submit' name='save_sop' style='background:#28a745; color:white;'>Save to My Favorites</button>";
            echo "</form>";
            
            echo "</div>";
        }
        ?>
    </main>
</body>
</html>
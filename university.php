<?php include('includes/db.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>University Selection</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header><h1>Select a University</h1></header>
    <main>
        <?php
        $query = "SELECT UNIVERSITY.Name AS UniName, UNIVERSITY.Ranking, CITY.Name AS CityName 
                  FROM UNIVERSITY 
                  JOIN CITY ON UNIVERSITY.CityID = CITY.CityID";
        $result = mysqli_query($conn, $query);

        while($row = mysqli_fetch_assoc($result)) {
            echo "<div class='uni-card'>";
            echo "<h3>" . $row['UniName'] . "</h3>";
            echo "<p>Ranking: #" . $row['Ranking'] . "</p>";
            echo "<p>Location: " . $row['CityName'] . "</p>";
            echo "</div>";
        }
        ?>
    </main>
</body>
</html>
<?php include('includes/db.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Country Filtration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header><h1>Filter by Country</h1></header>
    <main>
        <div class="country-list">
            <?php
            $query = "SELECT * FROM COUNTRY";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    echo "<div class='card'>";
                    echo "<h2>" . $row['Name'] . "</h2>";
                    echo "<p>Visa Difficulty: " . $row['VisaDifficulty'] . "</p>";
                    echo "<p>Avg Living Cost: $" . $row['AvgLivingCost'] . "</p>";
                    echo "</div>";
                }
            } else {
                echo "No countries found.";
            }
            ?>
        </div>
    </main>
</body>
</html>
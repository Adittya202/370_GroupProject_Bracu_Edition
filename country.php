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
    <header><h1>Country Filtration</h1></header>

    <!-- Search Form -->
    <form method="GET" action="country.php" style="margin-bottom: 20px;">
        <input type="text" name="search" placeholder="Search for a country..." 
               value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
        <button type="submit">Search</button>
        <a href="country.php"><button type="button">Reset</button></a>
    </form>

    <div class="country-list">
        <?php
        // 1. Check if a search term exists
        $searchTerm = "";
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $searchTerm = mysqli_real_escape_string($conn, $_GET['search']);
            // 2. Query with a WHERE clause using LIKE for partial matches
            $query = "SELECT * FROM COUNTRY WHERE Name LIKE '%$searchTerm%'";
        } else {
            // 3. Default query if no search is performed
            $query = "SELECT * FROM COUNTRY";
        }

        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                echo "<div class='card' style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
                echo "<h2>" . $row['Name'] . "</h2>";
                echo "<p>Visa Difficulty: " . $row['VisaDifficulty'] . "</p>";
                echo "</div>";
            }
        } else {
            echo "<p>No countries found matching '$searchTerm'.</p>";
        }
        ?>
    </div>
</main>
</body>
</html>
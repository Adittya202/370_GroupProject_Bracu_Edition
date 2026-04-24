<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>University Selection</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header><h1>Select a University by Country</h1></header>
    <main>
        <!-- Filter Form -->
        <form method="GET" action="university.php" style="margin-bottom: 30px;">
            <label for="country_filter">Filter by Country: </label>
            <select name="country_id" id="country_filter" onchange="this.form.submit()">
                <option value="">-- Show All Countries --</option>
                <?php
                // Fetch all countries for the dropdown
                $country_query = "SELECT * FROM COUNTRY";
                $country_result = mysqli_query($conn, $country_query);
                while($c_row = mysqli_fetch_assoc($country_result)) {
                    $selected = (isset($_GET['country_id']) && $_GET['country_id'] == $c_row['CountryID']) ? 'selected' : '';
                    echo "<option value='" . $c_row['CountryID'] . "' $selected>" . $c_row['Name'] . "</option>";
                }
                ?>
            </select>
            <a href="university.php"><button type="button">Clear</button></a>
        </form>

        <div class="uni-list">
            <?php
            // Logic to filter Universities
            if (isset($_GET['country_id']) && !empty($_GET['country_id'])) {
                $cid = mysqli_real_escape_string($conn, $_GET['country_id']);
                // This JOIN connects University -> City -> Country
                $sql = "SELECT UNIVERSITY.Name, UNIVERSITY.Ranking, CITY.Name AS CityName 
                        FROM UNIVERSITY 
                        JOIN CITY ON UNIVERSITY.CityID = CITY.CityID 
                        WHERE CITY.CountryID = '$cid'";
            } else {
                $sql = "SELECT UNIVERSITY.Name, UNIVERSITY.Ranking, CITY.Name AS CityName 
                        FROM UNIVERSITY 
                        JOIN CITY ON UNIVERSITY.CityID = CITY.CityID";
            }

            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    echo "<div class='uni-card' style='border:1px solid #007bff; margin:10px; padding:15px; border-radius:5px;'>";
                    echo "<h3>" . $row['Name'] . "</h3>";
                    echo "<p>Ranking: #" . $row['Ranking'] . " | Location: " . $row['CityName'] . "</p>";
                    echo "</div>";
                }
            } else {
                echo "<p>No universities found for this selection.</p>";
            }
            ?>
        </div>
    </main>
</body>
</html>
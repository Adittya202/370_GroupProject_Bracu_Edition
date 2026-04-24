<?php
session_start();
if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit();}
include 'db_connect.php';

$country_sql = "SELECT * FROM COUNTRY";
if (isset($_POST['filter_btn'])) {
    $max_cost = mysqli_real_escape_string($conn, $_POST['cost']);
    $country_sql = "SELECT * FROM COUNTRY WHERE AvgLivingCost <= '$max_cost'";
}
$countries = mysqli_query($conn, $country_sql);

$programs = mysqli_query($conn, "SELECT * FROM PROGRAM");
$exams = mysqli_query($conn, "SELECT * FROM ENGLISH_EXAM");
$docs = mysqli_query($conn, "SELECT * FROM DOCUMENT");
?>

<!DOCTYPE html>
<html>
<head>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <title>Credit Transfer Dashboard</title>
     <style>
        .feature-card {border-radius: 15px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.08); margin-bottom:20px;}
        .nav-custom {background: #003060; color: white;}
    </style>
</head>
<body class="bg-light">
<nav class="navbar nav-custom px-4 py-3 mb-4">
    <span class="navbar-brand text-white fw-bold">Credit Transfer System</span>
    <div class="text-white">
        Welcome, <?= $_SESSION['user_name'] ?> | 
        <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card p-4 feature-card">
                <h4 class="mb-3">Country Filtration </h4>
                <form method="POST" class="row g-2 mb-3">
                    <div class="col-8"><input type="number" name="cost" class="form-control" placeholder="Max Avg Living Cost"></div>
                    <div class="col-4"><button type="submit" name="filter_btn" class="btn btn-primary w-100">Filter</button></div>
                </form>
                <table class="table align-middle">
                    <thead class="table-dark"><tr><th>Country</th><th>Visa</th><th>Living Cost</th><tr></tr><thead>
                     <tbody>
                        <?php while($c = mysqli_fetch_assoc($countries)): ?>
                            <tr><td><?= $c['Name'] ?></td><td><?= $c['VisaDifficulty'] ?></td><td>$<?= $c['AvgLivingCost'] ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 feature-card">
                <h4 class="mb-3">Program Selection</h4>
                <select class="form-select mb-3">
                    <option>Select a Program</option>
                    <?php while($p = mysqli_fetch_assoc($programs)): ?>
                        <option><?= $p['ProgramName'] ?></option>
                    <?php endwhile; ?>
                </select>
                <button class="btn btn-outline-dark w-100">Search Universities</button>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card p-4 feature-card">
                <h4 class="mb-3">English Exam Recommendations</h4>
                <div class="list-group">
                    <?php while($e = mysqli_fetch_assoc($exams)): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <?= $e['ExamName'] ?> <span class="badge bg-info">Verified</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-4 feature-card">
                <h4 class="mb-3">Required Documents</h4>
                <div class="row">
                    <?php while($d = mysqli_fetch_assoc($docs)): ?>
                        <div class="col-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox">
                                <label class="form-check-label"><?= $d['DocName'] ?></label>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>


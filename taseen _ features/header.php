<?php
session_start();
require_once 'db.php'; 

// TEMPORARY ADMIN HACK: Remove this once your teammate's login works!
$_SESSION['Role'] = 'admin'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Transfer System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">Transfer Portal</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="university.php">Universities</a></li>
        <li class="nav-item"><a class="nav-link" href="equivalency.php">Credit Equivalency</a></li>
        <li class="nav-item"><a class="nav-link" href="accommodation.php">Housing</a></li>
      </ul>
      <span class="navbar-text text-warning fw-bold">
        Role: <?php echo strtoupper($_SESSION['Role'] ?? 'GUEST'); ?>
      </span>
    </div>
  </div>
</nav>
<div class="container bg-white p-4 rounded shadow-sm">

<?php
// header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Transfer System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex: 1;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">CTS Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="results.php">Universities</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="courses.php">Courses</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sop_templates.php">SOP Templates</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-warning" href="transfer_checker.php"><i class="bi bi-calculator"></i> Transfer Checker</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['UserID'])): ?>
                    <?php if (isset($_SESSION['Role']) && $_SESSION['Role'] === 'Admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Admin Panel
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item" href="admin_universities.php">Manage Universities</a></li>
                                <li><a class="dropdown-item" href="admin_locations.php">Manage Locations</a></li>
                                <li><a class="dropdown-item" href="admin_accommodations.php">Manage Accommodations</a></li>
                                <li><a class="dropdown-item" href="admin_programs.php">Manage Programs</a></li>
                                <li><a class="dropdown-item" href="admin_courses.php">Manage Courses</a></li>
                                <li><a class="dropdown-item" href="admin_course_equivalents.php">Manage Equivalencies</a></li>
                                <li><a class="dropdown-item" href="admin_exams_docs.php">Manage Exams & Docs</a></li>
                                <li><a class="dropdown-item" href="admin_sops.php">Manage SOP Templates</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin_dashboard.php">Dashboard</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">My Dashboard</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item d-flex align-items-center">
                        <span class="nav-link text-light border-start ms-2 ps-3 d-none d-lg-block">Hello, <?php echo htmlspecialchars($_SESSION['Name'] ?? 'User'); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2 px-3 text-white" href="logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary ms-2 px-3 fw-bold" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container my-5">

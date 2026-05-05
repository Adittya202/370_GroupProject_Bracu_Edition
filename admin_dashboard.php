<?php
// admin_dashboard.php
require_once 'db.php';
require_once 'header.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Quick stats queries
$uniCount = $conn->query("SELECT COUNT(*) as cnt FROM university")->fetch_assoc()['cnt'] ?? 0;
$sopCount = $conn->query("SELECT COUNT(*) as cnt FROM sop_template")->fetch_assoc()['cnt'] ?? 0;
$courseCount = $conn->query("SELECT COUNT(*) as cnt FROM course")->fetch_assoc()['cnt'] ?? 0;
$programCount = $conn->query("SELECT COUNT(*) as cnt FROM program")->fetch_assoc()['cnt'] ?? 0;
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold">Admin Dashboard</h2>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['Name']); ?>! Here's an overview of the system.</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow-sm h-100 border-0 rounded-4">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title opacity-75">Universities</h5>
                    <h2 class="display-5 fw-bold mb-0"><?php echo $uniCount; ?></h2>
                </div>
                <div class="mt-3">
                    <a href="admin_universities.php" class="text-white text-decoration-none fw-semibold">Manage &rarr;</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white shadow-sm h-100 border-0 rounded-4">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title opacity-75">SOP Templates</h5>
                    <h2 class="display-5 fw-bold mb-0"><?php echo $sopCount; ?></h2>
                </div>
                <div class="mt-3">
                    <a href="admin_sops.php" class="text-white text-decoration-none fw-semibold">Manage &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-info text-white shadow-sm h-100 border-0 rounded-4">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title opacity-75">Programs</h5>
                    <h2 class="display-5 fw-bold mb-0"><?php echo $programCount; ?></h2>
                </div>
                <div class="mt-3">
                    <a href="admin_programs.php" class="text-white text-decoration-none fw-semibold">View &rarr;</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-dark shadow-sm h-100 border-0 rounded-4">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title opacity-75">Courses</h5>
                    <h2 class="display-5 fw-bold mb-0"><?php echo $courseCount; ?></h2>
                </div>
                <div class="mt-3">
                    <a href="admin_courses.php" class="text-dark text-decoration-none fw-semibold">View &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <h4 class="card-title fw-bold mb-3">Quick Actions</h4>
                <div class="d-flex flex-wrap gap-2">
                    <a href="admin_universities.php" class="btn btn-outline-primary">Add New University</a>
                    <a href="admin_sops.php" class="btn btn-outline-success">Manage SOPs</a>
                    <a href="admin_exams_docs.php" class="btn btn-outline-info">Manage Requirements</a>
                    <a href="admin_accommodations.php" class="btn btn-outline-secondary">Manage Accommodations</a>
                    <a href="admin_locations.php" class="btn btn-outline-dark">Manage Locations</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>

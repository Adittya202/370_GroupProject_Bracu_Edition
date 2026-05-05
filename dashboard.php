<?php
// dashboard.php
require_once 'db.php';
require_once 'header.php';

// Ensure user is logged in and is a regular 'User'
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'User') {
    header("Location: login.php");
    exit;
}

// Fetch some basic stats for the dashboard
$saved_sops = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_saves_sop WHERE UserID = ?");
$stmt->bind_param("i", $_SESSION['UserID']);
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) {
    $saved_sops = $row['count'];
}
$stmt->close();
?>

<!-- Add Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .dash-hero {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
        padding: 4rem 0;
        border-radius: 1.5rem;
        box-shadow: 0 10px 30px rgba(13, 110, 253, 0.2);
        margin-bottom: 3rem;
    }
    .action-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 1.2rem;
    }
    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }
</style>

<div class="dash-hero text-center px-3">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Student Dashboard</h1>
        <p class="lead mb-0 opacity-75">Welcome back, <?php echo htmlspecialchars($_SESSION['Name']); ?>!</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 action-card h-100 text-center p-3">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3 mt-2">
                        <i class="bi bi-search text-primary" style="font-size: 3.5rem;"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Find Universities</h4>
                    <p class="text-muted mb-4">Search our global database to discover partner universities that offer your program.</p>
                    <a href="index.php" class="btn btn-outline-primary fw-bold mt-auto rounded-pill mx-4">Search Now</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm border-0 action-card h-100 text-center p-3">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3 mt-2">
                        <i class="bi bi-calculator-fill text-success" style="font-size: 3.5rem;"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Transfer Checker</h4>
                    <p class="text-muted mb-4">Calculate exactly how many credits you can transfer abroad based on your completed courses.</p>
                    <a href="transfer_checker.php" class="btn btn-success fw-bold mt-auto rounded-pill mx-4 shadow-sm">Check Credits</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm border-0 action-card h-100 text-center p-3">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3 mt-2">
                        <i class="bi bi-journal-check text-warning" style="font-size: 3.5rem;"></i>
                    </div>
                    <h4 class="fw-bold text-dark">SOP Templates</h4>
                    <p class="text-muted mb-4">You have <strong><?php echo $saved_sops; ?></strong> saved templates. View and use them for your applications.</p>
                    <a href="sop_templates.php" class="btn btn-outline-warning fw-bold text-dark mt-auto rounded-pill mx-4">View Templates</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>

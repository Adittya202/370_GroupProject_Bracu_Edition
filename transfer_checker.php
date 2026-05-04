<?php
// transfer_checker.php
require_once 'db.php';
require_once 'header.php';

// Fetch all programs first to ensure even empty ones are listed
$all_courses = [];
$pQuery = $conn->query("SELECT ProgramName FROM program ORDER BY ProgramName");
if ($pQuery) {
    while ($row = $pQuery->fetch_assoc()) {
        $all_courses[$row['ProgramName']] = [];
    }
}

// Fetch all courses grouped by program for the selection form
$cQuery = $conn->query("SELECT c.*, COALESCE(p.ProgramName, 'Uncategorized') as ProgramName FROM course c LEFT JOIN program p ON c.ProgramID = p.ProgramID ORDER BY p.ProgramName, c.CourseCode");
if ($cQuery) {
    while ($row = $cQuery->fetch_assoc()) {
        if (!isset($all_courses[$row['ProgramName']])) {
            $all_courses[$row['ProgramName']] = [];
        }
        $all_courses[$row['ProgramName']][] = $row;
    }
}

$results = [];
$total_credits = 0;
$has_searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_transfers'])) {
    $has_searched = true;
    $selected_courses = $_POST['courses'] ?? [];
    
    if (!empty($selected_courses)) {
        // Sanitize arrays for integer values to prevent SQL injection
        $selected_courses = array_map('intval', $selected_courses);
        $in_clause = implode(',', $selected_courses);
        
        $sql = "
            SELECT 
                ce.TransferableCredits,
                c1.CourseCode AS LocalCode, c1.Title AS LocalTitle,
                c2.CourseCode AS HostCode, c2.Title AS HostTitle,
                p.ProgramName AS HostProgram
            FROM course_equivalent ce
            JOIN course c1 ON ce.CourseID = c1.CourseID
            JOIN course c2 ON ce.EquivalentCourseID = c2.CourseID
            JOIN program p ON c2.ProgramID = p.ProgramID
            WHERE ce.CourseID IN ($in_clause)
        ";
        
        $resQuery = $conn->query($sql);
        if ($resQuery) {
            while ($row = $resQuery->fetch_assoc()) {
                $results[] = $row;
                $total_credits += $row['TransferableCredits'];
            }
        }
    }
}
?>

<style>
    .checker-header {
        background: linear-gradient(135deg, #198754 0%, #146c43 100%);
        color: white;
        padding: 5rem 0;
        border-radius: 1.5rem;
        margin-bottom: 3rem;
        box-shadow: 0 10px 30px rgba(25, 135, 84, 0.2);
    }
    .course-checkbox-card {
        border: 2px solid transparent;
        background-color: #f8f9fa;
        border-radius: 0.8rem;
        padding: 1rem 1.2rem;
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
    }
    .course-checkbox-card:hover {
        background-color: #e9ecef;
    }
    .form-check-input:checked + .form-check-label {
        color: #198754;
    }
    .course-checkbox-card:has(.form-check-input:checked) {
        border-color: #198754;
        background-color: rgba(25, 135, 84, 0.05);
    }
    .sticky-panel {
        position: sticky;
        top: 2rem;
        z-index: 10;
    }
</style>

<div class="checker-header text-center">
    <div class="container">
        <h1 class="display-4 fw-bolder mb-3"><i class="bi bi-calculator-fill me-2"></i> Credit Transfer Calculator</h1>
        <p class="lead mb-0 opacity-75">Select the courses you have successfully completed locally to instantly discover how many credits you can transfer abroad.</p>
    </div>
</div>

<div class="row g-5 mb-5">
    <!-- Left Column: The Form -->
    <div class="col-lg-5">
        <div class="card shadow border-0 rounded-4 sticky-panel">
            <div class="card-body p-4 p-md-5">
                <h4 class="fw-bold mb-4 text-dark">Your Completed Courses</h4>
                <form method="POST" action="transfer_checker.php">
                    <input type="hidden" name="check_transfers" value="1">
                    
                    <div class="accordion" id="coursesAccordion">
                        <?php 
                        $i = 0;
                        foreach ($all_courses as $programName => $courses): 
                            $i++;
                        ?>
                            <div class="accordion-item border-0 mb-3 shadow-sm rounded-4 overflow-hidden">
                                <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                                    <button class="accordion-button <?php echo $i > 1 ? 'collapsed' : ''; ?> bg-light text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $i; ?>">
                                        <?php echo htmlspecialchars($programName); ?>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $i; ?>" class="accordion-collapse collapse <?php echo $i === 1 ? 'show' : ''; ?>" data-bs-parent="#coursesAccordion">
                                    <div class="accordion-body bg-white pt-3 pb-2 px-3">
                                        <?php if (count($courses) > 0): ?>
                                            <?php foreach ($courses as $c): ?>
                                                <div class="form-check course-checkbox-card mb-2 d-flex align-items-center">
                                                    <input class="form-check-input mt-0 me-3" style="transform: scale(1.3);" type="checkbox" name="courses[]" value="<?php echo $c['CourseID']; ?>" id="course_<?php echo $c['CourseID']; ?>" <?php echo (isset($_POST['courses']) && in_array($c['CourseID'], $_POST['courses'])) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label w-100 m-0" style="cursor: pointer;" for="course_<?php echo $c['CourseID']; ?>">
                                                        <span class="fw-bold fs-5 d-block mb-1"><?php echo htmlspecialchars($c['CourseCode']); ?></span>
                                                        <span class="text-muted d-block mb-2"><?php echo htmlspecialchars($c['Title']); ?></span>
                                                        <span class="badge bg-secondary rounded-pill"><?php echo $c['Credits']; ?> Credits</span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-center text-muted p-3 fst-italic">
                                                <i class="bi bi-inbox d-block fs-4 mb-2"></i>
                                                No local courses available in this program yet.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="d-grid mt-4 pt-2 border-top">
                        <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm py-3">
                            Calculate Transfers <i class="bi bi-arrow-right-circle-fill ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: The Results -->
    <div class="col-lg-7">
        <?php if ($has_searched): ?>
            <div class="card shadow border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                    <h3 class="fw-bold mb-0 text-success"><i class="bi bi-check-circle-fill me-2"></i> Transfer Results</h3>
                    <div class="text-end bg-success text-white px-4 py-2 rounded-4 shadow-sm">
                        <span class="d-block small fw-bold text-uppercase opacity-75">Total Potential Credits</span>
                        <span class="display-5 fw-bold"><?php echo $total_credits; ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (count($results) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th class="ps-4 py-3 border-0">Your Course</th>
                                        <th class="border-0"></th>
                                        <th class="border-0">Equivalent Host Course</th>
                                        <th class="border-0">Target Program</th>
                                        <th class="text-end pe-4 border-0">Transferable</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $r): ?>
                                        <tr>
                                            <td class="ps-4 py-4">
                                                <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($r['LocalCode']); ?></span>
                                                <small class="text-muted"><?php echo htmlspecialchars($r['LocalTitle']); ?></small>
                                            </td>
                                            <td class="text-center text-muted"><i class="bi bi-arrow-right fw-bold fs-4 text-warning"></i></td>
                                            <td class="py-4">
                                                <span class="fw-bold text-primary d-block"><?php echo htmlspecialchars($r['HostCode']); ?></span>
                                                <small class="text-muted"><?php echo htmlspecialchars($r['HostTitle']); ?></small>
                                            </td>
                                            <td><span class="badge bg-light text-dark border p-2"><?php echo htmlspecialchars($r['HostProgram']); ?></span></td>
                                            <td class="text-end pe-4">
                                                <span class="badge bg-success rounded-pill px-3 py-2 fs-6 shadow-sm">+<?php echo $r['TransferableCredits']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center my-5">
                            <i class="bi bi-shield-x text-warning mb-3" style="font-size: 4rem;"></i>
                            <h4 class="fw-bold">No Equivalencies Found</h4>
                            <p class="text-muted lead mx-auto" style="max-width: 400px;">Unfortunately, none of the courses you selected currently map to international equivalent courses in our system.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-light border border-dashed" style="border-width: 2px !important; border-color: #dee2e6 !important;">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-5 opacity-75">
                    <div class="bg-white p-4 rounded-circle shadow-sm mb-4">
                        <i class="bi bi-list-check text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-3">Awaiting Selection</h3>
                    <p class="lead text-muted mx-auto mb-0" style="max-width: 400px;">Select your completed courses from the panel on the left and click <strong>Calculate Transfers</strong> to generate your personalized credit report.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<?php
require_once 'footer.php';
?>

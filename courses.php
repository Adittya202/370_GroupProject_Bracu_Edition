<?php
// courses.php
require_once 'db.php';
require_once 'header.php';

// Fetch all courses
$courses = [];
$query = "
    SELECT c.*, p.ProgramName, GROUP_CONCAT(u.Name ORDER BY u.Name SEPARATOR ', ') as Universities 
    FROM course c 
    JOIN program p ON c.ProgramID = p.ProgramID 
    LEFT JOIN offers o ON p.ProgramID = o.ProgramID
    LEFT JOIN university u ON o.UniID = u.UniID
    GROUP BY c.CourseID
    ORDER BY p.ProgramName, c.CourseCode
";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>

<div class="container mt-5 pt-3 mb-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bolder text-dark mb-3">Course Directory</h1>
        <p class="lead text-muted mx-auto" style="max-width: 600px;">Browse our comprehensive list of academic courses available for credit transfer analysis.</p>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4 py-3 border-0">Course Code</th>
                            <th class="py-3 border-0">Course Title</th>
                            <th class="py-3 border-0">Program</th>
                            <th class="py-3 border-0">Available At</th>
                            <th class="text-end pe-4 py-3 border-0">Credits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($courses) > 0): ?>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <span class="fw-bold text-primary"><?php echo htmlspecialchars($c['CourseCode']); ?></span>
                                    </td>
                                    <td class="py-3 fw-semibold text-dark">
                                        <?php echo htmlspecialchars($c['Title']); ?>
                                    </td>
                                    <td class="py-3 text-muted">
                                        <i class="bi bi-mortarboard me-1"></i> <?php echo htmlspecialchars($c['ProgramName']); ?>
                                    </td>
                                    <td class="py-3" style="max-width: 250px;">
                                        <?php if (!empty($c['Universities'])): ?>
                                            <span class="text-secondary small d-inline-block text-truncate w-100" title="<?php echo htmlspecialchars($c['Universities']); ?>">
                                                <i class="bi bi-building me-1"></i><?php echo htmlspecialchars($c['Universities']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Not currently offered</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4 py-3">
                                        <span class="badge bg-secondary rounded-pill px-3 py-2 shadow-sm"><?php echo $c['Credits']; ?> CR</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="fw-bold mt-3">No courses found</h5>
                                    <p class="mb-0">There are currently no courses available in the directory.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-5">
        <div class="d-inline-block bg-light px-4 py-3 rounded-pill border shadow-sm">
            <p class="text-muted mb-0">Want to see how these courses transfer? 
                <a href="transfer_checker.php" class="fw-bold text-success text-decoration-none ms-2">
                    <i class="bi bi-calculator-fill me-1"></i> Use the Transfer Checker <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </p>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<?php
require_once 'footer.php';
?>

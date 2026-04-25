<?php
require_once 'header.php';
$role = $_SESSION['Role'];

// Admin Actions
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO COURSE_EQUIVALENT (CourseID, EquivalentCourseID, TransferableCredits) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $_POST['course_id'], $_POST['equivalent_course_id'], $_POST['transferable_credits']);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM COURSE_EQUIVALENT WHERE EquivID = ?");
            $stmt->bind_param("i", $_POST['equiv_id']);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: equivalency.php");
        exit;
    }
}

// Search Logic
$searchCode = $_GET['searchCode'] ?? '';
$query = "
    SELECT 
        ce.EquivID,
        c1.CourseCode AS LocalCode, c1.Title AS LocalTitle,
        c2.CourseCode AS ForeignCode, c2.Title AS ForeignTitle,
        ce.TransferableCredits
    FROM COURSE_EQUIVALENT ce
    JOIN COURSE c1 ON ce.CourseID = c1.CourseID
    JOIN COURSE c2 ON ce.EquivalentCourseID = c2.CourseID
";

if ($searchCode !== '') {
    $query .= " WHERE c1.CourseCode LIKE ?";
    $stmt = $conn->prepare($query);
    $searchParam = "%" . $searchCode . "%";
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$equivalencies = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch courses for admin form dropdowns
$courses = [];
if ($role === 'admin') {
    $courseResult = $conn->query("SELECT CourseID, CourseCode, Title FROM COURSE");
    if ($courseResult) $courses = $courseResult->fetch_all(MYSQLI_ASSOC);
}
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Credit Equivalency Mappings</h2>
        <span class="badge bg-secondary">Role: <?= htmlspecialchars($role) ?></span>
    </div>

    <!-- Search Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="equivalency.php" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="searchCode" class="form-control" placeholder="Search by Local Course Code (e.g. CS101)" value="<?= htmlspecialchars($searchCode) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($role === 'admin'): ?>
    <!-- Add New Equivalency Form -->
    <div class="card mb-4 shadow-sm border-primary">
        <div class="card-header bg-primary text-white">Add New Equivalency</div>
        <div class="card-body">
            <form method="POST" action="equivalency.php" class="row g-3">
                <input type="hidden" name="action" value="add">
                <div class="col-md-4">
                    <label>Local Course</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Local Course...</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['CourseID'] ?>"><?= htmlspecialchars($c['CourseCode'] . ' - ' . $c['Title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Foreign Equivalent Course</label>
                    <select name="equivalent_course_id" class="form-control" required>
                        <option value="">Select Foreign Course...</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['CourseID'] ?>"><?= htmlspecialchars($c['CourseCode'] . ' - ' . $c['Title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Transferable Credits</label>
                    <input type="number" name="transferable_credits" class="form-control" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Add Mapping</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Equivalency Data Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Local Code</th>
                            <th>Local Title</th>
                            <th>Foreign Code</th>
                            <th>Foreign Title</th>
                            <th>Credits Transferable</th>
                            <?php if ($role === 'admin'): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($equivalencies)): ?>
                            <tr><td colspan="<?= ($role === 'admin') ? 6 : 5 ?>" class="text-center">No equivalencies found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($equivalencies as $equiv): ?>
                                <tr>
                                    <td><?= htmlspecialchars($equiv['LocalCode']) ?></td>
                                    <td><?= htmlspecialchars($equiv['LocalTitle']) ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($equiv['ForeignCode']) ?></span></td>
                                    <td><?= htmlspecialchars($equiv['ForeignTitle']) ?></td>
                                    <td><strong><?= htmlspecialchars($equiv['TransferableCredits']) ?></strong></td>
                                    
                                    <?php if ($role === 'admin'): ?>
                                    <td>
                                        <form method="POST" action="equivalency.php" class="d-inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="equiv_id" value="<?= $equiv['EquivID'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this mapping?');">Delete</button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php require_once 'footer.php'; ?>

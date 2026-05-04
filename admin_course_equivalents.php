<?php
// admin_course_equivalents.php
require_once 'db.php';
require_once 'header.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$msgType = '';

// Handle POST actions for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $courseId = (int)$_POST['course_id'];
            $equivCourseId = (int)$_POST['equiv_course_id'];
            $transferCredits = (int)$_POST['transfer_credits'];

            if ($courseId === $equivCourseId) {
                $message = "A course cannot be equivalent to itself.";
                $msgType = "danger";
            } else {
                // Check if mapping already exists
                $checkStmt = $conn->prepare("SELECT 1 FROM course_equivalent WHERE CourseID=? AND EquivalentCourseID=?");
                $checkStmt->bind_param("ii", $courseId, $equivCourseId);
                $checkStmt->execute();
                if ($checkStmt->get_result()->num_rows > 0) {
                    $message = "This equivalency already exists.";
                    $msgType = "danger";
                } else {
                    $stmt = $conn->prepare("INSERT INTO course_equivalent (CourseID, EquivalentCourseID, TransferableCredits) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $courseId, $equivCourseId, $transferCredits);
                    
                    if ($stmt->execute()) {
                        $message = "Equivalency added successfully.";
                        $msgType = "success";
                    } else {
                        $message = "Error adding equivalency: " . $stmt->error;
                        $msgType = "danger";
                    }
                    $stmt->close();
                }
                $checkStmt->close();
            }
        } elseif ($action === 'edit') {
            $courseId = (int)$_POST['course_id'];
            $equivCourseId = (int)$_POST['equiv_course_id'];
            $transferCredits = (int)$_POST['transfer_credits'];

            $stmt = $conn->prepare("UPDATE course_equivalent SET TransferableCredits=? WHERE CourseID=? AND EquivalentCourseID=?");
            $stmt->bind_param("iii", $transferCredits, $courseId, $equivCourseId);
            
            if ($stmt->execute()) {
                $message = "Equivalency updated successfully.";
                $msgType = "success";
            } else {
                $message = "Error updating equivalency: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        } elseif ($action === 'delete') {
            $courseId = (int)$_POST['course_id'];
            $equivCourseId = (int)$_POST['equiv_course_id'];
            
            $stmt = $conn->prepare("DELETE FROM course_equivalent WHERE CourseID=? AND EquivalentCourseID=?");
            $stmt->bind_param("ii", $courseId, $equivCourseId);
            
            if ($stmt->execute()) {
                $message = "Equivalency deleted successfully.";
                $msgType = "success";
            } else {
                $message = "Error deleting equivalency: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        }
    }
}

// Fetch all courses for dropdowns
$courses = [];
$cQuery = $conn->query("SELECT CourseID, CourseCode, Title FROM course ORDER BY CourseCode");
if ($cQuery) {
    while ($row = $cQuery->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Fetch all equivalencies
$equivalencies = [];
$eqQuery = $conn->query("
    SELECT ce.*, 
           c1.CourseCode as LocalCode, c1.Title as LocalTitle,
           c2.CourseCode as EquivCode, c2.Title as EquivTitle
    FROM course_equivalent ce
    JOIN course c1 ON ce.CourseID = c1.CourseID
    JOIN course c2 ON ce.EquivalentCourseID = c2.CourseID
    ORDER BY c1.CourseCode, c2.CourseCode
");
if ($eqQuery) {
    while ($row = $eqQuery->fetch_assoc()) {
        $equivalencies[] = $row;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Course Equivalencies</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Equivalencies</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add New Mapping
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">Local Course</th>
                    <th><span class="text-warning fw-bold">&rarr;</span> Mapped To</th>
                    <th>Host Course</th>
                    <th>Transfer Credits</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($equivalencies) > 0): ?>
                    <?php foreach ($equivalencies as $eq): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-primary"><?php echo htmlspecialchars($eq['LocalCode']); ?></span><br>
                                <small class="text-muted"><?php echo htmlspecialchars($eq['LocalTitle']); ?></small>
                            </td>
                            <td><span class="text-muted">&rarr;</span></td>
                            <td>
                                <span class="fw-bold text-success"><?php echo htmlspecialchars($eq['EquivCode']); ?></span><br>
                                <small class="text-muted"><?php echo htmlspecialchars($eq['EquivTitle']); ?></small>
                            </td>
                            <td><span class="badge bg-secondary rounded-pill px-3 py-2"><?php echo $eq['TransferableCredits']; ?> Credits</span></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-secondary edit-btn me-1" 
                                    data-c1="<?php echo $eq['CourseID']; ?>"
                                    data-c2="<?php echo $eq['EquivalentCourseID']; ?>"
                                    data-credits="<?php echo $eq['TransferableCredits']; ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    Edit
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Remove this equivalency mapping?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="course_id" value="<?php echo $eq['CourseID']; ?>">
                                    <input type="hidden" name="equiv_course_id" value="<?php echo $eq['EquivalentCourseID']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No course equivalencies found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_course_equivalents.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Map Course Equivalency</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Local Course <span class="text-danger">*</span></label>
                    <select name="course_id" class="form-select" required>
                        <option value="">Select Local Course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['CourseID']; ?>">
                                <?php echo htmlspecialchars($c['CourseCode'] . ' - ' . $c['Title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3 text-center text-muted">
                    <span class="fw-bold fs-4">&darr;</span>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Host (Equivalent) Course <span class="text-danger">*</span></label>
                    <select name="equiv_course_id" class="form-select" required>
                        <option value="">Select Host Course</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['CourseID']; ?>">
                                <?php echo htmlspecialchars($c['CourseCode'] . ' - ' . $c['Title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Transferable Credits <span class="text-danger">*</span></label>
                    <input type="number" name="transfer_credits" class="form-control" required min="1" placeholder="e.g. 3">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Save Mapping</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_course_equivalents.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white border-0">
                <h5 class="modal-title fw-bold">Edit Transfer Credits</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="course_id" id="edit_course_id">
                <input type="hidden" name="equiv_course_id" id="edit_equiv_course_id">
                
                <div class="alert alert-info border-0 shadow-sm">
                    You are editing the transferable credits for an existing mapping. To change the mapped courses themselves, delete this mapping and create a new one.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Transferable Credits <span class="text-danger">*</span></label>
                    <input type="number" name="transfer_credits" id="edit_transfer_credits" class="form-control" required min="1">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Update Credits</button>
            </div>
        </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_course_id').value = this.getAttribute('data-c1');
            document.getElementById('edit_equiv_course_id').value = this.getAttribute('data-c2');
            document.getElementById('edit_transfer_credits').value = this.getAttribute('data-credits');
        });
    });
});
</script>

<?php
require_once 'footer.php';
?>

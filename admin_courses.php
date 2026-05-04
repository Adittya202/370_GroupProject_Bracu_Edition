<?php
// admin_courses.php
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
            $code = trim($_POST['code']);
            $title = trim($_POST['title']);
            $credits = (int)$_POST['credits'];
            $progId = (int)$_POST['program_id'];

            $stmt = $conn->prepare("INSERT INTO course (CourseCode, Title, Credits, ProgramID) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $code, $title, $credits, $progId);
            
            if ($stmt->execute()) {
                $message = "Course added successfully.";
                $msgType = "success";
            } else {
                $message = "Error adding course: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $courseId = (int)$_POST['course_id'];
            $code = trim($_POST['code']);
            $title = trim($_POST['title']);
            $credits = (int)$_POST['credits'];
            $progId = (int)$_POST['program_id'];

            $stmt = $conn->prepare("UPDATE course SET CourseCode=?, Title=?, Credits=?, ProgramID=? WHERE CourseID=?");
            $stmt->bind_param("ssiii", $code, $title, $credits, $progId, $courseId);
            
            if ($stmt->execute()) {
                $message = "Course updated successfully.";
                $msgType = "success";
            } else {
                $message = "Error updating course: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        } elseif ($action === 'delete') {
            $courseId = (int)$_POST['course_id'];
            $stmt = $conn->prepare("DELETE FROM course WHERE CourseID=?");
            $stmt->bind_param("i", $courseId);
            
            if ($stmt->execute()) {
                $message = "Course deleted successfully.";
                $msgType = "success";
            } else {
                $message = "Error deleting course: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        }
    }
}

// Fetch all programs for dropdowns
$programs = [];
$progQuery = $conn->query("SELECT * FROM program ORDER BY ProgramName");
if ($progQuery) {
    while ($row = $progQuery->fetch_assoc()) {
        $programs[] = $row;
    }
}

// Fetch all courses
$courses = [];
$query = "
    SELECT c.*, p.ProgramName, GROUP_CONCAT(u.Name ORDER BY u.Name SEPARATOR ', ') as Universities 
    FROM course c 
    JOIN program p ON c.ProgramID = p.ProgramID 
    LEFT JOIN offers o ON p.ProgramID = o.ProgramID
    LEFT JOIN university u ON o.UniID = u.UniID
    GROUP BY c.CourseID
    ORDER BY c.CourseCode
";
$courseQuery = $conn->query($query);
if ($courseQuery) {
    while ($row = $courseQuery->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Manage Courses</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Courses</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add New Course
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
                    <th class="ps-4">ID</th>
                    <th>Course Code</th>
                    <th>Title</th>
                    <th>Credits</th>
                    <th>Program</th>
                    <th>Offered At</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($courses) > 0): ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td class="ps-4"><?php echo $course['CourseID']; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($course['CourseCode']); ?></td>
                            <td class="fw-semibold text-primary"><?php echo htmlspecialchars($course['Title']); ?></td>
                            <td><span class="badge bg-secondary rounded-pill px-3 py-2"><?php echo $course['Credits']; ?> Credits</span></td>
                            <td><?php echo htmlspecialchars($course['ProgramName']); ?></td>
                            <td>
                                <?php if (!empty($course['Universities'])): ?>
                                    <span class="d-inline-block text-truncate text-secondary small" style="max-width: 150px;" title="<?php echo htmlspecialchars($course['Universities']); ?>">
                                        <?php echo htmlspecialchars($course['Universities']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-secondary edit-btn me-1" 
                                    data-id="<?php echo $course['CourseID']; ?>"
                                    data-code="<?php echo htmlspecialchars($course['CourseCode']); ?>"
                                    data-title="<?php echo htmlspecialchars($course['Title']); ?>"
                                    data-credits="<?php echo $course['Credits']; ?>"
                                    data-prog="<?php echo $course['ProgramID']; ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    Edit
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="course_id" value="<?php echo $course['CourseID']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No courses found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_courses.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Add New Course</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3 p-4">
                <input type="hidden" name="action" value="add">
                
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Course Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required placeholder="e.g. CS101">
                </div>
                
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Course Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Intro to Programming">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Credits <span class="text-danger">*</span></label>
                    <input type="number" name="credits" class="form-control" required min="1">
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Belongs to Program <span class="text-danger">*</span></label>
                    <select name="program_id" class="form-select" required>
                        <option value="">Select Program</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?php echo $prog['ProgramID']; ?>">
                                <?php echo htmlspecialchars($prog['ProgramName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Save Course</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_courses.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white border-0">
                <h5 class="modal-title fw-bold">Edit Course</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3 p-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="course_id" id="edit_course_id">
                
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Course Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="edit_code" class="form-control" required>
                </div>
                
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Course Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Credits <span class="text-danger">*</span></label>
                    <input type="number" name="credits" id="edit_credits" class="form-control" required min="1">
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Belongs to Program <span class="text-danger">*</span></label>
                    <select name="program_id" id="edit_program_id" class="form-select" required>
                        <option value="">Select Program</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?php echo $prog['ProgramID']; ?>">
                                <?php echo htmlspecialchars($prog['ProgramName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Update Course</button>
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
            document.getElementById('edit_course_id').value = this.getAttribute('data-id');
            document.getElementById('edit_code').value = this.getAttribute('data-code');
            document.getElementById('edit_title').value = this.getAttribute('data-title');
            document.getElementById('edit_credits').value = this.getAttribute('data-credits');
            document.getElementById('edit_program_id').value = this.getAttribute('data-prog');
        });
    });
});
</script>

<?php
require_once 'footer.php';
?>

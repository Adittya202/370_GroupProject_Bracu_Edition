<?php
// admin_programs.php
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
            $name = trim($_POST['name']);
            $uni_ids = isset($_POST['uni_ids']) ? $_POST['uni_ids'] : [];
            
            $stmt = $conn->prepare("INSERT INTO program (ProgramName) VALUES (?)");
            $stmt->bind_param("s", $name);
            
            if ($stmt->execute()) {
                $progId = $conn->insert_id;
                foreach ($uni_ids as $uid) {
                    $uid = (int)$uid;
                    $conn->query("INSERT INTO offers (ProgramID, UniID) VALUES ($progId, $uid)");
                }
                $message = "Program added successfully.";
                $msgType = "success";
            } else {
                $message = "Error adding program: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $progId = (int)$_POST['prog_id'];
            $name = trim($_POST['name']);
            $uni_ids = isset($_POST['uni_ids']) ? $_POST['uni_ids'] : [];
            
            $stmt = $conn->prepare("UPDATE program SET ProgramName=? WHERE ProgramID=?");
            $stmt->bind_param("si", $name, $progId);
            
            if ($stmt->execute()) {
                $conn->query("DELETE FROM offers WHERE ProgramID = $progId");
                foreach ($uni_ids as $uid) {
                    $uid = (int)$uid;
                    $conn->query("INSERT INTO offers (ProgramID, UniID) VALUES ($progId, $uid)");
                }
                $message = "Program updated successfully.";
                $msgType = "success";
            } else {
                $message = "Error updating program: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        } elseif ($action === 'delete') {
            $progId = (int)$_POST['prog_id'];
            try {
                // 1. Delete associated offers
                $conn->query("DELETE FROM offers WHERE ProgramID = $progId");
                
                // 2. Find all courses under this program
                $cRes = $conn->query("SELECT CourseID FROM course WHERE ProgramID = $progId");
                if ($cRes) {
                    while ($cRow = $cRes->fetch_assoc()) {
                        $cId = (int)$cRow['CourseID'];
                        // 3. Delete course equivalents for these courses
                        $conn->query("DELETE FROM course_equivalent WHERE CourseID = $cId OR EquivalentCourseID = $cId");
                    }
                }
                
                // 4. Delete the courses
                $conn->query("DELETE FROM course WHERE ProgramID = $progId");
                
                // 5. Finally, delete the program
                $stmt = $conn->prepare("DELETE FROM program WHERE ProgramID=?");
                $stmt->bind_param("i", $progId);
                
                if ($stmt->execute()) {
                    $message = "Program and all its associated courses were deleted successfully.";
                    $msgType = "success";
                } else {
                    $message = "Error deleting program: " . $stmt->error;
                    $msgType = "danger";
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1451) {
                    $message = "Cannot delete this program because it is still linked to other records. Please review its dependencies.";
                } else {
                    $message = "Database error: " . $e->getMessage();
                }
                $msgType = "danger";
            }
        }
    }
}

// Fetch all universities for dropdowns
$universities = [];
$uniQuery = $conn->query("SELECT UniID, Name FROM university ORDER BY Name");
if ($uniQuery) {
    while ($row = $uniQuery->fetch_assoc()) {
        $universities[] = $row;
    }
}

// Fetch all programs
$programs = [];
$progQuery = $conn->query("
    SELECT p.*, GROUP_CONCAT(u.Name ORDER BY u.Name SEPARATOR ', ') as Universities,
           GROUP_CONCAT(u.UniID SEPARATOR ',') as UniIDs
    FROM program p
    LEFT JOIN offers o ON p.ProgramID = o.ProgramID
    LEFT JOIN university u ON o.UniID = u.UniID
    GROUP BY p.ProgramID
    ORDER BY p.ProgramName
");
if ($progQuery) {
    while ($row = $progQuery->fetch_assoc()) {
        $programs[] = $row;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Manage Programs</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Programs</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add New Program
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
                    <th class="ps-4" width="10%">ID</th>
                    <th>Program Name</th>
                    <th>Offered At</th>
                    <th class="text-end pe-4" width="20%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($programs) > 0): ?>
                    <?php foreach ($programs as $prog): ?>
                        <tr>
                            <td class="ps-4"><?php echo $prog['ProgramID']; ?></td>
                            <td class="fw-semibold text-primary"><?php echo htmlspecialchars($prog['ProgramName']); ?></td>
                            <td>
                                <?php if (!empty($prog['Universities'])): ?>
                                    <span class="d-inline-block text-truncate text-secondary small" style="max-width: 250px;" title="<?php echo htmlspecialchars($prog['Universities']); ?>">
                                        <?php echo htmlspecialchars($prog['Universities']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-secondary edit-btn me-1" 
                                    data-id="<?php echo $prog['ProgramID']; ?>"
                                    data-name="<?php echo htmlspecialchars($prog['ProgramName']); ?>"
                                    data-uni-ids="<?php echo htmlspecialchars($prog['UniIDs'] ?? ''); ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    Edit
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this program?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="prog_id" value="<?php echo $prog['ProgramID']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted">No programs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_programs.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Add New Program</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Program Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. BSc Computer Science">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Offered At (Universities)</label>
                    <div style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.5rem;">
                        <?php foreach ($universities as $uni): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="uni_ids[]" value="<?php echo $uni['UniID']; ?>" id="add_uni_<?php echo $uni['UniID']; ?>">
                                <label class="form-check-label" for="add_uni_<?php echo $uni['UniID']; ?>">
                                    <?php echo htmlspecialchars($uni['Name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Save Program</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_programs.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white border-0">
                <h5 class="modal-title fw-bold">Edit Program</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="prog_id" id="edit_prog_id">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Program Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Offered At (Universities)</label>
                    <div style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.5rem;">
                        <?php foreach ($universities as $uni): ?>
                            <div class="form-check">
                                <input class="form-check-input edit-uni-check" type="checkbox" name="uni_ids[]" value="<?php echo $uni['UniID']; ?>" id="edit_uni_<?php echo $uni['UniID']; ?>">
                                <label class="form-check-label" for="edit_uni_<?php echo $uni['UniID']; ?>">
                                    <?php echo htmlspecialchars($uni['Name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Update Program</button>
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
            document.getElementById('edit_prog_id').value = this.getAttribute('data-id');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            
            // Clear checkboxes
            document.querySelectorAll('.edit-uni-check').forEach(chk => chk.checked = false);
            
            // Check the ones offered
            const uniIdsStr = this.getAttribute('data-uni-ids');
            if (uniIdsStr) {
                const uniIds = uniIdsStr.split(',');
                uniIds.forEach(id => {
                    const chk = document.getElementById('edit_uni_' + id);
                    if (chk) chk.checked = true;
                });
            }
        });
    });
});
</script>

<?php
require_once 'footer.php';
?>

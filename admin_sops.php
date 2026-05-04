<?php
// admin_sops.php
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
            $category = trim($_POST['category']);
            $content = trim($_POST['content']);

            $stmt = $conn->prepare("INSERT INTO sop_template (Category, Content) VALUES (?, ?)");
            $stmt->bind_param("ss", $category, $content);
            
            if ($stmt->execute()) {
                $message = "SOP Template added successfully.";
                $msgType = "success";
            } else {
                $message = "Error adding SOP Template: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $templateId = (int)$_POST['template_id'];
            $category = trim($_POST['category']);
            $content = trim($_POST['content']);

            $stmt = $conn->prepare("UPDATE sop_template SET Category=?, Content=? WHERE TemplateID=?");
            $stmt->bind_param("ssi", $category, $content, $templateId);
            
            if ($stmt->execute()) {
                $message = "SOP Template updated successfully.";
                $msgType = "success";
            } else {
                $message = "Error updating SOP Template: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        } elseif ($action === 'delete') {
            $templateId = (int)$_POST['template_id'];
            $stmt = $conn->prepare("DELETE FROM sop_template WHERE TemplateID=?");
            $stmt->bind_param("i", $templateId);
            
            if ($stmt->execute()) {
                $message = "SOP Template deleted successfully.";
                $msgType = "success";
            } else {
                $message = "Error deleting SOP Template: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        }
    }
}

// Fetch all templates
$templates = [];
$query = $conn->query("SELECT * FROM sop_template ORDER BY Category");
if ($query) {
    while ($row = $query->fetch_assoc()) {
        $templates[] = $row;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Manage SOP Templates</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">SOP Templates</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add New Template
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
                    <th>Category</th>
                    <th style="width: 50%;">Content Snippet</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($templates) > 0): ?>
                    <?php foreach ($templates as $tpl): ?>
                        <tr>
                            <td class="ps-4"><?php echo $tpl['TemplateID']; ?></td>
                            <td class="fw-semibold text-primary"><?php echo htmlspecialchars($tpl['Category']); ?></td>
                            <td>
                                <span class="d-inline-block text-truncate text-muted" style="max-width: 400px;">
                                    <?php echo htmlspecialchars($tpl['Content']); ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-secondary edit-btn me-1" 
                                    data-id="<?php echo $tpl['TemplateID']; ?>"
                                    data-category="<?php echo htmlspecialchars($tpl['Category']); ?>"
                                    data-content="<?php echo htmlspecialchars($tpl['Content']); ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    Edit
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="template_id" value="<?php echo $tpl['TemplateID']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted">No SOP templates found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="POST" action="admin_sops.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Add New SOP Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category (e.g. Computer Science, Engineering) <span class="text-danger">*</span></label>
                    <input type="text" name="category" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Template Content <span class="text-danger">*</span></label>
                    <textarea name="content" class="form-control" rows="10" required></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Save Template</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="POST" action="admin_sops.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white border-0">
                <h5 class="modal-title fw-bold">Edit SOP Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="template_id" id="edit_template_id">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <input type="text" name="category" id="edit_category" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Template Content <span class="text-danger">*</span></label>
                    <textarea name="content" id="edit_content" class="form-control" rows="10" required></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Update Template</button>
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
            document.getElementById('edit_template_id').value = this.getAttribute('data-id');
            document.getElementById('edit_category').value = this.getAttribute('data-category');
            document.getElementById('edit_content').value = this.getAttribute('data-content');
        });
    });
});
</script>

<?php
require_once 'footer.php';
?>

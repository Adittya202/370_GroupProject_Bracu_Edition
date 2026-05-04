<?php
// sop_templates.php
require_once 'db.php';
require_once 'header.php';

// Handle saving a template if the user is a student
$message = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    if (isset($_SESSION['UserID']) && $_SESSION['Role'] === 'User') {
        $templateId = (int)$_POST['template_id'];
        $userId = (int)$_SESSION['UserID'];
        
        // Check if already saved
        $check = $conn->prepare("SELECT 1 FROM student_saves_sop WHERE UserID = ? AND TemplateID = ?");
        $check->bind_param("ii", $userId, $templateId);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO student_saves_sop (UserID, TemplateID) VALUES (?, ?)");
            $stmt->bind_param("ii", $userId, $templateId);
            $stmt->execute();
            $message = "Template successfully saved to your profile!";
            $msgType = "success";
            $stmt->close();
        } else {
            $message = "You have already saved this template.";
            $msgType = "warning";
        }
        $check->close();
    } else {
        $message = "You must be logged in as a Student to save templates.";
        $msgType = "danger";
    }
}

// Fetch user's saved templates if logged in
$saved_templates = [];
if (isset($_SESSION['UserID']) && $_SESSION['Role'] === 'User') {
    $stmt = $conn->prepare("SELECT TemplateID FROM student_saves_sop WHERE UserID = ?");
    $stmt->bind_param("i", $_SESSION['UserID']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $saved_templates[] = $row['TemplateID'];
    }
    $stmt->close();
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

<!-- Add Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .template-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .template-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    }
    .content-box {
        background-color: #f8f9fa;
        border-radius: 0.8rem;
        border: 1px solid #dee2e6;
        padding: 1.5rem;
        max-height: 280px;
        overflow-y: auto;
        position: relative;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
    }
    
    /* Custom Scrollbar for the content box */
    .content-box::-webkit-scrollbar {
        width: 8px;
    }
    .content-box::-webkit-scrollbar-track {
        background: #f1f1f1; 
        border-radius: 4px;
    }
    .content-box::-webkit-scrollbar-thumb {
        background: #ced4da; 
        border-radius: 4px;
    }
    .content-box::-webkit-scrollbar-thumb:hover {
        background: #adb5bd; 
    }
    
    .copy-btn {
        position: sticky;
        top: 0;
        float: right;
        z-index: 10;
        background-color: rgba(255,255,255,0.9);
        backdrop-filter: blur(2px);
    }
</style>

<div class="container mt-5 pt-3 mb-5">
    <div class="text-center mb-5">
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-3">Resources & Guides</span>
        <h1 class="display-4 fw-bolder text-dark mb-3">Statement of Purpose Templates</h1>
        <p class="lead text-muted mx-auto" style="max-width: 600px;">Jumpstart your university application with our curated collection of successful SOP templates categorized by field of study.</p>
    </div>

    <?php if ($message): ?>
        <div class="row justify-content-center mb-4">
            <div class="col-md-8">
                <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (count($templates) > 0): ?>
            <?php foreach ($templates as $index => $tpl): ?>
                <div class="col-md-6 col-lg-6">
                    <div class="card shadow-sm border-0 h-100 rounded-4 template-card">
                        <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                            <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-mortarboard-fill text-primary me-2"></i> <?php echo htmlspecialchars($tpl['Category']); ?></h4>
                            
                            <?php if (isset($_SESSION['UserID']) && $_SESSION['Role'] === 'User'): ?>
                                <?php if (in_array($tpl['TemplateID'], $saved_templates)): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill"><i class="bi bi-check2-circle me-1"></i> Saved</span>
                                <?php else: ?>
                                    <form method="POST" class="m-0 p-0">
                                        <input type="hidden" name="save_template" value="1">
                                        <input type="hidden" name="template_id" value="<?php echo $tpl['TemplateID']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold"><i class="bi bi-bookmark-plus me-1"></i> Save to Profile</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                        </div>
                        <div class="card-body p-4">
                            <div class="content-box shadow-inner">
                                <button class="btn btn-sm border shadow-sm copy-btn text-secondary hover-primary" onclick="copyToClipboard('content-<?php echo $tpl['TemplateID']; ?>')" title="Copy text to clipboard">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                                <div id="content-<?php echo $tpl['TemplateID']; ?>" class="text-dark mt-2" style="white-space: pre-wrap;"><?php echo htmlspecialchars($tpl['Content']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-journal-x text-muted mb-3" style="font-size: 3rem;"></i>
                <h4 class="fw-bold text-muted">No templates available</h4>
                <p class="text-muted">Check back later for new Statement of Purpose templates.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Toast for copy notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
  <div id="copyToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body fw-semibold">
        <i class="bi bi-check-circle-fill text-success me-2"></i> Template copied to clipboard!
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script>
function copyToClipboard(elementId) {
    var content = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(content).then(function() {
        var toastEl = document.getElementById('copyToast');
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
    }, function(err) {
        console.error('Could not copy text: ', err);
        alert("Failed to copy text. Please try selecting and copying manually.");
    });
}
</script>

<?php
require_once 'footer.php';
?>

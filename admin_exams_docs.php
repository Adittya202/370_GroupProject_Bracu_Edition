<?php
// admin_exams_docs.php
require_once 'db.php';
require_once 'header.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Exam Dictionary CRUD
    if ($action === 'add_exam') {
        $name = trim($_POST['exam_name']);
        $stmt = $conn->prepare("INSERT INTO english_exam (ExamName) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) { $message = "Exam added successfully."; $msgType = "success"; }
        else { $message = "Error: " . $stmt->error; $msgType = "danger"; }
        $stmt->close();
    } elseif ($action === 'delete_exam') {
        $id = (int)$_POST['exam_id'];
        $stmt = $conn->prepare("DELETE FROM english_exam WHERE ExamID=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) { $message = "Exam deleted successfully."; $msgType = "success"; }
        else { $message = "Error: " . $stmt->error; $msgType = "danger"; }
        $stmt->close();
    }

    // 2. Document Dictionary CRUD
    elseif ($action === 'add_doc') {
        $name = trim($_POST['doc_name']);
        $stmt = $conn->prepare("INSERT INTO document (DocName) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) { $message = "Document added successfully."; $msgType = "success"; }
        else { $message = "Error: " . $stmt->error; $msgType = "danger"; }
        $stmt->close();
    } elseif ($action === 'delete_doc') {
        $id = (int)$_POST['doc_id'];
        $stmt = $conn->prepare("DELETE FROM document WHERE DocID=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) { $message = "Document deleted successfully."; $msgType = "success"; }
        else { $message = "Error: " . $stmt->error; $msgType = "danger"; }
        $stmt->close();
    }

    // 3. Map Exam to Uni
    elseif ($action === 'map_exam') {
        $uniId = (int)$_POST['uni_id'];
        $examId = (int)$_POST['exam_id'];
        $score = trim($_POST['min_score']);
        $conn->query("DELETE FROM requires_score WHERE UniID=$uniId AND ExamID=$examId");
        $stmt = $conn->prepare("INSERT INTO requires_score (UniID, ExamID, MinBandScore) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $uniId, $examId, $score);
        if ($stmt->execute()) { $message = "Exam requirement mapped to university."; $msgType = "success"; }
        else { $message = "Error: " . $stmt->error; $msgType = "danger"; }
        $stmt->close();
    } elseif ($action === 'delete_map_exam') {
        $uniId = (int)$_POST['uni_id'];
        $examId = (int)$_POST['exam_id'];
        $stmt = $conn->prepare("DELETE FROM requires_score WHERE UniID=? AND ExamID=?");
        $stmt->bind_param("ii", $uniId, $examId);
        if ($stmt->execute()) { $message = "Exam requirement removed from university."; $msgType = "success"; }
        $stmt->close();
    }

    // 4. Map Doc to Uni
    elseif ($action === 'map_doc') {
        $uniId = (int)$_POST['uni_id'];
        $docId = (int)$_POST['doc_id'];
        $conn->query("DELETE FROM requires_doc WHERE UniID=$uniId AND DocID=$docId");
        $stmt = $conn->prepare("INSERT INTO requires_doc (UniID, DocID) VALUES (?, ?)");
        $stmt->bind_param("ii", $uniId, $docId);
        if ($stmt->execute()) { $message = "Document requirement mapped to university."; $msgType = "success"; }
        else { $message = "Error: " . $stmt->error; $msgType = "danger"; }
        $stmt->close();
    } elseif ($action === 'delete_map_doc') {
        $uniId = (int)$_POST['uni_id'];
        $docId = (int)$_POST['doc_id'];
        $stmt = $conn->prepare("DELETE FROM requires_doc WHERE UniID=? AND DocID=?");
        $stmt->bind_param("ii", $uniId, $docId);
        if ($stmt->execute()) { $message = "Document requirement removed from university."; $msgType = "success"; }
        $stmt->close();
    }
}

// Fetch Data safely
$exams = []; 
$q = $conn->query("SELECT * FROM english_exam ORDER BY ExamName"); 
if ($q) while($r = $q->fetch_assoc()) $exams[] = $r;

$docs = []; 
$q = $conn->query("SELECT * FROM document ORDER BY DocName"); 
if ($q) while($r = $q->fetch_assoc()) $docs[] = $r;

$unis = []; 
$q = $conn->query("SELECT UniID, Name FROM university ORDER BY Name"); 
if ($q) while($r = $q->fetch_assoc()) $unis[] = $r;

$examMaps = [];
$q = $conn->query("SELECT r.UniID, r.ExamID, r.MinBandScore, u.Name as UniName, e.ExamName FROM requires_score r JOIN university u ON r.UniID=u.UniID JOIN english_exam e ON r.ExamID=e.ExamID ORDER BY u.Name, e.ExamName");
if ($q) while($r = $q->fetch_assoc()) $examMaps[] = $r;

$docMaps = [];
$q = $conn->query("SELECT r.UniID, r.DocID, u.Name as UniName, d.DocName FROM requires_doc r JOIN university u ON r.UniID=u.UniID JOIN document d ON r.DocID=d.DocID ORDER BY u.Name, d.DocName");
if ($q) while($r = $q->fetch_assoc()) $docMaps[] = $r;

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Manage Admissions Requirements</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Exams & Documents</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="reqTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-bold text-dark" id="map-tab" data-bs-toggle="tab" data-bs-target="#map-pane" type="button" role="tab">University Requirements</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold text-dark" id="exams-tab" data-bs-toggle="tab" data-bs-target="#exams-pane" type="button" role="tab">English Exams Library</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold text-dark" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs-pane" type="button" role="tab">Documents Library</button>
  </li>
</ul>

<div class="tab-content" id="reqTabsContent">
  <!-- UNIVERSITY REQUIREMENTS PANE -->
  <div class="tab-pane fade show active" id="map-pane" role="tabpanel">
      <div class="row g-4">
          <!-- Assign Exams -->
          <div class="col-md-6">
              <div class="card shadow-sm border-0 rounded-4 h-100">
                  <div class="card-header bg-white border-bottom pt-4 pb-3 px-4">
                      <h5 class="fw-bold m-0 text-primary">Assign Exam Requirement</h5>
                  </div>
                  <div class="card-body p-4">
                      <form method="POST" class="mb-4 p-3 bg-light rounded border">
                          <input type="hidden" name="action" value="map_exam">
                          <div class="mb-3">
                              <select name="uni_id" class="form-select" required>
                                  <option value="">Select University...</option>
                                  <?php foreach($unis as $u): ?>
                                      <option value="<?php echo $u['UniID']; ?>"><?php echo htmlspecialchars($u['Name']); ?></option>
                                  <?php endforeach; ?>
                              </select>
                          </div>
                          <div class="row g-2 mb-3">
                              <div class="col-md-7">
                                  <select name="exam_id" class="form-select" required>
                                      <option value="">Select Exam...</option>
                                      <?php foreach($exams as $e): ?>
                                          <option value="<?php echo $e['ExamID']; ?>"><?php echo htmlspecialchars($e['ExamName']); ?></option>
                                      <?php endforeach; ?>
                                  </select>
                              </div>
                              <div class="col-md-5">
                                  <input type="text" name="min_score" class="form-control" placeholder="Min Score (e.g. 6.5)" required>
                              </div>
                          </div>
                          <button type="submit" class="btn btn-primary w-100 fw-bold">Add Exam Requirement</button>
                      </form>

                      <div class="table-responsive">
                          <table class="table table-sm table-hover align-middle">
                              <thead class="table-light"><tr><th>University</th><th>Exam</th><th>Score</th><th></th></tr></thead>
                              <tbody>
                                  <?php foreach($examMaps as $em): ?>
                                  <tr>
                                      <td class="fw-semibold text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($em['UniName']); ?>"><?php echo htmlspecialchars($em['UniName']); ?></td>
                                      <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($em['ExamName']); ?></span></td>
                                      <td><?php echo htmlspecialchars($em['MinBandScore']); ?></td>
                                      <td class="text-end">
                                          <form method="POST" onsubmit="return confirm('Remove this exam requirement?');">
                                              <input type="hidden" name="action" value="delete_map_exam">
                                              <input type="hidden" name="uni_id" value="<?php echo $em['UniID']; ?>">
                                              <input type="hidden" name="exam_id" value="<?php echo $em['ExamID']; ?>">
                                              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                          </form>
                                      </td>
                                  </tr>
                                  <?php endforeach; ?>
                              </tbody>
                          </table>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Assign Docs -->
          <div class="col-md-6">
              <div class="card shadow-sm border-0 rounded-4 h-100">
                  <div class="card-header bg-white border-bottom pt-4 pb-3 px-4">
                      <h5 class="fw-bold m-0 text-success">Assign Document Requirement</h5>
                  </div>
                  <div class="card-body p-4">
                      <form method="POST" class="mb-4 p-3 bg-light rounded border">
                          <input type="hidden" name="action" value="map_doc">
                          <div class="mb-3">
                              <select name="uni_id" class="form-select" required>
                                  <option value="">Select University...</option>
                                  <?php foreach($unis as $u): ?>
                                      <option value="<?php echo $u['UniID']; ?>"><?php echo htmlspecialchars($u['Name']); ?></option>
                                  <?php endforeach; ?>
                              </select>
                          </div>
                          <div class="mb-3">
                              <select name="doc_id" class="form-select" required>
                                  <option value="">Select Document...</option>
                                  <?php foreach($docs as $d): ?>
                                      <option value="<?php echo $d['DocID']; ?>"><?php echo htmlspecialchars($d['DocName']); ?></option>
                                  <?php endforeach; ?>
                              </select>
                          </div>
                          <button type="submit" class="btn btn-success w-100 fw-bold">Add Document Requirement</button>
                      </form>

                      <div class="table-responsive">
                          <table class="table table-sm table-hover align-middle">
                              <thead class="table-light"><tr><th>University</th><th>Document</th><th></th></tr></thead>
                              <tbody>
                                  <?php foreach($docMaps as $dm): ?>
                                  <tr>
                                      <td class="fw-semibold text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($dm['UniName']); ?>"><?php echo htmlspecialchars($dm['UniName']); ?></td>
                                      <td><i class="bi bi-file-earmark-check text-success"></i> <?php echo htmlspecialchars($dm['DocName']); ?></td>
                                      <td class="text-end">
                                          <form method="POST" onsubmit="return confirm('Remove this document requirement?');">
                                              <input type="hidden" name="action" value="delete_map_doc">
                                              <input type="hidden" name="uni_id" value="<?php echo $dm['UniID']; ?>">
                                              <input type="hidden" name="doc_id" value="<?php echo $dm['DocID']; ?>">
                                              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                          </form>
                                      </td>
                                  </tr>
                                  <?php endforeach; ?>
                              </tbody>
                          </table>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <!-- EXAMS LIBRARY PANE -->
  <div class="tab-pane fade" id="exams-pane" role="tabpanel">
      <div class="card shadow-sm border-0 rounded-4">
          <div class="card-body p-4">
              <form method="POST" class="row g-3 align-items-end mb-4 bg-light p-3 rounded border">
                  <input type="hidden" name="action" value="add_exam">
                  <div class="col-md-9">
                      <label class="form-label fw-semibold">New English Exam Name</label>
                      <input type="text" name="exam_name" class="form-control" placeholder="e.g. Duolingo English Test" required>
                  </div>
                  <div class="col-md-3">
                      <button type="submit" class="btn btn-primary w-100 fw-bold">Add to Library</button>
                  </div>
              </form>

              <table class="table table-hover align-middle">
                  <thead class="table-dark"><tr><th>Exam ID</th><th>Exam Name</th><th class="text-end pe-4">Actions</th></tr></thead>
                  <tbody>
                      <?php foreach($exams as $e): ?>
                      <tr>
                          <td><?php echo $e['ExamID']; ?></td>
                          <td class="fw-bold"><?php echo htmlspecialchars($e['ExamName']); ?></td>
                          <td class="text-end pe-4">
                              <form method="POST" onsubmit="return confirm('Delete this exam from the library? This will also remove it from any assigned universities!');">
                                  <input type="hidden" name="action" value="delete_exam">
                                  <input type="hidden" name="exam_id" value="<?php echo $e['ExamID']; ?>">
                                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                              </form>
                          </td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
      </div>
  </div>

  <!-- DOCS LIBRARY PANE -->
  <div class="tab-pane fade" id="docs-pane" role="tabpanel">
      <div class="card shadow-sm border-0 rounded-4">
          <div class="card-body p-4">
              <form method="POST" class="row g-3 align-items-end mb-4 bg-light p-3 rounded border">
                  <input type="hidden" name="action" value="add_doc">
                  <div class="col-md-9">
                      <label class="form-label fw-semibold">New Document Name</label>
                      <input type="text" name="doc_name" class="form-control" placeholder="e.g. Financial Bank Statement" required>
                  </div>
                  <div class="col-md-3">
                      <button type="submit" class="btn btn-success w-100 fw-bold">Add to Library</button>
                  </div>
              </form>

              <table class="table table-hover align-middle">
                  <thead class="table-dark"><tr><th>Doc ID</th><th>Document Name</th><th class="text-end pe-4">Actions</th></tr></thead>
                  <tbody>
                      <?php foreach($docs as $d): ?>
                      <tr>
                          <td><?php echo $d['DocID']; ?></td>
                          <td class="fw-bold text-secondary"><i class="bi bi-file-earmark-text me-2"></i><?php echo htmlspecialchars($d['DocName']); ?></td>
                          <td class="text-end pe-4">
                              <form method="POST" onsubmit="return confirm('Delete this document from the library? This will also remove it from any assigned universities!');">
                                  <input type="hidden" name="action" value="delete_doc">
                                  <input type="hidden" name="doc_id" value="<?php echo $d['DocID']; ?>">
                                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                              </form>
                          </td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
      </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>

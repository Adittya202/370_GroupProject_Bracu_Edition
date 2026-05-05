<?php
// admin_universities.php
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

        // Helper function to dynamically resolve or create a City and Country
        if (!function_exists('resolveLocation')) {
            function resolveLocation($conn, $locationString) {
                $parts = explode(',', $locationString);
                $cityName = trim($parts[0]);
                $countryName = isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : 'Unknown Country';
                
                if (empty($cityName)) return null;

                // 1. Resolve Country
                $countryId = null;
                $stmtC = $conn->prepare("SELECT CountryID FROM country WHERE Name = ?");
                $stmtC->bind_param("s", $countryName);
                $stmtC->execute();
                $resC = $stmtC->get_result();
                if ($rowC = $resC->fetch_assoc()) {
                    $countryId = $rowC['CountryID'];
                } else {
                    $stmtInsC = $conn->prepare("INSERT INTO country (Name) VALUES (?)");
                    $stmtInsC->bind_param("s", $countryName);
                    if ($stmtInsC->execute()) $countryId = $conn->insert_id;
                    $stmtInsC->close();
                }
                $stmtC->close();

                // 2. Resolve City
                $cityId = null;
                $stmtCity = $conn->prepare("SELECT CityID FROM city WHERE Name = ? AND CountryID = ?");
                $stmtCity->bind_param("si", $cityName, $countryId);
                $stmtCity->execute();
                $resCity = $stmtCity->get_result();
                if ($rowCity = $resCity->fetch_assoc()) {
                    $cityId = $rowCity['CityID'];
                } else {
                    $stmtInsCity = $conn->prepare("INSERT INTO city (Name, CountryID) VALUES (?, ?)");
                    $stmtInsCity->bind_param("si", $cityName, $countryId);
                    if ($stmtInsCity->execute()) $cityId = $conn->insert_id;
                    $stmtInsCity->close();
                }
                $stmtCity->close();

                return $cityId;
            }
        }

        if ($action === 'add') {
            $name = trim($_POST['name']);
            $ranking = !empty($_POST['ranking']) ? (int)$_POST['ranking'] : null;
            $credits = !empty($_POST['credits']) ? (int)$_POST['credits'] : null;
            $tuition = !empty($_POST['tuition']) ? (float)$_POST['tuition'] : null;
            $intlScore = !empty($_POST['intl_score']) ? (int)$_POST['intl_score'] : null;
            $cityId = resolveLocation($conn, $_POST['location']);

            if (!$cityId) {
                $message = "Invalid location. Please provide at least a City name.";
                $msgType = "danger";
            } else {
                $stmt = $conn->prepare("INSERT INTO university (Name, Ranking, MaxTransferCredits, TuitionCost, IntlFriendlyScore, CityID) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siidii", $name, $ranking, $credits, $tuition, $intlScore, $cityId);
                if ($stmt->execute()) {
                    $message = "University added successfully.";
                    $msgType = "success";
                } else {
                    $message = "Error adding university: " . $stmt->error;
                    $msgType = "danger";
                }
                $stmt->close();
            }
        } elseif ($action === 'edit') {
            $uniId = (int)$_POST['uni_id'];
            $name = trim($_POST['name']);
            $ranking = !empty($_POST['ranking']) ? (int)$_POST['ranking'] : null;
            $credits = !empty($_POST['credits']) ? (int)$_POST['credits'] : null;
            $tuition = !empty($_POST['tuition']) ? (float)$_POST['tuition'] : null;
            $intlScore = !empty($_POST['intl_score']) ? (int)$_POST['intl_score'] : null;
            $cityId = resolveLocation($conn, $_POST['location']);

            if (!$cityId) {
                $message = "Invalid location. Please provide at least a City name.";
                $msgType = "danger";
            } else {
                $stmt = $conn->prepare("UPDATE university SET Name=?, Ranking=?, MaxTransferCredits=?, TuitionCost=?, IntlFriendlyScore=?, CityID=? WHERE UniID=?");
                $stmt->bind_param("siidiii", $name, $ranking, $credits, $tuition, $intlScore, $cityId, $uniId);
                if ($stmt->execute()) {
                    $message = "University updated successfully.";
                    $msgType = "success";
                } else {
                    $message = "Error updating university: " . $stmt->error;
                    $msgType = "danger";
                }
                $stmt->close();
            }
        } elseif ($action === 'delete') {
            $uniId = (int)$_POST['uni_id'];
            $stmt = $conn->prepare("DELETE FROM university WHERE UniID=?");
            $stmt->bind_param("i", $uniId);
            
            if ($stmt->execute()) {
                $message = "University deleted successfully.";
                $msgType = "success";
            } else {
                $message = "Error deleting university: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        }
    }
}

// Fetch all cities for dropdowns
$cities = [];
$cityQuery = $conn->query("SELECT c.CityID, c.Name as CityName, co.Name as CountryName FROM city c JOIN country co ON c.CountryID = co.CountryID ORDER BY co.Name, c.Name");
if ($cityQuery) {
    while ($row = $cityQuery->fetch_assoc()) {
        $cities[] = $row;
    }
}

// Fetch all universities
$universities = [];
$uniQuery = $conn->query("SELECT u.*, c.Name as CityName, co.Name as CountryName FROM university u JOIN city c ON u.CityID = c.CityID JOIN country co ON c.CountryID = co.CountryID ORDER BY u.Name");
if ($uniQuery) {
    while ($row = $uniQuery->fetch_assoc()) {
        $universities[] = $row;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Manage Universities</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Universities</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add New University
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
                    <th>Name</th>
                    <th>Location</th>
                    <th>Ranking</th>
                    <th>Max Credits</th>
                    <th>Tuition ($)</th>
                    <th>Intl Score</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($universities) > 0): ?>
                    <?php foreach ($universities as $uni): ?>
                        <tr>
                            <td class="ps-4"><?php echo $uni['UniID']; ?></td>
                            <td class="fw-semibold text-primary"><?php echo htmlspecialchars($uni['Name']); ?></td>
                            <td><?php echo htmlspecialchars($uni['CityName'] . ', ' . $uni['CountryName']); ?></td>
                            <td><?php echo $uni['Ranking'] ? '#' . $uni['Ranking'] : '-'; ?></td>
                            <td><?php echo $uni['MaxTransferCredits'] ?? '-'; ?></td>
                            <td><?php echo $uni['TuitionCost'] !== null ? number_format($uni['TuitionCost'], 2) : '-'; ?></td>
                            <td>
                                <?php if ($uni['IntlFriendlyScore']): ?>
                                    <div class="progress" style="height: 10px; width: 60px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $uni['IntlFriendlyScore']; ?>%;" aria-valuenow="<?php echo $uni['IntlFriendlyScore']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $uni['IntlFriendlyScore']; ?>/100</small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-secondary edit-btn me-1" 
                                    data-id="<?php echo $uni['UniID']; ?>"
                                    data-name="<?php echo htmlspecialchars($uni['Name']); ?>"
                                    data-ranking="<?php echo $uni['Ranking']; ?>"
                                    data-credits="<?php echo $uni['MaxTransferCredits']; ?>"
                                    data-tuition="<?php echo $uni['TuitionCost']; ?>"
                                    data-score="<?php echo $uni['IntlFriendlyScore']; ?>"
                                    data-location="<?php echo htmlspecialchars($uni['CityName'] . ', ' . $uni['CountryName']); ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    Edit
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this university? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="uni_id" value="<?php echo $uni['UniID']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">No universities found in the system.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="POST" action="admin_universities.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Add New University</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3 p-4">
                <input type="hidden" name="action" value="add">
                
                <div class="col-md-12">
                    <label class="form-label fw-semibold">University Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Stanford University">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">City/Location <span class="text-danger">*</span></label>
                    <input type="text" name="location" class="form-control" list="cityList" required placeholder="e.g. London, UK">
                    <datalist id="cityList">
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city['CityName'] . ', ' . $city['CountryName']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">World Ranking</label>
                    <input type="number" name="ranking" class="form-control" min="1" placeholder="e.g. 5">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Max Transfer Credits</label>
                    <input type="number" name="credits" class="form-control" min="0" placeholder="e.g. 30">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tuition Cost ($)</label>
                    <input type="number" step="0.01" name="tuition" class="form-control" min="0" placeholder="e.g. 50000.00">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Intl Friendly Score</label>
                    <input type="number" name="intl_score" class="form-control" min="1" max="100" placeholder="1-100">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Save University</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form method="POST" action="admin_universities.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white border-0">
                <h5 class="modal-title fw-bold">Edit University</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3 p-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="uni_id" id="edit_uni_id">
                
                <div class="col-md-12">
                    <label class="form-label fw-semibold">University Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">City/Location <span class="text-danger">*</span></label>
                    <input type="text" name="location" id="edit_location" class="form-control" list="cityListEdit" required placeholder="e.g. London, UK">
                    <datalist id="cityListEdit">
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city['CityName'] . ', ' . $city['CountryName']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">World Ranking</label>
                    <input type="number" name="ranking" id="edit_ranking" class="form-control" min="1">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Max Transfer Credits</label>
                    <input type="number" name="credits" id="edit_credits" class="form-control" min="0">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tuition Cost ($)</label>
                    <input type="number" step="0.01" name="tuition" id="edit_tuition" class="form-control" min="0">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Intl Friendly Score</label>
                    <input type="number" name="intl_score" id="edit_intl_score" class="form-control" min="1" max="100">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Update University</button>
            </div>
        </div>
    </form>
  </div>
</div>

<script>
// Small script to populate the Edit modal with data from the button clicked
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_uni_id').value = this.getAttribute('data-id');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_location').value = this.getAttribute('data-location');
            document.getElementById('edit_ranking').value = this.getAttribute('data-ranking');
            document.getElementById('edit_credits').value = this.getAttribute('data-credits');
            document.getElementById('edit_tuition').value = this.getAttribute('data-tuition');
            document.getElementById('edit_intl_score').value = this.getAttribute('data-score');
        });
    });
});
</script>

<?php
require_once 'footer.php';
?>

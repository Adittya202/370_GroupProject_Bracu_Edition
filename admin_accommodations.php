<?php
// admin_accommodations.php
require_once 'db.php';
require_once 'header.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$msgType = '';

// Removed CREATE TABLE since accommodation already exists with a composite key

// Handle POST actions for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $cityId = (int)$_POST['city_id'];
            $accName = trim($_POST['acc_name']);
            $type = trim($_POST['type']);
            $avgRent = !empty($_POST['avg_rent']) ? (float)$_POST['avg_rent'] : null;

            $stmt = $conn->prepare("INSERT INTO accommodation (CityID, AccName, Type, AvgRent) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issd", $cityId, $accName, $type, $avgRent);
            
            if ($stmt->execute()) {
                $message = "Accommodation added successfully.";
                $msgType = "success";
            } else {
                $message = "Error adding accommodation: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        } elseif ($action === 'edit') {
            $oldCityId = (int)$_POST['old_city_id'];
            $oldAccName = trim($_POST['old_acc_name']);
            $cityId = (int)$_POST['city_id'];
            $accName = trim($_POST['acc_name']);
            $type = trim($_POST['type']);
            $avgRent = !empty($_POST['avg_rent']) ? (float)$_POST['avg_rent'] : null;

            $stmt = $conn->prepare("UPDATE accommodation SET CityID=?, AccName=?, Type=?, AvgRent=? WHERE CityID=? AND AccName=?");
            $stmt->bind_param("issdis", $cityId, $accName, $type, $avgRent, $oldCityId, $oldAccName);
            
            if ($stmt->execute()) {
                $message = "Accommodation updated successfully.";
                $msgType = "success";
            } else {
                $message = "Error updating accommodation: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        } elseif ($action === 'delete') {
            $cityId = (int)$_POST['city_id'];
            $accName = trim($_POST['acc_name']);
            $stmt = $conn->prepare("DELETE FROM accommodation WHERE CityID=? AND AccName=?");
            $stmt->bind_param("is", $cityId, $accName);
            
            if ($stmt->execute()) {
                $message = "Accommodation deleted successfully.";
                $msgType = "success";
            } else {
                $message = "Error deleting accommodation: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();
        }
    }
}

// Fetch all cities for dropdown
$cities = [];
$cQuery = $conn->query("SELECT c.CityID, c.Name as CityName, co.Name as CountryName FROM city c JOIN country co ON c.CountryID = co.CountryID ORDER BY co.Name, c.Name");
if ($cQuery) {
    while ($row = $cQuery->fetch_assoc()) {
        $cities[] = $row;
    }
}

// Fetch all accommodations
$accommodations = [];
$aQuery = $conn->query("
    SELECT a.*, c.Name as CityName, co.Name as CountryName 
    FROM accommodation a 
    JOIN city c ON a.CityID = c.CityID 
    JOIN country co ON c.CountryID = co.CountryID 
    ORDER BY co.Name, c.Name, a.AccName
");
if ($aQuery) {
    while ($row = $aQuery->fetch_assoc()) {
        $accommodations[] = $row;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Manage Accommodations</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Accommodations</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add Accommodation
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
                    <th>City / Location</th>
                    <th>Housing Name</th>
                    <th>Type</th>
                    <th>Avg. Rent ($)</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($accommodations) > 0): ?>
                    <?php foreach ($accommodations as $acc): ?>
                        <tr>
                            <td><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?php echo htmlspecialchars($acc['CityName'] . ', ' . $acc['CountryName']); ?></td>
                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($acc['AccName']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($acc['Type']); ?></span></td>
                            <td><?php echo $acc['AvgRent'] !== null ? '$' . number_format($acc['AvgRent'], 2) : 'N/A'; ?></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-secondary edit-btn me-1" 
                                    data-city="<?php echo $acc['CityID']; ?>"
                                    data-name="<?php echo htmlspecialchars($acc['AccName']); ?>"
                                    data-type="<?php echo htmlspecialchars($acc['Type']); ?>"
                                    data-rent="<?php echo $acc['AvgRent']; ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    Edit
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this accommodation option?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="city_id" value="<?php echo $acc['CityID']; ?>">
                                    <input type="hidden" name="acc_name" value="<?php echo htmlspecialchars($acc['AccName']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No accommodations found in the database.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_accommodations.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Add Housing Option</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                    <select name="city_id" class="form-select" required>
                        <option value="">Select City</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?php echo $c['CityID']; ?>"><?php echo htmlspecialchars($c['CityName'] . ', ' . $c['CountryName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Accommodation Name <span class="text-danger">*</span></label>
                    <input type="text" name="acc_name" class="form-control" placeholder="e.g. University Hall" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                        <input type="text" name="type" class="form-control" placeholder="e.g. Dormitory, Apartment" required list="accTypes">
                        <datalist id="accTypes">
                            <option value="Dormitory">
                            <option value="Shared Apartment">
                            <option value="Private Studio">
                            <option value="Homestay">
                            <option value="Hostel">
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Average Rent ($)</label>
                        <input type="number" step="0.01" name="avg_rent" class="form-control" min="0" placeholder="e.g. 500">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Save</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_accommodations.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white border-0">
                <h5 class="modal-title fw-bold">Edit Housing Option</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="old_city_id" id="edit_old_city_id">
                <input type="hidden" name="old_acc_name" id="edit_old_acc_name">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                    <select name="city_id" id="edit_city_id" class="form-select" required>
                        <option value="">Select City</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?php echo $c['CityID']; ?>"><?php echo htmlspecialchars($c['CityName'] . ', ' . $c['CountryName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Accommodation Name <span class="text-danger">*</span></label>
                    <input type="text" name="acc_name" id="edit_acc_name" class="form-control" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                        <input type="text" name="type" id="edit_type" class="form-control" required list="accTypesEdit">
                        <datalist id="accTypesEdit">
                            <option value="Dormitory">
                            <option value="Shared Apartment">
                            <option value="Private Studio">
                            <option value="Homestay">
                            <option value="Hostel">
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Average Rent ($)</label>
                        <input type="number" step="0.01" name="avg_rent" id="edit_avg_rent" class="form-control" min="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Update</button>
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
            const cityId = this.getAttribute('data-city');
            const accName = this.getAttribute('data-name');
            
            document.getElementById('edit_old_city_id').value = cityId;
            document.getElementById('edit_old_acc_name').value = accName;
            
            document.getElementById('edit_city_id').value = cityId;
            document.getElementById('edit_acc_name').value = accName;
            document.getElementById('edit_type').value = this.getAttribute('data-type');
            document.getElementById('edit_avg_rent').value = this.getAttribute('data-rent');
        });
    });
});
</script>

<?php
require_once 'footer.php';
?>

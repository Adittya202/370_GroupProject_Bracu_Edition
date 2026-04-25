<?php
require_once 'header.php';
$role = $_SESSION['Role'];

// Admin Actions
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO ACCOMMODATION (CityID, AccName, Type, AvgRent) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issd", $_POST['city_id'], $_POST['acc_name'], $_POST['type'], $_POST['avg_rent']);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE ACCOMMODATION SET CityID=?, AccName=?, Type=?, AvgRent=? WHERE AccID=?");
            $stmt->bind_param("issdi", $_POST['city_id'], $_POST['acc_name'], $_POST['type'], $_POST['avg_rent'], $_POST['acc_id']);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM ACCOMMODATION WHERE AccID=?");
            $stmt->bind_param("i", $_POST['acc_id']);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: accommodation.php");
        exit;
    }
}

// Fetch cities for dropdowns (Filter and Forms)
$cities = [];
$cityResult = $conn->query("SELECT CityID, Name FROM CITY");
if ($cityResult) $cities = $cityResult->fetch_all(MYSQLI_ASSOC);

// Search Logic
$searchCity = $_GET['searchCity'] ?? '';
$query = "SELECT a.*, c.Name AS CityName FROM ACCOMMODATION a LEFT JOIN CITY c ON a.CityID = c.CityID";

if ($searchCity !== '') {
    $query .= " WHERE a.CityID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $searchCity);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$accommodations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Accommodations by City</h2>
        <span class="badge bg-secondary">Role: <?= htmlspecialchars($role) ?></span>
    </div>

    <!-- Search Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="accommodation.php" class="row g-3">
                <div class="col-md-10">
                    <select name="searchCity" class="form-control">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= $city['CityID'] ?>" <?= ($searchCity == $city['CityID']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($role === 'admin'): ?>
    <!-- Add New Accommodation Form -->
    <div class="card mb-4 shadow-sm border-primary">
        <div class="card-header bg-primary text-white">Add New Accommodation</div>
        <div class="card-body">
            <form method="POST" action="accommodation.php" class="row g-3">
                <input type="hidden" name="action" value="add">
                <div class="col-md-3">
                    <label>City</label>
                    <select name="city_id" class="form-control" required>
                        <option value="">Select City...</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= $city['CityID'] ?>"><?= htmlspecialchars($city['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Accommodation Name</label>
                    <input type="text" name="acc_name" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>Type</label>
                    <input type="text" name="type" class="form-control" placeholder="e.g. Dormitory">
                </div>
                <div class="col-md-2">
                    <label>Average Rent</label>
                    <input type="number" step="0.01" name="avg_rent" class="form-control">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Add</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Accommodation Data Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>City</th>
                            <th>Accommodation Name</th>
                            <th>Type</th>
                            <th>Average Rent</th>
                            <?php if ($role === 'admin'): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accommodations)): ?>
                            <tr><td colspan="<?= ($role === 'admin') ? 5 : 4 ?>" class="text-center">No accommodations found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($accommodations as $acc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($acc['CityName']) ?></td>
                                    <td><?= htmlspecialchars($acc['AccName']) ?></td>
                                    <td><?= htmlspecialchars($acc['Type']) ?></td>
                                    <td>$<?= htmlspecialchars($acc['AvgRent']) ?></td>
                                    
                                    <?php if ($role === 'admin'): ?>
                                    <td>
                                        <!-- Edit Modal Trigger -->
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $acc['AccID'] ?>">Edit</button>
                                        
                                        <!-- Delete Form -->
                                        <form method="POST" action="accommodation.php" class="d-inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="acc_id" value="<?= $acc['AccID'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this accommodation?');">Delete</button>
                                        </form>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?= $acc['AccID'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="accommodation.php">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Accommodation</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="edit">
                                                            <input type="hidden" name="acc_id" value="<?= $acc['AccID'] ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label>City</label>
                                                                <select name="city_id" class="form-control" required>
                                                                    <?php foreach ($cities as $city): ?>
                                                                        <option value="<?= $city['CityID'] ?>" <?= ($city['CityID'] == $acc['CityID']) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($city['Name']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Accommodation Name</label>
                                                                <input type="text" name="acc_name" class="form-control" value="<?= htmlspecialchars($acc['AccName']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Type</label>
                                                                <input type="text" name="type" class="form-control" value="<?= htmlspecialchars($acc['Type']) ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Average Rent</label>
                                                                <input type="number" step="0.01" name="avg_rent" class="form-control" value="<?= htmlspecialchars($acc['AvgRent']) ?>">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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

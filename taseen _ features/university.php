<?php
require_once 'header.php';
$role = $_SESSION['Role'];

// Handle Admin Actions
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO UNIVERSITY (Name, Ranking, MaxTransferCredits, TuitionCost, IntlFriendlyScore, CityID, IsActive) VALUES (?, ?, ?, ?, ?, ?, TRUE)");
            $stmt->bind_param("siddii", $_POST['name'], $_POST['ranking'], $_POST['max_credits'], $_POST['tuition'], $_POST['intl_score'], $_POST['city_id']);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE UNIVERSITY SET Name=?, Ranking=?, MaxTransferCredits=?, TuitionCost=?, IntlFriendlyScore=?, CityID=? WHERE UniID=?");
            $stmt->bind_param("siddiii", $_POST['name'], $_POST['ranking'], $_POST['max_credits'], $_POST['tuition'], $_POST['intl_score'], $_POST['city_id'], $_POST['uni_id']);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'deactivate') {
            $stmt = $conn->prepare("UPDATE UNIVERSITY SET IsActive=FALSE WHERE UniID=?");
            $stmt->bind_param("i", $_POST['uni_id']);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: university.php");
        exit;
    }
}

// Filtering Logic
$searchName = $_GET['searchName'] ?? '';
$searchRanking = $_GET['searchRanking'] ?? '';
$searchCity = $_GET['searchCity'] ?? '';

$query = "SELECT u.*, c.Name AS CityName FROM UNIVERSITY u LEFT JOIN CITY c ON u.CityID = c.CityID WHERE u.IsActive = TRUE";
$params = [];
$types = "";

if ($searchName !== '') {
    $query .= " AND u.Name LIKE ?";
    $params[] = "%" . $searchName . "%";
    $types .= "s";
}
if ($searchRanking !== '') {
    $query .= " AND u.Ranking <= ?";
    $params[] = (int)$searchRanking;
    $types .= "i";
}
if ($searchCity !== '') {
    $query .= " AND u.CityID = ?";
    $params[] = (int)$searchCity;
    $types .= "i";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
if (!$stmt) {
    die("MySQL Error: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$universities = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch cities for dropdowns
$cities = [];
$cityResult = $conn->query("SELECT CityID, Name FROM CITY");
if ($cityResult) {
    $cities = $cityResult->fetch_all(MYSQLI_ASSOC);
}
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>University Directory</h2>
        <span class="badge bg-secondary">Role: <?= htmlspecialchars($role) ?></span>
    </div>

    <!-- Search / Filter Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="university.php" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="searchName" class="form-control" placeholder="Search by Name" value="<?= htmlspecialchars($searchName) ?>">
                </div>
                <div class="col-md-3">
                    <input type="number" name="searchRanking" class="form-control" placeholder="Max Ranking (e.g. 100)" value="<?= htmlspecialchars($searchRanking) ?>">
                </div>
                <div class="col-md-3">
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
    <!-- Add New University Form (Admin Only) -->
    <div class="card mb-4 shadow-sm border-primary">
        <div class="card-header bg-primary text-white">Add New University</div>
        <div class="card-body">
            <form method="POST" action="university.php" class="row g-3">
                <input type="hidden" name="action" value="add">
                <div class="col-md-4">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>Ranking</label>
                    <input type="number" name="ranking" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>Max Credits</label>
                    <input type="number" name="max_credits" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>Tuition Cost</label>
                    <input type="number" step="0.01" name="tuition" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>Intl Score</label>
                    <input type="number" name="intl_score" class="form-control">
                </div>
                <div class="col-md-4 mt-3">
                    <label>City</label>
                    <select name="city_id" class="form-control" required>
                        <option value="">Select City</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= $city['CityID'] ?>"><?= htmlspecialchars($city['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-success">Add University</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Data Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Ranking</th>
                            <th>City</th>
                            <th>Max Credits</th>
                            <th>Tuition</th>
                            <th>Intl Score</th>
                            <?php if ($role === 'admin'): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($universities)): ?>
                            <tr><td colspan="<?= ($role === 'admin') ? 8 : 7 ?>" class="text-center">No universities found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($universities as $uni): ?>
                                <tr>
                                    <td><?= htmlspecialchars($uni['UniID']) ?></td>
                                    <td><?= htmlspecialchars($uni['Name']) ?></td>
                                    <td><?= htmlspecialchars($uni['Ranking']) ?></td>
                                    <td><?= htmlspecialchars($uni['CityName']) ?></td>
                                    <td><?= htmlspecialchars($uni['MaxTransferCredits']) ?></td>
                                    <td>$<?= htmlspecialchars($uni['TuitionCost']) ?></td>
                                    <td><?= htmlspecialchars($uni['IntlFriendlyScore']) ?></td>
                                    
                                    <?php if ($role === 'admin'): ?>
                                    <td>
                                        <!-- Edit Modal Trigger -->
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $uni['UniID'] ?>">Edit</button>
                                        
                                        <!-- Deactivate Form -->
                                        <form method="POST" action="university.php" class="d-inline">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="uni_id" value="<?= $uni['UniID'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Deactivate this university?');">Deactivate</button>
                                        </form>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?= $uni['UniID'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="university.php">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit <?= htmlspecialchars($uni['Name']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="edit">
                                                            <input type="hidden" name="uni_id" value="<?= $uni['UniID'] ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label>Name</label>
                                                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($uni['Name']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Ranking</label>
                                                                <input type="number" name="ranking" class="form-control" value="<?= htmlspecialchars($uni['Ranking']) ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>City</label>
                                                                <select name="city_id" class="form-control" required>
                                                                    <?php foreach ($cities as $city): ?>
                                                                        <option value="<?= $city['CityID'] ?>" <?= ($city['CityID'] == $uni['CityID']) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($city['Name']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Max Credits</label>
                                                                <input type="number" name="max_credits" class="form-control" value="<?= htmlspecialchars($uni['MaxTransferCredits']) ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Tuition</label>
                                                                <input type="number" step="0.01" name="tuition" class="form-control" value="<?= htmlspecialchars($uni['TuitionCost']) ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Intl Score</label>
                                                                <input type="number" name="intl_score" class="form-control" value="<?= htmlspecialchars($uni['IntlFriendlyScore']) ?>">
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

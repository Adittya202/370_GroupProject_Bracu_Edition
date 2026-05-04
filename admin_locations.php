<?php
// admin_locations.php
require_once 'db.php';
require_once 'header.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$msgType = '';

// Make sure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS country (
    CountryID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL UNIQUE
)");
$conn->query("CREATE TABLE IF NOT EXISTS city (
    CityID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    CountryID INT NOT NULL,
    FOREIGN KEY (CountryID) REFERENCES country(CountryID) ON DELETE RESTRICT
)");

// Handle POST actions for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // --- Country Actions ---
        if ($action === 'add_country') {
            $name = trim($_POST['name']);
            
            $stmt = $conn->prepare("INSERT INTO country (Name) VALUES (?)");
            $stmt->bind_param("s", $name);
            
            try {
                if ($stmt->execute()) {
                    $message = "Country added successfully.";
                    $msgType = "success";
                } else {
                    $message = "Error adding country: " . $stmt->error;
                    $msgType = "danger";
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) { // Duplicate entry
                    $message = "This country already exists.";
                } else {
                    $message = "Database error: " . $e->getMessage();
                }
                $msgType = "danger";
            }
            $stmt->close();

        } elseif ($action === 'edit_country') {
            $countryId = (int)$_POST['country_id'];
            $name = trim($_POST['name']);
            
            $stmt = $conn->prepare("UPDATE country SET Name=? WHERE CountryID=?");
            $stmt->bind_param("si", $name, $countryId);
            
            try {
                if ($stmt->execute()) {
                    $message = "Country updated successfully.";
                    $msgType = "success";
                } else {
                    $message = "Error updating country: " . $stmt->error;
                    $msgType = "danger";
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $message = "This country name already exists.";
                } else {
                    $message = "Database error: " . $e->getMessage();
                }
                $msgType = "danger";
            }
            $stmt->close();

        } elseif ($action === 'delete_country') {
            $countryId = (int)$_POST['country_id'];
            
            try {
                $stmt = $conn->prepare("DELETE FROM country WHERE CountryID=?");
                $stmt->bind_param("i", $countryId);
                
                if ($stmt->execute()) {
                    $message = "Country deleted successfully.";
                    $msgType = "success";
                } else {
                    $message = "Error deleting country: " . $stmt->error;
                    $msgType = "danger";
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1451) {
                    $message = "Cannot delete this country because it has associated cities. Please delete or reassign those cities first.";
                } else {
                    $message = "Database error: " . $e->getMessage();
                }
                $msgType = "danger";
            }

        // --- City Actions ---
        } elseif ($action === 'add_city') {
            $name = trim($_POST['name']);
            $countryId = (int)$_POST['country_id'];
            
            $stmt = $conn->prepare("INSERT INTO city (Name, CountryID) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $countryId);
            
            if ($stmt->execute()) {
                $message = "City added successfully.";
                $msgType = "success";
            } else {
                $message = "Error adding city: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();

        } elseif ($action === 'edit_city') {
            $cityId = (int)$_POST['city_id'];
            $name = trim($_POST['name']);
            $countryId = (int)$_POST['country_id'];
            
            $stmt = $conn->prepare("UPDATE city SET Name=?, CountryID=? WHERE CityID=?");
            $stmt->bind_param("sii", $name, $countryId, $cityId);
            
            if ($stmt->execute()) {
                $message = "City updated successfully.";
                $msgType = "success";
            } else {
                $message = "Error updating city: " . $stmt->error;
                $msgType = "danger";
            }
            $stmt->close();

        } elseif ($action === 'delete_city') {
            $cityId = (int)$_POST['city_id'];
            
            try {
                $stmt = $conn->prepare("DELETE FROM city WHERE CityID=?");
                $stmt->bind_param("i", $cityId);
                
                if ($stmt->execute()) {
                    $message = "City deleted successfully.";
                    $msgType = "success";
                } else {
                    $message = "Error deleting city: " . $stmt->error;
                    $msgType = "danger";
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1451) {
                    $message = "Cannot delete this city because it is currently linked to one or more universities. Please delete or reassign those universities first.";
                } else {
                    $message = "Database error: " . $e->getMessage();
                }
                $msgType = "danger";
            }
        }
    }
}

// Fetch data for display
$countries = [];
$cQuery = $conn->query("SELECT * FROM country ORDER BY Name");
if ($cQuery) {
    while ($row = $cQuery->fetch_assoc()) {
        $countries[] = $row;
    }
}

$cities = [];
$cityQuery = $conn->query("
    SELECT c.CityID, c.Name as CityName, c.CountryID, co.Name as CountryName 
    FROM city c 
    JOIN country co ON c.CountryID = co.CountryID 
    ORDER BY co.Name, c.Name
");
if ($cityQuery) {
    while ($row = $cityQuery->fetch_assoc()) {
        $cities[] = $row;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Manage Locations</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Locations</li>
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

<div class="row g-4">
    <!-- Countries Column -->
    <div class="col-md-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Countries</h4>
            <button class="btn btn-sm btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addCountryModal">
                + Add Country
            </button>
        </div>
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3" width="15%">ID</th>
                            <th>Country Name</th>
                            <th class="text-end pe-3" width="30%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($countries) > 0): ?>
                            <?php foreach ($countries as $c): ?>
                                <tr>
                                    <td class="ps-3"><?php echo $c['CountryID']; ?></td>
                                    <td class="fw-semibold text-primary"><?php echo htmlspecialchars($c['Name']); ?></td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-outline-secondary edit-country-btn me-1" 
                                            data-id="<?php echo $c['CountryID']; ?>"
                                            data-name="<?php echo htmlspecialchars($c['Name']); ?>"
                                            data-bs-toggle="modal" data-bs-target="#editCountryModal">
                                            Edit
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this country?');">
                                            <input type="hidden" name="action" value="delete_country">
                                            <input type="hidden" name="country_id" value="<?php echo $c['CountryID']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No countries found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cities Column -->
    <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Cities</h4>
            <button class="btn btn-sm btn-success shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addCityModal">
                + Add City
            </button>
        </div>
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0 table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark" style="position: sticky; top: 0; z-index: 1;">
                        <tr>
                            <th class="ps-3" width="10%">ID</th>
                            <th>City</th>
                            <th>Country</th>
                            <th class="text-end pe-3" width="25%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($cities) > 0): ?>
                            <?php foreach ($cities as $city): ?>
                                <tr>
                                    <td class="ps-3"><?php echo $city['CityID']; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($city['CityName']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($city['CountryName']); ?></td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-outline-secondary edit-city-btn me-1" 
                                            data-id="<?php echo $city['CityID']; ?>"
                                            data-name="<?php echo htmlspecialchars($city['CityName']); ?>"
                                            data-country="<?php echo $city['CountryID']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#editCityModal">
                                            Edit
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this city?');">
                                            <input type="hidden" name="action" value="delete_city">
                                            <input type="hidden" name="city_id" value="<?php echo $city['CityID']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No cities found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Country Modal -->
<div class="modal fade" id="addCountryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_locations.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">Add Country</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add_country">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Country Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. United Kingdom">
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

<!-- Edit Country Modal -->
<div class="modal fade" id="editCountryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_locations.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white border-0">
                <h5 class="modal-title fw-bold">Edit Country</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="edit_country">
                <input type="hidden" name="country_id" id="edit_country_id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Country Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="edit_country_name" class="form-control" required>
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

<!-- Add City Modal -->
<div class="modal fade" id="addCityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_locations.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title fw-bold">Add City</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add_city">
                <div class="mb-3">
                    <label class="form-label fw-semibold">City Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. London">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Country <span class="text-danger">*</span></label>
                    <select name="country_id" class="form-select" required>
                        <option value="">-- Choose Country --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?php echo $c['CountryID']; ?>"><?php echo htmlspecialchars($c['Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Save</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit City Modal -->
<div class="modal fade" id="editCityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="admin_locations.php">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white border-0">
                <h5 class="modal-title fw-bold">Edit City</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="edit_city">
                <input type="hidden" name="city_id" id="edit_city_id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">City Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="edit_city_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Country <span class="text-danger">*</span></label>
                    <select name="country_id" id="edit_city_country" class="form-select" required>
                        <option value="">-- Choose Country --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?php echo $c['CountryID']; ?>"><?php echo htmlspecialchars($c['Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
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
    // Country Edit
    const editCountryBtns = document.querySelectorAll('.edit-country-btn');
    editCountryBtns.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_country_id').value = this.getAttribute('data-id');
            document.getElementById('edit_country_name').value = this.getAttribute('data-name');
        });
    });

    // City Edit
    const editCityBtns = document.querySelectorAll('.edit-city-btn');
    editCityBtns.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_city_id').value = this.getAttribute('data-id');
            document.getElementById('edit_city_name').value = this.getAttribute('data-name');
            document.getElementById('edit_city_country').value = this.getAttribute('data-country');
        });
    });
});
</script>

<?php require_once 'footer.php'; ?>

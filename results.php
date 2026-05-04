<?php
// results.php
require_once 'db.php';
require_once 'header.php';

// Fetch programs for sidebar
$programs = [];
$progQuery = $conn->query("SELECT ProgramID, ProgramName FROM program ORDER BY ProgramName");
if ($progQuery) {
    while ($row = $progQuery->fetch_assoc()) {
        $programs[] = $row;
    }
}

// Fetch countries for sidebar
$countries = [];
$countryQuery = $conn->query("SELECT CountryID, Name FROM country ORDER BY Name");
if ($countryQuery) {
    while ($row = $countryQuery->fetch_assoc()) {
        $countries[] = $row;
    }
}

// Get filter values securely
$program_id = isset($_GET['program_id']) && $_GET['program_id'] !== '' ? (int)$_GET['program_id'] : null;
$country_id = isset($_GET['country_id']) && $_GET['country_id'] !== '' ? (int)$_GET['country_id'] : null;
$max_tuition = isset($_GET['max_tuition']) && $_GET['max_tuition'] !== '' ? (float)$_GET['max_tuition'] : null;
$max_ranking = isset($_GET['max_ranking']) && $_GET['max_ranking'] !== '' ? (int)$_GET['max_ranking'] : null;
$min_transfer_credits = isset($_GET['min_transfer_credits']) && $_GET['min_transfer_credits'] !== '' ? (int)$_GET['min_transfer_credits'] : null;
$min_intl_score = isset($_GET['min_intl_score']) && $_GET['min_intl_score'] !== '' ? (int)$_GET['min_intl_score'] : null;

// Build dynamic query
$query = "
    SELECT u.*, c.Name as CityName, co.Name as CountryName
    FROM university u
    JOIN city c ON u.CityID = c.CityID
    JOIN country co ON c.CountryID = co.CountryID
    LEFT JOIN offers o ON u.UniID = o.UniID
    WHERE 1=1
";
$types = "";
$params = [];

if ($program_id !== null) {
    $query .= " AND o.ProgramID = ?";
    $types .= "i";
    $params[] = $program_id;
}
if ($country_id !== null) {
    $query .= " AND co.CountryID = ?";
    $types .= "i";
    $params[] = $country_id;
}
if ($max_tuition !== null) {
    $query .= " AND u.TuitionCost <= ?";
    $types .= "d";
    $params[] = $max_tuition;
}
if ($max_ranking !== null) {
    $query .= " AND u.Ranking <= ? AND u.Ranking > 0";
    $types .= "i";
    $params[] = $max_ranking;
}
if ($min_transfer_credits !== null) {
    $query .= " AND u.MaxTransferCredits >= ?";
    $types .= "i";
    $params[] = $min_transfer_credits;
}
if ($min_intl_score !== null) {
    $query .= " AND u.IntlFriendlyScore >= ?";
    $types .= "i";
    $params[] = $min_intl_score;
}

$query .= " GROUP BY u.UniID ORDER BY CASE WHEN u.Ranking = 0 THEN 1 ELSE 0 END, u.Ranking ASC";

$universities = [];
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $universities[] = $row;
}
$stmt->close();
?>

<style>
    .sidebar-card {
        position: sticky;
        top: 2rem;
        border-radius: 1.2rem;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
    .uni-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 1.2rem;
        overflow: hidden;
    }
    .uni-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
    }
    .card-img-placeholder {
        height: 160px;
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, rgba(13, 110, 253, 0.15) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0d6efd;
        font-size: 3rem;
    }
    .ranking-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background-color: #ffc107;
        color: #000;
        font-weight: bold;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .filter-label {
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #6c757d;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }
</style>

<div class="container mt-5 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-2">
        <h2 class="fw-bolder m-0 text-dark"><i class="bi bi-search text-primary me-2"></i> Search Results</h2>
        <span class="badge bg-primary fs-6 py-2 px-3 rounded-pill shadow-sm"><?php echo count($universities); ?> Universities Found</span>
    </div>

    <div class="row g-5">
        <!-- Left Column: Filtering Sidebar -->
        <div class="col-lg-3">
            <div class="card sidebar-card bg-white">
                <div class="card-header bg-white border-bottom pt-4 pb-3 px-4 d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded px-2 py-1 me-2">
                        <i class="bi bi-funnel-fill"></i>
                    </div>
                    <h5 class="fw-bold m-0">Refine Search</h5>
                </div>
                <div class="card-body p-4">
                    <form action="results.php" method="GET">
                        
                        <div class="mb-4">
                            <label class="filter-label">Intended Program</label>
                            <select name="program_id" class="form-select bg-light border-0 py-2">
                                <option value="">Any Program</option>
                                <?php foreach ($programs as $prog): ?>
                                    <option value="<?php echo $prog['ProgramID']; ?>" <?php echo $program_id === (int)$prog['ProgramID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prog['ProgramName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="filter-label">Target Country</label>
                            <select name="country_id" class="form-select bg-light border-0 py-2">
                                <option value="">Any Country</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo $country['CountryID']; ?>" <?php echo $country_id === (int)$country['CountryID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($country['Name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="filter-label">Max Tuition Cost ($)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-currency-dollar"></i></span>
                                <input type="number" name="max_tuition" class="form-control bg-light border-0 py-2" placeholder="e.g. 50000" value="<?php echo htmlspecialchars($_GET['max_tuition'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="filter-label">Max World Rank</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-trophy"></i></span>
                                <input type="number" name="max_ranking" class="form-control bg-light border-0 py-2" placeholder="e.g. 100" value="<?php echo htmlspecialchars($_GET['max_ranking'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="filter-label">Min Transfer Credits</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-arrow-left-right"></i></span>
                                <input type="number" name="min_transfer_credits" class="form-control bg-light border-0 py-2" placeholder="e.g. 30" value="<?php echo htmlspecialchars($_GET['min_transfer_credits'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="filter-label">Min Intl Friendly Score</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-heart-half"></i></span>
                                <input type="number" name="min_intl_score" class="form-control bg-light border-0 py-2" placeholder="e.g. 80" min="0" max="100" value="<?php echo htmlspecialchars($_GET['min_intl_score'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="d-grid gap-3">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">Apply Filters</button>
                            <a href="results.php" class="btn btn-light text-muted fw-bold border">Reset All</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Results Grid -->
        <div class="col-lg-9">
            <div class="row g-4">
                <?php if (count($universities) > 0): ?>
                    <?php foreach ($universities as $uni): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 shadow-sm uni-card">
                                <div class="position-relative">
                                    <div class="card-img-placeholder">
                                        <i class="bi bi-building-fill"></i>
                                    </div>
                                    <?php if ($uni['Ranking'] > 0): ?>
                                        <div class="ranking-badge">
                                            <i class="bi bi-trophy-fill me-1"></i> #<?php echo $uni['Ranking']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body p-4 d-flex flex-column">
                                    <h5 class="card-title fw-bolder mb-1 text-truncate text-dark" title="<?php echo htmlspecialchars($uni['Name']); ?>">
                                        <?php echo htmlspecialchars($uni['Name']); ?>
                                    </h5>
                                    <p class="text-muted small mb-4">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i> 
                                        <?php echo htmlspecialchars($uni['CityName'] . ', ' . $uni['CountryName']); ?>
                                    </p>
                                    
                                    <div class="d-flex flex-column gap-2 mb-4 mt-auto">
                                        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border border-light">
                                            <small class="text-muted fw-semibold"><i class="bi bi-cash me-1"></i> Tuition</small>
                                            <span class="fw-bold text-dark fs-6"><?php echo $uni['TuitionCost'] > 0 ? '$' . number_format($uni['TuitionCost']) : 'Free'; ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border border-light">
                                            <small class="text-muted fw-semibold"><i class="bi bi-heart me-1"></i> Intl. Score</small>
                                            <span class="fw-bold text-success fs-6"><?php echo $uni['IntlFriendlyScore'] ? $uni['IntlFriendlyScore'] . '/100' : 'N/A'; ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border border-light">
                                            <small class="text-muted fw-semibold"><i class="bi bi-arrow-left-right me-1"></i> Max Transfer</small>
                                            <span class="fw-bold text-primary fs-6"><?php echo $uni['MaxTransferCredits'] ? $uni['MaxTransferCredits'] . ' CR' : 'N/A'; ?></span>
                                        </div>
                                    </div>
                                    
                                    <a href="university_details.php?id=<?php echo $uni['UniID']; ?>" class="btn btn-outline-primary w-100 fw-bold py-2 rounded-pill shadow-sm">
                                        View Details <i class="bi bi-arrow-right-short fs-5 lh-1 ms-1" style="vertical-align: middle;"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5 bg-white border rounded-4 shadow-sm h-100 d-flex flex-column justify-content-center align-items-center" style="min-height: 400px;">
                            <div class="bg-light rounded-circle p-4 mb-4">
                                <i class="bi bi-search text-muted opacity-50" style="font-size: 3rem;"></i>
                            </div>
                            <h3 class="fw-bold text-dark">No Match Found</h3>
                            <p class="text-muted lead mx-auto" style="max-width: 400px;">Try adjusting or clearing your filters on the left to discover more partner universities.</p>
                            <a href="results.php" class="btn btn-primary px-4 py-2 mt-3 fw-bold rounded-pill shadow-sm">Reset All Filters</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<?php
require_once 'footer.php';
?>

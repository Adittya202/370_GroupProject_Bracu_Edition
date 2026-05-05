<?php
// university_details.php
require_once 'db.php';
require_once 'header.php';

$uni_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($uni_id === 0) {
    echo "<div class='container mt-5 pt-5 text-center'><div class='alert alert-danger shadow-sm d-inline-block px-5 py-4'><i class='bi bi-exclamation-triangle-fill fs-1 d-block mb-3'></i> Invalid University ID.</div></div>";
    require_once 'footer.php';
    exit;
}

// 1. Fetch University Details
$uni = null;
$stmtU = $conn->prepare("
    SELECT u.*, c.Name as CityName, co.CountryID, co.Name as CountryName, co.VisaDifficulty, co.AvgLivingCost
    FROM university u
    JOIN city c ON u.CityID = c.CityID
    JOIN country co ON c.CountryID = co.CountryID
    WHERE u.UniID = ?
");
$stmtU->bind_param("i", $uni_id);
$stmtU->execute();
$resU = $stmtU->get_result();
if ($row = $resU->fetch_assoc()) {
    $uni = $row;
}
$stmtU->close();

if (!$uni) {
    echo "<div class='container mt-5 pt-5 text-center'><div class='alert alert-warning shadow-sm d-inline-block px-5 py-4'><i class='bi bi-search fs-1 d-block mb-3'></i> University not found in our system.</div></div>";
    require_once 'footer.php';
    exit;
}

// 2. Fetch Visa Info (Based on Country)
$visas = [];
$stmtV = $conn->prepare("SELECT * FROM visa_info WHERE CountryID = ?");
$stmtV->bind_param("i", $uni['CountryID']);
$stmtV->execute();
$resV = $stmtV->get_result();
while ($row = $resV->fetch_assoc()) {
    $visas[] = $row;
}
$stmtV->close();

// 3. Fetch Accommodation (Based on City)
$accommodations = [];
$stmtA = $conn->prepare("SELECT * FROM accommodation WHERE CityID = ?");
$stmtA->bind_param("i", $uni['CityID']);
$stmtA->execute();
$resA = $stmtA->get_result();
while ($row = $resA->fetch_assoc()) {
    $accommodations[] = $row;
}
$stmtA->close();

// 4. Fetch Required Documents (Based on UniID)
$documents = [];
$stmtD = $conn->prepare("
    SELECT d.DocName 
    FROM document d
    JOIN requires_doc rd ON d.DocID = rd.DocID
    WHERE rd.UniID = ?
");
$stmtD->bind_param("i", $uni_id);
$stmtD->execute();
$resD = $stmtD->get_result();
while ($row = $resD->fetch_assoc()) {
    $documents[] = $row['DocName'];
}
$stmtD->close();

// 5. Fetch Required English Exams
$exams = [];
$stmtE = $conn->prepare("
    SELECT ee.ExamName, rs.MinBandScore 
    FROM requires_score rs
    JOIN english_exam ee ON rs.ExamID = ee.ExamID
    WHERE rs.UniID = ?
");
$stmtE->bind_param("i", $uni_id);
$stmtE->execute();
$resE = $stmtE->get_result();
while ($row = $resE->fetch_assoc()) {
    $exams[] = $row;
}
$stmtE->close();
?>

<!-- Add Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .uni-hero {
        background: linear-gradient(rgba(13, 110, 253, 0.85), rgba(10, 88, 202, 0.95)), url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80') center/cover;
        color: white;
        padding: 5rem 0 4rem;
        border-radius: 1.5rem;
        margin-bottom: 4rem;
        position: relative;
        box-shadow: 0 15px 35px rgba(13, 110, 253, 0.2);
    }
    .info-card {
        border-radius: 1.2rem;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 10px 20px rgba(0,0,0,0.02);
        height: 100%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .info-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    }
    .card-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
    }
    .bg-light-primary { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
    .bg-light-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .bg-light-warning { background-color: rgba(255, 193, 7, 0.15); color: #d39e00; }
    
    .list-group-item-custom {
        border-left: 4px solid transparent;
        transition: all 0.2s;
    }
    .list-group-item-custom:hover {
        border-left-color: #0d6efd;
        background-color: #f8f9fa !important;
    }
</style>

<div class="uni-hero">
    <div class="container position-relative z-1 px-4">
        <a href="javascript:history.back()" class="text-white text-decoration-none mb-4 d-inline-block fw-semibold opacity-75 hover-opacity-100"><i class="bi bi-arrow-left me-1"></i> Back to Results</a>
        
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-4 mt-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-white text-primary fs-6 px-3 py-2 rounded-pill shadow-sm"><i class="bi bi-geo-alt-fill me-1"></i> <?php echo htmlspecialchars($uni['CityName'] . ', ' . $uni['CountryName']); ?></span>
                    <?php if ($uni['MaxTransferCredits']): ?>
                        <span class="badge bg-success bg-opacity-25 text-white border border-success fs-6 px-3 py-2 rounded-pill"><i class="bi bi-arrow-left-right me-1"></i> Max <?php echo $uni['MaxTransferCredits']; ?> Transfer Credits</span>
                    <?php endif; ?>
                </div>
                
                <h1 class="display-3 fw-bolder mb-2" style="letter-spacing: -1px;"><?php echo htmlspecialchars($uni['Name']); ?></h1>
                
                <div class="d-flex flex-wrap gap-4 mt-4">
                    <?php if ($uni['Ranking']): ?>
                        <div class="d-flex align-items-center bg-white bg-opacity-10 rounded-3 px-3 py-2">
                            <i class="bi bi-trophy-fill text-warning fs-3 me-3"></i>
                            <div>
                                <small class="d-block text-white-50 fw-semibold text-uppercase" style="font-size: 0.7rem;">World Rank</small>
                                <strong class="text-white fs-5">#<?php echo $uni['Ranking']; ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex align-items-center bg-white bg-opacity-10 rounded-3 px-3 py-2">
                        <i class="bi bi-cash-stack text-success fs-3 me-3"></i>
                        <div>
                            <small class="d-block text-white-50 fw-semibold text-uppercase" style="font-size: 0.7rem;">Tuition Cost</small>
                            <strong class="text-white fs-5"><?php echo $uni['TuitionCost'] > 0 ? '$' . number_format($uni['TuitionCost']) : 'Free'; ?></strong>
                        </div>
                    </div>
                    
                    <?php if ($uni['IntlFriendlyScore']): ?>
                        <div class="d-flex align-items-center bg-white bg-opacity-10 rounded-3 px-3 py-2">
                            <i class="bi bi-heart-fill text-danger fs-3 me-3"></i>
                            <div>
                                <small class="d-block text-white-50 fw-semibold text-uppercase" style="font-size: 0.7rem;">Intl. Friendly</small>
                                <strong class="text-white fs-5"><?php echo $uni['IntlFriendlyScore']; ?>/100</strong>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pb-2">
                <button class="btn btn-light text-primary btn-lg fw-bold shadow-lg px-4 rounded-pill"><i class="bi bi-bookmark-heart-fill me-2 text-danger"></i> Save to Favorites</button>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        
        <!-- Required Documents -->
        <div class="col-lg-6 col-md-6">
            <div class="card info-card bg-white">
                <div class="card-body p-4 p-xl-5">
                    <div class="card-icon bg-light-primary">
                        <i class="bi bi-file-earmark-check-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-3">Required Documents</h3>
                    <p class="text-muted mb-4 pb-2">Mandatory documentation required by the admissions office for international applications.</p>
                    
                    <?php if (count($documents) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($documents as $doc): ?>
                                <li class="list-group-item px-3 py-3 d-flex align-items-center bg-transparent border-bottom list-group-item-custom">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                        <i class="bi bi-check-lg fw-bold"></i>
                                    </div>
                                    <span class="fw-semibold text-dark fs-6"><?php echo htmlspecialchars($doc); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-4 bg-light rounded-3 border border-dashed">
                            <i class="bi bi-inbox text-muted fs-3 d-block mb-2"></i>
                            <span class="text-muted fw-semibold">No specific documents listed.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- English Exams -->
        <div class="col-lg-6 col-md-6">
            <div class="card info-card bg-white">
                <div class="card-body p-4 p-xl-5">
                    <div class="card-icon" style="background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                        <i class="bi bi-translate"></i>
                    </div>
                    <h3 class="fw-bold mb-3">Language Requirements</h3>
                    <p class="text-muted mb-4 pb-2">Minimum English proficiency scores required for admission to this institution.</p>
                    
                    <?php if (count($exams) > 0): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($exams as $exam): ?>
                                <div class="p-3 border rounded-3 bg-white shadow-sm d-flex justify-content-between align-items-center list-group-item-custom">
                                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-award-fill text-info me-2"></i> <?php echo htmlspecialchars($exam['ExamName']); ?></h6>
                                    <span class="badge bg-info text-dark shadow-sm fs-6 px-3 py-2">Min Score: <?php echo htmlspecialchars($exam['MinBandScore']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 bg-light rounded-3 border border-dashed">
                            <i class="bi bi-chat-slash text-muted fs-3 d-block mb-2"></i>
                            <span class="text-muted fw-semibold">No specific language exams listed.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Visa Information -->
        <div class="col-lg-6 col-md-6">
            <div class="card info-card bg-white">
                <div class="card-body p-4 p-xl-5">
                    <div class="card-icon bg-light-warning">
                        <i class="bi bi-passport-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-3">Visa Requirements</h3>
                    <p class="text-muted mb-4 pb-2">Official visa details for international students studying in <?php echo htmlspecialchars($uni['CountryName']); ?>.</p>
                    
                    <div class="d-flex align-items-center bg-light p-3 rounded-3 mb-4 border">
                        <div class="me-3">
                            <i class="bi bi-shield-exclamation text-secondary fs-3"></i>
                        </div>
                        <div>
                            <span class="text-muted d-block small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Difficulty Level</span>
                            <?php 
                            $diff = strtolower($uni['VisaDifficulty'] ?? 'unknown');
                            $badgeClass = $diff === 'high' ? 'danger' : ($diff === 'medium' ? 'warning text-dark' : 'success');
                            ?>
                            <span class="badge bg-<?php echo $badgeClass; ?> fs-6 rounded-pill px-3 shadow-sm">
                                <?php echo htmlspecialchars($uni['VisaDifficulty'] ?? 'Unknown'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (count($visas) > 0): ?>
                        <div class="accordion border-0" id="visaAccordion">
                            <?php foreach ($visas as $index => $visa): ?>
                                <div class="accordion-item bg-transparent border rounded-3 mb-2 overflow-hidden shadow-sm">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold py-3 bg-white text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#visa-<?php echo $index; ?>">
                                            <i class="bi bi-card-heading me-2 text-warning"></i> <?php echo htmlspecialchars($visa['VisaType']); ?> Visa
                                        </button>
                                    </h2>
                                    <div id="visa-<?php echo $index; ?>" class="accordion-collapse collapse" data-bs-parent="#visaAccordion">
                                        <div class="accordion-body bg-light border-top">
                                            <?php if ($visa['ProcessingTime']): ?>
                                                <div class="d-flex align-items-center mb-3 text-dark">
                                                    <i class="bi bi-stopwatch text-primary fs-5 me-2"></i> 
                                                    <span class="fw-semibold">~<?php echo $visa['ProcessingTime']; ?> Days</span>
                                                    <span class="text-muted ms-1">processing time</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($visa['EmbassyLink']): ?>
                                                <div class="d-grid">
                                                    <a href="<?php echo htmlspecialchars($visa['EmbassyLink']); ?>" target="_blank" class="btn btn-outline-dark fw-semibold">
                                                        Visit Embassy <i class="bi bi-box-arrow-up-right ms-1"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 bg-light rounded-3 border border-dashed">
                            <i class="bi bi-airplane-engines text-muted fs-3 d-block mb-2"></i>
                            <span class="text-muted fw-semibold">Visa information not available.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Accommodation -->
        <div class="col-lg-6 col-md-6">
            <div class="card info-card bg-white">
                <div class="card-body p-4 p-xl-5">
                    <div class="card-icon bg-light-success">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-3">Housing & Living</h3>
                    <p class="text-muted mb-4 pb-2">Accommodation options and estimated living costs in <?php echo htmlspecialchars($uni['CityName']); ?>.</p>
                    
                    <div class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 p-3 mb-4 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-success"><i class="bi bi-wallet2 me-2"></i> Avg. Living Cost</span>
                        <strong class="fs-5 text-success">$<?php echo number_format($uni['AvgLivingCost'] ?? 0); ?><small class="fw-normal text-success opacity-75">/mo</small></strong>
                    </div>
                    
                    <?php if (count($accommodations) > 0): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($accommodations as $acc): ?>
                                <div class="p-3 border rounded-3 bg-white shadow-sm position-relative list-group-item-custom">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold text-dark mb-0 pe-2 lh-base"><?php echo htmlspecialchars($acc['AccName']); ?></h6>
                                        <span class="badge bg-success shadow-sm fs-6 px-2 py-1">$<?php echo number_format($acc['AvgRent']); ?></span>
                                    </div>
                                    <span class="badge bg-light text-secondary border px-2 py-1"><i class="bi bi-tag-fill me-1 opacity-50"></i> <?php echo htmlspecialchars($acc['Type']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 bg-light rounded-3 border border-dashed">
                            <i class="bi bi-house-x text-muted fs-3 d-block mb-2"></i>
                            <span class="text-muted fw-semibold">No local housing listed.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
require_once 'footer.php';
?>

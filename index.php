<?php
// index.php
require_once 'db.php';
require_once 'header.php';

// Fetch programs for the dropdown
$programs = [];
$progQuery = $conn->query("SELECT ProgramID, ProgramName FROM program ORDER BY ProgramName");
if ($progQuery) {
    while ($row = $progQuery->fetch_assoc()) {
        $programs[] = $row;
    }
}

// Fetch countries for the dropdown
$countries = [];
$countryQuery = $conn->query("SELECT CountryID, Name FROM country ORDER BY Name");
if ($countryQuery) {
    while ($row = $countryQuery->fetch_assoc()) {
        $countries[] = $row;
    }
}
?>

<style>
    .hero-section {
        background: linear-gradient(135deg, #0d6efd 0%, #084298 100%);
        color: white;
        padding: 6rem 0;
        border-radius: 1.5rem;
        box-shadow: 0 15px 35px rgba(13, 110, 253, 0.2);
        margin-bottom: 5rem;
        position: relative;
        overflow: hidden;
    }
    
    .hero-section::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
        pointer-events: none;
    }

    .search-card {
        background: rgba(255, 255, 255, 0.98);
        border-radius: 1.2rem;
        transform: translateY(40px);
    }
    
    .feature-icon {
        width: 70px;
        height: 70px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), rgba(13, 110, 253, 0.05));
        color: #0d6efd;
        font-size: 2rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .feature-card {
        padding: 2rem;
        border-radius: 1rem;
        transition: background-color 0.3s ease;
    }

    .feature-card:hover {
        background-color: #f8f9fa;
    }
    
    .feature-card:hover .feature-icon {
        transform: scale(1.1) translateY(-5px);
        box-shadow: 0 10px 20px rgba(13, 110, 253, 0.15);
    }
</style>

<!-- Add Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- Hero Section -->
<div class="hero-section text-center px-3">
    <div class="container position-relative" style="z-index: 2;">
        <span class="badge bg-light text-primary mb-3 px-3 py-2 rounded-pill shadow-sm">Credit Transfer System 2026</span>
        <h1 class="display-3 fw-bolder mb-3" style="letter-spacing: -1px;">Your Global Journey Starts Here</h1>
        <p class="lead mb-5 opacity-75 mx-auto" style="max-width: 600px;">Seamlessly transfer your university credits and discover top-tier study abroad programs tailored to your ambitions.</p>
        
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                <div class="card search-card shadow border-0">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="text-dark fw-bold mb-4 text-start">Where do you want to study?</h4>
                        <form action="results.php" method="GET" class="row g-3">
                            <div class="col-md-5">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-book"></i></span>
                                    <select name="program_id" class="form-select border-start-0 ps-0 text-dark" style="cursor: pointer;" required>
                                        <option value="" selected disabled>Select Program</option>
                                        <?php foreach ($programs as $prog): ?>
                                            <option value="<?php echo $prog['ProgramID']; ?>">
                                                <?php echo htmlspecialchars($prog['ProgramName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-geo-alt"></i></span>
                                    <select name="country_id" class="form-select border-start-0 ps-0 text-dark" style="cursor: pointer;" required>
                                        <option value="" selected disabled>Select Country</option>
                                        <?php foreach ($countries as $country): ?>
                                            <option value="<?php echo $country['CountryID']; ?>">
                                                <?php echo htmlspecialchars($country['Name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="container mt-5 pt-5 mb-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Why Choose CTS?</h2>
        <p class="text-muted">Everything you need to successfully transition to a foreign university.</p>
    </div>
    <div class="row text-center g-4">
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <h5 class="fw-bold">Accurate Credit Mapping</h5>
                <p class="text-muted">Instantly see exactly how your current local courses translate and count towards international university requirements.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-globe-americas"></i>
                </div>
                <h5 class="fw-bold">Global University Network</h5>
                <p class="text-muted">Discover top-ranked universities, compare tuition costs, and analyze international-friendliness scores.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-file-earmark-richtext"></i>
                </div>
                <h5 class="fw-bold">Smart SOP Templates</h5>
                <p class="text-muted">Gain access to highly curated Statement of Purpose templates tailored specifically for your target field of study.</p>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>

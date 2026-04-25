<?php require_once 'header.php'; ?>

<div class="text-center py-5">
    <h1 class="display-5 fw-bold">Welcome to the Credit Transfer System</h1>
    <p class="lead text-muted">Select a module below to manage your data.</p>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100 border-primary">
            <div class="card-body text-center">
                <h4 class="card-title">Universities</h4>
                <p class="card-text">View and filter international universities.</p>
                <a href="university.php" class="btn btn-primary w-100">Enter</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-success">
            <div class="card-body text-center">
                <h4 class="card-title">Credit Equivalency</h4>
                <p class="card-text">Check which courses transfer over.</p>
                <a href="equivalency.php" class="btn btn-success w-100">Enter</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-info">
            <div class="card-body text-center">
                <h4 class="card-title">Accommodation</h4>
                <p class="card-text">Manage housing options by city.</p>
                <a href="accommodation.php" class="btn btn-info w-100 text-white">Enter</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

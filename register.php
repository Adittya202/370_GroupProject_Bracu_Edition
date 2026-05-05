<?php
// register.php
require_once 'db.php';
require_once 'header.php';

// Redirect if already logged in
if (isset($_SESSION['UserID'])) {
    header("Location: " . ($_SESSION['Role'] === 'Admin' ? 'admin_dashboard.php' : 'dashboard.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT UserID FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            // Hash password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'User';
            $status = 'Active';

            // Start transaction to insert into both users and student tables
            $conn->begin_transaction();
            try {
                // Insert into users table
                $insert_user = $conn->prepare("INSERT INTO users (Name, Email, Password, Role, AccountStatus) VALUES (?, ?, ?, ?, ?)");
                $insert_user->bind_param("sssss", $name, $email, $hashed_password, $role, $status);
                $insert_user->execute();
                
                $user_id = $conn->insert_id;
                
                // Insert into student table
                $insert_student = $conn->prepare("INSERT INTO student (UserID) VALUES (?)");
                $insert_student->bind_param("i", $user_id);
                $insert_student->execute();
                
                $conn->commit();
                $success = 'Registration successful! You can now login to your new account.';
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Registration failed. Please try again later.';
            }
        }
        $stmt->close();
    }
}
?>

<div class="row justify-content-center mb-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm mt-5 border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-person-plus-fill fs-2"></i>
                    </div>
                    <h3 class="card-title fw-bold text-dark">Create an Account</h3>
                    <p class="text-muted">Join our portal to start your study abroad journey.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger shadow-sm border-0" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success shadow-sm border-0 text-center py-4" role="alert">
                        <i class="bi bi-check-circle-fill d-block fs-1 mb-2 text-success"></i> 
                        <strong><?php echo htmlspecialchars($success); ?></strong>
                        <div class="mt-4">
                            <a href="login.php" class="btn btn-success btn-lg fw-bold w-100 shadow-sm">Go to Login</a>
                        </div>
                    </div>
                <?php else: ?>

                    <form method="POST" action="register.php">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg bg-light border-0" id="name" name="name" required placeholder="e.g. John Doe">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-lg bg-light border-0" id="email" name="email" required placeholder="name@example.com">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-lg bg-light border-0" id="password" name="password" required minlength="6" placeholder="At least 6 characters">
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-lg bg-light border-0" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Repeat your password">
                        </div>
                        <div class="d-grid mt-4 pt-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">Create Account</button>
                        </div>
                    </form>
                    
                <?php endif; ?>
                
                <div class="text-center mt-4 text-muted border-top pt-4">
                    <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none fw-bold text-primary">Sign in here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<?php
require_once 'footer.php';
?>

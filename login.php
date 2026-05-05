<?php
// login.php
require_once 'db.php';
require_once 'header.php';

// Redirect if already logged in
if (isset($_SESSION['UserID'])) {
    if ($_SESSION['Role'] === 'Admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT UserID, Name, Password, Role, AccountStatus FROM users WHERE Email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if ($row['AccountStatus'] !== 'Active') {
                    $error = 'Your account is suspended. Please contact support.';
                } else {
                    // Verify the password hash
                    if (password_verify($password, $row['Password'])) {
                        // Login successful, set session variables
                        $_SESSION['UserID'] = $row['UserID'];
                        $_SESSION['Name'] = $row['Name'];
                        $_SESSION['Role'] = $row['Role'];

                        // Redirect based on role
                        if ($row['Role'] === 'Admin') {
                            header("Location: admin_dashboard.php");
                        } else {
                            header("Location: dashboard.php");
                        }
                        exit;
                    } else {
                        $error = 'Invalid email or password.';
                    }
                }
            } else {
                $error = 'Invalid email or password.';
            }
            $stmt->close();
        } else {
            $error = 'Database error. Please try again later.';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm mt-5 border-0 rounded-3">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4 fw-bold text-primary">Login</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email address</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" required autofocus placeholder="name@example.com">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required placeholder="••••••••">
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold">Sign In</button>
                    </div>
                </form>
                
                <div class="text-center mt-4 text-muted">
                    <p>Don't have an account? <a href="register.php" class="text-decoration-none fw-semibold">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>

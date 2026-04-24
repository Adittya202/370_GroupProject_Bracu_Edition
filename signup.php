<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connect.php'; // connects db with php file



// if the request method is post then the
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); //using bcrypt to encrypt our pass


// sql - insertion
    $sql = "INSERT INTO USERS (Name, Email, Password) VALUES ('$name', '$email', '$password')";

    // if user info valid , create user_id and include else throw error
   if(mysqli_query($conn, $sql)) {
    $last_id = mysqli_insert_id($conn); //cration of last_id attribute in users table
    $sql_student = "INSERT INTO STUDENT (UserID) VALUES ('$last_id')";
    mysqli_query($conn, $sql_student);

    header("Location: login.php?success=Registration Successful! Please log in");

    }
    else {
        $error = "Registration failed: " . mysqli_error($conn);
    }

}


?>


<!DOCTYPE html>
<html>
<head>
    <!-- BOOTSTRAP IMPLEMENTATION LINK (COPIED) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title> Signup </title>
</head>


<body class="bg-light">
    <div class="container mt-5">
        <div class="col-md-4 mx-auto card p-4 shadow">
            <h2 class="text-center mb-4">Create Account</h2>
            <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="POST">
                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100"> Sign Up </button>
            </form>
            <p class="text-center mt-3"><a href="login.php">Already have an account? Login</a></p>
        </div>
    </div>
</body>
</html>


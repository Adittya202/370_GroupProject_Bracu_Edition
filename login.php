<?php
include 'db_connect.php';
session_start();

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']); 
    $password = $_POST['password']; 

   
    $result = mysqli_query($conn, "SELECT * FROM USERS WHERE Email='$email'");
    $user = mysqli_fetch_assoc($result);

    if($user && password_verify($password, $user['Password'])) {
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['user_name'] = $user['Name'];
        header("Location: dashboard.php"); 
        exit();
    }
    else {
        $error = "Invalid email or password";
    }
}
?>   


<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Login</title>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="col-md-4 mx-auto card p-4 shadow">
            <h2 class="text-center mb-4">Login</h2>
            <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="POST">
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Login</button>
            </form>
        </div>  
    </div>
</body>
</html>
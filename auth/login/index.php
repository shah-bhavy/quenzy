<?php
session_start();

if(isset($_SESSION['user_id'])){
    header("Location: ../../dashboard/");
}
require '../../database/db.php'; // Ensure this file exists and connects to the DB

// Ensure the database connection is successful
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = mysqli_real_escape_string($mysqli, trim($_POST['email']));
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please enter both email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";   
    } else {
        // Check if the email exists in the database
        $stmt = $mysqli->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Bind result variables
            $stmt->bind_result($user_id, $user_name, $user_email, $hashed_password);
            $stmt->fetch();

            // Verify password
            if (password_verify($password, $hashed_password)) {
                // Login successful
                // Store user information in the session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $user_name;
                $_SESSION['user_email'] = $user_email;

                // If the user doesn't have a profile picture, set it to the default 'pro.png'
                // $_SESSION['profile_picture'] = !empty($profile_picture) ? $profile_picture : 'pro.png'; 

                // Set success message and redirect
                $_SESSION['success'] = "Login successful! Redirecting...";
                header("Location: ../../dashboard"); // Redirect to dashboard or homepage
                exit();
            } else {
                $_SESSION['error'] = "Invalid password. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Email not registered. Please sign up first.";
        }

        // Close statement
        $stmt->close();
    }
}

// Close database connection
$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quenzy Login</title>
    <link rel="stylesheet" href="../../styles/login.css">
    <style>
        .message {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .error {
            color: #f44336;
        }
        .success {
            color: #4CAF50;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="left-section"></div>
    <div class="right-section">
        <!-- Display error message if any -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <!-- Display success message if any -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <h1>Login</h1>
        <form id="login-form" method="POST" action="#">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-buttons">
                <button type="submit" name="login" class="register-btn">LOGIN</button>
            </div>
            <div class="form-footer">
                <p>Don't have an account? <a href="../signup/index.php">Sign Up</a></p>
                <p><a href="../forgot-password/forgot-password.php">Forgot Password?</a></p>
            </div>
        </form>
    </div>
</div>

</body>
</html>

<?php
require '../includes/db.php';

$error = '';
$success = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if token is valid and not expired
    $stmt = $mysqli->prepare("SELECT user_id, expires FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $expires);
        $stmt->fetch();

        if ($expires >= date("U")) {
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $new_password = trim($_POST['new_password']);
                $confirm_password = trim($_POST['confirm_password']);

                if ($new_password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update password in the users table
                    $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    $stmt->execute();

                    // Delete the token from the password_resets table
                    $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    $success = "Your password has been reset. You can now <a href='../login/index.php'>login</a>.";
                }
            }
        } else {
            $error = "This reset link has expired.";
        }
    } else {
        $error = "Invalid or expired token.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../signup/styles.css">
</head>
<body>

<div class="container">
    <div class="left-section"></div>
    <div class="right-section">
        <?php if(!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(!empty($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>

        <h1>Reset Password</h1>
        <form method="POST" action="#">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-buttons">
                <button type="submit" class="register-btn">Reset Password</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>

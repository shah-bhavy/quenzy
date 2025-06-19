<?php
require '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_verified']) {
            echo "Login successful!";
        } else {
            echo "Account not verified. Please verify via OTP!";
        }
    } else {
        echo "Invalid credentials!";
    }
}
?>

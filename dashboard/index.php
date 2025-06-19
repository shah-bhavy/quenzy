<?php
session_start();
require_once '../database/db.php'; // Database mysqliection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: ../auth/login');
    exit;
}

// Retrieve user ID from session
$user_id = $_SESSION['user_id'];

// Query to fetch the user's role
$query = "SELECT role FROM users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if the user exists and has a role
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $role = $user['role'];

    // Redirect based on role
    if ($role === 'teacher') {
        // Allow access to teacher dashboard
        header('Location: teacher/');
    } elseif ($role === 'student') {
        // Allow access to student dashboard
        header('Location: student/');
    } else {
        // Invalid role - logout for security
        session_destroy();
        header('Location: ../auth/login?error=invalid_role');
    }
    exit;
} else {
    // User not found - logout for security
    session_destroy();
    header('Location: ../auth/login?error=user_not_found');
    exit;
}
?>

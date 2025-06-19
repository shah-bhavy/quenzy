<?php
// Database connection and session
session_start();
require '../../../database/db.php'; 

// Fetch teacher ID from session
if (!isset($_SESSION['user_id'])) {
    die("Error: Teacher ID is not set in the session.");
}

$teacher_id = $_SESSION['user_id'];

// If form data exists
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_id = (int) $_POST['test_id'];

    // Validate input
    if (empty($test_id)) {
        die("Test ID is required.");
    }

    // Check if test belongs to the teacher
    $stmt = $mysqli->prepare("SELECT * FROM tests WHERE test_id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $test_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Test not found or you do not have permission to delete this test.");
    }

    // Delete associated questions
    $stmt = $mysqli->prepare("DELETE FROM test_questions WHERE test_id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();

    // Delete test
    $stmt = $mysqli->prepare("DELETE FROM tests WHERE test_id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();

    echo "Test deleted successfully.";
    header("Location: tests.php?message=Test deleted successfully.");
    exit();
}
?>

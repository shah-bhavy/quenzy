<?php
session_start();
require '../../../../database/db.php'; // Adjust path as per your actual file location

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to add questions.";
    header("Location: ../../auth/login.php");
    exit();
}

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $chapter_id = filter_input(INPUT_POST, 'chapter_id', FILTER_VALIDATE_INT);
    $question_text = filter_input(INPUT_POST, 'question_text', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    $marks = filter_input(INPUT_POST, 'marks', FILTER_VALIDATE_FLOAT);

    // Basic validation
    if ($chapter_id === false || $chapter_id <= 0) {
        $_SESSION['error'] = "Invalid Chapter ID provided.";
        header("Location: view_questions.php"); // Redirect back, perhaps to a default page or current chapter
        exit();
    }
    if (empty($question_text)) {
        $_SESSION['error'] = "Question text cannot be empty.";
        header("Location: view_questions.php?chapter_id=" . $chapter_id);
        exit();
    }
    if ($marks === false || $marks <= 0) {
        $_SESSION['error'] = "Invalid marks value. Marks must be a positive number.";
        header("Location: view_questions.php?chapter_id=" . $chapter_id);
        exit();
    }

    // Trim whitespace from question text
    $question_text = trim($question_text);

    // Insert question into database
    $created_at = date('Y-m-d H:i:s'); // Get current timestamp

    $sql = "INSERT INTO questions (question_text, chapter_id, marks, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sid", $question_text, $chapter_id, $marks);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Question added successfully!";
        } else {
            $_SESSION['error'] = "Error adding question: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database query preparation failed: " . $mysqli->error;
    }

    $mysqli->close(); // Close database connection

    // Redirect back to the view_questions page for the respective chapter
    header("Location: view_questions.php?chapter_id=" . $chapter_id);
    exit();

} else {
    // If not a POST request, redirect or show an error
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../subjects.php"); // Or any appropriate default page
    exit();
}
?>
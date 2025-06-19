<?php
// Include database connection
require_once '../../../../database/db.php'; // Update the path to your config file
session_start();

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Check if the subject ID is provided
if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])) {
    $subject_id = intval($_GET['subject_id']);

    // Verify the subject belongs to the logged-in teacher
    $stmt = $mysqli->prepare("SELECT subject_id FROM subjects WHERE subject_id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $subject_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Subject belongs to the teacher, delete associated chapters first
        $stmt->close();

        $delete_chapters = $mysqli->prepare("DELETE FROM chapters WHERE subject_id = ?");
        $delete_chapters->bind_param("i", $subject_id);
        $delete_chapters->execute();
        $delete_chapters->close();

        // Now delete the subject
        $delete_subject = $mysqli->prepare("DELETE FROM subjects WHERE subject_id = ?");
        $delete_subject->bind_param("i", $subject_id);

        if ($delete_subject->execute()) {
            $_SESSION['success'] = "Subject and its chapters deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete the subject. Please try again.";
        }
        $delete_subject->close();
    } else {
        $_SESSION['error'] = "Invalid subject or you do not have permission to delete this subject.";
    }
} else {
    $_SESSION['error'] = "No subject selected for deletion.";
}

// Redirect back to the subjects dashboard
header("Location: ../subjects.php");
exit;
?>

<?php
session_start();
require '../../../../database/db.php';

if (isset($_GET['question_id']) && isset($_GET['chapter_id'])) {
    $question_id = (int)$_GET['question_id'];
    $chapter_id = (int)$_GET['chapter_id'];
    //remove the foreign key check constraint temporarily
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
    $stmt = $mysqli->prepare("DELETE FROM questions WHERE question_id = ? AND chapter_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $question_id, $chapter_id);
        if ($stmt->execute()) {
            $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
            $_SESSION['success'] = "Question deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting question: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error: " . $mysqli->error;
    }
    header("Location: view_questions.php?chapter_id=" . $chapter_id);
    exit();
} else {
    $_SESSION['error'] = "Invalid request or missing parameters.";
    header("Location: ../subjects.php"); // Or appropriate default
    exit();
}
?>
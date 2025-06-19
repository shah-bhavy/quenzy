<?php
session_start();
require '../../../../database/db.php';

// Ensure user is logged in and is a teacher
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_chapter'])) {
    $chapter_id = intval($_POST['chapter_id']);
    $chapter_name = trim($_POST['chapter_name']);
    $subject_id = intval($_POST['subject_id']);

    if (!empty($chapter_name)) {
        $stmt = $mysqli->prepare("UPDATE chapters SET chapter_name = ? WHERE chapter_id = ?");
        $stmt->bind_param("si", $chapter_name, $chapter_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Chapter updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update chapter.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Chapter name cannot be empty.";
    }
}

header("Location: view_subject.php?subject_id=$subject_id");
exit();
?>

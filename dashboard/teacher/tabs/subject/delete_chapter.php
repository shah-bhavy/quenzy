<?php
session_start();
require '../../../../database/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit();
}

$subject_id = $_GET['subject_id'];
$chapter_id = $_GET['chapter_id'];

// Remove the foreign key check constraint temporarily
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

// Delete the chapter from the database
$delete_stmt = $mysqli->prepare("DELETE FROM chapters WHERE chapter_id = ? AND subject_id = ?");
$delete_stmt->bind_param("ii", $chapter_id, $subject_id);



if ($delete_stmt->execute()) {
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
    $_SESSION['success'] = "Chapter deleted successfully!";
    header("Location: view_subject.php?subject_id=$subject_id");
    exit();
} else {
    $_SESSION['error'] = "Failed to delete chapter.";
    header("Location: view_subject.php?subject_id=$subject_id");
    exit();
}

?>

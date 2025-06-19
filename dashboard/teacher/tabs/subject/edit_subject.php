<?php
// edit_subject.php
require_once '../../../../database/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $subject_name = trim($_POST['subject_name']);

    if (!empty($subject_name)) {
        $stmt = $mysqli->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ? AND teacher_id = ?");
        $stmt->bind_param("sii", $subject_name, $subject_id, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Subject updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update subject.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Subject name cannot be empty.";
    }
    header("Location: ../subjects.php");
    exit;
}
?>

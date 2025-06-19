<?php
session_start();
require_once '../../../database/db.php';
if (isset($_POST['subject_id'])) {
    $subject_id = intval($_POST['subject_id']);
    $chapters = [];
    $sql = "SELECT chapter_id, chapter_name FROM chapters WHERE subject_id = $subject_id";
    $result = $mysqli->query($sql);
    $_SESSION['subject_id']=$subject_id;
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['chapter_name']) . '</td>';
            echo '<td><input type="number" name="weightage[' . $row['chapter_id'] . ']" min="0" max="100" required></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="2">No chapters found for this subject.</td></tr>';
    }
} else {
    echo 'Invalid request.';
}
?>

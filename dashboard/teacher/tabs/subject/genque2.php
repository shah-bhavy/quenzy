<?php
session_start();
require '../../../../database/db.php';
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD']!=='POST') {
    echo json_encode(['success'=>false,'message'=>'Auth error']); exit;
}
$chapter_id = isset($_POST['chapter_id'])?(int)$_POST['chapter_id']:0;
$delete = !empty($_POST['delete']);

// Optionally: delete all questions if requested
if ($delete && $chapter_id > 0) {
    $mysqli->query("DELETE FROM questions WHERE chapter_id = $chapter_id");
}

// This will execute quegen2.py for the chapter and add questions into the DB
// Make sure quegen2.py takes chapter_id as argument and adds 5-5 questions PER MARKER into the DB
$output = [];
$return_var = 0;
exec("python3 quegen2.py ".escapeshellarg($chapter_id), $output, $return_var);

if ($return_var === 0) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'AI question generation failed.']);
}
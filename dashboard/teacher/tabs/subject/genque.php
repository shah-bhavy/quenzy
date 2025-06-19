<?php
session_start();
include('../../../../database/db.php'); // Adjust this include as per your actual file location

$file_path = null;
$chapter_id = null; // This will now be the primary identifier passed
$upload_id = null; // Will be derived from the latest upload for the given chapter_id

if (isset($_POST['delete']) && $_POST['delete'] == 1) {
    $action = 'delete_and_generate';
} else {
    $action = 'generate';
}

// --- Logic for getting file information ---

// Priority 1: Check for explicit chapter_id passed via GET/POST
if (isset($_GET['chapter_id']) || isset($_POST['chapter_id'])) {
    $chapter_id = (int)($_GET['chapter_id'] ?? $_POST['chapter_id']);

    // Lookup the LATEST file_path and upload_id for this chapter_id from the 'uploads' table
    // We order by id DESC and LIMIT 1 to get the most recent upload for that chapter
    $sql = "SELECT id, file_path FROM uploads WHERE chapter_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $mysqli->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $chapter_id);
        $stmt->execute();
        $stmt->bind_result($fetched_upload_id, $fetched_file_path);
        if ($stmt->fetch()) {
            $upload_id = $fetched_upload_id;
            $file_path = $fetched_file_path;
            // Optionally, update session for consistency if this becomes the "current" file
            $_SESSION['last_uploaded_file'] = $file_path;
            $_SESSION['last_uploaded_chapter_id'] = $chapter_id;
            $_SESSION['last_upload_id'] = $upload_id;
        } else {
            // No uploads found for this chapter ID
            echo json_encode(['success'=>false, 'message'=>"No document found for the provided chapter ID ($chapter_id). Please upload a document for this chapter first."]);
            exit();
        }
        $stmt->close();
    } else {
        echo json_encode(['success'=>false, 'message'=>"Failed to prepare database statement for fetching latest upload. Error: " . $mysqli->error]);
        exit();
    }
}
// Priority 2: If no explicit chapter_id, fall back to session for recently uploaded file
// This block will only execute if the above 'if' block was false
elseif (isset($_SESSION['last_uploaded_file']) && isset($_SESSION['last_uploaded_chapter_id'])) {
    $file_path = $_SESSION['last_uploaded_file'];
    $chapter_id = $_SESSION['last_uploaded_chapter_id'];
    $upload_id = $_SESSION['last_upload_id'] ?? null;
}

// --- End of logic for getting file information ---

// Check if we successfully retrieved file_path and chapter_id
if (!$file_path || !$chapter_id) {
    echo json_encode(['success'=>false, 'message'=>"No valid file information or chapter ID available for question generation. Please upload a document or select a chapter."]);
    exit();
}

// If delete_and_generate action is requested, wipe all previous questions for this chapter
if (($action === 'delete_and_generate') && $chapter_id) {
    // Using prepared statement for security

    // temporarily turn off foreign key checks to avoid issues with deleting questions
    $mysqli->query("SET FOREIGN_KEY_CHECKS=0");

    $delete_sql = "DELETE FROM questions WHERE chapter_id = ?";
    $delete_stmt = $mysqli->prepare($delete_sql);
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $chapter_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        // Re-enable foreign key checks
        $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
        // Don't exit here, continue to generate new questions
    } else {
        echo json_encode(['success'=>false, 'message'=>"Failed to prepare statement for deleting old questions. Error: " . $mysqli->error]);
        exit();
    }
}

// Now, call quegen.py (which generates 5-5 questions per marker)
// Pass the file_path and chapter_id as arguments// Use the full absolute path to your python.exe executable
// IMPORTANT: Adjust this path to where Python is actually installed on your system.
// Example for Windows:
$py_path = "C:\\Users\\YOUR_USERNAME\\AppData\\Local\\Programs\\Python\\Python313\\python.exe"; // <--- CHANGE THIS LINE
// Make sure to use double backslashes \\ or forward slashes / for paths on Windows in PHP strings.
// E.g., "C:/Users/YourUser/AppData/Local/Programs/Python/Python39/python.exe" also works.

// Example for Linux (common path):
// $py_path = "/usr/bin/python3";$py_path = "python3"; // or python, adjust as appropriate for your server!
$command = "$py_path quegen.py " . escapeshellarg($file_path) . " " . escapeshellarg($chapter_id);

exec($command, $output, $ret);

if ($ret === 0) {
    // Success — send generic success for AJAX
    echo json_encode(['success'=>true, 'message'=>"Questions generated successfully via AI.", 'output' => $output]);
} else {
    // Log the error for debugging, but send a user-friendly message
    error_log("Python script error (code $ret) for file: $file_path, chapter: $chapter_id. Output: " . implode("\n", $output));
    echo json_encode(['success'=>false, 'message'=>"Python script error (code $ret). Please contact support. Error details have been logged."]);
}

// Close the database connection
$mysqli->close();
?>

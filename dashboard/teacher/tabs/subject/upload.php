<?php
session_start();

// Include database connection (assuming db.php is in ../../database/)
include('../../../../database/db.php');

if (isset($_FILES['file']) && isset($_POST['chapter_id'])) {
    $chapter_id = $_POST['chapter_id'];

    $target_dir = "uploads/";
    // It's good practice to sanitize and unique-ify filenames to prevent overwrites and security issues.
    // For simplicity, I'm keeping your original basename approach, but be aware of this.
    $fileName = basename($_FILES["file"]["name"]);
    $target_file = $target_dir . $fileName;

    // Create the uploads directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        // Use the existing $mysqli connection from db.php
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }

        // Prepare the SQL statement with a placeholder for file_path and chapter_id
        // Using prepared statements for security!
        $sql = "INSERT INTO uploads (file_path, chapter_id) VALUES (?, ?)";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            // Bind parameters: 's' for string (file_path), 'i' for integer (chapter_id)
            $stmt->bind_param("si", $target_file, $chapter_id);

            // Execute the statement
            if ($stmt->execute()) {
                // Store file_path and chapter_id in session for potential use by generate_questions.php
                // Or you could pass an 'upload_id' to generate_questions.php to retrieve details from DB
                $_SESSION['last_uploaded_file'] = $target_file;
                $_SESSION['last_uploaded_chapter_id'] = $chapter_id;
                $_SESSION['last_upload_id'] = $stmt->insert_id; // Get the ID of the newly inserted row

                echo "File uploaded and saved to database successfully!";
                // You might want to redirect the user to a success page or the question generation page
                // header("Location: generate_questions_form.php?upload_id=" . $stmt->insert_id);
                // exit();
            } else {
                echo "Error executing database statement: " . $stmt->error;
            }
            $stmt->close(); // Close the prepared statement
        } else {
            echo "Error preparing database statement: " . $mysqli->error;
        }

        // $mysqli->close(); // Don't close $mysqli here if it's reused across the app
    } else {
        echo "Error uploading file to server.";
    }
} else {
    // Handle cases where file or chapter_id is not set (e.g., direct access or incomplete form submission)
    echo "No file uploaded or chapter ID provided.";
}
?>
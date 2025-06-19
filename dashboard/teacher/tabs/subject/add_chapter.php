<?php
session_start();
require '../../../../database/db.php'; // Include the DB connection

// Ensure user is logged in and is a teacher
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login"); // Redirect to login if not logged in
    exit();
}

$subject_id = $_GET['subject_id']; // Get the subject ID from the URL

// Fetch the subject name for display
$stmt = $mysqli->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$stmt->bind_result($subject_name);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $chapter_name = mysqli_real_escape_string($mysqli, trim($_POST['chapter_name']));

    // Basic validation
    if (empty($chapter_name)) {
        $_SESSION['error'] = "Chapter name is required.";
    } else {
        // Insert chapter into the database
        $stmt = $mysqli->prepare("INSERT INTO chapters (subject_id, chapter_name) VALUES (?, ?)");
        $stmt->bind_param("is", $subject_id, $chapter_name);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Chapter added successfully!";
            header("Location: view_subject.php?subject_id=" . $subject_id); // Redirect to subject dashboard
            exit();
        } else {
            $_SESSION['error'] = "Failed to add chapter. Please try again.";
        }
        
        $stmt->close();
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Chapter - <?php echo $subject_name; ?></title>
    <link rel="stylesheet" href="../../styles/dashboard.css">
    <style>
        /* Styling for Add Chapter page */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
        }

        .navbar {
            background-color: #333;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .navbar ul {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            font-size: 16px;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        .container {
            width: 80%;
            margin: 30px auto;
        }

        .form-container {
            background-color: white;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }

        .form-container h2 {
            color: #333;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-size: 16px;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .message {
            padding: 15px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }

        .error {
            background-color: #f44336;
        }

        .back-btn {
            background-color: #ff5722;
            text-decoration: none;
            padding: 10px 20px;
            color: white;
            border-radius: 5px;
            font-size: 14px;
        }

        .back-btn:hover {
            background-color: #e64a19;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <ul>
            <li><a href="../../dashboard">Dashboard</a></li>
            <li><a href="view_subject.php?subject_id=<?php echo $subject_id; ?>"><?php echo $subject_name; ?></a></li>
            <li><a href="../../logout">Logout</a></li>
        </ul>
    </nav>

    <div class="container">

        <!-- Display error/success messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Add New Chapter - <?php echo $subject_name; ?></h2>
            <form method="POST" action="add_chapter.php?subject_id=<?php echo $subject_id; ?>">
                <div class="form-group">
                    <label for="chapter_name">Chapter Name</label>
                    <input type="text" id="chapter_name" name="chapter_name" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn">Add Chapter</button>
                </div>
            </form>
        </div>

        <div class="back-btn-container">
            <a href="view_subject.php?subject_id=<?php echo $subject_id; ?>" class="back-btn">Back to Subject</a>
        </div>

    </div>

</body>
</html>

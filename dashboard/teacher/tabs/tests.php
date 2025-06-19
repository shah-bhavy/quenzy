<?php
session_start();
// Ensure user is logged in and has the 'teacher' role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../auth/login/index.php");
    exit();
}

include('../../../database/db.php'); // Adjust path as per your actual file location

$user_id = $_SESSION['user_id'];

// Verify user role (optional, but good for security)
$stmt = $mysqli->prepare("SELECT role, name FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role, $userName);
$stmt->fetch();
$stmt->close();

if ($role !== 'teacher') {
    header("Location: ../" . ($role === 'student' ? 'student' : 'auth') . "/dashboard.php");
    exit();
}

// Fetch tests with subject names and chapter counts using JOINs
$tests_query = $mysqli->query("
    SELECT 
        t.test_id, 
        t.test_name, 
        s.subject_name,
        (SELECT COUNT(*) FROM test_questions tq WHERE tq.test_id = t.test_id) AS total_questions
    FROM tests t
    JOIN subjects s ON t.subject_id = s.subject_id
    WHERE t.teacher_id = $user_id
    ORDER BY t.test_id DESC
");

$tests = [];
if ($tests_query->num_rows > 0) {
    while ($row = $tests_query->fetch_assoc()) {
        $tests[] = $row;
    }
}

// Fetch subjects for the "Create New Test" modal dropdown
$subjects_query = $mysqli->query("SELECT subject_id, subject_name FROM subjects WHERE teacher_id = $user_id");
$subjects = [];
if ($subjects_query->num_rows > 0) {
    while ($row = $subjects_query->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Notification/message handling (from previous actions like delete, generate)
$message = '';
$message_type = ''; // 'success' or 'error'

if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    $message_type = 'success';
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    $message_type = 'error';
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Tests</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #7B61FF;
            --glass: rgba(255, 255, 255, 0.45);
            --active-bg: rgba(123, 97, 255, 0.18);
            --active-border: #7B61FF;
            --sidebar-bg: #FFF;
            --sidebar-blur: 22px;
            --kpi-glass: rgba(123, 97, 255, 0.11);
            --kpi-border: rgba(123, 97, 255, 0.13);
            --main-bg: linear-gradient(120deg, #E7E0FD 0%, #F4F2FF 100%);
            --nav-icon: #555;
            --font-size: 16px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: var(--font-size);
            margin: 0;
            display: flex;
            min-height: 100vh;
            background: var(--main-bg);
            color: #222;
            overflow-x: hidden;
        }

        .sidebar {
            width: 64px;
            background: var(--sidebar-bg);
            box-shadow: 2px 0 14px rgba(50, 48, 156, .06);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: sticky;
            left: 0;
            top: 0;
            z-index: 21;
            min-height: 100vh;
            backdrop-filter: blur(var(--sidebar-blur));
            user-select: none;
        }

        .sidebar ul {
            padding: 0;
            margin: 0;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0;
            flex: 1;
            width: 100%;
            align-items: center;
        }

        .sidebar li {
            width: 100%;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 44px;
            text-decoration: none;
            color: var(--nav-icon);
            font-size: 19px;
            border-left: 4px solid transparent;
            border-radius: 10px 0 0 10px;
            transition: background 0.22s;
            margin: 0;
        }

        .sidebar a.active,
        .sidebar a:focus {
            background: var(--active-bg);
            border-left: 4px solid var(--active-border);
            color: var(--primary);
        }

        .sidebar .logo {
            font-weight: 800;
            font-size: 27px;
            margin: 18px 0 15px 0;
            color: var(--primary);
            background: var(--glass);
            border-radius: 12px;
            padding: 8px 0;
            width: 100%;
            text-align: center;
            letter-spacing: -1px;
        }

        .sidebar .logout {
            margin-bottom: 18px;
            width: 100%;
        }

        .sidebar .logout a {
            color: #fa5757;
            background: transparent;
            font-size: 19px;
        }

        /* AUX SIDEBAR - Not used in this layout, but keeping styles from dashboard for consistency */
        .aux {
            display: none; /* Hide aux sidebar for this page as per dashboard reference */
        }

        /* MAIN CONTENT */
        .main {
            flex: 1;
            overflow: auto;
            padding: 26px 3% 15px 3%;
            min-width: 0;
        }

        .header-glass {
            border-radius: 21px;
            background: var(--glass);
            box-shadow: 0 4px 32px #7b61ff20;
            padding: 22px 28px;
            margin-bottom: 23px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            backdrop-filter: blur(14px);
        }

        .header-glass h1 {
            font-size: 2.25rem;
            margin: 0 0 5px 0;
        }

        .header-glass p {
            margin: 0;
            font-size: 17.6px;
            color: #555;
        }

        /* Test Cards */
        .tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .test-card {
            background: rgba(255, 255, 255, 0.6); /* Lighter glass for cards */
            border: 1.5px solid rgba(123, 97, 255, 0.2); /* Softer border for cards */
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 25px rgba(123, 97, 255, 0.15);
            backdrop-filter: blur(8px);
            transition: box-shadow 0.2s, transform 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(123, 97, 255, 0.25);
        }

        .test-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--primary);
        }

        .test-card p {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 12px;
        }

        .test-card .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap; /* Allow buttons to wrap */
        }

        .glassmorphism-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Specific button colors and hover effects */
        .btn-view {
            color: #fff;
            background: linear-gradient(135deg, rgba(123, 97, 255, 0.7), rgba(90, 70, 255, 0.7));
        }
        .btn-view:hover {
            background: linear-gradient(135deg, rgba(123, 97, 255, 0.9), rgba(90, 70, 255, 0.9));
            box-shadow: 0 4px 15px rgba(90, 70, 255, 0.3);
            transform: translateY(-2px);
        }

        .btn-edit {
            color: #fff;
            background: linear-gradient(135deg, rgba(255, 175, 50, 0.7), rgba(255, 140, 0, 0.7));
        }
        .btn-edit:hover {
            background: linear-gradient(135deg, rgba(255, 175, 50, 0.9), rgba(255, 140, 0, 0.9));
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3);
            transform: translateY(-2px);
        }

        .btn-delete {
            color: #fff;
            background: linear-gradient(135deg, rgba(255, 99, 71, 0.7), rgba(220, 20, 60, 0.7));
        }
        .btn-delete:hover {
            background: linear-gradient(135deg, rgba(255, 99, 71, 0.9), rgba(220, 20, 60, 0.9));
            box-shadow: 0 4px 15px rgba(220, 20, 60, 0.3);
            transform: translateY(-2px);
        }

        /* Modal specific styles for glassmorphism */
        #modal .bg-white {
            background: var(--glass); /* Use glass background for modal */
            backdrop-filter: blur(15px); /* Apply blur to modal */
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 30px rgba(123, 97, 255, 0.2);
            padding: 30px;
            border-radius: 20px;
        }

        #modal button[type="submit"], #modal .close-modal-btn {
            background: var(--primary);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        #modal button[type="submit"]:hover, #modal .close-modal-btn:hover {
            background: #593eda;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        #modal label {
            color: #444; /* Darker label text for better contrast on glass */
            font-weight: 600;
            margin-bottom: 5px;
        }

        #modal input[type="text"],
        #modal input[type="number"],
        #modal select {
            background: rgba(255, 255, 255, 0.7); /* Slightly opaque input background */
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 10px 12px;
            border-radius: 8px;
            color: #333;
            transition: all 0.2s;
        }

        #modal input[type="text"]:focus,
        #modal input[type="number"]:focus,
        #modal select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(123, 97, 255, 0.3);
            outline: none;
            background: #fff;
        }

        @media (max-width:900px) {
            .main {
                padding: 9px 7px;
            }
            .aux {
                display: none;
            }
        }

        @media (max-width:600px) {
            .sidebar {
                width: 100%;
                flex-direction: row;
                height: 52px;
                min-height: 0;
                position: fixed;
                bottom: 0;
                top: auto;
                background: #fff9;
            }
            .sidebar ul {
                flex-direction: row;
            }
            .sidebar .logo,
            .sidebar .logout {
                display: none;
            }
            .main {
                padding-top: 25px;
                padding-bottom: 60px; /* Add padding to prevent content from being hidden by fixed sidebar */
            }
            .tests-grid {
                grid-template-columns: 1fr; /* Stack cards on small screens */
            }
        }

        /* Message Box Styles */
        .message-box {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="logo">Q</div>
        <ul>
            <li><a href="../" title="Home"><i class="fa fa-home"></i></a></li>
            <li><a href="subjects.php" title="Subjects"><i class="fa fa-book"></i></a></li>
            <li><a class="active" href="tests.php" title="Tests"><i class="fa fa-clipboard-list"></i></a></li>
            <li><a href="#" title="Settings"><i class="fa fa-cog"></i></a></li>
        </ul>
        <div class="logout"><a href="../../auth/logout.php" title="Logout"><i class="fa fa-sign-out-alt"></i></a></div>
    </div>

    <div class="main">
        <div class="header-glass">
            <div>
                <h1>Manage Tests</h1>
                <p>Create, view, and organize your examination papers.</p>
            </div>
            <button class="glassmorphism-btn btn-view" onclick="openModal()">
                <i class="fa fa-plus mr-2"></i>Create New Test
            </button>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="message-box <?php echo $message_type; ?>">
                <?php if ($message_type === 'success') : ?>
                    <i class="fa fa-check-circle"></i>
                <?php else : ?>
                    <i class="fa fa-exclamation-triangle"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="tests-grid">
            <?php if (!empty($tests)) : ?>
                <?php foreach ($tests as $test) : ?>
                    <div class="test-card">
                        <div>
                            <h3><?php echo htmlspecialchars($test['test_name']); ?></h3>
                            <p class="text-sm">Subject: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($test['subject_name']); ?></span></p>
                            <p class="text-sm">Total Questions: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($test['total_questions']); ?></span></p>
                        </div>
                        <div class="actions">
                            <a href="view_test.php?test_id=<?php echo $test['test_id']; ?>" class="glassmorphism-btn btn-view">
                                <i class="fa fa-eye mr-1"></i> View Test
                            </a>
                            <a href="#" class="glassmorphism-btn btn-edit">
                                <i class="fa fa-edit mr-1"></i> Edit
                            </a>
                            <form action="delete_test.php" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this test? This action cannot be undone.');">
                                <input type="hidden" name="test_id" value="<?php echo $test['test_id']; ?>">
                                <button type="submit" class="glassmorphism-btn btn-delete">
                                    <i class="fa fa-trash-alt mr-1"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="text-gray-600 text-lg col-span-full">No tests available. Click "Create New Test" to get started!</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-lg relative">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Create New Test</h2>
            <button class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 close-modal-btn" onclick="closeModal()">&times;</button>
            <form action="generate_paper.php" method="POST" class="space-y-4">
                <div>
                    <label for="test_name" class="block font-medium text-gray-700 mb-1">Test Name</label>
                    <input type="text" id="test_name" name="test_name" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300" required>
                </div>
                <div>
                    <label for="subject" class="block font-medium text-gray-700 mb-1">Select Subject</label>
                    <select id="subject" name="subject_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300" required>
                        <?php if (!empty($subjects)) : ?>
                            <?php foreach ($subjects as $subject) : ?>
                                <option value="<?php echo $subject['subject_id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <option value="">No subjects available. Please add a subject first.</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label for="one_mark" class="block font-medium text-gray-700 mb-1">Number of 1-mark Questions</label>
                    <input type="number" id="one_mark" name="one_marker" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300" min="0" value="0" required>
                </div>
                <div>
                    <label for="two_mark" class="block font-medium text-gray-700 mb-1">Number of 2-mark Questions</label>
                    <input type="number" id="two_mark" name="two_marker" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300" min="0" value="0" required>
                </div>
                <div>
                    <label for="five_mark" class="block font-medium text-gray-700 mb-1">Number of 5-mark Questions</label>
                    <input type="number" id="five_mark" name="five_marker" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300" min="0" value="0" required>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="glassmorphism-btn btn-view">Generate Test</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }
    </script>
</body>

</html>
<?php
session_start();
// Ensure user is logged in and has the 'teacher' role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../auth/login/index.php");
    exit();
}


include('../../../database/db.php'); // Adjust path as per your actual file location
require '../../../tcpdf/tcpdf.php'; // Include TCPDF library

$teacher_id = $_SESSION['user_id'];
$test_id = null;
$test_name = '';
$subject_name = '';
$questions = [];
$questions_one = [];
$questions_two = [];
$questions_five = [];
$total_question_count = 0;

// --- Handle display of the test questions ---
if (isset($_GET['test_id']) && is_numeric($_GET['test_id'])) {
    $test_id = (int)$_GET['test_id'];

    // Fetch test details
    $stmt = $mysqli->prepare("
        SELECT t.test_name, s.subject_name
        FROM tests t
        JOIN subjects s ON t.subject_id = s.subject_id
        WHERE t.test_id = ? AND t.teacher_id = ?
    ");
    $stmt->bind_param("ii", $test_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $test_data = $result->fetch_assoc();
        $test_name = $test_data['test_name'];
        $subject_name = $test_data['subject_name'];

        // Fetch questions for this test, ordered by question_id to keep 1-markers, 2-markers, 5-markers grouped
        $questions_query = $mysqli->query("
            SELECT q.question_id, q.question_text, q.marks
            FROM test_questions tq
            JOIN questions q ON tq.question_id = q.question_id
            WHERE tq.test_id = $test_id
            ORDER BY q.marks ASC, q.question_id ASC
        ");

        if ($questions_query->num_rows > 0) {
            while ($question = $questions_query->fetch_assoc()) {
                $questions[] = $question;
                $total_question_count++;

                // Categorize questions for PDF generation
                if ($question['marks'] == 1) {
                    $questions_one[] = $question;
                } elseif ($question['marks'] == 2) {
                    $questions_two[] = $question;
                } elseif ($question['marks'] == 5) {
                    $questions_five[] = $question;
                }
            }
        }
    } else {
        $_SESSION['error'] = "Test not found or you don't have permission to view it.";
        header("Location: tests.php");
        exit();
    }
} elseif (isset($_POST['download_pdf'])) {
    // --- Handle PDF download initiated from the form on this page ---
    $test_id = (int)$_POST['test_id'];

    // Re-fetch necessary data from DB to ensure integrity and security
    // (Don't rely solely on hidden form fields for critical data)
    $stmt = $mysqli->prepare("
        SELECT t.test_name, s.subject_name, t.test_id
        FROM tests t
        JOIN subjects s ON t.subject_id = s.subject_id
        WHERE t.test_id = ? AND t.teacher_id = ?
    ");
    $stmt->bind_param("ii", $test_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $test_data = $result->fetch_assoc();
        $test_name = $test_data['test_name'];
        $subject_name = $test_data['subject_name'];

        // Fetch questions associated with this test for PDF generation
        $questions_pdf_query = $mysqli->query("
            SELECT q.question_id, q.question_text, q.marks
            FROM test_questions tq
            JOIN questions q ON tq.question_id = q.question_id
            WHERE tq.test_id = $test_id
            ORDER BY q.marks ASC, q.question_id ASC
        ");

        if ($questions_pdf_query->num_rows > 0) {
            while ($question = $questions_pdf_query->fetch_assoc()) {
                if ($question['marks'] == 1) {
                    $questions_one[] = $question;
                } elseif ($question['marks'] == 2) {
                    $questions_two[] = $question;
                } elseif ($question['marks'] == 5) {
                    $questions_five[] = $question;
                }
            }
            generatePDF($test_name, $subject_name, $questions_one, $questions_two, $questions_five);
            exit; // Stop script execution after PDF generation
        } else {
             $_SESSION['error'] = "No questions found for this test to download.";
             header("Location: view_test.php?test_id=" . $test_id); // Redirect back to view page
             exit();
        }
    } else {
        $_SESSION['error'] = "Test not found or unauthorized to download.";
        header("Location: tests.php");
        exit();
    }
} else {
    $_SESSION['error'] = "No test specified to view.";
    header("Location: tests.php");
    exit();
}

// --- PDF generation function (from your sample, slightly adjusted) ---
function generatePDF($test_name, $subject_name, $questions_one, $questions_two, $questions_five) {
    ob_clean(); // clear output buffer
flush();    // flush system output buffer
$pdf->Output($test_name . '.pdf', 'D'); // Download

    class CustomPDF extends TCPDF {
        // Page header
        public function Header() {
            if ($this->getPage() > 1) {
                $this->SetFont('helvetica', '', 12);
                $this->Cell(0, 10, 'LJ University', 0, 1, 'C');
                $this->Ln(5); // Add space after header
            } else {
                // Adjust path for logo if needed, or remove if not available
                // Example: $this->Image('../../../assets/img/logo.png', 90, 10, 30, '', '', '', '', false, 300, '', false, false, 0, false, false, false);
                $this->Ln(20); // Add space after logo even without an image
            }
        }

        // Page footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'LJ POLYTECHNIC           ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . '           ' . 'Page ' . $this->getPage(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            $this->Ln(2); // Add space after footer
            $this->Cell(0, 10, '___________________________________________________________________________________________', 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    $pdf = new CustomPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Question Paper Generator');
    $pdf->SetTitle('Generated Question Paper');

    $pdf->SetMargins(15, 20, 15);
    $pdf->SetAutoPageBreak(TRUE, 10);

    $pdf->AddPage();

    $current_date = date('d/m/Y');
    $marks1 = count($questions_one);
    $marks2 = count($questions_two) * 2;
    $marks3 = count($questions_five) * 5;
    $total_marks = $marks1 + $marks2 + $marks3;

    $html = '
    <table cellspacing="0" cellpadding="1" border="0" style="font-size: 11pt;">
        <tr>
            <td>Enrollment Number: _____</td>
            <td style="text-align:right;">Seat No. ______</td>
        </tr>
        <tr>
            <td>Subject Name: ' . htmlspecialchars($subject_name) . '</td>
            <td style="text-align:right;">Total Marks: ' . $total_marks . '</td>
        </tr>
        <tr>
            <td>Date: ' . $current_date . '</td>
            <td style="text-align:right;">Time: 10:00 AM to 12:00 PM</td>
        </tr>
        <tr>
            <td colspan="2" style="text-align:center; font-weight: bold; font-size: 14pt;">' . htmlspecialchars($test_name) . '</td>
        </tr>
    </table>
    <br><br>
    <p style="font-size: 10pt;"><b>Instruction:</b></p>
    <p style="font-size: 10pt;">1. Figures to the right indicate full marks</p>
    <p style="font-size: 10pt;">2. Attempt all questions.</p>
    <br>';

    if (!empty($questions_one)) {
        $html .= '
        <h3 style="font-size: 12pt; font-weight: bold;">Q.1 Answer the following <span style="float:right;">(Marks - ' . $marks1 . ')</span></h3>
        <hr style="border-top: 1px solid #000;"><br>';
        foreach ($questions_one as $index => $question) {
            $html .= '<p style="font-size: 10pt;">' . ($index + 1) . '. ' . htmlspecialchars($question['question_text']) . '</p>';
        }
        $html .= '<br>';
    }

    if (!empty($questions_two)) {
        $html .= '
        <h3 style="font-size: 12pt; font-weight: bold;">Q.2 Answer the following <span style="float:right;">(Marks - ' . $marks2 . ')</span></h3>
        <hr style="border-top: 1px solid #000;"><br>';
        foreach ($questions_two as $index => $question) {
            $html .= '<p style="font-size: 10pt;">' . ($index + 1) . '. ' . htmlspecialchars($question['question_text']) . '</p>';
        }
        $html .= '<br>';
    }

    if (!empty($questions_five)) {
        $html .= '
        <h3 style="font-size: 12pt; font-weight: bold;">Q.3 Answer the following <span style="float:right;">(Marks - ' . $marks3 . ')</span></h3>
        <hr style="border-top: 1px solid #000;"><br>';
        foreach ($questions_five as $index => $question) {
            $html .= '<p style="font-size: 10pt;">' . ($index + 1) . '. ' . htmlspecialchars($question['question_text']) . '</p>';
        }
        $html .= '<br>';
    }

    $pdf->writeHTML($html, true, false, true, false, '');

    // Close and output PDF document
    $pdf->Output($test_name . '.pdf', 'D'); // 'D' for download
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Test Paper - <?php echo htmlspecialchars($test_name); ?></title>
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

        /* AUX SIDEBAR - Not used in this layout */
        .aux {
            display: none;
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

        .test-paper-container {
            background: rgba(255, 255, 255, 0.6);
            border: 1.5px solid rgba(123, 97, 255, 0.2);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 25px rgba(123, 97, 255, 0.15);
            backdrop-filter: blur(8px);
            margin-top: 20px;
        }

        .question-group h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: 25px;
            margin-bottom: 10px;
            border-bottom: 2px solid rgba(123, 97, 255, 0.3);
            padding-bottom: 5px;
        }

        .question-item {
            margin-bottom: 15px;
            padding-left: 10px;
        }

        .question-item p {
            margin: 0;
            font-size: 1rem;
            line-height: 1.5;
        }

        .question-item span.marks {
            font-weight: 600;
            color: #666;
            font-size: 0.9em;
            margin-left: 10px;
        }

        .glassmorphism-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-download {
            color: #fff;
            background: linear-gradient(135deg, rgba(62, 185, 137, 0.7), rgba(46, 139, 87, 0.7));
        }

        .btn-download:hover {
            background: linear-gradient(135deg, rgba(62, 185, 137, 0.9), rgba(46, 139, 87, 0.9));
            box-shadow: 0 4px 15px rgba(46, 139, 87, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-back {
            color: #fff;
            background: linear-gradient(135deg, rgba(100, 100, 100, 0.7), rgba(80, 80, 80, 0.7));
        }

        .btn-back:hover {
            background: linear-gradient(135deg, rgba(100, 100, 100, 0.9), rgba(80, 80, 80, 0.9));
            box-shadow: 0 4px 15px rgba(80, 80, 80, 0.3);
            transform: translateY(-2px);
        }

        .message-box {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width:900px) {
            .main {
                padding: 9px 7px;
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
                padding-bottom: 60px;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="logo">Q</div>
        <ul>
            <li><a href="../dashboard.php" title="Home"><i class="fa fa-home"></i></a></li>
            <li><a href="subjects.php" title="Subjects"><i class="fa fa-book"></i></a></li>
            <li><a class="active" href="tests.php" title="Tests"><i class="fa fa-clipboard-list"></i></a></li>
            <li><a href="#" title="Settings"><i class="fa fa-cog"></i></a></li>
        </ul>
        <div class="logout"><a href="../../auth/logout.php" title="Logout"><i class="fa fa-sign-out-alt"></i></a></div>
    </div>

    <div class="main">
        <div class="header-glass">
            <div>
                <h1>Test Paper: <?php echo htmlspecialchars($test_name); ?></h1>
                <p>Subject: <?php echo htmlspecialchars($subject_name); ?></p>
            </div>
            <a href="tests.php" class="glassmorphism-btn btn-back">
                <i class="fa fa-arrow-left mr-2"></i>Back to Tests
            </a>
        </div>

        <?php if (isset($_SESSION['error'])) : ?>
            <div class="message-box error">
                <i class="fa fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($questions)) : ?>
            <div class="test-paper-container">
                <p class="text-center text-lg text-gray-600">No questions found for this test. It might be empty or an error occurred.</p>
                <p class="text-center text-md text-gray-500 mt-2">You can edit the test to add questions or create a new one.</p>
            </div>
        <?php else : ?>
            <div class="test-paper-container">
                <h2 class="text-xl font-bold text-center mb-6">Question Paper</h2>

                <?php if (!empty($questions_one)) : ?>
                    <div class="question-group">
                        <h3>Q.1 Answer the following (1-mark questions)</h3>
                        <ol class="list-decimal pl-5">
                            <?php foreach ($questions_one as $q) : ?>
                                <li class="question-item">
                                    <p><?php echo htmlspecialchars($q['question_text']); ?> <span class="marks">(1 Mark)</span></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endif; ?>

                <?php if (!empty($questions_two)) : ?>
                    <div class="question-group">
                        <h3>Q.2 Answer the following (2-mark questions)</h3>
                        <ol class="list-decimal pl-5">
                            <?php foreach ($questions_two as $q) : ?>
                                <li class="question-item">
                                    <p><?php echo htmlspecialchars($q['question_text']); ?> <span class="marks">(2 Marks)</span></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endif; ?>

                <?php if (!empty($questions_five)) : ?>
                    <div class="question-group">
                        <h3>Q.3 Answer the following (5-mark questions)</h3>
                        <ol class="list-decimal pl-5">
                            <?php foreach ($questions_five as $q) : ?>
                                <li class="question-item">
                                    <p><?php echo htmlspecialchars($q['question_text']); ?> <span class="marks">(5 Marks)</span></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endif; ?>

                <div class="mt-8 text-center">
                    <form method="POST" class="inline" action="">
                        <input type="hidden" name="test_id" value="<?= (int)$test_id ?>">

                        <input type="hidden" name="download_pdf" value="1">
                        <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($test_id); ?>">
                        <button type="submit" class="glassmorphism-btn btn-download">
                            <i class="fa fa-download mr-2"></i>Download PDF
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
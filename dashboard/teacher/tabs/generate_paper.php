<?php
session_start();
require '../../../database/db.php';
require '../../../tcpdf/tcpdf.php';

if (!isset($_SESSION['user_id'])) die("Unauthorized Access");

$teacher_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $test_name = $_POST['test_name'];
    $one_marker_count = (int) $_POST['one_marker'];
    $two_marker_count = (int) $_POST['two_marker'];
    $five_marker_count = (int) $_POST['five_marker'];

    $question_count = $one_marker_count + $two_marker_count + $five_marker_count;

    // Get subject name
    $stmt = $mysqli->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $subject_name = $stmt->get_result()->fetch_assoc()['subject_name'];
    $stmt->close();

    // Insert test
    $stmt = $mysqli->prepare("INSERT INTO tests (test_name, teacher_id, subject_id, created_at, question_count) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param("siii", $test_name, $teacher_id, $subject_id, $question_count);
    $stmt->execute();
    $test_id = $stmt->insert_id;
    $stmt->close();

    // Helper function to fetch random questions
    function getRandomQuestions($subject_id, $marks, $limit, $mysqli) {
        $stmt = $mysqli->prepare("
            SELECT q.* FROM questions q
            INNER JOIN chapters c ON q.chapter_id = c.chapter_id
            WHERE c.subject_id = ? AND q.marks = ?
            ORDER BY RAND() LIMIT ?
        ");
        $stmt->bind_param("iii", $subject_id, $marks, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $questions_one = getRandomQuestions($subject_id, 1, $one_marker_count, $mysqli);
    $questions_two = getRandomQuestions($subject_id, 2, $two_marker_count, $mysqli);
    $questions_five = getRandomQuestions($subject_id, 5, $five_marker_count, $mysqli);

    $all_questions = array_merge($questions_one, $questions_two, $questions_five);

    foreach ($all_questions as $q) {
        $stmt = $mysqli->prepare("INSERT INTO test_questions (test_id, question_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $test_id, $q['question_id']);
        $stmt->execute();
    }

    // Download PDF if requested
    if (isset($_POST['download_pdf'])) {
        generatePDF($test_name, $subject_name, $questions_one, $questions_two, $questions_five);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Generated Paper</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-100 to-indigo-100 min-h-screen p-8">

<div class="max-w-4xl mx-auto bg-white/60 backdrop-blur-xl shadow-lg rounded-xl p-6 border border-purple-200">
  <h1 class="text-3xl font-bold text-purple-800 text-center mb-4">Question Paper Preview</h1>
  <p class="text-center text-gray-700 mb-6 text-lg">Test: <span class="font-semibold italic"><?= htmlspecialchars($test_name) ?></span></p>

  <table class="w-full text-left table-auto border-collapse border border-gray-300 rounded-md overflow-hidden shadow">
    <thead class="bg-purple-200 text-purple-800">
      <tr>
        <th class="px-4 py-2 border">#</th>
        <th class="px-4 py-2 border">Question</th>
        <th class="px-4 py-2 border">Marks</th>
      </tr>
    </thead>
    <tbody class="text-gray-800">
      <?php foreach ($all_questions as $index => $q): ?>
        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-purple-50' ?>">
          <td class="border px-3 py-2"><?= $index + 1 ?></td>
          <td class="border px-3 py-2"><?= htmlspecialchars($q['question_text']) ?></td>
          <td class="border px-3 py-2"><?= $q['marks'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <form method="POST" class="text-center mt-6 space-x-3">
    <input type="hidden" name="download_pdf" value="1">
    <input type="hidden" name="subject_id" value="<?= htmlspecialchars($subject_id) ?>">
    <input type="hidden" name="subject_name" value="<?= htmlspecialchars($subject_name) ?>">
    <input type="hidden" name="test_name" value="<?= htmlspecialchars($test_name) ?>">
    <input type="hidden" name="one_marker" value="<?= $one_marker_count ?>">
    <input type="hidden" name="two_marker" value="<?= $two_marker_count ?>">
    <input type="hidden" name="five_marker" value="<?= $five_marker_count ?>">
    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-full shadow hover:bg-purple-700">
      Download PDF
    </button>
  </form>
</div>
</body>
</html>
<?php
// PDF generation function
function generatePDF($test_name, $subject_name, $questions_one, $questions_two, $questions_five) {
    // Load TCPDF library
    class CustomPDF extends TCPDF {
        // Page header
        public function Header() {
            if ($this->getPage() > 1) {
                $this->SetFont('helvetica', '', 12);
                $this->Cell(0, 10, 'LJ University', 0, 1, 'C');
                $this->Ln(5);
            } else {
                // Add logo here if needed
                $this->Image('', 90, 10, 30);
                $this->Ln(20);
            }
        }

        // Page footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $page = 'LJ POLYTECHNIC     ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . '     Page ' . $this->getPage();
            $this->Cell(0, 10, $page, 0, false, 'C');
            $this->Ln(2);
            $this->Cell(0, 10, str_repeat('_', 100), 0, false, 'C');
        }
    }

    $pdf = new CustomPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Question Paper Generator');
    $pdf->SetTitle('Generated Question Paper');

    $pdf->SetMargins(15, 20, 15);
    $pdf->SetAutoPageBreak(true, 20); // Extra space for footer
    $pdf->AddPage();

    $date = date('d/m/Y');
    $marks1 = count($questions_one);
    $marks2 = count($questions_two) * 2;
    $marks3 = count($questions_five) * 5;
    $total_marks = $marks1 + $marks2 + $marks3;

    // Build HTML content
    $html = '
    <style>
        h3 { font-weight: bold; }
        p { margin: 4px 0; }
    </style>

    <table cellspacing="0" cellpadding="1" border="0">
        <tr>
            <td>Enrollment Number: ____________________</td>
            <td align="right">Seat No: ____________________</td>
        </tr>
        <tr>
            <td>Subject Name: <strong>' . htmlspecialchars($subject_name) . '</strong></td>
            <td align="right">Total Marks: <strong>' . $total_marks . '</strong></td>
        </tr>
        <tr>
            <td>Date: ' . $date . '</td>
            <td align="right">Time: 10:00 AM to 12:00 PM</td>
        </tr>
        <tr>
            <td colspan="2" align="center"><strong>' . htmlspecialchars($test_name) . '</strong></td>
        </tr>
    </table>

    <br><p><strong>Instructions:</strong></p>
    <ul>
        <li>Figures to the right indicate full marks.</li>
        <li>Attempt all questions.</li>
    </ul>
    ';

    if (!empty($questions_one)) {
        $html .= '<h3>Q.1 Answer the following <span style="float:right;">(Marks - ' . $marks1 . ')</span></h3><hr>';
        foreach ($questions_one as $i => $q) {
            $html .= '<p>' . ($i + 1) . '. ' . htmlspecialchars($q['question_text']) . '</p>';
        }
    }

    if (!empty($questions_two)) {
        $html .= '<br><h3>Q.2 Answer the following <span style="float:right;">(Marks - ' . $marks2 . ')</span></h3><hr>';
        foreach ($questions_two as $i => $q) {
            $html .= '<p>' . ($i + 1) . '. ' . htmlspecialchars($q['question_text']) . '</p>';
        }
    }

    if (!empty($questions_five)) {
        $html .= '<br><h3>Q.3 Answer the following <span style="float:right;">(Marks - ' . $marks3 . ')</span></h3><hr>';
        foreach ($questions_five as $i => $q) {
            $html .= '<p>' . ($i + 1) . '. ' . htmlspecialchars($q['question_text']) . '</p>';
        }
    }

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($test_name . '.pdf', 'D');
}
?>


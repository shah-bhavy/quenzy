<?php
session_start();
require '../../../../database/db.php'; // Include the DB connection

// Ensure user is logged in and is a teacher
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../../auth/login/index.php"); // Corrected redirect path if needed
    exit();
}

$user_id = $_SESSION['user_id'];
$stmtU = $mysqli->prepare("SELECT name FROM users WHERE id=?");
$stmtU->bind_param("i", $user_id);
$stmtU->execute();
$stmtU->bind_result($userName);
$stmtU->fetch();
$stmtU->close();
$profileLetter = strtoupper($userName[0]);

$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;

if ($chapter_id === 0) {
    // Redirect or show an error if chapter_id is not provided
    $_SESSION['error'] = "Chapter ID not provided.";
    header("Location: ../subjects.php"); // Redirect back to subjects or a relevant page
    exit();
}

// Fetch chapter details
$chapter_name = "Unknown Chapter";
$subject_id = null;
$stmtC = $mysqli->prepare("SELECT chapter_name, subject_id FROM chapters WHERE chapter_id = ?");
$stmtC->bind_param("i", $chapter_id);
$stmtC->execute();
$stmtC->bind_result($chapter_name, $subject_id);
$stmtC->fetch();
$stmtC->close();

// Fetch subject name for breadcrumb/navigation
$subject_name = "Unknown Subject";
if ($subject_id) {
    $stmtS = $mysqli->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
    $stmtS->bind_param("i", $subject_id);
    $stmtS->execute();
    $stmtS->bind_result($subject_name);
    $stmtS->fetch();
    $stmtS->close();
}

// --- Define Marker Colors ---
// Map specific marker values to distinct colors
$markerColors = [
    1.0 => "#4CAF50", // Green for 1-marker
    2.0 => "#2196F3", // Blue for 2-marker
    3.0 => "#FFC107", // Amber for 3-marker
    4.0 => "#FF5722", // Deep Orange for 4-marker
    5.0 => "#9C27B0", // Purple for 5-marker
    // Add more as needed. Ensure keys match your 'marks' column data type (float/decimal or int)
    'default' => "#607D8B" // Grey for any undefined or unexpected marks
];

// Fetch questions for the given chapter_id including 'marks'
$questions = [];
// --- MODIFIED QUERY to include 'marks' column ---
$questions_stmt = $mysqli->prepare("SELECT question_id, question_text, created_at, marks FROM questions WHERE chapter_id = ? ORDER BY created_at DESC");
$questions_stmt->bind_param("i", $chapter_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();
$questions = $questions_result->fetch_all(MYSQLI_ASSOC);
$questions_stmt->close();

// Notifications data (copied from chapters.php for UI consistency)
$notifications = [
    ["type"=>"reminder","text"=>"Don't forget to review these questions!","time"=>"Today"],
    ["type"=>"newstudent","text"=>"New student joined class","time"=>"1d ago"],
    ["type"=>"report","text"=>"Check question analytics for $chapter_name","time"=>"2h ago"],
];
$currentDate = date("F j, Y");
$currentDay = date("l");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($chapter_name); ?> - Questions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>:root{
    --primary:#7b61ff;
    --glass:rgba(255,255,255,0.44);
    --sidebar-bg:#fff;
    --active-bg:rgba(123,97,255,.18);
    --active-border:#7b61ff;
    --sidebar-blur:22px;
    --main-bg:linear-gradient(120deg,#e7e0fd 0%,#f4f2ff 100%);
    --font-size:15px;}
    body{font-family:'Inter',Arial,sans-serif;font-size:var(--font-size);margin:0;display:flex;min-height:100vh;background:var(--main-bg);color:#222;}
    .sidebar{width:64px;background:var(--sidebar-bg);box-shadow:2px 0 14px rgba(50,48,156,.06);display:flex;flex-direction:column;align-items:center;position:sticky;left:0;top:0;z-index:21;min-height:100vh;backdrop-filter:blur(var(--sidebar-blur));user-select:none;}
    .sidebar ul{padding:0;margin:0;list-style:none;display:flex;flex-direction:column;gap:0;flex:1;width:100%;align-items:center;}
    .sidebar li{width:100%;}
    .sidebar a{display:flex;align-items:center;justify-content:center;width:100%;height:44px;text-decoration:none;color:#555;font-size:20px;border-left:4px solid transparent;border-radius:10px 0 0 10px;transition:background 0.22s;margin:0;}
    .sidebar a.active,.sidebar a:focus{background:var(--active-bg);border-left:4px solid var(--active-border);color:var(--primary);}
    .sidebar .logo{font-weight:800;font-size:27px;margin:18px 0 15px 0;color:var(--primary);background:var(--glass);border-radius:12px;padding:8px 0;width:100%;text-align:center;letter-spacing:-1px;}
    .sidebar .logout{margin-bottom:18px;width:100%;}
    .sidebar .logout a{color:#fa5757;background:transparent;font-size:19px;}
    .aux{width:260px;min-width:215px;padding:20px 15px 11px 15px;gap:20px;background:var(--glass);backdrop-filter:blur(24px);display:flex;flex-direction:column;border-right:1.5px solid #eceafc77;}
    .aux .profile{display:flex;align-items:center;gap:13px;margin-bottom:6px;}
    .profilepic{width:34px;height:34px;background:var(--primary);border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;box-shadow:0 2px 9px #b0a3ff13;}
    .profilename{font-size:14px;font-weight:600;letter-spacing:-.5px;}
    .aux .date{background:var(--glass);border-radius:8px;padding:7px 0;text-align:center;}
    .aux .date h4{margin:0;color:var(--primary);font-size:13.2px;font-weight:600;}
    .aux .date span{display:block;font-size:17px;font-weight:600;color:#232029;margin:auto;}
    .notifications{background:var(--glass);border-radius:11px;box-shadow:0 1px 9px #d8ceff20;padding:11px 10px 7px 14px;max-height:110px;overflow:auto;display:flex;flex-direction:column;}
    .notifications::-webkit-scrollbar{width:4px;background:transparent;}
    .notifications h3{margin:0 0 7px 0;font-size:13.3px;font-weight:700;letter-spacing:-.5px;}
    .notilist{padding:0;margin:0;list-style:none;}
    .notilist li{display:flex;align-items:center;gap:10px;margin-bottom:5.5px;padding:6px 7px;border-radius:7px;font-size:11.7px;font-weight:500;background:rgba(255,255,255,0.18);}
    .iconNf{border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:12.5px;font-weight:600}
    .nf-report{background:#ddd7ff;color:#7B61FF;}
    .nf-reminder{background:#e7edff;color:#39399d;}
    .nf-newstudent{background:#d4fbeb;color:#0f9f6e;}
    .nf-txt{flex:1;}.nf-time{font-size:10.3px;color:#8a89a3;padding-left:6px;}
    .view-all-btn{background:var(--primary);color:#fff;font-size:10.8px;border:none;margin:4px auto 1.5px auto;border-radius:7px;padding:2.4px 11px;cursor:pointer;font-weight:600;box-shadow:0 0 7px #dccfff05;display:block;outline:none;}
    .main{flex:1;min-width:0;padding:33px 6% 18px 6%;}
    .header-glass{border-radius:19px;background:var(--glass);box-shadow:0 3px 23px #7b61ff22;padding:19px 21px;margin-bottom:27px;display:flex;align-items:center;justify-content:space-between;}

    /* Adjusted header-glass to allow for flexible positioning of back button and title */
    .header-content {
        display: flex;
        align-items: center;
        gap: 15px; /* Space between back button and title */
    }

    /* Consistent button styles for icons only */
    .cbtn {
        background:#f7f4ff;color:var(--primary);border:none;border-radius:7px;
        width:32px; /* Fixed width for icon buttons */
        height:32px; /* Fixed height for icon buttons */
        display:flex;align-items:center;justify-content:center; /* Center the icon */
        cursor:pointer;font-weight:600;font-size:14px; /* Icon size */
        transition:.13s;
        padding:0; /* Remove padding as width/height are fixed */
    }
    .cbtn.edit{background:#7bcfff;color:#12407f;}
    .cbtn.del{background:#ffe3eb;color:#b22031;}
    .cbtn.upload{background:#fab1b8;color:#722242;}
    .cbtn.questions{background:#a996fd;color:#fff;}
    .cbtn.view-file{background:#e0f7fa;color:#17a2b8;}

    .back-btn-icon {
        background:var(--primary);
        color:#fff;
        border:none;
        border-radius:7px;
        width:32px;
        height:32px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:14px;
        transition:.13s;
        cursor:pointer;
        text-decoration: none;
    }
    .back-btn-icon:hover {
        background:#593eda;
    }

    .add-fab{
        background:#7b61ff;color:#fff;box-shadow:0 4px 30px #7b61ff22;
        border-radius:50%;width:54px;height:54px;display:flex;align-items:center;justify-content:center;
        font-size:28px;position:fixed;top:23px;right:6vw;z-index:22;transition:.13s;cursor:pointer;
    }
    .add-fab:hover{background:#593eda;}

    /* Modal styles (copied from chapters.php) */
    .modal{display:none;position:fixed;backdrop-filter:blur(10px);z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.32);justify-content:center;align-items:center;}
    .modal-content{background:var(--glass);box-shadow:0 9px 35px #7b61ff23;padding:23px 33px 20px 31px;border-radius:15px;width:96vw;max-width:360px;min-width:220px;}
    .modal-header{display:flex;justify-content:space-between;align-items:center;}
    .modal-title{font-size:17px;font-weight:700;color:var(--primary);}
    .modal-close{background:transparent;border:none;font-size:1.7em;color:#c45c90;font-weight:800;line-height:1;cursor:pointer;padding:0 3px;}
    .modal-body label{display:block;font-size:13.7px;margin-bottom:6px;text-align:left;}
    .modal-body input,
    .modal-body textarea { /* Added textarea for question text */
        width:96%;border:1px solid #ddd;padding:10px 9px;border-radius:6px;font-size:14.5px;margin-bottom:10px;}
    .modal-footer{display:flex;justify-content:flex-end;gap:7px;}
    .close-btn{background:#fff0f3;color:#d40020;padding:5px 12px;cursor:pointer;border:none;border-radius:6px;font-weight:600;}
    .modal button[type=submit]{background:var(--primary);color:#fff;}
    progress{width:95%;margin:10px auto 1px auto;height:8px;border-radius:5px;}
    #statusText{font-size:13px;margin:6px auto;}

    /* Message banners (copied from chapters.php) */
    .message{padding:11px 0 10px 0;background:rgba(123,97,255,.16);color:#43249a;text-align:center;margin-bottom:12px;font-size:15px;border-radius:7px;}
    .error{background:rgba(240,67,86,.22);color:#b22031;}

    /* Questions Specific Styling */
    .questions-section { margin:18px 0 0 0;}
    .question-cards-wrap {
        display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:22px 18px; margin:0;
    }
    .question-card {
        background:var(--glass);position:relative;
        border-radius:15px;box-shadow:0 5px 19px #a7a3f825;
        padding:22px 18px 15px 19px;display:flex;flex-direction:column;
        border-left:7px solid #efeaff;transition:box-shadow .17s,transform .17s;
        padding-top: 40px; /* Add space for the marker badge at the top */
    }
    .question-card:hover{box-shadow:0 8px 24px #7b61ff28;transform:translateY(-2.5px) scale(1.006);}
    .question-card .question-text{
        font-size:15.5px;font-weight:500;color:#333;margin-bottom:15px;line-height:1.4;
        text-shadow:0 1px 4px #fff9;
    }
    .question-card .card-actions{
        display:flex; gap:8px;flex-wrap:wrap;margin-top:auto; /* Push actions to bottom */
    }
    .question-card .created-at {
        font-size:11px;
        color:#888;
        text-align:right;
        margin-top:10px;
        font-style: italic;
    }

    /* New CSS for the marker badge */
    .question-card .marker-badge {
        position: absolute;
        top: 0px; /* Position it at the very top of the padding */
        right: 15px; /* Aligned with right padding */
        padding: 4px 10px;
        border-bottom-left-radius: 8px; /* Rounded corner only on bottom left */
        border-bottom-right-radius: 8px; /* Rounded corner only on bottom right */
        font-size: 12px; /* Slightly larger font for readability */
        font-weight: 700;
        color: #fff; /* White text for contrast */
        z-index: 10;
        /* Background color will be set inline by PHP */
        box-shadow: 0 2px 5px rgba(0,0,0,0.15); /* Subtle shadow for depth */
    }

    @media(max-width:900px){.main{padding:10px 3vw;}.aux{display:none;}}
    @media(max-width:600px){.sidebar{width:100%;flex-direction:row;height:52px;min-height:0;position:fixed;bottom:0;top:auto;background:#fff9;}.sidebar ul{flex-direction:row;}.sidebar .logo,.sidebar .logout{display:none;}.main{padding-top:25px;}}
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo">Q</div>
    <ul>
        <li><a href="../../dashboard.php" title="Home"><i class="fa fa-home"></i></a></li>
        <li><a class="active" href="../subjects.php" title="Subjects"><i class="fa fa-book"></i></a></li>
        <li><a href="../../tests/tests.php" title="Tests"><i class="fa fa-clipboard-list"></i></a></li>
    </ul>
    <div class="logout"><a href="../../auth/logout.php" title="Logout"><i class="fa fa-sign-out-alt"></i></a></div>
</div>
<div class="add-fab" onclick="openQuestionChoiceModal()" title="Add Question"><i class="fa fa-plus"></i></div>
<div class="main">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="header-glass">
        <div class="header-content">
            <a href="view_subject.php?subject_id=<?= $subject_id; ?>" class="back-btn-icon" title="Back to Chapters"><i class="fa fa-arrow-left"></i></a>
            <h1>Questions for <?= htmlspecialchars($chapter_name); ?></h1>
        </div>
    </div>
    <div class="questions-section">
        <div class="question-cards-wrap">
            <?php if($questions): foreach($questions as $idx=>$q):
                // Get the color based on 'marks'
                $qMarks = (float)$q['marks']; // Cast to float for accurate key lookup
                $col = isset($markerColors[$qMarks]) ? $markerColors[$qMarks] : $markerColors['default'];
            ?>
                <div class="question-card" style="border-left-color: <?= $col ?>;">
                    <div class="marker-badge" style="background-color: <?= $col ?>;"><?= htmlspecialchars($qMarks) ?> Marks</div>

                    <p class="question-text"><?= nl2br(htmlspecialchars($q['question_text'])) ?></p>
                    <div class="card-actions">
                        <button class="cbtn edit" onclick="openEditQuestionModal('<?= $q['question_id']; ?>', '<?= htmlspecialchars(addslashes($q['question_text'])) ?>', '<?= htmlspecialchars($qMarks) ?>')" title="Edit Question"><i class="fa fa-edit"></i></button>
                        <a href="delete_question.php?question_id=<?= $q['question_id']; ?>&chapter_id=<?= $chapter_id; ?>" class="cbtn del" onclick="return confirm('Are you sure you want to delete this question?')" title="Delete Question"><i class="fa fa-trash"></i></a>
                    </div>
                    <div class="created-at">Created: <?= date('M d, Y H:i', strtotime($q['created_at'])); ?></div>
                </div>
            <?php endforeach; else: ?>
            <div style="opacity:.88;text-align:center;font-size:15px;color:#b5abe5;margin:20px auto 0 auto">
                <i class="fa fa-inbox"></i> No questions found for this chapter.<br>
                Upload a document or add a question to get started.
            </div>
            <?php endif ?>
        </div>
    </div>

<!-- Floating Add FAB -->
<div class="add-fab" onclick="openQuestionChoiceModal()" title="Add Question"><i class="fa fa-plus"></i></div>
<!-- QUESTION CHOICE MODAL -->
<div id="questionChoiceModal" class="modal"><div class="modal-content">
    <div class="modal-header">
        <span class="modal-title"><i class="fa fa-brain"></i> How would you like to add questions?</span>
        <button class="modal-close" onclick="closeQuestionChoiceModal()" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
        <button class="cbtn questions" style="width:100%;background:#ef85a3;margin-bottom:14px;"
            onclick="aiGenerateQuestions(true)">
            <i class="fa fa-broom"></i> Delete current &amp; Generate AI Questions
        </button>
        <button class="cbtn questions" style="width:100%;background:#7bcfff;margin-bottom:14px;"
            onclick="aiGenerateQuestions(false)">
            <i class="fa fa-robot"></i> Generate AI Questions (Add)
        </button>
        <button class="cbtn questions" style="width:100%;background:#a996fd;"
            onclick="openManualQuestionModal()">
            <i class="fa fa-keyboard"></i> Type Question Manually
        </button>
    </div>
</div></div>

<!-- Manual Add Question Modal (same as before, just rename open/close functions) -->
<div id="addQuestionModal" class="modal"><div class="modal-content">
    <div class="modal-header">
        <span class="modal-title"><i class="fa fa-plus-circle"></i> Add Question</span>
        <button class="modal-close" onclick="closeManualQuestionModal()" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
        <form method="POST" action="add_question.php">
            <input type="hidden" name="chapter_id" value="<?= $chapter_id ?>">
            <label for="add_question_text">Question Text</label>
            <textarea id="add_question_text" name="question_text" rows="6" placeholder="Enter question text" required></textarea>
            <label for="add_question_marks">Marks</label>
            <input type="number" step="0.5" min="0.5" id="add_question_marks" name="marks" value="1.0" required>
            <div class="modal-footer"><button type="submit" class="cbtn questions">Add Question</button></div>
        </form>
    </div>
</div></div>

<!-- Loading Modal for AI generation -->
<div id="aiLoadingModal" class="modal"><div class="modal-content">
    <div style="text-align:center;padding:25px 0 13px 0;">
        <div style="font-size:2em;color:#7b61ff;animation:spin 1s infinite linear;display:inline-block;">
            <i class="fa fa-robot"></i>
        </div>
        <div id="aiLoadingText" style="font-size:16px;margin-top:14px;">Generating questions with AI, please wait...</div>
    </div>
</div></div>
</div>

    <div id="editQuestionModal" class="modal"><div class="modal-content">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-edit"></i> Edit Question</span>
            <button class="modal-close" onclick="closeEditQuestionModal()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="edit_question.php">
                <input type="hidden" id="edit_question_id" name="question_id">
                <input type="hidden" name="chapter_id" value="<?= $chapter_id; ?>">
                <label for="edit_question_text">Question Text</label>
                <textarea id="edit_question_text" name="question_text" rows="6" required></textarea>
                <label for="edit_question_marks">Marks</label>
                <input type="number" step="0.5" min="0.5" id="edit_question_marks" name="marks" required>
                <div class="modal-footer"><button type="submit" name="edit_question" class="cbtn questions">Save Changes</button></div>
            </form>
        </div>
    </div></div>

</div>
<style>
@keyframes spin {100%{transform:rotate(360deg);}}
</style>
<script>
function openQuestionChoiceModal() {
    document.getElementById('questionChoiceModal').style.display = 'flex';
}
function closeQuestionChoiceModal() {
    document.getElementById('questionChoiceModal').style.display = 'none';
}
function openManualQuestionModal() {
    closeQuestionChoiceModal();
    setTimeout(() => {
        document.getElementById('addQuestionModal').style.display = 'flex';
        document.getElementById('add_question_text').focus();
    },180);
}
function closeManualQuestionModal() {
    document.getElementById('addQuestionModal').style.display = 'none';
}
function openLoadingModal(msg) {
    document.getElementById('aiLoadingModal').style.display = 'flex';
    document.getElementById('aiLoadingText').innerText = msg || "Generating questions with AI, please wait...";
}
function closeLoadingModal() {
    document.getElementById('aiLoadingModal').style.display = 'none';
}

// Option 1/2: AI Question Generation
function aiGenerateQuestions(delBefore) {
    closeQuestionChoiceModal();
    openLoadingModal(delBefore
        ? "Deleting questions then generating 25 one markers, 10 two markers and 10 five marker questions using AI..."
        : "Generating 25 one markers, 10 two markers and 10 five marker questions using AI..."
    );
    // Call PHP endpoint, which triggers quegen2.py; then reload
    var xhr = new XMLHttpRequest();
    xhr.open('POST','genque.php',true); // This is where you specify the target PHP script
    xhr.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xhr.onload = function() {
        closeLoadingModal();
        if (xhr.status===200) {
            try{
                var res=JSON.parse(xhr.responseText);
                if (res.success) {location.reload();}
                else alert(res.message || "AI generation failed.");
            }catch(e){
                alert("AI generation error!");
            }
        } else {
            closeLoadingModal();
            alert("AI service failed!");
        }
    };
    // THIS IS THE LINE THAT SENDS THE chapter_id
    xhr.send('chapter_id=<?= $chapter_id ?>&delete='+ (delBefore?1:0));
}
window.onclick = function(event) {
    ['addQuestionModal', 'editQuestionModal', 'questionChoiceModal',"aiLoadingModal"].forEach(function(id){
        var modal = document.getElementById(id);
        if (modal && event.target === modal) modal.style.display = 'none';
    });
};
</script>
<script>
// Modals logic for questions
function openAddQuestionModal(){
    document.getElementById('addQuestionModal').style.display='flex';
    setTimeout(()=>{
        document.getElementById('add_question_text').focus();
        document.getElementById('add_question_marks').value = 1.0; // Default value for new questions
    },180);
}
function closeAddQuestionModal(){document.getElementById('addQuestionModal').style.display='none';}

// --- MODIFIED openEditQuestionModal to pass marks ---
function openEditQuestionModal(questionId, questionText, marks){
    document.getElementById('editQuestionModal').style.display='flex';
    document.getElementById('edit_question_id').value=questionId;
    document.getElementById('edit_question_text').value=questionText;
    document.getElementById('edit_question_marks').value=marks; // Set marks value
    setTimeout(()=>{document.getElementById('edit_question_text').focus()},180);
}
function closeEditQuestionModal(){document.getElementById('editQuestionModal').style.display='none';}

window.onclick = function(event) {
    ['addQuestionModal', 'editQuestionModal'].forEach(function(id){
        var modal = document.getElementById(id);
        if (event.target === modal) modal.style.display = 'none';
    });
}
</script>
</body>
</html>
<?php
require_once '../../../database/db.php';
session_start();
if (!isset($_SESSION['user_id'])) header("Location: ../../auth/login/index.php") && exit;

$user_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT name FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute(); $stmt->bind_result($userName); $stmt->fetch(); $stmt->close();
$profileLetter = strtoupper($userName[0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    if ($subject_name !== "") {
        $stmt = $mysqli->prepare("INSERT INTO subjects (subject_name, teacher_id) VALUES (?, ?)");
        $stmt->bind_param("si", $subject_name, $user_id);
        $success = $stmt->execute();
        $stmt->close();
        if ($success) $success_msg = "Subject added!";
        else $error_msg = "Failed to add subject.";
    } else $error_msg = "Subject name is required.";
}
$stmt = $mysqli->prepare("SELECT s.subject_id, s.subject_name, 
    (SELECT COUNT(*) FROM chapters c WHERE c.subject_id = s.subject_id) AS chapter_count 
    FROM subjects s WHERE s.teacher_id = ?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$result = $stmt->get_result(); $subjects = $result->fetch_all(MYSQLI_ASSOC); $stmt->close();

$notifications = [
    ["type"=>"report","text"=>"Update report for Subject X!","time"=>"1h ago"],
    ["type"=>"reminder","text"=>"Check chapters in Physics","time"=>"Today"],
    ["type"=>"newstudent","text"=>"Student joined your course","time"=>"2d ago"],
];
$currentDate = date("F j, Y");
$currentDay = date("l");
$subjectColors = ["#a996fd","#7bcfff","#feaf50","#fab1b8","#7bffc6","#ffc878","#f3a0fff0","#9ce0ff", "#ffd7b5"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Subjects</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>:root{
--primary:#7b61ff;
--glass:rgba(255,255,255,0.44);
--active-bg:rgba(123,97,255,.18);
--active-border:#7b61ff;
--sidebar-bg:#fff;
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
.header-glass h1{font-size:1.23rem;margin:0 0 4px 0;}
.header-glass p{margin:0;font-size:12.6px;color:#555;}
/* --- SUBJECT CARDS --- */
.subjects-section { margin:18px 0 0 0;}
.subject-cards-wrap {
    display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:29px 22px; margin:0;
}
.subject-card {
    background:var(--glass);position:relative;
    border-radius:17px;box-shadow:0 7px 24px #a7a3f825;
    min-height:116px;padding:26px 20px 19px 21px;display:flex;flex-direction:column;align-items:flex-start;overflow:hidden;
    border-left:7px solid #efeaff;transition:box-shadow .17s,transform .17s;
}
.subject-card:hover{box-shadow:0 8px 34px #7b61ff28;transform:translateY(-2.5px) scale(1.016);}
.subject-card .subj-badge{
    width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:20.5px;border-radius:12px;margin-bottom:13px;color:#fff;font-weight:bold;box-shadow:0 2px 9px #e8e1f522;
}
.subject-card .subject-title{font-size:19px;font-weight:700;color:#4b2767;margin-bottom:8px;text-shadow:0 1px 4px #fff9;}
.subject-card .chapters-info{font-size:14px;color:#9789bd;margin-bottom:12px;}
.subject-card .card-actions{display:flex; gap:10px;}
.cbtn{background:#f7f4ff;color:var(--primary);border:none;border-radius:7px;padding:6px 13px;cursor:pointer;font-weight:600;font-size:13px;transition:.13s;}
.cbtn.view{background:#7b61ff;color:#fff;}
.cbtn.edit{background:#7bcfff;color:#12407f;}
.cbtn.del{background:#ffe3eb;color:#b22031;}
/* Floating Add Button */
.add-fab{
    background:#7b61ff;color:#fff;box-shadow:0 4px 30px #7b61ff22;
    border-radius:50%;width:54px;height:54px;display:flex;align-items:center;justify-content:center;
    font-size:28px;position:fixed;top:29px;right:6vw;z-index:22;transition:.13s;cursor:pointer;
}
.add-fab:hover{background:#593eda;}
/* Modal */
.modal{display:none;position:fixed;backdrop-filter:blur(10px);z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.32);justify-content:center;align-items:center;}
.modal-content{background:var(--glass);box-shadow:0 9px 35px #7b61ff23;padding:23px 33px 20px 31px;border-radius:15px;width:96vw;max-width:360px;min-width:220px;}
.modal-header{display:flex;justify-content:space-between;align-items:center;}
.modal-title{font-size:18px;font-weight:700;color:var(--primary);}
.modal-close{background:transparent;border:none;font-size:1.9em;color:#c45c90;font-weight:800;line-height:1;cursor:pointer;padding:0 3px;}
.modal-body label{display:block;font-size:14px;margin-bottom:6px;text-align:left;}
.modal-body input{width:96%;border:1px solid #ddd;padding:10px 9px;border-radius:6px;font-size:15px;margin-bottom:10px;}
.modal-footer{display:flex;justify-content:flex-end;gap:7px;}
.close-btn{background:#fff0f3;color:#d40020;padding:5px 12px;cursor:pointer;border:none;border-radius:6px;font-weight:600;}
.modal button[type=submit]{background:var(--primary);color:#fff;}
/* Message banners */
.message{padding:11px 0 10px 0;background:rgba(123,97,255,.16);color:#43249a;text-align:center;margin-bottom:12px;font-size:15px;border-radius:7px;}
.error{background:rgba(240,67,86,.22);color:#b22031;}
@media(max-width:900px){.main{padding:10px 3vw;}.aux{display:none;}}
@media(max-width:600px){.sidebar{width:100%;flex-direction:row;height:52px;min-height:0;position:fixed;bottom:0;top:auto;background:#fff9;}.sidebar ul{flex-direction:row;}.sidebar .logo,.sidebar .logout{display:none;}.main{padding-top:25px;}}
</style>
</head>
<body>
<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">Q</div>
    <ul>
        <li><a href="../" title="Home"><i class="fa fa-home"></i></a></li>
        <li><a class="active" href="subjects.php" title="Subjects"><i class="fa fa-book"></i></a></li>
        <li><a href="tests.php" title="Tests"><i class="fa fa-clipboard-list"></i></a></li>
        <li><a href="../settings.php" title="Settings"><i class="fa fa-cog"></i></a></li>
    </ul>
    <div class="logout"><a href="../../auth/logout.php" title="Logout"><i class="fa fa-sign-out-alt"></i></a></div>
</div>
<!-- Aux sidebar -->
<!-- Floating add FAB -->
<div class="add-fab" onclick="openAddModal()" title="Add Subject"><i class="fa fa-plus"></i></div>
<!-- MAIN content -->
<div class="main">
    <div class="header-glass">
        <h1>Your Subjects</h1>
    </div>
    <?php if(!empty($success_msg)): ?><div class="message"><?= $success_msg ?></div><?php endif ?>
    <?php if(!empty($error_msg)): ?><div class="message error"><?= $error_msg ?></div><?php endif ?>
    <div class="subjects-section">
        <div class="subject-cards-wrap">
            <?php if($subjects): foreach($subjects as $idx=>$sb):
                $col = $subjectColors[$idx%count($subjectColors)];
                $iconLetter = strtoupper($sb['subject_name'][0]);
            ?>
                <div class="subject-card" style="border-left-color: <?= $col ?>;">
                    <div class="subj-badge" style="background:<?= $col ?>">
                        <?= $iconLetter ?>
                    </div>
                    <div class="subject-title"><?= htmlspecialchars($sb['subject_name']) ?></div>
                    <div class="chapters-info"><?= $sb['chapter_count'] ?> chapter<?= $sb['chapter_count']==1?'':'s' ?></div>
                    <div class="card-actions">
                        <a href="subject/view_subject.php?subject_id=<?= $sb['subject_id'] ?>" class="cbtn view"><i class="fa fa-eye"></i> View</a>
                        <button type="button" class="cbtn edit" onclick="openEditModal(<?= $sb['subject_id'] ?>,'<?= htmlspecialchars(addslashes($sb['subject_name'])) ?>')"><i class="fa fa-edit"></i></button>
                        <a href="subject/delete_subject.php?subject_id=<?= $sb['subject_id'] ?>" class="cbtn del" onclick="return confirm('Are you sure you want to delete this subject?');"><i class="fa fa-trash"></i></a>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div style="opacity:.88;text-align:center;font-size:15px;color:#b5abe5;margin:20px auto 0 auto">
                    <i class="fa fa-inbox"></i> No subjects found.<br>
                </div>
            <?php endif ?>
        </div>
    </div>
    <!-- Add Subject Modal -->
    <div id="addModal" class="modal"><div class="modal-content">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-plus-circle"></i> Add Subject</span>
            <button class="modal-close" onclick="closeAddModal()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" style="margin:0;">
                <label for="add_subject_name">Subject Name</label>
                <input type="text" id="add_subject_name" name="subject_name" placeholder="Enter subject name" required>
                <div class="modal-footer">
                    <button type="submit" name="add_subject" class="cbtn view">Add</button>
                </div>
            </form>
        </div>
    </div></div>
    <!-- Modal for Edit Subject -->
    <div id="editModal" class="modal"><div class="modal-content">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-edit"></i> Edit Subject</span>
            <button class="modal-close" onclick="closeEditModal()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editSubjectForm" method="POST" action="subject/edit_subject.php">
                <input type="hidden" id="subject_id" name="subject_id">
                <label for="edit_subject_name">Subject Name</label>
                <input type="text" id="edit_subject_name" name="subject_name" required>
                <div class="modal-footer">
                    <button type="submit" class="cbtn view">Save</button>
                </div>
            </form>
        </div>
    </div></div>
</div>
<script>
function openAddModal(){
    document.getElementById('addModal').style.display='flex';
    setTimeout(()=>{document.getElementById('add_subject_name').focus()},180);
}
function closeAddModal(){
    document.getElementById('addModal').style.display='none';
}
function openEditModal(id,name){
    document.getElementById('subject_id').value=id;
    document.getElementById('edit_subject_name').value=name;
    document.getElementById('editModal').style.display='flex';
}
function closeEditModal(){
    document.getElementById('editModal').style.display='none';
}
window.onclick = function(event) {
    var add = document.getElementById('addModal');
    var edit = document.getElementById('editModal');
    if (event.target === add) closeAddModal();
    if (event.target === edit) closeEditModal();
}
</script>
</body>
</html>
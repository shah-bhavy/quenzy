<?php
session_start();
require '../../../../database/db.php'; // Include the DB connection

// Ensure user is logged in and is a teacher
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../../auth/login/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmtU = $mysqli->prepare("SELECT name FROM users WHERE id=?");
$stmtU->bind_param("i", $user_id);
$stmtU->execute(); $stmtU->bind_result($userName); $stmtU->fetch(); $stmtU->close();
$profileLetter = strtoupper($userName[0]);

$subject_id = $_GET['subject_id'];
$stmt = $mysqli->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
$stmt->bind_param("i", $subject_id); $stmt->execute();
$stmt->bind_result($subject_name); $stmt->fetch(); $stmt->close();

// --- MODIFIED QUERY TO FETCH UPLOADED FILE PATH ---
$chapters_stmt = $mysqli->prepare("
    SELECT 
        c.chapter_id, 
        c.chapter_name, 
        (SELECT file_path FROM uploads u WHERE u.chapter_id = c.chapter_id ORDER BY uploaded_at DESC LIMIT 1) AS uploaded_file_path
    FROM chapters c
    WHERE c.subject_id = ?
");
$chapters_stmt->bind_param("i", $subject_id);
$chapters_stmt->execute();
$chapters_result = $chapters_stmt->get_result();
$chapters = $chapters_result->fetch_all(MYSQLI_ASSOC);
$chapters_stmt->close();

$notifications = [
    ["type"=>"reminder","text"=>"Don't forget to add questions!","time"=>"Today"],
    ["type"=>"newstudent","text"=>"Priya joined this class","time"=>"1d ago"],
    ["type"=>"report","text"=>"Update progress in $subject_name","time"=>"2h ago"],
];
$currentDate = date("F j, Y");
$currentDay = date("l");
$chapterColors = ["#7bcfff","#a996fd","#feaf50","#fab1b8","#7bffc6","#ffc878","#9ce0ff", "#ffd7b5"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($subject_name); ?> - Chapters</title>
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
    .header-glass h1{font-size:1.12rem;margin:0 0 2px 0;}
    /* Chapters as Cards */
    .chapters-section { margin:18px 0 0 0;}
    .chapter-cards-wrap {
        display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:26px 19px; margin:0;
    }
    .chapter-card {
        background:var(--glass);position:relative;
        border-radius:15px;box-shadow:0 5px 19px #a7a3f825;
        min-height:86px;padding:22px 18px 15px 19px;display:flex;flex-direction:column;align-items:flex-start;overflow:hidden;
        border-left:7px solid #efeaff;transition:box-shadow .17s,transform .17s;
    }
    .chapter-card:hover{box-shadow:0 8px 24px #7b61ff28;transform:translateY(-2.5px) scale(1.016);}
    .chapter-card .ch-badge{
        width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:18.5px;border-radius:9px;margin-bottom:13px;color:#fff;font-weight:700;box-shadow:0 2px 9px #e8e1f522;
    }
    .chapter-card .chapter-title{font-size:16.6px;font-weight:600;color:#432a55;margin-bottom:8px;text-shadow:0 1px 4px #fff9;}
    .chapter-card .card-actions{display:flex; gap:8px;flex-wrap:wrap;}
    /* Modified .cbtn for icon-only buttons */
    .cbtn{
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
    /* New style for view button */
    .cbtn.view-file{background:#e0f7fa;color:#17a2b8;}

    /* Back button style */
    .back-btn-icon { /* New class for the back button */
        background:var(--primary);
        color:#fff;
        border:none;
        border-radius:7px;
        width:32px; /* Fixed width for icon buttons */
        height:32px; /* Fixed height for icon buttons */
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:14px;
        transition:.13s;
        cursor:pointer;
        text-decoration: none; /* Remove underline for anchor */
    }
    .back-btn-icon:hover {
        background:#593eda;
    }

    /* Floating Add Button */
    .add-fab{
        background:#7b61ff;color:#fff;box-shadow:0 4px 30px #7b61ff22;
        border-radius:50%;width:54px;height:54px;display:flex;align-items:center;justify-content:center;
        font-size:28px;position:fixed;top:23px;right:6vw;z-index:22;transition:.13s;cursor:pointer;
    }
    .add-fab:hover{background:#593eda;}
    /* Modal */
    .modal{display:none;position:fixed;backdrop-filter:blur(10px);z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.32);justify-content:center;align-items:center;}
    .modal-content{background:var(--glass);box-shadow:0 9px 35px #7b61ff23;padding:23px 33px 20px 31px;border-radius:15px;width:96vw;max-width:360px;min-width:220px;}
    .modal-header{display:flex;justify-content:space-between;align-items:center;}
    .modal-title{font-size:17px;font-weight:700;color:var(--primary);}
    .modal-close{background:transparent;border:none;font-size:1.7em;color:#c45c90;font-weight:800;line-height:1;cursor:pointer;padding:0 3px;}
    .modal-body label{display:block;font-size:13.7px;margin-bottom:6px;text-align:left;}
    .modal-body input,
    .modal-body input[type=file]{width:96%;border:1px solid #ddd;padding:10px 9px;border-radius:6px;font-size:14.5px;margin-bottom:10px;}
    .modal-footer{display:flex;justify-content:flex-end;gap:7px;}
    .close-btn{background:#fff0f3;color:#d40020;padding:5px 12px;cursor:pointer;border:none;border-radius:6px;font-weight:600;}
    .modal button[type=submit]{background:var(--primary);color:#fff;}
    progress{width:95%;margin:10px auto 1px auto;height:8px;border-radius:5px;}
    #statusText{font-size:13px;margin:6px auto;}
    /* Message banners */
    .message{padding:11px 0 10px 0;background:rgba(123,97,255,.16);color:#43249a;text-align:center;margin-bottom:12px;font-size:15px;border-radius:7px;}
    .error{background:rgba(240,67,86,.22);color:#b22031;}
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
        <li><a href="../../settings.php" title="Settings"><i class="fa fa-cog"></i></a></li>
    </ul>
    <div class="logout"><a href="../../auth/logout.php" title="Logout"><i class="fa fa-sign-out-alt"></i></a></div>
</div>
<div class="add-fab" onclick="openAddChapterModal()" title="Add Chapter"><i class="fa fa-plus"></i></div>
<div class="main">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="header-glass">
        <h1><?= htmlspecialchars($subject_name); ?> - Chapters</h1>
        <a href="../subjects.php" class="back-btn-icon" title="Back to Subjects"><i class="fa fa-arrow-left"></i></a>
    </div>
    <div class="chapters-section">
        <div class="chapter-cards-wrap">
            <?php if($chapters): foreach($chapters as $idx=>$ch):
                $col = $chapterColors[$idx%count($chapterColors)];
                $iconLetter = strtoupper($ch['chapter_name'][0]);
            ?>
                <div class="chapter-card" style="border-left-color: <?= $col ?>;">
                    <div class="ch-badge" style="background:<?= $col ?>"><?= $iconLetter ?></div>
                    <div class="chapter-title"><?= htmlspecialchars($ch['chapter_name']) ?></div>
                    <div class="card-actions">
                        <button class="cbtn edit" onclick="openEditModal('<?= $ch['chapter_id']; ?>', '<?= htmlspecialchars(addslashes($ch['chapter_name'])) ?>')" title="Edit Chapter"><i class="fa fa-edit"></i></button>
                        <a href="delete_chapter.php?chapter_id=<?= $ch['chapter_id']; ?>&subject_id=<?= $subject_id; ?>" class="cbtn del" onclick="return confirm('Are you sure you want to delete this chapter?')" title="Delete Chapter"><i class="fa fa-trash"></i></a>
                        <button class="cbtn upload" onclick="openUploadModal('<?= $ch['chapter_id']; ?>')" title="Upload Document"><i class="fa fa-upload"></i></button>
                        <?php if (!empty($ch['uploaded_file_path'])): ?>
                            <a href="<?= htmlspecialchars($ch['uploaded_file_path']); ?>" target="_blank" class="cbtn view-file" title="View Uploaded File"><i class="fa fa-eye"></i></a>
                        <?php else: ?>
                            <button class="cbtn view-file" disabled title="No File Uploaded"><i class="fa fa-eye-slash"></i></button>
                        <?php endif; ?>
                        <a href="view_questions.php?chapter_id=<?= $ch['chapter_id']; ?>" class="cbtn questions" title="View Questions"><i class="fa fa-question-circle"></i></a>
                    </div>
                </div>
            <?php endforeach; else: ?>
            <div style="opacity:.88;text-align:center;font-size:15px;color:#b5abe5;margin:20px auto 0 auto">
                <i class="fa fa-inbox"></i> No chapters found.<br>
            </div>
            <?php endif ?>
        </div>
    </div>

    <div id="addChapterModal" class="modal"><div class="modal-content">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-plus-circle"></i> Add Chapter</span>
            <button class="modal-close" onclick="closeAddChapterModal()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="add_chapter.php">
                <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                <label for="add_chapter_name">Chapter Name</label>
                <input type="text" id="add_chapter_name" name="chapter_name" placeholder="Enter chapter name" required>
                <div class="modal-footer"><button type="submit" class="cbtn questions">Add</button></div>
            </form>
        </div>
    </div></div>
    <div id="editChapterModal" class="modal"><div class="modal-content">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-edit"></i> Edit Chapter</span>
            <button class="modal-close" onclick="closeEditModal()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="edit_chapter.php">
                <input type="hidden" id="chapter_id" name="chapter_id">
                <input type="hidden" name="subject_id" value="<?= $subject_id; ?>">
                <label for="chapter_name">Chapter Name</label>
                <input type="text" id="chapter_name" name="chapter_name" required>
                <div class="modal-footer"><button type="submit" name="edit_chapter" class="cbtn questions">Save</button></div>
            </form>
        </div>
    </div></div>
    <div id="uploadModal" class="modal"><div class="modal-content">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-upload"></i> Upload PDF</span>
            <button class="modal-close" onclick="closeUploadModal()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="uploadForm" method="post" action="upload.php" enctype="multipart/form-data">
                <input type="hidden" id="upload_chapter_id" name="chapter_id">
                
                <p style="font-size:13px;color:#555;margin-bottom:10px;">
                    Please upload a PDF file for only one chapter. <br>10 pages is the most viable option. <br>Ensure the PDF is well-formatted for optimal question generation.</p>

                <label>PDF File</label>
                <input type="file" name="file" id="file" accept="application/pdf" required />
                <progress id="uploadProgress" value="0" max="100"></progress>
                <div id="statusText"></div>
                <div class="modal-footer"><button type="submit" class="cbtn upload">Upload</button></div>
            </form>
        </div>
    </div></div>
</div>
<script>
// Chapter modals logic
function openAddChapterModal(){
    document.getElementById('addChapterModal').style.display='flex';
    setTimeout(()=>{document.getElementById('add_chapter_name').focus()},180);
}
function closeAddChapterModal(){document.getElementById('addChapterModal').style.display='none';}
function openEditModal(chapterId,chapterName){
    document.getElementById('editChapterModal').style.display='flex';
    document.getElementById('chapter_id').value=chapterId;
    document.getElementById('chapter_name').value=chapterName;
}
function closeEditModal(){document.getElementById('editChapterModal').style.display='none';}
function openUploadModal(chapterId){
    document.getElementById('uploadModal').style.display='flex';
    document.getElementById('upload_chapter_id').value=chapterId;
    document.getElementById('file').value=null;
    document.getElementById('statusText').innerText="";
    document.getElementById('uploadProgress').value=0;
}
function closeUploadModal(){document.getElementById('uploadModal').style.display='none';}
window.onclick = function(event) {
    ['addChapterModal','editChapterModal','uploadModal'].forEach(function(id){
        var modal = document.getElementById(id);
        if (event.target === modal) modal.style.display = 'none';
    });
}
// UPLOAD AJAX
document.addEventListener("DOMContentLoaded", function(){
    var uploadForm = document.getElementById("uploadForm");
    if(uploadForm) uploadForm.onsubmit=function(e){
        e.preventDefault();
        var formData = new FormData(uploadForm);
        var xhr = new XMLHttpRequest();
        xhr.open("POST","upload.php",true); // Assuming upload.php is the target for file uploads
        xhr.upload.onprogress= function(e){
            if(e.lengthComputable){
                document.getElementById('uploadProgress').value=(e.loaded/e.total)*100;
                document.getElementById('statusText').textContent='Uploading: '+Math.round((e.loaded/e.total)*100)+'%';
            }
        }
        xhr.onload=function(){
            if(xhr.status === 200){
                // Check for a specific success message from the server response
                // This assumes upload.php will echo "File uploaded and saved to database successfully!" on success
                if(xhr.responseText.includes("File uploaded and saved to database successfully!")) {
                    document.getElementById('statusText').textContent="Done! Reloading...";
                    setTimeout(()=>{ closeUploadModal(); location.reload();},1200); // Close modal and reload
                } else {
                    // If the response text doesn't contain the success message,
                    // assume it's an error message from the server.
                    document.getElementById('statusText').textContent="Error: " + xhr.responseText;
                }
            } else {
                document.getElementById('statusText').textContent="Error uploading. Server responded with status: " + xhr.status;
            }
        }
        xhr.onerror = function() {
            document.getElementById('statusText').textContent="Network Error: Could not connect to the server.";
        };
        xhr.send(formData);
    }
});
</script>
</body>
</html>
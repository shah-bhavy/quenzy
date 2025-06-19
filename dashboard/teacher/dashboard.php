<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: ../../auth/login.php") && exit();

include('../../database/db.php');
$user_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT role, name FROM users WHERE id=?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$stmt->bind_result($role, $userName); $stmt->fetch(); $stmt->close();

if ($role !== 'teacher') {
    header("Location: ../".($role==='student'?'student':'auth')."/dashboard.php");
    exit();
}

// DB DATA: Lesson(subject), Test, Question COUNT fetch
$totalLessons = $mysqli->query("SELECT COUNT(*) FROM subjects WHERE teacher_id=$user_id")->fetch_row()[0];
$totalTests   = $mysqli->query("SELECT COUNT(*) FROM tests WHERE teacher_id=$user_id")->fetch_row()[0];
$totalQues    = $mysqli->query("SELECT COUNT(*) FROM test_questions WHERE test_id IN (SELECT test_id FROM tests WHERE teacher_id=$user_id)")->fetch_row()[0];

$profileLetter = strtoupper($userName[0]);
$currentDate = date("F j, Y");
$currentDay = date("l");

// Quick notifications fetch -- simple array for demo, else fetch from DB
$notifications = [
    ["type"=>"report","text"=>"Complete your monthly report","time"=>"1h ago"],
    ["type"=>"reminder","text"=>"Class in 1hr with John","time"=>"Today"],
    ["type"=>"newstudent","text"=>"Anna joined your class","time"=>"2d ago"],
    ["type"=>"trophy","text"=>"Congrats! Top rated teacher","time"=>"June 5"],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
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
            /* Sidebar unscrollable */
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
            /* icons with no margin between */
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

        /* Sidebar unscrollable */
        .sidebar {
            overflow: visible;
        }

        /* AUX SIDEBAR */
        .aux {
            width: 270px;
            min-width: 220px;
            padding: 21px 18px 14px 18px;
            gap: 20px;
            background: var(--glass);
            backdrop-filter: blur(24px);
            box-shadow: 1.5px 0 8px #b4c0ff0e;
            display: flex;
            flex-direction: column;
            border-right: 1.5px solid #eceafc77;
        }

        .aux .profile {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 6px;
        }

        .profilepic {
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: 50%;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 2px 9px #b0a3ff13;
        }

        .profilename {
            font-size: 19px;
            font-weight: 600;
            letter-spacing: -.5px;
        }

        .aux .date {
            background: var(--glass);
            border-radius: 8px;
            padding: 7px 0;
            text-align: center;
        }

        .aux .date h4 {
            margin: 0;
            color: var(--primary);
            font-size: 13.2px;
            font-weight: 600;
        }

        .aux .date span {
            display: block;
            font-size: 17px;
            font-weight: 600;
            color: #232029;
            margin: auto;
        }

        /* Calendar */
        .calendar {
            background: var(--glass);
            border-radius: 11px;
            padding: 10px 8px 16px 8px;
            margin-bottom: 5px;
            box-shadow: 0 2px 12px #c9d4ff12;
        }

        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .calendar-header h3 {
            margin: 0;
            font-size: 12.5px;
        }

        .days,
        .dates {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .days span,
        .dates span {
            text-align: center;
            font-size: 11px;
            padding: 2px;
        }

        .dates span {
            padding: 4px 1px 4px 1px;
            cursor: pointer;
        }

        .dates span.today {
            background: var(--primary);
            color: #fff;
            border-radius: 4px;
            font-weight: 700;
        }

        /* Scrollable notifications */
        .notifications {
            background: var(--glass);
            border-radius: 11px;
            box-shadow: 0 1px 9px #d8ceff20;
            padding: 11px 10px 7px 14px;
            max-height: 175px;
            overflow: auto;
            display: flex;
            flex-direction: column;
        }

        .notifications::-webkit-scrollbar {
            width: 4px;
            background: transparent;
        }

        .notifications h3 {
            margin: 0 0 7px 0;
            font-size: 15.3px;
            font-weight: 700;
            letter-spacing: -.5px;
        }

        .notilist {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .notilist li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5.5px;
            padding: 6px 7px;
            border-radius: 7px;
            font-size: 12.7px;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.18);
            transition: background 0.15s;
        }

        .notilist li:last-child {
            margin-bottom: 3px;
        }

        .iconNf {
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12.5px;
            font-weight: 600;
        }

        .nf-report {
            background: #ddd7ff;
            color: #7B61FF;
        }

        .nf-reminder {
            background: #e7edff;
            color: #39399d;
        }

        .nf-newstudent {
            background: #d4fbeb;
            color: #0f9f6e;
        }

        .nf-trophy {
            background: #ffe2aa;
            color: #87640c;
        }

        .nf-txt {
            flex: 1;
        }

        .nf-txt span {
            font-weight: 700;
        }

        .nf-time {
            font-size: 10.3px;
            color: #8a89a3;
            padding-left: 7px;
        }

        .view-all-btn {
            background: var(--primary);
            color: #fff;
            font-size: 10.5px;
            border: none;
            margin: 4px auto 1.5px auto;
            border-radius: 6px;
            padding: 2.7px 13px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 0 7px #dccfff05;
            display: block;
            outline: none;
            transition: background 0.13s;
        }

        .view-all-btn:hover {
            background: #593eda;
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

        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 14px;
        }

        .kpi-card {
            background: var(--kpi-glass);
            border: 1.5px solid var(--kpi-border);
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 20px #a7a3f820;
            padding: 18px 19px;
            min-width: 0;
            backdrop-filter: blur(6px);
            transition: box-shadow .15s, transform .13s;
        }

        .kpi-card:hover {
            transform: translateY(-3.5px) scale(1.013);
            box-shadow: 0 6px 20px #ad95fa12;
        }

        .kpi-icon {
            font-size: 20.5px;
            width: 41px;
            height: 41px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #fff;
        }

        .kpi-lessons {
            background: #a996fd;
        }

        .kpi-tests {
            background: #62ceb7;
        }

        .kpi-questions {
            background: #feaf50;
        }

        .kpi-info h4 {
            margin: 0 0 2px 0;
            font-size: 11.3px;
            color: #8e93b1;
            font-weight: 500;
        }

        .kpi-info p {
            margin: 0;
            font-size: 22.5px;
            font-weight: 700;
            color: #303038;
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
            }
        }
    </style>
</head>

<body>
    <!-- 1st SIDEBAR -->
    <div class="sidebar">
        <div class="logo">Q</div>
        <ul>
            <li><a class="active" href="dashboard.php" title="Home"><i class="fa fa-home"></i></a></li>
            <li><a href="#" title="Schedule"><i class="fa fa-calendar-alt"></i></a></li>
            <li><a href="#" title="Tests"><i class="fa fa-clipboard-list"></i></a></li>
            <li><a href="#" title="Settings"><i class="fa fa-cog"></i></a></li>
        </ul>
        <div class="logout"><a href="../../auth/logout.php" title="Logout"><i class="fa fa-sign-out-alt"></i></a></div>
    </div>
    <!-- 2nd SIDEBAR -->
    <div class="aux">
        <div class="profile">
            <div class="profilepic">
                <?php echo $profileLetter; ?>
            </div>
            <div>
                <div class="profilename">
                    <?php echo htmlspecialchars($userName); ?>
                </div>
                <div style="font-size:11px;">Teacher</div>
            </div>
        </div>
        <div class="date">
            <h4>
                <?php echo $currentDate; ?>
            </h4>
            <span>
                <?php echo $currentDay; ?>
            </span>
        </div>
        <div class="calendar">
            <div class="calendar-header">
                <button id="cPrev"
                    style="background:none;border:none;cursor:pointer;font-size:13px;color:#555;">&lt;</button>
                <h3 id="cMonth"></h3>
                <button id="cNext"
                    style="background:none;border:none;cursor:pointer;font-size:13px;color:#555;">&gt;</button>
            </div>
            <div class="days">
                <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
            </div>
            <div class="dates" id="cDates"></div>
        </div>
        <div class="notifications">
            <h3>Notifications</h3>
            <ul class="notilist">
                <?php foreach ($notifications as $nf): 
                $icons = [
                    "report"=>["fa-file-alt","nf-report"],
                    "reminder"=>["fa-clock","nf-reminder"],
                    "newstudent"=>["fa-user-plus","nf-newstudent"],
                    "trophy"=>["fa-trophy","nf-trophy"],
                ]; $icon = $icons[$nf["type"]];
            ?>
                <li>
                    <div class="iconNf <?php echo $icon[1]; ?>">
                        <i class="fa <?php echo $icon[0]; ?>"></i>
                    </div>
                    <div class="nf-txt">
                        <?php echo htmlspecialchars($nf['text']); ?>
                    </div>
                    <div class="nf-time">
                        <?php echo htmlspecialchars($nf['time']); ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <button class="view-all-btn">View All</button>
        </div>
    </div>
    <!-- MAIN -->
    <div class="main">
        <div class="header-glass">
            <div>
                <h1>Welcome,
                    <?php echo htmlspecialchars($userName); ?>!
                </h1>
                <p style="margin-top:7px;font-size:16.5px;font-style:italic;color:#aaa;">“If opportunity doesn’t knock,
                    build a door.” <b>- Milton Berle</b></p>
            </div>
            <img src="https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=facearea&w=90&h=90&facepad=2.5"
                style="border-radius:50%;width:52px;margin-left:12px;box-shadow:0 3px 15px #947eff37;">
        </div>
        <div class="kpi-row">
            <div class="kpi-card" onclick="location.href='tabs/subjects.php'" style="cursor:pointer;">
                <div class="kpi-icon kpi-lessons"><i class="fa fa-book"></i></div>
                <div class="kpi-info" onclick=>
                    <h4>Subjects</h4>
                    <p>
                        <?php echo $totalLessons; ?>
                    </p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon kpi-tests"><i class="fa fa-clipboard-list"></i></div>
                <div class="kpi-info">
                    <h4>Tests</h4>
                    <p>
                        <?php echo $totalTests; ?>
                    </p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon kpi-questions"><i class="fa fa-question-circle"></i></div>
                <div class="kpi-info">
                    <h4>Questions</h4>
                    <p>
                        <?php echo $totalQues; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Glassmorphism calendar -- partially dynamic, keeps code small
        const cMonthEl = document.getElementById('cMonth');
        const cDatesEl = document.getElementById('cDates');
        let today = new Date(), m = today.getMonth(), y = today.getFullYear();
        const mn = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        function updateCal() {
            cMonthEl.textContent = mn[m] + " " + y;
            let fd = new Date(y, m, 1).getDay() || 7, dim = new Date(y, m + 1, 0).getDate();
            let d = 1, html = '';
            for (let i = 1; i < fd; i++) html += '<span></span>';
            for (; d <= dim; d++) {
                let isT = d === today.getDate() && m === today.getMonth() && y === today.getFullYear();
                html += `<span class="${isT ? 'today' : ''}">${d}</span>`;
            }
            cDatesEl.innerHTML = html;
        }
        document.getElementById('cPrev').onclick = () => { m--; if (m < 0) { y--; m = 11; } updateCal(); };
        document.getElementById('cNext').onclick = () => { m++; if (m > 11) { y++; m = 0; } updateCal(); };
        updateCal();
    </script>
</body>

</html>
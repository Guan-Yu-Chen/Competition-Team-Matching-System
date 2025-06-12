<?php
require 'db_connect.php';
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = $_POST['tid'];
    $applicant = $_SESSION['user_id'];
    $applicationID = $tid . $applicant;

    // 先檢查是否已經申請過
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Applicationlist WHERE TeamID = ? AND ApplicantID = ?");
    $stmt->execute([$tid, $applicant]);
    if ($stmt->fetchColumn() > 0) {
        $success = "<span class='text-danger'>您已經申請過這個隊伍！</span>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO Applicationlist (ApplicationID, TeamID, ApplicantID) VALUES (?, ?, ?)");
        $stmt->execute([$applicationID, $tid, $applicant]);
        $success = "申請已提交";
    }
}

$stmt = $pdo->prepare("SELECT tr.Team AS TID, t.Team_Name, t.Leader
                       FROM TeamRecruitment tr
                       JOIN Team t ON tr.Team = t.TID");
$stmt->execute();
$recruitments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user_id = $_SESSION['user_id'];
// 取得目前所有隊伍（我有參加且未離開）
$stmt = $pdo->prepare("SELECT t.TID, t.Team_Name, tmh.Join_Date
                       FROM TeamMembershipHistory tmh
                       JOIN Team t ON tmh.Team = t.TID
                       WHERE tmh.Member = ? AND tmh.Leave_Date IS NULL");
$stmt->execute([$user_id]);
$my_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得目前所有隊伍（只要隊名和TID，for sidebar）
$sidebar_teams = $my_teams;

// 取得所有隊長名稱與技能
$team_info = [];
foreach ($recruitments as $rec) {
    // 隊長名稱
    $stmt = $pdo->prepare("SELECT Name FROM User WHERE Account = ?");
    $stmt->execute([$rec['Leader']]);
    $leader_name = $stmt->fetchColumn();

    // 需要技能
    $stmt = $pdo->prepare("SELECT Skill FROM TeamRequireSkill WHERE Team = ?");
    $stmt->execute([$rec['TID']]);
    $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $team_info[$rec['TID']] = [
        'leader' => $leader_name,
        'skills' => $skills
    ];
}

// 取得目前選中的隊伍ID
$TID = $_GET['TID'] ?? null;
$page_mode = 'list'; // 這裡僅用於 sidebar 樣式
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>申請加入隊伍</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .sidebar-sticky { min-height: 100vh; }
        .sidebar .nav-link.active { font-weight: bold; color: #4e54c8 !important; }
        .sidebar .nav-link { cursor: pointer; }
        .sidebar .team-list { display: block; padding-left: 1.5em; }
        .modal-bg {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.3);
            justify-content: center;
            align-items: center;
        }
        .modal-bg.active { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 2rem 2.5rem;
            min-width: 320px;
            max-width: 95vw;
            box-shadow: 0 8px 32px #4e54c822;
            position: relative;
        }
        .modal-close {
            position: absolute;
            right: 1.2rem;
            top: 1.2rem;
            font-size: 1.5rem;
            color: #888;
            cursor: pointer;
        }
        .modal-box h5 { margin-bottom: 1.2rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">學生: <?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 bg-light sidebar">
                <div class="sidebar-sticky d-flex flex-column" style="height: 100%;">
                    <ul class="nav flex-column flex-grow-1">
                        <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
                        <li class="nav-item"><a class="nav-link" href="update_profile.php">個人檔案</a></li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_team.php">我的隊伍</a>
                            <ul class="team-list" id="teamList">
                                <?php foreach ($sidebar_teams as $team): ?>
                                    <li>
                                        <a class="nav-link<?php if ($TID == $team['TID']) echo ' active'; ?>" href="my_team.php?TID=<?php echo $team['TID']; ?>">
                                            <?php echo htmlspecialchars($team['Team_Name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="create_team.php">創建隊伍</a></li>
                        <li class="nav-item"><a class="nav-link active" href="apply_team.php">申請入隊</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_invitations.php">管理訊息</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_history.php">組隊紀錄</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_ratings.php">查看評價</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>申請加入隊伍</h2>
                <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>隊伍名稱</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recruitments as $rec): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rec['Team_Name']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="tid" value="<?php echo htmlspecialchars($rec['TID']); ?>">
                                        <button type="submit" class="btn btn-success btn-sm">申請</button>
                                    </form>
                                    <button type="button"
                                        class="btn btn-primary btn-sm view-team-btn"
                                        data-leader="<?php echo htmlspecialchars($team_info[$rec['TID']]['leader']); ?>"
                                        data-skills="<?php echo htmlspecialchars(implode(', ', $team_info[$rec['TID']]['skills'])); ?>"
                                        data-teamname="<?php echo htmlspecialchars($rec['Team_Name']); ?>"
                                    >查看</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="student.php" class="btn btn-secondary">返回</a>
            </main>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-bg" id="teamModal">
        <div class="modal-box">
            <span class="modal-close" id="modalClose">&times;</span>
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.view-team-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const teamName = btn.getAttribute('data-teamname');
                const leader = btn.getAttribute('data-leader');
                const skills = btn.getAttribute('data-skills');
                let html = `<h5>隊伍名稱：${teamName}</h5>`;
                html += `<div><strong>隊長：</strong>${leader}</div>`;
                html += `<div class="mt-2"><strong>隊伍要求技能：</strong> ${skills || '無'}</div>`;
                document.getElementById('modalContent').innerHTML = html;
                document.getElementById('teamModal').classList.add('active');
            });
        });
        document.getElementById('modalClose').onclick = function() {
            document.getElementById('teamModal').classList.remove('active');
        };
        document.getElementById('teamModal').onclick = function(e) {
            if (e.target === this) this.classList.remove('active');
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
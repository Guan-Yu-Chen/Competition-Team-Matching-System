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
    $applicant_id = $_POST['applicant_id'];
    $action = $_POST['action'];

    if ($action === 'accept') {
        $stmt = $pdo->prepare("UPDATE TeamMembershipHistory SET Join_Date = CURDATE() WHERE Team = ? AND Member = ? AND Join_Date IS NULL");
        $stmt->execute([$tid, $applicant_id]);
        $success = "已接受申請";
    } else {
        $stmt = $pdo->prepare("DELETE FROM TeamMembershipHistory WHERE Team = ? AND Member = ? AND Join_Date IS NULL");
        $stmt->execute([$tid, $applicant_id]);
        $success = "已拒絕申請";
    }
}

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

// 查詢收到的邀請（含邀請人名字）
$stmt = $pdo->prepare("SELECT il.TeamID AS TID, t.Team_Name, il.inviterID, u.Name AS inviter_name, il.inviteeID
                       FROM invitationlist il
                       JOIN Team t ON il.TeamID = t.TID
                       JOIN User u ON il.inviterID = u.Account
                       WHERE il.inviteeID = ?");
$stmt->execute([$_SESSION['user_id']]);
$invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 查詢我的申請
$stmt = $pdo->prepare("SELECT al.TeamID AS TID, t.Team_Name, t.Leader
                       FROM applicationlist al
                       JOIN Team t ON al.TeamID = t.TID
                       WHERE al.ApplicantID = ?");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得所有隊伍資訊（for modal）
$team_info = [];
foreach ($invitations as $inv) {
    // 隊長名稱
    $stmt = $pdo->prepare("SELECT Name FROM User WHERE Account = (SELECT Leader FROM Team WHERE TID = ?)");
    $stmt->execute([$inv['TID']]);
    $leader_name = $stmt->fetchColumn();

    // 需要技能
    $stmt = $pdo->prepare("SELECT Skill FROM TeamRequireSkill WHERE Team = ?");
    $stmt->execute([$inv['TID']]);
    $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 目前隊伍成員（未離開）
    $stmt = $pdo->prepare("SELECT u.Name FROM TeamMembershipHistory m JOIN User u ON m.Member = u.Account WHERE m.Team = ? AND m.Leave_Date IS NULL");
    $stmt->execute([$inv['TID']]);
    $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $team_info[$inv['TID']] = [
        'team_name' => $inv['Team_Name'],
        'leader' => $leader_name,
        'skills' => $skills,
        'members' => $members
    ];
}
foreach ($applications as $app) {
    // 隊長名稱
    $stmt = $pdo->prepare("SELECT Name FROM User WHERE Account = ?");
    $stmt->execute([$app['Leader']]);
    $leader_name = $stmt->fetchColumn();

    // 需要技能
    $stmt = $pdo->prepare("SELECT Skill FROM TeamRequireSkill WHERE Team = ?");
    $stmt->execute([$app['TID']]);
    $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $team_info[$app['TID']] = [
        'team_name' => $app['Team_Name'],
        'leader' => $leader_name,
        'skills' => $skills,
        'members' => [] // 不顯示成員
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>管理組隊邀請</title>
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
                        <li class="nav-item"><a class="nav-link" href="apply_team.php">申請入隊</a></li>
                        <li class="nav-item"><a class="nav-link active" href="manage_invitations.php">管理訊息</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_history.php">組隊紀錄</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_ratings.php">查看評價</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>管理組隊邀請與申請</h2>
                <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                <h3>收到的邀請</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>隊伍名稱</th>
                            <th>邀請人</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invitations as $inv): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inv['Team_Name']); ?></td>
                                <td><?php echo htmlspecialchars($inv['inviter_name']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="tid" value="<?php echo htmlspecialchars($inv['TID']); ?>">
                                        <input type="hidden" name="applicant_id" value="<?php echo htmlspecialchars($inv['inviteeID']); ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-success btn-sm">接受</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="tid" value="<?php echo htmlspecialchars($inv['TID']); ?>">
                                        <input type="hidden" name="applicant_id" value="<?php echo htmlspecialchars($inv['inviteeID']); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm">拒絕</button>
                                    </form>
                                    <button type="button"
                                        class="btn btn-primary btn-sm view-team-btn"
                                        data-tid="<?php echo htmlspecialchars($inv['TID']); ?>"
                                        data-type="invite"
                                    >查看隊伍資訊</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <h3>我的申請</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>隊伍名稱</th>
                            <th>狀態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['Team_Name']); ?></td>
                                <td>待審核</td>
                                <td>
                                    <button type="button"
                                        class="btn btn-primary btn-sm view-team-btn"
                                        data-tid="<?php echo htmlspecialchars($app['TID']); ?>"
                                        data-type="apply"
                                    >查看隊伍資訊</button>
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
        // 將 PHP 陣列轉成 JS 物件
        const teamInfo = <?php echo json_encode($team_info, JSON_UNESCAPED_UNICODE); ?>;
        document.querySelectorAll('.view-team-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const tid = btn.getAttribute('data-tid');
                const type = btn.getAttribute('data-type');
                const info = teamInfo[tid];
                let html = `<h5>隊伍名稱：${info.team_name}</h5>`;
                html += `<div><strong>隊長：</strong>${info.leader}</div>`;
                html += `<div class="mt-2"><strong>隊伍要求技能：</strong> ${info.skills.length ? info.skills.join(', ') : '無'}</div>`;
                if (type === 'invite') {
                    html += `<div class="mt-2"><strong>目前隊伍成員：</strong><ul>`;
                    info.members.forEach(m => {
                        html += `<li>${m}</li>`;
                    });
                    html += `</ul></div>`;
                }
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
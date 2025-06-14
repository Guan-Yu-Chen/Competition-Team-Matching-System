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

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// 處理邀請接受/拒絕
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action_type = $_POST['action_type'];
        if ($action_type === 'accept_invite') {
            // 接受邀請
            $tid = $_POST['tid'];
            $invitee_id = $_SESSION['user_id'];
            // 1. invitationlist status 改 accepted
            $stmt = $pdo->prepare("UPDATE invitationlist SET status = 'accepted' WHERE TeamID = ? AND InviteeID = ?");
            $stmt->execute([$tid, $invitee_id]);
            // 2. 新增到 TeamMembershipHistory
            $stmt = $pdo->prepare("INSERT INTO TeamMembershipHistory (Team, Member, Join_Date) VALUES (?, ?, CURDATE())");
            $stmt->execute([$tid, $invitee_id]);
            $success = "已接受邀請";
        } elseif ($action_type === 'reject_invite') {
            // 拒絕邀請
            $tid = $_POST['tid'];
            $invitee_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("UPDATE invitationlist SET status = 'rejected' WHERE TeamID = ? AND InviteeID = ?");
            $stmt->execute([$tid, $invitee_id]);
            $success = "已拒絕邀請";
        } elseif ($action_type === 'withdraw_application' || $action_type === 'delete_application') {
            // 撤回申請或刪除訊息
            $tid = $_POST['tid'];
            $applicant_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("DELETE FROM applicationlist WHERE TeamID = ? AND ApplicantID = ?");
            $stmt->execute([$tid, $applicant_id]);
            $success = "已刪除申請";
        }
    }
}

// 取得目前所有隊伍（我有參加且未離開）
$stmt = $pdo->prepare("SELECT t.TID, t.Team_Name, tmh.Join_Date
                       FROM TeamMembershipHistory tmh
                       JOIN Team t ON tmh.Team = t.TID
                       WHERE tmh.Member = ? AND tmh.Leave_Date IS NULL");
$stmt->execute([$user_id]);
$my_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得目前所有隊伍（只要隊名和TID，for sidebar）
$sidebar_teams = $my_teams;

// 查詢收到的邀請（只顯示自己且 status = pending）
$stmt = $pdo->prepare("SELECT il.TeamID AS TID, t.Team_Name, il.InviterID, u.Name AS inviter_name, il.InviteeID
                       FROM invitationlist il
                       JOIN Team t ON il.TeamID = t.TID
                       JOIN User u ON il.InviterID = u.Account
                       WHERE il.InviteeID = ? AND il.status = 'pending'");
$stmt->execute([$user_id]);
$invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 查詢我的申請
$stmt = $pdo->prepare("SELECT al.TeamID AS TID, t.Team_Name, t.Leader, al.status
                       FROM applicationlist al
                       JOIN Team t ON al.TeamID = t.TID
                       WHERE al.ApplicantID = ?");
$stmt->execute([$user_id]);
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

    $team_info[$inv['TID']] = [
        'team_name' => $inv['Team_Name'],
        'leader' => $leader_name,
        'skills' => $skills
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
        'skills' => $skills
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
        .d-flex.gap-2 > * { margin-right: 0.5rem; }
        .d-flex.gap-2 > *:last-child { margin-right: 0; }
        .status-accepted { color: #198754; font-weight: bold; }
        .status-rejected { color: #dc3545; font-weight: bold; }
        .status-pending { color: #ffc107; font-weight: bold; }
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
                                        <a class="nav-link" href="my_team.php?TID=<?php echo $team['TID']; ?>">
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
                <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
                <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

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
                                    <div class="d-flex gap-2">
                                        <button type="button"
                                            class="btn btn-success btn-sm accept-invite-btn"
                                            data-tid="<?php echo htmlspecialchars($inv['TID']); ?>"
                                            data-teamname="<?php echo htmlspecialchars($inv['Team_Name']); ?>"
                                        >接受</button>
                                        <button type="button"
                                            class="btn btn-danger btn-sm reject-invite-btn"
                                            data-tid="<?php echo htmlspecialchars($inv['TID']); ?>"
                                            data-teamname="<?php echo htmlspecialchars($inv['Team_Name']); ?>"
                                        >拒絕</button>
                                        <button type="button"
                                            class="btn btn-primary btn-sm view-team-btn"
                                            data-tid="<?php echo htmlspecialchars($inv['TID']); ?>"
                                        >查看隊伍</button>
                                    </div>
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
                                <td>
                                    <?php
                                        if ($app['status'] === 'pending') {
                                            echo '<span class="status-pending">待審核</span>';
                                        } elseif ($app['status'] === 'accepted') {
                                            echo '<span class="status-accepted">已接受申請</span>';
                                        } elseif ($app['status'] === 'rejected') {
                                            echo '<span class="status-rejected">已拒絕申請</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button"
                                            class="btn btn-primary btn-sm view-team-btn"
                                            data-tid="<?php echo htmlspecialchars($app['TID']); ?>"
                                        >查看隊伍</button>
                                        <?php if ($app['status'] === 'pending'): ?>
                                            <button type="button"
                                                class="btn btn-warning btn-sm withdraw-app-btn"
                                                data-tid="<?php echo htmlspecialchars($app['TID']); ?>"
                                                data-teamname="<?php echo htmlspecialchars($app['Team_Name']); ?>"
                                            >撤回申請</button>
                                        <?php else: ?>
                                            <button type="button"
                                                class="btn btn-danger btn-sm delete-app-btn"
                                                data-tid="<?php echo htmlspecialchars($app['TID']); ?>"
                                                data-teamname="<?php echo htmlspecialchars($app['Team_Name']); ?>"
                                                data-status="<?php echo htmlspecialchars($app['status']); ?>"
                                            >刪除訊息</button>
                                        <?php endif; ?>
                                    </div>
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
    <!-- 動作確認 Modal -->
    <div class="modal-bg" id="actionModal">
        <div class="modal-box">
            <span class="modal-close" id="actionModalClose">&times;</span>
            <div id="actionModalContent"></div>
            <form id="actionForm" method="POST" style="display:none;">
                <input type="hidden" name="action_type" id="action_type">
                <input type="hidden" name="tid" id="action_tid">
            </form>
            <div class="mt-3 d-flex justify-content-end gap-2" id="actionModalBtns"></div>
        </div>
    </div>

    <script>
        // 隊伍資訊
        const teamInfo = <?php echo json_encode($team_info, JSON_UNESCAPED_UNICODE); ?>;

        // 查看隊伍
        document.querySelectorAll('.view-team-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const tid = btn.getAttribute('data-tid');
                const info = teamInfo[tid];
                let html = `<h5>隊伍名稱：${info.team_name}</h5>`;
                html += `<div><strong>隊長：</strong>${info.leader}</div>`;
                html += `<div class="mt-2"><strong>隊伍要求技能：</strong> ${info.skills.length ? info.skills.join(', ') : '無'}</div>`;
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

        // 接受邀請
        document.querySelectorAll('.accept-invite-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const tid = btn.getAttribute('data-tid');
                const teamName = btn.getAttribute('data-teamname');
                document.getElementById('actionModalContent').innerHTML =
                    `是否接受 <strong>${teamName}</strong> 的邀請？`;
                document.getElementById('action_type').value = 'accept_invite';
                document.getElementById('action_tid').value = tid;
                document.getElementById('actionModalBtns').innerHTML =
                    `<button type="button" class="btn btn-secondary btn-sm" id="actionCancelBtn">取消</button>
                     <button type="button" class="btn btn-success btn-sm" id="actionConfirmBtn">接受</button>`;
                document.getElementById('actionModal').classList.add('active');
                document.getElementById('actionConfirmBtn').onclick = function() {
                    document.getElementById('actionForm').submit();
                };
                document.getElementById('actionCancelBtn').onclick = function() {
                    document.getElementById('actionModal').classList.remove('active');
                };
            });
        });

        // 拒絕邀請
        document.querySelectorAll('.reject-invite-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const tid = btn.getAttribute('data-tid');
                const teamName = btn.getAttribute('data-teamname');
                document.getElementById('actionModalContent').innerHTML =
                    `是否拒絕 <strong>${teamName}</strong> 的邀請？`;
                document.getElementById('action_type').value = 'reject_invite';
                document.getElementById('action_tid').value = tid;
                document.getElementById('actionModalBtns').innerHTML =
                    `<button type="button" class="btn btn-secondary btn-sm" id="actionCancelBtn">取消</button>
                     <button type="button" class="btn btn-danger btn-sm" id="actionConfirmBtn">拒絕</button>`;
                document.getElementById('actionModal').classList.add('active');
                document.getElementById('actionConfirmBtn').onclick = function() {
                    document.getElementById('actionForm').submit();
                };
                document.getElementById('actionCancelBtn').onclick = function() {
                    document.getElementById('actionModal').classList.remove('active');
                };
            });
        });

        // 撤回申請
        document.querySelectorAll('.withdraw-app-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const tid = btn.getAttribute('data-tid');
                const teamName = btn.getAttribute('data-teamname');
                document.getElementById('actionModalContent').innerHTML =
                    `是否撤回申請 <strong>${teamName}</strong>？`;
                document.getElementById('action_type').value = 'withdraw_application';
                document.getElementById('action_tid').value = tid;
                document.getElementById('actionModalBtns').innerHTML =
                    `<button type="button" class="btn btn-secondary btn-sm" id="actionCancelBtn">取消</button>
                     <button type="button" class="btn btn-warning btn-sm" id="actionConfirmBtn">撤回申請</button>`;
                document.getElementById('actionModal').classList.add('active');
                document.getElementById('actionConfirmBtn').onclick = function() {
                    document.getElementById('actionForm').submit();
                };
                document.getElementById('actionCancelBtn').onclick = function() {
                    document.getElementById('actionModal').classList.remove('active');
                };
            });
        });

        // 刪除訊息
        document.querySelectorAll('.delete-app-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const tid = btn.getAttribute('data-tid');
                const teamName = btn.getAttribute('data-teamname');
                const status = btn.getAttribute('data-status');
                let statusText = '';
                if (status === 'accepted') statusText = '<span class="status-accepted">已接受申請</span>';
                else if (status === 'rejected') statusText = '<span class="status-rejected">已拒絕申請</span>';
                document.getElementById('actionModalContent').innerHTML =
                    `是否刪除 <strong>${teamName}</strong> 的申請訊息？<br>狀態：${statusText}`;
                document.getElementById('action_type').value = 'delete_application';
                document.getElementById('action_tid').value = tid;
                document.getElementById('actionModalBtns').innerHTML =
                    `<button type="button" class="btn btn-secondary btn-sm" id="actionCancelBtn">取消</button>
                     <button type="button" class="btn btn-danger btn-sm" id="actionConfirmBtn">刪除</button>`;
                document.getElementById('actionModal').classList.add('active');
                document.getElementById('actionConfirmBtn').onclick = function() {
                    document.getElementById('actionForm').submit();
                };
                document.getElementById('actionCancelBtn').onclick = function() {
                    document.getElementById('actionModal').classList.remove('active');
                };
            });
        });

        document.getElementById('actionModalClose').onclick = function() {
            document.getElementById('actionModal').classList.remove('active');
        };
        document.getElementById('actionModal').onclick = function(e) {
            if (e.target === this) this.classList.remove('active');
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

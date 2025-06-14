<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$user_name = $_SESSION['user_name'];

// 取得目前所有隊伍（我有參加且未離開）
$stmt = $pdo->prepare("SELECT t.TID, t.Team_Name, tmh.Join_Date
                       FROM TeamMembershipHistory tmh
                       JOIN Team t ON tmh.Team = t.TID
                       WHERE tmh.Member = ? AND tmh.Leave_Date IS NULL");
$stmt->execute([$user_id]);
$my_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得目前所有隊伍（只要隊名和TID，for sidebar）
$sidebar_teams = $my_teams;

// 處理 TID 參數
$TID = $_GET['TID'] ?? null;
$page_mode = 'list'; // list, detail
$team_detail = null;
$team_members = [];
$team_skills = [];
$team_applicants = [];
$team_competitions = [];
$team_access = true;

if ($TID) {
    // 檢查是否有權限
    $stmt = $pdo->prepare("SELECT t.TID, t.Team_Name, t.Leader FROM Team t
                           JOIN TeamMembershipHistory tmh ON t.TID = tmh.Team
                           WHERE t.TID = ? AND tmh.Member = ? AND tmh.Leave_Date IS NULL");
    $stmt->execute([$TID, $user_id]);
    $team_detail = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$team_detail) {
        $team_access = false;
        $page_mode = 'detail';
    } else {
        $page_mode = 'detail';
        // 技能
        $stmt = $pdo->prepare("SELECT Skill FROM TeamRequireSkill WHERE Team = ?");
        $stmt->execute([$TID]);
        $team_skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 成員
        $stmt = $pdo->prepare("SELECT u.Account, u.Name, tmh.Join_Date, t.Leader
                               FROM TeamMembershipHistory tmh
                               JOIN User u ON tmh.Member = u.Account
                               JOIN Team t ON tmh.Team = t.TID
                               WHERE tmh.Team = ? AND tmh.Leave_Date IS NULL
                               ORDER BY (u.Account = t.Leader) DESC, tmh.Join_Date ASC");
        $stmt->execute([$TID]);
        $team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 入隊申請者
        $stmt = $pdo->prepare("SELECT a.ApplicantID, u.Name
                               FROM Applicationlist a
                               JOIN User u ON a.ApplicantID = u.Account
                               WHERE a.TeamID = ? AND a.status = 'pending'");
        $stmt->execute([$TID]);
        $team_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 取得邀請名單
        $team_id = $TID; // 假設用 GET 傳遞隊伍ID
        $stmt = $pdo->prepare("SELECT i.*, inviter.Name AS inviter_name, invitee.Name AS invitee_name
                                FROM invitationlist i
                                JOIN user inviter ON i.InviterID = inviter.Account
                                JOIN user invitee ON i.InviteeID = invitee.Account
                                WHERE i.TeamID = ?");
        $stmt->execute([$team_id]);
        $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 參加的競賽
        $stmt = $pdo->prepare("SELECT c.CID, c.Name
                               FROM Participation p
                               JOIN Competition c ON p.Competition = c.CID
                               WHERE p.Team = ?");
        $stmt->execute([$TID]);
        $team_competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 處理退出隊伍
        if (isset($_POST['leave_team'])) {
            $stmt = $pdo->prepare("UPDATE TeamMembershipHistory SET Leave_Date = CURDATE() WHERE Team = ? AND Member = ? AND Leave_Date IS NULL");
            $stmt->execute([$TID, $user_id]);
            header("Location: my_team.php");
            exit;
        }
        // 處理報名新競賽
        if (isset($_POST['join_competition'])) {
            $cid = $_POST['competition_id'];
            $stmt = $pdo->prepare("INSERT INTO Participation (Team, Competition) VALUES (?, ?)");
            $stmt->execute([$TID, $cid]);
            header("Location: my_team.php?TID=$TID");
            exit;
        }
    }
}

// 處理 AJAX 請求
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    // 查看個人資料
    if ($_GET['ajax'] === 'profile' && isset($_GET['uid'])) {
        $uid = $_GET['uid'];
        $stmt = $pdo->prepare("SELECT Name FROM User WHERE Account = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT Skill FROM skill WHERE SID = ?");
        $stmt->execute([$uid]);
        $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT Title FROM portfolio WHERE SID = ?");
        $stmt->execute([$uid]);
        $portfolio = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT Award_Title FROM award WHERE SID = ?");
        $stmt->execute([$uid]);
        $awards = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'name' => $user['Name'] ?? '',
            'skills' => $skills,
            'portfolio' => $portfolio,
            'awards' => $awards
        ]);
        exit;
    }
    // 查看評論
    if ($_GET['ajax'] === 'ratings' && isset($_GET['uid'])) {
        $uid = $_GET['uid'];
        $stmt = $pdo->prepare("SELECT Reviewer, Rating, Comment FROM TeamRatings WHERE Reviewee = ?");
        $stmt->execute([$uid]);
        $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ratings as &$r) {
            $stmt2 = $pdo->prepare("SELECT Name FROM User WHERE Account = ?");
            $stmt2->execute([$r['Reviewer']]);
            $r['ReviewerName'] = $stmt2->fetchColumn();
        }
        echo json_encode($ratings);
        exit;
    }
    // 新增/編輯評論
    if ($_GET['ajax'] === 'edit-rating' && isset($_GET['uid'])) {
        $uid = $_GET['uid'];
        $stmt = $pdo->prepare("SELECT Rating, Comment FROM TeamRatings WHERE Reviewer = ? AND Reviewee = ?");
        $stmt->execute([$user_id, $uid]);
        $rating = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($rating ?: []);
        exit;
    }
    // 儲存/刪除評論
    if ($_GET['ajax'] === 'save-rating' && isset($_POST['uid'])) {
        $uid = $_POST['uid'];
        $rating = $_POST['rating'];
        $comment = $_POST['comment'];
        // 檢查是否已存在
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM TeamRatings WHERE Reviewer = ? AND Reviewee = ?");
        $stmt->execute([$user_id, $uid]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $pdo->prepare("UPDATE TeamRatings SET Rating = ?, Comment = ? WHERE Reviewer = ? AND Reviewee = ?");
            $stmt->execute([$rating, $comment, $user_id, $uid]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO TeamRatings (Team, Reviewer, Reviewee, Rating, Comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$TID, $user_id, $uid, $rating, $comment]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
    if ($_GET['ajax'] === 'delete-rating' && isset($_POST['uid'])) {
        $uid = $_POST['uid'];
        $stmt = $pdo->prepare("DELETE FROM TeamRatings WHERE Reviewer = ? AND Reviewee = ?");
        $stmt->execute([$user_id, $uid]);
        echo json_encode(['success' => true]);
        exit;
    }
    // 查看競賽詳情
    if ($_GET['ajax'] === 'competition' && isset($_GET['cid'])) {
        $cid = $_GET['cid'];
        $stmt = $pdo->prepare("SELECT * FROM Competition WHERE CID = ?");
        $stmt->execute([$cid]);
        $comp = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($comp ?: []);
        exit;
    }
    // 退出競賽
    if ($_GET['ajax'] === 'leave-competition' && isset($_POST['cid']) && isset($_POST['tid'])) {
        $cid = $_POST['cid'];
        $tid = $_POST['tid'];
        $stmt = $pdo->prepare("DELETE FROM Participation WHERE Competition = ? AND Team = ?");
        $stmt->execute([$cid, $tid]);
        echo json_encode(['success' => true]);
        exit;
    }
    // 報名新競賽
    if ($_GET['ajax'] === 'join-competition' && isset($_POST['cid']) && isset($_POST['tid'])) {
        $cid = $_POST['cid'];
        $tid = $_POST['tid'];
        // 檢查是否存在
        $stmt = $pdo->prepare("SELECT * FROM Competition WHERE CID = ?");
        $stmt->execute([$cid]);
        $comp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$comp) {
            echo json_encode(['error' => '找不到此競賽']);
            exit;
        }
        // 檢查是否已經參加
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Participation WHERE Team = ? AND Competition = ?");
        $stmt->execute([$tid, $cid]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['already' => true]);
            exit;
        }
        // 新增參賽
        $stmt = $pdo->prepare("INSERT INTO Participation (Team, Competition) VALUES (?, ?)");
        $stmt->execute([$tid, $cid]);
        echo json_encode(['success' => true]);
        exit;
    }
    // 退出隊伍
    if ($_GET['ajax'] === 'leave-team' && isset($_POST['tid'])) {
        $tid = $_POST['tid'];
        $stmt = $pdo->prepare("UPDATE TeamMembershipHistory SET Leave_Date = CURDATE() WHERE Team = ? AND Member = ? AND Leave_Date IS NULL");
        $stmt->execute([$tid, $user_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    // 編輯技能需求
    if ($_GET['ajax'] === 'edit-skill' && isset($_POST['tid'])) {
        $tid = $_POST['tid'];
        $skills = trim($_POST['skills'] ?? '');

        // 刪除原本技能
        $stmt = $pdo->prepare("DELETE FROM TeamRequireSkill WHERE Team = ?");
        $stmt->execute([$tid]);

        // 新增新技能
        if ($skills !== '') {
            foreach (explode(',', $skills) as $skill) {
                $skill = trim($skill);
                if ($skill !== '') {
                    $stmt2 = $pdo->prepare("INSERT INTO TeamRequireSkill (Team, Skill) VALUES (?, ?)");
                    $stmt2->execute([$tid, $skill]);
                }
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }
    // 處理 AJAX：邀請功能
    if ($_GET['ajax'] === 'invite' && isset($_POST['tid']) && isset($_POST['sid'])) {
        $tid = $_POST['tid'];
        $sid = $_POST['sid'];
        $inviter = $user_id;

        // 1. 檢查是否已經在隊伍內
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM TeamMembershipHistory WHERE Team = ? AND Member = ? AND Leave_Date IS NULL");
        $stmt->execute([$tid, $sid]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'reason' => 'already_in_team']);
            exit;
        }

        // 2. 檢查是否已經邀請過（pending 狀態）
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invitationlist WHERE TeamID = ? AND InviteeID = ? AND status = 'pending'");
        $stmt->execute([$tid, $sid]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'reason' => 'already_invited']);
            exit;
        }

        // 3. 查詢對方名字
        $stmt = $pdo->prepare("SELECT Name FROM User WHERE Account = ?");
        $stmt->execute([$sid]);
        $invitee = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. 新增邀請
        $stmt = $pdo->prepare("INSERT INTO invitationlist (TeamID, InviterID, InviteeID, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$tid, $inviter, $sid]);
        echo json_encode(['success' => true, 'invitee_name' => $invitee ? $invitee['Name'] : $sid]);
        exit;
    }

    // 處理 AJAX：撤回邀請
    if ($_GET['ajax'] === 'withdraw-invitation') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM invitationlist WHERE TeamID = ? AND InviterID = ? AND InviteeID = ? AND status = 'pending'");
        $ok = $stmt->execute([$data['TeamID'], $data['InviterID'], $data['InviteeID']]);
        echo json_encode([
            'success' => $ok,
            'rowCount' => $stmt->rowCount()
        ]);
        exit;
    }

    // 處理 AJAX：刪除邀請
    if ($_GET['ajax'] === 'delete-invitation') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM invitationlist WHERE TeamID = ? AND InviterID = ? AND InviteeID = ?");
        $ok = $stmt->execute([$data['TeamID'], $data['InviterID'], $data['InviteeID']]);
        echo json_encode(['success' => $ok]);
        exit;
    }

    // 處理 AJAX：接受申請者
    if ($_GET['ajax'] === 'update-applicant-status' && isset($_POST['tid']) && isset($_POST['applicant_id']) && isset($_POST['status'])) {
        $tid = $_POST['tid'];
        $applicant_id = $_POST['applicant_id'];
        $status = $_POST['status'];
        if (!in_array($status, ['accepted', 'rejected'])) {
            echo json_encode(['success' => false]);
            exit;
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE Applicationlist SET status = ? WHERE TeamID = ? AND ApplicantID = ?");
        $ok = $stmt->execute([$status, $tid, $applicant_id]);
        if ($ok && $status === 'accepted') {
            // 新增到 TeamMembershipHistory
            $stmt2 = $pdo->prepare("INSERT INTO TeamMembershipHistory (Team, Member, Join_Date) VALUES (?, ?, CURDATE())");
            $ok = $stmt2->execute([$tid, $applicant_id]);
        }
        if ($ok) {
            $pdo->commit();
        } else {
            $pdo->rollBack();
        }
        echo json_encode(['success' => $ok]);
        exit;
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>我的隊伍</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">學生: <?php echo htmlspecialchars($user_name); ?></a>
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
                            <a class="nav-link<?php if ($page_mode === 'list') echo ' active'; ?>" href="my_team.php">我的隊伍</a>
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
                        <li class="nav-item"><a class="nav-link" href="manage_invitations.php">管理訊息</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_history.php">組隊紀錄</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_ratings.php">查看評價</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <!-- Main Content -->
            <main class="col-md-10 px-4">
                <?php if ($page_mode === 'list'): ?>
                    <h2>我的隊伍</h2>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>隊伍名稱</th>
                                <th>加入日期</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_teams as $team): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($team['Team_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($team['Join_Date']); ?></td>
                                    <td>
                                        <a class="btn btn-primary btn-sm" href="my_team.php?TID=<?php echo $team['TID']; ?>">查看隊伍</a>
                                        <button type="button" class="btn btn-danger btn-sm open-modal" data-modal-type="leave-team" data-team-id="<?php echo $team['TID']; ?>" data-team-name="<?php echo htmlspecialchars($team['Team_Name']); ?>">退出隊伍</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($my_teams)): ?>
                                <tr><td colspan="3" class="text-muted">目前沒有所在隊伍</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php elseif ($page_mode === 'detail'): ?>
                    <?php if (!$team_access): ?>
                        <div class="alert alert-danger">你沒有權限存取這個隊伍</div>
                    <?php else: ?>
                        <h2>隊伍資訊：<?php echo htmlspecialchars($team_detail['Team_Name']); ?></h2>
                        <div class="mb-3"><strong>隊伍技能需求：</strong>
                            <?php echo $team_skills ? htmlspecialchars(implode(', ', $team_skills)) : '無'; ?>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2 open-modal" id="editSkillBtn"
                                data-modal-type="edit-skill"
                                data-team-id="<?php echo $TID; ?>"
                                data-skills="<?php echo htmlspecialchars(implode(', ', $team_skills)); ?>">
                                編輯
                            </button>
                        </div>
                        <div class="mb-3">
                            <div class="mb-3 d-flex align-items-center">
                            <strong>成員：</strong>
                            <button type="button" class="btn btn-warning btn-sm open-modal ms-2"
                                data-modal-type="invite"
                                data-team-id="<?php echo $TID; ?>">
                                邀請
                            </button>
                        </div>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>隊員名稱</th>
                                        <th>加入日期</th>
                                        <th>身分</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($team_members as $member): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['Name']); ?></td>
                                            <td><?php echo htmlspecialchars($member['Join_Date']); ?></td>
                                            <td>
                                                <?php echo ($member['Account'] == $team_detail['Leader']) ? '<span class="text-primary">隊長</span>' : '隊員'; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-info btn-sm open-modal" data-modal-type="profile" data-uid="<?php echo $member['Account']; ?>">查看個人資料</button>
                                                <button type="button" class="btn btn-secondary btn-sm open-modal" data-modal-type="ratings" data-uid="<?php echo $member['Account']; ?>">查看評論</button>
                                                <button type="button" class="btn btn-success btn-sm open-modal" data-modal-type="edit-rating" data-uid="<?php echo $member['Account']; ?>">新增/編輯評論</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mb-3">
                            <strong>入隊申請者：</strong>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>申請者名稱</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($team_applicants as $applicant): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($applicant['Name']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-info btn-sm open-modal" data-modal-type="profile" data-uid="<?php echo $applicant['ApplicantID']; ?>">查看個人資料</button>
                                                <button type="button" class="btn btn-secondary btn-sm open-modal" data-modal-type="ratings" data-uid="<?php echo $applicant['ApplicantID']; ?>">查看評論</button>
                                                <button type="button" class="btn btn-success btn-sm open-modal" data-modal-type="accept-applicant" data-applicant-id="<?php echo $applicant['ApplicantID']; ?>" data-team-id="<?php echo $TID; ?>">接受</button>
                                                <button type="button" class="btn btn-danger btn-sm open-modal" data-modal-type="reject-applicant" data-applicant-id="<?php echo $applicant['ApplicantID']; ?>" data-team-id="<?php echo $TID; ?>">拒絕</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($team_applicants)): ?>
                                        <tr><td colspan="2" class="text-muted">目前沒有申請者</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <strong>邀請中的成員</strong>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>被邀請者</th>
                                        <th>邀請者</th>
                                        <th>狀態</th>
                                        <th>操作</th>
                                    </tr>
                                    <?php if (empty($invitations)): ?>
                                        <tr>
                                            <td colspan="4" class="text-muted">目前沒有邀請中的成員</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($invitations as $inv): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($inv['invitee_name']) ?></td>
                                            <td><?= htmlspecialchars($inv['inviter_name']) ?></td>
                                            <td>
                                                <?php
                                                    if ($inv['status'] === 'pending') {
                                                        echo '<span class="status-pending">待接受</span>';
                                                    } elseif ($inv['status'] === 'accepted') {
                                                        echo '<span class="status-accepted">已接受</span>';
                                                    } elseif ($inv['status'] === 'rejected') {
                                                        echo '<span class="status-rejected">已拒絕</span>';
                                                    } else {
                                                        echo htmlspecialchars($inv['status']);
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                            <!-- 查看個人資料（用 modal） -->
                                            <button type="button"
                                                class="btn btn-info btn-sm open-modal"
                                                data-modal-type="profile"
                                                data-uid="<?= htmlspecialchars($inv['InviteeID']) ?>">
                                                查看個人資料
                                            </button>
                                            <?php if ($inv['status'] == 'pending'): ?>
                                                <!-- 撤回邀請（跳 modal 確認） -->
                                                <button type="button"
                                                    class="btn btn-danger btn-sm open-modal"
                                                    data-modal-type="withdraw-invitation"
                                                    data-team-id="<?= htmlspecialchars($inv['TeamID']) ?>"
                                                    data-inviter-id="<?= htmlspecialchars($inv['InviterID']) ?>"
                                                    data-invitee-id="<?= htmlspecialchars($inv['InviteeID']) ?>">
                                                    撤回邀請
                                                </button>
                                            <?php else: ?>
                                                <!-- 刪除（跳 modal 確認） -->
                                                <button type="button"
                                                    class="btn btn-danger btn-sm open-modal"
                                                    data-modal-type="delete-invitation"
                                                    data-team-id="<?= htmlspecialchars($inv['TeamID']) ?>"
                                                    data-inviter-id="<?= htmlspecialchars($inv['InviterID']) ?>"
                                                    data-invitee-id="<?= htmlspecialchars($inv['InviteeID']) ?>">
                                                    刪除
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </table>
                        </div>
                        <div class="mb-3">
                            <strong>參加的競賽：</strong>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>競賽名稱</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($team_competitions as $comp): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($comp['Name']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-info btn-sm open-modal" data-modal-type="competition" data-cid="<?php echo $comp['CID']; ?>">查看競賽詳情</button>
                                                <button type="button" class="btn btn-danger btn-sm open-modal" data-modal-type="leave-competition" data-cid="<?php echo $comp['CID']; ?>" data-team-id="<?php echo $TID; ?>" data-comp-name="<?php echo htmlspecialchars($comp['Name']); ?>">退出競賽</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($team_competitions)): ?>
                                        <tr><td colspan="2" class="text-muted">尚未參加任何競賽</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <div class="input-group mt-2">
                                <input type="text" id="joinCompInput" class="form-control" placeholder="輸入競賽ID報名">
                                <button type="button" class="btn btn-primary" id="joinCompBtn" data-team-id="<?php echo $TID; ?>">報名新競賽</button>
                            </div>
                        </div>
                        <form method="post">
                            <button type="button" class="btn btn-danger open-modal" data-modal-type="leave-team" data-team-id="<?php echo $TID; ?>" data-team-name="<?php echo htmlspecialchars($team_detail['Team_Name']); ?>">退出隊伍</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal-bg" id="mainModal">
        <div class="modal-box">
            <span class="modal-close" id="modalClose">&times;</span>
            <div id="modalContent"></div>
        </div>
    </div>
    <!-- 撤回邀請 Modal -->
    <div class="modal-bg" id="withdrawInvitationModal" style="display:none;">
        <div class="modal-box">
            <span class="modal-close" id="withdrawInvitationModalClose">&times;</span>
            <div id="withdrawInvitationModalContent">
                <div class="mb-2">確定要撤回這個邀請嗎？</div>
                <button type="button" class="btn btn-danger" id="confirmWithdrawInvitation">確認撤回</button>
                <button type="button" class="btn btn-secondary" id="cancelWithdrawInvitation">取消</button>
            </div>
        </div>
    </div>
    <!-- 刪除邀請 Modal -->
    <div class="modal-bg" id="deleteInvitationModal" style="display:none;">
        <div class="modal-box">
            <span class="modal-close" id="deleteInvitationModalClose">&times;</span>
            <div id="deleteInvitationModalContent">
                <p>你確定要刪除此邀請嗎？</p>
                <div class="text-end">
                    <button type="button" class="btn btn-secondary btn-sm" id="cancelDeleteInvitation">取消</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteInvitation">刪除</button>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/my_team.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
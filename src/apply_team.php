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

// 取得目前所有隊伍（我有參加且未離開）
$stmt = $pdo->prepare("SELECT Team FROM TeamMembershipHistory WHERE Member = ? AND Leave_Date IS NULL");
$stmt->execute([$user_id]);
$my_team_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 取得已申請過的隊伍
$stmt = $pdo->prepare("SELECT TeamID FROM Applicationlist WHERE ApplicantID = ?");
$stmt->execute([$user_id]);
$applied_team_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 合併排除條件
$exclude_ids = array_merge($my_team_ids, $applied_team_ids);

// 取得自己有的技能
$stmt = $pdo->prepare("SELECT Skill FROM skill WHERE SID = ?");
$stmt->execute([$user_id]);
$my_skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 處理技能篩選
$filter_skills = [];
if (isset($_GET['skill']) && is_array($_GET['skill'])) {
    $filter_skills = array_filter($_GET['skill'], function($s) use ($my_skills) {
        return in_array($s, $my_skills);
    });
}

// 取得所有正在招募的隊伍，排除自己目前已在的隊伍和已申請的隊伍
$recruitments = [];
if (count($exclude_ids) > 0) {
    $in = str_repeat('?,', count($exclude_ids) - 1) . '?';
    $base_sql = "SELECT tr.Team AS TID, t.Team_Name, t.Leader
            FROM TeamRecruitment tr
            JOIN Team t ON tr.Team = t.TID
            WHERE tr.Team NOT IN ($in)";
    $params = $exclude_ids;
} else {
    $base_sql = "SELECT tr.Team AS TID, t.Team_Name, t.Leader
            FROM TeamRecruitment tr
            JOIN Team t ON tr.Team = t.TID";
    $params = [];
}

// 如果有篩選技能
if (!empty($filter_skills)) {
    // 只顯示隊伍要求技能有包含勾選技能的隊伍
    $skill_conditions = [];
    foreach ($filter_skills as $i => $skill) {
        $skill_conditions[] = "EXISTS (SELECT 1 FROM teamrequireskill trs WHERE trs.Team = tr.Team AND trs.Skill = ?)";
        $params[] = $skill;
    }
    $base_sql .= (strpos($base_sql, 'WHERE') !== false ? " AND " : " WHERE ") . "(" . implode(" OR ", $skill_conditions) . ")";
}

$stmt = $pdo->prepare($base_sql);
$stmt->execute($params);
$recruitments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得所有隊長名稱與技能
$team_info = [];
foreach ($recruitments as $rec) {
    // 隊長名稱
    $stmt = $pdo->prepare("SELECT Name FROM User WHERE Account = ?");
    $stmt->execute([$rec['Leader']]);
    $leader_name = $stmt->fetchColumn();

    // 需要技能
    $stmt = $pdo->prepare("SELECT Skill FROM teamrequireskill WHERE Team = ?");
    $stmt->execute([$rec['TID']]);
    $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $team_info[$rec['TID']] = [
        'leader' => $leader_name,
        'skills' => $skills
    ];
}

// 取得目前所有隊伍（只要隊名和TID，for sidebar）
$stmt = $pdo->prepare("SELECT t.TID, t.Team_Name, tmh.Join_Date
                       FROM TeamMembershipHistory tmh
                       JOIN Team t ON tmh.Team = t.TID
                       WHERE tmh.Member = ? AND tmh.Leave_Date IS NULL");
$stmt->execute([$user_id]);
$sidebar_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得目前選中的隊伍ID
$TID = $_GET['TID'] ?? null;
$page_mode = 'list'; // 這裡僅用於 sidebar 樣式

// 申請入隊處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_tid'])) {
    $tid = $_POST['apply_tid'];
    $applicant = $_SESSION['user_id'];

    // 檢查是否已經申請過
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Applicationlist WHERE TeamID = ? AND ApplicantID = ?");
    $stmt->execute([$tid, $applicant]);
    if ($stmt->fetchColumn() > 0) {
        $success = "<span class='text-danger'>您已經申請過這個隊伍！</span>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO Applicationlist (TeamID, ApplicantID, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$tid, $applicant]);
        header("Location: manage_invitations.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>申請入隊</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .navbar-brand { color: #8f94fb; }

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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>招募中的隊伍</h2>
                    <!-- 篩選條件按鈕 -->
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterSkillModal">篩選條件</button>
                </div>
                <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>隊伍名稱</th>
                            <th>隊長</th>
                            <th>要求技能</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recruitments) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">目前沒有符合條件的隊伍</td>
                            </tr>
                        <?php else: ?>
                        <?php foreach ($recruitments as $rec): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rec['Team_Name']); ?></td>
                                <td><?php echo htmlspecialchars($team_info[$rec['TID']]['leader']); ?></td>
                                <td><?php echo htmlspecialchars(implode(', ', $team_info[$rec['TID']]['skills'])); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="apply_tid" value="<?php echo htmlspecialchars($rec['TID']); ?>">
                                        <button type="submit" class="btn btn-success btn-sm">申請入隊</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <a href="student.php" class="btn btn-secondary">返回</a>
            </main>
        </div>
    </div>

    <!-- 技能篩選 Modal -->
    <div class="modal fade" id="filterSkillModal" tabindex="-1" aria-labelledby="filterSkillModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="GET" action="" id="filterSkillForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filterSkillModalLabel">技能篩選</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
                    </div>
                    <div class="modal-body" id="filter-skill-container">
                        <?php
                        if (empty($my_skills)) {
                            echo '<div class="text-muted">您尚未設定任何技能，請先到個人檔案新增技能。</div>';
                        } else {
                            foreach ($my_skills as $skill) {
                                $is_checked = in_array($skill, $filter_skills) ? 'checked' : '';
                                echo '
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="skill[]" value="' . htmlspecialchars($skill) . '" id="filter_skill_' . htmlspecialchars($skill) . '" ' . $is_checked . '>
                                    <label class="form-check-label" for="filter_skill_' . htmlspecialchars($skill) . '">' . htmlspecialchars($skill) . '</label>
                                </div>';
                            }
                        }
                        ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="clearSkillFilterBtn">清除</button>
                        <button type="submit" class="btn btn-primary">套用篩選</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- 技能篩選 Modal end -->

    <script>
        // 清除技能篩選
        document.getElementById('clearSkillFilterBtn').addEventListener('click', function () {
            document.querySelectorAll('#filter-skill-container input[type="checkbox"]').forEach(function (checkbox) {
                checkbox.checked = false;
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
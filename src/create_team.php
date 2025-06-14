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

// 取得目前選中的隊伍ID
$TID = $_GET['TID'] ?? null;
$page_mode = 'create_team';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name'] ?? '');
    $skills = trim($_POST['skills'] ?? '');

    if ($team_name === '') {
        $err = "隊伍名稱不得為空";
    } else {
        // 產生唯一的 TID（格式 TID001, TID002, ...）
        $tid_num = 1;
        do {
            $new_tid = sprintf('TID%03d', $tid_num);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Team WHERE TID = ?");
            $stmt->execute([$new_tid]);
            $exists = $stmt->fetchColumn() > 0;
            $tid_num++;
        } while ($exists);

        // 新增隊伍（帶入自訂 TID）
        $stmt = $pdo->prepare("INSERT INTO Team (TID, Team_Name, Leader) VALUES (?, ?, ?)");
        $stmt->execute([$new_tid, $team_name, $user_id]);

        // 新增技能
        if ($skills !== '') {
            foreach (explode(',', $skills) as $skill) {
                $skill = trim($skill);
                if ($skill !== '') {
                    $stmt2 = $pdo->prepare("INSERT INTO TeamRequireSkill (Team, Skill) VALUES (?, ?)");
                    $stmt2->execute([$new_tid, $skill]);
                }
            }
        }

        // 自己加入隊伍（Team, Member, Join_Date, Leave_Date）
        $stmt = $pdo->prepare("INSERT INTO TeamMembershipHistory (Team, Member, Join_Date, Leave_Date) VALUES (?, ?, CURDATE(), NULL)");
        $stmt->execute([$new_tid, $user_id]);

        // 新增到 TeamRecruitment
        $stmt = $pdo->prepare("INSERT INTO TeamRecruitment (Team) VALUES (?)");
        $stmt->execute([$new_tid]);

        // 立即導向，避免重複送出
        header("Location: my_team.php?TID=" . $new_tid);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>創建隊伍</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .sidebar-sticky { min-height: 100vh; }
        .sidebar .nav-link.active { font-weight: bold; color: #4e54c8 !important; }
        .sidebar .nav-link { cursor: pointer; }
        .sidebar .team-list { display: block; padding-left: 1.5em; }
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
                        <li class="nav-item"><a class="nav-link active" href="create_team.php">創建隊伍</a></li>
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
                <h2>創建隊伍</h2>
                <?php if ($err): ?>
                    <div class="alert alert-danger"><?php echo $err; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="team_name" class="form-label">隊伍名稱</label>
                        <input type="text" class="form-control" id="team_name" name="team_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="skills" class="form-label">所需技能（用逗號分隔）</label>
                        <input type="text" class="form-control" id="skills" name="skills" placeholder="例如：Python, SQL, 溝通">
                    </div>
                    <button type="submit" class="btn btn-primary">確認</button>
                    <a href="my_team.php" class="btn btn-secondary">返回</a>
                </form>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
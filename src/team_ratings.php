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
$stmt = $pdo->prepare("SELECT t.TID, t.Team_Name, tmh.Join_Date
                       FROM TeamMembershipHistory tmh
                       JOIN Team t ON tmh.Team = t.TID
                       WHERE tmh.Member = ? AND tmh.Leave_Date IS NULL");
$stmt->execute([$user_id]);
$my_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 取得目前所有隊伍（只要隊名和TID，for sidebar）
$sidebar_teams = $my_teams;

$stmt = $pdo->prepare("SELECT tr.Team AS TID, tr.Reviewer, tr.Reviewee, tr.Rating, tr.Comment, t.Team_Name, u1.Name AS Reviewer_Name, u2.Name AS Reviewee_Name
                       FROM TeamRatings tr
                       JOIN Team t ON tr.Team = t.TID
                       JOIN User u1 ON tr.Reviewer = u1.Account
                       JOIN User u2 ON tr.Reviewee = u2.Account
                       WHERE tr.Reviewer = ? OR tr.Reviewee = ?");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>隊友評價</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar-sticky { min-height: 100vh; }
        .sidebar .nav-link.active { font-weight: bold; color: #4e54c8 !important; }
        .sidebar .nav-link { cursor: pointer; }
        .sidebar .team-list { display: block; padding-left: 1.5em; }
        /* Modal 樣式 */
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
        .modal-box ul { padding-left: 1.2em; }
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
                        <li class="nav-item"><a class="nav-link" href="manage_invitations.php">管理訊息</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_history.php">組隊紀錄</a></li>
                        <li class="nav-item"><a class="nav-link active" href="team_ratings.php">查看評價</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>隊友評價</h2>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>隊伍名稱</th>
                            <th>評價者</th>
                            <th>被評價者</th>
                            <th>評分</th>
                            <th>評論</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ratings as $rating): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rating['Team_Name']); ?></td>
                                <td><?php echo htmlspecialchars($rating['Reviewer_Name']); ?></td>
                                <td><?php echo htmlspecialchars($rating['Reviewee_Name']); ?></td>
                                <td><?php echo htmlspecialchars($rating['Rating']); ?></td>
                                <td><?php echo htmlspecialchars($rating['Comment'] ?: '無'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="student.php" class="btn btn-secondary">返回</a>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
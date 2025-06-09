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
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">學生: <?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 bg-light sidebar">
                <div class="sidebar-sticky d-flex flex-column" style="height: 100%;">
                    <ul class="nav flex-column flex-grow-1">
                        <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
                        <li class="nav-item"><a class="nav-link" href="update_profile.php">個人檔案</a></li>
                        <li class="nav-item"><a class="nav-link" href="post_recruitment.php">隊伍徵求</a></li>
                        <li class="nav-item"><a class="nav-link" href="apply_team.php">申請隊伍</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_invitations.php">管理邀請</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_history.php">隊伍歷史</a></li>
                        <li class="nav-item"><a class="nav-link active" href="team_ratings.php">隊友評價</a></li>
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
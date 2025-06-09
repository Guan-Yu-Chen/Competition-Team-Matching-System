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
    $stmt = $pdo->prepare("INSERT INTO TeamMembershipHistory (Team, Member, Join_Date) VALUES (?, ?, NULL)");
    $stmt->execute([$tid, $_SESSION['user_id']]);
    $success = "申請已提交";
}

$stmt = $pdo->prepare("SELECT tr.Team AS TID, t.Team_Name
                       FROM TeamRecruitment tr
                       JOIN Team t ON tr.Team = t.TID");
$stmt->execute();
$recruitments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>申請加入隊伍</title>
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
                        <li class="nav-item"><a class="nav-link active" href="apply_team.php">申請隊伍</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_invitations.php">管理邀請</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_history.php">隊伍歷史</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_ratings.php">隊友評價</a></li>
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
                                    <form method="POST">
                                        <input type="hidden" name="tid" value="<?php echo htmlspecialchars($rec['TID']); ?>">
                                        <button type="submit" class="btn btn-success btn-sm">申請</button>
                                    </form>
                                </td>
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
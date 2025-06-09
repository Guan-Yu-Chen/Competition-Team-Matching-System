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

$stmt = $pdo->prepare("SELECT tmh.Team AS TID, tmh.Member AS Applicant, t.Team_Name
                       FROM TeamMembershipHistory tmh
                       JOIN Team t ON tmh.Team = t.TID
                       WHERE t.Leader = ? AND tmh.Join_Date IS NULL");
$stmt->execute([$_SESSION['user_id']]);
$invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT tmh.Team AS TID, t.Team_Name
                       FROM TeamMembershipHistory tmh
                       JOIN Team t ON tmh.Team = t.TID
                       WHERE tmh.Member = ? AND tmh.Join_Date IS NULL");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>管理組隊邀請</title>
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
                        <li class="nav-item"><a class="nav-link active" href="manage_invitations.php">管理邀請</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_history.php">隊伍歷史</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_ratings.php">隊友評價</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>管理組隊邀請與申請</h2>
                <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                <h3>收到的申請</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>隊伍名稱</th>
                            <th>申請人</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invitations as $inv): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inv['Team_Name']); ?></td>
                                <td><?php echo htmlspecialchars($inv['Applicant']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="tid" value="<?php echo htmlspecialchars($inv['TID']); ?>">
                                        <input type="hidden" name="applicant_id" value="<?php echo htmlspecialchars($inv['Applicant']); ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-success btn-sm">接受</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="tid" value="<?php echo htmlspecialchars($inv['TID']); ?>">
                                        <input type="hidden" name="applicant_id" value="<?php echo htmlspecialchars($inv['Applicant']); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm">拒絕</button>
                                    </form>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['Team_Name']); ?></td>
                                <td>待審核</td>
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
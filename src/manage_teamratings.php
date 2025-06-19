<?php
require 'db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// 處理刪除請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_team_review'])) {
    $team = $_POST['team'];
    $reviewer = $_POST['reviewer'];
    $reviewee = $_POST['reviewee'];

    $stmt = $pdo->prepare("DELETE FROM teamratings WHERE Team = ? AND Reviewer = ? AND Reviewee = ?");
    $stmt->execute([$team, $reviewer, $reviewee]);
    header("Location: manage_teamratings.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>隊伍評論管理（不當言論刪除）</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .navbar-brand { color: #8f94fb; }
        .navbar {
            padding-top: 0.4rem;
            padding-bottom: 0.4rem;
        }
        .sidebar-sticky { min-height: 100vh; }
        .sidebar .nav-link.active { font-weight: bold; color: #4e54c8 !important; }
        .sidebar .nav-link { cursor: pointer; }
        .sidebar .team-list { display: block; padding-left: 1.5em; }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">管理員: <?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 bg-light sidebar">
                <div class="sidebar-sticky d-flex flex-column" style="height: 100%;">
                    <ul class="nav flex-column flex-grow-1">
                        <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
                        <li class="nav-item"><a class="nav-link" href="create_competition.php">創建競賽</a></li>
                        <!-- <li class="nav-item"><a class="nav-link active" href="#competitions">競賽管理</a></li> -->
                        <li class="nav-item"><a class="nav-link active" href="manage_teamratings.php">評價管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_disputes.php">糾紛管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_blacklist.php">黑名單管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="post_announcement.php">發佈公告</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_categories.php">管理分類</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>隊伍評論管理</h2>
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>隊伍</th>
                            <th>評論者</th>
                            <th>被評論者</th>
                            <th>評分</th>
                            <th>評論內容</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM teamratings");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['Team']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['Reviewer']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['Reviewee']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['Rating']) . '</td>';
                            echo '<td>' . nl2br(htmlspecialchars($row['Comment'])) . '</td>';
                            echo '<td>
                                <form method="POST" onsubmit="return confirm(\'確定要刪除此評論嗎？\')">
                                    <input type="hidden" name="team" value="' . htmlspecialchars($row['Team']) . '">
                                    <input type="hidden" name="reviewer" value="' . htmlspecialchars($row['Reviewer']) . '">
                                    <input type="hidden" name="reviewee" value="' . htmlspecialchars($row['Reviewee']) . '">
                                    <button type="submit" name="delete_team_review" class="btn btn-sm btn-danger">刪除</button>
                                </form>
                            </td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <a href="admin.php" class="btn btn-secondary">返回管理後台</a>
            </main>
        </div>
    </div>
</body>
</html>

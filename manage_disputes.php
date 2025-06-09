<?php
require 'db_connect.php';
session_start();

// 處理登出
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>糾紛管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- 頁首 -->
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">管理員: <?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
        </div>
    </nav>

    <!-- 主要內容 -->
    <div class="container-fluid">
        <div class="row">
            <!-- 側邊欄 -->
            <nav class="col-md-2 bg-light sidebar">
                <div class="sidebar-sticky d-flex flex-column" style="height: 100%;">
                    <ul class="nav flex-column flex-grow-1">
                        <li class="nav-item"><a class="nav-link" href="index.php">首頁</a></li>
                        <li class="nav-item"><a class="nav-link" href="create_competition.php">創建競賽</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin.php">競賽管理</a></li>
                        <li class="nav-item"><a class="nav-link active" href="manage_disputes.php">糾紛管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_blacklist.php">黑名單管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="post_announcement.php">發佈公告</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>

            <!-- 動態內容 -->
            <main class="col-md-10 px-4">
                <h2>糾紛管理</h2>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>糾紛ID</th>
                            <th>申訴人</th>
                            <th>被申訴人</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT DisputeID, ComplainantID, RespondentID FROM TeamDispute WHERE AdminID = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '
                            <tr>
                                <td>' . htmlspecialchars($row['DisputeID']) . '</td>
                                <td>' . htmlspecialchars($row['ComplainantID']) . '</td>
                                <td>' . htmlspecialchars($row['RespondentID']) . '</td>
                                <td>
                                    <button class="btn btn-sm btn-primary">處理</button>
                                </td>
                            </tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <a href="admin.php" class="btn btn-secondary">返回</a>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
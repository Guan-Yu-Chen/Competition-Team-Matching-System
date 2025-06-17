<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ver0.2 修正新增公告跳轉頁面"Location: post_announcement.php"

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 處理刪除公告
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['announcement_id'])) {
        $del_id = $_POST['announcement_id'];
        $stmt = $pdo->prepare("DELETE FROM Announcement WHERE AnnouncementID = ?");
        $stmt->execute([$del_id]);
        header("Location: post_announcement.php"); // 刪除後回列表刷新
        exit;
    }
    $ann_id = uniqid('ANN');
    $title = $_POST['title'];
    $content = $_POST['content'];
    $published_by = $_SESSION['user_id'];
    $stmt = $pdo->prepare("INSERT INTO Announcement (AnnouncementID, Title, Content, Published_By) VALUES (?, ?, ?, ?)");
    $stmt->execute([$ann_id, $title, $content, $published_by]);
    header("Location: post_announcement.php"); // 刪除後回列表刷新
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>發佈公告</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .navbar-brand { color: #8f94fb; }
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
                        <li class="nav-item"><a class="nav-link" href="manage_teamratings.php">評價管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_disputes.php">糾紛管理</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_blacklist.php">黑名單管理</a></li>
                        <li class="nav-item"><a class="nav-link active" href="post_announcement.php">發佈公告</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_categories.php">管理分類</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>發佈公告</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label for="title" class="form-label">標題</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">內容</label>
                        <textarea class="form-control" id="content" name="content" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">發佈</button>
                    <a href="admin.php" class="btn btn-secondary">返回</a>
                </form>
                <?php
                    // ver0.2 抓取所有公告顯示。
                    $stmt = $pdo->query("SELECT AnnouncementID, Title, Content, Published_By FROM Announcement ORDER BY AnnouncementID DESC");
                    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="container my-5">
                    <h3>公告列表</h3>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>公告ID</th>
                                <th>標題</th>
                                <th>內容</th>
                                <th>發布者</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($announcements as $ann): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ann['AnnouncementID']); ?></td>
                                <td><?php echo htmlspecialchars($ann['Title']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($ann['Content'])); ?></td>
                                <td><?php echo htmlspecialchars($ann['Published_By']); ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('確定要刪除公告嗎？');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="announcement_id" value="<?php echo htmlspecialchars($ann['AnnouncementID']); ?>">
                                        <!-- ver0.2 新增刪除公告功能。 -->
                                        <button type="submit" class="btn btn-danger btn-sm">刪除</button>
                                    </form>
                                </td>

                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
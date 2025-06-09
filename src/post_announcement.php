<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ann_id = uniqid('ANN');
    $title = $_POST['title'];
    $content = $_POST['content'];
    $published_by = $_SESSION['user_id'];
    $stmt = $pdo->prepare("INSERT INTO Announcement (AnnouncementID, Title, Content, Published_By) VALUES (?, ?, ?, ?)");
    $stmt->execute([$ann_id, $title, $content, $published_by]);
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>發佈公告</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
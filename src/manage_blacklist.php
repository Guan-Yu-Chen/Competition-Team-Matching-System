<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = $_POST['sid'];
    $reason = $_POST['reason'] ?: null;
    $stmt = $pdo->prepare("INSERT INTO Blacklist (SID, Reason) VALUES (?, ?)");
    $stmt->execute([$sid, $reason]);
    header("Location: manage_blacklist.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>黑名單管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h2>黑名單管理</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="sid" class="form-label">學生ID</label>
                <input type="text" class="form-control" id="sid" name="sid" required>
            </div>
            <div class="mb-3">
                <label for="reason" class="form-label">原因（可選）</label>
                <textarea class="form-control" id="reason" name="reason"></textarea>
            </div>
            <button type="submit" class="btn btn-danger">加入黑名單</button>
        </form>
        <h3 class="mt-4">黑名單列表</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>學生ID</th>
                    <th>原因</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT SID, Reason FROM Blacklist");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '
                    <tr>
                        <td>' . htmlspecialchars($row['SID']) . '</td>
                        <td>' . htmlspecialchars($row['Reason'] ?? '') . '</td>
                    </tr>';
                }
                ?>
            </tbody>
        </table>
        <a href="admin.php" class="btn btn-secondary">返回</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
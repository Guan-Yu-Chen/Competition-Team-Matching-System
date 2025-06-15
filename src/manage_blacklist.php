<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ver0.1 簡短功能
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $sid = $_POST['sid'];
//     $reason = $_POST['reason'] ?: null;
//     $stmt = $pdo->prepare("INSERT INTO Blacklist (SID, Reason) VALUES (?, ?)");
//     $stmt->execute([$sid, $reason]);
//     header("Location: manage_blacklist.php");
//     exit;
// }

// ver0.2 確認黑名單SID是否存在student TABLE。其後，若SID已經在黑名單則顯示警告，否則新增至黑名單。

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['delete_sid'])) {
        $deleteSid = $_POST['delete_sid'];
        // echo "Deleting SID: " . $_POST['delete_sid'];

        $stmt = $pdo->prepare("DELETE FROM Blacklist WHERE SID = ?");
        $stmt->execute([$deleteSid]);

        header("Location: manage_blacklist.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = $_POST['sid'];
    $reason = $_POST['reason'] ?: null;

    // 檢查此 SID 是否存在於 Student 資料表
    $checkStudentStmt = $pdo->prepare("SELECT COUNT(*) FROM Student WHERE SID = ?");
    $checkStudentStmt->execute([$sid]);
    $studentExists = $checkStudentStmt->fetchColumn();

    if (!$studentExists) {
        echo "<script>alert('查無此學生，無法加入黑名單'); window.location.href = 'manage_blacklist.php';</script>";
        exit;
    }

    // 檢查此 SID 是否已在黑名單
    $checkBlacklistStmt = $pdo->prepare("SELECT COUNT(*) FROM Blacklist WHERE SID = ?");
    $checkBlacklistStmt->execute([$sid]);
    $isBlacklisted = $checkBlacklistStmt->fetchColumn();

    if ($isBlacklisted) {
        echo "<script>alert('該學生已在黑名單中'); window.location.href = 'manage_blacklist.php';</script>";
        exit;
    }

    // 加入黑名單
    $insertStmt = $pdo->prepare("INSERT INTO Blacklist (SID, Reason) VALUES (?, ?)");
    $insertStmt->execute([$sid, $reason]);

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
            <!-- ver0.2 新增黑名單刪除按鈕。 -->
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT SID, Reason FROM Blacklist");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '
                    <tr>
                        <td>' . htmlspecialchars($row['SID']) . '</td>
                        <td>' . htmlspecialchars($row['Reason'] ?? '') . '</td>
                        <td>
                            <form method="post" onsubmit="return confirm(\'確定要刪除 SID ' . htmlspecialchars($row['SID']) . ' 嗎？\');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="delete_sid" value="' . htmlspecialchars($row['SID']) . '">
                                <button type="submit">刪除</button>
                            </form>
                        </td>
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
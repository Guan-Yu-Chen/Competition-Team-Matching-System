<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>隊伍歷史</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h2>隊伍歷史</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>隊伍</th>
                    <th>加入日期</th>
                    <th>離開日期</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT Team, Join_Date, Leave_Date FROM TeamMembershipHistory WHERE Member = ?");
                $stmt->execute([$_SESSION['user_id']]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '
                    <tr>
                        <td>' . htmlspecialchars($row['Team']) . '</td>
                        <td>' . htmlspecialchars($row['Join_Date']) . '</td>
                        <td>' . htmlspecialchars($row['Leave_Date'] ?? '尚未離開') . '</td>
                    </tr>';
                }
                ?>
            </tbody>
        </table>
        <a href="student.php" class="btn btn-secondary">返回</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
require 'db_connect.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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
</head>
<body>
    <div class="container mt-4">
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
    </div>
</body>
</html>

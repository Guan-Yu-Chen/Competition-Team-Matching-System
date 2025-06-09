<?php
require 'db_connect.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = uniqid('TEAM');
    $team_name = $_POST['team_name'];
    $skills = explode(',', $_POST['skills']);
    $stmt = $pdo->prepare("INSERT INTO Team (TID, Team_Name, Leader) VALUES (?, ?, ?)");
    $stmt->execute([$tid, $team_name, $_SESSION['user_id']]);
    $stmt = $pdo->prepare("INSERT INTO TeamRecruitment (Team) VALUES (?)");
    $stmt->execute([$tid]);
    foreach ($skills as $skill) {
        $skill = trim($skill);
        if ($skill) {
            $stmt = $pdo->prepare("INSERT INTO TeamRequireSkill (Team, Skill) VALUES (?, ?)");
            $stmt->execute([$tid, $skill]);
        }
    }
    header("Location: student.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>發佈隊伍徵求</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h2>發佈隊伍徵求</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="team_name" class="form-label">隊伍名稱</label>
                <input type="text" class="form-control" id="team_name" name="team_name" required>
            </div>
            <div class="mb-3">
                <label for="skills" class="form-label">所需技能（以逗號分隔）</label>
                <input type="text" class="form-control" id="skills" name="skills" placeholder="例如: Python, SQL, C++">
            </div>
            <button type="submit" class="btn btn-success">發佈</button>
            <a href="student.php" class="btn btn-secondary">返回</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
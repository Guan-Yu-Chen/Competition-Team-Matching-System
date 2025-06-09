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
    $name = $_POST['name'];
    $skills = $_POST['skills'] ?: '';
    $awards = $_POST['awards'] ?: '';
    $portfolio = $_POST['portfolio'] ?: '';

    $stmt = $pdo->prepare("UPDATE User SET Name = ? WHERE Account = ?");
    $stmt->execute([$name, $_SESSION['user_id']]);

    $stmt = $pdo->prepare("DELETE FROM Skill WHERE SID = ?");
    $stmt->execute([$_SESSION['user_id']]);

    foreach (explode(',', $skills) as $skill) {
        $skill = trim($skill);
        if ($skill) {
            $stmt = $pdo->prepare("INSERT INTO Skill (SID, Skill) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $skill]);
        }
    }

    foreach (explode(',', $awards) as $award) {
        $award = trim($award);
        if ($award) {
            $stmt = $pdo->prepare("INSERT INTO Skill (SID, Skill) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], "Award: $award"]);
        }
    }

    foreach (explode(',', $portfolio) as $item) {
        $item = trim($item);
        if ($item) {
            $stmt = $pdo->prepare("INSERT INTO Skill (SID, Skill) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], "Portfolio: $item"]);
        }
    }
    $success = "個人檔案更新成功";
}

$stmt = $pdo->prepare("SELECT Name FROM User WHERE Account = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT Skill FROM Skill WHERE SID = ? AND Skill NOT LIKE 'Award:%' AND Skill NOT LIKE 'Portfolio:%'");
$stmt->execute([$_SESSION['user_id']]);
$skills = implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN));

$stmt = $pdo->prepare("SELECT Skill FROM Skill WHERE SID = ? AND Skill LIKE 'Award:%'");
$stmt->execute([$_SESSION['user_id']]);
$awards = implode(', ', array_map(function($s) { return substr($s['Skill'], 6); }, $stmt->fetchAll(PDO::FETCH_ASSOC)));

$stmt = $pdo->prepare("SELECT Skill FROM Skill WHERE SID = ? AND Skill LIKE 'Portfolio:%'");
$stmt->execute([$_SESSION['user_id']]);
$portfolio = implode(', ', array_map(function($s) { return substr($s['Skill'], 10); }, $stmt->fetchAll(PDO::FETCH_ASSOC)));
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>更新個人檔案</title>
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
                        <li class="nav-item"><a class="nav-link active" href="update_profile.php">個人檔案</a></li>
                        <li class="nav-item"><a class="nav-link" href="post_recruitment.php">隊伍徵求</a></li>
                        <li class="nav-item"><a class="nav-link" href="apply_team.php">申請隊伍</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_invitations.php">管理邀請</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_history.php">隊伍歷史</a></li>
                        <li class="nav-item"><a class="nav-link" href="team_ratings.php">隊友評價</a></li>
                        <li class="nav-item"><a class="nav-link logout" href="?logout=1">登出</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 px-4">
                <h2>更新個人檔案</h2>
                <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">姓名</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['Name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="skills" class="form-label">技能（以逗號分隔）</label>
                        <input type="text" class="form-control" id="skills" name="skills" value="<?php echo htmlspecialchars($skills); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="awards" class="form-label">獲獎經驗（以逗號分隔）</label>
                        <input type="text" class="form-control" id="awards" name="awards" value="<?php echo htmlspecialchars($awards); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="portfolio" class="form-label">作品集（以逗號分隔）</label>
                        <input type="text" class="form-control" id="portfolio" name="portfolio" value="<?php echo htmlspecialchars($portfolio); ?>">
                    </div>
                    <button type="submit" class="btn btn-success">更新</button>
                    <a href="student.php" class="btn btn-secondary">返回</a>
                </form>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>